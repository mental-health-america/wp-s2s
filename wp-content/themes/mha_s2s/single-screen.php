<?php
/**
 * Simple Page Template
 */

get_header();
?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<div class="wrap normal">
			<div class="page-heading plain">			
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
			</div>
		</div>

		<div class="wrap medium">
			<div class="page-intro">
				<?php the_content(); ?>				
			</div>
		</div>

	</article>

<?php
get_footer();
