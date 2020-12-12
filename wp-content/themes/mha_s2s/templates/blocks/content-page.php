<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package LCV VF
 * @subpackage LCV VF
 * @since 1.0
 * @version 1.0
 */

$type = get_post_type();
$customClasses = '';

if($type == 'article'){
	$customClasses = ' red';
}

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<div class="page-heading bar<?php echo $customClasses; ?>">	
	<div class="wrap normal">		
		
		<?php
			if ( function_exists('yoast_breadcrumb') ) {
				yoast_breadcrumb( '<p id="breadcrumbs">','</p>' );
			}
		?>
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		
	</div>
	</div>

	<div class="page-content">
	<div class="wrap normal">	

		<?php the_content(); ?>		

	</div>
	</div>

</article>
