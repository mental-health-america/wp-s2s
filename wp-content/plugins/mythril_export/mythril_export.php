<?php
/**
 * Plugin Name: Mythril - Signup Export
 * Plugin URI: https://
 * Version: 1.0
 * Author: Mythril Web Team
 * Author URI: https://
 * Description: A companion plugin for Mythril - Signup to easily export CSVs of user signups.
 */

add_action( 'admin_menu', 'export_menu' );
function export_menu() {
	add_menu_page(
		'Signup Export', 
		'Signup Export', 
		'edit_posts', 
		'mythrilexport', 
		'mythrilexport', 
		'dashicons-list-view', 
		26
	);

	add_submenu_page(
		null, // Don't display menu
		'Export Action',
		'Export',
		'edit_posts',
		'mythrilexport_export',
		'mythrilexport_export'
	);
}

define('ROOTDIR', plugin_dir_path(__FILE__));
require_once(ROOTDIR . 'list.php');
require_once(ROOTDIR . 'export.php');