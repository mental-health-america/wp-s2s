<?php
/**
 * Direct Reading Path
 * Just redirect to the first path item
 */

get_header();

$path_id = get_the_ID();
if(get_field('path')){
	$path = get_field('path');
	$url = add_query_arg('pathway', $path_id, get_the_permalink($path[0]['article']));
	echo $url;

	wp_redirect( $url );
	exit;
}

get_footer();
