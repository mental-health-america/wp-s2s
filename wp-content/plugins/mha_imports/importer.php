<?php

// Plugins
require_once __DIR__ . '/vendor/autoload.php';
use League\Csv\CharsetConverter;
use League\Csv\Reader;
use League\Csv\Statement;

// Enqueing Scripts
add_action('init', 'mhaImportScripts');
function mhaImportScripts() {
    if(current_user_can('manage_options')){
        wp_enqueue_script('process_mhaImporters', plugin_dir_url(__FILE__) . 'mha_imports.js', array('jquery'), time(), true );
        wp_localize_script('process_mhaImporters', 'do_mhaImports', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
    }
}


/**
 * CSV Upload
 */
add_action( 'wp_ajax_mhaImporterUploader', 'mhaImporterUploader' );
function mhaImporterUploader(){
    
    // General Vars
    $result = [];

    // Confirm WP file upload is available here
    /*
    if (!function_exists('wp_handle_upload')) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
    }
    */

    // Upload the file
    $filename = date('U').'_'.$_FILES['file']['name'];
    //$uploadedfile = $_FILES['import_provider_file'];
    //$movefile = wp_handle_upload($uploadedfile, array('test_form' => false, 'mimes' => array('csv' => 'text/csv')));
    $uploadedfile = plugin_dir_path(__FILE__)."/tmp/".$filename;
    $movefile = move_uploaded_file( $_FILES['file']['tmp_name'], $uploadedfile );

    if ($movefile && !isset($movefile['error'])) {
        $result['file'] = urlencode($filename); 
        $result['page'] = 0;
        $result['error'] = false;
    } else {
        $result['file'] = false;
        $result['error'] = $movefile['error'];
    }
    
    echo json_encode($result);
    die();

}

function convert_smart_quotes($string) {
    $search = array(chr(145),
                    chr(146),
                    chr(147),
                    chr(148),
                    chr(151));
 
    $replace = array("'",
                     "'",
                     '"',
                     '"',
                     '-');
 
    return str_replace($search, $replace, $string);
} 


add_action( 'wp_ajax_mhaImporterLooper', 'mhaImporterLooper' );
function mhaImporterLooper( $data = null ) {

    // Defaults
    $pager = 50;

    // Initial data
    if($data){
        $data = $data;
        $filename = urldecode($data['file']);
        $offset = $data['next_page'] * $pager;
    } else {
        parse_str($_POST['data'], $data);  
        $offset = $data['next_page'] * $pager;
        $filename = urldecode($data['file']);
    }

    // Load CSV and get data
    $csv = Reader::createFromPath(__DIR__.'/tmp/'.$filename, 'r');
    $csv->setHeaderOffset(0);

    //$records = Statement::create()->process($csv);
    //$records->getHeader();
    //$header = $csv->getHeader();

    $records = $csv->getRecords();
    $total_records = count($records);
    $max_pages = ceil($total_records / $pager);

    // Loop through data
    //$record_selection = Statement::create()->offset($offset)->limit($pager);
    $data['log'] = '';
    foreach ($records as $record) {
        
        $post_content = mb_convert_encoding($record['post_content'], "HTML-ENTITIES", 'UTF-8');
        
        // Create Article
        $new_article = array(
            'post_title'    =>  $record['post_title'],
            'post_status'   =>  $record['post_status'],
            'post_author'   =>  $record['post_author'],
            'post_type'     =>  $record['post_type'],
            'post_content'  =>  $post_content,
            'post_excerpt'  =>  $record['post_excerpt'],
            'post_status'   =>  $record['post_status']
        );
        $pid = wp_insert_post($new_article, 1);

        // Taxonomy
        wp_set_object_terms( $pid, explode(',', sanitize_text_field($record['post_tag'])), 'post_tag', false );
        wp_set_object_terms( $pid, explode(',', sanitize_text_field($record['related_condition'])), 'condition', false );
        wp_set_object_terms( $pid, explode(',', sanitize_text_field($record['related_ages'])), 'age_group', false );

        // Primary Condition
        $primary_condition = get_term_by('name', sanitize_text_field($record['primary_condition']), 'condition');
        update_field('primary_condition', $primary_condition->term_id, $pid);

        // Custom Fields
        
        update_field('featured',                intval($record['featured']), $pid);
        update_field('type',                    explode(',', $record['type']), $pid);
        update_field('area_served',             explode(',', $record['area_served']), $pid);
        update_field('service_type',            explode(',', $record['service_type']), $pid);        
        update_field('diy_type',                explode(',', $record['diy_type']), $pid);
        update_field('diy_issue',               explode(',', $record['diy_issue']), $pid);
        update_field('treatment_type',          explode(',', $record['treatment_type']), $pid);
        update_field('whole_state',             sanitize_text_field($record['whole_state']), $pid);
        update_field('hide_locations',          intval($record['hide_locations']), $pid);
        update_field('introductory_content',    mb_convert_encoding($record['introductory_content'], "HTML-ENTITIES", 'UTF-8'), $pid);
        update_field('featured_link',           esc_url_raw($record['featured_link']), $pid);
        update_field('featured_link_text',      sanitize_text_field($record['featured_link_text']), $pid);
        update_field('customer_service_email',           sanitize_email($record['customer_service_email']), $pid);
        update_field('customer_service_contact_form',    esc_url_raw($record['customer_service_contact_form']), $pid);
        update_field('customer_service_phone',           sanitize_text_field($record['customer_service_phone']), $pid);
        update_field('pricing_information',     mb_convert_encoding($record['pricing_information'], "HTML-ENTITIES", 'UTF-8'), $pid);
        update_field('privacy_information',     mb_convert_encoding($record['privacy_information'], "HTML-ENTITIES", 'UTF-8'), $pid);
        update_field('disclaimer',              mb_convert_encoding($record['disclaimer'], "HTML-ENTITIES", 'UTF-8'), $pid);
        update_field('all_conditions',          intval($record['all_conditions']), $pid);
        update_field('point_of_contact_name',   sanitize_text_field($record['point_of_contact_name']), $pid);
        update_field('point_of_contact_title',  sanitize_text_field($record['point_of_contact_title']), $pid);
        update_field('point_of_contact_email',  sanitize_email($record['point_of_contact_email']), $pid);
        update_field('point_of_contact_phone',  sanitize_text_field($record['point_of_contact_phone']), $pid);


        // Featured Image
        if($record['featured_image'] != ''){
            $image_id = attach_remote_image_to_post(esc_url_raw($record['featured_image']), $pid, '');
            if($image_id){ set_post_thumbnail( $pid, $image_id ); }
        }

        // Locations
        $location_max = 50;
        $location_counter = 1;
        if($record['location_address_1'] != ''){
            $location_data = [];
            while ($location_counter < $location_max) {
                if($record['location_address_'.$location_counter]){
                    $location_data[] = array(
                        "address"	=> sanitize_text_field($record['location_address_'.$location_counter]),
                        "city"	    => sanitize_text_field($record['location_city_'.$location_counter]),
                        "state"	    => sanitize_text_field($record['location_state_'.$location_counter]),
                        "phone"	    => sanitize_text_field($record['location_phone_'.$location_counter])
                    );
                    $location_counter++;
                } else {
                    break;
                }
            }
            update_field( 'field_5fd3ef47dab98', $location_data, $pid );
        }

        // Accolades
        $accolades_max = 50;
        $accolades_counter = 1;
        if($record['accolades_text_1'] != ''){
            $accolades_data = [];
            while ($accolades_counter < $accolades_max) {
                if($record['accolades_text_'.$accolades_counter]){
                    $accolades_data[] = array(
                        "text"      => sanitize_text_field($record['accolades_text_'.$accolades_counter]),
                        "source"    => sanitize_text_field($record['accolades_source_'.$accolades_counter])
                    );
                    $accolades_counter++;
                } else {
                    break;
                }
            }
            update_field( 'field_5fea327fa3cc0', $accolades_data, $pid );
        }

        // Log to export
        $data['log'] .= '<div>Article #'.$pid.' Created - '.sanitize_text_field($record['post_title']).'</div>';

    }

    // Prep for next round
    $data['next_page'] = $data['next_page'] + 1;
    $data['percent'] = round( ( ($data['next_page'] / $max_pages) * 100 ), 2 );

    if( ($data['next_page'] + 1) >= $max_pages){
        $data['next_page'] = '';
    }

    // Return our responses
    echo json_encode($data);
    exit();
}



/**
 * Unique CTA Code Importer
 */
add_action( 'wp_ajax_mhaCtaCodeImporter', 'mhaCtaCodeImporter' );
function mhaCtaCodeImporter(){

    // Defaults
    $defaults = array(
        'file'                       => null,
        'page'                       => 0,
        'max_pages'                  => null,
        'log'                        => '',
        'percent'                    => 0,
    );      
    parse_str( $_POST['data'], $data);
    $args = wp_parse_args( $data, $defaults );  
    
    // General Vars
    $result = $args;
    
    // Load CSV and get data
    $filepath = WP_PLUGIN_DIR . '/mha_imports/tmp/'.$result['file'];
    $csv = Reader::createFromPath($filepath, 'r');
    $csv->setHeaderOffset(0);
    $records = iterator_to_array($csv->getRecords());

    // Pagination
    $pager = 10000;
    if(!$result['max_pages']){
        $total_records = count($records);
        $result['max_pages'] = ceil($total_records / $pager);
    }
    $start_records = $args['page'] * $pager;
    $end_records = $start_records + $pager;

    // Loop data
    $i = 0;

    global $wpdb;
    $record_uploads = [];
    if($records){
        foreach ($records as $record) {
            if($i > $end_records){
                break;
            }
            if($i >= $start_records && $i < $end_records){
                //$record_uploads[] = $record;
                $wpdb->insert( 'cta_codes', $record); 
            }
            $i++;
        }
    } else {
        // Return our responses
        $result['error'] = 'No records to upload.';
        echo json_encode($result);
        exit();
    }

    //$result['records'] = $record_uploads;

    //$result['record_uploads'] = $record_uploads; // Debug
    $result['page'] = $result['page'] + 1; // Next Page
    $max_pages = ($result['max_pages'] && $result['max_pages'] > 0) ? $result['max_pages'] : 1;
    $result['percent'] = round( ( ( intval($result['page']) / intval($max_pages)) * 100 ), 2 ); // Percent complete
        
    // Return our responses
    echo json_encode($result);
    exit();
}