<?php

// Plugins
require_once dirname(__DIR__) . '/vendor/autoload.php';
use League\Csv\CharsetConverter;
use League\Csv\Writer;

add_action( 'wp_ajax_mha_export_click_monitor_data', 'mha_export_click_monitor_data' );
function mha_export_click_monitor_data(){

	// General variables
    $timezone = new DateTimeZone('America/New_York');
    
    // Define CSV headers once - order matters for column arrangement
    $csv_headers_default = array('ID', 'Date', 'IPIDEN', 'Click ID', 'CTA', 'URL', 'SID', 'Data Attributes');
	
    // Prep our post data args
    if(isset($_POST['start']) && intval($_POST['start']) == 1 || empty($_POST) ){

        // For the first pass, set our defaults
        $defaults = array(
            'nonce'                         => null,
            'export_click_monitor_start_date'    => date('Y-m', strtotime('now - 1 month')).'-01',
            'export_click_monitor_end_date'      => date('Y-m-t', strtotime('now - 1 month')),
            'page'                          => 1,
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
            'debug'                         => null
        );      

        // Testing options
        if($defaults['debug'] == 1){
            $defaults['export_click_monitor_start_date']  = '2023-01-01';
            $defaults['export_click_monitor_end_date']    = '2023-01-02';
            $args = $defaults;
        } else {
            if (isset($_POST['data'])) {
                parse_str( $_POST['data'], $data);
                $args = wp_parse_args( $data, $defaults );
            } else {
                $args = $defaults;
            }
        }
        
    } else {        

        // For loops, just use the data given
        // Check if data is already an array (from JSON) or needs to be parsed
        if (isset($_POST['data'])) {
            if (is_array($_POST['data'])) {
                $args = stripslashes_deep($_POST['data']);
            } else {
                // If it's a string, try to decode as JSON first, then fall back to parse_str
                $decoded = json_decode(stripslashes($_POST['data']), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $args = $decoded;
                } else {
                    parse_str($_POST['data'], $args);
                }
            }
        } else {
            $args = array('page' => 1);
        }
        
    }

    // Ensure $args is an array and has required keys
    if (!is_array($args)) {
        $args = array();
    }
    if (!isset($args['page']) || !is_numeric($args['page'])) {
        $args['page'] = 1;
    }
    if (!isset($args['export_click_monitor_start_date'])) {
        $args['export_click_monitor_start_date'] = date('Y-m', strtotime('now - 1 month')).'-01';
    }
    if (!isset($args['export_click_monitor_end_date'])) {
        $args['export_click_monitor_end_date'] = date('Y-m-t', strtotime('now - 1 month'));
    }

    // Pagination
    $page_size = 1500;
    $offset = ($args['page'] - 1) * $page_size;
    if($offset < 0){
        $offset = 0;
    }

    // Get click monitor data from database
    global $wpdb;
    $table_name = 'mha_click_monitor';
    
    // Build date query
    $start_date = $args['export_click_monitor_start_date'] . ' 00:00:00';
    $end_date = $args['export_click_monitor_end_date'] . ' 23:59:59';
    
    // Get total count
    $total_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE date >= %s AND date <= %s",
        $start_date,
        $end_date
    ));
    
    // Get paginated results
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE date >= %s AND date <= %s ORDER BY date DESC LIMIT %d OFFSET %d",
        $start_date,
        $end_date,
        $page_size,
        $offset
    ), ARRAY_A);
    
    $csv_data = [];
    $i = 0;
    
    // Parse through click monitor entries
    foreach($results as $row){
        $temp_array = [];

        // Update Timezone
        $row_date = new DateTime($row['date']);
        $row_date->setTimezone($timezone);

        // Extract SID from URL if present
        $sid = '';
        if (!empty($row['url'])) {
            $url_parts = parse_url($row['url']);
            if (isset($url_parts['query'])) {
                parse_str($url_parts['query'], $query_params);
                if (isset($query_params['sid'])) {
                    $sid = $query_params['sid'];
                }
            }
        }

        // Map database fields to CSV columns
        $csv_data[$i]['ID'] = $row['id'];
        $csv_data[$i]['Date'] = $row_date->format("Y-m-d H:i:s");
        $csv_data[$i]['IPIDEN'] = $row['ipiden'];
        $csv_data[$i]['Click ID'] = $row['click_id'];
        $csv_data[$i]['CTA'] = $row['cta'];
        $csv_data[$i]['URL'] = $row['url'];
        $csv_data[$i]['SID'] = $sid;
        // Only include Data Attributes if the column exists in the database
        if (array_key_exists('data_attributes', $row)) {
            $csv_data[$i]['Data Attributes'] = $row['data_attributes'];
        } else {
            $csv_data[$i]['Data Attributes'] = '';
        }
        
        $i++;
    }

    /**
     * Set next step variables and exit
     */
    $args['total'] = $total_count > 0 ? $total_count : 1; // Set to 1 just in case of no entries to avoid divide by 0 errors
    $max_pages = (ceil($total_count / $page_size) > 0) ? ceil($total_count / $page_size) : 1;
    $args['max'] = $max_pages;
    $args['percent'] = round( ( ($args['page'] / $max_pages) * 100 ), 2 );
    if($args['page'] >= $max_pages){
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

        $args['filename'] = $args['filename'] ? $args['filename'] : 'click-monitor--'.$args['export_click_monitor_start_date'].'_'.$args['export_click_monitor_end_date'].'--'.date('U').'.csv';
        $writer_type = $args['filename'] ? 'a+' : 'w+';
                
        if($args['page'] >= $max_pages){
            
            // Final page
            $args['download'] = WP_PLUGIN_URL.'/mha_exports/tmp/'.$args['filename'];   

            // Elapsed time
            $args['elapsed_end'] = time();
            $interval = $args['elapsed_end'] - $args['elapsed_start'];
            $args['total_elapsed_time'] = gmdate("H:i:s", abs($interval));
        }

        $writer = Writer::createFromPath(WP_PLUGIN_DIR.'/mha_exports/tmp/'.$args['filename'], $writer_type);        

        // Set the headers only on page 1        
        if($args['page'] == 1 && $csv_data){
            // Use default headers or extract from first row if available
            $args['csv_headers'] = $csv_headers_default;
            
            // Set headers
            $writer->insertOne($args['csv_headers']);
        }    

        // Ensure csv_headers is set (from previous page or default)
        if (!isset($args['csv_headers']) || !is_array($args['csv_headers'])) {
            $args['csv_headers'] = $csv_headers_default;
        }

        // Organize the data by the header values
        $csv_data_ordered = [];
        if (!empty($csv_data) && !empty($args['csv_headers'])) {
            $header_flip = array_flip($args['csv_headers']);
            foreach($csv_data as $cd){
                $csv_data_ordered[] = sortArrayByArray($cd, $header_flip);
            }
        }

        if($args['debug'] == 1){
            pre($csv_data_ordered);
            pre($args);
            return;
        }

        // Write the results to the CSV
        $writer->insertAll(new ArrayIterator($csv_data_ordered));
        $encoder = (new CharsetConverter())
            ->inputEncoding('utf-8')
            ->outputEncoding('iso-8859-15')
        ;
        
        $writer->addFormatter($encoder);


    } catch (Exception $e) {
        $args['error'] = $e->getMessage();
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
