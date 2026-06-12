<?php
/**
 * Contextual TranslatePress translation blocks for Gravity Forms choice labels.
 *
 * Problem: The same choice text (e.g. "Not at all") appears on many screening questions
 * and may need different Spanish translations per test (OCD vs Depression). Wrapping an
 * entire field in translation-block exposes raw HTML in the TranslatePress editor.
 *
 * Solution: When a form's CSS Class includes trp-contextual-choices, each choice label
 * is wrapped in a translation-block with an inner context span (TranslatePress docs
 * pattern: different inner HTML = different block, e.g. masc/fem wrappers around "Partner").
 *
 * ## Setup (content editors)
 *
 * 1. Open Gravity Forms → select the screening form → Settings → Form Layout.
 * 2. In "CSS Class Name", add:
 *      trp-contextual-choices trp-ctx-ocd
 *    Replace `ocd` with a short slug for that test (e.g. trp-ctx-depression).
 *
 *    Optional modifiers (form CSS class):
 *      trp-page-1 … trp-page-50 — only wrap choices on that form page.
 *      trp-no-demographics — skip pages whose page break CSS class includes
 *        `demographics` (page 1 uses Form Settings → First Page CSS class).
 * 3. Save the form.
 * 4. Visit the form on the front end and open TranslatePress → Translate Site.
 * 5. Translate each choice label once; the same translation applies to every question
 *    on that form that uses that label.
 *
 * If you omit trp-ctx-{slug}, the context falls back to form-{formId} (e.g. form-84).
 *
 * @package MHA_Screens
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wrap Gravity Forms choice labels in contextual TranslatePress translation blocks.
 *
 * @param string   $choice_markup Choice HTML.
 * @param array    $choice        Choice properties.
 * @param GF_Field $field         Field object.
 * @param string   $value         Current field value.
 * @return string
 */
function mha_trp_gf_wrap_contextual_choice_markup( $choice_markup, $choice, $field, $value ) {
	unset( $value );

	if ( ! is_object( $field ) || empty( $field->formId ) ) {
		return $choice_markup;
	}

	if ( mha_trp_gf_should_skip_render() ) {
		return $choice_markup;
	}

	$context = mha_trp_gf_get_form_translation_context( (int) $field->formId );
	if ( null === $context ) {
		return $choice_markup;
	}

	if ( ! mha_trp_gf_should_apply_contextual_markup( $field, $context ) ) {
		return $choice_markup;
	}

	$choice_text = isset( $choice['text'] ) ? (string) $choice['text'] : '';
	if ( '' === trim( wp_strip_all_tags( $choice_text ) ) ) {
		return $choice_markup;
	}

	$choice_key   = mha_trp_gf_build_choice_key( $choice );
	$context_class = 'trp-ctx-' . $context['slug'] . '-' . $choice_key;

	return mha_trp_gf_inject_contextual_choice_markup( $choice_markup, $choice_text, $context_class );
}

add_filter( 'gform_field_choice_markup_pre_render', 'mha_trp_gf_wrap_contextual_choice_markup', 10, 4 );

/**
 * Skip form editor and other admin contexts where translated markup is not needed.
 *
 * @return bool
 */
function mha_trp_gf_should_skip_render() {
	if ( class_exists( 'GFCommon' ) && GFCommon::is_form_editor() ) {
		return true;
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return true;
	}

	return false;
}

/**
 * Load and cache translation context for a form.
 *
 * @param int $form_id Gravity Form ID.
 * @return array|null Context array with slug key, or null if feature disabled.
 */
function mha_trp_gf_get_form_translation_context( $form_id ) {
	static $cache = array();

	if ( isset( $cache[ $form_id ] ) ) {
		return $cache[ $form_id ];
	}

	if ( ! class_exists( 'GFFormsModel' ) ) {
		$cache[ $form_id ] = null;
		return null;
	}

	$form = GFFormsModel::get_form_meta( $form_id );
	if ( empty( $form ) || empty( $form['cssClass'] ) ) {
		$cache[ $form_id ] = null;
		return null;
	}

	if ( ! mha_trp_gf_css_class_list_has( $form['cssClass'], 'trp-contextual-choices' ) ) {
		$cache[ $form_id ] = null;
		return null;
	}

	$cache[ $form_id ] = array(
		'slug'              => mha_trp_gf_parse_context_slug( $form['cssClass'], $form_id ),
		'target_page'       => mha_trp_gf_parse_target_page( $form['cssClass'] ),
		'skip_demographics' => mha_trp_gf_css_class_list_has( $form['cssClass'], 'trp-no-demographics' ),
	);

	return $cache[ $form_id ];
}

