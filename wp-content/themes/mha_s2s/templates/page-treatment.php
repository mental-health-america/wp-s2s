<?php 
/* Template Name: Treatment */
get_header(); 
?>

<div class="wrap medium center">
    <?php
        while ( have_posts() ) : the_post();
            get_template_part( 'templates/blocks/content', 'plain' );
        endwhile;
    ?>
</div>

<div class="wrap medium">
    <div class="bubble round-small bubble-border light-blue">
    <div class="inner">


    </div>
    </div>
</div>

<?php
get_footer();