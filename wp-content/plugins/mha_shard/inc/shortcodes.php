<?php


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

		// JSON Source File
		$json = plugin_dir_path( __FILE__ ).'tmp/pop_'.$atts['tag'].'_articles.json'; 
		
		// Return JSON contents
		if(file_exists($json) && filemtime($json) > strtotime('-1 months')) {
			
			$pop_data = file_get_contents($json);
			$articles = json_decode( json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $pop_data), true ), true );

		} else {
				
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
						$articles[] = $a->ID;
						$bunch_counter++;
					}
				}
			}
			
			$pop_json = json_encode($articles);
				
			$fp = fopen($json, 'w');
			fwrite($fp, json_encode($pop_json));
			fclose($fp);

		}

	} else {

		// JSON Source File
		$json = plugin_dir_path( __FILE__ ).'tmp/pop_8_articles.json'; 
		
		// Return JSON contents
		if(file_exists($json) && filemtime($json) > strtotime('-1 months')) {
			
			$pop_data = file_get_contents($json);
			$articles = json_decode( json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $pop_data), true ), true );

		} else {
			
			// All articles
			$article_query = $wpdb->get_results('
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
			
			$articles = [];
			foreach($article_query as $pa){
				$articles[] = $pa->ID;
			}
			$pop_json = json_encode($articles);
				
			$fp = fopen($json, 'w');
			fwrite($fp, json_encode($pop_json));
			fclose($fp);

		}
		
	}

	if($articles){

		$inline_list = [];

		if($atts['style'] == 'inline'){
			$html .= '<div class="conditions-list">';
		} else {
			$html .= '<ol class="plain popular-articles">';
		}
		
		foreach($articles as $a){
			
			$item_link = '<a class="plain montserrat semi" href="'.get_the_permalink($a).'">'.get_the_title($a).'</a>';

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
								Tome el <?php the_title(); ?>
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
 * Custom Reading Path
 */

function mha_reading_path( $atts ){

    ob_start();

    $path_id = isset($atts['path']) ? intval($atts['path']) : null;
    $no_wrapper = isset($atts['wrapper']) ? intval($atts['wrapper']) : 1;
    $breakout = isset($atts['breakout']) ? $atts['breakout'] : false;

    if($path_id){
        get_template_part( 'templates/blocks/reading', 'path', array( 
            'no_wrapper' => $no_wrapper, 
            'path_id' => $path_id, 
            'breakout' => $breakout
        ));
    } 

    return ob_get_clean();

} 
add_shortcode('mha_reading_path', 'mha_reading_path'); 



/** 
 * CTA Display
 */

function mha_display_cta_shortcode( $atts ){

    ob_start();

    $id = isset($atts['id']) ? $atts['id'] : null;
	$ids_raw = explode(',',$id);
	$ids = array_map('intval', $ids_raw);
	
    if($id){

		if(count($ids) > 1){
			echo '<div id="cta-col" class="cta-cols">';
		}

		foreach($ids as $cta_id):
			get_template_part( 'templates/blocks/block', 'cta', array( 
				'id' => $cta_id
			));
		endforeach;

		if(count($ids) > 1){
			echo '</div><div class="clear"></div>';
		}

    } 

    return ob_get_clean();

} 
add_shortcode('mha_cta', 'mha_display_cta_shortcode'); 