<?php if(!is_user_logged_in()): ?>
    <?php 
        $width = $args['width'] ? $args['width'] : 'narrow';
        $corners = $args['corners'] ? $args['corners'] : '';
        $iframe_var = $args['iframe_var'] ? $args['iframe_var'] : null;
    ?>
    <div class="wrap mt-3 <?php echo $width; ?>">
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
                <a class="append-thought-id text-white"<?php echo $login_target; ?> href="/log-in/?redirect_to=<?php echo urlencode(site_url().'/my-account?action=save_screen_').$args['id'] ?>">Log in</a>
                or
                <a class="append-thought-id text-white"<?php echo $login_target; ?> href="/sign-up/?action=save_screen_<?php echo $args['id']; ?>">register for an account</a>
                to save this result to your account.
            </div>
            </div>
        </div>
    </div>
<?php endif; ?>