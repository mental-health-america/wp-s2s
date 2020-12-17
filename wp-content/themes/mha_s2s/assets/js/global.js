(function( $ ) {
	
	new WOW().init(); 

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
			if(!a.test(this.href) && $(this).hasClass('social-share')){
				$(this).click(function(event) {
					event.preventDefault();
					event.stopPropagation();
					window.open(this.href, '_blank');
				});
			}
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


		// Reveal Buttons
		$('.reveal-button').on('click', function(event){
			event.preventDefault();			
			var id = $(this).attr('data-reveal');
			$(this).toggleClass('active');
			$('#'+id).toggleClass('reveal');
		});
		$('.reveal-slide-button').on('click', function(event){
			event.preventDefault();			
			var id = $(this).attr('data-reveal');
			$(this).toggleClass('active');
			$('#'+id).slideToggle(500, "easeOutBounce");

			if($(this).hasClass('active')){
				$('html, body').animate({
					scrollTop: $('#'+id).offset().top
				}, 1000);
			}
		});

		// Account Confirmation Message Check Display
		if($('#account-settings-form').length){
			if($('.gform_confirmation_wrapper').length){
				$('#account-settings-form').addClass('reveal');
			}
		}

		// Checkbox Limiter
		if($('.limit-3 .ginput_container_checkbox').length){			
			$.fn.limit = function(n) {
				var self = this;
				this.click(function(){ return (self.filter(":checked").length<=n); });
			}
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
		}

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
			var reveal = $(this).attr('data-reveal');
			$('#'+reveal).slideToggle('200', 'easeInOutQuad').toggleClass('show').parent('a').toggleClass('revealed');
			
			$(this).attr('aria-expanded', function (i, attr) {
				return attr == 'true' ? 'false' : 'true'
			});					
			$(this).toggleClass('revealed').text(function (i, attr) {
				return attr == '-' ? '+' : '-'
			});			
		});

	});

	/**
	 * Window Resize Functions
	 */
	$(window).resize(function() {
		
	});

})( jQuery );
