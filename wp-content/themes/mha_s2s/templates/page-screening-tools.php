<?php 
/* Template Name: Take a Screen */
get_header(); 
?>

<?php the_content(); ?>

<?php
    $args = array(
        "post_type" => 'screen',
        "order"	=> 'ASC',
        "post_status" => 'publish',
        "orderby" => 'date',
        "posts_per_page" => 25
    );
    $loop = new WP_Query($args);
    while($loop->have_posts()) : $loop->the_post();
?>  
    
    <a href="<?php echo get_the_permalink(); ?>">
        <?php the_title('<h2>','</h2>'); ?>
    </a>

<?php endwhile; ?>

<?php
get_footer();