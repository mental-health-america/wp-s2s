<?php

// Plugins
require_once __DIR__ . '/vendor/autoload.php';

add_action( 'wp_ajax_mha_export_screen_data', 'mha_export_screen_data' );
function mha_export_screen_data(){
    
	// General variables
    $result = array();
    

    echo json_encode($result);
    exit();   

}