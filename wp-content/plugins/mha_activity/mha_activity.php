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
		$ipiden = get_ipiden();	
		$uid = get_current_user_id();	
		if(!$uid){
			$uid = 4; // Default "Anonymous" User
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
			$result['pid'] = $post_id;

			// Update Activity reference
			update_field('activity', intval($data['page']), $post_id);

			// Started Time
			update_field('started', sanitize_text_field($data['started']), $post_id);
			
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
			$response_text = sanitize_text_field($data['thought_'.$path.'_'.$question.'']);
			$result['path'] = $path;
			$result['question'] = $question;
			
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
						'key'		=> 'abandoned',
						'compare'   => 'NOT EXISTS',
					)
				)
			);
			$loop = new WP_Query($args);
			while($loop->have_posts()) : $loop->the_post();
				$post_id = get_the_ID();
				$result['pid'] = $post_id;
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
				$publish = array(
					'ID'           => $post_id,
					'post_status'  => 'publish',
				);

				// Update the post into the database
				$result['publish'] = wp_update_post( $publish );
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
		$row = intval($data['row']);
		$pid = intval( $data['pid']);	
		$ref_pid = intval($data['ref_pid']);	

		// Vars
		$table = 'thoughts_likes';
		$ipiden = get_ipiden();	
		$uid = get_current_user_id();	
		if(!$uid){
			$uid = 4; // Default "Anonymous" User
		}

		// Handle anonymous or logged in differently
		if($uid == 4){			
			$user_where = "uid = 4 AND ipiden = '$ipiden'";
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
				'ref_pid' => $ref_pid,
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
		$row = intval($data['row']);
		$pid = intval($data['pid']);	
		$ref_pid = intval($data['ref_pid']);	

		// Vars
		$table = 'thoughts_flags';
		$ipiden = get_ipiden();	
		$uid = get_current_user_id();	
		if(!$uid){
			$uid = 4; // Default "Anonymous" User
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
				'ref_pid' => $ref_pid,
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
	$ipiden = get_ipiden();	
	$uid = get_current_user_id();

	// Handle anonymous or logged in differently
	if($uid == 0){			
		$user_where = "uid = 4 AND ipiden = '$ipiden'";
	} else {
		$user_where = "uid = $uid";
	}	

	$db_like = $wpdb->get_var("SELECT id FROM $table WHERE $user_where AND row = $row AND pid = $pid AND unliked = 0");	

	if($db_like){
		return true;
	} 
	
	return false;
}


function get_all_mha_user_likes(){

	// Vars
	$ipiden = get_ipiden();	
	$uid = get_current_user_id();

	// Handle anonymous or logged in differently
	$user_where = ($uid == 0) ? "uid = 4 AND ipiden = '$ipiden'" : "uid = $uid";

	// Get Results
	global $wpdb;
	$db_likes = $wpdb->get_results("SELECT pid, row FROM thoughts_likes WHERE $user_where AND unliked = 0 ORDER BY date DESC LIMIT 100");	

	return $db_likes ? $db_likes : false;
}

function mha_liked_response( $likes, $pid, $row ) {
	if(isset($likes) && is_array($likes)){
		foreach($likes as $l){
			if($l->row == $row && $l->pid == $pid){
				return true;
			}
		}
	}
	return false;
}


/**
 * Update Thoughts Submitted
 */

 
function loadMoreThoughts(){

	parse_str($_POST['data'], $data);  

	$activity_id = 	isset($data['activity_id']) ? intval($data['activity_id']) : null;
	$index = 		isset($data['index']) ? intval($data['index']) : null;
	$path = 		isset($data['path']) ? intval($data['path']) : null;
	$admin_seed = 	isset($data['admin_seed']) ? intval($data['admin_seed']) : null;
	$user_seed = 	isset($data['user_seed']) ? intval($data['user_seed']) : null;
	$return = 		isset($data['return']) ? intval($data['return']) : null;
	$paged = 		isset($data['paged']) ? intval($data['paged']) : null;

	return getThoughtsSubmitted($activity_id, $index, $path, $admin_seed, $user_seed, $return, $paged);

}
add_action("wp_ajax_nopriv_loadMoreThoughts", "loadMoreThoughts");
add_action("wp_ajax_loadMoreThoughts", "loadMoreThoughts");


/**
 * Get submitted thoughts for each path
 */
function getThoughtsSubmitted( $activity_id = null, $index = null, $path = null, $admin_seed = null, $user_seed = null, $return = null, $paged = 1 ){
	
	/**
	 * Var overrides when ajax requested
	 */
	if(isset($_POST['data'])){
		parse_str($_POST['data'], $data); 
		$activity_id = $data['activity_id'];
		$index = $data['index'];
		$path = $data['path'];
		$admin_seed = $data['admin_seed'];
		$user_seed = $data['user_seed'];
		$return = isset($data['return']) ? $data['return'] : null;
		$paged = isset($data['paged']) ? $data['paged'] : null;
		if($index == 0 && $path != ''){
			$index = 1;
		}
	}

	// Debugging
	/*
	echo '<pre>';
		echo '====DEBUG====';
		echo 'ID: '.$activity_id.'<br />';
		echo 'Index: '.$index.'<br />';
		echo 'Path: '.$path.'<br />';
		echo 'Admin Seed: '.$admin_seed.'<br />';
		echo 'User Seed: '.$user_seed.'<br />';
		echo 'Return: '.$return.'<br />';
		echo 'Paged: '.$paged.'<br />';
	echo '</pre>';
	*/

	// General Vars
	$admin_seeds = get_field('pre_generated_responses', $activity_id);
	$unique_admin_seeds = [];
	$unique_user_seeds = [];
	$thought_counter = 0;
	$thought_query_fail = 0;

	$args = array(
		"post_type" 	 => 'thought',
		"post_status" 	 => array('publish', 'draft'),
		"order"			 => 'DESC',
		"orderby" 		 => 'date',
		"posts_per_page" => 250,
		"paged" 		 => $paged,
		"meta_query"	 => array(
			array(
				'key'		=> 'activity',
				'value'		=> $activity_id
			)
		)
	);
	
	// Add to our meta query for subsequent questions
	if($path != '' && $path > 0){
		$args['meta_query'] = array( 'relation' => 'AND' );
		$args['meta_query'] = array(
			'key'		=> 'responses_1_path', 
			'value'  	=> intval($path)
		);
	}

		
	// Admin Seeded Thought Overrides
	if(is_numeric($admin_seed)){
		// Add the admin connection 
		$args['meta_query'][] = array(
			'key'		=> 'responses_0_admin_pre_seeded_thought',
			'value'  	=> intval($admin_seed)
		);
	}
	
	// User Seeeded Thought Overrides
	$loop_extra = '';
	$loop_extra_thought = '';
	if(is_numeric($user_seed)){
		// Add the original thought to our list
		$args_extra = array(
			"p" 			=> $user_seed,
			"post_type" 	=> 'thought'
		);
		//$loop_extra = new WP_Query($args_extra);
	}
	/*
	if($loop_extra != '' && $loop_extra->have_posts()):		
		while($loop_extra->have_posts()) : $loop_extra->the_post();
										
			// Vars
			$pid = get_the_ID();				
			$thoughts = get_field( 'responses', $pid );	
			
			if(isset($thoughts[$index]['response']) && $thoughts[$index]['response'] != '' && $thoughts[$index]['hide'] != 1){					
				thoughtRow($pid, $thoughts, $index);
				$counter++;
			}

		endwhile;
	endif;
	*/

	$loop = new WP_Query($args);
	$max_pages = $loop->max_num_pages;
		
	if($loop->have_posts()):
		while($loop->have_posts()) : $loop->the_post();

			$pid = get_the_ID();
			$thoughts = get_field('responses');	

			// Skip user seeded thoughts (only show the original), and skip thoughts that have been hidden
			if(
				$index === 0 && $thoughts[0]['user_pre_seeded_thought'] || 
				$index === 0 && $thoughts[0]['admin_pre_seeded_thought'] || 
				isset($thoughts[$index]) && isset($thoughts[$index]['hide']) && $thoughts[$index]['hide'] == 1 || 
				!isset($thoughts[$index]) || 
				$index > 0 && isset($thoughts[1]) && $thoughts[1]['path'] && $thoughts[1]['path'] != $path
			){
			//if($index === 0 && $thoughts[0]['user_pre_seeded_thought'] || $thoughts[$index]['hide'] == 1 || !isset($thoughts[$index]) || $index > 0 && $thoughts[1]['path'] != $path){
				continue;
			}

			// Get the Thought Row text
			$thought_text = $thoughts[$index]['response'];
					
			// Admin seeded thoughts override
			if($index === 0 && $thoughts[0]['admin_pre_seeded_thought']){	
				$admin_thought_text = get_field('pre_generated_responses', $activity_id);
				$admin_thought_row = intval($thoughts[0]['admin_pre_seeded_thought']);
				$thought_text = $admin_thought_text[$admin_thought_row]['response']; 				
			}

			// Skip empty thoughts
			if($thought_text == ''){
				continue;
			}

			thoughtRow($pid, $thought_text, $index);	
			$thought_counter++;
				
		endwhile; 
			
		// Load More
		$paged_next = $paged + 1;
		if( $max_pages > 1 && $paged_next < $max_pages ):
			echo '<li class="load-more navigation pagination pt-5 mr-2 mr-md-3" data-index="'.$index.'" style="background: none;">';
				echo '<button class="load-more-thoughts button round red" 
					data-activity-id="'.$activity_id.'"
					data-index="'.$index.'"
					data-path="'.$path.'"
					data-admin-seed="'.$admin_seed.'"
					data-user-seed="'.$user_seed.'"
					data-return="'.$return.'"
					data-paged="'.$paged_next.'">Load More Thoughts</button>';
			echo '</li>';
		endif;
		
	else:

		echo '<li class="round-small-bl bubble thin submitted-by-user wow fadeIn">';
			echo '<div class="inner clearfix">There are no other thoughts to display.</div>';
		echo '</li>';
		$thought_query_fail = 1;

	endif;

	if($thought_counter == 0 && $thought_query_fail == 0){
		echo '<li class="round-small-bl bubble thin submitted-by-user wow fadeIn">';
			echo '<div class="inner clearfix">There are no other thoughts to display.</div>';
		echo '</li>';
	}

	if(isset($_POST['data'])){
		die(); // Prevent Wordpress default ajax from returning 0
	}

}
add_action("wp_ajax_nopriv_getThoughtsSubmitted", "getThoughtsSubmitted");
add_action("wp_ajax_getThoughtsSubmitted", "getThoughtsSubmitted");



/**
 * Template for display other responses
 */
function thoughtRow($pid = null, $text = null, $index = null) {
	
	if($pid == null || $text == null ){
		return false;
	}

	// Get like count
	$like_count = getThoughtLikes($pid, $index);
			
	// Start the output
	echo '<li class="round-small-bl bubble thin submitted-by-user wow fadeIn" data-count="'.$like_count.'" data-index="'.$index.'" id="thought-'.$pid.'">';
	echo '<div class="inner clearfix">';

		// Thought Display
		echo edit_post_link('Edit', '', '', $pid);
		echo '<div class="thought-text" data-pid="'.$pid.'">';
		
			echo $text;
			
		echo '</div>';		
		
		// Actions
		echo '<div class="thought-actions">';
			// Relate
			$like_class = (likeChecker($pid, $index)) ? ' liked' : '';
			echo '<button class="icon thought-like'.$like_class.'" data-nonce="'.wp_create_nonce('thoughtLike').'" data-pid="'.$pid.'" data-row="0">';
				echo '<span class="image">';
					include("assets/heart.svg");
				echo '</span>';
				echo '<span class="text">I relate</span>';
			echo '</button>';

			// Flag
			echo '<button class="icon thought-flagger" data-toggle="tooltip" data-placement="top" title="Report this thought for review if you feel it is inappropriate." aria-controls="#thought-'.$pid.'">';
				echo '<span class="image">';
					include("assets/flag.svg");
				echo '</span>';
				echo '<span class="text">Report</span>';
			echo '</button>';
			
			// Explore
			if($index === 0){
				echo '<span class="explore-container"><button class="bar submit submit-initial-thought seed-user submitted-thought" value="'.$pid.'">Explore this thought &raquo;</button></span>';
			}	
			echo '</div>';	
		echo '</div>';		

		// Flag Prompt
		echo '<div class="thought-flag-confirm-container text-center hidden">
			<div class="thought-flag-confirm-container-inner p-2 pt-4 pb-4 relative">
				<p class="mb-3"><em>&quot;'.$text.'&quot;</em></p>
				<p class="mb-3">This will alert moderators that the comment may be inappropriate. Continue?</p>
				<p class="mb-3"><button class="icon thought-flag thin button red round small" data-nonce="'.wp_create_nonce('thoughtFlag').'" data-pid="'.$pid.'" data-row="0" data-thought-id="#thought-'.$pid.'">Yes, report this comment</button></p>
				<button class="cancel-flag-thought button blue thin round small">Nevermind</button>
			</div>
		</div>';
		
	echo '</li>';	

}


/**
 * Get a thought's total likes
 */
function getThoughtLikes($pid, $row = 0){
	global $wpdb;
	$like_count = $wpdb->get_var( "SELECT COUNT(*) FROM thoughts_likes WHERE pid = $pid AND row = $row AND unliked = 0" );
	if(!$like_count){
		$like_count = 0;
	}
	return $like_count;
}


/** 
 * Thought flagging
 */
function abandonThought(){
	
	// General variables
	global $wpdb;
    $result = array();
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);  
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'abandonThought');
	
	// Submission is good, proceed
	if($isAuthentic){
			
		// Organize our data
		$result['response'] = $data;
		$page = intval($data['page']);

		// Vars
		$ipiden = get_ipiden();	
		$uid = get_current_user_id();	
		if(!$uid){
			$uid = 4; // Default "Anonymous" User
		}

		// Abandon all unpublished thoughts
		$args = array(
			"post_type" 	 => 'thought',
			"author"		 => $uid,
			"orderby" 		 => 'date', // Get the most recent
			"order"			 => 'DESC', // Get the most recent
			"post_status" 	 => 'draft', // Incomplete thoughts only
			"posts_per_page" => -1,
			"meta_query"	 => array(
				'relation'	 	=> 'AND',
				array(
					'key'		=> 'ipiden',
					'value'		=> $ipiden
				),
				array(
					'key'		=> 'abandoned',
					'compare'  	=> 'NOT EXISTS'
				),
				array(
					'key'		=> 'activity',
					'compare'   => $page
				)
			)
		);
		$loop = new WP_Query($args);
		$post_id = null;
		while($loop->have_posts()) : $loop->the_post();
			$post_id = get_the_ID();
			$result['post_id'] = $post_id;
			// Update the post and abandon it
			$publish = array(
				'ID'           => $post_id,
				'post_status'  => 'publish',
			);
			wp_update_post( $publish );
			update_field('abandoned', date('Y-m-d H:i:s'), $post_id);
		endwhile;
		

		// Set a redirect to start from scratch
		$result['page_redirect'] = add_query_arg('cb', time() + $post_id, get_the_permalink($page));

    } else {

		$result['error'] = 'Error';	

	}

    echo json_encode($result);
    exit();
}
add_action("wp_ajax_nopriv_abandonThought", "abandonThought");
add_action("wp_ajax_abandonThought", "abandonThought");



