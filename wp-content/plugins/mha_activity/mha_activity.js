jQuery(function ($) {

	// Thought Form Actions
	if($('#form-activity').length){

		/**
		 * Start with an initial thought and begin saving the activity
		 */
		$('.submit-initial-thought.self-thought').on('click', function(event){

			// Disable default form submit
			event.preventDefault();
			
			if($('textarea[name="thought_0').val()){

				// Disable submit
				$('.submit-initial-thought').prop('disabled', true);

				// Clear validation
				$('.validation').removeClass('alert alert-warning').html('');
				
				// Hide initial thought
				$('.form-item.initial, .form-item.pre-seed').hide(); // TODO: If errors show everything again
				
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

						// ID associated with the thought 
						$('input[name="pid"]').val(resultData['pid']);

						// Next steps
						$('.further-actions').show();

					},
					error: function(xhr, ajaxOptions, thrownError){
						console.error(xhr,thrownError);
					}
				});				

			} else {
				
				// There is no thought submitted
				$(this).parents('.question-item').find('.validation').addClass('alert alert-warning').html('Responses cannot be blank.');
				//alert('No thought submitted');
				
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
			$('.form-item.initial, .form-item.pre-seed').hide(); // TODO: If errors show everything again
			
			// Prep the data
			$('textarea[name="thought_0').val(seedText);
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
					$('.further-actions').show();

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
				seedText = $(this).parents('li').find('.thought-text').text();
				
			// Disable submit
			$('.submit-initial-thought').prop('disabled', true);
			
			// Hide initial thought
			$('.form-item.initial, .form-item.pre-seed').hide(); // TODO: If errors show everything again
			
			// Prep the data
			$('textarea[name="thought_0').val(seedText);
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
					$('.further-actions').show();

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
			$('.further-actions').hide();

			// Show selected path and first question
			var path = $(this).val();
			$('ol[data-path="'+path+'"]').addClass('active').show();
			$('ol[data-path="'+path+'"] li[data-question="0"]').show();

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

					$('#thought-history').html('<p>'+resultData.response.thought_0.replace(/\\/g, "")+'</p>').show();

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
			
			// Prep the data
			var ref = $(this).parents('li').attr('data-reference'),
				ref_2 = $(this).parents('li').attr('data-additional-reference'),
				question = parseInt($(this).parents('li').attr('data-question')),
				path = parseInt($('ol.path.active').attr('data-path')),
				last = '',
				argsSuffix = '';

			if($(this).hasClass('last')){
				argsSuffix = '&last=1';			
			}

			var args = $('#form-activity').serialize() + '&ref=' + ref + '&ref2=' + ref_2 + '&question=' + question + '&path=' + path + '&continue=1' + argsSuffix;
			
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
					
					// Hide other questions
					$('ol[data-path="'+path+'"] li').hide();
					
					if($('ol[data-path="'+path+'"] li[data-question="'+(question + 1)+'"]').length){
						// Show next question
						$('ol[data-path="'+path+'"] li[data-question="'+(question + 1)+'"]').show();

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
									
						$('#thought-history').html(thoughtHistory);	
					} else {
						
						// Question Log
						var thoughtSummary = '<h2>Your Responses</h2>';
						$('.question-item').each(function(event){
							var question = $(this).find('label').text(),
								answer = $(this).find('textarea').val();
							if(answer){
								thoughtSummary += '<p><strong>'+question+'</strong><br />'+answer+'</p>';
							}
						});
						$('#thought-summary').html(thoughtSummary);

						// Show ending
						$('#thought-history').hide();
						$('#thought-end').show();
					}

				},
				error: function(xhr, ajaxOptions, thrownError){
					console.error('Error');
				}
			});				

		});

		
		/**
		 * Liking a thought
		 */
		$('.thought-like').on('click', function(event){

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

					var resp = JSON.parse(results),
						likeText = 'Unlike';

					if(resp.liked == 0){
						likeText = 'Like';
					}

					$(`.thought-like[data-pid="${pid}"][data-row="${row}"]`).text(likeText).toggleClass('liked').prop('disabled', false);

				},
				error: function(xhr, ajaxOptions, thrownError){
					
					console.error(xhr,thrownError);

				}
			});	

		});


		/**
		 * Flagging a thought
		 */
		$('.thought-flag').on('click', function(event){

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

					var resp = JSON.parse(results);

					$(`.thought-flag[data-pid="${pid}"][data-row="${row}"]`).text('Flagged').toggleClass('flagged').prop('disabled', false);

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
			
			$('#thought-history').html(thoughtHistory).show();	
		}

		// User has yet to choose a path
		if($('.further-actions.continue').length){
			
			// Show initial thought and the thought history for path selection
			var refText0 = $('textarea[name="thought_0"]').val();
			var thoughtHistory = '<p>'+refText0+'</p>'; // Initial thought
			$('#thought-history').html(thoughtHistory).show();

		}


	}

});