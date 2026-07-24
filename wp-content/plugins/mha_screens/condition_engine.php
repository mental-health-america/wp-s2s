<?php
/**
 * Reusable nested condition evaluation for Featured Next Steps and shortcodes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @var array|null */
global $mha_condition_context;

/**
 * @return array<string, mixed>
 */
function mha_get_default_condition_context() {
	return array(
		'result_title'       => '',
		'answered_demos'     => array(),
		'general_score_data' => array(),
		'user_screen_result' => array(),
		'screen_id'          => '',
		'referer'            => '',
	);
}

/**
 * @param array<string, mixed> $context
 */
function mha_set_condition_context( $context ) {
	global $mha_condition_context;
	$mha_condition_context = wp_parse_args( $context, mha_get_default_condition_context() );
	if ( ! isset( $mha_condition_context['user_screen_result'] ) || ! is_array( $mha_condition_context['user_screen_result'] ) ) {
		$mha_condition_context['user_screen_result'] = array();
	}
	if ( ! isset( $mha_condition_context['user_screen_result']['general_score_data'] ) ) {
		$mha_condition_context['user_screen_result']['general_score_data'] = $mha_condition_context['general_score_data'] ?? array();
	}
}

/**
 * @return array<string, mixed>|null
 */
function mha_get_condition_context() {
	global $mha_condition_context;
	return is_array( $mha_condition_context ) ? $mha_condition_context : null;
}

/**
 * @return array<string, string>
 */
function mha_condition_operator_aliases() {
	return array(
		'is'                   => 'equals',
		'equals'               => 'equals',
		'one of'               => 'one_of',
		'one_of'               => 'one_of',
		'any'                  => 'one_of',
		'none of'              => 'none_of',
		'none_of'              => 'none_of',
		'none'                 => 'none_of',
		'contains'             => 'contains',
		'contain'              => 'contains',
		'does not contain'     => 'not_contains',
		'not_contains'         => 'not_contains',
		'not_contain'          => 'not_contains',
		'starts with'          => 'starts_with',
		'starts_with'          => 'starts_with',
		'ends with'            => 'ends_with',
		'ends_with'            => 'ends_with',
		'does not equal'       => 'not_equals',
		'not_equals'           => 'not_equals',
		'is_not'               => 'not_equals',
		'does not start with'  => 'not_starts_with',
		'not_starts_with'      => 'not_starts_with',
		'does not end with'    => 'not_ends_with',
		'not_ends_with'        => 'not_ends_with',
		'exists'               => 'exists',
		'not null'             => 'not_null',
		'not_null'             => 'not_null',
		'is null'              => 'is_null',
		'is_null'              => 'is_null',
		'greater than'         => 'greater_than',
		'greater_than'         => 'greater_than',
		'greater'              => 'greater_than',
		'less than'            => 'less_than',
		'less_than'            => 'less_than',
		'less'                 => 'less_than',
	);
}

/**
 * @return array<string, string>
 */
function mha_condition_type_aliases() {
	return array(
		'Test Result'           => 'test_result',
		'test_result'           => 'test_result',
		'URL Parameter'         => 'url_parameter',
		'url_parameter'         => 'url_parameter',
		'Question Response'     => 'question_response',
		'question_response'     => 'question_response',
		'Demographic Response'  => 'demographic_response',
		'demographic_response'  => 'demographic_response',
	);
}

/**
 * @param mixed $value
 * @return array<int, string>
 */
function mha_condition_normalize_values( $value ) {
	if ( is_array( $value ) ) {
		$values = array();
		foreach ( $value as $item ) {
			if ( is_scalar( $item ) ) {
				$item = trim( (string) $item );
				if ( $item !== '' ) {
					$values[] = $item;
				}
			}
		}
		return $values;
	}

	if ( ! is_scalar( $value ) ) {
		return array();
	}

	$value = trim( (string) $value );
	if ( $value === '' ) {
		return array();
	}

	if ( str_contains( $value, '|' ) ) {
		return array_values(
			array_filter(
				array_map( 'trim', explode( '|', $value ) ),
				static function ( $item ) {
					return $item !== '';
				}
			)
		);
	}

	return array( $value );
}

/**
 * @param string $operator
 */
function mha_condition_normalize_operator( $operator ) {
	$operator = strtolower( trim( (string) $operator ) );
	$aliases  = mha_condition_operator_aliases();
	return $aliases[ $operator ] ?? $operator;
}

/**
 * @param string $type
 */
function mha_condition_normalize_type( $type ) {
	$type    = trim( (string) $type );
	$aliases = mha_condition_type_aliases();
	return $aliases[ $type ] ?? strtolower( str_replace( ' ', '_', $type ) );
}

/**
 * @return array<string, string> Slug => admin label
 */
