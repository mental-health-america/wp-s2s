<?php
/**
 * Unified Next Steps for Screen Results
 *
 * Builds a single link pool (URL includes, screen featured, result manual, demo, scored articles),
 * then splits into Featured Next Steps (top N) and Related Articles (rest + fill).
 * Use for refactor comparison and eventual replacement of display_featured_next_steps + mha_results_related_articles.
 *
 * @see DEV_DOCUMENTATION.md
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
 * Exclusion rules: merged from
 * $args['excluded_ids'] plus base exclusions from mha_unified_next_steps_base_excluded_ids()
 * (global_hide_articles, screen_results_hide_articles, exclude_ids query var), then demo_steps
 * excluded_ids when building demo data.
 *
 * @param array $args  user_screen_result, excluded_ids, demo_steps, next_step_manual, espanol, layout, iframe_var, partner_var, answered_demos, limit
 * @return array [ 'pool' => array of items, 'used_ids' => array ]
 */
/**
 * Get URL parameter value for condition checks: $_GET first, then $url_params (e.g. from results page).
 *
 * @param string $key        Query key (e.g. 'layout').
 * @param array  $url_params Optional override (e.g. [ 'layout' => 'ras_r' ]).
 * @return string|null
 */
function mha_unified_featured_get_url_param( $key, $url_params = [] ) {
	if ( isset( $_GET[ $key ] ) ) {
		return sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
	}
	if ( isset( $url_params[ $key ] ) ) {
		return is_string( $url_params[ $key ] ) ? $url_params[ $key ] : '';
	}
	return null;
}

/**
 * Evaluate a single featured next-step condition (test_result, url_parameter, question_response, demographic_response).
 * Mirrors logic in featured_next_steps.php so conditional link groups match.
 *
 * @param string $con_type      Condition type: test_result, url_parameter, question_response, demographic_response.
 * @param string $con_condition Operator: equals, contains, starts with, ends with, does not equal, etc.
 * @param string $con_key       Key (e.g. URL param name or demo key).
 * @param string $con_value     Value to compare against.
 * @param array  $args          Full context: result_title, user_screen_result, answered_demos, url_params.
 * @return bool True if this condition passes.
 */
