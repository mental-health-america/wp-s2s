<?php
/**
 * Gravity Forms form editor performance fixes.
 *
 * Large screening forms (e.g. form 97) can lock wp_gf_entry_meta when GF synchronously
 * deletes entry values for removed fields. This module defers those deletes to batched
 * cron jobs and avoids other expensive editor save/load work.
 */

use Gravity_Forms\Gravity_Forms\Save_Form\Config\GF_Admin_Form_Save_Config;
use Gravity_Forms\Gravity_Forms\Save_Form\GF_Form_CRUD_Handler;
use Gravity_Forms\Gravity_Forms\Save_Form\GF_Save_Form_Service_Provider;

const MHA_GF_DEFERRED_DELETE_OPTION = 'mha_gf_deferred_field_delete_queue';
const MHA_GF_DEFERRED_DELETE_CRON   = 'mha_gf_process_deferred_field_deletes';
const MHA_GF_DELETE_BATCH_SIZE      = 500;

add_action( 'plugins_loaded', 'mha_gf_register_performance_hooks', 20 );

/**
 * Register hooks after Gravity Forms has loaded.
 */
function mha_gf_register_performance_hooks() {
	if ( ! class_exists( 'GFForms' ) ) {
		return;
	}

	add_action( 'admin_init', 'mha_gf_maybe_intercept_form_editor_post_save', 0 );
	add_action( 'admin_init', 'mha_gf_maybe_handle_form_editor_ajax', 1 );

	add_filter( 'gform_disable_custom_field_names_query', '__return_true' );
	add_filter( 'gform_disable_ajax_save', '__return_false', 999 );
	add_action( MHA_GF_DEFERRED_DELETE_CRON, 'mha_gf_process_deferred_field_deletes' );
}

/**
 * Strip deletedFields from classic (non-AJAX) form editor POST saves.
 *
 * The server log showed saves hitting admin.php?page=gf_edit_forms via gform_meta POST.
 */
function mha_gf_maybe_intercept_form_editor_post_save() {
	if ( wp_doing_ajax() || ! is_admin() ) {
		return;
	}

	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

	if ( 'gf_edit_forms' !== $page || $id <= 0 || rgempty( 'gform_meta' ) ) {
		return;
	}

	$save_form_helper = GFForms::get_service_container()->get( GF_Save_Form_Service_Provider::GF_SAVE_FROM_HELPER );
	if ( $save_form_helper && $save_form_helper->is_ajax_save_action() ) {
		return;
	}

	mha_gf_begin_performance_mode();

	$form_json = rgpost( 'gform_meta', false );
	$prepared  = mha_gf_prepare_form_save_json( $id, $form_json );

	if ( $prepared !== $form_json ) {
		$_POST['gform_meta'] = wp_slash( $prepared );
	}
}

/**
 * Handle GF form editor AJAX requests before GF's default handlers run.
 */
function mha_gf_maybe_handle_form_editor_ajax() {
	if ( ! wp_doing_ajax() || empty( $_REQUEST['action'] ) ) {
		return;
	}

	switch ( $_REQUEST['action'] ) {
		case 'gf_get_submitted_fields':
			mha_gf_short_circuit_submitted_fields();
			break;

		case 'form_editor_save_form':
			mha_gf_fast_form_editor_save();
			break;
	}
}

/**
 * Save-time optimizations only. Do not run on form editor page load — disabling the
 * DOM parser there prevents GF from injecting hook JS and breaks the field menu.
 */
