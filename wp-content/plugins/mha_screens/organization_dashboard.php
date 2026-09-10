<?php
/**
 * Organization dashboard: screen-collection org entries, aggregation, and CSV export.
 * Used by the My Account org dashboard template in the mha_s2s theme.
 *
 * @package mha_screens
 */

/**
 * Whether an `allowed_organizations` repeater row matches the given organization term ID.
 *
 * @param array<string,mixed> $row Repeater sub-row.
 * @param int                 $term_id Organization term ID.
 */
function mha_s2s_screen_collection_allowed_org_row_matches_term( array $row, $term_id ) {
	$term_id = absint( $term_id );
	if ( ! $term_id ) {
		return false;
	}
	$term = get_term( $term_id, 'organization' );
	if ( ! $term || is_wp_error( $term ) ) {
		return false;
	}
	$raw = isset( $row['organization_id'] ) ? $row['organization_id'] : null;
	if ( null === $raw || '' === $raw ) {
		return false;
	}
	if ( is_numeric( $raw ) && absint( $raw ) === $term_id ) {
		return true;
	}
	$raw_s = is_scalar( $raw ) ? trim( (string) $raw ) : '';
	if ( '' === $raw_s ) {
		return false;
	}
	if ( $raw_s === $term->name || strcasecmp( $raw_s, $term->slug ) === 0 ) {
		return true;
	}
	return false;
}

/**
 * Gravity Forms field ID whose admin label or label is "SC Organization".
 *
 * @param array<string,mixed> $form Form array from GFAPI::get_form.
 * @return int|null
 */
function mha_s2s_gf_form_sc_organization_field_id( $form ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return null;
	}
	foreach ( $form['fields'] as $field ) {
		if ( ! is_object( $field ) ) {
			continue;
		}
		$admin = isset( $field->adminLabel ) ? trim( (string) $field->adminLabel ) : '';
		$label = isset( $field->label ) ? trim( (string) $field->label ) : '';
		if ( strcasecmp( $admin, 'SC Organization' ) === 0 || strcasecmp( $label, 'SC Organization' ) === 0 ) {
			return (int) $field->id;
		}
	}
	return null;
}

/**
 * Gravity Forms field ID whose admin label or label is "SC User".
 *
 * @param array<string,mixed> $form Form array from GFAPI::get_form.
 * @return int|null
 */
function mha_s2s_gf_form_sc_user_field_id( $form ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return null;
	}
	foreach ( $form['fields'] as $field ) {
		if ( ! is_object( $field ) ) {
			continue;
		}
		$admin = isset( $field->adminLabel ) ? trim( (string) $field->adminLabel ) : '';
		$label = isset( $field->label ) ? trim( (string) $field->label ) : '';
		if ( strcasecmp( $admin, 'SC User' ) === 0 || strcasecmp( $label, 'SC User' ) === 0 ) {
			return (int) $field->id;
		}
	}
	return null;
}

/**
 * Gravity Forms field ID whose admin label or label is "Start Time".
 *
 * @param array<string,mixed> $form Form array from GFAPI::get_form.
 * @return int|null
 */
function mha_s2s_gf_form_start_time_field_id( $form ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return null;
	}
	foreach ( $form['fields'] as $field ) {
		if ( ! is_object( $field ) ) {
			continue;
		}
		$admin = isset( $field->adminLabel ) ? trim( (string) $field->adminLabel ) : '';
		$label = isset( $field->label ) ? trim( (string) $field->label ) : '';
		if ( strcasecmp( $admin, 'Start Time' ) === 0 || strcasecmp( $label, 'Start Time' ) === 0 ) {
			return (int) $field->id;
		}
	}
	return null;
}

/**
 * Format Gravity Forms entry date_created for dashboard display.
 * GF stores date_created as MySQL datetime in UTC (see GF forms model docs).
 *
 * @param mixed $raw Entry date_created string.
 * @return string Empty if no value; otherwise site-local date+time via wp_date().
 */
function mha_s2s_dashboard_format_gf_date_created_display( $raw ) {
	if ( null === $raw || '' === $raw ) {
		return '';
	}
	$s = is_string( $raw ) ? trim( $raw ) : ( is_scalar( $raw ) ? trim( (string) $raw ) : '' );
	if ( '' === $s ) {
		return '';
	}
	try {
		$utc = new DateTimeZone( 'UTC' );
		$dt  = new DateTimeImmutable( $s, $utc );
	} catch ( Exception $e ) {
		return $s;
	}
	return wp_date(
		get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
		$dt->getTimestamp(),
		wp_timezone()
	);
}

