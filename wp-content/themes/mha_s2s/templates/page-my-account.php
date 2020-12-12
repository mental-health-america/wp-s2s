<?php 
/* Template Name: My Account */
get_header(); 
?>

<?php
    while ( have_posts() ) : the_post();
        get_template_part( 'templates/blocks/content', 'account' );
    endwhile;
?>

<?php
get_footer();