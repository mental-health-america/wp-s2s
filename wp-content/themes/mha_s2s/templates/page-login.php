<?php 
/* Template Name: Log In */
get_header(); 
$signup_url = '/sign-up';
$redirect_query = get_query_var('redirect_to');
if($redirect_query){
    $signup_url = add_query_arg( 'redirect_to', $redirect_query, $signup_url ); 
}
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

                <p class="w-100">
                    <?php 
                        echo mha_sso_google( $redirect_query ); 
                    ?>
                </p>  

                <p class="w-100">
                    <a class="button round-small-br small w-100" href="<?php echo $signup_url; ?>">Don't have an account?<br /> <strong>Sign up here</strong></a>
                </p>
            </div>
            
            <?php if( isset( $_GET['login_error'] ) && $_GET['login_error'] == 'true' ): ?>
                <div class="validation_error mt-4">
                    Please supply a valid username and/or password.
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
                    'id_username' => 'user_login_page',
                    'id_password' => 'user_pass_page',
                    'id_remember' => 'user_page_remember',
                    'id_submit' => 'user_page_submit',
                    'remember' => true,
                    'redirect' => add_query_arg( 'logged_in', 'true', $redirect )
                );
                wp_login_form($args); 
            ?>

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