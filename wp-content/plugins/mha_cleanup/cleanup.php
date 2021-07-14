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
    $timeCheck = strtotime('now - 3 month');

    // Date check so we don't delete entries that are too new
    if(strtotime($endDate) > $timeCheck){
        $data['error'] = 'End Date is too recent. Please select a date older than '.date('Y-m-t', $timeCheck);
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
    $data['deleted_entries'] = $deleted_entries + $data['deleted_entries'];

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