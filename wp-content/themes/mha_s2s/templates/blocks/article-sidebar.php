<div class="sticky">
<div class="inner-sidebar pb-4">

<?php   
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
    $article_tags = [];
    if(count(array_intersect($article_type, $resources)) > 0){
        $categoryColor = 'raspberry';
    } else {
        $categoryColor = 'dark-blue';
    }
    
    // Conditions and Tags
    $terms_conditions = get_the_terms( $article_id, 'condition' );
    $terms_tags = get_the_terms( $article_id, 'post_tag' );
    $terms_all = '';

    //if(has_term('All Conditions', 'condition')){
    /*
    if(get_field('all_conditions')){

        // Display all tags
        $terms_all = get_terms( array(
            'taxonomy' => 'condition',
            'hide_empty' => true
        ));

    } 
    */

    // Mental Health 101 tag override when no conditions present

    // Use assigned tags 
    if($terms_conditions && $terms_tags){
        $terms_all = array_merge($terms_conditions, $terms_tags);
        usort($terms_all, "term_sort_name");
    } else if($terms_conditions && !$terms_tags){
        $terms_all = $terms_conditions;
    } else if(!$terms_conditions && $terms_tags){
        $terms_all = $terms_tags;
    }

    if(empty($terms_conditions)){
        $m101 = 0;
        foreach($terms_tags as $tags){
            if($tags->term_id == 116){
                $m101++;
            }
        }
        if($m101 == 0){
            $terms_tags[] = get_term(116, 'post_tag');
        }
    }    

    if($terms_all):
    ?>
        <div class="bubble <?php echo $categoryColor; ?> thin round-big-tl mb-4">
        <div class="inner">
                        
            <h4>Related Topics</h4>
            <p class="mb-4">​Click on each topic to see more articles:</p>
            <?php 
                echo '<ol class="plain ml-2 ml-lg-5 mb-0">'; 
                foreach($terms_all as $c){
                    if ($c->parent == 0 && !get_field('hide_on_front_end', $c->taxonomy.'_'.$c->term_id)){

                        if(get_field('custom_category_name', $c->taxonomy.'_'.$c->term_id)){
                            $term_name = get_field('custom_category_name', $c->taxonomy.'_'.$c->term_id);
                        } else {
                            $term_name = $c->name;
                        }
                        
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
                                    'value' => $c->term_id
                                )
                            )
                        );
                        $loop = new WP_Query($args);
                        if($loop->have_posts()):
                            while($loop->have_posts()) : $loop->the_post();  
                                // Link to path collection page if applicable   
                                echo '<li><a class="plain bold montserrat bold" href="'.get_the_permalink().'">'.$term_name.'</a></li>';
                            endwhile;
                        else:   
                            // Otherwise just go to the archive   
                            echo '<li><a class="plain bold montserrat bold" href="'.get_term_link($c->term_id).'">'.$term_name.'</a></li>';
                        endif;
                        wp_reset_query();

                         // Used later for related content 
                        if($c->taxonomy == 'condition'){
                            $article_conditions[] = $c->term_id;
                        }
                        if($c->taxonomy == 'post_tag'){
                            $article_tags[] = $c->term_id; 
                        }
                    }
                }
                echo '</ol>';
            ?>

        </div>
        </div>
    <?php endif; ?>

    <?php
        /**
         * Screening CTAs
         */

        $primary_condition = get_field('primary_condition');
        $has_screen_cta = 0;

        // Pathway override
        if(get_query_var('pathway')){
            $path_terms = get_the_terms(get_query_var('pathway'), 'condition');
            if($path_terms){
                $primary_condition = $path_terms[0]->term_id;
            }
        } else {
            if($primary_condition){
                $primary_condition = $primary_condition->term_id;
            }
        }

        // Screens
        if($primary_condition){

            // Show Specific Related Test
            if(count(array_intersect($article_type, $resources)) == 0){
                $args = array(
                    "post_type"         => 'screen',
                    "order"	            => 'DESC',
                    "post_status"       => 'publish',
                    "posts_per_page"    => 1,
                    'tax_query'      => array(
                        array(
                            'taxonomy'          => 'condition',
                            'include_children'  => false,
                            'field'             => 'term_id',
                            'terms'             => $primary_condition
                        ),
                    ),
                    'meta_query' => array( 
                        'relation' => 'OR',
                        array(
                            'key' => 'espanol',
                            'value' => '1',
                            'compare' => '!='
                        ),
                        array(
                            'key' => 'espanol',
                            'value' => '1',
                            'compare' => 'NOT EXISTS'
                        )
                    )
                );
                $loop = new WP_Query($args);
                if($loop->have_posts()):
                ?>
                    <div class="bubble orange thin round-big-tl mb-4">
                    <div class="inner">
                    <?php while($loop->have_posts()) : $loop->the_post(); ?> 
                        <?php
                            $an_a = ' '; 
                            $title = get_the_title();
                            if($title[0] == 'A'){
                                $an_a = 'n ';
                            }
                        ?>                         
                        <?php the_title('<h4>Take a'.$an_a,'</h4>'); ?>   
                        <div class="excerpt thin"><?php the_excerpt(); ?></div>
                        <div class="text-center pb-3"><a href="<?php echo get_the_permalink(); ?>" class="button white round text-orange">Take a<?php echo $an_a; ?> <?php the_title(); ?></a></div>
                    <?php endwhile; ?>
                    </div>
                    </div>
                <?php
                $has_screen_cta++;
                endif;
                wp_reset_query();
            }

        } else {

            // Show Random Related Test
            if(count(array_intersect($article_type, $resources)) == 0){
                $args = array(
                    "post_type"      => 'screen',
                    "orderby"        => 'rand',
                    "order"	         => 'DESC',
                    "post_status"    => 'publish',
                    "posts_per_page" => 1,
                    'tax_query'      => array(
                        array(
                            'taxonomy'          => 'condition',
                            'include_children'  => false,
                            'field'             => 'term_id',
                            'terms'             => $article_conditions
                        ),
                    ),
                    'meta_query' => array( 
                        'relation' => 'OR',
                        array(
                            'key' => 'espanol',
                            'value' => '1',
                            'compare' => '!='
                        ),
                        array(
                            'key' => 'espanol',
                            'value' => '1',
                            'compare' => 'NOT EXISTS'
                        )
                    )
                );
                $loop = new WP_Query($args);
                if($loop->have_posts()):
                ?>
                    <div class="bubble orange thin round-big-tl mb-4 hide-mobile">
                    <div class="inner">
                    <?php while($loop->have_posts()) : $loop->the_post(); ?>    
                        <?php
                            $an_a = ' '; 
                            $title = get_the_title();
                            if($title[0] == 'A'){
                                $an_a = 'n ';
                            }
                        ?>                            
                        <?php the_title('<h4>Take a'.$an_a,'</h4>'); ?>   
                        <div class="excerpt"><?php the_excerpt(); ?></div>
                        <div class="text-center pb-3"><a href="<?php echo get_the_permalink(); ?>" class="button white round text-orange">Take a<?php echo $an_a; ?> <?php the_title(); ?></a></div>
                    <?php endwhile; ?>
                    </div>
                    </div>
                <?php
                $has_screen_cta++;
                endif;
                wp_reset_query();
            }

        }

    ?>

    <?php
        /**
         * Related Articles
         */

        //$resources[] = 'condition';
        if(count(array_intersect($article_type, $resources)) > 0 || count( array_intersect($article_type, array(null,'condition')) ) > 0 && $has_screen_cta == 0 ){               

            $related_articles = [];
            $args = array(
                "post_type"      => 'article',
                "orderby"        => 'title',
                "post_status"    => 'publish',
                "posts_per_page" => 500,
                'meta_query' => array(
                    array(
                        'key' => 'type',
                        'value' => array('condition', 'diy','connect','treatment','provider'),
                        'compare' => 'LIKE'
                    )
                )
            );

                
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
            
            $loop = new WP_Query($args);     

            $counter = 0;
            $terms_match = [];
            foreach($terms_all as $ta){
                $terms_match[] = $ta->term_id;
            }

            // Get popular articles for later
            $pop_array = mha_monthly_pop_articles( 'read' );

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
                if(get_field('espanol')){
                    continue;
                }

                // Matching primary condition
                if($new_primary && $primary_condition && $new_primary->term_id == $primary_condition){
                    $rel_score = $rel_score + 3;
                }

                // Matching Popular article
                if(in_array($new_id, $pop_array)){
                    $rel_score = $rel_score + 1;
                }

                // Matching Types
                $rel_score = $rel_score + count(array_intersect($article_type, get_field('type', $new_id)));

                // Matching conditions
                foreach($new_cond as $nc){
                    if(in_array($nc->term_id, $terms_match)){
                        $rel_score = $rel_score + 1;
                    }
                    if($nc->term_id == $primary_condition){
                        $rel_score = $rel_score + 2;
                    }
                }

                foreach($new_tags as $nt){
                    if(in_array($nt->term_id, $terms_match)){
                        $rel_score = $rel_score + 1;
                    }
                    if($nt->term_id == $primary_condition){
                        $rel_score = $rel_score + 2;
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
            $related_articles_display = array_slice($related_articles, 0, 6);

            if(count($related_articles_display) > 0 || $more_links):     
                $related_color = 'coral';
                if(count(array_intersect($article_type, array('condition'))) > 0){
                    $related_color = 'teal';
                }
            ?>

                <div class="bubble <?php echo $related_color; ?> thin round-big-tl mb-4">
                <div class="inner">                        
                    <h4>Related Articles</h4>
                    <?php 
                        echo '<ol class="plain ml-2 ml-lg-5 mb-0">';                                             

                            // Manual Related Links
                            if( have_rows('more_links', $article_id) ):
                            while( have_rows('more_links', $article_id) ) : the_row();                                        
                                $page = get_sub_field('page');
                                if($page){
                                    echo '<li><a class="plain white bold montserrat bold" href="'.get_the_permalink($page).'">';
                                        if(get_sub_field('custom_title')){
                                            the_sub_field('custom_title');
                                        } else {
                                            echo get_the_title($page);
                                        }
                                    echo '</a></li>';
                                }
                            endwhile;
                            endif;

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
        }
        
    ?>

    <div class="hide-mobile">
        <?php get_template_part( 'templates/blocks/article', 'actions', array( 'placement' => 'desktop' ) ); ?>
    </div>
    
</div>
</div>


    

    