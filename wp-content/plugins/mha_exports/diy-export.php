<?php

// Plugins
require_once __DIR__ . '/vendor/autoload.php';
use League\Csv\CharsetConverter;
use League\Csv\Writer;
use League\Csv\Reader;

// Enqueing Scripts
add_action('init', 'mhaDiyToolsExportScripts');
function mhaDiyToolsExportScripts() {
    if(current_user_can('edit_posts')){
        //wp_enqueue_script( 'process_diyToolsExport', plugin_dir_url(__FILE__) . 'diy_export.js', array('jquery'), 'v1.2', true );
        wp_enqueue_script( 'process_diyToolsExport', plugin_dir_url(__FILE__) . 'diy_export.js', array('jquery'), time(), true );
        wp_localize_script('process_diyToolsExport', 'do_mhaDiyToolsExport', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }
}

// Remove tooltip from question function
function removeMhaTooltip($input) {
    $pattern = '/\[mha_tooltip.*?\[\/mha_tooltip\]/s';
    $output = preg_replace($pattern, '', $input);    
    return $output;
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
            'form_id'                    => null,
            'nonce'                      => null,
            'diytool_export_start_date'  => null,
            'diytool_export_end_date'    => null,
            'page'                       => 1,
            'csv_headers'                => array(),
            'filename'                   => null,
            'total'                      => null,
            'max'                        => null,
            'percent'                    => null,
            'next_page'                  => null,
            'elapsed_start'              => null,
            'elapsed_end'                => null,
            'total_elapsed_time'         => null,
            'download'                   => null,
            'debug'                      => null,

            'all_forms'                 => null,
            'all_forms_ids'             => null,
        );      

        parse_str( $_POST['data'], $data);
        $args = wp_parse_args( $data, $defaults );  
        
    } else {        

        // For loops, just use the data given
        $args = stripslashes_deep($_POST['data']);
        
    }

    /**
     * Elapsed Time
     */
    if(isset($args['elapsed_start'])){
        $args['elapsed_start'] = $args['elapsed_start'];
    } else {
        $args['elapsed_start'] = time();
    }


    // Begin CSV Headers
    $csv_headers = [
        0 => 'activity',
        1 => 'ipiden',
        2 => 'username',
        3 => 'hidden_from_my_account',
        4 => 'hidden_from_crowdsource',
        5 => 'hidden_from_my_account',
        6 => 'start_page',
        7 => 'started_on_embed',
        8 => 'ref_code',
        9 => 'post_status',
        10 => 'post_id',
    ];

    // Begin query
    $diy_res_args = array(
        "post_type" => 'diy_responses',
        "post_status" => array('draft','publish'),
        "posts_per_page" => 100,
        "order" => 'ASC',
        "orderby" => 'date',
        "paged" => $args['page'],
        "meta_query"		=> array(
            array(
                'key'       => 'activity_id',
                'value'     => $args["form_id"],
                'compare'   => 'LIKE'
            )
        )
    );    

    // Date query
    if($args['diytool_export_start_date'] || $args['diytool_export_end_date']){
        if($args['diytool_export_start_date']) { 
            $diy_res_args['date_query']['after'] = date('F j, Y', strtotime($args['diytool_export_start_date']));
        }
        if($args['diytool_export_end_date']) { 
            $diy_res_args['date_query']['before'] = date('F j, Y', strtotime($args['diytool_export_end_date']));
        }
        $diy_res_args['date_query']['inclusive'] = true;
    }
    
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
        $author_id = get_post_field ('post_author', $response_id);
        $display_name = get_the_author_meta( 'display_name' , $author_id );
        
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
            'username'                          => get_the_author_meta( 'user_nicename', get_post_field ('post_author', $response_id) ),
            'hidden_from_my_account'            => get_field('hidden'),
            'hidden_from_crowdsource'           => get_field('crowdsource_hidden'),
            'viewed_crowdsource'                => get_field('user_viewed_crowdsource'),
            'start_page'                        => get_field('start_page') ? get_the_title( get_field('start_page') ).' (#'.get_field('start_page').')' : '',
            'started_on_embed'                  => get_field('start_page') ? 1 : 0,
            'start_page_url'                    => get_field('start_page_url'),
            'ref_code'                          => get_field('ref_code'),
            'post_status'                       => get_post_status(),
            'post_id'                           => $response_id,
            'tool_type'                         => get_field('tool_type')
        ];

        // Put each question in first in case of blanks
        foreach($activity_questions as $k => $v){
            if($v['question_type'] == 'html' || $v['question_type'] == 'breathe'){
                continue;
            }
            $label_key = $v['question_label'] != '' ? $v['question_label'] : removeMhaTooltip(strip_tags($v['question']));
            $csv_data[$i][$label_key.' - Response'] = '';
            $csv_data[$i][$label_key.' - Date']     = '';
            $csv_data[$i][$label_key.' - Updated']  = '';
            //if($v['tool_type'] == 'question_answer'){
                $csv_data[$i][$label_key.' - Likes']    = '';
                $csv_data[$i][$label_key.' - Flags']  = '';
            //}
        }

        // Update question columns with actual responses
        $response_total_likes = 0;
        $response_total_flags = 0;
        foreach($activity_response as $ar){     
            if($ar['question_type'] == 'html' || $ar['question_type'] == 'breathe'){
                continue;
            }  
            $total_likes = $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_likes WHERE pid = '.$response_id.' AND \'row\' = '.$ar['id'].' AND unliked = 0');
            $total_flags = $wpdb->get_var( 'SELECT COUNT(*) FROM thoughts_flags WHERE pid = '.$response_id.' AND \'row\' = '.$ar['id'].' AND status = 0');

            $ar_date_convert = str_replace('/', '-', $ar['date']);
            $row_date = new DateTime($ar_date_convert);
            $row_date->setTimezone($timezone);
            
            $question_key = $activity_questions[ $ar['id'] ]['question_label'] != '' ? $activity_questions[ $ar['id'] ]['question_label'] : removeMhaTooltip(strip_tags($activity_questions[ $ar['id'] ]['question']));
            $csv_data[$i][$question_key.' - Response'] = $ar['answer'];
            $csv_data[$i][$question_key.' - Date']     = $row_date->format("Y-m-d H:i:s");
            $csv_data[$i][$question_key.' - Updated']  = $ar['updated'];
            //if($ar['tool_type'] == 'question_answer'){
                $csv_data[$i][$question_key.' - Likes']    = $total_likes ? $total_likes : "0";
                $csv_data[$i][$question_key.' - Flags']    = $total_flags ? $total_flags : "0";
            //}
            $response_total_likes = $response_total_likes + $total_likes;
            $response_total_flags = $response_total_flags + $total_flags;
        }

        //if($ar['tool_type'] == 'question_answer'){
            $csv_data[$i]['Total Likes'] = $response_total_likes;
            $csv_data[$i]['Total Flags'] = $response_total_flags;
            $csv_data[$i]['Admin Notes'] = get_field('admin_notes');
            $csv_data[$i]['Post Date'] = get_the_date('Y-m-d H:i:s');
        //}  

        $hash_email = '';
        if($author_id != 4){
            $user_info = get_userdata($author_id);
            $hash_email = md5($user_info->user_email);
        }

        $csv_data[$i]['User Email (Hashed)'] = $hash_email;
        $csv_data[$i]['uid'] = $author_id != 4 ? $author_id : '';
        $csv_data[$i]['Post Link'] = html_entity_decode(get_the_permalink($response_id));

        $i++;
    endwhile;        
    endif;

    /**
     * Set next step variables
     */    
    
    $args['max'] = $diy_res_loop->max_num_pages > 0 ? $diy_res_loop->max_num_pages : 1;    
    $args['percent'] = round( ( ( $args['page'] / $args['max']) * 100 ), 2 );
    
    if($args['page'] >= $args['max']){
        $args['next_page'] = '';
    } else {
        $args['next_page'] = $args['page'] + 1;
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

        if(!$args['filename']){
            $form_slug = sanitize_title (get_the_title($args['form_id']) );
            $args['filename'] = $args['filename'] ? $args['filename'] : $form_slug.'--'.$args['diytool_export_start_date'].'_'.$args['diytool_export_end_date'].'--'.date('U').'.csv';
        }
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
            if(isset($csv_data[array_key_first($csv_data)])){
                foreach($csv_data[array_key_first($csv_data)] as $k => $v){
                    $csv_headers[] = $k;                           
                }
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
        /*
        $encoder = (new CharsetConverter())
            ->inputEncoding('utf-8')
            ->outputEncoding('iso-8859-15')
        ;
        
        $writer->addFormatter($encoder);
        */

    } catch (CannotInsertRecord $e) {
        $args['error'] = $e->getRecords();
    }

    // All Forms Continuation
    if(!$args['all_forms']){    
        $args['all_forms'] = null;
        $args['all_forms_continue'] = null;
    } else {
        $args['all_forms_continue'] = 1;
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