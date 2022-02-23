<?php 
#header("HTTP/1.1 301 Moved Permanently"); 
#header("Location: /");

// Use a specific post ID for the 404 content
global $post; 
$post = get_post( get_field('404_post_id', 'options'), OBJECT );
setup_postdata( $post );

get_header(); 

// Hero
get_template_part( 'templates/blocks/block', 'hero' );
?>

<div class="wrap medium">
    <div class="bubble cerulean thin round-bl mb-5">
    <div class="inner">
        <div class="form-container line-form blue" id="search-form-interior">
            <?php echo get_search_form(); ?>
        </div>
    </div>
    </div>
</div>


<div class="wrap normal">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="page-intro">
            <?php the_content(); ?>				
        </div>
    </article>
</div>


<div class="clear pt-4">
    <?php 
        // Content Blocks
        wp_reset_query();
        if( have_rows('block') ):
        while ( have_rows('block') ) : the_row();
            $layout = get_row_layout();
            if( get_template_part( 'templates/blocks/block', $layout ) ):
                get_template_part( 'templates/blocks/block', $layout );
            endif;
        endwhile;
        endif;
    ?>
</div>

<?php
get_footer();