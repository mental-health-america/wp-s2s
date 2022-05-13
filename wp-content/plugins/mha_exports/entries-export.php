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

add_action( 'wp_ajax_mha_export_screen_data', 'mha_export_screen_data' );
function mha_export_screen_data(){

	// General variables
    $timezone = new DateTimeZone('America/New_York');
	
    // Prep our post data args
    if(intval($_POST['start']) == 1){

        // For the first pass, set our defaults
        $defaults = array(
            'form_id'                   => null,
            'nonce'                     => null,
            'export_screen_start_date'  => date('Y-m', strtotime('now - 1 month')).'-01',
            'export_screen_end_date'    => date('Y-m-t', strtotime('now - 1 month')),
            'export_screen_ref'         => null,
            'export_excluded_ips'       => 0,
            'excluded_ips'              => array_map('trim', explode(PHP_EOL, get_field('ip_exclusions', 'options'))),
            'field_labels'              => '',
            'page'                      => 1,
            'fields'                    => array(),
            'filename'                  => null,
            'total'                     => null,
            'max'                       => null,
            'percent'                   => null,
            'next_page'                 => null,
            'elapsed_start'             => null,
            'elapsed_end'               => null,
            'total_elapsed_time'        => null,
            'download'                  => null
        );    
        parse_str( $_POST['data'], $data);
        $args = wp_parse_args( $data, $defaults );  
        
    } else {        

        // For loops, just use the data given
        $args = stripslashes_deep($_POST['data']);
        
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
    $gform = GFAPI::get_form( $args['form_id'] );
    $form_slug = sanitize_title_with_dashes($gform['title']);
    
    if(empty($args['fields'])){

        // Manual additional field
        $args['fields'][0] = array(
            'type' => 'mha_custom',
            'key' => 'Date',
            'label' => 'Date'
        );          
        $args['fields'][1] = array(
            'type' => 'mha_custom',
            'key' => 'User IP',
            'label' => 'User IP'
        );

        // Auto fields
        $fi = count($args['field']) + 1;
        foreach($gform['fields'] as $gf){  
            $field = GFAPI::get_field( $args['form_id'], $gf['id'] );
            if(
                isset($field->type) && $field->type == 'html' || 
                isset($field->label) && $field->label == 'Screen ID' || 
                isset($field->label) && $field->label == 'Source URL' || 
                isset($field->label) && $field->label == ''){
                continue; // Skip these columns
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
    $entries = GFAPI::get_entries( $args['form_id'], $search_criteria, null, $paging, $total_count );
    $search_criteria['total_count_real'] = $total_count;
    $csv_data = [];
    $i = 0;

    // Parse through form entries
    foreach($entries as $entry){
        $temp_array = [];
        $spam_check = 0;

        // Skip matching IP addresses if option is selected
        if($args['export_excluded_ips'] && in_array($entry['ip'], $args['excluded_ips'])){
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
                    if($ftv['model']){
                        foreach($ftv['model'] as $m){
                            if($m['value'] == $v){
                                $v = $m['text'];
                            }
                        } 
                    }
                }

                // Checkboxes are split weirdly so...
                if($ftv['inputs']){
                    $entry_inputs = array();
                    foreach($ftv['inputs'] as $fin){
                        if($entry[$fin]){
                            $entry_inputs[] = $entry[$fin];
                        }
                    }
                    $v = implode('|',$entry_inputs);
                }

                
                // Custom value overrides
                if($ftv['label'] == 'Age Range' && validateDate($v)){                
                    $v = "\"$v\""; // Add quotes to age ranges with a dash so excel doesn't turn it into a date
                }
                if($ftv['label'] == 'Please check this box if you identify as transgender.'){     
                    $v = $v ? 'Yes' : 'No';
                }

                // Put into our array
                $temp_array[ $ftv['label'] ] = $v;
                
            }

        }

        // Reorder our fields based on how they appear on the form and add the row
        foreach($args['fields'] as $k => $v){  
            $csv_data[$i][$v['label']] = $temp_array[$v['label']]; 
        }            

        // Custom field assignments
        $csv_data[$i]['Date'] = $row_date->format("Y-m-d H:i:s");     
        $csv_data[$i]['User IP'] = $entry['ip'];    
        $csv_data[$i]['uid hashed'] = $temp_array['uid'] ? md5($temp_array['uid']) : '';

        $i++;
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
    
    if(count($csv_data) == 0){
        $args['download'] = '#';   
        $args['filename'] = '';
        $args['elapsed_end'] = time();
        echo json_encode($args);
        exit();           
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

            

            // All forms override
            if($args['all_forms']) {
                $args['all_forms_continue'] = 1;
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

    // All Forms Continuation
    if(isset($args['all_forms']) && count($args['all_forms']) == 0){    
        $args['all_forms'] = null;
        $args['all_forms_continue'] = null;
    }
    
    $args['page'] = $args['next_page']; // Set the loops next page

    echo json_encode($args);
    exit();   

}