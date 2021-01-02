<?php
    // Content Block - Grid

    // Styles
    $style = get_sub_field('style');
    $color = get_sub_field('color');
    $padding = get_sub_field('padding');
    $custom = get_sub_field('custom_classes');
?>

<div class="content-block block-grid <?php echo $custom.' '.$color.' '.$padding; ?>">
<div class="wrap normal">
<div class="inner">

    <?php if(get_sub_field('headline')): ?>
        <h2 class="block-title"><?php the_sub_field('headline'); ?></h2>
    <?php endif; ?>

    <?php the_sub_field('content'); ?>

    <?php
        // Check rows exists.
        if( have_rows('grid_items') ):
        echo '<div class="grid-items">';
        while( have_rows('grid_items') ) : the_row();
        $link = get_sub_field('link');
        ?>
            <div class="grid-item">
            <div class="grid-item-inner">
            <div class="grid-item-content">

                <?php if($link['url']): ?>
                    <a class="content-inner" href="<?php echo $link['url']; ?>">
                <?php else: ?>
                    <span class="content-inner">
                <?php endif; ?>

                    <?php
                        $image = get_sub_field('icon');
                        if( !empty( $image ) ): 
                    ?>
                        <div class="icon"><img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" /></div>
                    <?php endif; ?>

                    <?php if(get_sub_field('title')): ?>
                        <h4><?php the_sub_field('title'); ?></h4>
                    <?php endif; ?>

                    <?php the_sub_field('text'); ?>

                <?php if($link['url']): ?>
                    </a>
                <?php else: ?>
                    </span>
                <?php endif; ?>
                
            </div>
            </div>
            </div>
        <?php
        endwhile;
        echo '</div>';
        endif;
    ?>
    
</div>
</div>
</div>