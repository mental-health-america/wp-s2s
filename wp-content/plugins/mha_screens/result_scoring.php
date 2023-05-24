<?php

add_action( 'gform_after_submission', 'mha_screen_submission_update_entry_with_results', 10, 2 );
function mha_screen_submission_update_entry_with_results( $entry, $form ) {

    $user_screen_result = getUserScreenResults( $entry['id'] );  

    // Result Display
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

            // Result Title
            if( get_field('survey', $user_screen_result['screen_id']) && !get_field('show_survey_results', $user_screen_result['screen_id']) ){
                // Survey Result
                $result = get_field('results', $user_screen_result['screen_id']);
                $result_title = $result[0]['result_title'];
            } else {
                // Test Results
                $result_title = get_sub_field('result_title');
            }

            // Additional scores
            $additional_scores = array();
            if(have_rows('additional_results', $user_screen_result['screen_id'])):
                // Specific Score Groups
                while( have_rows('additional_results', $user_screen_result['screen_id']) ) : the_row();  
                    $add_scores = get_sub_field('scores');
                    $add_score_total = 0;
                    $add_score_max = 0;
                    foreach($add_scores as $score){
                        $add_score_total = intval($user_screen_result['general_score_data'][$score['question_id']]) + $add_score_total;
                        $add_score_max = $add_score_max + $user_screen_result['max_values'][$score['question_id']];
                    }                                            
                    $additional_scores[] = strval($add_score_total);
                endwhile;

            endif;

            // Update the entry with the user score and result
            $updateScreenArray = array(
                'entry_id'          => $entry['id'],
                'user_score'        => strval($user_screen_result['total_score']),
                'user_result'       => $result_title,
                'additional_scores' => $additional_scores
            );
            updateUserScreenResults( $updateScreenArray );
        }

    endwhile;

}



function mha_screen_submission_update_entry_with_results_test( $entry, $form ) {

    $user_screen_result = getUserScreenResults( $entry['id'] );  

    // Result Display
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

            // Result Title
            if( get_field('survey', $user_screen_result['screen_id']) && !get_field('show_survey_results', $user_screen_result['screen_id']) ){
                // Survey Result
                $result = get_field('results', $user_screen_result['screen_id']);
                $result_title = $result[0]['result_title'];
            } else {
                // Test Results
                $result_title = get_sub_field('result_title');
            }

            // Additional scores
            $additional_scores = array();
            if(have_rows('additional_results', $user_screen_result['screen_id'])):
                // Specific Score Groups
                while( have_rows('additional_results', $user_screen_result['screen_id']) ) : the_row();  
                    $add_scores = get_sub_field('scores');
                    $add_score_total = 0;
                    $add_score_max = 0;
                    foreach($add_scores as $score){
                        // TODO FIX THIS
                        $add_score_total = intval($user_screen_result['general_score_data'][$score['question_id']]) + $add_score_total;
                        $add_score_max = $add_score_max + $user_screen_result['max_values'][$score['question_id']];
                    }                                            
                    $additional_scores[] = strval($add_score_total);
                endwhile;

            endif;

            // Update the entry with the user score and result
            $updateScreenArray = array(
                'entry_id'          => $entry['id'],
                'user_score'        => strval($user_screen_result['total_score']),
                'user_result'       => $result_title,
                'additional_scores' => $additional_scores
            );
            //updateUserScreenResults( $updateScreenArray );
        }

    endwhile;

}