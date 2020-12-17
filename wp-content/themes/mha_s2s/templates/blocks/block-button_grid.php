<?php
    // Content Block - Button Grid
    
    // Styles
    $style = get_sub_field('style');
    $color = get_sub_field('color');
    $rounded = get_sub_field('corner_style');
    $padding = get_sub_field('padding');
    $custom = get_sub_field('custom_classes');
?>

<div class="content-block block-button-grid <?php echo $custom; ?>">
        
    <?php
        // Open Wrappers
        switch($style){
            case 'bubble':
                echo '<div class="wrap normal">';
                echo '<div class="bubble '.$rounded.' '.$color.' '.$padding.'">';
                echo '<div class="inner">';
                break;
            case 'wide':
                echo '<div class="wide-block">';
                echo '<div class="bubble '.$rounded.' '.$color.' '.$padding.'">';
                echo '<div class="inner">';
                break;
            default:
                echo '<div class="wrap narrow '.$color.'">';
                break;
        }
    ?>

    <?php if(get_sub_field('headline')): ?>
        <h2 class="block-title"><?php the_sub_field('headline'); ?></h2>
    <?php endif; ?>

    <?php the_sub_field('content'); ?>
    
    <?php
        // Featured buttons
        if( have_rows('featured_buttons') ):
        echo '<div class="featured-buttons">';
        while( have_rows('featured_buttons') ) : the_row();
        $link = get_sub_field('url');
        ?>
            <div class="grid-item">
                <a class="button block round teal large" href="<?php echo get_sub_field('link'); ?>">
                    <?php the_sub_field('text'); ?>
                </a>                
            </div>
        <?php
        endwhile;
        echo '</div>';
        endif;
    ?>

    <?php
        // Secondary buttons
        if( have_rows('secondary_buttons') ):
            $counter = 1;
            $total = 0;
            while( have_rows('secondary_buttons') ) : the_row();
                $total++;
            endwhile;

            echo '<div class="secondary-buttons">';
            while( have_rows('secondary_buttons') ) : the_row();

                $link = get_sub_field('url');
                $button_color = 'teal';
                if($counter == $total){
                    $button_color = 'gray';
                }
                if(strpos($custom, 'blue-buttons') !== false){
                    $button_color = 'blue';
                }
                ?>
                    <div class="grid-item">
                        <a class="text-button <?php echo $button_color; ?>" href="<?php echo get_sub_field('link'); ?>">
                            <?php the_sub_field('text'); ?>
                        </a>
                    </div>
                <?php
                $counter++;

            endwhile;
            echo '</div>';
        endif;
    ?>

    
    <?php
        // Close Wrappers
        switch($style){
            case 'bubble':
                echo '</div>';
                echo '</div>';
                echo '</div>';
                break;
            case 'wide':
                echo '</div>';
                echo '</div>';
                echo '</div>';
                break;
            default:
                echo '</div>';
                break;
        }
    ?>

</div>