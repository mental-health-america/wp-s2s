<?php
    $show = isset($args['show']) ? '' : 'collapse';
?>

<div class="bubble thick light-teal bubble-border round-tl montserrat mb-4 <?php echo $show; ?> anchor-content" id="email-results">
<div class="inner small">
    <div class="container-fluid">
        
        <form id="email-screening-results" action="#" method="POST" class="form-container line-form wide blue" autocomplete="off">   

        <div class="form-message" style="display: none;"></div>
        <div class="form-content">

            <p class="form-group float-label mb-0">
                <label class="form-label" for="email">email</label>
                <input type="text" name="email" id="email" class="form-input required" />
                <input type="text" autocomplete="off" name="email_doublecheck" value="" class="email_doublecheck" tabindex="-1" />
            </p>

            <?php 					
                global $post;
                $postSlug = $post->post_name;
            ?>
            <div class="form-actions pt-3">
                <input type="hidden" name="nonce" value="<?php $nonce = wp_create_nonce('mhaScreenEmail'); echo $nonce; ?>" />
                <input type="hidden" name="screen_id" value="<?php echo $args['screen_id']; ?>" />
                <input type="hidden" name="entry_id" value="<?php echo $args['entry_id']; ?>" />
                <input type="hidden" name="screen_user_id" value="<?php echo $args['user_screen_id']; ?>" />                                                
                <?php if($args['espanol']): ?>
                    <input type="submit" class="submit button teal gform_button espanol" value="Enviar" />
                <?php else: ?>
                    <input type="submit" class="submit button teal gform_button" value="Send Results" />
                <?php endif; ?>
            </div>

        </div>
        </form>

    </div>
</div>
</div>