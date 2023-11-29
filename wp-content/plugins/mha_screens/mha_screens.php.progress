<?php
/**
 * Plugin Name: MHA - Screens
 * Plugin URI: https://screening.mhanational.org
 * Version: 1.1
 * Author:  MHA Web Team
 * Author URI: https://screening.mhanational.org
 * Description: Extra scripts for MHA Screens
 */

// General Vars
define( 'MHASCREENS_VERSION', '1.2.1' );

// Enqueing Scripts
add_action('init', 'mhaScreenScripts');
function mhaScreenScripts() {
	wp_enqueue_script('mhaScreen_validate', plugin_dir_url( __FILE__ ).'assets/jquery.validate.min.js', 'jquery', '1.0', true);
	wp_enqueue_script('process_mhaScreenEmail', plugin_dir_url( __FILE__ ).'mha_screens.js', 'jquery', MHASCREENS_VERSION, true);
	wp_localize_script('process_mhaScreenEmail', 'do_mhaScreenEmail', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

function getUserScreenResults( $user_screen_id ) {

    /**
    * Results Scoring
    */

    // Vars
    $user_screen_results['user_screen_results'] = [];
    $user_screen_results['total_score'] = 0;
    $user_screen_results['your_answers'] = '';
    $user_screen_results['result_terms'] = [];
    $user_screen_results['required_result_tags'] = [];
    $user_screen_results['has_advanced_conditions'] = 0;
    $user_screen_results['advanced_condition_row'] = '';
	$user_screen_results['screen_id'] = '';
	$user_screen_results['alert'] = 0;
    $user_screen_results['general_score_data'] = []; 
    $user_screen_results['graph_data'] = []; 
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

            /*
            // Future Vars
            $test_id = $data->id;
            $test_title = get_the_title( $user_screen_results['screen_id'] );
            $test_date = date('M j, Y', strtotime($data->date_created));
            $max_score = get_field('overall_max_score', $user_screen_results['screen_id']);                

            // Graph Data         
            $user_screen_results['graph_data'][$test_title]['labels'][] = $test_date;
            $user_screen_results['graph_data'][$test_title]['scores'][] = $user_screen_results['total_score'];
            $user_screen_results['graph_data'][$test_title]['max'] = $max_score;
            $user_screen_results['graph_data'][$test_title]['steps'] = get_field('chart_steps', $user_screen_results['screen_id'] );

            // Results Display
            $user_screen_results['your_results_display'][$test_title][$count_results]['test_id'] = $test_id;     
            $user_screen_results['your_results_display'][$test_title][$count_results]['test_date'] = $test_date;
            $user_screen_results['your_results_display'][$test_title][$count_results]['test_title'] = $test_title;
            $user_screen_results['your_results_display'][$test_title][$count_results]['total_score'] = $user_screen_results['total_score'];
            $user_screen_results['your_results_display'][$test_title][$count_results]['max_score'] = $max_score;
            $user_screen_results['your_results_display'][$test_title][$count_results]['test_link'] = $test_id;    
            */
            
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
        }
                    
        // Update total score to be the max possible score if its over
        $max_score = get_field('overall_max_score', $user_screen_results['screen_id']);
        if($user_screen_results['total_score'] >= $max_score){
            $user_screen_results['total_score'] = $max_score;
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
        endwhile;

        // If the total advanced conditions don't match the positive matches, reset to the first result
        if($user_screen_results['has_advanced_conditions'] != $advanced_counter){
            $user_screen_results['advanced_condition_row'] = 0;
        }

        while( have_rows('results', $user_screen_results['screen_id']) ) : the_row();            
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
        endwhile;
    endif;

    return $user_screen_results;

}

function mhaScreenEmail(){
    global $wpdb;
    
	// General variables
    $result = array();
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);
	
	// Cleaned Up Data
	$email = sanitize_email($data['email']);
    $screen_id = intval($data['screen_id']);
    $entry_id = intval($data['entry_id']);
    $screen_user_id = sanitize_text_field($data['screen_user_id']);
    
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'mhaScreenEmail');
	
	if($isAuthentic){
	
		// Send the email
		$to = $email;
		$subject = 'Mental Health America '.get_the_title($screen_id).' Results';
		$body = mha_get_screen_email_body( $screen_user_id, $screen_id, $entry_id );
		$headers = array('Content-Type: text/html; charset=UTF-8');	
		$headers[] = 'From: MHA Screening - Mental Health America <screening@mhanational.org>';

		$result['mail'] = wp_mail( $to, $subject, $body, $headers );
		
	} else {
		
		$result['error'] = true;

	}

    echo json_encode($result);

    exit();
}

