<?php

/**
 * REST API Endpoint to get a user's screen result data by their SID
 */

use MHA\ApiLogEntry;

// REST API Endpoint
add_action( 'rest_api_init', function () {
  // Remove the default filter and add headers as custom; fix CORS
  remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
  add_filter( 'rest_pre_serve_request', function ( $value ) {
    header( 'Access-Control-Allow-Origin: *' );
    header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
    header( 'Access-Control-Allow-Credentials: true' );
    header( 'Access-Control-Allow-Headers: *' );

    return $value;
  } );

  register_rest_route( 'custom-api/v1', '/get-sid-data/(?P<sid>[a-zA-Z0-9\-_]+)', [
    'methods'             => WP_REST_Server::READABLE,
    'callback'            => 'mha_api_get_user_screen_result_by_sid',
    'permission_callback' => 'mha_authenticate_api_key',
    'args'                => [
      'sid' => [
        'required'          => true,
        'sanitize_callback' => 'sanitize_text_field',
      ],
    ],
  ] );
} );

// Get screen data by SID
function mha_api_get_user_screen_result_by_sid( WP_REST_Request $request )
{
  global $wpdb;

  $requesting_ip      = mha_get_requesting_ip( $request );
  $requesting_api_key = $request->get_header( 'x_api_key' );
  $requesting_api_key_label = mha_get_api_key_label( $requesting_api_key );
  $requested_sid      = $request->get_param( 'sid' ); // Get SID from URL parameter
  $user_screen_id     = str_replace( '_ref', '', $requested_sid ); // Remove _ref in case of chained forms

  // Find the entry ID by searching for the SID in the meta table
  $entry_id = $wpdb->get_var( $wpdb->prepare(
    "SELECT entry_id FROM {$wpdb->prefix}gf_entry_meta WHERE meta_value = %s ORDER BY id DESC LIMIT 1",
    $user_screen_id
  ) );

  $_log_entry = new ApiLogEntry(
    $requesting_api_key_label,
    $requesting_ip,
    $requested_sid,
    100,
    null
  );

  // No entry found
  if ( empty( $entry_id ) ) {
    $log_get_entry_failure = clone $_log_entry;
    $log_get_entry_failure->http_response_code = 404;
    $log_get_entry_failure->http_response_message = 'Entry not found for SID: ' . $requested_sid;
    $log_get_entry_failure->updateTimestamp();

    MHA\ApiLogger::save( $log_get_entry_failure );

    return new WP_REST_Response(
      [ 'error' => $log_get_entry_failure->http_response_message ],
      $log_get_entry_failure->http_response_code
    );
  }

  // Get the user screen results
  $user_screen_result = mha_get_user_screen_results( $entry_id, true );
  $user_data_return = [];

  // Make sure there is no error
  if ( is_wp_error( $user_screen_result ) || empty( $user_screen_result ) ) {
    $log_get_user_screen_results_error = clone $_log_entry;
    $log_get_user_screen_results_error->http_response_code = 404;
    $log_get_user_screen_results_error->http_response_message = 'Screen results not found for entry: ' . $entry_id;
    $log_get_user_screen_results_error->updateTimestamp();

    MHA\ApiLogger::save( $log_get_user_screen_results_error );

    return new WP_REST_Response( [ 'error' => 'Screen results not found for entry: ' . $entry_id ], 404 );
  }

  
  // Get the form structure to identify demographic questions
  $entry = GFAPI::get_entry( $entry_id );
  //$form = GFAPI::get_form( $entry['form_id'] );
  
  // Return only relevant data
  $user_data_return = [
    'SID' => $requested_sid,
    //'screen' => $form['title'],
    'userscore' => $user_screen_result['total_score'],
    "age" => $user_screen_result['answered_demos']['Age Range'][0] ?? '',
    "householdincome" => $user_screen_result['answered_demos']['Household Income'][0] ?? '',
    "state" => $user_screen_result['answered_demos']['State'][0] ?? '',
    "zipcode" => $user_screen_result['answered_demos']['Zip/Postal Code'][0] ?? '',
    "gender" => isset($user_screen_result['answered_demos']['Gender'][0]) ? strtolower($user_screen_result['answered_demos']['Gender'][0]) : '',
    "transgender" => (!empty($user_screen_result['answered_demos']['Please check this box if you identify as transgender.']) && !empty($user_screen_result['answered_demos']['Please check this box if you identify as transgender.'][0])) ? 'yes' : 'no',
  ];

  // Log success
  $log_success = clone $_log_entry;
  $log_success->http_response_code = 200;
  $log_success->http_response_message = 'Returned user screen results for SID: ' . $requested_sid;
  $log_success->updateTimestamp();

  MHA\ApiLogger::save( $log_success );
  return new WP_REST_Response( $user_data_return, 200 );
}

