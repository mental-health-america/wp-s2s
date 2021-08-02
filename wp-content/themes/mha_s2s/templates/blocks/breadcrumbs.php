<p id="breadcrumbs">

    <a class="crumb crumb-home" href="<?php echo site_url(); ?>">Home</a>

    <?php
        $post_id = get_the_ID();
        $type = get_field('type');

        if(get_field('espanol')):

            // Spanish Breadcrumbs
            echo '<span class="crumb">Recursos en español</span>';

        else:

            // English Breadcrumbs

            /**
             * Pathway Breadcrumbs
             */
            if(get_query_var('pathway')){
                
                $ref_id = get_query_var('pathway');
                $path_terms = get_the_terms($ref_id, 'condition');

                if($path_terms){
                    $args = array(
                        'post_type'  => 'page', 
                        "post_status" => 'publish',
                        "posts_per_page" => 1,
                        'meta_query' => array( 
                            'relation' => 'AND',
                            array(
                                'key'   => '_wp_page_template', 
                                'value' => 'templates/page-path-collection.php'
                            ),
                            array(
                                'key'   => 'condition', 
                                'value' => $path_terms[0]->term_id
                            )
                        )
                    );
                    $loop = new WP_Query($args);
                    if($loop->have_posts()):
                    while($loop->have_posts()) : $loop->the_post();  
                        echo '<a class="crumb crumb-primary" href="'.get_the_permalink().'">'.get_the_title().'</a>';
                    endwhile;
                    endif;
                    wp_reset_query();
                }
                    
                echo '<span class="crumb">'.get_the_title($ref_id).'</span>';
                            
            }

            /**
             * Resource Breadcrumbs
             */
            else if(get_field('type')){
                $type = get_field('type');            
                foreach($type as $t){
                    if($t == 'diy'){
                        echo '<a class="crumb crumb-type" href="/diy-tools">DIY Tools</a>';
                    }
                    else if($t == 'connect'){
                        echo '<a class="crumb crumb-type" href="/connect">Connect</a>';
                    }
                    else if($t == 'treatment'){
                        echo '<a class="crumb crumb-type" href="/treatment">Treatment Info</a>';
                    }
                    else if($t == 'provider'){
                        echo '<a class="crumb crumb-type" href="/get-help">Get Help</a>';
                    }
                    else if($t == 'condition'){
                        
                        if(get_field('primary_condition')){
                            
                            // Use primary condition as the default
                            $args = array(
                                "post_type"         => 'page',
                                "order"	            => 'DESC',
                                "post_status"       => 'publish',
                                "posts_per_page"    => 1,
                                'meta_query'        => array(
                                    array(
                                        'key'       => 'condition',
                                        'value'     => get_field('primary_condition'),
                                    )
                                )
                            );
                            $loop = new WP_Query($args);
                            if($loop->have_posts()):
                            while($loop->have_posts()) : $loop->the_post();  
                                echo '<a class="crumb crumb-primary" href="'.get_the_permalink().'">'.get_the_title().'</a>';
                            endwhile;
                            endif;
                            wp_reset_query();

                        } else {

                            // Use the referral page otherwise
                            $ref_id = url_to_postid(wp_get_referer());
                            $ref_template = get_page_template_slug( $ref_id );
                            if( $ref_template == 'templates/page-path-collection.php' ){
                                echo '<a class="crumb crumb-referrer" href="'.get_the_permalink($ref_id).'">'.get_the_title($ref_id).'</a>';
                            } else {
                                if(get_query_var('ref')){
                                    if(get_term( get_query_var('ref') )){
                                        $term = get_term( get_query_var('ref') );
                                        if(get_field('custom_category_name', $term->taxonomy.'_'.$term->term_id)){
                                            $term_name = get_field('custom_category_name', $term->taxonomy.'_'.$term->term_id);
                                        } else {
                                            $term_name = $term->name;
                                        }
                                        echo '<a class="crumb crumb-referrer" href="'.get_term_link($term).'">'.$term_name.'</a>';
                                    } else {
                                        echo '<a class="crumb crumb-referrer" href="'.get_the_permalink( get_query_var('ref') ).'">'.get_the_title( get_query_var('ref') ).'</a>';
                                    }
                                }
                            }

                        }

                    }
                }
            }

            wp_reset_query();
            
        endif;

    ?>
</p>