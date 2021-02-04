<?php
    // Content Block - Text

    // Styles
    $style = get_field('style');
    $color = get_field('color');
    $rounded = get_field('corner_style');
    $padding = get_field('padding');
    $custom = get_field('custom_classes');
?>

<div class="content-block block-text block-cta <?php echo $custom; ?> mb-4 mt-4">
        
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

    <?php if(get_field('headline')): ?>
        <h2 class="section-title small bold"><?php the_field('headline'); ?></h2>
    <?php endif; ?>

    <?php the_field('content'); ?>

    <?php if(get_field('button_url')): ?>
        <?php 
            if(get_field('button_text')){
                $button_text = get_field('button_text');
            } else {
                $button_text = 'Read More';
            }
        ?>
        <a class="button round wide" href="<?php echo get_field('button_text'); ?>"><?php echo $button_text; ?></a>
    <?php endif; ?>
    
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