function mha_unified_featured_condition_passes( $con_type, $con_condition, $con_key, $con_value, $args ) {
	$result_title = isset( $args['result_title'] ) ? $args['result_title'] : '';
	$url_params   = isset( $args['url_params'] ) && is_array( $args['url_params'] ) ? $args['url_params'] : [];
	$get_key      = mha_unified_featured_get_url_param( $con_key, $url_params );
	$score_data   = isset( $args['user_screen_result']['general_score_data'] ) ? $args['user_screen_result']['general_score_data'] : [];
	$answered     = isset( $args['answered_demos'] ) && is_array( $args['answered_demos'] ) ? $args['answered_demos'] : [];

	// Resolve subject value by type
	switch ( $con_type ) {
		case 'test_result':
			$subject = $result_title;
			break;
		case 'url_parameter':
			$subject = $get_key;
			break;
		case 'question_response':
			$subject = isset( $score_data[ $con_key ] ) ? $score_data[ $con_key ] : null;
			break;
		case 'demographic_response':
			$subject = isset( $answered[ $con_key ] ) ? $answered[ $con_key ] : null;
			break;
		default:
			return false;
	}

	// String comparisons (subject can be string or array for demographic)
	$subject_str = is_array( $subject ) ? implode( '|', $subject ) : (string) $subject;
	$con_value   = trim( (string) $con_value );

	// Demographic-specific: equals = exactly one element matches; contains = any element matches
	if ( $con_type === 'demographic_response' && is_array( $subject ) ) {
		if ( $con_condition === 'equals' ) {
			$count = 0;
			foreach ( $subject as $s ) {
				if ( (string) $s === $con_value ) {
					$count++;
				}
			}
			return $count === 1;
		}
		if ( $con_condition === 'contains' ) {
			return in_array( $con_value, $subject, true ) || in_array( $con_value, array_map( 'strval', $subject ), true );
		}
		if ( $con_condition === 'does not equal' ) {
			foreach ( $subject as $s ) {
				if ( (string) $s === $con_value ) {
					return false;
				}
			}
			return true;
		}
	}

	switch ( $con_condition ) {
		case 'equals':
			return $subject_str === $con_value;
		case 'contains':
			return $subject_str !== '' && str_contains( $subject_str, $con_value );
		case 'starts with':
			return $subject_str !== '' && str_starts_with( $subject_str, $con_value );
		case 'ends with':
			return $subject_str !== '' && str_ends_with( $subject_str, $con_value );
		case 'does not equal':
			return $subject_str != $con_value;
		case 'does not contain':
			return $subject_str !== '' && ! str_contains( $subject_str, $con_value );
		case 'does not start with':
			return $subject_str !== '' && ! str_starts_with( $subject_str, $con_value );
		case 'does not end with':
			return $subject_str !== '' && ! str_ends_with( $subject_str, $con_value );
		case 'exists':
		case 'not null':
			return $subject !== null && $subject !== '';
		case 'is null':
			return $subject === null || $subject === '';
		case 'greater than':
			$n = is_numeric( $subject ) ? (float) $subject : ( is_array( $subject ) ? array_sum( array_map( 'floatval', $subject ) ) : 0 );
			return $n > ( is_numeric( $con_value ) ? (float) $con_value : 0 );
		case 'less than':
			$n = is_numeric( $subject ) ? (float) $subject : ( is_array( $subject ) ? array_sum( array_map( 'floatval', $subject ) ) : 0 );
			return $n < ( is_numeric( $con_value ) ? (float) $con_value : 0 );
		case 'none of':
			$values = array_map( 'trim', explode( '|', $con_value ) );
			$subjects = is_array( $subject ) ? $subject : [ $subject_str ];
			foreach ( $values as $v ) {
				foreach ( $subjects as $s ) {
					if ( (string) $s === $v ) {
						return false;
					}
				}
			}
			return true;
		case 'one of':
			$values = array_map( 'trim', explode( '|', $con_value ) );
			if ( is_array( $subject ) ) {
				foreach ( $subject as $s ) {
					foreach ( $values as $v ) {
						if ( (string) $s === $v ) {
							return true;
						}
					}
				}
				return false;
			}
			// URL param "one of" can be comma-separated in get_key
			if ( $con_type === 'url_parameter' && $get_key !== null ) {
				$parts = array_map( 'trim', explode( '|', $get_key ) );
				foreach ( $parts as $p ) {
					foreach ( array_map( 'trim', explode( ',', $p ) ) as $p2 ) {
						foreach ( $values as $v ) {
							if ( $p2 === $v ) {
								return true;
							}
						}
					}
				}
				return false;
			}
			return in_array( $subject_str, $values, true );
		default:
			return false;
	}
}

/**
 * Unified featured next steps data: same behavior as mha_featured_next_steps_data() but returns an array
 * (no JSON) and uses refactored condition evaluation. Used by the unified pool builder.
 *
 * Flow: screen featured_next_steps → partner override (referer) → featured_next_steps_test conditional
 * next_step_links → demographic fill (get_mha_demo_steps) → related articles fill. Returns used_links
 * in display order, additional_result_text, and is_partner_source.
 *
 * @param array $args result_title, user_screen_result, answered_demos, url_params (optional)
 * @return array{ used_links: int[], additional_result_text: string[], is_partner_source: bool }|null Null if no data.
 */
