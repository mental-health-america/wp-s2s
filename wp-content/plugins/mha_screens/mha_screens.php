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
				$total_score = $total_score + $v; // Add to total score				
				$html .= '<p>';
					$html .= '<strong>'.$label.'</strong><br />';
					$html .= $value_label.' ('.$v.')';
				$html .= '</p>';
			}

			// Warning message counter
			if (isset($field->cssClass) && strpos($field->cssClass, 'alert') !== false) {    
				if($v > 0){
					$alert++;
				}  
			}
			
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
		$html .= '<div><h2>Interpretation of Scores</h2>'.get_field('interpretation_of_scores', $screen_id).'</div>';

		// Title (based on score)
		$header = '';
        if( have_rows('results', $screen_id) ):
		while( have_rows('results', $screen_id) ) : the_row();
			$min = get_sub_field('score_range_minimum');
			$max = get_sub_field('score_range_max');				
			if($total_score >= $min && $total_score <= $max){		

				// Result Header
				$header .= '<div>Your score was</div><h1 style="margin-top: 0; padding-top: 0;"><strong>'.get_sub_field('result_title').'</strong></h1>';
				$header .= get_sub_field('result_content');

				// Link back to results page
				$header .= '<p><a href="'.get_site_url().'/screening-results/?sid='.$user_screen_id.'">View your results online and see next steps</a></p>';
		
			}
		endwhile;
		endif;
		return $header.''.$html;

	}
	
	return false;

}