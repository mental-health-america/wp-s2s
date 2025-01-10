<?php
    // Content Block - Two Column Text

    // Styles
    $style = get_sub_field('style');
    $color = get_sub_field('color');
    $rounded = get_sub_field('corner_style');
    $padding = get_sub_field('padding');
    $widths = 'cols-'.get_sub_field('column_widths');
    $custom_class_all = get_sub_field('custom_classes_container');
    $custom_class_left = get_sub_field('custom_classes');
    $custom_class_right = get_sub_field('custom_classes_2');
    $alignment = get_sub_field('alignment');
    $section_width = get_sub_field('section_width') ? get_sub_field('section_width') : 'normal';
?>

<div class="content-block block-two-column-text <?php echo $custom_class_all; ?>">
<div class="wrap <?php echo $section_width; ?>">

<?php if( get_sub_field('section_title') ): ?>
    <h2 class="section-title-cb large text-center"><?php the_sub_field('section_title'); ?></h2>
<?php endif; ?>

<div class="two-cols <?php echo $alignment; ?> <?php echo $widths; ?>">

    <!-- Begin left column -->
    <div class="left-col <?php echo $custom_class_left; ?>">
        <?php
            // Open Wrappers
            switch($style){
                case 'bubble':
                    echo '<div class="bubble '.$rounded.' '.$color.' '.$padding.'">';
                    break;
                default:
                    echo '<div class="left-container plain '.$color.' '.$padding.'">';
                    break;
            }
        ?>
        <div class="inner">

            <?php if(get_sub_field('left_title')): ?>
                <h3 class="block-title"><?php the_sub_field('left_title'); ?></h3>
            <?php endif; ?>

            <?php the_sub_field('left_column'); ?>

        </div>
        </div>

        <?php
            // Left button
            $button_left = get_sub_field('left_button');
            if( $button_left ): 
                $left_button_margin = get_sub_field('left_column') ? 'mt-4' : 'mt-0';
                $left_link_url = $button_left['url'];
                $left_link_title = $button_left['title'];
                $left_link_target = $button_left['target'] ? $button_left['target'] : '_self';
            ?>
                <div class="text-right section-button-container <?php echo $left_button_margin; ?>"><a class="button red round-tr thick" href="<?php echo esc_url( $left_link_url ); ?>" target="<?php echo esc_attr( $left_link_target ); ?>"><?php echo esc_html( $left_link_title ); ?></a></div>
            <?php endif; ?>
    </div>
    <!-- End left column -->

    <!-- Begin right column -->
    <div class="right-col <?php echo $custom_class_right; ?>">

        <?php
            $style_2 = get_sub_field('style_2');
            $color_2 = get_sub_field('color_2');
            $rounded_2 = get_sub_field('corner_2');
            $padding_2 = get_sub_field('padding_2');
            // Open Wrappers
            switch($style_2){
                case 'bubble':
                    echo '<div class="bubble '.$rounded_2.' '.$color_2.' '.$padding_2.'">';
                    break;
                default:
                    echo '<div class="right-container plain '.$color_2.' '.$padding_2.'">';
                    break;
            }
        ?>
        <div class="inner">

            <?php if(get_sub_field('right_title')): ?>
                <h3 class="block-title"><?php the_sub_field('right_title'); ?></h3>
            <?php endif; ?>

            <?php 
                echo get_sub_field('right_column'); 
                wp_reset_query(); 
                wp_reset_postdata(); 
            ?>

        </div>
        </div>

        <?php
            // Right button
            $button_right = get_sub_field('right_button');
            if( $button_right ): 
                $right_button_margin = get_sub_field('right_column') ? 'mt-4' : 'mt-0';
                $right_link_url = $button_right['url'];
                $right_link_title = $button_right['title'];
                $right_link_target = $button_right['target'] ? $button_right['target'] : '_self';
            ?>
                <div class="text-left section-button-container <?php echo $right_button_margin; ?>"><a class="button red round-tl thick" href="<?php echo esc_url( $right_link_url ); ?>" target="<?php echo esc_attr( $right_link_target ); ?>"><?php echo esc_html( $right_link_title ); ?></a></div>
            <?php endif; ?>

    </div>
    <!-- End right column -->

</div>

</div>
</div>