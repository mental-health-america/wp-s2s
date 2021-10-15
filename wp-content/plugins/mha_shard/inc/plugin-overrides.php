<?php

/**
 * Plugin Overrides
 * General overrides for plugin specific changes.
 */

 
/**
 * Add an ACF Options page for site configuration
 */
if( function_exists('acf_add_options_page') ) {
	$settings = array(
		'page_title' => __('MHA Global Options'),
		'icon_url' => '/wp-content/themes/mha_s2s/assets/images/mha_icon.png',
	);
	acf_add_options_page( $settings );	
}


/**
 * Yoast Comment Removal
 */

if (defined('WPSEO_VERSION')){
	add_action('get_header',function (){ ob_start(function ($o){
	return preg_replace('/^<!--.*?[Y]oast.*?-->$/mi','',$o); }); });
	add_action('wp_head',function (){ ob_end_flush(); }, 999);
}
add_filter( 'wpseo_debug_markers', '__return_false' );


/**
 * Make sure Yoast is always on the bottom of pages
 */
function yoastToBottom() {
    return 'low';
}
add_filter( 'wpseo_metabox_prio', 'yoastToBottom');


/** 
 * oEmbed Override
 */

add_filter('embed_oembed_html', function ($html, $url, $attr, $post_id) {
	if(strpos($html, 'youtube.com') !== false || strpos($html, 'youtu.be') !== false || strpos($html, 'vimeo.com') !== false){
		return '<div class="responsive-video">' . $html . '</div>';
	} else {
		return $html;
	}
}, 10, 4);