function mha_gf_begin_performance_mode() {
	static $active = false;

	if ( $active ) {
		return;
	}

	$active = true;

	@ini_set( 'max_execution_time', '300' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

	add_filter( 'gform_disable_form_settings_sanitization', '__return_true', 999 );
	add_filter( 'pre_option_gform_enable_logging', 'mha_gf_force_logging_off', 999 );
	add_filter( 'gform_trim_input_value', '__return_false', 999 );

	if ( class_exists( 'GF_Field_Image_Choice' ) ) {
		remove_action( 'gform_after_save_form', array( 'GF_Field_Image_Choice', 'resize_images' ), 10 );
	}
}

/**
 * Force GF debug logging off during heavy admin operations.
 *
 * @return string
 */
function mha_gf_force_logging_off() {
	return '';
}

/**
 * Remove deletedFields from save payload and queue background entry-meta cleanup.
 *
 * @param int    $form_id   Form ID.
 * @param string $form_json Raw form JSON from the editor.
 * @return string JSON ready for GF_Form_CRUD_Handler::save().
 */
function mha_gf_prepare_form_save_json( $form_id, $form_json ) {
	$form_meta = json_decode( stripslashes( (string) $form_json ), true );

	if ( ! is_array( $form_meta ) ) {
		return $form_json;
	}

	$deleted_fields = rgar( $form_meta, 'deletedFields' );
	if ( empty( $deleted_fields ) || ! is_array( $deleted_fields ) ) {
		return $form_json;
	}

	mha_gf_queue_deferred_field_deletes( $form_id, $deleted_fields );
	unset( $form_meta['deletedFields'] );

	return json_encode( $form_meta, JSON_UNESCAPED_UNICODE );
}

/**
 * Queue entry-meta deletes so save does not run GF's synchronous DELETE.
 *
 * Even when a form has zero entries, GF still runs DELETE on save if a field was
 * removed from the editor. On a large wp_gf_entry_meta table (all forms combined)
 * that query can block waiting for row locks even when it deletes nothing.
 *
 * @param int   $form_id        Form ID.
 * @param array $deleted_fields Field IDs removed in the editor.
 */
function mha_gf_queue_deferred_field_deletes( $form_id, $deleted_fields ) {
	$form_id = absint( $form_id );
	if ( $form_id <= 0 || empty( $deleted_fields ) ) {
		return;
	}

	if ( class_exists( 'GFAPI' ) && 0 === (int) GFAPI::count_entries( $form_id ) ) {
		return;
	}

	$queue = get_option( MHA_GF_DEFERRED_DELETE_OPTION, array() );
	if ( ! is_array( $queue ) ) {
		$queue = array();
	}

	foreach ( $deleted_fields as $field_id ) {
		$field_id = absint( $field_id );
		if ( $field_id <= 0 ) {
			continue;
		}

		$queue[] = array(
			'form_id'  => $form_id,
			'field_id' => $field_id,
			'queued'   => time(),
		);
	}

	update_option( MHA_GF_DEFERRED_DELETE_OPTION, $queue, false );
	mha_gf_schedule_deferred_field_deletes();
}

/**
 * Schedule background processing for queued field value deletes.
 */
function mha_gf_schedule_deferred_field_deletes() {
	if ( wp_next_scheduled( MHA_GF_DEFERRED_DELETE_CRON ) ) {
		return;
	}

	wp_schedule_single_event( time() + 30, MHA_GF_DEFERRED_DELETE_CRON );
}

/**
 * Process one batch of deferred entry-meta deletes.
 */
function mha_gf_process_deferred_field_deletes() {
	if ( ! class_exists( 'GFFormsModel' ) ) {
		return;
	}

	$queue = get_option( MHA_GF_DEFERRED_DELETE_OPTION, array() );
	if ( empty( $queue ) || ! is_array( $queue ) ) {
		return;
	}

	$job = array_shift( $queue );
	update_option( MHA_GF_DEFERRED_DELETE_OPTION, $queue, false );

	$form_id  = absint( rgar( $job, 'form_id' ) );
	$field_id = absint( rgar( $job, 'field_id' ) );

	if ( $form_id > 0 && $field_id > 0 ) {
		$completed = mha_gf_batch_delete_field_values( $form_id, $field_id );

		if ( ! $completed ) {
			array_unshift( $queue, $job );
			update_option( MHA_GF_DEFERRED_DELETE_OPTION, $queue, false );
		}
	}

	if ( ! empty( $queue ) ) {
		wp_schedule_single_event( time() + 60, MHA_GF_DEFERRED_DELETE_CRON );
	}
}

/**
 * Delete entry meta for a field in small batches to avoid lock wait timeouts.
 *
 * @param int $form_id  Form ID.
 * @param int $field_id Field ID.
 * @return bool True when all matching rows are deleted.
 */
function mha_gf_batch_delete_field_values( $form_id, $field_id ) {
	global $wpdb;

	$entry_meta_table = GFFormsModel::get_entry_meta_table_name();
	$like_prefix      = $wpdb->esc_like( (string) $field_id . '.' ) . '%';

	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT id FROM {$entry_meta_table}
			WHERE form_id = %d
			AND ( meta_key = %s OR meta_key LIKE %s )
			LIMIT %d",
			$form_id,
			(string) $field_id,
			$like_prefix,
			MHA_GF_DELETE_BATCH_SIZE
		)
	);

	if ( empty( $ids ) ) {
		return true;
	}

	$id_list = implode( ',', array_map( 'absint', $ids ) );
	$wpdb->query( "DELETE FROM {$entry_meta_table} WHERE id IN ({$id_list})" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectQuery, WordPress.DB.DirectQuery.NoCaching

	return count( $ids ) < MHA_GF_DELETE_BATCH_SIZE;
}

