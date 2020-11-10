<?php
/**
 * Simple Page Template
 */

get_header();

	while ( have_posts() ) : the_post();
		get_template_part( 'templates/blocks/content', 'page' );
	endwhile;
		
get_footer();
