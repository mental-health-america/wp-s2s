<?php

// Plugins
require_once __DIR__ . '/vendor/autoload.php';
use League\Csv\CharsetConverter;
use League\Csv\Writer;
use League\Csv\Reader;

add_action( 'wp_ajax_mha_export_feedback_data', 'mha_export_feedback_data' );
function mha_export_feedback_data(){

	// General variables
    $timezone = new DateTimeZone('America/New_York');
	
    // Prep our post data args
    if(isset($_POST['start']) && intval($_POST['start']) == 1 || empty($_POST) ){

        // For the first pass, set our defaults
        $defaults = array(
            'form_id'                       => null,
            'nonce'                         => null,
            'export_feedback_start_date'    => date('Y-m', strtotime('now - 1 month')).'-01',
            'export_feedback_end_date'      => date('Y-m-t', strtotime('now - 1 month')),
            'export_feedback_ref'           => null,
            'export_excluded_ips'           => 0,
            'export_only_demographic'       => 0,
            'excluded_ips'                  => array_map('trim', explode(PHP_EOL, get_field('ip_exclusions', 'options'))),
            'field_labels'                  => '',
            'page'                          => 1,
            'fields'                        => array(),
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
            'export_feedback_scores'        => 0,

            'all_forms'                     => null,
            'all_forms_ids'                 => null,
            'all_forms_headers'             => null,
            'export_single'                 => null,
            'export_single_continue'        => null,

            'debug'                         => null
        );      

        // Testing options
        if($defaults['debug'] == 1){
            $defaults['form_id']                   = 34;
            $defaults['export_only_demographic']   = 0;
            $defaults['export_feedback_start_date']  = '2023-01-01';
            $defaults['export_feedback_end_date']    = '2023-01-02';
            $defaults['all_forms_ids']             = null;
            $args = $defaults;
        }

        parse_str( $_POST['data'], $data);
        $args = wp_parse_args( $data, $defaults );  
        
    } else {        

        // For loops, just use the data given
        $args = stripslashes_deep($_POST['data']);
        
    }

    // Pagination
    $page_size = 1500;
    $offset = ($args['page'] - 1) * $page_size;
    if($offset < 0){
        $offset = 0;
    }

    // CSV Export
    $search_criteria = [];
    $search_criteria['status'] = 'active';
    $search_criteria['field_filters']['mode'] = 'all';
    $search_criteria['start_date'] = $args['export_feedback_start_date'];
    $search_criteria['end_date'] = $args['export_feedback_end_date'];
    
    // Get field order/headers for later
    $gform = GFAPI::get_form( $args['form_id'] );
    $form_slug = $args['export_single'] ? 'combined' : sanitize_title($gform['title']);
        
    if(empty($args['fields'])){

        // Manual additional field
        $args['fields'][0] = array(
            'type' => 'mha_custom',
            'key' => 'Created',
            'label' => 'Created'
        );          
        $args['fields'][1] = array(
            'type' => 'mha_custom',
            'key' => 'Remote IP address',
            'label' => 'Remote IP address'
        );


        // Field population
        $fi = count($args['fields']) + 1;
        $unique_fields = [];

        foreach($gform['fields'] as $gf){  
            $field = GFAPI::get_field( $args['form_id'], $gf['id'] );
            $field_type = isset($field->type) ? $field->type : '';
            $field_label = isset($field->label) ? $field->label : '';

            // Normal Export Skips
            if(
                $field_type == 'html' || 
                $field_label == 'Source URL' || 
                $field_label == 'Duplicate' || 
                $field_label == 'uid hashed' || 
                $field_label == ''){
                continue; // Skip these columns
            } 

            // Set field order
            if(in_array($gf['label'], $unique_fields)){
                $label_text = $gf['label']. ' (#'.$gf['id'].')';
            } else {
                $label_text = $gf['label'];
            }
            $unique_fields[] = $label_text;
            $args['fields'][$fi] = array(
                'type' => $gf['type'],
                'key' => $gf['id'],
                'label' => $label_text
            );

            // Radio/Checkboxes label model to carry over
            if($gf['choices']){
                $model = RGFormsModel::get_field( $args['form_id'], $gf['id'] );
                $args['fields'][$fi]['model'] = $model['choices'];
            }

            // Checkboxes
            if($gf['inputs']){
                foreach($model['inputs'] as $input){     
                    $args['fields'][$fi]['inputs'][] = $input['id'];
                }
            }

            $fi++;
        }

    }

    // Get form entries
    $paging = array( 'offset' => $offset, 'page_size' => $page_size );
    $total_count = 0;    

    $entries = GFAPI::get_entries( $args['form_id'], $search_criteria, null, $paging, $total_count );
    $csv_data = [];
    $i = 0;
    
    // Parse through form entries
    foreach($entries as $entry){
        $temp_array = [];
        $spam_check = 0;

        // Update Timeszone
        $row_date = new DateTime($entry['date_created']);
        $row_date->setTimezone($timezone);

        // Get all the fields
        foreach($entry as $e){

            foreach($args['fields'] as $ftk => $ftv){  

                // Default value
                $v = strval(rgar( $entry, $ftv['key'] ));
                
                // Check for a label if the value is just a number
                if(is_numeric($v) && $args['export_feedback_scores'] != 1){
                    if(isset($ftv['model'])){
                        foreach($ftv['model'] as $m){
                            if($m['value'] == $v){
                                $v = $m['text'];
                            }
                        } 
                    }
                }

                // Checkboxes are split weirdly so...
                if(isset($ftv['inputs'])){
                    $entry_inputs = array();
                    foreach($ftv['inputs'] as $fin){
                        if(isset($entry[$fin])){
                            if(trim($entry[$fin]) !=  ''){
                                $entry_inputs[] = $entry[$fin];
                            }
                        }
                    }
                    $v = implode(';',$entry_inputs);
                }

                // Put into our array
                if(isset($temp_array[ $ftv['label'] ])){
                    $temp_array[ $ftv['label'].' (#'.$ftk.')' ] = $v;
                } else {
                    $temp_array[ $ftv['label'] ] = $v;
                }
                
            }

        }

        // Reorder our fields based on how they appear on the form, override some label names, and add the row
        foreach($args['fields'] as $k => $v){              
            $vlabel = $v['label'];
            $csv_data[$i][$vlabel] = $temp_array[$v['label']]; 
        }            

        // Custom field assignments
        $csv_data[$i]['Created'] = $row_date->format("Y-m-d H:i:s");     
        $csv_data[$i]['Remote IP address'] = $entry['ip'];    
        $csv_data[$i]['uid'] = isset($temp_array['uid']) ? md5($temp_array['uid']) : '';
        $csv_data[$i]['Source URL'] = $entry['source_url'];  
        $csv_data[$i]['User Agent'] = $entry['user_agent'];   

        $source_post = url_to_postid( $entry['source_url'] );
        $related_id = $source_post ? $source_post : 'meh';
        $csv_data[$i]['Related Post ID'] = $related_id; 

        //$csv_data[$i]['Main User'] = $entry['created_by'];  
        $i++;
    }

    /**
     * Set next step variables and exit
     */
    $args['total'] = $total_count > 0 ? $total_count : 1; // Set to 1 just in case of no entries to avoid divide by 0 errors
    $max_pages = (ceil($total_count / $page_size) > 0) ? ceil($total_count / $page_size) : 1;
    //if($max_pages == 0){ $max_pages = 1; }
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

        $args['filename'] = $args['filename'] ? $args['filename'] : $form_slug.'--'.$args['export_feedback_start_date'].'_'.$args['export_feedback_end_date'].'--'.date('U').'.csv';
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

            $csv_headers = [];

            // Create header array
            //foreach($args['fields'] as $k => $v){
            foreach($csv_data[array_key_first($csv_data)] as $k => $v){
                $csv_headers[] = $k;                    
                //$csv_headers[] = $v['label'];                
            }
                        
            // Set order for later
            $args['csv_headers'] = array_values($csv_headers);
            
            // Set headers
            if($args['export_single_continue'] != 1){
                $writer->insertOne($csv_headers);
            }
        }    

        // Organize the data by the header values
        $csv_data_ordered = [];
        $header_flip = array_flip($args['csv_headers']);
        foreach($csv_data as $cd){
            $csv_data_ordered[] = sortArrayByArray($cd, $header_flip);
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


    } catch (CannotInsertRecord $e) {
        $args['error'] = $e->getRecords();
    }

    // All Forms Continuation
    if(!$args['all_forms']){    
        $args['all_forms'] = null;
        $args['all_forms_continue'] = null;
        $args['export_single'] = null;
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