<?php
    // Placement addendum for desktop/mobile
    $placement = $args['placement'] ? '_'.$args['placement'] : '';

    // A/B Testing
    $layout = get_layout_array(get_query_var('layout')); // Used for A/B testing
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
    $article_tags = [];
    if(count(array_intersect($article_type, $resources)) > 0){
        $categoryColor = 'raspberry';
    } else {
        $categoryColor = 'dark-blue';
    }
    
    // Conditions and Tags
    $terms_conditions = get_the_terms( $article_id, 'condition' );
    $terms_tags = get_the_terms( $article_id, 'post_tag' );
    $terms_all = [];

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

    // Use assigned tags 
    if($terms_conditions && $terms_tags){
        $terms_all = array_merge($terms_conditions, $terms_tags);
        usort($terms_all, "term_sort_name");
    } else if($terms_conditions && !$terms_tags){
        $terms_all = $terms_conditions;
    } else if(!$terms_conditions && $terms_tags){
        $terms_all = $terms_tags;
    }

    // Mental Health 101 tag override when no conditions present
    /*
    if(empty($terms_conditions)){
        $m101 = 0;
        if(is_array($terms_tags)){
            foreach($terms_tags as $tags){
                if($tags->term_id == 116){
                    $m101++;
                }
            }
        }
        if($m101 == 0){
            $m101term = get_term(116, 'post_tag');
            $terms_tags[] = $m101term;
            $article_tags[] = 116;    
            $terms_all[] = $m101term;
        }
    }    
    */

    if($terms_all && !count(array_intersect( array('sidebar_only_test'), $layout)) || count(array_intersect( array('sidebar_show_related'), $layout)) ):
    ?>
        <div id="article--related-topics<?php echo $placement; ?>" class="bubble <?php echo $categoryColor; ?> thin round-big-tl mb-4">
        <div class="inner">
                        
            <h4>Related Topics</h4>
            <p class="mb-4">Click on each topic to see more articles:</p>
            <?php 
                echo '<ol class="plain ml-2 ml-lg-5 mb-0">'; 
                
                // Related topics
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

                // General Mental Health inclusion
                if(get_field('all_conditions')){
                    echo '<li class="type-related"><a class="plain bold montserrat bold" href="/general-mental-health/">General Mental Health</a></li>';                    
                }

                // Extra topics based on Type
                if( array_intersect($article_type, array('treatment') ) ){
                    echo '<li class="type-related"><a class="plain bold montserrat bold" href="/treatment/">Mental Health Treatment Info</a></li>';
                }

                if( array_intersect($article_type, array('diy') ) ){
                    echo '<li class="type-related"><a class="plain bold montserrat bold" href="/diy/">DIY / Self-Help Tools</a></li>';
                }

                if( array_intersect($article_type, array('connect') ) ){
                    echo '<li class="type-related"><a class="plain bold montserrat bold" href="/connect/">Connect with Peers</a></li>';
                }

                if( array_intersect($article_type, array('provider') ) ){
                    echo '<li class="type-related"><a class="plain bold montserrat bold" href="/get-help/">Find Help</a></li>';
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

        $primary_condition = get_field('primary_condition'); // term_id & taxonomy
        if(!$primary_condition){
            if($terms_conditions && is_array($terms_conditions) && count($terms_conditions) == 1){
                $primary_condition = $terms_conditions[0];
            }
        }

        $has_screen_cta = 0;

        // Pathway override
        if(get_query_var('pathway')){
            $path_terms = get_the_terms(get_query_var('pathway'), 'condition');
            if($path_terms){
                $primary_condition = $path_terms[0];
            }
        }

        // Screens
        if( count( array_intersect($article_type, array('condition')) ) > 0 && !count(array_intersect( array('sidebar_only_related'), $layout)) ):

            // Show Specific Related Test
            $args = array(
                "post_type"         => 'screen',
                "order"	            => 'DESC',
                "post_status"       => 'publish',
                "posts_per_page"    => 1
            );
            $has_query = false;

            if( $primary_condition && count(array_intersect($article_type, $resources)) == 0){
                // Conditions
                $args['tax_query'] = array(
                    array(
                        'taxonomy'          => $primary_condition->taxonomy,
                        'include_children'  => false,
                        'field'             => 'term_id',
                        'terms'             => $primary_condition->term_id
                    ),
                );
                $has_query = true;
            }
            
            else if( count($article_tags) == 1 ){
                // Tags
                $args['tax_query'] = array(
                    array(
                        'taxonomy'          => 'post_tag',
                        'include_children'  => false,
                        'field'             => 'term_id',
                        'terms'             => $article_tags,
                        'operator'          => 'IN'
                    ),
                );
                $has_query = true;
            }
                
            $args['meta_query'] = array( 
                'relation' => 'AND',
                array(
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
                ),                
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'invisible',
                        'value' => '1',
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'invisible',
                        'value' => '1',
                        'compare' => 'NOT EXISTS'
                    )
                ),                
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'survey',
                        'value' => '1',
                        'compare' => '!='
                    ),
                    array(
                        'key' => 'survey',
                        'value' => '1',
                        'compare' => 'NOT EXISTS'
                    )
                )
            );
            if($primary_condition){
                $args['meta_query'][] = array(
                    array(
                        'key' => '_yoast_wpseo_primary_'.$primary_condition->taxonomy,
                        'value' => $primary_condition->term_id
                    )
                );
            }

            if($has_query && $primary_condition):
                $loop = new WP_Query($args);
                if($loop->have_posts()):
                ?>
                    <div id="article--test<?php echo $placement; ?>" class="bubble orange thin round-big-tl mb-4">
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
                        <div class="text-center pb-0"><a href="<?php echo get_the_permalink(); ?>" class="button white round text-orange">Take a<?php echo $an_a; ?> <?php the_title(); ?></a></div>
                    <?php endwhile; ?>
                    </div>
                    </div>
                <?php
                $has_screen_cta++;
                endif;
            endif;
            wp_reset_query();

        endif;
            
        // Generic take a test message
        if( 
            !$has_screen_cta && count(array_intersect( array('sidebar_only_test'), $layout)) && !count(array_intersect( array('sidebar_only_related'), $layout)) ||
            !$has_screen_cta && count(array_intersect( array('sidebar_show_test'), $layout)) && !count(array_intersect( array('sidebar_only_related'), $layout)) 
        ):
            ?>
                <div id="article--test<?php echo $placement; ?>" class="bubble orange thin round-big-tl mb-4 hide-mobile">
                <div class="inner">                
                    <h4><?php echo _e('Take a Mental Health Test', 'mhas2s'); ?></h4>
                    <div class="excerpt font-weight-normal">
                        <?php echo strip_tags(get_field('hero_introduction', 36), '<p>'); ?>
                    </div>
                    <div class="text-center pb-3"><a href="/screening-tools/" class="button white round text-orange"><?php echo _e('Take a Mental Health Test', 'mhas2s'); ?></a></div>
                </div>
                </div>
            <?php 
            //$has_screen_cta++;
        endif;

        // Show Random Related Test
        /*
        } else {
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
                    <div id="article--test<?php echo $placement; ?>" class="bubble orange thin round-big-tl mb-4 hide-mobile">
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
                        <div class="text-center pb-0"><a href="<?php echo get_the_permalink(); ?>" class="button white round text-orange">Take a<?php echo $an_a; ?> <?php the_title(); ?></a></div>
                    <?php endwhile; ?>
                    </div>
                    </div>
                <?php
                $has_screen_cta++;
                endif;
                wp_reset_query();

            } else {
            ?>

            <div id="article--test<?php echo $placement; ?>" class="bubble orange thin round-big-tl mb-4 hide-mobile">
            <div class="inner">                
                <h4><?php echo _e('Take a Mental Health Test', 'mhas2s'); ?></h4>
                <div class="excerpt font-weight-normal">
                    <?php echo strip_tags(get_field('hero_introduction', 36), '<p>'); ?>
                </div>
                <div class="text-center pb-3"><a href="/screening-tools/" class="button white round text-orange"><?php echo _e('Take a Mental Health Test', 'mhas2s'); ?></a></div>
            </div>
            </div>
            <?php
            $has_screen_cta++;
        }
        */
    ?>

    <?php
        /**
         * Related Articles
         */

        if( count(array_intersect( array('sidebar_only_test'), $layout)) ) {
            $has_screen_cta = 1;
        }

        if( $has_screen_cta == 0 || count(array_intersect( array('sidebar_show_related'), $layout)) ):    

            $related_articles = [];
            $exclude_ids = [];
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

            // URL Settings
            if(get_query_var('exclude_ids')){
                $url_hide_articles = explode(',',get_query_var('exclude_ids'));
                foreach($url_hide_articles as $uha){
                    $exclude_ids[] = $uha;
                }
            }

            // Exclude IDs from related articles
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
            
            $loop = new WP_Query($args);     

            $counter = 0;
            $terms_match = [];
            if(is_array($terms_all)){
                foreach($terms_all as $ta){
                    $terms_match[] = $ta->term_id;
                }
            }

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

            // Get popular articles for later
            $pop_array = mha_monthly_pop_articles( 'read' );

            while($loop->have_posts()) : $loop->the_post();
                $rel_score = 0;
                $new_id = get_the_ID();
                $new_cond = get_the_terms( $new_id, 'condition' );
                $new_tags = get_the_terms( $new_id, 'post_tag' );
                $new_primary = get_field('primary_condition', $new_id);

                // Skip already added articles
                if( isset($related_articles[$new_id]) ){
                    continue;
                }

                // Skip local providers
                if(get_field('area_served')){
                    continue;
                }

                // Skip Spanish Articles
                if(get_field('espanol')){
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
            $related_articles_display = array_slice($related_articles, 0, 6);

            if(count($related_articles_display) > 0 || $more_links):     
                $related_color = 'coral';
                if(count(array_intersect($article_type, array('condition'))) > 0){
                    $related_color = 'teal';
                }
            ?>

                <div id="article--related-articles<?php echo $placement; ?>" class="bubble <?php echo $related_color; ?> thin round-big-tl mb-4">
                <div class="inner">                        
                    <h4>Related Articles</h4>
                    <?php 
                        echo '<ol class="plain ml-2 ml-lg-5 mb-0">';                                             

                            // Related Articles
                            foreach($related_articles_display as $rad){
                                if($rad['id'] == $article_id){
                                    continue;// Skip if the same article
                                }
                                echo '<li><a class="plain white bold montserrat bold" href="'.get_the_permalink($rad['id']).'">';
                                if( isset($rad['custom_title']) && $rad['custom_title'] != ''){
                                    echo $rad['custom_title'];
                                } else {
                                    echo get_the_title($rad['id']);
                                }
                                echo '</a></li>';
                            }

                        echo '</ol>';
                    ?>
                </div>
                </div>

            <?php
            endif;
            wp_reset_query();
        //endif;
        endif;
        
    ?>

    <div class="hide-mobile">
        <?php get_template_part( 'templates/blocks/article', 'actions', array( 'placement' => $placement ) ); ?>
    </div>
    
</div>
</div>


    

    