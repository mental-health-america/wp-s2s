<?php

/**
 * Enqueue screen collection script
 */
add_action('wp_enqueue_scripts', 'mhaScreenCollectionScripts');
function mhaScreenCollectionScripts() {
	wp_enqueue_script('mha_screen_collection', plugin_dir_url( __FILE__ ).'js/screen-collection.js', array('jquery'), MHASCREENS_VERSION, true);
	wp_localize_script('mha_screen_collection', 'mhaScreenCollection', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

/**
 * Parse Gravity Forms form ID from a Screen post (content, excerpt, optional ACF description, and the_content output).
 *
 * @param int $screen_id Post ID.
 * @return int|null Form ID or null if not found.
 */
function mha_screen_collection_parse_gravity_form_id_from_screen( $screen_id ) {
	$post = get_post( $screen_id );
	if ( ! $post ) {
		return null;
	}

	$candidates = array( $post->post_content );
	$candidates[] = apply_filters( 'the_content', $post->post_content );

	if ( ! empty( $post->post_excerpt ) ) {
		$candidates[] = $post->post_excerpt;
		$candidates[] = apply_filters( 'the_content', $post->post_excerpt );
	}

	if ( function_exists( 'get_field' ) ) {
		$description = get_field( 'description', $screen_id, false );
		if ( is_string( $description ) && $description !== '' ) {
			$candidates[] = $description;
			$candidates[] = apply_filters( 'the_content', $description );
		}
	}

	foreach ( $candidates as $html ) {
		if ( ! is_string( $html ) || $html === '' ) {
			continue;
		}
		if ( preg_match( '/\[gravityform\s+id=["\']?(\d+)["\']?/i', $html, $matches ) && ! empty( $matches[1] ) ) {
			return absint( $matches[1] );
		}
	}

	return null;
}

/**
 * Build unified prescreen query token: {screen_collection_id}_{form_id}_{gf_page}.
 *
 * @param int $collection_id Screen collection post ID.
 * @param int $form_id       Gravity Forms form ID.
 * @param int $page          GF page number.
 * @return string Empty if invalid.
 */
function mha_screen_collection_build_sc( $collection_id, $form_id, $page ) {
	$collection_id = absint( $collection_id );
	$form_id       = absint( $form_id );
	$page          = absint( $page );
	if ( ! $collection_id || ! $form_id || $page < 1 ) {
		return '';
	}
	return $collection_id . '_' . $form_id . '_' . $page;
}

/**
 * Parse and validate `sc` token (three underscore-separated positive integers).
 *
 * @param string $token Raw token.
 * @return array{collection_id:int,form_id:int,page:int}|null
 */
function mha_screen_collection_parse_sc_token( $token ) {
	if ( ! is_string( $token ) || $token === '' ) {
		return null;
	}
	$token = trim( $token );
	if ( strlen( $token ) > 64 || substr_count( $token, '_' ) !== 2 ) {
		return null;
	}
	$parts = explode( '_', $token, 3 );
	if ( count( $parts ) !== 3 ) {
		return null;
	}
	$collection_id = absint( $parts[0] );
	$form_id       = absint( $parts[1] );
	$page          = absint( $parts[2] );
	if ( ! $collection_id || ! $form_id || $page < 1 ) {
		return null;
	}
	if ( get_post_type( $collection_id ) !== 'screen-collection' ) {
		return null;
	}
	if ( ! class_exists( 'GFAPI' ) || ! class_exists( 'GFFormDisplay' ) ) {
		return null;
	}
	$form = GFAPI::get_form( $form_id );
	if ( ! $form || is_wp_error( $form ) ) {
		return null;
	}
	if ( (int) rgar( $form, 'id' ) !== $form_id ) {
		return null;
	}
	$max = (int) GFFormDisplay::get_max_page_number( $form );
	if ( $max < 1 ) {
		$max = 1;
	}
	if ( $page > $max ) {
		return null;
	}
	return array(
		'collection_id' => $collection_id,
		'form_id'       => $form_id,
		'page'          => $page,
	);
}

/**
 * First GF page whose first substantive field is a Section (same rule as prescreen_pages_meta).
 *
 * @param array $form GF form array.
 * @return int Page number (minimum 1).
 */
function mha_screen_collection_prescreen_first_section_page( $form ) {
	$rows = mha_screen_collection_prescreen_pages_meta( $form, home_url( '/' ), array() );
	if ( empty( $rows ) ) {
		return 1;
	}
	return (int) $rows[0]['page'];
}

/**
 * Find hidden field that stores the full `sc` token for multipage POST (label "Prescreen sc" or admin/prepop prescreen_sc).
 *
 * @param array $form GF form.
 * @return int Field ID or 0.
 */
function mha_screen_collection_prescreen_sc_context_field_id( $form ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return 0;
	}
	foreach ( $form['fields'] as $field ) {
		if ( ! is_object( $field ) ) {
			continue;
		}
		$admin = isset( $field->adminLabel ) ? trim( (string) $field->adminLabel ) : '';
		if ( $admin !== '' && ( strcasecmp( $admin, 'Prescreen sc' ) === 0 || strtolower( $admin ) === 'prescreen_sc' ) ) {
			return (int) $field->id;
		}
		$label = isset( $field->label ) ? trim( wp_strip_all_tags( (string) $field->label ) ) : '';
		if ( $label !== '' && strcasecmp( $label, 'Prescreen sc' ) === 0 ) {
			return (int) $field->id;
		}
		if ( ! empty( $field->allowsPrepopulate ) ) {
			$input_name = rgar( $field, 'inputName' );
			if ( is_string( $input_name ) && strtolower( trim( $input_name ) ) === 'prescreen_sc' ) {
				return (int) $field->id;
			}
		}
	}
	return 0;
}

/**
 * Read sc context field from POST for any submitted form.
 *
 * @return array{collection_id:int,form_id:int,page:int}|null
 */
function mha_screen_collection_prescreen_sc_token_from_post_for_forms() {
	if ( ! function_exists( 'rgpost' ) ) {
		return null;
	}
	// Try known pattern input_X for each form render — scan POST for input_* containing token shape.
	if ( empty( $_POST ) || ! is_array( $_POST ) ) {
		return null;
	}
	foreach ( $_POST as $key => $val ) {
		if ( ! is_string( $key ) || ! is_string( $val ) || strpos( $key, 'input_' ) !== 0 ) {
			continue;
		}
		$t = trim( (string) wp_unslash( $val ) );
		if ( $t === '' || strlen( $t ) > 64 ) {
			continue;
		}
		$parsed = mha_screen_collection_parse_sc_token( $t );
		if ( $parsed ) {
			return $parsed;
		}
	}
	return null;
}

/**
 * Parsed `sc` from GET/POST or legacy sc_page_{formId} (partial, no collection).
 *
 * @return array<string,int|bool>|null
 */
function mha_screen_collection_get_sc_request_context() {
	if ( isset( $_GET['sc'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$p = mha_screen_collection_parse_sc_token( sanitize_text_field( wp_unslash( $_GET['sc'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $p ) {
			return $p;
		}
	}
	if ( isset( $_POST['sc'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$p = mha_screen_collection_parse_sc_token( sanitize_text_field( wp_unslash( $_POST['sc'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $p ) {
			return $p;
		}
	}
	if ( function_exists( 'rgpost' ) ) {
		$from_hidden = mha_screen_collection_prescreen_sc_token_from_post_for_forms();
		if ( $from_hidden ) {
			return $from_hidden;
		}
	}
	// Legacy: sc_page_{formId}=page (GET and POST when query string is stripped).
	$sources = array( $_GET, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	foreach ( $sources as $bag ) {
		if ( empty( $bag ) || ! is_array( $bag ) ) {
			continue;
		}
		foreach ( $bag as $key => $val ) {
			if ( ! is_string( $key ) || ! preg_match( '/^sc_page_(\d+)$/', $key, $m ) ) {
				continue;
			}
			$fid = absint( $m[1] );
			$pg  = absint( wp_unslash( $val ) );
			if ( $fid && $pg > 0 ) {
				return array(
					'legacy_sc_page' => true,
					'collection_id'  => 0,
					'form_id'        => $fid,
					'page'           => $pg,
				);
			}
		}
	}
	return null;
}

/**
 * Collection ID for current prescreen context when `sc` identifies the form.
 *
 * @param int $form_id GF form ID.
 * @return int Collection post ID or 0.
 */
function mha_screen_collection_prescreen_resolve_collection_id( $form_id ) {
	$form_id = absint( $form_id );
	$ctx     = mha_screen_collection_get_sc_request_context();
	if ( $ctx && empty( $ctx['legacy_sc_page'] ) && (int) $ctx['form_id'] === $form_id && ! empty( $ctx['collection_id'] ) ) {
		return (int) $ctx['collection_id'];
	}
	return 0;
}

/**
 * Build prescreen page metadata: GF page number, section label, and target URL with start-page query arg.
 * Omits any GF page whose first substantive field on that page (in form order) is not a Section field.
 * Skips Gravity Forms structural fields (e.g. page breaks, honeypot) so the page-break field is not treated as the first field on a page.
 *
 * @param array  $form Gravity Forms form array.
 * @param string $base_url Permalink to append query args to.
 * @param array  $extra_query_args org, ref, iframe, partner, etc.
 * @return array<int, array{page:int,label:string,href:string}>
 */
function mha_screen_collection_prescreen_pages_meta( $form, $base_url, $extra_query_args = array() ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) || ! class_exists( 'GFFormDisplay' ) ) {
		return array();
	}

	$form_id        = (int) rgar( $form, 'id' );
	$collection_id  = isset( $extra_query_args['collection_id'] ) ? absint( $extra_query_args['collection_id'] ) : 0;
	$extra_no_coll  = $extra_query_args;
	unset( $extra_no_coll['collection_id'] );
	$extra_query_args = $extra_no_coll;

	$max      = (int) GFFormDisplay::get_max_page_number( $form );
	if ( $max < 1 ) {
		$max = 1;
	}

	$pages = array();
	for ( $p = 1; $p <= $max; $p++ ) {
		$first_on_page = null;
		foreach ( $form['fields'] as $field ) {
			if ( ! is_object( $field ) ) {
				continue;
			}
			$page_number = isset( $field->pageNumber ) ? (int) $field->pageNumber : 1;
			if ( $page_number !== $p ) {
				continue;
			}
			// GF assigns the next page number to Page Break fields; skip structural fields when finding "first" field.
			$ftype = isset( $field->type ) ? $field->type : '';
			if ( 'page' === $ftype || 'honeypot' === $ftype ) {
				continue;
			}
			$first_on_page = $field;
			break;
		}

		// Only prescreen pages whose first substantive field on that page (in form order) is a Section.
		if ( ! $first_on_page || ! isset( $first_on_page->type ) || $first_on_page->type !== 'section' ) {
			continue;
		}

		$label = isset( $first_on_page->label ) ? wp_strip_all_tags( (string) $first_on_page->label ) : '';
		if ( $label === '' ) {
			$label = sprintf( __( 'Page %d', 'mha_screens' ), $p );
		}

		if ( $collection_id && $form_id ) {
			$sc_token = mha_screen_collection_build_sc( $collection_id, $form_id, $p );
			if ( '' !== $sc_token ) {
				$args = array_merge( $extra_query_args, array( 'sc' => $sc_token ) );
			} else {
				$args = array_merge( $extra_query_args, array( 'sc_page_' . $form_id => $p ) );
			}
		} else {
			$args = array_merge( $extra_query_args, array( 'sc_page_' . $form_id => $p ) );
		}
		$pages[] = array(
			'page'  => $p,
			'label' => $label,
			'href'  => add_query_arg( $args, $base_url ),
		);
	}

	return $pages;
}

/**
 * Cookie name: marks that the user started this form via prescreen deep link (survives multipage POST when query args drop off).
 *
 * @param int $form_id GF form ID.
 * @return string
 */
function mha_screen_collection_prescreen_cookie_name( $form_id ) {
	return 'mha_sc_prescreen_' . absint( $form_id );
}

/**
 * Whether the custom prescreen progress bar should replace the default GF bar.
 *
 * @param int $form_id GF form ID.
 * @return bool
 */
function mha_screen_collection_prescreen_progress_mode_active( $form_id ) {
	$form_id = absint( $form_id );
	if ( ! $form_id ) {
		return false;
	}
	$ctx = mha_screen_collection_get_sc_request_context();
	if ( $ctx && ! empty( $ctx['form_id'] ) && (int) $ctx['form_id'] === $form_id ) {
		return true;
	}
	$cname = mha_screen_collection_prescreen_cookie_name( $form_id );
	return ! empty( $_COOKIE[ $cname ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Whether a field default contains a merge tag (export vs nav CSV field).
 *
 * @param object $field GF field object.
 * @param string $tag   Merge tag substring e.g. "{sc_prescreen_answers}".
 * @return bool
 */
function mha_screen_collection_prescreen_field_default_contains_tag( $field, $tag ) {
	if ( ! is_object( $field ) || ! is_string( $tag ) || $tag === '' ) {
		return false;
	}
	$def = isset( $field->defaultValue ) ? (string) $field->defaultValue : '';

	return strpos( $def, $tag ) !== false;
}

/**
 * GF field that receives `{sc_prescreen_answers}` JSON (per-page yes/no), distinct from nav CSV field.
 *
 * @param array $form GF form array.
 * @return int Field ID or 0.
 */
function mha_screen_collection_prescreen_sc_answers_export_field_id( $form ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return 0;
	}
	foreach ( $form['fields'] as $field ) {
		if ( ! is_object( $field ) ) {
			continue;
		}
		if ( mha_screen_collection_prescreen_field_default_contains_tag( $field, '{sc_prescreen_answers}' ) ) {
			return (int) $field->id;
		}
		$admin = isset( $field->adminLabel ) ? trim( (string) $field->adminLabel ) : '';
		if ( $admin !== '' && strcasecmp( $admin, 'sc_prescreen_answers' ) === 0 ) {
			return (int) $field->id;
		}
		if ( ! empty( $field->allowsPrepopulate ) ) {
			$input_name = rgar( $field, 'inputName' );
			if ( is_string( $input_name ) && strtolower( trim( $input_name ) ) === 'sc_prescreen_answers' ) {
				return (int) $field->id;
			}
		}
		$label = isset( $field->label ) ? trim( wp_strip_all_tags( (string) $field->label ) ) : '';
		if ( $label !== '' && strcasecmp( $label, 'SC Prescreen Answers' ) === 0 ) {
			return (int) $field->id;
		}
	}
	return 0;
}

/**
 * Cookie: raw screen-collection user id (bridged to PHP for `{sc_user}` hash). Non-HttpOnly; set by JS.
 *
 * @return string
 */
function mha_screen_collection_sc_user_cookie_name() {
	return 'mha_screen_collection_uid';
}

/**
 * Cookie: JSON array of prescreen answers for `{sc_prescreen_answers}`. Set by JS; keyed by screen + form.
 *
 * @param int $screen_id Screen post ID.
 * @param int $form_id   GF form ID.
 * @return string
 */
function mha_screen_collection_sc_prescreen_json_cookie_name( $screen_id, $form_id ) {
	return 'mha_sc_prescreen_json_' . absint( $screen_id ) . '_' . absint( $form_id );
}

/**
 * Validate and re-encode prescreen JSON payload: list of { "page": int, "answer": 0|1 }.
 *
 * @param mixed $data Decoded JSON.
 * @return string JSON string or empty.
 */
function mha_screen_collection_prescreen_sc_answers_json_sanitize_payload( $data ) {
	if ( ! is_array( $data ) ) {
		return '';
	}
	$out = array();
	foreach ( $data as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}
		$page = isset( $row['page'] ) ? absint( $row['page'] ) : 0;
		$ans  = isset( $row['answer'] ) ? (int) $row['answer'] : -1;
		if ( ! $page || ( $ans !== 0 && $ans !== 1 ) ) {
			continue;
		}
		$out[] = array(
			'page'   => $page,
			'answer' => $ans,
		);
	}
	if ( empty( $out ) ) {
		return '';
	}
	usort(
		$out,
		static function ( $a, $b ) {
			return (int) $a['page'] <=> (int) $b['page'];
		}
	);
	$json = wp_json_encode( $out );
	if ( ! is_string( $json ) || strlen( $json ) > 4096 ) {
		return '';
	}
	return $json;
}

/**
 * JSON for `{sc_prescreen_answers}` from POST (multipage carry-over).
 *
 * @param array $form GF form.
 * @return string JSON or empty.
 */
function mha_screen_collection_prescreen_sc_answers_json_from_post( $form ) {
	$fid = mha_screen_collection_prescreen_sc_answers_export_field_id( $form );
	if ( ! $fid || ! function_exists( 'rgpost' ) ) {
		return '';
	}
	$raw = rgpost( 'input_' . $fid );
	if ( $raw === null || $raw === '' || is_array( $raw ) ) {
		return '';
	}
	$raw = trim( (string) wp_unslash( $raw ) );
	if ( $raw === '' || strlen( $raw ) > 4096 ) {
		return '';
	}
	$decoded = json_decode( $raw, true );
	return mha_screen_collection_prescreen_sc_answers_json_sanitize_payload( $decoded );
}

/**
 * JSON for `{sc_prescreen_answers}` from JS cookie (GET / first paint).
 *
 * @param array $form GF form.
 * @return string JSON or empty.
 */
function mha_screen_collection_prescreen_sc_answers_json_from_cookie( $form ) {
	if ( empty( $form['id'] ) ) {
		return '';
	}
	$screen_id = function_exists( 'get_queried_object_id' ) ? (int) get_queried_object_id() : 0;
	if ( ! $screen_id ) {
		return '';
	}
	$name = mha_screen_collection_sc_prescreen_json_cookie_name( $screen_id, (int) $form['id'] );
	if ( empty( $_COOKIE[ $name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}
	$raw = wp_unslash( $_COOKIE[ $name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! is_string( $raw ) || $raw === '' ) {
		return '';
	}
	$raw = rawurldecode( $raw );
	if ( strlen( $raw ) > 4096 ) {
		return '';
	}
	$decoded = json_decode( $raw, true );
	return mha_screen_collection_prescreen_sc_answers_json_sanitize_payload( $decoded );
}

/**
 * Resolved JSON for `{sc_prescreen_answers}`: POST then cookie.
 *
 * @param array $form GF form.
 * @return string JSON or empty.
 */
function mha_screen_collection_prescreen_sc_answers_json_resolved( $form ) {
	$from_post = mha_screen_collection_prescreen_sc_answers_json_from_post( $form );
	if ( $from_post !== '' ) {
		return $from_post;
	}
	return mha_screen_collection_prescreen_sc_answers_json_from_cookie( $form );
}

/**
 * Raw user id from JS cookie (for hashing only; never output raw).
 *
 * @return string
 */
function mha_screen_collection_sc_user_id_from_cookie() {
	$name = mha_screen_collection_sc_user_cookie_name();
	if ( empty( $_COOKIE[ $name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}
	$raw = wp_unslash( $_COOKIE[ $name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! is_string( $raw ) || $raw === '' ) {
		return '';
	}
	$raw = trim( rawurldecode( $raw ) );
	if ( $raw === '' || strlen( $raw ) > 256 ) {
		return '';
	}
	return sanitize_text_field( $raw );
}

/**
 * HMAC-SHA256 of screen-collection user id for `{sc_user}` merge tag (stable per site, not reversible without secret).
 *
 * @return string Hex digest or empty.
 */
function mha_screen_collection_sc_user_merge_value() {
	$uid = mha_screen_collection_sc_user_id_from_cookie();
	if ( $uid === '' ) {
		return '';
	}
	return hash_hmac( 'sha256', $uid, wp_salt( 'mha_screen_collection_sc_user' ) );
}

/**
 * Find the GF field that stores prescreen **nav** order (comma-separated GF page numbers).
 * Excludes fields whose default is `{sc_prescreen_answers}` (export JSON field).
 * Matches admin prescreen_nav_csv / prescreen_answers, label Prescreen Answers, inputName prescreen_answers. (Label "SC Prescreen Answers" is for the JSON export field with `{sc_prescreen_answers}`.)
 *
 * @param array $form GF form array.
 * @return int Field ID or 0.
 */
function mha_screen_collection_prescreen_answers_field_id( $form ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return 0;
	}
	foreach ( $form['fields'] as $field ) {
		if ( ! is_object( $field ) ) {
			continue;
		}
		if ( mha_screen_collection_prescreen_field_default_contains_tag( $field, '{sc_prescreen_answers}' ) ) {
			continue;
		}
		$admin = isset( $field->adminLabel ) ? trim( (string) $field->adminLabel ) : '';
		if ( $admin !== '' ) {
			$al = strtolower( $admin );
			if ( in_array( $al, array( 'prescreen_nav_csv', 'prescreen_answers' ), true ) || strcasecmp( $admin, 'Prescreen Answers' ) === 0 ) {
				return (int) $field->id;
			}
		}
		$label = isset( $field->label ) ? trim( wp_strip_all_tags( (string) $field->label ) ) : '';
		if ( $label !== '' && strcasecmp( $label, 'Prescreen Answers' ) === 0 ) {
			return (int) $field->id;
		}
		if ( ! empty( $field->allowsPrepopulate ) ) {
			$input_name = rgar( $field, 'inputName' );
			if ( is_string( $input_name ) && strtolower( trim( $input_name ) ) === 'prescreen_answers' ) {
				return (int) $field->id;
			}
		}
	}
	return 0;
}

/**
 * Sanitize prescreen nav CSV (comma-separated GF page numbers).
 *
 * @param string $raw Raw string.
 * @return string Sanitized CSV or empty.
 */
function mha_screen_collection_prescreen_nav_csv_normalize_raw( $raw ) {
	if ( ! is_string( $raw ) ) {
		return '';
	}
	$raw = trim( $raw );
	if ( $raw === '' || strlen( $raw ) > 512 ) {
		return '';
	}
	if ( ! preg_match( '/^[\d,]+$/', $raw ) ) {
		return '';
	}
	return sanitize_text_field( $raw );
}

/**
 * Prescreen nav order CSV from the posted hidden field (multipage-safe).
 *
 * @param array $form GF form array.
 * @return string Sanitized CSV or empty.
 */
function mha_screen_collection_prescreen_nav_csv_from_field( $form ) {
	if ( empty( $form['id'] ) || ! function_exists( 'rgpost' ) ) {
		return '';
	}
	$fid = mha_screen_collection_prescreen_answers_field_id( $form );
	if ( ! $fid ) {
		return '';
	}
	$raw = rgpost( 'input_' . $fid );
	if ( $raw === null || $raw === '' || is_array( $raw ) ) {
		return '';
	}
	return mha_screen_collection_prescreen_nav_csv_normalize_raw( (string) wp_unslash( $raw ) );
}

/**
 * Cookie name for JS-bridged Prescreen Answers CSV (GET / template pager).
 *
 * @param int $form_id GF form ID.
 * @return string
 */
function mha_screen_collection_prescreen_nav_csv_cookie_name( $form_id ) {
	return 'mha_prescreen_answers_' . absint( $form_id );
}

/**
 * Prescreen nav CSV from cookie set by prescreen-form-nav.js (same order as collection prescreen localStorage).
 *
 * @param array $form GF form array.
 * @return string Sanitized CSV or empty.
 */
function mha_screen_collection_prescreen_nav_csv_from_cookie( $form ) {
	if ( empty( $form['id'] ) ) {
		return '';
	}
	$name = mha_screen_collection_prescreen_nav_csv_cookie_name( (int) $form['id'] );
	if ( empty( $_COOKIE[ $name ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return '';
	}
	$raw = wp_unslash( $_COOKIE[ $name ] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! is_string( $raw ) || $raw === '' ) {
		return '';
	}
	$raw = rawurldecode( $raw );
	return mha_screen_collection_prescreen_nav_csv_normalize_raw( $raw );
}

/**
 * Resolved Prescreen Answers CSV: POST first, then cookie (GET parity with JS reorder).
 *
 * @param array $form GF form array.
 * @return string Sanitized CSV or empty.
 */
function mha_screen_collection_prescreen_nav_csv_resolved( $form ) {
	$from_post = mha_screen_collection_prescreen_nav_csv_from_field( $form );
	if ( $from_post !== '' ) {
		return $from_post;
	}
	return mha_screen_collection_prescreen_nav_csv_from_cookie( $form );
}

/**
 * Reorder prescreen page rows to match a CSV of GF page numbers (Yes-first then No from the hidden "Prescreen Answers" field).
 *
 * @param array  $pages Prescreen rows from mha_screen_collection_prescreen_pages_meta().
 * @param string $csv   Comma-separated page numbers.
 * @return array
 */
function mha_screen_collection_prescreen_reorder_pages( $pages, $csv ) {
	$csv = trim( (string) $csv );
	if ( $csv === '' || empty( $pages ) ) {
		return $pages;
	}
	$order = array_filter( array_map( 'absint', explode( ',', $csv ) ) );
	if ( empty( $order ) ) {
		return $pages;
	}
	$by_page = array();
	foreach ( $pages as $row ) {
		$by_page[ (int) $row['page'] ] = $row;
	}
	$out = array();
	foreach ( $order as $p ) {
		if ( isset( $by_page[ $p ] ) ) {
			$out[]        = $by_page[ $p ];
			unset( $by_page[ $p ] );
		}
	}
	foreach ( $by_page as $row ) {
		$out[] = $row;
	}
	return $out;
}

/**
 * org / ref / iframe / partner args for collection permalinks (current request).
 *
 * @return array<string, string>
 */
function mha_screen_collection_query_args_from_request_for_collection() {
	$args = array();
	if ( isset( $_GET['org'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$args['org'] = sanitize_text_field( wp_unslash( $_GET['org'] ) );
	} else {
		$org_qv = get_query_var( 'org' );
		if ( is_string( $org_qv ) && $org_qv !== '' ) {
			$args['org'] = sanitize_text_field( $org_qv );
		}
	}
	if ( isset( $_GET['ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$args['ref'] = sanitize_text_field( wp_unslash( $_GET['ref'] ) );
	}
	if ( isset( $_GET['iframe'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$args['iframe'] = sanitize_text_field( wp_unslash( $_GET['iframe'] ) );
	}
	$partner_var = get_query_var( 'partner' );
	if ( isset( $_GET['partner'] ) && function_exists( 'mha_approved_partners' ) && in_array( $partner_var, mha_approved_partners(), true ) ) {
		$args['partner'] = $partner_var;
	}
	return $args;
}

/**
 * Permalink to the screen-collection for the current prescreen context (`sc` / hidden), with org/ref/iframe/partner aligned to the request.
 *
 * @param int $screen_post_id Screen post ID — only used when the WP_Query fallback filter is enabled.
 * @return string URL or empty when unknown.
 */
function mha_screen_collection_prescreen_back_to_collection_url( $screen_post_id = 0 ) {
	$screen_post_id = absint( $screen_post_id );
	$ctx            = mha_screen_collection_get_sc_request_context();
	$collection_id  = ( $ctx && ! empty( $ctx['collection_id'] ) ) ? absint( $ctx['collection_id'] ) : 0;
	if ( $collection_id && get_post_type( $collection_id ) === 'screen-collection' ) {
		$permalink = get_permalink( $collection_id );
		if ( ! $permalink ) {
			return '';
		}
		$args = mha_screen_collection_query_args_from_request_for_collection();
		return empty( $args ) ? $permalink : add_query_arg( $args, $permalink );
	}

	if ( ! apply_filters( 'mha_screen_collection_prescreen_back_wp_query_fallback', false, $screen_post_id ) || ! $screen_post_id ) {
		return '';
	}

	$q = new WP_Query(
		array(
			'post_type'              => 'screen-collection',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_query'             => array(
				array(
					'key'     => 'screens',
					'value'   => (string) $screen_post_id,
					'compare' => 'LIKE',
				),
			),
		)
	);
	if ( ! $q->have_posts() ) {
		wp_reset_postdata();
		return '';
	}
	$q->the_post();
	$permalink = get_permalink();
	wp_reset_postdata();
	if ( ! $permalink ) {
		return '';
	}
	$args = mha_screen_collection_query_args_from_request_for_collection();
	return empty( $args ) ? $permalink : add_query_arg( $args, $permalink );
}

/**
 * Build progress-bar markup as prescreen section links when URL/query/hidden carry `sc`, legacy `sc_page_{formId}`, or the prescreen cookie is set.
 *
 * @param array $form           Gravity Forms form.
 * @param int   $current_page  Current GF page number from GFFormDisplay::get_current_page().
 * @param array $layout        Theme layout array from get_layout_array().
 * @return string HTML or empty string when not in prescreen navigation mode / no links.
 */
function mha_screen_collection_prescreen_progress_links_html( $form, $current_page, $layout ) {
	$form_id = (int) rgar( $form, 'id' );
	if ( ! $form_id || ! mha_screen_collection_prescreen_progress_mode_active( $form_id ) ) {
		return '';
	}

	$base = get_permalink();
	if ( ! $base ) {
		return '';
	}

	$extra = array();
	if ( isset( $_GET['org'] ) ) {
		$extra['org'] = sanitize_text_field( wp_unslash( $_GET['org'] ) );
	}
	if ( isset( $_GET['ref'] ) ) {
		$extra['ref'] = sanitize_text_field( wp_unslash( $_GET['ref'] ) );
	}
	if ( isset( $_GET['iframe'] ) ) {
		$extra['iframe'] = sanitize_text_field( wp_unslash( $_GET['iframe'] ) );
	}
	$partner_var = get_query_var( 'partner' );
	if ( isset( $_GET['partner'] ) && function_exists( 'mha_approved_partners' ) && in_array( $partner_var, mha_approved_partners() ) ) {
		$extra['partner'] = $partner_var;
	}

	$resolved_col = mha_screen_collection_prescreen_resolve_collection_id( $form_id );
	if ( $resolved_col ) {
		$extra['collection_id'] = $resolved_col;
	}

	$pages = mha_screen_collection_prescreen_pages_meta( $form, $base, $extra );
	if ( empty( $pages ) ) {
		return '';
	}

	$nav_csv = mha_screen_collection_prescreen_nav_csv_resolved( $form );
	$pages   = mha_screen_collection_prescreen_reorder_pages( $pages, $nav_csv );

	$step_count   = count( $pages );
	// Step classes follow visual order after CSV reorder (matches {prescreen_answers} / cookie bridge).
	$nav_page_ids = array_map( 'intval', array_column( $pages, 'page' ) );
	$current_idx  = array_search( (int) $current_page, $nav_page_ids, true );
	$step_pos     = false !== $current_idx ? (int) $current_idx + 1 : min( max( 1, (int) $current_page ), $step_count );

	$ol_class = 'full-progress-bar prescreen-section-nav clearfix step-' . (int) $step_pos . '-of-' . (int) $step_count;

	$inner = '';
	$back_collection_url = mha_screen_collection_prescreen_back_to_collection_url( (int) get_queried_object_id() );
	if ( $back_collection_url !== '' ) {
		$inner .= '<p><a class="button round-tl thin teal mha-back-to-screen-collection" href="' . esc_url( $back_collection_url ) . '">' . esc_html__( 'Back to Screen Collection', 'mha_screens' ) . '</a></p>';
	}
	$inner .= '<ol class="' . esc_attr( $ol_class ) . '">';
	foreach ( $pages as $idx => $row ) {
		$k           = (int) $row['page'];
		$pager_class = '';
		if ( false !== $current_idx ) {
			if ( $idx < $current_idx ) {
				$pager_class = 'filled';
			} elseif ( $idx === $current_idx ) {
				$pager_class = 'active';
			} else {
				$pager_class = 'empty';
			}
		} elseif ( (int) $current_page === $k ) {
			// Current GF page is missing from $nav_page_ids (edge case): only mark active, never use $current_page > $k
			// because $pages may be reordered and numeric GF order would mis-label steps.
			$pager_class = 'active';
		} else {
			$pager_class = 'empty';
		}
		$inner .= '<li class="step-' . esc_attr( (string) $k ) . ' ' . esc_attr( $pager_class ) . '">';
		$inner .= '<a href="' . esc_url( $row['href'] ) . '">' . esc_html( $row['label'] ) . '</a>';
		$inner .= '</li>';
	}
	$inner .= '</ol>';

	$out = '';
	if ( is_array( $layout ) && in_array( 'side_progress', $layout, true ) ) {
		$out .= '<div class="progress-container sticky">';
	}
	$out .= $inner;
	if ( is_array( $layout ) && in_array( 'side_progress', $layout, true ) ) {
		$out .= '</div>';
	}

	return $out;
}

/**
 * Resolve current GF page for prescreen pager when rendering before gform_pre_render (`sc` / legacy `sc_page_*` / GF current page).
 *
 * @param int $form_id GF form ID.
 * @return int Page number, minimum 1.
 */
function mha_screen_collection_prescreen_resolve_current_page( $form_id ) {
	$form_id = absint( $form_id );
	if ( ! $form_id ) {
		return 1;
	}
	$ctx = mha_screen_collection_get_sc_request_context();
	if ( $ctx && (int) $ctx['form_id'] === $form_id ) {
		return max( 1, (int) $ctx['page'] );
	}
	if ( class_exists( 'GFFormDisplay' ) ) {
		return max( 1, (int) GFFormDisplay::get_current_page( $form_id ) );
	}
	return 1;
}

/**
 * Prescreen section pager for single-screen template (above the_content). Suppress the in-form bar via
 * filter mha_screen_collection_suppress_embedded_prescreen_progress when outputting here.
 *
 * @param int|null $screen_post_id Screen post ID; defaults to queried object.
 * @return string HTML or empty string.
 */
function mha_screen_collection_prescreen_render_template_pager( $screen_post_id = null ) {
	if ( ! class_exists( 'GFAPI' ) ) {
		return '';
	}
	$post_id = $screen_post_id ? absint( $screen_post_id ) : (int) get_queried_object_id();
	if ( ! $post_id ) {
		return '';
	}
	$form_id = mha_screen_collection_parse_gravity_form_id_from_screen( $post_id );
	if ( ! $form_id ) {
		return '';
	}
	$form = GFAPI::get_form( $form_id );
	if ( ! $form || is_wp_error( $form ) ) {
		return '';
	}
	if ( ! mha_screen_collection_prescreen_progress_mode_active( $form_id ) ) {
		return '';
	}
	$layout       = function_exists( 'get_layout_array' ) ? get_layout_array( get_query_var( 'layout' ) ) : array();
	$current_page = mha_screen_collection_prescreen_resolve_current_page( $form_id );
	$inner        = mha_screen_collection_prescreen_progress_links_html( $form, $current_page, $layout );
	if ( $inner === '' ) {
		return '';
	}
	return '<div class="mha-prescreen-template-pager">' . $inner . '</div>';
}

/**
 * When loading a screen via GET with `sc` or legacy sc_page_{formId}, open that GF page (does not run during form POST/paging).
 *
 * @param array $form Form object.
 * @return array
 */
function mha_screen_collection_prescreen_gform_pre_render( $form ) {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
		return $form;
	}
	if ( empty( $form['id'] ) || ! class_exists( 'GFFormDisplay' ) ) {
		return $form;
	}

	$form_id = (int) $form['id'];
	if ( function_exists( 'rgpost' ) && absint( rgpost( 'gform_submit' ) ) === $form_id ) {
		return $form;
	}

	$ctx = mha_screen_collection_get_sc_request_context();
	if ( ! $ctx || (int) $ctx['form_id'] !== $form_id ) {
		return $form;
	}
	$page = (int) $ctx['page'];
	if ( $page < 1 ) {
		return $form;
	}

	$max = (int) GFFormDisplay::get_max_page_number( $form );
	if ( $max < 1 ) {
		$max = 1;
	}
	if ( $page > $max ) {
		return $form;
	}

	GFFormDisplay::set_current_page( $form_id, $page );
	return $form;
}
add_filter( 'gform_pre_render', 'mha_screen_collection_prescreen_gform_pre_render', 20 );

/**
 * Set a short-lived cookie when the user lands with `sc` or legacy sc_page_{formId} so multipage POSTs still use the prescreen progress bar.
 */
function mha_screen_collection_prescreen_set_progress_cookie() {
	if ( headers_sent() ) {
		return;
	}
	$secure = is_ssl();

	if ( isset( $_GET['sc'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$p = mha_screen_collection_parse_sc_token( sanitize_text_field( wp_unslash( $_GET['sc'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $p && ! empty( $p['form_id'] ) ) {
			mha_screen_collection_prescreen_set_progress_cookie_for_form( (int) $p['form_id'], $secure );
		}
	}

	foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! is_string( $key ) || ! preg_match( '/^sc_page_(\d+)$/', $key, $m ) ) {
			continue;
		}
		$form_id = absint( $m[1] );
		if ( ! $form_id ) {
			continue;
		}
		mha_screen_collection_prescreen_set_progress_cookie_for_form( $form_id, $secure );
	}
}

/**
 * @param int  $form_id GF form ID.
 * @param bool $secure  SSL flag for setcookie.
 */
function mha_screen_collection_prescreen_set_progress_cookie_for_form( $form_id, $secure ) {
	$form_id = absint( $form_id );
	if ( ! $form_id ) {
		return;
	}
	$cname = mha_screen_collection_prescreen_cookie_name( $form_id );
	$exp   = time() + DAY_IN_SECONDS;
	setcookie( $cname, '1', $exp, COOKIEPATH, COOKIE_DOMAIN, $secure, true );
	if ( COOKIEPATH !== SITECOOKIEPATH ) {
		setcookie( $cname, '1', $exp, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true );
	}
}
add_action( 'template_redirect', 'mha_screen_collection_prescreen_set_progress_cookie', 0 );

/**
 * Clear prescreen progress cookie after successful submission.
 *
 * @param string|array $confirmation Confirmation message or redirect array.
 * @param array        $form         Form.
 * @param array        $entry        Entry.
 * @param bool         $ajax         Ajax flag.
 * @return string|array
 */
function mha_screen_collection_prescreen_confirmation_clear_cookie( $confirmation, $form, $entry, $ajax ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	if ( headers_sent() || empty( $form['id'] ) ) {
		return $confirmation;
	}
	$form_id = absint( $form['id'] );
	$cname   = mha_screen_collection_prescreen_cookie_name( $form_id );
	$past    = time() - HOUR_IN_SECONDS;
	$secure  = is_ssl();
	setcookie( $cname, '', $past, COOKIEPATH, COOKIE_DOMAIN, $secure, true );
	if ( COOKIEPATH !== SITECOOKIEPATH ) {
		setcookie( $cname, '', $past, SITECOOKIEPATH, COOKIE_DOMAIN, $secure, true );
	}
	return $confirmation;
}
add_filter( 'gform_confirmation', 'mha_screen_collection_prescreen_confirmation_clear_cookie', 10, 4 );

/**
 * Replace prescreen merge tags in field defaults / messages:
 * `{prescreen_answers}` (nav CSV), `{prescreen_sc}`, `{sc_prescreen_answers}` (JSON page/answer), `{sc_user}` (hashed user id).
 *
 * @param string     $text       Text with merge tags.
 * @param array      $form       Form.
 * @param array|bool $entry      Entry or false.
 * @param bool       $url_encode Whether to URL-encode.
 * @param bool       $esc_html   Escape HTML.
 * @param bool       $nl2br      nl2br.
 * @param string     $format     Format.
 * @return string
 */
function mha_screen_collection_prescreen_replace_merge_tags( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	if ( ! is_string( $text ) ) {
		return $text;
	}
	$has_legacy_csv = strpos( $text, '{prescreen_answers}' ) !== false;
	$has_sc         = strpos( $text, '{prescreen_sc}' ) !== false;
	$has_sc_pa      = strpos( $text, '{sc_prescreen_answers}' ) !== false;
	$has_sc_user    = strpos( $text, '{sc_user}' ) !== false;
	if ( ! $has_legacy_csv && ! $has_sc && ! $has_sc_pa && ! $has_sc_user ) {
		return $text;
	}
	if ( empty( $form ) || ! is_array( $form ) ) {
		if ( $has_legacy_csv ) {
			$text = str_replace( '{prescreen_answers}', '', $text );
		}
		if ( $has_sc ) {
			$text = str_replace( '{prescreen_sc}', '', $text );
		}
		if ( $has_sc_pa ) {
			$text = str_replace( '{sc_prescreen_answers}', '', $text );
		}
		if ( $has_sc_user ) {
			$text = str_replace( '{sc_user}', '', $text );
		}
		return $text;
	}
	if ( $has_legacy_csv ) {
		$csv  = mha_screen_collection_prescreen_nav_csv_resolved( $form );
		$text = str_replace( '{prescreen_answers}', $csv, $text );
	}
	if ( $has_sc ) {
		$form_id = (int) rgar( $form, 'id' );
		$token    = '';
		$ctx      = mha_screen_collection_get_sc_request_context();
		if ( $ctx && (int) $ctx['form_id'] === $form_id && ! empty( $ctx['collection_id'] ) ) {
			$token = mha_screen_collection_build_sc( (int) $ctx['collection_id'], $form_id, (int) $ctx['page'] );
		}
		$text = str_replace( '{prescreen_sc}', $token, $text );
	}
	if ( $has_sc_pa ) {
		$json = mha_screen_collection_prescreen_sc_answers_json_resolved( $form );
		$text = str_replace( '{sc_prescreen_answers}', $url_encode ? rawurlencode( $json ) : $json, $text );
	}
	if ( $has_sc_user ) {
		$user_token = mha_screen_collection_sc_user_merge_value();
		$text       = str_replace( '{sc_user}', $url_encode ? rawurlencode( $user_token ) : $user_token, $text );
	}
	return $text;
}
add_filter( 'gform_replace_merge_tags', 'mha_screen_collection_prescreen_replace_merge_tags', 10, 7 );

/**
 * Fill hidden "Prescreen sc" from request so multipage POST keeps collection + form + page context.
 *
 * @param array $form Form.
 * @return array
 */
function mha_screen_collection_prescreen_populate_sc_context_field( $form ) {
	$fid = mha_screen_collection_prescreen_sc_context_field_id( $form );
	if ( ! $fid ) {
		return $form;
	}
	$form_id = (int) rgar( $form, 'id' );
	$token   = '';
	$ctx     = mha_screen_collection_get_sc_request_context();
	if ( $ctx && (int) $ctx['form_id'] === $form_id && ! empty( $ctx['collection_id'] ) ) {
		$token = mha_screen_collection_build_sc( (int) $ctx['collection_id'], $form_id, (int) $ctx['page'] );
	}
	if ( $token === '' && function_exists( 'rgpost' ) ) {
		$prev = rgpost( 'input_' . $fid ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( is_string( $prev ) && $prev !== '' ) {
			$parsed = mha_screen_collection_parse_sc_token( trim( $prev ) );
			if ( $parsed && (int) $parsed['form_id'] === $form_id ) {
				$token = trim( $prev );
			}
		}
	}
	if ( $token === '' ) {
		return $form;
	}
	foreach ( $form['fields'] as &$field ) {
		if ( ! is_object( $field ) ) {
			continue;
		}
		if ( (int) $field->id !== $fid ) {
			continue;
		}
		$field->defaultValue = $token;
		break;
	}
	unset( $field );
	return $form;
}
add_filter( 'gform_pre_render', 'mha_screen_collection_prescreen_populate_sc_context_field', 23 );

/**
 * Ensure the hidden field default reflects the posted value after Next/Previous (redundant with merge tag but reliable).
 *
 * @param array $form Form.
 * @return array
 */
function mha_screen_collection_prescreen_populate_answers_field( $form ) {
	$fid = mha_screen_collection_prescreen_answers_field_id( $form );
	if ( ! $fid ) {
		return $form;
	}
	$csv = mha_screen_collection_prescreen_nav_csv_resolved( $form );
	if ( $csv === '' ) {
		return $form;
	}
	foreach ( $form['fields'] as &$field ) {
		if ( ! is_object( $field ) ) {
			continue;
		}
		if ( (int) $field->id === $fid ) {
			$field->defaultValue = $csv;
			break;
		}
	}
	unset( $field );
	return $form;
}
add_filter( 'gform_pre_render', 'mha_screen_collection_prescreen_populate_answers_field', 25 );

/**
 * Front-end script: fill "Prescreen Answers" hidden field from collection localStorage (pager is in single-screen template).
 *
 * @param array $form  GF form.
 * @param bool  $ajax  Ajax mode.
 */
function mha_screen_collection_prescreen_enqueue_form_nav_script( $form, $ajax ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
	if ( is_admin() || empty( $form['id'] ) ) {
		return;
	}
	$form_id = (int) $form['id'];
	if ( ! mha_screen_collection_prescreen_progress_mode_active( $form_id ) ) {
		return;
	}
	$field_id = mha_screen_collection_prescreen_answers_field_id( $form );
	if ( ! $field_id ) {
		return;
	}
	$extra = array();
	$cid   = mha_screen_collection_prescreen_resolve_collection_id( $form_id );
	if ( $cid ) {
		$extra['collection_id'] = $cid;
	}
	$pages = mha_screen_collection_prescreen_pages_meta( $form, home_url( '/' ), $extra );
	if ( empty( $pages ) ) {
		return;
	}
	$section_pages = array_map( 'intval', array_column( $pages, 'page' ) );
	$screen_id     = (int) get_queried_object_id();
	if ( $screen_id <= 0 && function_exists( 'get_the_ID' ) ) {
		$screen_id = (int) get_the_ID();
	}
	$ver = defined( 'MHASCREENS_VERSION' ) ? MHASCREENS_VERSION : '1';
	wp_enqueue_script(
		'mha-prescreen-form-nav',
		plugins_url( 'js/prescreen-form-nav.js', __FILE__ ),
		array( 'jquery' ),
		$ver,
		true
	);
	wp_localize_script(
		'mha-prescreen-form-nav',
		'mhaPrescreenFormNav',
		array(
			'formId'       => $form_id,
			'fieldId'      => $field_id,
			'sectionPages' => $section_pages,
			'storageKey'   => 'mha_screen_prescreen_' . $screen_id . '_' . $form_id,
			'screenId'     => $screen_id > 0 ? $screen_id : 0,
		)
	);
}
add_action( 'gform_enqueue_scripts', 'mha_screen_collection_prescreen_enqueue_form_nav_script', 15, 2 );

/**
 * Check if a user has completed a specific screen
 */
function mha_check_screen_completion_status($user_id, $org_id, $screen_id, $return_debug = false) {

	if (empty($user_id) || empty($screen_id) || empty($org_id)) {
		if ($return_debug) {
			return array('completed' => false, 'debug' => array('error' => 'Missing required parameters', 'user_id' => $user_id, 'org_id' => $org_id, 'screen_id' => $screen_id));
		}
		return false;
	}
	
	// Build the Screen Collection field value: org_id&&&user_id
	$screen_collection_value = $org_id . '&&&' . $user_id;
	$debug_info = array(
		'screen_collection_value' => $screen_collection_value,
		'screen_id' => $screen_id
	);
	
	$post = get_post($screen_id);
	if (!$post) {
		if ($return_debug) {
			return array('completed' => false, 'debug' => array_merge($debug_info, array('error' => 'Screen post not found', 'screen_id' => $screen_id)));
		}
		return false;
	}

	$form_id = mha_screen_collection_parse_gravity_form_id_from_screen( $screen_id );
	
	if (!$form_id) {
		if ($return_debug) {
			return array('completed' => false, 'debug' => array_merge($debug_info, array(
				'error' => 'Gravity Forms shortcode not found in screen post content',
				'post_content_preview' => substr($post->post_content, 0, 200),
				'has_content' => !empty($post->post_content)
			)));
		}
		return false;
	}
	$debug_info['form_id'] = $form_id;
	$debug_info['form_id_source'] = 'parsed_from_shortcode';
	
	// Get the form to find field IDs
	$form = GFAPI::get_form($form_id);
	if (!$form || is_wp_error($form)) {
		if ($return_debug) {
			return array('completed' => false, 'debug' => array_merge($debug_info, array('error' => 'Form not found or error', 'form_error' => is_wp_error($form) ? $form->get_error_message() : 'Form is null')));
		}
		return false;
	}
	
	// Find the Screen Collection field ID
	$screen_collection_field_id = null;
	
	foreach ($form['fields'] as $field) {
		if (isset($field->label)) {
			$label_lower = strtolower($field->label);
			// Check for Screen Collection field
			if (strpos($label_lower, 'screen collection') !== false || $field->label == 'Screen Collection') {
				$screen_collection_field_id = $field->id;
				break;
			}
		}
	}
	
	if (!$screen_collection_field_id) {
		if ($return_debug) {
			$available_fields = array();
			foreach ($form['fields'] as $f) {
				$available_fields[] = isset($f->label) ? $f->label : 'No label';
			}
			return array('completed' => false, 'debug' => array_merge($debug_info, array('error' => 'Screen Collection field not found in form', 'available_fields' => $available_fields)));
		}
		return false;
	}
	$debug_info['screen_collection_field_id'] = $screen_collection_field_id;
	
	// Search for entries where Screen Collection field equals "org_id&&&user_id"
	$search_criteria = array(
		'status' => 'active',
		'field_filters' => array(
			array(
				'key' => $screen_collection_field_id,
				'value' => trim($screen_collection_value)
			)
		)
	);
	$debug_info['search_criteria'] = $search_criteria;
	
	$entries = GFAPI::get_entries($form_id, $search_criteria);
	
	// Check if we got a WP_Error
	if (is_wp_error($entries)) {
		if ($return_debug) {
			return array('completed' => false, 'debug' => array_merge($debug_info, array('error' => 'GFAPI::get_entries returned error', 'gfapi_error' => $entries->get_error_message())));
		}
		return false;
	}
	
	$entry_count = !empty($entries) ? count($entries) : 0;
	$completed = $entry_count > 0;
	
	if ($return_debug) {
		$debug_info['entries_found'] = $entry_count;
		$debug_info['entries'] = array();
		if (!empty($entries)) {
			foreach ($entries as $entry) {
				$debug_info['entries'][] = array(
					'id' => $entry['id'],
					'screen_collection_value' => isset($entry[$screen_collection_field_id]) ? $entry[$screen_collection_field_id] : 'NOT FOUND'
				);
			}
		}
		return array('completed' => $completed, 'debug' => $debug_info);
	}
	
	return $completed;
}

/**
 * Shortcode to display screen collection list
 * Usage: [screen_collection_list screens="1,2,3" screen_order="true" org_id="123" user_id="456" referrer="ref" iframe_mode="true" collection_id="123"]
 */
add_shortcode('screen_collection_list', 'mha_screen_collection_list_shortcode');
function mha_screen_collection_list_shortcode($atts) {
	$atts = shortcode_atts(array(
		'screens' => '',
		'screen_order' => false,
		'org_id' => '',
		'user_id' => '',
		'referrer' => '',
		'iframe_mode' => 'false',
		'collection_id' => '',
	), $atts);
	$screens = !empty($atts['screens']) ? explode(',', $atts['screens']) : array();
	$screen_order = filter_var($atts['screen_order'], FILTER_VALIDATE_BOOLEAN);
	$org_id = $atts['org_id'];
	$user_id = $atts['user_id'];
	$referrer = $atts['referrer'];
	$iframe_mode = $atts['iframe_mode'];
	$list_collection_id = absint( $atts['collection_id'] );
	if ( ! $list_collection_id && is_singular( 'screen-collection' ) ) {
		$list_collection_id = (int) get_queried_object_id();
	}
	if ( $list_collection_id && get_post_type( $list_collection_id ) !== 'screen-collection' ) {
		$list_collection_id = 0;
	}
	
	if (empty($screens)) {
		return '';
	}
	
	// Check completion status for each screen if screen_order is enabled and user_id is provided
	$completed_screens = array();
	$debug_data = array();
	if ($screen_order && !empty($user_id) && !empty($org_id)) {
		foreach ($screens as $screen) {
			$screen = intval(trim($screen));
			$result = mha_check_screen_completion_status($user_id, $org_id, $screen, true);
			$completed_screen_check = is_array($result) ? $result['completed'] : $result;
			
			// Store debug info for this screen
			if (is_array($result) && isset($result['debug'])) {
				$debug_data[$screen] = $result['debug'];
			}
			
			if ($screen && $completed_screen_check) {
				$completed_screens[] = $screen;
			}
		}
	}
	
	// Store debug data globally so AJAX handler can access it
	global $mha_screen_collection_debug;
	$mha_screen_collection_debug = $debug_data;
	
	// Only hide the list if user_id is not provided (let JavaScript handle showing it)
	$hide_class = empty($user_id) ? ' d-none' : '';
	$aria_hidden = empty($user_id) ? 'true' : 'false';
	
	ob_start();
	?>
	<div id="screenings-list" class="m-0<?php echo $hide_class; ?>" aria-hidden="<?php echo $aria_hidden; ?>" aria-label="Screenings List" data-screen-order="<?php echo $screen_order ? 'true' : 'false'; ?>"<?php echo $list_collection_id ? ' data-collection-id="' . esc_attr( (string) $list_collection_id ) . '"' : ''; ?>>
		<?php
		foreach($screens as $index => $screen):
			$screen = intval(trim($screen));
			if (!$screen) continue;
			
			$screen_id = 'screen-'.$screen;
			$screen_color = 'purple';
			
			$screen_link_args = array();
			
			if($org_id){
				$screen_link_args['org'] = $org_id;
			}
			if($referrer){
				$screen_link_args['ref'] = $referrer;
			}
			if($iframe_mode == 'true'){
				$screen_link_args['iframe'] = 'true';
			}

			if ( $list_collection_id && class_exists( 'GFAPI' ) ) {
				$collection_post_id = $list_collection_id;
				$screen_gf_id       = mha_screen_collection_parse_gravity_form_id_from_screen( $screen );
				if ( $collection_post_id && $screen_gf_id ) {
					$gform_for_sc = GFAPI::get_form( $screen_gf_id );
					if ( $gform_for_sc && ! is_wp_error( $gform_for_sc ) ) {
						$initial_page = mha_screen_collection_prescreen_first_section_page( $gform_for_sc );
						$sc_tok       = mha_screen_collection_build_sc( $collection_post_id, $screen_gf_id, $initial_page );
						if ( $sc_tok !== '' ) {
							$screen_link_args['sc'] = $sc_tok;
						}
					}
				}
			}

			$partner_var = get_query_var('partner');
			if(isset($_GET['partner']) && in_array($partner_var, mha_approved_partners() )){
				$screen_link_args['partner'] = $partner_var;
			}
			$screen_link = add_query_arg( $screen_link_args, get_the_permalink($screen));
			
			// Determine if this link should be disabled
			$is_disabled = false;
			$disabled_style = '';
			
			if ($screen_order) {
				if ($index === 0) {
					// First screen is always enabled
					$is_disabled = false;
				} else {
					// Check if previous screen is completed
					$previous_screen = intval(trim($screens[$index - 1]));
					$is_disabled = !in_array($previous_screen, $completed_screens);
				}
				
				if ($is_disabled) {
					$disabled_style = 'style="pointer-events: none; opacity: 0.5;"';
				}
			}

			?>  		
				<div class="screen-item relative" data-screen-id="<?php echo esc_attr($screen); ?>" data-screen-index="<?php echo esc_attr($index); ?>">
					<button class="reveal-excerpt"  
						data-reveal="<?php echo $screen_id; ?>"
						aria-expanded="false"
						aria-controls="<?php echo $screen_id; ?>">+</button>
					<a class="button round block text-left large <?php echo $screen_color; ?><?php echo $is_disabled ? ' disabled' : ''; ?>"
						href="<?php echo esc_url($screen_link); ?>"
						data-screen-link="<?php echo esc_attr($screen_link); ?>"
						<?php echo $disabled_style; ?>>
						<span class="excerpt-title"><?php echo get_the_title($screen); ?></span>
						<span class="excerpt block" style="display: none;" id="<?php echo $screen_id; ?>">
							<?php echo get_the_excerpt($screen); ?><br />
							<strong class="caps lh-normal">							
								<?php if(get_field('espanol', $screen)): ?>
									Tome el <?php echo get_the_title($screen); ?>
								<?php else: ?>
									Take <?php echo get_the_title($screen); ?>
								<?php endif; ?>
							</strong>
						</span>
					</a>
				</div>		
			<?php 
		endforeach;
		?>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Prescreen: Yes/No interest per GF page, then a single link to the first screening page. Usage:
 * [screen_collection_prescreen screen="123" collection_id="..." org_id="..." referrer="..." iframe_mode="false" form_url="https://..."]
 */
add_shortcode( 'screen_collection_prescreen', 'mha_screen_collection_prescreen_shortcode' );
function mha_screen_collection_prescreen_shortcode( $atts ) {
	if ( ! class_exists( 'GFAPI' ) || ! class_exists( 'GFFormDisplay' ) ) {
		return '';
	}

	$atts = shortcode_atts(
		array(
			'screen'         => '',
			'form_url'       => '',
			'org_id'         => '',
			'referrer'       => '',
			'iframe_mode'    => 'false',
			'collection_id'  => '',
		),
		$atts,
		'screen_collection_prescreen'
	);

	$screen_id = absint( $atts['screen'] );
	if ( ! $screen_id ) {
		return '';
	}

	$form_id = mha_screen_collection_parse_gravity_form_id_from_screen( $screen_id );
	if ( ! $form_id ) {
		return '';
	}

	$form = GFAPI::get_form( $form_id );
	if ( ! $form || is_wp_error( $form ) ) {
		return '';
	}

	$base_url = ! empty( $atts['form_url'] ) ? esc_url_raw( $atts['form_url'] ) : get_permalink( $screen_id );
	if ( ! $base_url ) {
		return '';
	}

	$screen_link_args = array();
	if ( ! empty( $atts['org_id'] ) ) {
		$screen_link_args['org'] = $atts['org_id'];
	}
	if ( ! empty( $atts['referrer'] ) ) {
		$screen_link_args['ref'] = $atts['referrer'];
	}
	if ( 'true' === $atts['iframe_mode'] ) {
		$screen_link_args['iframe'] = 'true';
	}
	$partner_var = get_query_var( 'partner' );
	if ( isset( $_GET['partner'] ) && function_exists( 'mha_approved_partners' ) && in_array( $partner_var, mha_approved_partners() ) ) {
		$screen_link_args['partner'] = $partner_var;
	}

	$collection_for_sc = absint( $atts['collection_id'] );
	if ( ! $collection_for_sc && is_singular( 'screen-collection' ) ) {
		$collection_for_sc = (int) get_queried_object_id();
	}
	if ( $collection_for_sc && get_post_type( $collection_for_sc ) !== 'screen-collection' ) {
		$collection_for_sc = 0;
	}
	if ( $collection_for_sc ) {
		$screen_link_args['collection_id'] = $collection_for_sc;
	}

	$pages = mha_screen_collection_prescreen_pages_meta( $form, $base_url, $screen_link_args );
	if ( empty( $pages ) ) {
		return '';
	}

	$form_start_href = ! empty( $pages[0]['href'] ) ? $pages[0]['href'] : $base_url;

	$storage_key = 'mha_screen_prescreen_' . $screen_id . '_' . $form_id;
	$config      = array(
		'storageKey' => $storage_key,
		'pages'      => $pages,
	);
	$config_json = wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );

	ob_start();
	?>
	<div class="mha-screen-collection-prescreen" data-screen-id="<?php echo esc_attr( (string) $screen_id ); ?>" data-form-id="<?php echo esc_attr( (string) $form_id ); ?>">
		<script type="application/json" class="mha-prescreen-config"><?php echo $config_json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></script>
		<form class="mha-prescreen-form" novalidate>
			<?php
			foreach ( $pages as $row ) :
				$p      = (int) $row['page'];
				$plabel = $row['label'];
				?>
			<fieldset class="mha-prescreen-question mb-3">
				<legend class="h6"><?php echo esc_html( sprintf( __( 'Are you interested in %s?', 'mha_screens' ), $plabel ) ); ?></legend>
				<div>
					<label class="d-inline-block mr-3"><input type="radio" name="prescreen_page_<?php echo esc_attr( (string) $p ); ?>" value="yes" required /> <?php esc_html_e( 'Yes', 'mha_screens' ); ?></label>
					<label class="d-inline-block"><input type="radio" name="prescreen_page_<?php echo esc_attr( (string) $p ); ?>" value="no" /> <?php esc_html_e( 'No', 'mha_screens' ); ?></label>
				</div>
			</fieldset>
				<?php
			endforeach;
			?>
			<button type="submit" class="button round"><?php esc_html_e( 'Continue', 'mha_screens' ); ?></button>
		</form>
		<div class="mha-prescreen-continue d-none" aria-hidden="true" hidden>
			<p><a class="button round" href="<?php echo esc_url( $form_start_href ); ?>"><?php esc_html_e( 'Continue to screening', 'mha_screens' ); ?></a></p>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * AJAX handler to reload screen collection list with completion checks
 */
function mha_reload_screen_collection_list() {
	$screens = isset($_POST['screens']) ? sanitize_text_field($_POST['screens']) : '';
	$screen_order = isset($_POST['screen_order']) ? filter_var($_POST['screen_order'], FILTER_VALIDATE_BOOLEAN) : false;
	$org_id = isset($_POST['org_id']) ? sanitize_text_field($_POST['org_id']) : '';
	$user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
	$referrer = isset($_POST['referrer']) ? sanitize_text_field($_POST['referrer']) : '';
	$iframe_mode = isset($_POST['iframe_mode']) ? sanitize_text_field($_POST['iframe_mode']) : 'false';
	$collection_id = isset( $_POST['collection_id'] ) ? absint( $_POST['collection_id'] ) : 0;
	if ( $collection_id && get_post_type( $collection_id ) !== 'screen-collection' ) {
		$collection_id = 0;
	}
	
	// Generate the shortcode output with user_id
	$shortcode = '[screen_collection_list screens="' . esc_attr($screens) . '" screen_order="' . ($screen_order ? 'true' : 'false') . '" org_id="' . esc_attr($org_id) . '" user_id="' . esc_attr($user_id) . '" referrer="' . esc_attr($referrer) . '" iframe_mode="' . esc_attr($iframe_mode) . '"' . ( $collection_id ? ' collection_id="' . esc_attr( (string) $collection_id ) . '"' : '' ) . ']';
	
	$html = do_shortcode($shortcode);
	
	// Get debug data from shortcode execution
	global $mha_screen_collection_debug;
	$debug_info = isset($mha_screen_collection_debug) ? $mha_screen_collection_debug : array();
	
	wp_send_json_success(array(
		'html' => $html,
		'debug' => $debug_info
	));
}
add_action('wp_ajax_reload_screen_collection_list', 'mha_reload_screen_collection_list');
add_action('wp_ajax_nopriv_reload_screen_collection_list', 'mha_reload_screen_collection_list');