function mha_get_condition_block_choices() {
	$blocks = get_field( 'mha_condition_blocks', 'options' );
	if ( ! is_array( $blocks ) ) {
		return array();
	}

	$choices = array();
	foreach ( $blocks as $block ) {
		if ( empty( $block['slug'] ) ) {
			continue;
		}

		$slug  = sanitize_title( $block['slug'] );
		$title = trim( (string) ( $block['title'] ?? '' ) );
		$choices[ $slug ] = $title !== '' ? $slug . ' — ' . $title : $slug;
	}

	return $choices;
}

/**
 * Populate Featured Next Steps condition block picker from Global Options.
 *
 * @param array $field
 */
function mha_load_condition_block_slug_choices( $field ) {
	$field['choices'] = mha_get_condition_block_choices();
	return $field;
}

/**
 *
 * @param mixed $definition
 * @return array<string, mixed>|WP_Error
 */
function mha_prepare_condition_tree_from_definition( $definition ) {
	$tree = mha_parse_condition_block_definition( $definition );
	if ( ! is_array( $tree ) ) {
		return new WP_Error( 'mha_condition_invalid_json', 'Condition definition must be valid JSON.' );
	}

	if ( empty( $tree['operator'] ) && ! empty( $tree['conditions'] ) ) {
		$tree['operator'] = 'and';
	}

	return $tree;
}

/**
 * Parse a condition block definition from ACF (string JSON or array).
 *
 * @param mixed $value
 * @return array<string, mixed>|null
 */
