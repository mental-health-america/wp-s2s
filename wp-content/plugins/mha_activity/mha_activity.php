<?php
/**
 * Plugin Name: MHA - Thought Activity
 * Plugin URI: https://screening.mhanational.org
 * Version: 1.0
 * Author:  MHA Web Team
 * Author URI: https://screening.mhanational.org
 * Description: Simple form sign up with database backup and easy API integration.
 */

// Enqueing Scripts
add_action('init', 'mhaActivityScripts');
function mhaActivityScripts() {
	wp_enqueue_script('process_mhaActivity', plugin_dir_url( __FILE__ ).'mha_activity.js', 'jquery', time(), true);
	wp_localize_script('process_mhaActivity', 'do_mhaActivity', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

function thoughtSubmission(){
    
	// General variables
    $result = array();
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);  
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'thoughtSubmission');
	
	// Submission is good, proceed
	if($isAuthentic){
			
		// Organize our data
		$result['response'] = $data;
		$timestamp = date('Y-m-d H:i:s');		
		$ipiden = md5($_SERVER['REMOTE_ADDR']);	
		$uid = get_current_user_id();	
		if(!$uid){
			$uid = 4; // Anonymous user
		}
		
		/**
		 * Saving initial thought
		 */
		if(isset($result['response']['start']) && $result['response']['start'] == 1){
			// Create post
			$new_thought = array(
				'post_title' => $timestamp,
				'post_status' => 'draft',
				'post_author' => $uid,
				'post_type' => 'thought'
			);
			$post_id = wp_insert_post($new_thought);

			// Update Activity reference
			update_field('activity', intval($data['page']), $post_id);
			
			// Add first thought to repeater
			$response_row = array(
				'field_5f84dbbc647b1'	=> '0', // Question
				'field_5f84dbb8647b0' 	=> sanitize_text_field($data['thought_0']), // Response
				'field_5fa58855bc758' 	=> $timestamp, // Submitted
			);

			// Admin Seeded Thought details
			if(isset($result['response']['seed_admin']) && $result['response']['seed_admin'] != ''){
				$response_row['field_5f84dbb8647b0'] = ''; // Clear "response" since we'll be using the seeded entry
				$response_row['field_5f89f49a16baf'] = intval($data['seed_admin']); // Admin Seeded Thought Reference (The row ID)
			}
			
			// User Seeded Thought details
			if(isset($result['response']['seed_user']) && $result['response']['seed_user'] != ''){
				$response_row['field_5f84dbb8647b0'] = ''; // Clear "response" since we'll be using the seeded entry
				$response_row['field_5fa490c4b0e4c'] = intval($data['seed_user']); // User Seeded Thought Reference (The post ID)
				$result['seed_user'] = $data['seed_user'];
			}

			// Add thought to response if the first row is empty
			if(!get_field('responses', $post_id)){
				$add_row = add_row('field_5f84dbab647af', $response_row, $post_id);				
			}

			// Updated ipiden field only if its empty
			if(!get_field('field_5f84c85780df4', $post_id)){
				$add_ipiden = update_field('field_5f84c85780df4', $ipiden, $post_id);
			}
			
		}
		
		/**
		 * Follow up responses
		 */
		if(isset($result['response']['continue']) && $result['response']['continue'] == 1){	

			$question = intval($data['question']);
			$path = intval($data['path']);
			$response_text = $data['thought_'.$path.'_'.$question.''];
			
			// Get user's most recent incomplete thought to update.
			// Shouldn't trust a variable from the front end
			$post_id = '';
			$args = array(
				"post_type" 	 => 'thought',
				"author"		 => $uid,
				"orderby" 		 => 'date', // Get the most recent
				"order"			 => 'DESC', // Get the most recent
				"post_status" 	 => 'draft', // Incomplete thoughts only
				"posts_per_page" => 1,
				"meta_query"	 => array(
					'relation'	 	=> 'AND',
					array(
						'key'		=> 'ipiden',
						'value'		=> $ipiden
					),
					array(
						'key'		=> 'abandoned', // TODO: Update this in case "reopening" thoughts is ever a thing
						'compare'   => 'NOT EXISTS',
					)
				)
			);
			$loop = new WP_Query($args);
			while($loop->have_posts()) : $loop->the_post();
				$post_id = get_the_ID();
			endwhile;
			
			$response_row = array(
				'field_5f84dbbc647b1'	=> $question, // Question
				'field_5f84dbb8647b0' 	=> $response_text, // Response
				'field_5f84dbc9647b2' 	=> $path, // Path
				'field_5fa58855bc758' 	=> $timestamp, // Submitted
			);
			$result['add_row'] = add_row('field_5f84dbab647af', $response_row, $post_id);		
			
			// Publish if its the last question
			if(isset($result['response']['last']) && $result['response']['last'] == 1){	
				// Update post 37
				$publish = array(
					'ID'           => $post_id,
					'post_status'  => 'publish',
				);

				// Update the post into the database
				wp_update_post( $publish );
			}


		}

    }

    echo json_encode($result);
    exit();
}
add_action("wp_ajax_nopriv_thoughtSubmission", "thoughtSubmission");
add_action("wp_ajax_thoughtSubmission", "thoughtSubmission");


