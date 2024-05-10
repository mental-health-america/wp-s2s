<?php


function mha_get_user_screen_results( $user_screen_id = null, $related_articles = false ) {

    /**
    * Results Scoring
    */

    // Vars
    $user_screen_results['user_screen_id'] = $user_screen_id;
    $user_screen_results['total_score'] = 0;
    $user_screen_results['your_answers'] = '';
    $user_screen_results['result_terms'] = [];
    $user_screen_results['required_result_tags'] = [];
    $user_screen_results['has_advanced_conditions'] = 0;
    $user_screen_results['advanced_condition_row'] = '';
	$user_screen_results['screen_id'] = '';
	$user_screen_results['result_title'] = 'asdasd';
	$user_screen_results['alert'] = 0;
    $user_screen_results['general_score_data'] = []; 
    $user_screen_results['text'] = null;
    $user_screen_results['graph_data'] = []; 
    $user_screen_results['answered_demos'] = [];
    $user_screen_results['featured_cta'] = [];
    $user_screen_results['next_step_terms'] = [];
    $user_screen_results['next_step_manual'] = [];
    $user_screen_results['admin_user_result'] = null;
    
    $user_screen_results['featured_next_steps_data'] = null;
    $with_related_articles = $related_articles;
    $your_answers = [];

    // Get entry object
    $search_entries = GFAPI::get_entry( $user_screen_id );

    if($search_entries){

        // Got a good response, proceed!
        $data = $search_entries;
        
        // Text
        $label = '';
        $value_label = '';
        $i = 0;        
        $row = 0;        
        $count_results = 0; 
        $advanced_conditions_data = []; 

        $user_screen_results['result_id'] = $data['id'];
        
        foreach($data as $k => $v){
            
            // Get field object
            $field = GFFormsModel::get_field( $data['form_id'], $k );  

            // Get referring screen ID
            if (isset($field->label) && strpos($field->label, 'Screen ID') !== false) {  
                $user_screen_results['screen_id'] = $v;
            }

            // Referrer/Source Code
            if (isset($field->label) && strpos($field->label, 'Referer') !== false) {  
                $user_screen_results['referer'] = $v;
            }

            // Referrer/Source Code
            if (isset($field->label) && strpos($field->label, 'User Result') !== false) {  
                $user_screen_results['result_title'] = trim($v) == '' ? $v : '';
            }

            // Featured Next Steps Test Record
            if (isset($field->label) && strpos($field->label, 'Featured Link Data') !== false) {  
                $user_screen_results['featured_next_steps_data'] = $v != '' ? $v : null;
            }

            // Get screen token
            /*                  
            if (isset($field->label) && strpos($field->label, 'Token') !== false) {     
                $test_id = $v;
            }
            */

            //Screening Questions
            if (isset($field->cssClass) && strpos($field->cssClass, 'question') !== false) {  
                
                // Advanced Conditions Check
                $get_results = get_field('results', $user_screen_results['screen_id'], false);
                if( $get_results ) {
                    foreach($get_results as $result){
                        if(isset($result['advanced_conditions'])){
                            foreach($result['advanced_conditions'] as $ac){
                                if($ac['question_id'] == $field->id){
                                    $advanced_conditions_data[$field->id] = $v; 
                                }                                
                            }
                        }
                    }
                }

                $user_screen_results['general_score_data'][$field->id] = $v; 

                $label = $field->label; // Field label    
                if(strpos($field->cssClass, 'exclude') === false){             
                    $user_screen_results['total_score'] = $user_screen_results['total_score'] + intval($v); // Add to total score
                }
				// Get label for selected choice
                $max_choice = 0;
				if($field['choices']){
					foreach($field['choices'] as $choice){
						if($choice['value'] == $v){
							$value_label = $choice['text'];
						}
                        if($choice['value'] > $max_choice){
                            $max_choice = $choice['value'];
                        }
					}
                    $user_screen_results['max_values'][$k] = $max_choice;
				}

                if($v != ''){			
                    
                    // Format numbers vs text fields
                    if($field->type == 'number'){
                        $value_extra = intval($v);    
                        $value_label = '';    
                    } else {
                        $value_extra = is_numeric($v) ? ' ('.intval($v).')' : '';
                    }

                    // Prepend a specific HTML field before the results for contextual labelling
                    $extra_label_keys = explode(' ', $field->cssClass);  
                    $extra_label_content = null;
                    foreach($extra_label_keys as $extra_label_value){
                        if (preg_match('|label-above--|', $extra_label_value)) {
                            $extra_label_id = explode('--', $extra_label_value);     
                            $extra_label_content = null;
                            if(is_numeric($extra_label_id[1])){   
                                $extra_label_field = GFFormsModel::get_field( $data['form_id'], $extra_label_id[1] );  
                                $your_answers[$row] = '<div class="row pb-2"><div class="col-12 text-gray">'.$extra_label_field->content.'</div></div>';
                                $row++;
                                break;
                            }
                        }
                    }

                    $has_indent = strpos($field->cssClass, 'indent') !== false ? ' pl-5' : ' pl-0';

                    $your_answers[$row] = '<div class="row pb-4'.$has_indent.'"><div class="col-sm-7 col-12 text-gray">'.$label.'</div><div class="col-sm-5 col-12 bold caps text-dark-blue">'.$value_label.''.$value_extra.'</div></div>';
                }
            }

            // Warning message counter  
            if (isset($field->cssClass) && strpos($field->cssClass, 'alert') !== false) {    
                if($v > 0){
                    $user_screen_results['alert']++;
                }  
            }

            // Taxonomy grabber
            if (isset($field->cssClass) && strpos($field->cssClass, 'taxonomy') !== false) {  
                $term = get_term_by('slug', esc_attr($v), $field->adminLabel);
                if($term){
                    $user_screen_results['result_terms'][$i]['id'] = $term->term_id;
                    $user_screen_results['result_terms'][$i]['taxonomy'] = $field->adminLabel;
                    $i++;
                }
            }

            // All answered questions
            if(trim($search_entries[$k] ?? '') != '' && isset($field->label)){
                $user_screen_results['answered_demos'][$field->label][] = $search_entries[$k];
            }
            
            $row++;
            
        }   

        // Your Answers cleanup
        $user_screen_results['your_answers'] = implode('',$your_answers);
        
        // Custom Logic Override
        $user_screen_results['custom_results_logic'] = get_field('custom_results_logic', $user_screen_results['screen_id']);
        $user_screen_results['custom_result_row'] = '';
        if($user_screen_results['custom_results_logic']){
            $custom_result_logic_data = custom_logic_checker($user_screen_results['general_score_data'], $user_screen_results['custom_results_logic']);
            $user_screen_results['custom_result_logic_data'] = $custom_result_logic_data;
            $user_screen_results['total_score'] = $custom_result_logic_data['total_score'];
            $user_screen_results['custom_result_row'] = $custom_result_logic_data['custom_result_row'];
            if(isset($custom_result_logic_data['admin_user_result'])){
                $user_screen_results['admin_user_result'] = $custom_result_logic_data['admin_user_result'];
            }
        }
                    
        // Update total score to be the max possible score if its over
        $user_screen_results['max_score'] = get_field('overall_max_score', $user_screen_results['screen_id']);
        if($user_screen_results['total_score'] >= $user_screen_results['max_score']){
            $user_screen_results['total_score'] = $user_screen_results['max_score'];
        }

        // Entry Date
        $date_created = new DateTime($data['date_created']);
        $timezone = new DateTimeZone('America/New_York');
        $date_created->setTimezone($timezone);
        $user_screen_results['date'] = $date_created->format('F j, Y, g:i a T');
        
    }

    /**
     * Results Content
     */

    $required_check = '0';
    $advanced_counter = '';

    // Check this result's required tags
    if( have_rows('results', $user_screen_results['screen_id']) ):
        
        // Advanced Conditions
        while( have_rows('results', $user_screen_results['screen_id']) ) : the_row();   
            $advanced_conditions = get_sub_field('advanced_conditions');
            if($advanced_conditions && count($advanced_conditions) > 1){

                $advanced_counter = count($advanced_conditions);

                foreach($advanced_conditions as $ac){
                    $advanced_min = $ac['score_range_minimum'];
                    $advanced_max = $ac['score_range_max'];
                    $advanced_id = $ac['question_id']; 
                    if(isset($advanced_conditions_data[$advanced_id])){
                        if($advanced_max && $advanced_min){
                            if($advanced_conditions_data[$advanced_id] >= $advanced_min && $advanced_conditions_data[$advanced_id] <= $advanced_max ){
                                $user_screen_results['advanced_condition_row'] = get_row_index();
                                $user_screen_results['has_advanced_conditions']++;
                            }
                        } else if($advanced_min) {
                            if($advanced_conditions_data[$advanced_id] == $advanced_min){
                                $user_screen_results['advanced_condition_row'] = get_row_index();
                                $user_screen_results['has_advanced_conditions']++;
                            }
                        }
                    }
                }

            }

            $min = get_sub_field('score_range_minimum');
            $max = get_sub_field('score_range_max');
            if($user_screen_results['total_score'] >= $min && $user_screen_results['total_score'] <= $max || $user_screen_results['has_advanced_conditions'] > 0 && $user_screen_results['advanced_condition_row'] == get_row_index()){

                if($user_screen_results['has_advanced_conditions'] > 0){
                    if($user_screen_results['advanced_condition_row'] != get_row_index()){ 
                        continue;
                    }
                }

                if(get_sub_field('required_tags')){
                    $req = get_sub_field('required_tags');
                    foreach($req as $t){
                        if(in_multiarray($t, $user_screen_results['result_terms'])){
                            $user_screen_results['required_result_tags'][] = $t;
                        }
                    }
                }
            }


        $custom_logic_condition_row = get_sub_field('custom_logic_condition');        
        if(
            $user_screen_results['total_score'] >= $min && $user_screen_results['total_score'] <= $max || 
            $user_screen_results['has_advanced_conditions'] > 0 && $user_screen_results['advanced_condition_row'] == get_row_index() || 
            isset($user_screen_results['custom_results_logic']) && $user_screen_results['custom_results_logic'] != '' && $user_screen_results['custom_result_row'] == $custom_logic_condition_row ){

            // Advanced Condition Double Check (in case score condition passes)
            if($user_screen_results['has_advanced_conditions'] > 0){
                if($user_screen_results['advanced_condition_row'] != get_row_index()){ 
                    continue;
                }
            }

            // Custom Condition Double Check (in case score condition passes)
            if(isset($user_screen_results['custom_results_logic']) && $user_screen_results['custom_results_logic'] != ''){
                if($user_screen_results['custom_result_row'] != $custom_logic_condition_row){ 
                    continue;
                }
            }
            
            // Required Tags Check
            if(empty($user_screen_results['required_result_tags']) && !empty(get_sub_field('required_tags'))){
                continue;
            }

            // Relevant Tags
            if(get_sub_field('relevant_tags')){
                $tags = get_sub_field('relevant_tags');
                foreach($tags as $t){
                    $user_screen_results['next_step_terms'][] = $t;
                }
            }

            // Manual Next Steps
            $next = get_sub_field('featured_next_steps');
            if($next){
                foreach($next as $n){
                    if(isset($n['link']->ID)){
                        $user_screen_results['next_step_manual'][] = $n['link']->ID;
                    }
                }
            }
            
            $featured_cta = get_sub_field('featured_call_to_actions');
            if($featured_cta){
                foreach($featured_cta as $cta){
                    $user_screen_results['featured_cta'][] = $cta;
                }
            }
            
            if($user_screen_results['result_title'] == ''){
                if( get_field('survey', $user_screen_results['screen_id']) && !get_field('show_survey_results', $user_screen_results['screen_id']) ):

                    /** 
                     * Survey 
                     */
                    $result_data = get_field('results', $user_screen_results['screen_id']);
                    $user_screen_results['result_title'] = $result_data[0]['result_title']; 
                    
                else:

                    /** 
                     * Test Results
                     */
                    $user_screen_results['result_title'] = get_sub_field('result_title'); 
                    
                endif; 
            }


            // Result content
            if( get_field('survey', $user_screen_results['screen_id']) && !get_field('show_survey_results', $user_screen_results['screen_id']) ){
                if(isset($user_screen_results[0])){
                    $user_screen_results['text'] = $user_screen_results[0]['result_content'];
                }
            } else {
                $user_screen_results['text'] = get_sub_field('result_content');
            }
            
        
            /**
             * Additional Result Content
             */

            // Additional scores to display
            $user_screen_results['additional_scores'] = [];
            $i = 0;
            if(have_rows('additional_results', $user_screen_results['screen_id'])):
            while( have_rows('additional_results', $user_screen_results['screen_id']) ) : the_row();  
                $add_scores = get_sub_field('scores');
                $add_score_total = 0;
                $add_score_max = 0;
                foreach($add_scores as $score){                    
                    $new_add_score = isset($user_screen_results['general_score_data'][$score['question_id']]) ? intval($user_screen_results['general_score_data'][$score['question_id']]) : 0;
                    $add_score_total = $new_add_score + $add_score_total;
                    if(isset($user_screen_results['max_values'])){
                        $add_score_max = $add_score_max + $user_screen_results['max_values'][$score['question_id']];
                    }                    
                }
                $user_screen_results['additional_scores'][$i]['title'] = get_sub_field('title');
                $user_screen_results['additional_scores'][$i]['total'] = $add_score_total;
                $user_screen_results['additional_scores'][$i]['max'] = intval($add_score_max);              
                $i++;                                
            endwhile;
            endif;

        }

        endwhile;

        // If the total advanced conditions don't match the positive matches, reset to the first result
        if($user_screen_results['has_advanced_conditions'] != $advanced_counter){
            $user_screen_results['advanced_condition_row'] = 0;
        }

    endif;

    // Result Content
    $user_screen_results['footer'] = get_field('results_footer', $user_screen_results['screen_id']);
    $user_screen_results['warning'] = $user_screen_results['alert'] > 0 ? get_field('warning_message', $user_screen_results['screen_id']) : null;
    $user_screen_results['screen_title'] = get_the_title($user_screen_results['screen_id']);


    // Featured Next Step Links
    if(!$user_screen_results['featured_next_steps_data'] && $with_related_articles){

        $featured_next_steps_args = array(
            'user_screen_result' => $user_screen_results,
            'result_title'       => $user_screen_results['result_title'],
            'answered_demos'     => $user_screen_results['answered_demos']
        );            
        $featured_next_steps_data = mha_featured_next_steps_data($featured_next_steps_args);
        if($featured_next_steps_data):
            
            $user_screen_results['featured_next_steps_data'] = $featured_next_steps_data;

        else:

            $demo_steps = [];
            $espanol = get_field('espanol', $user_screen_results['screen_id']); // Spanish page
            $partner_var = get_query_var('partner'); // Partner layout overrides
            $iframe_var = get_query_var('iframe'); // Template flags when site is viewed in an iframe
            $layout = get_layout_array(get_query_var('layout')); // Used for A/B testing

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
            $demo_data = get_mha_demo_steps( $user_screen_results['screen_id'], $user_screen_results['answered_demos'] );      
            foreach($demo_data['excluded_ids'] as $ex){ 
                $excluded_ids[] = $ex;
            }
            foreach($demo_data['demo_steps'] as $e){
                $demo_steps[] = $e;
            }

            // Global demo steps
            $demo_data_global = get_mha_demo_steps( 'options', $user_screen_results['answered_demos'] );
            foreach($demo_data_global['demo_steps'] as $e){
                $demo_steps[] = $e;
            }

            // Related Articles
            $related_article_args = array(
                'demo_steps'         => $demo_steps,
                'next_step_manual'   => $user_screen_results['next_step_manual'],
                'user_screen_result' => $user_screen_results,
                'excluded_ids'       => $excluded_ids,
                'next_step_terms'    => $user_screen_results['next_step_terms'],
                'espanol'            => $espanol,
                'iframe_var'         => $iframe_var,
                'partner_var'        => $partner_var,
                'total'              => 4,
                'style'              => 'featured',
                'hide_all'           => true,
                'layout'             => $layout,
                'answered_demos'     => $user_screen_results['answered_demos']
            );
            $user_screen_results['featured_next_steps_data'] = mha_results_related_articles( $related_article_args );

        endif;

    }

    // Return what we got
    return $user_screen_results;

}