function mha_parse_condition_block_definition( $value ) {
	if ( is_array( $value ) ) {
		return $value;
	}

	if ( ! is_string( $value ) ) {
		return null;
	}

	$definition = wp_unslash( trim( $value ) );
	if ( $definition === '' ) {
		return null;
	}

	// Strip UTF-8 BOM and decode common HTML entities from the code editor.
	$definition = preg_replace( '/^\xEF\xBB\xBF/', '', $definition );
	$definition = html_entity_decode( $definition, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	$tree = json_decode( $definition, true );
	if ( is_array( $tree ) ) {
		return $tree;
	}

	// Some editors/store paths double-encode quotes; try one stripslashes pass.
	$tree = json_decode( stripslashes( $definition ), true );
	if ( is_array( $tree ) ) {
		return $tree;
	}

	return null;
}

/**
 * @param string $slug
 * @return array<string, mixed>|WP_Error
 */
function mha_get_condition_block( $slug ) {
	$slug = sanitize_title( (string) $slug );
	if ( $slug === '' ) {
		return new WP_Error( 'mha_condition_invalid_slug', 'Condition block slug is required.' );
	}

	$blocks = get_field( 'mha_condition_blocks', 'options' );
	if ( ! is_array( $blocks ) ) {
		return new WP_Error( 'mha_condition_not_found', 'Condition block not found.' );
	}

	foreach ( $blocks as $block ) {
		if ( empty( $block['slug'] ) ) {
			continue;
		}
		if ( sanitize_title( $block['slug'] ) !== $slug ) {
			continue;
		}

		$definition = $block['definition'] ?? '';
		$tree       = mha_prepare_condition_tree_from_definition( $definition );
		if ( is_wp_error( $tree ) ) {
			return $tree;
		}

		$tree['slug']       = $slug;
		$tree['title']      = $block['title'] ?? '';
		$tree['debug_mode'] = ! empty( $block['debug_mode'] );

		return $tree;
	}

	return new WP_Error( 'mha_condition_not_found', 'Condition block not found.' );
}

/**
 * @param array<string, mixed> $node
 * @param array<string, mixed> $context
 * @param bool                 $debug
 * @param array<int, string>   $visited_refs
 * @param array<int, mixed>    $trace
 */
function mha_evaluate_condition_tree( $node, $context = null, $debug = false, $visited_refs = array(), &$trace = array() ) {
	if ( null === $context ) {
		$context = mha_get_condition_context();
	}
	if ( ! is_array( $context ) ) {
		return false;
	}

	if ( isset( $node['ref'] ) ) {
		$slug = sanitize_title( (string) $node['ref'] );
		if ( $slug === '' || in_array( $slug, $visited_refs, true ) ) {
			if ( $debug ) {
				$trace[] = array(
					'node'    => 'ref',
					'slug'    => $slug,
					'matched' => false,
					'error'   => 'Missing or circular reference.',
				);
			}
			return false;
		}

		$block = mha_get_condition_block( $slug );
		if ( is_wp_error( $block ) ) {
			if ( $debug ) {
				$trace[] = array(
					'node'    => 'ref',
					'slug'    => $slug,
					'matched' => false,
					'error'   => $block->get_error_message(),
				);
			}
			return false;
		}

		$visited_refs[] = $slug;
		$block_debug    = $debug || ! empty( $block['debug_mode'] ) || mha_condition_debug_enabled();
		$matched        = mha_evaluate_condition_tree( $block, $context, $block_debug, $visited_refs, $trace );

		if ( $debug ) {
			$trace[] = array(
				'node'    => 'ref',
				'slug'    => $slug,
				'matched' => $matched,
			);
		}

		return $matched;
	}

	if ( isset( $node['type'] ) ) {
		$matched = mha_evaluate_condition_leaf( $node, $context );
		if ( $debug ) {
			$trace[] = array(
				'node'    => 'leaf',
				'type'    => $node['type'] ?? '',
				'key'     => $node['key'] ?? '',
				'condition' => $node['condition'] ?? '',
				'value'   => $node['value'] ?? '',
				'matched' => $matched,
			);
		}
		return $matched;
	}

	if ( empty( $node['conditions'] ) || ! is_array( $node['conditions'] ) ) {
		return false;
	}

	$operator = strtolower( (string) ( $node['operator'] ?? 'and' ) );
	$results  = array();

	foreach ( $node['conditions'] as $child ) {
		if ( ! is_array( $child ) ) {
			continue;
		}
		$child_trace = array();
		$results[]   = mha_evaluate_condition_tree( $child, $context, $debug, $visited_refs, $child_trace );
		if ( $debug ) {
			$trace[] = array(
				'node'      => 'group_child',
				'operator'  => $operator,
				'matched'   => end( $results ),
				'children'  => $child_trace,
			);
		}
	}

	if ( $operator === 'or' ) {
		$matched = in_array( true, $results, true );
	} else {
		$matched = ! empty( $results ) && ! in_array( false, $results, true );
	}

	if ( $debug ) {
		$trace[] = array(
			'node'     => 'group',
			'operator' => $operator,
			'matched'  => $matched,
		);
	}

	return $matched;
}

/**
 * @param array<string, mixed> $leaf
 * @param array<string, mixed> $context
 */
function mha_evaluate_condition_leaf( $leaf, $context ) {
	$type      = mha_condition_normalize_type( $leaf['type'] ?? '' );
	$condition = mha_condition_normalize_operator( $leaf['condition'] ?? '' );
	$key       = isset( $leaf['key'] ) ? (string) $leaf['key'] : '';
	$value     = $leaf['value'] ?? '';
	$values    = mha_condition_normalize_values( $value );

	$result_title = (string) ( $context['result_title'] ?? '' );
	$answered     = is_array( $context['answered_demos'] ?? null ) ? $context['answered_demos'] : array();
	$score_data   = array();
	if ( isset( $context['user_screen_result']['general_score_data'] ) && is_array( $context['user_screen_result']['general_score_data'] ) ) {
		$score_data = $context['user_screen_result']['general_score_data'];
	} elseif ( isset( $context['general_score_data'] ) && is_array( $context['general_score_data'] ) ) {
		$score_data = $context['general_score_data'];
	}

	switch ( $type ) {
		case 'test_result':
			return mha_condition_evaluate_scalar( $result_title, $condition, $values, $value );
		case 'url_parameter':
			$get_key = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : null;
			return mha_condition_evaluate_url_parameter( $get_key, $condition, $values, $value );
		case 'question_response':
			$answer = $score_data[ $key ] ?? null;
			return mha_condition_evaluate_scalar( $answer, $condition, $values, $value );
		case 'demographic_response':
			return mha_condition_evaluate_demographic( $answered, $key, $condition, $values, $value );
	}

	return false;
}

/**
 * @param mixed                $subject
 * @param string               $condition
 * @param array<int, string>   $values
 * @param mixed                $raw_value
 */
function mha_condition_evaluate_scalar( $subject, $condition, $values, $raw_value ) {
	$subject = is_scalar( $subject ) ? (string) $subject : '';

	switch ( $condition ) {
		case 'equals':
			return $subject === (string) $raw_value;
		case 'contains':
			return $subject !== '' && str_contains( $subject, (string) $raw_value );
		case 'starts_with':
			return $subject !== '' && str_starts_with( $subject, (string) $raw_value );
		case 'ends_with':
			return $subject !== '' && str_ends_with( $subject, (string) $raw_value );
		case 'not_equals':
			return $subject !== (string) $raw_value;
		case 'not_contains':
			return $subject === '' || ! str_contains( $subject, (string) $raw_value );
		case 'not_starts_with':
			return $subject === '' || ! str_starts_with( $subject, (string) $raw_value );
		case 'not_ends_with':
			return $subject === '' || ! str_ends_with( $subject, (string) $raw_value );
		case 'exists':
		case 'not_null':
			return $subject !== '';
		case 'is_null':
			return $subject === '';
		case 'none_of':
			foreach ( $values as $item ) {
				if ( $subject === $item ) {
					return false;
				}
			}
			return true;
		case 'one_of':
			foreach ( $values as $item ) {
				if ( $subject === $item ) {
					return true;
				}
			}
			return false;
		case 'greater_than':
			return is_numeric( $subject ) && is_numeric( $raw_value ) && (float) $subject > (float) $raw_value;
		case 'less_than':
			return is_numeric( $subject ) && is_numeric( $raw_value ) && (float) $subject < (float) $raw_value;
	}

	return false;
}

/**
 * @param string|null          $get_key
 * @param string               $condition
 * @param array<int, string>   $values
 * @param mixed                $raw_value
 */
function mha_condition_evaluate_url_parameter( $get_key, $condition, $values, $raw_value ) {
	switch ( $condition ) {
		case 'equals':
			return $get_key === (string) $raw_value;
		case 'contains':
			return $get_key && str_contains( $get_key, (string) $raw_value );
		case 'starts_with':
			return $get_key && str_starts_with( $get_key, (string) $raw_value );
		case 'ends_with':
			return $get_key && str_ends_with( $get_key, (string) $raw_value );
		case 'not_equals':
			return $get_key !== (string) $raw_value;
		case 'not_contains':
			return ! $get_key || ! str_contains( $get_key, (string) $raw_value );
		case 'not_starts_with':
			return ! $get_key || ! str_starts_with( $get_key, (string) $raw_value );
		case 'not_ends_with':
			return ! $get_key || ! str_ends_with( $get_key, (string) $raw_value );
		case 'exists':
		case 'not_null':
			return (bool) $get_key;
		case 'is_null':
			return ! $get_key;
		case 'none_of':
			if ( ! $get_key ) {
				return true;
			}
			$get_key_exp = explode( '|', $get_key );
			foreach ( $get_key_exp as $gke ) {
				foreach ( $values as $cve ) {
					if ( $gke === $cve ) {
						return false;
					}
				}
			}
			return true;
		case 'one_of':
			if ( ! $get_key ) {
				return false;
			}
			$get_key_exp = explode( '|', $get_key );
			foreach ( $get_key_exp as $gke ) {
				$gke_parts = explode( ',', $gke );
				foreach ( $gke_parts as $part ) {
					foreach ( $values as $cve ) {
						if ( trim( $part ) === $cve ) {
							return true;
						}
					}
				}
			}
			return false;
		case 'greater_than':
			return $get_key && is_numeric( $get_key ) && is_numeric( $raw_value ) && (float) $get_key > (float) $raw_value;
		case 'less_than':
			return $get_key && is_numeric( $get_key ) && is_numeric( $raw_value ) && (float) $get_key < (float) $raw_value;
	}

	return false;
}

/**
 * @param array<string, array<int, mixed>> $answered
 * @param string                           $key
 * @param string                           $condition
 * @param array<int, string>               $values
 * @param mixed                            $raw_value
 */
function mha_condition_evaluate_demographic( $answered, $key, $condition, $values, $raw_value ) {
	switch ( $condition ) {
		case 'equals':
			if ( ! isset( $answered[ $key ] ) ) {
				return false;
			}
			$temp_score = 0;
			foreach ( $answered[ $key ] as $ck ) {
				if ( (string) $ck === (string) $raw_value ) {
					$temp_score++;
				}
			}
			return $temp_score === 1;
		case 'contains':
			if ( ! isset( $answered[ $key ] ) ) {
				return false;
			}
			foreach ( $answered[ $key ] as $dr ) {
				if ( (string) $dr === (string) $raw_value ) {
					return true;
				}
			}
			return false;
		case 'not_equals':
			if ( ! isset( $answered[ $key ] ) ) {
				return false;
			}
			$total_drs  = $answered[ $key ];
			$temp_score = 0;
			foreach ( $answered[ $key ] as $dr ) {
				if ( (string) $dr !== (string) $raw_value ) {
					$temp_score++;
				}
			}
			return count( $total_drs ) === $temp_score;
		case 'exists':
		case 'not_null':
			return isset( $answered[ $key ] );
		case 'is_null':
			return ! isset( $answered[ $key ] );
		case 'starts_with':
			if ( ! isset( $answered[ $key ] ) ) {
				return false;
			}
			foreach ( $answered[ $key ] as $dr ) {
				$dr = (string) $dr;
				if ( $dr !== '' && str_starts_with( $dr, (string) $raw_value ) ) {
					return true;
				}
			}
			return false;
		case 'ends_with':
			if ( ! isset( $answered[ $key ] ) ) {
				return false;
			}
			foreach ( $answered[ $key ] as $dr ) {
				$dr = (string) $dr;
				if ( $dr !== '' && str_ends_with( $dr, (string) $raw_value ) ) {
					return true;
				}
			}
			return false;
		case 'not_starts_with':
			if ( ! isset( $answered[ $key ] ) || $answered[ $key ] === array() ) {
				return false;
			}
			foreach ( $answered[ $key ] as $dr ) {
				if ( str_starts_with( (string) $dr, (string) $raw_value ) ) {
					return false;
				}
			}
			return true;
		case 'not_ends_with':
			if ( ! isset( $answered[ $key ] ) || $answered[ $key ] === array() ) {
				return false;
			}
			foreach ( $answered[ $key ] as $dr ) {
				if ( str_ends_with( (string) $dr, (string) $raw_value ) ) {
					return false;
				}
			}
			return true;
		case 'none_of':
			if ( ! isset( $answered[ $key ] ) ) {
				return true;
			}
			foreach ( $values as $cve ) {
				foreach ( $answered[ $key ] as $ck ) {
					if ( (string) $ck === (string) $cve ) {
						return false;
					}
				}
			}
			return true;
		case 'one_of':
			if ( ! isset( $answered[ $key ] ) ) {
				return false;
			}
			foreach ( $values as $cve ) {
				foreach ( $answered[ $key ] as $ck ) {
					if ( (string) $ck === (string) $cve ) {
						return true;
					}
				}
			}
			return false;
		case 'greater_than':
			if ( ! isset( $answered[ $key ] ) ) {
				return false;
			}
			$demo_value = $answered[ $key ];
			if ( is_array( $demo_value ) ) {
				$demo_value = array_sum( $demo_value );
			}
			$demo_value    = is_numeric( $demo_value ) ? (float) $demo_value : 0;
			$con_value_num = is_numeric( $raw_value ) ? (float) $raw_value : 0;
			return $demo_value > $con_value_num;
		case 'less_than':
			if ( ! isset( $answered[ $key ] ) ) {
				return false;
			}
			$demo_value = $answered[ $key ];
			if ( is_array( $demo_value ) ) {
				$demo_value = array_sum( $demo_value );
			}
			$demo_value    = is_numeric( $demo_value ) ? (float) $demo_value : 0;
			$con_value_num = is_numeric( $raw_value ) ? (float) $raw_value : 0;
			return $demo_value < $con_value_num;
	}

	return false;
}

function mha_condition_debug_enabled() {
	if ( ! function_exists( 'get_field' ) ) {
		return ! empty( get_option( 'options_mha_condition_debug' ) );
	}

	$value = get_field( 'mha_condition_debug', 'options' );
	if ( $value === null || $value === false ) {
		$value = get_option( 'options_mha_condition_debug', false );
	}

	return ! empty( $value );
}

function mha_condition_debug_visible() {
	return mha_condition_debug_enabled() && current_user_can( 'manage_options' );
}

/**
 * Human-readable label for a condition block trace leaf node.
 *
 * @param array<string, mixed> $item
 * @param array<string, mixed> $context
 */
function mha_format_condition_trace_leaf_label( $item, $context ) {
	$type      = (string) ( $item['type'] ?? '' );
	$condition = (string) ( $item['condition'] ?? '' );
	$key       = (string) ( $item['key'] ?? '' );
	$value     = $item['value'] ?? '';
	$expected  = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );

	switch ( $type ) {
		case 'test_result':
			$actual = (string) ( $context['result_title'] ?? '' );
			return sprintf( 'test_result %s (actual: "%s", expected: "%s")', $condition, $actual, $expected );
		case 'url_parameter':
			$actual = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
			return sprintf( 'url_parameter %s param "%s" (actual: "%s", expected: "%s")', $condition, $key, $actual, $expected );
		case 'question_response':
			$score_data = $context['user_screen_result']['general_score_data'] ?? $context['general_score_data'] ?? array();
			$actual     = isset( $score_data[ $key ] ) ? (string) $score_data[ $key ] : '(not set)';
			return sprintf( 'question_response %s Q%s (actual: "%s", expected: "%s")', $condition, $key, $actual, $expected );
		case 'demographic_response':
			$actual = '(not set)';
			if ( isset( $context['answered_demos'][ $key ] ) ) {
				$actual = implode( ', ', array_map( 'strval', (array) $context['answered_demos'][ $key ] ) );
			}
			return sprintf( 'demographic_response %s "%s" (actual: "%s", expected: "%s")', $condition, $key, $actual, $expected );
		default:
			return sprintf( '%s %s %s=%s', $type, $condition, $key, $expected );
	}
}

