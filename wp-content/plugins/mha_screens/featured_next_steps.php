<?php

function shuffle_assoc($list) { 
    if (!is_array($list)) {
        return $list; 
    }

    $keys = array_keys($list); 
    shuffle($keys); 
    $random = array(); 
    foreach ($keys as $key) { 
        $random[$key] = $list[$key]; 
    }
    return $random; 
} 

function mha_featured_next_steps_data( $args ){                        

    // Args
    $defaults = array(
        'result_title'       => '',
        'espanol'            => '',
        'iframe_var'         => '',
        'partner_var'        => '',
        'user_screen_result' => array(),
        'answered_demos'     => array()
    );   
    $args = wp_parse_args( $args, $defaults );
    $return = [];
    $return['additional_result_text'] = []; // Initialize as array

    $debug = false;
    $debug_log = [];
    
    // Check for screen_id's featured_next_steps field first (takes precedence)
    $screen_id = isset($args['user_screen_result']['screen_id']) ? $args['user_screen_result']['screen_id'] : null;
    $screen_featured_links = [];
    $screen_heading = get_field('next_steps_heading', $screen_id) ?: '';
    
    if($screen_id && have_rows('featured_next_steps', $screen_id)):
        // Get heading if available
        
        $row_index = 0;
        while( have_rows('featured_next_steps', $screen_id) ) : the_row();
            $link = get_sub_field('link');
            if($link){
                $link_id = is_object($link) ? $link->ID : $link;
                if($link_id){
                    $screen_featured_links[] = $link_id;
                }
            }
        endwhile;
    endif;
    
    // Check for matching partner first
    $featured_next_steps_source = $args['user_screen_result']['screen_id']; // Default to screen_id
    $is_partner_source = false;
    
    // Get partners
    $partner_args = array(
        'post_type' => 'partners', 
        'post_status' => 'publish',
        'posts_per_page' => 100,
    );
    $partners = get_posts($partner_args);
    
    // Look for matching partner
    if (isset($args['user_screen_result']['referer']) && !empty($args['user_screen_result']['referer'])) {
        foreach ($partners as $partner) {
            $partner_information = get_field('partner_information', $partner->ID);
            if($partner_information && isset($partner_information['partner_code']) && $partner_information['partner_code'] == $args['user_screen_result']['referer']){
                $featured_next_steps_source = $partner->ID;
                $is_partner_source = true;
                break;
            }
        }
    }
    wp_reset_postdata();
    
    // Add screen's featured_next_steps links first (with precedence)
    if(!empty($screen_featured_links)){
        $screen_row_index = 0;
        $return['results'][$screen_row_index]['group_title'] = ''; // No group title for screen links
        $return['results'][$screen_row_index]['additional_result_text'] = '';
        $return['results'][$screen_row_index]['partner_next_steps'] = false;
        
        $counter = 1;
        foreach($screen_featured_links as $screen_link_id){
            $return['results'][$screen_row_index]['links'][$counter] = $screen_link_id;
            $counter++;
        }
        
        // Set heading for screen featured links (takes precedence)
        if(!empty($screen_heading)){
            $return['heading'] = $screen_heading;
        } else {
            $return['heading'] = 'Next Steps';
        }
    }
    
    // Use a running index so multiple conditional groups can display (e.g. layout contains actions_z AND layout contains actions_a).
    // get_row_index() would repeat across featured_next_steps_test rows and overwrite previous matches.
    $next_result_index = !empty($screen_featured_links) ? 1 : 0;

    if( have_rows('featured_next_steps_test', $featured_next_steps_source) ):
    while( have_rows('featured_next_steps_test', $featured_next_steps_source) ) : the_row();
        
        $heading = get_sub_field('next_steps_heading');
        $randomize = get_sub_field('dont_randomize_order');
        $randomize_group = get_sub_field('dont_randomize_group_order');
        $hide_group_titles = get_sub_field('hide_group_titles'); // Store during loop

        if( have_rows('next_step_links') ):
        while( have_rows('next_step_links') ) : the_row();

            $next_step_test_operator = get_sub_field('operator');
            $group_title = get_sub_field('link_group_title');
            $row_index = get_row_index();
            $proceed = false;
            $i = 0;
            $con_score = 0;
            
            if( have_rows('conditions') ):
            while( have_rows('conditions') ) : the_row();

                $con_type = get_sub_field('type');
                $con_condition = get_sub_field('condition');
                $con_key = get_sub_field('key');
                $get_key = isset($_GET[$con_key]) ? sanitize_text_field($_GET[$con_key]) : null;
                $con_value = get_sub_field('value');

                //echo "Condition checker: $con_type - $con_condition - $con_key - $get_key - $con_value<br />";

                switch($con_type):

                    case 'test_result':
                        switch($con_condition):
                            case 'equals':
                                if($args['result_title'] == $con_value){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                            case 'contains':
                                if( $args['result_title'] && str_contains($args['result_title'], $con_value) ){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                
                                break;
                            case 'starts with':
                                if( $args['result_title'] && str_starts_with($args['result_title'], $con_value)){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                     
                                break;
                            case 'ends with':
                                if( $args['result_title'] && str_ends_with($args['result_title'], $con_value)){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                      
                                break;
                            case 'does not equal':
                                if($args['result_title'] != $con_value){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                
                                break;
                            case 'does not contain':
                                if( $args['result_title'] && !str_contains($args['result_title'], $con_value)){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                           
                                break;
                            case 'does not start with':
                                if( $args['result_title'] && !str_starts_with($args['result_title'], $con_value)){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                         
                                break;
                            case 'does not end with':
                                if( $args['result_title'] && !str_ends_with($args['result_title'], $con_value)){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                     
                                break;
                            case 'exists':
                                if($args['result_title']){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                     
                                break;
                            case 'none of':
                                $con_value_exp = explode('|',$con_value);
                                $con_value_counter = 0;
                                foreach($con_value_exp as $cve){
                                    if($args['result_title'] != trim($cve)){
                                        $con_value_counter++;
                                    }                                    
                                }
                                if($con_value_counter == 0){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                            case 'one of':
                                $con_value_exp = explode('|',$con_value);
                                $con_value_counter = 0;
                                foreach($con_value_exp as $cve){
                                    if($args['result_title'] == trim($cve)){
                                        $con_value_counter++;
                                    }                                    
                                }
                                if($con_value_counter > 0){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                            case 'not null':
                                if($args['result_title']){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                            case 'is null':
                                if(!$args['result_title']){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                            case 'greater than':
                                if($args['result_title'] > $con_value){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                            case 'less than':
                                if($args['result_title'] < $con_value){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                        endswitch;
                        break;

                    case 'url_parameter':
                        switch($con_condition):
                            case 'equals':
                                if($get_key == $con_value){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                            case 'contains':
                                if( $get_key && str_contains($get_key, $con_value) ){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                
                                break;
                            case 'starts with':
                                if( $get_key && str_starts_with($get_key, $con_value)){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                     
                                break;
                            case 'ends with':
                                if( $get_key && str_ends_with($get_key, $con_value)){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                      
                                break;
                            case 'does not equal':
                                if($get_key != $con_value){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                
                                break;
                            case 'does not contain':
                                if( $get_key && !str_contains($get_key, $con_value)){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                           
                                break;
                            case 'does not start with':
                                if( $get_key && !str_starts_with($get_key, $con_value)){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                         
                                break;
                            case 'does not end with':
                                if( $get_key && !str_ends_with($get_key, $con_value)){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                     
                                break;
                            case 'exists':
                                if($get_key){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }                                     
                                break;
                            case 'none of':
                                $con_value_exp = explode('|',$con_value);
                                $get_key_exp = explode('|',$get_key);
                                $con_value_counter = 0;
                                foreach($get_key_exp as $gke){
                                    foreach($con_value_exp as $cve){
                                        if($gke != trim($cve)){
                                            $con_value_counter++;
                                        }                                    
                                    }
                                }
                                if($con_value_counter == 0){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                            case 'one of':
                                $con_value_exp = explode('|',$con_value);
                                $get_key_exp = $get_key ? explode('|',$get_key) : null;
                                $con_value_counter = 0;
                                if($get_key_exp){
                                    foreach($get_key_exp as $gke){
                                        $gke_explode = explode(',',$gke);
                                        foreach($gke_explode as $gkee){
                                            foreach($con_value_exp as $cve){
                                                if($gkee == trim($cve)){
                                                    $con_value_counter++;
                                                }                                    
                                            }
                                        }
                                    }
                                }
                                if($con_value_counter > 0){
                                    $con_score++;
                                }
                                if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score [$con_value_counter]"; }
                                if($debug){ $debug_log[] = "Value Explode:"; }
                                if($debug){ $debug_log[] = $con_value_exp; }
                                if($debug){ $debug_log[] = "Key Explode:"; }
                                if($debug){ $debug_log[] = $get_key_exp; }
                                if($debug){ $debug_log[] = $gke_explode; }
                                break;
                            case 'not null':
                                if($get_key){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                            case 'is null':
                                if($get_key){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                            case 'greater than':
                                if($get_key > $con_value){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                            case 'less than':
                                if($get_key < $con_value){
                                    $con_score++;
                                    if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                }
                                break;
                        endswitch;
                        break;
                            
                        case 'question_response':
                            switch($con_condition):
                                case 'equals':
                                    if(isset($args['user_screen_result']['general_score_data'][$con_key]) && $args['user_screen_result']['general_score_data'][$con_key] == $con_value){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }
                                    break;
                                case 'contains':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && str_contains($args['user_screen_result']['general_score_data'][$con_key], $con_value) ){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }                                
                                    break;
                                case 'starts with':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && str_starts_with(isset($args['user_screen_result']['general_score_data'][$con_key]), $con_value)){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }                                     
                                    break;
                                case 'ends with':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && str_ends_with($args['user_screen_result']['general_score_data'][$con_key], $con_value)){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }                                      
                                    break;
                                case 'does not equal':
                                    if(isset($args['user_screen_result']['general_score_data'][$con_key]) && $args['user_screen_result']['general_score_data'][$con_key] != $con_value){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }                                
                                    break;
                                case 'does not contain':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && !str_contains($args['user_screen_result']['general_score_data'][$con_key], $con_value)){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }                                           
                                    break;
                                case 'does not start with':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && !str_starts_with($args['user_screen_result']['general_score_data'][$con_key], $con_value)){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }                                         
                                    break;
                                case 'does not end with':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && !str_ends_with($args['user_screen_result']['general_score_data'][$con_key], $con_value)){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }                                     
                                    break;
                                case 'exists':
                                case 'not null':
                                    if(isset($args['user_screen_result']['general_score_data'][$con_key])){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }                                     
                                    break;
                                case 'is null':
                                    if(!isset($args['user_screen_result']['general_score_data'][$con_key])){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }                                     
                                    break;
                                case 'none of':
                                    $con_value_exp = explode('|',$con_value);
                                    $con_value_counter = 0;
                                    foreach($con_value_exp as $cve){
                                        if($args['result_title'] != trim($cve)){
                                            $con_value_counter++;
                                        }                                    
                                    }
                                    if($con_value_counter == 0){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }
                                    break;
                                case 'one of':
                                    $con_value_exp = explode('|',$con_value);
                                    $con_value_counter = 0;
                                    foreach($con_value_exp as $cve){
                                        if($args['result_title'] == trim($cve)){
                                            $con_value_counter++;
                                        }                                    
                                    }
                                    if($con_value_counter > 0){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }
                                    break;
                                case 'greater than':
                                    if(isset($args['user_screen_result']['general_score_data'][$con_key]) && $args['user_screen_result']['general_score_data'][$con_key] > $con_value){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }
                                    break;
                                case 'less than':
                                    if(isset($args['user_screen_result']['general_score_data'][$con_key]) && $args['user_screen_result']['general_score_data'][$con_key] < $con_value){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }
                                    break;

                            endswitch;
                            break;
                        
                        case 'demographic_response':                            
                            switch($con_condition):
                                case 'equals':
                                    if( isset($args['answered_demos'][$con_key]) ){
                                        $temp_score = 0;
                                        foreach($args['answered_demos'][$con_key] as $ck){
                                            if($ck == $con_value){
                                                $temp_score++;
                                            }
                                        }
                                        if( $temp_score == 1 ){
                                            $con_score++;
                                            if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                        }  
                                    }
                                    break;
                                case 'contains':
                                    if(isset($args['answered_demos'][$con_key]) ){
                                        foreach($args['answered_demos'][$con_key] as $dr){
                                            if($dr == $con_value){
                                                $con_score++;
                                                if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                            }
                                        }
                                    }                            
                                    break;
                                case 'does not equal':
                                    if(isset($args['answered_demos'][$con_key]) ){
                                        $total_drs = $args['answered_demos'][$con_key];
                                        $temp_score = 0;
                                        foreach($args['answered_demos'][$con_key] as $dr){
                                            if($dr != $con_value){
                                                $temp_score++;
                                            }
                                        }
                                        if($total_drs == $temp_score){
                                            $con_score++;
                                            if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                        }
                                    }                       
                                    break;
                                case 'exists':
                                case 'not null':
                                    if(isset($args['answered_demos'][$con_key])){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }                                     
                                    break;
                                case 'is null':
                                    if(!isset($args['answered_demos'][$con_key])){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }                                     
                                    break;
                                case 'none of':
                                    $con_value_exp = explode('|',$con_value);
                                    $con_value_counter = 0;
                                    foreach($con_value_exp as $cve){
                                        if($args['result_title'] != trim($cve)){
                                            $con_value_counter++;
                                        }                                    
                                    }
                                    if($con_value_counter == 0){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }
                                    break;
                                case 'one of':
                                    if( isset($args['answered_demos'][$con_key]) ){
                                        $temp_score = 0;
                                        $con_value_exp = explode('|',$con_value);
                                        foreach($con_value_exp as $cke){
                                            foreach($args['answered_demos'][$con_key] as $ck){
                                                if($ck == $cke){
                                                    $temp_score++;
                                                }
                                            }
                                        }
                                        if( $temp_score >= 1 ){
                                            $con_score++;
                                        }  
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score VS $temp_score"; }   
                                    }                               
                                    break;
                                case 'greater than':
                                    if(isset($args['answered_demos'][$con_key])){
                                        $demo_value = $args['answered_demos'][$con_key];
                                        // Handle array (sum values) or scalar value
                                        if(is_array($demo_value)){
                                            $demo_value = array_sum($demo_value);
                                        }
                                        // Convert to numeric for comparison
                                        $demo_value = is_numeric($demo_value) ? (float)$demo_value : 0;
                                        $con_value_num = is_numeric($con_value) ? (float)$con_value : 0;
                                        if($demo_value > $con_value_num){
                                            $con_score++;
                                            if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $demo_value > $con_value_num)  / $con_score"; }
                                        }
                                    }
                                    break;
                                case 'less than':
                                    if(isset($args['answered_demos'][$con_key])){
                                        $demo_value = $args['answered_demos'][$con_key];
                                        // Handle array (sum values) or scalar value
                                        if(is_array($demo_value)){
                                            $demo_value = array_sum($demo_value);
                                        }
                                        // Convert to numeric for comparison
                                        $demo_value = is_numeric($demo_value) ? (float)$demo_value : 0;
                                        $con_value_num = is_numeric($con_value) ? (float)$con_value : 0;
                                        if($demo_value < $con_value_num){
                                            $con_score++;
                                            if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $demo_value < $con_value_num)  / $con_score"; }
                                        }
                                    }
                                    break;
                                    
                            endswitch;
                            break;

                    break;
                endswitch;

                $i++;
            endwhile;
            endif;
            
            //echo "#$row_index. $group_title -- Score: $i == $con_score<hr />";

            // Operator check
            if(
                $next_step_test_operator == 'and' && $i == $con_score ||
                $next_step_test_operator == 'or' && $con_score > 0 
            ){
                $proceed = true;                    
            }

            if($proceed == true){
                
                // Get all the link group data
                $links = get_sub_field('links');  

                if(!$randomize && $links){
                    shuffle($links);
                }
            
                $return['results'][$next_result_index]['group_title'] = get_sub_field('link_group_title');      
                $return['results'][$next_result_index]['additional_result_text'] = get_sub_field('additional_result_text');   
                $return['results'][$next_result_index]['partner_next_steps'] = $is_partner_source;   
                $return['additional_result_text'][] = array(
                    'group_title' => get_sub_field('link_group_title'),
                    'text'        => get_sub_field('additional_result_text')
                );   

                if($debug){ $debug_log[] = get_sub_field('link_group_title').' Success'; }

                $ctas = get_sub_field('cta');                 
                $return['results'][$next_result_index]['ctas'] = $ctas ? $ctas : null;  

                $counter = 1;
                if($links){
                    foreach($links as $l){
                        $return['results'][$next_result_index]['links'][$counter] = $l;
                        $counter++;
                    }
                }

                $next_result_index++;
            }

        endwhile;
        endif;

        if(isset($return['results'])){
            // Only set heading from conditional results if screen heading wasn't already set
            // (screen featured links take precedence)
            if(empty($return['heading']) && !empty($heading)){
                $return['heading'] = $heading;
            } elseif(empty($return['heading'])){
                // Fallback to conditional heading or default
                $return['heading'] = !empty($heading) ? $heading : 'Next Steps';
            }
            $return['hide_group_titles'] = get_sub_field('hide_group_titles');
            if(!$randomize_group){
                // Preserve screen featured links at the beginning, shuffle the rest
                $screen_result = !empty($screen_featured_links) && isset($return['results'][0]) ? $return['results'][0] : null;
                $other_results = !empty($screen_featured_links) ? array_slice($return['results'], 1) : $return['results'];
                shuffle($other_results);
                if($screen_result !== null){
                    $return['results'] = array_merge([$screen_result], $other_results);
                } else {
                    $return['results'] = $other_results;
                }
            }
        }

    endwhile;
    endif;

    // Collect all link IDs from every matching next_step_links row (for manual/extra links pool)
    $return['all_conditional_link_ids'] = array();
    if(isset($return['results'])){
        foreach($return['results'] as $r){
            if(isset($r['links']) && is_array($r['links'])){
                foreach($r['links'] as $link_val){
                    $id = is_object($link_val) && isset($link_val->ID) ? (int) $link_val->ID : (is_numeric($link_val) ? (int) $link_val : null);
                    if($id !== null){
                        $return['all_conditional_link_ids'][] = $id;
                    }
                }
            }
        }
        $return['all_conditional_link_ids'] = array_values(array_unique($return['all_conditional_link_ids']));
    }
    
    // Add demographic_next_steps links as regular featured links if we don't have enough
    if(isset($return['results'])){
        // Count total links from existing results
        $total_existing_links = 0;
        foreach($return['results'] as $r){
            if(isset($r['links'])){
                $total_existing_links += count($r['links']);
            }
        }
        
        // Determine max links needed (4 if single group, 2 if multiple groups)
        $groups_with_links = 0;
        foreach($return['results'] as $r){
            if(isset($r['links']) && !empty($r['links'])){
                $groups_with_links++;
            }
        }
        $max_links_needed = $groups_with_links > 1 ? 2 : 4;
        
        // Add demographic_next_steps links if we need more
        if($total_existing_links < $max_links_needed && !$is_partner_source){
            $demo_steps_for_featured = [];
            $unused_conditional_link_ids = [];
            if(!empty($return['all_conditional_link_ids'])){
                // Links that are in results but beyond max_links_needed per group (not shown in featured slots)
                $displayed_link_ids = array();
                foreach($return['results'] as $r){
                    if(isset($r['links']) && is_array($r['links'])){
                        $i = 1;
                        while($i <= $max_links_needed){
                            if(isset($r['links'][$i])){
                                $lv = $r['links'][$i];
                                $id = is_object($lv) && isset($lv->ID) ? (int) $lv->ID : (is_numeric($lv) ? (int) $lv : null);
                                if($id !== null) $displayed_link_ids[] = $id;
                            }
                            $i++;
                        }
                    }
                }
                $unused_conditional_link_ids = array_values(array_diff($return['all_conditional_link_ids'], $displayed_link_ids));
            }
            
            // Get already used link IDs to exclude (all links currently in results; normalize to ID)
            $used_link_ids = [];
            foreach($return['results'] as $r){
                if(isset($r['links'])){
                    foreach($r['links'] as $link_val){
                        $id = is_object($link_val) && isset($link_val->ID) ? (int) $link_val->ID : (is_numeric($link_val) ? (int) $link_val : null);
                        if($id !== null) $used_link_ids[] = $id;
                    }
                }
            }
            
            // Get demographic_next_steps links (screen-specific first, then global)
            // Screen specific demo steps
            $demo_data_screen = get_mha_demo_steps( $args['user_screen_result']['screen_id'], $args['user_screen_result']['answered_demos'] );
            foreach($demo_data_screen['demo_steps'] as $demo_step){
                if(is_object($demo_step) && isset($demo_step->ID)){
                    $demo_steps_for_featured[] = $demo_step->ID;
                } elseif(is_numeric($demo_step)){
                    $demo_steps_for_featured[] = $demo_step;
                }
            }
            
            // Global demo steps
            $demo_data_global = get_mha_demo_steps( 'options', $args['user_screen_result']['answered_demos'] );
            foreach($demo_data_global['demo_steps'] as $demo_step){
                if(is_object($demo_step) && isset($demo_step->ID)){
                    $demo_steps_for_featured[] = $demo_step->ID;
                } elseif(is_numeric($demo_step)){
                    $demo_steps_for_featured[] = $demo_step;
                }
            }
            
            // Remove duplicates
            $demo_steps_for_featured = array_unique($demo_steps_for_featured);
            
            // Remove already used links from demographic pool
            $demo_steps_for_featured = array_diff($demo_steps_for_featured, $used_link_ids);
            $demo_steps_for_featured = array_values($demo_steps_for_featured); // Re-index array
            
            // Build pool to fill: first unused conditional (manual) links, then demographic
            $fill_pool = array_merge($unused_conditional_link_ids, $demo_steps_for_featured);
            $fill_pool = array_diff($fill_pool, $used_link_ids);
            $fill_pool = array_values($fill_pool);
            
            // Add manual/conditional + demographic links to existing result entry when we need more
            // This prevents creating a new group which would reduce max_links from 4 to 2
            if(!empty($fill_pool)){
                // Find existing result entry with empty group_title (screen featured links entry)
                $target_row_index = null;
                foreach($return['results'] as $idx => $r){
                    if(isset($r['group_title']) && $r['group_title'] === '' && isset($r['links'])){
                        $target_row_index = $idx;
                        break;
                    }
                }
                
                // If no empty group_title entry exists, create a new one
                if($target_row_index === null){
                    $target_row_index = count($return['results']);
                    $return['results'][$target_row_index]['group_title'] = ''; // No group title, like screen featured links
                    $return['results'][$target_row_index]['additional_result_text'] = '';
                    $return['results'][$target_row_index]['partner_next_steps'] = false;
                    $return['results'][$target_row_index]['links'] = [];
                }
                
                // Get the current highest link index in the target entry
                $current_max_index = 0;
                if(isset($return['results'][$target_row_index]['links']) && !empty($return['results'][$target_row_index]['links'])){
                    $current_max_index = max(array_keys($return['results'][$target_row_index]['links']));
                }
                
                // Add manual (conditional) + demographic links to the existing entry
                $counter = $current_max_index + 1;
                $links_to_add = $max_links_needed - $total_existing_links;
                foreach($fill_pool as $fill_link_id){
                    if($counter > ($current_max_index + $links_to_add)){
                        break;
                    }
                    $return['results'][$target_row_index]['links'][$counter] = $fill_link_id;
                    $counter++;
                }
            }
        }
    }

    //pre($return);

    if( isset($return['results']) || isset($return['additional_result_text']) ):
        //$additional_result_text = [];
        $link_groups = [];
        $used_links = [];
        $ctas = [];

        // Result Text
        if(isset($return['results']) && $return['results']):
            foreach($return['results'] as $r){
                //$additional_result_text[] = $r['additional_result_text'];

                // CTAs
                if(isset($r['ctas'])){
                    foreach($r['ctas'] as $c){
                        $ctas[] = $c;
                    }
                }            
            }
        endif;


        // Next Step Links
        //$total_result_groups = count($return['results']);
        //$max_links = $total_result_groups > 1 ? 2 : 4;

        // Not all groups have links, so we only want to count those
        $groups_with_links = 0;
        if(isset($return['results']) && $return['results']):
            foreach($return['results'] as $r){
                if(isset($r['links'])){
                    $groups_with_links++;
                }
            }
        endif;
        // When multiple groups: take 2 per group first, then top up to 4 total from groups that have more (so 1 manual + 3 link group is possible; 3 manual + 3 link group = 2+2)
        $max_links_single_group = 4;
        $count = 1;
        if(isset($return['results']) && $return['results']):
            if ( $groups_with_links > 1 ) {
                // Phase 1: take up to 2 from each group
                foreach($return['results'] as $r){
                    $group_link_count = isset($r['links']) && is_array($r['links']) ? count($r['links']) : 0;
                    $take = min( 2, $group_link_count );
                    for ( $i = 1; $i <= $take; $i++ ) {
                        if ( isset( $r['links'][$i] ) ) {
                            $used_links[] = $r['links'][$i];
                            $link_groups[$r['group_title']][$count] = $r['links'][$i];
                            if ( isset( $r['partner_next_steps'] ) ) {
                                $link_groups['partner_source'][$count] = $r['partner_next_steps'];
                            }
                            $count++;
                        }
                    }
                }
                // Phase 2: if total < 4, add one more from any group that has a 3rd link (until we reach 4)
                $total_used_links = count( $used_links );
                $slots_needed = 4 - $total_used_links;
                if ( $slots_needed > 0 ) {
                    foreach ( $return['results'] as $r ) {
                        if ( $slots_needed <= 0 ) break;
                        $group_link_count = isset($r['links']) && is_array($r['links']) ? count($r['links']) : 0;
                        $already_taken = isset($link_groups[$r['group_title']]) ? count($link_groups[$r['group_title']]) : 0;
                        if ( $already_taken >= 2 && $group_link_count >= 3 && isset($r['links'][3]) ) {
                            $used_links[] = $r['links'][3];
                            $link_groups[$r['group_title']][$count] = $r['links'][3];
                            if ( isset( $r['partner_next_steps'] ) ) {
                                $link_groups['partner_source'][$count] = $r['partner_next_steps'];
                            }
                            $count++;
                            $slots_needed--;
                        }
                    }
                }
            } else {
                // Single group: take up to 4
                foreach($return['results'] as $r){
                    $group_link_count = isset($r['links']) && is_array($r['links']) ? count($r['links']) : 0;
                    $max_links_this_group = min( $max_links_single_group, $group_link_count );
                    $i = 1;
                    while ( $i <= $max_links_this_group ) {
                        if ( isset( $r['links'][$i] ) ) {
                            $used_links[] = $r['links'][$i];
                            $link_groups[$r['group_title']][$count] = $r['links'][$i];
                            if ( isset( $r['partner_next_steps'] ) ) {
                                $link_groups['partner_source'][$count] = $r['partner_next_steps'];
                            }
                            $count++;
                        }
                        $i++;
                    }
                }
            }
        endif;

        // Calculate variables needed for extra links section
        $total_used_links = count($used_links);
        // Total desired = 4 (fill remaining with Additional Resources if needed)
        $total_desired_links = 4;
        $count_diff = max(0, $total_desired_links - $total_used_links);
        // For payload: when multiple groups we now allow up to 3 per group if group has ≤3 links
        $max_links = $groups_with_links > 1 ? 3 : 4;
        $extra_links = [];
        $extra_links_ids = null;
        $original_count = $count;

        // Overflow conditional links (beyond max_links per group) — exposed in payload so template can pass to mha_results_related_articles (e.g. $related_article_args_2); not added to this section's Additional Resources
        $overflow_conditional_link_ids = array();
        if(!empty($return['all_conditional_link_ids'])){
            $used_link_ids_flat = array_map(function($v){ return is_object($v) && isset($v->ID) ? (int)$v->ID : (is_numeric($v) ? (int)$v : null); }, $used_links);
            $used_link_ids_flat = array_filter($used_link_ids_flat);
            $overflow_conditional_link_ids = array_values(array_diff($return['all_conditional_link_ids'], $used_link_ids_flat));
        }

        // Call related_articles only when short on links (fill to total_desired_links)
        if($count_diff > 0 && !$is_partner_source){

            $demo_steps = [];
            $excluded_ids = []; // Initialize excluded_ids array
            $espanol = get_field('espanol', $args['user_screen_result']['screen_id']); // Spanish page
            $partner_var = get_query_var('partner'); // Partner layout overrides
            $iframe_var = get_query_var('iframe'); // Template flags when site is viewed in an iframe
            $layout = get_layout_array(get_query_var('layout')); // Used for A/B testing

            // Featured Extra Links
            foreach($used_links as $ul){
                $excluded_ids[] = $ul;
            }
            
            // Global Default Options
            $global_hide_articles = get_field('global_hide_articles', 'options');
            if($global_hide_articles){
                foreach($global_hide_articles as $gha){
                    $excluded_ids[] = $gha;
                }
            }

            $screen_results_hide_articles = get_field('screen_results_hide_articles', 'options');
            if($screen_results_hide_articles){
                foreach($screen_results_hide_articles as $srha){
                    $excluded_ids[] = $srha;
                }
            }

            $url_exclude = get_query_var('exclude_ids');
            if($url_exclude){
                $url_exclude_array = explode(',',$url_exclude);
                foreach($url_exclude_array as $ue){
                    $excluded_ids[] = $ue;
                }
            }

            // Screen specific demo steps
            $demo_data = get_mha_demo_steps( $args['user_screen_result']['screen_id'], $args['user_screen_result']['answered_demos'] );      
            foreach($demo_data['excluded_ids'] as $ex){ 
                $excluded_ids[] = $ex;
            }
            foreach($demo_data['demo_steps'] as $e){
                $demo_steps[] = $e;
            }

            // Global demo steps
            $demo_data_global = get_mha_demo_steps( 'options', $args['user_screen_result']['answered_demos'] );
            foreach($demo_data_global['demo_steps'] as $e){
                $demo_steps[] = $e;
            }

            // Only existing manual (overflow is passed by template to its own related_articles call)
            $next_step_manual = isset($args['user_screen_result']['next_step_manual']) && is_array($args['user_screen_result']['next_step_manual']) ? $args['user_screen_result']['next_step_manual'] : array();

            $related_article_args = array(
                'demo_steps'         => $demo_steps,
                'next_step_manual'   => $next_step_manual,
                'user_screen_result' => $args['user_screen_result'],
                'excluded_ids'       => $excluded_ids,
                'next_step_terms'    => $args['user_screen_result']['next_step_terms'],
                'espanol'            => $espanol,
                'iframe_var'         => $iframe_var,
                'partner_var'        => $partner_var,
                'total'              => 4,
                'style'              => 'featured',
                'hide_all'           => true,
                'layout'             => $layout,
                'answered_demos'     => $args['user_screen_result']['answered_demos']
            );
            $extra_links_result = mha_results_related_articles( $related_article_args );
            if($extra_links_result){
                $extra_links_result_decoded = json_decode($extra_links_result);
                if( isset($extra_links_result_decoded->link_groups)){
                    $extra_links_ids = $extra_links_result_decoded->link_groups->related_links;
                    if(isset($extra_links_result_decoded->link_groups->related_links)){
                        $new_i = 1;
                        foreach($extra_links_ids as $eli){
                            if($new_i > $count_diff){
                                break;
                            }
                            $used_links[] = $eli;
                            $link_groups['Additional Resources'][$count] = $eli;
                            $link_groups['partner_source'][$count] = false; // Extra links are never from partner
                            $count++;
                            $new_i++;
                        }

                    }
                }
            }
        }

        //shuffle_assoc($link_groups);

        $results = array(
            'original_count' => $original_count,
            'total_used_links' => $total_used_links,
            'count_diff' => $count_diff,
            'max_links' => $max_links,
            'heading' => (!empty($return['heading'])) ? $return['heading'] : 'Next Steps',
            'hide_group_titles' => isset($return['hide_group_titles']) ? $return['hide_group_titles'] : 0,
            'link_groups' => $link_groups,
            'additional_result_text' => isset($return['additional_result_text']) ? $return['additional_result_text'] : '',
            'used_links' => $used_links,
            'ctas' => $ctas,
            'is_partner_source' => $is_partner_source,
            'overflow_conditional_link_ids' => isset($overflow_conditional_link_ids) ? $overflow_conditional_link_ids : array()
        );

        if($debug){ pre($debug_log); }
        return json_encode( $results, false, JSON_UNESCAPED_SLASHES );  

    endif;

    if($debug){ pre($debug_log); }
    return false;

}


function display_featured_next_steps( $args ){

    // Args
    $defaults = array(
        'heading' => '',
        'hide_group_titles' => 0,
        'link_groups' => array(),
        'additional_result_text' => [],
        'show_title' => true,
        'is_partner_source' => false
    );   
    $args = wp_parse_args( $args, $defaults );

    // Convert link_groups to array if it's an object
    if(is_object($args['link_groups'])) {
        $args['link_groups'] = (array)$args['link_groups'];
    }

    $return_html = '';
    $count = 1;

    // Result Text (link_group_title is not shown here — only with featured-next-steps-test-group below)
    if(!empty($args['additional_result_text'])){
        foreach($args['additional_result_text'] as $item){
            // Support both { group_title, text } and legacy plain string
            $addl_text = is_array($item) && isset($item['text']) ? $item['text'] : (is_object($item) && isset($item->text) ? $item->text : $item);
            // Strip shortcodes and scripts
            $addl_text = strip_shortcodes($addl_text);
            $addl_text = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $addl_text);
            if($addl_text != ''){
                $partner_class = $args['is_partner_source'] ? ' partner-source' : '';
                $return_html .= '<div class="featured-next-steps-test-additional-text'.$partner_class.'">';
                if($partner_class){
                    $return_html .= '<div class="bubble round-tl cerulean normal"><div class="inner">';
                }
                $return_html .= $addl_text;
                if($partner_class){
                    $return_html .= '</div></div>';
                }
                $return_html .= '</div>';
                $count++;
            }
        }
    }

    if($args['link_groups']):

        // Next Step Links
        $link_groups = $args['link_groups'];
        $total_result_groups = count($link_groups);
        $max_links = $total_result_groups > 1 ? 2 : 4;

        $return_html .= '<div class="featured-next-steps-test-container mt-5 mb-5">';
        if($args['show_title']):
            $return_html .= '<h2 class="section-title dark-blue bold mb-3">'.$args['heading'].'</h2>';
        endif;
        $display_group_keys = array_values(array_filter(array_keys($link_groups), function($key){ return $key !== 'partner_source'; }));
        // Hide "Additional Resources" subtitle when it would be the only subtitle (e.g. manual link + Additional Resources only)
        $other_group_keys = array_filter($display_group_keys, function($key){ return $key !== 'Additional Resources'; });
        $has_true_link_group = ! empty( array_filter( $other_group_keys, function( $key ) { return trim( (string) $key ) !== ''; } ) );
        $hide_additional_resources_heading = ! $has_true_link_group;
        $count = 1;
        foreach($link_groups as $k => $v){
            // Skip the partner status group
            if($k === 'partner_source') continue;
            
            $i = 1;
            $return_html .= '<div class="featured-next-steps-test-group">';
            if(!$args['hide_group_titles']){
                if(!($k === 'Additional Resources' && $hide_additional_resources_heading)){
                    $return_html .= '<p class="mt-4 mb-3">'.$k.'</p>';
                }
            }
            $return_html .= '<ol>';

            // Convert v to array if it's an object
            $display_links = is_object($v) ? (array)$v : $v;
            foreach($display_links as $lk => $lv){
                $partner_class = '';
                if(isset($link_groups['partner_source'])) {
                    $partner_class = $link_groups['partner_source'] ? ' partner-source' : '';
                }
                $return_html .= '<li class="link-item mb-3'.$partner_class.'"><a class="button green thin round mr-3 rec-screen-featured-test" href="'.add_query_arg( 'order', $count, get_the_permalink($lv) ).'">'.get_the_title($lv).'</a></li>';
                $count++;
            }
            $return_html .= '</ol>';
            $return_html .= '</div>';
        }
        $return_html .= '</div>';

    endif;

    if($return_html != ''){
        return $return_html;  
    } 
    
    return false;
    
}