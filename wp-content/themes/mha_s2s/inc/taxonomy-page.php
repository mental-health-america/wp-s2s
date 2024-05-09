<?php
get_header();
$term = get_queried_object();
$espanol = get_field('espanol');
if(get_query_var('search')){
	$search_query = get_query_var('search');
} else {
	$search_query = '';
}
$search_tax = get_query_var('search_tax');
$search_term = get_query_var('search_term');

// Test CTAs
$test_cta = [];
$tindex = 0;
$args = array(
	"post_type"         => 'screen',
	"order"	            => 'DESC',
	"post_status"       => 'publish',
	"posts_per_page"    => 50,
	'fields' => 'ids'
);

if($espanol){
	$args['meta_key'] = 'espanol';
	$args['meta_value'] = 1;
} else {
	$args['tax_query'] = array(
		array(
			'taxonomy'          => $term->taxonomy,
			'field'             => 'term_id',
			'terms'             => $term->term_id
		)
	);
}
$loop = new WP_Query($args);
$cta_count = 0;
if($loop->have_posts()):
while($loop->have_posts()) : $loop->the_post(); 
	$primary_condition_yoast = get_post_meta(get_the_ID(),'_yoast_wpseo_primary_condition', true);
	if($espanol){
		if( $cta_count > 1 ){
			continue;
		}
	} else if( get_field('invisible') || get_field('survey') || $primary_condition_yoast != $term->term_id || $cta_count > 0 || get_field('espanol') ){
		continue;
	}
	$test_cta[] = get_the_ID(); 
	$cta_count++;
