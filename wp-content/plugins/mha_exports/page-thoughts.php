<?php

// Plugins
require_once __DIR__ . '/vendor/autoload.php';
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
										
        <?php $snonce = wp_create_nonce('mhathoughtexport'); ?>
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
    <form action="#" id="nonaggregate-data-export" method="POST">
    <fieldset>

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
	
	// Submission is good, proceed

    /**
     * Aggregate Data For Users
     */
    
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
                    //'Time Spent on Thoughts',
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

?>