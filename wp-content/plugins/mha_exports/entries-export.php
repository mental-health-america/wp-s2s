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

    
    // Form ID Determination
    $form_id = $data['export_screen_form'];
    $result['all_forms'] = null;
    $all_forms = null;
    if(isset($data['all_forms']) && $data['all_forms'] != null && $data['all_forms'] != "null"){
        // All Forms Override
        $all_forms = json_decode(stripslashes($data['all_forms']), true);
        $form_id = $all_forms[0];
        $result['all_forms'] = stripslashes($data['all_forms']);
    }
    $result['export_screen_form'] = $form_id;
    
    // TODO: Speedier exports by passing the checkbox labels instead of querying every time?
    /*
    if(isset($data['field_labels'])){
        $field_labels = json_decode($data['field_labels']); 
    } else {
        $field_labels = []; 
    }
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
    //$result['offset'] = $offset;

    // CSV Export
    $search_criteria = [];
    $search_criteria['status'] = 'active';
    $search_criteria['field_filters']['mode'] = 'all';
    $search_criteria['start_date'] = $startDate;
    $search_criteria['end_date'] = $endDate;
    
    // Get field order/headers for later
    $gform = GFAPI::get_form( $form_id );
    $form_slug = sanitize_title_with_dashes($gform['title']);
    $field_order = [];
    $field_order['Date'] = 'Date';                          // Manual additional field
    $field_order['Spam Likely'] = 'Spam Likely';            // Manual additional field
    //$field_order['Item Counter'] = 'Item Counter';        // Manual additional field

    foreach($gform['fields'] as $gf){  
        $field = GFAPI::get_field( $form_id, $gf['id'] );
        if(
            isset($field->type) && $field->type == 'html' || 
            isset($field->label) && $field->label == 'User IP' || 
            isset($field->label) && $field->label == 'Token' || 
            isset($field->label) && $field->label == 'Screen ID' || 
            isset($field->label) && $field->label == 'Source URL' || 
            isset($field->label) && $field->label == 'uid' || 
            isset($field->label) && $field->label == ''){
            continue; // Skip these columns
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
        $last_field = '';

        $row_date = new DateTime($gfdata['date_created']);
        $row_date->setTimezone($timezone);
        //$temp_array['Item Counter'] = $i;
        $temp_array['Date'] = $row_date->format("Y-m-d H:i:s");       

        foreach($gfdata as $k => $v){           
            $field = GFAPI::get_field( $form_id, $k );
            $current_field = $field->id;

            // Skip these columns
            if(
                isset($field->type) && $field->type == 'html' || 
                isset($field->label) && $field->label == 'User IP' || 
                isset($field->label) && $field->label == 'Token' || 
                isset($field->label) && $field->label == 'Screen ID' || 
                isset($field->label) && $field->label == 'Source URL' || 
                isset($field->label) && $field->label == 'uid' || 
                isset($field->label) && $field->label == ''){
                continue;
            }

            // Get the label instead of value
            if($field->type == 'checkbox'){
                $check_field = RGFormsModel::get_field( $form_id, $k );
                $v = is_object( $check_field ) ? $check_field->get_value_export( $e ) : '';
            }
            if($field->type == 'radio'){
                $v = $field->get_value_export($e, $k, true);  
            }


            // Insert into array
            $temp_array[$field->label] = $v;

            // Count required questions with no value
            if($last_field != $current_field){
                $last_field = $current_field;
                if($field->isRequired && $v == ''){
                    $spam_check++;
                    $result['field_checker'][$i][] = $field;
                }
            }
       
        }

        if($spam_check > 0){
            $temp_array['Spam Likely'] = 'yes';
        } else {
            $temp_array['Spam Likely'] = 'no';
        }

        if($exclude_spam == 1 && $spam_check > 0){
            $temp_array = [];
        }

        // Reorder our fields based on how they appear on the form and add the row
        if(!empty($temp_array)){
            foreach($field_order as $key){  
                $csv_data[$i][$key] = $temp_array[$key]; 
            }
            $i++;
        }
        
    }

    /**
     * Set next step variables and exit
     */
    //$result['field_labels'] = json_encode($field_labels);

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
            
            // All Forms Continuation
            if(isset($data['all_forms']) && count($all_forms) > 0){    
                $cut_form = array_shift($all_forms);
                $result['all_forms'] = json_encode($all_forms, JSON_UNESCAPED_SLASHES);
                $result['all_forms_continue'] = 1;
            } else {
                $result['all_forms'] = null;
                $result['all_forms_continue'] = null;
            }

        }
        
        // Headers only on page 1
        $reader = Reader::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'r+');
        $csv_row = $reader->fetchOne();
        if($page == 1 || empty($csv_row)){
            $csv_headers = [];
            foreach($csv_data[array_key_first($csv_data)] as $k => $v){
                $csv_headers[] = $k;
            }
            $writer = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'w+');
            $writer->insertOne($csv_headers);
        }    
        $writer->insertAll(new ArrayIterator($csv_data));

    } catch (CannotInsertRecord $e) {
        $result['error'] = $e->getRecords();
    }

    echo json_encode($result);
    exit();   

}