add_action("wp_ajax_nopriv_mhaScreenEmail", "mhaScreenEmail");
add_action("wp_ajax_mhaScreenEmail", "mhaScreenEmail");

// Get users response for email/plain text
function mha_get_screen_email_body( $user_screen_id, $screen_id, $entry_id ){
    
    // Vars setup
    $user_screen_result = mha_get_user_screen_results( $entry_id, false );

	$html = '';
    $header = '';
    
    // Result Header
    $header .= '<h1 style="margin-top: 0; padding-top: 0;"><strong>'.$user_screen_result['result_title'].'</strong></h1>';

    $intro = str_replace('class="layout-action', 'style="display: none !important; visibility: hidden !important;" class="layout-action', $user_screen_result['text']);
    $intro = str_replace('h3>', 'h2>', $intro);
    $header .= $intro;
    
    $header .= '<p><strong>Overall Score:</strong> '.$user_screen_result['total_score'].' / '.get_field('overall_max_score', $screen_id).'<br />';
    if(count($user_screen_result['additional_scores']) > 0):
        foreach($user_screen_result['additional_scores'] as $addl_score):
            $header .= '<strong>'.$addl_score['title'].'</strong> '.$addl_score['total'].' / '.$addl_score['max'].'<br />';    
        endforeach;
        $header .= '</p>';
    endif;

    // Link back to results page
    $header .= '<p><a href="'.get_site_url().'/screening-results/?sid='.$user_screen_id.'">View your results online and see next steps</a></p>';

    // Body
    $html .= '<h3 class="section-title dark-teal mb-4">Your Answers</h3>';   
    
    $answers_clean = str_replace('class="col-sm-7 col-12 text-gray"', 'style="font-weight: bold;"', $user_screen_result['your_answers']);
    $answers_clean = str_replace('class="col-sm-5 col-12 bold caps text-dark-blue"', '', $answers_clean);
    $answers_clean = str_replace('class="row pb-4 pl-0"', 'style="margin-bottom: 10px;"', $answers_clean);
    $html .= $answers_clean;

    if($user_screen_result['warning'] != ''){
        $html .= '<div style="background: #FA6767; color: #FFF !important; padding: 5px 10px;"><strong>'.$user_screen_result['warning'].'</strong></div>';
    }
    
    $explanation = get_field('interpretation_of_scores', $screen_id);
    $answers_clean = str_replace('h3>', 'h2>', $answers_clean);
    $html .= '<div class="explanation">'.get_field('interpretation_of_scores', $screen_id).'</div>';

    return $header.''.$html;
    
}


