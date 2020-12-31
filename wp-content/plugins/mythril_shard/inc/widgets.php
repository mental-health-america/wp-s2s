<?php

/** 
 * Recent Article Submissions
 */
add_action('wp_dashboard_setup', 'recent_article_submissions_widget');  
function recent_article_submissions_widget() {
    global $wp_meta_boxes;    
    wp_add_dashboard_widget('custom_help_widget', 'Recent Article Drafts', 'recent_article_drafts');
} 
function recent_article_drafts() {

    $args = array(
        "post_type" => 'article',
        "orderby" => 'date',
        "order"	=> 'DESC',
        "post_status" => 'draft',
        "posts_per_page" => 10
    );
    $loop = new WP_Query($args);
    if($loop->have_posts()):
        echo '<div class="rss-widget"><ol>';
        while($loop->have_posts()) : $loop->the_post();
            echo '<li>'.get_the_date().'<br /><strong><a href="'.get_the_permalink().'">'.get_the_title().'</a></strong><br />'.get_the_author().'</li>';
        endwhile;
        echo '</ol></div>';
    endif;

}

?>