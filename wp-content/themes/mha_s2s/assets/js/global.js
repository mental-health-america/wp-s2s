(function( $ ) {
    
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

	});

	/**
	 * Window Resize Functions
	 */
	$(window).resize(function() {
		
	});

})( jQuery );
