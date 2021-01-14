<?php

// Custom ADmin Javascript

function mha_admin_scripts($hook) {
    wp_enqueue_script('my_custom_script', plugin_dir_url(__FILE__) . '/js/mha_admin.js');
}

add_action('admin_enqueue_scripts', 'mha_admin_scripts');