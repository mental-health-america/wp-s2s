<?php						
    $type = get_field('type');                        
    $article_type = get_field('type');
?>
<li class="mb-4">
    <p class="mb-2">	
        <a class="dark-gray plain" href="<?php echo add_query_arg('ref', $term->term_id, get_the_permalink()); ?>"><?php the_title(); ?></a>
    </p>
    <!--<div class="medium small pl-5"><?php echo short_excerpt(); ?></div>-->
</li>