/**
 * Format nested condition block trace as indented log lines.
 *
 * @param array<int, mixed>    $trace
 * @param array<string, mixed> $context
 * @param int                  $depth
 * @return array<int, string>
 */
function mha_format_condition_trace_lines( $trace, $context = null, $depth = 0 ) {
	if ( ! is_array( $trace ) || empty( $trace ) ) {
		return array();
	}

	if ( null === $context ) {
		$context = mha_get_condition_context();
	}

	$lines = array();
	$pad   = str_repeat( '  ', max( 0, $depth ) );

	foreach ( $trace as $item ) {
		if ( ! is_array( $item ) ) {
			continue;
		}

		$node    = (string) ( $item['node'] ?? '' );
		$matched = ! empty( $item['matched'] );
		$status  = $matched ? 'PASS' : 'FAIL';

		if ( $node === 'group_child' ) {
			if ( ! empty( $item['children'] ) && is_array( $item['children'] ) ) {
				$lines = array_merge( $lines, mha_format_condition_trace_lines( $item['children'], $context, $depth ) );
			}
			continue;
		}

		if ( $node === 'ref' ) {
			$slug = (string) ( $item['slug'] ?? '' );
			if ( ! empty( $item['error'] ) ) {
				$lines[] = sprintf( '%sref %s — %s → FAIL', $pad, $slug, $item['error'] );
			} else {
				$lines[] = sprintf( '%sref %s → %s', $pad, $slug, $status );
			}
			continue;
		}

		if ( $node === 'leaf' ) {
			$lines[] = sprintf( '%s%s → %s', $pad, mha_format_condition_trace_leaf_label( $item, $context ), $status );
			continue;
		}

		if ( $node === 'group' ) {
			$operator = strtoupper( (string) ( $item['operator'] ?? 'and' ) );
			$lines[]  = sprintf( '%s%s group → %s', $pad, $operator, $status );
		}
	}

	return $lines;
}

