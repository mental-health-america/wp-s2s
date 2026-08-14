<?php
/**
 * Screen collection results: resolve collection from entry, score modules, build answers by page.
 *
 * @package mha_screens
 */

/**
 * Read a labeled GF entry field value (label substring match, case-insensitive).
 *
 * @param array  $entry GF entry.
 * @param array  $form  GF form.
 * @param string $needle Label substring.
 * @return string
 */
function mha_screen_collection_entry_field_value_by_label( $entry, $form, $needle ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) || ! is_array( $entry ) ) {
		return '';
	}
	$needle = strtolower( trim( (string) $needle ) );
	if ( '' === $needle ) {
		return '';
	}
	foreach ( $form['fields'] as $field ) {
		if ( ! is_object( $field ) ) {
			continue;
		}
		$label = isset( $field->label ) ? strtolower( trim( (string) $field->label ) ) : '';
		$admin = isset( $field->adminLabel ) ? strtolower( trim( (string) $field->adminLabel ) ) : '';
		if ( false === strpos( $label, $needle ) && false === strpos( $admin, $needle ) ) {
			continue;
		}
		$fid = (string) $field->id;
		if ( isset( $entry[ $fid ] ) && '' !== $entry[ $fid ] && null !== $entry[ $fid ] ) {
			return is_scalar( $entry[ $fid ] ) ? (string) $entry[ $fid ] : '';
		}
	}
	return '';
}

/**
 * Whether a collection's allowed_organizations includes the SC Organization entry value.
 *
 * @param int    $collection_id Collection post ID.
 * @param string $sc_org        Value from SC Organization field.
 * @return bool
 */
function mha_screen_collection_matches_sc_organization( $collection_id, $sc_org ) {
	$sc_org = trim( (string) $sc_org );
	if ( '' === $sc_org || ! function_exists( 'get_field' ) ) {
		return false;
	}
	$rows = get_field( 'allowed_organizations', $collection_id );
	if ( ! is_array( $rows ) || empty( $rows ) ) {
		return false;
	}
	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$org_id = isset( $row['organization_id'] ) ? $row['organization_id'] : '';
		$display = isset( $row['organization_display_name'] ) ? trim( (string) $row['organization_display_name'] ) : '';
		if ( $display !== '' && strcasecmp( $display, $sc_org ) === 0 ) {
			return true;
		}
		if ( is_numeric( $org_id ) ) {
			$term = get_term( (int) $org_id, 'organization' );
			if ( $term && ! is_wp_error( $term ) ) {
				if ( strcasecmp( $term->name, $sc_org ) === 0 || strcasecmp( $term->slug, $sc_org ) === 0 ) {
					return true;
				}
			}
		} elseif ( is_scalar( $org_id ) && '' !== (string) $org_id ) {
			$raw = trim( (string) $org_id );
			if ( strcasecmp( $raw, $sc_org ) === 0 ) {
				return true;
			}
			$term = get_term_by( 'slug', $raw, 'organization' );
			if ( ! $term ) {
				$term = get_term_by( 'name', $raw, 'organization' );
			}
			if ( $term && ! is_wp_error( $term ) ) {
				if ( strcasecmp( $term->name, $sc_org ) === 0 || strcasecmp( $term->slug, $sc_org ) === 0 ) {
					return true;
				}
			}
		}
	}
	return false;
}

/**
 * Resolve a screen-collection post ID from a GF entry (reverse lookup by Screen ID).
 *
 * @param array      $entry GF entry.
 * @param array|null $form  Optional form array; loaded from entry if omitted.
 * @return int Collection post ID or 0.
 */
