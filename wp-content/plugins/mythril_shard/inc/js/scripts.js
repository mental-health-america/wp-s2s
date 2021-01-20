jQuery(function ($) {

	// Main Search Filter Search
	function submitFilterForm( order, orderby, append = '', page = 1 ){

		// Prep the data
		var args = $('.search-filters').serialize();
		if(orderby !=  '' && order !=  ''){
			args = args+'&order='+order+'&orderby='+orderby+append;
		}
		if(append === 1){
			args = args+'&append=1';
		}
		if(page > 1){
			args = args+'&paged='+page;
		}

		// Disable things while search
		$('#filters-content').addClass('loading');	
		$('#filters-content').addClass('loading');			
		$('#filters-content button, #filters-content input', $(this)).prop('disabled', true);

		$.ajax({
			type: "POST",
			url: do_mhaContent.ajaxurl,
			data: { 
				action: 'getArticlesAjax',
				data: args
			},
			success: function( results ) {
				
				// Remove previous pager
				$('#filters-content .navigation').remove();

				// Append or replace
				if(append === 1){
					$('#filters-content').removeClass('loading').append( results );	
				} else {
					$('#filters-content').removeClass('loading').html( results );	
				}

				// Make filters usable again
				$('#filters-content button, #filters-content input', $(this)).prop('disabled', false);

			},
			error: function(xhr, ajaxOptions, thrownError){				
				$('#filters-content').removeClass('loading');
				$('#filters-content button, #filters-content input', $(this)).prop('disabled', false);
			}
		});	

	}

	// Submit filter on checkbox changes
	var intentDelay;
	$('.search-filters input[type="text"], .search-filters input[type="number"]').on('input',function(event){
		
		var $this = $(this);
		$this.addClass('searching');
		
		clearTimeout(intentDelay);

		intentDelay = setTimeout(function() {

			$this.prop('disabled',true);

			var orderby = $('#orderSelection').val(),
				order = $('#orderSelection').attr('data-order');
			submitFilterForm( order, orderby );	
			$this.addClass('disabled').removeClass('searching');
				
			setTimeout(function() {
				$this.prop('disabled',false).removeClass('disabled');
			}, 2000);
			
		}, 1000);

	});
	
	// Submit filter on checkbox changes
	$('.filter-checkboxes input[type="checkbox"]').change(function(event) {
		event.preventDefault();
		var orderby = $('#orderSelection').val(),
		order = $('#orderSelection').attr('data-order');
		submitFilterForm( order, orderby );	
	});

	// Submit filter search
	$('.search-filters').on('submit', function(event){
		event.preventDefault();
		// Get selected ordering
		var orderby = $('#orderSelection').val(),
			order = $('#orderSelection').attr('data-order');
		submitFilterForm(order, orderby );		
	});

	// Change order
	$('.filter-order').on('click', function(event){
		event.preventDefault();
		// Set up vars
		var text = $(this).text(),
			orderby = $(this).val(),
			order = $(this).attr('data-order');			
		$('#orderSelection').text(text).attr('data-order', order).val(orderby);
		submitFilterForm( order, orderby );		
	});

	// Load More Ajax
	$(document).on('click', '.load-more-articles', function(event){
		event.preventDefault();
		
		// Disable and show loading state
		$(this).prop('disabled',true).addClass('loading');	

		// Set up vars
		var page = $(this).attr('data-paged'),
			orderby = $(this).val(),
			order = $(this).attr('data-order');		

		// Submit
		submitFilterForm( order, orderby, 1, page );		
	});

});