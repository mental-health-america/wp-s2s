<?php
/**
 * DIY Tool Container
 */

get_header();
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="page-heading plain">	
    <div class="wrap normal" data-aos="fade-down">				
        <?php 
            // get_template_part( 'templates/blocks/breadcrumbs' );
            the_title( '<h1 class="entry-title">', '</h1>' ); 
        ?>
        <div class="page-intro mx-auto">
            <?php the_content(); ?>				
        </div>
    </div>
    </div>
</article>

<?php 
	$tool_type = get_field('tool_type');
	switch($tool_type){
		case 'question_answer':
			get_template_part( 'templates/diy-tools/question', 'answers' ); 
			break;
	}
?>

<?php		
get_footer();
