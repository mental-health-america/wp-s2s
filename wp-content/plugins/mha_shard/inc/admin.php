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

function remove_dashboard_meta() {
    remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');//since 3.8    
    remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
    remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
    remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
}
add_action( 'admin_init', 'remove_dashboard_meta' );

/** Override for extra admin menu item hiding */
function custom_admin_css_for_partner_role() {
    $current_user = wp_get_current_user();
    if (in_array('partner', $current_user->roles)) {
        echo '<style>
            #toplevel_page_acf-options-mha-redirects,
            #toplevel_page_acf-options-mha-global-options { display: none !important; }
        </style>';
    }
}
add_action('admin_head', 'custom_admin_css_for_partner_role');


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