/** 
 * Thought liking
 */
function thoughtLike(){
	
	// General variables
	global $wpdb;
    $result = array();
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);  
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'thoughtLike');
	
	// Submission is good, proceed
	if($isAuthentic){
			
		// Organize our data
		$result['response'] = $data;
		$row = $data['row'];
		$pid = $data['pid'];	

		// Vars
		$table = 'thoughts_likes';
		$ipiden = md5($_SERVER['REMOTE_ADDR']);	
		$uid = get_current_user_id();	
		if(!$uid){
			$uid = 4; // Anonymous user
		}

		// Handle anonymous or logged in differently
		if($uid == 0){			
			$user_where = "uid = 0 AND ipiden = '$ipiden'";
		} else {
			$user_where = "uid = $uid";
		}		

		// Check if liked previously
		$db_like = $wpdb->get_results("SELECT * FROM $table WHERE $user_where AND row = $row AND pid = $pid");		
		//$result['db_liked'] = $db_like[0]->id;		
		
		if($db_like && $db_like[0]->unliked == 0){

			// Result found, let's unlike it!
			$db_update = $wpdb->update(
				$table, 
				array('unliked' => 1), 
				array('id' => $db_like[0]->id)
			);			
			$result['liked'] = 0;

		} else if($db_like && $db_like[0]->unliked == 1){

			// Thought was previously unliked, so lets like it again!
			$db_update = $wpdb->update(
				$table, 
				array('unliked' => 0), 
				array('id' => $db_like[0]->id)
			);			
			$result['liked'] = 2;

		} else {

			// No results, like it for the first time!
			$response =	array( 
				'uid' => $uid,
				'ipiden' => $ipiden,
				'pid' => $pid,
				'row' => $row
			);	
			$db_insert = $wpdb->insert($table, $response);
			$result['liked'] = 1;

		}

    } else {

		$result['error'] = 'Error';	

	}

    echo json_encode($result);
    exit();
}
add_action("wp_ajax_nopriv_thoughtLike", "thoughtLike");
add_action("wp_ajax_thoughtLike", "thoughtLike");


/** 
 * Thought flagging
 */
function thoughtFlag(){
	
	// General variables
	global $wpdb;
    $result = array();
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);  
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'thoughtFlag');
	
	// Submission is good, proceed
	if($isAuthentic){
			
		// Organize our data
		$result['response'] = $data;
		$row = $data['row'];
		$pid = $data['pid'];	

		// Vars
		$table = 'thoughts_flags';
		$ipiden = md5($_SERVER['REMOTE_ADDR']);	
		$uid = get_current_user_id();	
		if(!$uid){
			$uid = 4; // Anonymous user
		}

		// Handle anonymous or logged in differently
		if($uid == 0){			
			$user_where = "uid = 0 AND ipiden = '$ipiden'";
		} else {
			$user_where = "uid = $uid";
		}		

		// Check if flagged previously
		$db_flag = $wpdb->get_results("SELECT * FROM $table WHERE $user_where AND row = $row AND pid = $pid");		
		
		if($db_flag){

			// User previously flagged it, don't do anything
			$result['flagged'] = 0;

		} else {

			// No results, flag it!
			$response =	array( 
				'uid' => $uid,
				'ipiden' => $ipiden,
				'pid' => $pid,
				'row' => $row
			);	
			$db_insert = $wpdb->insert($table, $response);
			$result['flagged'] = 1;

		}

    } else {

		$result['error'] = 'Error';	

	}

    echo json_encode($result);
    exit();
}
add_action("wp_ajax_nopriv_thoughtFlag", "thoughtFlag");
add_action("wp_ajax_thoughtFlag", "thoughtFlag");


/**
 * Cheeck if thought was liked or not
 */
function likeChecker($pid, $row){
	global $wpdb;

	// Vars
	$table = 'thoughts_likes';
	$ipiden = md5($_SERVER['REMOTE_ADDR']);	
	$uid = get_current_user_id();	
	if(!$uid){
		$uid = 4; // Anonymous user
	}

	// Handle anonymous or logged in differently
	if($uid == 0){			
		$user_where = "uid = 0 AND ipiden = '$ipiden'";
	} else {
		$user_where = "uid = $uid";
	}	

	$db_like = $wpdb->get_var("SELECT id FROM $table WHERE $user_where AND row = $row AND pid = $pid AND unliked = 0");	

	if($db_like){
		return true;
	} 
	
	return false;
}


