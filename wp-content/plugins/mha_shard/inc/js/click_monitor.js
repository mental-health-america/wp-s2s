jQuery(function ($) {
	
	// Track clicks on elements with class "dl-click"
	$(document).on('click', '.dl-click', function(e) {
		
		var $clickedElement = $(this);
		
		// Get click_id from href attribute
		var click_id = $clickedElement.attr('href') || '';
		
		// Find parent div with data-cta-title attribute
		var cta_id = '';
		$clickedElement.parents('div').each(function() {
			var dataCtaTitle = $(this).attr('data-cta-title');
			if (dataCtaTitle) {
				cta_id = dataCtaTitle;
				return false; // Break the loop once found
			}
		});
		
		// Get current page URL
		var url = window.location.href;
		
		// Capture all data attributes from the clicked element
		var dataAttributes = {};
		if ($clickedElement.length) {
			// Get all attributes
			var attrs = $clickedElement[0].attributes;
			for (var i = 0; i < attrs.length; i++) {
				var attr = attrs[i];
				// Check if it's a data attribute
				if (attr.name.indexOf('data-') === 0) {
					dataAttributes[attr.name] = attr.value;
				}
			}
		}
		
		// Send AJAX request to track the click
		$.ajax({
			type: "POST",
			url: do_mhaClickMonitor.ajaxurl,
			data: {
				action: 'track_click_monitor',
				click_id: click_id,
				cta_id: cta_id,
				url: url,
				data_attributes: JSON.stringify(dataAttributes)
			},
			success: function(response) {
				//console.log('Click tracked:', response);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				//console.error('Error tracking click:', thrownError);
			}
		});
		
	});
	
});
