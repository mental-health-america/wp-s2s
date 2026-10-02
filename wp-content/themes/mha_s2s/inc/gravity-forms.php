<?php

/**
 * MHA + Columbia Research Project
 * Sends specific submission data to the Dexterous API
 */

// General Keys
include_once 'keys.php';

/**
 * Digital Pathways step logging.
 * Writes to the Gravity Forms debug log and the PHP error log, so steps are
 * visible even when Gravity Forms logging is turned off.
 */
function mha_dpp_log( $message ) {
    global $mha_dpp_started;
    $elapsed = $mha_dpp_started ? sprintf( ' [+%.2fs]', microtime( true ) - $mha_dpp_started ) : '';
    $line = 'DIGITAL PATHWAYS PROJECT' . $elapsed . ' - ' . $message;
    if ( class_exists( 'GFCommon' ) ) {
        GFCommon::log_debug( $line );
    }
    error_log( $line );
}

function mha_dpp_step( $step ) {
    global $mha_dpp_step;
    $mha_dpp_step = $step;
    mha_dpp_log( 'STEP: ' . $step );
}

function mha_dpp_remember_error( $message ) {
    $GLOBALS['mha_dpp_last_error'] = $message;
    mha_dpp_log( $message );
}

/**
 * Columbia responses that mean the submission was accepted.
 * Anything else stored in the status field is treated as an error.
 */
function mha_dpp_success_messages() {
    return array(
        'Data saved successfully',
        'Duplicate submission processed successfully',
    );
}

function mha_dpp_status_message( $value ) {
    $value = trim( (string) $value );
    $decoded = json_decode( $value, true );
    if ( ! is_array( $decoded ) && preg_match( '/\{.*\}/s', $value, $matches ) ) {
        $decoded = json_decode( $matches[0], true );
    }
    if ( is_array( $decoded ) && isset( $decoded['message'] ) ) {
        return trim( (string) $decoded['message'] );
    }
    return '';
}

function mha_dpp_stored_value_is_error( $value ) {
    $value = trim( (string) $value );
    if ( $value === '' ) {
        return false;
    }

    $message = mha_dpp_status_message( $value );
    return ! in_array( $message, mha_dpp_success_messages(), true );
}

/**
 * A 200 can still be a failure. Only the two known success messages are ignored.
 */
function mha_dpp_response_is_error( $status, $body ) {
    if ( $status >= 400 || $status === 0 ) {
        return true;
    }

    return mha_dpp_stored_value_is_error( $body );
}

function mha_dpp_notify_error( $entry, $error ) {
    $entry_id = rgar( $entry, 'id' );
    $form_id  = rgar( $entry, 'form_id' );
    $sid      = str_replace( '_pull', '', (string) rgar( $entry, 3 ) );
    $entry_url = admin_url( 'admin.php?page=gf_entries&view=entry&id=' . absint( $form_id ) . '&lid=' . absint( $entry_id ) );
    $error = trim( wp_strip_all_tags( (string) $error ) );
    if ( strlen( $error ) > 2000 ) {
        $error = substr( $error, 0, 2000 ) . '…';
    }

    $sent = wp_mail(
        'justin@cplusk.com',
        sprintf( 'Digital Pathways submission error (entry %s)', $entry_id ),
        "A Digital Pathways form submission failed.\n\n"
        . "Entry: {$entry_id}\n"
        . "Form: {$form_id}\n"
        . "SID: {$sid}\n"
        . "View entry: {$entry_url}\n\n"
        . "Error:\n{$error}\n"
    );

    mha_dpp_log( $sent ? 'Error email sent' : 'Error email failed to send' );
    return $sent;
}

function mha_dpp_send_test_email() {
    $user = wp_get_current_user();
    $sent = wp_mail(
        'justin@cplusk.com',
        'Digital Pathways test email',
        "This is a test of the Digital Pathways error email.\n\n"
        . 'Sent at ' . wp_date( 'M j, Y g:i a' ) . "\n"
        . 'Sent by ' . ( $user->user_login ?: 'unknown' ) . "\n"
    );

    mha_dpp_log( $sent ? 'Test email sent' : 'Test email failed to send' );
    return $sent;
}

// Catches fatals and timeouts that kill the request before the confirmation is sent.
function mha_dpp_shutdown() {
    global $mha_dpp_step;
    $error = error_get_last();
    if ( $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ], true ) ) {
        $message = sprintf( 'FATAL during step "%s": %s in %s:%d', $mha_dpp_step, $error['message'], $error['file'], $error['line'] );
        mha_dpp_log( $message );
        if ( ! empty( $GLOBALS['mha_dpp_entry'] ) ) {
            mha_dpp_notify_error( $GLOBALS['mha_dpp_entry'], $message );
        }
    } else {
        mha_dpp_log( sprintf( 'Request ended. Last step: "%s"', $mha_dpp_step ) );
    }
}