/**
 * @param mixed $valid
 * @param mixed $value
 * @param array $field
 */
function mha_validate_condition_block_definition( $valid, $value, $field ) {
	if ( $valid !== true ) {
		return $valid;
	}

	if ( is_string( $value ) && trim( wp_unslash( $value ) ) === '' ) {
		return true;
	}

	$tree = mha_parse_condition_block_definition( $value );
	if ( ! is_array( $tree ) ) {
		return 'Condition block definition must be valid JSON. Check for smart quotes or stray characters outside the { } block.';
	}

	$error = mha_validate_condition_tree_node( $tree, 0 );
	if ( is_string( $error ) ) {
		return $error;
	}

	return $valid;
}

/**
 * @param array<string, mixed> $node
 * @param int                  $depth
 * @return true|string
 */
function mha_validate_condition_tree_node( $node, $depth ) {
	if ( $depth > 8 ) {
		return 'Condition block exceeds maximum nesting depth.';
	}

	$allowed_leaf_keys = array( 'type', 'condition', 'key', 'value', 'exclude' );
	$allowed_group_keys = array( 'operator', 'conditions', 'ref', 'slug', 'title', 'debug_mode' );

	if ( isset( $node['ref'] ) ) {
		$slug = sanitize_title( (string) $node['ref'] );
		if ( $slug === '' || ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
			return 'Condition block reference slug is invalid.';
		}
		return true;
	}

	if ( isset( $node['type'] ) ) {
		$type = mha_condition_normalize_type( $node['type'] );
		if ( ! in_array( $type, array( 'test_result', 'url_parameter', 'question_response', 'demographic_response' ), true ) ) {
			return 'Unsupported condition type: ' . $node['type'];
		}
		$condition = mha_condition_normalize_operator( $node['condition'] ?? '' );
		$allowed   = array_keys( mha_condition_operator_aliases() );
		$canonical = array_unique( array_values( mha_condition_operator_aliases() ) );
		if ( ! in_array( $condition, $canonical, true ) && ! in_array( $node['condition'] ?? '', $allowed, true ) ) {
			return 'Unsupported condition operator: ' . ( $node['condition'] ?? '' );
		}
		foreach ( array_keys( $node ) as $key ) {
			if ( ! in_array( $key, $allowed_leaf_keys, true ) ) {
				return 'Unknown key in leaf condition: ' . $key;
			}
		}
		return true;
	}

	if ( ! isset( $node['conditions'] ) || ! is_array( $node['conditions'] ) ) {
		return 'Condition group must include a conditions array.';
	}

	$operator = strtolower( (string) ( $node['operator'] ?? 'and' ) );
	if ( ! in_array( $operator, array( 'and', 'or' ), true ) ) {
		return 'Condition group operator must be "and" or "or".';
	}

	foreach ( array_keys( $node ) as $key ) {
		if ( ! in_array( $key, $allowed_group_keys, true ) ) {
			return 'Unknown key in condition group: ' . $key;
		}
	}

	foreach ( $node['conditions'] as $child ) {
		if ( ! is_array( $child ) ) {
			return 'Each child condition must be an object.';
		}
		$error = mha_validate_condition_tree_node( $child, $depth + 1 );
		if ( is_string( $error ) ) {
			return $error;
		}
	}

	return true;
}

