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
 * Additional requirements
 */

define('ROOTDIR', plugin_dir_path(__FILE__));
require_once(ROOTDIR . 'entries-export.php');
require_once(ROOTDIR . 'diy-export.php');
require_once(ROOTDIR . 'feedback-export.php');
require_once(ROOTDIR . 'page-export.php');
require_once(ROOTDIR . 'page-update_results.php');
require_once(ROOTDIR . 'flag-moderation.php');