// Get Auth0 Token
function mha_get_auth0_token() {

    foreach ( [ 'AUTH0_DOMAIN', 'AUTH0_CLIENT_ID', 'AUTH0_CLIENT_SECRET', 'AUTH0_AUDIENCE' ] as $constant ) {
        if ( ! defined( $constant ) ) {
            mha_dpp_remember_error( 'Missing constant ' . $constant . ' (is keys.php present on this server?)' );
            return null;
        }
    }

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
    $request_start = microtime( true );
    $response = wp_remote_post($url, [
        'method'    => 'POST',
        'headers'   => ['Content-Type' => 'application/json'],
        'body'      => json_encode($body),
        'timeout'   => 15,
    ]);
    mha_dpp_log( sprintf( 'Auth0 request took %.2fs', microtime( true ) - $request_start ) );

    // Check for errors
    if (is_wp_error($response)) {
        mha_dpp_remember_error( 'Auth0 token request failed => ' . $response->get_error_message() );
        return null;
    }

    // Decode response
    $status = (int) wp_remote_retrieve_response_code($response);
    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    // A rejected grant (bad secret, revoked client, wrong audience) answers with an
    // error payload instead of a token, so treat a missing token as a hard failure.
    if (empty($response_body['access_token'])) {
        mha_dpp_remember_error( sprintf(
            'Auth0 returned no access token (HTTP %d) => %s: %s',
            $status,
            $response_body['error'] ?? 'unknown_error',
            $response_body['error_description'] ?? wp_remote_retrieve_body($response)
        ) );
        return null;
    }

    // Return the access token
    return $response_body['access_token'];
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

        global $mha_dpp_started;
        $mha_dpp_started = microtime( true );
        $GLOBALS['mha_dpp_entry'] = $entry;
        register_shutdown_function( 'mha_dpp_shutdown' );
        mha_dpp_step( 'start (entry ' . rgar( $entry, 'id' ) . ', max_execution_time ' . ini_get( 'max_execution_time' ) . ')' );

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
		mha_dpp_step( 'lookup screening entry' );
		$entry_id = mha_get_gf_entry_id_by_sid( $sid );
		mha_dpp_step( 'score screening entry ' . $entry_id );
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
		mha_dpp_step( 'request Auth0 token' );
		$jwt_token = mha_get_auth0_token();

        // Don't log the token itself; it's a live credential and these logs are kept on disk.
        GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT $jwt_token => ' . ( $jwt_token ? 'obtained' : 'MISSING' ) );

        // Without a token the API answers {"message":"Unauthorized"}, which reads as a
        // Columbia-side rejection. Record the real cause instead.
        if ( ! $jwt_token ) {
            $token_error = $GLOBALS['mha_dpp_last_error'] ?? 'Error: could not obtain Auth0 access token';
            GFAPI::update_entry_field( rgar($entry, 'id'), '4', $token_error );
            mha_dpp_notify_error( $entry, $token_error );
            mha_dpp_step( 'done (no token)' );
            return;
        }

        if ( ! defined( 'DIGIPATH_DOMAIN' ) ) {
            $domain_error = 'Error: DIGIPATH_DOMAIN not defined';
            mha_dpp_log( 'Missing constant DIGIPATH_DOMAIN (is keys.php present on this server?)' );
            GFAPI::update_entry_field( rgar($entry, 'id'), '4', $domain_error );
            mha_dpp_notify_error( $entry, $domain_error );
            mha_dpp_step( 'done (no API domain)' );
            return;
        }

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
		mha_dpp_step( 'POST to Digital Pathways API' );
		$api_response = wp_remote_post($url, $args);
		mha_dpp_step( 'API responded' );

        GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT - $api_response => ' . print_r($api_response, true) );

		if ( is_wp_error( $api_response ) ) {
			$api_error = 'Error: ' . $api_response->get_error_message();
			GFAPI::update_entry_field( rgar($entry, 'id'), '4', $api_error );
			GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT - API request failed => ' . $api_response->get_error_message() );
			mha_dpp_notify_error( $entry, $api_error );
		} else {
			$status = (int) wp_remote_retrieve_response_code( $api_response );
			$response = wp_remote_retrieve_body( $api_response );

			// Prefix rejections with the status so the entry shows a 401 apart from a 403 or 500.
			$stored = $status >= 400 ? 'HTTP ' . $status . ' ' . $response : $response;

			$result = GFAPI::update_entry_field( rgar($entry, 'id'), '4', $stored );
			GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT - Updating Entry for => ' . print_r(rgar($entry, 'id'), true) );
			GFCommon::log_debug( 'DIGITAL PATHWAYS PROJECT $result => ' . print_r($result, true) );

			if ( mha_dpp_response_is_error( $status, $response ) ) {
				mha_dpp_notify_error( $entry, $stored );
			}
		}

		mha_dpp_step( 'done' );
		
    }

}

