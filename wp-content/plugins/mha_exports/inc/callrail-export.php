<?php

// Plugins
require_once dirname(__DIR__) . '/vendor/autoload.php';
use League\Csv\CharsetConverter;
use League\Csv\Writer;
use League\Csv\Reader;

// Enqueing Scripts
add_action('init', 'mhacallrailctaCodesExportScripts');
function mhacallrailctaCodesExportScripts() {
    if(current_user_can('edit_posts')){
        wp_enqueue_script( 'process_mhacallrailctaExport', plugin_dir_url(__DIR__) . 'js/callrail_export.js', array('jquery'), time(), true );
        wp_localize_script('process_mhacallrailctaExport', 'do_mhacallrailctaExport', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }
}

add_action( 'wp_ajax_mha_export_callrailcta', 'mha_export_callrailcta' );
function mha_export_callrailcta(){

	// General variables
    global $wpdb;
    $timezone = new DateTimeZone('America/New_York');
	
    // Prep our post data args
    if(isset($_POST['start']) && intval($_POST['start']) == 1 || empty($_POST) ){

        // For the first pass, set our defaults
        $defaults = array(
            'nonce'                         => null,
            'callrailcta_export_start_date' => null,
            'callrailcta_export_end_date'   => null,
            'export_source'                 => null,
            'page'                          => 0,
            'csv_headers'                   => array(),
            'filename'                      => null,
            'total'                         => null,
            'max'                           => null,
            'percent'                       => null,
            'next_page'                     => null,
            'elapsed_start'                 => null,
            'elapsed_end'                   => null,
            'total_elapsed_time'            => null,
            'download'                      => null,
            'total_rows'                    => null,
            'debug'                         => null
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

    $i = 0;
    $csv_data = [];
    $per_page = 5000;
    $offset = $args['page'] * $per_page;

    if( $args['callrailcta_export_start_date'] && !$args['callrailcta_export_end_date'] ){
        $where = 'WHERE date >= \''.$args['callrailcta_export_start_date'].'\'';
    } else if( !$args['callrailcta_export_start_date'] && $args['callrailcta_export_end_date'] ){
        $where = 'WHERE date <= \''.$args['callrailcta_export_start_date'].'\'';
    } else if( $args['callrailcta_export_start_date'] && $args['callrailcta_export_end_date'] ){
        $where = 'WHERE date BETWEEN \''.$args['callrailcta_export_start_date'].'\' AND \''.$args['callrailcta_export_end_date'].'\'';
    } else {
        $where = '';
    }

    if($args['page'] == 0){
        $total_rows = $wpdb->get_var("SELECT COUNT(*) FROM callrail_log $where");
        $args['total_rows'] = $total_rows;
        $args['max'] = ceil($total_rows / $per_page);
        $offset = 0;
    }

    $args['query'] = "SELECT * FROM callrail_log $where LIMIT $offset,$per_page";

    $csv_data = $wpdb->get_results($args['query'], ARRAY_A );

    /**
     * Set next step variables
     */      
    if($args['max'] == 0){
        $args['error'] = 'There is no data for the selected criteria.';
        echo json_encode($args); 
        exit();
    }

    $args['percent'] = round( ( ( $args['page'] / $args['max']) * 100 ), 2 );
    if($args['page'] >= $args['max']){
        $args['next_page'] = '';
    } else {
        $args['next_page'] = $args['page'] + 1;
    }  
    
    if(count($csv_data) == 0){
        $args['download'] = '#'; 
        $args['elapsed_end'] = time();
    }
        
    /**
     * Write CSV
     */
    try {

        if(!$args['filename']){
            $form_slug = 'callrail_ad_log';
            $args['filename'] = $args['filename'] ? $args['filename'] : $form_slug.'-'.$args['export_start_date'].'_'.$args['export_end_date'].'-'.date('U').'.csv';
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
        if($args['page'] == 0){

            $csv_headers = [];

            // Create header array
            foreach($csv_data[array_key_first($csv_data)] as $k => $v){
                $csv_headers[] = $k;                           
            }

            // Set order for later
            $args['csv_headers'] = array_values($csv_headers);
            $writer->insertOne($csv_headers);
        }    

        // Write the results to the CSV
        $writer->insertAll(new ArrayIterator($csv_data));

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