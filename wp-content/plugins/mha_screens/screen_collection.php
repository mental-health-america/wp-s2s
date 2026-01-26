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
	
	// Find the form ID for this screen by parsing the post content for Gravity Forms shortcode
	$form_id = null;
	
	// Get the post content
	$post = get_post($screen_id);
	if (!$post) {
		if ($return_debug) {
			return array('completed' => false, 'debug' => array_merge($debug_info, array('error' => 'Screen post not found', 'screen_id' => $screen_id)));
		}
		return false;
	}
	
	// Get post content (also check if it's processed through the_content filter)
	$post_content = $post->post_content;
	
	// Apply shortcodes to get processed content in case form is embedded via template
	$processed_content = apply_filters('the_content', $post_content);
	
	// Look for Gravity Forms shortcode pattern: [gravityform id="X"] or [gravityform id='X'] or [gravityform id=X]
	// Try both original and processed content
	preg_match('/\[gravityform\s+id=["\']?(\d+)["\']?/i', $post_content, $matches);
	
	if (empty($matches[1]) && $processed_content) {
		preg_match('/\[gravityform\s+id=["\']?(\d+)["\']?/i', $processed_content, $matches);
	}
	
	if (!empty($matches[1])) {
		$form_id = intval($matches[1]);
	}
	
	if (!$form_id) {
		if ($return_debug) {
			return array('completed' => false, 'debug' => array_merge($debug_info, array(
				'error' => 'Gravity Forms shortcode not found in screen post content',
				'post_content_preview' => substr($post_content, 0, 200),
				'has_content' => !empty($post_content)
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
 * Usage: [screen_collection_list screens="1,2,3" screen_order="true" org_id="123" user_id="456" referrer="ref" iframe_mode="true"]
 */
add_shortcode('screen_collection_list', 'mha_screen_collection_list_shortcode');
function mha_screen_collection_list_shortcode($atts) {
	$atts = shortcode_atts(array(
		'screens' => '',
		'screen_order' => false,
		'org_id' => '',
		'user_id' => '',
		'referrer' => '',
		'iframe_mode' => 'false'
	), $atts);
	$screens = !empty($atts['screens']) ? explode(',', $atts['screens']) : array();
	$screen_order = filter_var($atts['screen_order'], FILTER_VALIDATE_BOOLEAN);
	$org_id = $atts['org_id'];
	$user_id = $atts['user_id'];
	$referrer = $atts['referrer'];
	$iframe_mode = $atts['iframe_mode'];
	
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
	<div id="screenings-list" class="m-0<?php echo $hide_class; ?>" aria-hidden="<?php echo $aria_hidden; ?>" aria-label="Screenings List" data-screen-order="<?php echo $screen_order ? 'true' : 'false'; ?>">
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
 * AJAX handler to reload screen collection list with completion checks
 */
function mha_reload_screen_collection_list() {
	$screens = isset($_POST['screens']) ? sanitize_text_field($_POST['screens']) : '';
	$screen_order = isset($_POST['screen_order']) ? filter_var($_POST['screen_order'], FILTER_VALIDATE_BOOLEAN) : false;
	$org_id = isset($_POST['org_id']) ? sanitize_text_field($_POST['org_id']) : '';
	$user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
	$referrer = isset($_POST['referrer']) ? sanitize_text_field($_POST['referrer']) : '';
	$iframe_mode = isset($_POST['iframe_mode']) ? sanitize_text_field($_POST['iframe_mode']) : 'false';
	
	// Generate the shortcode output with user_id
	$shortcode = '[screen_collection_list screens="' . esc_attr($screens) . '" screen_order="' . ($screen_order ? 'true' : 'false') . '" org_id="' . esc_attr($org_id) . '" user_id="' . esc_attr($user_id) . '" referrer="' . esc_attr($referrer) . '" iframe_mode="' . esc_attr($iframe_mode) . '"]';
	
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
