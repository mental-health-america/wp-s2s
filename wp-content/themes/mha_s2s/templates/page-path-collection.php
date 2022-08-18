<?php 
/* Template Name: Path Collection */
get_header(); 
$espanol = get_field('espanol');
$term = null;
?>

	<?php
		// Hero
		get_template_part( 'templates/blocks/block', 'hero' );

		// Normal Content
		while ( have_posts() ) : the_post();
			get_template_part( 'templates/blocks/content', 'page-heading' );
		endwhile;
		?>
		
		<?php
			// Popular Articles
			$tag = get_field('condition');
			$term_con = get_term_by('term_id', $tag, 'condition');
			$term_tag = get_term_by('term_id', $tag, 'post_tag');
			if($term_con){
				$tag = $term_con->term_id;
				$tax = $term_con->taxonomy;
			};
			if($term_tag){
				$tag = $term_tag->term_id;
				$tax = $term_tag->taxonomy;
			};
			$popular = do_shortcode("[mha_popular_articles tag='$tag' tax='$tax' style='inline']");
			if($popular):
		?>
			<div class="wrap normal mb-5">
				<div class="bubble short dark-blue round-br">
				<div class="inner">
					<h3>Popular Articles</h3>
					<?php echo $popular; ?>
				</div>
				</div>
			</div>
		<?php endif; ?>
		
		<div class="wrap normal">
			<?php
				$args = array(
					"post_type" 		=> 'reading_path',
					"orderby" 			=> 'menu_order',
					"order"				=> 'ASC',
					"post_status" 		=> 'publish',
					"posts_per_page" 	=> 200,
					"tax_query" 		=> array(
						array(
							'taxonomy' => $tax,
							'field'    => 'id',
							'terms'    => $tag
						)
					)
				);
				$loop = new WP_Query($args);
				$has_reading_path = false;
				$zebra = 'odd';
				if($loop->have_posts()):
					
					$has_reading_path = true;

					while($loop->have_posts()) : $loop->the_post();
						get_template_part( 'templates/blocks/reading', 'path', array( 
							'no_wrapper' => 1, 
							'zebra' => $zebra 
						) );
						$zebra = $zebra == 'odd' ? 'even' : 'odd';
					endwhile; 

				else:

					// No reading paths, just display articles
					$search_query = get_query_var('search');
					$order = get_query_var('order');
					$orderby = get_query_var('orderby');
					?>
						<div class="wrap medium" id="ac">
							<div class="bubble pale-blue bubble-border round-small mb-5">
							<div class="inner">	
								
								<div class="bubble cerulean thin round-bl mb-5">
								<div class="inner">
									<form method="GET" action="<?php echo get_the_permalink(get_the_ID()); ?>#ac" class="form-container line-form blue">
										<div class="container-fluid">
										<div class="row">

											<div class="col-12 col-md-6">
												<p class="mb-0 wide block"><input id="search-archive" name="search" value="<?php echo $search_query; ?>" placeholder="Search <?php if($term_con){echo $term_con->name; } if($term_tag){ echo $term_tag->name; } ?> articles" type="text" /></p>
											</div>

											<div class="col-12 col-md-3 mt-3 mt-md-0 pl-1 pr-1">
												<?php if($term): ?>
													<input type="hidden" name="search_tag" value="<?php echo $term->term_id; ?>" />
													<input type="hidden" name="search_taxonomy" value="<?php echo $term->taxonomy; ?>" />
												<?php endif; ?>
												<input type="hidden" name="order" value="<?php echo $order; ?>" />
												<input type="hidden" name="orderby" value="<?php echo $orderby; ?>" />
												<p class="m-0 wide block"><input type="submit" class="button gform_button white block pl-0 pr-0" value="Search" /></p>
											</div>

											<div class="col-12 col-md-3 mt-3 mt-md-0 pl-1 pr-1">								
												<div class="dropdown text-right pr-0 pr-md-4">
													<button class="button cerulean round dropdown-toggle normal-case mobile-wide block" type="button" id="archiveOrder" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-order="DESC" value="featured">
														<?php
															if($orderby == 'title' && $order == 'ASC'){
																echo 'A-Z';
															} elseif($orderby == 'title' && $order == 'DESC'){
																echo 'Z-A';
															} elseif($orderby == 'date' && $order == 'ASC'){
																echo 'Oldest';
															} elseif($orderby == 'date' && $order == 'DESC'){
																echo 'Newest';
															} else {
																echo 'Sort';
															}
														?>
													</button>
													<div class="dropdown-menu" aria-labelledby="orderSelection">
														<a href="<?php echo add_query_arg(
															array( 
																'search' => get_query_var('search'), 
																'search_tag' => get_query_var('search_tag'), 
																'search_taxonomy' => get_query_var('search_taxonomy'), 
																'order' => 'DESC',  
																'orderby' => 'default'
															), get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="" value="">Default</a>
														<a href="<?php echo add_query_arg(
															array( 
																'search' => get_query_var('search'), 
																'search_tag' => get_query_var('search_tag'), 
																'search_taxonomy' => get_query_var('search_taxonomy'), 
																'order' => 'ASC',  
																'orderby' => 'title'
															), get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="ASC" value="title">A-Z</a>
														<a href="<?php echo add_query_arg(
															array( 
																'search' => get_query_var('search'), 
																'search_tag' => get_query_var('search_tag'), 
																'search_taxonomy' => get_query_var('search_taxonomy'), 
																'order' => 'DESC', 
																'orderby' => 'title'
															), get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="DESC" value="title">Z-A</a>
														<a href="<?php echo add_query_arg(
															array( 
																'search' => get_query_var('search'), 
																'search_tag' => get_query_var('search_tag'), 
																'search_taxonomy' => get_query_var('search_taxonomy'), 
																'order' => 'DESC', 
																'orderby' => 'date')
															, get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="DESC" value="date">Newest</a>
														<a href="<?php echo add_query_arg(
															array( 
																'search' => get_query_var('search'), 
																'search_tag' => get_query_var('search_tag'), 
																'search_taxonomy' => get_query_var('search_taxonomy'), 
																'order' => 'ASC',  
																'orderby' => 'date')
															, get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="ASC" value="date">Oldest</a>
													</div>
												</div>
											</div>

										</div>
										</div>
									</form>									
								</div>
								</div>
								
								<?php	
									// Display related articles
									echo get_condition_articles($tax, $tag, $search_query);
								?>
								
							</div>
							</div>
						</div>
					<?php
				endif;
			?>
		</div>

		<div class="content-block block-two-column-text">
		<div class="wrap normal">
				
			<div class="two-cols top cols-50">

				<?php if(!$espanol): ?>
					<div class="left-col ">
					<div class="bubble round-br dark-blue normal">
						<div class="inner">		
							<?php 
								if($has_reading_path): 
							?>	
							<h3>
								<?php 
									if(get_field('custom_category_name', $tax.'_'.$tag)){
										$term_name = get_field('custom_category_name', $tax.'_'.$tag);
									} else {
										$term_name = get_term($tag, $tax)->name;
									}
								?>
								<a class="plain white" href="<?php echo get_term_link($tag, $tax); ?>">See All Articles Related to <?php echo $term_name; ?> &raquo;</a>
							</h3>
							<?php 
								else: 									
									$term = get_term($tag, $tax)->ID;
									$related_conditions = get_field('related_conditions', $term);
									if($related_conditions && count($related_conditions) > 0 ){
										echo '<h3>Learn About Other Related Mental Health Conditions</h3>';
										echo '<div class="conditions-list">';
										$rc_counter = 1;
										foreach($related_conditions as $rc){
											echo '<a class="plain cerulean" href="'.get_term_link($rc).'">'.$rc->name.'</a>';	 
											if($rc_counter < count($related_conditions)){
												echo ' &nbsp;<span class="noto" role="separator">|</span>&nbsp; ';
											}
											$rc_counter++;
										}
										echo '</div>';
									} else {
										echo '<h3>Learn About Mental Health Conditions</h3>';
										echo do_shortcode('[mha_conditions]'); 
									}
								endif;
							?>
						</div>
					</div>    
					</div>
				<?php endif; ?>

				<div class="right-col">
					<?php 
						$args = array(
							"post_type"         => 'screen',
							"order"	            => 'DESC',
							"post_status"       => 'publish',
							"posts_per_page"    => 50,
							'tax_query'      => array(
								array(
									'taxonomy' => $tax,
									'field'    => 'id',
									'terms'    => $tag
								),
							),
							'meta_query' => array( 
								'relation' => 'OR',
								array(
									'key' => 'espanol',
									'value' => '1',
									'compare' => '!='
								),
								array(
									'key' => 'espanol',
									'value' => '1',
									'compare' => 'NOT EXISTS'
								)
							)
						);
						$loop = new WP_Query($args);
						$cta_count = 0;
						if($loop->have_posts()):
						while($loop->have_posts()) : $loop->the_post(); 
							
							$primary_condition_yoast = get_post_meta(get_the_ID(),'_yoast_wpseo_primary_condition', true);
							if(get_field('invisible') || get_field('survey') || $primary_condition_yoast != $tag || $cta_count > 0){
								continue;
							}
							?>   
								<div class="bubble round-tl orange normal">
								<div class="inner">
									<?php
										$an_a = ' '; 
										$title = get_the_title();
										if($title[0] == 'A'){
											$an_a = 'n ';
										}
									?>
									<?php the_title('<h3>Take a'.$an_a,'</h3>'); ?>   
									<div class="excerpt"><?php the_excerpt(); ?></div>
									<div class="text-center pb-3"><a href="<?php echo get_the_permalink(); ?>" class="button white round text-orange">Take a<?php echo $an_a; ?> <?php the_title(); ?></a></div>
								
								</div>
								</div>
							<?php 
							$cta_count++;
						endwhile;
						endif;
						wp_reset_query();
					?>
				</div>
			</div>

		</div>
		</div>

		<?php
		// Content Blocks
		wp_reset_query();
		if( have_rows('block') ):
		while ( have_rows('block') ) : the_row();
			$layout = get_row_layout();
			if( get_template_part( 'templates/blocks/block', $layout ) ):
				get_template_part( 'templates/blocks/block', $layout );
			endif;
		endwhile;
		endif;
	?>

<?php
get_footer();
