<?php if(!is_user_logged_in()): ?>
    <?php 
        $width = isset($args['width']) && $args['width'] != '' ? $args['width'] : 'narrow';
        $corners = isset($args['corners']) && $args['corners'] ? $args['corners'] : '';
        $iframe_var = isset($args['iframe_var']) && $args['iframe_var'] != '' ? $args['iframe_var'] : null;
    ?>
    <div class="wrap <?php echo $width; ?>">
        <div id="screen-save">
            <div class="bubble round white thick mb-3 <?php echo $corners; ?>">
            <div class="inner bold text-left">
                <?php 
                    if($iframe_var){    
                        $login_target = ' target="_blank"';
                    } else {
                        $login_target = '';
                    }
                ?>

                <p>To save your results, create an account. With an MHA Screening account, you can track your symptoms and scores over time. All your results remain completely confidential! You can also save articles and other resources to revisit later.</p>

                <?php if($args['with_email'] == true): ?>
                <p>Or, you can enter an email address and have us email your results to you.</p>
                <?php endif; ?>

                <a class="append-thought-id button navy round"<?php echo $login_target; ?> href="/sign-up/?redirect_to=<?php echo urlencode(site_url().'/my-account?action=save_screen_').$args['id'] ?>">Register or log in to save results</a>
            </div>
            </div>
        </div>
    </div>
<?php endif; ?>