// Old email function (Deprecated 10/2023)
function getScreenAnswers( $user_screen_id, $screen_id, $entry_id ){
	
	// Vars
	$result = [];
	$total_score = 0;

	// Future Content
	$html = '';
        
    $data = GFAPI::get_entry( $entry_id );

    if($data){

		// Text
		$label = '';
		$value_label = '';
		$alert = 0;
		$i = 0;         
		$advanced_conditions_data = [];  
		$general_score_data = [];  

		$html .= '<h3 class="section-title dark-teal mb-4">Your Answers</h3>';              
		foreach($data as $k => $v){
			
			// Get field object
			$field = GFFormsModel::get_field( $data['form_id'], $k );  

			// Get referring screen ID                
			if (isset($field->label) && strpos($field->label, 'Screen ID') !== false) {     
				$screen_id = $v;
			}
			
			//Screening Questions
			if (isset($field->cssClass) && strpos($field->cssClass, 'question') !== false) {  
				
                // Advanced Conditions Check
                $get_results = get_field('results', $screen_id);
                if( $get_results ) {
                    foreach($get_results as $result){
                        if($result['advanced_conditions']){
                            foreach($result['advanced_conditions'] as $ac){
                                if($ac['question_id'] == $field->id){
                                    $advanced_conditions_data[$field->id] = $v; 
                                }                                
                            }
                        }
                    }
                }
				
				$label = $field->label; // Field label  
                $max_choice = 0;
                if(isset($field['choices']) && is_array($field['choices'])){
                    foreach($field['choices'] as $choice){
                        if($choice['value'] == $v){
                            $value_label = $choice['text'];
                        }
                        if($choice['value'] > $max_choice){
                            $max_choice = $choice['value'];
                        }
                    }
                }
                $general_score_data['max_values'][$k] = $max_choice;                 
				
				if(isset($field->cssClass) && strpos($field->cssClass, 'exclude') === false){     
					$total_score = $total_score + intval($v); // Add to total score	
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
                            $html .= '<p><strong>'.$extra_label_field->content.'</strong></p>';
                            break;
                        }
                    }
                }
				
				if($v != ''){			

                    if($field->type == 'number'){
                        $value_extra = intval($v);    
                        $value_label = '';    
                    } else {
                        $value_extra = is_numeric($v) ? ' ('.intval($v).')' : '';
                    }

                    $has_indent = strpos($field->cssClass, 'indent') !== false ? ' style="padding-left: 20px;"' : ' style="padding-left: 0;"';

					$html .= '<p'.$has_indent.'>';
						$html .= '<strong>'.$label.'</strong><br />';                        
						$html .= $value_label;
                        $html .= $value_extra;
					$html .= '</p>';

                    // Advanced Conditions Check
                    if(get_sub_field('advanced_condition', $screen_id) && count(get_sub_field('advanced_condition', $screen_id)) > 0){
                        $advanced_conditions_data[$field->id] = $v; 
                    };
                    $general_score_data[$field->id] = $v; 
				}
			}

			// Warning message counter
			if (isset($field->cssClass) && strpos($field->cssClass, 'alert') !== false) {    
				if($v > 0){
					$alert++;
				}  
			}
			
		}   
		
		// Custom Logic Override
		$custom_results_logic = get_field('custom_results_logic', $screen_id);
		$custom_result_row = '';
		if($custom_results_logic){
			$custom_result_logic_data = custom_logic_checker($general_score_data, $custom_results_logic);
			$total_score = $custom_result_logic_data['total_score'];
			$custom_result_row = $custom_result_logic_data['custom_result_row'];
		}

		// Update total score to be the max possible score if its over
		$max_score = get_field('overall_max_score', $screen_id);
		if($total_score >= $max_score){
			$total_score = $max_score;
		}

		// Warning Message
		if($alert > 0){
			$html .= '<div style="background: #FA6767; color: #FFF !important; padding: 5px 10px;"><strong>'.get_field('warning_message', $screen_id).'</strong></div>';
		}

		// Intepretation
		//$html .= '<div><h2>Interpretation of Scores</h2>'.get_field('interpretation_of_scores', $screen_id).'</div>';
		$html .= '<div>'.get_field('interpretation_of_scores', $screen_id).'</div>';

		// Title (based on score)
		$header = '';
		
		$has_advanced_conditions = 0;
		$advanced_condition_row = '';   
		$required_check = '0';
		$advanced_counter = '';
		
		if( have_rows('results', $screen_id) ):
			
			// Advanced Conditions
            while( have_rows('results', $screen_id) ) : the_row();   
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
                                    $advanced_condition_row = get_row_index();
                                    $has_advanced_conditions++;
                                }
                            } else if($advanced_min) {
                                if($advanced_conditions_data[$advanced_id] == $advanced_min){
                                    $advanced_condition_row = get_row_index();
                                    $has_advanced_conditions++;
                                }
                            }
                        }
                    }

                }
            endwhile;

            // If the total advanced conditions don't match the positive matches, reset to the first result
            if($has_advanced_conditions != $advanced_counter){
                $advanced_condition_row = 0;
            }

			while( have_rows('results', $screen_id) ) : the_row();
				$min = get_sub_field('score_range_minimum');
				$max = get_sub_field('score_range_max');
				$custom_logic_condition_row = get_sub_field('custom_logic_condition');

				if($total_score >= $min && $total_score <= $max || $has_advanced_conditions > 0 && $advanced_condition_row == get_row_index() || $custom_results_logic != '' && $custom_result_row == $custom_logic_condition_row ){

					// Advanced Condition Double Check (in case score condition passes)
					if($has_advanced_conditions > 0){
						if($advanced_condition_row != get_row_index()){ 
							continue;
						}
					}

					// Custom Condition Double Check (in case score condition passes)
					if($custom_results_logic != ''){
						if($custom_result_row != $custom_logic_condition_row){ 
							continue;
						}
					}

					// Result Header
					$header .= '<h1 style="margin-top: 0; padding-top: 0;"><strong>'.get_sub_field('result_title').'</strong></h1>';
					$header .= preg_replace('/<form.*?<\/form>/s', '', strip_shortcodes(get_sub_field('result_content')));
					
					if(have_rows('additional_results', $screen_id)):
						$header .= '<p><strong>Overall Score:</strong> '.intval($total_score).'<br />';
                        while( have_rows('additional_results', $screen_id) ) : the_row();  
                            $add_scores = get_sub_field('scores');
                            $add_score_total = 0;
                            $add_score_max = 0;
                            foreach($add_scores as $score){
                                $add_score_total = $general_score_data[$score['question_id']] + $add_score_total;
                                $add_score_max = $add_score_max + $general_score_data['max_values'][$score['question_id']];
                            }

                            $header .= '<strong>'.get_sub_field('title').'</strong> '.intval($add_score_total).' / '.intval($add_score_max).'<br />';
                        endwhile;
                        $header .= '</p>';
                    endif;

					// Link back to results page
					$header .= '<p><a href="'.get_site_url().'/screening-results/?sid='.$user_screen_id.'">View your results online and see next steps</a></p>';
			
				}
			endwhile;

		endif;
		return $header.''.$html;

	}
	
	return false;

}