/**
 * Skip the expensive DISTINCT query on entry meta when the form editor loads.
 */
function mha_gf_short_circuit_submitted_fields() {
	if ( ! class_exists( 'GFCommon' ) || ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
		return;
	}

	if ( ! wp_verify_nonce( rgpost( 'nonce' ), 'gf_get_submitted_fields' ) ) {
		return;
	}

	wp_send_json_success( array(
		'fields'  => array(),
		'form_id' => absint( rgpost( 'form_id' ) ),
	) );
}

/**
 * Save the form without re-rendering the entire editor markup afterward.
 */
function mha_gf_fast_form_editor_save() {
	if ( ! class_exists( 'GFCommon' ) || ! GFCommon::current_user_can_any( 'gravityforms_edit_forms' ) ) {
		return;
	}

	check_ajax_referer( 'form_editor_save_form' );

	$form_id   = absint( rgpost( 'form_id' ) );
	$form_json = mha_gf_get_save_form_json();

	if ( ! $form_id || '' === $form_json ) {
		wp_send_json_error( array(
			'error' => 'Missing form save payload.',
		), 400 );
	}

	mha_gf_begin_performance_mode();
	$form_json = mha_gf_prepare_form_save_json( $form_id, $form_json );

	$handler = GFForms::get_service_container()->get( GF_Save_Form_Service_Provider::GF_FORM_CRUD_HANDLER );
	$result  = $handler->save( $form_id, $form_json );

	if ( rgar( $result, 'status' ) === GF_Form_CRUD_Handler::STATUS_SUCCESS ) {
		unset( $result['actions_markup'], $result['is_new'] );
		$result['updated_markup'] = '';

		wp_send_json_success( mha_gf_wrap_form_save_response( $result ) );
	}

	wp_send_json_error( mha_gf_wrap_form_save_error_response( $result ) );
}

/**
 * Read the form JSON payload from the AJAX request.
 *
 * @return string
 */
function mha_gf_get_save_form_json() {
	$form_json = rgpost( 'data' );

	if ( is_string( $form_json ) && '' !== $form_json ) {
		return $form_json;
	}

	$form_json = rgpost( 'gform_meta', false );
	if ( is_string( $form_json ) && '' !== $form_json ) {
		return $form_json;
	}

	if ( isset( $_POST['form'] ) && is_string( $_POST['form'] ) ) {
		return wp_unslash( $_POST['form'] );
	}

	return '';
}

/**
 * Wrap a successful save response the way Gravity Forms expects.
 *
 * @param array $result Save handler result.
 * @return array
 */
function mha_gf_wrap_form_save_response( $result ) {
	return array_merge(
		array( GF_Admin_Form_Save_Config::JSON_START_STRING => 0 ),
		$result,
		array( GF_Admin_Form_Save_Config::JSON_END_STRING => 1 )
	);
}

/**
 * Wrap a failed save response the way Gravity Forms expects.
 *
 * @param array $result Save handler result.
 * @return array
 */
function mha_gf_wrap_form_save_error_response( $result ) {
	$status = rgar( $result, 'status', GF_Form_CRUD_Handler::STATUS_FAILURE );

	if ( $status === GF_Form_CRUD_Handler::STATUS_DUPLICATE_TITLE ) {
		$result['error'] = esc_html__( 'Please enter a unique form title, this title is used for an existing form.', 'gravityforms' );
	} elseif ( $status === 0 || ! is_numeric( $status ) ) {
		$result['error'] = sprintf(
			/* Translators: 1. Opening link tag, 2. Closing link tag. */
			esc_html__( 'There was an error while saving your form. Please %1$scontact our support team%2$s.', 'gravityforms' ),
			'<a target="_blank" href="' . esc_attr( GFCommon::get_support_url() ) . '">',
			'<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'gravityforms' ) . '</span>&nbsp;<span class="gform-icon gform-icon--external-link" aria-hidden="true"></span></a>'
		);
	}

	return mha_gf_wrap_form_save_response( $result );
}
