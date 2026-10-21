<?php
/**
 * Recent screening results for the account dashboard.
 *
 * Entries, forms, and screen configuration are loaded once and reused.
 * The dashboard shows the newest results rather than every saved entry.
 */

const MHA_ACCOUNT_RECENT_RESULT_LIMIT = 250;

/**
 * Build the account result groups and chart series for one user.
 *
 * @param string $user_email         Gravity Forms user-email field value.
 * @param array  $hidden_entry_ids   Entry IDs the user has hidden.
 * @return array{display:array,graph:array,truncated:bool,limit:int}
 */
function mha_account_build_test_results( $user_email, $hidden_entry_ids = array() ) {
	$empty = array(
		'display'   => array(),
		'graph'     => array(),
		'truncated' => false,
		'limit'     => MHA_ACCOUNT_RECENT_RESULT_LIMIT,
	);

	$user_email = is_string( $user_email ) ? trim( $user_email ) : '';
	if ( '' === $user_email || ! class_exists( 'GF_Query' ) || ! class_exists( 'GFFormsModel' ) ) {
		return $empty;
	}

	$entry_ids = mha_account_recent_entry_ids( $user_email, $hidden_entry_ids );
	if ( empty( $entry_ids['ids'] ) ) {
		return $empty;
	}

	$loaded = ( new GF_Query() )->get_entries( $entry_ids['ids'] );
	if ( ! is_array( $loaded ) || empty( $loaded ) ) {
		return $empty;
	}

	$prepared = array();
	$post_ids = array();
	foreach ( $loaded as $entry ) {
		if ( ! is_array( $entry ) ) {
			continue;
		}
		$row = mha_account_prepare_entry( $entry );
		if ( empty( $row['screen_id'] ) ) {
			continue;
		}
		$prepared[]                    = $row;
		$post_ids[ $row['screen_id'] ] = $row['screen_id'];
	}

	if ( $post_ids ) {
		_prime_post_caches( array_values( $post_ids ), true, false );
	}

	$display = array();
	$graph   = array();

	foreach ( $prepared as $row ) {
		$post_type            = get_post_type( $row['screen_id'] );
		$row['is_collection'] = ( 'screen-collection' === $post_type );
		if ( $row['is_collection'] && '' === $row['test_link'] && ! empty( $row['entry']['partial_entry_id'] ) ) {
			$row['test_link'] = $row['entry']['partial_entry_id'];
		}

		$config = mha_account_screen_config( $row['screen_id'] );
		if ( ! in_array( $config['post_type'], array( 'screen', 'screen-collection' ), true ) ) {
			continue;
		}
		if ( ! $row['is_collection'] && $config['survey'] ) {
			continue;
		}

		$scored = mha_account_score_entry( $row, $config );
		$title  = $config['title'] ? $config['title'] : __( 'Screening', 'mha_s2s' );

		$display[ $title ][] = array(
			'screen_id'         => $row['screen_id'],
			'is_collection'     => $row['is_collection'],
			'test_id'           => $row['entry_id'],
			'test_date'         => $row['test_date'],
			'test_title'        => $title,
			'total_score'       => $scored['total_score'],
			'max_score'         => $scored['max_score'],
			'test_link'         => $row['test_link'],
			'concerns'          => $scored['concerns'],
			'result_title'      => $scored['result_title'],
			'espanol'           => $config['espanol'],
			'hide_result_score' => $config['hide_result_score'],
		);

		if ( ! $row['is_collection'] ) {
			if ( ! isset( $graph[ $title ] ) ) {
				$graph[ $title ] = array(
					'hide_scores' => $config['hide_result_score'],
					'labels'      => array(),
					'scores'      => array(),
					'max'         => $scored['max_score'],
					'steps'       => $config['chart_steps'],
				);
			}
			$graph[ $title ]['labels'][] = $row['chart_label'];
			$graph[ $title ]['scores'][] = $scored['total_score'];
		}
	}

	if ( $display ) {
		uksort( $display, 'strnatcasecmp' );
	}

	$empty['display']   = $display;
	$empty['graph']     = $graph;
	$empty['truncated'] = ! empty( $entry_ids['truncated'] );

	return $empty;
}

/**
 * Newest saved entry IDs for a user's email, excluding hidden results.
 *
 * @param string $user_email
 * @param array  $hidden_entry_ids
 * @return array{ids:int[],truncated:bool}
 */
