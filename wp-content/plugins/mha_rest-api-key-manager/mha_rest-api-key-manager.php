<?php
/**
 * Plugin Name: MHA - REST API Key Manager
 * Description: Adds a basic interface to manage API keys for access to custom REST endpoints. Customized fork of rest-api-key-authentication
 * Version: 1.0.0
 * Author: MHA Screening Web Team
 * Author URI: http://mhascreening.org
 * Text Domain: mha-rest-api-key-manager
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'MRAKM_VERSION', '1.0.0' );

class MHA_Rest_API_Key_Manager {

	/**
	 * Initialize hooks
	 */
	public function __construct() {
		// Add admin menu.
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
	}

	/**
	 * Register admin menu
	 */
	public function register_admin_menu() {
		add_menu_page(
			__( 'API Key Authentication', 'mha-rest-api-key-manager' ),
			__( 'API Keys', 'mha-rest-api-key-manager' ),
			'manage_options',
			'wp-api-key-auth',
			array( $this, 'render_admin_page' ),
			'dashicons-admin-network'
		);

        // Enqueue styles and scripts.
	    add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Render admin page
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Include admin page logic.
		require_once plugin_dir_path( __FILE__ ) . 'admin/admin-page.php';
	}

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets( $hook ) {
        if ( $hook !== 'toplevel_page_wp-api-key-auth' ) {
            return;
        }

        wp_enqueue_style(
            'wp-api-key-auth-admin-style',
            plugin_dir_url( __FILE__ ) . 'css/admin-style.css',
            array(),
            MRAKM_VERSION
        );

        wp_enqueue_script(
            'wp-api-key-auth-admin-script',
            plugin_dir_url( __FILE__ ) . 'js/admin-script.js',
            array(),
            MRAKM_VERSION,
            true
        );
    }

}

// Initialize the plugin.
new MHA_Rest_API_Key_Manager();
