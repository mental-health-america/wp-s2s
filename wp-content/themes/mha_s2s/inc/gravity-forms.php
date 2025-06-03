
<?php

/**
 * MHA + Columbia Research Project
 * Sends specific submission data to the Dexterous API
 */

// General Keys
include_once 'keys.php';

// Get Auth0 Token
function mha_get_auth0_token() {

    // Auth0 credentials
    $auth0_domain = AUTH0_DOMAIN;
    $client_id = AUTH0_CLIENT_ID;
    $client_secret = AUTH0_CLIENT_SECRET;
    $audience = AUTH0_AUDIENCE;

    // Auth0 token URL
    $url = "https://$auth0_domain/oauth/token";

    // Request body
    $body = [
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'audience' => $audience,
        'grant_type' => 'client_credentials',
    ];

    // Send request using WordPress HTTP API (or cURL if not using WP)
    $response = wp_remote_post($url, [
        'method'    => 'POST',
        'headers'   => ['Content-Type' => 'application/json'],
        'body'      => json_encode($body),
        'timeout'   => 15,
    ]);

    // Check for errors
    if (is_wp_error($response)) {
        return 'Error: ' . $response->get_error_message();
    }

    // Decode response
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    // Return the access token
    return $response_body['access_token'] ?? null;
}

// Pre form submission overrides
add_action( 'gform_pre_submission', 'mha_form_pre_submit_override_customizations' );
function mha_form_pre_submit_override_customizations( $form ) {

    /**
     * Before submitting the .digital-pathways-project form
     */
    if (isset($form['cssClass']) && strpos($form['cssClass'], 'digital-pathways-project') !== false) {
		$_POST['input_3'] = $_POST['input_3'].'_pull'; // Add "_pull" prefix to avoid interferring with test submission tokens
	}
}

// After form submission overrides
add_action( 'gform_after_submission', 'mha_form_post_submit_override_customizations', 10, 2 );
function mha_form_post_submit_override_customizations( $entry, $form ) {

    /**
	 * MHA + Columbia - Digital Pathways Submission
     * After submitting the .digital-pathways-project form
     */
    if (isset($form['cssClass']) && strpos($form['cssClass'], 'digital-pathways-project') !== false) {
        
		// Initial data
		$phone = rgar($entry, 1);
		$sid = str_replace( '_pull', '', rgar($entry, 3));

        $offhours = 'false';
		$offhours_field = GFAPI::get_field( $form, 2 );
        if($offhours_field){
            $offhours_value = $offhours_field->get_value_submission( array() );
            $offhours = $offhours_value['2.1'] == 1 ? 'true' : 'false';
        }

		// Get the test data
		global $wpdb;
		$user_screen_id = str_replace('_ref', '', $sid); // Remove _ref in case of chained forms
		$entry_id = $wpdb->get_var("SELECT entry_id FROM wp_gf_entry_meta WHERE meta_value = '$user_screen_id' ORDER BY id DESC LIMIT 1"); 
		$user_screen_result = mha_get_user_screen_results( $entry_id, false );

        GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT $entry => ' . print_r($entry, true) );
        GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT $sid => ' . print_r($sid, true) );
        GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT $entry_id => ' . print_r($entry_id, true) );
        GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT $user_screen_result => ' . print_r($user_screen_result, true) );

		// Submission data cleanup
		$transgender = (!empty($user_screen_result['answered_demos']['Please check this box if you identify as transgender.']) && !empty($user_screen_result['answered_demos']['Please check this box if you identify as transgender.'][0])) ? 'yes' : 'no';
		$gender = isset($user_screen_result['answered_demos']['Gender'][0]) ? strtolower($user_screen_result['answered_demos']['Gender'][0]) : '';
	
		$digital_pathways_payload = [
			"mobilenumber" => preg_replace('/\D/', '', $phone), // Required. 10 digits.
			"SID" => $sid, // Optional
			"age" => $user_screen_result['answered_demos']['Age Range'][0] ?? '', // Optional
			"householdincome" => $user_screen_result['answered_demos']['Household Income'][0] ?? '', // Optional
			"state" => $user_screen_result['answered_demos']['State'][0] ?? '', // Optional
			"zipcode" => $user_screen_result['answered_demos']['Zip/Postal Code'][0] ?? '', // Optional
			"gender" => $gender, // Optional
			"transgender" => $transgender, // Optional
			"sendwelcomesms" => "1", // Optional, defaults to "1"
			// "offhours" => $offhours, // Optional, defaults to "false"
			"userscore" => $user_screen_result['total_score'] // Optional
		];

        GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT $payload => ' . print_r($digital_pathways_payload, true) );
		
		// Connect to Columbia API
		$jwt_token = mha_get_auth0_token();

        GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT $jwt_token => ' . print_r($jwt_token, true) );

        $api_domain = DIGIPATH_DOMAIN;
		
		$url = $api_domain."/api/v1/user";
		$headers = [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $jwt_token
		];
		$args = [
			'method'    => 'POST',
			'headers'   => $headers,
			'body'      => json_encode($digital_pathways_payload),
			'timeout'   => 15,
		];
		$api_response = wp_remote_post($url, $args);

        GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT - $api_response => ' . print_r($api_response, true) );

		if ( !is_wp_error( $api_response ) ) {
			$response = wp_remote_retrieve_body( $api_response );
			$result = GFAPI::update_entry_field( rgar($entry, 'id'), '4', $response );
			GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT - Updating Entry for => ' . print_r(rgar($entry, 'id'), true) );
			GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT $result => ' . print_r($result, true) );
		}
		
    }

}

