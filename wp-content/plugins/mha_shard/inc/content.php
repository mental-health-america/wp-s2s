<?php


add_action('init', 'mhaContentScripts');
function mhaContentScripts() {
	wp_enqueue_script('process_mhaContent', plugin_dir_url( __FILE__ ).'js/scripts.js', 'jquery', '1.13', true);
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
function mha_popular_articles( $options ) { 
	
	global $wpdb;

	$html = '';
	
	// Default Args
    $defaults = array (
		'tag' 		=> null, 
		'tax' 		=> null, 
		'style'		=> null, 
	);
	$atts = wp_parse_args( $options, $defaults );

	$month_range = date('Ym').','.date('Ym', strtotime("-1 months")).','.date('Ym', strtotime("-2 months")); // Last 3 months
	
	if($atts['tag']){	

		// All articles
		$articles_bunch = $wpdb->get_results('
			SELECT DISTINCT posts.ID, postview.id, postmeta.post_id, SUM(postview.count) as total
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
			LIMIT 300'
		);

		$articles = [];

		$bunch_counter = 0;
		if($articles_bunch){
			foreach($articles_bunch as $a){
				if($bunch_counter > 8){
					continue;
				}
				if(has_term($atts['tag'], $atts['tax'], $a->ID)){
					$articles[] = $a;
					$bunch_counter++;
				}
			}
		}

	} else {
		// All articles
		$articles = $wpdb->get_results('
			SELECT DISTINCT posts.ID, postview.id, postmeta.post_id, SUM(postview.count) as total
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

		if($atts['style'] == 'inline'){
			$html .= '<div class="conditions-list">';
		} else {
			$html .= '<ol class="plain popular-articles">';
		}
		
		foreach($articles as $a){
			
			$item_link = '<a class="plain montserrat semi" href="'.get_the_permalink($a->ID).'">'.get_the_title($a->ID).'</a>';

			if($atts['style'] == 'inline'){
				$inline_list[] = $item_link;
			} else {
				$html .= '<li>'.$item_link.'</li>';
			}			
		}

		if($atts['style'] == 'inline'){
			
			$html .= implode('&nbsp;<span class="noto" role="separator">|</span>&nbsp; ', $inline_list);
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
		$html .= implode('&nbsp;<span class="noto" role="separator">|</span>&nbsp; ', $conditions);
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
	
	$referrer = '';
	if(get_query_var('ref')){
		$referrer = get_query_var('ref');
	}

	$iframe_mode = '';
	if(get_query_var('iframe')){
		$iframe_mode = get_query_var('iframe');
	}
	
    $args = array(
        "post_type" => 'screen',
        "orderby" => 'menu_order',
        "order"	=> 'ASC',
        "post_status" => 'publish',
        "posts_per_page" => 200
    );
	$loop = new WP_Query($args);
	
	ob_start();
	if($loop->have_posts()):
	echo '<div id="screenings-list">';
	while($loop->have_posts()) : $loop->the_post();
		$screen_id = 'screen-'.get_the_ID();
		$screen_color = 'teal';
		if(get_field('survey', get_the_ID())){
			$screen_color = 'purple survey';
		}

		// Hide from listing pages
		if(get_field('invisible', get_the_ID())){
			continue;
		}

		$screen_link_args = array(
			'ref' => $referrer
		);
		if($iframe_mode == 'true'){
			$screen_link_args['iframe'] = 'true';
		}

		$partner_var = get_query_var('partner');
		if(isset($_GET['partner']) && in_array($partner_var, mha_approved_partners() )){
			$screen_link_args['partner'] = $partner_var;
		}
		$screen_link = add_query_arg( $screen_link_args, get_the_permalink());

		?>  		
			<div class="screen-item relative">
				<button class="reveal-excerpt"  
					data-reveal="<?php echo $screen_id; ?>"
					aria-expanded="false"
					aria-controls="<?php echo $screen_id; ?>>">+</button>
				<a class="button round block text-left large <?php echo $screen_color; ?>"
					href="<?php echo $screen_link; ?>">
					<span class="excerpt-title"><?php the_title(); ?></span>
					<span class="excerpt block" style="display: none;" id="<?php echo $screen_id; ?>">
						<?php echo get_the_excerpt(); ?><br />
						<strong class="caps lh-normal">							
							<?php if(get_field('espanol')): ?>
								Tome la <?php the_title(); ?>
							<?php else: ?>
								Take <?php the_title(); ?>
							<?php endif; ?>
						</strong>
					</span>
				</a>
			</div>		
		<?php 
	endwhile;
	echo '</div>';
	endif;
	return ob_get_clean();

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

	// Tags
	if($data['tags']){
		$options['tag_terms'] = $data['tags'];
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
		$url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address_url&key=".GOOGLE_API_KEY;
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
	  //'type' 				=> null, 
	  	'search' 			=> null, 
		'condition_terms'	=> null, 
		'tag_terms'			=> null, 
	  	'filters' 			=> null, 
		'order' 			=> 'DESC', 
		'orderby' 			=> 'featured', 
	  	'geo' 				=> null, 
	  	'area_served'		=> null, 
		'paged' 			=> 1, 
	  	'espanol' 	    	=> '!=', 
	  	'all_conditions' 	=> null
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
	if($article_args['orderby'] == 'featured'){
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

	// Area Served
	if($options['area_served']){
		$article_args['meta_query'][] = array(
			'key'	 	=> 'area_served',
			'value'	  	=> $options['area_served'],
			'compare'   => 'LIKE'
		);
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
	if($options['condition_terms'] || $options['tag_terms']){

		$tax_args = array(
			"post_type"      => 'article',
			"orderby" 	     => array( 
				'featured'   => 'DESC',
				'date'       => 'DESC', 
			),
			//"meta_key"    	 => 'type',
			//"meta_value"	 => $options['type'],
			"post_status"    => 'publish',
			"posts_per_page" => -1
		);

		$tag_terms = [];
		foreach($options['tag_terms'] as $ct){
			$tag_terms[] = $ct;
		}
		if(count($tag_terms) > 0){
			$tax_args['tag__and'] = $tag_terms;
		}
		
		foreach($options['condition_terms'] as $ct){
			$tax_args['tax_query']['relation'] = 'AND';
			$tax_args['tax_query'][] = array(
				'taxonomy' => 'condition',
				'field'    => 'term_id',
				'terms'    => $ct
			);
		}

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
	$geo_search = false;
	if($options['geo']){

		$geo_search = true;

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
			'value'	  	=> '',
			'compare'	=> '!='
		);
		$geo_state_query = new WP_Query($geo_args);
		foreach($geo_state_query->posts as $p){
			// Get matching type and state
			$type = get_field('type',$p->ID);			
			$state_lower = strtolower(get_field('whole_state',$p->ID) );
			$whole_state = array_map('trim', explode(',', $state_lower));

			if(in_array($options['type'], $type) && in_array(strtolower($options['geo']['state']), $whole_state)){
				$geo_posts[] = $p->ID;
			}
		}

		$geo_posts = array_unique($geo_posts);

		/**
		 * National Results
		 */
		if(count($geo_posts) == 0){
			$get_national_results = 1;
			$geo_args['orderby'] = array( 
				'meta_value' 	 	=> 'DESC',
				'date'       	 	=> 'DESC', 
			);
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

		if(isset($tax_posts)){	
			// Only show matching IDs based on articles of this type and selected taxonomy
			$articles = array_intersect($tax_posts, $geo_posts);
		}	
		
		if(isset($allcon_posts)){	
			// Add all condition articles after the above	
			$articles_addendum = array_diff($allcon_posts, $geo_posts);
			$articles = array_merge($geo_posts, $articles_addendum);
		}
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
			//echo '<div class="bubble round thin orange mb-4" style="width: 100%;"><div class="inner text-center"><strong>No local results were found based on your search, but here are some relevant National supporters that may be of assistance.</strong></div></div>';
			echo '<div class="bubble round thin orange mb-4" style="width: 100%;"><div class="inner text-center"><strong>';
			echo 'To find local resources for the ZIP code you entered, please use the <a href="https://findtreatment.samhsa.gov/" target="_blank">SAMHSA Treatment Locator</a>. Or, look at the nationwide resources listed below.';
			echo '</strong></div></div>';
		} else {
			// Geo search with results
			if($geo_search === true){
				echo '<div class="bubble round thin orange mb-4" style="width: 100%;"><div class="inner text-center"><strong>';
				echo 'To find more local resources for the ZIP code you entered, please use the <a href="https://findtreatment.samhsa.gov/" target="_blank">SAMHSA Treatment Locator</a>. To see nationwide resources, <a href="https://screening.mhanational.org/get-help/">click here</a> or refresh the page.';
				echo '</strong></div></div>';
			}
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
				$url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address_url&key=".GOOGLE_API_KEY;
				curl_setopt($handle, CURLOPT_URL, $url);
				curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
				$output = json_decode(curl_exec($handle), true);
				curl_close($handle);

				// Get the city/state for display purposes				
				$city = '';
				$state = '';
				foreach($output['results'][0]['address_components'] as $ac){
					if($ac['types'][0] == 'locality'){
						$city = $ac['long_name'];
					}
					if($ac['types'][0] == 'administrative_area_level_1'){
						$state = $ac['long_name'];
					}
				}
								
				// Update values
				update_post_meta($post_id, 'location_'.$i.'_latitude', sanitize_text_field($output['results'][0]['geometry']['location']['lat']));
				update_post_meta($post_id, 'location_'.$i.'_longitude', sanitize_text_field($output['results'][0]['geometry']['location']['lng']));
				if($city != '' && !isset($_POST['acf']['field_5feca600b09ad']) ){
					update_post_meta($post_id, 'location_'.$i.'_city', sanitize_text_field($city));
				}
				if($state != '' && !isset($_POST['acf']['field_5feca604b09ae']) ){
					update_post_meta($post_id, 'location_'.$i.'_state', sanitize_text_field($state));
				}

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


/**
 * Monthly Popular Article Check
 */

add_action( 'init', 'register_monthly_generate_mha_popular_article_json_event');

function register_monthly_generate_mha_popular_article_json_event() {
    if( !wp_next_scheduled( 'generate_mha_popular_article_json' ) ) {
        wp_schedule_event( time(), 'monthly', 'generate_mha_popular_article_json' );
    }
}

add_action( 'generate_mha_popular_article_json', 'mha_monthly_pop_articles' );

function mha_monthly_pop_articles( $read = null ){

	// JSON Source File
	$json = plugin_dir_path(__FILE__).'/tmp/pop_articles.json'; 

	// Return JSON content if 
	if($read == 'read'){		
		if(file_exists($json)) {
			$pop_data = file_get_contents($json);
			$pop_return = json_decode( json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $pop_data), true ), true );
			return $pop_return;
		}
	}

	global $wpdb;
	$month_range = date('Ym').','.date('Ym', strtotime("-1 months")).','.date('Ym', strtotime("-2 months")); // Last 3 months
	$pop_articles_raw = $wpdb->get_results('
		SELECT DISTINCT posts.ID, SUM(postview.count) as total
		FROM '.$wpdb->prefix.'posts AS posts

		INNER JOIN '.$wpdb->prefix.'post_views AS postview
		ON posts.ID = postview.id

		WHERE posts.post_status LIKE "publish"
		AND posts.post_type LIKE "article" 
		AND postview.period IN ('.$month_range.') 

		GROUP BY posts.ID
		ORDER BY total DESC
		LIMIT 50'
	);
	$pop_articles = [];
	foreach($pop_articles_raw as $pa){
		$pop_articles[] = $pa->ID;
	}
	$pop_json = json_encode($pop_articles);
		
	$fp = fopen($json, 'w');
	fwrite($fp, json_encode($pop_json));
	fclose($fp);

	return;
	
}


/**
 * Get used tags for specific resource pages
 */

function get_tag_filters( $args ){

	// Args
	$defaults = array(
		"post_type"      => 'article',
		"type"           => '',
		"taxonomy"       => '',
	);            
	$args = wp_parse_args( $args, $defaults );


	$loop_args = array(
		"post_type"      => $args['post_type'],
		"post_status"    => 'publish',
		"posts_per_page" => -1,                            
		'meta_query'     => array(
			array(
				'key'       => 'type',
				'value'     => $args['type'],
				'compare'   => 'LIKE',
			)
		)
	);
	$articles = new WP_Query($loop_args);   
	$tags_array = [];                

	if($articles->have_posts()):
	while($articles->have_posts()) : $articles->the_post();
		
		if($args['taxonomy'] != 'tags'){
			$tags = get_the_terms( get_the_ID(), $args['taxonomy'] );
		} else {
			$tags = get_the_tags(get_the_ID());
		}

		foreach($tags as $tag){
			if(!get_field('hide_on_front_end', $tag->taxonomy.'_'.$tag->term_id)){
				$tags_array[$tag->term_id] = $tag->name;
			}
		}
	endwhile;
	endif;

	// Pre-checked filters
	$params = explode(',', get_query_var($args['taxonomy']));
	$checked_params = [];
	foreach($params as $p){
		$checked_params[] = strtolower(urldecode($p));
	}

	foreach($tags_array as $k => $v):	
		$checked = in_array( strtolower(urldecode($v)), $checked_params) ? ' checked="checked"' : '';
		?>
		<div class="form-item">
			<input id="<?php echo $args['taxonomy']; ?>-<?php echo $k; ?>" type="checkbox" value="<?php echo $k; ?>" name="<?php echo $args['taxonomy']; ?>[]"<?php echo $checked; ?>/>
			<label for="<?php echo $args['taxonomy']; ?>-<?php echo $k; ?>"><?php echo $v; ?></label>
		</div>
		<?php
	endforeach;

}