/**
 * Check Article Likes
 */
function checkArticleLikes( $pid, $uid ){
	global $wpdb;
	$like_count = $wpdb->get_var( "SELECT COUNT(*) FROM article_likes WHERE pid = $pid AND uid = $uid AND unliked = 0" );
	if($like_count){
		return true;
	}
	return false;
}

/**
 * Like an article
 */
function articleLike(){
	
	// General variables
	global $wpdb;
    $result = array();
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);  
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'articleLike');
	
	// Submission is good, proceed
	if($isAuthentic && is_user_logged_in()){
			
		// Organize our data
		$result['response'] = $data;
		$uid = get_current_user_id();
		$pid = $data['pid'];	

		// Vars
		$table = 'article_likes';	

		// Check if liked previously
		$db_like = $wpdb->get_results("SELECT * FROM $table WHERE uid = $uid AND pid = $pid");				
		
		if($db_like && $db_like[0]->unliked == 0){

			// Result found, let's unlike it!
			$result['db_update'] = $wpdb->update(
				$table, 
				array('unliked' => 1), 
				array('id' => $db_like[0]->id)
			);			
			$result['liked'] = 0;

		} else if($db_like && $db_like[0]->unliked == 1){

			// Thought was previously unliked, so lets like it again!
			$result['db_update'] = $wpdb->update(
				$table, 
				array('unliked' => 0), 
				array('id' => $db_like[0]->id)
			);			
			$result['liked'] = 2;

		} else {

			// No results, like it for the first time!
			$response =	array( 
				'uid' => $uid,
				'pid' => $pid
			);	
			$result['db_insert'] = $wpdb->insert($table, $response);
			$result['liked'] = 1;

		}

    } else {

		if(!is_user_logged_in()){
			$result['login'] = 1;	
		} else {
			$result['error'] = 'Error';	
		}

	}

    echo json_encode($result);
    exit();
}
add_action("wp_ajax_nopriv_articleLike", "articleLike");
add_action("wp_ajax_articleLike", "articleLike");


