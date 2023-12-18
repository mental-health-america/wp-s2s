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
 * Google SSO 
 */
function mha_sso_google( $redirect_query = null ){

    require_once 'vendor/autoload.php';

    $client_id = GOOGLE_CLIENT_ID;
    $client_secret = GOOGLE_CLIENT_SECRET;
    $redirect_uri = get_site_url(null, '/sso', 'https');
    if($redirect_query){
        $redirect_uri = add_query_arg( 'redirect_to', $redirect_query, $redirect_uri ); 
    }
    
    // Creating new google client instance
    $client = new Google_Client();
    $client->setClientId($client_id);
    $client->setClientSecret($client_secret);
    $client->setRedirectUri($redirect_uri);
    $client->addScope("email");
    $client->addScope("profile");
    $client->setPrompt("select_account");

    $google_sso_code = get_query_var('code');

    if($google_sso_code):
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        if(!isset($token["error"])){
            $client->setAccessToken($token['access_token']);
            $google_oauth = new Google_Service_Oauth2($client);
            $google_account_info = $google_oauth->userinfo->get();
        
            // User data
            $id = sanitize_text_field($google_account_info->id);
            $first_name = sanitize_text_field(trim($google_account_info->givenName));
            $last_name = sanitize_text_field(trim($google_account_info->familyName));
            $full_name = sanitize_text_field(trim($google_account_info->name));
            $email = sanitize_email($google_account_info->email);

            $exists = email_exists( $email );
            if($exists){
                // Existing user
                $user_id = $exists;
            } else {
                // New User
                $user_id = wp_create_user($email, wp_generate_password(), $email);
                
            }

            if ( !is_wp_error( $user_id ) ) {

                // Update user's information
                $user_data = wp_update_user([
                    'ID' => $user_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'display_name' => $first_name
                ]);

                // Update the hidden SSO field with the source
                $user_update_sso = update_field( 'sso', 'Google', 'user_'.$user_id ); 

                // Log the user in and redirect to the homepage
                echo '<p>Log in was successful, redirecting you to the <a href="'.get_home_url().'">homepage</a> now.</p>';
                wp_set_auth_cookie( $user_id, false, is_ssl() );
                wp_redirect( '/my-account/' );
                exit();

            }

        } else {
            // Error
            echo 'There was a problem with logging you in, please try again or if the issue persists please <a href="https://mhanational.org/get-involved/contact-us?ref=screening" target="_blank">contact us</a>.';
        }
        
    else: 
    ?>       
        <a type="button" class="login-with-google-btn button round-small-br small w-100 button-normal white text-blue" target="_self" href="<?php echo $client->createAuthUrl(); ?>">
            <i class="fa fa-google" aria-label="Google icon"></i>&nbsp; Sign in with Google
        </a>
    <?php endif;

}