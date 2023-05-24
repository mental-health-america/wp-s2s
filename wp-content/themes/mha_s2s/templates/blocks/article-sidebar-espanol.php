<?php
    // Placement addendum for desktop/mobile
    $placement = $args['placement'] ? '_'.$args['placement'] : '';
?>

<div class="sticky">
<div class="inner-sidebar pb-4">

<?php   
    // Image
    if(!count(array_intersect( array('featured_image_article'), $layout))){
        if(has_post_thumbnail()){
            echo '<div class="featured-image mb-5">';
                the_post_thumbnail();
            echo '</div>';
        } elseif( get_field('featured_image') ) {
            echo '<div class="featured-image mb-5">';                                
            echo wp_get_attachment_image( get_field('featured_image'), 'banner' );
            echo '</div>';
        }
    }

    // Future vars
    $article_id = get_the_ID();
    $resources = array('diy','connect','treatment','provider');
    $article_type = get_field('type');
    $more_links = get_field('more_links');
    
    // Related content triggers
    $article_diy_issue = get_field('diy_type');
    $article_treatment_type = get_field('treatment_type');
    $article_service_type = get_field('service_type');
    $article_conditions = [];
    if(count(array_intersect($article_type, $resources)) > 0){
        $categoryColor = 'raspberry';
    } else {
        $categoryColor = 'dark-blue';
    }
?>

