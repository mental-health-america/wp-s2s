<?php
    $layout = get_layout_array(get_query_var('layout')); // Used for A/B testing
?>

<div class="wrap normal">
    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="page-heading plain mb-0">			
            <?php 
                if(in_array('results_header_v1', $layout)):       
                    if($args['espanol']){
                        echo '<h1 class="entry-title">Sus Resultados</h1>';
                    } else {
                        the_title( '<h1 class="entry-title">', '</h1>' ); 
                    }
                endif;
            ?>
        </div>
        <div class="page-intro">
            <?php the_content(); ?>				
        </div>
    </article>
</div>