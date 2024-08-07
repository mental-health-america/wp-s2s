<?php
/**
 * Archive Page Template
 */

get_header();
$term = get_queried_object();
$search_query = get_query_var('search');
$search_tax = get_query_var('search_tax');
$search_term = get_query_var('search_term');
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

	<div class="page-content-archive">
			
		<div class="wrap medium" id="ac">	

			<?php the_archive_description(); ?>		

			<div class="bubble pale-blue bubble-border round-small">
			<div class="inner">	

				<div class="bubble cerulean thin round-bl mb-5">
				<div class="inner">
                	<form method="GET" action="<?php echo get_term_link($term); ?>#ac" class="form-container line-form blue">
						<div class="container-fluid">
						<div class="row">

                        	<div class="col-12 col-md-6">
								<p class="mb-0 wide block"><input id="search-archive" name="search" value="<?php echo $search_query; ?>" placeholder="Search <?php echo $term->name; ?> articles" type="text" /></p>
							</div>

							<div class="col-12 col-md-3 mt-3 mt-md-0">
								<input type="hidden" name="search_tag" value="<?php echo $term->term_id; ?>" />
								<input type="hidden" name="search_taxonomy" value="<?php echo $term->taxonomy; ?>" />
								<input type="hidden" name="order" value="<?php echo get_query_var('order'); ?>" />
								<input type="hidden" name="orderby" value="<?php echo get_query_var('orderby'); ?>" />
								<p class="m-0 wide block"><input type="submit" class="button gform_button white block pl-0 pr-0" value="Search" /></p>
							</div>

							<div class="col-12 col-md-3 mt-3 mt-md-0 pl-1 pr-1">								
								<div class="dropdown text-right pr-0 pr-md-4">
									<button class="button cerulean round dropdown-toggle normal-case mobile-wide block" type="button" id="archiveOrder" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-order="DESC" value="featured">
										Sort
									</button>
									<div class="dropdown-menu" aria-labelledby="orderSelection">
										<a href="<?php get_term_link($term); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="DESC" value="featured">Default</a>
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
							</div>

						</div>
						</div>
					</form>
				</div>
				</div>

				<?php					
					if ( have_posts() ) :	
						$resources = array('condition');
						echo '<ol class="plain mb-0">';
						while ( have_posts() ) : the_post();	
							$type = get_field('type');                        
							$article_type = get_field('type');
							?>
							<li class="mb-4">
								<p class="mb-2">	
									<a class="dark-gray plain" href="<?php echo add_query_arg('ref', $term->term_id, get_the_permalink()); ?>"><?php the_title(); ?></a>
								</p>
								<!--<div class="medium small pl-5"><?php echo short_excerpt(); ?></div>-->
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

		
		<div class="content-block block-two-column-text mt-5">
		<div class="wrap normal">
				
			<div class="two-cols top cols-40">
				<div class="left-col ">
				<div class="bubble round-br dark-blue normal">
					<div class="inner">			
						<h3>Learn About Mental Health Conditions</h3>
						<?php echo do_shortcode('[mha_conditions]'); ?>
					</div>
				</div>    
				</div>

				<div class="right-col">
					<?php 
						$args = array(
							"post_type"         => 'screen',
							"order"	            => 'DESC',
							"post_status"       => 'publish',
							"posts_per_page"    => 1,
							'tax_query'      => array(
								array(
									'taxonomy'          => $term->taxonomy,
									'field'             => 'term_id',
									'terms'             => $term->term_id
								),
							),
							'meta_query' => array( 
								'relation' => 'OR',
								array(
									'key' => 'espanol',
									'value' => '1',
									'compare' => '!='
								),
								array(
									'key' => 'espanol',
									'value' => '1',
									'compare' => 'NOT EXISTS'
								)
							)
						);
						$loop = new WP_Query($args);
						if($loop->have_posts()):
						?>
							<div class="bubble round-tl orange normal">
							<div class="inner">
							<?php while($loop->have_posts()) : $loop->the_post(); ?>   
								<?php
									$an_a = 'Take a';
									$title = get_the_title();
									if($title[0] == 'A'){
										$an_a .= 'n ';
									}
								?>
								<?php the_title('<h3>Take a'.$an_a,'</h3>'); ?>   
								<div class="excerpt"><?php the_excerpt(); ?></div>
								<div class="text-center pb-3"><a href="<?php echo get_the_permalink(); ?>" class="button white round text-orange">Take a<?php echo $an_a; ?> <?php the_title(); ?></a></div>
							<?php endwhile; ?>
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
