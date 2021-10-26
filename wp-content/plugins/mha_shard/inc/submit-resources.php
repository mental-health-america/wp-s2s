<?php



/**
 * Custom Article Submission Form
 */
function mha_submit_article_form_display(){
	
	parse_str($_POST['data'], $data);  
    $isAuthentic = wp_verify_nonce( $data['snonce'], 'showForm');
	
	$captcha_verify = 'https://www.google.com/recaptcha/api/siteverify?secret=6LftXuYZAAAAAPlFM99le3nsKdZOrySkjVaSOInT&response='.$data['g-recaptcha-response'].'&remoteip=user_ip_address';
	$captcha_response = wp_remote_get($captcha_verify);
	$captcha_body = wp_remote_retrieve_body( $captcha_response );
	$captcha_result = json_decode( $captcha_body );

	// Submission is good, proceed
	if($isAuthentic && $captcha_result->success){
		acf_form(array(
			'post_id'           => 'new_post',                
			'post_content'      => true,
			'post_title'        => true,
			'honeypot'          => true,
			'return' 			=> esc_url_raw($data['return']),
			'new_post'          => array(
				'post_type'     => 'article',
				'post_status'   => 'draft'
			),
			'fields' => array(
				'field_616dd717809c1', // Excerpt **

				'field_5fd3eec524b34', // Type
				'field_5fd3f1a935255', // DIY Type
				'field_5fea345c4d25c', // DIY Issue
				'field_5fd3f7a3951ad', // Treatment Type
				'field_5fd3eef624b35', // Area Served
				'field_5fdc0a1448b13', // Service Type
				'field_5fd3ef47dab98', // Location
				'field_6008b40cc7ec1', // Whole State

				'field_60077079157b4', // All Conditions
				'field_5fea2f2863cdb', // Condition

				'field_616f23c49f9db', // Tags **
				'field_5fea2f4463cdc', // Age

				'field_5fea2f6663cdd', // Featured Image

				'field_5fea2f7063cde', // Link
				//'field_5fedf6b5b7dc2', // Link Text

				'field_60a80c3047984', // Customer Service Email
				//'field_60a80c3947985', // Customer Service Contact Form
				'field_60a80c4447986', // Customer Service Phone

				'pricing_information', // Pricing
				'field_5fea3372584f9', // Privacy
				'field_5fea337d584fa', // Disclaimer
				'field_5fea327fa3cc0', // Accolades
				
				'field_5fea2e5763cd8', // Contact Name
				'field_5fea359ded711', // Contact Title
				'field_5fea2ee063cd9', // Contact Email
				'field_5fea2ee963cda', // Contact Phone

			),
			'submit_value'  => 'Submit Resource for Review'
		)); 
	} else {
		echo 'Verification failed. Please try later.';
	}

	exit();
}
add_action("wp_ajax_nopriv_mha_submit_article_form_display", "mha_submit_article_form_display");
add_action("wp_ajax_mha_submit_article_form_display", "mha_submit_article_form_display");
