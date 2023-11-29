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

    $debug = false;
    $debug_log = [];
    
    if( have_rows('featured_next_steps_test', $args['user_screen_result']['screen_id']) ):
    while( have_rows('featured_next_steps_test', $args['user_screen_result']['screen_id']) ) : the_row();
        
        $heading = get_sub_field('next_steps_heading');
        $randomize = get_sub_field('dont_randomize_order');
        $randomize_group = get_sub_field('dont_randomize_group_order');

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
                                    if(isset($args['answered_demos'][$con_key]) && $args['answered_demos'][$con_key] > $con_value){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
                                    }
                                    break;
                                case 'less than':
                                    if(isset($args['answered_demos'][$con_key]) && $args['answered_demos'][$con_key] < $con_value){
                                        $con_score++;
                                        if($debug){ $debug_log[] = "#$row_index. $group_title - $con_type / $con_condition ($con_key : $con_value)  / $con_score"; }
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
            
                $return['results'][$row_index]['group_title'] = get_sub_field('link_group_title');      
                $return['results'][$row_index]['additional_result_text'] = get_sub_field('additional_result_text');   
                $return['additional_result_text'][] = get_sub_field('additional_result_text');   

                if($debug){ $debug_log[] = get_sub_field('link_group_title').' Success'; }

                $ctas = get_sub_field('cta');                 
                $return['results'][$row_index]['ctas'] = $ctas ? $ctas : null;  

                $counter = 1;
                if($links){
                    foreach($links as $l){
                        $return['results'][$row_index]['links'][$counter] = $l;
                        $counter++;
                    }
                }

            }

        endwhile;
        endif;

        if(isset($return['results'])){
            $return['heading'] = $heading;
            $return['hide_group_titles'] = get_sub_field('hide_group_titles');
            if(!$randomize_group){
                shuffle($return['results']);
            }
        }

    endwhile;
    endif;

    //pre($return);

    if( isset($return['results']) || isset($return['additional_result_text']) ):
        //$additional_result_text = [];
        $link_groups = [];
        $used_links = [];
        $ctas = [];

        // Result Text
        if($return['results']):
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
        if($return['results']):
            foreach($return['results'] as $r){
                if(isset($r['links'])){
                    $groups_with_links++;
                }
            }
        endif;
        $max_links = $groups_with_links > 1 ? 2 : 4;

        $count = 1;
        if($return['results']):
            foreach($return['results'] as $r){
                $i = 1;
                while($i <= $max_links){
                    if(isset($r['links'][$i])){ // In case there are less than the $max_links
                        $used_links[] = $r['links'][$i];
                        $link_groups[$r['group_title']][$count] = $r['links'][$i];
                        $count++;
                    }
                    $i++;
                }
            }
        endif;

        // In case of not enough links
        $total_used_links = count($used_links);
        $count_diff = $max_links - $total_used_links;
        $extra_links = [];
        $extra_links_ids = null;
        $original_count = $count;
        if($total_used_links < $max_links){

            $demo_steps = [];
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

            // Related Articles
            $related_article_args = array(
                'demo_steps'         => $demo_steps,
                'next_step_manual'   => $args['user_screen_result']['next_step_manual'],
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
                            $count++;
                            $i++;
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
            'heading' => $return['heading'],
            'hide_group_titles' => $return['hide_group_titles'],
            'link_groups' => $link_groups,
            'additional_result_text' => $return['additional_result_text'],
            'used_links' => $used_links,
            'ctas' => $ctas
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
        'additional_result_text' => []
    );   
    $args = wp_parse_args( $args, $defaults );

    $return_html = '';

    // Result Text
    foreach($args['additional_result_text'] as $addl_text){
        $return_html .= '<div class="featured-next-steps-test-additional-text">'.$addl_text.'</div>';
    }
    
    if($args['link_groups']):

        // Next Step Links
        $link_groups = (array)$args['link_groups'];
        $total_result_groups = count($link_groups);
        $max_links = $total_result_groups > 1 ? 2 : 4;

        $return_html .= '<div class="featured-next-steps-test-container mt-5 mb-5">';
        $return_html .= '<h2 class="section-title dark-blue bold mb-3">'.$args['heading'].'</h2>';
        $count = 1;
        foreach($link_groups as $k => $v){
            $i = 1;
            $return_html .= '<div class="featured-next-steps-test-group">';
            if(!$args['hide_group_titles']){
                $return_html .= '<p class="mt-4 mb-3">'.$k.'</p>';
            }
            $return_html .= '<ol>';

            $display_links = (array)$v;
            foreach($display_links as $lk => $lv){
                $return_html .= '<li class="link-item mb-3"><a class="button green thin round mr-3 rec-screen-featured-test" href="'.add_query_arg( 'order', $count, get_the_permalink($lv) ).'">'.get_the_title($lv).'</a></li>';
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