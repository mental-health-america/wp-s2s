jQuery(function ($) {


	// Initial sort the other thoughts
	function sortThoughts(){
		if($('#thoughts-submitted').length){
			$("#thoughts-submitted li").sort(sort_li).appendTo('#thoughts-submitted');
			function sort_li(a, b) {
				return ($(a).data('count')) < ($(b).data('count')) ? 1 : -1;
			}
		}
	}
	sortThoughts();

	
	// Thought Form Actions
	if($('#form-activity').length){

		/**
		 * Start with an initial thought and begin saving the activity
		 */
		$('.submit-initial-thought.self-thought').on('click', function(event){

			// Disable default form submit
			event.preventDefault();

			// Disable submit
			$('.submit-initial-thought').prop('disabled', true);			
			
			// Simple validation for response
			var thoughtCheck = $.trim($('textarea[name="thought_0"]').val());

			if(thoughtCheck){

				$('#thoughts-submitted .seed-admin, #thoughts-submitted .seed-user').fadeOut();

				// Clear validation
				$('.validation').removeClass('alert alert-warning').html('');
				
				// Prep the data
				var args = $('#form-activity').serialize() + '&start=1';
				
				$.ajax({
					type: "POST",
					url: do_mhaActivity.ajaxurl,
					data: { 
						action: 'thoughtSubmission',
						data: args
					},
					success: function( results ) {

						// Show initial thought in the log
						var resultData = JSON.parse(results);	
						console.log(resultData);
				
						setTimeout(() => {
							// Hide initial thought
							$('.activity-response, .form-item.pre-seed').slideUp();
							
							// Hide introduction
							$('article.thought_activity, #other-responses').slideUp();		
						}, 400);

						// ID associated with the thought 
						$('input[name="pid"]').val(resultData['pid']);
						setTimeout(() => {
							$('#start-over-container').slideDown();		
							$('#thought-history .inner').html('<p>'+resultData.response.thought_0.replace(/\\/g, "")+'</p>').slideDown().addClass('fade-in');		
							$('.further-actions').slideDown().addClass('fade-in');			
						}, 400);

					},
					error: function(xhr, ajaxOptions, thrownError){
						console.error(xhr,thrownError);
					}
				});				

			} else {
				
				// Simple validation
				$('.submit-initial-thought').prop('disabled', false);
				$(this).parents('.question-item').find('.validation').addClass('alert alert-warning').html('Responses cannot be blank.');
				
			}

		});


		/**
		 * Start with admin seeded thought and begin saving the activity
		 */
		$('.submit-initial-thought.seed-admin').on('click', function(event){

			// Disable default form submit
			event.preventDefault();

			// Vars
			var seedVal = $(this).val(),
				seedText = $('.pre-seed button[value="'+seedVal+'"]').text();
				
			// Disable submit
			$('.submit-initial-thought').prop('disabled', true);
			
			// Hide initial thought
			$('.activity-response, .form-item.pre-seed').fadeOut(); // TODO: If errors show everything again
			
			// Prep the data
			$('textarea[name="thought_0').val(seedText);
			$('input[name="admin_seed').val(seedVal);
			var args = $('#form-activity').serialize() + '&start=1&seed_admin='+seedVal;

			$.ajax({
				type: "POST",
				url: do_mhaActivity.ajaxurl,
				data: { 
					action: 'thoughtSubmission',
					data: args
				},
				success: function( results ) {

					var resultData = JSON.parse(results);
					console.log(resultData);
					
					// Next steps
					$('.further-actions').fadeIn();
					$('#start-over-container').fadeIn();
					$('article.thought_activity, #other-responses').slideUp();

					$('#thought-history .inner').html('<p>'+resultData.response.thought_0.replace(/\\/g, "")+'</p>').fadeIn();

				},
				error: function(xhr, ajaxOptions, thrownError){
					console.error('Error');
				}
			});				

		});

		
		/**
		 * Start with another users's submitted thought
		 */
		$('.submit-initial-thought.seed-user').on('click', function(event){

			// Disable default form submit
			event.preventDefault();

			// Vars
			var seedVal = $(this).val(),
				seedText = $('.thought-text[data-pid="'+seedVal+'"]').text();
				
			// Disable submit
			$('.submit-initial-thought').prop('disabled', true);
			
			// Hide initial thought
			$('.activity-response, .form-item.pre-seed').fadeOut(); // TODO: If errors show everything again
			
			// Prep the data
			$('textarea[name="thought_0').val(seedText);
			$('input[name="user_seed').val(seedVal);
			var args = $('#form-activity').serialize() + '&start=1&seed_user='+seedVal;

			$.ajax({
				type: "POST",
				url: do_mhaActivity.ajaxurl,
				data: { 
					action: 'thoughtSubmission',
					data: args
				},
				success: function( results ) {

					var resultData = JSON.parse(results);
					console.log(resultData);

					// Next steps
					$('.further-actions').slideDown();
					$('#start-over-container').fadeIn();
					$('article.thought_activity, #other-responses').slideUp();

					$('#thought-history .inner').html('<p>'+resultData.response.thought_0.replace(/\\/g, "")+'</p>').fadeIn();

				},
				error: function(xhr, ajaxOptions, thrownError){
					console.error('Error');
				}
			});				

		});

		
		/**
		 * Select a path
		 */
		$('.submit-path').on('click', function(event){
			
			// Disable default form submit
			event.preventDefault();

			// Hide path selection
			$('.further-actions').slideUp().addClass('fadeOut animated');

			// Show selected path and first question
			var path = $(this).val();
			setTimeout(() => {
				$('ol[data-path="'+path+'"]').slideDown().addClass('active');
			}, 400);

			setTimeout(() => {
				$('ol[data-path="'+path+'"] li[data-question="0"]').slideDown().addClass('active');
			}, 800);

			// Prep the data
			var args = $('#form-activity').serialize() + '&continue=0';

			$.ajax({
				type: "POST",
				url: do_mhaActivity.ajaxurl,
				data: { 
					action: 'thoughtSubmission',
					data: args
				},
				success: function( results ) {
					
					// Show initial thought in the log
					var resultData = JSON.parse(results);
					console.log(resultData);

					setTimeout(() => {
						$('#other-responses').fadeIn();
						$('#thought-history .inner').html('<p>'+resultData.response.thought_0.replace(/\\/g, "")+'</p>').fadeIn();				
					}, 1200);
									
					/**
					 * Update the user submitted thought list
					 */
					var index = 0,
						admin_seed = $('input[name="admin_seed').val(),
						user_seed = $('input[name="user_seed').val(),
						userThoughtArgs = 'activity_id='+resultData.response['page']+'&index='+index+'&path='+resultData.response['path']+'&admin_seed='+admin_seed+'&user_seed='+user_seed;

						console.log(userThoughtArgs);

					$.ajax({
						type: "POST",
						url: do_mhaActivity.ajaxurl,
						data: { 
							action: 'getThoughtsSubmitted',
							data: userThoughtArgs
						},
						success: function( results ) {		

							//var resultData = JSON.parse(results);
							//console.log(results);								
							$('#thoughts-submitted').html(results);
							sortThoughts();

						},
						error: function(xhr, ajaxOptions, thrownError){
							console.error('Error');
						}
					});	

				},
				error: function(xhr, ajaxOptions, thrownError){
					console.error('Error');
				}
			});		
	
		});

		
		/**
		 * Submit follow up thoughts
		 */
		$('.submit-thought').on('click', function(event){

			// Disable default form submit
			event.preventDefault();
				
			// Disable this submit button
			$(this).prop('disabled', true);

			// Set up easy referencees for later
			var $thisButton = $(this);

			// Simple validation for response
			var thoughtCheck = $.trim($(this).parents('.question-item').find('textarea').val());
			console.log(thoughtCheck);
			
			if(thoughtCheck){
				
				// Prep the data
				var ref = $(this).parents('li').attr('data-reference'),
					ref_2 = $(this).parents('li').attr('data-additional-reference'),
					question = parseInt($(this).parents('li').attr('data-question')),
					path = parseInt($('ol.path.active').attr('data-path')),
					argsSuffix = '&continue=1';

				if($(this).parents('li.question-item').hasClass('last')){
					argsSuffix += '&last=1';			
				}

				var args = $('#form-activity').serialize() + '&ref=' + ref + '&ref2=' + ref_2 + '&question=' + question + '&path=' + path + '' + argsSuffix;

				// Disable textarea
				// $(this).parents('.question-item').find('textarea').prop('disabled', true);
				
				$.ajax({
					type: "POST",
					url: do_mhaActivity.ajaxurl,
					data: { 
						action: 'thoughtSubmission',
						data: args
					},
					success: function( results ) {				
						
						var resultData = JSON.parse(results);
						console.log(resultData);	

						$('#temp-result-data').text(results);

						$('.question-item.active .text-entry').slideUp();
						
						/**
						 * Interstitial Step
						 */
						$thisButton.hide();
						$thisButton.next('.continue-thought').fadeIn();
						
						// Display thought in the submitted area
						var newThought = '<li class="round-bl bubble thin submitted-by-user new-thought" style="display: none;"><div class="inner clearfix"><div class="thought-text">'+thoughtCheck+'</div><div class="thought-actions"><span class="explore-container"><button class="bar submit continue-thought-preview">Continue working &raquo;</button></span></div></div></li>';		
						$('#thoughts-submitted').prepend(newThought);	
						$('.new-thought').slideDown();
						setTimeout(() => {
							$('.new-thought').addClass('show-thought');		
							if($('#thoughts-submitted .no-thought').length){
								$('#thoughts-submitted .no-thought').slideUp();
							}					
						}, 400);						

					},
					error: function(xhr, ajaxOptions, thrownError){
						console.error('Error');
					}
				});		
				
				
			} else {
				
				// There is no thought submitted
				$(this).prop('disabled', false);
				$(this).parents('.question-item').find('.validation').addClass('alert alert-warning').html('Responses cannot be blank.');
				
			}

		});


		/**
		 * Additional continue button
		 */
		$(document).on('click', '.continue-thought-preview', function(event){
			
			// Disable default form submit
			event.preventDefault();

			// Just click our existing continue button
			$(document).find('.question-item.active .continue-thought').click();

		})


		/**
		 * Continue after interstitial step
		 */
		$('.continue-thought').on('click', function(event){

			// Disable default form submit
			event.preventDefault();

			// Carry over data			
			var resultData = JSON.parse($('#temp-result-data').text());
			$('#temp-result-data').html('');
			
			// Prep the data
			var ref = $(this).parents('li').attr('data-reference'),
				ref_2 = $(this).parents('li').attr('data-additional-reference'),
				question = parseInt($(this).parents('li').attr('data-question')),
				path = parseInt($('ol.path.active').attr('data-path'));

			// Hide other questions
			$('ol[data-path="'+path+'"] li').slideUp().removeClass('active');
			
			if($('ol[data-path="'+path+'"] li[data-question="'+(question + 1)+'"]').length){

				// Show next question
				$('ol[data-path="'+path+'"] li[data-question="'+(question + 1)+'"]').fadeIn().addClass('active');

				// Update thought log
				var thoughtHistory = '';
				if(ref == 0){
					thoughtHistory += '<p>'+resultData.response['thought_'+ref]+'</p>'; // Initial thought
				} else if(resultData.response['thought_'+path+'_'+ref]){	
					thoughtHistory += '<p>'+resultData.response['thought_'+path+'_'+ref]+'</p>'; // Referred thought
				}

				if(resultData.response['thought_'+path+'_'+ref_2]){	
					thoughtHistory += '<p>'+resultData.response['thought_'+path+'_'+ref_2]+'</p>'; // Additional referred thought	
				}		
							
				$('#thought-history .inner').html(thoughtHistory);	

			} else {
				
				// Question Log
				var thoughtSummary = '<h2>Your Responses</h2>';
				thoughtSummary += '<div class="bubble round-bl light-blue thin"><div class="inner">';
				$('.question-item').each(function(event){
					var question = $(this).find('label').text(),
						answer = $(this).find('textarea').val();
					if(answer){
						thoughtSummary += '<p><strong>'+question+'</strong><br />'+answer+'</p>';
					}
				});
				thoughtSummary += '</div></div>';
				$('#thought-summary').html(thoughtSummary);

				// Show ending
				$('#other-responses, #thought-history, #start-over-container').slideUp();
				setTimeout(() => {
					$('#thought-end').slideDown();					
				}, 400);

			}

								
			/**
			 * Update the user submitted thought list
			 */
			var index = parseInt(resultData.add_row);
			if(!$('ol[data-path="'+path+'"] li[data-question="'+index+'"]').length){
				index = index - 1;
			}
			var admin_seed = $('input[name="admin_seed').val(),
				user_seed = $('input[name="user_seed').val(),
				userThoughtArgs = 'activity_id='+resultData.response['page']+'&index='+index+'&path='+resultData.response['path']+'&admin_seed='+admin_seed+'&user_seed='+user_seed;

			$.ajax({
				type: "POST",
				url: do_mhaActivity.ajaxurl,
				data: { 
					action: 'getThoughtsSubmitted',
					data: userThoughtArgs
				},
				success: function( results ) {		

					//var resultData = JSON.parse(results);
					//console.log(results);								
					$('#thoughts-submitted').html(results);					
					sortThoughts();

				},
				error: function(xhr, ajaxOptions, thrownError){
					console.error('Error');
				}
			});	

		});

		
		/**
		 * Liking a thought
		 */
		$(document).on('click', '.thought-like', function(event){

			// Disable default form submit
			event.preventDefault();

			// Vars
			var nonce = $(this).attr('data-nonce'),
				pid = $(this).attr('data-pid'),
				row = $(this).attr('data-row');
				
			// Prep the data
			var args = 'nonce='+nonce+'&pid='+pid+'&row='+row;
			
			// Disable like button
			$(this).prop('disabled', true);			
			
			$.ajax({
				type: "POST",
				url: do_mhaActivity.ajaxurl,
				data: { 
					action: 'thoughtLike',
					data: args
				},
				success: function( results ) {

					$(`.thought-like[data-pid="${pid}"][data-row="${row}"]`).toggleClass('liked').prop('disabled', false);

				},
				error: function(xhr, ajaxOptions, thrownError){
					
					console.error(xhr,thrownError);

				}
			});	

		});


		/**
		 * Flagging a thought
		 */
		$(document).on('click', '.thought-flag', function(event){

			// Disable default form submit
			event.preventDefault();

			// Vars
			var nonce = $(this).attr('data-nonce'),
				pid = $(this).attr('data-pid'),
				row = $(this).attr('data-row');
				
			// Prep the data
			var args = 'nonce='+nonce+'&pid='+pid+'&row='+row;
			
			// Disable flag button
			$(this).prop('disabled', true);			
			
			$.ajax({
				type: "POST",
				url: do_mhaActivity.ajaxurl,
				data: { 
					action: 'thoughtFlag',
					data: args
				},
				success: function( results ) {

					$(`.thought-flag[data-pid="${pid}"][data-row="${row}"]`).toggleClass('flagged').prop('disabled', false);

				},
				error: function(xhr, ajaxOptions, thrownError){
					
					console.error(xhr,thrownError);

				}
			});	

		});


		/**
		 * Continue a thought upon returning
		 */

		 // User stopped at a question
		if($('.question-item.continue').length){
	
			var path = $('.question-item.continue').parent('ol').attr('data-path'),
				ref = $('.question-item.continue').attr('data-reference'),
				ref_2 = $('.question-item.continue').attr('data-additional-reference'),
				refText0 = $('textarea[name="thought_0"]').val(),
				refText1 = $('textarea[data-path="'+path+'"][data-question="'+(ref - 1)+'"]').val(), // 0 based index adjustment
				refText2 = $('ol[data-path="'+path+'"] li[data-question="'+(ref_2 - 1)+'"] textarea').val(); // 0 based index adjustment				
				
			var thoughtHistory = '';
			if(ref == 0){
				thoughtHistory += '<p>'+refText0+'</p>'; // Initial thought
			} else if(refText1){	
				thoughtHistory += '<p>'+refText1+'</p>'; // Referred thought
			}

			if(refText2){	
				thoughtHistory += '<p>'+refText2+'</p>'; // Additional referred thought	
			}	
			
			$('#thought-history .inner').html(thoughtHistory).slideDown();	
		}

		// User has yet to choose a path
		if($('.further-actions.continue').length){
			
			// Show initial thought and the thought history for path selection
			var refText0 = $('textarea[name="thought_0"]').val();
			var thoughtHistory = '<p>'+refText0+'</p>'; // Initial thought
			$('#thought-history .inner').html(thoughtHistory).slideDown();

		}


		/**
		 * Abandon a thought and start fresh
		 */
		$('#start-over').on('click', function(event){
			
			event.preventDefault();

			// Vars
			var page = $('input[name="page"]').val(),
				nonce = $(this).attr('data-nonce');
				
			// Prep the data
			var args = 'page='+page+'&nonce='+nonce;
			
			// Disable flag button
			$('#start-over').prop('disabled', true);			
			
			$.ajax({
				type: "POST",
				url: do_mhaActivity.ajaxurl,
				data: { 
					action: 'abandonThought',
					data: args
				},
				success: function( results ) {

					var resp = JSON.parse(results);
					console.log(resp);

					window.location.href = resp.page_redirect;

				},
				error: function(xhr, ajaxOptions, thrownError){					
					console.error(xhr,thrownError);
				}
			});	

		})


	}

});