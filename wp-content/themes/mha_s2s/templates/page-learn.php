<?php 
/* Template Name: Learn */
get_header(); 
$search_query = get_query_var('search');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

    <div class="page-heading bar<?php echo $customClasses; ?>">	
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
            
            <div class="bubble cerulean round-bl mb-5">
            <div class="inner">
                <form method="GET" action="<?php echo get_the_permalink(get_the_ID()); ?>" class="form-container line-form blue">
                    <div class="container-fluid">
                    <div class="row">
                        <div class="col-12 col-md-8">
                            <p class="mb-0 wide block"><input id="search-archive" name="search" value="<?php echo $search_query; ?>" placeholder="Enter search terms here" type="text" /></p>
                        </div>
                        <div class="col-12 col-md-4 mt-3 mt-md-0">
                            <p class="mb-0 wide block"><input type="submit" class="button gform_button white block" value="Search" /></p>
                        </div>
                    </div>
                    </div>
                </form>
            </div>
            </div>
            
            <?php		
                $args = array(
                    "post_type" => 'article',
                    "orderby" => 'date',
                    "order"	=> 'ASC',
                    "post_status" => 'publish',
                    "posts_per_page" => 25,
                    "meta_query" => array(
                        array(
                            'key' => 'type',
                            'value' => 'condition'
                        )
                    )
                );
                if($search_query){
                    $args['s'] = $search_query;
                }
                $loop = new WP_Query($args);
                if ( $loop->have_posts() ) :	
                    $resources = array('condition');
                    echo '<ol class="plain mb-0">';
                    while($loop->have_posts()) : $loop->the_post();						
                        $type = get_field('type');                        
                        $article_type = get_field('type');
                        ?>
                        <li class="mb-4">
                            <p class="mb-2">	
                                <a class="dark-gray plain" href="<?php echo add_query_arg('ref',$term->term_id, get_the_permalink()); ?>"><?php the_title(); ?></a>
                            </p>
                            <div class="medium small pl-5"><?php echo short_excerpt(); ?></div>
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
                    
                endif; 
                wp_reset_query();
            ?>
            
        </div>
        </div>
        
    </div>
    </div>

</article>

<?php
get_footer();