/**
 * Custom Logic Overrides
 */
function custom_logic_checker($general_score_data, $custom_results_logic) {

	$results = false;

	// Eating Disorder
	if($custom_results_logic == 'eating_disorder'):

		$results = [];

		$total_score = ($general_score_data[49] + $general_score_data[47] + $general_score_data[48] + $general_score_data[50] + $general_score_data[51]) / 5;
		$total_score = round($total_score, 2);
		$results['total_score'] = $total_score;
        $results['admin_user_result'] = 0;

        // Height details
        $height_choice = isset($general_score_data[119]) ? $general_score_data[119] : null; // Height Choice
        $height_ft = isset($general_score_data[124]) ? $general_score_data[124] : null; // Feet
        $height_in = isset($general_score_data[125]) ? $general_score_data[125] : null; // Inches
        $height_cm = isset($general_score_data[126]) ? $general_score_data[126] : null; // Centimeters
        $height_final = null;

        // Weight Details
        $weight_type = isset($general_score_data[130]) ? $general_score_data[130] : null; // lbs or kg
        $entered_weight = isset($general_score_data[67]) ? $general_score_data[67] : null; // lbs
        $weight = null;
        if($weight_type == 'lbs'){
            $weight = $entered_weight;
        } else if( $weight_type == 'kg' ){
            $weight = $entered_weight * 2.20462262185;
        }
        
        if($height_choice == 'feet'){
            if($height_ft != '' || $height_in != ''){
                $height_final = ($height_ft * 12) + $height_in;
            }
        } elseif ($height_choice == 'centimeters'){
            $height_final = ($height_cm / 2.54);
        }

        // BMI Calculation
        if( $height_final != null xor $weight != null){
            $bmi = NULL; // Height/Weight are optional, don't calculate BMI in this instance
        } else if($general_score_data[49] > 0 && $weight){
            $bmi = $height_final / $weight / ( $weight * 703 );
		} else {
			$bmi = 0;
		}
        
		$results['general_score_data'] = $general_score_data;
		$results['bmi_raw'] = $bmi;
		$results['bmi'] = $total_score;
		$results['height_final'] = $height_final;
		$results['height_calcs'] = "Choice:$height_choice, FT:$height_ft, IN: $height_in, CM: $height_cm";
		
        // Test Scoring
        if (($bmi !== NULL && $bmi < 18.5 && $general_score_data[60] == 1) && ($total_score >= 47 || $general_score_data[47] >= 75) && ($total_score >= 47 || $general_score_data[50] >= 66.7)) {
            $custom_result_row = 1; // At Risk for Eating Disorder
            $results['admin_user_result'] = 1; // At Risk for Anorexia Nervosa
        } elseif (($general_score_data[53] > 1) && (($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) > 1) && ($general_score_data[53] >= 12 && ($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) >= 12) && ($total_score >= 47 || $general_score_data[50] >= 66.7)) {
            $custom_result_row = 1; // At Risk for Eating Disorder
            $results['admin_user_result'] = 2; // At Risk for Bulimia Nervosa
        } elseif (($general_score_data[53] > 1) && (($general_score_data[70] + $general_score_data[71] + $general_score_data[72] + $general_score_data[73] + $general_score_data[74]) >= 3) && ($general_score_data[75] >= 4) && (($general_score_data[53] >= 12) && ($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) < 3)) {
            $custom_result_row = 1; // At Risk for Eating Disorder
            $results['admin_user_result'] = 3; // At Risk for Binge Eating Disorder
        } elseif (($bmi !== NULL && $bmi >= 18.5 && $general_score_data[60] == 1) && ($total_score >= 47 || $general_score_data[47] >= 75) && ($total_score >= 47 || $general_score_data[50] >= 66.7)) {
            $custom_result_row = 1; // At Risk for Eating Disorder
            $results['admin_user_result'] = 4; // At Risk for Atypical Anorexia Nervosa
        } elseif (($bmi == NULL && $general_score_data[60] == 1) && ($total_score >= 47 || $general_score_data[47] >= 75) && ($total_score >= 47 || $general_score_data[50] >= 66.7)) {
            $custom_result_row = 1; // At Risk for Eating Disorder
            $results['admin_user_result'] = 5; // At Risk for Anorexia Nervosa (no BMI info)
        } elseif (($general_score_data[53] > 1) && (($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) > 1) && ($general_score_data[53] >= 3 && $general_score_data[53] < 12 && ($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) >= 3 && ($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) < 12) && ($total_score >= 47 || $general_score_data[50] >= 66.7)) {
            $custom_result_row = 1; // At Risk for Eating Disorder
            $results['admin_user_result'] = 6; // At Risk for Subclinical Bulimia Nervosa
        } elseif (($general_score_data[53] > 1) && (($general_score_data[70] + $general_score_data[71] + $general_score_data[72] + $general_score_data[73] + $general_score_data[74]) >= 3) && ($general_score_data[75] >= 4) && (($general_score_data[53] >= 3 && $general_score_data[53] < 12) && ($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) < 3)) {
            $custom_result_row = 1; // At Risk for Eating Disorder
            $results['admin_user_result'] = 7; // At Risk for Subclinical Binge Eating Disorder
        } elseif (($general_score_data[53] == 0) && (($general_score_data[55] + $general_score_data[57]) >= 12)) {
            $custom_result_row = 1; // At Risk for Eating Disorder
            $results['admin_user_result'] = 8; // At Risk for Purging Disorder
        } elseif (($general_score_data[53] >= 3) || (($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) >= 3)) {
            $custom_result_row = 1; // At Risk for Eating Disorder
            $results['admin_user_result'] = 9; // At Risk for Unspecified Feeding or Eating Disorder (UFED)
        } elseif ($total_score >= 47 || $general_score_data[50] >= 66.7 || $general_score_data[47] >= 75) {
            $custom_result_row = 1; // At Risk for Eating Disorder
            $results['admin_user_result'] = 10; // At Risk for Eating Disorder
        } elseif ($general_score_data[61] == 1 || $general_score_data[62] == 1 || $general_score_data[63] == 1) {
            $custom_result_row = 2; // At Risk for Avoidant/Restrictive Food Intake Disorder (ARFID)
            $results['admin_user_result'] = 11; // Avoidant/Restrictive Food Intake Disorder (ARFID)
        } else {
            $custom_result_row = 3; // Low Risk
            $results['admin_user_result'] = 12; // Low Risk
        }         
		$results['custom_result_row'] = $custom_result_row;         

	endif;

    
	// Bipolar
	if($custom_results_logic == 'bipolar'):

		$results = [];

        // Question 1
		$any_time = $general_score_data[47] + $general_score_data[50] + $general_score_data[51] + $general_score_data[52] + $general_score_data[53] + $general_score_data[54] + $general_score_data[55] + $general_score_data[56] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59] + $general_score_data[60];
		
        // Question 2
        $same_period = $general_score_data[61];

        // Question 3
        $problem = $general_score_data[62];

        // Results
        $results['total_score'] = $any_time + $same_period + $problem;
		$custom_result_row = 2; // Bipolar Negative
        if($any_time >= 7 && $same_period > 0 && $problem > 1){
			$custom_result_row = 1; // Bipolar Positive
        }

		$results['custom_result_row'] = $custom_result_row;         

	endif;

	return $results;

}



