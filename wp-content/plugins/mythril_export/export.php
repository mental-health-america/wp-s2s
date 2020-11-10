<?php
add_action( 'admin_post_mythrilexport_export', 'mythrilexport_export' );

function mythrilexport_export() {
    global $wpdb;
    
    if (wp_verify_nonce( $_GET['snonce'], 'mythrilexport' ) ) {
        global $wpdb;
        global $posts;

        $file = 'Comments';
        date_default_timezone_set('America/New_York');
        $filename = $file."_". date("Y-m-d H:i:s"). ".csv";
        header("Content-Disposition: attachment; filename=\"$filename\""); 
        header('Content-Type: text/csv; charset=utf-8');
            
        $cols = '';

        // Sign Up
        $columns = array(
            'Date',
            'Full Name',
            'Email',
            'Zip',
            'Source',
            'Custom Source'
        );
        foreach($columns as $column){
            $cols .= $column ."\t";
        }
        echo $cols."\r\n";
        $obj = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    date, 
                    full_name, 
                    email,
                    zip,
                    source,
                    custom_source
                FROM mythril_signup 
                ORDER BY date DESC"), ARRAY_A);

        foreach($obj as $row){
            $row_data = $row['date']."\t".$row['full_name']."\t".$row['email']."\t".$row['zip']."\t".$row['source']."\t".$row['custom_source']."\t";
            echo $row_data."\r\n";
        }
        
        exit();
    } else { 
        die('Something went wrong. Please try again.');
    }
	
    
}