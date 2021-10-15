(function( $ ) {
	
	// Wow implementation
	new WOW().init(); 

	// Show/Hide filter on mobile
	function showFilters() {
		if($('.search-filters.form-container').length){
			var windowWidth = $(window).width();
			if(windowWidth < 768){
				$('.search-filters.form-container').removeClass('show');
			} else {
				$('.search-filters.form-container').addClass('show');
			}
		}
	}


	/**
	 * Document Ready Functions
	 */
	$(document).ready(function() {

		// Social Buttons
		$('a.social-share').click(function(e){
			e.preventDefault(); 
			var url = $(this).attr('href');
			window.open(url, '_blank', 'toolbar=yes, scrollbars=yes, resizable=no, top=200, left=200, width=570, height=400');
		});

		// Search Toggle
		$('#search-toggle').on('click', function(event){
			event.preventDefault();

			$("input#mha-search-form").focus();

			$(this).attr('aria-expanded', function (i, attr) {
				return attr == 'true' ? 'false' : 'true'
			});
			$('#search-header').toggleClass('show');
		});	

		// Log In Toggle
		$('#sign-in-toggle').on('click', function(event){
			event.preventDefault();			
			$(this).attr('aria-expanded', function (i, attr) {
				return attr == 'true' ? 'false' : 'true'
			});					
			$('strong', this).text(function (i, attr) {
				return attr == 'Close' ? 'Log In' : 'Close'
			});			
			$('#utility-menu').toggleClass('show-login-hover');
		});

		// External links open in a new tab
		$('#content a').each(function() {
			var a = new RegExp('/' + window.location.host + '/');
			if(!a.test(this.href) && !$(this).hasClass('social-share')){
				$(this).click(function(event) {
					event.preventDefault();
					event.stopPropagation();
					window.open(this.href, '_blank');
				});
			}
		});

		// Disable "heading" links in the menu
		$('li.heading > a').on('click', function(event) {
			event.preventDefault();
		});
		
		// Dropdown Menus
		$('.sf-menu').superfish({
			delay:         400,                         
			speed:         'fast',                          
			autoArrows:    false,        
			animation:     { opacity: 'show', left: '15px' },
			animationOut:  { opacity: 'hide', left: '-15px' }
		});

		// Hamburger Icon
		$('#mobile-menu-button').on('click',function(e){
			e.preventDefault();

			// Toggle the active class
			$('body').toggleClass('mobile-menu-active');

			// Aria toggles
			$(this).attr('aria-expanded', function (i, attr) {
				return attr == 'true' ? 'false' : 'true'
			});

			// Toggle the text
			var text = $('#mobile-menu-button .text').text();
			$('#mobile-menu-button .text').text(text == 'Menu' ? 'Close' : 'Menu');
		});


		// Anchor Jump Buttons
		$('.anchor-button').each(function(event){
			$(this).addEventListener('click', function(ev) {
				window.parent.postMessage(JSON.stringify({
					eventName: 'scroll',
					scrollY: window.scrollY,
					rect: expandingElement.getBoundingClientRect()
				}), '*');
			});
		})

		// Account Confirmation Message Check Display
		if($('#account-settings-form').length){
			if($('.gform_confirmation_wrapper').length){
				$('#account-settings-form').addClass('reveal');
			}
		}
		
		// Scrolling to reveal content when opened    
		$(document).on('shown.bs.collapse', function(event){

			// If .anchor-content scroll to the content
			// Useful for multiple stacking reveals
			if($(event.target).hasClass('anchor-content')){
				$('html, body').animate({
					scrollTop: $(event.target).offset().top
				}, 1000, 'easeInOutQuad');		
			}

			if($(event.target).hasClass('all-screen-results')){
				var id = $(event.target).attr('id');
				$('button[aria-controls="'+id+'"]').text('Show Fewer Results');
			}

			if($(event.target).attr('id') == 'allThoughts'){
				var id = $(event.target).attr('id');
				$('button[aria-controls="'+id+'"]').text('View Less Thoughts');
			}

		});

		$(document).on('hidden.bs.collapse', function(event){

			if($(event.target).hasClass('all-screen-results')){
				var id = $(event.target).attr('id');
				$('button[aria-controls="'+id+'"]').text('Show More Results');
			}

			if($(event.target).attr('id') == 'allThoughts'){
				var id = $(event.target).attr('id');
				$('button[aria-controls="'+id+'"]').text('View All Thoughts');
			}

		});

		// Checkbox Limiter
		$.fn.limit = function(n) {
			var self = this;
			this.click(function(){ return (self.filter(":checked").length<=n); });
		}
		/*
		$('.limit-1 .ginput_container_checkbox ul').each(function(e){
			var id = $(this).attr('id');
			$("ul#"+id+" li input:checkbox").limit(1);
		});
		*/
		$('.limit-2 .ginput_container_checkbox ul').each(function(e){
			var id = $(this).attr('id');
			$("ul#"+id+" li input:checkbox").limit(2);
		});
		$('.limit-3 .ginput_container_checkbox ul').each(function(e){
			var id = $(this).attr('id');
			$("ul#"+id+" li input:checkbox").limit(3);
		});
		$('.limit-4 .ginput_container_checkbox ul').each(function(e){
			var id = $(this).attr('id');
			$("ul#"+id+" li input:checkbox").limit(4);
		});
		$('.limit-5 .ginput_container_checkbox ul').each(function(e){
			var id = $(this).attr('id');
			$("ul#"+id+" li input:checkbox").limit(5);
		});

		// Checkbox single limiter (radio style hotfix)
		$('.limit-1 .ginput_container_checkbox input:checkbox').on('change', function(evt) {
			$(this).parents('.gfield_checkbox').find('input:checkbox').prop('checked', false);
			$(this).prop('checked', true);
		});

		// Animated form labels
		$(".float-label input").on("blur input focus", function() {
			var $field = $(this).closest(".float-label");
			if (this.value) {
				$field.addClass("filled");
			} else {
				$field.removeClass("filled");
			}
		});
		$(".float-label input").on("focus", function() {
			var $field = $(this).parents(".float-label");
			if (this) {
				$field.addClass("filled");
			} else {
				$field.removeClass("filled");
			}
		});
		$(".float-label input").each(function(e){
			if($(this).val()){
				$(this).parents(".float-label").addClass('filled');
			}
		});

		// Reveal button toggle
		$('.reveal-excerpt').on('click', function(event){
			event.preventDefault();

			// Close Others
			var $thisScreen = $(this).parents('.screen-item');
			$('.screen-item').not($thisScreen).each(function(e){
				$(this).find('.reveal-excerpt').not(this).removeClass('revealed').attr('aria-expanded', 'false').text('+');
				$(this).find('.excerpt.block').slideUp('200', 'easeInOutQuad').removeClass('show');
				$(this).find('.button').removeClass('revealed');
			});

			// Expand This One
			var reveal = $(this).attr('data-reveal');
			$('#'+reveal).slideToggle('200', 'easeInOutQuad').toggleClass('show').parent('a').toggleClass('revealed');
			
			$(this).attr('aria-expanded', function (i, attr) {
				return attr == 'true' ? 'false' : 'true'
			});					
			$(this).toggleClass('revealed').text(function (i, attr) {
				return attr == '-' ? '+' : '-'
			});			
		});

		// Filter Display
		showFilters();

		// User test results display toggler
		$('.show-test-group').on('click', function(event){
			event.preventDefault();
			var group = $(this).attr('data-group-control');
			var text = $(this).text();
			
			$('div[data-test-group]').addClass('loading');
			$('#testSelection span').text(text);
			setTimeout(function() {
				$('div[data-test-group]').addClass('hidden');
				$('div[data-test-group]').removeClass('loading');	
				$('div[data-test-group="'+group+'"]').removeClass('hidden');			
			}, 400);
		});

		// Bootstrap tooltips
		/*
		$('[data-toggle="tooltip"]').tooltip({
			options: 'click hover'
		});
		*/
		$('body').tooltip({
			selector: '[data-toggle=tooltip]',
			options: 'click hover'
		});

		// Sticky Sidebars		
		$("aside.article-right .sticky").stick_in_parent();


		/**
		 * Iframe mode options
		 */
		// Open links out of iframes
		$('.iframe-mode #page a').each(function(){
			$parent = $(this).parent();
			if(!$parent.hasClass('screen-item') && $(this).attr('id') != 'screen-take'){
				$(this).attr('target', '_blank');
			}
		});

		// Open form submissions in new window
		/*
		if($('body').hasClass('iframe-mode')){
			if($('.screen-progress-bar').hasClass('step-2-of-3')){
				// $('.iframe-mode form').attr('target', '_blank'); // Open results in new window
				// $('.single-screen form input[type="submit"]').attr('formtarget', '_blank');
			}
		}
		*/

		// Simple Masonry
		
		$('.cta-cols').each(function(event){
			if($(this).children('.block-cta').length > 1){
				var macyId = $(this).attr('id');
				$('#'+macyId).addClass('masonry');
				var macy = Macy({
					container: '#'+macyId,
					trueOrder: true,
					waitForImages: false,
					margin: 0,
					columns: 2,
					breakAt: {
						767: 1
					}
				});
			}
		});

	});


	/**
	 * Window Resize Functions
	 */
	$(window).resize(function() {
		
		// Filter Display
		showFilters();

	});

})( jQuery );