/**
 * Whether contextual markup should apply to a field on its current form page.
 *
 * @param GF_Field $field   Field object.
 * @param array    $context Translation context from mha_trp_gf_get_form_translation_context().
 * @return bool
 */
function mha_trp_gf_should_apply_contextual_markup( $field, $context ) {
	$page_number = isset( $field->pageNumber ) ? absint( $field->pageNumber ) : 1;
	if ( $page_number <= 0 ) {
		$page_number = 1;
	}

	if ( null !== $context['target_page'] && $page_number !== $context['target_page'] ) {
		return false;
	}

	if ( ! empty( $context['skip_demographics'] ) ) {
		$page_css = mha_trp_gf_get_page_css_class( (int) $field->formId, $page_number );
		if ( mha_trp_gf_css_class_list_has( $page_css, 'demographics' ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Parse trp-page-{n} from form CSS classes (1–50), or null for all pages.
 *
 * @param string $css_class Form CSS class string.
 * @return int|null Target page number, or null when not restricted.
 */
function mha_trp_gf_parse_target_page( $css_class ) {
	if ( preg_match( '/\btrp-page-(50|[1-4][0-9]|[1-9])\b/i', $css_class, $matches ) ) {
		return absint( $matches[1] );
	}

	return null;
}

/**
 * Load and cache the CSS class string for a multipage form page.
 *
 * Page 1 uses firstPageCssClass; later pages use the matching page break field.
 *
 * @param int $form_id     Gravity Form ID.
 * @param int $page_number 1-based page number.
 * @return string
 */
function mha_trp_gf_get_page_css_class( $form_id, $page_number ) {
	static $cache = array();

	$form_id     = absint( $form_id );
	$page_number = absint( $page_number );
	if ( $page_number <= 0 ) {
		$page_number = 1;
	}

	$cache_key = $form_id . ':' . $page_number;
	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}

	if ( ! class_exists( 'GFFormsModel' ) ) {
		$cache[ $cache_key ] = '';
		return '';
	}

	$form = GFFormsModel::get_form_meta( $form_id );
	if ( empty( $form ) ) {
		$cache[ $cache_key ] = '';
		return '';
	}

	if ( 1 === $page_number ) {
		$cache[ $cache_key ] = isset( $form['firstPageCssClass'] ) ? (string) $form['firstPageCssClass'] : '';
		return $cache[ $cache_key ];
	}

	$page_css = '';
	if ( ! empty( $form['fields'] ) && is_array( $form['fields'] ) ) {
		foreach ( $form['fields'] as $field ) {
			if ( is_object( $field ) && 'page' === $field->type && absint( $field->pageNumber ) === $page_number ) {
				$page_css = isset( $field->cssClass ) ? (string) $field->cssClass : '';
				break;
			}
		}
	}

	$cache[ $cache_key ] = $page_css;
	return $cache[ $cache_key ];
}

/**
 * Parse trp-ctx-{slug} from form CSS classes, or fall back to form-{id}.
 *
 * @param string $css_class Form CSS class string.
 * @param int    $form_id   Form ID.
 * @return string Sanitized slug.
 */
function mha_trp_gf_parse_context_slug( $css_class, $form_id ) {
	if ( preg_match( '/\btrp-ctx-([a-z0-9_-]+)\b/i', $css_class, $matches ) ) {
		return sanitize_title( strtolower( $matches[1] ) );
	}

	return 'form-' . absint( $form_id );
}

/**
 * Build a stable key for a choice label within a form context.
 *
 * @param array $choice Choice properties.
 * @return string
 */
function mha_trp_gf_build_choice_key( $choice ) {
	$text = isset( $choice['text'] ) ? (string) $choice['text'] : '';
	$key  = sanitize_title( wp_strip_all_tags( $text ) );

	if ( '' !== $key ) {
		return $key;
	}

	$value = isset( $choice['value'] ) ? (string) $choice['value'] : '';
	$key   = sanitize_title( $value );

	return '' !== $key ? 'choice-' . $key : 'choice';
}

/**
 * Apply contextual translation markup to label or option elements.
 *
 * Radio/checkbox labels get an inner span (valid HTML). Select options receive
 * translation-block classes on the option element itself because nested tags
 * inside option are invalid HTML.
 *
 * @param string $choice_markup Original choice markup.
 * @param string $choice_text   Original choice text.
 * @param string $context_class Inner context class (trp-ctx-{slug}-{choice-key}).
 * @return string
 */
function mha_trp_gf_inject_contextual_choice_markup( $choice_markup, $choice_text, $context_class ) {
	if ( preg_match( '/^\s*<option\b/i', $choice_markup ) ) {
		return mha_trp_gf_add_block_class_to_option( $choice_markup, $context_class );
	}

	$wrapped = mha_trp_gf_build_translation_block_markup( $context_class, wp_kses_post( $choice_text ) );

	return mha_trp_gf_replace_label_inner_html( $choice_markup, $wrapped );
}

/**
 * Add translation-block classes to a select option element.
 *
 * @param string $choice_markup Option markup.
 * @param string $context_class Context class for this form + choice.
 * @return string
 */
function mha_trp_gf_add_block_class_to_option( $choice_markup, $context_class ) {
	$block_classes = 'translation-block ' . $context_class;

	$updated = preg_replace_callback(
		'/(<option\b)([^>]*)(>)(.*?)(<\/option>)/is',
		function ( $matches ) use ( $block_classes ) {
			$attrs = $matches[2];

			if ( preg_match( '/\bclass=(["\'])(.*?)\1/i', $attrs, $class_match ) ) {
				$new_class = trim( $class_match[2] . ' ' . $block_classes );
				$attrs     = preg_replace(
					'/\bclass=(["\']).*?\1/i',
					'class="' . esc_attr( $new_class ) . '"',
					$attrs,
					1
				);
			} else {
				$attrs .= ' class="' . esc_attr( $block_classes ) . '"';
			}

			return $matches[1] . $attrs . $matches[3] . $matches[4] . $matches[5];
		},
		$choice_markup,
		1,
		$count
	);

	return $count > 0 ? $updated : $choice_markup;
}

/**
 * Replace label inner HTML with the translation block span.
 *
 * @param string $choice_markup Original choice markup.
 * @param string $wrapped       Wrapped translation block HTML.
 * @return string
 */
function mha_trp_gf_replace_label_inner_html( $choice_markup, $wrapped ) {
	$updated = preg_replace(
		'/(<label\b[^>]*>)(.*?)(<\/label>)/is',
		'$1' . $wrapped . '$3',
		$choice_markup,
		1,
		$count
	);

	return $count > 0 ? $updated : $choice_markup;
}

/**
 * Build translation-block markup matching TranslatePress contextual pattern.
 *
 * Outer element is only translation-block; inner span carries the unique context
 * class so the stored block HTML differs per form (see TP docs masc/fem example).
 *
 * @param string $context_class Inner context class (trp-ctx-{slug}-{choice-key}).
 * @param string $text          Choice label text.
 * @return string
 */
function mha_trp_gf_build_translation_block_markup( $context_class, $text ) {
	return sprintf(
		'<span class="translation-block"><span class="%s">%s</span></span>',
		esc_attr( $context_class ),
		$text
	);
}

/**
 * Check whether a CSS class string contains a given class token.
 *
 * @param string $css_class   Space-separated class list.
 * @param string $class_token Class to find.
 * @return bool
 */
function mha_trp_gf_css_class_list_has( $css_class, $class_token ) {
	$classes = preg_split( '/\s+/', trim( $css_class ) );
	return in_array( $class_token, $classes, true );
}
