<?php
/**
 * DIY Tools
 */

// Enqueing Scripts
add_action('init', 'mhaDiyToolsScripts');
function mhaDiyToolsScripts() {
	wp_enqueue_script('process_mhaDiyTools', plugin_dir_url( __FILE__ ).'diy_tools.js', array( 'jquery' ), 'v20240403', true);
	//wp_enqueue_script('process_mhaDiyTools', plugin_dir_url( __FILE__ ).'diy_tools.js', array( 'jquery' ), time(), true);
	wp_localize_script('process_mhaDiyTools', 'do_mhaDiyTools', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

function truncate_answer($text, $limit, $id) {
    if (str_word_count($text, 0) > $limit) {
        $words = str_word_count($text, 2);
        $pos   = array_keys($words);
        $text  = '<div class="text-snippet-short" data-snippet-id="'.$id.'" aria-expanded="true">'.trim(substr($text, 0, $pos[$limit])). '...</div>
        <div class="hidden text-snippet-long" data-snippet-id="'.$id.'" id="'.$id.'" aria-expanded="false">'.$text.'</div>
        <button class="text-snippet-toggle bar blue mt-3" data-snippet-toggle="'.$id.'" aria-controls="'.$id.'">Read more</button>';
    }
    return $text;    
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
        'nonce'                 => null,
        'response_id'           => null,
        'ref_code'              => null,
        'activity_id'           => array(),
        'submit'                => 0,
        'opened_diy'            => null,
        'opened_diy_question'   => null,
        'crowdsource_hidden'    => 0,
        'embedded'              => 0
    );    
    parse_str($_POST['data'], $data);  
    $args = wp_parse_args( $data, $defaults ); 
	
    $args['crowdsource_hidden'] = $args['crowdsource_hidden'] ? intval($args['crowdsource_hidden']) : 0;
    $result['args'] = $args;

	// Submission is good, proceed
	if( wp_verify_nonce( $args['nonce'], 'diySubmission') ){
        
		$timestamp = date('Y-m-d H:i:s');		
		$ipiden = get_ipiden();	
		$uid = get_current_user_id() ? get_current_user_id() : 4; // Default "anonymous" user is 4	

        // Draft Already Exists
        /*
		$current_post_args = array(
			"post_type" 	 => 'diy_responses',
			"author"		 => $uid,
			"orderby" 		 => 'date', // Get the most recent
			"order"			 => 'DESC', // Get the most recent
			"post_status" 	 => 'draft', // Incomplete thoughts only
			"posts_per_page" => 1,
			"ipiden"         => $ipiden,
        );
        */
		// $current_post_loop = new WP_Query($current_post_args);
        // $current_post_id = $current_post_loop->found_posts ? $current_post_loop->post->ID : null;
        $current_post_id = $args['diytool_current_id'] ? $args['diytool_current_id'] : null;
        
        $response_rows = array();
        $answer_count = 0;
        foreach($args as $k => $v){
            if(strpos($k, 'answer_') !== false){
                $r_id = str_replace("answer_", "", $k);

                $answer = is_array($args['answer_'.$r_id]) ? sanitize_text_field( implode(', ', $args['answer_'.$r_id]) ) : sanitize_text_field($args['answer_'.$r_id]);

                if($answer != ''){
                    $new_row = array(
                        'field_634840ef4cbb3'	=> $r_id, // Question ID
                        'field_634857934cbb4' 	=> strlen($answer) > 1000 ? substr($answer,0,1000)." [[...]]" : $answer, // Answer
                        'field_6348579d4cbb5' 	=> $timestamp, // Date
                    );
                    array_push($response_rows, $new_row);
                    $answer_count++;
                }
            }
        }

        $result['answer_count'] = $answer_count;
        $result['current_responses'] = $current_post_id ? get_field('field_63483d064cbb0', $current_post_id) : null;

        if($result['current_responses']){
            foreach($response_rows as $nrk => $nrv){
                foreach($result['current_responses'] as $cr){
                    if($nrv['field_634840ef4cbb3'] == $cr['id']){
                        $response_rows[$nrk]['field_6348579d4cbb5'] = $cr['date'];
                    }
                }
            }
        }
        $result['response_rows'] = $response_rows;

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

            } else {
                
                $result['error'] = 'You must have at least one question answered before you can submit.';   
                echo json_encode($result);
                exit();
                
            }

        }

        if($result['post_id']){
            
            $result['updated_activity_id'] = update_field('activity_id', array($args['activity_id']), $result['post_id']); // Post object needs an array to submit?
            $result['updated_ipiden'] = update_field('ipiden', $ipiden, $result['post_id']);
            $result['updated_started'] = !get_field('started', $result['post_id']) ? update_field('started', $timestamp, $result['post_id']) : get_field('started', $result['post_id']);
            $result['updated_ref_code'] = update_field('ref_code', sanitize_text_field($args['ref_code']), $result['post_id']);         
            $result['updated_user_viewed_crowdsource'] = update_field('user_viewed_crowdsource', $args['opened_diy'], $result['post_id']);               
            $result['updated_crowdsource_hidden'] = update_field('crowdsource_hidden', $args['crowdsource_hidden'], $result['post_id']);
            $result['updated_user_viewed_crowdsource_question'] = update_field('user_viewed_crowdsource_question', $args['opened_diy_question'], $result['post_id']);
            $result['updated_start_page_url'] = update_field('start_page_url', sanitize_url( $args['current_url'] ), $result['post_id']);

            // Only add the referring start page for this activity if the value is blank
            if(!get_field('start_page', $result['post_id'])){
                $result['updated_start_page'] = update_field('start_page', $args['start_page'], $result['post_id']);
            }
            
            // If crowdsource is viewed and hasn't been previously updated...
            if(!get_field('user_viewed_crowdsource', $result['post_id'])){            
                update_field('user_viewed_crowdsource', $args['opened_diy'], $result['post_id']);
                update_field('user_viewed_crowdsource_question', $args['opened_diy_question'], $result['post_id']);
            }
            
            // Add/Update responses
            if($answer_count > 0){

                update_field('field_63483d064cbb0', $response_rows, $result['post_id']);

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


function getDiyTopLikes( $activity_id, $where_flag, $total_questions, $args ) {

    $crowdsource_scoring_date_range = get_field('crowdsource_scoring_date_range', $activity_id);
    $crowdsource_scoring_time = strtotime( $crowdsource_scoring_date_range );
    $date_old = date('Y-m-d', $crowdsource_scoring_time);
    $relate_bonus = get_field('crowdsource_scoring_relate_bonus', $activity_id);

    $responses = []; // Store carousel of responses
    $responses_collection = []; // Uses for storing response IDs

    $use_cache = false;
	$json = plugin_dir_path( __FILE__ ).'tmp/total_relates_'.$activity_id.'.json'; 
    $responses = null;

    if (file_exists($json) && filemtime($json) > strtotime('-1 day')) {
        $responses = json_decode(file_get_contents($json), true);
        $use_cache = true;
    }

    if(!$use_cache){

        if( get_field('override_crowdsource_scoring', $activity_id) ){
            $crowdsource_scoring_id = $args['activity_id'];
        } else {
            $crowdsource_scoring_id = 'options';
        }

        global $wpdb;
        $top_likes = $wpdb->get_results("
            SELECT pid, COUNT(*) as total_likes 
            FROM thoughts_likes 
            WHERE 
                ref_pid = 108701 AND 
                unliked = 0 AND 
                date >= '2024-03-04'
            GROUP BY pid 
            ORDER BY total_likes DESC
            LIMIT 100
        ");
        if($top_likes){
            foreach($top_likes as $tl){
                if(get_post($tl->pid) && !get_field('crowdsource_hidden', $tl->pid)){
                    $response_likes_override = $relate_bonus ? ($relate_bonus * $tl->total_likes) : $tl->total_likes;
                    $responses_collection[$tl->pid] = array(
                        'id'    => $tl->pid,
                        'date'  => get_the_date('', $tl->pid),
                        'likes' => ($tl->total_likes > 0) ? $response_likes_override : 0,
                        'true_likes' => $tl->total_likes,
                    );                      
                }
            }
        }

        foreach($responses_collection as $k => $v){
            $response_args = array(
                'pid'                       => $k,
                'true_likes'                => $v['true_likes'],
                'likes'                     => $v['likes'],
                'args'                      => $args,
                'date'                      => get_the_date('', $k),
                'total_questions'           => $total_questions,
                'crowdsource_scoring_id'    => $crowdsource_scoring_id
            );

            $get_response_display = get_diy_response_display( $response_args );
            if($get_response_display){
                $responses[] = get_diy_response_display( $response_args );
            }
        }

        // Print responses, sorted by likes
        if($responses){
            // Sort
            usort($responses, function ($a, $b) {return $b['true_likes'] <=> $a['true_likes'];} );

            // Update the cache file
            $fp = fopen($json, 'w');
            fwrite($fp, json_encode($responses));
            fclose($fp);
        }

    }

    return $responses;

}


add_action("wp_ajax_nopriv_getDiyCrowdsource", "getDiyCrowdsource");
add_action("wp_ajax_getDiyCrowdsource", "getDiyCrowdsource");
function getDiyCrowdsource(){

    // Post data
    $result = [];
    $defaults = array(
        'question'      => null,
        'current'       => null,
        'activity_id'   => null,
        'carousel'      => null,
        'offset'        => 0,
        'page'          => 0,
        'total_pages'   => 0,
        'embedded'      => 0,
        'single_embed'  => 0
    );    
    parse_str($_POST['data'], $data);  
    $args = wp_parse_args( $data, $defaults ); 
    $result['html'] = '';
    $result['args'] = $args;
    $args['page'] = intval($args['page']);
    
    // Pagination settings
    $per_page = $args['embedded'] ? 5 : 10; // Different page counts for display types
    $args['offset'] = (intval($args['page']) - 1) * $per_page;
    $args['per_page'] = $per_page;

    // Future helpers
    $diy_flag_message = get_field('flag_message', 'options');
    $diy_flag_confirm = get_field('flag_confirmation', 'options');
    $activity_questions = get_field('questions', $args["activity_id"]);
    $total_questions = $activity_questions ? count($activity_questions) : 0;
    $show_next_previews = get_field('show_next_previews', $args["activity_id"]) ? get_field('show_next_previews', $args["activity_id"]) : 0;

    if($args['single_embed'] == 1){
        $show_next_previews = 0;
    }
    
    $responses = []; // Store carousel of responses
    $responses_collection = []; // Uses for storing response IDs

	// Return JSON contents if available
    $use_cache = false;
	$json = plugin_dir_path( __FILE__ ).'tmp/'.$args['activity_id'].'_'.$args['page'].'.json'; 

    if (file_exists($json) && filemtime($json) > strtotime('-1 day')) {
        $responses = json_decode(file_get_contents($json), true);
        $use_cache = true;
    }

    if(!$use_cache){

        // Get top recent likes
        global $wpdb;
        
        // Crowdsource Scoring Options
        if( get_field('override_crowdsource_scoring', $args['activity_id']) ){
            $crowdsource_scoring_id = $args['activity_id'];
        } else {
            $crowdsource_scoring_id = 'options';
        }
        $crowdsource_scoring_date_range = get_field('crowdsource_scoring_date_range', $crowdsource_scoring_id);
        $crowdsource_scoring_time = strtotime( $crowdsource_scoring_date_range );
        $date_old = date('Y-m-d', $crowdsource_scoring_time);
        $relate_bonus = get_field('crowdsource_scoring_relate_bonus', $crowdsource_scoring_id);

        // Get flags for later
        $top_flags = [];
        $top_flags_query = $wpdb->get_results("
            SELECT pid, 'row', COUNT(pid) AS highflags 
            FROM thoughts_flags 
            WHERE 
                ref_pid = {$args['activity_id']}
            GROUP BY pid
            HAVING (highflags >= 1) 
            ORDER BY date DESC 
            LIMIT 200
        ");
        if($top_flags_query){
            foreach($top_flags_query as $tf){
                $top_flags[] = $tf->pid;
            }
        }            
        // Crosscheck with admin notes
        foreach($top_flags as $k => $v){
            $admin_note = get_field('admin_notes', $v);
            if($admin_note && $admin_note != ''){
                unset($top_flags[$k]);
            }
        }
        
        // Get Likes
        $where_flag = '';
        if(count($top_flags)){
            $flag_id_string = implode(',', $top_flags);
            $where_flag = "AND pid NOT IN($flag_id_string)";
        }
        //$top_likes_demo = getDiyTopLikes( $crowdsource_scoring_id, $where_flag );
        $top_likes = getDiyTopLikes( $args['activity_id'], $where_flag, $total_questions, $args );

        $toplikes_per_page = $per_page;
        $toplikes_offset = $toplikes_per_page * ($args['page'] - 1);
        $toplikes_counter = 0;

        if($top_likes){
            $top_likes_sliced = array_slice($top_likes, $toplikes_offset);
            $result['toplikes_normal'] = $top_likes;
            $result['toplikes_sliced'] = $top_likes_sliced;
            if($top_likes_sliced){
                foreach($top_likes_sliced as $tl){
                    if(
                        get_post($tl['pid']) && 
                        !get_field('crowdsource_hidden', $tl['pid']) && 
                        !in_array($tl['pid'], $top_flags) &&
                        $toplikes_counter < $toplikes_per_page
                    ){
                        $response_likes_override = $relate_bonus ? ($relate_bonus * $tl['true_likes']) : $tl['true_likes'];
                        $responses_collection[$tl['pid']] = array(
                            'id'    => $tl['pid'],
                            'date'  => get_the_date('', $tl['pid']),
                            'likes' => ($tl['true_likes'] > 0) ? $response_likes_override : 0,
                            'true_likes' => $tl['true_likes'],
                        );       
                        $toplikes_counter++;               
                    }
                }
            }
        }
        
        $crowd_args = array(
            "post_type"     	=> 'diy_responses',
            "order"             => 'DESC',
            "orderby"           => 'date',
            "post_status"       => 'publish',
            "posts_per_page"    => $per_page,
            "paged"             => $args['page'],
            "post__not_in"      => $top_flags,
            'date_query' => array(
                array(
                    'after'     => $date_old,
                    'inclusive' => true
                ),
            ),
            "meta_query"		=> array(
                'relation' => 'AND',
                array(
                    'key'       => 'activity_id',
                    'value'     => '"'.$args['activity_id'].'"',
                    'compare'   => 'LIKE'
                ),                
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'crowdsource_hidden',
                        'value' => '1',
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'crowdsource_hidden',
                        'value' => '1',
                        'compare' => 'NOT EXISTS'
                        )
                )
            ),
            "fields" => 'ids'
        );
        
        if($args['current']){
            $crowd_args["post__not_in"] = array( $args['current'] );
        }

        // Get the answers for this question
        $crowd_loop = new WP_Query($crowd_args); 
        $args['total_pages'] = $crowd_loop->max_num_pages; 
        
        $result['toplikes_counter'] = $toplikes_counter;

        $crowdsource_counter = 0;
        if($crowd_loop->have_posts() && $toplikes_counter < $per_page):
        while($crowd_loop->have_posts()) : $crowd_loop->the_post();   

            if($per_page - $toplikes_counter > $crowdsource_counter){
                // Skip previoulsy retrieved IDs
                $pid = get_the_ID();
            
                $response_likes = $wpdb->get_var("
                    SELECT COUNT(*) as total_likes 
                    FROM thoughts_likes 
                    WHERE pid = {$pid} AND unliked = 0 AND date >= '{$date_old}' 
                    GROUP BY pid 
                    ORDER BY total_likes DESC 
                ");

                $response_likes_override = $relate_bonus ? ($relate_bonus * $response_likes) : $response_likes;
                $responses_collection[$pid] = array(
                    'id'    => $pid,
                    'date'  => get_the_date('', $pid),
                    'likes' => ($response_likes > 0) ? $response_likes_override : 0,
                    'true_likes' => $response_likes,
                ); 
            }
            
            $crowdsource_counter++;
        endwhile;
        endif;    
        $result['crowdsource_counter'] = $crowdsource_counter;

        foreach($responses_collection as $k => $v){
            $response_args = array(
                'pid'                       => $k,
                'true_likes'                => $v['true_likes'],
                'likes'                     => $v['likes'],
                'args'                      => $args,
                'date'                      => get_the_date('', $k),
                'total_questions'           => $total_questions,
                'crowdsource_scoring_id'    => $crowdsource_scoring_id
            );

            $get_response_display = get_diy_response_display( $response_args );
            if($get_response_display){
                $responses[] = get_diy_response_display( $response_args );
            }
        }

        // Print responses, sorted by likes
        usort($responses, function ($a, $b) {return $b['score'] <=> $a['score'];} );

        // Update the cache file
        $fp = fopen($json, 'w');
        fwrite($fp, json_encode($responses));
        fclose($fp);
    }
    // End $use_cache

    // Get user likes for this activity to pre-mark them
    $pids_search = [];
    foreach($responses as $r){
        $pids_search[] = $r['pid'];
    }
    $user_likes = get_all_mha_user_likes( $pids_search );

    // Build HTML to return
    $result['responses'] = $responses;
    
    // Debug helper
    $result['html'] .= '<div class="question-container" data-page="'.$args['page'].'">';  
    

    if($args['page'] > 1 && count($responses) > 0){
        //$result['html'] .= '<div class="wrap narrow crowdsource-page-label text-center text-teal mb-3"><hr class="mt-4 mb-0" style="border-color: #1fb4bb;" />New</div>';   
        $result['html'] .= '<div class="wrap narrow crowdsource-page-label text-center text-teal mb-3">Page '.$args['page'].'</div>';   
    }

    foreach($responses as $r){
        
        // Begin HTML
        if($args['carousel']){
            $result['html'] .= '<div class="crowdsource-responses glide">';
            $result['html'] .= '<div class="glide__track" data-glide-el="track">';
            $result['html'] .= '<ol class="glide__slides">';
        } else {
            $result['html'] .= '<ol class="crowdthought">';
        }
        
        $q_counter = 0;
        foreach($activity_questions as $qid => $qval){
            if(!$args['carousel'] && $r['answers'][$qid]['id'] != $args['question']){
                continue;
            }
        
            if($args['carousel']){
                $result['html'] .= '<li class="glide__slide">';
            } else {
                $result['html'] .= '<li data-question="'.$r['answers'][$qid]['id'].'">';
            }
            
            if(isset($r['answers'][$qid])){
                $answer = $r['answers'][$qid]['answer'];
            } else {
                $answer = '<em class="no-response">User did not provide a response.</em>';
            }
            
            // Shorten long question labels
            $question_label_full = $activity_questions[$qid]['question'];
            $question_label_length = str_word_count($question_label_full, 0);
            if($question_label_length > 15 && $question_label_length > 18){
                $question_label_short = limit_text($question_label_full, 15);
                $question_label_display = '<div class="question-label-toggle mb-3">';
                $question_label_display .= '<div class="question-label-short small"><strong>'.$question_label_short.'</strong></div>';
                $question_label_display .= '<div class="question-label-long d-none small"><strong>'.$question_label_full.'</strong></div>';
                $question_label_display .= '</div>';
            } else {
                $question_label_display = '<div class="question-label small mb-3"><strong>'.$question_label_full.'</strong></div>';
            }

            $result['html'] .= '<div class="thought-response-container bubble round-bl light-blue thinish" id="thought-'.$r['pid'].'-'.$qid.'">
                <div class="inner">
                    <div class="container-fluid">
                    <div class="row">
                        <div class="col-12 col-md-7 pl-md-0 mb-2 mb-md-0">
                            '.$question_label_display.'
                            <div class="user-response">'.$answer.'</div>
                        </div>';
            
            $like_class = mha_liked_response( $user_likes, $r['pid'], $qid ) ? ' liked' : '';
            
            $result['html'] .= '<div class="col-12 col-md-5 px-0 thought-actions text-right">
                <button class="icon thought-like mr-3 mr-md-3 mx-md-3 text-right '.$like_class.'" data-nonce="'.wp_create_nonce("thoughtLike").'" data-pid="'.$r['pid'].'" data-row="'.$qid.'">
                    <span class="image mr-0"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30.93" height="25.99"viewBox="0 0 30.93 25.99" class="heart"><g transform="translate(0 0)"><g transform="translate(0 0)"><path d="M25.592,28s11.175-6.421,11.175-12.608A6.012,6.012,0,0,0,25.592,12.3a6.012,6.012,0,0,0-11.175,3.087C14.417,21.576,25.592,28,25.592,28Z"transform="translate(-10.127 -5.505)" stroke-linecap="round"stroke-linejoin="round" stroke-width="2" /></g></g></svg></span>
                    <span class="text">I relate</span>
                </button>';
        
            $result['html'] .= '<button class="icon thought-flagger px-md-0 mx-md-3 mt-md-3 text-right" data-toggle="tooltip" data-placement="top" title="'.$diy_flag_message.'" aria-controls="#thought-'.$r['pid'].'-'.$qid.'">
                    <span class="image mr-0"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18.231" height="23.342"viewBox="0 0 18.231 23.342" class="flag"><g><path d="M0,23.068a.425.425,0,0,0,.849,0V.7A.425.425,0,0,0,0,.7Z" transform="translate(0 -0.151)" fill="#3d3d3d"stroke="#264a5c" stroke-width="2" /><path class="sail" d="M18.819,11.351H4V1.831Z" transform="translate(-3.287 -0.987)" stroke-miterlimit="10" stroke-width="2" /></g></svg></span>
                    <span class="text">Report</span>
                </button>  
            </div>';
        
            // Admin Debug
            if (current_user_can('edit_posts')) {
                $result['html'] .= '<div class="col-12 admin-debug px-0 pt-4 small caps bold">';
                    $result['html'] .= 'Admin Debug:<br />';   
                    
                    $result['html'] .= '&bull; Activity ID: '.get_field('activity_id', $r['pid'])->ID.'<br />';  
                    $result['html'] .= '&bull; Response ID: '.$r['pid'].'<br />';   
                    $result['html'] .= '&bull; Date Started: '.get_field('started', $r['pid']).'<br />';     
                    $result['html'] .= '&bull; Question Index: '.$qid.'<br /><br />';   

                    $result['html'] .= '&bull; True Likes: '.$r['true_likes'].'<br />';                                 
                    $result['html'] .= '&bull; Modified Likes: '.$r['likes'].'<br />';                                 
                    $result['html'] .= '&bull; All Answered: ';                  
                    $result['html'] .=  ($r['total_answers'] == $total_questions) ? 'Yes' : 'No (+0)';   
        
                    $result['html'] .= '<br />&bull; Total Score: '.$r['score'];     
                    $result['html'] .= '<br />&bull; [<a target="_blank" href="'.get_edit_post_link($r['pid']).'">Edit Submission</a>]<br />';             
                $result['html'] .= '</div>';
            }
            
            $result['html'] .='</div>
                    </div>
                </div>';
        
            // Flag Prompt
            $result['html'] .= '<div class="thought-flag-confirm-container text-center hidden">
                <div class="thought-flag-confirm-container-inner p-2 pt-4 pb-4 relative">
                    <p class="mb-3"><em>&quot;'.$answer.'&quot;</em></p>
                    <p class="mb-3">'.$diy_flag_confirm.'</p>
                    <p class="mb-3"><button class="icon thought-flag thin button red round small" data-nonce="'.wp_create_nonce('thoughtFlag').'" data-pid="'.$r['pid'].'" data-row="'.$qid.'" data-thought-id="#thought-'.$r['pid'].'-'.$qid.'">Yes, this comment is inappropriate</button></p>
                    <button class="cancel-flag-thought button blue thin round small">Nevermind</button>
                </div>
            </div>';
        
            $result['html'] .= '</div>
            </li>';
        
            if($args['single_embed'] == 1 && $q_counter >= 0){
                break; // Skip other responses when only 1 question displayed in embeds
            }

            $q_counter++;
        }
        
        $result['html'] .= '</ol>'; 
        
        if($args['carousel']){
            $result['html'] .= '</div>';
            
            $result['html'] .= '<div class="glide__arrows" data-glide-el="controls">';
            if($show_next_previews){
                $result['html'] .= '<button class="peek diy-carousel-nav fade-left glide__arrow glide__arrow--left" data-glide-dir="<"></button>';
                $result['html'] .= '<button class="peek diy-carousel-nav fade-right glide__arrow glide__arrow--right" data-glide-dir=">"></button>';
            }
        
            foreach($activity_questions as $qid => $qval):
                $result['html'] .= '<button class="diy-direct-slide d-none" data-index="'.$qid.'" data-glide-dir="='.$qid.'">Go to slide #'.($qid+1).'</button>';
            endforeach;
        
            $result['html'] .= '</div>';
            $result['html'] .= '</div>';
        }      
        // End HTML
    }

    // Next Page
    if(count($responses)){
        $result['html'] .= '<div id="diy-load-more-container" class="text-center mt-4 pb-5">';
            if($args['page'] > 1 ){
                $result['html'] .= '<button class="button gray round-tl mr-3 diy-previous-page" data-show-page="'.($args['page'] - 1).'">Previous Page</button>';
            }
            if($args['total_pages'] > 0 && $args['page'] < $args['total_pages'] || $use_cache && count($responses) >= $per_page){
                $result['html'] .= '<button class="diy-load-more button teal round-br" data-show-page="'.($args['page'] + 1).'">Next Page</button>';
            }
        $result['html'] .= '</div>';
    } else {
        $result['html'] .= '<div class="wrap narrow crowdsource-page-label text-center text-orange mb-4 mt-4"><em>No more responses available.</em><hr class="mb-4 mt-2" style="border-color: #FA6767;" /></div>';   
    }
    
    $result['html'] .= '</div>';

    // Wrap it up
    echo json_encode($result);
    exit();
}

function limit_text($text, $limit) {
    if (str_word_count($text, 0) > $limit) {
        $words = str_word_count($text, 2);
        $pos   = array_keys($words);
        $text  = trim(substr($text, 0, $pos[$limit])).'...';
    }
    return $text;
}

function get_diy_response_display( $args ) {
    
    // Post data
    $defaults = array(
        'pid'               => null,
        'true_likes'        => 0,
        'likes'             => 0,
        'args'              => array(),
        'date'              => null,
        'total_questions'   => array(),
        'crowdsource_scoring_id' => 'options'
    );    
    $args = wp_parse_args( $args, $defaults ); 

    // Scoring options
    $complete_bonus = get_field('crowdsource_scoring_complete_answers_bonus', $args['crowdsource_scoring_id']);
    $recency_check = get_field('crowdsource_scoring_recency_bias', $args['crowdsource_scoring_id']);

    $response = [];

    // Confirm the crowdthought is part of the activity
    $post_act_id = get_field('activity_id', $args['pid']);

    if($args['pid'] && $post_act_id && $post_act_id->ID == $args['args']['activity_id']){

        $response['pid'] = $args['pid'];
        $response['true_likes'] = $args['true_likes'];
        $response['likes'] = $args['likes'];
        $response['qresponses'] = get_field('response', $args['pid']);
        $response['total_answers'] = 0;

        if( $response['qresponses'] && is_array( $response['qresponses'])){
            foreach( $response['qresponses'] as $qr){
                if($qr['answer'] != ''){
                    // +1 total
                    $response['total_answers'] = $response['total_answers'] + 1;

                    // Truncated answer toggle
                    if(str_word_count($qr['answer']) > 45){
                        $response['answers'][$qr['id']]['answer'] = truncate_answer($qr['answer'], 35, 'q'.$args['pid'].''.$qr['id']);
                    } else {
                        $response['answers'][$qr['id']]['answer'] = $qr['answer'];
                    }
                }
            }
        }

        $total_answers_bonus = $complete_bonus ? get_field('crowdsource_scoring_complete_answers_bonus', $args['crowdsource_scoring_id']) : 0;
        $response['score'] = ($response['total_answers'] == $args['total_questions']) ? $args['likes'] + $total_answers_bonus : $args['likes'];

        // Additional Recency Bias calculations
        $p_date = strtotime($args['date']);
        if( $recency_check ):
            $i = 0;
            foreach( $recency_check as $r ):
                $r_date = strtotime($r['date_range']);
                $r_score = $r['score'];
                $response[$i]['r_date'] = $r_date;
                $response[$i]['r_score'] = $r_score;
                $response[$i]['p_date'] = $p_date;
                if($r_date >= $p_date){
                    $response['score'] = $response['score'] + $r_score;
                }
                $i++;
            endforeach;
        endif; 

        return $response;
    }

    return false;

}

/**
 * Get users results
 */
add_action("wp_ajax_nopriv_mhaDiyGetConfirmation", "mhaDiyGetConfirmation");
add_action("wp_ajax_mhaDiyGetConfirmation", "mhaDiyGetConfirmation");
function mhaDiyGetConfirmation() {
    
	// General variables
    $result = [];

    // Post data
    $defaults = array(
        'id' => null,
        'embedded' => 0
    );    
    parse_str($_POST['data'], $data);  
    $args = wp_parse_args( $data, $defaults ); 	
    $result['args'] = $args;
    $result['title'] = get_the_title($args['id']);

    ob_start();
    //get_template_part( 'templates/diy-tools/cta', 'login', array( 'id' => $args['id'], 'embedded' => $args['embedded'] ) ); 
    get_template_part( 'templates/diy-tools/page', 'confirmation', array( 'id' => $args['id'], 'embedded' => $args['embedded'] ) );	
    $result['html'] = ob_get_contents();
    ob_end_clean();

    echo json_encode($result);
    exit();

}



/**
 * Toggle hide thought
 */
add_action("wp_ajax_nopriv_mhaToggleHideThought", "mhaToggleHideThought");
add_action("wp_ajax_mhaToggleHideThought", "mhaToggleHideThought");
function mhaToggleHideThought() {

	// General variables
    $result = [];

    // Post data
    $defaults = array(
        'pid' => null,
        'value' => null
    );    
    parse_str($_POST['data'], $data); 

    $result['defaults'] = $defaults;
    $result['data'] = $data;

    $result['updated'] = update_field('crowdsource_hidden', $data['value'], $data['pid']);
    $result['new_value'] = get_field('crowdsource_hidden', $data['pid']);

    echo json_encode($result);
    exit();

}