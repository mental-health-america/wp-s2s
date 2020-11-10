<?php 
/* Template Name: Example */
get_header(); 
?>

<?php
    $args = array(
        "category__in" => 3,
        "order"	=> 'ASC',
        "post_status" => 'publish',
        "orderby" => 'date',
        "posts_per_page" => 25
    );
    $loop = new WP_Query($args);
    while($loop->have_posts()) : $loop->the_post();
?>  
    
    <?php the_title('<h1>','</h1>'); ?>   
    <div class="text"><?php the_content(); ?></div>       

<?php endwhile; ?>

<?php
get_footer();