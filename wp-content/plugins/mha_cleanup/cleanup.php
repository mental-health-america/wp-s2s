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
    $timeCheck = strtotime('last day of 2 months ago');

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
    $data['deleted_entries'] = isset($data['deleted_entries']) ? $data['deleted_entries'] : 0;

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
    $response['review'] = isset($data['review']) ? $data['review'] : false;
    
    $deleted_suffix = $response['review'] ? '' : ' removed';

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

        if( in_array( "administrator2", $this_user_roles) ) {
            $response['error'] = '<hr />This user is admin and cannot be deleted via this method.';
        } else {

            $delete_user = false;
            if(!$response['review']){
                $delete_user = wp_delete_user( $user_id, null );
            }

            if( $delete_user || isset($response['review']) && $response['review'] ){

                if($response['review']){
                    $response['message'] = '<hr />User ID #'.$user_id.' data:<br />';
                } else {
                    $response['message'] = '<hr />User ID #'.$user_id.' successfully removed.<br />';
                }

                // Get likes, flags, and hides
                global $wpdb;
                $results_1 = $wpdb->get_results( "SELECT pid FROM article_likes WHERE uid = ".$user_id."", OBJECT );   
                $response['message'] .= '<br /><strong>'.count($results_1).'</strong> Article Likes'.$deleted_suffix.'.';
                if($response['review']){
                    if(count($results_1) > 0){
                        $response['message'] .= '<ol>';
                        foreach($results_1 as $r){                
                            $response['message'] .= '<li><a target="_blank" href="'.get_the_permalink($r->pid).'">Article liked #'.$r->pid.'</a></li>';                        
                        }
                        $response['message'] .= '</ol>';
                    }
                } else {
                    foreach($results_1 as $eid1){
                        $response['eid1_status'] = $wpdb->delete( 'article_likes', array( 'id' => $eid1->id ) );
                    }
                }

                $results_2 = $wpdb->get_results( "SELECT pid FROM screens_hidden WHERE uid = ".$user_id."", OBJECT );
                $response['message'] .= '<br /><strong>'.count($results_2).'</strong> Screens Hidden'.$deleted_suffix.'.';
                if($response['review']){
                    if(count($results_2) > 0){
                        $response['message'] .= '<ol>';
                        foreach($results_2 as $r){                
                            $response['message'] .= '<li><a target="_blank" href="'.get_the_permalink($r->pid).'">Screen hidden #'.$r->pid.'</a></li>';                        
                        }
                        $response['message'] .= '</ol>';
                    }
                } else {
                    foreach($results_2 as $eid2){
                        $wpdb->delete( 'screens_hidden', array( 'id' => $eid2->id ) );
                    }
                }

                $results_3 = $wpdb->get_results( "SELECT * FROM thoughts_flags WHERE uid = ".$user_id."", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_3).'</strong> Thought Flags'.$deleted_suffix.'.';
                if($response['review']){
                    if(count($results_3) > 0){
                        $response['message'] .= '<ol>';
                        foreach($results_3 as $r){
                            $response['message'] .= '<li><a target="_blank" href="/wp-admin/post.php?post='.$r->id.'&action=edit">Flagged thought ID #'.$r->pid.' (Row #'.$r->row.')</a></li>';                        
                        }
                        $response['message'] .= '</ol>';
                    }
                } else {
                    foreach($results_3 as $eid3){
                        $wpdb->delete( 'thoughts_flags', array( 'id' => $eid3->id ) );
                    }
                }

                $results_4 = $wpdb->get_results( "SELECT pid FROM thoughts_hidden WHERE uid = ".$user_id."", OBJECT );  
                $response['message'] .= '<br /><strong>'.count($results_4).'</strong> Thoughts Hidden'.$deleted_suffix.'.';
                if($response['review']){
                    if(count($results_4) > 0){
                        $response['message'] .= '<ol>';
                        foreach($results_4 as $r){                
                            $response['message'] .= '<li><a target="_blank" href="'.get_the_permalink($r->pid).'">Thought hidden #'.$r->pid.'</a></li>';                        
                        }
                        $response['message'] .= '</ol>';
                    }
                } else {
                    foreach($results_4 as $eid4){
                        $wpdb->delete( 'thoughts_hidden', array( 'id' => $eid4->id ) );
                    }
                }

                $results_5 = $wpdb->get_results( "SELECT * FROM thoughts_likes WHERE uid = ".$user_id."", OBJECT );   
                $response['message'] .= '<br /><strong>'.count($results_5).'</strong> Thought Likes'.$deleted_suffix.'.'; 
                if($response['review']){
                    if(count($results_5) > 0){
                        $response['message'] .= '<ol>';
                        foreach($results_5 as $r){                
                            $response['message'] .= '<li><a target="_blank" href="/wp-admin/post.php?post='.$r->pid.'&action=edit">Thought liked #'.$r->pid.' (Row #'.$r->row.')</a></li>';                        
                        }
                        $response['message'] .= '</ol>';
                    }
                } else {
                    foreach($results_5 as $eid5){
                        $wpdb->delete( 'thoughts_likes', array( 'id' => $eid5->id ) );
                    }
                }

                $results_6 = $wpdb->get_results( "SELECT id, form_id FROM {$wpdb->prefix}gf_entry WHERE created_by = ".$user_id."", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_6).'</strong> Screening Tests'.$deleted_suffix.'.';                
                if($response['review']){
                    if(count($results_6) > 0){
                        $response['message'] .= '<ol>';
                        foreach($results_6 as $r){                
                            $response['message'] .= '<li><a target="_blank" href="/wp-admin/admin.php?page=gf_entries&view=entry&id='.$r->form_id.'&lid='.$r->id.'">Test ID #'.$r->id.'</a></li>';                        
                        }
                        $response['message'] .= '</ol>';
                    }
                } else {
                    foreach($results_6 as $eid6){
                        GFAPI::delete_entry( $eid6->id );
                    }
                }

                $results_7 = $wpdb->get_results( "SELECT ID FROM {$wpdb->prefix}posts WHERE post_author = ".$user_id." AND post_type = 'diy_responses'", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_7).'</strong> DIY Responses'.$deleted_suffix.'.';
                if($response['review']){
                    if(count($results_7) > 0){
                        $response['message'] .= '<ol>';
                        foreach($results_7 as $r){                
                            $response['message'] .= '<li><a target="_blank" href="'.get_the_permalink($r->ID).'">DIY Response ID #'.$r->ID.'</a></li>';                        
                        }
                        $response['message'] .= '</ol>';
                    }
                } else {
                    foreach($results_7 as $eid7){
                        wp_delete_post($eid7->id, true);   
                    }
                }
            
                $results_8 = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}posts WHERE post_author = ".$user_id." AND post_type = 'thought'", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_8).'</strong> Thoughts'.$deleted_suffix.'.';
                if($response['review']){
                    if(count($results_8) > 0){
                        $response['message'] .= '<ol>';
                        foreach($results_8 as $r){                
                            $response['message'] .= '<li><a target="_blank" href="'.get_the_permalink($r->id).'">Thought ID #'.$r->id.'</a></li>';                        
                        }
                        $response['message'] .= '</ol>';
                    }
                } else {
                    foreach($results_8 as $eid8){
                        wp_delete_post($eid8->id, true);  
                    }
                }

                $results_9 = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}relevanssi_log WHERE user_id = ".$user_id."", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_9).'</strong> Relevanssi log entries'.$deleted_suffix.'.';
                if(!$response['review']){
                    foreach($results_9 as $eid9){
                        $wpdb->delete( "{$wpdb->prefix}relevanssi_log", array( 'user_id' => $eid9->id ) );
                    }
                }

                $results_10 = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}fa_user_logins WHERE user_id = ".$user_id."", OBJECT ); 
                $response['message'] .= '<br /><strong>'.count($results_10).'</strong> User Login History logs'.$deleted_suffix.'.';
                if(!$response['review']){
                    foreach($results_10 as $eid10){
                        $wpdb->delete( "{$wpdb->prefix}fa_user_logins", array( 'user_id' => $eid10->id ) );
                    }
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


/**
 * A/B Testing Data Cleanup
 */

add_action( 'wp_ajax_abtestingcleanupLooper', 'abtestingcleanupLooper' );
function abtestingcleanupLooper( $data = null ) {

    if(!current_user_can( 'manage_options' )){
        exit();
    }

    global $wpdb;

    // Define the table name (with WordPress table prefix)
    $table_name = 'ab_redirects';

    // Initial data
    if($data){
        $data = $data;
    } else {
        parse_str($_POST['data'], $data);  
    }
    
    $page_size = 1000;
    if(isset($data['next_page'])){
        $page = $data['next_page'];
    } else {
        $page = 1;
    }
    $data['page'] = $page;
    $start_date = $data['start_date'];
    $data['start_date'] = $start_date;
    $end_date = $data['end_date'];
    $data['end_date'] = $end_date;

    if(!isset($data['deleted_entries'])){
        $data['deleted_entries'] = 0;
    }

    $total_deleted = 0;

    try {
        // Get the total number of rows to delete
        if(!isset($data['total'])){
            $sql_total = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE `date` BETWEEN '%s' AND '%s'",
                $start_date,
                $end_date
            );
            $total_rows = $wpdb->get_var($sql_total);
            $data['total'] = $total_rows;
        }

        // Calculate the maximum number of pages
        if(!isset($data['max_pages'])){
            $data['max_pages'] = (int) ceil($total_rows / $page_size);
        }

        // If there are no rows to delete, return early
        if ($total_rows == 0) {
            $data['next_page'] = null; // No more pages if no rows to delete
            echo json_encode($data);
            exit();
        }

        // Calculate the offset based on the page number
        $offset = ($data['page'] - 1) * $page_size;
        $data['percent'] = round( ( ($data['page'] / $data['max_pages']) * 100 ), 2 );

        // Select up to $page_size rows for the current page
        $sql_select = $wpdb->prepare(
            "SELECT id FROM $table_name WHERE `date` BETWEEN %s AND %s LIMIT %d OFFSET %d",
            $start_date,
            $end_date,
            $page_size,
            $offset
        );

        // Get the IDs of rows to delete
        $rows_to_delete = $wpdb->get_col($sql_select);

        // If there are rows to delete, delete them
        if (!empty($rows_to_delete)) {
            // Convert the array of IDs into a comma-separated string for the SQL DELETE query
            $ids_to_delete = implode(',', array_map('intval', $rows_to_delete));

            // Prepare the SQL query to delete the selected rows
            $sql_delete = "DELETE FROM $table_name WHERE id IN ($ids_to_delete)";
            $data['ids_to_delete'] = $ids_to_delete;

            // Execute the delete query
            $deleted = $wpdb->query($sql_delete);

            if ($deleted === false) {
                throw new Exception('An error occurred while deleting rows.');
            }
            $total_deleted = count($rows_to_delete);

            // Determine if there are more pages to process
            if ($data['page'] < $data['max_pages']) {
                $data['next_page'] = $data['page'] + 1;
            }
        } else {
            $data['next_page'] = null; // No more pages if no rows to delete
        }
    } catch (Exception $e) {
        $data['error'] = $e->getMessage();
    }

    $data['deleted_entries'] = $total_deleted;
    // Return the response as JSON
    echo json_encode($data);
    exit();
}