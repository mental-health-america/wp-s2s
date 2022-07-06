<?php
/* Template Name: Search */
get_header(); 
?>


	<div class="wrap medium center mb-5">
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<div class="page-heading plain">			
			<?php if ( have_posts() ) : ?>
				<h1 class="page-title"><?php printf( __( 'Search Results for: %s', 'mha_s2s' ), '' ); ?></h1>
			<?php else : ?>
				<h1 class="page-title"><?php _e( 'No items found', 'mha_s2s' ); ?></h1>
			<?php endif; ?>
				<div class="page-intro">
					<p class="text-center bold large text-blue"><?php echo get_search_query(); ?></p>
				</div>
			</div>
		</article>
	</div>


	<div id="primary" class="content-area">
	<div class="wrap medium">		

		<?php
		if ( have_posts() ) :
			/* Start the Loop */
			while ( have_posts() ) : the_post();
			?>
			<article class="bubble round-small-br cerulean mb-4">
			<div class="inner">
				<?php the_title('<h3><a class="plain cerulean" href="'.get_the_permalink().'">','</a></h3>'); ?>
				<div class="text-gray mb-0">
					<?php echo the_excerpt(); ?> 
					<div class="mt-4"><strong><a class="cerulean button round" href="<?php echo get_the_permalink(); ?>">Continue reading <?php the_title(); ?> &raquo;</a></strong></div>
				</div>				
			</div>
			</article>

			<?php
			endwhile; // End of the loop.

			the_posts_pagination( array(
				'prev_text' => '<span class="screen-reader-text">' . __( 'Previous page', 'mha_s2s' ) . '</span>',
				'next_text' => '<span class="screen-reader-text">' . __( 'Next page', 'mha_s2s' ) . '</span>',
				'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'mha_s2s' ) . ' </span>',
			) );

		else: ?>

			<p><?php _e( 'Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'mha_s2s' ); ?></p>

			<div class="bubble cerulean thin round-bl mb-5">
			<div class="inner">
				<div class="form-container line-form blue" id="search-form-interior">
					<?php echo get_search_form(); ?>
				</div>
			</div>
			</div>

		<?php endif; ?>

	</div>
	</div>

</div>

<?php get_footer();