/**
 * Hide Thoughts
 */

function hideThought(){
	
	// General variables
	global $wpdb;
    $result = array();
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);  
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'hideThought');
	
	// Submission is good, proceed
	if($isAuthentic && is_user_logged_in()){
			
		// Organize our data
		$result['response'] = $data;
		$uid = get_current_user_id();
		$pid = $data['pid'];	

		// Vars
		$table = 'thoughts_hidden';	

		// Check if liked previously
		$db_hidden = $wpdb->get_results("SELECT * FROM $table WHERE uid = $uid AND pid = $pid");			
		
		
		if($db_hidden && $db_hidden[0]->unliked == 0){

			// Result found, let's unlike it!
			$db_update = $wpdb->update(
				$table, 
				array('unhidden' => 1), 
				array('id' => $db_hidden[0]->id)
			);			
			$result['unhidden'] = 0;

		} else if($db_hidden && $db_hidden[0]->unliked == 1){

			// Thought was previously hidden, so lets hide it again!
			$db_update = $wpdb->update(
				$table, 
				array('unhidden' => 0), 
				array('id' => $db_hidden[0]->id)
			);			
			$result['unhidden'] = 2;

		} else {

			// No results, hide it for the first time!
			$response =	array( 
				'uid' => $uid,
				'pid' => $pid
			);	
			$db_insert = $wpdb->insert($table, $response);
			$result['unhidden'] = 1;

		}

		// Abandon the thought at the same time
		$result['updated'] = update_field('abandoned', date('Y-m-d H:i:s'), $pid);
		$result['user_hidden'] = update_field('hidden', date('Y-m-d H:i:s'), $pid);

    }

    echo json_encode($result);
    exit();
}
add_action("wp_ajax_nopriv_hideThought", "hideThought");
add_action("wp_ajax_hideThought", "hideThought");


