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
 * Resolve a screen-collection post ID from a GF entry (`Prescreen sc` token, then Form field).
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

	$entry_form_id = (int) $entry['form_id'];

	$sc_raw = mha_screen_collection_entry_field_value_by_label( $entry, $form, 'prescreen sc' );
	if ( '' === $sc_raw && function_exists( 'mha_screen_collection_prescreen_sc_context_field_id' ) ) {
		$sc_fid = mha_screen_collection_prescreen_sc_context_field_id( $form );
		if ( $sc_fid && ! empty( $entry[ (string) $sc_fid ] ) && is_scalar( $entry[ (string) $sc_fid ] ) ) {
			$sc_raw = (string) $entry[ (string) $sc_fid ];
		}
	}
	if ( '' !== $sc_raw && function_exists( 'mha_screen_collection_parse_sc_token' ) ) {
		$parsed = mha_screen_collection_parse_sc_token( $sc_raw );
		if ( $parsed && (int) $parsed['form_id'] === $entry_form_id ) {
			return (int) $parsed['collection_id'];
		}
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

		$collection_form_id = function_exists( 'mha_screen_collection_get_form_id' )
			? mha_screen_collection_get_form_id( $collection_id )
			: absint( get_field( 'form', $collection_id ) );
		if ( $collection_form_id && (int) $collection_form_id === $entry_form_id ) {
			$candidates[] = $collection_id;
		}
	}

	$candidates = array_values( array_unique( $candidates ) );

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
 * Whether a field stores several selected choices (checkbox or multi-select).
 *
 * @param object $field GF field.
 * @return bool
 */
function mha_screen_collection_is_multi_choice_field( $field ) {
	$type       = isset( $field->type ) ? (string) $field->type : '';
	$input_type = isset( $field->inputType ) ? (string) $field->inputType : '';
	return in_array( $type, array( 'checkbox', 'multiselect', 'multi_choice' ), true ) || 'checkbox' === $input_type;
}

/**
 * Choice label for a stored value.
 *
 * @param object $field GF field.
 * @param mixed  $value Stored choice value.
 * @return string
 */
function mha_screen_collection_choice_label( $field, $value ) {
	if ( ! empty( $field->choices ) && is_array( $field->choices ) ) {
		foreach ( $field->choices as $choice ) {
			if ( isset( $choice['value'] ) && (string) $choice['value'] === (string) $value ) {
				$text = isset( $choice['text'] ) ? trim( (string) $choice['text'] ) : '';
				return '' !== $text ? $text : (string) $value;
			}
		}
	}
	return trim( (string) $value );
}

/**
 * Selected labels for a checkbox or multi-select field.
 *
 * Returns null when the field is not a multi-choice field. An empty array means
 * nothing was selected.
 *
 * @param object $field GF field.
 * @param array  $entry GF entry.
 * @return array<int,string>|null
 */
function mha_screen_collection_multi_choice_labels( $field, $entry ) {
	if ( ! is_object( $field ) || ! mha_screen_collection_is_multi_choice_field( $field ) || ! is_array( $entry ) ) {
		return null;
	}

	$selected = array();
	$type     = isset( $field->type ) ? (string) $field->type : '';

	if ( 'multiselect' === $type ) {
		$fid = (string) $field->id;
		$raw = isset( $entry[ $fid ] ) ? $entry[ $fid ] : '';
		$values = array();
		if ( is_array( $raw ) ) {
			$values = $raw;
		} elseif ( is_string( $raw ) && '' !== $raw ) {
			$decoded = json_decode( $raw, true );
			$values  = is_array( $decoded ) ? $decoded : array_map( 'trim', explode( ',', $raw ) );
		}
		foreach ( $values as $value ) {
			if ( '' === $value || null === $value ) {
				continue;
			}
			$selected[] = mha_screen_collection_choice_label( $field, $value );
		}
		return $selected;
	}

	$inputs = ! empty( $field->inputs ) && is_array( $field->inputs ) ? $field->inputs : array();
	if ( $inputs ) {
		foreach ( $inputs as $input ) {
			$iid = isset( $input['id'] ) ? (string) $input['id'] : '';
			if ( '' === $iid || ! isset( $entry[ $iid ] ) || '' === $entry[ $iid ] || null === $entry[ $iid ] ) {
				continue;
			}
			$selected[] = mha_screen_collection_choice_label( $field, $entry[ $iid ] );
		}
		return $selected;
	}

	$prefix = (string) $field->id . '.';
	foreach ( $entry as $key => $value ) {
		if ( ! is_scalar( $key ) || 0 !== strpos( (string) $key, $prefix ) || '' === $value || null === $value ) {
			continue;
		}
		$selected[] = mha_screen_collection_choice_label( $field, $value );
	}
	return $selected;
}

