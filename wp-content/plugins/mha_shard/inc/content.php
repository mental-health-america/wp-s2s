<?php


add_action('init', 'mhaContentScripts');
function mhaContentScripts() {
	wp_enqueue_script('process_mhaContent', plugin_dir_url( __FILE__ ).'js/scripts.js', 'jquery', '20221206_1', true);
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
 * Resource Articles
 */
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
		if(isset($options['tag_terms'])){
			foreach($options['tag_terms'] as $ct){
				$tag_terms[] = $ct;
			}
			if(count($tag_terms) > 0){
				$tax_args['tag__and'] = $tag_terms;
			}
		}
		
		if(isset($options['condition_terms'])){
			foreach($options['condition_terms'] as $ct){
				$tax_args['tax_query']['relation'] = 'AND';
				$tax_args['tax_query'][] = array(
					'taxonomy' => 'condition',
					'field'    => 'term_id',
					'terms'    => $ct
				);
			}
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

	/** 
	 * Scoring Order Overrides
	 */

	// Treatment Articles
	if($options['type'] == 'treatment'){
		$treatment_articles = [];
		$pop_array = array_slice(mha_monthly_pop_articles('read'), 0, 50);
		foreach($articles as $a){

			// Defaults
			$treatment_articles[$a]['id'] = $a;
			$treatment_articles[$a]['score_labels'] = '';
			$score = 0;

			// Top 50 Popular +5
			if(in_array($a, $pop_array)){
				$score = $score + 5;
				$pop_number = array_search($a, $pop_array) ? array_search($a, $pop_array) : '';
				$treatment_articles[$a]['score_labels'] .= 'Top50(#'.$pop_number.') ';
			}

			// Primary Condition
			$primary_condition = get_field('primary_condition', $a);
			if($options['condition_terms'] && in_array($primary_condition, $options['condition_terms'])){
				$score = $score + 10;
				$treatment_articles[$a]['score_labels'] .= 'HasPrimary ';
			}

			// No Filters Check
			if(!isset($options['condition_terms']) || !isset($options['tag_terms'])){
				$tags = get_the_terms( $a, 'post_tag' );
				$m101 = false;
				if($tags){
					foreach($tags as $t){
						if($t->slug == 'mental-health-101'){
							$m101 = true;
						}
					}
				}
				if(get_field('all_conditions', $a) == 1 || $m101 == true){
					$score = $score + 10;
					$treatment_articles[$a]['score_labels'] .= 'AllConditions ';
				}
			}

			// Treatment Types
			$filter_treatment_types = [];
			if(isset($options['filters'])){
				foreach($options['filters'][0] as $k => $v){
					if($k == 'treatment_type'){
						foreach($v as $t){
							$filter_treatment_types[] = $t;
						}
					}
				}				
			}
			if(count($filter_treatment_types) == 0 || !isset($options['filters'])){
				$score = $score + count( get_field('treatment_type', $a) );		
				$treatment_articles[$a]['score_labels'] .= 'TreatmentTypes(x'.count( get_field('treatment_type', $a) ).') ';
			}		
			
			// Set the final score
			$treatment_articles[$a]['score'] = $score;
		}
		$treatment_articles_og = $treatment_articles; // Used later; need to preserve ID as keys before sorting
		usort($treatment_articles, function ($item1, $item2) {
			return $item2['score'] <=> $item1['score'];
		});
		$articles = [];
		foreach($treatment_articles as $ta){
			$articles[] = $ta['id'];
		}
	}	

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
				get_template_part( 'templates/blocks/resource', 'item', array( 
					'score' => isset($treatment_articles_og[$post]) ? $treatment_articles_og[$post]['score'] : 0, 
					'score_labels' => isset($treatment_articles_og[$post]) ? $treatment_articles_og[$post]['score_labels'] : '' 
				));
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


/**
 * Get Articles but faceted
 */
function get_articles_faceted( $options ){
	
	/**
	 * Settings
	 */

	// Default Args
    $defaults = array (
		"post_type"      	=> 'article',
		//'type' 			=> null, 
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
		'all_conditions' 	=> null,
	);
	$options = wp_parse_args( $options, $defaults );

	/**
	 * Default Articles that match "Type"
	 */

	// Default Args
	$article_args = array(
		"post_type"      => $options['post_type'],
		"orderby"        => $options['orderby'],
		"order"	         => $options['order'],
		"post_status"    => 'publish',
		"posts_per_page" => -1,
		"facetwp" 		 => true,
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
	
	if($articles_query->have_posts()):
	while($articles_query->have_posts()) : $articles_query->the_post();
		$article_posts[] = get_the_ID();
	endwhile;
	endif;

	/**
	 * Geo Search
	 */
	$get_national_results = 0;
	$geo_search = false;
		
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

	/** 
	 * Scoring Order Overrides
	 */
	

	// Treatment Articles
	if($options['type'] == 'treatment'){
		$treatment_articles = [];
		$pop_array = array_slice(mha_monthly_pop_articles('read'), 0, 50);
		foreach($articles as $a){

			// Defaults
			$treatment_articles[$a]['id'] = $a;
			$treatment_articles[$a]['score_labels'] = '';
			$score = 0;

			// Top 50 Popular +5
			if(in_array($a, $pop_array)){
				$score = $score + 5;
				$pop_number = array_search($a, $pop_array) ? array_search($a, $pop_array) : '';
				$treatment_articles[$a]['score_labels'] .= 'Top50(#'.$pop_number.') ';
			}

			// Primary Condition
			$primary_condition = '';
			$article_conditions = [];
			if(get_field('primary_condition', $a)){
				$primary_condition_raw = get_field('primary_condition', $a);
				$primary_condition = $primary_condition_raw->term_id;
			} else {
				$article_conditions_raw = get_the_terms($a, 'condition');
				if($article_conditions_raw){
					foreach($article_conditions_raw as $acr){
						$article_conditions[] = $acr->term_id;
					}
				}
				if(count($article_conditions) == 1){
					$primary_condition = $article_conditions[0];
				}
			}
			
			if($options['condition_terms'] && in_array($primary_condition, $options['condition_terms'])){
				$score = $score + 10;
				$treatment_articles[$a]['score_labels'] .= 'HasPrimaryCondition ';
			}

			// Featured Score overide
			if(get_field('featured', $a)){				
				$score = $score + 100;
				$treatment_articles[$a]['score_labels'] .= 'Featured ';
			}

			// No Filters Check
			if(!isset($options['condition_terms']) || !isset($options['tag_terms'])){
				$tags = get_the_terms( $a, 'post_tag' );
				$m101 = false;
				if($tags){
					foreach($tags as $t){
						if($t->slug == 'mental-health-101'){
							$m101 = true;
						}
					}
				}
				if(get_field('all_conditions', $a) == 1){
					$score = $score + 10;
					$treatment_articles[$a]['score_labels'] .= 'GeneralMentalHealth ';
				}
			}

			// Treatment Types
			$filter_treatment_types = [];
			if(isset($options['filters'])){
				foreach($options['filters'][0] as $k => $v){
					if($k == 'treatment_type'){
						foreach($v as $t){
							$filter_treatment_types[] = $t;
						}
					}
				}				
			}
			if(count($filter_treatment_types) == 0 || !isset($options['filters'])){
				$score = $score + count( get_field('treatment_type', $a) );		
				$treatment_articles[$a]['score_labels'] .= 'TreatmentTypes(x'.count( get_field('treatment_type', $a) ).') ';
			}		
			
			// Set the final score
			$treatment_articles[$a]['score'] = $score;
		}
		$treatment_articles_og = $treatment_articles; // Used later; need to preserve ID as keys before sorting
		usort($treatment_articles, function ($item1, $item2) {
			return $item2['score'] <=> $item1['score'];
		});
		$articles = [];
		foreach($treatment_articles as $ta){
			$articles[] = $ta['id'];
		}
	}	

	if(count($articles) > 0 ){
		
		// Display Articles
		$counter = 1;
		foreach($articles as $post):
			//echo $counter .' / '. $offset.' // '. get_the_ID() .'<br />';			
			//if($counter >= $offset && $counter <= $offset_ceil){
				setup_postdata($post);

				$faux_paging = $counter > $posts_per_page ? 'd-none' : '';
				get_template_part( 'templates/blocks/resource', 'item', array( 
					'score' => isset($treatment_articles_og[$post]) ? $treatment_articles_og[$post]['score'] : 0, 
					'score_labels' => isset($treatment_articles_og[$post]) ? $treatment_articles_og[$post]['score_labels'] : '',
					'paginated_display' => $faux_paging
				));
			//}
			$counter++;			
		endforeach;

		// Load More
		$posts_per_page = 30;
		$total_posts = count($articles);
		$max_pages = ceil($total_posts / $posts_per_page);
		$offset = ($options['paged'] - 1) * $posts_per_page;
		$offset_ceil = $options['paged'] * $posts_per_page;

		if(count($articles) > $posts_per_page){
			$paged_next = $options['paged'] + 1;
			echo '<div class="navigation pagination pt-5 mr-2 mr-md-3">';
			echo '<button class="load-more-articles-facet button round red" data-paged="'.$paged_next.'" data-per-page="'.$posts_per_page.'">';
				echo _e('Load More');
			echo '</button>';
			echo '</div>';
		}

	} else {		

		// No articles to display messages
		if($options['geo']){
			echo '<div id="resource-error" class="resource-error-message bubble round thin raspberry" style="width: 100%;"><div class="inner text-center"><strong>';
			echo _e('No results were found within 50 miles of your search criteria. Please try another search.');
			echo '</strong></div></div>';
		} else {
			echo '<div id="resource-error" class="resource-error-message bubble round thin raspberry" style="width: 100%;"><div class="inner text-center"><strong>';
			echo _e('No results matched your search criteria. Please try another search.');
			echo '</strong></div></div>';
		}
	}
	
	return;
}


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
	if(isset($data['paged']) && $data['paged'] != ''){
		$options['paged'] = intval($data['paged']);
	}

	// Conditions Content
	if(isset($data['condition']) && $data['condition']){
		$options['condition_terms'] = $data['condition'];
	}

	// Tags
	if(isset($data['tags']) && $data['tags']){
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
	if(isset($data['espanol'])){
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
function get_geo( $zip = null ){

	// For Ajax use
	if($_POST['data']){
		// General variables
		$result = array();
		$options = [];

		// Make serialized data readable
		parse_str($_POST['data'], $data);
		$zip = $data['zip'];
	}

	global $wpdb;
	$zip_check = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM zips
			WHERE zip = %d LIMIT 1",
			$zip
		)
	);

	$geo = [];

	if ( count($zip_check) > 0 ){

		// Already have the lat/long, just spit those back out
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
	
	// Get the articles
	echo json_encode($geo);
	exit();
	
}
add_action("wp_ajax_nopriv_get_geo", "get_geo");
add_action("wp_ajax_get_geo", "get_geo");



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
	$json = plugin_dir_path( __FILE__ ).'tmp/pop_articles.json'; 
	
	// Return JSON contents
	if($read == 'read' && file_exists($json) && filemtime($json) > strtotime('-3 months')) {
		$pop_data = file_get_contents($json);
		$pop_return = json_decode( json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $pop_data), true ), true );
		return $pop_return;
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

	return $pop_articles;
	
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

		if($tags){
			foreach($tags as $tag){
				if(!get_field('hide_on_front_end', $tag->taxonomy.'_'.$tag->term_id)){
					$tags_array[$tag->term_id] = $tag->name;
				}
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