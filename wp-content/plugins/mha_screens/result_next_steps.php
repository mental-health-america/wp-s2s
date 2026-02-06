<?php
/**
 * Unified Next Steps for Screen Results
 *
 * Builds a single link pool (URL includes, screen featured, result manual, demo, scored articles),
 * then splits into Featured Next Steps (top N) and Related Articles (rest + fill).
 * Use for refactor comparison and eventual replacement of display_featured_next_steps + mha_results_related_articles.
 *
 * @see AI_HELPER_SCREEN_RESULTS.md
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse answered_demos for scoring (age, LGBTQ, BIPOC, caregiver).
 *
 * @param array $answered_demos
 * @return array { age_check: string|null, user_demo_lgbtq: bool, user_demo_bipoc: bool, user_demo_caregiver: bool }
 */
function mha_next_steps_parse_demographics( $answered_demos ) {
	$user_demo_ages   = [];
	$user_demo_lgbtq  = false;
	$user_demo_bipoc  = false;
	$user_demo_caregiver = false;

	if ( empty( $answered_demos ) || ! is_array( $answered_demos ) ) {
		return [
			'age_check'          => null,
			'user_demo_lgbtq'    => false,
			'user_demo_bipoc'    => false,
			'user_demo_caregiver'=> false,
		];
	}

	foreach ( $answered_demos as $key => $value ) {
		$key_lower = strtolower( $key );
		if ( strpos( $key_lower, 'age' ) !== false || strpos( $key_lower, 'edad' ) !== false ) {
			foreach ( (array) $value as $v ) {
				$val_ages = explode( '-', $v );
				foreach ( $val_ages as $va ) {
					$user_demo_ages[] = intval( $va );
				}
			}
		}
		if ( strpos( $key_lower, 'populations' ) !== false || strpos( $key_lower, 'poblaciones' ) !== false ) {
			foreach ( (array) $value as $v ) {
				if ( strpos( strtolower( $v ), 'lgbtq' ) !== false ) {
					$user_demo_lgbtq = true;
				}
			}
		}
		if ( strpos( $key_lower, 'ethnicity' ) !== false || strpos( $key_lower, 'étnico' ) !== false ) {
			foreach ( (array) $value as $v ) {
				if ( strpos( strtolower( $v ), 'white' ) === false && strpos( strtolower( $v ), 'blanco' ) === false ) {
					$user_demo_bipoc = true;
				}
			}
		}
		if ( strpos( $key_lower, 'someone else' ) !== false || strpos( $key_lower, 'otra persona' ) !== false ) {
			foreach ( (array) $value as $v ) {
				if ( strpos( strtolower( $v ), 'someone else' ) !== false || strpos( strtolower( $v ), 'otra persona' ) !== false ) {
					$user_demo_caregiver = true;
				}
			}
		}
	}

	$age_check = null;
	$cleaned   = array_filter( array_map( 'intval', $user_demo_ages ), function ( $a ) { return $a > 0; } );
	foreach ( $cleaned as $uda ) {
		$age_check = $uda >= 18 ? 'over' : 'under';
	}

	return [
		'age_check'           => $age_check,
		'user_demo_lgbtq'    => $user_demo_lgbtq,
		'user_demo_bipoc'    => $user_demo_bipoc,
		'user_demo_caregiver'=> $user_demo_caregiver,
	];
}

/**
 * Get scored articles for additional (related) links only. Not used for featured next steps.
 *
 * Uses same taxonomy + scoring as related_articles.php, including popularity rules.
 * Scoring rules match old related_articles.php. Screen does not need tags: condition and
 * popularity alone can qualify an article so popular high-scoring articles can bubble up.
 *
 * @param array $args  user_screen_result, excluded_ids, espanol, layout, iframe_var, answered_demos, limit
 * @return array List of items: id, type='scored', title, url, target, score, score_debug, pop
 */