/**
 * Hide Screens
 */

function hideScreen(){
	
	// General variables
	global $wpdb;
    $result = array();
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);  
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'hideScreen');
	
	// Submission is good, proceed
	if($isAuthentic && is_user_logged_in()){
			
		// Organize our data
		$result['response'] = $data;
		$uid = get_current_user_id();
		$pid = $data['pid'];	

		// Vars
		$table = 'screens_hidden';	

		// Check if liked previously
		$db_hidden = $wpdb->get_results("SELECT * FROM $table WHERE uid = $uid AND pid = $pid");			
		
		if($db_hidden && $db_hidden[0]->unliked == 0){

			// Result found, let's unlike it!
			$db_update = $wpdb->update(
				$table, 
				array('unhidden' => 1), 
				array('id' => $db_hidden[0]->id)
			);			
			$result['unhidden'] = 0;

		} else if($db_hidden && $db_hidden[0]->unliked == 1){

			// Thought was previously hidden, so lets hide it again!
			$db_update = $wpdb->update(
				$table, 
				array('unhidden' => 0), 
				array('id' => $db_hidden[0]->id)
			);			
			$result['unhidden'] = 2;

		} else {

			// No results, hide it for the first time!
			$response =	array( 
				'uid' => $uid,
				'pid' => $pid
			);	
			$db_insert = $wpdb->insert($table, $response);
			$result['unhidden'] = 1;

		}

    }

    echo json_encode($result);
    exit();
}
add_action("wp_ajax_nopriv_hideScreen", "hideScreen");
add_action("wp_ajax_hideScreen", "hideScreen");

include_once('diy_tools.php');