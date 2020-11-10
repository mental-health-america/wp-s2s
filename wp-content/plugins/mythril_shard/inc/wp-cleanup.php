<?php

/**
 * Wordpress Cleanup
 * General functions to clean up Wordpress output (e.g. removing unnecessary tags like emoji CSS)
 */


// General Extraneous Wordpress Stuff Clean Up
function remove_header_info() {
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'short_link'); // Removes the index link
	remove_action('wp_head', 'parent_post_rel_link'); // Removes the prev link
	remove_action('wp_head', 'start_post_rel_link'); // Removes the start link
	remove_action('wp_head', 'wp_generator'); // Removes the prev link
	remove_action('wp_head', 'feed_links_extra', 3 ); // This is the main code that removes unwanted RSS Feeds
	remove_action('wp_head', 'feed_links', 2 ); // Removes Post and Comment Feeds
	remove_action('wp_head', 'index_rel_link' ); // Removes the index link
	remove_action('wp_head', 'adjacent_posts_rel_link'  ); // Remove relational links for the posts adjacent to the current post.
	remove_action('wp_head', 'print_emoji_detection_script', 7 ); // Remove Emoji Detection Script
	remove_action('wp_print_styles', 'print_emoji_styles' ); // Remove Emoji Styles
    remove_action('rest_api_init', 'wp_oembed_register_route'); // Remove the REST API endpoint.
	remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10); // Turn off oEmbed auto discovery. Don't filter oEmbed results.
	remove_action('wp_head', 'wp_oembed_add_discovery_links'); // Remove oEmbed discovery links.
	remove_action('wp_head', 'wp_oembed_add_host_js'); // Remove oEmbed-specific JavaScript from the front-end and back-end.	
}

// Generator Removal
add_filter('the_generator', 'wpb_remove_version');
add_action('init', 'remove_header_info');
function wpb_remove_version() {
	return '';
}

// Comment Style Stuff Removal
function mythril_remove_recent_comments_style() {  
	global $wp_widget_factory;  
	remove_action( 'wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ) );  
}  
add_action( 'widgets_init', 'mythril_remove_recent_comments_style' );