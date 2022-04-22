<?php


// Plugins
require_once __DIR__ . '/vendor/autoload.php';
use League\Csv\CharsetConverter;
use League\Csv\Writer;
use League\Csv\Reader;

function char_fix( $input ){
    $bad_chars  = array('’');
    $good_chars = array('\'');
    $output = str_replace($bad_chars,$good_chars,$input);
    return $output;
}

/** 
 * Init Scripts
 */
add_action('init', 'mhaThoughtScripts');
function mhaThoughtScripts() {
    if(current_user_can('edit_posts')){
        wp_enqueue_script( 'process_mhaThoughts', plugin_dir_url(__FILE__) . 'mha_export.js', array('jquery'), time(), true );
        wp_enqueue_style( 'process_mhaacfeui', '/wp-content/plugins/acf-extended/assets/css/acfe-ui.min.css', array(), time() );
        wp_enqueue_style( 'process_mhaThoughts', plugin_dir_url(__FILE__) . 'mha_export.css', array(), time() );
        wp_localize_script('process_mhaThoughts', 'do_mhaThoughts', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }
}

// List Page
function mhathoughtexport(){
?>

<div id="poststuff" class="wrap">

    <h1>General Data Exports</h1>

    <form id="mha-all-screen-exports" action="#" method="POST">
        <div class="acf-columns-2">
        <div class="acf-column-1">
        
            <div id="screen-export-error"></div>
            <h2>Screen Exports</h2>
            <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="export_screen_start_date">Start Date</label></th>
                    <td>
                        <input type="text" name="export_screen_start_date" id="export_screen_start_date" value="<?php echo date('Y-m', strtotime('now - 1 month')); ?>-01" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="export_screen_end_date">End Date</label></th>
                    <td>
                        <input type="text" name="export_screen_end_date" id="export_screen_end_date" value="<?php echo date('Y-m-t', strtotime('now - 1 month')); ?>" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="export_screen_ref">Referrer URL Contains</label></th>
                    <td>
                        <input type="text" name="export_screen_ref" id="export_screen_ref" placeholder="mhanational.org" />
                    </td>
                </tr>
                <!--
                <tr>
                    <th scope="row"><label for="export_screen_spam">Exclude Suspected Spam</label></th>
                    <td>
                        <input type="checkbox" name="export_screen_spam" id="export_screen_spam" value="1" />
                    </td>
                </tr>
                -->
                <tr>
                    <th scope="row"><label for="export_screen_ref">Forms</label><br /></th>
                    <td>
                        <?php 
                            $gforms = GFAPI::get_forms(true, false, 'title'); 
                            echo '<select name="form_id">';
                            foreach($gforms as $gf){
                                if (strpos(strtolower($gf['title']), 'test') !== false || strpos(strtolower($gf['title']), 'survey') !== false || strpos(strtolower($gf['title']), 'quiz') !== false) {
                                    echo '<option name="gform[]" value="'.$gf['id'].'" />'.$gf['title'].'</option>';                                    
                                    // Multiple Checkbox. TODO: Simply too much data for great exports, try again another time?
                                    //echo '<label for="gform-'.$gf['id'].'"><input id="gform-'.$gf['id'].'" type="checkbox" name="gform[]" value="'.$gf['id'].'" />'.$gf['title'].'</label><br />';
                                }
                            }
                            echo '<option name="gform[]" value="all" />[All] Export All Forms</option>';
                            echo '</select>'
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">

                        <p>
                            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mhathoughtexport'); ?>" />
                            <input type="submit" class="button button-primary" id="export_screen_link"  value="Download Screening Data">
                        </p>
                        
                        <div id="screen-exports-progress" style="display: none; margin-top: 20px;">
                            <div class="bar-wrapper"><div class="bar"></div></div>            
                            <strong class="label"><span class="label-number">0</span>%</strong>
                        </div>
                        <ul id="screen-exports-download" style="display: none;"></ul>      
                        <br /><br />
                    </td>
                </tr>
            </tbody>
            </table>
        </div>
        </div>
    </form>
    <br />

    <form id="mha-user-exports" action="#" method="POST">
        <div class="acf-columns-2">
        <div class="acf-column-1">
        
            <div id="user-export-error"></div>
            <h2>User Export</h2>
                <tr>
                    <td colspan="2">

                        <p>
                            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mhathoughtexport'); ?>" />
                            <input type="submit" class="button button-primary" id="export_user_link"  value="Download Users">
                        </p>
                        
                        <div id="user-exports-progress" style="display: none; margin-top: 20px;">
                            <div class="bar-wrapper"><div class="bar"></div></div>            
                            <strong class="label"><span class="label-number">0</span>%</strong>
                        </div>
                        <ul id="user-exports-download" style="display: none;"></ul>      
                        <br /><br />
                    </td>
                </tr>
            </tbody>
            </table>
        </div>
        </div>
    </form>
    <br />

    <h1>UCI Data Exports</h1>		

    <form action="#" id="aggregate-data-export" method="POST">
        <div class="acf-columns-2">
        <div class="acf-column-1">
        
            <div id="aggregate-error"></div>
            <h2>Aggregate Data</h2>
            <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label id="manual_users" >User Selection</label> </th>
                    <td>
                        <input type="text" name="manual_users" id="manual_users" value="" placeholder="e.g. 1, 2, 3" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label id="aggregate_start_date" >After</label> </th>
                    <td>
                        <input type="text" name="start_date" id="aggregate_start_date" value="" placeholder="<?php echo date('Y-m'); ?>-01" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label id="aggregate_end_date" >Before</label> </th>
                    <td>
                        <input type="text" name="end_date" id="aggregate_end_date" value="" placeholder="<?php echo date('Y-m-t'); ?>" />
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mhathoughtexport'); ?>" />
                        <button class="button button-primary" id="submit-aggregate-data-export">
                            Download Aggregate Data
                        </button>
        
                        <div id="aggregate-progress" style="display: none; margin-top: 20px;">
                            <div class="bar-wrapper"><div class="bar"></div></div>            
                            <strong class="label"><span class="label-number">0</span>%</strong>
                        </div>
                        <p id="aggregate-download" style="display: none;"></p>                        
                    </td>
                </tr>
            </tbody>
            </table>

        </div>
        </div>
    </form>

    <form action="#" id="nonaggregate-data-export" method="POST">
        <div class="acf-columns-2">
        <div class="acf-column-1">
        
            <div id="nonaggregate-error"></div>
            <h2>Non-Aggregate Data</h2>
            <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label id="manual_users" >User Selection</label> 
                    </th>
                    <td>
                        <input type="text" name="manual_users" id="manual_users" value="" placeholder="e.g. 4, 12, 67" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label id="nonaggregate_start_date" >After</label> </th>
                    <td>
                        <input type="text" name="start_date" id="nonaggregate_start_date" value="" placeholder="<?php echo date('Y-m'); ?>-01" />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label id="nonaggregate_end_date" >Before</label> </th>
                    <td>
                        <input type="text" name="end_date" id="nonaggregate_end_date" value="" placeholder="<?php echo date('Y-m-t'); ?>" />
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mhathoughtexport'); ?>" />
                        <button class="button button-primary" id="submit-nonaggregate-data-export">
                            Download Non-Aggregate Data
                        </button>
        
                        <div id="nonaggregate-progress" style="display: none; margin-top: 20px;">
                            <div class="bar-wrapper"><div class="bar"></div></div>            
                            <strong class="label"><span class="label-number">0</span>%</strong>
                        </div>
                        <p id="nonaggregate-download" style="display: none;"></p>             
                    </td>
                </tr>
            </tbody>
            </table>

        </div>
        </div>
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
    $timezone = new DateTimeZone('America/New_York');
	
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
    
    $result['proceed'] = '1';

    // Get unique users and identifiers    
    $args = array(
        "post_type" => 'thought',
        "post_status" => array('publish', 'draft'),
        "posts_per_page" => 200,
        'paged' => $paged
    );
    
    // Start/End Date Query
    $start_date = '';
    $end_date = '';
    if($data['start_date'] != ''){
        $start_date = date('F jS, Y', strtotime($data['start_date']));
        $result['start_date'] = $start_date;
    }
    if($data['end_date'] != ''){
        $end_date = date('F jS, Y', strtotime($data['end_date']));
        $result['end_date'] = $end_date;
    }
    if($start_date != '' || $end_date != ''){
        $args['date_query'] = array(
            array(
                'inclusive' => true
            ),
        );
        if($start_date != ''){
            $args['date_query'][]['after'] = $start_date;
        }
        if($end_date != ''){
            $args['date_query'][]['before'] = $end_date;
        }
    }
    
    // Manual User Check
    $manual_users = sanitize_text_field($data['manual_users']);
    $result['manual_users'] = $manual_users;
    if($manual_users != ''){
        $manual_users = explode(',', $manual_users);
        $args['author__in'] = $manual_users;
    }

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
        if($author_id != 4){
            $csv_data[$i]['Participant Email'] = get_the_author_meta( 'email', $author_id );
        } else {
            $csv_data[$i]['Participant Email'] = '';
        }

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
            
            $temp_time = new DateTime($first_login);
            $temp_time->setTimezone($timezone);
            $csv_data[$i]['Day of first login'] = $temp_time->format("Y-m-d H:i:s");

            $temp_time = new DateTime($last_login);
            $temp_time->setTimezone($timezone);
            $csv_data[$i]['Day of most recent login'] = $temp_time->format("Y-m-d H:i:s");
            
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
        
        // Headers only on page 1
        if($paged == 1){
            $writer->insertOne(
                [
                    "Participant Identifier",
                    "Participant Email",
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
    
    if($paged >= $max_pages){
            
        /**
         * Remove duplicates
         */
        $uniques = array();
        $newRecords = [];
        $writerProcessed = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/processed-'.$filename, 'a+');
        $reader = Reader::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'r');
        $records = $reader->getRecords();            
        foreach ($records as $offset => $record) {
            if (isset($uniques[$record[0]])) {            
                continue; // Skip duplicates
            }                
            $uniques[$record[0]] = true; // Write uniques to array
            $newRecords[] = $record; // Add record to new CSV
        }            
        $writerProcessed->insertAll(new ArrayIterator($newRecords));

        $result['download'] = plugin_dir_url(__FILE__).'tmp/processed-'.$filename;
        
    }    

    echo json_encode($result);

    exit();
}


/** 
 * Aggregate Duplicate Remover
 */
function mha_aggregate_remove_duplicates($filename){

    $uniques = array();
    $newRecords = [];

    $writer = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/processed-'.$filename, 'a+');
    $reader = Reader::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'r');
    $records = $reader->getRecords();
    
    foreach ($records as $offset => $record) {

        if (isset($uniques[$record[0]])) {            
            continue; // Skip duplicates
        }
        
        $uniques[$record[0]] = true; // Write uniques to array
        $newRecords[] = $record; // Add record to new CSV
    }
    
    $writer->insertAll(new ArrayIterator($newRecords));

    return plugin_dir_url(__FILE__).'tmp/processed-'.$filename;

}

/**
 * Non-Aggregate Data to CSV
 */
add_action( 'wp_ajax_mha_nonaggregate_data_export', 'mha_nonaggregate_data_export' );
function mha_nonaggregate_data_export(){
        
	// General variables
    $result = array();
    $timezone = new DateTimeZone('America/New_York');
	
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
        "posts_per_page" => 50,
        'paged' => $paged
    );

    // Start/End Date Query
    $start_date = '';
    $end_date = '';
    if($data['start_date'] != ''){
        $start_date = date('F jS, Y', strtotime($data['start_date']));
        $result['start_date'] = $start_date;
    }
    if($data['end_date'] != ''){
        $end_date = date('F jS, Y', strtotime($data['end_date']));
        $result['end_date'] = $end_date;
    }
    if($start_date != '' || $end_date != ''){
        $args['date_query'] = array(
            array(
                'inclusive' => true
            ),
        );
        if($start_date != ''){
            $args['date_query'][]['after'] = $start_date;
        }
        if($end_date != ''){
            $args['date_query'][]['before'] = $end_date;
        }
    }

    
    // Manual User Check
    $manual_users = sanitize_text_field($data['manual_users']);
    $result['manual_users'] = $manual_users;
    if($manual_users != ''){
        $manual_users = explode(',', $manual_users);
        $args['author__in'] = $manual_users;
    }

    $loop = new WP_Query($args);

    if(!$loop->have_posts() || empty($loop)){
        $result['error'] = 'No data available for this query.';
        echo json_encode($result);
        exit();        
    }

    $max_pages = $loop->max_num_pages;

    $result['loop'] = $loop;
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
        $response_data['pid'] = get_the_ID();
        $response_data['Participant Identifier'] = '';
        $response_data['Participant Email'] = '';
        $response_data['Total Logins'] = '';
        $response_data['Last Login'] = '';
        $response_data['Total Time Spent On Site'] = '';
        $response_data['Total Time Spent On This Thought'] = '';
        $response_data['Abandoned Thought'] = get_field('abandoned', $post_id);
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
        if($author_id != 4){
            $response_data['Participant Email'] = get_the_author_meta( 'email', $author_id );
        } else {
            $response_data['Participant Email'] = '';
        }

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
            
            $temp_time = new DateTime($last_login);
            $temp_time->setTimezone($timezone);
            $response_data['Last Login'] = $temp_time->format("Y-m-d H:i:s");
            
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
        $response_data['Initial Thought'] = char_fix($responses[0]['response']);
        $response_data['Initial Thought - Admin'] = char_fix($initial_thought_admin);
        $response_data['Initial Thought - User'] = char_fix($initial_thought_user);
        
        $temp_time = new DateTime($responses[0]['submitted']);
        $temp_time->setTimezone($timezone);
        $response_data['Initial Thought - Time'] = $temp_time->format("Y-m-d H:i:s");

        /**
         * Follow Up Thoughts
         */
        foreach($responses as $resp){

            // Skip Initial Thought
            if($resp['question'] > 0){

                $response_data['Path '.($resp['path'] + 1).' - Question '.($resp['question'])] = $resp['response'];
                
                $temp_time = new DateTime($resp['submitted']);
                $temp_time->setTimezone($timezone);
                $response_data['Path '.($resp['path'] + 1).' - Question '.($resp['question']).' - Time'] = $temp_time->format("Y-m-d H:i:s");

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

        // Set up max total related thought columns for the first entry
        if($paged == 1){
            $like_total_counter = 1;
            $like_total_date = '';
            /*
            if($start_date && $end_date){
                $like_total_date = 'AND date between "'.date('Y-m-d', strtotime($data['start_date'])).'" and "'.date('Y-m-d', strtotime($data['end_date'])).'"';
            }
            else if($start_date && !$end_date){
                $like_total_date = 'AND date >  "'.date('Y-m-d', strtotime($data['start_date'])).'"';
            }
            else if(!$start_date && $end_date){
                $like_total_date = 'AND date <  "'.date('Y-m-d', strtotime($data['end_date'])).'"';
            }
            */
            $like_total_query = 'SELECT COUNT(ipiden) as total_count FROM thoughts_likes WHERE unliked = 0 '.$like_total_date.' GROUP BY ipiden, uid ORDER BY total_count DESC LIMIT 1';
            $like_total_response = $wpdb->get_results( $like_total_query );
            while ($like_total_counter <= $like_total_response[0]->total_count) {
                $response_data['Related Thought '.$like_total_counter] = '';
                $response_data['Related Thought '.$like_total_counter.' - Time'] = '';
                $like_total_counter++;
            }
        }

        // Get the rows with actual likes
        $like_query = 'SELECT date, pid, row FROM thoughts_likes WHERE (uid = '.$author_id.' AND ipiden = "'.$ipiden.'" AND ref_pid = "'.$post_id.'" AND unliked = 0) OR (uid = '.$author_id.' AND ipiden = "'.$ipiden.'" AND ref_pid = 0 AND unliked = 0) ORDER BY date ASC';
        $user_likes = $wpdb->get_results( $like_query );
        $user_like_counter = 1;
        foreach($user_likes as $like){
            $pid = $like->pid;
            $row = $like->row;
            $liked_response = get_field('responses', $pid);
            $thought_text = $liked_response[$row]['response'];

            // Other thought override
            if(trim($thought_text) == ''){
                if(is_numeric($liked_response[$row]['admin_pre_seeded_thought'])){
                    $activity_id = get_field('activity', $pid);
                    $admin_thoughts = get_field('pre_generated_responses', $activity_id);
                    $thought_text = $admin_thoughts[$liked_response[$row]['admin_pre_seeded_thought']]['response'];
                } else if(is_numeric($liked_response[$row]['user_pre_seeded_thought'])){
                    $user_thought = get_field('responses', $liked_response[$row]['user_pre_seeded_thought']);
                    $thought_text = $user_thought[$row]['response'];
                }
            }
            
            // Final likes
            $like_time = strtotime($like->date);
            if($like_time <= strtotime($responses[$last_key]['submitted']) && strtotime($started_thought) < $like_time){
                $response_data['Related Thought '.$user_like_counter] = $thought_text;

                $temp_time = new DateTime($like->date);
                $temp_time->setTimezone($timezone);
                $response_data['Related Thought '.$user_like_counter.' - Time'] = $temp_time->format("Y-m-d H:i:s");
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

                $temp_time = new DateTime($flag->date);
                $temp_time->setTimezone($timezone);
                $response_data['Flagged Thought '.$user_flag_counter.' - Time'] = $temp_time->format("Y-m-d H:i:s");
                $user_flag_counter++;
            }
        }


        // Wrap it up!
        $csv_data[] = $response_data;
        
    endwhile;
    wp_reset_query();
    
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

/**
 * User Exporter
 */
add_action( 'wp_ajax_mha_user_data_export', 'mha_user_data_export' );
function mha_user_data_export(){
        
	// General variables
    $result = array();
    $timezone = new DateTimeZone('America/New_York');
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);  
    $isAuthentic = wp_verify_nonce( $data['nonce'], 'mhathoughtexport');
    $paged = intval($data['paged']);

    // General Vars
    global $wpdb;
    $csv_header = [];
    $csv_data = [];
    $result = [];
    $per_page = 1000;
    $i = 0;    


    // Get the users
    $total_users = count_users();
    $headers = array( 'user_registered', 'display_name', 'user_email' );
    $users = get_users( 
        array( 
            'fields' => $headers
        ) 
    );    
    
    $max_pages = round( $total_users['total_users'] / $per_page );
    if($max_pages < 1){
        $max_pages = 1;
    }
    $result['paged'] = $paged;
    $result['max'] = $max_pages;
    $result['percent'] = round( ( ($paged / $max_pages) * 100 ), 2 );
    if($paged >= $max_pages){
        $result['next_page'] = '' ;
    } else {
        $result['next_page'] = $paged + 1;
    }

    $start = $per_page * $paged;
    $end = $per_page * ($paged + 1);
    if($end > $total_users['total_users']){
        $end = $total_users['total_users'];
    }
    
    foreach($users as $user){
        if($i >= $start){
            foreach($headers as $k){
                if($k == 'user_registered'){
                    $temp_time = new DateTime($user->$k);
                    $temp_time->setTimezone($timezone);
                    $csv_data[$i][$k] = $temp_time->format("Y-m-d H:i:s");
                } else {
                    $csv_data[$i][$k] = $user->$k;
                }
                    
            }
        }
        $i++;
        if($i >= $end){
            break;
        }
    }

    $result['i'] = $i;
    $result['start'] = $start;
    $result['end'] = $end;
    
    /**
     * Write Data
     */
    try {

        // Create CSV
        if($data['filename']){
            // Update existing file
            $filename = $data['filename'];
            $writer = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'a+');
        } else {
            // Create file
            $filename = 'mhauser-export-'.date('U').'.csv';
            $writer = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'w+');
        }
        //$writer->addFormatter($encoder);
        
        $result['filename'] = $filename;
        if($paged >= $max_pages){
            // Final page
            $result['download'] = plugin_dir_url(__FILE__).'tmp/'.$filename;
        }
        
        // Headers only on page 1
        if($paged == 0){
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


/** 
 * WPEngine Heartbeat Override
 * https://wordpress.org/support/topic/missing-dependencies-in-query-monitor-with-wp-auth-check-and-heatbeat-missing/
 */
add_filter( 'wpe_heartbeat_allowed_pages', function( $pages ) {
	global $pagenow;
	$pages[] =  $pagenow;
	return $pages;
});


/**
 * Export entries with WP CLI
 */
function mha_cli_screen_exporter(){

    // Dynamic Variables
    $form = 1;
    $start_date = '2021-05-01';
    $end_date = '2021-05-31';

    // Constructor Variables
    $gform = GFAPI::get_form( $form_id );
    $form_slug = sanitize_title_with_dashes($gform['title']);
    $filename = $form_slug.'--'.$start_date.'_'.$end_date.'--'.date('U').'.csv';
    $tmp_dir = plugin_dir_path(__FILE__).'tmp';

    // wp gf entry export ( wp gf entry list 5 ) test.csv --dir=~/sites/mhanationalstg/wp-content/plugins/mha_exports/tmp --format=csv --start_date=21-05-01 --end_date=21-05-31;
    // wp gf entry export ( wp gf entry list 1 ) test.csv --format=csv --start_date=2021-05-01 --end_date=2021-05-02;
    $cli_command = "wp gf entry export ( wp gf entry list $form ) $filename --dir=$tmp_dir --format=csv --start_date=$start_date --end_date=$end_date";

}