<?php
    // Content Block - Two Column Text

    // Styles
    $style = get_sub_field('style');
    $color = get_sub_field('color');
    $rounded = get_sub_field('corner_style');
    $padding = get_sub_field('padding');
    $widths = 'cols-'.get_sub_field('column_widths');
    $custom = get_sub_field('custom_classes');
    $alignment = get_sub_field('alignment');
?>

<div class="content-block block-two-column-text">
<div class="wrap normal">

<div class="two-cols <?php echo $alignment; ?> <?php echo $widths; ?>">
    <div class="left-col <?php echo $custom; ?>">
        <?php
            /**
             * Left Column
             */
            // Open Wrappers
            switch($style){
                case 'bubble':
                    echo '<div class="bubble '.$rounded.' '.$color.' '.$padding.'">';
                    echo '<div class="inner">';
                    break;
                default:
                    echo '<div class="bubble plain '.$color.' '.$padding.'">';
                    echo '<div class="inner">';
                    break;
            }
        ?>

        <?php if(get_sub_field('left_title')): ?>
            <h3 class="block-title"><?php the_sub_field('left_title'); ?></h3>
        <?php endif; ?>

        <?php the_sub_field('left_column'); ?>

        <?php
            // Close Wrappers
            switch($style){
                case 'bubble':
                    echo '</div>';
                    echo '</div>';
                    break;
                case 'wide':
                    echo '</div>';
                    echo '</div>';
                    break;
            }
        ?>
    </div>

    <div class="right-col<?php if(get_sub_field('custom_classes_2')){ echo ' '.get_sub_field('custom_classes_2'); } ?>">
        <?php
            /**
             * Right Column
             */
            $style_2 = get_sub_field('style_2');
            $color_2 = get_sub_field('color_2');
            $rounded_2 = get_sub_field('corner_2');
            $padding_2 = get_sub_field('padding_2');
            // Open Wrappers
            switch($style_2){
                case 'bubble':
                    echo '<div class="bubble '.$rounded_2.' '.$color_2.' '.$padding_2.'">';
                    echo '<div class="inner">';
                    break;
                default:
                    echo '<div class="bubble plain '.$color_2.' '.$padding_2.'">';
                    echo '<div class="inner">';
                    break;
            }
        ?>

        <?php if(get_sub_field('right_title')): ?>
            <h3 class="block-title"><?php the_sub_field('right_title'); ?></h3>
        <?php endif; ?>

        <?php echo get_sub_field('right_column'); ?>

        <?php
            // Close Wrappers
            switch($style){
                case 'bubble':
                    echo '</div>';
                    echo '</div>';
                    break;
                case 'wide':
                    echo '</div>';
                    echo '</div>';
                    break;
            }
        ?>
    </div>
</div>

</div>
</div>