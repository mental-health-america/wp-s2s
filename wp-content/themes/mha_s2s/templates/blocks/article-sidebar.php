<div class="sticky">
<div class="inner-sidebar pb-4">

<?php   
    // Future vars
    $article_id = get_the_ID();
    $resources = array('diy','connect','treatment','provider');
    $article_type = get_field('type');
    
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

<?php /*if(has_post_thumbnail()): ?>
    <div class="bubble white thin round-big-tl mb-4 hide-mobile sidebar-featured-image">
    <div class="inner">
        <?php the_post_thumbnail(); ?>
    </div>
    </div>
<?php endif; */?>

<?php

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

    } else {
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
        

    //}
    

    if($terms_all):
    ?>
        <div class="bubble <?php echo $categoryColor; ?> thin round-big-tl mb-4">
        <div class="inner">
            <h4>Categories</h4>
            <p class="mb-4">Tags associated with this article:</p>
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
                                echo '<li><a class="plain bold caps montserrat bold" href="'.get_the_permalink().'">'.$term_name.'</a></li>';
                            endwhile;
                        else:   
                            // Otherwise just go to the archive   
                            echo '<li><a class="plain bold caps montserrat bold" href="'.get_term_link($c->term_id).'">'.$term_name.'</a></li>';
                        endif;
                        wp_reset_query();

                        $article_conditions[] = $c->term_id; // Used later for related content 
                    }
                }
                echo '</ol>';
            ?>
        </div>
        </div>
    <?php endif; ?>

    <?php
        /**
         * Test CTA
         */

        $primary_condition = get_field('primary_condition');

        // Pathway override
        if(get_query_var('pathway')){
            $path_terms = get_the_terms(get_query_var('pathway'), 'condition');
            if($path_terms){
                $primary_condition = $path_terms[0]->term_id;
            }
        }

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
                        <div class="excerpt"><?php the_excerpt(); ?></div>
                        <div class="text-center pb-3"><a href="<?php echo get_the_permalink(); ?>" class="button white round text-orange">Take a<?php echo $an_a; ?> <?php the_title(); ?></a></div>
                    <?php endwhile; ?>
                    </div>
                    </div>
                <?php
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
                endif;
                wp_reset_query();
            }

        }

    ?>

    <?php
        /**
         * Related Articles
         */
        if(count(array_intersect($article_type, $resources)) > 0){   
            
            $more_links = get_field('more_links');
            $args = array(
                "post_type"      => 'article',
                "orderby"        => 'rand',
                "post_status"    => 'publish',
                "posts_per_page" => 5,
                'tax_query'      => array(
                    array(
                        'taxonomy'          => 'condition',
                        'include_children'  => false,
                        'field'             => 'term_id',
                        'terms'             => $article_conditions
                    ),
                ),
                'meta_query'    => array(
                    array(
                        'key' => 'type',
                        'value' => array('condition', 'diy'),
                        'compare' => 'IN'
                    )
                )
            );
            $loop = new WP_Query($args);            

            if($loop->have_posts() || $more_links):                     
            ?>

                <div class="bubble coral thin round-big-tl mb-4">
                <div class="inner">
                    <h4>Related Articles</h4>
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
                                echo '<li><a class="plain white bold caps montserrat bold" href="'.get_the_permalink().'">'.get_the_title().'</a></li>';
                            endwhile;

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
        <?php get_template_part( 'templates/blocks/article', 'actions' ); ?>
    </div>
    
</div>
</div>