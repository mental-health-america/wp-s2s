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

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<div class="page-heading plain">			
		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
	</div>
	<div class="page-intro">
		<?php the_content(); ?>				
	</div>
</article>