function mha_account_recent_entry_ids( $user_email, $hidden_entry_ids ) {
	global $wpdb;

	$hidden_entry_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $hidden_entry_ids ) ) ) );
	$table            = GFFormsModel::get_entry_meta_table_name();
	$limit            = MHA_ACCOUNT_RECENT_RESULT_LIMIT;
	$params           = array( '41', $user_email );
	$not_in           = '';

	if ( $hidden_entry_ids ) {
		$not_in = ' AND entry_id NOT IN (' . implode( ',', array_fill( 0, count( $hidden_entry_ids ), '%d' ) ) . ')';
		$params = array_merge( $params, $hidden_entry_ids );
	}

	$params[] = $limit + 1;
	// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name comes from Gravity Forms and the IN list is a placeholder string.
	$sql  = $wpdb->prepare(
		"SELECT entry_id, MAX(id) AS meta_id
		FROM {$table}
		WHERE meta_key = %s AND meta_value = %s{$not_in}
		GROUP BY entry_id
		ORDER BY meta_id DESC
		LIMIT %d",
		$params
	);
	$rows = $wpdb->get_results( $sql );

	$ids = array();
	foreach ( (array) $rows as $row ) {
		$ids[] = (int) $row->entry_id;
	}

	$truncated = count( $ids ) > $limit;
	if ( $truncated ) {
		$ids = array_slice( $ids, 0, $limit );
	}

	return array(
		'ids'       => $ids,
		'truncated' => $truncated,
	);
}

/**
 * Resolve the screen, token, and question values stored on one entry.
 *
 * @param array $entry Gravity Forms entry.
 * @return array
 */
function mha_account_prepare_entry( $entry ) {
	$form_id = isset( $entry['form_id'] ) ? (int) $entry['form_id'] : 0;
	$form    = mha_account_form_bundle( $form_id );
	$screen_id = '';
	$test_link = '';

	foreach ( $entry as $key => $value ) {
		$field = mha_account_entry_field( $form['fields'], $key );
		if ( ! $field || ! isset( $field->label ) ) {
			continue;
		}
		if ( false !== strpos( $field->label, 'Screen ID' ) ) {
			$screen_id = $value;
		}
		if ( false !== strpos( $field->label, 'Token' ) && '' !== $value && null !== $value ) {
			$test_link = $value;
		}
	}

	if ( ! $screen_id && function_exists( 'mha_screen_collection_resolve_from_entry' ) ) {
		$screen_id = (int) mha_screen_collection_resolve_from_entry( $entry, $form['form'] );
	}

	$created = isset( $entry['date_created'] ) ? $entry['date_created'] : '';

	return array(
		'entry'         => $entry,
		'entry_id'      => isset( $entry['id'] ) ? (int) $entry['id'] : 0,
		'form'          => $form,
		'screen_id'     => (int) $screen_id,
		'is_collection' => false,
		'test_link'     => $test_link,
		'test_date'     => $created ? date( 'M j, Y', strtotime( $created ) ) : '',
		'chart_label'   => $created ? date( 'M', strtotime( $created ) ) : '',
	);
}

/**
 * Score one prepared entry from its cached screen configuration.
 *
 * @param array $row
 * @param array $config
 * @return array{total_score:mixed,max_score:mixed,result_title:?string,concerns:array}
 */