/**
 * Format a form field datetime string for dashboard display as site-local wall clock.
 * (Unlike GF date_created, field values are not UTC.)
 *
 * @param mixed $raw Field value.
 * @return string Empty if no value; otherwise localized date+time or original string if not parseable.
 */
function mha_s2s_dashboard_format_form_datetime_display( $raw ) {
	if ( null === $raw || '' === $raw ) {
		return '';
	}
	$s = is_string( $raw ) ? trim( $raw ) : ( is_scalar( $raw ) ? trim( (string) $raw ) : '' );
	if ( '' === $s ) {
		return '';
	}
	$tz = wp_timezone();
	$fmts = array(
		'Y-m-d H:i:s',
		'Y-m-d H:i',
		'Y-m-d',
		'm/d/Y H:i:s',
		'm/d/Y H:i',
		'm/d/Y g:i a',
		'm/d/Y G:i',
		'm/d/Y',
	);
	foreach ( $fmts as $fmt ) {
		$dt = DateTimeImmutable::createFromFormat( $fmt, $s, $tz );
		if ( $dt instanceof DateTimeImmutable ) {
			return wp_date(
				get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
				$dt->getTimestamp(),
				$tz
			);
		}
	}
	try {
		$dt = new DateTimeImmutable( $s, $tz );
	} catch ( Exception $e ) {
		return $s;
	}
	return wp_date(
		get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
		$dt->getTimestamp(),
		$tz
	);
}

/**
 * Whether a GF field label or admin label ends with "Score" (case-insensitive).
 *
 * @param object $field GF field object.
 * @return bool
 */