/**
 * Update Thoughts Submitted
 */

 function getThoughtsSubmitted(){
	 
	$admin_seeds = get_field('pre_generated_responses');
	$unique_admin_seeds = [];
	$unique_user_seeds = [];

	$args = array(
		"post_type" 	 => 'thought',
		"post_status" 	 => 'publish',
		"order"			 => 'DESC',
		"orderby" 		 => 'date',
		"posts_per_page" => 50,
		"meta_key" 		 => 'activity',
		"meta_value"     => $activity_id,
		/*
		'meta_query' 	 => array(
			'relation'   => 'AND',
			array(
				'key' 	 	=> 'activity',
				'value'  	=> $activity_id,
				'compare'	=> '='
			),
			array(
				'key'		=> 'responses_$_response', // Avoid empty entries (Yeah it's an edge case)
				'value'  	=> '',
				'compare'	=> '!='
			)
		),
		*/
	);
	$loop = new WP_Query($args);
	if($loop->have_posts()):
		while($loop->have_posts()) : $loop->the_post();

			$thoughts = get_field('responses');
			
			if($thoughts[0]['response']) { 

				// Vars
				$pid = get_the_ID();

				// Entered response					
				if(in_array($pid, $unique_user_seeds)){
					continue;
				}
			
				echo '<li class="submitted-by-user">';
				echo ' <p class="thought-text" data-pid="'.$pid.'">'.$thoughts[0]['response'].'</p>'; 

				$liked = likeChecker($pid, 0);
				$like_text = 'Like';
				$like_class = '';
				if($liked){
					$like_text = 'Unlike';
					$like_class = ' liked';
				}
				echo ' <button class="thought-like'.$like_class.'" data-nonce="'.wp_create_nonce('thoughtLike').'" data-pid="'.$pid.'" data-row="0">'.$like_text.'</button>';
				echo ' <button class="thought-flag" data-nonce="'.wp_create_nonce('thoughtFlag').'" data-pid="'.$pid.'" data-row="0">Flag</button>';
				
				if(!$unfinished_thought){
					echo ' <button class="submit submit-initial-thought seed-user submitted-thought" value="'.$pid.'">Explore this thought</button>';
				}
				echo ' <br />'.edit_post_link('Edit', '', '', $pid);
				echo '</li>';

				$unique_user_seeds[] = $thoughts[0]['user_pre_seeded_thought'];

			} elseif($thoughts[0]['admin_pre_seeded_thought']) { 
				
				// Admin seeded response					
				if(in_array($thoughts[0]['admin_pre_seeded_thought'], $unique_admin_seeds)){
					continue;
				}

				$admin_thought_text = get_field('pre_generated_responses', $activity_id);
				$admin_thought_row = $thoughts[0]['admin_pre_seeded_thought'];
				$admin_thought_row_adjust = $admin_thought_row - 1; // ACF saves with a 1 based index instead of 0

				echo '<li class="submitted-by-admin">';
				echo ' <p class="thought-text">'.$admin_thought_text[$admin_thought_row_adjust]['response'].'</p>'; 
				
				$liked = likeChecker($activity_id, $admin_thought_row);
				$like_text = 'Like';
				$like_class = '';
				if($liked){
					$like_text = 'Unlike';
					$like_class = ' liked';
				}
				echo ' <button class="thought-like'.$like_class.'" data-nonce="'.wp_create_nonce('thoughtLike').'" data-pid="'.$activity_id.'" data-row="'.$admin_thought_row.'">'.$like_text.'</button>';
				echo ' <button class="thought-flag" data-nonce="'.wp_create_nonce('thoughtFlag').'" data-pid="'.$activity_id.'" data-row="'.$admin_thought_row.'">Flag</button>';
				
				if(!$unfinished_thought){
					echo ' <button class="submit submit-initial-thought seed-admin submitted-thought" value="'.$admin_thought_row.'">Explore this thought</button>';
				}
				
				echo ' <br />'.edit_post_link('Edit', '', '', get_the_ID());
				echo '</li>';

				$unique_admin_seeds[] = $thoughts[0]['admin_pre_seeded_thought'];
			
			} elseif($thoughts[0]['user_pre_seeded_thought']) { 
				
				/**
				 * User seeds should never show, only display admin seeds or user 
				 * thoughts directly for liking consolidation and such.
				 */
				continue;

				/*
				// User seeded response					
				if(in_array($thoughts[0]['user_pre_seeded_thought'], $unique_user_seeds)){
					continue;
				}

				$user_thought_id = $thoughts[0]['user_pre_seeded_thought'];
				$user_thoughts = get_field('responses',$user_thought_id);

				echo '<li class="submitted-by-seed">';
				echo 'Seed<p class="thought-text">'.$user_thoughts[0]['response'].'</p>'; 
				echo ' <button class="thought-like" data-nonce="'.wp_create_nonce('thoughtLike').'" data-pid="'.$user_thought_id.'" data-row="0">Like</button>';
				echo ' <button class="thought-flag" data-nonce="'.wp_create_nonce('thoughtFlag').'" data-pid="'.$user_thought_id.'" data-row="0">Flag</button>';
				echo ' <button class="submit submit-initial-thought seed-user submitted-thought" value="'.$user_thought_id.'">Explore this thought</button>';
				echo '<br />'.edit_post_link('Edit', '', '', get_the_ID());
				echo '</li>';

				$unique_user_seeds[] = $thoughts[0]['user_pre_seeded_thought'];
				*/

			}
		endwhile; 
	endif; 

 }