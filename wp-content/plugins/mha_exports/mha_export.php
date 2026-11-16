<?php
/**
 * Plugin Name: MHA - Exports
 * Plugin URI: https://screening.mhanational.org
 * Version: 1.0
 * Author: MHA Web Team
 * Author URI: https://screening.mhanational.org
 * Description: A custom MHA tool for exporting data.
 */

add_action( 'admin_menu', 'export_menu' );
function export_menu() {
	add_menu_page(
		'Data Exports', 
		'Data Exports', 
		'manage_options', 
		'mhathoughtexport', 
		'mhathoughtexport', 
		'dashicons-list-view', 
		26
	);
	add_menu_page(
		'Flagged Thoughts', 
		'Flagged Thoughts', 
		'manage_options', 
		'mhaflaggedthoughtmod', 
		'mhaflaggedthoughtmod', 
		'dashicons-list-view', 
		26
	);
	add_menu_page(
		'Update User Screen Results', 
		'Update User Screen Results', 
		'manage_options', 
		'mhaUpdateResults', 
		'mhaUpdateResults', 
		'dashicons-list-view', 
		26
	);

}

// Quick and dirty, but does the trick.
use GFExcel\GFExcel;
use GFExcel\GFExcelOutput;
use GFExcel\Renderer\PHPExcelMultisheetRenderer;

add_action('request', function ($query_vars) {

    // only respond to a plugin call
    if (!array_key_exists(GFExcel::KEY_ACTION, $query_vars) ||
        !array_key_exists(GFExcel::KEY_HASH, $query_vars) ||
        $query_vars[GFExcel::KEY_ACTION] !== GFExcel::$slug) {
        return $query_vars;
    }

    // Set the URL for our custom export
    $secret = 'all_screen_export';
    if ($query_vars[GFExcel::KEY_HASH] !== $secret || !GFCommon::current_user_can_any('gravityforms_create_form')) {
        return $query_vars;
    }
    
    // instantiate multi sheet renderer and push all forms to it.    
    $renderer = new PHPExcelMultisheetRenderer();

    // Only get specific screening forms
    $screening_forms = [];
    $screening_export = parse_url($_SERVER["REQUEST_URI"]);
    parse_str($screening_export['query'], $export_query);
    $screening_forms[] = $export_query['form_id'];
    //$screening_forms = array(15,8,10,1,13,12,5,18,17,9,11,16,14);

    foreach (GFFormsModel::get_form_ids() as $form_id) {
        if(in_array($form_id, $screening_forms)){
            $output = new GFExcelOutput((int) $form_id, $renderer);
            $output->render();
        }
    }

    // start the rendering and the download.
    $renderer->renderOutput();

    exit;

}, 9);


/**
 * Authorization guard for the export AJAX endpoints.
 *
 * A `wp_ajax_` hook only requires the request to be authenticated, not authorized, so
 * every callback has to check capability itself. The nonce is always required. The paging
 * loops in js/ send it on every continuation request (either inside the serialized `data`
 * string or as `data[nonce]` when the previous response is posted back).
 *
 * Responses are echoed as plain JSON rather than through wp_send_json_*(), because the
 * scripts in js/ run JSON.parse() on the raw response text.
 *
 * Sends an error and exits when the request is not permitted.
 */
function mha_exports_verify_ajax_request( $nonce_action = null ) {

	if ( ! current_user_can( 'manage_options' ) ) {
		status_header( 403 );
		echo json_encode( array( 'error' => 'You do not have permission to run this export.' ) );
		exit();
	}

	if ( ! $nonce_action ) {
		return;
	}

	// The form data arrives either serialized (first pass) or as an array (continuations).
	$nonce = null;
	if ( isset( $_POST['data'] ) ) {
		if ( is_array( $_POST['data'] ) ) {
			$nonce = isset( $_POST['data']['nonce'] ) ? $_POST['data']['nonce'] : null;
		} else {
			parse_str( (string) $_POST['data'], $parsed_data );
			$nonce = isset( $parsed_data['nonce'] ) ? $parsed_data['nonce'] : null;
		}
	}

	if ( ! is_string( $nonce ) || '' === $nonce || ! wp_verify_nonce( $nonce, $nonce_action ) ) {
		echo json_encode( array( 'error' => 'Security check failed. Refresh the page and try again.' ) );
		exit();
	}

	// Housekeeping: drop stale export files whenever an export is being used.
	mha_exports_cleanup_tmp();
}


/**
 * Export files
 *
 * WP Engine offers no non-public folder, so exports are written to this plugin's tmp/ folder and
 * downloaded from there directly. Protection comes from three things:
 *  - names that stay readable (what was exported, and the date range) but end in a long random
 *    token instead of a guessable timestamp,
 *  - strict validation of any file name the browser sends back during paging, so it can never
 *    point anywhere except a CSV with a name this plugin generated, inside tmp/,
 *  - automatic deletion of files older than two hours.
 */
