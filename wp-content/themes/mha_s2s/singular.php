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
			<div class="wrap normal show-tablet mt-4">
				<?php get_template_part( 'templates/blocks/article', 'actions', array( 'placement' => 'mobile' ) ); ?>
			</div>
		<?php 
		endwhile;
		endif; 

		// Reading Path
		//$resources = array('diy','connect','treatment','provider');
		$resources = array('diy','connect','provider');
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
			if( is_array($article_type) && count(array_intersect($article_type, $resources)) > 0){ 
				$bubble_color = 'red';
				$button_color = 'red';
			}
			?>  
				<div class="wrap normal">

					<?php if($next_id): ?>
					<p class="text-left mt-3 pt-5 mb-5">
						<a class="button round-small-tl thick red next next-article" href="<?php echo add_query_arg('pathway', $path_id, get_the_permalink($next_id)); ?>">
							<?php if(get_field('espanol')): ?>
								Siguiente artículo
							<?php else: ?>
								Next Article
							<?php endif; ?>
						</a>
					</p>
					<?php endif; ?>

					<div id="article--reading-path" class="bubble round-tl <?php echo $bubble_color; ?> mb-5 bubble-border path-container mt-5" role="complementary">
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
								
								if(get_field('espanol',$current)){
									echo '<h4 class="thin">Más recursos</h4>';
								} else {
									echo '<h4 class="thin">More Links</h4>';
								}

								while( have_rows('more_links', $current) ) : the_row();

									$page = get_sub_field('page');
									if($page){
										if(!in_array($page->ID, $link_skip)){ // Skip pathway links to avoid duplicates
											echo '<a class="button '.$button_color.' text-normal-case thin round mr-3 mb-3" href="'.get_the_permalink($page).'">';
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

							// Display the path
							if($path_id){
								get_template_part( 'templates/blocks/reading', 'path', array( 
									'no_wrapper' => 0, 
									'path_id' => $path_id, 
									'article_type' => $article_type, 
									'resources' => $resources 
								) );
							} 
						?>
						
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
				<div class="article-right col-12 col-lg-4 pl-0 pr-0 pl-lg-5 pt-3 mt-3 show-tablet">
					<?php 
						if(get_field('espanol')){
							get_template_part( 'templates/blocks/article', 'sidebar-espanol', array( 'placement' => 'mobile' ) ); 
						} else {
							get_template_part( 'templates/blocks/article', 'sidebar', array( 'placement' => 'mobile' ) ); 
						}
					?>
				</div>	
			</div>
		<?php 
		endwhile;
		endif; 
	?>

<?php
get_footer();
