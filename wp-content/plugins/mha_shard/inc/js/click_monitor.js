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
		
		// Send AJAX request to track the click
		$.ajax({
			type: "POST",
			url: do_mhaClickMonitor.ajaxurl,
			data: {
				action: 'track_click_monitor',
				click_id: click_id,
				cta_id: cta_id,
				url: url
			},
			success: function(response) {
				// Optional: Log success or handle response
				if (typeof console !== 'undefined' && console.log) {
					console.log('Click tracked:', response);
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				// Optional: Log error
				if (typeof console !== 'undefined' && console.error) {
					console.error('Error tracking click:', thrownError);
				}
			}
		});
		
	});
	
});