function mha_s2s_gf_field_label_ends_with_score_or_result( $field ) {
	if ( ! is_object( $field ) ) {
		return false;
	}
	$admin = isset( $field->adminLabel ) ? trim( (string) $field->adminLabel ) : '';
	$label = isset( $field->label ) ? trim( (string) $field->label ) : '';
	foreach ( array( $label, $admin ) as $text ) {
		if ( '' === $text ) {
			continue;
		}
		if ( preg_match( '/score$/i', $text ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Score column labels excluded from the org dashboard table and CSV export.
 *
 * @param string $column Column label.
 * @return bool
 */
function mha_s2s_dashboard_is_excluded_score_result_column( $column ) {
	$column = trim( (string) $column );
	if ( '' === $column ) {
		return false;
	}
	$excluded = array( 'User Score', 'User Result' );
	foreach ( $excluded as $name ) {
		if ( strcasecmp( $column, $name ) === 0 ) {
			return true;
		}
	}
	return false;
}

/**
 * Map of Gravity Forms field ID => column title for fields whose label or admin label ends with
 * "Score". Column title prefers the public label when it matches; otherwise the admin label.
 * Excludes User Score. Result columns are not included.
 *
 * @param array<string,mixed> $form Form array from GFAPI::get_form.
 * @return array<int,string>
 */
function mha_s2s_gf_form_score_result_field_map( $form ) {
	$map = array();
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return $map;
	}
	$skip_types = array( 'section', 'html', 'page', 'captcha' );
	foreach ( $form['fields'] as $field ) {
		if ( ! is_object( $field ) || empty( $field->id ) ) {
			continue;
		}
		if ( isset( $field->type ) && in_array( $field->type, $skip_types, true ) ) {
			continue;
		}
		if ( ! mha_s2s_gf_field_label_ends_with_score_or_result( $field ) ) {
			continue;
		}
		$admin = isset( $field->adminLabel ) ? trim( (string) $field->adminLabel ) : '';
		$label = isset( $field->label ) ? trim( (string) $field->label ) : '';
		$column = '';
		if ( '' !== $label && preg_match( '/score$/i', $label ) ) {
			$column = $label;
		} elseif ( '' !== $admin && preg_match( '/score$/i', $admin ) ) {
			$column = $admin;
		} else {
			$column = '' !== $label ? $label : $admin;
		}
		if ( '' === $column || mha_s2s_dashboard_is_excluded_score_result_column( $column ) ) {
			continue;
		}
		$map[ (int) $field->id ] = $column;
	}
	return $map;
}

/**
 * Resolve org dashboard date range from GET (or explicit args), defaulting to the current calendar year.
 *
 * @param array<string,mixed> $args Optional overrides: start_date, end_date (Y-m-d).
 * @return array{start_date: string, end_date: string}
 */
function mha_s2s_dashboard_resolve_org_date_range( array $args = array() ) {
	$year          = (int) wp_date( 'Y' );
	$default_start = sprintf( '%04d-01-01', $year );
	$default_end   = sprintf( '%04d-12-31', $year );

	$raw_start = '';
	$raw_end   = '';
	if ( isset( $args['start_date'] ) && is_string( $args['start_date'] ) ) {
		$raw_start = trim( $args['start_date'] );
	} elseif ( isset( $_GET['org_dash_start'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw_start = sanitize_text_field( wp_unslash( (string) $_GET['org_dash_start'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( isset( $args['end_date'] ) && is_string( $args['end_date'] ) ) {
		$raw_end = trim( $args['end_date'] );
	} elseif ( isset( $_GET['org_dash_end'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw_end = sanitize_text_field( wp_unslash( (string) $_GET['org_dash_end'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$start = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw_start ) ? $raw_start : $default_start;
	$end   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw_end ) ? $raw_end : $default_end;

	if ( strcmp( $start, $end ) > 0 ) {
		$tmp   = $start;
		$start = $end;
		$end   = $tmp;
	}

	return array(
		'start_date' => $start,
		'end_date'   => $end,
	);
}

/**
 * Entries for screen collections that allow the user's organization, limited to screens on those
 * collections whose GF form has "SC Organization" matching the org display name (or term name).
 *
 * @param int|null            $user_id Defaults to current user.
 * @param array<string,mixed> $args    Optional: start_date, end_date (Y-m-d). Defaults to current year from GET/args.
 * @return array{ok:bool,term_id:int,term_name:string,org_match_values:string[],rows:array<int,array<string,mixed>>,score_result_columns:string[],has_start_time_column:bool,start_date:string,end_date:string,message:string}
 */
function mha_s2s_dashboard_screen_collection_org_entries( $user_id = null, $args = array() ) {
	$date_range = mha_s2s_dashboard_resolve_org_date_range( is_array( $args ) ? $args : array() );
	$empty      = array(
		'ok'                     => false,
		'term_id'                => 0,
		'term_name'              => '',
		'org_match_values'       => array(),
		'rows'                   => array(),
		'score_result_columns'   => array(),
		'has_start_time_column'  => false,
		'start_date'             => $date_range['start_date'],
		'end_date'               => $date_range['end_date'],
		'message'                => '',
	);

	$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
	if ( ! $user_id || ! function_exists( 'get_field' ) || ! class_exists( 'GFAPI' ) ) {
		$empty['message'] = 'unavailable';
		return $empty;
	}

	$term_id = (int) get_field( 'screen_collection_organization', 'user_' . $user_id );
	if ( ! $term_id ) {
		$empty['message'] = 'no_organization';
		return $empty;
	}

	$term = get_term( $term_id, 'organization' );
	if ( ! $term || is_wp_error( $term ) ) {
		$empty['message'] = 'invalid_term';
		return $empty;
	}

	$collections = get_posts(
		array(
			'post_type'      => 'screen-collection',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	$form_jobs = array();
	foreach ( $collections as $collection_id ) {
		$allowed = get_field( 'allowed_organizations', $collection_id );
		if ( ! is_array( $allowed ) ) {
			continue;
		}
		$matched_display  = '';
		$allowed_for_user = false;
		foreach ( $allowed as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			if ( ! mha_s2s_screen_collection_allowed_org_row_matches_term( $row, $term_id ) ) {
				continue;
			}
			$allowed_for_user = true;
			if ( ! empty( $row['organization_display_name'] ) ) {
				$matched_display = trim( (string) $row['organization_display_name'] );
			}
			break;
		}
		if ( ! $allowed_for_user ) {
			continue;
		}

		$form_id = function_exists( 'mha_screen_collection_get_form_id' )
			? mha_screen_collection_get_form_id( $collection_id )
			: absint( get_field( 'form', $collection_id ) );
		if ( ! $form_id ) {
			continue;
		}
		$key = $collection_id . ':' . $form_id;
		if ( isset( $form_jobs[ $key ] ) ) {
			continue;
		}
		$form = GFAPI::get_form( $form_id );
		if ( ! $form || is_wp_error( $form ) ) {
			continue;
		}
		$org_field_id = mha_s2s_gf_form_sc_organization_field_id( $form );
		if ( ! $org_field_id ) {
			continue;
		}
		$sc_user_field_id      = mha_s2s_gf_form_sc_user_field_id( $form );
		$start_time_field_id   = mha_s2s_gf_form_start_time_field_id( $form );
		$form_jobs[ $key ]     = array(
			'collection_id'        => (int) $collection_id,
			'collection_title'     => get_the_title( $collection_id ),
			'screen_id'            => 0,
			'screen_title'         => get_the_title( $collection_id ),
			'form_id'              => (int) $form_id,
			'form_title'           => isset( $form['title'] ) ? (string) $form['title'] : '',
			'org_field_id'         => (int) $org_field_id,
			'sc_user_field_id'     => $sc_user_field_id ? (int) $sc_user_field_id : 0,
			'start_time_field_id'  => $start_time_field_id ? (int) $start_time_field_id : 0,
			'score_result_fields'  => mha_s2s_gf_form_score_result_field_map( $form ),
			'org_display'          => $matched_display,
		);
	}

	$score_result_columns = array();
	$has_start_time_column = false;
	foreach ( $form_jobs as $fj ) {
		if ( ! empty( $fj['start_time_field_id'] ) ) {
			$has_start_time_column = true;
		}
		if ( empty( $fj['score_result_fields'] ) || ! is_array( $fj['score_result_fields'] ) ) {
			continue;
		}
		foreach ( $fj['score_result_fields'] as $col_label ) {
			if ( ! in_array( $col_label, $score_result_columns, true ) ) {
				$score_result_columns[] = $col_label;
			}
		}
	}

	if ( ! $form_jobs ) {
		return array(
			'ok'                    => true,
			'term_id'               => $term_id,
			'term_name'             => $term->name,
			'org_match_values'      => array_unique( array_filter( array( $term->name ) ) ),
			'rows'                  => array(),
			'score_result_columns'  => array(),
			'has_start_time_column' => false,
			'start_date'            => $date_range['start_date'],
			'end_date'              => $date_range['end_date'],
			'message'               => 'no_collections',
		);
	}

	$org_values = array( $term->name );
	foreach ( $form_jobs as $job ) {
		if ( ! empty( $job['org_display'] ) ) {
			$org_values[] = $job['org_display'];
		}
	}
	$org_values = array_values( array_unique( array_filter( array_map( 'trim', $org_values ) ) ) );

	$rows       = array();
	$page_size  = ! empty( $args['fetch_all'] ) ? 100 : 400;
	$sorting    = array( 'key' => 'date_created', 'direction' => 'DESC' );
	$seen_entry = array();

	foreach ( $form_jobs as $job ) {
		foreach ( $org_values as $org_val ) {
			$search_criteria = array(
				'status'        => 'active',
				'start_date'    => $date_range['start_date'],
				'end_date'      => $date_range['end_date'] . ' 23:59:59',
				'field_filters' => array(
					array(
						'key'      => (string) $job['org_field_id'],
						'value'    => $org_val,
						'operator' => 'is',
					),
				),
			);
			$offset = 0;
			do {
				$paging  = array( 'offset' => $offset, 'page_size' => $page_size );
				$entries = GFAPI::get_entries( $job['form_id'], $search_criteria, $sorting, $paging );
				if ( is_wp_error( $entries ) || ! is_array( $entries ) ) {
					break;
				}
				$batch_count = count( $entries );
				foreach ( $entries as $entry ) {
				if ( empty( $entry['id'] ) ) {
					continue;
				}
				$eid = (int) $entry['id'];
				if ( isset( $seen_entry[ $eid ] ) ) {
					continue;
				}
				$seen_entry[ $eid ] = true;
				$fid          = (string) $job['org_field_id'];
				$org_in_entry = isset( $entry[ $fid ] ) ? (string) $entry[ $fid ] : '';

				$sc_user_val = '';
				if ( ! empty( $job['sc_user_field_id'] ) ) {
					$uid_key = (string) $job['sc_user_field_id'];
					if ( isset( $entry[ $uid_key ] ) ) {
						$sc_user_val = is_string( $entry[ $uid_key ] ) ? $entry[ $uid_key ] : (string) $entry[ $uid_key ];
					}
				}

				$start_time_raw = '';
				if ( ! empty( $job['start_time_field_id'] ) ) {
					$st_key = (string) $job['start_time_field_id'];
					if ( isset( $entry[ $st_key ] ) ) {
						$st_raw = $entry[ $st_key ];
						if ( is_array( $st_raw ) ) {
							$start_time_raw = implode(
								', ',
								array_map(
									static function ( $v ) {
										return is_scalar( $v ) ? (string) $v : '';
									},
									$st_raw
								)
							);
						} else {
							$start_time_raw = is_string( $st_raw ) ? $st_raw : (string) $st_raw;
						}
					}
				}

				$score_result = array();
				if ( ! empty( $job['score_result_fields'] ) && is_array( $job['score_result_fields'] ) ) {
					foreach ( $job['score_result_fields'] as $sr_fid => $sr_label ) {
						$sr_key = (string) $sr_fid;
						if ( ! isset( $entry[ $sr_key ] ) ) {
							$score_result[ $sr_label ] = '';
							continue;
						}
						$sr_raw = $entry[ $sr_key ];
						if ( is_array( $sr_raw ) ) {
							$score_result[ $sr_label ] = implode(
								', ',
								array_map(
									static function ( $v ) {
										return is_scalar( $v ) ? (string) $v : '';
									},
									$sr_raw
								)
							);
						} else {
							$score_result[ $sr_label ] = is_string( $sr_raw ) ? $sr_raw : (string) $sr_raw;
						}
					}
				}

				$date_created = isset( $entry['date_created'] ) ? (string) $entry['date_created'] : '';

				$rows[] = array(
					'collection_title'      => $job['collection_title'],
					'screen_title'          => $job['screen_title'],
					'form_id'               => $job['form_id'],
					'form_title'            => $job['form_title'],
					'entry_id'              => $eid,
					'date_created'          => $date_created,
					'date_created_display'  => mha_s2s_dashboard_format_gf_date_created_display( $date_created ),
					'start_time_display'    => mha_s2s_dashboard_format_form_datetime_display( $start_time_raw ),
					'sc_organization'       => $org_in_entry,
					'sc_user'               => $sc_user_val,
					'score_result'          => $score_result,
				);
				}
				$offset += $page_size;
				if ( empty( $args['fetch_all'] ) || $batch_count < $page_size ) {
					break;
				}
			} while ( true );
		}
	}

	usort(
		$rows,
		static function ( $a, $b ) {
			return strcmp( (string) $b['date_created'], (string) $a['date_created'] );
		}
	);

	return array(
		'ok'                    => true,
		'term_id'               => $term_id,
		'term_name'             => $term->name,
		'org_match_values'      => $org_values,
		'rows'                  => $rows,
		'score_result_columns'  => $score_result_columns,
		'has_start_time_column' => $has_start_time_column,
		'start_date'            => $date_range['start_date'],
		'end_date'              => $date_range['end_date'],
		'message'               => '',
	);
}

/**
 * Fixed per-test score field definitions for the aggregated org dashboard.
 *
 * @return array<int,array{id: string, title: string, field_labels: string[]}>
 */
function mha_s2s_dashboard_aggregate_per_test_definitions() {
	return array(
		array(
			'id'           => 'social_phobia',
			'title'        => 'Social Phobia',
			'field_labels' => array( 'Social Phobia Score' ),
		),
		array(
			'id'           => 'separation_anxiety',
			'title'        => 'Separation Anxiety',
			'field_labels' => array( 'Separation Anxiety Score' ),
		),
		array(
			'id'           => 'agoraphobia',
			'title'        => 'Agoraphobia',
			'field_labels' => array( 'Agoraphobia Score' ),
		),
		array(
			'id'           => 'panic_attacks',
			'title'        => 'Panic Attacks',
			'field_labels' => array( 'Panic Attacks Score' ),
		),
		array(
			'id'           => 'generalized_anxiety',
			'title'        => 'Generalized Anxiety',
			'field_labels' => array( 'Generalized Anxiety Score' ),
		),
		array(
			'id'           => 'specific_phobia',
			'title'        => 'Specific Phobia',
			'field_labels' => array( 'Specific Phobia Score' ),
		),
		array(
			'id'           => 'ocd',
			'title'        => 'OCD',
			'field_labels' => array( 'Obsessions and Compulsions Score', 'OCD Score' ),
		),
		array(
			'id'           => 'ptsd',
			'title'        => 'PTSD',
			'field_labels' => array( 'PTSD Score' ),
		),
		array(
			'id'           => 'eating_disorder',
			'title'        => 'Eating Disorder',
			'field_labels' => array( 'Eating Disorder Score' ),
		),
		array(
			'id'           => 'depression',
			'title'        => 'Depression',
			'field_labels' => array( 'Depression Score' ),
		),
		array(
			'id'           => 'mania',
			'title'        => 'Mania',
			'field_labels' => array( 'Mania Score' ),
		),
		array(
			'id'           => 'adhd',
			'title'        => 'ADHD',
			'field_labels' => array( 'ADHD Score' ),
		),
	);
}

/**
 * Aggregate org dashboard rows into per-test score distributions for Chart.js.
 *
 * @param array<string,mixed> $dash Return value of mha_s2s_dashboard_screen_collection_org_entries().
 * @return array{
 *   tests: array<int,array{id:string,title:string,field_labels:string[],labels:string[],counts:int[],mean:float|null,n:int}>,
 *   submission_count: int,
 *   start_date: string,
 *   end_date: string
 * }
 */
function mha_s2s_dashboard_aggregate_per_test_scores( array $dash ) {
	$rows       = isset( $dash['rows'] ) && is_array( $dash['rows'] ) ? $dash['rows'] : array();
	$start_date = isset( $dash['start_date'] ) ? (string) $dash['start_date'] : '';
	$end_date   = isset( $dash['end_date'] ) ? (string) $dash['end_date'] : '';
	$defs       = mha_s2s_dashboard_aggregate_per_test_definitions();

	$tests_raw = array();
	foreach ( $defs as $def ) {
		$tests_raw[ $def['id'] ] = array(
			'id'           => $def['id'],
			'title'        => $def['title'],
			'field_labels' => $def['field_labels'],
			'values'       => array(),
		);
	}

	$label_to_test = array();
	foreach ( $defs as $def ) {
		foreach ( $def['field_labels'] as $fl ) {
			$label_to_test[ strtolower( $fl ) ] = $def['id'];
		}
	}

	foreach ( $rows as $row ) {
		$cells = isset( $row['score_result'] ) && is_array( $row['score_result'] )
			? $row['score_result']
			: array();
		foreach ( $cells as $raw_label => $raw_val ) {
			$key = strtolower( trim( (string) $raw_label ) );
			if ( ! isset( $label_to_test[ $key ] ) ) {
				continue;
			}
			$val = is_string( $raw_val ) ? trim( $raw_val ) : ( is_scalar( $raw_val ) ? trim( (string) $raw_val ) : '' );
			if ( '' === $val || ! is_numeric( $val ) ) {
				continue;
			}
			$tid = $label_to_test[ $key ];
			$tests_raw[ $tid ]['values'][] = (float) $val;
		}
	}

	$tests = array();
	foreach ( $defs as $def ) {
		$raw    = $tests_raw[ $def['id'] ];
		$values = $raw['values'];
		$n      = count( $values );
		$mean   = null;
		$labels = array();
		$counts = array();

		if ( $n > 0 ) {
			$mean           = round( array_sum( $values ) / $n, 2 );
			$use_one_decimal = false;
			foreach ( $values as $v ) {
				if ( abs( $v - round( $v ) ) > 0.0001 ) {
					$use_one_decimal = true;
					break;
				}
			}
			$bins = array();
			foreach ( $values as $v ) {
				$bin_key = $use_one_decimal
					? number_format( round( $v, 1 ), 1, '.', '' )
					: (string) (int) round( $v );
				if ( ! isset( $bins[ $bin_key ] ) ) {
					$bins[ $bin_key ] = 0;
				}
				$bins[ $bin_key ]++;
			}
			uksort(
				$bins,
				static function ( $a, $b ) {
					return ( (float) $a < (float) $b ) ? -1 : ( ( (float) $a > (float) $b ) ? 1 : 0 );
				}
			);
			$labels = array_map( 'strval', array_keys( $bins ) );
			$counts = array_map( 'intval', array_values( $bins ) );
		}

		$tests[] = array(
			'id'           => $def['id'],
			'title'        => $def['title'],
			'field_labels' => $def['field_labels'],
			'labels'       => $labels,
			'counts'       => $counts,
			'mean'         => $mean,
			'n'            => $n,
		);
	}

	return array(
		'tests'            => $tests,
		'submission_count' => count( $rows ),
		'start_date'       => $start_date,
		'end_date'         => $end_date,
	);
}

/**
 * Whether the current user can use the organization dashboard export.
 *
 * @return bool
 */
function mha_s2s_dashboard_org_user_can_export() {
	if ( ! is_user_logged_in() || ! function_exists( 'get_field' ) ) {
		return false;
	}
	$user_id = get_current_user_id();
	return (bool) get_field( 'view_organization_dashboard', 'user_' . $user_id );
}

/**
 * Uploads subdirectory for org dashboard CSV exports.
 *
 * @return array{dir: string, url: string}|WP_Error
 */
function mha_s2s_dashboard_org_export_dir() {
	$upload = wp_upload_dir();
	if ( ! empty( $upload['error'] ) ) {
		return new WP_Error( 'upload_dir', (string) $upload['error'] );
	}
	$dir = trailingslashit( $upload['basedir'] ) . 'org-dashboard-exports';
	$url = trailingslashit( $upload['baseurl'] ) . 'org-dashboard-exports';
	if ( ! wp_mkdir_p( $dir ) ) {
		return new WP_Error( 'mkdir', 'Unable to create export directory.' );
	}
	return array(
		'dir' => $dir,
		'url' => $url,
	);
}

/**
 * Build CSV rows for aggregated org dashboard export.
 *
 * @param array<string,mixed> $agg Aggregate payload.
 * @return array{header: string[], rows: array<int,string[]>}
 */
function mha_s2s_dashboard_org_export_aggregated_rows( array $agg ) {
	$header = array( 'Test', 'Score', 'Count' );
	$rows   = array();
	$tests  = isset( $agg['tests'] ) && is_array( $agg['tests'] ) ? $agg['tests'] : array();
	foreach ( $tests as $test ) {
		$title  = isset( $test['title'] ) ? (string) $test['title'] : '';
		$labels = isset( $test['labels'] ) && is_array( $test['labels'] ) ? $test['labels'] : array();
		$counts = isset( $test['counts'] ) && is_array( $test['counts'] ) ? $test['counts'] : array();
		if ( ! $labels ) {
			$rows[] = array( $title, '', '' );
			continue;
		}
		foreach ( $labels as $i => $score ) {
			$rows[] = array(
				$title,
				(string) $score,
				isset( $counts[ $i ] ) ? (string) (int) $counts[ $i ] : '0',
			);
		}
	}
	return array(
		'header' => $header,
		'rows'   => $rows,
	);
}

/**
 * Build CSV header + row arrays for non-aggregated org dashboard export.
 *
 * @param array<string,mixed> $dash Dashboard payload.
 * @return array{header: string[], rows: array<int,string[]>}
 */
function mha_s2s_dashboard_org_export_table_rows( array $dash ) {
	$score_cols = isset( $dash['score_result_columns'] ) && is_array( $dash['score_result_columns'] )
		? $dash['score_result_columns']
		: array();
	$has_start  = ! empty( $dash['has_start_time_column'] );
	$header     = array( 'Submitted' );
	if ( $has_start ) {
		$header[] = 'Start Time';
	}
	$header[] = 'SC User';
	foreach ( $score_cols as $col ) {
		$header[] = (string) $col;
	}

	$rows = array();
	$src  = isset( $dash['rows'] ) && is_array( $dash['rows'] ) ? $dash['rows'] : array();
	foreach ( $src as $row ) {
		$line = array(
			isset( $row['date_created_display'] ) ? (string) $row['date_created_display'] : '',
		);
		if ( $has_start ) {
			$line[] = isset( $row['start_time_display'] ) ? (string) $row['start_time_display'] : '';
		}
		$line[]   = isset( $row['sc_user'] ) ? (string) $row['sc_user'] : '';
		$sr_cells = isset( $row['score_result'] ) && is_array( $row['score_result'] ) ? $row['score_result'] : array();
		foreach ( $score_cols as $col ) {
			$line[] = isset( $sr_cells[ $col ] ) ? (string) $sr_cells[ $col ] : '';
		}
		$rows[] = $line;
	}

	return array(
		'header' => $header,
		'rows'   => $rows,
	);
}

/**
 * AJAX: batch-export organization dashboard CSV (aggregated or row-level).
 */
function mha_s2s_dashboard_org_export_csv_ajax() {
	if ( ! mha_s2s_dashboard_org_user_can_export() ) {
		wp_send_json_error( array( 'error' => 'Unauthorized.' ), 403 );
	}

	check_ajax_referer( 'mha_org_dash_export', 'nonce' );

	$mode       = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( (string) $_POST['mode'] ) ) : 'rows';
	$paged      = isset( $_POST['paged'] ) ? max( 1, absint( $_POST['paged'] ) ) : 1;
	$filename   = isset( $_POST['filename'] ) ? sanitize_file_name( wp_unslash( (string) $_POST['filename'] ) ) : '';
	$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['start_date'] ) ) : '';
	$end_date   = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['end_date'] ) ) : '';
	$batch_size = 50;

	if ( ! in_array( $mode, array( 'aggregated', 'rows' ), true ) ) {
		$mode = 'rows';
	}

	$date_args = mha_s2s_dashboard_resolve_org_date_range(
		array(
			'start_date' => $start_date,
			'end_date'   => $end_date,
		)
	);

	$paths = mha_s2s_dashboard_org_export_dir();
	if ( is_wp_error( $paths ) ) {
		wp_send_json_error( array( 'error' => $paths->get_error_message() ) );
	}

	$user_id      = get_current_user_id();
	$transient_key = 'mha_org_dash_export_' . $user_id;
	$csv_rows      = array();
	$header        = array();

	if ( 1 === $paged ) {
		$dash = mha_s2s_dashboard_screen_collection_org_entries(
			$user_id,
			array_merge(
				$date_args,
				array( 'fetch_all' => true )
			)
		);
		if ( empty( $dash['ok'] ) ) {
			wp_send_json_error( array( 'error' => 'Unable to load organization data.' ) );
		}

		if ( 'aggregated' === $mode ) {
			$agg  = mha_s2s_dashboard_aggregate_per_test_scores( $dash );
			$built = mha_s2s_dashboard_org_export_aggregated_rows( $agg );
		} else {
			$built = mha_s2s_dashboard_org_export_table_rows( $dash );
		}
		$header   = $built['header'];
		$csv_rows = $built['rows'];

		set_transient(
			$transient_key,
			array(
				'header'     => $header,
				'rows'       => $csv_rows,
				'start_date' => $date_args['start_date'],
				'end_date'   => $date_args['end_date'],
				'mode'       => $mode,
			),
			HOUR_IN_SECONDS
		);

		$filename = sprintf(
			'org-dashboard-%s-%s-to-%s-%d.csv',
			$mode,
			$date_args['start_date'],
			$date_args['end_date'],
			time()
		);
		$filename = sanitize_file_name( $filename );
	} else {
		$cached = get_transient( $transient_key );
		if ( ! is_array( $cached ) || ! isset( $cached['rows'] ) || ! is_array( $cached['rows'] ) || '' === $filename ) {
			wp_send_json_error( array( 'error' => 'Export session expired. Please try again.' ) );
		}
		$header   = isset( $cached['header'] ) && is_array( $cached['header'] ) ? $cached['header'] : array();
		$csv_rows = $cached['rows'];
	}

	$total     = count( $csv_rows );
	$max_pages = max( 1, (int) ceil( $total / $batch_size ) );
	$offset    = ( $paged - 1 ) * $batch_size;
	$chunk     = array_slice( $csv_rows, $offset, $batch_size );

	$filepath = trailingslashit( $paths['dir'] ) . $filename;
	$fh       = fopen( $filepath, ( 1 === $paged ) ? 'w' : 'a' );
	if ( ! $fh ) {
		wp_send_json_error( array( 'error' => 'Unable to write export file.' ) );
	}
	if ( 1 === $paged && $header ) {
		fputcsv( $fh, $header );
	}
	foreach ( $chunk as $line ) {
		fputcsv( $fh, $line );
	}
	fclose( $fh );

	$percent = $total > 0 ? round( ( min( $paged, $max_pages ) / $max_pages ) * 100, 2 ) : 100;
	$done    = $paged >= $max_pages || 0 === $total;

	$result = array(
		'paged'      => $paged,
		'max'        => $max_pages,
		'percent'    => $percent,
		'filename'   => $filename,
		'start_date' => $date_args['start_date'],
		'end_date'   => $date_args['end_date'],
		'mode'       => $mode,
		'next_page'  => $done ? '' : ( $paged + 1 ),
	);

	if ( $done ) {
		$result['download'] = trailingslashit( $paths['url'] ) . rawurlencode( $filename );
		delete_transient( $transient_key );
	}

	wp_send_json_success( $result );
}
add_action( 'wp_ajax_mha_s2s_org_dashboard_export_csv', 'mha_s2s_dashboard_org_export_csv_ajax' );