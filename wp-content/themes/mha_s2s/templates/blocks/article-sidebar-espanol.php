<?php
    // Placement addendum for desktop/mobile
    $placement = $args['placement'] ? '_'.$args['placement'] : '';
?>

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
                    "posts_per_page" => 50,
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
    <?php endif; ?>

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