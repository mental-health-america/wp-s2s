<?php

/**
 * REST API Endpoint to get a user's screen result data by their SID
 */

use MHA\ApiLogEntry;

// REST API Endpoint
add_action( 'rest_api_init', function () {

  // WordPress sends its own CORS headers for every REST route. Replace that handler so the
  // custom API can send stricter, API-specific headers, and hand every other route straight
  // back to core's handler so nothing else on the site changes.
  remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
  add_filter( 'rest_pre_serve_request', function ( $value, $result = null, $request = null ) {

    $route = ( $request instanceof WP_REST_Request ) ? $request->get_route() : '';

    if ( 0 !== strpos( $route, '/custom-api/v1/' ) ) {
      return rest_send_cors_headers( $value );
    }

    // This API is authenticated by an API key header, never by cookies, so credentialed
    // CORS is not needed and is not enabled. Browser access can be limited to specific
    // origins with the `mha_api_allowed_origins` filter; an empty list allows any origin.
    $allowed_origins = (array) apply_filters( 'mha_api_allowed_origins', array() );
    $origin          = get_http_origin();

    if ( empty( $allowed_origins ) ) {
      header( 'Access-Control-Allow-Origin: *' );
    } elseif ( $origin && in_array( $origin, $allowed_origins, true ) ) {
      header( 'Access-Control-Allow-Origin: ' . esc_url_raw( $origin ) );
      header( 'Vary: Origin', false );
    }

    header( 'Access-Control-Allow-Methods: GET, OPTIONS' );
    header( 'Access-Control-Allow-Headers: Content-Type, Accept, X-API-Key' );
    header( 'Access-Control-Max-Age: 600' );

    return $value;
  }, 10, 3 );

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
  $entry_id = mha_get_gf_entry_id_by_sid( $requested_sid );

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

/**
 * Stored API key hashes, as rows of option_name / option_value.
 *
 * @return array
 */
function mha_get_stored_api_keys() {
  global $wpdb;

  return (array) $wpdb->get_results(
    $wpdb->prepare(
      "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
      $wpdb->esc_like( '_api_key_' ) . '%'
    ),
    ARRAY_A
  );
}

// Failed key attempts allowed from one IP inside the window before it is locked out.
const MHA_API_MAX_FAILED_ATTEMPTS = 10;
const MHA_API_FAILED_ATTEMPT_WINDOW = 15 * MINUTE_IN_SECONDS;

function mha_authenticate_api_key( WP_REST_Request $request ) {

  $requesting_ip = mha_get_requesting_ip( $request );
  $failure_key   = 'mha_api_fail_' . md5( $requesting_ip );
  $failures      = (int) get_transient( $failure_key );

  // Locked out: answer without checking keys or writing a log row for every attempt.
  if ( $failures >= MHA_API_MAX_FAILED_ATTEMPTS ) {
    return new WP_Error(
      'api_key_throttled',
      __( 'Too many failed attempts. Try again later.' ),
      array( 'status' => 429 )
    );
  }

  $api_key = $request->get_header('x_api_key');

  if ( ! empty( $api_key ) ) {
    // Validate the API key.
    foreach ( mha_get_stored_api_keys() as $key ) {
      if ( password_verify( $api_key, $key['option_value'] ) ) {
        return true;
      }
    }
  }

  // Authentication Failure
  set_transient( $failure_key, $failures + 1, MHA_API_FAILED_ATTEMPT_WINDOW );

  $log_auth_failure = new ApiLogEntry(
    'missing-or-invalid',
    $requesting_ip,
    $request->get_param( 'sid' ),
    403,
    __( 'Missing or invalid API key.' ),
   // accessed_on,
   // id
  );

  $save_result = MHA\ApiLogger::save( $log_auth_failure );
  if ( is_wp_error( $save_result ) ) error_log( 'Error saving API log entry for key auth failure: '. $save_result->get_error_message() );

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
  foreach ( mha_get_stored_api_keys() as $key ) {
    if ( password_verify( $api_key, $key['option_value'] ) ) {
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
 * Address of the client making the request, for the audit log and for lockouts.
 *
 * Only REMOTE_ADDR is used. X-Forwarded-For and Client-IP are supplied by the caller, so
 * trusting them would let anyone dodge the lockout or write a false address into the log.
 *
 * @param  WP_REST_Request  $request
 *
 * @return string
 */
function mha_get_requesting_ip( WP_REST_Request $request ): string
{
  return mha_get_remote_ip();
}
