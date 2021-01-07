
<div class="article-actions">
    <?php 
        /**
        * Article actions
        */
        $article_id = get_the_ID();
        $uid = get_current_user_id();
        $like_class = '';
        $like_check = checkArticleLikes( $article_id, $uid );
        $like_prefix = 'Save ';
        $like_tooltip = '';
        if($like_check){
            $like_class .= ' liked';
            $like_prefix = 'Unsave ';
        }
        
        if(is_user_logged_in()){
            $like_class .= ' article-like';
        } else {
            $like_class .= ' logged-out';
            $like_tooltip = 'data-toggle="tooltip" data-placement="top" data-html="true" title="You must be <a href=\'/log-in/?redirect_to='.urlencode(get_the_permalink()).'\'>logged in</a> to save this page."';
        }
    ?>
    <p>
        <button class="icon caps like-button<?php echo $like_class; ?>" <?php echo $like_tooltip; ?>data-pid="<?php echo get_the_ID(); ?>" data-nonce="<?php echo wp_create_nonce('articleLike'); ?>">
            <span class="image"><?php include get_theme_file_path("assets/images/save.svg"); ?></span>                        
            <span class="text blue caps light"><?php echo $like_prefix; ?>This Page</span>
        </button>
    </p>

    <?php
        // Share
        if(get_query_var('pathway')){
            $share_url = add_query_arg('pathway', get_query_var('pathway'), get_the_permalink($next_id));
        } else {
            $share_url = get_the_permalink($next_id);
        }
    ?>
    <div class="dropdown">
        <button class="icon share-button dropdown-toggle" type="button" id="shareOptions" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <span class="image"><?php include get_theme_file_path("assets/images/share.svg"); ?></span>                        
            <span class="text blue caps light">Share</span>
        </button>
        <div class="dropdown-menu" aria-labelledby="shareOptions">
            <a class="dropdown-item social-share" href="<?php echo formatTwitter(get_the_title(), $share_url); ?>">Share on Twitter</a>
            <a class="dropdown-item social-share" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>">Share on Facebook</a>
        </div>
    </div>

</div>