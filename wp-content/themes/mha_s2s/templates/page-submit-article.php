<?php 
/* Template Name: Submit Article */
acf_form_head();
acf_enqueue_uploader();
get_header(); 
?>

<script src='https://www.google.com/recaptcha/api.js' async defer></script>

<div class="wrap medium">
    <?php
        while ( have_posts() ) : the_post();
            get_template_part( 'templates/blocks/content', 'plain' );
        endwhile;        
    ?>
</div>

<div class="wrap medium">    
<div class="bubble round-tl light-blue">
<div class="inner" id="article-submit-container">
    
    <?php
        if(get_query_var('updated') == 'true'):
            echo '<div id="message" class="updated">';
            the_field('thank_you_message');
            echo '</div>';
        else:            
        ?>
            <form id="article-submit-recaptcha-confirm" class="text-center" action="?" method="POST">
                <div id="recaptcha-error" class="bubble round coral hidden thinner mb-4"><div class="inner"></div></div>
                <label>Please confirm the following captcha to submit a resource:</label>
                <div style="width: 304px; margin: 0 auto;"><div class="g-recaptcha" data-sitekey="6LftXuYZAAAAAOyPYz_3N6shIU7JiSovAbrGHjWf"></div></div>
                <br/>
                <input type="hidden" name="snonce" value="<?php echo wp_create_nonce('showForm'); ?>" />
                <input type="hidden" name="return" value="<?php echo add_query_arg( 'updated', 'true', get_the_permalink()); ?>" />
                <input type="submit" class="button round" value="Proceed" />
            </form>
        <?php 
        endif; 
    ?>

</div>
</div>
</div>

<?php get_footer(); ?>