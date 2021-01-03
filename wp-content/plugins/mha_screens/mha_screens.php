<?php
/**
 * Plugin Name: MHA - Screens
 * Plugin URI: https://screening.mhanational.org
 * Version: 1.0
 * Author:  MHA Web Team
 * Author URI: https://screening.mhanational.org
 * Description: Extra scripts for MHA Screens
 */

// General Vars

// Enqueing Scripts
add_action('init', 'mhaScreenScripts');
function mhaScreenScripts() {
	wp_enqueue_script('mhaScreen_validate', plugin_dir_url( __FILE__ ).'assets/jquery.validate.min.js', 'jquery', '1.0', true);
	wp_enqueue_script('process_mhaScreenEmail', plugin_dir_url( __FILE__ ).'mha_screens.js', 'jquery', time(), true);
	wp_localize_script('process_mhaScreenEmail', 'do_mhaScreenEmail', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
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
    $screen_user_id = sanitize_text_field($data['screen_user_id']);
    
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'mhaScreenEmail');
	
	if($isAuthentic){
	
		// Send the email
		$to = $email;
		$subject = 'Mental Health America '.get_the_title($screen_id).' Results';
		$body = getScreenAnswers( $screen_user_id, $screen_id );
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


function getScreenAnswers( $user_screen_id, $screen_id ){
	
	// Vars
	$result = [];
	$total_score = 0;

	// Gravity Forms API Connection
	$consumer_key = 'ck_0edaed6a92a48bea23695803046fc15cfd8076f5';
	$consumer_secret = 'cs_7b33382b0f109b52ac62706b45f9c8e0a5657ced';
	$headers = array( 'Authorization' => 'Basic ' . base64_encode( "{$consumer_key}:{$consumer_secret}" ) );
	//$response = wp_remote_get( 'https://mhascreening.wpengine.com/wp-json/gf/v2/entries/'.$user_screen_id.'?_labels[0]=1&_field_ids[0]=1' , array( 'headers' => $headers ) );	
	$response = wp_remote_get( 'https://mhascreening.wpengine.com/wp-json/gf/v2/entries/?search={"field_filters": [{"key":38,"value":"'.$user_screen_id.'","operator":"contains"}]}', array( 'headers' => $headers ) );
	
	// Future Content
	$html = '';

	// Check the response code.
	if ( wp_remote_retrieve_response_code( $response ) != 200 || ( empty( wp_remote_retrieve_body( $response ) ) ) ){
		
		$result['error'] = 'There was a problem displaying to your results. Please contact us if the issue persists.';
		return false;

	} else {

		// Got a good response, proceed!
		$json = wp_remote_retrieve_body($response);
		$data = json_decode($json);              
		$data = $data->entries[0]; 

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
			$field = GFFormsModel::get_field( $data->form_id, $k );
			
			//Screening Questions
			if (isset($field->cssClass) && strpos($field->cssClass, 'question') !== false) {  
				$label = $field->label; // Field label  
				foreach($field['choices'] as $choice){
					if($choice['value'] == $v){
						$value_label = $choice['text'];
					}
				}                   
				
				if(strpos($field->cssClass, 'exclude') === false){     
					$total_score = $total_score + $v; // Add to total score	
				}
				
				if($v != ''){			
					$html .= '<p>';
						$html .= '<strong>'.$label.'</strong><br />';
						$html .= $value_label.' ('.$v.')';
					$html .= '</p>';

                    // Advanced Conditions Check
                    if(count(get_sub_field('advanced_condition', $screen_id)) > 0){
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
			$html .= $alert;
			$html .= '<div style="background: #FA6767; color: #FFF !important; padding: 5px 10px;"><strong>'.get_field('warning_message', $screen_id).'</strong></div>';
		}

		// Intepretation
		//$html .= '<div><h2>Interpretation of Scores</h2>'.get_field('interpretation_of_scores', $screen_id).'</div>';
		$html .= '<div>'.get_field('interpretation_of_scores', $screen_id).'</div>';

		// Title (based on score)
		$header = '';
		if( have_rows('results', $screen_id) ):
			
			// Advanced Conditions
			while( have_rows('results', $screen_id) ) : the_row();   
			$advanced_conditions = get_sub_field('advanced_conditions');
			if(count($advanced_conditions) > 1){
				foreach($advanced_conditions as $ac){
					$advanced_min = $ac['score_range_minimum'];
					$advanced_max = $ac['score_range_max'];
					$advanced_id = $ac['question_id'];   
					if($advanced_conditions_data[$advanced_id]){
						if($advanced_max){
							if($advanced_conditions_data[$advanced_id] >= $advanced_min && $advanced_conditions_data[$advanced_id] <= $advanced_max ){
								$advanced_condition_row = get_row_index();
							}
						} else if($advanced_min) {
							if($advanced_conditions_data[$advanced_id] == $advanced_min){
								$advanced_condition_row = get_row_index();
							}
						}
					}
				}
				$has_advanced_conditions++;
			}
			endwhile;

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
					$header .= '<div>Your score was</div><h1 style="margin-top: 0; padding-top: 0;"><strong>'.get_sub_field('result_title').'</strong></h1>';
					$header .= get_sub_field('result_content');
					
					if(have_rows('additional_results', $screen_id)):
						$header .= '<p><strong>Overall Score:</strong> '.$total_score.'<br />';
							while( have_rows('additional_results', $screen_id) ) : the_row();  
								$add_scores = get_sub_field('scores');
								$add_score_total = 0;
								foreach($add_scores as $score){
									$add_score_total = $general_score_data[$score['question_id']] + $add_score_total;
								}

								$header .= '<strong>'.get_sub_field('title').'</strong> '.$add_score_total.'<br />';
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

		if($general_score_data[49] > 0){
			$bmi = $general_score_data[67] / $general_score_data[68] / $general_score_data[68] * 703;
		} else {
			$bmi = 0;
		}
		$results['bmi'] = $total_score;
		
		if (($bmi < 18.5 && $general_score_data[60] == 1) && ($total_score >= 47 || $general_score_data[47] >= 75) && ($total_score >= 47 || $general_score_data[50] >= 66.7)) {
			$custom_result_row = 1; // At Risk for Eating Disorder
		} elseif (($general_score_data[53] > 1) && (($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) > 1) && ($general_score_data[53] >= 12 && ($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) >= 12) && ($total_score >= 47 || $general_score_data[50] >= 66.7)) {
			$custom_result_row = 1; // At Risk for Eating Disorder
		} elseif (($general_score_data[53] > 1) && (($general_score_data[70] + $general_score_data[71] + $general_score_data[72] + $general_score_data[73] + $general_score_data[74]) >= 3) && ($general_score_data[75] >= 4) && (($general_score_data[53] >= 12) && ($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) < 3)) {
			$custom_result_row = 1; // At Risk for Eating Disorder
		} elseif (($bmi >= 18.5 && $general_score_data[60] == 1) && ($total_score >= 47 || $general_score_data[47] >= 75) && ($total_score >= 47 || $general_score_data[50] >= 66.7)) {
			$custom_result_row = 1; // At Risk for Eating Disorder
		} elseif (($general_score_data[53] > 1) && (($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) > 1) && ($general_score_data[53] >= 3 && $general_score_data[53] < 12 && ($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) >= 3 && ($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) < 12) && ($total_score >= 47 || $general_score_data[50] >= 66.7)) {
			$custom_result_row = 1; // At Risk for Eating Disorder
		} elseif (($general_score_data[53] > 1) && (($general_score_data[70] + $general_score_data[71] + $general_score_data[72] + $general_score_data[73] + $general_score_data[74]) >= 3) && ($general_score_data[75] >= 4) && (($general_score_data[53] >= 3 && $general_score_data[53] < 12) && ($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) < 3)) {
			$custom_result_row = 1; // At Risk for Eating Disorder
		} elseif (($general_score_data[53] == 0) && (($general_score_data[55] + $general_score_data[57]) >= 12)) {
			$custom_result_row = 1; // At Risk for Eating Disorder
		} elseif (($general_score_data[53] >= 3) || (($general_score_data[55] + $general_score_data[57] + $general_score_data[58] + $general_score_data[59]) >= 3)) {
			$custom_result_row = 1; // At Risk for Eating Disorder
		} elseif ($total_score >= 47 || $general_score_data[50] >= 66.7 || $general_score_data[47] >= 75) {
			$custom_result_row = 1; // At Risk for Eating Disorder
		} elseif ($general_score_data[61] == 1 || $general_score_data[62] == 1 || $general_score_data[63] == 1) {
			$custom_result_row = 2; // At Risk for Avoidant/Restrictive Food Intake Disorder (ARFID)
		} else {
			$custom_result_row = 3; // Low Risk
		}         
		$results['custom_result_row'] = $custom_result_row;         

	endif;

	return $results;

}