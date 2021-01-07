<?php 
/* Template Name: Log In */
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
                <a href="/sign-up">Don't have an account? <strong>Register here</strong></a>
            </div>
            
            <?php if( isset( $_GET['login_error'] ) && $_GET['login_error'] == 'true' ): ?>
                <div class="validation_error">
                    The login credentials were not valid. Please try again.
                </div>
            <?php endif; ?>

            <?php 
                if(get_query_var('redirect_to')){
                    $redirect = get_query_var('redirect_to');
                } else {
                    $redirect = site_url();
                }
                $args = array( 
                    'label_username' => 'Email Address',
                    'remember' => false,
                    'redirect' => $redirect
                );
                wp_login_form($args); 
            ?>

        </div>

    </div>
    </div>
</div>

<?php
get_footer();