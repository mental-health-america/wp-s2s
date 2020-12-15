<?php if(get_field('hero_headline') || get_field('hero_introduction')): ?>
    <div id="hero" class="wrap wide">
        <div class="bubble round-tl <?php echo get_field('hero_style'); ?>">
            <div class="inner">

                <?php if(get_field('hero_headline')): ?>
                    <h1><?php the_field('hero_headline'); ?></h1>
                <?php endif; ?>

                <?php the_field('hero_introduction'); ?>

                <?php 
                    if( have_rows('hero_buttons') ):
                    echo '<div class="hero-buttons">';
                    while( have_rows('hero_buttons') ) : the_row();

                        $text = get_sub_field('text');
                        $url = get_sub_field('link');
                        echo '<a href="'.$url.'" class="button round green" href="">'.$text.'</a>';

                    endwhile;
                    echo '</div>';
                    endif;
                ?>

            </div>
        </div>
    </div>
<?php endif; ?>