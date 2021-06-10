<?php

// Plugins
require_once __DIR__ . '/vendor/autoload.php';
use League\Csv\CharsetConverter;
use League\Csv\Writer;
use League\Csv\Reader;

add_action( 'wp_ajax_mha_export_screen_data', 'mha_export_screen_data' );
function mha_export_screen_data(){
    
	// General variables
    $result = array();
    $timezone = new DateTimeZone('America/New_York');
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);  

    // Options
    $startDate = $data['export_screen_start_date'];
    $result['export_screen_start_date'] = $startDate;

    $endDate = $data['export_screen_end_date'];
    $result['export_screen_end_date'] = $endDate;

    $ref = $data['export_screen_ref'];
    $result['export_screen_ref'] = $ref;

    $exclude_spam = $data['export_screen_spam'];
    $result['export_screen_spam'] = $exclude_spam;

    $form_id = $data['export_screen_form'];
    //$form_id = 1; // Debug
    $result['export_screen_form'] = $form_id;

    
    // For speedier exports, avoid checking get_value_export() every time.
    if(isset($data['field_labels'])){
        $field_labels = json_decode($data['field_labels'],); 
    } else {
        $field_labels = []; 
    }

    /*
    $dupes = '';
    if(isset($data['export_screen_duplicates']) && $data['export_screen_duplicates'] != ''){
        $dupes = $data['export_screen_duplicates'];
    }
    $result['export_screen_duplicates'] = $dupes;
    */

    // Pagination
    $page_size = 1000;
    if(isset($data['page'])){
        $page = $data['page'];
    } else {
        $page = 1;
    }
    $result['page'] = $page;
    $offset = ($page - 1) * $page_size;
    $result['offset'] = $offset;

    // CSV Export
    $search_criteria = [];
    $search_criteria['status'] = 'active';
    $search_criteria['field_filters']['mode'] = 'all';
    $search_criteria['start_date'] = $startDate;
    $search_criteria['end_date'] = $endDate;
    
    // Get field order for later
    $gform = GFAPI::get_form( $form_id );
    $form_slug = sanitize_title_with_dashes($gform['title']);
    $field_order = [];
    $field_order['Date'] = 'Date'; // Manual additional field
    foreach($gform['fields'] as $gf){
        
        // Skip these columns
        $field = GFAPI::get_field( $form_id, $gf['id'] );
        if(
            isset($field->type) && $field->type == 'html' || 
            isset($field->label) && $field->label == 'User IP' || 
            isset($field->label) && $field->label == 'Token' || 
            isset($field->label) && $field->label == 'Screen ID' || 
            isset($field->label) && $field->label == 'Source URL' || 
            isset($field->label) && $field->label == 'uid'){
            continue;
        } 
        $field_order[] = $gf['label'];

    }

    // Referer filter
    if($ref != ''):
        foreach($gform['fields'] as $field):
            if($field['label'] == 'Referer'){
                $search_criteria['field_filters'][] = array( 
                    'key' => $field['id'], 
                    'operator' => 'contains', 
                    'value' => $ref
                );
                break;
            }
        endforeach;
    endif;

    // Get form entries
    $paging = array( 'offset' => $offset, 'page_size' => $page_size );
    $total_count = 0;
    $entries = GFAPI::get_entries( $form_id, $search_criteria, null, $paging, $total_count );
    $search_criteria['total_count_real'] = $total_count;
    $csv_data = [];
    $i = 0;

    foreach($entries as $e){

        // Put all fields in the CSV
        $gfdata = GFAPI::get_entry( $e['id'] );
        $temp_array = [];
        $spam_check = 0;

        $row_date = new DateTime($gfdata['date_created']);
        $row_date->setTimezone($timezone);
        $temp_array['Counter'] = $i;
        $temp_array['Date'] = $row_date->format("Y-m-d H:i:s");

        foreach($gfdata as $k => $v){           
            $field = GFAPI::get_field( $form_id, $k );

            // Skip these columns
            if(
                isset($field->type) && $field->type == 'html' || 
                isset($field->label) && $field->label == 'User IP' || 
                isset($field->label) && $field->label == 'Token' || 
                isset($field->label) && $field->label == 'Screen ID' || 
                isset($field->label) && $field->label == 'Source URL' || 
                isset($field->label) && $field->label == 'uid'){
                continue;
            }

            // Count required questions with no value
            if($field->isRequired && $v == ''){
                $spam_check++;
            }

            // Get the label instead of value
            if(isset($field->type) && $field->type == 'radio' || isset($field->type) && $field->type == 'checkbox'){
                $v = $field->get_value_export($e, $k, true);  
            }

            // Insert into array
            $temp_array[$field->label] = $v;
       
        }

        // Reorder our fields based on how they appear on the form and add the row
        if(!empty($temp_array)){
            //$result['temp_array'] = $temp_array;
            foreach($field_order as $key){             
                if($exclude_spam == 1 && $spam_check > 0 ){
                    continue;
                }         
                $csv_data[$i][$key] = $temp_array[$key];  
                $csv_data[$i]['Spam Likely'] = $spam_check; // Spam Speculation
            }            
            
            if($exclude_spam == 1 && $spam_check > 0 ){
                continue; // Skip counter if entry is suspected spam and we want to exclude it
            }
            $i++;
        }
        
    }

    /**
     * Set next step variables and exit
     */
    $result['field_labels'] = json_encode($field_labels);

    $result['total'] = $total_count;
    $max_pages = ceil($total_count / $page_size);
    $result['max'] = $max_pages;
    $result['percent'] = round( ( ($page / $max_pages) * 100 ), 2 );
    if($page >= $max_pages){
        $result['next_page'] = '' ;
    } else {
        $result['next_page'] = $page + 1;
    }  

    /**
     * Elapsed Time
     */
    if(isset($data['elapsed_start'])){
        $result['elapsed_start'] = $data['elapsed_start'];
    } else {
        $result['elapsed_start'] = time();
    }

    /**
     * Write CSV
     */
    try {
        // Create CSV
        if(isset($data['filename'])){
            // Update existing file
            $filename = $data['filename'];
            $writer_type = 'a+';
        } else {
            // Create file
            $filename = $form_slug.'--'.$startDate.'_'.$endDate.'--'.date('U').'.csv';
            $writer_type = 'w+';
        }

        $writer = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, $writer_type);
        $result['filename'] = $filename;
        
        if($page >= $max_pages){
            // Final page
            $result['download'] = plugin_dir_url(__FILE__).'tmp/'.$filename;

            // Elapsed time
            $result['elapsed_end'] = time();
            $interval = $result['elapsed_end'] - $result['elapsed_start'];
            $result['total_elapsed_time'] = gmdate("H:i:s", abs($interval));
        }
        
        // Headers only on page 1
        if($page == 1){
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