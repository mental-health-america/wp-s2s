<?php
/**
 * Archive Page Template
 */

get_header();
$term = get_queried_object();
wp_reset_query();
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

				<div class="bubble cerulean round-bl mb-5">
				<div class="inner">
					<form role="search" method="get" id="searchform" action="<?php echo site_url(); ?>" class="form-container line-form blue">
						<div class="container-fluid">
						<div class="row">
							<div class="col-8">
								<p class="mb-0 wide block"><input id="search-archive" name="s" value="" placeholder="Enter search terms here" type="text" /></p>
							</div>
							<div class="col-4">
								<p class="mb-0 wide block"><input type="submit" class="button gform_button white block" value="Search" /></p>
							</div>
						</div>
						</div>
					</form>
				</div>
				</div>


				<?php
					if ( have_posts() ) :	
						echo '<ol class="plain mb-0">';
						while ( have_posts() ) : the_post();
						
							$type = get_field('type');
						?>

							<li class="mb-4">
								<p class="mb-0">									
									<a class="dark-gray plain" href="<?php echo add_query_arg('ref',$term->term_id, get_the_permalink()); ?>"><?php the_title(); ?></a>
								</p>
								<div class="medium small pl-5"><?php echo short_excerpt(); ?></div>
							</li>

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