function mha_unified_featured_next_steps_data( $args ) {
	$defaults = [
		'result_title'       => '',
		'user_screen_result' => [],
		'answered_demos'     => [],
		'url_params'         => [],
	];
	$args = wp_parse_args( $args, $defaults );

	$screen_id = isset( $args['user_screen_result']['screen_id'] ) ? (int) $args['user_screen_result']['screen_id'] : 0;
	if ( ! $screen_id || ! function_exists( 'get_field' ) ) {
		return null;
	}

	$results            = [];
	$additional_text    = [];
	$is_partner_source  = false;
	$screen_heading     = get_field( 'next_steps_heading', $screen_id ) ?: '';

	// 1. Screen featured_next_steps (simple repeater)
	$screen_links = [];
	if ( have_rows( 'featured_next_steps', $screen_id ) ) {
		while ( have_rows( 'featured_next_steps', $screen_id ) ) {
			the_row();
			$link = get_sub_field( 'link' );
			if ( $link ) {
				$id = is_object( $link ) ? ( isset( $link->ID ) ? $link->ID : 0 ) : (int) $link;
				if ( $id ) {
					$screen_links[] = $id;
				}
			}
		}
	}
	if ( ! empty( $screen_links ) ) {
		$results[] = [
			'group_title'           => '',
			'additional_result_text' => '',
			'partner_next_steps'     => false,
			'links'                  => array_combine( range( 1, count( $screen_links ) ), $screen_links ),
		];
	}

	// 2. Resolve source: screen or partner (by referer)
	$source_id = $screen_id;
	if ( ! empty( $args['user_screen_result']['referer'] ) && function_exists( 'get_posts' ) ) {
		$partners = get_posts( [
			'post_type'      => 'partners',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
		] );
		foreach ( $partners as $partner ) {
			$info = get_field( 'partner_information', $partner->ID );
			if ( ! empty( $info['partner_code'] ) && $info['partner_code'] === $args['user_screen_result']['referer'] ) {
				$source_id         = (int) $partner->ID;
				$is_partner_source = true;
				break;
			}
		}
		wp_reset_postdata();
	}

	// 3. Conditional featured_next_steps_test → next_step_links (each matching row adds a result; multiple conditions can each display)
	$last_heading          = '';
	$last_randomize_group  = false;
	if ( have_rows( 'featured_next_steps_test', $source_id ) ) {
		while ( have_rows( 'featured_next_steps_test', $source_id ) ) {
			the_row();
			$heading          = get_sub_field( 'next_steps_heading' );
			$randomize        = get_sub_field( 'dont_randomize_order' );
			$randomize_group  = get_sub_field( 'dont_randomize_group_order' );
			$hide_group_titles = get_sub_field( 'hide_group_titles' );
			$last_heading     = $heading ?: $last_heading;
			$last_randomize_group = $randomize_group;

			// Process every next_step_links row; each that passes adds a separate result (so layout contains X and layout contains Y both show)
			if ( have_rows( 'next_step_links' ) ) {
				while ( have_rows( 'next_step_links' ) ) {
					the_row();
					$operator   = get_sub_field( 'operator' );
					$group_title = get_sub_field( 'link_group_title' );
					$conditions_met = 0;
					$conditions_total = 0;

					if ( have_rows( 'conditions' ) ) {
						while ( have_rows( 'conditions' ) ) {
							the_row();
							$conditions_total++;
							if ( mha_unified_featured_condition_passes(
								get_sub_field( 'type' ),
								get_sub_field( 'condition' ),
								get_sub_field( 'key' ),
								get_sub_field( 'value' ),
								$args
							) ) {
								$conditions_met++;
							}
						}
					}

					$proceed = ( $operator === 'and' && $conditions_met === $conditions_total )
						|| ( $operator === 'or' && $conditions_met > 0 );

					if ( $proceed ) {
						$links_raw = get_sub_field( 'links' );
						$links     = [];
						if ( $links_raw && is_array( $links_raw ) && ! $randomize ) {
							shuffle( $links_raw );
						}
						if ( $links_raw && is_array( $links_raw ) ) {
							$idx = 1;
							foreach ( $links_raw as $l ) {
								$link_id = is_object( $l ) ? ( isset( $l->ID ) ? $l->ID : 0 ) : ( is_array( $l ) && isset( $l['ID'] ) ? (int) $l['ID'] : (int) $l );
								if ( $link_id ) {
									$links[ $idx++ ] = $link_id;
								}
							}
						}
						$addl = get_sub_field( 'additional_result_text' );
						$results[] = [
							'group_title'           => $group_title ?: '',
							'additional_result_text' => $addl ?: '',
							'partner_next_steps'     => $is_partner_source,
							'links'                  => $links,
						];
						if ( $addl ) {
							$additional_text[] = $addl;
						}
					}
				}
			}
		}

		// Apply heading from conditionals once we have results
		if ( ! empty( $results ) && ! empty( $last_heading ) && empty( $screen_heading ) ) {
			$screen_heading = $last_heading;
		}
		// Shuffle group order once after collecting all matching rows (preserve first/screen so multiple conditions all display)
		if ( ! $last_randomize_group && count( $results ) > 1 ) {
			$first = array_shift( $results );
			$other = $results;
			shuffle( $other );
			$results = array_merge( [ $first ], $other );
		}
	}

	// 4. Demographic fill when under max and not partner
	$groups_with_links = 0;
	$total_links = 0;
	foreach ( $results as $r ) {
		if ( ! empty( $r['links'] ) ) {
			$groups_with_links++;
			$total_links += count( $r['links'] );
		}
	}
	$max_per_group = $groups_with_links > 1 ? 2 : 4;
	$needed = $max_per_group - $total_links;

	if ( $needed > 0 && ! $is_partner_source && function_exists( 'get_mha_demo_steps' ) ) {
		$answered = isset( $args['user_screen_result']['answered_demos'] ) ? $args['user_screen_result']['answered_demos'] : [];
		$demo_screen = get_mha_demo_steps( $args['user_screen_result']['screen_id'], $answered );
		$demo_global = get_mha_demo_steps( 'options', $answered );
		$demo_ids = [];
		foreach ( [ $demo_screen, $demo_global ] as $data ) {
			if ( ! empty( $data['demo_steps'] ) && is_array( $data['demo_steps'] ) ) {
				foreach ( $data['demo_steps'] as $e ) {
					$id = is_object( $e ) && isset( $e->ID ) ? (int) $e->ID : ( is_array( $e ) && isset( $e['ID'] ) ? (int) $e['ID'] : (int) $e );
					if ( $id ) {
						$demo_ids[] = $id;
					}
				}
			}
		}
		$demo_ids = array_values( array_unique( $demo_ids ) );
		$used_ids = [];
		foreach ( $results as $r ) {
			if ( ! empty( $r['links'] ) ) {
				$used_ids = array_merge( $used_ids, array_values( $r['links'] ) );
			}
		}
		$demo_ids = array_values( array_diff( $demo_ids, $used_ids ) );
		$target_idx = null;
		foreach ( $results as $idx => $r ) {
			if ( isset( $r['group_title'] ) && $r['group_title'] === '' && isset( $r['links'] ) ) {
				$target_idx = $idx;
				break;
			}
		}
		if ( $target_idx === null ) {
			$target_idx = count( $results );
			$results[] = [
				'group_title'           => '',
				'additional_result_text' => '',
				'partner_next_steps'     => false,
				'links'                  => [],
			];
		}
		$max_idx = empty( $results[ $target_idx ]['links'] ) ? 0 : max( array_keys( $results[ $target_idx ]['links'] ) );
		$add_count = 0;
		foreach ( $demo_ids as $did ) {
			if ( $add_count >= $needed ) {
				break;
			}
			$results[ $target_idx ]['links'][ $max_idx + 1 + $add_count ] = $did;
			$add_count++;
		}
	}

	// 5. Build used_links (flat order) and optionally fill from related articles
	$used_links = [];
	foreach ( $results as $r ) {
		if ( empty( $r['links'] ) ) {
			continue;
		}
		$i = 1;
		while ( $i <= $max_per_group ) {
			if ( isset( $r['links'][ $i ] ) ) {
				$used_links[] = (int) $r['links'][ $i ];
			}
			$i++;
		}
	}
	$total_used = count( $used_links );
	$count_diff = $max_per_group - $total_used;

	// 6. Related articles fill when still short
	if ( $count_diff > 0 && ! $is_partner_source && function_exists( 'mha_results_related_articles' ) && function_exists( 'get_layout_array' ) ) {
		$excluded = $used_links;
		$excluded = array_merge( $excluded, (array) ( function_exists( 'get_field' ) ? get_field( 'global_hide_articles', 'options' ) : [] ) );
		$excluded = array_merge( $excluded, (array) ( function_exists( 'get_field' ) ? get_field( 'screen_results_hide_articles', 'options' ) : [] ) );
		if ( get_query_var( 'exclude_ids' ) ) {
			$excluded = array_merge( $excluded, array_map( 'intval', array_filter( explode( ',', get_query_var( 'exclude_ids' ) ) ) ) );
		}
		$demo_data = get_mha_demo_steps( $args['user_screen_result']['screen_id'], $args['user_screen_result']['answered_demos'] );
		if ( ! empty( $demo_data['excluded_ids'] ) ) {
			$excluded = array_merge( $excluded, (array) $demo_data['excluded_ids'] );
		}
		$demo_steps_list = [];
		if ( ! empty( $demo_data['demo_steps'] ) ) {
			$demo_steps_list = array_merge( $demo_steps_list, (array) $demo_data['demo_steps'] );
		}
		$demo_global = get_mha_demo_steps( 'options', $args['user_screen_result']['answered_demos'] );
		if ( ! empty( $demo_global['demo_steps'] ) ) {
			$demo_steps_list = array_merge( $demo_steps_list, (array) $demo_global['demo_steps'] );
		}
		$related_args = [
			'demo_steps'         => $demo_steps_list,
			'next_step_manual'   => isset( $args['user_screen_result']['next_step_manual'] ) ? $args['user_screen_result']['next_step_manual'] : [],
			'user_screen_result' => $args['user_screen_result'],
			'excluded_ids'       => array_unique( $excluded ),
			'next_step_terms'    => isset( $args['user_screen_result']['next_step_terms'] ) ? $args['user_screen_result']['next_step_terms'] : [],
			'espanol'            => function_exists( 'get_field' ) ? get_field( 'espanol', $args['user_screen_result']['screen_id'] ) : '',
			'iframe_var'         => '',
			'partner_var'        => get_query_var( 'partner' ),
			'total'              => 4,
			'style'              => 'featured',
			'hide_all'           => true,
			'layout'             => get_layout_array( get_query_var( 'layout' ) ),
			'answered_demos'     => isset( $args['user_screen_result']['answered_demos'] ) ? $args['user_screen_result']['answered_demos'] : [],
		];
		$related_json = mha_results_related_articles( $related_args );
		if ( $related_json ) {
			$decoded = json_decode( $related_json );
			$related_links = isset( $decoded->link_groups->related_links ) ? (array) $decoded->link_groups->related_links : [];
			if ( ! empty( $related_links ) ) {
				$n = 0;
				foreach ( $related_links as $id ) {
					if ( $n >= $count_diff ) {
						break;
					}
					$used_links[] = (int) $id;
					$n++;
				}
			}
		}
	}

	if ( empty( $used_links ) && empty( $additional_text ) ) {
		return null;
	}

	return [
		'used_links'            => array_map( 'intval', $used_links ),
		'additional_result_text' => $additional_text,
		'is_partner_source'     => $is_partner_source,
	];
}

