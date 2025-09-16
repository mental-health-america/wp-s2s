/**
 * Cookie Consent Banner
 */

// Check if banner should be shown
function shouldShowBanner() {
	var hasConsent = localStorage.getItem('cookie_consent') === 'accepted';
	var deniedUntil = localStorage.getItem('cookie_denied_until');
	
	if (hasConsent) return false;
	if (deniedUntil) {
		var deniedDate = new Date(parseInt(deniedUntil));
		return new Date() >= deniedDate;
	}
	return true;
}

// Show/hide banner
function toggleBanner(show) {
	var banner = document.getElementById('cookie-consent-banner');
	if (banner) {
		banner.style.display = show ? 'block' : 'none';
		if (show) {
			document.body.classList.add('cookie-banner-display');
		}
	}
}

// Grant consent and track
function grantConsent() {
	if (typeof gtag !== 'undefined') {
		gtag('consent', 'update', {
			'ad_storage': 'granted',
			'ad_personalization': 'granted',
			'ad_user_data': 'granted',
			'analytics_storage': 'granted',
			'personalization_storage': 'granted'
		});
		window.dataLayer.push({ event: 'consent_granted' });
	}
}

// Initialize on page load
function initiCookieConsentCheck() {
	if (localStorage.getItem('cookie_consent') === 'accepted') {
		grantConsent();
	}
	toggleBanner(shouldShowBanner());
}

// Event handlers
window.handleCookieAccept = function() {
	localStorage.setItem('cookie_consent', 'accepted');
	window.dataLayer.push({ event: 'consent_granted_click' });
	grantConsent();
	toggleBanner(false);
};

window.handleCookieDeny = function() {
	var weekFromNow = new Date();
	weekFromNow.setDate(weekFromNow.getDate() + 7);
	localStorage.setItem('cookie_denied_until', weekFromNow.getTime());
	toggleBanner(false);
};

// Initialize when ready
if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initiCookieConsentCheck);
} else {
	initiCookieConsentCheck();
}
