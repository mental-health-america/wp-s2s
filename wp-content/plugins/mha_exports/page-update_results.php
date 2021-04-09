<?php

/** 
 * Init Scripts
 */
add_action('init', 'mhaUpdateResultsScripts');
function mhaUpdateResultsScripts() {
    if(current_user_can('edit_posts')){
        wp_enqueue_script( 'process_mhaUpdateResults', plugin_dir_url(__FILE__) . 'mha_update.js', array('jquery'), time(), true );
        wp_localize_script('process_mhaUpdateResults', 'do_mhaUpdateScreenResults', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }
}

// List Page
function mhaUpdateResults(){
?>

<div id="poststuff" class="wrap">

    <h1>Update User Results</h1>
    <p>For developer use only. This will update all submitted screens from February 1, 2021 to April 1, 2021 and recalculate their saved user score and result.</p>

    <form id="mha-update-user-results" action="#" method="POST">
        <div class="acf-columns-2">
        <div class="acf-column-1">

            <div id="screen-export-error"></div>
            <input type="text" name="form_id" value="1" />
            <p><input type="submit" value="Begin Updates" id="update-user-results-submit" class="button button-primary" /></p>
            <hr />

            <ol id="result-log" style="font-family: courier;"></ol>

        </div>
        </div>
    </form>

</div>

<?php
}


add_action( 'wp_ajax_mha_result_updater_looper', 'mha_result_updater_looper' );
function mha_result_updater_looper(){

    // Init
    $result = array();
    $page_size = 200;
	parse_str($_POST['data'], $data);  

    // Initial Vars
    $form_id = $data['form_id'];
    $result['form_id'] = $form_id;

    // Paging
    if(isset($data['page'])){
        $page = $data['page'];
    } else {
        $page = 1;
    }
    $offset = ($page - 1) * $page_size;
    $paging = array( 'offset' => $offset, 'page_size' => $page_size );

    // Get entries
    $total_count = 0;
    $search_criteria = [];
    $search_criteria['status'] = 'active';
    $search_criteria['field_filters']['mode'] = 'all';
    $search_criteria['start_date'] = '2021-02-01';
    $search_criteria['end_date'] = '2021-04-01';
    $entries = GFAPI::get_entries( $form_id, $search_criteria, null, $paging, $total_count );
    
    $max_pages = ceil($total_count / $page_size);
    if($page >= $max_pages){
        $result['next_page'] = '' ;
    } else {
        $result['next_page'] = $page + 1;
    }
    $result['percent'] = round( ( ($page / $max_pages) * 100 ), 2 );

    $result['append'] = '';

    // Vars for later
    foreach($entries as $entry){

        $gfdata = GFAPI::get_entry( $entry['id'] );

        // Get IDs for later
        $user_screen_id = null;
        $user_score_id = null;
        $user_result_id = null;
        foreach($gfdata as $k => $v){            
            // Get field object
            $field = GFFormsModel::get_field( $data['form_id'], $k );  

            // Get screen token                  
            if (isset($field->label) && strpos($field->label, 'Token') !== false) { 
                $user_screen_id = $v;
                continue;
            }
            
            // User Score
            if (isset($field->label) && strpos($field->label, 'User Score') !== false) { 
                $user_score_id = $field->id;
                $user_score_old = $v;
                continue;
            }

            // User Result
            if (isset($field->label) && strpos($field->label, 'User Result') !== false) {  
                $user_result_id = $field->id;
                $user_result_old = $v;
                continue;
            }
        }

        $user_screen_result = getUserScreenResults( $user_screen_id ); 
        if( have_rows('results', $user_screen_result['screen_id']) ):
        while( have_rows('results', $user_screen_result['screen_id']) ) : the_row();
            $min = get_sub_field('score_range_minimum');
            $max = get_sub_field('score_range_max');
            $custom_logic_condition_row = get_sub_field('custom_logic_condition');
            
            if(
                $user_screen_result['total_score'] >= $min && $user_screen_result['total_score'] <= $max || 
                $user_screen_result['has_advanced_conditions'] > 0 && $user_screen_result['advanced_condition_row'] == get_row_index() || 
                isset($user_screen_result['custom_results_logic']) && $user_screen_result['custom_results_logic'] != '' && $user_screen_result['custom_result_row'] == $custom_logic_condition_row ){
        
                // Advanced Condition Double Check (in case score condition passes)
                if($user_screen_result['has_advanced_conditions'] > 0){
                    if($user_screen_result['advanced_condition_row'] != get_row_index()){ 
                        continue;
                    }
                }
        
                // Custom Condition Double Check (in case score condition passes)
                if(isset($user_screen_result['custom_results_logic']) && $user_screen_result['custom_results_logic'] != ''){
                    if($user_screen_result['custom_result_row'] != $custom_logic_condition_row){ 
                        continue;
                    }
                }
                
                // Required Tags Check
                if(empty($user_screen_result['required_result_tags']) && !empty(get_sub_field('required_tags'))){
                    continue;
                }
        
                // Additional scores to display
                $additional_scores = array();
                if(have_rows('additional_results', $user_screen_result['screen_id'])):
                    while( have_rows('additional_results', $user_screen_result['screen_id']) ) : the_row();  
                        $add_scores = get_sub_field('scores');
                        $add_score_total = 0;
                        foreach($add_scores as $score){
                            $add_score_total = $user_screen_result['general_score_data'][$score['question_id']] + $add_score_total;
                        }
                        $additional_scores[] = strval($add_score_total);
        
                    endwhile;
                    
                endif;
        
                // Add score data to result
                $gfdata[$user_score_id] = strval($user_screen_result['total_score']);
                $gfdata[$user_result_id] = get_sub_field('result_title');
        
            }
        
        endwhile;
        endif;
        
        $changed = null;
        $update_result = GFAPI::update_entry( $gfdata );
        if($user_score_old != $gfdata[$user_score_id] || $user_result_old != $gfdata[$user_result_id]){
            $changed = '[CHANGED]';
        }
        $result['append'] .= '<li>Updating... '.$user_screen_id.' '.$changed.' -- '.$update_result.'</li>';
        
    }
    
    echo json_encode($result);
    exit();   

}