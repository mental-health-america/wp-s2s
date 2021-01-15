<?php

// Plugins
require_once __DIR__ . '/vendor/autoload.php';
use League\Csv\CharsetConverter;
use League\Csv\Writer;

/** 
 * Init Scripts
 */
add_action('init', 'mhaThoughtScripts');
function mhaThoughtScripts() {
	wp_enqueue_script( 'process_mhaThoughts', plugin_dir_url(__FILE__) . 'mha_export.js', array('jquery'), time(), true );
	wp_enqueue_style( 'process_mhaThoughts', plugin_dir_url(__FILE__) . 'mha_export.css', array(), time() );
	wp_localize_script('process_mhaThoughts', 'do_mhaThoughts', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

// List Page
function mhathoughtexport(){
?>

<div class="wrap">

    <h1>UCI Data Exports</h1>		

    <h2>Aggregate Data</h2>
    <div id="aggregate-error"></div>
    <form action="#" id="aggregate-data-export" method="POST">
    <fieldset>
										
        <p>
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mhathoughtexport'); ?>" />
            <button class="button button-primary" id="submit-aggregate-data-export">
                Export
            </button>
        </p>
        
        <div id="aggregate-progress" style="display: none;">
            <div class="bar-wrapper"><div class="bar"></div></div>            
            <strong class="label"><span class="label-number">0</span>%</strong>
        </div>
        <p id="aggregate-download" style="display: none;"></p>

    </fieldset> 
    </form>

    <hr />

    <h2>Non-Aggregate Data</h2>
    <div id="nonaggregate-error"></div>
    <form action="#" id="nonaggregate-data-export" method="POST">
    <fieldset>

        <p>
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mhathoughtexport'); ?>" />
            <button class="button button-primary" id="submit-nonaggregate-data-export">
                Export
            </button>
        </p>
        
        <div id="nonaggregate-progress" style="display: none;">
            <div class="bar-wrapper"><div class="bar"></div></div>            
            <strong class="label"><span class="label-number">0</span>%</strong>
        </div>
        <p id="nonaggregate-download" style="display: none;"></p>

    </fieldset> 
    </form>

</div>	
<?php } 


/**
 * Aggregate Data to CSV
 */
add_action( 'wp_ajax_mha_aggregate_data_export', 'mha_aggregate_data_export' );
function mha_aggregate_data_export(){
        
	// General variables
    $result = array();
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);  
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'mhathoughtexport');
    $paged = intval($data['paged']);
	
    // General Vars
    global $wpdb;
    $csv_data = [];
    $user_list = [];
    $user_exclude = [];
    $ipiden_exclude = [];
    $result = [];
    $i = 0;
    
    /*
    if($data['exclude']){
        $more_users = explode(',',$data['exclude']);
        foreach($more_users as $user){
            $user_exclude[] = $user;
        }
    }
    */
    
    // Get unique users and identifiers    
    $args = array(
        "post_type" => 'thought',
        "post_status" => array('publish', 'draft'),
        "posts_per_page" => 200,
        'paged' => $paged
    );
    $loop = new WP_Query($args);
    $max_pages = $loop->max_num_pages;
    
    $result['paged'] = $paged;
    $result['max'] = $max_pages;
    $result['percent'] = round( ( ($paged / $max_pages) * 100 ), 2 );
    if($paged >= $max_pages){
        $result['next_page'] = '' ;
    } else {
        $result['next_page'] = $paged + 1;
    }

    while($loop->have_posts()) : $loop->the_post();

        $author_id = get_post_field( 'post_author', get_the_ID() );
        $ipiden = get_field('ipiden');

        if(!in_array($author_id, $user_exclude)){
            $user_list[$i]['id'] = $author_id;
            $user_list[$i]['ipiden'] = $ipiden;
            if($author_id != 4) {
                $user_exclude[] = $author_id;
            }
            $ipiden_exclude[] = $ipiden;
            $i++;
        }

        // Handle anonymous users
        if($author_id == 4 && !in_array($ipiden, $ipiden_exclude)){
            $user_list[$i]['id'] = $author_id;
            $user_list[$i]['ipiden'] = $ipiden;
        }
        
    endwhile;
    wp_reset_query();

    $user_list = array_unique($user_list, SORT_REGULAR);

    $i = 0;
    foreach($user_list as $user){
        
        // General Vars   
        $author_id = $user['id'];
        $ipiden = $user['ipiden'];
        $user_post_ids = [];

        $args = array(
            "author" => $author_id,
            //"author__not_in" => $user_exclude,
            "post_type" => 'thought',
            "post_status" => array('publish', 'draft'),
            "posts_per_page" => -1,
            "meta_query" => array(
                array(
                    "key" => 'ipiden',
                    "value" => $ipiden
                )
            )
        );

        /**
         * Participant Identifier
         */ 
        $csv_data[$i]['Participant Identifier'] = $ipiden.'_'.$author_id;

        /**
         * Thoughts
         */
        $thoughts = new WP_Query($args);     
        $total_responses = 0;       
        //$times = [];
        while($thoughts->have_posts()) : $thoughts->the_post();
            $user_post_ids[] = get_the_ID();
            $responses = get_field('responses');
            $last_key = array_key_last( $responses );
            $total_responses = $total_responses + count($responses);                
            $times[] = (strtotime($responses[$last_key]['submitted']) - strtotime($responses[0]['submitted']) );
        endwhile;

        /**
         * Time on thoughts
         */
        /*
        $times = array_filter($times);
        $average_time = 'N/A';
        if(count($times)) {
            $average_time = array_sum($times)/count($times);
        }
        $csv_data[$i]['Time Spent on Thoughts'] = gmdate("H:i:s", $average_time);
        */


        /**
         * Number of Thoughts Started
         */
        $csv_data[$i]['Number of Thoughts Started'] = $thoughts->post_count;

        /**
         * Number of Responses
         */
        $csv_data[$i]['Number of Responses Answered'] = $total_responses;

        // Logged in user data
        if($author_id != 4){
            $total_logins = $wpdb->get_var( 'SELECT COUNT(*) FROM '.$wpdb->prefix.'fa_user_logins WHERE user_id = '.$author_id.' AND login_status = "login"');
            $first_login = $wpdb->get_var( 'SELECT time_login FROM '.$wpdb->prefix.'fa_user_logins WHERE user_id = '.$author_id.' AND login_status = "login" ORDER BY time_login ASC LIMIT 1');
            $last_login = $wpdb->get_var( 'SELECT time_login FROM '.$wpdb->prefix.'fa_user_logins WHERE user_id = '.$author_id.' AND login_status = "login" ORDER BY time_login DESC LIMIT 1');
            $duration = $wpdb->get_results( 'SELECT time_login, time_last_seen FROM '.$wpdb->prefix.'fa_user_logins WHERE user_id = '.$author_id.' AND login_status = "login" ORDER BY time_login ASC LIMIT 1');

            /**
             * Login Data
             */
            $csv_data[$i]['Number of Logins'] = $total_logins;
            $csv_data[$i]['Day of first login'] = $first_login;
            $csv_data[$i]['Day of most recent login'] = $last_login;
            
            $total_time = '';
            foreach($duration as $d){
                $total_time = $total_time + (strtotime($d->time_last_seen) - strtotime($d->time_login));
            }
            $csv_data[$i]['Time on site'] = gmdate("H:i:s", $total_time);

        } else {
            $csv_data[$i]['Number of Logins'] = 'N/A';
            $csv_data[$i]['Day of first login'] = 'N/A';
            $csv_data[$i]['Day of most recent login'] = 'N/A';
            $csv_data[$i]['Time on site'] = 'N/A';
        }

        /**
         * Number of "I relates"
         */
        $total_likes = 0;
        foreach($user_post_ids as $post_id){
            $total_likes = $total_likes + $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_likes WHERE pid = '.$post_id.' AND unliked = 0');
        }
        $csv_data[$i]['Number of "I relates" received'] = $total_likes;

        /**
         * Number of "flags"
         */
        $total_flags = 0;
        foreach($user_post_ids as $post_id){
            $total_flags = $total_flags + $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_flags WHERE pid = '.$post_id.' AND status = 0');
        }
        $csv_data[$i]['Number of "flags" received'] = $total_flags;
    
        // Loop counter
        $i++;

    }

    /**
     * Write Data
     */
    try {

        // Create CSV
        if($data['filename']){
            $filename = $data['filename'];
            $writer = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'a+');
        } else {
            $filename = 'aggregate-export-'.date('U').'.csv';
            $writer = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'w+');
        }

        $result['filename'] = $filename;
        if($paged >= $max_pages){
            // Final page
            $result['download'] = plugin_dir_url(__FILE__).'tmp/'.$filename;
        }
        
        // Headers only on page 1
        if($paged == 1){
            $writer->insertOne(
                [
                    "Participant Identifier",
                    'Number of Thoughts Started',
                    'Number of Responses Answered',
                    'Number of Logins',
                    'Day of first login',
                    'Day of most recent login',
                    'Time on site',
                    'Number of "I relates" received',
                    'Number of "flags" received',
                ]
            );
        }    
        $writer->insertAll(new ArrayIterator($csv_data));

        // User Exclusions for next page
        $user_exclude = array_unique($user_exclude);
        $result['exclude'] = $user_exclude;

    } catch (CannotInsertRecord $e) {

        $result['error'] = $e->getRecords();

    }
    

    echo json_encode($result);

    exit();
}




