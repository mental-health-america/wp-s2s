<?php
/**
 * DIY Tools
 */

// Enqueing Scripts
add_action('init', 'mhaDiyToolsScripts');
function mhaDiyToolsScripts() {
	wp_enqueue_script('process_mhaDiyTools', plugin_dir_url( __FILE__ ).'mha_diy_tools.js', 'jquery', time(), true);
	wp_localize_script('process_mhaDiyTools', 'do_mhaDiyTools', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

/**
 * Submitting and Answer
 */
add_action("wp_ajax_nopriv_mhaDiySubmit", "mhaDiySubmit");
add_action("wp_ajax_mhaDiySubmit", "mhaDiySubmit");
function mhaDiySubmit(){

	// General variables
    $result = [];

    // Post data
    $defaults = array(
        'nonce'            => null,
        'response_id'      => null,
        'activity_id'      => null,
        'submit'           => 0,
    );    
    parse_str($_POST['data'], $data);  
    $args = wp_parse_args( $data, $defaults ); 
	
    $result['args'] = $args;

	// Submission is good, proceed
	if( wp_verify_nonce( $args['nonce'], 'diySubmission') ){
        
		$timestamp = date('Y-m-d H:i:s');		
		$ipiden = get_ipiden();	
		$uid = get_current_user_id() ? get_current_user_id() : 4; // Default "anonymous" user is 4	

        // Draft Already Exists
		$current_post_args = array(
			"post_type" 	 => 'diy_responses',
			"author"		 => $uid,
			"orderby" 		 => 'date', // Get the most recent
			"order"			 => 'DESC', // Get the most recent
			"post_status" 	 => 'draft', // Incomplete thoughts only
			"posts_per_page" => 1,
			"ipiden"         => $ipiden,
        );
		$current_post_loop = new WP_Query($current_post_args);
        $current_post_id = $current_post_loop->found_posts ? $current_post_loop->post->ID : null;
    
        $response_rows = array();
        $answer_count = 0;
        foreach($args as $k => $v){
            if(strpos($k, 'answer_') !== false){
                $r_id = str_replace("answer_", "", $k);
                $answer = sanitize_text_field($args['answer_'.$r_id]);
                if($answer != ''){
                    $answer_count++;
                }
                $new_row = array(
                    'field_634840ef4cbb3'	=> $r_id, // Question ID
                    'field_634857934cbb4' 	=> $answer, // Answer
                    'field_6348579d4cbb5' 	=> $timestamp, // Date
                );
                array_push($response_rows, $new_row);
            }
        }

        if($current_post_id){

            // Post exists, just update it going forward
            $result['post_id'] = $current_post_id;

        } else {

            if($answer_count > 0){

                // Create the new post
                $new_thought = array(
                    'post_title' => $timestamp,
                    'post_status' => 'draft',
                    'post_author' => $uid,
                    'post_type' => 'diy_responses'
                );
                $result['post_id'] = wp_insert_post($new_thought);
                update_field('activity_id', intval($args['activity_id']), $result['post_id']);
                update_field('ipiden', $ipiden, $result['post_id']);

            } else {
                
                $result['error'] = 'You must have at least one question answered before you can submit.';   
                echo json_encode($result);
                exit();
                            
            }

        }

        if($result['post_id']){
            
            $current_responses = get_field('responses', $result['post_id']);
            $result['current_responses'] = $current_responses;
            
            // Add/Update responses
            if($answer_count > 0){

                update_field('responses', $response_rows, $result['post_id']);

                // Publish if its the last question
                if($args['submit'] == 1){	
                    $publish = array(
                        'ID'           => $result['post_id'],
                        'post_status'  => 'publish',
                    );
                    $result['publish'] = wp_update_post( $publish );
                    $result['redirect'] = get_the_permalink($result['post_id']);
                }
            } else {
                $result['error'] = 'You must have at least one question answered before you can submit.';
                echo json_encode($result);
                exit();
            }

        } else {

            $result['error'] = 'There was an error saving your response. Please try again later.';
            echo json_encode($result);
            exit();

        }

    }

    echo json_encode($result);
    exit();
}





