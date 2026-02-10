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
 * @return array { age_check, user_demo_lgbtq, user_demo_bipoc, user_demo_caregiver, user_no_age, user_under_11, user_11_17, user_18_up }
 */
function mha_next_steps_parse_demographics( $answered_demos ) {
	$user_demo_ages   = [];
	$user_demo_lgbtq  = false;
	$user_demo_bipoc  = false;
	$user_demo_caregiver = false;

	$default_return = [
		'age_check'      => null,
		'user_demo_lgbtq'=> false,
		'user_demo_bipoc'=> false,
		'user_demo_caregiver' => false,
		'user_no_age'    => true,
		'user_under_11'  => false,
		'user_11_17'     => false,
		'user_18_up'     => false,
	];
	if ( empty( $answered_demos ) || ! is_array( $answered_demos ) ) {
		return $default_return;
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
	$user_no_age   = empty( $cleaned );
	$user_under_11 = false;
	$user_11_17    = false;
	$user_18_up    = false;
	foreach ( $cleaned as $uda ) {
		$age_check = $uda >= 18 ? 'over' : 'under';
		if ( $uda <= 11 ) {
			$user_under_11 = true;
		} elseif ( $uda < 18 ) {
			$user_11_17 = true;
		}
		if ( $uda >= 18 ) {
			$user_18_up = true;
			$user_under_11 = true; // match old related_articles: 18+ can see under-11 articles
		}
	}

	return [
		'age_check'      => $age_check,
		'user_demo_lgbtq'=> $user_demo_lgbtq,
		'user_demo_bipoc'=> $user_demo_bipoc,
		'user_demo_caregiver' => $user_demo_caregiver,
		'user_no_age'    => $user_no_age,
		'user_under_11'  => $user_under_11,
		'user_11_17'     => $user_11_17,
		'user_18_up'     => $user_18_up,
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
		'espanol'            => false,
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
		'posts_per_page' => 50,
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

	if ( $args['espanol'] ) {
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

		// Exclude Spanish articles when not in espanol mode (same as related_articles.php: "Skip Spanish Articles")
		if ( ! $args['espanol'] && get_field( 'espanol', $article_id ) ) {
			continue;
		}

		$rel_score   = 0;
		$score_debug = '';
		$hasPrimary  = false;

		if ( $args['espanol'] ) {
			if ( get_field( 'espanol', $article_id ) ) {
				$rel_score += 1;
				$score_debug .= '#SharesEspanol ';
			} else {
				continue; // In espanol mode only include articles with ACF espanol set (same as related_articles.php)
			}
		}

		if ( get_field( 'area_served', $article_id ) ) {
			continue;
		}

		// Article type + age_group rules (same as related_articles.php): diy/connect/provider only shown when age matches
		$article_type = get_field( 'type', $article_id );
		if ( $article_type && is_array( $article_type ) && count( array_intersect( [ 'diy', 'connect', 'provider' ], $article_type ) ) > 0 ) {
			$article_ages = get_the_terms( $article_id, 'age_group' );
			if ( $article_ages ) {
				foreach ( $article_ages as $a ) {
					$slug = isset( $a->slug ) ? $a->slug : '';
					if ( $slug === 'under-11' && ( ! $demographics['user_under_11'] || $demographics['user_no_age'] ) ) {
						continue 2; // skip this article
					}
					if ( $slug === '11-17' && ( ! $demographics['user_11_17'] || $demographics['user_no_age'] ) ) {
						continue 2;
					}
					if ( $slug === 'over-18' && ( ! $demographics['user_18_up'] || $demographics['user_no_age'] ) ) {
						continue 2;
					}
				}
			}
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
		if ( $score_debug === '' && !$args['espanol'] ) {
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
 * Whether featured next steps should be treated as partner-sourced (same logic as featured_next_steps.php).
 * When true, render can add partner-source class for styling. Used by mha_render_unified_featured_next_steps().
 *
 * @param array $user_screen_result Must include 'referer' key.
 * @return bool
 */
function mha_unified_next_steps_is_partner_source( $user_screen_result ) {
	if ( empty( $user_screen_result['referer'] ) ) {
		return false;
	}
	$partners = get_posts( [
		'post_type'      => 'partners',
		'post_status'    => 'publish',
		'posts_per_page' => 100,
	] );
	$referer = $user_screen_result['referer'];
	foreach ( $partners as $partner ) {
		$info = get_field( 'partner_information', $partner->ID );
		if ( ! empty( $info['partner_code'] ) && $info['partner_code'] === $referer ) {
			return true;
		}
	}
	return false;
}

/**
 * Build a single ordered link pool. Priority order:
 * 1. Include IDs from the URL (include_ids query var)
 * 2. Featured next steps from the screen (ACF repeater on screen post)
 * 3. Result based links (next_step_manual from results repeater)
 * 4. Demographic based links (screen + global options from get_mha_demo_steps)
 * 5. Scored articles (WP_Query + global options scoring)
 *
 * Exclusion rules (aligned with mha_featured_next_steps_data and page-screen-results): merged from
 * $args['excluded_ids'] plus base exclusions from mha_unified_next_steps_base_excluded_ids()
 * (global_hide_articles, screen_results_hide_articles, exclude_ids query var), then demo_steps
 * excluded_ids when building demo data.
 *
 * @param array $args  user_screen_result, excluded_ids, demo_steps, next_step_manual, espanol, layout, iframe_var, partner_var, answered_demos, limit
 * @return array [ 'pool' => array of items, 'used_ids' => array ]
 */
/**
 * Build base excluded IDs from options and URL (same as template and mha_featured_next_steps_data).
 * Use so unified pool always applies global_hide_articles, screen_results_hide_articles, exclude_ids.
 *
 * @return array Excluded post IDs (integers)
 */
function mha_unified_next_steps_base_excluded_ids() {
	$excluded = [];
	if ( function_exists( 'get_field' ) ) {
		$global_hide = get_field( 'global_hide_articles', 'options' );
		if ( $global_hide && is_array( $global_hide ) ) {
			foreach ( $global_hide as $id ) {
				$excluded[] = (int) $id;
			}
		}
		$screen_results_hide = get_field( 'screen_results_hide_articles', 'options' );
		if ( $screen_results_hide && is_array( $screen_results_hide ) ) {
			foreach ( $screen_results_hide as $id ) {
				$excluded[] = (int) $id;
			}
		}
	}
	$url_exclude = get_query_var( 'exclude_ids' );
	if ( $url_exclude ) {
		$parts = array_map( 'trim', explode( ',', $url_exclude ) );
		foreach ( $parts as $ue ) {
			if ( $ue !== '' ) {
				$excluded[] = (int) $ue;
			}
		}
	}
	return array_unique( array_filter( $excluded ) );
}

function mha_build_unified_next_steps_pool( $args ) {
	$defaults = [
		'user_screen_result' => [],
		'excluded_ids'       => [],
		'demo_steps'         => [],
		'next_step_manual'   => [],
		'espanol'            => false,
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

	// Merge template-style exclusions: passed excluded_ids + global/screen/URL (same as mha_featured_next_steps_data and page-screen-results)
	$excluded = array_merge(
		(array) $args['excluded_ids'],
		mha_unified_next_steps_base_excluded_ids()
	);
	$excluded = array_unique( array_map( 'intval', array_filter( $excluded ) ) );

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
		'espanol'              => false,
		'layout'               => [],
		'iframe_var'           => '',
		'partner_var'          => '',
		'answered_demos'       => [],
		'featured_count'       => 4,
		'max_related_total'    => 20,
		'return_parts'         => 'both',
		'already_displayed_ids'=> [],
		'related_skip'         => 0,
		'debug'                => false,
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
 * Group labels for featured next steps (matches display_featured_next_steps-style group titles).
 */
function mha_unified_featured_group_labels() {
	return [
		'include_ids'   => 'URL Included',
		'manual'        => 'Featured',
		'result_manual' => 'Result Based',
		'demo'          => 'Demographic',
		'scored'        => 'Additional Resources',
	];
}

/**
 * Render unified featured next steps block.
 *
 * Mirrors display_featured_next_steps(): groups links in featured-next-steps-test-group divs
 * (one per type), optional group title <p>, then <ol> of items. Partner styling when is_partner_source.
 *
 * @param array $items Array of pool items (id, type, title, url, target, score?, score_debug?)
 * @param array $args  heading, show_title, layout, is_partner_source, user_screen_result, hide_group_titles, debug
 * @return string HTML
 */
function mha_render_unified_featured_next_steps( $items, $args = [] ) {
	$args = wp_parse_args(
		$args, [
			'heading'            => 'Next Steps',
			'show_title'         => true,
			'layout'             => [],
			'is_partner_source'  => null,
			'user_screen_result' => [],
			'hide_group_titles'  => false,
			'debug'              => false,
		]
	);
	if ( $args['is_partner_source'] === null && ! empty( $args['user_screen_result'] ) && function_exists( 'mha_unified_next_steps_is_partner_source' ) ) {
		$args['is_partner_source'] = mha_unified_next_steps_is_partner_source( $args['user_screen_result'] );
	}
	$partner_class = ! empty( $args['is_partner_source'] ) ? ' partner-source' : '';
	$out = '';
	
	$out .= '<div class="featured-next-steps-test-container mt-5 mb-5' . esc_attr( $partner_class ) . '">';
	if ( ! empty( $args['heading'] ) ) {
		$out .= '<h2 class="section-title dark-blue bold mb-3">' . esc_html( $args['heading'] ) . '</h2>';
	}

	// Group by type, order matching pool priority (same as display_featured_next_steps link_groups)
	$type_order = [ 'include_ids', 'manual', 'result_manual', 'demo', 'scored' ];
	$groups = [];
	foreach ( $items as $item ) {
		$t = isset( $item['type'] ) ? $item['type'] : 'scored';
		if ( ! isset( $groups[ $t ] ) ) {
			$groups[ $t ] = [];
		}
		$groups[ $t ][] = $item;
	}
	$labels = mha_unified_featured_group_labels();

	foreach ( $type_order as $type ) {
		if ( empty( $groups[ $type ] ) ) {
			continue;
		}
		$group_items = $groups[ $type ];
		$out .= '<div class="featured-next-steps-test-group">';
		if ( ! $args['hide_group_titles'] && isset( $labels[ $type ] ) && $labels[ $type ] !== '' && $labels[ $type ] !== 'Featured' ) {
			$out .= '<p class="mt-4 mb-3">' . esc_html( $labels[ $type ] ) . '</p>';
		}
		$out .= '<ol class="next-steps list-unstyled">';
		$count = 0;
		foreach ( $group_items as $item ) {
			$count++;
			$link_class = 'button green thin round mr-3 rec-unified-featured';
			$score_debug = '';
			if ( current_user_can( 'manage_options' ) ) {
				if ( isset( $item['score'] ) && $item['score'] !== null ) {
					$pop_display = isset( $item['pop'] ) ? $item['pop'] : '—';
					$score_debug = '<br /> <span class="small text-red">(Score: ' . (int) $item['score'] . ', Popularity: #' . esc_html( $pop_display ) . ') <br /> [' . esc_html( isset( $item['score_debug'] ) ? $item['score_debug'] : '' ) . ']</span>';
				} elseif ( ! empty( $item['type'] ) ) {
					$score_debug = '<br /> <span class="small text-red">[' . esc_html( $item['type'] ) . ']</span>';
				}
			}
			$out .= '<li class="link-item mb-3' . esc_attr( $partner_class ) . '"><a class="' . esc_attr( $link_class ) . '" href="' . esc_url( $item['url'] ) . '"' . $item['target'] . '>' . esc_html( $item['title'] ) . '</a>' . $score_debug . '</li>';
		}
		$out .= '</ol>';
		$out .= '</div>';
	}

	$out .= '</div>';
	return $out;
}

/**
 * Render unified related articles block.
 *
 * Heading matches page-screen-results.php (next_steps_subtitle block). At the end of the list,
 * adds the same dynamic "See All" link as related_articles.php when the screen has see_all_link
 * (and optional see_all_link_text, e.g. "More articles about depression").
 *
 * @param array $items Array of pool items
 * @param array $args  layout, heading, show_title, user_screen_result, espanol, iframe_var, partner_var, hide_see_all_link
 * @return string HTML
 */
function mha_render_unified_related_articles( $items, $args = [] ) {
	$args = wp_parse_args( $args, [
		'layout'              => [],
		'heading'             => 'More Info & Resources',
		'show_title'          => true,
		'user_screen_result'  => [],
		'espanol'             => false,
		'iframe_var'          => '',
		'partner_var'         => '',
		'hide_see_all_link'   => false,
		'debug'               => false,
	] );
	if ( empty( $items ) ) {
		return '';
	}
	$out = '';

	$screen_id = isset( $args['user_screen_result']['screen_id'] ) ? (int) $args['user_screen_result']['screen_id'] : 0;

	// Same grouping structure as display_featured_next_steps: container > featured-next-steps-test-group > list
	$out .= '<div class="featured-next-steps-test-container">';
	$out .= '<div class="featured-next-steps-test-group">';
	$out .= '<ol class="next-steps two-column">';
	foreach ( $items as $item ) {
		$score_debug = '';
		if ( current_user_can( 'manage_options' ) ) {
			$pop_display = isset( $item['pop'] ) ? $item['pop'] : '—';
			$score_debug = '<br /> <span class="small text-red">(Score: ' . (int) $item['score'] . ', Popularity: #' . esc_html( $pop_display ) . ') <br /> [' . esc_html( isset( $item['score_debug'] ) ? $item['score_debug'] : '' ) . ']</span>';
		}
		$out .= '<li class="link-item mb-4"><a class="dark-gray plain rec-unified-related" href="' . esc_url( $item['url'] ) . '"' . $item['target'] . '>' . esc_html( $item['title'] ) . '</a>' . $score_debug . '</li>';
	}

	// Dynamic "See All" link at end (same as related_articles.php: see_all_link, see_all_link_text on screen)
	if ( ! $args['hide_see_all_link'] && $screen_id && function_exists( 'get_field' ) ) {
		$see_all_link = get_field( 'see_all_link', $screen_id );
		if ( $see_all_link ) {
			$see_all_text = get_field( 'see_all_link_text', $screen_id );
			if ( ! $see_all_text ) {
				$see_all_text = 'See All';
			}
			$see_all_target = ! empty( $args['iframe_var'] ) ? ' target="_blank"' : '';
			$out .= '<li class="see-all-link"><a class="caps cerulean plain"' . $see_all_target . ' href="' . esc_url( $see_all_link ) . '">' . esc_html( $see_all_text ) . '</a></li>';
		}
	}

	$out .= '</ol>';
	$out .= '</div>';
	$out .= '</div>';
	return $out;
}
