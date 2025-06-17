<?php

/**
 * Plugin Overrides
 * General overrides for plugin specific changes.
 */

 
/**
 * Add an ACF Options page for site configuration
 */
if( function_exists('acf_add_options_page') ) {

	// General Options
	acf_add_options_page( array(
		'page_title' => __('MHA Global Options'),
		'icon_url' => '/wp-content/themes/mha_s2s/assets/images/mha_icon.png',
	));	

	// General Options
	acf_add_options_page( array(
		'page_title' => __('MHA Redirects'),
		'icon_url' => 'dashicons-networking',
	));	

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


/**
 * TranslatePress Integration
 * Mark pages with espanol ACF field as Spanish pages
 */
function mha_mark_spanish_pages($post_id) {
    // Only proceed if TranslatePress is active
    if (!function_exists('trp_get_languages')) {
        return;
    }

    // Get the espanol field value
    $espanol = get_field('espanol', $post_id);
    
    if ($espanol) {
        // Get the Spanish language code from TranslatePress
        $languages = trp_get_languages();
        $spanish_code = '';
        
        // TranslatePress returns an array of language objects
        foreach ($languages as $language) {
            if (isset($language['name']) && strtolower($language['name']) === 'spanish') {
                $spanish_code = $language['language_code'];
                break;
            }
        }
        
        if ($spanish_code) {
            // Mark the post as Spanish
            update_post_meta($post_id, 'trp_language', $spanish_code);
        }
    } else {
        // If espanol is not checked, remove the Spanish language mark
        delete_post_meta($post_id, 'trp_language');
    }
}
add_action('acf/save_post', 'mha_mark_spanish_pages', 20);
