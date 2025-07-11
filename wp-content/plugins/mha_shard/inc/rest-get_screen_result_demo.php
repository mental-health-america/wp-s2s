<?php

/**
 * REST API Endpoint to get a user's screen result data by their SID
 */


// These keys were generated in a local environment and should be replaced
// with your own during development/testing/concepting. Use the `mha_rest-apo-key-manager`
// to generate API keys and your WordPress account credentials--preferrably an application
// password instead of your login password.
const LOCAL_TEST_API_KEY = '2ewa7j0VszFkQ6mvartyWayesKFLrVMI';
const LOCAL_TEST_APP_PASS = 'E5x7 Y5JO WeIW fg0w Kj0D LW1o';

// REST API Endpoint
add_action('rest_api_init', function () {
    register_rest_route('custom-api/v1', '/get-sid-data/(?P<sid>[a-zA-Z0-9\-_]+)', [
        'methods' => 'GET',
        'callback' => 'mha_api_get_user_screen_result_by_sid',
        'permission_callback' => 'mha_api_permission_check',
        'args' => [
            'sid' => [
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field',
            ]
        ],
    ]);
});

// Get screen data by SID
function mha_api_get_user_screen_result_by_sid($request) {
    global $wpdb;

    $requested_sid = $request['sid']; // Get SID from URL parameter
    $user_screen_id = str_replace('_ref', '', $requested_sid); // Remove _ref in case of chained forms
    
    // Find the entry ID by searching for the SID in the meta table
    $entry_id = $wpdb->get_var($wpdb->prepare(
        "SELECT entry_id FROM {$wpdb->prefix}gf_entry_meta WHERE meta_value = %s ORDER BY id DESC LIMIT 1",
        $user_screen_id
    ));
    
    // No entry found
    if (!$entry_id) {
        return new WP_REST_Response(['error' => 'Entry not found for SID: ' . $requested_sid], 404);
    }
    
    // Get the user screen results
    $user_screen_result = mha_get_user_screen_results($entry_id, true);

    // Make sure there is no error
    if (is_wp_error($user_screen_result) || empty($user_screen_result)) {
        return new WP_REST_Response(['error' => 'Screen results not found for entry: ' . $entry_id], 404);
    }

    // Return only relevant data
    // @TODO: Only return demographic data for the result
    $result = [
        'sid' => $requested_sid,
        'answered_demos' => $user_screen_result['answered_demos'],
    ];

    return new WP_REST_Response($result, 200);
}

function mha_api_permission_check($request) {
    // Validate API Key
    $is_key_valid = mha_authenticate_api_key( $request );
    if ( is_wp_error( $is_key_valid ) ) {
      return $is_key_valid;

    } else {
      // Validate the WordPress user to check permissions
      $x_wp_auth = $request->get_header('x_wp_auth');
      error_log( 'x_wp_auth: ' . $x_wp_auth );

      if ( empty( $x_wp_auth ) ) {
        return new WP_Error(
          'wp_user_missing',
          __( 'Missing or invalid WordPress user credentials.' ),
          array( 'status' => 403 )
        );
      }

      list($username, $password) = explode(':', base64_decode( $x_wp_auth ) );
      $user = wp_authenticate( $username, $password );

      if ( is_wp_error( $user ) ) {
        return $user;
      } else {
        /** @var $user WP_User */
        // TODO: Perform any necessary permissions checking?
        error_log( 'Authenticated REST API user: ' . $user->user_email );
        return true;
      }
    }
}

function mha_authenticate_api_key( WP_REST_Request $request ) {

  $api_key = $request->get_header('x_api_key');
  error_log( 'request header x-api-key: ' . $api_key );

  if ( empty( $api_key ) ) {
    return new WP_Error(
      'api_key_missing',
      __( 'Missing or invalid API key.' ),
      array( 'status' => 403 )
    );
  }

  // Validate the API key.
  global $wpdb;
  $stored_keys = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE '_api_key_%'", ARRAY_A );
  error_log( 'Stored keys: ' . print_r( $stored_keys, true ) );

  foreach ( $stored_keys as $key ) {
    if ( password_verify( $api_key, $key['option_value'] ) ) {
      return true;
    }
  }

  return new WP_Error(
    'api_key_invalid',
    __( 'Invalid API key.', 'rest-api-key-authentication' ),
    array( 'status' => 403 )
  );

}