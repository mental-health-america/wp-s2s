<?php
    // Content Block - Text

    // Styles
    $style = get_sub_field('style');
    $color = get_sub_field('color');
    $rounded = get_sub_field('corner_style');
    $padding = get_sub_field('padding');
    $custom = get_sub_field('custom_classes');
?>

<div class="content-block block-text <?php echo $custom; ?>">
        
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
                echo '<div class="wrap normal">';
                break;
        }
    ?>

    <?php if(get_sub_field('headline')): ?>
        <h2 class="block-title"><?php the_sub_field('headline'); ?></h2>
    <?php endif; ?>

    <?php the_sub_field('content'); ?>

    
    <?php
        // Close Wrappers
        switch($style){
            case 'bubble':
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