<?php
/**
 * Archive Page Template
 */

get_header();
?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<?php
			the_archive_title( '<h1 class="page-title">', '</h1>' );
			the_archive_description( '<div class="taxonomy-description">', '</div>' );
			
			if ( have_posts() ) :			
				while ( have_posts() ) : the_post();	
				
					get_template_part( 'templates/blocks/content', 'excerpt' );
		
				endwhile;
		
				the_posts_pagination( array(
					'prev_text' => '<span class="screen-reader-text">' . __( 'Previous page', 'mha_s2s' ) . '</span>',
					'next_text' => '<span class="screen-reader-text">' . __( 'Next page', 'mha_s2s' ) . '</span>',
					'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'mha_s2s' ) . ' </span>',
				) );
		
			else :
		
				get_template_part( 'templates/blocks/content', 'page' );
		
			endif; 
		?>
	</article>

<?php get_footer();
