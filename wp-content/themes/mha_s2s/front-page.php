<?php
/**
 * The front page template file
 *
 * If the user has selected a static page for their homepage, this is what will
 * appear.
 * Learn more: https://codex.wordpress.org/Template_Hierarchy
 *
 * @package MHA S2S
 * @subpackage MHA S2S
 * @since 1.0
 * @version 1.0
 */

get_header(); 
?>

    <?php
        // Hero
		get_template_part( 'templates/blocks/block', 'hero' );

        // Normal Content
        /*
		while ( have_posts() ) : the_post();
			get_template_part( 'templates/blocks/content', 'page' );
        endwhile;
        */
		
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

<?php get_footer();
