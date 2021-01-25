<?php

/**
 * General Helper Functions
 */


/**
 * Debugging Helpers
 */
function pre($arr){
	echo '<pre>';
	print_r($arr);
	echo '</pre>';
}


/**
 * Get ipiden value
 */
function get_ipiden(){
	return md5($_SERVER['REMOTE_ADDR']);
}

/**
 * Replaces "[...]" (appended to automatically generated excerpts) with ... and a 'Continue reading' link.
 */
function mha_shard__excerpt_more( $url ) {

    $link = esc_url_raw($url);
    
	if ( is_admin() ) {
		return $link;
	}

	$link = sprintf( '<p class="link-more"><a href="%1$s" class="more-link">%2$s</a></p>',
		esc_url( get_permalink( get_the_ID() ) ),
		sprintf( __( 'Continue reading<span class="screen-reader-text"> "%s"</span>', 'mha_shard_' ), get_the_title( get_the_ID() ) )
	);
	return ' &hellip; ' . $link;
}
add_filter( 'excerpt_more', 'mha_shard__excerpt_more' );


/**
 * Custom short excerpt
 */
function short_excerpt($limit = 50, $separator = '...') {

    $excerpt = explode(' ', get_the_excerpt(), $limit);

    // Excerpt Generator
    if (count($excerpt)>=$limit) {
        array_pop($excerpt);
        $excerpt = implode(" ",$excerpt).$separator;
    } else {
        $excerpt = implode(" ",$excerpt);
    }   
    $excerpt = preg_replace('`\[[^\]]*\]`','',$excerpt);
    return $excerpt;
}


/*
 * Flatten string substituting all spaces and punctuation
 */
function flatStr($str,$seperator="_") {
	$str = preg_replace('/[^\w]+/', $seperator, $str);		
	$str = preg_replace('/[\W]+/', $seperator, $str);
	$str = strtolower($str);
	return $str;
}


/**
 * Get YouTube poster image
 */
function getYTImage($ytlink){
	$link_test = strstr($ytlink, 'https://youtu.be');

	if($link_test){
		$vid_url = rtrim($ytlink, '/');
	} else {
		$unformatted_url = parse_url($ytlink,PHP_URL_QUERY);
		parse_str($unformatted_url,$query);
		$vid_url = 'https://youtu.be/'.$query['v'];
	}

	$url_segs = explode("/",$vid_url);
	$url_segs_len = count($url_segs);

	$yt_vid_id = $url_segs[$url_segs_len - 1];
	$thumb = 'https://img.youtube.com/vi/'.$yt_vid_id.'/0.jpg';

	return $thumb;
}


/**
 * Get source var from URL
 */
function getSources($type=''){
	$customSource = '';
	if(isset($_GET['src'])){
	    $customSource = filter_var($_GET['src'], FILTER_SANITIZE_STRING); 
	}
	else if(isset($_GET['source'])){
	    $customSource = filter_var($_GET['source'], FILTER_SANITIZE_STRING); 
	}
	$link_src = $customSource != '' ? '?src='.$customSource : '';
	if($type=='link'){
		echo $link_src;
	}else {
		echo $customSource;
	}
}


/**
 * Create a Twitter share link
 */
function formatTwitter($title,$link){
	$title = str_replace('&#8220;', '"', $title);
	$title = str_replace('&#8221;', '"', $title);
	$title = str_replace('&#8217;',"'", $title);
	$title = str_replace('&#8218;',"'", $title);
	$content = strlen($title) > 280 ? substr($title,0,97).'...' : $title.' ';
	$content = urlencode($content);
	$url = urlencode($link);
	return 'https://twitter.com/intent/tweet?text='.$content.' '.$url;
} 

/**
 * CSV Parser Helpers
 */
function ck_csv($file) {
	$row = 0; 
	ini_set('auto_detect_line_endings', true);
	if (($handle = fopen($file, "r")) !== FALSE) {
	    while (($data = fgetcsv($handle, "," )) !== FALSE) {
		   $num = count($data);
		   for ($c=0; $c <= $num; $c++) { 
			   if( ($c+1) % ($num+1) == 0 )
				$row++; 
			   else {			
				if($row==0) 
					$data_row[$row][$c] = $data[$c];
				else 
					$data_row[$row][$data_row[0][$c]] = $data[$c];
			   }
		   }	  
	    }
	    fclose($handle);
	    return $data_row;
	} 
} 

function ck_csv_multi($file) {
	$row = 0; 
	ini_set('auto_detect_line_endings', true);
	if (($handle = fopen($file, "r")) !== FALSE) {
	    while (($data = fgetcsv($handle, "," )) !== FALSE) {
		   $num = count($data);
		   $k = 0;
		   for ($c=0; $c <= $num; $c++) { 
			   if( ($c+1) % ($num+1) == 0 )
				$row++; 
			   else {			
				if($row==0) 
					$data_row[$row][$c] = $data[$c];
				else {
					if($data_row[0][$c] == 'CID' || $data_row[0][$c] == 'Candidate Name')
						$data_row[$row][$data_row[0][$c]] = $data[$c];
					else{
						$data_row[$row][$data_row[0][$c].'_'.$k] = $data[$c];
						$k++;
					}
				}		
			   }
		   }	  
	    }
	    fclose($handle);
	    return $data_row;
	} 
} 


/**
 * Simple javascript detection
 */
function mha_shard__javascript_detection() {
	echo "<script>(function(html){html.className = html.className.replace(/\bno-js\b/,'js')})(document.documentElement);</script>\n";
}
add_action( 'wp_head', 'mha_shard__javascript_detection', 0 );