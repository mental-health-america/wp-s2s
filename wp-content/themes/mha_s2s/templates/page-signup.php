<?php 
/* Template Name: Sign Up */
get_header(); 
?>

<div class="wrap medium">
    <?php
        while ( have_posts() ) : the_post();
            get_template_part( 'templates/blocks/content', 'plain' );
        endwhile;
    ?>
</div>

<div class="wrap medium">
    <div class="bubble round-small bubble-border light-blue">
    <div class="inner">

        <div id="sign-up-form" class="form-container line-form blue">

            <div class="existing-account right">                
                <?php 
                    $signup_url = '/log-in';
                    if(get_query_var('redirect_to')){
                        $signup_url = add_query_arg( 'redirect_to', get_query_var('redirect_to'), $signup_url ); 
                    }
                ?>
                <a href="<?php echo $signup_url; ?>">Have an account? <strong>Log in here</strong></a>
            </div>

            <?php echo do_shortcode('[gravityform id="2" title="false" description="false"]'); ?>
        </div>

    </div>
    </div>
</div>

<div class="wrap normal">
    <div class="clear pt-4">
        <?php 
            // Content Blocks
            wp_reset_query();
            if( have_rows('block') ):
            while ( have_rows('block') ) : the_row();
                $layout = get_row_layout();
                if( get_template_part( 'templates/blocks/block', $layout ) ):
                    get_template_part( 'templates/blocks/block', $layout );
                endif;
            endwhile;
            endif;
        ?>
    </div>
</div>

<?php
get_footer();