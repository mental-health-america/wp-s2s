<?php if(!is_user_logged_in()): ?>
    <?php 
        $width = isset($args['width']) && $args['width'] ? $args['width'] : 'narrow';
        $corners = isset($args['corners']) && $args['corners'] ? $args['corners'] : '';
        $iframe_var = isset($args['iframe_var']) && $args['iframe_var'] ? $args['iframe_var'] : null;
    ?>
    <div class="wrap pt-3 <?php echo $width; ?>">
        <div id="screen-save">
            <div class="bubble round blue thin mb-1 <?php echo $corners; ?>">
            <div class="inner bold text-center">
                <?php 
                    if($iframe_var){    
                        $login_target = ' target="_blank"';
                    } else {
                        $login_target = '';
                    }
                ?>
                <a class="append-thought-id text-white"<?php echo $login_target; ?> href="/log-in/?redirect_to=<?php echo urlencode(site_url().'/my-account?action=save_screen_').$args['id'] ?>">
                    <?php esc_html_e( 'Log in', 'mha_s2s' ); ?>
                </a>
                <?php esc_html_e( 'or', 'mha_s2s' ); ?>
                <a class="append-thought-id text-white"<?php echo $login_target; ?> href="/sign-up/?action=save_screen_<?php echo $args['id']; ?>">
                    <?php esc_html_e( 'register for an account', 'mha_s2s' ); ?>
                </a>
                <?php esc_html_e( 'to save this result to your account', 'mha_s2s' ); ?>
            </div>
            </div>
        </div>
    </div>
<?php endif; ?>
