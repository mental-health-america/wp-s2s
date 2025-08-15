(function( $ ) {
	
	$(document).ready(function() {

		function mhaDiyNextQuestion( event, thisEl ){
			event.preventDefault();

			// Vars for later
			let $thisButton = thisEl,
				$diyParent = $thisButton.parents('.diy-tool-container'),
				q_id = $thisButton.attr('data-question'),
				$diy_container = $diyParent.find('.diy-questions-container'),
				response_id = $diy_container.attr('diy-questions-container'),
				q_answer = $diyParent.find('.textarea[data-question='+q_id+']').val(),
				embed_single = $diy_container.attr('data-embed-single'),
				embed_action = $diy_container.attr('data-action'),
				diy_type = $diy_container.attr('data-type');

			// Focus on next question when clicking the "next" button
			setTimeout(() => {
				$diyParent.find('.glide__slide.glide__slide--active').find('.textarea').focus();	
			}, 500);

			if( q_answer != '' || diy_type == 'worksheet' ){

				$thisButton.prop('disabled', true);

				// Disable submit
				$diyParent.find('.action-button.next-question[data-question='+q_id+']').prop('disabled', true);	

				// Prep the data
				var args = $diy_container.serialize();

				if($diy_container.hasClass('embedded-diy')){
					args += '&embedded=1';
				}

				// Submit complete form check
				if( $thisButton.hasClass('submit')) {
					args += '&submit=1';
				}

				// Recommended slide jump
				$(document).on('click', '.question-jump', function(e){
					let slideId = $(this).attr('data-glide-dir'),
						$diyParent = $(this).parents('.diy-tool-container');
					$diyParent.find('.question-direct[data-question="'+slideId+'"]').click();
				});
				
				$.ajax({
					type: "POST",
					url: do_mhaDiyTools.ajaxurl,
					data: { 
						action: 'mhaDiySubmit',
						data: args
					},
					success: function( results ) {
							
						if(embed_single){

							// Single embed redirect override
							var embed_action_url = new URL(embed_action);
							embed_action_url.searchParams.append('diy_continue', 1);
							$diyParent.find('.diy-questions-container').append('<div class="loading-next-diy"></div>');
							$diy_container.attr('action', embed_action_url.href).submit();
							return;

						} else {

							// Normal submits
							$diyParent.find('.action-button.next-question[data-question='+q_id+']').prop('disabled', false);		
							var res = JSON.parse(results);
							
							var current_post = $diyParent.find('input[name="diytool_current_id"]').val();
							if(current_post == ''){
								$diyParent.find('input[name="diytool_current_id"]').val(res.post_id);
							}

							if(res.error){

								$diyParent.find('.next-question.submit').tooltip({
									title: res.error,
								});
								$diyParent.find('.next-question.submit').tooltip('show');

							} else {
								
								// Recommended friction
								let submitRecommendedBlock = $diyParent.find('.next-question.submit').attr('data-has-recommended');
								if(submitRecommendedBlock == 'true'){

									// Display recommendation text
									let recommended_text = '',
										recommended_count = 0,
										recommended_header_text = $diyParent.find('.diy-questions-container').attr('data-recommended-header'),
										recommended_footer_text = $diyParent.find('.diy-questions-container').attr('data-recommended-footer');

									recommended_text += recommended_header_text+'<br />';

									$diyParent.find('.question[data-recommended="true"]').each(function(){
										let questionRecText = $(this).attr('data-recommended-text'),
											questionData = $(this).attr('data-question'),
											questionRecButton = '<button class="question-jump button tiny" data-glide-dir="'+questionData+'">&laquo; Revisit this question</button>';
										recommended_text += ''+questionRecText+' '+questionRecButton+'<br />';
										recommended_count++;
									});

									recommended_text += recommended_footer_text;

									if(recommended_count > 0){
										$diyParent.find('.next-question.submit').attr('data-has-recommended', 'false'); // Allow next submit click
										// Display recommended text
										$diyParent.prepend('<div class="wrap normal pb-2 recommended-display" style="max-width: 918px;"><div class="recommended-tooltip bubble round-tl narrow dark-blue"><div class="inner">'+recommended_text+'</div></div></div>');
										$('html,body').animate({
											scrollTop: $diyParent.offset().top - 75
										});
										return;
									}
								}

								if(res.redirect){
									
									let total_questions = $diyParent.find('.diy-questions .textarea').length,
										total_answers = 0;
										
									$diyParent.find('.diy-questions .textarea').each(function(){
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

									// Redirect to TY page
									//window.location.href = res.redirect;
									var futureRedirect = res.redirect;

									// Embed TY on page or redirect
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
												
												var res = JSON.parse(results),
													$completedDiyContainer = $diyParent.parents('.diy-tool-shortcode');

												$completedDiyContainer.html(res.html);

												$('html,body').animate({
													scrollTop: $completedDiyContainer.offset().top
												});

												// Iframe for a server post log to track completions 
												let iframeUrl = new URL(futureRedirect);
												iframeUrl.searchParams.append('completed_embed', 1);
												$('<iframe id="completedDiyFrame" frameborder="0" width="0" height="0" class="invisible" scrolling="no" />').prop('src', iframeUrl).appendTo( $completedDiyContainer );
												
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

						}

					},
					error: function(xhr, ajaxOptions, thrownError){
						console.error(xhr,thrownError);
					}
				});		

			}
			
		}

		function getBreadPositions( diyParentRaw = null ) {

			var $diyParent = $('#'+diyParentRaw),
				windowWidth = $(window).width(),
				mod = windowWidth <= 940 ? 84 : 30,
				crowdTop = $diyParent.find('.diy-footer').offset().top,
				crowdBottom = crowdTop + $diyParent.find('.crowdthoughts').outerHeight() + mod,
				breadHeight = $diyParent.find('.question-breadcrumb-container').outerHeight(),
				crowdTopMods = windowWidth <= 940 ? crowdTop + breadHeight + 69 : crowdTop + breadHeight + 25,
				scrollPositionBottom = $(window).scrollTop() + $(window).height();

			// Bottom alignment
			if (scrollPositionBottom > crowdBottom) {
				$diyParent.find('.question-breadcrumb-container').addClass('not-fixed');
			} else {
				$diyParent.find('.question-breadcrumb-container').removeClass('not-fixed');
			}

			if (scrollPositionBottom < crowdTopMods) {
				$diyParent.find('.question-breadcrumb-container').addClass('max-top');
			} else {
				$diyParent.find('.question-breadcrumb-container').removeClass('max-top');
			}
		
		}

		function diyBreadcrumbScroll( diyParentRaw = null ){
			$('#content .wrap.wide.no-margin-mobile').addClass('position-relative');
			getBreadPositions( diyParentRaw );
			$(window).scroll(function(){
				getBreadPositions( diyParentRaw );
			});

		}

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

		function getMhaDiyCrowdsource( load_page = false, diyParentRaw = null ){
			
			// Vars
			let $diyParent = $('#'+diyParentRaw).length ? $('#'+diyParentRaw) : $('#crowdthoughtsAll'),
				current_question = $diyParent.find('.crowdthoughtsContent').attr('data-question'),
				current_activity = $diyParent.find('.crowdthoughtsContent').attr('data-activity'),
				current_post = $('input[name="diytool_current_id"]').length ? $('input[name="diytool_current_id"]').val() : $diyParent.find('.crowdthoughtsContent').attr('data-current'),
				carousel = $diyParent.find('.crowdthoughtsContent').attr('data-carousel'),
				embed_type = $diyParent.find('.crowdthoughtsContent').attr('data-carousel'),
				activity_page = parseInt( $diyParent.find('.crowdthoughtsContent').attr('data-page') ),
				crowdsource_loaded = parseInt( $diyParent.find('.crowdthoughtsContent').attr('data-loaded') ),
				data = 'question='+current_question+'&activity_id='+current_activity+'&carousel='+carousel+'&current='+current_post+'&page='+activity_page;

			if( $diyParent.find('.diy-questions-container').hasClass('embedded-diy') || $diyParent.find('#crowdthoughtsAll').hasClass('embedded-diy') ){
				data = data+'&embedded=1';
			}
			if( $diyParent.find('.diy-questions-container').attr('data-embed-single') == 'true' ){
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
			$diyParent.find('.crowdthoughtsContent').addClass('loading').attr('data-loaded', '1')

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
					$diyParent.find('.crowdthoughtsContent').removeClass('loading');
					
					// The initial population
					if(activity_page == 1){
						$diyParent.find('.crowdthoughtsContent').html(res.html);
						$diyParent.find('.crowdthoughtsContent').attr('data-page', (activity_page + 1) );
					}

					// Append additional pages of crowdsource content
					if(load_page == true){
						// Additional page appending
						// $('#diy-load-more-container, .crowdsource-page-label').remove();
						var $crowdJump = $diyParent.find('.crowdthoughts');
						if($('#crowdthoughtsAll').length){
							$crowdJump = $('#crowdthoughtsAll');
						}
						$('html, body').animate({
							scrollTop: $crowdJump.offset().top
						}, 0);
						
						$diyParent.find('.crowdthoughtsContent .question-container').addClass('hidden d-none');
						$diyParent.find('.crowdthoughtsContent').append(res.html);
						$diyParent.find('.crowdthoughtsContent').attr('data-page', (activity_page + 1) );
					}

					const sliders = document.querySelectorAll('.crowdsource-responses:not(.glide--slider)'),
						questionAllowSkip = $diyParent.find('.diy-questions').attr('data-skip'),
						questionsTotal = $diyParent.find('.crowdthoughtsContent .crowdsource-responses:first ol li').length;
						questionPeek = $diyParent.find('.diy-questions').length ? $diyParent.find('.diy-questions').attr('data-peek') : 1
						crowdGliderPrefix = 'crowdGlider';	

					for (var i = 0; i < sliders.length; i++) {
						var crowdGlide = {
							type: 'slider',
							startAt: current_question,
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

						$diyParent.find('.question-breadcrumb-container').addClass('sticky');
						if(!$('#crowdthoughtsAll').length){
							diyBreadcrumbScroll( diyParentRaw );
						}
					}

					// Control ALL the slides
					let startingIndex = $diyParent.find('.crowdthoughtsContent').attr('data-question');
					sliders.forEach((slider, index) => {
						const crowdGlider = new Glide(slider, crowdGlide ).mount();					
						let lastIndex = $diyParent.find('.crowdthoughtsContent').attr('data-question');
						crowdGlider.on(['move.after'], () => {	
							if (crowdGlider.index !== lastIndex && startingIndex == 0) {
								$diyParent.find('.question-direct[data-question=q'+crowdGlider.index+']').click();
								$diyParent.find('.diy-direct-slide[data-index='+crowdGlider.index+']').click();
								lastIndex = crowdGlider.index;
								startingIndex = 0;
							}
							if (startingIndex == lastIndex && startingIndex > 0) {
								$diyParent.find('.question-direct[data-question=q'+startingIndex+']').click();
								$diyParent.find('.diy-direct-slide[data-index='+crowdGlider.index+']').click();
								lastIndex = crowdGlider.index;
								startingIndex = 0;
							}
						});
					});


				},
				error: function(xhr, ajaxOptions, thrownError){
					console.error(xhr,thrownError);
				}
			});	
		}

		if($('.diy-questions').length){

			$('.diy-questions').each(function(e){
				
				if($(this).hasClass('glide')){

					// Disable buttons from submitting the form accidentally
					$('.toggle-crowdthoughts').on("click",function(e){
						e.preventDefault();
					});

					/**
					 * Carousel for DIY Questions/Answer Tools
					 */
					let $diyParent = $(this).parents('.diy-tool-container'),
						diyRaw = $(this).parents('.diy-tool-container').attr('id'),
						questionStart = $diyParent.find('.diy-questions').attr('data-start'),
						questionPeek = $diyParent.find('.diy-questions').attr('data-peek'),
						questionAllowSkip = $diyParent.find('.diy-questions').attr('data-skip'),
						questionsTotal = $diyParent.find('.diy-questions .glide__slide').length;

					$diyParent.find('.question-direct[data-question=q'+questionStart+']').parent('li').addClass('active');
					
					// Setup Glide
					var glideOptions = {
						type: 'slider',
						startAt: questionStart,
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
					const questionTotal = $diyParent.find('.glide__slide').length;

					// Activate Glide
					if($diyParent.find('.diy-questions-container').attr('data-embed-single') != 'true'){

						const question = new Glide('#'+diyRaw+' .glide', glideOptions ).mount({
							GlideAutoHeight: GlideAutoHeight
						});

						question.on(['run', 'move.after'], () => {

							// Disable/enable Next
							if( question.index + 1 >= questionTotal ){
								$diyParent.find('.question-next').prop('disabled',true);
								$diyParent.find('.peek.diy-carousel-nav.fade-right').prop('disabled',true);
							} else {
								$diyParent.find('.question-next').prop('disabled',false);
								$diyParent.find('.peek.diy-carousel-nav.fade-right').prop('disabled',false);
							}
							
							// Disable/enable previous
							if( question.index == 0 ){
								$diyParent.find('.question-prev').prop('disabled',true);
								$diyParent.find('.peek.diy-carousel-nav.fade-left').prop('disabled',true);
							} else {
								$diyParent.find('.question-prev').prop('disabled',false);
								$diyParent.find('.peek.diy-carousel-nav.fade-left').prop('disabled',false);
							}

							// Update crowdsource index
							$diyParent.find('.crowdthoughtsContent').attr('data-question', question.index);
							$diyParent.find('.diy-direct-slide[data-index="'+question.index+'"]').click();

							// Update Tabindexes on change
							$diyParent.find('[tabindex]').attr('tabindex', '-1');
							$('.glide__slide--active [tabindex]').attr('tabindex', '0');

							// Last Slide Mods

							// Scroll to the proper question it was opened on
							setTimeout(() => {
								$diyParent.find('.crowdsource-responses .glide__arrows .diy-direct-slide[data-index="'+question.index+'"]').click();					
							}, 50); // Slight delay to help make sure everything is loaded before clicking
						});

						// Update active navigation
						question.on('move', function(e, f) {
							$diyParent.find('.question-breadcrumb li').removeClass('active');
							$diyParent.find('.question-direct[data-question=q'+question.index+']').parent('li').addClass('active');
						});

						// Update active navigation
						question.on('run.after', function(e) {
							// Scroll to the proper question it was opened on
							if( $diyParent.find('.toggle-crowdthoughts').attr('aria-expanded') == 'true' ){
								$diyParent.find('.crowdsource-responses .glide__arrows .diy-direct-slide[data-index="'+question.index+'"]').click();
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
							if( $diyParent.find('.diy-questions-container').attr('data-skippable') == 0){
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


						$diyParent.find('.diy-questions[data-skip=0] .textarea').each(function(e){
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
						
						$(".diy-questions[data-skip=0] input[type='radio'], .diy-questions[data-skip=0] input[type='checkbox']").each(function(e){
							let $parent = $(this).parents('li'),
								inputName = $(this).attr('name'),
								$nextButton = $parent.find('.action-button');

							// Enable in case of refresh
							if($(this).is(":checked")){
								$nextButton.prop('disabled', false);
								$parent.addClass('valid');
							}

							// Simple validation*
							$('.diy-questions[data-skip=0] input[name="'+inputName+'"]').on("change", function(event) {
								if($(this).is(":checked")){
									$nextButton.prop('disabled', false);
									$parent.addClass('valid');
								} else {
									$nextButton.prop('disabled', true);
									$parent.removeClass('valid');
								}
							});
						});
						
						// Enter key navigation
						$(document).on("keydown", ".diy-questions .question .textarea", function(e) { 
							
							let val = $(this).val(),
								rec = $(this).parents('.question').attr('data-recommended');
							if(val != '' && rec == 'true'){
								$(this).parents('.question').attr('data-recommended', 'false');
							}
							if(val == '' && rec == 'false'){
								$(this).parents('.question').attr('data-recommended', 'true');
							}

							if (e.key === 'Enter' || e.keyCode === 13) {
								e.preventDefault(); 
								e.stopPropagation(); 
								if($(e.currentTarget).val() != ''){
									// Focus on next slide
									$(this).parents('.diy-tool-container').find('.glide__slide.glide__slide--active').next('li').find('.textarea').focus();

									// Go to slide
									question.go('>');
								}
							}

						});
					}

				}

			});

			/**
			 * Form Submissions
			 */

			// Question & Answers Next Button Clicks
			$('.diy-questions-container .action-button.next-question').on('click', function(event){ 
				mhaDiyNextQuestion(event, $(this) );
			});

			// Worksheet textarea focus or button click
			let userAnswers = {};

			// Textarea changes
			$('.diy-questions-container .diy-worksheet .worksheet-item textarea').on('focusout', function(event){ 
				$textarea = $(this);
				checkWorksheetAnswers(event, $textarea, userAnswers);
			});

			// Breathe button click
			$('.diy-questions-container .diy-worksheet .start-breathing').on('click', function(event){ 
				let $textarea = $(this);
				checkWorksheetAnswers(event, $textarea, userAnswers);
			});

			function checkWorksheetAnswers( event, $textarea, userAnswers ){
				let qid = $textarea.attr('data-question'),
					$tempAnswers = $textarea.parents('.diy-questions-container').find('textarea[name="temp_worksheet"]');
					$worksheetButton = $textarea.parents('.question').find('.action-button[data-question="'+qid+'"]');

				$('.diy-questions-container .diy-worksheet .worksheet-item textarea').each(function(e){
					let questionAnswer = $.trim($(this).val()),
						thisQid = $(this).attr('data-question');
					if( questionAnswer !== ''){
						userAnswers['question_'+thisQid] = questionAnswer;
					}
				});

				let userAnswersJson = JSON.stringify( userAnswers );
				if( Object.keys(userAnswers).length > 0 && $tempAnswers.val() != userAnswersJson ){
					$tempAnswers.val( userAnswersJson );
					mhaDiyNextQuestion(event, $worksheetButton );
				}
			}

			// Crowdsource Display on Activity Page
			$('.crowdthoughts').on('show.bs.collapse', function () {
				// getCurrentQuestions();
				let diyParent = $(this).parents('.diy-tool-container').attr('id');
				getMhaDiyCrowdsource( false, diyParent );
				$(this).parents('.diy-tool-container').find('.question-breadcrumb-container').addClass('sticky');
			});
			$('.crowdthoughts').on('hidden.bs.collapse', function () {
				let $diyParent = $(this).parents('.diy-tool-container');
				$diyParent.find('.question-breadcrumb-container').removeClass('sticky');
			});

		}

		// Crowdsource Display
		$(document).on('show.bs.collapse','#crowdthoughtsAll', function () {
			let diyParent = $(this).parents('.diy-tool-container').attr('id');
			getMhaDiyCrowdsource( false, 'crowdthoughtsAll' );
		});
		if( $('.single-diy_responses #crowdthoughtsAll').hasClass('show') ){
			let diyParent = $('.diy-tool-container').attr('id');
			getMhaDiyCrowdsource( false, diyParent );
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
			let showPage = $(this).attr('data-show-page'),
				$diyParent = $(this).parents('.diy-tool-container');

				if($('#crowdthoughtsAll').length){
					$diyParent = $('#crowdthoughtsAll');
				}

			if( $diyParent.find('.crowdthoughtsContent .question-container[data-page="'+showPage+'"]').length ){
				// Page was previously loaded, just unhide it
				$diyParent.find('.crowdthoughtsContent .question-container').addClass('hidden d-none');
				$diyParent.find('.crowdthoughtsContent .question-container[data-page="'+showPage+'"]').removeClass('hidden d-none');

				if($('#crowdthoughtsAll').length){
					$('html, body').animate({
						scrollTop: $('#crowdthoughtsAll').offset().top
					}, 0);
				} else {
					$('html, body').animate({
						scrollTop: $diyParent.find('.crowdthoughts').offset().top
					}, 0);
				}
			} else {
				// Page hasn't been loaded yet, grab it normally
				let diyParent = $(this).parents('.diy-tool-container').attr('id');
				getMhaDiyCrowdsource( true, diyParent );
			}
		});

		// Previous Page Button clicks
		$(document).on('click', '.diy-previous-page', function(e){
			e.preventDefault();
			let showPage = $(this).attr('data-show-page'),
				$diyParent = $(this).parents('.diy-tool-container');

			if($('#crowdthoughtsAll').length){
				$diyParent = $('#crowdthoughtsAll');
			}

			$diyParent.find('.crowdthoughtsContent .question-container').addClass('hidden d-none');
			$diyParent.find('.crowdthoughtsContent .question-container[data-page="'+showPage+'"]').removeClass('hidden d-none');
			
			if($('#crowdthoughtsAll').length){
				$('html, body').animate({
					scrollTop: $('#crowdthoughtsAll').offset().top
				}, 0);
			} else {
				$('html, body').animate({
					scrollTop: $diyParent.find('.crowdthoughts').offset().top
				}, 0);
			}
		});
		
		/**
		 * Toggle question display on crowdsource
		 */
		$(document).on('click', '.question-label-toggle', function(e){
			$(this).find('.question-label-short').toggleClass("d-none");
			$(this).find('.question-label-long').toggleClass("d-none");
		});

		$(document).on('change', '.crowdsource_hidden', function(e){
			var updateMsg = '';
			if( this.checked ){
				updateMsg = 'This submission will be hidden from other users and only visible only to you.';
			} else {
				updateMsg = 'This submission will be visible to other users. Submissions are anonymous; no usernames are displayed.';
			}
			if(updateMsg != ''){
				//$('.diy-opt-out-message').removeClass('invisible').find('.inner').html(updateMsg);
				$(this).parent('label').attr('data-original-title', updateMsg).tooltip('show');
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


		$('.diy-carousel-nav.fade-right').on('click', function(e){
			let $diyParent = $(this).parents('.diy-tool-container');
			$diyParent.find('.glide__slide.glide__slide--active').next('li').find('.textarea').focus();
		});
		
		$('.diy-carousel-nav.fade-left').on('click', function(e){
			let $diyParent = $(this).parents('.diy-tool-container');
			$diyParent.find('.glide__slide.glide__slide--active').prev('li').find('.textarea').focus();
		});

		$(document).on("keyup", ".diy-questions .question .textarea", function(e) { 
			
			/* Character Counter */
			let characterCount = $(this).val().length,
				thisName = $(this).attr('name'),
				current = $('.character-counter[data-answer="'+thisName+'"] .current'),
				maximum = $('.character-counter[data-answer="'+thisName+'"] .maximum'),
				theCount = $('.character-counter[data-answer="'+thisName+'"]');
			
			current.text(characterCount);
					
			if (characterCount < 900) {
				current.css('color', '#144B5E');
			}
			if (characterCount > 900 && characterCount < 975) {
				current.css('color', '#F89941');
			}
			if (characterCount > 975) {
				current.css('color', '#C1335D');
			}
			
			if (characterCount >= 900) {
				theCount.removeClass('d-none').addClass('d-block');
			} else {
				theCount.removeClass('d-block').addClass('d-none');
			}

		});
		

		/**
		 * Breathing question
		 */
		$(document).on('click', '.start-breathing', function(e){
			e.preventDefault();
			let $button = $(this);
				bid = $button.attr('data-breathe'),
				sec = $button.attr('data-timer');

			$('.breathing-container[data-breathe="'+bid+'"]').find('.breathing-circle').addClass('animate');
			$button.prop('disabled',true).addClass('disabled');
			$button.find('.text').text('Breathe... '+sec);

			let timer = setInterval(function() { 
				$button.find('.text').text('Breathe... '+--sec);
				if (sec == 0) {

					$button.removeClass('red').addClass('wine').find('.text').text('Thank you. Please continue.'); // Replace button with helpful text
					$('.breathing-container[data-breathe="'+bid+'"] .breathing-inner').addClass('fade-out').slideUp('slow'); // Hide the breathing exercise animation
					$('.worksheet-item[aria-hidden="true"]').attr('aria-hidden', 'false').slideDown('slow'); // Hide the breathing exercise animation						
					$button.parents('li.worksheet-item').next('li.worksheet-item').find('.textarea').focus(); // Focus on next question

					// Show submit button if available
					$('.submit-container[data-last-question="breathe"]').attr('aria-hidden', 'false').slideDown('slow');

					clearInterval(timer);
				} 
			}, 1000);

		});

	});

})( jQuery );
