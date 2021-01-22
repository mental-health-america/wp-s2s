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
			
			$item_link = '<a class="plain gray-dark montserrat semi" href="'.get_the_permalink($a->ID).'">'.get_the_title($a->ID).'</a>';

			if($style == 'inline'){
				$inline_list[] = $item_link;
			} else {
				$html .= '<li>'.$item_link.'</li>';
			}			
		}

		if($style == 'inline'){
			
			$html .= implode('&nbsp;<span class="noto">|</span>&nbsp; ', $inline_list);
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
			if(!get_field('hide_on_front_end', $c->taxonomy.'_'.$c->term_id)){
				$conditions[] = '<a class="plain cerulean" href="'.get_term_link($c->term_id).'">'.$c->name.'</a>';
			}
		}
		$html = '<div class="conditions-list">';
		$html .= implode('&nbsp;<span class="noto">|</span>&nbsp; ', $conditions);
		$html .= '</div>';
		return $html;
	}

	return false;

} 
add_shortcode('mha_conditions', 'mha_conditions'); 

/**
 * Shortcode - Provider Search
 * Display search form for providers
 */
function mha_provider_search() { 

	$html = '<div class="form-container line-form red mt-5">
		<form action="/get-help" method="GET">
			<p class="form-group float-label wide">
				<label class="form-label caps" for="email">Enter your zip code</label>
				<input type="number" name="geo" value="" placeholder="" />
			</p>
			<div class="text-center">
				<input type="submit" class="button gform_button round red" value="Find Support" />
			</div>
		</form>
	</div>';

	return $html;
	
} 
add_shortcode('mha_provider_search', 'mha_provider_search'); 


/**
 * Shortcode - Screenings
 * Display all screenings in a custom button list
 */
function mha_show_tests() { 
	
    $args = array(
        "post_type" => 'screen',
        "orderby" => 'menu_order',
        "order"	=> 'ASC',
        "post_status" => 'publish',
        "posts_per_page" => 200
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
					<span class="excerpt-title"><?php the_title(); ?></span>
					<span class="excerpt block" style="display: none;" id="<?php echo $screen_id; ?>">
						<?php echo get_the_excerpt(); ?><br />
						<strong class="caps lh-normal">Take <?php the_title(); ?></strong>
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
	$options = [];
	
	// Make serialized data readable
	parse_str($_POST['data'], $data);

	// Article Type
	$options['type'] = sanitize_text_field($data['type']);
	
	// Search Content
	$search = null;
	if($data['search'] != ''){
		$options['search'] = sanitize_text_field($data['search']);
	}

	// Page
	$options['paged'] = 1;
	if($data['paged'] != ''){
		$options['paged'] = intval($data['paged']);
	}

	// Conditions Content
	if($data['condition']){
		$options['condition_terms'] = $data['condition'];
	}
	
	// All Conditions
	$allConditions = null;
	if(isset($data['all_conditions'])){
		$options['all_conditions'] = 1;
	}

	// Additional Filters
	$filters = [];	
	if(isset($data['service_type'])){
		foreach($data['service_type'] as $k => $v){
			$filters['service_type'][] = sanitize_text_field($v);
		}	
	}
	if(isset($data['area_served'])){
		foreach($data['area_served'] as $k => $v){
			$filters['area_served'][] = sanitize_text_field($v);
		}	
	}
	if(isset($data['treatment_type'])){
		foreach($data['treatment_type'] as $k => $v){
			$filters['treatment_type'][] = sanitize_text_field($v);
		}	
	}
	if(isset($data['diy_issue'])){
		foreach($data['diy_issue'] as $k => $v){
			$filters['diy_issue'][] = sanitize_text_field($v);
		}	
	}
	if(isset($data['diy_type'])){
		foreach($data['diy_type'] as $k => $v){
			$filters['diy_type'][] = sanitize_text_field($v);
		}	
	}

	if(count($filters) > 0){
		$options['filters'][] = $filters;
	}

	// Geo Search
	if(isset($data['zip']) && $data['zip'] != ''){
		$options['geo'] = get_geo($data['zip']);
	}

	// Ordering
	if(isset($data['order'])){
		$options['order'] = sanitize_text_field($data['order']);
	}
	if(isset($data['orderby'])){
		$options['orderby'] = sanitize_text_field($data['orderby']);
	}

	// Spanish
	if($data['espanol']){
		$options['espanol'] = '=';
	}
		
	// Get the articles
	echo get_articles( $options );
	exit();

}
add_action("wp_ajax_nopriv_getArticlesAjax", "getArticlesAjax");
add_action("wp_ajax_getArticlesAjax", "getArticlesAjax");


/**
 * Get Geography Search
 */
function get_geo( $zip ){

	global $wpdb;
	$zip_check = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM zips
			WHERE zip = %d LIMIT 1",
			$zip
		)
	);

	$geo = '';

	if ( count($zip_check) > 0 ){

		// Already have the lat/long, just spit those back out
		$geo = [];
		$geo['lat'] = $zip_check[0]->lat;
		$geo['lng'] = $zip_check[0]->lng;
		$geo['state'] = $zip_check[0]->state;

	} else {

		// Get lat/long from Google
		$address_url = urlencode( $zip );		
		$handle = curl_init(); 
		$url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address_url&key=AIzaSyAi7OToMkshpA4zFYbj_MsWh3QOREESaxc";
		curl_setopt($handle, CURLOPT_URL, $url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		$output = json_decode(curl_exec($handle), true);
		curl_close($handle);

		if(count($output['results']) > 0){
			$geo = [];

			// Get lat/long
			$geo['lat'] = $output['results'][0]['geometry']['location']['lat'];
			$geo['lng'] = $output['results'][0]['geometry']['location']['lng'];

			// Get State
			$geo['state'] = '';
			for ($i = 0; $i < count($output['results'][0]['address_components']); $i++) {
				$state_check = array($output['results'][0]['address_components'][$i]['types'][0]);
				if(in_array("administrative_area_level_1", $state_check)) {
					$geo['state'] = $output['results'][0]['address_components'][$i]['long_name'];
				}
			}

			$insert_zips = $wpdb->insert("zips", array(
					'zip' => $zip,
					'lat' => $geo['lat'],
					'lng' => $geo['lng'],
					'state' => $geo['state']
				), array(
					'%d',
					'%f',
					'%f',
					'%s'
				)
			);
		}

	}
	
	return $geo;

}

