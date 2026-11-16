<?php
/**
 * Plugin Name: MHA - SSO Options
 * Plugin URI: https://screening.mhanational.org
 * Version: 1.0
 * Author:  MHA Web Team
 * Author URI: https://screening.mhanational.org
 * Description: Single sign-on options for MHA.
 */


// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//General Keys
include_once 'keys.php';

/**
 * OAuth redirect URI registered with Google (must match console config).
 *
 * @return string
 */
function mha_sso_google_get_oauth_redirect_uri() {
	return get_site_url( null, '/sso', 'https' );
}

/**
 * Name of the short-lived cookie that ties an OAuth round trip to the browser that started it.
 */
const MHA_SSO_STATE_COOKIE = 'mha_sso_state';

/**
 * Set or clear the OAuth state cookie.
 *
 * SameSite=Lax is required: Google returns the visitor to the site with a top-level GET
 * navigation, and Lax cookies are sent on those but not on cross-site POSTs or sub-requests.
 *
 * @param string $value   State value, or '' to clear it.
 * @param int    $expires Unix timestamp.
 */
function mha_sso_set_state_cookie( $value, $expires ) {
	if ( headers_sent() ) {
		return;
	}
	setcookie(
		MHA_SSO_STATE_COOKIE,
		$value,
		array(
			'expires'  => $expires,
			'path'     => '/',
			'domain'   => '',
			'secure'   => true,
			'httponly' => true,
			'samesite' => 'Lax',
		)
	);
}

/**
 * JSON state payload for OAuth: a one-time value that is checked on the way back, plus the
 * optional same-site redirect_to.
 *
 * @param string|null $redirect_query Raw redirect_to from the app.
 * @param string      $state_nonce    One-time value also stored in the state cookie.
 * @return string JSON.
 */
function mha_sso_google_build_state_payload( $redirect_query, $state_nonce ) {
	$state_vars = array( 'n' => $state_nonce );
	if ( $redirect_query ) {
		$safe = wp_validate_redirect( $redirect_query, false );
		if ( $safe ) {
			$state_vars['redirect_to'] = $safe;
		}
	}
	return wp_json_encode( $state_vars );
}

/**
 * Google authorization URL without loading Composer / Google_Client.
 *
 * @param string|null $redirect_query Optional redirect_to to embed in state.
 * @param string      $state_nonce    One-time value for the state.
 * @return string
 */
function mha_sso_google_authorization_url( $redirect_query, $state_nonce ) {
	$params = array(
		'client_id'     => GOOGLE_CLIENT_ID,
		'redirect_uri'  => mha_sso_google_get_oauth_redirect_uri(),
		'response_type' => 'code',
		'scope'         => 'openid email profile',
		'state'         => mha_sso_google_build_state_payload( $redirect_query, $state_nonce ),
		'prompt'        => 'select_account',
	);
	return add_query_arg( $params, 'https://accounts.google.com/o/oauth2/v2/auth' );
}

/**
 * URL the sign-in button points at. Visiting it starts the OAuth flow (see
 * mha_sso_maybe_start_oauth), so the one-time state is created when the button is clicked
 * rather than when the page is built. Pages with this button can be cached, and a cached page
 * would otherwise hand every visitor the same state.
 *
 * @param string|null $redirect_query Optional same-site redirect_to for after login.
 * @return string
 */
function mha_sso_google_start_url( $redirect_query = null ) {
	$args = array( 'mha_sso_start' => '1' );
	if ( $redirect_query ) {
		$safe = wp_validate_redirect( $redirect_query, false );
		if ( $safe ) {
			$args['redirect_to'] = $safe;
		}
	}
	return add_query_arg( $args, home_url( '/' ) );
}

/**
 * Start the OAuth flow: remember a fresh one-time value in a cookie and send the visitor to Google.
 */
