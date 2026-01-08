<?php

/**
 * Enqueue click monitor script
 */
add_action('init', 'mhaClickMonitorScripts');
function mhaClickMonitorScripts() {
	wp_enqueue_script('mha_click_monitor_script', plugin_dir_url( __FILE__ ).'js/click_monitor.js', array('jquery'), time(), true);
	wp_localize_script('mha_click_monitor_script', 'do_mhaClickMonitor', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

/**
 * AJAX handler to track clicks and insert into database
 */
function track_click_monitor() {
	
	// Sanitize and get the data
	$click_id = isset($_POST['click_id']) ? sanitize_text_field($_POST['click_id']) : '';
	$cta_id = isset($_POST['cta_id']) ? sanitize_text_field($_POST['cta_id']) : '';
	$url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
	
	// Get ipiden using the get_ipiden() function
	$ipiden = get_ipiden();
	
	// Get the current date/time
	$date = current_time('mysql');
	
	// Insert into database
	global $wpdb;
	$table_name = 'mha_click_monitor';
	
	$insert_data = array(
		'date' => $date,
		'ipiden' => $ipiden,
		'click_id' => $click_id,
		'cta' => $cta_id,
		'url' => $url
	);
	
	$result = $wpdb->insert($table_name, $insert_data);
	
	// Return response
	if ($result !== false) {
		wp_send_json_success(array(
			'message' => 'Click tracked successfully',
			'insert_id' => $wpdb->insert_id
		));
	} else {
		wp_send_json_error(array(
			'message' => 'Failed to track click',
			'error' => $wpdb->last_error
		));
	}
	
}

// Register AJAX handlers for both logged-in and non-logged-in users
add_action('wp_ajax_track_click_monitor', 'track_click_monitor');
add_action('wp_ajax_nopriv_track_click_monitor', 'track_click_monitor');
