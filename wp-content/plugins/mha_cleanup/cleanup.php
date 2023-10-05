<?php

// Enqueing Scripts
add_action('init', 'mhacleanupScripts');
function mhacleanupScripts() {
    if(current_user_can('manage_options')){
        wp_enqueue_script('process_mhacleanuppers', plugin_dir_url(__FILE__) . 'mha_cleanup.js', array('jquery'), time(), true );
        wp_localize_script('process_mhacleanuppers', 'do_mhacleanups', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }
}


add_action( 'wp_ajax_mhacleanuperLooper', 'mhacleanuperLooper' );
function mhacleanuperLooper( $data = null ) {

    if(!current_user_can( 'manage_options' )){
        exit();
    }

    // Initial data
    if($data){
        $data = $data;
    } else {
        parse_str($_POST['data'], $data);  
    }

    // Defaults
    //$form_ids = array(15, 8, 10, 1, 13, 12, 5, 18, 17, 9, 11, 16, 14); // All screening related form IDs     
    $form_ids = explode(',',$data['form_ids']);
    $data['form_ids'] = $form_ids;
    $data['log'] = '';

    // Pagination
    $page_size = 1000;
    if(isset($data['next_page'])){
        $page = $data['next_page'];
    } else {
        $page = 1;
    }
    $data['page'] = $page;
    $offset = ($page - 1) * $page_size;

    // Filters
    $startDate = $data['start_date'];
    $data['start_date'] = $startDate;
    $endDate = $data['end_date'];
    $data['end_date'] = $endDate;
    $timeCheck = strtotime('now - 2 month');

    // Date check so we don't delete entries that are too new
    if(strtotime($endDate) > $timeCheck){
        $data['error'] = 'End Date is too recent. Please select a date older than '.date('Y-m-d', $timeCheck);
        echo json_encode($data);
        exit();
    }

    // Search criteria
    $search_criteria = [];
    $search_criteria['start_date'] = $startDate;
    $search_criteria['end_date'] = $endDate;
    //$search_criteria['field_filters'][] = array( 'key' => 41, 'value' => null );    

    // Get form entries
    $paging = array( 'offset' => $offset, 'page_size' => $page_size );
    $total_count = 0; // Set this for later
    $data['entries'] = [];

    //$search_criteria['field_filters'][] = array( 'key' => 41, 'value' => null );
    $entries = GFAPI::get_entries( $form_ids[0], $search_criteria, null, $paging, $total_count );

    $data['total'] = $total_count;
    $max_pages = ceil($total_count / $page_size);
    $data['max_pages'] = $max_pages;

    if($total_count > 0){
        foreach($entries as $e){
            if(empty($e[41])){ // This is the UID field we're checking for anonymous users 
                $data['entries'][] = $e['id'];
            }
        }
    } else {        
        $remove_form_id = array_shift($form_ids);
        $data['log'] = '<br />No matching applicable entries from Form #'.$remove_form_id.'... Skipping.';
        $data['form_ids'] = $form_ids;
        $data['next_page'] = null;
        $data['deleted_entries'] = 0;
        echo json_encode($data);
        exit();
    }

    $max_pages = ceil($total_count / $page_size);
    $data['max'] = $max_pages;
    $data['percent'] = round( ( ($page / $max_pages) * 100 ), 2 );
    if($page >= $max_pages){
        
        $remove_form_id = array_shift($form_ids);
        $data['log'] = '<br />Cleaning up Form #'.$remove_form_id;
        $data['form_ids'] = $form_ids;
        $data['next_page'] = null;

    } else {
        //$data['log'] = '';
        $data['next_page'] = $page + 1;
    }  


    // Extras to pass along
    $data['deleted_entries'] = $data['deleted_entries'];

    // Return our responses
    echo json_encode($data);
    exit();
}



add_action( 'wp_ajax_mhaCleanerJsonScrubber', 'mhaCleanerJsonScrubber' );
function mhaCleanerJsonScrubber() {

    if(!current_user_can( 'manage_options' )){
        exit();
    }

    $data = explode(",", trim($_POST['data']));
    $response['data'] = $data;
    $response['entries'] = [];
    
    $counter = 0;
    foreach($data as $d) {
        if($d != ''){
            GFAPI::delete_entry( $d );
            $response['entries_new'][] = $d;
            $counter++;
        }
    }

    $response['deleted_entries'] = $counter;
    
    // Return our responses
    echo json_encode($response);
    exit();

}


/**
 * User Cleaner
 */
add_action( 'wp_ajax_mhausercleanupper', 'mhausercleanupper' );
function mhausercleanupper() {

    // Admin check
    if(!current_user_can( 'manage_options' )){
        exit();
    }

    // Prep response
    $response = [];

    // Check user
    parse_str($_POST['data'], $data);  
    // $response['data'] = $data;

    $user_data = $data['user_data'];
    $response['user_data'] = $data['user_data'];

    // User ID was submitted
    $user = false;
    if(intval($data['user_data'])){
        $user = get_user_by( 'ID', intval($user_data) );
    } else if(filter_var($user_data, FILTER_VALIDATE_EMAIL)) {
        $user = get_user_by( 'email', sanitize_text_field($user_data) );
    }

    if($user){
        $response['user'] = $user;
        $response['error'] = '';
        $response['message'] = '';

        // Handle deleting the user
        $user_id = $user->ID;
        $user_info = get_userdata( $user_id );
        $this_user_roles = $user_info->roles;

        //For wp_delete_user() function
        require_once(ABSPATH.'wp-admin/includes/user.php' );

        if( in_array( "administrator", $this_user_roles) ) {
            $response['error'] = '<hr />This user is admin and cannot be deleted via this method.';
        } else {
            $delete_user = wp_delete_user( $user_id, null );
            if( $delete_user ){

                $response['message'] = '<hr />User ID #'.$user_id.' successfully removed.<br />';

                // Get likes, flags, and hides
                global $wpdb;
                $results_1 = $wpdb->get_results( "SELECT id FROM article_likes WHERE uid = ".$user_id."", OBJECT );   
                $response['message'] .= '<br /><strong>'.count($results_1).'</strong> Article Likes removed.';
                foreach($results_1 as $eid1){
                    $response['eid1_status'] = $wpdb->delete( 'article_likes', array( 'id' => $eid1->id ) );
                }

                $results_2 = $wpdb->get_results( "SELECT id FROM screens_hidden WHERE uid = ".$user_id."", OBJECT );
                $response['message'] .= '<br /><strong>'.count($results_2).'</strong> Screens Hidden removed.';
                foreach($results_2 as $eid2){
                    $wpdb->delete( 'screens_hidden', array( 'id' => $eid2->id ) );
                }

                $results_3 = $wpdb->get_results( "SELECT id FROM thoughts_flags WHERE uid = ".$user_id."", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_3).'</strong> Thought Flags removed.';
                foreach($results_3 as $eid3){
                    $wpdb->delete( 'thoughts_flags', array( 'id' => $eid3->id ) );
                }

                $results_4 = $wpdb->get_results( "SELECT id FROM thoughts_hidden WHERE uid = ".$user_id."", OBJECT );  
                $response['message'] .= '<br /><strong>'.count($results_4).'</strong> Thoughts Hidden removed.';
                foreach($results_4 as $eid4){
                    $wpdb->delete( 'thoughts_hidden', array( 'id' => $eid4->id ) );
                }

                $results_5 = $wpdb->get_results( "SELECT id FROM thoughts_likes WHERE uid = ".$user_id."", OBJECT );   
                $response['message'] .= '<br /><strong>'.count($results_5).'</strong> Thought Likes removed.'; 
                foreach($results_5 as $eid5){
                    $wpdb->delete( 'thoughts_likes', array( 'id' => $eid5->id ) );
                }

                $results_6 = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}gf_entry WHERE created_by = ".$user_id."", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_6).'</strong> Screening Tests removed.';
                foreach($results_6 as $eid6){
                    GFAPI::delete_entry( $eid6->id );
                }

                $results_7 = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}posts WHERE post_author = ".$user_id." AND post_type = 'diy_responses'", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_7).'</strong> DIY Responses removed.';
                foreach($results_7 as $eid7){
                    wp_delete_post($eid7->id, true);   
                }
            
                $results_8 = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}posts WHERE post_author = ".$user_id." AND post_type = 'thought'", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_8).'</strong> Thoughts removed.';
                foreach($results_8 as $eid8){
                    wp_delete_post($eid8->id, true);  
                }
                
                $results_9 = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}relevanssi_log WHERE user_id = ".$user_id."", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_9).'</strong> Relevanssi log entries removed.';
                foreach($results_9 as $eid9){
                    $wpdb->delete( "{$wpdb->prefix}relevanssi_log", array( 'user_id' => $eid9->id ) );
                }

                $results_10 = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}fa_user_logins WHERE user_id = ".$user_id."", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_10).'</strong> User Login History logs removed.';
                foreach($results_10 as $eid10){
                    $wpdb->delete( "{$wpdb->prefix}fa_user_logins", array( 'user_id' => $eid10->id ) );
                }

            } else {
                $response['error'] = 'There is a problem while deleting the user. Please contact your developer.';
            }
        }

    } else {
        $response['error'] = '<hr />No users match this query. Please confirm that the ID you entered was correct.';
    }

    // Return our responses
    echo json_encode($response);
    exit();

}