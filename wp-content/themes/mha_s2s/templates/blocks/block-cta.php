<?php
    // Content Block - Text

    // Styles
    $id = isset($args['id']) ? $args['id'] : get_the_ID();
    $style = get_field('style', $id);
    $color = get_field('color', $id);
    $rounded = get_field('corner_style', $id);
    $padding = get_field('padding', $id);
    $custom = get_field('custom_classes', $id);

    $cta_title = html_entity_decode( addslashes(get_the_title()).' (#'.$id.')' );
?>

<script>
    window.dataLayer.push({
        'event': 'cta_visible',
        'cta_title': "<?php echo $cta_title; ?>"
    });
</script>

<div class="content-block block-text block-cta <?php echo $custom; ?> mb-5 mt-4" data-cta-title="<?php echo $cta_title; ?>">
        
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

    <?php if(get_field('headline', $id)): ?>
        <h2 class="section-title small bold"><?php the_field('headline', $id); ?></h2>
    <?php endif; ?>

    <?php the_field('content', $id); ?>

    <?php if(get_field('button_url', $id)): ?>
        <?php 
            if(get_field('button_text', $id)){
                $button_text = get_field('button_text', $id);
            } else {
                $button_text = 'Read More';
            }
        ?>
        <a class="button round wide cta-button" href="<?php echo get_field('button_text', $id); ?>"><?php echo $button_text; ?></a>
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