<?php

// Custom Admin Javascript
function mha_admin_scripts($hook) {
    wp_enqueue_script('my_custom_script', plugin_dir_url(__FILE__) . '/js/mha_admin.js');
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