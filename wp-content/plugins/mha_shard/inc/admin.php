<?php

// Custom Admin Javascript
function mha_admin_scripts($hook) {
    wp_enqueue_script('mythril_shard_scripts', plugin_dir_url(__FILE__) . 'js/mha_admin.js', array(), '1.0');
    wp_enqueue_style( 'mythril_shard_scripts', plugin_dir_url(__FILE__) . 'css/mythril_shard.css', array(), '1.1' );
}

add_action('admin_enqueue_scripts', 'mha_admin_scripts');

// Disable comment button on toolbar
function remove_comments(){
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
}
add_action( 'wp_before_admin_bar_render', 'remove_comments' );

// Disabmle xmlrpc.php access
add_filter( 'xmlrpc_enabled', '__return_false' );


// Dynamically Populate the "Demographic Based Next Steps" Key Field
/*
function my_acf_load_field( $field ) {
    
    // reset choices
    $field['choices'] = array();    

    // Get demo labels
    $forms = GFAPI::get_forms();  
    $demo_labels = [];
    foreach($forms as $form){
        foreach($form['fields'] as $field){
            if(strpos($field->cssClass, 'optional') !== false){
                $demo_labels[] = $field->label;
            }
        }
    }
    $demo_labels = array_unique($demo_labels);
    $demo_labels = array_values($demo_labels);
    
    foreach( $demo_labels as $choice ) {            
        $field['choices'] = $demo_labels;            
    }   

    return $field;
    
}

add_filter('acf/load_field/name=key_test', 'acf_load_screen_field_choices');
*/