function mha_account_score_entry( $row, $config ) {
	$entry                    = $row['entry'];
	$total_score              = 0;
	$advanced_conditions_data = array();
	$general_score_data       = array();
	$seen_screen_id           = '';

	foreach ( $entry as $key => $value ) {
		$field = mha_account_entry_field( $row['form']['fields'], $key );
		if ( ! $field ) {
			continue;
		}

		if ( isset( $field->label ) && false !== strpos( $field->label, 'Screen ID' ) ) {
			$seen_screen_id = $value;
		}

		if ( ! isset( $field->cssClass ) || false === strpos( $field->cssClass, 'question' ) ) {
			continue;
		}

		if ( false === strpos( $field->cssClass, 'exclude' ) ) {
			$total_score += intval( $value );
		}

		foreach ( mha_account_results_for_screen( $seen_screen_id ) as $result ) {
			if ( empty( $result['advanced_conditions'] ) || ! is_array( $result['advanced_conditions'] ) ) {
				continue;
			}
			foreach ( $result['advanced_conditions'] as $condition ) {
				if ( isset( $condition['question_id'] ) && $condition['question_id'] == $field->id ) {
					$advanced_conditions_data[ $field->id ] = $value;
				}
			}
		}

		$general_score_data[ $field->id ] = $value;
	}

	$custom_results_logic = $config['custom_results_logic'];
	$custom_result_row    = '';
	if ( $custom_results_logic && function_exists( 'custom_logic_checker' ) ) {
		$custom_result = custom_logic_checker( $general_score_data, $custom_results_logic );
		if ( is_array( $custom_result ) ) {
			if ( isset( $custom_result['total_score'] ) ) {
				$total_score = $custom_result['total_score'];
			}
			$custom_result_row = isset( $custom_result['custom_result_row'] ) ? $custom_result['custom_result_row'] : '';
		}
	}

	$max_score = $config['overall_max_score'];
	if ( $total_score >= $max_score ) {
		$total_score = $max_score;
	}

	$concerns = array();
	if ( $row['is_collection'] && function_exists( 'mha_get_collection_module_results' ) ) {
		$collection = mha_get_collection_module_results( $row['screen_id'], $entry, $row['form']['form'] );
		if ( ! empty( $collection['positive'] ) && is_array( $collection['positive'] ) ) {
			foreach ( $collection['positive'] as $module ) {
				if ( ! empty( $module['module_label'] ) ) {
					$concerns[] = $module['module_label'];
				}
			}
		}
	}

	$result_title = null;
	if ( ! $config['survey'] ) {
		$result_title = mha_account_match_result_title(
			$config['results'],
			$total_score,
			$advanced_conditions_data,
			$custom_results_logic,
			$custom_result_row
		);
	}

	return array(
		'total_score'  => $total_score,
		'max_score'    => $max_score,
		'result_title' => $result_title,
		'concerns'     => $concerns,
	);
}

/**
 * Choose the same result row the account page previously selected with have_rows().
 *
 * @param array $results
 * @param mixed $total_score
 * @param array $advanced_conditions_data
 * @param mixed $custom_results_logic
 * @param mixed $custom_result_row
 * @return string|null
 */
function mha_account_match_result_title( $results, $total_score, $advanced_conditions_data, $custom_results_logic, $custom_result_row ) {
	if ( ! is_array( $results ) || empty( $results ) ) {
		return null;
	}

	$has_advanced_conditions = 0;
	$advanced_condition_row  = '';
	$advanced_counter        = '';
	$row_index               = 0;

	foreach ( $results as $result ) {
		$row_index++;
		$conditions = isset( $result['advanced_conditions'] ) ? $result['advanced_conditions'] : null;
		if ( ! is_array( $conditions ) || count( $conditions ) < 2 ) {
			continue;
		}

		$advanced_counter = count( $conditions );
		foreach ( $conditions as $condition ) {
			$advanced_id = isset( $condition['question_id'] ) ? $condition['question_id'] : null;
			if ( null === $advanced_id || ! isset( $advanced_conditions_data[ $advanced_id ] ) ) {
				continue;
			}

			$advanced_min   = isset( $condition['score_range_minimum'] ) ? $condition['score_range_minimum'] : null;
			$advanced_max   = isset( $condition['score_range_max'] ) ? $condition['score_range_max'] : null;
			$answer         = $advanced_conditions_data[ $advanced_id ];
			$matched_range  = $advanced_max && $advanced_min && $answer >= $advanced_min && $answer <= $advanced_max;
			$matched_single = ! $advanced_max && $advanced_min && $answer == $advanced_min;
			if ( $matched_range || $matched_single ) {
				$advanced_condition_row = $row_index;
				$has_advanced_conditions++;
			}
		}
	}

	if ( $has_advanced_conditions != $advanced_counter ) {
		$advanced_condition_row = 0;
	}

	$title     = null;
	$row_index = 0;
	foreach ( $results as $result ) {
		$row_index++;
		$min                          = isset( $result['score_range_minimum'] ) ? $result['score_range_minimum'] : null;
		$max                          = isset( $result['score_range_max'] ) ? $result['score_range_max'] : null;
		$custom_logic_condition_row   = isset( $result['custom_logic_condition'] ) ? $result['custom_logic_condition'] : null;
		$score_match                  = ( $total_score >= $min && $total_score <= $max );
		$advanced_match               = ( $has_advanced_conditions > 0 && $advanced_condition_row == $row_index );
		$custom_match                 = ( $custom_results_logic != '' && $custom_result_row == $custom_logic_condition_row );

		if ( ! ( $score_match || $advanced_match || $custom_match ) ) {
			continue;
		}
		if ( $has_advanced_conditions > 0 && $advanced_condition_row != $row_index ) {
			continue;
		}
		if ( $custom_results_logic != '' && $custom_result_row != $custom_logic_condition_row ) {
			continue;
		}
		if ( ! empty( $result['required_tags'] ) ) {
			continue;
		}
		if ( isset( $result['result_title'] ) ) {
			$title = $result['result_title'];
		}
	}

	return $title;
}

