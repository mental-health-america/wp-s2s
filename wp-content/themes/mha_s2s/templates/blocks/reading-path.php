<?php
    $path_id = isset($args['path_id']) ? $args['path_id'] : get_the_ID();
    $no_wrapper = isset($args['no_wrapper']) ? $args['no_wrapper'] : 0;
    $breakout = isset($args['breakout']) ? $args['breakout'] : false;

    $delay = 0;
    $button_color = 'cerulean';

    if(isset($args['zebra']) && $args['zebra'] == 'odd'){
        $path_color = 'cerulean bubble-border-blue';
        $args['zebra'] = 'even';
    } else {
        $path_color = 'pale-blue bubble-border-blue';
        $args['zebra'] = 'odd';
    }

    if(isset($args['article_type']) && isset($args['resources'])){
        if(count(array_intersect($args['article_type'], $args['resources'])) > 0){ 
            $path_color = 'red bubble-border';
            $button_color = 'red';
        }
    }

?>  


<?php if($breakout == 'screening_results'): ?>
</div>
</article>
</div>
<div class="wrap normal">
<?php endif; ?>

<?php if($no_wrapper): ?>
<div class="bubble round-tl mb-5 <?php echo $path_color; ?> path-container wow fadeIn" role="complementary">
<div class="inner">
<?php endif; ?>

    <h3><?php echo get_the_title($path_id); ?></h3>  
    <ol class="path-list hidden-list" aria-hidden="true">

        <?php
            $counter = 0;
            $spacer_counter_wide = 0;
            $spacer_counter_narrow = 0;
            $path = get_field('path', $path_id);
            $max = $path ? count($path) : 0;
            if( have_rows('path', $path_id) ):
            while( have_rows('path', $path_id) ) : the_row();
                $article = get_sub_field('article');
                echo '<li class="path-item wow fadeIn" data-wow-delay="'.($delay).'s">';
                    echo '<a class="button round-tiny thin '.$button_color.' block" href="'.add_query_arg('pathway', $path_id, get_the_permalink($article)).'">';
                        echo '<span class="table">';
                        echo '<span class="cell">';
                            if(get_sub_field('custom_title')){
                                echo get_sub_field('custom_title');
                            } else {
                                echo get_the_title($article);
                            }
                        echo '</span>';
                        echo '</span>';
                    echo '</a>';
                echo '</li>';
                $counter++;

                // Spacers
                if($counter < $max){
                    echo '<li class="path-spacer path-spacer-mobile wow fadeIn" data-wow-delay="'.($delay).'s">';
                    get_template_part( 'templates/blocks/block', 'path.svg' );
                    echo '</li>';

                    $spacer_counter_wide++;
                    $spacer_counter_narrow++;
                    if($spacer_counter_wide == 4){
                        echo '<li class="path-spacer path-spacer-wide wow fadeIn" data-wow-delay="'.($delay).'s">';
                        get_template_part( 'templates/blocks/block', 'path.svg' );
                        echo '</li>';
                        $spacer_counter_wide = 0;
                    }
                    if($spacer_counter_narrow == 3){
                        echo '<li class="path-spacer path-spacer-narrow wow fadeIn" data-wow-delay="'.($delay).'s">';
                        get_template_part( 'templates/blocks/block', 'path.svg' );
                        echo '</li>';
                        $spacer_counter_narrow = 0;
                    }
                }
                $delay = $delay + .1;
            endwhile;
            endif;
        ?>

    </ol>
    
<?php if($no_wrapper): ?>
</div>
</div>
<?php endif; ?>


<?php if($breakout == 'screening_results'): ?>
</div>
<div class="wrap narrow">
<article class="screen screen-result">
<div class="pt-0">
<?php endif; ?>