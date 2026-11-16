<?php

/**
 * CallRail CTA logging
 *
 * These endpoints are open to anonymous visitors on purpose: they record that a CTA was shown and
 * clicked so it can be analysed later. Because anyone can call them, they are protected without
 * relying on a nonce (the pages carrying this script can be cached, which would leave a stale
 * nonce behind):
 *  - every call is rate limited per IP,
 *  - input is validated (this site's URLs, sms:/tel:/https links, bounded length),
 *  - a new log row is handed back with a random token that must accompany any later update to
 *    that row, so one visitor cannot edit another visitor's records by counting up IDs,
 *  - nothing the visitor sent is echoed back.
 */

// How long a visitor may keep updating a log row they created.
const MHA_CALLRAIL_TOKEN_TTL = 6 * HOUR_IN_SECONDS;

/**
 * Read the posted `data` array (jQuery posts it as nested fields).
 *
 * @return array
 */
function mha_callrail_posted_data() {
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- see file docblock
    return ( isset( $_POST['data'] ) && is_array( $_POST['data'] ) ) ? wp_unslash( $_POST['data'] ) : array();
}

/**
 * Stop the request with a JSON error. Echoed as plain JSON, because mha_screens.js runs
 * JSON.parse() on the raw response text.
 *
 * @param int $status HTTP status.
 */
function mha_callrail_fail( $status ) {
    status_header( $status );
    echo json_encode( array( 'error' => true ) );
    exit();
}

/**
 * Only a link the CTA could really contain: an SMS/phone link or a web link.
 *
 * @param mixed $link Value from the browser.
 * @return string Sanitized link, or '' when it is not acceptable.
 */
function mha_callrail_clean_link( $link ) {
    if ( ! is_string( $link ) || '' === $link || strlen( $link ) > 500 ) {
        return '';
    }
    $link = sanitize_text_field( $link );
    return preg_match( '#^(sms:|tel:|https?://)#i', $link ) ? $link : '';
}

/**
 * Token proving this visitor created the log row.
 */
function mha_callrail_record_token_valid( $record_id, $token ) {
    $stored = get_transient( 'mha_cr_' . (int) $record_id );
    return is_string( $stored ) && is_string( $token ) && '' !== $token && hash_equals( $stored, $token );
}

// Capture Callrail CTA displayed
function mha_record_callrail_cta_display(){

    global $wpdb;

    if ( function_exists( 'mha_rate_limit_allow' ) && ! mha_rate_limit_allow( 'callrail_display', mha_get_remote_ip(), 60, HOUR_IN_SECONDS ) ) {
        mha_callrail_fail( 429 );
    }

    $args = wp_parse_args( mha_callrail_posted_data(), array(
        'url'           => null,
        'original_link' => null,
    ) );

    // The page must be one of this site's pages and the link must look like a CTA link.
    $url           = is_string( $args['url'] ) ? esc_url_raw( $args['url'], array( 'http', 'https' ) ) : '';
    $site_host     = wp_parse_url( home_url(), PHP_URL_HOST );
    $url_host      = $url ? wp_parse_url( $url, PHP_URL_HOST ) : '';
    $original_link = mha_callrail_clean_link( $args['original_link'] );

    if ( ! $url || strlen( $url ) > 2000 || ! $url_host || strtolower( $url_host ) !== strtolower( (string) $site_host ) || '' === $original_link ) {
        mha_callrail_fail( 400 );
    }

    $result = array();

    // Submit display to database
    $db_insert = array(
        'url' => $url,
        'original_link' => $original_link,
    );
    $result['inserted'] = $wpdb->insert( 'callrail_log', $db_insert );
    if($result['inserted']){
        $result['record_id'] = $wpdb->insert_id;

        // Only this visitor gets to update the row they just created.
        $token = wp_generate_password( 24, false );
        set_transient( 'mha_cr_' . (int) $result['record_id'], $token, MHA_CALLRAIL_TOKEN_TTL );
        $result['record_token'] = $token;
    }

    echo json_encode($result);
    exit();

}
add_action("wp_ajax_nopriv_process_mha_record_callrail_cta_display", "mha_record_callrail_cta_display");
add_action("wp_ajax_process_mha_record_callrail_cta_display", "mha_record_callrail_cta_display");


function mha_update_record_callrail_cta_display(){

    global $wpdb;

    if ( function_exists( 'mha_rate_limit_allow' ) && ! mha_rate_limit_allow( 'callrail_update', mha_get_remote_ip(), 120, HOUR_IN_SECONDS ) ) {
        mha_callrail_fail( 429 );
    }

    $args = wp_parse_args( mha_callrail_posted_data(), array(
        'record_id'    => null,
        'record_token' => null,
        'updated_link' => null,
    ) );

    $record_id    = absint( $args['record_id'] );
    $updated_link = mha_callrail_clean_link( $args['updated_link'] );

    if ( ! $record_id || '' === $updated_link || ! mha_callrail_record_token_valid( $record_id, $args['record_token'] ) ) {
        mha_callrail_fail( 403 );
    }

    $result = array();

    // Execute the update query
    $result['updated_time'] = current_time('mysql', 1);

    $result['updated'] = $wpdb->update(
        'callrail_log',
        array(
            'updated_link' => $updated_link,
            'updated_time' => $result['updated_time']
        ),
        array(
            'id' => $record_id
        )
    );

    echo json_encode($result);
    exit();

}
add_action("wp_ajax_nopriv_mha_update_record_callrail_cta_display", "mha_update_record_callrail_cta_display");
add_action("wp_ajax_mha_update_record_callrail_cta_display", "mha_update_record_callrail_cta_display");


function mha_update_click_callrail_cta_display(){

    global $wpdb;

    if ( function_exists( 'mha_rate_limit_allow' ) && ! mha_rate_limit_allow( 'callrail_click', mha_get_remote_ip(), 120, HOUR_IN_SECONDS ) ) {
        mha_callrail_fail( 429 );
    }

    $args = wp_parse_args( mha_callrail_posted_data(), array(
        'record_id'    => null,
        'record_token' => null,
    ) );

    $record_id = absint( $args['record_id'] );

    if ( ! $record_id || ! mha_callrail_record_token_valid( $record_id, $args['record_token'] ) ) {
        mha_callrail_fail( 403 );
    }

    $result = array();
    $result['click_time'] = current_time('mysql', 1);

    $result['updated'] = $wpdb->update(
        'callrail_log',
        array(
            'clicked' => $result['click_time']
        ),
        array(
            'id' => $record_id
        )
    );

    echo json_encode($result);
    exit();

}
add_action("wp_ajax_nopriv_mha_update_click_callrail_cta_display", "mha_update_click_callrail_cta_display");
add_action("wp_ajax_mha_update_click_callrail_cta_display", "mha_update_click_callrail_cta_display");
