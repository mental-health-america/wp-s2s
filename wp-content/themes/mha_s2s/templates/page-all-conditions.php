<?php 
/* Template Name: All Conditions */
get_header(); 
$search_query = get_query_var('search');
$post_id = get_the_ID();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <div class="page-heading bar">	
    <div class="wrap normal">	
        <?php
            the_title('<h1>','</h1>');
            if($search_query){
                echo '<p><strong class="text-cerulean">Search results articles containing: '.$search_query.'</strong></p>';
            }
        ?>		
    </div>
    </div>


    <div class="page-content">
    <div class="wrap medium">

        <div class="bubble pale-blue bubble-border round-small">
        <div class="inner">	
            
            <div class="bubble cerulean thin round-bl mb-5">
            <div class="inner">
            <form method="GET" action="<?php echo get_the_permalink(get_the_ID()); ?>#ac" class="form-container line-form blue">
                <div class="container-fluid">
                <div class="row">

                    <div class="col-12 col-md-5">
                        <p class="mb-0 wide block"><input id="search-archive" name="search" value="<?php echo $search_query; ?>" placeholder="Search all articles" type="text" /></p>
                    </div>

                    <div class="col-12 col-md-3 mt-3 mt-md-0">
                        <input type="hidden" name="search_tag" value="<?php echo get_query_var('search_tag'); ?>" />
                        <input type="hidden" name="search_tax" value="<?php echo get_query_var('search_tax'); ?>" />
                        <input type="hidden" name="order" value="<?php echo get_query_var('order'); ?>" />
                        <input type="hidden" name="orderby" value="<?php echo get_query_var('orderby'); ?>" />
                        <p class="m-0 wide block"><input type="submit" class="button gform_button white block pl-0 pr-0" value="Search" /></p>
                    </div>

                    <div class="col-12 col-md-2 mt-3 mt-md-0 pl-1 pr-1">								
                        <div class="dropdown text-right pr-0">
                            <button class="button cerulean round dropdown-toggle normal-case mobile-wide block" type="button" id="archiveOrder" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-order="DESC" value="featured">
                                Sort 
                            </button>
                            <div class="dropdown-menu" aria-labelledby="orderSelection">
                                <a href="<?php echo add_query_arg(
                                    array( 
                                        'search' => get_query_var('search'), 
                                        'search_tag' => get_query_var('search_tag'), 
                                        'search_tax' => get_query_var('search_tax'), 
                                        'order' => 'ASC',  
                                        'orderby' => 'title'
                                    ), get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="" value="">Default</a>
                                <a href="<?php echo add_query_arg(
                                    array( 
                                        'search' => get_query_var('search'), 
                                        'search_tag' => get_query_var('search_tag'), 
                                        'search_tax' => get_query_var('search_tax'), 
                                        'order' => 'ASC',  
                                        'orderby' => 'title'
                                    ), get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="ASC" value="title">A-Z</a>
                                <a href="<?php echo add_query_arg(
                                    array( 
                                        'search' => get_query_var('search'), 
                                        'search_tag' => get_query_var('search_tag'), 
                                        'search_tax' => get_query_var('search_tax'), 
                                        'order' => 'DESC', 
                                        'orderby' => 'title'
                                    ), get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="DESC" value="title">Z-A</a>
                                <a href="<?php echo add_query_arg(
                                    array( 
                                        'search' => get_query_var('search'), 
                                        'search_tag' => get_query_var('search_tag'), 
                                        'search_tax' => get_query_var('search_tax'), 
                                        'order' => 'DESC', 
                                        'orderby' => 'date')
                                    , get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="DESC" value="date">Newest</a>
                                <a href="<?php echo add_query_arg(
                                    array( 
                                        'search' => get_query_var('search'), 
                                        'search_tag' => get_query_var('search_tag'), 
                                        'search_tax' => get_query_var('search_tax'), 
                                        'order' => 'ASC',  
                                        'orderby' => 'date')
                                    , get_the_permalink($post_id)); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="ASC" value="date">Oldest</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 col-md-2 mt-3 mt-md-0 pl-1 pr-1">								
                        <div class="dropdown text-right pr-0">
                            <button class="button cerulean round dropdown-toggle normal-case mobile-wide block" type="button" id="archiveOrder_tag" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-order="DESC" value="featured">
                                Tag
                            </button>
                            <div class="dropdown-menu" aria-labelledby="orderSelection">

                            <a href="<?php echo add_query_arg(
                                    array( 
                                        'search' => get_query_var('search'), 
                                        'search_tag' => '', 
                                        'search_tax' => '', 
                                        'order' => 'ASC',  
                                        'orderby' => 'title'
                                    ), get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button" data-order="" value="">None</a>

                                <?php
                                    // Condition Filters
                                    $query = get_terms(array(
                                        'taxonomy' => array('condition','post_tag'),
                                        'hide_empty' => true,
                                        'parent' => 0
                                    ));
                                    if($query){
                                        foreach($query as $term){
                                            if(!get_field('hide_on_front_end', $term->taxonomy.'_'.$term->term_id)){
                                            ?>
                                                <a href="<?php echo add_query_arg(
                                                    array( 
                                                        'search' => get_query_var('search'), 
                                                        'search_tag' => $term->term_id, 
                                                        'search_tax' => $term->taxonomy, 
                                                        'order' => 'ASC',  
                                                        'orderby' => 'title'
                                                    ), get_the_permalink()); ?>#content" class="dropdown-item normal-case archive-filter-order" type="button"><?php echo $term->name; ?></a>
                                            <?php
                                            }
                                        }
                                    }
                                ?>                                
                            </div>
                        </div>
                    </div>

                </div>
                </div>
            </form>	
            </div>
            </div>
            
            <?php		
                if(get_query_var('order')){
                    $order = get_query_var('order');
                } else {
                    $order = 'DESC';
                }

                if(get_query_var('orderby')){
                    $orderby = get_query_var('orderby');
                } else {
                    $orderby = array('meta_value' => 'DESC', 'date' => 'DESC');
                }

                $args = array(
                    "post_type" => 'article',
                    "orderby" => $orderby,
                    "order"	=> $order,
                    "post_status" => 'publish',
                    "posts_per_page" => 25,
                    "meta_query" => array(
                        array(
                            'key' => 'all_conditions',
                            'value' => 1
                        )
                    )
                );

                if($search_query){
                    $args['s'] = $search_query;
                }

                if(get_query_var('search_tax') && get_query_var('search_tag')){
                    $args['tax_query'] = array(
                        array(
                            'taxonomy' => get_query_var('search_tax'),
                            'field'    => 'term_id',
                            'terms'    => get_query_var('search_tag'),
                        )
                    );
                }

                $loop = new WP_Query($args);
                if ( $loop->have_posts() ) :	
                    $resources = array('condition');
                    echo '<ol class="plain mb-0">';
                    while($loop->have_posts()) : $loop->the_post();		
                    ?>
                        <li class="mb-4">
                            <p class="mb-2">	
                                <a class="dark-gray plain" href="<?php echo add_query_arg('ref', $post_id, get_the_permalink()); ?>"><?php the_title(); ?></a>
                            </p>
                            <!--<div class="medium small pl-5"><?php echo short_excerpt(); ?></div>-->
                        </li>
                    <?php	
                    endwhile;
                    echo '</ol>';

                    echo '<div class="navigation pagination pt-5">';
                    echo paginate_links( array(
                        'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
                        'total'        => $loop->max_num_pages,
                        'current'      => max( 1, get_query_var( 'paged' ) ),
                        'format'       => '?paged=%#%',
                        'show_all'     => false,
                        'type'         => 'plain',
                        'end_size'     => 2,
                        'mid_size'     => 1,
                        'prev_next'    => true,
                        'prev_text'    => sprintf( '<i></i> %1$s', __( 'Previous', 'text-domain' ) ),
                        'next_text'    => sprintf( '%1$s <i></i>', __( 'Next', 'text-domain' ) ),
                        'add_args'     => false,
                        'add_fragment' => '',
                    ) );
                    echo '</div>';
                else:
                    echo '<p>There are no results for your search criteria. Please try another search.';
                endif; 
                wp_reset_query();
            ?>
            
        </div>
        </div>
        
    </div>
    </div>
    
    
    <?php
		// Content Blocks
		wp_reset_query();
		if( have_rows('block') ):
        echo '<div class="mt-5">';
		while ( have_rows('block') ) : the_row();
			$layout = get_row_layout();
			if( get_template_part( 'templates/blocks/block', $layout ) ):
				get_template_part( 'templates/blocks/block', $layout );
			endif;
        endwhile;
        echo '</div>';
		endif;
    ?>

</article>

<?php
get_footer();