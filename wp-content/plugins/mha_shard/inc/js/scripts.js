jQuery(function ($) {

	// Disable wheel scrolling on number fields to prevent number changes
	$(document).on("wheel", "input[type=number]", function (e) {
		$(this).blur();
	});

	// Main Search Filter Search
	function submitFilterForm( order, orderby, append, page ){

		// IE doesn't like default parameters, so using this shorthand cheat instead
		var append = append || '';
		var page = page || 1;

		// Provider/Get Help Override
		if($('body').hasClass('page-template-page-providers')){
			// If zip has value, "uncheck" the national only checkbox
			if($('#zip-search').val()){
				$('#area-national').val('');
			} else {
				$('#area-national').val('national');
			}
		}

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
	//var intentDelay;
	$('#zip-search, #keyword-search').on('keyup', function (e) {
		
		if (e.key === 'Enter' || e.keyCode === 13) {
			var orderby = $('#orderSelection').val(),
				order = $('#orderSelection').attr('data-order');
			submitFilterForm( order, orderby );	
			return false;
		}

		/*
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
		*/

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


	// Show "All Conditions" checkbox
	if($('.show-all-conditions').length){
		var countChecked = function() {
			var checks = $(".show-all-conditions input").filter(":checked").length;
			
			if(checks >= 1){
				$("#all-conditions-container").collapse('show');
			} else {
				$("#all-conditions-container").collapse('hide');
			}        
		};
		countChecked();		 
		$(".show-all-conditions input").on("change", countChecked );
	}


	// Submit Article Form Display + Captcha Confirmation
	$('#article-submit-recaptcha-confirm').on('submit', function(event){

		event.preventDefault();		

		var recaptcha = $('#g-recaptcha-response').val();
		$('#article-submit-recaptcha-confirm .button').prop('disabled',true).addClass('loading');

		if(recaptcha.length == 0 ){
			$('#recaptcha-error .inner').html('We\'re sorry, but that response was invalid, please try again.');
			$('#recaptcha-error').removeClass('hidden');
			$('#article-submit-recaptcha-confirm .button').prop('disabled',false).removeClass('loading');
		} else {
			$('#recaptcha-error').addClass('hidden');
			$('#recaptcha-error .inner').html('');
			
			var args = $('#article-submit-recaptcha-confirm').serialize();

			$.ajax({
				type: "POST",
				url: do_mhaContent.ajaxurl,
				data: { 
					action: 'mha_submit_article_form_display',
					data: args
				},
				success: function( result ) {
					
					$('#article-submit-container').html(result);
					acf.do_action('append', $('#article-submit-container'));

				},
				error: function(xhr, ajaxOptions, thrownError){		
					$('#recaptcha-error .inner').html('We\'re sorry, but there was an error loading the form, please try again later.');
					$('#recaptcha-error').removeClass('hidden');
					$('#article-submit-recaptcha-confirm .button').prop('disabled',false).removeClass('loading');
				}
			});	
			
		}

	});

});