<?php

    $terms_conditions = get_the_terms( $article_id, 'condition' );
    $terms_tags = get_the_terms( $article_id, 'post_tag' );
    $terms_all = '';

    // Use assigned tags 
    if($terms_conditions && $terms_tags){
        $terms_all = array_merge($terms_conditions, $terms_tags);
        usort($terms_all, "term_sort_name");
    } else if($terms_conditions && !$terms_tags){
        $terms_all = $terms_conditions;
    } else if(!$terms_conditions && $terms_tags){
        $terms_all = $terms_tags;
    }
    

    if($terms_all):
    /*
    ?>
        <div id="article--related-articles<?php echo $placement; ?>" class="bubble <?php echo $categoryColor; ?> thin round-big-tl mb-4">
        <div class="inner">
                    
            <?php 
                // Spanish Articles            
                $args = array(
                    "post_type"      => 'article',
                    "orderby"        => 'title',
                    "order"          => 'ASC',
                    "post_status"    => 'publish',
                    "posts_per_page" => 6,
                    'meta_query'    => array(
                        array(
                            'key' => 'espanol',
                            'value' => 1
                        )
                    )
                );
                $loop = new WP_Query($args);     
                if($loop->have_posts() || $more_links):                     
                ?>
                    <h4>Más recursos</h4>
                    <?php 
                        echo '<ol class="plain ml-2 ml-lg-5 mb-0">';                                            

                            // Manual Related Links
                            if( have_rows('more_links') ):
                            while( have_rows('more_links') ) : the_row();                                        
                                $page = get_sub_field('page');
                                if($page){
                                    echo '<li><a class="plain white bold caps montserrat bold" href="'.get_the_permalink($page).'">';
                                        if(get_sub_field('custom_title')){
                                            the_sub_field('custom_title');
                                        } else {
                                            echo get_the_title($page);
                                        }
                                    echo '</a></li>';
                                }
                            endwhile;
                            endif;

                            // Automatic Related
                            while($loop->have_posts()) : $loop->the_post();
                                if(get_the_ID() == $article_id){
                                    continue;// Skip if the same article
                                }
                                echo '<li><a class="plain white bold caps montserrat bold" href="'.get_the_permalink().'">'.get_the_title().'</a></li>';
                            endwhile;

                        echo '</ol>';
                    ?>
                <?php
                endif;
                wp_reset_query();
            ?>

        </div>
        </div>
    */
    ?>


    <?php
        /**
         * Related Articles
         */

        //if(count(array_intersect($article_type, $resources)) > 0 || count( array_intersect($article_type, array('condition')) ) > 0 && $has_screen_cta == 0 ):       

            $related_articles = [];
            $exclude_ids = [];
            $primary_condition = get_field('primary_condition');
            $args = array(
                "post_type"      => 'article',
                "orderby"        => 'title',
                "post_status"    => 'publish',
                "posts_per_page" => 500
            );

            // Global Options
            $global_hide_articles = get_field('global_hide_articles', 'options');
            if($global_hide_articles){
                foreach($global_hide_articles as $gha){
                    $exclude_ids[] = $gha;
                }
            }
            $article_sidebar_hide_articles = get_field('article_sidebar_hide_articles', 'options');
            if($article_sidebar_hide_articles){
                foreach($article_sidebar_hide_articles as $asha){
                    $exclude_ids[] = $asha;
                }
            }
            if(count($exclude_ids) > 0){
                $args['post__not_in'] = $exclude_ids;
            }
                
            if(!empty($article_conditions) && !empty($article_tags)){
                $args['tax_query']['relation'] = 'OR';
            }
            if(!empty($article_conditions)){
                $args['tax_query'][] = array(
                    'taxonomy'          => 'condition',
                    'include_children'  => false,
                    'field'             => 'term_id',
                    'terms'             => $article_conditions
                );
            }
            if(!empty($article_tags)){
                $args['tax_query'][] = array(
                    'taxonomy'          => 'post_tag',
                    'include_children'  => false,
                    'field'             => 'term_id',
                    'terms'             => $article_tags
                );
            }
            
            //pre($args);
            $loop = new WP_Query($args);     

            $counter = 0;
            $terms_match = [];
            if(is_array($terms_all)){
                foreach($terms_all as $ta){
                    $terms_match[] = $ta->term_id;
                }
            }

            // Get popular articles for later
            $pop_array = mha_monthly_pop_articles( 'read' );

            // Manual Related Links
            if( have_rows('more_links', $article_id) ):
            while( have_rows('more_links', $article_id) ) : the_row();                                        
                $page = get_sub_field('page');
                if($page){          
                    $related_articles[$page->ID]['id'] = $page->ID;
                    $related_articles[$page->ID]['custom_title'] = get_sub_field('custom_title') ? get_sub_field('custom_title') : $page->post_title;
                    $related_articles[$page->ID]['score'] = 200 - get_row_index();
                }
            endwhile;
            endif;
            
            while($loop->have_posts()) : $loop->the_post();
                $rel_score = 0;
                $new_id = get_the_ID();
                $new_cond = get_the_terms( $new_id, 'condition' );
                $new_tags = get_the_terms( $new_id, 'post_tag' );
                $new_primary = get_field('primary_condition', $new_id);

                // Skip local providers
                if(get_field('area_served')){
                    continue;
                }

                // Skip Spanish Articles
                if(!get_field('espanol')){
                    continue;
                }

                // Matching primary condition
                if($new_primary && $primary_condition && $new_primary->term_id == $primary_condition->term_id){
                    $rel_score = $rel_score + 3;
                }

                // Matching Popular article
                if(in_array($new_id, $pop_array)){
                    $rel_score = $rel_score + 1;
                }

                // Matching Types
                $rel_score = $rel_score + count(array_intersect($article_type, get_field('type', $new_id)));

                // Matching conditions
                if($new_cond){
                    foreach($new_cond as $nc){
                        if(in_array($nc->term_id, $terms_match)){
                            $rel_score = $rel_score + 1;
                        }
                        if($primary_condition && $nc->term_id == $primary_condition->term_id){
                            $rel_score = $rel_score + 2;
                        }
                    }
                }

                if($new_tags){
                    foreach($new_tags as $nt){
                        if(in_array($nt->term_id, $terms_match)){
                            $rel_score = $rel_score + 1;
                        }
                        if($primary_condition && $nt->term_id == $primary_condition->term_id){
                            $rel_score = $rel_score + 2;
                        }
                    }
                }

                $related_articles[$new_id]['id'] = get_the_ID();
                $related_articles[$new_id]['score'] = $rel_score;
                $counter++;
            endwhile;
            wp_reset_query(); // Reset the query here to be safe

            usort($related_articles, function ($item1, $item2) {
                return $item2['score'] <=> $item1['score'];
            });
            $related_articles_display = array_slice($related_articles, 0, 5);

            if(count($related_articles_display) > 0 || $more_links):     
                $related_color = 'coral';
                if(count(array_intersect($article_type, array('condition'))) > 0){
                    $related_color = 'teal';
                }
            ?>

                <div id="article--related-articles<?php echo $placement; ?>" class="bubble <?php echo $categoryColor; ?> thin round-big-tl mb-4">
                <div class="inner">            
                    <h4>Más recursos</h4>
                    <?php 
                        echo '<ol class="plain ml-2 ml-lg-5 mb-0">';                                             

                            // Related Articles
                            foreach($related_articles_display as $rad){
                                if($rad['id'] == $article_id){
                                    continue;// Skip if the same article
                                }
                                echo '<li><a class="plain white bold montserrat bold" href="'.get_the_permalink($rad['id']).'">'.get_the_title($rad['id']).'</a></li>';
                            }

                        echo '</ol>';
                    ?>
                </div>
                </div>

            <?php
            endif;
            wp_reset_query();
        endif;
        
    ?>





    <?php
        /**
         * Screening CTAs
         */

        $primary_condition = get_field('primary_condition');

        // Pathway override
        if(get_query_var('pathway')){
            $path_terms = get_the_terms(get_query_var('pathway'), 'condition');
            if($path_terms){
                $primary_condition = $path_terms[0]->term_id;
            }
        }

        // Screens
        $args = array(
            "post_type"         => 'screen',
            "order"	            => 'DESC',
            "post_status"       => 'publish',
            "posts_per_page"    => 1,
            'meta_query' => array( 
                array(
                    'key' => 'espanol',
                    'value' => '1',
                )
            ),
            'tax_query'      => array(
                array(
                    'taxonomy'          => 'condition',
                    'include_children'  => false,
                    'field'             => 'term_id',
                    'terms'             => $primary_condition
                ),
            ),
        );
        $loop = new WP_Query($args);
        $counter = 0;
        if($loop->have_posts()):
        ?>     
            <div id="article--test<?php echo $placement; ?>" class="bubble orange thin round-big-tl mb-4">
            <div class="inner">    
                <?php 
                    while($loop->have_posts()) : $loop->the_post();
                    if($counter == 0): ?>
                        <h4>Toma una prueba de salud mental</h4>
                    <?php endif; ?>
                    <div class="excerpt thin"><?php the_excerpt(); ?></div>
                    <div class="text-center pb-3 mb-3"><a href="<?php echo get_the_permalink(); ?>" class="button white round text-orange">Toma una <?php echo trim( preg_replace("/\([^)]+\)/","", get_the_title( get_the_ID() )) ); ?></a></div>
                <?php 
                    $counter++;
                    endwhile; 
                ?>
            </div>
            </div>
        <?php
        endif;
    ?>

    <div class="hide-mobile">
        <?php get_template_part( 'templates/blocks/article', 'actions', array( 'placement' => $placement ) ); ?>
    </div>
    
</div>
</div>