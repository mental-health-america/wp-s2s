<?php
/**
 * DIY Tool Container
 */

get_header();
$tool_type = get_field('tool_type');

switch($tool_type){
    case 'worksheet':
        $heading_type = 'plain red'; 
        break;
    default:
        $heading_type = 'plain';
        break;
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
    <div class="page-heading <?php echo $heading_type; ?>">	
    <div class="wrap normal" data-aos="fade-down">				
        <?php 
            the_title( '<h1 class="entry-title">', '</h1>' ); 
        ?>
        <?php if($tool_type != 'worksheet'): ?>
        <div class="page-intro mx-auto">
            <?php the_content(); ?>				
        </div>
        <?php endif; ?>
    </div>
    </div>
</article>

<?php 
	switch($tool_type){
		case 'question_answer':
			get_template_part( 'templates/diy-tools/question', 'answers' ); 
			break;
        case 'worksheet':
            get_template_part( 'templates/diy-tools/worksheet' ); 
            break;
	}
?>

<?php		
get_footer();
