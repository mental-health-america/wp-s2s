<?php

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
	$style = $atts['style'];

	
	if($tag){	
		// Add a tag to the mix	
		$articles = $wpdb->get_results('
			SELECT DISTINCT posts.ID, postview.id, COUNT(postview.count) as total
			FROM '.$wpdb->prefix.'posts AS posts

			INNER JOIN '.$wpdb->prefix.'post_views AS postview
			ON posts.ID = postview.id

			INNER JOIN '.$wpdb->prefix.'term_relationships AS meta
			ON posts.ID = meta.object_id AND meta.term_taxonomy_id = "'.$tag.'"	

			WHERE posts.post_status LIKE "publish" AND posts.post_type LIKE "article"
			GROUP BY posts.ID
			ORDER BY total DESC
			LIMIT 8'
		);
	} else {
		// All articles
		$articles = $wpdb->get_results('
			SELECT DISTINCT posts.ID, postview.id, COUNT(postview.count) as total
			FROM '.$wpdb->prefix.'posts AS posts
			INNER JOIN '.$wpdb->prefix.'post_views AS postview
			ON posts.ID = postview.id
			WHERE posts.post_status LIKE "publish" AND posts.post_type LIKE "article"
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