<?php
/**
 * Archive Page Template
 */

get_header();
$term = get_queried_object();
?>


<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="page-heading bar<?php echo $customClasses; ?>">	
	<div class="wrap normal">		
		
		<?php
			if(get_field('custom_title', $term)){
				echo '<h1 class="page-title">'.get_field('custom_title', $term).'</h1>';
			} else {
				the_archive_title( '<h1 class="page-title">', '</h1>' ); 
			}
		?>
		
	</div>
	</div>

	<div class="page-content">
			
		<div class="wrap medium">	

			<?php the_archive_description(); ?>		

			<div class="bubble pale-blue bubble-border round-small">
			<div class="inner">	
				<?php
					if ( have_posts() ) :	
						echo '<ol class="plain mb-0">';
						while ( have_posts() ) : the_post();	
						?>

							<li class="mb-4"><a class="dark-gray plain" href="<?php echo get_the_permalink(); ?>"><?php the_title(); ?></a></li>

						<?php			
						endwhile;
						echo '</ol>';
				
						the_posts_pagination( array(
							'prev_text' => '<span class="screen-reader-text">' . __( 'Previous page', 'mha_s2s' ) . '</span>',
							'next_text' => '<span class="screen-reader-text">' . __( 'Next page', 'mha_s2s' ) . '</span>',
							'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'mha_s2s' ) . ' </span>',
						));		
					endif; 
				?>
			</div>
			</div>

		</div>

		<?php 
		if( have_rows('block', $term) ):
		echo '<div class="wrap normal mt-5 pt-5">';
		while ( have_rows('block', $term) ) : the_row();
			$layout = get_row_layout();
			if( get_template_part( 'templates/blocks/block', $layout ) ):
				get_template_part( 'templates/blocks/block', $layout );
			endif;
		endwhile;
		echo '</div>';
		endif;
		?>

	</div>

</article>


<?php get_footer();
