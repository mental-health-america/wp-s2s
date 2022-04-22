<?php

/** 
 * Init Scripts
 */
add_action('init', 'mhaUpdateResultsScripts');
function mhaUpdateResultsScripts() {
    if(current_user_can('edit_posts')){
        wp_enqueue_script( 'process_mhaUpdateResults', plugin_dir_url(__FILE__) . 'mha_update.js', array('jquery'), time(), true );
        wp_enqueue_style( 'process_mhaacfeui', '/wp-content/plugins/acf-extended/assets/css/acfe-ui.min.css', array(), time() );
        wp_enqueue_style( 'process_mhaUpdateResults', plugin_dir_url(__FILE__) . 'mha_export.css', array(), time() );
        wp_localize_script('process_mhaUpdateResults', 'do_mhaUpdateScreenResults', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }
}

// List Page
function mhaUpdateResults(){
?>

<div id="poststuff" class="wrap">

    <h1>Update User Results</h1>
    <p>This tool will scan screens from the last 3 months and look for entires where the final result is blank.</p>

    <form id="mha-update-user-results" action="#" method="POST">
        <div class="acf-columns-2">
        <div class="acf-column-1">

            <div id="screen-export-error"></div>
            <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="form_id">Forms</label><br /></th>
                    <td>
                        <?php 
                            $gforms = GFAPI::get_forms(true, false, 'title'); 
                            echo '<select name="form_id" id="form_id">';
                            foreach($gforms as $gf){
                                if (strpos(strtolower($gf['title']), 'test') !== false || strpos(strtolower($gf['title']), 'survey') !== false || strpos(strtolower($gf['title']), 'quiz') !== false) {
                                    echo '<option value="'.$gf['id'].'" />'.$gf['title'].'</option>';
                                }
                            }
                            echo '</select>'
                        ?>
                    </td>
                </tr>                
                <tr>
                    <th scope="row"><label for="export_screen_start_date">Start Date</label></th>
                    <td>
                        <input type="text" name="export_screen_start_date" id="export_screen_start_date" value="<?php echo date('Y-m', strtotime('1 month ago')); ?>-01" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="export_screen_end_date">End Date</label></th>
                    <td>
                        <input type="text" name="export_screen_end_date" id="export_screen_end_date" value="<?php echo date('Y-m', strtotime('now')); ?>-01" />
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <p>
                            <input type="submit" class="button button-primary" id="update-user-results-submit"  value="Update Screening Data">
                        </p>
                        
                        <div id="update-screen-results-progress" style="display: none; margin-top: 20px;">
                            <div class="bar-wrapper"><div class="bar"></div></div>            
                            <strong class="label"><span class="label-number">0</span>%</strong>
                        </div><br /><br />
                        <ol id="result-log" style="font-family: courier;"></ol>
                    </td>
                </tr>
            </tbody>
            </table>


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

    // Entry IDs
    $result['entry_ids'] = array();
    
    // Paging
    if(isset($data['page'])){
        $page = $data['page'];
    } else {
        $page = 1;
    }
    $offset = ($page - 1) * $page_size;
    $paging = array( 'offset' => $offset, 'page_size' => $page_size );

    // Entries search criteria
    $total_count = 0;
    $search_criteria = [];
    $search_criteria['status'] = 'active';
    $search_criteria['field_filters']['mode'] = 'all';    
    $search_criteria['start_date'] = $data['start_date'];
    $search_criteria['end_date'] = $data['end_date'];

    // Get IDS for search criteria
    $form_data = GFAPI::get_form( $form_id );
    $form_field_ids = array();
    foreach($form_data['fields'] as $fd){
        if($fd->label == 'User Score' || $fd->label == 'User Result'){
            $form_field_ids[$fd->id] = $fd->id;
        }
    }    
    foreach($form_field_ids as $fid){
        $search_criteria['field_filters'][] = array( 'key' => $fid, 'value' => '', 'operator' => 'is', );
    }

    // Get entries
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

            // Check for blank scores and store IDs
            if (isset($field->label)){
                if(strpos($field->label, 'User Score') !== false || strpos($field->label, 'User Result') !== false) { 
                    if($entry[$field->id] == ''){;
                        $result['entry_ids'][$entry['id']] = $entry['id'];
                        continue;
                    }
                }
            }
        }

    }

    
    // Loop through IDs to fix
    foreach($result['entry_ids'] as $eid){

        $user_screen_result = getUserScreenResults( $eid ); 
        
        $updateScreenArray = array(
            'entry_id'          => $eid,
            'user_score'        => null,
            'user_result'       => null,
            'additional_scores' => null,
            'user_id' => null,
        );

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
                $updateScreenArray['user_score'] = strval($user_screen_result['total_score']);
                $updateScreenArray['user_result'] = get_sub_field('result_title');
                $updateScreenArray['additional_scores'] = $additional_scores;
        
            }
        
        endwhile;
        endif;
                
        // Update the entry with the user score and result
        updateUserScreenResults( $updateScreenArray );
        
        //$update_result = GFAPI::update_entry( $gfdata );
        $result['update_result'][] = $update_result;
        $result['append'] .= '<li>Updating... Entry #'.$eid.'</li>';

    }
    
    echo json_encode($result);
    exit();   

}