/**
 * Whether a question field has a saved answer in the entry.
 *
 * @param object $field GF field.
 * @param array  $entry GF entry.
 * @return bool
 */
function mha_screen_collection_field_is_answered( $field, $entry ) {
	if ( ! is_object( $field ) || ! is_array( $entry ) ) {
		return false;
	}

	$multi = mha_screen_collection_multi_choice_labels( $field, $entry );
	if ( is_array( $multi ) ) {
		return ! empty( $multi );
	}

	$fid = (string) $field->id;
	$val = isset( $entry[ $fid ] ) ? $entry[ $fid ] : '';
	return '' !== $val && null !== $val;
}

/**
 * Whether a field was shown for this entry (GF conditional logic + page/section).
 *
 * Hidden fields are treated as unanswered-on-purpose, not incomplete.
 *
 * @param array  $form  GF form.
 * @param object $field GF field.
 * @param array  $entry GF entry.
 * @return bool
 */
function mha_screen_collection_field_is_visible( $form, $field, $entry ) {
	if ( ! is_object( $field ) || ! is_array( $form ) || ! is_array( $entry ) ) {
		return true;
	}
	if ( ! class_exists( 'GFFormsModel' ) || ! is_callable( array( 'GFFormsModel', 'is_field_hidden' ) ) ) {
		return true;
	}
	return ! GFFormsModel::is_field_hidden( $form, $field, array(), $entry );
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
		$answer = '' !== $value_label ? $value_label : $value;
	} else {
		$answer = $value_label . $value_extra;
	}
	if ( '' === trim( (string) $answer ) ) {
		$answer = is_scalar( $value ) ? (string) $value : '';
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
		$temp_answer = wp_strip_all_tags( (string) $temp_answer );
		$css = isset( $ya['css'] ) ? $ya['css'] : 'question-row row pb-4';
		if ( isset( $ya['type'] ) && 'extra' === $ya['type'] ) {
			$html[] = '<div class="' . esc_attr( $css ) . '"><div class="col-12 text-gray">' . esc_html( $temp_answer ) . '</div></div>';
		} else {
			$q = isset( $ya['question'] ) ? wp_strip_all_tags( (string) $ya['question'] ) : '';
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
 *   incomplete:array<int,array<string,mixed>>,
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
		'incomplete'           => array(),
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
		// `exclude` still belongs in Your Answers. It only means the field is not scored.
		if ( false === strpos( $css, 'question' ) ) {
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

		$total              = 0;
		$answer_rows        = array();
		$visible_questions  = 0;
		$unanswered_visible = 0;
		foreach ( $page_fields as $field ) {
			$fid      = (string) $field->id;
			$css      = isset( $field->cssClass ) ? (string) $field->cssClass : '';
			$excluded = false !== strpos( $css, 'exclude' );
			$visible  = mha_screen_collection_field_is_visible( $form, $field, $entry );
			$answered = mha_screen_collection_field_is_answered( $field, $entry );
			$multi    = mha_screen_collection_multi_choice_labels( $field, $entry );

			if ( $visible ) {
				++$visible_questions;
				if ( ! $answered ) {
					++$unanswered_visible;
				}
			}

			if ( is_array( $multi ) ) {
				if ( empty( $multi ) ) {
					continue;
				}
				// Checkbox and multi-select values are condition labels, not scores.
				$val = implode( ', ', $multi );
			} else {
				$val = isset( $entry[ $fid ] ) ? $entry[ $fid ] : '';
				if ( '' === $val || null === $val ) {
					continue;
				}
				if ( ! $excluded ) {
					$total += intval( $val );
				}
			}

			$row_built = mha_screen_collection_build_answer_row( $field, $val );
			if ( $row_built ) {
				$answer_rows[] = $row_built;
			}
		}

		$rank       = $max_score > 0 ? ( $total / $max_score ) : 0;
		$positive   = $total >= $threshold;
		// Below threshold with skipped visible questions = incomplete, not "not struggling".
		$incomplete = ! $positive && $unanswered_visible > 0;
		// Page/fields all hidden by conditional logic: omit from negative and incomplete lists.
		$skipped = ! $positive && ! $incomplete && $visible_questions < 1;

		$modules[] = array(
			'index'               => (int) $idx,
			'module_label'        => $label,
			'symptom_label'       => $symptom,
			'form_page_number'    => $page,
			'total_score'         => $total,
			'maximum_score'       => $max_score,
			'threshold'           => $threshold,
			'rank'                => $rank,
			'positive'            => $positive,
			'incomplete'          => $incomplete,
			'skipped'             => $skipped,
			'visible_questions'   => $visible_questions,
			'unanswered_visible'  => $unanswered_visible,
			'recommended_screen'  => $rec,
			'your_answers_html'   => mha_screen_collection_render_answers_html( $answer_rows ),
		);
	}

	$by_rank_desc = static function ( $a, $b ) {
		if ( $a['rank'] === $b['rank'] ) {
			return $a['index'] <=> $b['index'];
		}
		return $a['rank'] < $b['rank'] ? 1 : -1;
	};

	$by_index = static function ( $a, $b ) {
		return $a['index'] <=> $b['index'];
	};

	$positive = array_values( array_filter( $modules, static function ( $m ) {
		return ! empty( $m['positive'] );
	} ) );
	usort( $positive, $by_rank_desc );

	$incomplete = array_values( array_filter( $modules, static function ( $m ) {
		return ! empty( $m['incomplete'] );
	} ) );
	usort( $incomplete, $by_index );

	$negative = array_values( array_filter( $modules, static function ( $m ) {
		return empty( $m['positive'] ) && empty( $m['incomplete'] ) && empty( $m['skipped'] );
	} ) );
	usort( $negative, $by_rank_desc );

	$recommended = mha_collection_limit_recommended_screens(
		array_values(
			array_filter(
				$positive,
				static function ( $m ) {
					return ! empty( $m['recommended_screen'] );
				}
			)
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
		'incomplete'           => $incomplete,
		'recommended'          => $recommended,
		'default_module_index' => $default_index,
	);
}

/**
 * Gravity Forms view totals, keyed by form ID.
 *
 * @return array<int,int>
 */
function mha_screen_collection_form_view_counts() {
	static $counts = null;
	if ( null !== $counts ) {
		return $counts;
	}
	$counts = array();
	if ( ! class_exists( 'GFFormsModel' ) || ! is_callable( array( 'GFFormsModel', 'get_view_count_per_form' ) ) ) {
		return $counts;
	}
	$rows = GFFormsModel::get_view_count_per_form();
	if ( ! is_array( $rows ) ) {
		return $counts;
	}
	foreach ( $rows as $row ) {
		$form_id = isset( $row->form_id ) ? (int) $row->form_id : 0;
		if ( $form_id ) {
			$counts[ $form_id ] = isset( $row->view_count ) ? (int) $row->view_count : 0;
		}
	}
	return $counts;
}

/**
 * Form ID embedded in a Screen post, without rendering the content.
 *
 * @param int $screen_id Screen post ID.
 * @return int
 */
function mha_screen_collection_screen_form_id( $screen_id ) {
	static $cache = array();
	$screen_id = absint( $screen_id );
	if ( ! $screen_id ) {
		return 0;
	}
	if ( isset( $cache[ $screen_id ] ) ) {
		return $cache[ $screen_id ];
	}
	$post = get_post( $screen_id );
	$form_id = 0;
	if ( $post ) {
		$haystack = (string) $post->post_content . "\n" . (string) $post->post_excerpt;
		if ( preg_match( '/\[gravityform\s+id=["\']?(\d+)["\']?/i', $haystack, $matches ) ) {
			$form_id = absint( $matches[1] );
		}
	}
	$cache[ $screen_id ] = $form_id;
	return $form_id;
}

/**
 * Popularity for a Screen: total Gravity Forms views of its embedded form.
 *
 * @param int $screen_id Screen post ID.
 * @return int
 */
function mha_screen_collection_screen_popularity( $screen_id ) {
	$form_id = mha_screen_collection_screen_form_id( $screen_id );
	if ( ! $form_id ) {
		return 0;
	}
	$counts = mha_screen_collection_form_view_counts();
	return isset( $counts[ $form_id ] ) ? (int) $counts[ $form_id ] : 0;
}

/**
 * Unique recommended screens, most-viewed first, capped at 4.
 *
 * Ties fall back to the module rank (highest first).
 *
 * @param array<int,array<string,mixed>> $recommended Positive modules that have a recommended screen.
 * @param int                             $limit      Maximum links.
 * @return array<int,array<string,mixed>>
 */
function mha_collection_limit_recommended_screens( $recommended, $limit = 4 ) {
	$seen   = array();
	$unique = array();
	foreach ( $recommended as $mod ) {
		if ( ! is_array( $mod ) ) {
			continue;
		}
		$screen_id = isset( $mod['recommended_screen'] ) ? absint( $mod['recommended_screen'] ) : 0;
		if ( ! $screen_id || isset( $seen[ $screen_id ] ) || 'publish' !== get_post_status( $screen_id ) ) {
			continue;
		}
		$seen[ $screen_id ]         = true;
		$mod['screen_popularity']   = mha_screen_collection_screen_popularity( $screen_id );
		$unique[]                   = $mod;
	}

	usort(
		$unique,
		static function ( $a, $b ) {
			$pop_a = isset( $a['screen_popularity'] ) ? (int) $a['screen_popularity'] : 0;
			$pop_b = isset( $b['screen_popularity'] ) ? (int) $b['screen_popularity'] : 0;
			if ( $pop_a !== $pop_b ) {
				return $pop_a < $pop_b ? 1 : -1;
			}
			$rank_a = isset( $a['rank'] ) ? (float) $a['rank'] : 0;
			$rank_b = isset( $b['rank'] ) ? (float) $b['rank'] : 0;
			if ( $rank_a !== $rank_b ) {
				return $rank_a < $rank_b ? 1 : -1;
			}
			$index_a = isset( $a['index'] ) ? (int) $a['index'] : 0;
			$index_b = isset( $b['index'] ) ? (int) $b['index'] : 0;
			return $index_a <=> $index_b;
		}
	);

	return array_slice( $unique, 0, max( 0, (int) $limit ) );
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
		'incomplete_summary_prefix'   => "These pages weren't fully answered, so we couldn't tell whether these areas are a concern:",
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
		'incomplete_summary_prefix'   => (string) $get( 'incomplete_summary_prefix', $defaults['incomplete_summary_prefix'] ),
		'empty_positive_message'      => (string) $get( 'empty_positive_message', $defaults['empty_positive_message'] ),
		'share_results_message'       => (string) $get( 'share_results_message', $defaults['share_results_message'] ),
		'recommended_screens_heading' => (string) $get( 'recommended_screens_heading', $defaults['recommended_screens_heading'] ),
		'show_recommended_screens'    => $show,
		'results_resources'           => $resources,
	);
}

/**
 * Results line: bold module name, then the symptom phrase.
 *
 * A symptom that already begins with the module name is not repeated.
 *
 * @param string $module_label  Clinical module name.
 * @param string $symptom_label Plain-language symptom phrase.
 * @return string Escaped HTML.
 */
function mha_collection_format_labeled_symptom( $module_label, $symptom_label ) {
	$module  = trim( wp_strip_all_tags( (string) $module_label ) );
	$symptom = trim( wp_strip_all_tags( (string) $symptom_label ) );

	if ( $module !== '' && $symptom !== '' ) {
		$symptom = preg_replace( '/^' . preg_quote( $module, '/' ) . '\s*[:\x{2013}\x{2014}\-]?\s*/iu', '', $symptom );
		$symptom = trim( (string) $symptom );
	}

	if ( $symptom !== '' ) {
		$symptom = mb_strtoupper( mb_substr( $symptom, 0, 1 ) ) . mb_substr( $symptom, 1 );
	}

	if ( $module === '' ) {
		return esc_html( $symptom );
	}
	if ( $symptom === '' ) {
		return '<strong>' . esc_html( $module ) . '</strong>';
	}

	return '<strong>' . esc_html( $module ) . ':</strong> ' . esc_html( $symptom );
}

/**
 * Build the focused results email for a screen collection.
 *
 * This intentionally mirrors the collection summary and next-steps sections,
 * without including the legacy Screen result copy or the full answer list.
 *
 * @param string $user_screen_id Public results token.
 * @param int    $collection_id  Screen collection post ID.
 * @param int    $entry_id       Gravity Forms entry ID.
 * @return string
 */
function mha_get_collection_email_body( $user_screen_id, $collection_id, $entry_id ) {
	$collection_id = absint( $collection_id );
	$entry_id      = absint( $entry_id );

	if ( ! $collection_id || ! $entry_id || ! class_exists( 'GFAPI' ) ) {
		return '';
	}

	$entry = GFAPI::get_entry( $entry_id );
	if ( ! is_array( $entry ) || is_wp_error( $entry ) ) {
		return '';
	}

	$form           = GFAPI::get_form( (int) $entry['form_id'] );
	$module_results = mha_get_collection_module_results( $collection_id, $entry, $form );
	$settings       = mha_get_collection_results_settings( $collection_id );
	$positive       = isset( $module_results['positive'] ) ? $module_results['positive'] : array();
	$recommended    = isset( $module_results['recommended'] ) ? $module_results['recommended'] : array();
	$resources      = isset( $settings['results_resources'] ) ? $settings['results_resources'] : array();
	$results_url    = add_query_arg(
		'sid',
		(string) $user_screen_id,
		set_url_scheme( home_url( '/screening-results/' ), is_ssl() ? 'https' : 'http' )
	);

	$html  = '<h1 style="margin-top:0;">' . esc_html( get_the_title( $collection_id ) ) . ' Results</h1>';
	$html .= '<p><a href="' . esc_url( $results_url ) . '">View your results online</a></p>';

	if ( ! empty( $positive ) ) {
		$html .= '<p><strong>' . esc_html( $settings['positive_summary_prefix'] ) . '</strong></p><ul>';
		foreach ( $positive as $module ) {
			$html .= '<li>' . mha_collection_format_labeled_symptom( $module['module_label'], $module['symptom_label'] ) . '</li>';
		}
		$html .= '</ul>';
	} else {
		$html .= '<p>' . esc_html( $settings['empty_positive_message'] ) . '</p>';
	}

	$html .= '<h2>Next Steps</h2>';
	if ( '' !== $settings['share_results_message'] ) {
		$html .= '<p>' . esc_html( $settings['share_results_message'] ) . '</p>';
	}

	if ( ! empty( $settings['show_recommended_screens'] ) && ! empty( $recommended ) ) {
		$html .= '<h3>' . esc_html( $settings['recommended_screens_heading'] ) . '</h3><ul>';
		foreach ( $recommended as $module ) {
			$screen_id = isset( $module['recommended_screen'] ) ? absint( $module['recommended_screen'] ) : 0;
			$url       = $screen_id ? get_permalink( $screen_id ) : '';
			if ( ! $url ) {
				continue;
			}
			$html .= '<li><a href="' . esc_url( $url ) . '">' . esc_html( get_the_title( $screen_id ) ) . '</a></li>';
		}
		$html .= '</ul>';
	}

	if ( ! empty( $resources ) ) {
		$html .= '<h3>Articles and resources</h3><ul>';
		foreach ( $resources as $resource_id ) {
			$resource_id = absint( $resource_id );
			if ( ! $resource_id || 'publish' !== get_post_status( $resource_id ) ) {
				continue;
			}
			$url   = get_permalink( $resource_id );
			$title = get_the_title( $resource_id );
			if ( ! $url || '' === $title ) {
				continue;
			}
			$html .= '<li><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></li>';
		}
		$html .= '</ul>';
	}

	return $html;
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

/**
 * Sample results_modules and copy for the demo screen collection.
 *
 * @return array{modules:array<int,array<string,mixed>>,copy:array<string,mixed>,resource_ids:int[],resource_titles:string[]}
 */
function mha_example_collection_results_seed_payload() {
	return array(
		'modules'          => array(
			array(
				'module_label'             => 'Social Phobia',
				'symptom_label'            => 'feeling very nervous with groups of children or adults',
				'form_page_number'         => 1,
				'positive_score_threshold' => 1,
				'maximum_score'            => 2,
				'recommended_screen'       => 251896,
				'recommended_screen_title' => 'Social Anxiety Test',
			),
			array(
				'module_label'             => 'Separation Anxiety',
				'symptom_label'            => 'trouble being away from home or caregivers',
				'form_page_number'         => 2,
				'positive_score_threshold' => 1,
				'maximum_score'            => 7,
				'recommended_screen'       => '',
				'recommended_screen_title' => '',
			),
			array(
				'module_label'             => 'Agoraphobia',
				'symptom_label'            => 'avoiding places where it feels hard to escape or get help',
				'form_page_number'         => 3,
				'positive_score_threshold' => 1,
				'maximum_score'            => 8,
				'recommended_screen'       => '',
				'recommended_screen_title' => '',
			),
			array(
				'module_label'             => 'Panic Attacks',
				'symptom_label'            => 'times when you become suddenly very afraid',
				'form_page_number'         => 4,
				'positive_score_threshold' => 1,
				'maximum_score'            => 4,
				'recommended_screen'       => '',
				'recommended_screen_title' => '',
			),
			array(
				'module_label'             => 'Generalized Anxiety',
				'symptom_label'            => 'feeling nervous a lot of the time',
				'form_page_number'         => 5,
				'positive_score_threshold' => 1,
				'maximum_score'            => 4,
				'recommended_screen'       => 3165,
				'recommended_screen_title' => 'Anxiety Test',
			),
			array(
				'module_label'             => 'Specific Phobia',
				'symptom_label'            => 'intense fear of one particular thing or situation',
				'form_page_number'         => 6,
				'positive_score_threshold' => 1,
				'maximum_score'            => 7,
				'recommended_screen'       => '',
				'recommended_screen_title' => '',
			),
			array(
				'module_label'             => 'OCD',
				'symptom_label'            => 'unwanted thoughts or repeating habits that are hard to stop',
				'form_page_number'         => 7,
				'positive_score_threshold' => 1,
				'maximum_score'            => 9,
				'recommended_screen'       => 245905,
				'recommended_screen_title' => 'OCD Test',
			),
			array(
				'module_label'             => 'PTSD',
				'symptom_label'            => 'upsetting memories or feeling on edge after something scary',
				'form_page_number'         => 8,
				'positive_score_threshold' => 4,
				'maximum_score'            => 17,
				'recommended_screen'       => 3176,
				'recommended_screen_title' => 'PTSD Test',
			),
			array(
				'module_label'             => 'Eating Disorder',
				'symptom_label'            => 'worrying a lot about food, weight, or body shape',
				'form_page_number'         => 9,
				'positive_score_threshold' => 1,
				'maximum_score'            => 4,
				'recommended_screen'       => 3175,
				'recommended_screen_title' => 'Eating Disorder Test',
			),
			array(
				'module_label'             => 'Depression',
				'symptom_label'            => 'feeling down, hopeless, or uninterested most days',
				'form_page_number'         => 10,
				'positive_score_threshold' => 1,
				'maximum_score'            => 7,
				'recommended_screen'       => 22,
				'recommended_screen_title' => 'Depression Test',
			),
			array(
				'module_label'             => 'Mania',
				'symptom_label'            => 'unusually high energy, racing thoughts, or needing much less sleep',
				'form_page_number'         => 11,
				'positive_score_threshold' => 1,
				'maximum_score'            => 5,
				'recommended_screen'       => 3168,
				'recommended_screen_title' => 'Bipolar Test',
			),
		),
		'copy'             => array(
			'positive_summary_prefix'     => 'Here are some things you seem to be struggling with right now:',
			'negative_summary_prefix'     => "Here are some things you don't seem to be struggling with right now:",
			'incomplete_summary_prefix'   => "These pages weren't fully answered, so we couldn't tell whether these areas are a concern:",
			'empty_positive_message'      => "Based on your answers, you don't seem to be struggling with the areas we asked about right now. Still, it's okay to check in with someone you trust if anything feels off.",
			'share_results_message'       => 'Consider sharing these results with someone you trust — a parent, counselor, or other supportive adult.',
			'recommended_screens_heading' => 'Screens to take next',
			'show_recommended_screens'    => 1,
		),
		'resource_ids'     => array( 125368, 86760, 70636 ),
		'resource_titles'  => array(
			'What does peer support look like?',
			'Should I go to therapy?',
			'Am I broken?',
		),
	);
}

/**
 * Resolve a published post ID by ID first, then exact title.
 *
 * @param int    $post_id   Preferred ID.
 * @param string $title     Fallback title.
 * @param string $post_type Post type.
 * @return int
 */
function mha_example_collection_resolve_post_id( $post_id, $title, $post_type ) {
	$post_id = absint( $post_id );
	if ( $post_id && get_post_type( $post_id ) === $post_type ) {
		return $post_id;
	}
	$title = trim( (string) $title );
	if ( $title === '' ) {
		return 0;
	}
	$found = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'title'          => $title,
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	return ! empty( $found[0] ) ? (int) $found[0] : 0;
}

/**
 * Write sample collection results fields onto a screen-collection post.
 *
 * @param int  $collection_id Collection post ID.
 * @param bool $overwrite     Replace existing results_modules when true.
 * @return array{ok:bool,message:string,collection_id:int,modules:int,resources:int}
 */
function mha_seed_example_collection_results( $collection_id, $overwrite = false ) {
	$collection_id = absint( $collection_id );
	$empty         = array(
		'ok'             => false,
		'message'        => '',
		'collection_id'  => $collection_id,
		'modules'        => 0,
		'resources'      => 0,
	);

	if ( ! $collection_id || get_post_type( $collection_id ) !== 'screen-collection' ) {
		$empty['message'] = 'Collection post not found.';
		return $empty;
	}
	if ( ! function_exists( 'update_field' ) ) {
		$empty['message'] = 'ACF is not available.';
		return $empty;
	}

	$existing = get_field( 'results_modules', $collection_id );
	if ( ! $overwrite && is_array( $existing ) && ! empty( $existing ) ) {
		$empty['ok']      = true;
		$empty['message'] = 'Results modules already exist. Re-run with force=1 to overwrite.';
		$empty['modules'] = count( $existing );
		return $empty;
	}

	$payload = mha_example_collection_results_seed_payload();
	$modules = array();
	foreach ( $payload['modules'] as $row ) {
		$rec_id = mha_example_collection_resolve_post_id(
			isset( $row['recommended_screen'] ) ? $row['recommended_screen'] : 0,
			isset( $row['recommended_screen_title'] ) ? $row['recommended_screen_title'] : '',
			'screen'
		);
		$modules[] = array(
			'module_label'             => $row['module_label'],
			'symptom_label'            => $row['symptom_label'],
			'form_page_number'         => $row['form_page_number'],
			'positive_score_threshold' => $row['positive_score_threshold'],
			'maximum_score'            => $row['maximum_score'],
			'recommended_screen'       => $rec_id ? $rec_id : '',
		);
	}

	update_field( 'results_modules', $modules, $collection_id );
	foreach ( $payload['copy'] as $name => $value ) {
		update_field( $name, $value, $collection_id );
	}

	$resources = array();
	foreach ( $payload['resource_ids'] as $i => $rid ) {
		$title = isset( $payload['resource_titles'][ $i ] ) ? $payload['resource_titles'][ $i ] : '';
		$resolved = mha_example_collection_resolve_post_id( $rid, $title, 'article' );
		if ( ! $resolved ) {
			$resolved = mha_example_collection_resolve_post_id( $rid, $title, 'page' );
		}
		if ( $resolved ) {
			$resources[] = $resolved;
		}
	}
	if ( ! empty( $resources ) ) {
		update_field( 'results_resources', $resources, $collection_id );
	}

	return array(
		'ok'            => true,
		'message'       => 'Seeded sample collection results fields.',
		'collection_id' => $collection_id,
		'modules'       => count( $modules ),
		'resources'     => count( $resources ),
	);
}

/**
 * Admin flag: visit /wp-admin/?mha_seed_collection_results=1 while logged in as an admin.
 * Optional: &id=123 to target a specific collection, &force=1 to overwrite existing modules.
 */
function mha_maybe_seed_example_collection_results() {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || empty( $_GET['mha_seed_collection_results'] ) ) {
		return;
	}

	$requested = absint( wp_unslash( $_GET['id'] ?? 0 ) );
	if ( ! $requested ) {
		$flag = wp_unslash( $_GET['mha_seed_collection_results'] );
		$requested = is_numeric( $flag ) ? absint( $flag ) : 257658;
	}

	$collection_id = mha_example_collection_resolve_post_id( $requested, 'Example Collection', 'screen-collection' );
	$overwrite     = ! empty( $_GET['force'] );
	$result        = mha_seed_example_collection_results( $collection_id, $overwrite );

	wp_die(
		esc_html(
			sprintf(
				'%s Collection ID %d. Modules: %d. Resources: %d.',
				$result['message'],
				$result['collection_id'],
				$result['modules'],
				$result['resources']
			)
		),
		'Seed collection results',
		array( 'response' => $result['ok'] ? 200 : 400 )
	);
}
add_action( 'admin_init', 'mha_maybe_seed_example_collection_results' );