function get_articles( $options ){
	
	/**
	 * Settings
	 */

	// Default Args
    $defaults = array (
	  //'type' 			=> null, 
	  //'search' 		=> null, 
	  //'conditions' 	=> null, 
	  //'filters' 		=> null, 
		'order' 		=> 'DESC', 
		'orderby' 		=> 'featured', 
	  //'geo' 			=> null, 
		'paged' 		=> 1, 
	  //'espanol' 	    => '!=', 
	  //'all_conditions' => null
	);
	$options = wp_parse_args( $options, $defaults );

	/**
	 * Default Articles that match "Type"
	 */

	// Default Args
	$article_args = array(
		"post_type"      => 'article',
		"orderby"        => $options['orderby'],
		"order"	         => $options['order'],
		"post_status"    => 'publish',
		"posts_per_page" => -1,
        "paged" 		 => $options['paged']
	);
	
	// Article Type
	$article_args['meta_query'] = array(
		array(
			'key'	 	=> 'type',
			'value'	  	=> $options['type'],
			'compare'   => 'LIKE'
		)
	);
	if($options['espanol']){
		$article_args['meta_query']['relationship'] = 'AND';
		$article_args['meta_query'][] = array(
			'key'	 	=> 'espanol',
			'value'	  	=> 1,
			'compare'   => $options['espanol']
		);
	} else {
		$article_args['meta_query']['relationship'] = 'AND';
		$article_args['meta_query'][] = array(
			'key'	 	=> 'espanol',
			'value'	  	=> 1,
			'compare'   => '!='
		);
	}

	// Featured Ordering
	if($orderby == 'featured'){
		$article_args['orderby'] = array( 
			'meta_value' => 'DESC',
			'date'       => 'DESC', 
		);
		$article_args['meta_key'] = 'featured';
	}

	// Keyword Search
	if($options['search']){
		$article_args['s'] = sanitize_text_field($options['search']);
	}

	// Additional Custom Fields
	if($options['filters']){
		$article_args['meta_query']['relation'] = 'AND';
		foreach($options['filters'] as $filter_array){
			foreach($filter_array as $k => $v){
				$values = [];
				foreach($v as $value){
					$article_args['meta_query'][] = array(
						'key'	 	=> $k,
						'value'	  	=> $value,
						'compare' 	=> 'LIKE'
					);
				}
			}
		}
	}
	$articles_query = new WP_Query($article_args);
	$article_posts = [];
	foreach($articles_query->posts as $p){
		$article_posts[] = $p->ID;
	}

	/**
	 * All Conditions 
	 */	
	if($options['all_conditions']){	
		$allcon_args = array(
			"post_type"      		=> 'article',
			"orderby" 	    		=> array( 
				'meta_value' 	 	=> 'DESC',
				'date'       	 	=> 'DESC', 
			),
			"meta_key"    	 		=> 'featured',
			"post_status"    		=> 'publish',
			"posts_per_page" 		=> -1,
			"meta_query"	 		=> array(
				'relationship' 		=> 'AND',
				array(
					'key'	 		=> 'all_conditions',
					'value'	  		=> 1,
					'condition'		=> '='
				),
				array(
					'key'	 	=> 'type',
					'value'	  	=> $options['type'],
					'compare'   => 'LIKE'
				)
			)
		);
		
		if($options['espanol']){
			$allcon_args['meta_query'][] = array(
				'key'	 	=> 'espanol',
				'value'	  	=> 1,
				'compare'   => $options['espanol']
			);
		} else {
			$allcon_args['meta_query'][] = array(
				'key'	 	=> 'espanol',
				'value'	  	=> 1,
				'compare'   => '!='
			);
		}
		
		if($options['filters']){
			$article_args['meta_query']['relation'] = 'AND';
			foreach($options['filters'] as $filter_array){
				foreach($filter_array as $k => $v){
					$values = [];
					foreach($v as $value){
						$allcon_args['meta_query'][] = array(
							'key'	 	=> $k,
							'value'	  	=> $value,
							'compare' 	=> 'LIKE'
						);
					}
				}
			}
		}
		
		$allcon_query = new WP_Query($allcon_args);
		$allcon_posts = [];
		foreach($allcon_query->posts as $p){
			$allcon_posts[] = $p->ID;
		}
	}

	/**
	 * Taxonomy Query
	 */
	if($options['condition_terms']){

		$tax_query_terms = [];
		foreach($options['condition_terms'] as $ct){
			$tax_query_terms[] = $ct;
		}

		$tax_args = array(
			"post_type"      => 'article',
			"orderby" 	     => array( 
				'featured'   => 'DESC',
				'date'       => 'DESC', 
			),
			"meta_key"    	 => 'type',
			"meta_value"	 => $options['type'],
			"post_status"    => 'publish',
			"posts_per_page" => -1,
			"tax_query" 	 => array(
				array(
					'taxonomy' => 'condition',
					'field'    => 'term_id',
					'terms'    => $tax_query_terms,
					'compare'  => 'LIKE'
				)
			)
		);
		$tax_query = new WP_Query($tax_args);
		$tax_posts = [];
		foreach($tax_query->posts as $p){
			$tax_posts[] = $p->ID;
		}
	}


	/**
	 * Geo Search
	 */
	$get_national_results = 0;
	if($options['geo']){

		/**
		 * Nearby Results
		 */
		$geo_args = array(
			"post_type"      => 'article',
			"post_status"    => 'publish',
			"posts_per_page" => -1,
			"geo_query" 	 => array(
				"latitude" => $options['geo']['lat'],
				"longitude" => $options['geo']['lng'],
				"radius" => 50
			)
		);

		if($options['filters']){
			$geo_args['meta_query']['relation'] = 'AND';
			foreach($options['filters'] as $filter_array){
				foreach($filter_array as $k => $v){
					$values = [];
					foreach($v as $value){
						$geo_args['meta_query'][] = array(
							'key'	 	=> $k,
							'value'	  	=> $value,
							'compare' 	=> 'LIKE'
						);
					}
				}
			}
		}
		$geo_query = new WP_Query($geo_args);
		$geo_posts = [];
		foreach($geo_query->posts as $p){
			$geo_posts[] = $p->ID;
		}

		/**
		 * Statewide Results Too
		 */
		unset($geo_args['geo_query']);
		$geo_args['meta_query'][] = array(
			'key'	 	=> 'whole_state',
			'value'	  	=> $options['geo']['state'],
			'compare'	=> 'LIKE'
		);
		$geo_state_query = new WP_Query($geo_args);
		foreach($geo_state_query->posts as $p){
			$type = get_field('type');
			if(in_array($option['type'], $type)){
				$geo_posts[] = $p->ID;
			}
		}

		$geo_posts = array_unique($geo_posts);

		/**
		 * National Results
		 */
		if(count($geo_posts) == 0){
			$get_national_results = 1;
			$geo_args['meta_query'] = array(	
				'relation' => 'AND',
				array(
					'key'	 	=> 'type',
					'value'	  	=> $option['type'],
					'compare'   => 'LIKE'
				),
				array(
					'key'	 	=> 'area_served',
					'value'	  	=> 'national',
					'compare'  	=> 'LIKE'
				)
			);
			if($options['filters']){
				$geo_args['meta_query']['relation'] = 'AND';
				foreach($options['filters'] as $filter_array){
					foreach($filter_array as $k => $v){
						$values = [];
						foreach($v as $value){
							$geo_args['meta_query'][] = array(
								'key'	 	=> $k,
								'value'	  	=> $value,
								'compare' 	=> 'LIKE'
							);
						}
					}
				}
			}
			$national_query = new WP_Query($geo_args);
			foreach($national_query->posts as $p){
				$geo_posts[] = $p->ID;
			}
		}

	}
	

	/**
	 * Print Results
	 */

	global $post;

	// Start our main articles array
	$articles = $article_posts;

	if(isset($tax_posts)){		
		// Only show matching IDs based on articles of this type and selected taxonomy
		$articles = array_intersect($article_posts, $tax_posts);
	}	

	if(isset($allcon_posts)){	
		// Add all condition articles after the above	
		$articles_addendum = array_diff($allcon_posts, $articles);
		$articles = array_merge($articles, $articles_addendum);
	}

	if(isset($geo_posts)){	
		$articles = $geo_posts;
	}

	// Pager
	$posts_per_page = 30;
	$total_posts = count($articles);
	$max_pages = ceil($total_posts / $posts_per_page);
	$offset = ($options['paged'] - 1) * $posts_per_page;
	$offset_ceil = $options['paged'] * $posts_per_page;

	/*
	echo 'Current: '.$options['paged'].'<br />';
	echo 'Per Page: '.$posts_per_page.'<br />';
	echo 'Total: '.$total_posts.'<br />';
	echo 'Max: '.$max_pages.'<br />';
	echo 'Offset: '.$offset.'<br />';
	echo 'Ceil: '.$offset_ceil.'<br />';
	*/

	if(count($articles) > 0 ){
		
		if($get_national_results == 1){
			echo '<div class="bubble round thin orange mb-4" style="width: 100%;"><div class="inner text-center"><strong>No local results were found based on your search, but here are some relevant National supporters that may be of assistance.</strong></div></div>';
		}

		// Display Articles
		$counter = 1;
		foreach($articles as $post):			
			if($counter >= $offset && $counter <= $offset_ceil){
				setup_postdata($post);
				get_template_part( 'templates/blocks/resource', 'item' );
			}
			$counter++;			
		endforeach;

		// Load More
		echo '<div class="navigation pagination pt-5 mr-2 mr-md-3">';
			$paged_next = $options['paged'] + 1;
			if( $max_pages > 1 && $paged_next <= $max_pages ){
				echo '<button class="load-more-articles button round red" data-paged="'.$paged_next.'">Load More</button>';
			}
		echo '</div>';

	} else {		

		// No articles to display messages
		if($options['geo']){
			echo '<div class="bubble round thin raspberry" style="width: 100%;"><div class="inner text-center"><strong>No results were found within 50 miles of your search criteria. Please try another search.</strong></div></div>';
		} else {
			echo '<div class="bubble round thin raspberry" style="width: 100%;"><div class="inner text-center"><strong>No results matched your search critera. Please try another search.</strong></div></div>';
		}
	}
	
	return;
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

// Sort term arrays by name
function term_sort_name($a, $b) {
	return strcmp($a->name, $b->name);
}