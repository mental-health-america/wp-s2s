<?php 
/* Template Name: General Mental Health */
get_header();

$search_query = get_query_var('search') ? get_query_var('search') : '';
$search_term = get_query_var('search_term');
$pid = get_the_ID();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="page-heading bar">	
	<div class="wrap normal">	
		<?php the_title( '<h1 class="page-title">', '</h1>' ); ?>		
	</div>
	</div>

	<div class="page-content-archive">
			
		<div class="wrap medium" id="ac">	

			<?php 
				the_content(); 
				$order = get_query_var('order');
				$orderby = get_query_var('orderby');
			?>		

			<div class="bubble pale-blue bubble-border round-small">
			<div class="inner">	

				<div class="bubble cerulean thin round-bl mb-5">
				<div class="inner">
					<form method="GET" action="<?php echo get_the_permalink(); ?>#ac" class="form-container line-form blue">
						<div class="container-fluid">
						<div class="row">

							<div class="col-12 col-md-6">
								<p class="mb-0 wide block">
                                    <input id="search-archive" name="search" value="<?php echo $search_query; ?>" placeholder="Search <?php echo get_the_title(); ?> articles" type="text" />
                                </p>
							</div>

							<div class="col-12 col-md-3 mt-3 mt-md-0">
								<input type="hidden" name="order" value="<?php echo $order; ?>" />
								<input type="hidden" name="orderby" value="<?php echo $orderby; ?>" />
								<p class="m-0 wide block"><input type="submit" class="button gform_button white block pl-0 pr-0" value="Search" /></p>
							</div>

							<div class="col-12 col-md-3 mt-3 mt-md-0 pl-1 pr-1">								
								<div class="dropdown text-right pr-0 pr-md-4">
									<button class="button cerulean round dropdown-toggle normal-case mobile-wide block" type="button" id="archiveOrder" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-order="DESC" value="featured">
										<?php
											if($orderby == 'title' && $order == 'ASC'){
												echo 'A-Z';
											} elseif($orderby == 'title' && $order == 'DESC'){
												echo 'Z-A';
											} elseif($orderby == 'date' && $order == 'ASC'){
												echo 'Oldest';
											} elseif($orderby == 'date' && $order == 'DESC'){
												echo 'Newest';
											} else {
												echo 'Sort';
											}
										?>
									</button>
									<div class="dropdown-menu" aria-labelledby="orderSelection">
										<a href="<?php echo add_query_arg(
											array( 
												'search' => get_query_var('search'), 
												'order' => 'DESC',  
												'orderby' => 'default'
											), get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="" value="">Default</a>
										<a href="<?php echo add_query_arg(
											array( 
												'search' => get_query_var('search'), 
												'order' => 'ASC',  
												'orderby' => 'title'
											), get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="ASC" value="title">A-Z</a>
										<a href="<?php echo add_query_arg(
											array( 
												'search' => get_query_var('search'), 
												'order' => 'DESC', 
												'orderby' => 'title'
											), get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="DESC" value="title">Z-A</a>
										<a href="<?php echo add_query_arg(
											array( 
												'search' => get_query_var('search'), 
												'order' => 'DESC', 
												'orderby' => 'date')
											, get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="DESC" value="date">Newest</a>
										<a href="<?php echo add_query_arg(
											array( 
												'search' => get_query_var('search'), 
												'order' => 'ASC',  
												'orderby' => 'date')
											, get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="ASC" value="date">Oldest</a>
									</div>
								</div>
							</div>

						</div>
						</div>
					</form>
				</div>
				</div>

				<?php	
					// Display related articles
					echo get_articles_by_custom_field('all_conditions', $search_query);
				?>

			</div>
			</div>

		</div>

		
		<div class="content-block block-two-column-text mt-5">
		<div class="wrap normal">
				
			<div class="two-cols top cols-40">
				<div class="left-col ">
				<div class="bubble round-br dark-blue normal">
					<div class="inner">			
						<?php 
                            echo '<h3>Learn About Mental Health Conditions</h3>';
                            echo do_shortcode('[mha_conditions]'); 
						?>
					</div>
				</div>    
				</div>

				<div class="right-col">
				</div>
			</div>

		</div>
		</div>

		<?php 
		if( have_rows('block', $pid ) ):
		echo '<div class="wrap normal mt-5 pt-5">';
		while ( have_rows('block', $pid ) ) : the_row();
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