/**
 * @param mixed $valid
 * @param mixed $value
 */
function mha_validate_condition_block_slug( $valid, $value ) {
	if ( $valid !== true ) {
		return $valid;
	}
	$slug = sanitize_title( (string) $value );
	if ( $slug === '' ) {
		return 'Condition block slug is required.';
	}
	if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
		return 'Slug may only contain lowercase letters, numbers, and hyphens.';
	}
	return $valid;
}

/**
 * Validate featured next steps slug field when provided.
 *
 * @param mixed $valid
 * @param mixed $value
 */
function mha_validate_optional_condition_block_slug( $valid, $value ) {
	if ( $valid !== true || trim( (string) $value ) === '' ) {
		return $valid;
	}

	$slug = sanitize_title( (string) $value );
	if ( ! preg_match( '/^[a-z0-9-]+$/', $slug ) ) {
		return 'Slug may only contain lowercase letters, numbers, and hyphens.';
	}

	return $valid;
}

/**
 * @param array<string, mixed> $atts
 * @param string|null          $content
 */
function mha_conditional_shortcode( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'id'     => '',
			'action' => 'show',
		),
		$atts,
		'mha_conditional'
	);

	$slug   = sanitize_title( $atts['id'] );
	$action = strtolower( $atts['action'] ) === 'hide' ? 'hide' : 'show';

	if ( $slug === '' || ! mha_get_condition_context() ) {
		return '';
	}

	$block = mha_get_condition_block( $slug );
	if ( is_wp_error( $block ) ) {
		return '';
	}

	$trace   = array();
	$debug   = mha_condition_debug_enabled() || ! empty( $block['debug_mode'] );
	$matched = mha_evaluate_condition_tree( $block, mha_get_condition_context(), $debug, array(), $trace );

	$visible = ( $action === 'show' ) ? $matched : ! $matched;
	if ( ! $visible ) {
		return '';
	}

	if ( $content === null || $content === '' ) {
		return '';
	}

	return do_shortcode( wp_kses_post( $content ) );
}

