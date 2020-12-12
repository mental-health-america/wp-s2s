<?php
/**
 * Simple Page Template
 */

get_header();
?>

	<?php
		while ( have_posts() ) : the_post();
			get_template_part( 'templates/blocks/content', 'page' );
		endwhile;
	?>

<?php
get_footer();