function mha_sso_maybe_start_oauth() {
	// Read-only navigation that only redirects to Google; there is nothing to protect with a nonce.
	if ( empty( $_GET['mha_sso_start'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}

	nocache_headers();

	$redirect_to = isset( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$state_nonce = wp_generate_password( 32, false );

	mha_sso_set_state_cookie( $state_nonce, time() + ( 10 * MINUTE_IN_SECONDS ) );

	// Not wp_safe_redirect: the destination is Google, and it is built entirely server-side.
	wp_redirect( mha_sso_google_authorization_url( is_string( $redirect_to ) ? $redirect_to : null, $state_nonce ) ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
	exit();
}
add_action( 'init', 'mha_sso_maybe_start_oauth', 1 );

/**
 * Generic failure message shown for any problem during sign-in.
 */
function mha_sso_login_problem_message() {
	echo 'There was a problem with logging you in, please try again or if the issue persists please <a href="https://mhanational.org/get-involved/contact-us?ref=screening" target="_blank" rel="noopener">contact us</a>.';
}

/**
 * Exchange code, create/update user, set cookie, redirect (loads Google SDK only on this path).
 *
 * @param string|null $redirect_query Unused; state comes from query string.
 */
function mha_sso_google_handle_oauth_callback( $redirect_query = null ) {

	// The state must come back exactly as this browser sent it out. Without this check anyone
	// could hand a victim a link carrying the attacker's authorization code and sign the victim
	// in to the attacker's account (login CSRF).
	$url_state    = get_query_var( 'state' );
	$state_nonce  = '';
	$state_object = $url_state ? json_decode( stripslashes( $url_state ) ) : null;
	if ( is_object( $state_object ) && isset( $state_object->n ) && is_string( $state_object->n ) ) {
		$state_nonce = $state_object->n;
	}
	$cookie_nonce = isset( $_COOKIE[ MHA_SSO_STATE_COOKIE ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ MHA_SSO_STATE_COOKIE ] ) ) : '';

	// One use only.
	mha_sso_set_state_cookie( '', time() - HOUR_IN_SECONDS );

	if ( '' === $state_nonce || '' === $cookie_nonce || ! hash_equals( $cookie_nonce, $state_nonce ) ) {
		mha_sso_login_problem_message();
		return;
	}

	require_once __DIR__ . '/vendor/autoload.php';

	$client_id     = GOOGLE_CLIENT_ID;
	$client_secret = GOOGLE_CLIENT_SECRET;
	$redirect_uri  = mha_sso_google_get_oauth_redirect_uri();

	$client = new Google_Client();
	$client->setClientId( $client_id );
	$client->setClientSecret( $client_secret );
	$client->setRedirectUri( $redirect_uri );
	$client->addScope( 'openid' );
	$client->addScope( 'email' );
	$client->addScope( 'profile' );
	$client->setPrompt( 'select_account' );

	if ( $url_state ) {
		$client->setState( stripslashes( $url_state ) );
	}

	$redirect_url = home_url( '/my-account/' );
	if ( is_object( $state_object ) && isset( $state_object->redirect_to ) && is_string( $state_object->redirect_to ) ) {
		$redirect_url = wp_validate_redirect( $state_object->redirect_to, $redirect_url );
	}

	$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
	$token = $code ? $client->fetchAccessTokenWithAuthCode( $code ) : array( 'error' => 'missing_code' );

	if ( isset( $token['error'] ) ) {
		mha_sso_login_problem_message();
		return;
	}

	$client->setAccessToken( $token['access_token'] );
	$google_oauth        = new Google_Service_Oauth2( $client );
	$google_account_info = $google_oauth->userinfo->get();

	$first_name = sanitize_text_field( trim( (string) $google_account_info->givenName ) );
	$last_name  = sanitize_text_field( trim( (string) $google_account_info->familyName ) );
	$email      = sanitize_email( $google_account_info->email );

	// Only trust an email address Google has verified. Without that, whoever controls an
	// unverified address could sign in to the existing account that uses it.
	if ( ! $email || empty( $google_account_info->verifiedEmail ) ) {
		mha_sso_login_problem_message();
		return;
	}

	$exists = email_exists( $email );
	if ( $exists ) {
		$user_id = $exists;

		// Administrators sign in with their password. Signing one in through a matching Google
		// email is an account-takeover path if that mailbox is ever compromised or recycled.
		// Return false from this filter to allow administrators to use Google sign-in.
		$existing_user = get_userdata( $exists );
		if ( $existing_user && user_can( $existing_user, 'manage_options' ) && apply_filters( 'mha_sso_block_administrators', true, $existing_user ) ) {
			mha_sso_login_problem_message();
			return;
		}
	} else {
		$user_id = wp_create_user( $email, wp_generate_password(), $email );
	}

	if ( is_wp_error( $user_id ) ) {
		mha_sso_login_problem_message();
		return;
	}

	wp_update_user(
		array(
			'ID'           => $user_id,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			'display_name' => $first_name,
		)
	);

	update_field( 'sso', 'Google', 'user_' . $user_id );

	$signed_in_user = get_userdata( $user_id );
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, false, is_ssl() );
	if ( $signed_in_user ) {
		// Lets login history and other login hooks see Google sign-ins like any other login.
		do_action( 'wp_login', $signed_in_user->user_login, $signed_in_user );
	}

	wp_safe_redirect( $redirect_url );
	exit();
}

/**
 * Google SSO: render sign-in button, or run OAuth callback when `code` is present.
 *
 * @param string|null $redirect_query Optional same-site redirect_to for post-login (stored in OAuth state).
 */
function mha_sso_google( $redirect_query = null ) {

	$google_sso_code = get_query_var( 'code' );
	if ( $google_sso_code ) {
		mha_sso_google_handle_oauth_callback( $redirect_query );
		return;
	}

	$auth_url = mha_sso_google_start_url( $redirect_query );
	?>
		<a type="button" class="login-with-google-btn button round-small-br small w-100 button-normal white text-blue" target="_self" href="<?php echo esc_url( $auth_url ); ?>">
			<i class="fa fa-google" aria-label="Google icon"></i>&nbsp; Sign in with Google
		</a>
	<?php
}

/**
 * Hide SSO field from front end for non-admins
 */
function mhaSso_hide_field( $field ) {
	if ( ! current_user_can( 'administrator' ) ) {
		return false;
	}
	return $field;
}
add_filter( 'acf/prepare_field/key=field_64e4dbe781a59', 'mhaSso_hide_field' );
