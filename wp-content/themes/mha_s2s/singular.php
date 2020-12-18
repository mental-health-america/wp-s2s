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


		// Reading Path
		$more_links = get_field('more_links');

		if(get_query_var('pathway') || have_rows('more_links')):

			$path_id = get_query_var('pathway');
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
			?>  
				<div class="wrap normal">

					<?php if($next_id): ?>
					<p class="text-right mt-3 pt-3 mb-5">
						<a class="button round-small-tl cerulean next" href="<?php echo add_query_arg('pathway', $path_id, get_the_permalink($next_id)); ?>">Next Article</a>
					</p>
					<?php endif; ?>

					<div class="bubble round-tl cerulean mb-5 bubble-border path-container">
					<div class="inner">

						<?php if($path_id): ?>
							<h3><?php echo get_the_title($path_id); ?></h3>
							<ol class="path-list">
								<?php
									$counter = 0;
									$max = count($path);
									if( have_rows('path', $path_id) ):
									while( have_rows('path', $path_id) ) : the_row();
										$article = get_sub_field('article');
										$current_class = '';
										if($current == $article){
											$current_class = ' current';
										}
										echo '<li class="path-item"><a class="button round thin cerulean block'.$current_class.'" href="'.add_query_arg('pathway', $path_id, get_the_permalink($article)).'">'.get_the_title($article).'</a></li>';
										$counter++;
										if($counter < $max){
											echo '<li class="path-spacer"></li>';
										}
									endwhile;
									endif;
								?>
							</ol>
						<?php endif; ?>


						<?php 
							if(have_rows('more_links', $current)): 
								if($next_id){
									$top_padding = ' mt-5';
								} else {
									$top_padding = '';
								}
								echo '<h4 class="thin'.$top_padding.'">More Links</h4>';
								while( have_rows('more_links', $current) ) : the_row();

									$page = get_sub_field('page');
									echo '<a class="button cerulean thin round blue mr-3 mb-3" href="'.get_the_permalink($page).'">';
										if(get_sub_field('custom_title')){
											the_sub_field('custom_title');
										} else {
											echo get_the_title($page);
										}
									echo '</a>';

								endwhile;
							endif; 
						?>
						
					</div>
					</div>
					
				</div>

			<?php 
		endif;
	?>

<?php
get_footer();