/**
 * Non-Aggregate Data to CSV
 */
add_action( 'wp_ajax_mha_nonaggregate_data_export', 'mha_nonaggregate_data_export' );
function mha_nonaggregate_data_export(){
        
	// General variables
    $result = array();
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);  
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'mhathoughtexport');
    $paged = intval($data['paged']);
	
    // General Vars
    global $wpdb;
    $csv_header = [];
    $csv_data = [];
    $user_list = [];
    $result = [];
    $i = 0;    
    
    // Get unique users and identifiers    
    $args = array(
        "post_type" => 'thought',
        "order" => 'DESC',
        "orderby" => 'date',
        "post_status" => array('publish', 'draft'),
        "posts_per_page" => 10,
        'paged' => $paged
    );
    $loop = new WP_Query($args);
    $max_pages = $loop->max_num_pages;

    $result['paged'] = $paged;
    $result['max'] = $max_pages;
    $result['percent'] = round( ( ($paged / $max_pages) * 100 ), 2 );
    if($paged >= $max_pages){
        $result['next_page'] = '' ;
    } else {
        $result['next_page'] = $paged + 1;
    }

    while($loop->have_posts()) : $loop->the_post();

        $author_id = get_post_field( 'post_author', get_the_ID() );
        $ipiden = get_field('ipiden');
        $post_id = get_the_ID();
        $activity_id = get_field('activity', $post_id);
        
        // Set Up Headers    
        $response_data = [];
        $response_data['Participant Identifier'] = '';
        $response_data['Total Logins'] = '';
        $response_data['Last Login'] = '';
        $response_data['Total Time Spent On Site'] = '';
        $response_data['Total Time Spent On This Thought'] = '';
        $response_data['Initial Thought'] = '';
        $response_data['Initial Thought - Admin'] = '';
        $response_data['Initial Thought - User'] = '';
        $response_data['Initial Thought - Time'] = '';
        $response_data['Initial Thought - Relates'] = '';
        $response_data['Initial Thought - Flags'] = '';
        
        
        // Get Question Headers
        $activity_args = array(
            "post_type" => 'thought_activity',
            "post_status" => 'publish',
            "posts_per_page" => -1
        );
        $activity_loop = new WP_Query($activity_args);
        while($activity_loop->have_posts()) : $activity_loop->the_post();
            $paths = get_field('paths');
            //pre($paths);
            foreach($paths as $path_key => $path_val){
                foreach($path_val['questions'] as $k => $v){   
                    //if($k > 0){             
                        $response_data['Path '.($path_key + 1).' - Question '.($k + 1)] = '';                    
                        $response_data['Path '.($path_key + 1).' - Question '.($k + 1).' - Time'] = '';    
                        $response_data['Path '.($path_key + 1).' - Question '.($k + 1).' - Relates'] = '';                    
                        $response_data['Path '.($path_key + 1).' - Question '.($k + 1).' - Flags'] = '';                    
                    //}
                }
            }
        endwhile;
        wp_reset_query();

        /**
         * Participant Identifier
         */ 
        $response_data['Participant Identifier'] = $ipiden.'_'.$author_id;

        /**
         * Thoughts
         */
        $times = [];
        $responses = get_field('responses', $post_id);
        $last_key = array_key_last( $responses );              
        $started_thought = get_field('started', $post_id);              
        $times[] = (strtotime($responses[$last_key]['submitted']) - strtotime($started_thought) );
        
        // Logged in user data
        if($author_id != 4){
            $total_logins = $wpdb->get_var( 'SELECT COUNT(*) FROM '.$wpdb->prefix.'fa_user_logins WHERE user_id = '.$author_id.' AND login_status = "login"');
            $last_login = $wpdb->get_var( 'SELECT time_login FROM '.$wpdb->prefix.'fa_user_logins WHERE user_id = '.$author_id.' AND login_status = "login" ORDER BY time_login DESC LIMIT 1');
            $duration = $wpdb->get_results( 'SELECT time_login, time_last_seen FROM '.$wpdb->prefix.'fa_user_logins WHERE user_id = '.$author_id.' AND login_status = "login" ORDER BY time_login ASC LIMIT 1');

            /**
             * Login Data
             */
            $response_data['Total Logins'] = $total_logins;
            $response_data['Last Login'] = $last_login;
            
            $total_time = '';
            foreach($duration as $d){
                $total_time = $total_time + (strtotime($d->time_last_seen) - strtotime($d->time_login));
            }
            $response_data['Total Time Spent On Site'] = gmdate("H:i:s", $total_time);

        } else {
            $response_data['Total Logins'] = 'N/A';
            $response_data['Last Login'] = 'N/A';
            $response_data['Total Time Spent On Site'] = 'N/A';
        }
        
        /**
         * Time on this thought
         */
        $times = array_filter($times);
        $average_time = 'N/A';
        if(count($times)) {
            $average_time = array_sum($times)/count($times);
        }
        $response_data['Total Time Spent On This Thought'] = gmdate("H:i:s", $average_time);

        /**
         * Questions
         */
        $initial_thought_admin = '';
        $initial_thought_user = '';
        $other_post_id = '';
        if(is_numeric($responses[0]['admin_pre_seeded_thought'])){
            $initial_thought = get_field('pre_generated_responses', $activity_id);
            $initial_thought_admin = $initial_thought[$responses[0]['admin_pre_seeded_thought']]['response'];
            $other_post_id = $responses[0]['admin_pre_seeded_thought'];
        } else if(is_numeric($responses[0]['user_pre_seeded_thought'])){
            $initial_thought = get_field('responses', $responses[0]['user_pre_seeded_thought']);
            $initial_thought_user = $initial_thought[0]['response'];
            $other_post_id = $responses[0]['user_pre_seeded_thought'];
        }

        /**
         * Initial Thought
         */
        $response_data['Initial Thought'] = $responses[0]['response'];
        $response_data['Initial Thought - Admin'] = $initial_thought_admin;
        $response_data['Initial Thought - User'] = $initial_thought_user;
        $response_data['Initial Thought - Time'] = $responses[0]['submitted'];

        /**
         * Follow Up Thoughts
         */
        foreach($responses as $resp){

            // Skip Initial Thought
            if($resp['question'] > 0){

                $response_data['Path '.($resp['path'] + 1).' - Question '.($resp['question'])] = $resp['response'];
                $response_data['Path '.($resp['path'] + 1).' - Question '.($resp['question']).' - Time'] = $resp['submitted'];

                // Likes
                $total_likes = $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_likes WHERE pid = '.$post_id.' AND row = '.$resp['question'].' AND unliked = 0');
                if(!$total_likes){
                    $total_likes = 0;
                }
                $response_data['Path '.($resp['path'] + 1).' - Question '.($resp['question']).' - Relates'] = $total_likes;

                // Flags
                $total_flags = $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_flags WHERE pid = '.$post_id.' AND row = '.$resp['question']);
                if(!$total_flags){
                    $total_flags = 0;
                }
                $response_data['Path '.($resp['path'] + 1).' - Question '.$resp['question'].' - Flags'] = $total_flags;

            } else {

                // Likes
                if($response_data['Initial Thought'] != ''){             
                    $total_likes = $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_likes WHERE pid = '.$post_id.' AND row = 0');
                    if(!$total_likes){
                        $total_likes = 0;
                    }                     
                    $response_data['Initial Thought - Relates'] = $total_likes;
                } else {                
                    $total_likes = $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_likes WHERE pid = '.$other_post_id.' AND row = 0');
                    if(!$total_likes){
                        $total_likes = 0;
                    }            
                    $response_data['Initial Thought - Relates'] = $total_likes;
                }

                // Flags
                if($response_data['Initial Thought'] != ''){           
                    $total_flags = $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_flags WHERE pid = '.$post_id.' AND row = 0');
                    if(!$total_flags){
                        $total_flags = 0;
                    }                       
                    $response_data['Initial Thought - Flags'] = $total_flags;
                } else {                
                    $total_flags = $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_flags WHERE pid = '.$other_post_id.' AND row = 0');
                    if(!$total_flags){
                        $total_flags = 0;
                    }            
                    $response_data['Initial Thought - Flags'] = $total_flags;
                }
            }

        }

        /**
         * User Likes During this Thought
         */
        $like_query = 'SELECT date, pid, row FROM thoughts_likes WHERE (uid = '.$author_id.' AND ipiden = "'.$ipiden.'" AND ref_pid = "'.$post_id.'" AND unliked = 0) OR (uid = '.$author_id.' AND ipiden = "'.$ipiden.'" AND ref_pid = 0 AND unliked = 0) ORDER BY date DESC';
        $user_likes = $wpdb->get_results( $like_query );
        $user_like_counter = 1;
        foreach($user_likes as $like){
            $pid = $like->pid;
            $row = $like->row;
            $liked_response = get_field('responses', $pid);
            $thought_text = $liked_response[$row]['response'];

            // Final likes
            $like_time = strtotime($like->date);
            if($like_time <= strtotime($responses[$last_key]['submitted']) && strtotime($started_thought) < $like_time){
                $response_data['Related Thought '.$user_like_counter] = $thought_text;
                $response_data['Related Thought '.$user_like_counter.' - Time'] = $like->date;
                $user_like_counter++;
            }
        }

        /**
         * User Flags During this Thought
         */
        $flag_query = 'SELECT date, pid, row FROM thoughts_flags WHERE (uid = '.$author_id.' AND ipiden = "'.$ipiden.'" AND ref_pid = "'.$post_id.'") OR (uid = '.$author_id.' AND ipiden = "'.$ipiden.'" AND ref_pid = 0) ORDER BY date DESC';
        $user_flags = $wpdb->get_results( $flag_query );
        $user_flag_counter = 1;
        foreach($user_flags as $flag){
            $pid = $flag->pid;
            $row = $flag->row;
            $flagged_response = get_field('responses', $pid);
            $thought_text = $flagged_response[$row]['response'];

            // Final flags
            $flag_time = strtotime($flag->date);
            if($flag_time <= strtotime($responses[$last_key]['submitted']) && strtotime($started_thought) < $like_time){
                $response_data['Flagged Thought '.$user_flag_counter] = $thought_text;
                $response_data['Flagged Thought '.$user_flag_counter.' - Time'] = $flag->date;
                $user_flag_counter++;
            }
        }


        // Wrap it up!
        $csv_data[] = $response_data;
        
    endwhile;
    wp_reset_query();
    
    // Debugging
    /*pre($csv_data);
    die();*/

    /**
     * Write Data
     */
    try {

        // Create CSV
        /*
        $encoder = (new CharsetConverter())
            ->inputEncoding('utf-8')
            ->outputEncoding('utf-8-bom')
        ;
        */
        if($data['filename']){
            // Update existing file
            $filename = $data['filename'];
            $writer = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'a+');
        } else {
            // Create file
            $filename = 'nonaggregate-export-'.date('U').'.csv';
            $writer = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'w+');
        }
        //$writer->addFormatter($encoder);
        
        $result['filename'] = $filename;
        if($paged >= $max_pages){
            // Final page
            $result['download'] = plugin_dir_url(__FILE__).'tmp/'.$filename;
        }
        
        // Headers only on page 1
        if($paged == 1){
            $csv_headers = [];
            foreach($csv_data[0] as $k => $v){
                $csv_headers[] = $k;
            }
            $writer->insertOne($csv_headers);
        }    
        $writer->insertAll(new ArrayIterator($csv_data));


    } catch (CannotInsertRecord $e) {

        $result['error'] = $e->getRecords();

    }

    echo json_encode($result);
    exit();
}

?>