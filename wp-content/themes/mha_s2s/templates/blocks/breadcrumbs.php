<p id="breadcrumbs">

    <a class="crumb crumb-home" href="<?php echo site_url(); ?>"><span class="text">Home</span></a>

    <?php
        $post_id = get_the_ID();
        $type = get_field('type');

        if(get_field('espanol')):

            // Spanish Breadcrumbs
            echo '<span class="crumb">La salud mental—información y recursos</span>';

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
                        echo '<a class="crumb crumb-primary" href="'.get_the_permalink().'"><span class="text">'.get_the_title().'</span></a>';
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
                    if($t == 'provider'){
                        echo '<a class="crumb crumb-type" href="/get-help"><span class="text">Treatment Resources</span></a>';
                        break;
                    }
                    else if($t == 'diy'){
                        echo '<a class="crumb crumb-type" href="/diy-tools"><span class="text">DIY Tools</span></a>';
                        break;
                    }
                    else if($t == 'connect'){
                        echo '<a class="crumb crumb-type" href="/connect"><span class="text">Connect Tools</span></a>';
                        break;
                    }
                    else if($t == 'treatment'){
                        echo '<a class="crumb crumb-type" href="/treatment"><span class="text">Treatment Info</span></a>';
                        break;
                    }
                    else if($t == 'condition'){

                        echo '<a class="crumb crumb-custom" href="/learn"><span class="text">';
                        echo _e('Mental Health Information', 'mhas2s');
                        echo '</span></a>';
                        
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
                                echo '<a class="crumb crumb-primary" href="'.get_the_permalink().'"><span class="text">'.get_the_title().'</span></a>';
                            endwhile;
                            endif;
                            wp_reset_query();
                            break;

                        } else {

                            // Use the referral page otherwise
                            $ref_id = url_to_postid(wp_get_referer());
                            $ref_template = get_page_template_slug( $ref_id );
                            if( $ref_template == 'templates/page-path-collection.php' ){
                                echo '<a class="crumb crumb-referrer" href="'.get_the_permalink($ref_id).'"><span class="text">'.get_the_title($ref_id).'</span></a>';
                            } else {
                                if(get_query_var('ref')){
                                    if(get_term( get_query_var('ref') )){
                                        $term = get_term( get_query_var('ref') );
                                        if(get_field('custom_category_name', $term->taxonomy.'_'.$term->term_id)){
                                            $term_name = get_field('custom_category_name', $term->taxonomy.'_'.$term->term_id);
                                        } else {
                                            $term_name = $term->name;
                                        }
                                        echo '<a class="crumb crumb-referrer" href="'.get_term_link($term).'"><span class="text">'.$term_name.'</span></a>';
                                    } else {
                                        echo '<a class="crumb crumb-referrer" href="'.get_the_permalink( get_query_var('ref') ).'"><span class="text">'.get_the_title( get_query_var('ref') ).'</span></a>';
                                    }
                                }
                            }

                        }

                    }
                }
            } else {
                
                // General                            
                echo '<a class="crumb crumb-custom" href="/learn"><span class="text">';
                echo _e('Mental Health Resources', 'mhas2s');
                echo '</span></a>';
                
            }

            wp_reset_query();
            
        endif;

    ?>
</p>