/**
 * Add User Results to Screen
 */
function updateUserScreenResults( $options ){
    
	// Default Args
    $defaults = array (
        'entry_id'                  => null, 
        'user_score'                => null, 
        'user_result'               => null,
        'additional_scores'         => null,
        'duplicate'                 => null,
        'featured_next_steps_data'  => null,
        'admin_user_result'         => null
    );

    $atts = wp_parse_args( $options, $defaults );

    $entry = GFAPI::get_entry( $atts['entry_id'] );

    if(!is_wp_error($entry)){

        // Vars for later
        $user_score = null;
        $user_result = null;
        $sub_score_1 = null;
        $sub_score_2 = null;
        $sub_score_3 = null;
        $duplicate = null;
        $admin_user_result = null;
        $featured_link_test_data = null;
        
        foreach($entry as $k => $v){
            
            // Get field object
            $field = GFFormsModel::get_field( $entry['form_id'], $k );  

            // User Score
            if (isset($field->label) && strpos($field->label, 'User Score') !== false) { 
                if($atts['user_score'] != $entry[$field->id]){
                    $user_score = strval($atts['user_score']);
                    $entry[$field->id] = $user_score;
                }
            }

            // User Result
            if (isset($field->label) && strpos($field->label, 'User Result') !== false) {  
                if($atts['user_result'] != $entry[$field->id]){
                    $user_result = strval($atts['user_result']);
                    $entry[$field->id] = $user_result;
                }
            }

            // Admin User Result
            if (isset($field->label) && strpos($field->label, 'Admin User Result') !== false) {  
                if($atts['admin_user_result'] != $entry[$field->id]){
                    $admin_user_result = strval($atts['admin_user_result']);
                    $entry[$field->id] = $admin_user_result;
                }
            }

            // Featured Link Test Data
            if (isset($field->label) && strpos($field->label, 'Featured Link Data') !== false) {  
                if($atts['featured_next_steps_data'] != $entry[$field->id]){
                    $featured_link_test_data = strval($atts['featured_next_steps_data']);
                    $entry[$field->id] = $featured_link_test_data;
                }
            }

            // Sub Score 1
            if (isset($field->label) && strpos($field->label, 'Sub Score 1') !== false) {  
                if(isset($atts['additional_scores'][0]) && $atts['additional_scores'][0] != $entry[$field->id]){
                    $sub_score_1 = strval($atts['additional_scores'][0]);
                    $entry[$field->id] = $sub_score_1;
                }
            }
            // Sub Score 2
            if (isset($field->label) && strpos($field->label, 'Sub Score 2') !== false) {  
                if(isset($atts['additional_scores'][1]) && $atts['additional_scores'][1] != $entry[$field->id]){
                    $sub_score_2 = strval($atts['additional_scores'][1]);
                    $entry[$field->id] = $sub_score_2;
                }
            }
            // Sub Score 3
            if (isset($field->label) && strpos($field->label, 'Sub Score 3') !== false) {  
                if(isset($atts['additional_scores'][2]) && $atts['additional_scores'][2] != $entry[$field->id]){
                    $sub_score_3 = strval($atts['additional_scores'][2]);
                    $entry[$field->id] = $sub_score_3;
                }
            }

        }

        // Update the entry if the fields were empty
        if($user_score || $user_result || $sub_score_1 || $sub_score_2 || $sub_score_3 || $duplicate){

            if(isset($entry['created_by']) && $entry['created_by'] != ''){
                $created_by = $entry['created_by'];
            } else {
                $created_by = null;
            }

            $result = GFAPI::update_entry( $entry );
            $result_2 = GFAPI::update_entry_property( $entry['id'], 'created_by', $created_by );

            return true;
        }

    }
    
    return false;

}

/**
 * Pre Submission Handler
 */
add_action( 'gform_pre_submission', 'mha_screening_pre_submission_handler' );
function mha_screening_pre_submission_handler( $form ) {

    // Populate token field with unique ID before save
    foreach( $form['fields'] as $field ) {
        if( $field->label == 'Token' ) {
            $_POST['input_'.$field->id] = wp_generate_uuid4().'-'.date('U', strtotime($form['date_created'])).'_'.$form['id'];
            break;
        }
    }
    
}

// Other Files
include_once 'result_content.php';
include_once 'result_scoring.php';
include_once 'demographic_steps.php';
include_once 'related_articles.php';
include_once 'featured_next_steps.php';