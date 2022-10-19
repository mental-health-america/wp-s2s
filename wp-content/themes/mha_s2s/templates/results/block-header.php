
<div class="wrap normal">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="page-heading plain">			
            <?php 
                if($args['espanol']){
                    echo '<h1 class="entry-title">Sus Resultados</h1>';
                } else {
                    the_title( '<h1 class="entry-title">', '</h1>' ); 
                }
            ?>
        </div>
        <div class="page-intro">
            <?php the_content(); ?>				
        </div>
    </article>
</div>