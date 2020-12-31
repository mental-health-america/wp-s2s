<?php


add_action('init', 'mhaContentScripts');
function mhaContentScripts() {
	wp_enqueue_script('process_mhaContent', plugin_dir_url( __FILE__ ).'js/scripts.js', 'jquery', time(), true);
	wp_localize_script('process_mhaContent', 'do_mhaContent', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
}

/**
 * Add additional classes to the body
 */
add_filter( 'body_class', 'custom_class' );
function custom_class( $classes ) {

	// Not the homepage
	if( !is_front_page() ) {
		$classes[] = 'not-front';
	}
	
    return $classes;
}

 
/**
 * Shortcode - Popular Articles
 * Display the most popular articles
 */
function mha_popular_articles( $atts ) { 
	
	global $wpdb;

	$html = '';
	$tag = $atts['tag'];
	$tax = $atts['tax'];
	$style = $atts['style'];
	$month_range = date('Ym').','.date('Ym', strtotime("-1 months")).','.date('Ym', strtotime("-2 months")); // Last 3 months
	
	if($tag){	
		// Add a tag to the mix	
		$articles = $wpdb->get_results('
			SELECT DISTINCT posts.ID, postview.id, COUNT(postview.count) as total
			FROM '.$wpdb->prefix.'posts AS posts

			INNER JOIN '.$wpdb->prefix.'post_views AS postview
			ON posts.ID = postview.id

			INNER JOIN '.$wpdb->prefix.'term_relationships AS meta
			ON posts.ID = meta.object_id AND meta.term_taxonomy_id = "'.$tag.'"	

			INNER JOIN '.$wpdb->prefix.'postmeta AS postmeta
			ON posts.ID = postmeta.post_id

			WHERE posts.post_status LIKE "publish" 
			AND posts.post_type LIKE "article"		
			AND postview.period IN ('.$month_range.') 
			AND postmeta.meta_value LIKE "condition"

			GROUP BY posts.ID
			ORDER BY total DESC
			LIMIT 8'
		);
	} else {
		// All articles
		$articles = $wpdb->get_results('
			SELECT DISTINCT posts.ID, postview.id, postmeta.post_id, COUNT(postview.count) as total
			FROM '.$wpdb->prefix.'posts AS posts

			INNER JOIN '.$wpdb->prefix.'post_views AS postview
			ON posts.ID = postview.id

			INNER JOIN '.$wpdb->prefix.'postmeta AS postmeta
			ON posts.ID = postmeta.post_id

			WHERE posts.post_status LIKE "publish"
			AND posts.post_type LIKE "article" 
			AND postview.period IN ('.$month_range.') 
			AND postmeta.meta_value LIKE "condition"

			GROUP BY posts.ID
			ORDER BY total DESC
			LIMIT 8'
		);
	}

	if($articles){

		$inline_list = [];

		if($style == 'inline'){
			$html .= '<div class="conditions-list">';
		} else {
			$html .= '<ol class="plain popular-articles">';
		}
		
		foreach($articles as $a){
			
			$item_link = '<a class="plain gray" href="'.get_the_permalink($a->ID).'">'.get_the_title($a->ID).'</a>';

			if($style == 'inline'){
				$inline_list[] = $item_link;
			} else {
				$html .= '<li>'.$item_link.'</li>';
			}			
		}

		if($style == 'inline'){
			
			$html .= implode(' | ', $inline_list);
			$html .= '</div>';
		} else {
			$html .= '</ol>';

		}
		return $html;
	}
	
	return false;

} 
add_shortcode('mha_popular_articles', 'mha_popular_articles'); 


/**
 * Shortcode - Conditions
 * Display all conditions in a simple text list
 */
function mha_conditions() { 
	
	$query = get_terms(array(
		'taxonomy' => 'condition',
		'hide_empty' => false,
		'parent' => 0
	));
	
	$conditions = [];
	if($query){
		foreach($query as $c){
			$conditions[] = '<a class="plain cerulean" href="'.get_term_link($c->term_id).'">'.$c->name.'</a>';
		}
		$html = '<div class="conditions-list">';
		$html .= implode(' | ', $conditions);
		$html .= '</div>';
		return $html;
	}

	return false;

} 
add_shortcode('mha_conditions', 'mha_conditions'); 


/**
 * Shortcode - Screenings
 * Display all screenings in a custom button list
 */
function mha_show_tests() { 
	
    $args = array(
        "post_type" => 'screen',
        "orderby" => 'menu_order',
        "order"	=> 'DESC',
        "post_status" => 'publish',
        "posts_per_page" => 999
    );
	$loop = new WP_Query($args);
	if($loop->have_posts()):
	echo '<div id="screenings-list">';
	while($loop->have_posts()) : $loop->the_post();
		$screen_id = 'screen-'.get_the_ID();
		$screen_color = 'teal';
		if(get_field('survey', get_the_ID())){
			$screen_color = 'purple';
		}
		?>  		
			<div class="screen-item relative">
				<button class="reveal-excerpt"  
					data-reveal="<?php echo $screen_id; ?>"
					aria-expanded="false"
					aria-controls="<?php echo $screen_id; ?>>">+</button>
				<a class="button round block text-left large <?php echo $screen_color; ?>"
					href="<?php echo get_the_permalink(); ?>">
					<?php the_title(); ?>
					<span class="excerpt block" style="display: none;" id="<?php echo $screen_id; ?>">
						<?php echo get_the_excerpt(); ?><br />
						<strong class="caps">Take <?php the_title(); ?></strong>
					</span>
				</a>
			</div>		
		<?php 
	endwhile;
	echo '</div>';
	endif;

} 
add_shortcode('mha_show_tests', 'mha_show_tests'); 


/**
 * Filter Bubble Query
 */

function getArticlesAjax(){
	
	// General variables
    $result = array();
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);

	// Article Type
	$type = sanitize_text_field($data['type']);

	// Search Content
	$search = null;
	if($data['search'] != ''){
		$search = sanitize_text_field($data['search']);
	}

	// Additional Filters
	$filters = [];	
	$conditions = '';

	// Conditions Content
	if(isset($data['condition'])){
		$conditions = [];
		foreach($data['condition'] as $c){
			$conditions[] = intval($c);
		}	
	}

	// Meta Query Filters
	if(isset($data['service_type'])){
		foreach($data['service_type'] as $k => $v){
			$filters['service_type'][$k][] = sanitize_text_field($v);
		}	
	}
	if(isset($data['area_served'])){
		foreach($data['area_served'] as $k => $v){
			$filters['area_served'][$k][] = sanitize_text_field($v);
		}	
	}
	if(isset($data['treatment_type'])){
		foreach($data['treatment_type'] as $k => $v){
			$filters['treatment_type'][$k][] = sanitize_text_field($v);
		}	
	}
	if(isset($data['diy_issue'])){
		foreach($data['diy_issue'] as $k => $v){
			$filters['diy_issue'][$k][] = sanitize_text_field($v);
		}	
	}
	if(isset($data['diy_type'])){
		foreach($data['diy_type'] as $k => $v){
			$filters['diy_type'][$k] = sanitize_text_field($v);
		}	
	}

	// Ordering
	$order = '';
	$orderby = '';
	if(isset($data['order'])){
		$order = sanitize_text_field($data['order']);
	}
	if(isset($data['orderby'])){
		$orderby = sanitize_text_field($data['orderby']);
	}

	echo get_articles($type, $search, $conditions, $filters, $order, $orderby);
	exit();

}
add_action("wp_ajax_nopriv_getArticlesAjax", "getArticlesAjax");
add_action("wp_ajax_getArticlesAjax", "getArticlesAjax");


