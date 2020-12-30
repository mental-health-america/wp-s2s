jQuery(function ($) {

	function submitFilterForm( order, orderby ){

		// Disable default form submit
		event.preventDefault();

		// Prep the data
		var args = $('.search-filters').serialize();
		if(orderby !=  '' && order !=  ''){
			args = args+'&order='+order+'&orderby='+orderby;
		}

		// Disable things while search
		$('#filters-content').addClass('loading');			
		$('#filters-content button, #filters-content input', $(this)).prop('disabled', true);

		console.log(args);

		$.ajax({
			type: "POST",
			url: do_mhaContent.ajaxurl,
			data: { 
				action: 'getArticlesAjax',
				data: args
			},
			success: function( results ) {
				
				$('#filters-content').removeClass('loading').html( results );	
				$('#filters-content button, #filters-content input', $(this)).prop('disabled', false);
			},
			error: function(xhr, ajaxOptions, thrownError){
				
				$('#filters-content').removeClass('loading');
				console.error(xhr,thrownError);
				$('#filters-content button, #filters-content input', $(this)).prop('disabled', false);

			}
		});	

	}

	$('.search-filters').on('submit', function(event){
		// Get selected ordering
		var orderby = $('#orderSelection').val(),
			order = $('#orderSelection').attr('data-order');

		// Submit
		submitFilterForm(order, orderby);		
	});

	$('.filter-order').on('click', function(event){
		// Set up vars
		var text = $(this).text(),
			orderby = $(this).val(),
			order = $(this).attr('data-order');
			
		// Update current selection
		$('#orderSelection').text(text).attr('data-order', order).val(orderby);

		// Submit
		submitFilterForm( order, orderby );		
	});

});