function mha_screen_collection_resolve_from_entry( $entry, $form = null ) {
	if ( ! is_array( $entry ) || empty( $entry['form_id'] ) || ! class_exists( 'GFAPI' ) ) {
		return 0;
	}
	if ( ! is_array( $form ) ) {
		$form = GFAPI::get_form( (int) $entry['form_id'] );
	}
	if ( ! is_array( $form ) ) {
		return 0;
	}

	$screen_id = absint( mha_screen_collection_entry_field_value_by_label( $entry, $form, 'screen id' ) );
	if ( ! $screen_id ) {
		return 0;
	}

	$sc_org = mha_screen_collection_entry_field_value_by_label( $entry, $form, 'sc organization' );

	$q = new WP_Query(
		array(
			'post_type'              => 'screen-collection',
			'post_status'            => 'publish',
			'posts_per_page'         => 50,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);

	$candidates = array();
	foreach ( (array) $q->posts as $collection_id ) {
		$collection_id = (int) $collection_id;
		if ( ! function_exists( 'get_field' ) ) {
			continue;
		}
		$screens = function_exists( 'mha_s2s_normalize_screen_collection_screens' )
			? mha_s2s_normalize_screen_collection_screens( get_field( 'screens', $collection_id ) )
			: array( absint( get_field( 'screens', $collection_id ) ) );
		if ( ! in_array( $screen_id, $screens, true ) ) {
			continue;
		}
		$candidates[] = $collection_id;
	}

	if ( empty( $candidates ) ) {
		return 0;
	}
	if ( 1 === count( $candidates ) ) {
		return (int) $candidates[0];
	}

	if ( '' !== $sc_org ) {
		$matched = array();
		foreach ( $candidates as $collection_id ) {
			if ( mha_screen_collection_matches_sc_organization( $collection_id, $sc_org ) ) {
				$matched[] = $collection_id;
			}
		}
		if ( 1 === count( $matched ) ) {
			return (int) $matched[0];
		}
		if ( ! empty( $matched ) ) {
			return (int) $matched[0];
		}
	}

	return (int) $candidates[0];
}

/**
 * Build a single Your Answers HTML row for a question field.
 *
 * @param object $field GF field.
 * @param mixed  $value Entry value.
 * @return array{id:int|string,type:string,css:string,question:string,answer:string}|null
 */
function mha_screen_collection_build_answer_row( $field, $value ) {
	if ( ! is_object( $field ) || '' === $value || null === $value ) {
		return null;
	}
	$css_class = isset( $field->cssClass ) ? (string) $field->cssClass : '';
	if ( false === strpos( $css_class, 'question' ) ) {
		return null;
	}

	$label       = isset( $field->label ) ? (string) $field->label : '';
	$value_label = '';
	if ( ! empty( $field->choices ) && is_array( $field->choices ) ) {
		foreach ( $field->choices as $choice ) {
			if ( isset( $choice['value'] ) && (string) $choice['value'] === (string) $value ) {
				$value_label = isset( $choice['text'] ) ? (string) $choice['text'] : '';
				break;
			}
		}
	}

	if ( isset( $field->type ) && 'number' === $field->type ) {
		$value_extra = intval( $value );
		$value_label = '';
	} else {
		$value_extra = is_numeric( $value ) ? ' (' . intval( $value ) . ')' : '';
	}

	$has_indent = false !== strpos( $css_class, 'indent' ) ? ' pl-5' : ' pl-0';
	if ( false !== strpos( $css_class, 'question-optional' ) ) {
		$answer = $value;
	} elseif ( false !== strpos( $css_class, 'hide-score' ) ) {
		$answer = $value_label;
	} else {
		$answer = $value_label . $value_extra;
	}

	return array(
		'id'       => $field->id,
		'type'     => 'question',
		'css'      => 'question-row row pb-4' . $has_indent,
		'question' => $label,
		'answer'   => $answer,
	);
}

/**
 * Render answer rows to HTML.
 *
 * @param array $rows Answer row arrays.
 * @return string
 */
function mha_screen_collection_render_answers_html( $rows ) {
	if ( empty( $rows ) || ! is_array( $rows ) ) {
		return '';
	}
	if ( function_exists( 'mergeDuplicates' ) ) {
		$rows = mergeDuplicates( $rows );
	}
	$html = array();
	foreach ( $rows as $ya ) {
		$temp_answer = isset( $ya['answer'] ) && function_exists( 'removeTextBetween' )
			? removeTextBetween( $ya['answer'], ' (e.g.', ')' )
			: ( isset( $ya['answer'] ) ? $ya['answer'] : '' );
		$css = isset( $ya['css'] ) ? $ya['css'] : 'question-row row pb-4';
		if ( isset( $ya['type'] ) && 'extra' === $ya['type'] ) {
			$html[] = '<div class="' . esc_attr( $css ) . '"><div class="col-12 text-gray">' . $temp_answer . '</div></div>';
		} else {
			$q = isset( $ya['question'] ) ? $ya['question'] : '';
			$html[] = '<div class="' . esc_attr( $css ) . '"><div class="col-sm-7 col-12 text-gray">' . esc_html( $q ) . '</div><div class="col-sm-5 col-12 bold text-dark-blue">' . esc_html( $temp_answer ) . '</div></div>';
		}
	}
	return implode( '', $html );
}

/**
 * Score collection results_modules against a GF entry.
 *
 * @param int        $collection_id Collection post ID.
 * @param array      $entry         GF entry.
 * @param array|null $form          Optional form; loaded if omitted.
 * @return array{
 *   collection_id:int,
 *   modules:array<int,array<string,mixed>>,
 *   positive:array<int,array<string,mixed>>,
 *   negative:array<int,array<string,mixed>>,
 *   recommended:array<int,array<string,mixed>>,
 *   default_module_index:int
 * }
 */
function mha_get_collection_module_results( $collection_id, $entry, $form = null ) {
	$collection_id = absint( $collection_id );
	$empty         = array(
		'collection_id'        => $collection_id,
		'modules'              => array(),
		'positive'             => array(),
		'negative'             => array(),
		'recommended'          => array(),
		'default_module_index' => 0,
	);

	if ( ! $collection_id || ! is_array( $entry ) || ! class_exists( 'GFAPI' ) || ! function_exists( 'get_field' ) ) {
		return $empty;
	}

	if ( ! is_array( $form ) ) {
		$form = GFAPI::get_form( (int) $entry['form_id'] );
	}
	if ( ! is_array( $form ) || empty( $form['fields'] ) ) {
		return $empty;
	}

	$modules_cfg = get_field( 'results_modules', $collection_id );
	if ( ! is_array( $modules_cfg ) || empty( $modules_cfg ) ) {
		return $empty;
	}

	// Index question fields by page number.
	$fields_by_page = array();
	foreach ( $form['fields'] as $field ) {
		if ( ! is_object( $field ) ) {
			continue;
		}
		$css = isset( $field->cssClass ) ? (string) $field->cssClass : '';
		if ( false === strpos( $css, 'question' ) || false !== strpos( $css, 'exclude' ) ) {
			continue;
		}
		$page = isset( $field->pageNumber ) ? (int) $field->pageNumber : 1;
		if ( ! isset( $fields_by_page[ $page ] ) ) {
			$fields_by_page[ $page ] = array();
		}
		$fields_by_page[ $page ][] = $field;
	}

	$modules = array();
	foreach ( $modules_cfg as $idx => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$page      = isset( $row['form_page_number'] ) ? (int) $row['form_page_number'] : 0;
		$max_score = isset( $row['maximum_score'] ) ? (float) $row['maximum_score'] : 0;
		$threshold = isset( $row['positive_score_threshold'] ) ? (float) $row['positive_score_threshold'] : 0;
		$label     = isset( $row['module_label'] ) ? trim( (string) $row['module_label'] ) : '';
		$symptom   = isset( $row['symptom_label'] ) ? trim( (string) $row['symptom_label'] ) : '';
		$rec       = isset( $row['recommended_screen'] ) ? absint( $row['recommended_screen'] ) : 0;

		if ( $page < 1 || $max_score <= 0 ) {
			continue;
		}

		$page_fields = isset( $fields_by_page[ $page ] ) ? $fields_by_page[ $page ] : array();
		if ( empty( $page_fields ) ) {
			continue;
		}

		$total        = 0;
		$answer_rows  = array();
		foreach ( $page_fields as $field ) {
			$fid = (string) $field->id;
			$val = isset( $entry[ $fid ] ) ? $entry[ $fid ] : '';
			if ( '' === $val || null === $val ) {
				continue;
			}
			$total += intval( $val );
			$row_built = mha_screen_collection_build_answer_row( $field, $val );
			if ( $row_built ) {
				$answer_rows[] = $row_built;
			}
		}

		$rank     = $max_score > 0 ? ( $total / $max_score ) : 0;
		$positive = $total >= $threshold;

		$modules[] = array(
			'index'              => (int) $idx,
			'module_label'       => $label,
			'symptom_label'      => $symptom,
			'form_page_number'   => $page,
			'total_score'        => $total,
			'maximum_score'      => $max_score,
			'threshold'          => $threshold,
			'rank'               => $rank,
			'positive'           => $positive,
			'recommended_screen' => $rec,
			'your_answers_html'  => mha_screen_collection_render_answers_html( $answer_rows ),
		);
	}

	$by_rank_desc = static function ( $a, $b ) {
		if ( $a['rank'] === $b['rank'] ) {
			return $a['index'] <=> $b['index'];
		}
		return $a['rank'] < $b['rank'] ? 1 : -1;
	};

	$positive = array_values( array_filter( $modules, static function ( $m ) {
		return ! empty( $m['positive'] );
	} ) );
	usort( $positive, $by_rank_desc );

	$negative = array_values( array_filter( $modules, static function ( $m ) {
		return empty( $m['positive'] );
	} ) );
	usort( $negative, $by_rank_desc );

	$recommended = array_values(
		array_filter(
			$positive,
			static function ( $m ) {
				return ! empty( $m['recommended_screen'] );
			}
		)
	);

	$default_index = 0;
	if ( ! empty( $positive ) ) {
		$first_positive_page = $positive[0]['form_page_number'];
		foreach ( $modules as $i => $m ) {
			if ( (int) $m['form_page_number'] === (int) $first_positive_page ) {
				$default_index = $i;
				break;
			}
		}
	}

	return array(
		'collection_id'        => $collection_id,
		'modules'              => $modules,
		'positive'             => $positive,
		'negative'             => $negative,
		'recommended'          => $recommended,
		'default_module_index' => $default_index,
	);
}

/**
 * Collection-level copy/settings used by the results template.
 *
 * @param int $collection_id Collection post ID.
 * @return array<string,mixed>
 */
function mha_get_collection_results_settings( $collection_id ) {
	$collection_id = absint( $collection_id );
	$defaults      = array(
		'positive_summary_prefix'     => 'Here are some things you seem to be struggling with right now:',
		'negative_summary_prefix'     => "Here are some things you don't seem to be struggling with right now:",
		'empty_positive_message'      => "Based on your answers, you don't seem to be struggling with the areas we asked about right now. Still, it's okay to check in with someone you trust if anything feels off.",
		'share_results_message'       => 'Consider sharing these results with someone you trust — a parent, counselor, or other supportive adult.',
		'recommended_screens_heading' => 'Screens to take next',
		'show_recommended_screens'    => true,
		'results_resources'           => array(),
	);

	if ( ! $collection_id || ! function_exists( 'get_field' ) ) {
		return $defaults;
	}

	$get = static function ( $name, $fallback ) use ( $collection_id ) {
		$val = get_field( $name, $collection_id );
		if ( null === $val || false === $val || '' === $val ) {
			return $fallback;
		}
		return $val;
	};

	$resources = $get( 'results_resources', array() );
	if ( ! is_array( $resources ) ) {
		$resources = $resources ? array( absint( $resources ) ) : array();
	} else {
		$resources = array_values( array_filter( array_map( 'absint', $resources ) ) );
	}

	$show = get_field( 'show_recommended_screens', $collection_id );
	if ( null === $show || false === $show || '' === $show ) {
		// ACF true_false returns false when off; treat unset as default on when field never saved.
		$show_meta = metadata_exists( 'post', $collection_id, 'show_recommended_screens' );
		$show      = $show_meta ? (bool) $show : true;
	} else {
		$show = (bool) $show;
	}

	return array(
		'positive_summary_prefix'     => (string) $get( 'positive_summary_prefix', $defaults['positive_summary_prefix'] ),
		'negative_summary_prefix'     => (string) $get( 'negative_summary_prefix', $defaults['negative_summary_prefix'] ),
		'empty_positive_message'      => (string) $get( 'empty_positive_message', $defaults['empty_positive_message'] ),
		'share_results_message'       => (string) $get( 'share_results_message', $defaults['share_results_message'] ),
		'recommended_screens_heading' => (string) $get( 'recommended_screens_heading', $defaults['recommended_screens_heading'] ),
		'show_recommended_screens'    => $show,
		'results_resources'           => $resources,
	);
}

/**
 * Enqueue collection results module-tab script.
 */
function mha_enqueue_collection_results_script() {
	wp_enqueue_script(
		'mha-collection-results',
		plugin_dir_url( __FILE__ ) . 'js/collection-results.js',
		array( 'jquery' ),
		defined( 'MHASCREENS_VERSION' ) ? MHASCREENS_VERSION : null,
		true
	);
}
