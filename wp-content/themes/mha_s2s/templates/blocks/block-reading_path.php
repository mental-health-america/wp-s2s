<?php
    // Content Block - Reading Path

    // Styles
    $style = get_sub_field('style');
    $color = get_sub_field('color');
    $rounded = get_sub_field('corner_style');
    $padding = get_sub_field('padding');
    $custom = get_sub_field('custom_classes');
    $reading_path = get_sub_field('reading_path');
    $section_width = get_sub_field('section_width') ? get_sub_field('section_width') : 'normal';
?>

<div class="content-block block-reading-path <?php echo $custom; ?>">
<div class="wrap <?php echo $section_width; ?>">
        
    <?php if( get_sub_field('section_title') ): ?>
        <h2 class="section-title-cb large text-center"><?php the_sub_field('section_title'); ?></h2>
    <?php endif; ?>

    <?php echo get_sub_field('content'); ?>

    <?php
        if($reading_path){
            get_template_part( 'templates/blocks/reading', 'path', array( 
                'no_wrapper' => 1, 
                'path_id' => $reading_path->ID,
                'zebra' => 'odd'
            ) );
        } 
    ?>

    <?php
        // Button
        $button = get_sub_field('button');
        if( $button ): 
            $link_url = $button['url'];
            $link_title = $button['title'];
            $link_target = $button['target'] ? $button['target'] : '_self';
        ?>
            <div class="text-right section-button-container" ><a class="button red round-tl thick" style="max-width: 520px; min-width: 0px;" href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>"><?php echo esc_html( $link_title ); ?></a></div>
    <?php endif; ?>
    
</div>
</div>