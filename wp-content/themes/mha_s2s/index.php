<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package MHA S2S
 * @subpackage MHA S2S
 * @since 1.0
 * @version 1.0
 */

get_header(); ?>

	<?php
		// Having a URL parameter on the homepage breaks when its part of query_vars, so set this as the homepage as a failsafe?
		if( get_the_ID() == 1 || !get_the_ID() ):
			global $post; 
			$post = get_post( get_option('page_on_front'), OBJECT );
			setup_postdata( $post );
		endif;

		// Hero
		get_template_part( 'templates/blocks/block', 'hero' );

		// Normal Content
		/*
		while ( have_posts() ) : the_post();
			get_template_part( 'templates/blocks/content', 'page' );
		endwhile;
		*/
		
		// Content Blocks
		if( have_rows('block') ):
		while ( have_rows('block') ) : the_row();
		
			$layout = get_row_layout();
			if( get_template_part( 'templates/blocks/block', $layout ) ):
				get_template_part( 'templates/blocks/block', $layout );
			endif;
			
		endwhile;
		endif;
		wp_reset_postdata();
	?>

<?php get_footer();
