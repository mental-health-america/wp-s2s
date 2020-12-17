<?php 
/* Template Name: Path Collection */
get_header(); 
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
			$popular = do_shortcode("[mha_popular_articles tag='$tag' style='inline']");
			if($popular):
		?>
			<div class="wrap wide mb-5">
				<div class="bubble short dark-blue round-br">
				<div class="inner">
					<h3>Popular Articles</h3>
					<?php echo $popular; ?>
				</div>
				</div>
			</div>
		<?php endif; ?>
		
		<div class="wrap wide">
			<?php
				$args = array(
					"post_type" => 'reading_path',
					"orderby" => 'menu_order',
					"order"	=> 'ASC',
					"post_status" => 'publish',
					"posts_per_page" => 9999
				);
				$loop = new WP_Query($args);
				if($loop->have_posts()):
				while($loop->have_posts()) : $loop->the_post();
					$path_id = get_the_ID();
				?>  

					<div class="bubble round-tl cerulean mb-5 bubble-border path-container">
					<div class="inner">
						<?php the_title('<h3>','</h3>'); ?> 
						
						<ol class="path-list">
							<?php
								$counter = 0;
								$path = get_field('path');
								$max = count($path);
								if( have_rows('path') ):
								while( have_rows('path') ) : the_row();
									$article = get_sub_field('article');
									echo '<li class="path-item"><a class="button round cerulean block" href="'.add_query_arg('pathway', $path_id, get_the_permalink($article)).'">'.get_the_title($article).'</a></li>';
									$counter++;
									if($counter < $max){
										echo '<li class="path-spacer"></li>';
									}
								endwhile;
								endif;
							?>
						</ol>
						
					</div>
					</div>

				<?php 
				endwhile; 
				endif;
			?>
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