function get_articles( $type = null, $search = null, $conditions = null, $filters = null , $order = 'DESC' , $orderby = 'featured' ){
	
	$html = '';
    $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	$args = array(
		"post_type"      => 'article',
		"orderby"        => $orderby,
		"order"	         => $order,
		"post_status"    => 'publish',
        "paged" 		 => $paged,
		"posts_per_page" => 15,
		"meta_query"	 => array(
			array(
				'key'	 	=> 'type',
				'value'	  	=> $type,
				'compare'   => 'LIKE'
			)
		)
	);

	if($orderby == 'featured'){
		$args['orderby'] = 'meta_value';
		$args['meta_key'] = 'featured';
	}

	// Free Text Search
	if($search){
		$args['s'] = sanitize_text_field($search);
	}

	// Conditions Taxonomy
	if($conditions){
		$args['tax_query'] = array(
			array(
				'taxonomy' => 'condition',
				'field'    => 'term_id',
				'terms'    => $conditions,
			)
		);
	}

	// Additional meta filters
	if($filters){
		$args['meta_query']['relation'] = 'AND';
		foreach($filters as $k => $v){

			$values = '';
			if(count($v) > 1){
				$values = array();
				foreach($v as $value){
					array_push($values, $value);
				}
				$compare = 'LIKE';
			} else {
				$values = $v[0][0];
				$compare = 'LIKE';
			}

			$args['meta_query'][] = array(
				'key'	 	=> $k,
				'value'	  	=> $values,
				'compare' 	=> $compare
			);
		}
	}

	$loop = new WP_Query($args);
	if($loop->have_posts()):
		while($loop->have_posts()) : $loop->the_post();

			$html .= '<a href="'.get_the_permalink().'" class="filter-bubble red">';
			if(get_the_post_thumbnail_url()){
				$html .= '<span class="block image" style="background-image: url(\''.get_the_post_thumbnail_url().'\');"></span>';
				$html .= '<span class="inner-text block">';
				$html .= '<strong class="text-red title caps block mb-3">'.get_the_title().'</strong>';
			} else {
				$html .= '<span class="title-image image block"><strong class="text-red caps">'.get_the_title().'</strong></span>';
				$html .= '<span class="inner-text block">';
			}

			// Custom Excerpts
			switch($type){

				case 'provider':	
					
					// Location
					$location = get_field('area_served');
					$location_display = '';
					foreach($location as $loc){
						if($loc == 'local'){

						} else {
							$location_display = ucfirst($loc);
						}
					}

					// Services
					$services_check = get_field('service_type');	
					$services = [];
					if($services_check){						
						foreach($services_check as $service){
							$services[] = $service['label'];
						}
					}
					$html .= '<span class="text-gray excerpt block pb-5">';
					$html .= '<span class="excerpt-text block mb-3">'.short_excerpt().'</span>';
					$html .= '<span class="block mb-3"><strong>Location:</strong> '.$location_display.'</span>'; 
					$html .= '<strong>Service Type:</strong> '.implode(', ',$services); 
					$html .= '</span>'; 
					break;

				default:
					$html .= '<span class="text-gray excerpt block pb-5">'.short_excerpt().'</span>'; 
					break;

			}

			$html .= '<strong class="text-red caps block learn-more">Learn More</strong>';
			$html .= '<div style="display:none"></div>';
			$html .= '</span>';
			$html .= '</a>';  

		endwhile; 

		$html .= '<div class="pagination pt-5">';
		$html .= paginate_links( array(
			'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
			'total'        => $loop->max_num_pages,
			'current'      => max( 1, get_query_var( 'paged' ) ),
			'format'       => '?paged=%#%',
			'show_all'     => false,
			'type'         => 'plain',
			'end_size'     => 2,
			'mid_size'     => 1,
			'prev_next'    => true,
			'prev_text'    => sprintf( '<i></i> %1$s', __( 'Previous', 'text-domain' ) ),
			'next_text'    => sprintf( '%1$s <i></i>', __( 'Next', 'text-domain' ) ),
			'add_args'     => false,
			'add_fragment' => '',
		) );
		$html .= '</div>';
		
	else:
		$html .= '<div class="bubble round thin raspberry" style="width: 100%;"><div class="inner text-center"><strong>No items matched your filter selections. Please try another search.</strong></div></div>';
	endif;


	return $html;
	
}