/**
 * Build base excluded IDs from options and URL. Used so unified pool always applies 
 * global_hide_articles, screen_results_hide_articles, and exclude_ids query var.
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

/**
 * Sanitize a link group title for use in a #LinkGroup_XYZ source tag (alphanumeric + underscores).
 *
 * @param string $group_title Raw group title (e.g. "Related Links", "Additional Resources").
 * @return string Safe suffix for #LinkGroup_ (e.g. "Related_Links").
 */
function mha_unified_next_steps_sanitize_group_tag( $group_title ) {
	$s = preg_replace( '/[^a-zA-Z0-9]+/', '_', trim( (string) $group_title ) );
	return trim( $s, '_' ) ?: 'Unknown';
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

	// Merge template-style exclusions: passed excluded_ids + global/screen/URL
	$excluded = array_merge(
		(array) $args['excluded_ids'],
		mha_unified_next_steps_base_excluded_ids()
	);
	$excluded = array_unique( array_map( 'intval', array_filter( $excluded ) ) );

	$demo_steps = (array) $args['demo_steps'];
	$demo_steps_screen = [];
	$demo_steps_global = [];
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
		$demo_steps_screen = isset( $demo_data_screen['demo_steps'] ) ? (array) $demo_data_screen['demo_steps'] : [];
		$demo_steps_global = isset( $demo_data_global['demo_steps'] ) ? (array) $demo_data_global['demo_steps'] : [];
		foreach ( $demo_steps_screen as $e ) {
			$demo_steps[] = $e;
		}
		foreach ( $demo_steps_global as $e ) {
			$demo_steps[] = $e;
		}
	}
	$excluded = array_unique( $excluded );
	$screen_id = isset( $args['user_screen_result']['screen_id'] ) ? (int) $args['user_screen_result']['screen_id'] : 0;

	// Conditional featured next steps: use order from GF entry when stored (no shuffle on refresh); only shuffle on first display
	$conditional_used_links = [];
	$conditional_link_group_map = []; // link_id => group title (for #LinkGroup_XYZ source tag when from stored data)
	$additional_result_text = [];
	$is_partner_source = false;
	$stored_fns = isset( $args['user_screen_result']['featured_next_steps_data'] ) ? $args['user_screen_result']['featured_next_steps_data'] : '';
	if ( $screen_id ) {
		if ( $stored_fns !== '' && $stored_fns !== null ) {
			// Use order stored in GF entry (subsequent views) — no shuffle
			$decoded = is_string( $stored_fns ) ? json_decode( $stored_fns ) : $stored_fns;
			if ( $decoded && isset( $decoded->used_links ) && is_array( $decoded->used_links ) ) {
				$conditional_used_links = array_map( 'intval', array_filter( $decoded->used_links ) );
			}
			if ( $decoded && isset( $decoded->link_groups ) && ( is_array( $decoded->link_groups ) || is_object( $decoded->link_groups ) ) ) {
				$lg = (array) $decoded->link_groups;
				foreach ( $lg as $group_title => $ids ) {
					if ( $group_title === 'partner_source' ) {
						continue;
					}
					$ids = (array) $ids;
					foreach ( $ids as $link_id ) {
						$link_id = (int) $link_id;
						if ( $link_id ) {
							$conditional_link_group_map[ $link_id ] = $group_title;
						}
					}
				}
			}
			if ( $decoded && ! empty( $decoded->additional_result_text ) && is_array( $decoded->additional_result_text ) ) {
				$additional_result_text = $decoded->additional_result_text;
			}
			if ( $decoded && ! empty( $decoded->is_partner_source ) ) {
				$is_partner_source = true;
			}
		}
		if ( empty( $conditional_used_links ) && empty( $additional_result_text ) ) {
			// First display (no stored data): compute and shuffle once; order is saved to entry by result_content/GF
			$fns_args = [
				'user_screen_result' => $args['user_screen_result'],
				'result_title'       => isset( $args['user_screen_result']['result_title'] ) ? $args['user_screen_result']['result_title'] : '',
				'answered_demos'     => $args['answered_demos'],
				'url_params'         => [ 'layout' => is_array( $args['layout'] ) ? implode( ',', $args['layout'] ) : (string) $args['layout'] ],
			];
			$fns_result = mha_unified_featured_next_steps_data( $fns_args );
			if ( $fns_result ) {
				if ( ! empty( $fns_result['used_links'] ) && is_array( $fns_result['used_links'] ) ) {
					$conditional_used_links = array_map( 'intval', array_filter( $fns_result['used_links'] ) );
				}
				if ( ! empty( $fns_result['additional_result_text'] ) && is_array( $fns_result['additional_result_text'] ) ) {
					$additional_result_text = $fns_result['additional_result_text'];
				}
				if ( ! empty( $fns_result['is_partner_source'] ) ) {
					$is_partner_source = true;
				}
			}
		}
	}

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
				'score_debug' => '#URLIncluded',
			];
			$used[] = $id;
		}
	}

	// 2. Featured links: from conditional featured_next_steps_test (screen or partner) + demographic_next_steps fill, or fallback to screen featured_next_steps repeater
	if ( ! empty( $conditional_used_links ) ) {
		foreach ( $conditional_used_links as $id ) {
			$id = (int) $id;
			if ( ! $id || in_array( $id, $excluded, true ) || in_array( $id, $used, true ) ) {
				continue;
			}
			//$source_tags = '#FeaturedNextSteps';
			$source_tags = $is_partner_source ? '#FeaturedNextSteps_Partner' : '#FeaturedNextSteps_Screen';
			if ( ! empty( $conditional_link_group_map[ $id ] ) ) {
				$source_tags .= ' #LinkGroup_' . mha_unified_next_steps_sanitize_group_tag( $conditional_link_group_map[ $id ] );
			}
			$pool[] = [
				'id'          => $id,
				'type'        => 'manual',
				'title'       => get_the_title( $id ),
				'url'         => get_the_permalink( $id ),
				'target'      => $target,
				'score'       => null,
				'score_debug' => $source_tags,
			];
			$used[] = $id;
		}
	} elseif ( $screen_id && function_exists( 'get_field' ) ) {
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
					'score_debug' => '#ScreenFeaturedNextSteps #Screen',
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
			'score_debug' => '#ResultBased #Screen',
		];
		$used[] = $id;
	}

	// 4. Demographic based links (screen first, then global options, so source tag is accurate)
	$demo_sources = [
		'#Demographic #Screen'  => $demo_steps_screen,
		'#Demographic #Global' => $demo_steps_global,
	];
	foreach ( $demo_sources as $demo_source_tag => $steps ) {
		foreach ( $steps as $link ) {
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
				'score_debug' => $demo_source_tag,
			];
			$used[] = $id;
		}
	}
	// When demo_steps were passed in (e.g. from template) we may not have had screen/global split
	if ( ! empty( $demo_steps ) && empty( $demo_steps_screen ) && empty( $demo_steps_global ) ) {
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
				'score_debug' => '#Demographic',
			];
			$used[] = $id;
		}
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

	return [
		'pool'                  => $pool,
		'used_ids'              => $used,
		'additional_result_text'=> $additional_result_text,
		'is_partner_source'     => $is_partner_source,
	];
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
	$additional_result_text = isset( $built['additional_result_text'] ) ? $built['additional_result_text'] : [];
	$is_partner_source      = isset( $built['is_partner_source'] ) ? $built['is_partner_source'] : false;

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

	$extra = [
		'additional_result_text' => $additional_result_text,
		'is_partner_source'      => $is_partner_source,
	];
	if ( $return_parts === 'featured_only' ) {
		return [
			'featured'       => $featured,
			'related'        => [],
			'used_ids'       => $used,
			'displayed_ids'  => $displayed,
		] + $extra;
	}
	if ( $return_parts === 'related_only' ) {
		return [
			'featured'       => [],
			'related'        => $related,
			'used_ids'       => $used,
			'displayed_ids'  => $already,
		] + $extra;
	}

	return [
		'featured'      => $featured,
		'related'       => $related,
		'used_ids'      => $used,
		'displayed_ids' => $displayed,
	] + $extra;
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
 * @param array $args  heading, show_title, layout, is_partner_source, user_screen_result, hide_group_titles, additional_result_text, debug
 * @return string HTML
 */