define( 'MHA_EXPORTS_TMP_MAX_AGE', 2 * HOUR_IN_SECONDS );
define( 'MHA_EXPORTS_TOKEN_LENGTH', 20 );

function mha_exports_tmp_dir() {
	return plugin_dir_path( __FILE__ ) . 'tmp/';
}

/**
 * Build a new export file name: `<what>--<date range>--<random token>.csv`
 *
 * @param string $label Readable description of the export (form title, tool name, ...).
 * @param string $range Optional date range or qualifier, e.g. '2026-08-01_2026-09-30'.
 * @return string
 */
function mha_exports_new_filename( $label, $range = '' ) {

	$label = strtolower( preg_replace( '/[^A-Za-z0-9_-]+/', '-', sanitize_title( (string) $label ) ) );
	$label = trim( substr( $label, 0, 80 ), '-' );
	if ( '' === $label ) {
		$label = 'export';
	}

	$range = preg_replace( '/[^A-Za-z0-9_-]+/', '', (string) $range );
	$token = wp_generate_password( MHA_EXPORTS_TOKEN_LENGTH, false, false );

	return $label . '--' . ( '' !== $range ? $range . '--' : '' ) . $token . '.csv';
}

/**
 * Validate a file name received from the browser during paging.
 *
 * @param mixed $filename Value from the request.
 * @return string|false The file name when it is one this plugin could have generated, otherwise false.
 */
function mha_exports_resolve_filename( $filename ) {

	if ( ! is_string( $filename ) || '' === $filename ) {
		return false;
	}

	$filename = basename( $filename );

	$pattern = '/^[A-Za-z0-9_-]+--(?:[A-Za-z0-9_-]+--)?[A-Za-z0-9]{' . MHA_EXPORTS_TOKEN_LENGTH . '}\.csv$/';
	if ( ! preg_match( $pattern, $filename ) ) {
		return false;
	}

	return $filename;
}

/**
 * Same as mha_exports_resolve_filename(), but stops the request when the name is not valid.
 *
 * @param mixed $filename Value from the request.
 * @return string
 */
function mha_exports_require_filename( $filename ) {

	$valid = mha_exports_resolve_filename( $filename );

	if ( false === $valid ) {
		status_header( 400 );
		echo json_encode( array( 'error' => 'Invalid export file name. Please refresh this page and start the export again.' ) );
		exit();
	}

	return $valid;
}

/**
 * Delete export files older than the maximum age.
 *
 * Only *.csv files are removed, so the index.php guard in tmp/ is never touched. A file that is
 * still being written to has a recent modification time and is left alone.
 *
 * @param int $max_age Age in seconds.
 * @return int Number of files deleted.
 */
function mha_exports_cleanup_tmp( $max_age = MHA_EXPORTS_TMP_MAX_AGE ) {

	$files = glob( mha_exports_tmp_dir() . '*.csv' );
	if ( ! $files ) {
		return 0;
	}

	$cutoff  = time() - (int) $max_age;
	$deleted = 0;

	foreach ( $files as $file ) {
		if ( is_file( $file ) && filemtime( $file ) < $cutoff && @unlink( $file ) ) {
			$deleted++;
		}
	}

	return $deleted;
}

// Scheduled cleanup, so files are removed even when nobody is running an export.
add_filter( 'cron_schedules', function ( $schedules ) {
	$schedules['mha_exports_half_hour'] = array(
		'interval' => 30 * MINUTE_IN_SECONDS,
		'display'  => 'Every 30 minutes (MHA exports cleanup)',
	);
	return $schedules;
} );

add_action( 'mha_exports_cleanup_tmp_event', 'mha_exports_cleanup_tmp' );

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'mha_exports_cleanup_tmp_event' ) ) {
		wp_schedule_event( time() + 300, 'mha_exports_half_hour', 'mha_exports_cleanup_tmp_event' );
	}
} );

register_deactivation_hook( __FILE__, function () {
	wp_clear_scheduled_hook( 'mha_exports_cleanup_tmp_event' );
} );


/**
 * Additional requirements
 */

define('ROOTDIR', plugin_dir_path(__FILE__));
require_once(ROOTDIR . 'inc/entries-export.php');
require_once(ROOTDIR . 'inc/diy-export.php');
require_once(ROOTDIR . 'inc/ab-testing.php');
require_once(ROOTDIR . 'inc/cta-code-export.php');
require_once(ROOTDIR . 'inc/callrail-export.php');
require_once(ROOTDIR . 'inc/feedback-export.php');
require_once(ROOTDIR . 'inc/click-monitor-export.php');

require_once(ROOTDIR . 'page-export.php');
require_once(ROOTDIR . 'page-update_results.php');
require_once(ROOTDIR . 'inc/flag-moderation.php');