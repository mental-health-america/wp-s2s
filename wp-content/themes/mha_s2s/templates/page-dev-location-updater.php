<?php 
/* Template Name: Location Updater */
get_header(); 


// Update all posts with "provider" 

// Get Articles
$args = array(
    "post_type" => 'article',
    "posts_per_page" => -1,
    "post_status" => 'publish',
    "meta_query" => array(
        array(
            'key' => 'type',
            'value' => 'provider',
            'compare' => 'LIKE'
        )
    ),
);

$articles_query = new WP_Query($args);
$article_posts = [];

if($articles_query->have_posts()):
while($articles_query->have_posts()) : $articles_query->the_post();
    $article_posts[] = get_the_ID();
endwhile;
endif;

pre($article_posts);

$updated = [];

foreach($article_posts as $ap){
    $updated[$ap] = wp_update_post( array( 'ID' => $ap ) );
}

pre($updated);

get_footer();