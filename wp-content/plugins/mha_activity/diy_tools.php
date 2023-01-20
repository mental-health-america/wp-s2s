<?php
/**
 * DIY Tools
 */

// Enqueing Scripts
add_action('init', 'mhaDiyToolsScripts');
function mhaDiyToolsScripts() {
	wp_enqueue_script('process_mhaDiyTools', plugin_dir_url( __FILE__ ).'diy_tools.js', 'jquery', 'v20221209_p2', true);
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
        'crowdsource_hidden'    => null,
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
        // $current_post_id = $current_post_loop->found_posts ? $current_post_loop->post->ID : null;
        $current_post_id = $args['diytool_current_id'] ? $args['diytool_current_id'] : null;
    
        $response_rows = array();
        $answer_count = 0;
        foreach($args as $k => $v){
            if(strpos($k, 'answer_') !== false){
                $r_id = str_replace("answer_", "", $k);
                $answer = sanitize_text_field($args['answer_'.$r_id]);
                if($answer != ''){
                    $new_row = array(
                        'field_634840ef4cbb3'	=> $r_id, // Question ID
                        'field_634857934cbb4' 	=> $answer, // Answer
                        'field_6348579d4cbb5' 	=> $timestamp, // Date
                    );
                    array_push($response_rows, $new_row);
                    $answer_count++;
                }
            }
        }

        $result['answer_count'] = $answer_count;
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
                update_field('activity_id', array($args['activity_id']), $result['post_id']); // Post object needs an array to submit?
                update_field('ipiden', $ipiden, $result['post_id']);
                update_field('started', $timestamp, $result['post_id']);
                update_field('ref_code', sanitize_text_field($args['ref_code']), $result['post_id']);         
                update_field('user_viewed_crowdsource', $args['opened_diy'], $result['post_id']);               
                update_field('crowdsource_hidden', $args['crowdsource_hidden'], $result['post_id']);
                update_field('user_viewed_crowdsource_question', $args['opened_diy_question'], $result['post_id']);

            } else {
                
                $result['error'] = 'You must have at least one question answered before you can submit.';   
                echo json_encode($result);
                exit();
                
            }

        }

        if($result['post_id']){
            
            $current_responses = get_field('field_63483d064cbb0', $result['post_id']);
            $result['current_responses'] = $current_responses;
            
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


add_action("wp_ajax_nopriv_getDiyCrowdsource", "getDiyCrowdsource");
add_action("wp_ajax_getDiyCrowdsource", "getDiyCrowdsource");
function getDiyCrowdsource(){

    // Post data
    $result = [];
    $defaults = array(
        'question'      => null,
        'current'       => null,
        'activity_id'   => null,
        'carousel'      => null
    );    
    parse_str($_POST['data'], $data);  
    $args = wp_parse_args( $data, $defaults ); 
    $result['html'] = '';
    $result['args'] = $args;

    // Get likes for later
    global $wpdb;
    $top_likes = $wpdb->get_results("SELECT pid, row, uid FROM thoughts_likes WHERE ref_pid = {$args["activity_id"]} AND unliked = 0 ORDER BY date DESC LIMIT 200");

    // Get flags for later
    $top_flags_query = $wpdb->get_results("SELECT pid, row, COUNT(pid) AS highflags FROM thoughts_flags WHERE ref_pid = {$args["activity_id"]} HAVING (highflags > 5) ORDER BY date DESC LIMIT 200");
    $top_flags = [];
    if($top_flags_query){
        foreach($top_flags_query as $tf){
            $top_flags[] = $tf->pid;
        }
    }

    $crowd_args = array(
        "post_type"     	=> 'diy_responses',
        "order"             => 'DESC',
        "orderby"           => 'date',
        "post_status"       => array('publish'),
        "posts_per_page"    => 20,
        "paged"             => 1,
        "post__not_in"      => $top_flags,
        "meta_query"		=> array(
            array(
                'key'       => 'activity_id',
                'value'     => '"'.$args["activity_id"].'"',
                'compare'   => 'LIKE'
            )
        )
    );
    
    if($args['current']){
        $crowd_args["post__not_in"] = array( $args['current'] );
    }

    $questions = get_field('questions', $args["activity_id"]); // Matching question labels for later
    $diy_flag_message = get_field('flag_message','options'); // Used for the report button tooltip
    $diy_flag_confirm = get_field('flag_confirmation','options'); // Used for the report button tooltip

    $result['questions'] = $questions;   
    $result['html'] .= '<div class="question-container" data-question="'.$args['question'].'">';   
    $responses = [];

    // Get the answers for this question
    $crowd_loop = new WP_Query($crowd_args);                      
    if($crowd_loop->have_posts()):
    while($crowd_loop->have_posts()) : $crowd_loop->the_post();   

        if(get_field('crowdsource_hidden')){
            continue; // Skip items marked hidden from crowdsource display
        }

        $pid = get_the_ID();
        $qresponses = get_field('response');
        $qanswers = 0;
        foreach($qresponses as $qr){
            if($qr['answer'] != ''){
                $qanswers++;
            }
        }

        $responses[$pid]['date'] = get_the_date('c');
        $responses[$pid]['html'] = '';
        $responses[$pid]['likes'] = 0;

        $actid = get_field('activity_id');

        if($args['carousel']){
            $responses[$pid]['html'] .= '<div class="crowdsource-responses glide">';
            $responses[$pid]['html'] .= '<div class="glide__track" data-glide-el="track">';
            $responses[$pid]['html'] .= '<ol class="glide__slides">';
        } else {
            $responses[$pid]['html'] .= '<ol class="crowdthought">';
        }

        foreach($questions as $qid => $qval){

            if(!$args['carousel'] && $qresponses[$qid]['id'] != $args['question']){
                continue;
            }

            //$question_id = $args['question'] ? $args['question'] : $qresponses[$qid]['id'];
            $answer = '<em class="no-response">User did not provide a response.</em>';
            foreach($qresponses as $qr){
                if($qr['id'] == $qid){
                    $answer = $qr['answer'];
                    break;
                }
            }

            $answer_length = str_word_count($answer);
            if($answer_length > 45){
                $answer = truncate_answer($answer, 35, 'q'.$pid.''.$qid);
            }

            if($args['carousel']){
                $responses[$pid]['html'] .= '<li class="glide__slide">';
            } else {
                $responses[$pid]['html'] .= '<li data-question="'.$qresponses[$qid]['id'].'">';
            }

            $responses[$pid]['html'] .= '<div class="bubble round-bl light-blue" id="thought-'.$pid.'-'.$qid.'">
                <div class="inner">
                    <div class="container-fluid">
                    <div class="row">
                        <div class="col-12 col-md-7 pl-md-0">
                            <div class="question-label small"><strong>'.$questions[$qid]['question'].'</strong></div>
                            <div class="user-response">'.$answer.'</div>
                        </div>';
                        
            $like_class = (likeChecker($pid, $qid)) ? ' liked' : '';
            
            $responses[$pid]['html'] .= '<div class="col-12 col-md-5 px-0 thought-actions text-right">
                <button class="icon thought-like mr-4 '.$like_class.'" data-nonce="'.wp_create_nonce("thoughtLike").'" data-pid="'.$pid.'" data-row="'.$qid.'">
                    <span class="image mr-0"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30.93" height="25.99"viewBox="0 0 30.93 25.99" class="heart"><g transform="translate(0 0)"><g transform="translate(0 0)"><path d="M25.592,28s11.175-6.421,11.175-12.608A6.012,6.012,0,0,0,25.592,12.3a6.012,6.012,0,0,0-11.175,3.087C14.417,21.576,25.592,28,25.592,28Z"transform="translate(-10.127 -5.505)" stroke-linecap="round"stroke-linejoin="round" stroke-width="2" /></g></g></svg></span>
                    <span class="text">I relate</span>
                </button>';

            $responses[$pid]['html'] .= '<button class="icon thought-flagger px-md-0" data-toggle="tooltip" data-placement="top" title="'.$diy_flag_message.'" aria-controls="#thought-'.$pid.'-'.$qid.'">
                    <span class="image mr-0"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18.231" height="23.342"viewBox="0 0 18.231 23.342" class="flag"><g><path d="M0,23.068a.425.425,0,0,0,.849,0V.7A.425.425,0,0,0,0,.7Z" transform="translate(0 -0.151)" fill="#3d3d3d"stroke="#264a5c" stroke-width="2" /><path class="sail" d="M18.819,11.351H4V1.831Z" transform="translate(-3.287 -0.987)" stroke-miterlimit="10" stroke-width="2" /></g></svg></span>
                    <span class="text">Report</span>
                </button>  
            </div>';

            // Admin Debug
            $qlikes = 0;
            foreach($top_likes as $l){
                if($l->pid == $pid && $l->row == $qid){
                    $qlikes++;
                }
            }
            $responses[$pid]['likes'] = $qlikes;

            $score = ($qanswers == count($questions)) ? $qlikes + 1 : $qlikes;
            $responses[$pid]['score'] = $score;

            if (current_user_can('edit_posts')) {
                $responses[$pid]['html'] .= '<div class="col-12 admin-debug px-0 pt-4 small caps bold">';
                    $responses[$pid]['html'] .= 'Admin Debug:<br />';   
                    
                    $responses[$pid]['html'] .= '&bull; Activity ID: '.$args["activity_id"].'<br />';   
                    $responses[$pid]['html'] .= '&bull; Question Index: '.$qid.'<br />';   
                    $responses[$pid]['html'] .= '&bull; Likes: '.$qlikes.'<br />';             
                    
                    $responses[$pid]['html'] .= '&bull; All Answered: ';                  
                    $responses[$pid]['html'] .=  ($qanswers == count($questions)) ? 'Yes' : 'No';   

                    $responses[$pid]['html'] .= '<br />&bull; Total Score: '.$score;     
                    $responses[$pid]['html'] .= '<br />&bull; [<a target="_blank" href="'.get_edit_post_link($pid).'">Edit Submission</a>]<br />';             
                    //$responses[$pid]['html'] .= print_r($qresponses, true);             
                $responses[$pid]['html'] .= '</div>';
            }
            
            $responses[$pid]['html'] .='</div>
                    </div>
                </div>';

            // Flag Prompt
            $responses[$pid]['html'] .= '<div class="thought-flag-confirm-container text-center hidden">
                <div class="thought-flag-confirm-container-inner p-2 pt-4 pb-4 relative">
                    <p class="mb-3"><em>&quot;'.$answer.'&quot;</em></p>
                    <p class="mb-3">'.$diy_flag_confirm.'</p>
                    <p class="mb-3"><button class="icon thought-flag thin button red round small" data-nonce="'.wp_create_nonce('thoughtFlag').'" data-pid="'.$pid.'" data-row="'.$qid.'" data-thought-id="#thought-'.$pid.'-'.$qid.'">Yes, report this comment</button></p>
                    <button class="cancel-flag-thought button blue thin round small">Nevermind</button>
                </div>
            </div>';

            $responses[$pid]['html'] .= '</div>
            </li>';

        }

        $responses[$pid]['html'] .= '</ol>'; 

        if($args['carousel']){
            $responses[$pid]['html'] .= '</div>';
            
            $responses[$pid]['html'] .= '<div class="glide__arrows" data-glide-el="controls">';
            $responses[$pid]['html'] .= '<button class="peek diy-carousel-nav fade-left glide__arrow glide__arrow--left" data-glide-dir="<"></button>';
            $responses[$pid]['html'] .= '<button class="peek diy-carousel-nav fade-right glide__arrow glide__arrow--right" data-glide-dir=">"></button>';

            foreach($questions as $qid => $qval):
                $responses[$pid]['html'] .= '<button class="diy-direct-slide d-none" data-index="'.$qid.'" data-glide-dir="='.$qid.'">Go to slide #'.($qid+1).'</button>';
            endforeach;

            $responses[$pid]['html'] .= '</div>';
            $responses[$pid]['html'] .= '</div>';
        }      
        
    endwhile;
    endif;

    // Print responses, sorted by likes

    usort($responses, function ($a, $b) {return $a['score'] < $b['score'];});
    foreach($responses as $r){
        $result['html'] .= $r['html'];
    }

    $result['html'] .= '</div>';

    echo json_encode($result);
    exit();
}


