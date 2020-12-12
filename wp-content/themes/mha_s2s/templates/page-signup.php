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

            <div id="existing-account" class="right">
                <a href="/log-in">Have an account? <strong>Log in here</strong></a>
            </div>

            <?php echo do_shortcode('[gravityform id="2" title="false" description="false"]'); ?>
        </div>

    </div>
    </div>
</div>

<?php
get_footer();