endwhile;
endif;
shuffle($test_cta);
wp_reset_query();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="page-heading bar">	
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

	<div class="page-content-archive">
			
		<div class="wrap medium" id="ac">	

			<?php 
				the_archive_description(); 
				$order = get_query_var('order');
				$orderby = get_query_var('orderby');
			?>		

			<div class="bubble pale-blue bubble-border round-small">
			<div class="inner">	

				<div class="bubble cerulean thin round-bl mb-5">
				<div class="inner">
					<form method="GET" action="<?php echo get_term_link($term); ?>#ac" class="form-container line-form blue">
						<div class="container-fluid">
						<div class="row">

							<div class="col-12 col-md-6">
								<p class="mb-0 wide block"><input id="search-archive" name="search" value="<?php echo $search_query; ?>" placeholder="<?php echo $espanol ? 'Busca recursos en español' : 'Search '.$term->name.' articles'; ?>" type="text" /></p>
							</div>

							<div class="col-12 col-md-3 mt-3 mt-md-0">
								<input type="hidden" name="search_tag" value="<?php echo $term->term_id; ?>" />
								<input type="hidden" name="search_taxonomy" value="<?php echo $term->taxonomy; ?>" />
								<input type="hidden" name="order" value="<?php echo $order; ?>" />
								<input type="hidden" name="orderby" value="<?php echo $orderby; ?>" />
								<p class="m-0 wide block"><input type="submit" class="button gform_button white block pl-0 pr-0" value="<?php echo $espanol ? 'Buscar' : 'Search'; ?>" /></p>
							</div>

							<div class="col-12 col-md-3 mt-3 mt-md-0 pl-1 pr-1">	
								<?php if(!$espanol): ?>							
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
												'search_tag' => get_query_var('search_tag'), 
												'search_taxonomy' => get_query_var('search_taxonomy'), 
												'order' => 'DESC',  
												'orderby' => 'default'
											), get_term_link($term)); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="" value="">Default</a>
										<a href="<?php echo add_query_arg(
											array( 
												'search' => get_query_var('search'), 
												'search_tag' => get_query_var('search_tag'), 
												'search_taxonomy' => get_query_var('search_taxonomy'), 
												'order' => 'ASC',  
												'orderby' => 'title'
											), get_term_link($term)); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="ASC" value="title">A-Z</a>
										<a href="<?php echo add_query_arg(
											array( 
												'search' => get_query_var('search'), 
												'search_tag' => get_query_var('search_tag'), 
												'search_taxonomy' => get_query_var('search_taxonomy'), 
												'order' => 'DESC', 
												'orderby' => 'title'
											), get_term_link($term)); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="DESC" value="title">Z-A</a>
										<a href="<?php echo add_query_arg(
											array( 
												'search' => get_query_var('search'), 
												'search_tag' => get_query_var('search_tag'), 
												'search_taxonomy' => get_query_var('search_taxonomy'), 
												'order' => 'DESC', 
												'orderby' => 'date')
											, get_term_link($term)); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="DESC" value="date">Newest</a>
										<a href="<?php echo add_query_arg(
											array( 
												'search' => get_query_var('search'), 
												'search_tag' => get_query_var('search_tag'), 
												'search_taxonomy' => get_query_var('search_taxonomy'), 
												'order' => 'ASC',  
												'orderby' => 'date')
											, get_term_link($term)); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="ASC" value="date">Oldest</a>
									</div>
								</div>
								<?php endif; ?>
							</div>

						</div>
						</div>
					</form>
				</div>
				</div>

				<?php	
					// Display related articles
					echo get_condition_articles($term->taxonomy, $term->term_id, $search_query, $espanol);
				?>

			</div>
			</div>

		</div>

		<div class="content-block block-two-column-text mt-5">
		<div class="wrap normal">
				
			<?php 
				$col_width = $espanol ? 50 : 40;
			?>
			<div class="two-cols top cols-<?php echo $col_width; ?>">

				<div class="left-col ">

					<?php if(!$espanol): ?>
						<div class="bubble round-br dark-blue normal">
						<div class="inner">		

							<?php 
								$related_conditions = get_field('related_conditions', $term);
								if($related_conditions && count($related_conditions) > 0 ){
									echo '<h3>Learn About Other Related Mental Health Conditions</h3>';
									echo '<div class="conditions-list">';
									$rc_counter = 1;
									foreach($related_conditions as $rc){
										echo '<a class="plain cerulean" href="'.get_term_link($rc).'">'.$rc->name.'</a>';	 
										if($rc_counter < count($related_conditions)){
											echo ' &nbsp;<span class="noto" role="separator">|</span>&nbsp; ';
										}
										$rc_counter++;
									}
									echo '</div>';
								} else {
									echo '<h3>Learn About Mental Health Conditions</h3>';
									echo do_shortcode('[mha_conditions]'); 
								}
							?>						
						</div>
						</div>
					<?php else: ?>  

						<?php
							// Display randomized matching test CTA
							if(!empty($test_cta) && isset($test_cta[$tindex]) ):
								?>
									<div class="bubble round-tl orange normal">
									<div class="inner">
										<?php
											$an_a = ' '; 
											$title = get_the_title($test_cta[$tindex]);
											if($title[$tindex] == 'A'){
												$an_a = 'n ';
											}
											$test_espanol = get_field('espanol',$test_cta[$tindex]);
											$test_title = $test_espanol ? "Toma un $title": "$an_a $title";
										?>
										<h3><?php echo $test_title; ?></h3> 
										<div class="excerpt"><p><?php echo get_the_excerpt($test_cta[$tindex]); ?></p></div>
										<div class="text-center pb-3"><a href="<?php echo get_the_permalink($test_cta[$tindex]); ?>" class="button white round text-orange"><?php echo $test_title; ?></a></div>
									</div>
									</div>
								<?php
							endif;
							$tindex++;
						?>
						
					<?php endif; ?>
				</div>

				<div class="right-col">
					
					<?php
						// Display randomized matching test CTA
						if(!empty($test_cta) && isset($test_cta[$tindex]) ):
							?>
								<div class="bubble round-tl orange normal">
								<div class="inner">
									<?php
										$an_a = ' '; 
										$title = get_the_title($test_cta[$tindex]);
										if($title[$tindex] == 'A'){
											$an_a = 'n ';
										}
										$test_espanol = get_field('espanol',$test_cta[$tindex]);
										$test_title = $test_espanol ? "Toma un $title": "$an_a $title";
									?>
									<h3><?php echo $test_title; ?></h3> 
									<div class="excerpt"><p><?php echo get_the_excerpt($test_cta[$tindex]); ?></p></div>
									<div class="text-center pb-3"><a href="<?php echo get_the_permalink($test_cta[$tindex]); ?>" class="button white round text-orange"><?php echo $test_title; ?></a></div>
								</div>
								</div>
							<?php
						endif;
						wp_reset_query();
					?>
					
				</div>
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