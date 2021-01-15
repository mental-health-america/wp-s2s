<?php 
/* Template Name: Contact */
get_header(); 
?>

<div class="wrap medium">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="page-heading plain">			
            <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
        </div>
    </article>
</div>

<div class="wrap medium">
    <div class="bubble round-small bubble-border light-blue">
    <div class="inner">

        <div id="sign-up-form" class="form-container line-form wide blue">
            <?php the_content(); ?>
        </div>

    </div>
    </div>
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