/**
 * Forms that feed Digital Pathways: CSS class digital-pathways-project,
 * or a title containing "Digital Pathways".
 */
function mha_dpp_form_ids() {
    global $wpdb;

    if ( ! class_exists( 'GFFormsModel' ) ) {
        return array();
    }

    $form_table = GFFormsModel::get_form_table_name();
    $meta_table = GFFormsModel::get_meta_table_name();
    $class_like = '%' . $wpdb->esc_like( 'digital-pathways-project' ) . '%';
    $name_like  = '%' . $wpdb->esc_like( 'Digital Pathways' ) . '%';

    $ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT DISTINCT f.id
         FROM {$form_table} f
         LEFT JOIN {$meta_table} m ON m.form_id = f.id
         WHERE f.is_trash = 0
           AND ( f.title LIKE %s OR m.display_meta LIKE %s )",
        $name_like,
        $class_like
    ) );

    return array_values( array_filter( array_map( 'intval', (array) $ids ) ) );
}

function mha_dpp_can_view_errors() {
    if ( current_user_can( 'manage_options' ) ) {
        return true;
    }
    return class_exists( 'GFCommon' ) && GFCommon::current_user_can_any( 'gravityforms_view_entries' );
}

add_action( 'admin_init', 'mha_dpp_handle_test_email' );
function mha_dpp_handle_test_email() {
    if ( empty( $_GET['mha_dpp_test_email'] ) || ! mha_dpp_can_view_errors() ) {
        return;
    }

    check_admin_referer( 'mha_dpp_test_email' );
    $sent = mha_dpp_send_test_email();
    set_transient( 'mha_dpp_test_email_' . get_current_user_id(), $sent ? 'sent' : 'failed', MINUTE_IN_SECONDS );
    wp_safe_redirect( admin_url( 'index.php' ) );
    exit;
}

function mha_dpp_render_test_email_controls() {
    $notice = get_transient( 'mha_dpp_test_email_' . get_current_user_id() );
    if ( $notice ) {
        delete_transient( 'mha_dpp_test_email_' . get_current_user_id() );
        if ( $notice === 'sent' ) {
            echo '<p>Test email sent to justin@cplusk.com.</p>';
        } else {
            echo '<p>Test email failed to send. Check the site mail setup.</p>';
        }
    }

    $url = wp_nonce_url( add_query_arg( 'mha_dpp_test_email', '1', admin_url( 'index.php' ) ), 'mha_dpp_test_email' );
    echo '<p><a href="' . esc_url( $url ) . '">Send test email</a></p>';
}

add_action( 'wp_dashboard_setup', 'mha_dpp_register_dashboard_widget' );
function mha_dpp_register_dashboard_widget() {
    if ( ! mha_dpp_can_view_errors() ) {
        return;
    }

    wp_add_dashboard_widget(
        'mha_dpp_errors',
        'Digital Pathways errors',
        'mha_dpp_render_dashboard_widget'
    );
}

function mha_dpp_entries_date_url( $form_id, $date ) {
    return admin_url( 'admin.php?' . http_build_query( array(
        'page'      => 'gf_entries',
        'view'      => 'entries',
        'id'        => absint( $form_id ),
        'orderby'   => '0',
        'order'     => 'ASC',
        's'         => $date,
        'field_id'  => 'date_created',
        'operator'  => 'is',
    ) ) );
}

