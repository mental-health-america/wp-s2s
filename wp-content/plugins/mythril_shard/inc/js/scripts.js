jQuery(function ($) {

       $('#connect-filter').on('submit', function(event){

			// Disable default form submit
			event.preventDefault();

			// Prep the data
			var args = $('#connect-filter').serialize();

			// Disable things while search
			$('#filters-content').addClass('loading');			
			$('#filters-content button, #filters-content input', $(this)).prop('disabled', true);

			$.ajax({
				type: "POST",
				url: do_mhaContent.ajaxurl,
				data: { 
					action: 'connectArticlesAjax',
					data: args
				},
				success: function( results ) {
					
					console.log(results);

					$('#filters-content').removeClass('loading').html( results );	
					$('#filters-content button, #filters-content input', $(this)).prop('disabled', false);
				},
				error: function(xhr, ajaxOptions, thrownError){
					
					$('#filters-content').removeClass('loading');
					console.error(xhr,thrownError);
					$('#filters-content button, #filters-content input', $(this)).prop('disabled', false);

				}
            });	
            
       });

});