function mha_render_unified_featured_next_steps( $items, $args = [] ) {
	$args = wp_parse_args(
		$args, [
			'heading'                 => 'Next Steps',
			'show_title'              => true,
			'layout'                  => [],
			'is_partner_source'       => null,
			'user_screen_result'      => [],
			'hide_group_titles'       => false,
			'additional_result_text'  => [],
			'debug'                   => false,
		]
	);
	if ( $args['is_partner_source'] === null && ! empty( $args['user_screen_result'] ) && function_exists( 'mha_unified_next_steps_is_partner_source' ) ) {
		$args['is_partner_source'] = mha_unified_next_steps_is_partner_source( $args['user_screen_result'] );
	}
	$partner_class = ! empty( $args['is_partner_source'] ) ? ' partner-source' : '';
	$out = '';

	// Additional result text (same as display_featured_next_steps): from conditional featured_next_steps_test rows
	if ( ! empty( $args['additional_result_text'] ) && is_array( $args['additional_result_text'] ) ) {
		foreach ( $args['additional_result_text'] as $addl_text ) {
			$addl_text = strip_shortcodes( $addl_text );
			$addl_text = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $addl_text );
			if ( $addl_text !== '' ) {
				$out .= '<div class="featured-next-steps-test-additional-text' . esc_attr( $partner_class ) . '">';
				if ( $partner_class ) {
					$out .= '<div class="bubble round-tl cerulean normal"><div class="inner">';
				}
				$out .= $addl_text;
				if ( $partner_class ) {
					$out .= '</div></div>';
				}
				$out .= '</div>';
			}
		}
	}

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
			if ( current_user_can( 'manage_options' ) && $args['debug'] ) {
				if ( isset( $item['score'] ) && $item['score'] !== null ) {
					$pop_display = isset( $item['pop'] ) ? $item['pop'] : '—';
					$score_debug = '<br /> <span class="small text-red">(Score: ' . (int) $item['score'] . ', Popularity: #' . esc_html( $pop_display ) . ') <br /> [' . esc_html( isset( $item['score_debug'] ) ? $item['score_debug'] : '' ) . ']</span>';
				} elseif ( ! empty( $item['type'] ) ) {
					// For manual/result_manual/include_ids: show source tags (#FeaturedNextSteps #LinkGroup_XYZ etc.) when set
					$debug_content = ! empty( $item['score_debug'] ) ? $item['score_debug'] : '[' . $item['type'] . ']';
					$score_debug = '<br /> <span class="small text-red">[' . esc_html( $debug_content ) . ']</span>';
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
		if ( current_user_can( 'manage_options' ) && $args['debug'] ) {
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