/**
 * Cache one Gravity Form and an ID map of its fields.
 *
 * @param int $form_id
 * @return array{form:?array,fields:array}
 */
function mha_account_form_bundle( $form_id ) {
	static $bundles = array();

	$form_id = (int) $form_id;
	if ( isset( $bundles[ $form_id ] ) ) {
		return $bundles[ $form_id ];
	}

	$form   = ( $form_id && class_exists( 'GFAPI' ) ) ? GFAPI::get_form( $form_id ) : null;
	$fields = array();
	if ( is_array( $form ) && ! empty( $form['fields'] ) ) {
		mha_account_index_form_fields( $form['fields'], $fields );
	}

	$bundles[ $form_id ] = array(
		'form'   => is_array( $form ) ? $form : null,
		'fields' => $fields,
	);

	return $bundles[ $form_id ];
}

/**
 * Index form fields by ID, including nested fields.
 *
 * @param array $form_fields
 * @param array $fields
 */
function mha_account_index_form_fields( $form_fields, &$fields ) {
	foreach ( $form_fields as $field ) {
		if ( ! is_object( $field ) ) {
			continue;
		}
		$field_id = (int) $field->id;
		if ( $field_id && ! isset( $fields[ $field_id ] ) ) {
			$fields[ $field_id ] = $field;
		}
		if ( ! empty( $field->fields ) && is_array( $field->fields ) ) {
			mha_account_index_form_fields( $field->fields, $fields );
		}
	}
}

/**
 * Match Gravity Forms' numeric field lookup for an entry key.
 *
 * @param array      $fields
 * @param int|string $key
 * @return object|null
 */
function mha_account_entry_field( $fields, $key ) {
	if ( ! is_scalar( $key ) || ! preg_match( '/^\d+(\.\w+)?$/', (string) $key ) ) {
		return null;
	}

	$field_id = (int) $key;
	if ( ! $field_id || ! isset( $fields[ $field_id ] ) ) {
		return null;
	}

	return $fields[ $field_id ];
}

/**
 * Result rows visible at the moment a question is scored.
 *
 * An empty screen ID uses the current post, matching the previous loop.
 *
 * @param int|string $screen_id
 * @return array
 */
function mha_account_results_for_screen( $screen_id ) {
	static $results = array();

	$key = (string) $screen_id;
	if ( isset( $results[ $key ] ) ) {
		return $results[ $key ];
	}

	if ( ! $screen_id ) {
		$rows = function_exists( 'get_field' ) ? get_field( 'results' ) : array();
	} else {
		$rows = mha_account_screen_config( absint( $screen_id ) )['results'];
	}

	$results[ $key ] = is_array( $rows ) ? $rows : array();

	return $results[ $key ];
}

/**
 * Cache the ACF configuration used to score and label one screen.
 *
 * @param int $screen_id
 * @return array
 */
function mha_account_screen_config( $screen_id ) {
	static $configs = array();

	$screen_id = absint( $screen_id );
	if ( isset( $configs[ $screen_id ] ) ) {
		return $configs[ $screen_id ];
	}

	$results = $screen_id && function_exists( 'get_field' ) ? get_field( 'results', $screen_id ) : array();
	if ( ! is_array( $results ) ) {
		$results = array();
	}

	$configs[ $screen_id ] = array(
		'post_type'            => $screen_id ? (string) get_post_type( $screen_id ) : '',
		'title'                => $screen_id ? get_the_title( $screen_id ) : '',
		'survey'               => $screen_id && function_exists( 'get_field' ) ? get_field( 'survey', $screen_id ) : false,
		'results'              => $results,
		'custom_results_logic' => $screen_id && function_exists( 'get_field' ) ? get_field( 'custom_results_logic', $screen_id ) : '',
		'overall_max_score'    => $screen_id && function_exists( 'get_field' ) ? get_field( 'overall_max_score', $screen_id ) : null,
		'hide_result_score'    => $screen_id && function_exists( 'get_field' ) ? get_field( 'hide_result_score', $screen_id ) : false,
		'chart_steps'          => $screen_id && function_exists( 'get_field' ) ? get_field( 'chart_steps', $screen_id ) : null,
		'espanol'              => $screen_id && function_exists( 'get_field' ) ? get_field( 'espanol', $screen_id ) : false,
	);

	return $configs[ $screen_id ];
}