function mha_next_steps_get_scored_articles( $args ) {
	$defaults = [
		'user_screen_result' => [],
		'excluded_ids'       => [],
		'espanol'            => '',
		'layout'             => [],
		'iframe_var'         => '',
		'answered_demos'     => [],
		'limit'              => 200,
	];
	$args = wp_parse_args( $args, $defaults );
	$user_screen_result = $args['user_screen_result'];
	$excluded_ids       = array_unique( (array) $args['excluded_ids'] );
	$demographics       = mha_next_steps_parse_demographics( $args['answered_demos'] );

	$loop_args = [
		'post_type'      => [ 'article', 'diy' ],
		'order'          => 'ASC',
		'orderby'        => 'title',
		'post_status'    => 'publish',
		'posts_per_page' => 200,
	];

	if ( ! empty( $args['layout'] ) && in_array( 'ras_r', $args['layout'], true ) ) {
		$loop_args = [
			'post_type'      => 'article',
			'order'          => 'ASC',
			'orderby'        => 'date',
			'post_status'    => 'publish',
			'posts_per_page' => 500,
			'meta_query'     => [
				[
					'key'     => 'type',
					'value'   => 'condition',
					'compare' => 'LIKE',
				],
			],
		];
	}

	$taxonomy_query = [];
	if ( ! empty( $user_screen_result['next_step_terms'] ) ) {
		foreach ( array_unique( $user_screen_result['next_step_terms'] ) as $step ) {
			$t = get_term( $step, 'condition' );
			if ( $t ) { $taxonomy_query['condition'][] = $t->term_id; }
			$t = get_term( $step, 'age_group' );
			if ( $t ) { $taxonomy_query['age_group'][] = $t->term_id; }
			$t = get_term( $step, 'post_tag' );
			if ( $t ) { $taxonomy_query['post_tag'][] = $t->term_id; }
		}
	}
	if ( ! empty( $user_screen_result['result_terms'] ) ) {
		foreach ( $user_screen_result['result_terms'] as $step ) {
			if ( isset( $step['taxonomy'], $step['id'] ) && in_array( $step['taxonomy'], [ 'condition', 'age_group', 'post_tag' ], true ) ) {
				$taxonomy_query[ $step['taxonomy'] ][] = $step['id'];
			}
		}
	}
	$tags = get_field( 'related_tags', $user_screen_result['screen_id'] );
	if ( $tags ) {
		foreach ( $tags as $step ) {
			if ( isset( $step->taxonomy, $step->term_id ) && in_array( $step->taxonomy, [ 'condition', 'age_group', 'post_tag' ], true ) ) {
				$taxonomy_query[ $step->taxonomy ][] = $step->term_id;
			}
		}
	}

	$loop_args['post__not_in'] = $excluded_ids;
	if ( ! empty( $taxonomy_query ) ) {
		foreach ( $taxonomy_query as $tax => $term_ids ) {
			$loop_args['tax_query'][] = [
				'taxonomy' => $tax,
				'field'    => 'term_id',
				'terms'    => array_unique( $term_ids ),
			];
		}
		if ( count( $taxonomy_query ) > 1 ) {
			$loop_args['tax_query']['relation'] = 'AND';
		}
	}

	if ( ! empty( $args['espanol'] ) ) {
		unset( $loop_args['meta_query'], $loop_args['tax_query'] );
		$loop_args['post_type']      = [ 'article', 'diy' ];
		$loop_args['posts_per_page'] = 200;
		$loop_args['tax_query']      = [
			[ 'taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => [ 49 ] ],
		];
	}

	$loop       = new WP_Query( $loop_args );
	$pop_array  = function_exists( 'mha_monthly_pop_articles' ) ? mha_monthly_pop_articles( 'read' ) : [];
	$screen_conditions = get_the_terms( $user_screen_result['screen_id'], 'condition' );
	$primary_condition_yoast = get_post_meta( $user_screen_result['screen_id'], '_yoast_wpseo_primary_condition', true );
	$primary_condition = $primary_condition_yoast ? get_term( $primary_condition_yoast, 'condition' ) : ( $screen_conditions ? $screen_conditions[0] : null );
	$terms_tags = get_the_terms( $user_screen_result['screen_id'], 'post_tag' );
	$related    = [];

	// Scoring rules match old related_articles.php. Screen does not need tags: condition
	// and popularity alone can qualify an article (e.g. OnlyPrimary + IsPopular + HasPrimaryCondition),
	// so popular high-scoring articles can bubble up even when the screen has no post_tag terms.
	while ( $loop->have_posts() ) {
		$loop->the_post();
		$article_id   = get_the_ID();
		$related_link = get_the_permalink();
		$related_target = ! empty( $args['iframe_var'] ) ? ' target="_blank"' : '';
		$article_conditions = get_the_terms( $article_id, 'condition' );
		$article_tags  = get_the_terms( $article_id, 'post_tag' );
		$article_primary_condition = get_field( 'primary_condition', $article_id );

		if ( in_array( $article_id, $excluded_ids ) ) {
			continue;
		}

		$rel_score   = 0;
		$score_debug = '';
		$hasPrimary  = false;

		if ( ! empty( $args['espanol'] ) ) {
			if ( ! get_field( 'espanol', $article_id ) ) {
				continue;
			}
			$rel_score += 1;
			$score_debug .= '#SharesEspanol ';
		}

		if ( get_field( 'area_served', $article_id ) ) {
			continue;
		}

		if ( $article_primary_condition && $primary_condition && isset( $article_primary_condition->term_id ) && $article_primary_condition->term_id == $primary_condition->term_id
			|| ( is_array( $article_conditions ) && count( $article_conditions ) == 1 )
			|| ( $primary_condition && $article_primary_condition && isset( $article_primary_condition->term_id, $primary_condition->term_id ) && $article_primary_condition->term_id == $primary_condition->term_id ) ) {
			$rel_score += (int) get_field( 'scoring_primary_condition', 'options' );
			$score_debug .= '#OnlyPrimary ';
			$hasPrimary = true;
		}

		// Popularity: use loose comparison — $pop_array values may be strings while $article_id is int.
		if ( is_array( $pop_array ) && in_array( $article_id, $pop_array ) ) {
			$rel_score += (int) get_field( 'scoring_popular', 'options' );
			$score_debug .= '#IsPopular ';
		}

		if ( is_array( $article_conditions ) ) {
			foreach ( $article_conditions as $nc ) {
				$hasCondition = false;
				if ( $screen_conditions ) {
					foreach ( $screen_conditions as $sc ) {
						if ( $nc->term_id == $sc->term_id ) { $hasCondition = true; break; }
					}
				}
				if ( $hasCondition ) {
					$rel_score += (int) get_field( 'scoring_condition', 'options' );
					$score_debug .= '#HasPrimaryCondition ';
					break;
				}
				elseif ( $primary_condition && $nc->term_id == $primary_condition->term_id ) {
					if ( ! $hasPrimary ) {
						$rel_score += (int) get_field( 'scoring_matching_primary', 'options' );
						$score_debug .= '#HasCondition ';
						break;
					}
				}
			}
		}

		// Tag scoring — only requires article to have tags; screen tags and next_step_terms checked inside loop.
		if ( $article_tags ) {
			foreach ( $article_tags as $nt ) {
				$hasTag = false;
				if ( $terms_tags ) {
					foreach ( $terms_tags as $tt ) {
						if ( $nt->term_id == $tt->term_id ) {
							$hasTag = true;
							break;
						}
					}
				}
				if ( ! $hasTag && is_array( $user_screen_result['next_step_terms'] ) ) {
					foreach ( $user_screen_result['next_step_terms'] as $nst ) {
						$nst_term = get_term( $nst, 'post_tag' );
						if ( $nst_term && $nt->term_id == $nst_term->term_id ) {
							$hasTag = true;
							break;
						}
					}
				}
				if ( $hasTag ) {
					$rel_score += (int) get_field( 'scoring_tag', 'options' );
					$score_debug .= '#Tag ';
				}
				if ( $primary_condition && $nt->term_id == $primary_condition->term_id ) {
					$rel_score += (int) get_field( 'scoring_primary_tag', 'options' );
					$score_debug .= '#HasTag ';
				}
				if ( $nt->slug === 'youth'
					&& strpos( strtolower( get_the_title( $user_screen_result['screen_id'] ) ), 'youth' ) === false
					&& strpos( strtolower( get_the_title( $user_screen_result['screen_id'] ) ), 'parent' ) === false ) {
					if ( $demographics['age_check'] === 'over' ) {
						$rel_score -= (int) get_field( 'scoring_over_18', 'options' );
						$score_debug .= '#Over18 ';
					}
					if ( $demographics['age_check'] === 'under' ) {
						$rel_score += (int) get_field( 'scoring_under_18', 'options' );
						$score_debug .= '#Under18 ';
					}
				}
				if ( $nt->slug === 'lgbtq' && $demographics['user_demo_lgbtq'] ) {
					$rel_score += (int) get_field( 'scoring_lgbtq', 'options' );
					$score_debug .= '#LGBTQ+ ';
				}
				if ( $nt->slug === 'bipoc' && $demographics['user_demo_bipoc'] ) {
					$rel_score += (int) get_field( 'scoring_bipoc', 'options' );
					$score_debug .= '#BIPOC ';
				}
				if ( $nt->slug === 'caregiver' && $demographics['user_demo_caregiver'] ) {
					$rel_score += (int) get_field( 'scoring_caregiver', 'options' );
					$score_debug .= '#Caregiver ';
				}
			}
		}
		// Same as old method: keep article if it has any score (or is espanol). No requirement for tags.
		if ( $score_debug === '' && empty( $args['espanol'] ) ) {
			continue;
		}

		$related[ $article_id ] = [
			'id'          => $article_id,
			'type'        => 'scored',
			'title'       => get_the_title(),
			'url'         => $related_link,
			'target'      => $related_target,
			'score'       => $rel_score,
			'score_debug' => trim( $score_debug ),
			'pop'         => is_array( $pop_array ) && in_array( $article_id, $pop_array ) ? array_search( $article_id, $pop_array ) : 999,
		];
		$excluded_ids[] = $article_id;
	}
	wp_reset_postdata();

	if ( empty( $related ) ) {
		return [];
	}

	if ( empty( $args['layout'] ) || ! in_array( 'ras_r', $args['layout'], true ) ) {
		array_multisort(
			array_column( $related, 'score' ), SORT_DESC,
			array_column( $related, 'pop' ), SORT_ASC,
			$related
		);
	}

	return array_slice( array_values( $related ), 0, (int) $args['limit'] );
}

/**
 * Build a single ordered link pool. Priority order:
 * 1. Include IDs from the URL (include_ids query var)
 * 2. Featured next steps from the screen (ACF repeater on screen post)
 * 3. Result based links (next_step_manual from results repeater)
 * 4. Demographic based links (screen + global options from get_mha_demo_steps)
 * 5. Scored articles (WP_Query + global options scoring)
 *
 * @param array $args  user_screen_result, excluded_ids, demo_steps, next_step_manual, espanol, layout, iframe_var, partner_var, answered_demos, limit
 * @return array [ 'pool' => array of items, 'used_ids' => array ]
 */
function mha_build_unified_next_steps_pool( $args ) {
	$defaults = [
		'user_screen_result' => [],
		'excluded_ids'       => [],
		'demo_steps'         => [],
		'next_step_manual'   => [],
		'espanol'            => '',
		'layout'             => [],
		'iframe_var'         => '',
		'partner_var'        => '',
		'answered_demos'     => [],
		'limit'              => 200,
	];
	$args  = wp_parse_args( $args, $defaults );
	$pool  = [];
	$used  = [];
	$target = ! empty( $args['iframe_var'] ) ? ' target="_blank"' : '';

	$excluded = array_unique( (array) $args['excluded_ids'] );

	$demo_steps = (array) $args['demo_steps'];
	if ( empty( $demo_steps ) && ! empty( $args['user_screen_result']['screen_id'] ) && function_exists( 'get_mha_demo_steps' ) ) {
		$answered_demos = isset( $args['answered_demos'] ) ? $args['answered_demos'] : ( isset( $args['user_screen_result']['answered_demos'] ) ? $args['user_screen_result']['answered_demos'] : [] );
		$demo_data_screen = get_mha_demo_steps( $args['user_screen_result']['screen_id'], $answered_demos );
		$demo_data_global = get_mha_demo_steps( 'options', $answered_demos );
		if ( ! empty( $demo_data_screen['excluded_ids'] ) ) {
			$excluded = array_merge( $excluded, (array) $demo_data_screen['excluded_ids'] );
		}
		if ( ! empty( $demo_data_global['excluded_ids'] ) ) {
			$excluded = array_merge( $excluded, (array) $demo_data_global['excluded_ids'] );
		}
		foreach ( isset( $demo_data_screen['demo_steps'] ) ? (array) $demo_data_screen['demo_steps'] : [] as $e ) {
			$demo_steps[] = $e;
		}
		foreach ( isset( $demo_data_global['demo_steps'] ) ? (array) $demo_data_global['demo_steps'] : [] as $e ) {
			$demo_steps[] = $e;
		}
	}
	$excluded = array_unique( $excluded );

	// 1. Include IDs from the URL
	if ( get_query_var( 'include_ids' ) ) {
		$include_ids = array_map( 'intval', explode( ',', get_query_var( 'include_ids' ) ) );
		foreach ( $include_ids as $id ) {
			if ( ! $id || in_array( $id, $excluded, true ) || in_array( $id, $used, true ) ) {
				continue;
			}
			$pool[] = [
				'id' => $id,
				'type' => 'include_ids',
				'title' => get_the_title( $id ),
				'url' => get_the_permalink( $id ),
				'target' => $target,
				'score' => null,
				'score_debug' => '',
			];
			$used[] = $id;
		}
	}

	// 2. Screen featured next steps — only skip if already in pool ($used), not if in $excluded
	$screen_id = isset( $args['user_screen_result']['screen_id'] ) ? (int) $args['user_screen_result']['screen_id'] : 0;
	if ( $screen_id && function_exists( 'get_field' ) ) {
		$featured_rows = get_field( 'featured_next_steps', $screen_id );
		if ( is_array( $featured_rows ) ) {
			foreach ( $featured_rows as $row ) {
				$step = isset( $row['link'] ) ? $row['link'] : null;
				if ( ! $step ) { continue; }
				$id = 0;
				if ( is_object( $step ) && isset( $step->ID ) ) {
					$id = (int) $step->ID;
				} elseif ( is_array( $step ) && isset( $step['ID'] ) ) {
					$id = (int) $step['ID'];
				} else {
					$id = (int) $step;
				}
				if ( ! $id || in_array( $id, $used, true ) ) {
					continue;
				}
				$title = get_the_title( $id );
				if ( is_object( $step ) && isset( $step->post_title ) ) {
					$title = $step->post_title;
				} elseif ( is_array( $step ) && isset( $step['post_title'] ) ) {
					$title = $step['post_title'];
				}
				$pool[] = [
					'id' => $id,
					'type' => 'manual',
					'title' => $title,
					'url' => get_the_permalink( $id ),
					'target' => $target,
					'score' => null,
					'score_debug' => '',
				];
				$used[] = $id;
			}
		}
	}

	// 3. Result based links
	foreach ( (array) $args['next_step_manual'] as $raw_id ) {
		$id = is_object( $raw_id ) && isset( $raw_id->ID ) ? (int) $raw_id->ID : ( is_array( $raw_id ) && isset( $raw_id['ID'] ) ? (int) $raw_id['ID'] : (int) $raw_id );
		if ( ! $id || in_array( $id, $excluded, true ) || in_array( $id, $used, true ) ) {
			continue;
		}
		$pool[] = [
			'id' => $id,
			'type' => 'result_manual',
			'title' => get_the_title( $id ),
			'url' => get_the_permalink( $id ),
			'target' => $target,
			'score' => null,
			'score_debug' => '',
		];
		$used[] = $id;
	}

	// 4. Demographic based links
	foreach ( $demo_steps as $link ) {
		$id = 0;
		if ( is_object( $link ) && isset( $link->ID ) ) {
			$id = (int) $link->ID;
		} elseif ( is_array( $link ) && isset( $link['ID'] ) ) {
			$id = (int) $link['ID'];
		} else {
			$id = (int) $link;
		}
		if ( ! $id || in_array( $id, $excluded, true ) || in_array( $id, $used, true ) ) {
			continue;
		}
		$title = get_the_title( $id );
		if ( is_object( $link ) && isset( $link->post_title ) ) {
			$title = $link->post_title;
		} elseif ( is_array( $link ) && isset( $link['post_title'] ) ) {
			$title = $link['post_title'];
		}
		$pool[] = [
			'id' => $id,
			'type' => 'demo',
			'title' => $title,
			'url' => get_the_permalink( $id ),
			'target' => $target,
			'score' => null,
			'score_debug' => '',
		];
		$used[] = $id;
	}

	// 5. Scored articles
	$scored_exclude = array_merge( $excluded, $used );
	$scored = mha_next_steps_get_scored_articles( [
		'user_screen_result' => $args['user_screen_result'],
		'excluded_ids'       => $scored_exclude,
		'espanol'            => $args['espanol'],
		'layout'             => $args['layout'],
		'iframe_var'         => $args['iframe_var'],
		'answered_demos'     => $args['answered_demos'],
		'limit'              => $args['limit'],
	] );
	foreach ( $scored as $item ) {
		$pool[] = $item;
		$used[] = $item['id'];
	}

	return [ 'pool' => $pool, 'used_ids' => $used ];
}

/**
 * Get unified next steps: one pool, split into featured (top N) and/or related (rest + fill).
 *
 * @param array $args  user_screen_result, excluded_ids, demo_steps, next_step_manual, espanol, layout, iframe_var, partner_var, answered_demos, featured_count, max_related_total, return_parts ('both'|'featured_only'|'related_only'), already_displayed_ids, related_skip
 * @return array [ 'featured' => ..., 'related' => ..., 'used_ids' => ..., 'displayed_ids' => ... ]
 */
function mha_get_unified_next_steps( $args ) {
	$defaults = [
		'user_screen_result'   => [],
		'excluded_ids'         => [],
		'demo_steps'           => [],
		'next_step_manual'     => [],
		'espanol'              => '',
		'layout'               => [],
		'iframe_var'           => '',
		'partner_var'          => '',
		'answered_demos'       => [],
		'featured_count'       => 4,
		'max_related_total'    => 20,
		'return_parts'         => 'both',
		'already_displayed_ids'=> [],
		'related_skip'         => 0,
	];
	$args = wp_parse_args( $args, $defaults );

	$built = mha_build_unified_next_steps_pool( $args );
	$pool  = $built['pool'];
	$used  = $built['used_ids'];

	$featured_count = (int) $args['featured_count'];
	$max_related    = (int) $args['max_related_total'];
	$return_parts   = $args['return_parts'];
	$already        = array_unique( (array) $args['already_displayed_ids'] );
	$related_skip   = (int) $args['related_skip'];

	$featured = array_slice( $pool, 0, $featured_count );
	$rest     = array_slice( $pool, $featured_count );

	$displayed = [];
	if ( $return_parts !== 'related_only' ) {
		foreach ( $featured as $item ) {
			$displayed[] = $item['id'];
		}
	}

	$related = [];
	foreach ( $rest as $item ) {
		if ( in_array( $item['id'], $already, true ) ) {
			continue;
		}
		$related[] = $item;
	}
	if ( $related_skip > 0 ) {
		$related = array_slice( $related, $related_skip );
	}
	$related = array_slice( $related, 0, $max_related );

	if ( $return_parts === 'featured_only' ) {
		return [
			'featured'       => $featured,
			'related'        => [],
			'used_ids'       => $used,
			'displayed_ids'  => $displayed,
		];
	}
	if ( $return_parts === 'related_only' ) {
		return [
			'featured'       => [],
			'related'        => $related,
			'used_ids'       => $used,
			'displayed_ids'  => $already,
		];
	}

	return [
		'featured'      => $featured,
		'related'       => $related,
		'used_ids'      => $used,
		'displayed_ids' => $displayed,
	];
}

/**
 * Render unified featured next steps block.
 *
 * @param array $items Array of pool items (id, type, title, url, target, score?, score_debug?)
 * @param array $args  heading, show_title, layout
 * @return string HTML
 */
function mha_render_unified_featured_next_steps( $items, $args = [] ) {
	$args = wp_parse_args( $args, [ 'heading' => '', 'show_title' => true, 'layout' => [] ] );
	$out = '';
	if ( ! empty( $args['heading'] ) ) {
		$out .= '<h2 class="section-title dark-blue bold mb-3">' . esc_html( $args['heading'] ) . '</h2>';
	}
	$out .= '<ul class="next-steps list-unstyled">';
	$count = 0;
	foreach ( $items as $item ) {
		$count++;
		$link_class = 'button green thin round mr-3 rec-unified-featured';
		$score_debug = '';
		if ( current_user_can( 'edit_posts' ) && ! in_array( 'ras_r', (array) $args['layout'], true ) && isset( $item['type'], $item['score'] ) && $item['score'] !== null ) {
			$score_debug = ' <span class="small text-red">(Score: ' . (int) $item['score'];
			if ( ! empty( $item['score_debug'] ) ) {
				$score_debug .= ' [' . esc_html( $item['score_debug'] ) . ']';
			}
			$score_debug .= ')</span>';
		}
		if ( current_user_can( 'edit_posts' ) && ! empty( $item['type'] ) && ( ! isset( $item['score'] ) || $item['score'] === null ) ) {
			$score_debug = ' <span class="small text-muted">[' . esc_html( $item['type'] ) . ']</span>';
		}
		$out .= '<li class="link-item mb-3"><a class="' . esc_attr( $link_class ) . '" href="' . esc_url( $item['url'] ) . '"' . $item['target'] . '>' . esc_html( $item['title'] ) . '</a>' . $score_debug . '</li>';
	}
	$out .= '</ul>';
	return $out;
}

/**
 * Render unified related articles block.
 *
 * @param array $items Array of pool items
 * @param array $args  layout
 * @return string HTML
 */
function mha_render_unified_related_articles( $items, $args = [] ) {
	$args = wp_parse_args( $args, [ 'layout' => [] ] );
	$out = '<ul class="next-steps list-unstyled">';
	foreach ( $items as $item ) {
		$score_debug = '';
		if ( current_user_can( 'edit_posts' ) && ! in_array( 'ras_r', (array) $args['layout'], true ) && isset( $item['score'] ) && $item['score'] !== null ) {
			$score_debug = ' <span class="small text-red">(Score: ' . (int) $item['score'];
			if ( ! empty( $item['score_debug'] ) ) {
				$score_debug .= ' [' . esc_html( $item['score_debug'] ) . ']';
			}
			$score_debug .= ')</span>';
		}
		$out .= '<li class="link-item mb-2"><a class="dark-gray plain rec-unified-related" href="' . esc_url( $item['url'] ) . '"' . $item['target'] . '>' . esc_html( $item['title'] ) . '</a>' . $score_debug . '</li>';
	}
	$out .= '</ul>';
	return $out;
}
