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
    $filename = $_FILES['file']['name'];
    //$uploadedfile = $_FILES['import_provider_file'];
    //$movefile = wp_handle_upload($uploadedfile, array('test_form' => false, 'mimes' => array('csv' => 'text/csv')));
    $uploadedfile = plugin_dir_path(__FILE__)."/tmp/".$filename;
    $movefile = move_uploaded_file( $_FILES['file']['tmp_name'], $uploadedfile );

    if ($movefile && !isset($movefile['error'])) {
        $result['file'] = $filename;
        $result['error'] = false;
    } else {
        $result['file'] = false;
        $result['error'] = $movefile['error'];
    }
    
    echo json_encode($result);
    die();

}


function mhaImporterLooper() {

    //load the CSV document from a stream
    $stream = fopen('/path/to/your/csv/file.csv', 'r');
    $csv = Reader::createFromStream($stream);
    $csv->setDelimiter(';');
    $csv->setHeaderOffset(0);

    //build a statement
    $stmt = Statement::create()
        ->offset(10)
        ->limit(25);

    //query your records from the document
    $records = $stmt->process($csv);
    foreach ($records as $record) {
        //do something here
    }
}