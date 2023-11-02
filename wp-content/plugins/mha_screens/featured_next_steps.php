<?php

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

    
    if( have_rows('featured_next_steps_test', $args['user_screen_result']['screen_id']) ):
    while( have_rows('featured_next_steps_test', $args['user_screen_result']['screen_id']) ) : the_row();
        
        $heading = get_sub_field('next_steps_heading');
        $randomize = get_sub_field('dont_randomize_order');
        $randomize_group = get_sub_field('dont_randomize_group_order');

        if( have_rows('next_step_links') ):
        while( have_rows('next_step_links') ) : the_row();

            $next_step_test_operator = get_sub_field('operator');
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

                switch($con_type):

                    case 'test_result':
                        switch($con_condition):
                            case 'equals':
                                if($args['result_title'] == $con_value){
                                    $con_score++;
                                }
                                break;
                            case 'contains':
                                if( $args['result_title'] && str_contains($args['result_title'], $con_value) ){
                                    $con_score++;
                                }                                
                                break;
                            case 'starts with':
                                if( $args['result_title'] && str_starts_with($args['result_title'], $con_value)){
                                    $con_score++;
                                }                                     
                                break;
                            case 'ends with':
                                if( $args['result_title'] && str_ends_with($args['result_title'], $con_value)){
                                    $con_score++;
                                }                                      
                                break;
                            case 'does not equal':
                                if($args['result_title'] != $con_value){
                                    $con_score++;
                                }                                
                                break;
                            case 'does not contain':
                                if( $args['result_title'] && !str_contains($args['result_title'], $con_value)){
                                    $con_score++;
                                }                                           
                                break;
                            case 'does not start with':
                                if( $args['result_title'] && !str_starts_with($args['result_title'], $con_value)){
                                    $con_score++;
                                }                                         
                                break;
                            case 'does not end with':
                                if( $args['result_title'] && !str_ends_with($args['result_title'], $con_value)){
                                    $con_score++;
                                }                                     
                                break;
                            case 'exists':
                                if($args['result_title']){
                                    $con_score++;
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
                                }
                                break;
                            case 'not null':
                                if(!$args['result_title']){
                                    $con_score++;
                                }
                                break;
                            case 'greater than':
                                if($args['result_title'] > $con_value){
                                    $con_score++;
                                }
                                break;
                            case 'less than':
                                if($args['result_title'] < $con_value){
                                    $con_score++;
                                }
                                break;
                        endswitch;
                        break;

                    case 'url_parameter':
                        switch($con_condition):
                            case 'equals':
                                if($get_key == $con_value){
                                    $con_score++;
                                }
                                break;
                            case 'contains':
                                if( $get_key && str_contains($get_key, $con_value) ){
                                    $con_score++;
                                }                                
                                break;
                            case 'starts with':
                                if( $get_key && str_starts_with($get_key, $con_value)){
                                    $con_score++;
                                }                                     
                                break;
                            case 'ends with':
                                if( $get_key && str_ends_with($get_key, $con_value)){
                                    $con_score++;
                                }                                      
                                break;
                            case 'does not equal':
                                if($get_key != $con_value){
                                    $con_score++;
                                }                                
                                break;
                            case 'does not contain':
                                if( $get_key && !str_contains($get_key, $con_value)){
                                    $con_score++;
                                }                                           
                                break;
                            case 'does not start with':
                                if( $get_key && !str_starts_with($get_key, $con_value)){
                                    $con_score++;
                                }                                         
                                break;
                            case 'does not end with':
                                if( $get_key && !str_ends_with($get_key, $con_value)){
                                    $con_score++;
                                }                                     
                                break;
                            case 'exists':
                                if($get_key){
                                    $con_score++;
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
                                }
                                break;
                            case 'not null':
                                if(!$get_key){
                                    $con_score++;
                                }
                                break;
                            case 'greater than':
                                if($get_key > $con_value){
                                    $con_score++;
                                }
                                break;
                            case 'less than':
                                if($get_key < $con_value){
                                    $con_score++;
                                }
                                break;
                        endswitch;
                        break;
                            
                        case 'question_response':
                            switch($con_condition):
                                case 'equals':
                                    if(isset($args['user_screen_result']['general_score_data'][$con_key]) && $args['user_screen_result']['general_score_data'][$con_key] == $con_value){
                                        $con_score++;
                                    }
                                    break;
                                case 'contains':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && str_contains($args['user_screen_result']['general_score_data'][$con_key], $con_value) ){
                                        $con_score++;
                                    }                                
                                    break;
                                case 'starts with':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && str_starts_with(isset($args['user_screen_result']['general_score_data'][$con_key]), $con_value)){
                                        $con_score++;
                                    }                                     
                                    break;
                                case 'ends with':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && str_ends_with($args['user_screen_result']['general_score_data'][$con_key], $con_value)){
                                        $con_score++;
                                    }                                      
                                    break;
                                case 'does not equal':
                                    if(isset($args['user_screen_result']['general_score_data'][$con_key]) && $args['user_screen_result']['general_score_data'][$con_key] != $con_value){
                                        $con_score++;
                                    }                                
                                    break;
                                case 'does not contain':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && !str_contains($args['user_screen_result']['general_score_data'][$con_key], $con_value)){
                                        $con_score++;
                                    }                                           
                                    break;
                                case 'does not start with':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && !str_starts_with($args['user_screen_result']['general_score_data'][$con_key], $con_value)){
                                        $con_score++;
                                    }                                         
                                    break;
                                case 'does not end with':
                                    if( isset($args['user_screen_result']['general_score_data'][$con_key]) && !str_ends_with($args['user_screen_result']['general_score_data'][$con_key], $con_value)){
                                        $con_score++;
                                    }                                     
                                    break;
                                case 'exists':
                                case 'not null':
                                    if(isset($args['user_screen_result']['general_score_data'][$con_key])){
                                        $con_score++;
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
                                    }
                                    break;
                                case 'greater than':
                                    if(isset($args['user_screen_result']['general_score_data'][$con_key]) && $args['user_screen_result']['general_score_data'][$con_key] > $con_value){
                                        $con_score++;
                                    }
                                    break;
                                case 'less than':
                                    if(isset($args['user_screen_result']['general_score_data'][$con_key]) && $args['user_screen_result']['general_score_data'][$con_key] < $con_value){
                                        $con_score++;
                                    }
                                    break;

                            endswitch;
                            break;
                        
                        case 'demographic_response':                            
                            switch($con_condition):
                                case 'equals':
                                    if(
                                        isset($args['answered_demos'][$con_key]) && 
                                        count($args['answered_demos'][$con_key]) == 1 &&
                                        $args['answered_demos'][$con_key][0] == $con_value
                                    ){
                                        $con_score++;
                                    }
                                    break;
                                case 'contains':
                                    if(isset($args['answered_demos'][$con_key]) ){
                                        foreach($args['answered_demos'][$con_key] as $dr){
                                            if($dr == $con_value){
                                                $con_score++;
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
                                        }
                                    }                       
                                    break;
                                case 'exists':
                                case 'not null':
                                    if(isset($args['answered_demos'][$con_key])){
                                        $con_score++;
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
                                    }
                                    break;
                                case 'greater than':
                                    if(isset($args['answered_demos'][$con_key]) && $args['answered_demos'][$con_key] > $con_value){
                                        $con_score++;
                                    }
                                    break;
                                case 'less than':
                                    if(isset($args['answered_demos'][$con_key]) && $args['answered_demos'][$con_key] < $con_value){
                                        $con_score++;
                                    }
                                    break;
                                    
                            endswitch;
                            break;

                    break;
                endswitch;

                $i++;
            endwhile;
            endif;
            
            // Operator: "and"
            if(
                $next_step_test_operator == 'and' && $i == $con_score ||
                $next_step_test_operator == 'or' && $con_score > 0 
            ){
                $proceed = true;                    
            }

            if($proceed == true){
                
                // Get all the link group data
                $links = get_sub_field('links');              

                if(!$randomize){
                    shuffle($links);
                }
            
                $return['results'][$row_index]['group_title'] = get_sub_field('link_group_title');      
                $return['results'][$row_index]['additional_result_text'] = get_sub_field('additional_result_text');   

                $ctas = get_sub_field('cta');                 
                $return['results'][$row_index]['ctas'] = $ctas ? $ctas : null;  

                $counter = 1;
                foreach($links as $l){
                    $return['results'][$row_index]['links'][$counter] = $l;
                    $counter++;
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

    if(isset($return['results'])):
        $additional_result_text = [];
        $link_groups = [];
        $used_links = [];
        $ctas = [];

        // Result Text
        foreach($return['results'] as $r){
            $additional_result_text[] = $r['additional_result_text'];
        }

        // CTAs
        foreach($return['ctas'] as $c){
            $ctas[] = $c;
        }

        // Next Step Links
        $total_result_groups = count($return['results']);
        $max_links = $total_result_groups > 1 ? 2 : 4;

        $count = 1;
        foreach($return['results'] as $r){
            $i = 1;
            while($i <= $max_links){
                if(isset($r['links'][$i])){ // In case there are less than the $max_links
                    $used_links[] = $r['links'][$i];
                    $link_groups[$r['group_title']][$count] = $r['links'][$i];
                    $i++;
                    $count++;
                }
            }
        }

        $results = array(
            'heading' => $return['heading'],
            'hide_group_titles' => $return['hide_group_titles'],
            'link_groups' => $link_groups,
            'additional_result_text' => $additional_result_text,
            'used_links' => $used_links,
            'ctas' => $ctas
        );

        return json_encode( $results, false, JSON_UNESCAPED_SLASHES );  
    endif;

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

    if($args['link_groups']):
        $return_html = '';

        // Result Text
        foreach($args['additional_result_text'] as $addl_text){
            $return_html .= '<div class="featured-next-steps-test-additional-text">'.$addl_text.'</div>';
        }

        // Next Step Links
        $total_result_groups = count((array)$args['link_groups']);
        $max_links = $total_result_groups > 1 ? 2 : 4;

        $return_html .= '<div class="featured-next-steps-test-container mt-5 mb-5">';
        $return_html .= '<h2 class="section-title dark-blue bold mb-3">'.$args['heading'].'</h2>';
        $count = 1;
        foreach($args['link_groups'] as $k => $v){
            $i = 1;
            $return_html .= '<div class="featured-next-steps-test-group">';
            if(!$args['hide_group_titles']){
                $return_html .= '<p class="mt-4 mb-3">'.$k.'</p>';
            }
            $return_html .= '<ol>';
            foreach($v as $lk => $lv){
                $return_html .= '<li class="link-item mb-3"><a class="button green thin round mr-3 rec-screen-featured-test" href="'.add_query_arg( 'order', $count, get_the_permalink($lv) ).'">'.get_the_title($lv).'</a></li>';
                $count++;
            }
            $return_html .= '</ol>';
            $return_html .= '</div>';
        }
        $return_html .= '</div>';

        return $return_html;  
    endif;
    
    return false;
    
}