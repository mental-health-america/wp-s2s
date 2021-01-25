<?php 

$actions = get_sub_field('call_to_action');

if($actions){
    foreach($actions as $pid){
        $post = get_post($pid); 
        setup_postdata($post);
        get_template_part( 'templates/blocks/block', 'cta' );  
    }
}

wp_reset_query();