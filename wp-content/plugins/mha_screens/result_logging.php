<?php

// Capture Callrail CTA displayed
function mha_record_callrail_cta_display(){

    global $wpdb;
        
    // Default args
    $defaults = array(
        'url' => null,
        'original_link' => null,
        'updated_link' => null
    );
    $result = array();

    // Merge with submitted data
    $args = wp_parse_args( $_POST['data'], $defaults );
    
    // Submit display to database
    $db_insert = array(
        'url' => sanitize_url($args['url']),
        'original_link' => sanitize_text_field($args['original_link']),
    );
    $result['inserted'] = $wpdb->insert( 'callrail_log', $db_insert );  
    if($result['inserted']){
        $result['record_id'] = $wpdb->insert_id;
    }

    echo json_encode($result);
    exit();

}
add_action("wp_ajax_nopriv_process_mha_record_callrail_cta_display", "mha_record_callrail_cta_display");
add_action("wp_ajax_process_mha_record_callrail_cta_display", "mha_record_callrail_cta_display");


function mha_update_record_callrail_cta_display(){

    global $wpdb;
        
    // Default args
    $defaults = array(
        'record_id' => null,
        'updated_link' => null
    );
    $result = array();
    $result['updated'] = false;

    // Merge with submitted data
    $args = wp_parse_args( $_POST['data'], $defaults );
    $result['args'] = $args;

    if($args['record_id']){

        // Execute the update query
        $result['updated_time'] = current_time('mysql', 1);

        $result['updated'] = $wpdb->update(
            'callrail_log',
            array(
                'updated_link' => sanitize_url($args['updated_link']),
                'updated_time' => $result['updated_time']
            ),
            array(
                'id' => (int)$args['record_id']
            )
        );

    }

    echo json_encode($result);
    exit();

}
add_action("wp_ajax_nopriv_mha_update_record_callrail_cta_display", "mha_update_record_callrail_cta_display");
add_action("wp_ajax_mha_update_record_callrail_cta_display", "mha_update_record_callrail_cta_display");


function mha_update_click_callrail_cta_display(){

    global $wpdb;
        
    // Default args
    $defaults = array(
        'record_id' => null
    );
    $result = array();
    $result['click_time'] = current_time('mysql', 1);

    // Merge with submitted data
    $args = wp_parse_args( $_POST['data'], $defaults );
    $result['args'] = $args;

    if($args['record_id']){

        $result['updated'] = $wpdb->update(
            'callrail_log',
            array(
                'clicked' => $result['click_time']
            ),
            array(
                'id' => (int)$args['record_id']
            )
        );

    }

    echo json_encode($result);
    exit();

}
add_action("wp_ajax_nopriv_mha_update_click_callrail_cta_display", "mha_update_click_callrail_cta_display");
add_action("wp_ajax_mha_update_click_callrail_cta_display", "mha_update_click_callrail_cta_display");