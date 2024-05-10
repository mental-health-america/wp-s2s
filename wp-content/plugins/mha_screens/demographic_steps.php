<?php
function get_mha_demo_steps( $screen_id = null, $answered_demos ){

    $screen_demos = get_field('demographic_next_steps', $screen_id);
    $demo_data = [];
    $demo_data['ctas'] = [];
    $demo_data['demo_steps'] = [];
    $demo_data['excluded_ids'] = [];
    $demo_data['pre_exclude'] = [];
    $demo_data['debug'] = [];

    $counter = 1;
    
    if( $screen_demos ):
    foreach( $screen_demos as $step ):
        
        $admin_note = $step['admin_note'];
        $operator = $step['operator'];
        $conditions = $step['conditions'];
        $conditions_total = is_countable($conditions) ? count($conditions) : 0; 
        $exclusions_total = 0; 
        $exclusions_met = 0;
        $links = $step['links'];      
        $i = 0;

        if($conditions && $conditions_total):
            foreach($conditions as $con):

                // Overrides
                if($con['key'] == 'screen_id'){
                    $con['key'] = 'Screen ID';
                }
                if($con['key'] == 'user_result'){
                    $con['key'] = 'User Result';
                }

                /*
                if(isset($con['exclude']) && $con['exclude'] == 1){
                    if($links){
                        foreach($links as $link){
                            $demo_data['pre_exclude'][] = $link->ID;
                        }
                    }
                }
                */

                // Updated Exclusion Counting
                if(isset($con['exclude']) && $con['exclude'] == 1){
                    $exclusions_total++;
                }

                // Is
                if($con['condition'] == 'is'){            
                    $is_counter = 0;
                    if (strpos($con['value'], '|') !== false) {
                        $is_array = explode('|',$con['value']);
                    } else {
                        $is_array = [];
                        $is_array[] = $con['value'];
                    }      
                    foreach($is_array as $ia){
                        if( isset($answered_demos[$con['key']]) && in_array( $ia, $answered_demos[$con['key']] )){
                            $is_counter++;
                        }
                    }            
                    if( isset($answered_demos[$con['key']]) && $is_counter == count($is_array) && $is_counter == count($answered_demos[$con['key']])){
                        $i++;
                        if(isset($con['exclude']) && $con['exclude'] == 1){
                            $exclusions_met++;
                        }
                    }
                }

                // Is Not
                else if($con['condition'] == 'is_not'){              
                    $is_counter = 0;    
                    if (strpos($con['value'], '|') !== false) {
                        $is_array = explode('|',$con['value']);
                    } else {
                        $is_array = [];
                        $is_array[] = $con['value'];
                    }      
                    foreach($is_array as $ia){  
                        if( isset($answered_demos[$con['key']]) && in_array( $ia, $answered_demos[$con['key']] )){
                            $is_counter++;
                        }
                    }         
                    if($is_counter == 0){
                        $i++;
                        if(isset($con['exclude']) && $con['exclude'] == 1){
                            $exclusions_met++;
                        }
                    }
                }
                
                // Contains
                else if($con['condition'] == 'contain'){
                    if(isset($answered_demos[$con['key']])){
                        foreach($answered_demos[$con['key']] as $ad){
                            if (strpos(strtolower($ad), strtolower($con['value'])) !== false) {
                                $i++;
                                if(isset($con['exclude']) && $con['exclude'] == 1){
                                    $exclusions_met++;
                                }
                            }
                        } 
                    }
                }
                
                // Not Contains
                else if($con['condition'] == 'not_contain'){      
                    if(isset($answered_demos[$con['key']])){                          
                        foreach($answered_demos[$con['key']] as $ad){
                            if (strpos(strtolower($ad), strtolower($con['value'])) === false) {
                                $i++;
                                if(isset($con['exclude']) && $con['exclude'] == 1){
                                    $exclusions_met++;
                                }
                            }
                        } 
                    }
                }

                // Any Of
                else if($con['condition'] == 'any'){                    
                    $any_counter = 0;
                    if (strpos($con['value'], '|') !== false) {
                        $any_array = explode('|',$con['value']);
                    } else {
                        $any_array = [];
                        $any_array[] = $con['value'];
                    }                    
                    foreach($any_array as $a){
                        if( isset( $answered_demos[$con['key']] ) && in_array( $a, $answered_demos[$con['key']] ) ){
                            $any_counter++;
                        }
                    }
                    if($any_counter > 0){
                        $i++;
                        if(isset($con['exclude']) && $con['exclude'] == 1){
                            $exclusions_met++;
                        }
                    }
                }

                // None Of
                else if($con['condition'] == 'none'){                                
                    $any_counter = 0;
                    if (strpos($con['value'], '|') !== false) {
                        $any_array = explode('|',$con['value']);
                    } else {
                        $any_array = [];
                        $any_array[] = $con['value'];
                    }                    
                    foreach($any_array as $a){
                        if( isset($answered_demos[$con['key']]) && in_array( $a, $answered_demos[$con['key']] ) ){
                            $any_counter++;
                        }
                    }
                    if($any_counter == 0){
                        $i++;
                        if(isset($con['exclude']) && $con['exclude'] == 1){
                            $exclusions_met++;
                        }
                    }
                }

                // Less Than < & Greater Than >
                else if($con['condition'] == 'less' || $con['condition'] == 'greater'){  
                    $number_con = preg_replace("/[^0-9]/", '', $con['value'] );                    
                    if(isset($answered_demos[$con['key']])):
                        if (strpos($answered_demos[$con['key']][0], '-') !== false) {
                            $number_ans = explode('-',$answered_demos[$con['key']][0]);
                            $number_ans = preg_replace("/[^0-9]/", '', $number_ans[0] );
                        } else {
                            $number_ans = preg_replace("/[^0-9]/", '', $answered_demos[$con['key']][0] );
                        }  
                    else:
                        $number_ans = 0;
                    endif;

                    if($con['condition'] == 'greater'){  
                        if($number_ans > $number_con){
                            $i++;
                            if(isset($con['exclude']) && $con['exclude'] == 1){
                                $exclusions_met++;
                            }
                        } 
                    }
                    if($con['condition'] == 'less'){  
                        if($number_ans < $number_con){
                            $i++;
                            if(isset($con['exclude']) && $con['exclude'] == 1){
                                $exclusions_met++;
                            }
                        } 
                    }

                }

                // Null & Not Null
                else if($con['condition'] == 'is_null' || $con['condition'] == 'not_null'){  
                    if($con['condition'] == 'is_null'){
                        if( !isset($answered_demos[$con['key']]) ){
                            $i++;
                            if(isset($con['exclude']) && $con['exclude'] == 1){
                                $exclusions_met++;
                            }
                        }
                    }
                    if($con['condition'] == 'not_null'){
                        if( isset($answered_demos[$con['key']]) && count($answered_demos[$con['key']]) > 0 ){
                            $i++;
                            if(isset($con['exclude']) && $con['exclude'] == 1){
                                $exclusions_met++;
                            }
                        }
                    }
                }

            endforeach;


            // Print the Results
            $final_condition_total = $conditions_total - $exclusions_total;
            
            // Debug
            /*
            echo '<ul>';
                echo '<li>#'.$counter.')';
                    echo '<ul>';
                    echo "<li>Admin Note: $admin_note</li>";
                    echo "<li>Conditions Met: $i</li>";
                    echo "<li>Exclusions Met: $exclusions_met</li>";
                    echo "<li>Operator: $operator</li>";
                    echo "<li>Total Conditions: $conditions_total</li>";
                    echo "<li>Total Exclusions: $exclusions_total</li>";
                    echo "<li>Condition Threshold: $final_condition_total</li>";
                    echo '</ul>';
                echo '</li>';
            echo '</ul>';
            */

            if($operator == 'or' && $i > 0 && $exclusions_met == 0 || $operator == 'and' && $i == $final_condition_total){
                foreach($links as $link){
                    if(get_post_type( $link->ID ) == 'cta'){
                        $demo_data['ctas'][] = $link->ID;
                    } else {
                        if( !in_array($link->ID, $demo_data['excluded_ids']) && !in_array($link->ID, $demo_data['pre_exclude'])){
                            $demo_data['demo_steps'][] = $link;
                        } else {
                            $demo_data['excluded_ids'][] = $link->ID;
                        }
                    }   
                }
            }
            
        endif;

        $counter++;

    endforeach;
    endif;

    //return;
    return $demo_data;

}