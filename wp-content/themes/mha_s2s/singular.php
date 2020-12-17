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
		if(get_query_var('pathway')):
			$pathway = get_query_var('pathway');
			$current = get_the_ID();
			$path = get_field('path', $pathway);

			// Get Next Article in path
			$next = false;
			$next_id = false;
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
			$reading_path = '<h3>Reading Path:</h3>';
			?>  
				<div class="wrap normal">

					<?php if($next_id): ?>
					<p class="text-right">
						<a class="button round-small-tl cerulean next" href="<?php echo get_the_permalink($next_id); ?>">Next Article</a>
					</p>
					<?php endif; ?>

					<div class="bubble round-tl cerulean mb-5 bubble-border path-container">
					<div class="inner">

						<?php if($pathway): ?>
							<ol class="path-list">
								<?php
									$counter = 0;
									$max = count($path);
									if( have_rows('path', $pathway) ):
									while( have_rows('path', $pathway) ) : the_row();
										$article = get_sub_field('article');
										$current_class = '';
										if($current == $article){
											$current_class = ' current';
										}
										echo '<li class="path-item"><a class="button round cerulean block'.$current_class.'" href="'.add_query_arg('pathway', $path_id, get_the_permalink($article)).'">'.get_the_title($article).'</a></li>';
										$counter++;
										if($counter < $max){
											echo '<li class="path-spacer"></li>';
										}
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
get_footer();
