<?php
/**
 * Simple Page Template
 */

get_header();
?>

	<?php
		// Hero
		get_template_part( 'templates/blocks/block', 'hero' );

		// Normal Content
		$post_type = get_post_type();
		while ( have_posts() ) : the_post();
			if($post_type == 'article'){
				get_template_part( 'templates/blocks/content', 'article' );
			} else {
				get_template_part( 'templates/blocks/content', 'page' );
			}
		endwhile;
		
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

		// Mobile Article Actions 
		if($post_type == 'article') :
		while ( have_posts() ) : the_post();
		?>
			<div class="wrap normal show-mobile mt-4">
				<?php get_template_part( 'templates/blocks/article', 'actions' ); ?>
			</div>
		<?php 
		endwhile;
		endif; 

		// Reading Path
		$resources = array('diy','connect','treatment','provider');
		$article_type = get_field('type');
		$link_skip = [];

		if(get_query_var('pathway')){
			$path_id = get_query_var('pathway');
		} else if(get_field('default_pathway')){
			$path_id = get_field('default_pathway');
		} else {
			$path_id = null;
		}

		if($path_id || have_rows('more_links')):

			$current = get_the_ID();
			$path = get_field('path', $path_id);

			// Get Next Article in path
			$next = false;
			$next_id = false;
			if(get_field('path', $path_id)){
				foreach($path as $p){
					if($next == false){
						if($current == $p['article']) {
							$next = true;
						}
					} else {
						$next_id = $p['article'];
						break;
					}
				}
			}

			$bubble_color = 'cerulean';
			$button_color = 'cerulean';
			if(count(array_intersect($article_type, $resources)) > 0){ 
				$bubble_color = 'red';
				$button_color = 'red';
			}
			?>  
				<div class="wrap normal">

					<?php if($next_id): ?>
					<p class="text-left mt-3 pt-5 mb-5">
						<a class="button round-small-tl thick <?php echo $button_color; ?> next next-article" href="<?php echo add_query_arg('pathway', $path_id, get_the_permalink($next_id)); ?>">Next Article</a>
					</p>
					<?php endif; ?>

					<div class="bubble round-tl <?php echo $bubble_color; ?> mb-5 bubble-border path-container mt-5">
					<div class="inner">

						<?php 
							/**
							 * Check for duplicate articles in the pathway below and don't show them
							 * in the "More Links" section
							 */
							if($path_id){
								if( have_rows('path', $path_id) ):
								while( have_rows('path', $path_id) ) : the_row();
									$article = get_sub_field('article');
									$link_skip[] = $article;
								endwhile;
								endif;
							}
						?>

						<?php 
							/**
							 * More Links
							 */
							if(have_rows('more_links', $current)): 
								echo '<div class="pl-2 mb-4">';
								echo '<h4 class="thin">More Links</h4>';
								while( have_rows('more_links', $current) ) : the_row();

									$page = get_sub_field('page');
									if($page){
										if(!in_array($page->ID, $link_skip)){ // Skip pathway links to avoid duplicates
											echo '<a class="button '.$button_color.' thin round mr-3 mb-3" href="'.get_the_permalink($page).'">';
												if(get_sub_field('custom_title')){
													the_sub_field('custom_title');
												} else {
													echo get_the_title($page);
												}
											echo '</a>';
										}
									}

								endwhile;
								echo '</div>';
							endif; 
						?>

						<?php if($path_id): ?>
							<h3><?php echo get_the_title($path_id); ?></h3>
							<ol class="path-list">
								<?php
									$counter = 0;
									$delay = 0;
									$spacer_counter_wide = 0;
									$spacer_counter_narrow = 0;
									$max = count($path);
									if( have_rows('path', $path_id) ):
									while( have_rows('path', $path_id) ) : the_row();
										$article = get_sub_field('article');
										$current_class = '';
										if($current == $article){
											$current_class = ' current';
										}
										echo '<li class="path-item wow fadeIn" data-wow-delay="'.($delay).'s">';
											echo '<a class="button round-tiny thin '.$button_color.' block'.$current_class.'" href="'.add_query_arg('pathway', $path_id, get_the_permalink($article)).'">';
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
						<?php endif; ?>
						
					</div>
					</div>
					
				</div>

			<?php 
		endif;
	?>

	<?php 
		// Mobile Article Sidebar 
		if($post_type == 'article') :
		while ( have_posts() ) : the_post();
		?>
			<div class="wrap normal">
				<div class="article-right col-12 col-md-5 col-lg-4 pl-0 pr-0 pl-md-5 pt-3 mt-3 show-mobile">
					<?php get_template_part( 'templates/blocks/article', 'sidebar' ); ?>
				</div>	
			</div>
		<?php 
		endwhile;
		endif; 
	?>

<?php
get_footer();
