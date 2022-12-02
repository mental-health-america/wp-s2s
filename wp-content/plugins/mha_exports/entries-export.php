<?php

// Plugins
require_once __DIR__ . '/vendor/autoload.php';
use League\Csv\CharsetConverter;
use League\Csv\Writer;
use League\Csv\Reader;

function validateDate($date, $format = 'm-d'){
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

function sortArrayByArray($array,$orderArray) {
    $ordered = array();
    foreach($orderArray as $key => $value) {
        if(array_key_exists($key,$array)) {
            $ordered[$key] = $array[$key];
            unset($array[$key]);
        }
    }
    return $ordered + $array;
}

add_action( 'wp_ajax_mha_export_screen_data', 'mha_export_screen_data' );
function mha_export_screen_data(){

	// General variables
    $timezone = new DateTimeZone('America/New_York');
	
    // Prep our post data args
    if(isset($_POST['start']) && intval($_POST['start']) == 1 || empty($_POST) ){

        // For the first pass, set our defaults
        $defaults = array(
            'form_id'                   => null,
            'nonce'                     => null,
            'export_screen_start_date'  => date('Y-m', strtotime('now - 1 month')).'-01',
            'export_screen_end_date'    => date('Y-m-t', strtotime('now - 1 month')),
            'export_screen_ref'         => null,
            'export_excluded_ips'       => 0,
            'export_only_demographic'   => 0,
            'excluded_ips'              => array_map('trim', explode(PHP_EOL, get_field('ip_exclusions', 'options'))),
            'field_labels'              => '',
            'page'                      => 1,
            'fields'                    => array(),
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
            
            'all_forms'                 => null,
            'all_forms_ids'             => null,
            'all_forms_headers'         => null,
            'export_single'             => null,
            'export_single_continue'    => null,

            'debug'                     => null
        );      

        // Testing options
        if($defaults['debug'] == 1){
            $defaults['form_id']                   = 12;
            $defaults['export_only_demographic']   = 0;
            $defaults['export_screen_start_date']  = '2021-10-01';
            $defaults['export_screen_end_date']    = '2021-10-01';
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
    $search_criteria['start_date'] = $args['export_screen_start_date'];
    $search_criteria['end_date'] = $args['export_screen_end_date'];
    
    // Get field order/headers for later
    $gform = GFAPI::get_form( $args['form_id'] );
    $form_slug = $args['export_single'] ? 'combined' : sanitize_title($gform['title']);
    $all_form_ids = $args['all_forms_ids'] != null ? explode(',', $args['all_forms_ids']) : null;
        
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
 
        /**
         * Demographic Only Data
         */
        if($args['export_only_demographic'] == 1 && $args['page'] == 1){
            
            // Get headers for all forms
            $all_form_demo_fields = [];    
            foreach($all_form_ids as $form_id){
                $demo_form = GFAPI::get_form( $form_id );
                foreach($demo_form['fields'] as $df){  
                    $field = GFAPI::get_field( $form_id, $df['id'] );
                    $field_type = isset($field->type) ? $field->type : '';
                    $field_label = isset($field->label) ? $field->label : '';
                    
                    // Quick skip fields
                    if(
                        isset($field->cssClass) && strpos($field->cssClass, 'question') !== false || 
                        isset($field->cssClass) && strpos($field->cssClass, 'question-optional') !== false ||
                        $field_type == 'html' || 
                        $field_label == 'Token' ||
                        $field_label == 'Source URL' || 
                        $field_label == 'Duplicate' ||
                        $field_label == 'uid hashed' || 
                        $field_label == ''
                    ){
                        continue;
                    }
                    
                    // Add to array
                    $all_form_demo_fields[$form_id][$field_label] = $field_label;
                }
            }
            
            // Get all shared headers for later
            if(count($all_form_ids) > 1){
                $args['all_forms_headers'] = call_user_func_array('array_intersect', $all_form_demo_fields);
            } else {
                $args['all_forms_headers'] = $all_form_demo_fields[$all_form_ids[0]];
            }
        }


        // Field population
        $fi = count($args['fields']) + 1;
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

            // Demographic Only Export Skips
            if($args['export_only_demographic'] && $args['all_forms_headers'] && !array_key_exists($field_label, $args['all_forms_headers'])){
                continue;
            }

            // Set field order
            $args['fields'][$fi] = array(
                'type' => $gf['type'],
                'key' => $gf['id'],
                'label' => $gf['label']
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

    // Referer filter
    if($args['export_screen_ref'] != ''):
        foreach($gform['fields'] as $field):
            if($field['label'] == 'Referer'){
                $search_criteria['field_filters'][] = array( 
                    'key' => $field['id'], 
                    'operator' => 'contains', 
                    'value' => $args['export_screen_ref']
                );
                break;
            }
        endforeach;
    endif;


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

        // Skip matching IP addresses if option is selected
        if($args['export_excluded_ips'] == 0 && in_array($entry['ip'], $args['excluded_ips'])){
            continue;
        }

        // Update Timeszone
        $row_date = new DateTime($entry['date_created']);
        $row_date->setTimezone($timezone);

        // Get all the fields
        foreach($entry as $e){

            foreach($args['fields'] as $ftk => $ftv){  

                // Default value
                $v = strval(rgar( $entry, $ftv['key'] ));
                
                // Check for a label if the value is just a number
                if(is_numeric($v)){
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

                /**
                 * Custom Overrides
                 */

                // Custom value overrides
                if(str_contains( strtolower($ftv['label']), 'age') || str_contains( strtolower($ftv['label']), 'edad')){       
                    if($v != '' && validateDate($v)){         
                        $v = "\"$v\""; // Add quotes to age ranges so Excel doesn't turn it into a date
                    }
                } 
                if (strpos($ftv['label'], 'favor marque ') !== false || strpos($ftv['label'], 'check this box') !== false){     
                    $v = $v ? 'Yes' : 'No'; // Display Yes/No instead of 1/blank
                }
                if($ftv['label'] == 'Screen ID' || $ftv['label'] == 'Screen'){     
                    $screen_title = get_the_title($v);
                    $title_removals = array( 'Test', 'Test de');
                    $title_removals_to = array( '', '');
                    $v = trim( html_entity_decode ( str_replace( $title_removals, $title_removals_to, $screen_title) ) );
                }

                // Put into our array
                $temp_array[ $ftv['label'] ] = $v;
                
            }

        }

        // Reorder our fields based on how they appear on the form, override some label names, and add the row
        foreach($args['fields'] as $k => $v){  
                
            switch($v['label']){
                case 'User Score':
                    $vlabel = 'Score';
                    break;
                case 'User Result':
                    $vlabel = 'Result';
                    break;
                case 'ipiden':
                    $vlabel = 'IP Identifier';
                    break;
                case 'Screen ID':
                    $vlabel = 'Screen';
                    break;
                case 'Enter Gender':
                    $vlabel = 'Gender Other';
                    break;
                default:
                    $vlabel = $v['label'];
                    break;
            }
            
            $csv_data[$i][$vlabel] = $temp_array[$v['label']]; 
        }            

        // Custom field assignments
        $csv_data[$i]['Created'] = $row_date->format("Y-m-d H:i:s");     
        $csv_data[$i]['Remote IP address'] = $entry['ip'];    
        $csv_data[$i]['uid'] = $temp_array['uid'] ? md5($temp_array['uid']) : '';

        $i++;
    }


    /**
     * Set next step variables and exit
     */
    $args['total'] = $total_count > 0 ? $total_count : 1; // Set to 1 just in case of no entries to avoid divide by 0 errors
    $max_pages = ceil($total_count / $page_size);
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

        $args['filename'] = $args['filename'] ? $args['filename'] : $form_slug.'--'.$args['export_screen_start_date'].'_'.$args['export_screen_end_date'].'--'.date('U').'.csv';
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
        if($args['page'] == 1){

            $csv_headers = [];

            // Create header array
            //foreach($args['fields'] as $k => $v){
            foreach($csv_data[array_key_first($csv_data)] as $k => $v){
                $csv_headers[] = $k;                    
                //$csv_headers[] = $v['label'];                
            }

            // Custom Reordering

            // Move items last
            moveArrayKeyToLast($csv_headers, array_search('Are you taking this test for yourself or for someone else?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Zip/Postal Code', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Age Range', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Gender', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Child\'s Gender', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Gender Other', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Please check this box if you identify as transgender.', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Please check this box if your child identifies as transgender.', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Race/Ethnicity', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Household Income', $csv_headers) );             
            moveArrayKeyToLast($csv_headers, array_search('Have you ever received treatment/support for a mental health problem?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Are you receiving treatment/support now?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Which of the following populations describes you?', $csv_headers) );             
            moveArrayKeyToLast($csv_headers, array_search('Is your child caring for someone with a mental or physical health condition?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Is your child caring for someone with a mental or physical health condition? (old)', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Are you caring for someone with a mental or physical health condition?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Caring For - Other', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Which of the following describe your experience of trauma?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Trauma - Other', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Trauma - other', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Think about your mental health test. What are the main things contributing to your mental health problems right now?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Think about your child\'s mental health test. What are the main things contributing to your child\'s mental health problems right now?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Mental Health Problems - Other', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Which of the following best describes your child\'s sexual orientation?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Which of the following best describes your child\'s sexual orientation? (old)', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Which of the following best describes your sexual orientation?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Sexual Orientation - Other', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Do you currently have health insurance?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Does your child have any of the following general health conditions?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Do you have any of the following general health conditions?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search("If 'Other' please specify (for general health conditions)", $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('State', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Do you live in the United States or another country?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('What country do you live in?', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Screen', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Score', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Sub Score 1', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Sub Score 2', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Sub Score 3', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Result', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('Referer', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('IP Identifier', $csv_headers) ); 
            moveArrayKeyToLast($csv_headers, array_search('uid', $csv_headers) ); 
                        
            // Move items first
            unset($csv_headers[ array_search('Created', $csv_headers) ]);
            $csv_headers = array_values($csv_headers);
            unset($csv_headers[ array_search('Remote IP address', $csv_headers) ]);
            $csv_headers = array_values($csv_headers);
            array_unshift($csv_headers, "Created", "Remote IP address");

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

// Array Helpers
function moveArrayByKey(&$array, $a, $b) {
    $out = array_splice($array, $a, 1);
    array_splice($array, $b, 0, $out);
}

function moveArrayKeyToLast(&$array, $key){
    if(isset($array[$key])){
        $v = $array[$key];
        unset($array[$key]);
        $array[$key] = $v;
    }
    return $array;
}