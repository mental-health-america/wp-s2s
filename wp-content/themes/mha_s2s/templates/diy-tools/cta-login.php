<?php 
    $pid = isset($args['id']) ? $args['id'] : get_the_ID();
    if(!is_user_logged_in()):
    if( !get_field('viewed_result', $pid) || get_the_author_meta('ID') == get_current_user_id() ):
?>
    <?php 
        $width = isset($args['width']) ? $args['width'] : 'narrow';
        $corners = isset($args['corners']) ? $args['corners'] : '';
        $iframe_var = isset($args['iframe_var']) ? $args['iframe_var'] : null;
        $embedded = isset($args['embedded']) ? $args['embedded'] : 0;
        $login_target = $iframe_var ? ' target="_blank"' : '';
    ?>

    <?php if($embedded): ?>
        
        <div id="screen-save">
            <a class="append-thought-id button round blue text-white"<?php echo $login_target; ?> href="/log-in/?redirect_to=<?php echo urlencode(site_url().'/my-account?action=save_diy_').$args['id'] ?>">Log in to save response</a>
        </div>

    <?php else: ?>

        <div class="wrap mb-4 <?php echo $width; ?>">
        <div id="screen-save">
            <div class="bubble round blue thin mb-1 <?php echo $corners; ?>">
            <div class="inner bold text-center">
                <?php 
                ?>
                <a class="append-thought-id text-white"<?php echo $login_target; ?> href="/log-in/?redirect_to=<?php echo urlencode(site_url().'/my-account?action=save_diy_').$args['id'] ?>">Log in</a>
                or
                <a class="append-thought-id text-white"<?php echo $login_target; ?> href="/sign-up/?action=save_diy_<?php echo $args['id']; ?>">register for an account</a>
                to save this to your account.
            </div>
            </div>
        </div>
        </div>
        
    <?php endif; ?>

<?php 
    endif; 
    endif; 
?>