function mha_dpp_render_dashboard_widget() {
    global $wpdb;

    $form_ids = mha_dpp_form_ids();
    if ( ! $form_ids || ! class_exists( 'GFAPI' ) ) {
        echo '<p>No Digital Pathways forms could be found.</p>';
        //mha_dpp_render_test_email_controls();
        return;
    }

    $timezone = wp_timezone();
    $start = ( new DateTimeImmutable( 'today', $timezone ) )->modify( '-29 days' );
    $counts = array();
    $labels = array();
    $form_ids_by_day = array();
    for ( $i = 0; $i < 30; $i++ ) {
        $day = $start->modify( '+' . $i . ' days' );
        $key = $day->format( 'Y-m-d' );
        $counts[ $key ] = 0;
        $labels[ $key ] = wp_date( 'M j', $day->getTimestamp() );
    }

    $search_start = $start->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
    $entry_table = GFFormsModel::get_entry_table_name();
    $meta_table  = GFFormsModel::get_entry_meta_table_name();
    $success_saved = '%' . $wpdb->esc_like( 'Data saved successfully' ) . '%';
    $success_duplicate = '%' . $wpdb->esc_like( 'Duplicate submission processed successfully' ) . '%';

    $id_placeholders = implode( ',', array_fill( 0, count( $form_ids ), '%d' ) );
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT e.id, e.form_id, e.date_created, m.meta_value
         FROM {$entry_table} e
         INNER JOIN {$meta_table} m ON m.entry_id = e.id AND m.meta_key = '4'
         WHERE e.form_id IN ({$id_placeholders})
           AND e.status = 'active'
           AND e.date_created >= %s
           AND m.meta_value <> ''
           AND m.meta_value NOT LIKE %s
           AND m.meta_value NOT LIKE %s
         ORDER BY e.date_created DESC",
        ...array_merge( $form_ids, array( $search_start, $success_saved, $success_duplicate ) )
    ) );

    $recent = array();
    if ( is_array( $rows ) ) {
        foreach ( $rows as $row ) {
            if ( empty( $row->date_created ) || ! mha_dpp_stored_value_is_error( $row->meta_value ) ) {
                continue;
            }
            $created = ( new DateTimeImmutable( $row->date_created, new DateTimeZone( 'UTC' ) ) )->setTimezone( $timezone );
            $key = $created->format( 'Y-m-d' );
            if ( isset( $counts[ $key ] ) ) {
                $counts[ $key ]++;
                $form_ids_by_day[ $key ][ (int) $row->form_id ] = true;
            }
            if ( count( $recent ) < 5 ) {
                $recent[] = array(
                    'id'      => (int) $row->id,
                    'form_id' => (int) $row->form_id,
                    'when'    => $created->getTimestamp(),
                    'message' => $row->meta_value,
                );
            }
        }
    }

    $total = array_sum( $counts );
    echo '<p>Digital Pathways submission errors over the last 30 days (' . count( $form_ids ) . ' forms): <strong>' . (int) $total . '</strong></p>';
    echo '<table class="widefat striped"><thead><tr><th>Day</th><th>Errors</th></tr></thead><tbody>';
    foreach ( array_reverse( $counts, true ) as $day => $count ) {
        $highlight = $count > 0 ? ' style="background:#fff3a3;"' : '';
        $day_form_ids = array_keys( $form_ids_by_day[ $day ] ?? array() );
        if ( $count > 0 && count( $day_form_ids ) === 1 ) {
            $count_html = '<a href="' . esc_url( mha_dpp_entries_date_url( $day_form_ids[0], $day ) ) . '">' . (int) $count . '</a>';
        } elseif ( $count > 0 ) {
            $links = array();
            foreach ( $day_form_ids as $day_form_id ) {
                $links[] = '<a href="' . esc_url( mha_dpp_entries_date_url( $day_form_id, $day ) ) . '">form ' . (int) $day_form_id . '</a>';
            }
            $count_html = (int) $count . ' (' . implode( ', ', $links ) . ')';
        } else {
            $count_html = '0';
        }
        echo '<tr><td' . $highlight . '>' . esc_html( $labels[ $day ] ) . '</td><td' . $highlight . '>' . $count_html . '</td></tr>';
    }
    echo '</tbody></table>';
    //mha_dpp_render_test_email_controls();

    if ( ! $recent ) {
        return;
    }

    /*
    echo '<p style="margin-top:12px;"><strong>Latest</strong></p><ul>';
    foreach ( $recent as $row ) {
        $url = admin_url( 'admin.php?page=gf_entries&view=entry&id=' . absint( $row['form_id'] ) . '&lid=' . absint( $row['id'] ) );
        $message = mb_substr( wp_strip_all_tags( (string) $row['message'] ), 0, 180 );
        echo '<li>' . esc_html( wp_date( 'M j, g:i a', $row['when'] ) ) . ' — <a href="' . esc_url( $url ) . '">entry ' . (int) $row['id'] . '</a><br><span class="description">' . esc_html( $message ) . '</span></li>';
    }
    echo '</ul>';
    */
}

