<?php 
/* Template Name: Path Collection */
get_header(); 
$espanol = get_field('espanol');
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
				$zebra = 'odd';
				if($loop->have_posts()):
					
					while($loop->have_posts()) : $loop->the_post();
						$path_id = get_the_ID();
						$delay = 0;
						if($zebra == 'odd'){
							$pathColor = 'cerulean bubble-border-blue';
							$zebra = 'even';
						} else {
							$pathColor = 'pale-blue bubble-border-blue';
							$zebra = 'odd';
						}
					?>  

						<div class="bubble round-tl mb-5 <?php echo $pathColor; ?> path-container wow fadeIn">
						<div class="inner">
							<?php the_title('<h3>','</h3>'); ?> 
							
							<ol class="path-list">
								<?php
									$counter = 0;
									$spacer_counter_wide = 0;
									$spacer_counter_narrow = 0;
									$path = get_field('path');
									$max = count($path);
									if( have_rows('path') ):
									while( have_rows('path') ) : the_row();
										$article = get_sub_field('article');
										echo '<li class="path-item wow fadeIn" data-wow-delay="'.($delay).'s">';
											echo '<a class="button round-tiny thin cerulean block" href="'.add_query_arg('pathway', $path_id, get_the_permalink($article)).'">';
												echo '<span class="table">';
												echo '<span class="cell">';
													if(get_sub_field('custom_title')){
														echo get_sub_field('custom_title');
													} else {
														echo get_the_title($article);
													}
												echo '</span>';
												echo '</span>';
											echo '</a>';
										echo '</li>';
										$counter++;

										// Spacers
										if($counter < $max){
											echo '<li class="path-spacer path-spacer-mobile wow fadeIn" data-wow-delay="'.($delay).'s">';
											get_template_part( 'templates/blocks/block', 'path.svg' );
											echo '</li>';

											$spacer_counter_wide++;
											$spacer_counter_narrow++;
											if($spacer_counter_wide == 4){
												echo '<li class="path-spacer path-spacer-wide wow fadeIn" data-wow-delay="'.($delay).'s">';
												get_template_part( 'templates/blocks/block', 'path.svg' );
												echo '</li>';
												$spacer_counter_wide = 0;
											}
											if($spacer_counter_narrow == 3){
												echo '<li class="path-spacer path-spacer-narrow wow fadeIn" data-wow-delay="'.($delay).'s">';
												get_template_part( 'templates/blocks/block', 'path.svg' );
												echo '</li>';
												$spacer_counter_narrow = 0;
											}
										}
										$delay = $delay + .1;
									endwhile;
									endif;
								?>
							</ol>
							
						</div>
						</div>

					<?php 
					endwhile; 

				else:

					// No reading paths, just display articles
					$search_query = get_query_var('search');
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
												<input type="hidden" name="search_tag" value="<?php echo $term->term_id; ?>" />
												<input type="hidden" name="search_taxonomy" value="<?php echo $term->taxonomy; ?>" />
												<input type="hidden" name="order" value="<?php echo get_query_var('order'); ?>" />
												<input type="hidden" name="orderby" value="<?php echo get_query_var('orderby'); ?>" />
												<p class="m-0 wide block"><input type="submit" class="button gform_button white block pl-0 pr-0" value="Search" /></p>
											</div>

											<div class="col-12 col-md-3 mt-3 mt-md-0 pl-1 pr-1">								
												<div class="dropdown text-right pr-0 pr-md-4">
													<button class="button cerulean round dropdown-toggle normal-case mobile-wide block" type="button" id="archiveOrder" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-order="DESC" value="featured">
														Sort
													</button>
													<div class="dropdown-menu" aria-labelledby="orderSelection">
														<a href="<?php echo add_query_arg(
															array( 
																'search' => get_query_var('search'), 
																'search_tag' => get_query_var('search_tag'), 
																'search_taxonomy' => get_query_var('search_taxonomy'), 
																'order' => 'ASC',  
																'orderby' => 'title'
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
									if(get_query_var('order')){
										$order = get_query_var('order');
									} else {
										$order = 'DESC';
									}

									if(get_query_var('orderby')){
										$orderby = get_query_var('orderby');
									} else {
										$orderby = array('meta_value' => 'DESC', 'date' => 'DESC');
									}

									$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
									$args = array(
										"post_type" => 'article',
										'paged' => $paged,
										"orderby" => $orderby,
										"order"	=> $order,
										"post_status" => 'publish',
										"posts_per_page" => 40,
										"meta_query" => array(
											'relation' => 'AND',
											array(
												'relation' => 'OR',
												array(
													'key' => 'type',
													'value' => 'condition',
													'compare' => 'LIKE'
												),
												array(
													'key' => 'type',
													'value' => 'treatment',
													'compare' => 'LIKE'
												),
												array(
													'key' => 'type',
													'value' => 'connect',
													'compare' => 'LIKE'
												),
												array(
													'key' => 'type',
													'value' => 'diy',
													'compare' => 'LIKE'
												)
											),
											array(
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
										),
										"tax_query" => array(
											array(
												'taxonomy' => $tax,
												'field'    => 'id',
												'terms'    => $tag
											)
										)
									);
									if($search_query){
										$args['s'] = $search_query;
									}
									$loop = new WP_Query($args);
									if ( $loop->have_posts() ) :	
										$resources = array('condition');
										echo '<ol class="plain mb-0">';
										while($loop->have_posts()) : $loop->the_post();					
											$type = get_field('type');                        
											$article_type = get_field('type');
											?>
											<li class="mb-4">
												<p class="mb-2">	
													<a class="dark-gray plain" href="<?php echo add_query_arg('ref', $tag, get_the_permalink()); ?>"><?php the_title(); ?></a>
												</p>
												<!--<div class="medium small pl-5"><?php echo short_excerpt(); ?></div>-->
											</li>
										<?php
										endwhile;
										echo '</ol>';

										echo '<div class="navigation pagination pt-5">';
										echo paginate_links( array(
											'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
											'total'        => $loop->max_num_pages,
											'current'      => max( 1, $paged ),
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
										echo '</div>';
										
									endif; 
									wp_reset_query();
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
							"posts_per_page"    => 1,
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
						if($loop->have_posts()):
						?>
							<div class="bubble round-tl orange normal">
							<div class="inner">
							<?php while($loop->have_posts()) : $loop->the_post(); ?>   
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
							<?php endwhile; ?>
							</div>
							</div>
						<?php
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