function mha_api_permission_check( WP_REST_Request $request )
{
  // Validate API Key
  $is_key_valid = mha_authenticate_api_key( $request );

  if ( is_wp_error( $is_key_valid ) ) {
    return $is_key_valid;
  } else {
    // Validate the WordPress user to check permissions
    $x_wp_auth = $request->get_header( 'x_wp_auth' );
    // error_log( 'x_wp_auth: ' . $x_wp_auth );

    if ( empty( $x_wp_auth ) ) {
      return new WP_Error(
        'wp_user_missing',
        __( 'Missing or invalid WordPress user credentials.' ),
        [ 'status' => 403 ]
      );
    }

    // error_log( 'Authenticating WP user in REST API request...');
    [ $username, $password ] = explode( ':', base64_decode( $x_wp_auth ) );

    // Check if Application Passwords are enabled and use those, otherwise
    // expect regular login passwords
    $user = ( WP_Application_Passwords::is_in_use() )
      ? wp_authenticate_application_password( null, $username, $password )
      : wp_authenticate( $username, $password );

    // error_log( 'Authenticated REST API user: ' . print_r( $user, true ) );

    if ( is_wp_error( $user ) || is_null ( $user ) ) {

      return new WP_Error(
        'wp_user_invalid',
        __( 'Invalid WordPress user credentials.' ),
        [ 'status' => 403 ]
      );


    } else {
      /** @var $user WP_User */
      // TODO: Perform any necessary permissions checking?
      // error_log( 'Authenticated REST API user: ' . $user->user_email );
      return true;
    }
  }
}

function mha_authenticate_api_key( WP_REST_Request $request ) {

  $api_key = $request->get_header('x_api_key');
  // error_log( 'request header x-api-key: ' . $api_key );

  if ( ! empty( $api_key ) ) {
    // Validate the API key.
    global $wpdb;
    $stored_keys = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE '_api_key_%'", ARRAY_A );
    // error_log( 'Stored keys: ' . print_r( $stored_keys, true ) );

    foreach ( $stored_keys as $key ) {
      if ( password_verify( $api_key, $key['option_value'] ) ) {
        return true;
      }
    }
  }

  // Authentication Failure
  $log_auth_failure = new ApiLogEntry(
    'missing-or-invalid',
    mha_get_requesting_ip( $request ),
    $request->get_param( 'sid' ),
    403,
    __( 'Missing or invalid API key.' ),
    // accessed_on,
    // id
  );

//  if ( get_option( 'mha_api_log_failed_api_key_auth' ) ):
    $save_result = MHA\ApiLogger::save( $log_auth_failure );
    if ( is_wp_error( $save_result ) ) error_log( 'Error saving API log entry for key auth failure: '. $save_result->get_error_message() );
//  endif;

  return new WP_Error(
    'api_key_missing_or_invalid',
    $log_auth_failure->http_response_message,
    array( 'status' => $log_auth_failure->http_response_code )
  );

}

/**
 * @param  string  $api_key
 *
 * @return string|WP_Error
 */
function mha_get_api_key_label( string $api_key ): WP_Error|string
{
  global $wpdb;
  $stored_keys = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE '_api_key_%'", ARRAY_A );

  foreach ( $stored_keys as $key ) {
    if ( password_verify( $api_key, $key['option_value'] ) ) {
      // error_log( 'Found key: ' . print_r( $key, true ) );
      return substr( $key['option_name'], 9 );
    }
  }

  return new WP_Error(
    'api_key_invalid',
    __( 'Invalid API key.', 'rest-api-key-authentication' ),
    array( 'status' => 403 )
  );
}

/**
 * @param  WP_REST_Request  $request
 *
 * @return mixed
 */
function mha_get_requesting_ip( WP_REST_Request $request ): mixed
{
  $headers = $request->get_headers();

  // Check for IP in X-Forwarded-For header
  if (!empty($headers['x_forwarded_for'])) {
    // Get the first IP in case of multiple forwarded IPs
    $ips = explode(',', $headers['x_forwarded_for'][0]);
    return trim($ips[0]);
  }

  // Check for Client IP header
  if (!empty($headers['client_ip'])) {
    return $headers['client_ip'][0];
  }

  // Fall back to HTTP_CLIENT_IP or REMOTE_ADDR
  return $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
}