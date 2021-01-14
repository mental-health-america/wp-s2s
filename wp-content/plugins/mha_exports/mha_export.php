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
		'UCI Data Export', 
		'UCI Data Export', 
		'edit_posts', 
		'mhathoughtexport', 
		'mhathoughtexport', 
		'dashicons-list-view', 
		26
	);

}

define('ROOTDIR', plugin_dir_path(__FILE__));
require_once(ROOTDIR . 'page-thoughts.php');
require_once(ROOTDIR . 'export-thoughts.php');