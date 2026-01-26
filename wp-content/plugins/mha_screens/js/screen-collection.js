jQuery(function ($) {
	
	/**
	 * Update Gravity Forms hidden field with value "scd_blank"
	 * Replace it with localStorage values in format: org_id&&user_id
	 * Only runs when "org" URL parameter is present
	 */
	function updateGravityFormsScreenCollectionField() {
		// Check if "org" URL parameter is present
		var urlParams = new URLSearchParams(window.location.search);
		if (!urlParams.has('org')) {
			return; // Exit if "org" parameter is not in URL
		}
		
		var org_id = localStorage.getItem('mha_screen_collection_org_id') || '';
		var user_id = localStorage.getItem('mha_screen_collection_user_id') || '';
		
		// Only proceed if we have values
		if (!org_id && !user_id) {
			return;
		}
		
		// Find all Gravity Forms on the page
		var forms = document.querySelectorAll('form[id^="gform_"]');
		
		if (forms.length === 0) {
			return;
		}
		
		forms.forEach(function(form) {
			// Find all hidden input fields
			var hiddenInputs = form.querySelectorAll('input[type="hidden"]');
			
			hiddenInputs.forEach(function(input) {
				var currentValue = input.value || '';
				
				// Check if this field has the value "scd_blank"
				if (currentValue === 'scd_blank') {
					// Build the new value in format: org_id&&user_id
					var newValue = org_id + '&&&' + user_id;
					
					// Update the value
					input.value = newValue;
					
					// Trigger change event for Gravity Forms
					var changeEvent = new Event('change', { bubbles: true });
					input.dispatchEvent(changeEvent);
					
					// Also trigger input event
					var inputEvent = new Event('input', { bubbles: true });
					input.dispatchEvent(inputEvent);
					
					// If jQuery is available, also trigger jQuery events
					if (typeof jQuery !== 'undefined') {
						jQuery(input).val(newValue).trigger('change');
						
						// Trigger Gravity Forms specific events
						var formId = form.id.replace('gform_', '');
						if (formId) {
							var inputId = input.id || input.name;
							if (inputId) {
								// Extract field ID from input name (e.g., "input_1_5" -> "1.5")
								var fieldIdMatch = inputId.match(/input_(\d+)(?:_(\d+))?/);
								if (fieldIdMatch) {
									var fieldId = fieldIdMatch[2] ? fieldIdMatch[1] + '.' + fieldIdMatch[2] : fieldIdMatch[1];
									jQuery(document).trigger('gform_input_change', [input, formId, fieldId]);
								}
							}
						}
					}
				}
			});
		});
	}
	
	// Update Gravity Forms fields on page load and when forms are rendered
	updateGravityFormsScreenCollectionField();
	$(document).on('gform_post_render', function(event, formId, currentPage) {
		updateGravityFormsScreenCollectionField();
	});
	
	// Hide spinner and show form when loaded
	$('.spinner-border').hide();
	$('.screen-collection-user').removeClass('d-none');
	
	// Check if the form exists on the page
	var $form = $('.screen-collection-user');
	if ($form.length === 0) {
		return; // Exit if form doesn't exist
	}
	
	// Check if user ID already exists in local storage
	var userId = localStorage.getItem('mha_screen_collection_user_id');
		
	if (userId) {
		// User ID exists, show message instead of form
		showUserIdMessage(userId);
	} else {
		// No user ID, handle form submission
		handleFormSubmission();
	}
	
	/**
	 * Handle form submission
	 */
	function handleFormSubmission() {
		$form.on('submit', function(e) {
			e.preventDefault();
			
			var $userInput = $(this).find('input[name="user_id"]');
			var user_id = $userInput.val().trim();
			
			// Get org, org_id, screen_ids, and screen_collection from hidden inputs
			var $orgInput = $(this).find('input[name="org"]');
			var $orgIdInput = $(this).find('input[name="org_id"]');
			var $screenIdsInput = $(this).find('input[name="screen_ids"]');
			var $screenCollectionInput = $(this).find('input[name="screen_collection"]');
			var org = $orgInput.length > 0 ? $orgInput.val() : '';
			var org_id = $orgIdInput.length > 0 ? $orgIdInput.val() : '';
			var screen_ids = $screenIdsInput.length > 0 ? $screenIdsInput.val() : '';
			var screen_collection = $screenCollectionInput.length > 0 ? $screenCollectionInput.val() : '';
			
			if (user_id) {
				// Save to local storage
				localStorage.setItem('mha_screen_collection_user_id', user_id);
				if (org) {
					localStorage.setItem('mha_screen_collection_org', org);
				}
				if (org_id) {
					localStorage.setItem('mha_screen_collection_org_id', org_id);
				}
				if (screen_ids) {
					localStorage.setItem('mha_screen_collection_screen_ids', screen_ids);
				}
				if (screen_collection) {
					localStorage.setItem('mha_screen_collection', screen_collection);
				}
				
				// Get form reference before replacing
				var $formToReplace = $('.screen-collection-user');
				
				// Show the user ID message
				showUserIdMessage(user_id, $formToReplace);
			}
		});
	}
	
	/**
	 * Show user ID message and replace form
	 */
	function showUserIdMessage(userId, $formElement) {
		// Use provided form element or find it
		var $formToReplace = $formElement || $('.screen-collection-user');
		
		if ($formToReplace.length === 0) {
			return; // Form doesn't exist
		}
		
		// Create message HTML
		var messageHtml = '<div class="screen-collection-user-message">' +
			'<p><strong>Current User ID:</strong> ' + escapeHtml(userId) + ' <a href="#" class="screen-collection-reset small" data-reset="true">reset</a></p>' +
			'</div>';

        $('#screenings-list').removeClass('d-none');
        $('#screenings-list').attr('aria-hidden', 'false');
		
		// Replace form with message
		$formToReplace.replaceWith(messageHtml);
		
		// Reload screenings list with user_id to check completion in PHP
		var screenOrderEnabled = $('#screenings-list').attr('data-screen-order') === 'true';
		if (screenOrderEnabled) {
			reloadScreeningsListWithUserId(userId);
		}
	}
	
	/**
	 * Reload screenings list with user_id to check completion status
	 * This is needed because user_id is in localStorage (client-side) but PHP needs it server-side
	 */
	function reloadScreeningsListWithUserId(userId) {
		// Get org_id from localStorage
		var orgId = localStorage.getItem('mha_screen_collection_org_id') || '';
		
		// Get current URL parameters
		var urlParams = new URLSearchParams(window.location.search);
		var screensParam = urlParams.get('screens') || '';
		var screenOrderParam = $('#screenings-list').attr('data-screen-order') || 'false';
		var referrerParam = urlParams.get('ref') || '';
		var iframeModeParam = urlParams.get('iframe') || 'false';
		
		// Get screen IDs from existing list
		var screenIds = [];
		$('#screenings-list .screen-item').each(function() {
			var screenId = $(this).attr('data-screen-id');
			if (screenId) {
				screenIds.push(screenId);
			}
		});
		
		if (screenIds.length === 0) {
			return;
		}
		
		// Make AJAX call to reload the shortcode with user_id
		$.ajax({
			type: 'POST',
			url: mhaScreenCollection.ajaxurl,
			data: {
				action: 'reload_screen_collection_list',
				screens: screenIds.join(','),
				screen_order: screenOrderParam,
				org_id: orgId,
				user_id: userId,
				referrer: referrerParam,
				iframe_mode: iframeModeParam
			},
			success: function(response) {
				// Debug logging for Gravity Forms data
				console.log('=== Screen Collection GFAPI Debug ===');
				console.log('Full AJAX Response:', response);
				
				if (response.success) {
					if (response.data && response.data.debug) {
						console.log('GFAPI Debug Data:', response.data.debug);
						
						// Log details for each screen
						if (typeof response.data.debug === 'object') {
							for (var screenId in response.data.debug) {
								if (response.data.debug.hasOwnProperty(screenId)) {
									var screenDebug = response.data.debug[screenId];
									console.log('Screen ID ' + screenId + ':', screenDebug);
									
									if (screenDebug.search_criteria) {
										console.log('  Search Criteria:', screenDebug.search_criteria);
									}
									if (screenDebug.entries_found !== undefined) {
										console.log('  Entries Found:', screenDebug.entries_found);
									}
									if (screenDebug.entries && screenDebug.entries.length > 0) {
										console.log('  Entry Details:', screenDebug.entries);
										screenDebug.entries.forEach(function(entry, index) {
											console.log('    Entry ' + (index + 1) + ':', entry);
										});
									}
									if (screenDebug.error) {
										console.error('  Error:', screenDebug.error);
									}
									if (screenDebug.screen_collection_value) {
										console.log('  Looking for value:', screenDebug.screen_collection_value);
									}
									if (screenDebug.form_id) {
										console.log('  Form ID:', screenDebug.form_id);
									}
									if (screenDebug.screen_collection_field_id) {
										console.log('  Screen Collection Field ID:', screenDebug.screen_collection_field_id);
									}
								}
							}
						}
					} else {
						console.log('No debug data available in response');
					}
					
					if (response.data.html) {
						// Replace the screenings list with the updated version
						var $newList = $(response.data.html);
						$('#screenings-list').replaceWith($newList);
						// Ensure it's visible after reload
						$newList.removeClass('d-none');
						$newList.attr('aria-hidden', 'false');
					}
				} else {
					console.error('AJAX returned success:false', response);
				}
				console.log('=== End GFAPI Debug ===');
			},
			error: function(xhr, status, error) {
				console.error('=== Screen Collection AJAX Error ===');
				console.error('Status:', status);
				console.error('Error:', error);
				console.error('Response Text:', xhr.responseText);
				console.error('=== End AJAX Error ===');
			}
		});
	}
	
	// Handle reset link click (use event delegation so it works for dynamically added elements)
	$(document).on('click', '.screen-collection-reset', function(e) {
		e.preventDefault();
		
		// Remove from local storage
		localStorage.removeItem('mha_screen_collection_user_id');
		localStorage.removeItem('mha_screen_collection_org');
		localStorage.removeItem('mha_screen_collection_org_id');
		localStorage.removeItem('mha_screen_collection_screen_ids');
		localStorage.removeItem('mha_screen_collection');
		
		// Reload the page to show the form again
		window.location.reload();
	});
	
	/**
	 * Escape HTML to prevent XSS
	 */
	function escapeHtml(text) {
		var map = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};
		return text.replace(/[&<>"']/g, function(m) { return map[m]; });
	}
	
	
});
