<?php

/**
 * Enqueue click monitor script
 */
add_action('init', 'mhaClickMonitorScripts');
function mhaClickMonitorScripts() {
	wp_enqueue_script('mha_click_monitor_script', plugin_dir_url( __FILE__ ).'js/click_monitor.js', array('jquery'), '1.0.1', true);
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
	$data_attributes_json = isset($_POST['data_attributes']) ? $_POST['data_attributes'] : '';
	
	// Validate and sanitize JSON data attributes (exclude standard Bootstrap attributes)
	$data_attributes = '';
	if (!empty($data_attributes_json)) {
		$decoded = json_decode(stripslashes($data_attributes_json), true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
			$bootstrap_data_attrs = array(
				'data-toggle', 'data-target', 'data-dismiss', 'data-backdrop', 'data-keyboard',
				'data-show', 'data-hide', 'data-offset', 'data-flip', 'data-boundary',
				'data-reference', 'data-display', 'data-placement', 'data-trigger', 'data-html',
				'data-animation', 'data-container', 'data-delay', 'data-selector', 'data-parent',
				'data-slide', 'data-slide-to', 'data-ride', 'data-interval', 'data-pause',
				'data-wrap', 'data-focus', 'data-original-title', 'data-original-text',
			);
			$sanitized_attrs = array();
			foreach ($decoded as $key => $value) {
				$sanitized_key = sanitize_key($key);
				// Skip Bootstrap 4/5 standard attributes
				if (in_array($sanitized_key, $bootstrap_data_attrs, true)) {
					continue;
				}
				// Skip Bootstrap 5 data-bs-* attributes
				if (strpos($sanitized_key, 'data-bs-') === 0) {
					continue;
				}
				$sanitized_value = sanitize_text_field($value);
				$sanitized_attrs[$sanitized_key] = $sanitized_value;
			}
			$data_attributes = json_encode($sanitized_attrs);
		}
	}
	
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
		'url' => $url,
		'data_attributes' => $data_attributes
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