// Provider Location Address Conversions
add_action('acf/save_post', 'update_article', 20);
function update_article( $post_id ){

    // Only if lat/lng is blank
    if( !isset($_POST['acf']['field_5fd3efa5c74a5']) || !isset($_POST['acf']['field_5fd3efaac74a6']) ) {

		// get the number of rows in the repeater
		$count = intval(get_post_meta($post_id, 'location', true));

		// loop through the rows
		for ($i=0; $i<$count; $i++) {

			// Prep address
			if(!get_post_meta($post_id, 'location_'.$i.'_latitude', true) || !get_post_meta($post_id, 'location_'.$i.'_longitude', true)){

				$check_address = get_post_meta($post_id, 'location_'.$i.'_address', true);

				// Connect to Google
				$address_url = urlencode( $check_address );		
				$handle = curl_init(); 
				$url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address_url&key=AIzaSyAi7OToMkshpA4zFYbj_MsWh3QOREESaxc";
				curl_setopt($handle, CURLOPT_URL, $url);
				curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
				$output = json_decode(curl_exec($handle), true);
				curl_close($handle);
				
				// Update values
				update_post_meta($post_id, 'location_'.$i.'_latitude', sanitize_text_field($output['results'][0]['geometry']['location']['lat']));
				update_post_meta($post_id, 'location_'.$i.'_longitude', sanitize_text_field($output['results'][0]['geometry']['location']['lng']));
			}

		}
		
    }

}

function attach_remote_image_to_post($image_url, $parent_id, $alt = ''){

    $image = $image_url;

    $get = wp_remote_get( $image );

    $type = wp_remote_retrieve_header( $get, 'content-type' );

    if (!$type) {
		return false;
	}

    $mirror = wp_upload_bits( basename( $image ), '', wp_remote_retrieve_body( $get ) );

    $attachment = array(
        'post_title'=> basename( $image ),
        'post_mime_type' => $type
    );

    $attach_id = wp_insert_attachment( $attachment, $mirror['file'], $parent_id );

    require_once(ABSPATH . 'wp-admin/includes/image.php');

    $attach_data = wp_generate_attachment_metadata( $attach_id, $mirror['file'] );

	wp_update_attachment_metadata( $attach_id, $attach_data );
	if($alt){
		update_post_meta($attach_id, '_wp_attachment_image_alt', $alt);
	}

    return $attach_id;

}