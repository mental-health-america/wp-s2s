<?php


function mha_get_user_screen_results( $user_screen_id = null, $related_articles = false ) {

    // Return early if no valid user_screen_id provided
    if ( empty( $user_screen_id ) ) {
        return array(
            'user_screen_id' => null,
            'total_score' => 0,
            'your_answers' => '',
            'result_terms' => [],
            'required_result_tags' => [],
            'has_advanced_conditions' => 0,
            'advanced_condition_row' => '',
            'screen_id' => '',
            'result_title' => '',
            'alert' => 0,
            'general_score_data' => [],
            'text' => null,
            'graph_data' => [],
            'answered_demos' => [],
            'featured_cta' => [],
            'next_step_terms' => [],
            'next_step_manual' => [],
            'admin_user_result' => null,
            'featured_next_steps_data' => null,
            'referer' => null
        );
    }

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
    $user_screen_results['referer'] = null;
    $user_screen_results['featured_next_steps_data'] = null;
    $with_related_articles = $related_articles;
    $your_answers = [];
    $your_answers_temp = [];

    // Get entry object
    $data = GFAPI::get_entry( $user_screen_id );

    // Potential additional entry object
    $token = null;
    $additional_entry_id = null;

    // Got a good response, proceed!
    if($data && !is_wp_error($data)){
        
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
            if (isset($field->label) && strpos($field->label, 'Token') !== false) {     
                // $token = $v;
            }

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
                                //$your_answers[$row] = '<div class="row pb-2"><div class="col-12 text-gray">'.$extra_label_field->content.'</div></div>';

                                if(!str_contains( $extra_label_field->cssClass, 'screen-only' )){
                                    $your_answers_temp[$row]['id'] = $field->id;
                                    $your_answers_temp[$row]['type'] = 'extra';
                                    $your_answers_temp[$row]['css'] = 'extra-row row pb-2';
                                    $your_answers_temp[$row]['question'] = $extra_label_value;
                                    $your_answers_temp[$row]['answer'] = $extra_label_field->content;
                                    $row++;
                                    break;
                                }
                            }
                        }
                    }

                    $has_indent = strpos($field->cssClass, 'indent') !== false ? ' pl-5' : ' pl-0';

                    //$your_answers[$row] = '<div class="row pb-4'.$has_indent.'"><div class="col-sm-7 col-12 text-gray">'.$label.'</div><div class="col-sm-5 col-12 bold caps text-dark-blue">'.$value_label.''.$value_extra.'</div></div>';
                    if (isset($field->cssClass) && strpos($field->cssClass, 'question') !== false) {    
                        $your_answers_temp[$row]['id'] = $field->id;
                        $your_answers_temp[$row]['type'] = 'question';
                        $your_answers_temp[$row]['css'] = 'question-row row pb-4'.$has_indent;
                        $your_answers_temp[$row]['question'] = $label;
                        //$your_answers_temp[$row]['field'] = $field;

                        if(strpos($field->cssClass, 'question-optional') !== false){
                            $your_answers_temp[$row]['answer'] = $v;
                        } else if(strpos($field->cssClass, 'hide-score') !== false){
                            $your_answers_temp[$row]['answer'] = $value_label;
                        } else {
                            $your_answers_temp[$row]['answer'] = $value_label.''.$value_extra;
                        }
                    }

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
            if(trim($data[$k] ?? '') != '' && isset($field->label)){
                $user_screen_results['answered_demos'][$field->label][] = $data[$k];
            }
            
            $row++;
            
        }   

        // Get additional entries in case of chained forms
        /*
        if($token){
            global $wpdb;
            $token_ref = $token.'_ref';
            $additional_entry_id = $wpdb->get_var("SELECT entry_id FROM wp_gf_entry_meta WHERE meta_value = '$token_ref' ORDER BY id DESC LIMIT 1"); 
            $additional_data = GFAPI::get_entry( $additional_entry_id );

            $user_screen_results['additional_data'] = $additional_data; // Debug
            $user_screen_results['additional_entry_id'] = $additional_entry_id; // Debug

            if($additional_data):
                foreach($additional_data as $k => $v):
                    try {
                        $field = GFFormsModel::get_field( $additional_data['form_id'], $k );
                        if(trim($additional_data[$k] ?? '') != '' && isset($field->label)){
                            if(!isset($user_screen_results['answered_demos'][$field->label])){
                                $user_screen_results['answered_demos'][$field->label][] = $additional_data[$k];
                            }
                        }  
                    } catch (\Throwable $th) {
                        //throw $th;
                    }
                endforeach;
            endif;
        }
        */

        // Your Answers HTML
        $merged_answers = mergeDuplicates($your_answers_temp);
        $user_screen_results['your_answers_temp'] = $merged_answers;

        $form = GFFormsModel::get_form_meta( (int) $data['form_id'] );
        $form_classes = ! empty( $form['cssClass'] ) ? preg_split( '/\s+/', trim( $form['cssClass'] ) ) : array();
        $use_trp_answers = in_array( 'trp-answers', $form_classes, true );
        if ( $use_trp_answers ) {
            $trp_context_slug = mha_trp_gf_parse_context_slug( $form['cssClass'] ?? '', (int) $data['form_id'] );
        }

        foreach($merged_answers as $ya){     
            $temp_answer = isset($ya['answer']) ? removeTextBetween($ya['answer'], ' (e.g.', ')') : '';
            if($ya['type'] == 'extra'){
                $your_answers[] = '<div class="'.$ya['css'].'"><div class="col-12 text-gray">'.$temp_answer.'</div></div>';
            } else if ( $use_trp_answers ) {
                $display_answer = mha_trp_format_result_answer( $temp_answer, $trp_context_slug );
                $your_answers[] = '<div class="'.$ya['css'].'"><div class="col-sm-7 col-12 text-gray">'.$ya['question'].'</div><div class="col-sm-5 col-12 bold text-dark-blue">'.$display_answer.'</div></div>';
            } else {
                $your_answers[] = '<div class="'.$ya['css'].'"><div class="col-sm-7 col-12 text-gray">'.$ya['question'].'</div><div class="col-sm-5 col-12 bold text-dark-blue">'.$temp_answer.'</div></div>';
            }
        }

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

/**
 * Wrap result answer labels for TranslatePress when trp-answers is enabled.
 *
 * @param string $answer       Answer text, optionally with a trailing " (score)".
 * @param string $context_slug Context slug from trp-ctx-{slug} or form-{id} fallback.
 * @return string
 */
function mha_trp_format_result_answer( $answer, $context_slug ) {
	$answer = trim( (string) $answer );
	if ( '' === $answer ) {
		return '';
	}

	$parts = preg_split( '/,\s*/', $answer );
	foreach ( $parts as $i => $part ) {
		$part = trim( $part );
		if ( '' === $part ) {
			unset( $parts[ $i ] );
			continue;
		}

		$label_text   = $part;
		$score_suffix = '';

		if ( preg_match( '/^(.*)\s\((\d+)\)$/', $part, $matches ) ) {
			$label_text   = trim( $matches[1] );
			$score_suffix = ' (' . $matches[2] . ')';
		}

		$choice_key = mha_trp_gf_build_choice_key( array( 'text' => $label_text ) );
		if ( '' === $choice_key ) {
			$parts[ $i ] = esc_html( $part );
			continue;
		}

		$context_class = 'trp-ctx-' . $context_slug . '-' . $choice_key;
		$parts[ $i ]   = mha_trp_gf_build_translation_block_markup( $context_class, esc_html( $label_text ) ) . $score_suffix;
	}

	return implode( ', ', $parts );
}
