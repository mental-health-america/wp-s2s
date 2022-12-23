<?php

// Plugins
require_once __DIR__ . '/vendor/autoload.php';
use League\Csv\CharsetConverter;
use League\Csv\Writer;
use League\Csv\Reader;


// Enqueing Scripts
add_action('init', 'mhaDiyToolsExportScripts');
function mhaDiyToolsExportScripts() {
    wp_enqueue_script( 'process_diyToolsExport', plugin_dir_url(__FILE__) . 'diy_export.js', array('jquery'), time(), true );
    wp_localize_script('process_diyToolsExport', 'do_mhaDiyToolsExport', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

add_action( 'wp_ajax_mha_export_diy_tool_data', 'mha_export_diy_tool_data' );
function mha_export_diy_tool_data(){

	// General variables
    global $wpdb;
    $timezone = new DateTimeZone('America/New_York');
	
    // Prep our post data args
    if(isset($_POST['start']) && intval($_POST['start']) == 1 || empty($_POST) ){

        // For the first pass, set our defaults
        $defaults = array(
            'tool_id'                    => null,
            'nonce'                      => null,
            'diytool_export_start_date'  => date('Y-m', strtotime('now - 1 month')).'-01',
            'diytool_export_end_date'    => date('Y-m-t', strtotime('now - 1 month')),
            'page'                      => 1,
            'csv_headers'               => array(),
            'filename'                  => null,
            'total'                     => null,
            'max'                       => null,
            'percent'                   => null,
            'next_page'                 => null,
            'elapsed_start'             => null,
            'elapsed_end'               => null,
            'total_elapsed_time'        => null,
            'download'                  => null,
            'debug'                     => null
        );      

        parse_str( $_POST['data'], $data);
        $args = wp_parse_args( $data, $defaults );  
        
    } else {        

        // For loops, just use the data given
        $args = stripslashes_deep($_POST['data']);
        
    }

    // Begin CSV Headers
    $csv_headers = [
        0 => 'activity',
        1 => 'ipiden',
        2 => 'user_hidden',
        3 => 'crowdsource_hidden',
        4 => 'ref_code',
        5 => 'post_status',
    ];


    // Begin query
    $diy_res_args = array(
        "post_type" => 'diy_responses',
        "post_status" => array('draft','publish'),
        "posts_per_page" => 100,
        "order" => 'ASC',
        "orderby" => 'date',
        "paged" => $args['page'],
    );    
    
    $i = 0;
    $temp_array = [];
    $csv_data = [];

    $diy_res_loop = new WP_Query($diy_res_args);
    if($diy_res_loop->have_posts()):  
    while($diy_res_loop->have_posts()) : $diy_res_loop->the_post();
    
        $response_id = get_the_ID();
        $activity_id = get_field('activity_id')->ID;
        $activity_questions = get_field('questions', $activity_id);
        $activity_response = get_field('response');
        
        // Get headers on first page
        if( $args['page'] == 1 && $i == 0){
            foreach($activity_questions as $k => $v){
                $csv_headers[] = $v['question_label'].' - Response';
                $csv_headers[] = $v['question_label'].' - Date';
                $csv_headers[] = $v['question_label'].' - Updated';
                $csv_headers[] = $v['question_label'].' - Likes';
                $csv_headers[] = $v['question_label'].' - Flags';
            }
            $csv_headers[] = 'Total Likes';
            $csv_headers[] = 'Total Flags';
        }

        // Add data to temp array
        $csv_data[$i] = [
            'activity'                          => get_the_title($activity_id).' (#'.$activity_id.')',
            'ipiden'                            => get_field('ipiden'),
            'user_hidden'                       => get_field('hidden'),
            'crowdsource_hidden'                => get_field('crowdsource_hidden'),
            'ref_code'                          => get_field('ipiden'),
            'post_status'                       => get_post_status()
        ];

        // Put each question in first in case of blanks
        foreach($activity_questions as $k => $v){
            $csv_data[$i][$v['question_label'].' - Response'] = '';
            $csv_data[$i][$v['question_label'].' - Date']     = '';
            $csv_data[$i][$v['question_label'].' - Updated']  = '';
            $csv_data[$i][$v['question_label'].' - Likes']    = '';
            $csv_data[$i][$v['question_label'].' - Flags']  = '';
        }

        // Update question columns with actual responses
        $response_total_likes = 0;
        $response_total_flags = 0;
        foreach($activity_response as $ar){       
            $total_likes = $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_likes WHERE pid = '.$response_id.' AND row = '.$ar['id'].' AND unliked = 0');
            $total_flags = $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_flags WHERE pid = '.$response_id.' AND row = '.$ar['id'].' AND status = 0');

            $csv_data[$i][$activity_questions[ $ar['id'] ]['question_label'].' - Response'] = $ar['answer'];
            $csv_data[$i][$activity_questions[ $ar['id'] ]['question_label'].' - Date']     = $ar['date'];
            $csv_data[$i][$activity_questions[ $ar['id'] ]['question_label'].' - Updated']  = $ar['updated'];
            $csv_data[$i][$activity_questions[ $ar['id'] ]['question_label'].' - Likes']    = $total_likes ? $total_likes : "0";
            $csv_data[$i][$activity_questions[ $ar['id'] ]['question_label'].' - Flags']    = $total_flags ? $total_flags : "0";
            $response_total_likes = $response_total_likes + $total_likes;
            $response_total_flags = $response_total_flags + $total_flags;
        }
        $csv_data[$i]['Total Likes'] = $response_total_likes;
        $csv_data[$i]['Total Flags'] = $response_total_flags;

        $i++;
    endwhile;        
    endif;

    /**
     * Set next step variables
     */
    $args['max'] = $diy_res_loop->max_num_pages;
    $args['percent'] = round( ( ($args['page'] / $args['max']) * 100 ), 2 );
    if($args['page'] >= $args['max']){
        $args['next_page'] = '';
    } else {
        $args['next_page'] = $args['page'] + 1;
    }  

    /**
     * Elapsed Time
     */
    if(isset($args['elapsed_start'])){
        $args['elapsed_start'] = $args['elapsed_start'];
    } else {
        $args['elapsed_start'] = time();
    }
    
    if(count($csv_data) == 0){
        $args['download'] = '#';   
        $args['filename'] = '';
        $args['elapsed_end'] = time();
    }
    
    
    /**
     * Write CSV
     */
    try {

        $form_slug = sanitize_title (get_the_title($args['tool_id']) );
        $args['filename'] = $args['filename'] ? $args['filename'] : $form_slug.'--'.$args['diytool_export_start_date'].'_'.$args['diytool_export_end_date'].'--'.date('U').'.csv';
        $writer_type = $args['filename'] ? 'a+' : 'w+';
                
        if($args['page'] >= $args['max']){
            
            // Final page
            $args['download'] = WP_PLUGIN_URL.'/mha_exports/tmp/'.$args['filename'];   

            // Elapsed time
            $args['elapsed_end'] = time();
            $interval = $args['elapsed_end'] - $args['elapsed_start'];
            $args['total_elapsed_time'] = gmdate("H:i:s", abs($interval));
        }

        $writer = Writer::createFromPath(WP_PLUGIN_DIR.'/mha_exports/tmp/'.$args['filename'], $writer_type);        

        // Set the headers only on page 1        
        if($args['page'] == 1){

            $csv_headers = [];

            // Create header array
            foreach($csv_data[array_key_first($csv_data)] as $k => $v){
                $csv_headers[] = $k;                           
            }

            // Set order for later
            $args['csv_headers'] = array_values($csv_headers);
            $writer->insertOne($csv_headers);
        }    

        // Organize the data by the header values
        $csv_data_ordered = [];
        $header_flip = array_flip($args['csv_headers']);
        foreach($csv_data as $cd){
            $csv_data_ordered[] = sortArrayByArray($cd, $header_flip);
        }

        // Write the results to the CSV
        $writer->insertAll(new ArrayIterator($csv_data_ordered));
        $encoder = (new CharsetConverter())
            ->inputEncoding('utf-8')
            ->outputEncoding('iso-8859-15')
        ;
        
        $writer->addFormatter($encoder);


    } catch (CannotInsertRecord $e) {
        $args['error'] = $e->getRecords();
    }

    // Set the loops next page
    $args['page'] = $args['next_page'];
    
    if($args['debug'] == 1){
        unset($args['fields']);
        pre($args);
    } else {
        echo json_encode($args); 
        exit();
    }

}