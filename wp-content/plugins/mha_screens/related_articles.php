<?php

    function mha_results_related_articles( $args ){                        

        // Args
        $defaults = array(
            'demo_steps'         => array(),
            'next_step_manual'   => array(),
            'user_screen_result' => array(),
            'exclude_ids'        => array(),
            'next_step_terms'    => array(),
            'espanol'            => '',
            'iframe_var'         => '',
            'partner_var'        => '',
        );            
        $exclude_id = [];
        $args = wp_parse_args( $args, $defaults );
        $user_screen_result = $args['user_screen_result'];

        echo '<ol class="next-steps">';
            
            /**
             * Demo Based Steps
             */
            foreach($args['demo_steps'] as $link){
                echo '<li><a class="dark-gray plain rec-screen-demobased" href="'.get_the_permalink($link->ID).'">'.$link->post_title.'</a>';
            }

            /**
             * Result Based Manual Steps
             */
            foreach($args['next_step_manual'] as $step){
                $step_link = get_the_permalink($step);
                $step_link_target = '';
                if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
                    //$step_link = add_query_arg( 'partner', $args['partner_var'], $step_link );
                }
                if($args['iframe_var']){                                         
                    //$step_link = add_query_arg( 'iframe','true', $step_link );
                    $step_link_target = ' target="_blank"';
                }
                if(!in_array($step, $args['exclude_ids'])){
                    echo '<li><a class="dark-gray plain rec-result-manual"'.$step_link_target.' href="'.$step_link.'">'.get_the_title($step).'</a></li>';
                    $exclude_id[] = $step;
                }
            }

            /**
             * Manual Steps
             */
            if( have_rows('featured_next_steps', $user_screen_result['screen_id']) ):
            while( have_rows('featured_next_steps', $user_screen_result['screen_id']) ) : the_row();
                $step = get_sub_field('link');
                if($step && !in_array($step->id, $args['exclude_ids'])){
                    $manual_step_link = get_the_permalink($step->ID);
                    $manual_step_target = '';
                    if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
                        //$manual_step_link = add_query_arg( 'partner', $args['partner_var'], $manual_step_link );
                    }
                    if($args['iframe_var']){                                         
                        //$manual_step_link = add_query_arg( 'iframe','true', $manual_step_link );
                        $manual_step_target = ' target="_blank"';
                    }
                    echo '<li><a class="dark-gray plain rec-screen-manual"'.$manual_step_target.' href="'.$manual_step_link.'">'.$step->post_title.'</a></li>';
                    $exclude_id[] = $step->ID;
                }
            endwhile;        
            endif;

            /**
             * Automatic Query Args
             */
            if(!empty($exclude_id)){
                $total_exclude = count($exclude_id);
            } else {
                $total_exclude = 0;
            }
            $total_recs = 20 - $total_exclude;
            $loop_args = array(
                "post_type" => 'article',
                "order"	=> 'ASC',
                "orderby" => 'date',
                "post_status" => 'publish',
                "posts_per_page" => $total_recs,
                "meta_query" => array(
                    array(
                        "key"       => 'type',
                        "value"     => 'condition',
                        'compare'   => 'LIKE'
                    )
                )
            );

            if($espanol){
                $loop_args['meta_query'] = array(
                    array(
                        "key" => 'espanol',
                        "value" => 1
                    )
                );
                //$loop_args['meta_query']['relationship'] = 'AND';
            }


            /**
             * Result Based Next Steps
             */
            if(isset($next_step_terms)){
                $next_step_terms = array_unique($next_step_terms);
                $taxonomy_query = [];
                foreach($next_step_terms as $step){
                    $step = get_term($next);
                    if($step->taxonomy == 'condition' || $step->taxonomy == 'age_group' || $step->taxonomy == 'post_tag'){
                        $taxonomy_query[$step->taxonomy][] = $step->term_id;
                    }
                }
            }

            // Demographic based steps
            if(!empty($user_screen_result['result_terms'])){
                foreach($user_screen_result['result_terms'] as $step){ 
                    if($step['taxonomy'] == 'condition' || $step['taxonomy'] == 'age_group' || $step['taxonomy'] == 'post_tag'){
                        $taxonomy_query[$step['taxonomy']][] = $step['id'];    
                    }   
                }
            }

            // Overall screen based steps
            $tags = get_field('related_tags', $user_screen_result['screen_id']);
            if($tags){
                foreach($tags as $step){
                    if($step->taxonomy == 'condition' || $step->taxonomy == 'age_group' || $step->taxonomy == 'post_tag'){
                        $taxonomy_query[$step->taxonomy][] = $step->term_id;
                    }
                }
            }

            // Excluded previous manual 
            if(!empty($exclude_id)){
                $loop_args['post__not_in'] = $exclude_id; 
            }

            // Set up taxonomy query filters
            foreach($taxonomy_query as $k => $v){
                $loop_args['tax_query'][] = array(
                    'taxonomy' => $k,
                    'field'    => 'term_id',
                    'terms'    => $v
                );
            }

            // Make all tags required for multiple taxonomies (e.g. avoid eating disorder articles on depression results 
            // if someone answered 18-25 demographic questions)
            if(count($taxonomy_query) > 1){
                $loop_args['tax_query']['relation'] = 'AND';
            }

            // Automatic Related Article Query
            $loop = new WP_Query($loop_args);
            while($loop->have_posts()) : $loop->the_post();
                $related_link = get_the_permalink();
                $related_link_target = '';
                if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
                    //$related_link = add_query_arg( 'partner', $args['partner_var'], $related_link );
                }
                if($args['iframe_var']){                                         
                    //$related_link = add_query_arg( 'iframe','true', $related_link );
                    $related_link_target = ' target="_blank"';
                }
                if(!in_array(get_the_ID(), $args['exclude_ids'])){
                    echo '<li><a class="dark-gray plain rec-auto"'.$related_link_target.' href="'.$related_link.'">'.get_the_title().'</a></li>';
                }
            endwhile;

            // See All Link
            if(get_field('see_all_link', $user_screen_result['screen_id'])){
                $see_all_text = 'See All';
                $see_all_link = get_field('see_all_link', $user_screen_result['screen_id']);
                $see_all_target = '';
                if(get_field('see_all_link_text', $user_screen_result['screen_id'])){
                    $see_all_text = get_field('see_all_link_text', $user_screen_result['screen_id']);
                }
                if($args['partner_var'] && in_array($args['partner_var'], mha_approved_partners() )){                                    
                    //$see_all_link = add_query_arg( 'partner', $args['partner_var'], $see_all_link );
                }
                if($args['iframe_var']){                                         
                    //$see_all_link = add_query_arg( 'iframe','true', $see_all_link );
                    $see_all_target = ' target="_blank"';
                }
                echo '<li><a class="caps cerulean plain"'.$see_all_target.' href="'.$see_all_link.'">'.$see_all_text.'</a></li>';
            }
        
            echo '</ol>';

        return;

    }