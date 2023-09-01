(function( $ ) {
	
	$(document).ready(function() {

		function GlideAutoHeight(Glide, Components, Events) {
			const Component = {
				mount() {
					if (!Glide.settings.autoHeight) return;
					Components.Html.track.style.transition = 'height 200ms ease-in-out';
					this.updateTrackHeight();
				},
		
				updateTrackHeight() {
					if (!Glide.settings.autoHeight) return;		
					const activeSlides = Components.Html.slides.filter((slide, index) => {
						return (index >= Glide.index && index <= (Glide.index-1) + Glide.settings.perView);
					});		
					const newMaxHeight = activeSlides.reduce((maxHeight, slide) => {
						return Math.max(maxHeight, slide.offsetHeight);
					}, 0);		
					const glideTrack = Components.Html.track;
					if (newMaxHeight !== glideTrack.offsetHeight) {
						glideTrack.style.height = `${newMaxHeight}px`;
					}
				},
			};
		
			Events.on('run', () => {Component.updateTrackHeight();});
			Events.on('update', () => {Component.updateTrackHeight();});
			Events.on('resize', () => {Component.updateTrackHeight();});
		
			return Component;
		}

		function getMhaDiyCrowdsource( load_page = false ){
			
			// Vars
			let current_question = $('#crowdthoughtsContent').attr('data-question'),
				current_activity = $('#crowdthoughtsContent').attr('data-activity'),
				current_post = $('input[name="diytool_current_id"]').length ? $('input[name="diytool_current_id"]').val() : $('#crowdthoughtsContent').attr('data-current'),
				carousel = $('#crowdthoughtsContent').attr('data-carousel'),
				embed_type = $('#crowdthoughtsContent').attr('data-carousel'),
				activity_page = parseInt( $('#crowdthoughtsContent').attr('data-page') ),
				crowdsource_loaded = parseInt( $('#crowdthoughtsContent').attr('data-loaded') ),
				data = 'question='+current_question+'&activity_id='+current_activity+'&carousel='+carousel+'&current='+current_post+'&page='+activity_page;

			if( $('#diy-questions-container').hasClass('embedded-diy') || $('#crowdthoughtsAll').hasClass('embedded-diy') ){
				data = data+'&embedded=1';
			}
			if( $('#diy-questions-container').attr('data-embed-single') == 'true' ){
				data = data+'&single_embed=1';
			}

			// Crowdsource already loaded, no need to reload it
			if( crowdsource_loaded == 1 && load_page == false){
				return;
			}
			
			// GA Event - diy_show_others
			if( crowdsource_loaded == 0 ){
				window.dataLayer.push({
					'event': 'diy_show_others',
					'diy_title': $('h1.entry-title').text(),
					'submitted_url': $('input[name="current_url"]').val()
				});
			}

			// Loading animation
			$('#crowdthoughtsContent').addClass('loading').attr('data-loaded', '1')

			// Save that the user viewed crowdsource content and on which question
			if( $('input[name="opened_diy"]').val() == ''){
				$('input[name="opened_diy"]').val('1');
				$('input[name="opened_diy_question"]').val(current_question);
			}
					
			// Get the question content 
			$.ajax({
				type: "POST",
				url: do_mhaDiyTools.ajaxurl,
				data: { 
					action: 'getDiyCrowdsource',
					data: data
				},
				success: function( results ) {
					var res = JSON.parse(results);
					$('#crowdthoughtsContent').removeClass('loading');

					// The initial population
					if(activity_page == 1){
						$('#crowdthoughtsContent').html(res.html);
						$('#crowdthoughtsContent').attr('data-page', (activity_page + 1) );
					}

					// Append additional pages of crowdsource content
					if(load_page == true){
						// Additional page appending
						// $('#diy-load-more-container, .crowdsource-page-label').remove();
						var $crowdJump = $('#crowdthoughts');
						if($('#crowdthoughtsAll').length){
							$crowdJump = $('#crowdthoughtsAll');
						}
						$('html, body').animate({
							scrollTop: $crowdJump.offset().top
						}, 0);
						$('#crowdthoughtsContent .question-container').addClass('hidden');
						$('#crowdthoughtsContent').append(res.html);
						$('#crowdthoughtsContent').attr('data-page', (activity_page + 1) );
					}

					var sliders = document.querySelectorAll('.crowdsource-responses:not(.glide--slider)'),
						questionAllowSkip = $('#diy-questions').attr('data-skip'),
						questionsTotal = $('#crowdthoughtsContent .crowdsource-responses:first ol li').length;
						questionPeek = $('#diy-questions').length ? $('#diy-questions').attr('data-peek') : 1;	

					for (var i = 0; i < sliders.length; i++) {
						var crowdGlide = {
							type: 'slider',
							start: current_question,
							focusAt: 'center',
							perView: 1,
							gap: 40,
							rewind: false,
							autoHeight: true							
						};		
									
						if(questionPeek == 1){
							crowdGlide.peek = {
								before: 150,
								after: 150
							}
							crowdGlide.breakpoints = {
								880: {
									gap: 20,
									peek: {
										before: 80,
										after: 80
									}
								},
								580: {
									gap: 10,
									peek: {
										before: 30,
										after: 30
									}
								}
							}
						} else {
							crowdGlide.breakpoints = {
								880: {
									gap: 20
								}
							}
						}
						if(questionAllowSkip == 0 || questionsTotal < 2){
							crowdGlide.swipeThreshold = false;
							crowdGlide.dragThreshold = false
						}

						new Glide(sliders[i], crowdGlide ).mount();
						
					}

				},
				error: function(xhr, ajaxOptions, thrownError){
					console.error(xhr,thrownError);
				}
			});	
		}

		function getCurrentQuestions(){
			let current_question = $('#crowdthoughtsContent').attr('data-question'),
				current_activity = $('#crowdthoughtsContent').attr('data-activity'),
				carousel = $('#crowdthoughtsContent').attr('data-carousel'),
				activity_page = $('#crowdthoughtsContent').attr('data-page'),
				data = 'question='+current_question+'&activity_id='+current_activity+'&carousel='+carousel+'&activity_page='+activity_page;

			$('#crowdthoughtsContent').addClass('loading');


			// Get the question content 
			$.ajax({
				type: "POST",
				url: do_mhaDiyTools.ajaxurl,
				data: { 
					action: 'getDiyCrowdsource',
					data: data
				},
				success: function( results ) {
					
					var res = JSON.parse(results);
					$('#crowdthoughtsContent').removeClass('loading');
					if(!$('#crowdthoughtsContent').length){
						$('#crowdthoughtsContent').append(res.html);
						$('.diy-direct-slide[data-index="'+question.index+'"]').click();
					}

				},
				error: function(xhr, ajaxOptions, thrownError){
					console.error(xhr,thrownError);
				}
			});	
		}

		if($('#diy-questions').length){

			// Disable buttons from submitting the form accidentally
			$('.toggle-crowdthoughts').on("click",function(e){
				e.preventDefault();
			});

			/**
			 * Carousel for DIY Questions/Answer Tools
			 */
			var questionStart = $('#diy-questions').attr('data-start'),
				questionPeek = $('#diy-questions').attr('data-peek'),
				questionAllowSkip = $('#diy-questions').attr('data-skip'),
				questionsTotal = $('#diy-questions .glide__slide').length;
			$('.question-direct[data-question=q'+questionStart+']').parent('li').addClass('active');
			
			// Setup Glide
			var glideOptions = {
				type: 'slider',
				start: questionStart,
				focusAt: 'center',
				perView: 1,
				gap: 40,
				rewind: false,
				autoHeight: true,
			};
			if(questionPeek == 1){
				glideOptions.peek = {
					before: 150,
					after: 150
				}
				glideOptions.breakpoints = {
					880: {
						gap: 20,
						peek: {
							before: 80,
							after: 80
						}
					},
					580: {
						gap: 10,
						peek: {
							before: 30,
							after: 30
						}
					}
				}
			} else {
				glideOptions.breakpoints = {
					880: {
						gap: 20
					}
				}
			}
			if(questionAllowSkip == 0 || questionsTotal < 2){
				glideOptions.swipeThreshold = false;
				glideOptions.dragThreshold = false
			}

			// Total slides
			const questionTotal = function(Glide, Components, Events) {
				return {
					mount () {
						Events.emit('slider.length', Components.Sizes.length);
					}
				}
			}

			// Activate Glide
			const question = new Glide('.glide', glideOptions ).mount({
				GlideAutoHeight: GlideAutoHeight
			});

			question.on(['run', 'move.after'], () => {
				// Disable/enable Next
				if( questionTotal.length == question.index + 1 ){
					$('.question-next').prop('disabled',true);
				} else {
					$('.question-next').prop('disabled',false);
				}
				
				// Disable/enable previous
				if( question.index == 0 ){
					$('.question-prev').prop('disabled',true);
				} else {
					$('.question-prev').prop('disabled',false);
				}

				// Update crowdsource index
				$('#crowdthoughtsContent').attr('data-question', question.index);
				$('.diy-direct-slide[data-index="'+question.index+'"]').click();

				// Scroll to the proper question it was opened on
				setTimeout(() => {
					$('.crowdsource-responses .glide__arrows .diy-direct-slide[data-index="'+question.index+'"]').click();					
				}, 200); // Slight delay to help make sure everything is loaded before clicking
			});

			// Update active navigation
			question.on('move', function() {
				$('.question-breadcrumb li').removeClass('active');
				$('.question-direct[data-question=q'+question.index+']').parent('li').addClass('active');
				$('.question-direct[data-question=q'+question.index+']').find('textarea').focus();
			});
			// Update active navigation
			question.on('run.after', function() {
				// Scroll to the proper question it was opened on
				if( $('.toggle-crowdthoughts').attr('aria-expanded') == 'true' ){
					$('.crowdsource-responses .glide__arrows .diy-direct-slide[data-index="'+question.index+'"]').click();
				}
			});
			
			// Carousel navigation
			var elements = document.getElementsByClassName("diy-carousel-nav");
			var questionNavigation = function(e) {

				// Avoid submitting the form
				e.preventDefault();

				// Get the direction
				let dir = this.getAttribute("data-glide-dir");

				// Prevent skips
				if( $('#diy-questions-container').attr('data-skippable') == 0){
					// Previous
					if(dir == '<' && $('.glide__slide--active').prev().hasClass('valid') === false ){
						return;
					}

					// Next
					if(dir == '>' && $('.glide__slide--active').hasClass('valid') === false ){
						return;
					}
				}

				// Process skips
				question.go(dir);

			};
			for (var i = 0; i < elements.length; i++) {
				elements[i].addEventListener('click', questionNavigation, false);
			}


			$("#diy-questions[data-skip=0] textarea").each(function(e){
				let $parent = $(this).parents('li'),
					$nextButton = $parent.find('.action-button');
				// Enable in case of refresh
				if($(this).val() != ''){
					$nextButton.prop('disabled', false);
					$parent.addClass('valid');
				}
				
				// Simple validation*
				$(this).on("input", function() {
					if($(this).val() != ''){
						$nextButton.prop('disabled', false);
						$parent.addClass('valid');
					} else {
						$nextButton.prop('disabled', true);
						$parent.removeClass('valid');
					}
				});
			});

			
			$("#diy-questions[data-skip=0] input[type='radio'], #diy-questions[data-skip=0] input[type='checkbox']").each(function(e){
				let $parent = $(this).parents('li'),
					inputName = $(this).attr('name'),
					$nextButton = $parent.find('.action-button');

				// Enable in case of refresh
				if($(this).is(":checked")){
					$nextButton.prop('disabled', false);
					$parent.addClass('valid');
				}
				

				// Simple validation*
				$('#diy-questions[data-skip=0] input[name="'+inputName+'"]').on("change", function(event) {
					if($(this).is(":checked")){
						$nextButton.prop('disabled', false);
						$parent.addClass('valid');
					} else {
						$nextButton.prop('disabled', true);
						$parent.removeClass('valid');
					}
				});
			});


			/**
			 * Form Submissions
			 */
			$('#diy-questions-container .action-button.next-question').on('click', function(event){
				event.preventDefault();

				// Vars for later
				let q_id = $(this).attr('data-question'),
					$diy_container = $('#diy-questions-container'),
					response_id = $diy_container.attr('diy-questions-container'),
					q_answer = $('textarea[data-question='+q_id+']').val(),
					embed_single = $diy_container.attr('data-embed-single'),
					embed_action = $diy_container.attr('data-action');
		
				if(q_answer != ''){

					// Single embed redirect override
					if(embed_single){
						var embed_action_url = new URL(embed_action);
						embed_action_url.searchParams.append('diy_continue', 1);
						$('#diy-questions-container').append('<div class="loading-next-diy"></div>')
						$diy_container.attr('action', embed_action_url.href).submit();
						return;
					}

					// Disable submit
					$('.action-button.next-question[data-question='+q_id+']').prop('disabled', true);	

					// Prep the data
					var args = $diy_container.serialize();

					if($diy_container.hasClass('embedded-diy')){
						args += '&embedded=1';
					}

					// Submit complete form check
					if( $(this).hasClass('submit')) {
						args += '&submit=1';
					}
					
					$.ajax({
						type: "POST",
						url: do_mhaDiyTools.ajaxurl,
						data: { 
							action: 'mhaDiySubmit',
							data: args
						},
						success: function( results ) {
							
							$('.action-button.next-question[data-question='+q_id+']').prop('disabled', false);		
							var res = JSON.parse(results);

							var current_post = $('input[name="diytool_current_id"]').val();
							if(current_post == ''){
								$('input[name="diytool_current_id"]').val(res.post_id);
							}

							if(res.error){

								$('.next-question.submit').tooltip({
									title: res.error,
								});
								$('.next-question.submit').tooltip('show');

							} else {

								if(res.redirect){

									let total_questions = $('#diy-questions textarea').length,
										total_answers = 0;
									$('#diy-questions textarea').each(function(){
										if($(this).val()){
											total_answers++;
										}
									});

									// GA Event - diy_submit
									window.dataLayer.push({
										'event': 'diy_submit',
										'diy_title': $('h1.entry-title').text(),
										'submitted_url': $('input[name="current_url"]').val(),
										'diy_total_answers': total_answers,
										'diy_total_questions': total_questions
									});

									
									if(res.args.embedded == 1){

										// Display confirmation without reloading for embedded forms
										var resultArgs = 'id='+res.post_id+'&embedded=1';
										$('.tooltip').remove();
										$.ajax({
											type: "POST",
											url: do_mhaDiyTools.ajaxurl,
											data: { 
												action: 'mhaDiyGetConfirmation',
												data: resultArgs
											},
											success: function( results ) {
												var res = JSON.parse(results);
												$('.diy-tool-shortcode').html(res.html);
											},
											error: function(xhr, ajaxOptions, thrownError){
												console.error(xhr,thrownError);
											}
										});	

									} else {

										// Normal redirection 
										window.location.href = res.redirect;

									}
									
								}

							}

						},
						error: function(xhr, ajaxOptions, thrownError){
							console.error(xhr,thrownError);
						}
					});		

				} else {
					//
				}

			});

			// Crowdsource Display on Activity Page
			$('#crowdthoughts').on('show.bs.collapse', function () {
				// getCurrentQuestions();
				getMhaDiyCrowdsource();
			});


			// Continuing a started embed submission, go to second question
			if($('#diy-questions-container').attr('data-embed-continue') == 'true'){
				if( $('#diy-questions .question').eq(0).find('textarea').val() ){
					$('button.next-question[data-question="0"]').click();
					question.go('>');
				}
			}

		}

		// Crowdsource Display
		$(document).on('show.bs.collapse','#crowdthoughtsAll', function () {
			getMhaDiyCrowdsource();
		});
		if( $('.single-diy_responses #crowdthoughtsAll').hasClass('show') ){
			getMhaDiyCrowdsource();
		}

		// Show the full response text
		$(document).on('click', 'button.text-snippet-toggle', function(e){
			e.preventDefault;
			let snipid = $(this).attr('data-snippet-toggle');
			if($('.text-snippet-long[data-snippet-id="'+snipid+'"]').hasClass('hidden')){
				$(this).text('Read less');
				$('.text-snippet-short[data-snippet-id="'+snipid+'"]').addClass('hidden').attr('aria-expanded', 'false');
				$('.text-snippet-long[data-snippet-id="'+snipid+'"]').removeClass('hidden').attr('aria-expanded', 'true');
			} else {
				$(this).text('Read more');
				$('.text-snippet-short[data-snippet-id="'+snipid+'"]').removeClass('hidden').attr('aria-expanded', 'true');
				$('.text-snippet-long[data-snippet-id="'+snipid+'"]').addClass('hidden').attr('aria-expanded', 'false');
			}
		});

		// Crowdsource pagination read more
		$(document).on('click', '.diy-load-more', function(e){
			e.preventDefault();
			let showPage = $(this).attr('data-show-page');
			if( $('#crowdthoughtsContent .question-container[data-page="'+showPage+'"]').length ){
				// Page was previously loaded, just unhide it
				$('#crowdthoughtsContent .question-container').addClass('hidden');
				$('#crowdthoughtsContent .question-container[data-page="'+showPage+'"]').removeClass('hidden');
				$('html, body').animate({
					scrollTop: $('#crowdthoughts').offset().top
				}, 0);
			} else {
				// Page hasn't been loaded yet, grab it normally
				getMhaDiyCrowdsource( true );
			}
		});

		// Previous Page Button clicks
		$(document).on('click', '.diy-previous-page', function(e){
			e.preventDefault();
			let showPage = $(this).attr('data-show-page');
			$('#crowdthoughtsContent .question-container').addClass('hidden');
			$('#crowdthoughtsContent .question-container[data-page="'+showPage+'"]').removeClass('hidden');
			$('html, body').animate({
				scrollTop: $('#crowdthoughts').offset().top
			}, 0);
		});
		
		/**
		 * Toggle question display on crowdsource
		 */
		$(document).on('click', '.question-label-toggle', function(e){
			$(this).find('.question-label-short').toggleClass("d-none");
			$(this).find('.question-label-long').toggleClass("d-none");
		});

		$(document).on('change', '#crowdsource_hidden', function(e){
			var updateMsg = '';
			if( this.checked ){
				updateMsg = 'This submission will be hidden from other users and only visible only to you.';
			} else {
				updateMsg = 'This submission will be visible to other users. Submissions are anonymous; no usernames are displayed.';
			}
			if(updateMsg != ''){
				$('.diy-opt-out-message').removeClass('invisible').find('.inner').html(updateMsg);
			}
		});

		$(document).on('click', '.toggle_private_thought', function(e){

			let $checkbox = $(this),
				pid = $checkbox.attr('data-id'),
				value = $checkbox.prop( "checked" ) ? 1 : 0;

			$checkbox.prop('disabled', true).addClass('loading');

			$.ajax({
				type: "POST",
				url: do_mhaDiyTools.ajaxurl,
				data: { 
					action: 'mhaToggleHideThought',
					data: 'pid='+pid+'&value='+value
				},
				success: function( results ) {					
					var res = JSON.parse(results),
						updateMsg = '';
					if(res.new_value == true){
						updateMsg = 'This submission has been hidden from other users and is now visible only to you.';
					} else if ( res.new_value == false){
						updateMsg = 'This submission is now visible to other users. Submissions are anonymous; no usernames are displayed.';
					}
					$checkbox.prop('disabled', false).removeClass('loading');
					if(updateMsg != ''){
						$('.toggle_private_thought_message[data-thought="'+pid+'"]').removeClass('d-none').find('.inner').html(updateMsg);
					}
				},
				error: function(xhr, ajaxOptions, thrownError){
					console.error(xhr,thrownError);
				}
			});	
			
		});

	});

})( jQuery );
