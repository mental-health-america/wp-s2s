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
 * JSON state payload for OAuth (optional same-site redirect_to).
 *
 * @param string|null $redirect_query Raw redirect_to from the app.
 * @return string JSON.
 */
function mha_sso_google_build_state_payload( $redirect_query ) {
	$state_vars = array();
	if ( $redirect_query ) {
		$safe = wp_validate_redirect( $redirect_query, false );
		if ( $safe ) {
			$state_vars['redirect_to'] = $safe;
		}
	}
	return wp_json_encode( $state_vars );
}

/**
 * Google authorization URL without loading Composer / Google_Client (button render only).
 *
 * @param string|null $redirect_query Optional redirect_to to embed in state.
 * @return string
 */
function mha_sso_google_authorization_url( $redirect_query = null ) {
	$params = array(
		'client_id'     => GOOGLE_CLIENT_ID,
		'redirect_uri'  => mha_sso_google_get_oauth_redirect_uri(),
		'response_type' => 'code',
		'scope'         => 'openid email profile',
		'state'         => mha_sso_google_build_state_payload( $redirect_query ),
		'prompt'        => 'select_account',
	);
	return add_query_arg( $params, 'https://accounts.google.com/o/oauth2/v2/auth' );
}

/**
 * Exchange code, create/update user, set cookie, redirect (loads Google SDK only on this path).
 *
 * @param string|null $redirect_query Unused; state comes from query string.
 */
function mha_sso_google_handle_oauth_callback( $redirect_query = null ) {
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

	$url_state = get_query_var( 'state' );
	if ( $url_state ) {
		$client->setState( stripslashes( $url_state ) );
	}

	$redirect_url = home_url( '/my-account/' );
	if ( $url_state ) {
		$state = json_decode( stripslashes( $url_state ) );
		if ( is_object( $state ) && isset( $state->redirect_to ) && is_string( $state->redirect_to ) ) {
			$redirect_url = wp_validate_redirect( $state->redirect_to, $redirect_url );
		}
	}

	$code  = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
	$token = $code ? $client->fetchAccessTokenWithAuthCode( $code ) : array( 'error' => 'missing_code' );

	if ( isset( $token['error'] ) ) {
		echo 'There was a problem with logging you in, please try again or if the issue persists please <a href="https://mhanational.org/get-involved/contact-us?ref=screening" target="_blank">contact us</a>.';
		return;
	}

	$client->setAccessToken( $token['access_token'] );
	$google_oauth        = new Google_Service_Oauth2( $client );
	$google_account_info = $google_oauth->userinfo->get();

	$first_name = sanitize_text_field( trim( (string) $google_account_info->givenName ) );
	$last_name  = sanitize_text_field( trim( (string) $google_account_info->familyName ) );
	$email      = sanitize_email( $google_account_info->email );

	$exists = email_exists( $email );
	if ( $exists ) {
		$user_id = $exists;
	} else {
		$user_id = wp_create_user( $email, wp_generate_password(), $email );
	}

	if ( is_wp_error( $user_id ) ) {
		echo 'There was a problem with logging you in, please try again or if the issue persists please <a href="https://mhanational.org/get-involved/contact-us?ref=screening" target="_blank">contact us</a>.';
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

	wp_set_auth_cookie( $user_id, false, is_ssl() );

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

	$auth_url = mha_sso_google_authorization_url( $redirect_query );
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