/**
 * Seed default psychosis condition blocks if they do not already exist.
 */
function mha_seed_psychosis_condition_blocks() {
	if ( ! function_exists( 'get_field' ) || ! function_exists( 'update_field' ) ) {
		return;
	}

	$existing = get_field( 'mha_condition_blocks', 'options' );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}

	$existing_slugs = array();
	foreach ( $existing as $row ) {
		if ( ! empty( $row['slug'] ) ) {
			$existing_slugs[] = sanitize_title( $row['slug'] );
		}
	}

	$defaults = mha_get_psychosis_condition_block_defaults();
	foreach ( $defaults as $default ) {
		if ( in_array( $default['slug'], $existing_slugs, true ) ) {
			continue;
		}
		$existing[] = $default;
	}

	if ( count( $existing ) > count( $existing_slugs ) ) {
		update_field( 'mha_condition_blocks', $existing, 'options' );
	}
}

/**
 * @return array<int, array<string, mixed>>
 */
function mha_get_psychosis_condition_block_defaults() {
	$layout_values = 'actions_g|actions_h|actions_i|actions_j|actions_k|actions_l|actions_m|actions_n|actions_o|actions_p|actions_q|actions_r|actions_s|actions_t|actions_u|actions_v|actions_w|actions_x|actions_y|na_2';
	$valid_ages    = '11-13|14-15|16-17|18-24|25-34';
	$us_question   = 'Do you live in the United States or another country?';

	$eligible = array(
		'operator'   => 'and',
		'conditions' => array(
			array(
				'type'      => 'test_result',
				'condition' => 'equals',
				'value'     => 'Possible Risk for Psychosis',
			),
			array(
				'type'      => 'url_parameter',
				'key'       => 'layout',
				'condition' => 'one_of',
				'value'     => $layout_values,
			),
			array(
				'type'      => 'demographic_response',
				'key'       => 'Age Range',
				'condition' => 'one_of',
				'value'     => $valid_ages,
			),
			array(
				'type'      => 'demographic_response',
				'key'       => $us_question,
				'condition' => 'equals',
				'value'     => 'I live in the United States',
			),
		),
	);

	$disqualified = array(
		'operator'   => 'and',
		'conditions' => array(
			array(
				'type'      => 'test_result',
				'condition' => 'equals',
				'value'     => 'Possible Risk for Psychosis',
			),
			array(
				'type'      => 'url_parameter',
				'key'       => 'layout',
				'condition' => 'one_of',
				'value'     => $layout_values,
			),
			array(
				'operator'   => 'or',
				'conditions' => array(
					array(
						'type'      => 'demographic_response',
						'key'       => 'Age Range',
						'condition' => 'none_of',
						'value'     => $valid_ages,
					),
					array(
						'type'      => 'demographic_response',
						'key'       => $us_question,
						'condition' => 'not_equals',
						'value'     => 'I live in the United States',
					),
					array(
						'type'      => 'demographic_response',
						'key'       => 'Age Range',
						'condition' => 'is_null',
					),
					array(
						'type'      => 'demographic_response',
						'key'       => $us_question,
						'condition' => 'is_null',
					),
				),
			),
		),
	);

	return array(
		array(
			'slug'        => 'psychosis-ab-eligible',
			'title'       => 'Digital Pathways - positive scorers - A/B test eligible',
			'notes'       => 'Positive psychosis result, valid layout, eligible age range, US resident.',
			'definition'  => wp_json_encode( $eligible, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
			'debug_mode'  => 0,
		),
		array(
			'slug'        => 'psychosis-ab-disqualified',
			'title'       => 'Digital Pathways - positive scorers - disqualified from A/B test',
			'notes'       => 'Positive psychosis result, valid layout, but ineligible age/residency or missing demographics.',
			'definition'  => wp_json_encode( $disqualified, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
			'debug_mode'  => 0,
		),
	);
}

add_filter( 'acf/validate_value/key=field_6865c1d010008', 'mha_validate_condition_block_definition', 10, 3 );
add_filter( 'acf/validate_value/key=field_6865c1d010004', 'mha_validate_condition_block_slug', 10, 2 );
add_filter( 'acf/validate_value/key=field_6865c1d010009', 'mha_validate_optional_condition_block_slug', 10, 2 );
add_filter( 'acf/validate_value/key=field_6865c1d010010', 'mha_validate_condition_block_definition', 10, 3 );
add_filter( 'acf/load_field/key=field_6865c1d010009', 'mha_load_condition_block_slug_choices' );
add_shortcode( 'mha_conditional', 'mha_conditional_shortcode' );
add_action( 'acf/init', 'mha_seed_psychosis_condition_blocks' );
add_action( 'admin_init', 'mha_maybe_migrate_psychosis_featured_next_steps' );

/**
 * One-time admin migration for Psychosis Featured Next Steps link groups.
 * Visit /wp-admin/?mha_migrate_psychosis_steps=1 while logged in as an admin.
 */
function mha_maybe_migrate_psychosis_featured_next_steps() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || empty( $_GET['mha_migrate_psychosis_steps'] ) ) {
		return;
	}

	if ( get_option( 'mha_psychosis_featured_steps_migrated' ) ) {
		return;
	}

	$screens = get_posts(
		array(
			'post_type'      => 'screen',
			'posts_per_page' => 10,
			's'              => 'psychosis',
			'post_status'    => array( 'publish', 'draft', 'private' ),
		)
	);

	if ( empty( $screens ) ) {
		return;
	}

	mha_seed_psychosis_condition_blocks();

	foreach ( $screens as $screen ) {
		$test_groups = get_field( 'featured_next_steps_test', $screen->ID );
		if ( empty( $test_groups[0]['next_step_links'] ) || ! is_array( $test_groups[0]['next_step_links'] ) ) {
			continue;
		}

		$links            = $test_groups[0]['next_step_links'];
		$new_links        = array();
		$eligible_group   = null;
		$disqualified_grp = null;
		$other_groups     = array();

		foreach ( $links as $group ) {
			$title = strtolower( (string) ( $group['link_group_title'] ?? '' ) );

			if ( str_contains( $title, 'a/b test' ) || str_contains( $title, 'ab test' ) ) {
				if ( ! $eligible_group ) {
					$eligible_group = $group;
				}
				continue;
			}

			if (
				str_contains( $title, 'disqualified' ) ||
				str_contains( $title, 'standard positive' ) ||
				(
					str_contains( $title, 'positive scorer' ) &&
					! str_contains( $title, 'a/b test' ) &&
					! str_contains( $title, 'ab test' )
				)
			) {
				if ( ! $disqualified_grp ) {
					$disqualified_grp = $group;
				}
				continue;
			}

			$other_groups[] = $group;
		}

		if ( $eligible_group ) {
			$eligible_group['operator']    = 'and';
			$eligible_group['conditions']  = array(
				array(
					'type'                 => 'condition_block',
					'condition_block_slug' => 'psychosis-ab-eligible',
				),
			);
			$new_links[] = $eligible_group;
		}

		if ( $disqualified_grp ) {
			$disqualified_grp['operator']   = 'and';
			$disqualified_grp['conditions'] = array(
				array(
					'type'                 => 'condition_block',
					'condition_block_slug' => 'psychosis-ab-disqualified',
				),
			);
			$new_links[] = $disqualified_grp;
		}

		$new_links = array_merge( $new_links, $other_groups );

		if ( count( $new_links ) < count( $links ) || $eligible_group || $disqualified_grp ) {
			$test_groups[0]['next_step_links'] = $new_links;
			update_field( 'featured_next_steps_test', $test_groups, $screen->ID );
		}
	}

	update_option( 'mha_psychosis_featured_steps_migrated', 1, false );
}
