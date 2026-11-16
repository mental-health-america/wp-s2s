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

	// Open to anonymous visitors by design (click analytics), so cap how much any one IP can write.
	if ( function_exists( 'mha_rate_limit_allow' ) && ! mha_rate_limit_allow( 'click_monitor', mha_get_remote_ip(), 300, HOUR_IN_SECONDS ) ) {
		wp_send_json_error( array( 'message' => 'Too many requests' ), 429 );
	}
	
	// Sanitize and get the data (scalars only, bounded length)
	$click_id = ( isset($_POST['click_id']) && is_scalar($_POST['click_id']) ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['click_id'] ) ), 0, 500 ) : '';
	$cta_id = ( isset($_POST['cta_id']) && is_scalar($_POST['cta_id']) ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['cta_id'] ) ), 0, 255 ) : '';
	$url = ( isset($_POST['url']) && is_scalar($_POST['url']) ) ? mb_substr( esc_url_raw( wp_unslash( $_POST['url'] ) ), 0, 2000 ) : '';
	$data_attributes_json = ( isset($_POST['data_attributes']) && is_string($_POST['data_attributes']) && strlen($_POST['data_attributes']) <= 5000 ) ? $_POST['data_attributes'] : '';
	
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
			foreach (array_slice($decoded, 0, 30, true) as $key => $value) {
				if (!is_scalar($value)) {
					continue;
				}
				$sanitized_key = sanitize_key($key);
				// Skip Bootstrap 4/5 standard attributes
				if (in_array($sanitized_key, $bootstrap_data_attrs, true)) {
					continue;
				}
				// Skip Bootstrap 5 data-bs-* attributes
				if (strpos($sanitized_key, 'data-bs-') === 0) {
					continue;
				}
				$sanitized_value = mb_substr( sanitize_text_field($value), 0, 500 );
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
			'message' => 'Failed to track click'
		));
	}
	
}

// Register AJAX handlers for both logged-in and non-logged-in users
add_action('wp_ajax_track_click_monitor', 'track_click_monitor');
add_action('wp_ajax_nopriv_track_click_monitor', 'track_click_monitor');
