/**
 * Language Help Bar — fixed bottom bar with dismiss + restore toggle.
 */
(function () {
	var STORAGE_KEY = 'language_help_bar_dismissed';

	function getBar() {
		return document.getElementById('language-help-bar');
	}

	function getToggle() {
		return document.getElementById('language-help-bar-toggle');
	}

	function isCookieBannerVisible() {
		var cookie = document.getElementById('cookie-consent-banner');
		if (!cookie) {
			return false;
		}
		return window.getComputedStyle(cookie).display !== 'none';
	}

	function getMobileMenuOffset() {
		var menu = document.getElementById('main-menu-buttons');
		if (!menu) {
			return 0;
		}

		var style = window.getComputedStyle(menu);
		if (style.position !== 'fixed' || style.display === 'none' || style.visibility === 'hidden') {
			return 0;
		}

		return Math.ceil(menu.getBoundingClientRect().height) + 10;
	}

	function getStackedBottomOffset() {
		var offset = getMobileMenuOffset() || 10;
		var cookie = document.getElementById('cookie-consent-banner');

		if (cookie && isCookieBannerVisible()) {
			offset += cookie.getBoundingClientRect().height + 10;
		}

		return offset;
	}

	function updatePosition() {
		var bar = getBar();
		var toggle = getToggle();
		var bottom = getStackedBottomOffset() + 'px';

		if (bar && window.getComputedStyle(bar).display !== 'none') {
			bar.style.bottom = bottom;
		}

		if (toggle && window.getComputedStyle(toggle).display !== 'none') {
			toggle.style.bottom = bottom;
		}
	}

	function setBarVisible(show) {
		var bar = getBar();
		var toggle = getToggle();

		if (bar) {
			bar.style.display = show ? 'block' : 'none';
		}

		if (toggle) {
			toggle.style.display = show ? 'none' : 'inline-block';
			toggle.setAttribute('aria-expanded', show ? 'true' : 'false');
		}

		if (show) {
			document.body.classList.add('language-help-bar-display');
		} else {
			document.body.classList.remove('language-help-bar-display');
		}

		updatePosition();
	}

	function initLanguageHelpBar() {
		var bar = getBar();
		if (!bar) {
			return;
		}

		var dismissed = localStorage.getItem(STORAGE_KEY) === '1';
		setBarVisible(!dismissed);

		var dismissButton = document.getElementById('language-help-bar-dismiss');
		if (dismissButton) {
			dismissButton.addEventListener('click', function () {
				localStorage.setItem(STORAGE_KEY, '1');
				setBarVisible(false);
			});
		}

		var toggle = getToggle();
		if (toggle) {
			toggle.addEventListener('click', function () {
				localStorage.removeItem(STORAGE_KEY);
				setBarVisible(true);
			});
		}

		['cookie-accept', 'cookie-deny'].forEach(function (id) {
			var button = document.getElementById(id);
			if (button) {
				button.addEventListener('click', updatePosition);
			}
		});

		window.addEventListener('resize', updatePosition);
	}

	window.mhaUpdateLanguageHelpBarPosition = updatePosition;

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initLanguageHelpBar);
	} else {
		initLanguageHelpBar();
	}
})();
