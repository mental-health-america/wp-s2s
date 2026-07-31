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
        wp_localize_script('process_mhaImporters', 'do_mhaImports', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'mha_imports_ajax' ),
        ) );
    }
}


/**
 * Authorization guard for the import AJAX endpoints.
 *
 * A `wp_ajax_` hook only requires the request to be authenticated, not authorized, so
 * every callback has to check capability itself. Sends a 403 and exits when the request
 * is not permitted.
 */
function mha_imports_verify_ajax_request() {

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'error' => 'You do not have permission to run this import.' ), 403 );
    }

    if ( ! check_ajax_referer( 'mha_imports_ajax', 'nonce', false ) ) {
        wp_send_json_error( array( 'error' => 'Security check failed. Refresh the page and try again.' ), 403 );
    }
}


/**
 * Directory holding in-progress CSV uploads.
 */
function mha_imports_tmp_dir() {
    return plugin_dir_path( __FILE__ ) . 'tmp/';
}


/**
 * Create the tmp directory and its access guards if they are missing.
 *
 * The guards are written from here rather than committed because `tmp/` is gitignored,
 * so anything placed in it by hand never reaches the server. The .htaccess only applies
 * on Apache; on nginx the equivalent rule has to be set at the server level.
 *
 * @return bool False when the directory could not be created.
 */
function mha_imports_prepare_tmp_dir() {

    $dir = mha_imports_tmp_dir();

    if ( ! wp_mkdir_p( $dir ) ) {
        return false;
    }

    if ( ! file_exists( $dir . 'index.php' ) ) {
        file_put_contents( $dir . 'index.php', "<?php // Silence is golden." . PHP_EOL );
    }

    if ( ! file_exists( $dir . '.htaccess' ) ) {
        $rules  = "# Staged CSV imports are never meant to be fetched over HTTP." . PHP_EOL;
        $rules .= "<IfModule mod_authz_core.c>" . PHP_EOL;
        $rules .= "    Require all denied" . PHP_EOL;
        $rules .= "</IfModule>" . PHP_EOL;
        $rules .= "<IfModule !mod_authz_core.c>" . PHP_EOL;
        $rules .= "    Order allow,deny" . PHP_EOL;
        $rules .= "    Deny from all" . PHP_EOL;
        $rules .= "</IfModule>" . PHP_EOL;
        file_put_contents( $dir . '.htaccess', $rules );
    }

    return true;
}


/**
 * Resolve an uploaded CSV back to a real path inside the tmp directory.
 *
 * The filename round-trips through the browser between the upload request and each paged
 * import request, so on the way back in it is untrusted and must not be able to escape
 * the tmp directory.
 *
 * @return string|false
 */
function mha_imports_resolve_tmp_file( $filename ) {

    // basename() drops any directory portion, and the .csv requirement keeps the lookup
    // from reaching anything else that happens to live in the tmp directory.
    $filename = basename( (string) $filename );

    if ( ! preg_match( '/^[A-Za-z0-9._-]+\.csv$/', $filename ) ) {
        return false;
    }

    $path = mha_imports_tmp_dir() . $filename;

    return file_exists( $path ) ? $path : false;
}


/**
 * CSV Upload
 */
add_action( 'wp_ajax_mhaImporterUploader', 'mhaImporterUploader' );
function mhaImporterUploader(){

    mha_imports_verify_ajax_request();

    // General Vars
    $result = [];

    if ( empty( $_FILES['file'] ) || empty( $_FILES['file']['tmp_name'] ) || ! is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
        $result['file']  = false;
        $result['error'] = 'No file was received. Please choose a CSV and try again.';
        echo json_encode($result);
        die();
    }

    if ( ! empty( $_FILES['file']['error'] ) ) {
        $result['file']  = false;
        $result['error'] = 'The upload did not complete (error code '.intval( $_FILES['file']['error'] ).').';
        echo json_encode($result);
        die();
    }

    if ( strtolower( pathinfo( $_FILES['file']['name'], PATHINFO_EXTENSION ) ) !== 'csv' ) {
        $result['file']  = false;
        $result['error'] = 'Only .csv files can be imported.';
        echo json_encode($result);
        die();
    }

    // The stored name is generated rather than taken from the client, and the extension is
    // forced, so an upload can never land as an executable file in this directory.
    $filename     = date('U').'_'.wp_generate_password( 12, false ).'.csv';
    $uploadedfile = mha_imports_tmp_dir().$filename;

    if ( ! mha_imports_prepare_tmp_dir() ) {
        $result['file']  = false;
        $result['error'] = 'The import directory is not writable.';
        echo json_encode($result);
        die();
    }

    if ( move_uploaded_file( $_FILES['file']['tmp_name'], $uploadedfile ) ) {
        $result['file'] = urlencode($filename); 
        $result['page'] = 0;
        $result['error'] = false;
    } else {
        $result['file'] = false;
        $result['error'] = 'The uploaded file could not be saved.';
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

    mha_imports_verify_ajax_request();

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

    $filepath = mha_imports_resolve_tmp_file( $filename );
    if ( ! $filepath ) {
        echo json_encode( array( 'error' => 'The import file could not be found. Please upload it again.' ) );
        exit();
    }

    // Load CSV and get data
    $csv = Reader::createFromPath($filepath, 'r');
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

    mha_imports_verify_ajax_request();

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
    $filepath = mha_imports_resolve_tmp_file( urldecode( $result['file'] ) );
    if ( ! $filepath ) {
        $result['error'] = 'The import file could not be found. Please upload it again.';
        echo json_encode($result);
        exit();
    }
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