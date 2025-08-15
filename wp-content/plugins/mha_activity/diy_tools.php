<?php
/**
 * DIY Tools
 */

// Enqueing Scripts
add_action('init', 'mhaDiyToolsScripts');
function mhaDiyToolsScripts() {
	wp_enqueue_script('process_mhaDiyTools', plugin_dir_url( __FILE__ ).'diy_tools.js', array( 'jquery' ), 'v20250814_3', true);
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


function getDiyTopLikes($activity_id, $flagged_posts = [], $total_questions = 0, $args = []) {
    
    // Get scoring configuration    
    $crowdsource_scoring_date_range = get_field('crowdsource_scoring_date_range', $args['activity_id']);
    $date_old = $crowdsource_scoring_date_range 
        ? date('Y-m-d', strtotime($crowdsource_scoring_date_range))
        : date('Y-m-d', strtotime('30 days ago'));
    
    $relate_bonus = get_field('crowdsource_scoring_relate_bonus', $args['activity_id']);
    
    // Cache settings
    $cache_file = plugin_dir_path(__FILE__) . 'tmp/total_relates_' . $activity_id . '.json';
    $cache_dir = dirname($cache_file);
    
    // Ensure cache directory exists
    if (!is_dir($cache_dir)) {
        wp_mkdir_p($cache_dir);
    }
    
    // Check cache first
    $top_likes_data = [];
    
    /*
    if (file_exists($cache_file) && filemtime($cache_file) > strtotime('-1 day')) {
        $cached_data = json_decode(file_get_contents($cache_file), true);
        if ($cached_data && isset($cached_data['top_likes'])) {
            $top_likes_data = $cached_data['top_likes'];
        }
    }
    */
    
    if (empty($top_likes_data)) {
        global $wpdb;
        
        // Build efficient query for top likes
        $where_clause = $wpdb->prepare("
            WHERE ref_pid = %d 
            AND unliked = 0 
            AND date >= %s
        ", $activity_id, $date_old);
        
        // Add flagged posts exclusion if provided
        if (!empty($flagged_posts)) {
            $flagged_ids = implode(',', array_map('intval', $flagged_posts));
            $where_clause .= " AND pid NOT IN ($flagged_ids)";
        }
        
        $top_likes_query = "
            SELECT pid, COUNT(*) as total_likes 
            FROM thoughts_likes 
            $where_clause
            GROUP BY pid 
            ORDER BY total_likes DESC
            LIMIT 500
        ";
        
        $top_likes = $wpdb->get_results($top_likes_query);
        
        if ($top_likes) {
            foreach ($top_likes as $tl) {
                // Verify post exists and is not hidden
                if (get_post($tl->pid) && !get_field('crowdsource_hidden', $tl->pid)) {
                    $response_likes_override = $relate_bonus ? ($relate_bonus * $tl->total_likes) : $tl->total_likes;
                    $top_likes_data[] = [
                        'pid' => $tl->pid,
                        'date' => get_the_date('', $tl->pid),
                        'likes' => ($tl->total_likes > 0) ? $response_likes_override : 0,
                        'true_likes' => intval($tl->total_likes),
                    ];
                }
            }
        }
        
        // Cache the results
        $cache_data = [
            'top_likes' => $top_likes_data,
            'cached_at' => current_time('mysql')
        ];
        file_put_contents($cache_file, json_encode($cache_data));
    }
    
    return $top_likes_data;
}


/**
 * Debug function to check cache status
 */
function debug_diy_crowdsource_cache($activity_id = null) {
    global $wpdb;
    
    $debug_info = [
        'cache_system' => 'Unknown',
        'cache_enabled' => false,
        'object_cache_available' => false,
        'cache_keys_found' => [],
        'cache_groups_found' => [],
        'recent_cache_entries' => [],
        'wp_options_cache_entries' => []
    ];
    
    // Check if object cache is available
    if (function_exists('wp_cache_get')) {
        $debug_info['object_cache_available'] = true;
        
        // Test cache functionality
        $test_key = 'diy_cache_test_' . time();
        $test_data = ['test' => 'data'];
        wp_cache_set($test_key, $test_data, 'diy_test_group', 60);
        $retrieved = wp_cache_get($test_key, 'diy_test_group');
        
        if ($retrieved === $test_data) {
            $debug_info['cache_enabled'] = true;
            $debug_info['cache_system'] = 'Object Cache (Working)';
        } else {
            $debug_info['cache_system'] = 'Object Cache (Not Working)';
        }
        
        // Clean up test
        wp_cache_delete($test_key, 'diy_test_group');
    }
    
    // Check wp_options for cache entries (fallback)
    $cache_options = $wpdb->get_results("
        SELECT option_name, option_value, autoload 
        FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_%' 
        OR option_name LIKE '_site_transient_%'
        OR option_name LIKE '%diy_crowdsource%'
        ORDER BY option_name
        LIMIT 20
    ");
    
    if ($cache_options) {
        $debug_info['wp_options_cache_entries'] = array_map(function($option) {
            return [
                'name' => $option->option_name,
                'autoload' => $option->autoload,
                'value_length' => strlen($option->option_value)
            ];
        }, $cache_options);
    }
    
    // Check for specific DIY cache entries
    if ($activity_id) {
        $cache_group = 'diy_crowdsource_' . $activity_id;
        $cache_key = 'diy_crowdsource_' . $activity_id . '_' . md5(serialize(['test' => 'data']));
        
        // Try to get a specific cache entry
        $specific_cache = wp_cache_get($cache_key, $cache_group);
        $debug_info['specific_cache_test'] = [
            'cache_key' => $cache_key,
            'cache_group' => $cache_group,
            'found' => ($specific_cache !== false),
            'value' => $specific_cache
        ];
    }
    
    return $debug_info;
}

/**
 * SQL query to check cache entries in wp_options
 */
function get_diy_cache_sql_queries() {
    global $wpdb;
    
    $queries = [
        'all_transients' => "
            SELECT 
                option_name,
                LENGTH(option_value) as value_size,
                autoload,
                option_value
            FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%' 
            OR option_name LIKE '_site_transient_%'
            ORDER BY option_name
            LIMIT 50
        ",
        
        'diy_specific_transients' => "
            SELECT 
                option_name,
                LENGTH(option_value) as value_size,
                autoload,
                SUBSTRING(option_value, 1, 200) as value_preview
            FROM {$wpdb->options} 
            WHERE option_name LIKE '%diy%' 
            OR option_name LIKE '%crowdsource%'
            ORDER BY option_name
        ",
        
        'large_transients' => "
            SELECT 
                option_name,
                LENGTH(option_value) as value_size,
                autoload
            FROM {$wpdb->options} 
            WHERE (option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%')
            AND LENGTH(option_value) > 1000
            ORDER BY LENGTH(option_value) DESC
            LIMIT 20
        ",
        
        'recent_transients' => "
            SELECT 
                option_name,
                LENGTH(option_value) as value_size,
                autoload,
                option_value
            FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_%' 
            OR option_name LIKE '_site_transient_%'
            ORDER BY option_id DESC
            LIMIT 20
        "
    ];
    
    return $queries;
}

/**
 * Admin function to debug cache status
 */
add_action('wp_ajax_debug_diy_cache', 'admin_debug_diy_cache');
function admin_debug_diy_cache() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : null;
    $debug_info = debug_diy_crowdsource_cache($activity_id);
    
    // Get SQL queries
    $queries = get_diy_cache_sql_queries();
    $query_results = [];
    
    global $wpdb;
    foreach ($queries as $query_name => $query) {
        $query_results[$query_name] = $wpdb->get_results($query);
    }
    
    $debug_info['sql_queries'] = $query_results;
    
    wp_send_json_success($debug_info);
}

/**
 * Enhanced cache debugging in the main function
 */
add_action("wp_ajax_nopriv_getDiyCrowdsource", "getDiyCrowdsource");
add_action("wp_ajax_getDiyCrowdsource", "getDiyCrowdsource");
function getDiyCrowdsource(){
    // Cache debugging control - set to false to disable debugging
    $enable_cache_debugging = false;
    
    // Initialize result array
    $result = [
        'html' => '',
        'args' => [],
        'responses' => [],
        'total_pages' => 0,
        'current_page' => 0,
        'cache_debug' => []
    ];

    // Parse and validate input data
    $defaults = [
        'question'      => null,
        'current'       => null,
        'activity_id'   => null,
        'carousel'      => null,
        'page'          => 1,
        'embedded'      => 0,
        'single_embed'  => 0
    ];    
    parse_str($_POST['data'], $data);  
    $args = wp_parse_args($data, $defaults); 
    $args['page'] = max(1, intval($args['page'])); // Ensure page is at least 1
    
    $result['args'] = $args;
    $result['current_page'] = $args['page'];
    
    // Pagination settings
    $per_page = $args['embedded'] ? 5 : 10;
    
    // Get activity data
    $activity_questions = get_field('questions', $args['activity_id']);
    $total_questions = $activity_questions ? count($activity_questions) : 0;
    $show_next_previews = get_field('show_next_previews', $args['activity_id']) ?: 0;
    
    if($args['single_embed'] == 1){
        $show_next_previews = 0;
    }
    
    // Better caching strategy using WordPress transients
    // Create a stable cache key that excludes user-specific parameters
    $cache_args = $args;
    unset($cache_args['current']); // Remove user-specific current post ID
    $cache_key = 'diy_crowdsource_' . $args['activity_id'] . '_' . md5(serialize($cache_args));
    // Remove cache group - some cache backends don't handle groups properly
    $cache_group = '';
    
    // Enhanced cache debugging (only for administrators and when enabled)
    if ($enable_cache_debugging && current_user_can('manage_options')) {
        $result['cache_debug'] = [
            'cache_key' => $cache_key,
            'cache_group' => $cache_group,
            'cache_key_length' => strlen($cache_key),
            'object_cache_available' => function_exists('wp_cache_get'),
            'cache_test' => null,
            'original_args' => $args,
            'cache_args' => $cache_args,
            'cache_key_md5' => md5(serialize($cache_args)),
            'cache_key_validation' => [
                'length_ok' => strlen($cache_key) <= 250, // Most cache systems have limits
                'contains_special_chars' => preg_match('/[^a-zA-Z0-9_-]/', $cache_key),
                'cache_key_safe' => preg_match('/^[a-zA-Z0-9_-]+$/', $cache_key)
            ]
        ];
    } else {
        $result['cache_debug'] = null;
    }
    
    // Check cache first
    $use_cache = false;
    $responses = [];
    
    // Try to get from cache using transients (more reliable than object cache)
    $cached_data = get_transient($cache_key);
    $result['cached_data'] = $cached_data;
    if ($enable_cache_debugging && current_user_can('manage_options')) {
        $result['cache_debug']['cache_get_result'] = $cached_data;
    }
    
    if ($cached_data !== false) {
        $responses = $cached_data['responses'] ?? [];
        $result['total_pages'] = $cached_data['total_pages'] ?? 0;
        $result['has_next_page'] = $cached_data['has_next_page'] ?? false;
        $result['use_cache'] = 'true';
        $use_cache = true;
    }

    if (!$use_cache) {
        $result['use_cache'] = 'false';
        
        // Test cache functionality using transients
        $test_key = 'diy_test_' . time();
        $test_data = ['test' => 'data'];
        set_transient($test_key, $test_data, 60);
        $test_retrieved = get_transient($test_key);
        delete_transient($test_key);
        
        if ($enable_cache_debugging && current_user_can('manage_options')) {
            $result['cache_debug']['cache_test'] = [
                'set_success' => ($test_retrieved === $test_data),
                'test_retrieved' => $test_retrieved
            ];
            
            // Additional cache debugging
            $result['cache_debug']['cache_storage_test'] = [
                'cache_key_exists' => get_transient($cache_key) !== false,
                'cache_key_after_set' => null,
                'cache_group_keys' => [],
                'wp_cache_get_result' => get_transient($cache_key),
                'transient_test' => [
                    'set' => set_transient('diy_test_transient', ['test' => 'data'], 60),
                    'get' => get_transient('diy_test_transient'),
                    'delete' => delete_transient('diy_test_transient')
                ]
            ];
        }
        
        // Get scoring configuration        
        $crowdsource_scoring_date_range = get_field('crowdsource_scoring_date_range', $args['activity_id']);
        $date_old = $crowdsource_scoring_date_range 
            ? date('Y-m-d', strtotime($crowdsource_scoring_date_range))
            : date('Y-m-d', strtotime('30 days ago'));
            
        $relate_bonus = get_field('crowdsource_scoring_relate_bonus', $args['activity_id']);
        
        // Get flagged posts to exclude
        global $wpdb;
        $flagged_posts = [];
        $flagged_query = $wpdb->get_results($wpdb->prepare("
            SELECT pid 
            FROM thoughts_flags 
            WHERE ref_pid = %d
            GROUP BY pid
            HAVING COUNT(pid) >= 1
        ", $args['activity_id']));
        
        if ($flagged_query) {
            foreach ($flagged_query as $flag) {
                // Only exclude if no admin note (admin reviewed and approved)
                $admin_note = get_field('admin_notes', $flag->pid);
                if (!$admin_note || $admin_note == '') {
                    $flagged_posts[] = $flag->pid;
                }
            }
        }
        
        // Build exclusion list for flagged posts and current post
        $exclude_posts = $flagged_posts;
        if ($args['current']) {
            $exclude_posts[] = $args['current'];
        }
        $exclude_clause = '';
        if (!empty($exclude_posts)) {
            $exclude_ids = implode(',', array_map('intval', $exclude_posts));
            $exclude_clause = "AND p.ID NOT IN ($exclude_ids)";
        }
        
        // Single efficient query to get all posts with likes for this activity
        $posts_query = $wpdb->prepare("
            SELECT 
                p.ID as pid,
                p.post_date,
                p.post_title,
                COALESCE(l.like_count, 0) as like_count,
                COALESCE(l.recent_like_count, 0) as recent_like_count
            FROM {$wpdb->posts} p
            LEFT JOIN (
                SELECT 
                    pid,
                    COUNT(*) as like_count,
                    SUM(CASE WHEN date >= %s THEN 1 ELSE 0 END) as recent_like_count
                FROM thoughts_likes 
                WHERE unliked = 0
                GROUP BY pid
            ) l ON p.ID = l.pid
            LEFT JOIN {$wpdb->postmeta} pm_activity ON p.ID = pm_activity.post_id AND pm_activity.meta_key = 'activity_id'
            LEFT JOIN {$wpdb->postmeta} pm_hidden ON p.ID = pm_hidden.post_id AND pm_hidden.meta_key = 'crowdsource_hidden'
            WHERE p.post_type = 'diy_responses'
            AND p.post_status = 'publish'
            AND pm_activity.meta_value LIKE %s
            AND (pm_hidden.meta_value != '1' OR pm_hidden.meta_value IS NULL)
            $exclude_clause
            ORDER BY recent_like_count DESC, p.post_date DESC
            LIMIT 200
        ", $date_old, '%"' . $args['activity_id'] . '"%');
        
        $all_posts = $wpdb->get_results($posts_query);
        
        // Process posts and calculate scores
        $responses_collection = [];
        foreach ($all_posts as $post) {
            $pid = $post->pid;
            
            // Apply relate bonus if configured
            $true_likes = intval($post->recent_like_count);
            $likes = $relate_bonus ? ($relate_bonus * $true_likes) : $true_likes;
            
            $responses_collection[$pid] = [
                'id' => $pid,
                'date' => $post->post_date,
                'likes' => $likes,
                'true_likes' => $true_likes,
            ];
        }
        
        // Process responses through display function to calculate scores
        foreach ($responses_collection as $pid => $response_data) {
            $response_args = [
                'pid' => $pid,
                'true_likes' => $response_data['true_likes'],
                'likes' => $response_data['likes'],
                'args' => $args,
                'date' => $response_data['date'],
                'total_questions' => $total_questions,
                'crowdsource_scoring_id' => $args['activity_id']
            ];
            
            $display_response = get_diy_response_display($response_args);
            if ($display_response) {
                $responses[] = $display_response;
            }
        }
        
        // Sort by score (highest first)
        usort($responses, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });
        
        // Calculate pagination
        $total_posts = count($responses);
        $result['total_pages'] = max(1, ceil($total_posts / $per_page));
        
        // Check if next page would have content
        $next_page_start = $args['page'] * $per_page;
        $result['has_next_page'] = ($next_page_start < $total_posts);
        
        // Apply pagination to responses
        $start_index = ($args['page'] - 1) * $per_page;
        $responses = array_slice($responses, $start_index, $per_page);
        
        // Cache the results using WordPress transients (24 hours)
        $cache_data = [
            'responses' => $responses,
            'total_pages' => $result['total_pages'],
            'has_next_page' => $result['has_next_page'] ?? false,
            'cached_at' => current_time('mysql'),
            'cache_version' => '1.0'
        ];
        
        $cache_set_result = set_transient($cache_key, $cache_data, DAY_IN_SECONDS);
        if ($enable_cache_debugging && current_user_can('manage_options')) {
            $result['cache_debug']['cache_set_result'] = $cache_set_result;
            $result['cache_debug']['cache_data_size'] = strlen(serialize($cache_data));
        }
        
        // Test if cache can be retrieved immediately after setting
        $immediate_retrieval = get_transient($cache_key);
        if ($enable_cache_debugging && current_user_can('manage_options')) {
            $result['cache_debug']['cache_storage_test']['cache_key_after_set'] = [
                'retrieved' => $immediate_retrieval !== false,
                'retrieved_data' => $immediate_retrieval,
                'cache_key' => $cache_key,
                'cache_group' => 'transient' // Using transients instead of groups
            ];
        }
    }
    
    // Get user likes for pre-marking
    $pids_search = array_column($responses, 'pid');
    $user_likes = !empty($pids_search) ? get_all_mha_user_likes($pids_search) : [];
    
         // Build HTML output
     $result['responses'] = $responses;
     $result['html'] = build_crowdsource_html($responses, $args, $activity_questions, $user_likes, $show_next_previews, $total_questions, $result['total_pages'], $result['has_next_page'] ?? false);
    
    echo json_encode($result);
    exit();
}

/**
 * Build HTML for crowdsource responses
 */
function build_crowdsource_html($responses, $args, $activity_questions, $user_likes, $show_next_previews, $total_questions, $total_pages = 0, $has_next_page = false) {
    $html = '<div class="question-container" data-page="' . $args['page'] . '">';
    
    // Page label
    if ($args['page'] > 1 && count($responses) > 0) {
        $html .= '<div class="wrap narrow crowdsource-page-label text-center text-teal mb-3">Page ' . $args['page'] . '</div>';
    }
    
    // Helper variables
    $diy_flag_message = get_field('flag_message', 'options');
    $diy_flag_confirm = get_field('flag_confirmation', 'options');
    
    foreach ($responses as $r) {
        // Begin HTML structure
        if ($args['carousel']) {
            $html .= '<div class="crowdsource-responses glide">';
            $html .= '<div class="glide__track" data-glide-el="track">';
            $html .= '<ol class="glide__slides">';
        } else {
            $html .= '<ol class="crowdthought">';
        }
        
        $q_counter = 0;
        foreach ($activity_questions as $qid => $qval) {
            if (!$args['carousel'] && $r['answers'][$qid]['id'] != $args['question']) {
                continue;
            }
            
            if ($args['carousel']) {
                $html .= '<li class="glide__slide">';
            } else {
                $html .= '<li data-question="' . $r['answers'][$qid]['id'] . '">';
            }
            
            // Get answer text
            $answer = isset($r['answers'][$qid]) 
                ? $r['answers'][$qid]['answer'] 
                : '<em class="no-response">User did not provide a response.</em>';
            
            // Build question label
            $question_label_full = $activity_questions[$qid]['question'];
            $question_label_length = str_word_count($question_label_full, 0);
            
            if ($question_label_length > 15) {
                $question_label_short = limit_text($question_label_full, 15);
                $question_label_display = '<div class="question-label-toggle mb-3">';
                $question_label_display .= '<div class="question-label-short small"><strong>' . $question_label_short . '</strong></div>';
                $question_label_display .= '<div class="question-label-long d-none small"><strong>' . $question_label_full . '</strong></div>';
                $question_label_display .= '</div>';
            } else {
                $question_label_display = '<div class="question-label small mb-3"><strong>' . $question_label_full . '</strong></div>';
            }
            
            // Build response container
            $like_class = mha_liked_response($user_likes, $r['pid'], $qid) ? ' liked' : '';
            
            $html .= '<div class="thought-response-container bubble round-bl light-blue thinish" id="thought-' . $r['pid'] . '-' . $qid . '">
                <div class="inner">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-12 col-md-7 pl-md-0 mb-2 mb-md-0">
                                ' . $question_label_display . '
                                <div class="user-response">' . $answer . '</div>
                            </div>
                            <div class="col-12 col-md-5 px-0 thought-actions text-right">
                                <button class="icon thought-like mr-3 mr-md-3 mx-md-3 text-right ' . $like_class . '" data-nonce="' . wp_create_nonce("thoughtLike") . '" data-pid="' . $r['pid'] . '" data-row="' . $qid . '">
                                    <span class="image mr-0"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="30.93" height="25.99"viewBox="0 0 30.93 25.99" class="heart"><g transform="translate(0 0)"><g transform="translate(0 0)"><path d="M25.592,28s11.175-6.421,11.175-12.608A6.012,6.012,0,0,0,25.592,12.3a6.012,6.012,0,0,0-11.175,3.087C14.417,21.576,25.592,28,25.592,28Z"transform="translate(-10.127 -5.505)" stroke-linecap="round"stroke-linejoin="round" stroke-width="2" /></g></g></svg></span>
                                    <span class="text">I relate</span>
                                </button>
                                <button class="icon thought-flagger px-md-0 mx-md-3 mt-md-3 text-right" data-toggle="tooltip" data-placement="top" title="' . $diy_flag_message . '" aria-controls="#thought-' . $r['pid'] . '-' . $qid . '">
                                    <span class="image mr-0"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="18.231" height="23.342"viewBox="0 0 18.231 23.342" class="flag"><g><path d="M0,23.068a.425.425,0,0,0,.849,0V.7A.425.425,0,0,0,0,.7Z" transform="translate(0 -0.151)" fill="#3d3d3d"stroke="#264a5c" stroke-width="2" /><path class="sail" d="M18.819,11.351H4V1.831Z" transform="translate(-3.287 -0.987)" stroke-miterlimit="10" stroke-width="2" /></g></svg></span>
                                    <span class="text">Report</span>
                                </button>
                            </div>';
            
            // Admin debug info
            if (current_user_can('edit_posts')) {
                $html .= '<div class="col-12 admin-debug px-0 pt-4 small caps bold">';
                $html .= 'Admin Debug:<br />';
                $html .= '&bull; Activity ID: ' . get_field('activity_id', $r['pid'])->ID . '<br />';
                $html .= '&bull; Response ID: ' . $r['pid'] . '<br />';
                $html .= '&bull; Date Started: ' . get_field('started', $r['pid']) . '<br />';
                $html .= '&bull; Question Index: ' . $qid . '<br /><br />';
                $html .= '&bull; True Likes: ' . $r['true_likes'] . '<br />';
                $html .= '&bull; Modified Likes: ' . $r['likes'] . '<br />';
                $html .= '&bull; All Answered: ' . (($r['total_answers'] == $total_questions) ? 'Yes' : 'No (+0)') . '<br />';
                $html .= '&bull; Total Score: ' . $r['score'];
                $html .= '<br />&bull; [<a target="_blank" href="' . get_edit_post_link($r['pid']) . '">Edit Submission</a>]<br />';
                $html .= '</div>';
            }
            
            $html .= '</div>
                        </div>
                    </div>';
            
            // Flag confirmation
            $html .= '<div class="thought-flag-confirm-container text-center hidden">
                <div class="thought-flag-confirm-container-inner p-2 pt-4 pb-4 relative">
                    <p class="mb-3"><em>&quot;' . $answer . '&quot;</em></p>
                    <p class="mb-3">' . $diy_flag_confirm . '</p>
                    <p class="mb-3"><button class="icon thought-flag thin button red round small" data-nonce="' . wp_create_nonce('thoughtFlag') . '" data-pid="' . $r['pid'] . '" data-row="' . $qid . '" data-thought-id="#thought-' . $r['pid'] . '-' . $qid . '">Yes, this comment is inappropriate</button></p>
                    <button class="cancel-flag-thought button blue thin round small">Nevermind</button>
                </div>
            </div>';
            
            $html .= '</div></li>';
            
            if ($args['single_embed'] == 1 && $q_counter >= 0) {
                break;
            }
            $q_counter++;
        }
        
        $html .= '</ol>';
        
        // Carousel navigation
        if ($args['carousel']) {
            $html .= '</div>';
            $html .= '<div class="glide__arrows" data-glide-el="controls">';
            if ($show_next_previews) {
                $html .= '<button class="peek diy-carousel-nav fade-left glide__arrow glide__arrow--left" data-glide-dir="<"></button>';
                $html .= '<button class="peek diy-carousel-nav fade-right glide__arrow glide__arrow--right" data-glide-dir=">"></button>';
            }
            
            foreach ($activity_questions as $qid => $qval) {
                $html .= '<button class="diy-direct-slide d-none" data-index="' . $qid . '" data-glide-dir="=' . $qid . '">Go to slide #' . ($qid + 1) . '</button>';
            }
            
            $html .= '</div></div>';
        }
    }
    
         // Pagination
     $html .= '<div id="diy-load-more-container" class="text-center mt-4 pb-5">';
     
     // Always show Previous button if not on page 1
     if ($args['page'] > 1) {
         $html .= '<button class="button gray round-tl mr-3 diy-previous-page" data-show-page="' . ($args['page'] - 1) . '">Previous Page</button>';
     }
     
     // Show Next button only if there are actually more posts available
     if ($has_next_page) {
         $html .= '<button class="diy-load-more button teal round-br" data-show-page="' . ($args['page'] + 1) . '">Next Page</button>';
     }
     
     // Show "no more responses" message only if no responses and no next page
     if (count($responses) == 0 && !$has_next_page) {
         $html .= '<div class="wrap narrow crowdsource-page-label text-center text-orange mb-4 mt-4"><em>No more responses available.</em><hr class="mb-4 mt-2" style="border-color: #FA6767;" /></div>';
     }
     
     $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
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
 * Invalidate crowdsource cache for a specific activity
 */
function invalidate_diy_crowdsource_cache($activity_id) {
    // Since we're not using cache groups, we need to clear all DIY cache entries
    // This is a more aggressive approach but ensures cache invalidation works
    global $wpdb;
    
    // Get all cache keys that start with our DIY prefix
    $cache_keys = $wpdb->get_col($wpdb->prepare("
        SELECT option_name 
        FROM {$wpdb->options} 
        WHERE option_name LIKE %s
        AND option_name LIKE '_transient_%'
    ", 'diy_crowdsource_' . $activity_id . '_%'));
    
    foreach ($cache_keys as $cache_key) {
        $key = str_replace('_transient_', '', $cache_key);
        delete_transient($key);
    }
}

/**
 * Clear all DIY crowdsource caches
 */
function clear_all_diy_crowdsource_caches() {
    global $wpdb;
    
    // More efficient approach: directly clear all DIY transients
    $cache_keys = $wpdb->get_col("
        SELECT option_name 
        FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_diy_crowdsource_%'
    ");
    
    $cleared_count = 0;
    foreach ($cache_keys as $cache_key) {
        $key = str_replace('_transient_', '', $cache_key);
        if (delete_transient($key)) {
            $cleared_count++;
        }
    }
    
    // Also clear any timeout entries
    $timeout_keys = $wpdb->get_col("
        SELECT option_name 
        FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_timeout_diy_crowdsource_%'
    ");
    
    foreach ($timeout_keys as $timeout_key) {
        $key = str_replace('_transient_timeout_', '', $timeout_key);
        delete_option('_transient_timeout_' . $key);
    }
    
    return [
        'cleared_transients' => $cleared_count,
        'total_found' => count($cache_keys)
    ];
}

/**
 * Hook to invalidate cache when DIY responses are updated
 */
add_action('save_post_diy_responses', 'invalidate_diy_crowdsource_cache_on_save', 10, 2);
function invalidate_diy_crowdsource_cache_on_save($post_id, $post) {
    if ($post->post_status === 'publish') {
        $activity_id = get_field('activity_id', $post_id);
        if ($activity_id && is_object($activity_id)) {
            invalidate_diy_crowdsource_cache($activity_id->ID);
        }
    }
}

/**
 * Hook to invalidate cache when likes are added/removed
 */
add_action('wp_ajax_thoughtLike', 'invalidate_diy_crowdsource_cache_on_like', 1);
add_action('wp_ajax_nopriv_thoughtLike', 'invalidate_diy_crowdsource_cache_on_like', 1);
function invalidate_diy_crowdsource_cache_on_like() {
    // This will be called before the like is processed
    // We'll invalidate all caches to be safe
    clear_all_diy_crowdsource_caches();
}

/**
 * Warm cache for a specific activity (pre-generate first few pages)
 */
function warm_diy_crowdsource_cache($activity_id, $pages_to_warm = 3) {
    for ($page = 1; $page <= $pages_to_warm; $page++) {
        // Simulate the AJAX request to generate cache
        $args = [
            'question' => null,
            'current' => null,
            'activity_id' => $activity_id,
            'carousel' => null,
            'page' => $page,
            'embedded' => 0,
            'single_embed' => 0
        ];
        
        // Create a mock request to trigger cache generation
        $_POST['data'] = http_build_query($args);
        
        // Temporarily capture output
        ob_start();
        getDiyCrowdsource();
        ob_end_clean();
    }
}

/**
 * Warm cache for all activities (can be called via WP-CLI or admin)
 */
function warm_all_diy_crowdsource_caches($pages_to_warm = 3) {
    global $wpdb;
    
    $activity_ids = $wpdb->get_col("
        SELECT DISTINCT pm.meta_value 
        FROM {$wpdb->postmeta} pm 
        WHERE pm.meta_key = 'activity_id' 
        AND pm.meta_value != ''
    ");
    
    foreach ($activity_ids as $activity_id) {
        $clean_activity_id = str_replace(['"', "'"], '', $activity_id);
        if (is_numeric($clean_activity_id)) {
            warm_diy_crowdsource_cache($clean_activity_id, $pages_to_warm);
        }
    }
}

/**
 * Admin function to manually clear and warm caches
 */
add_action('wp_ajax_clear_diy_caches', 'admin_clear_diy_caches');
function admin_clear_diy_caches() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    clear_all_diy_crowdsource_caches();
    warm_all_diy_crowdsource_caches(2); // Warm first 2 pages
    
    wp_send_json_success(['message' => 'Caches cleared and warmed successfully']);
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

/**
 * Add admin menu for cache debugging
 */
add_action('admin_menu', 'add_diy_cache_debug_menu');
function add_diy_cache_debug_menu() {
    add_submenu_page(
        'tools.php',
        'DIY Cache Debug',
        'DIY Cache Debug',
        'manage_options',
        'diy-cache-debug',
        'diy_cache_debug_page'
    );
}

/**
 * Admin page for cache debugging
 */
function diy_cache_debug_page() {
    global $wpdb;
    
    // Handle form submissions
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'debug_cache':
                $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : null;
                $debug_info = debug_diy_crowdsource_cache($activity_id);
                break;
                
            case 'clear_cache':
                $clear_results = clear_all_diy_crowdsource_caches();
                echo '<div class="notice notice-success"><p>All DIY caches cleared! Cleared ' . $clear_results['cleared_transients'] . ' transients out of ' . $clear_results['total_found'] . ' found.</p></div>';
                break;
                
            case 'run_sql_queries':
                $queries = get_diy_cache_sql_queries();
                $query_results = [];
                foreach ($queries as $query_name => $query) {
                    $query_results[$query_name] = $wpdb->get_results($query);
                }
                break;
        }
    }
    
    ?>
    <div class="wrap">
        <h1>DIY Cache Debug</h1>
        
        <div class="card">
            <h2>Cache Status Check</h2>
            <form method="post">
                <input type="hidden" name="action" value="debug_cache">
                <p>
                    <label>Activity ID (optional):</label>
                    <input type="number" name="activity_id" value="<?php echo isset($_POST['activity_id']) ? esc_attr($_POST['activity_id']) : ''; ?>">
                </p>
                <p><input type="submit" class="button button-primary" value="Check Cache Status"></p>
            </form>
            
            <?php if (isset($debug_info)): ?>
                <h3>Cache Debug Results:</h3>
                <pre><?php echo esc_html(print_r($debug_info, true)); ?></pre>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Cache Management</h2>
            <form method="post" style="display: inline;">
                <input type="hidden" name="action" value="clear_cache">
                <p><input type="submit" class="button button-secondary" value="Clear All DIY Caches" onclick="return confirm('Are you sure?')"></p>
            </form>
        </div>
        
        <div class="card">
            <h2>SQL Queries for Manual Database Check</h2>
            <p>Run these queries in your database to check for cache entries:</p>
            
            <h3>1. Check for all transients:</h3>
            <pre>SELECT option_name, LENGTH(option_value) as value_size, autoload 
FROM <?php echo $wpdb->options; ?> 
WHERE option_name LIKE '_transient_%' 
OR option_name LIKE '_site_transient_%'
ORDER BY option_name
LIMIT 50;</pre>
            
            <h3>2. Check for DIY-specific entries:</h3>
            <pre>SELECT option_name, LENGTH(option_value) as value_size, autoload 
FROM <?php echo $wpdb->options; ?> 
WHERE option_name LIKE '%diy%' 
OR option_name LIKE '%crowdsource%'
ORDER BY option_name;</pre>
            
            <h3>3. Check for large cache entries:</h3>
            <pre>SELECT option_name, LENGTH(option_value) as value_size, autoload 
FROM <?php echo $wpdb->options; ?> 
WHERE (option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%')
AND LENGTH(option_value) > 1000
ORDER BY LENGTH(option_value) DESC
LIMIT 20;</pre>
            
            <h3>4. Check recent cache entries:</h3>
            <pre>SELECT option_name, LENGTH(option_value) as value_size, autoload 
FROM <?php echo $wpdb->options; ?> 
WHERE option_name LIKE '_transient_%' 
OR option_name LIKE '_site_transient_%'
ORDER BY option_id DESC
LIMIT 20;</pre>
            
            <form method="post">
                <input type="hidden" name="action" value="run_sql_queries">
                <p><input type="submit" class="button button-secondary" value="Run SQL Queries"></p>
            </form>
            
            <?php if (isset($query_results)): ?>
                <h3>SQL Query Results:</h3>
                <?php foreach ($query_results as $query_name => $results): ?>
                    <h4><?php echo esc_html($query_name); ?>:</h4>
                    <pre><?php echo esc_html(print_r($results, true)); ?></pre>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Common Cache Issues & Solutions</h2>
            <ul>
                <li><strong>No object cache configured:</strong> WordPress falls back to database storage in wp_options table</li>
                <li><strong>Redis/Memcached not working:</strong> Check server configuration and connection</li>
                <li><strong>Cache keys too long:</strong> Some systems have key length limits</li>
                <li><strong>Memory limits:</strong> Large cache entries might be rejected</li>
                <li><strong>Permissions:</strong> Cache directory/file permissions issues</li>
            </ul>
        </div>
    </div>
    <?php
}