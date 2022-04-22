<?php

// Plugins
require_once __DIR__ . '/vendor/autoload.php';
use League\Csv\CharsetConverter;
use League\Csv\Writer;
use League\Csv\Reader;

add_action( 'wp_ajax_mha_export_screen_data', 'mha_export_screen_data' );
function mha_export_screen_data(){
    
	// General variables
    $timezone = new DateTimeZone('America/New_York');
    $loop_check = intval($_POST['start']);
	
    // Prep our post data args
    if($loop_check == 1){

        // For the first pass, set our defaults
        $defaults = array(
            'form_id'                   => null,
            'all_forms'                 => null,
            'nonce'                     => null,
            'export_screen_start_date'  => null,
            'export_screen_end_date'    => null,
            'export_screen_ref'         => null,
            'export_screen_spam'        => 0,
            'field_labels'              => '',
            'page'                      => 1,
            'field_order'               => null,
            'field_types'               => null,
            'filename'                  => null,
            'total'                     => null,
            'max'                       => null,
            'percent'                   => null,
            'next_page'                 => null,
            'elapsed_start'             => null,
            'elapsed_end'               => null,
            'total_elapsed_time'        => null,
            'download'                  => null,
            'all_forms'                 => null,
            'all_forms_continue'        => null
        );    
        parse_str( $_POST['data'], $data);
        $args = wp_parse_args( $data, $defaults );  

    } else {        

        // For loops, just use the data given
        $args = stripslashes_deep($_POST['data']);
        
    }

    // Form ID Determination
    $form_id = $args['form_id'];    

    // All forms override
    if($args['all_forms']){
        $all_forms = json_decode(stripslashes($args['all_forms']), true);
        $form_id = $all_forms[0];
        $args['all_forms'] = $args['all_forms'];
    }

    // Pagination
    $page_size = 1500;
    $offset = ($args['page'] - 1) * $page_size;

    // CSV Export
    $search_criteria = [];
    $search_criteria['status'] = 'active';
    $search_criteria['field_filters']['mode'] = 'all';
    $search_criteria['start_date'] = $args['export_screen_start_date'];
    $search_criteria['end_date'] = $args['export_screen_end_date'];
    
    // Get field order/headers for later
    $gform = GFAPI::get_form( $form_id );
    $form_slug = sanitize_title_with_dashes($gform['title']);
    
    if(!$args['field_order']){
        // CSV headers
        $args['field_order'] = [];
        $args['field_order']['Date'] = 'Date';          // Manual additional field
        $args['field_order']['User IP'] = 'User IP';    // Manual additional field

        // CSV header data
        $args['field_types'] = [];

        foreach($gform['fields'] as $gf){  
            $field = GFAPI::get_field( $form_id, $gf['id'] );
            if(
                isset($field->type) && $field->type == 'html' || 
                isset($field->label) && $field->label == 'Screen ID' || 
                isset($field->label) && $field->label == 'Source URL' || 
                isset($field->label) && $field->label == ''){
                continue; // Skip these columns
            } 

            // Set field order
            $args['field_order'][] = $gf['label']; // Default header options
        
            // Update field type data
            $args['field_types'][$gf['id']] = array(
                'type' => $gf['type'],
                'label' => $gf['label']
            );
            if($gf['type'] == 'radio' || $gf['type'] == 'checkbox'){
                $model = RGFormsModel::get_field( $form_id, $gf['id'] );
                $args['field_types'][$gf['id']]['model'] = $model['choices'];
            }

            // Custom columns
            if($field->label == 'uid'){ 
                $args['field_order'][] = 'uid hashed'; 
                $args['field_types']['uid hashed'] = array(
                    'type' => 'mha_custom',
                    'label' => 'uid hashed'
                );
            }
        }

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

    // Parse through form entries
    foreach($entries as $e){
        $gfdata = GFAPI::get_entry( $e['id'] );
        $temp_array = [];
        $spam_check = 0;
        $last_field = '';

        $row_date = new DateTime($gfdata['date_created']);
        $row_date->setTimezone($timezone);
        //$temp_array['Item Counter'] = $i;
        $temp_array['Date'] = $row_date->format("Y-m-d H:i:s");     
        $temp_array['User IP'] = $gfdata['ip'];    
        
        foreach($args['field_types'] as $ftk => $ftv){
            $v = rgar( $e, $ftk, '' );
            if( $ftv['type'] == 'radio' || $ftv['type'] == 'radio' ){
                foreach($args['field_types'][$ftk]['model'] as $model){
                    if($model['value'] == $v){
                        $v = $model['text'];
                    }
                }
                $temp_array[ $args['field_types'][$ftk]['label'] ] = $v;    
            } else {
                if ($ftv['label'] == 'uid hashed' ) {
                    $temp_array[ 'uid hashed' ] = md5($e['uid']);
                } else {
                    $temp_array[ $args['field_types'][$ftk]['label'] ] = $v;
                }
            }
        }

        // Spam check
        /*
        if($spam_check > 0){
            $temp_array['Spam Likely'] = 'yes';
        } else {
            $temp_array['Spam Likely'] = 'no';
        }    
        if($exclude_spam == 1 && $spam_check > 0){
            $temp_array = [];
        }
        */

        // Reorder our fields based on how they appear on the form and add the row
        if(!empty($temp_array)){
            foreach($args['field_order'] as $key){  
                $csv_data[$i][$key] = $temp_array[$key]; 
            }
            $i++;
        }

    }

    /**
     * Set next step variables and exit
     */
    $args['total'] = $total_count;
    $max_pages = ceil($total_count / $page_size);
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

    /**
     * Write CSV
     */
    try {
        // Create CSV
        if(isset($args['filename'])){
            // Update existing file
            $filename = $args['filename'];
            $writer_type = 'a+';
        } else {
            // Create file
            $filename = $form_slug.'--'.$args['export_screen_start_date'].'_'.$args['export_screen_end_date'].'--'.date('U').'.csv';
            $writer_type = 'w+';
        }

        $writer = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, $writer_type);
        $args['filename'] = $filename;
        
        if($args['page'] >= $max_pages){
            // Final page
            $args['download'] = plugin_dir_url(__FILE__).'tmp/'.$filename;   

            // Elapsed time
            $args['elapsed_end'] = time();
            $interval = $args['elapsed_end'] - $args['elapsed_start'];
            $args['total_elapsed_time'] = gmdate("H:i:s", abs($interval));
            
            // All Forms Continuation
            if(isset($args['all_forms']) && count($all_forms) > 0){    
                $cut_form = array_shift($all_forms);
                $args['all_forms'] = json_encode($all_forms, JSON_UNESCAPED_SLASHES);
                $args['all_forms_continue'] = 1;
            } else {
                $args['all_forms'] = null;
                $args['all_forms_continue'] = null;
            }

        }
        
        // Headers only on page 1
        $reader = Reader::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'r+');
        $csv_row = $reader->fetchOne();
        if($args['page'] == 1 || empty($csv_row)){
            $csv_headers = [];
            foreach($csv_data[array_key_first($csv_data)] as $k => $v){
                $csv_headers[] = $k;
            }
            $writer = Writer::createFromPath(plugin_dir_path(__FILE__).'tmp/'.$filename, 'w+');
            $writer->insertOne($csv_headers);
        }    
        $writer->insertAll(new ArrayIterator($csv_data));
        $encoder = (new CharsetConverter())
            ->inputEncoding('utf-8')
            ->outputEncoding('iso-8859-15')
        ;
        $writer->addFormatter($encoder);

    } catch (CannotInsertRecord $e) {
        $args['error'] = $e->getRecords();
    }

    $args['page'] = $args['next_page']; // Set the loops next page

    echo json_encode($args);
    exit();   

}