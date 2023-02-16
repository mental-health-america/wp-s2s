<?php
/**
 * Simple Page Template
 */

get_header();
$layout = get_layout_array(get_query_var('layout')); // Used for A/B testing
$wrap_width = get_field('page_content_width') ? get_field('page_content_width') : 'normal';
?>

	<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		<?php if(in_array('screen_header_v1', $layout)): ?>
			<div class="wrap normal">
				<div class="page-heading plain">			
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</div>
			</div>
		<?php else: ?>
			<div class="page-heading mint bar">	
			<div class="wrap <?php echo $wrap_width; ?>">		
				<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>			
			</div>
			</div>
		<?php endif; ?>

		<div class="wrap normal">
			<div class="page-intro">
				<?php the_content(); ?>				
			</div>
		</div>

	</article>

<?php
get_footer();
