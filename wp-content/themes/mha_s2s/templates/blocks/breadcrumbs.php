<p id="breadcrumbs">

    <a class="crumb" href="<?php site_url(); ?>">Home</a>

    <?php
        $post_id = get_the_ID();
        $type = get_field('type');

        $term_list = wp_get_post_terms($post->ID, 'category', ['fields' => 'all']);
        foreach($term_list as $term) {
            if( get_post_meta($post->ID, '_yoast_wpseo_primary_category',true) == $term->term_id ) {
                pre($term);
            }
        }

        // Main Filter Pages
        /*
        $ref_id = url_to_postid(wp_get_referer());
        $ref_template = get_page_template_slug( $ref_id );
        if( $ref_template == 'templates/page-path-collection.php' ){
            echo '<a class="crumb" href="'.get_the_permalink($ref_id).'">'.get_the_title($ref_id).'</a>';
        }
        */

        // Pathway Override
        if(get_query_var('pathway')){

            //echo '<a class="crumb" href="'.get_the_permalink($ref_id).'">'.get_the_title($ref_id).'</a>';
            $terms = get_the_terms(get_query_var('pathway'), 'condition');        
            $args = array(
                "post_type" => 'page',
                "post_status" => 'publish',
                "posts_per_page" => 1,
                "meta_query" => array(
                    array(
                        'key'   => $terms->taxonomy,
                        'value' => $terms->term_id
                    )
                )
            );
            $loop = new WP_Query($args);
            if($loop->have_posts()):
            while($loop->have_posts()) : $loop->the_post();
                echo '<a class="crumb" href="'.get_the_permalink(get_the_ID()).'">'.get_the_title(get_the_ID()).'</a>';
            endwhile;
            endif;
            
            echo '<span class="crumb">'.get_the_title(get_query_var('pathway')).'</span>';
            

        }

        // Current Page
        // echo get_the_title();

        wp_reset_query();

    ?>
</p>