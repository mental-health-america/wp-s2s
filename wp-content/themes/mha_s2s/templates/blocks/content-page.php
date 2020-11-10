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
	<?php the_title( '<h1 class="entry-title wow fadeInDown">', '</h1>' ); ?>
	<div class="intro-content text-green pt-3 pb-3 wow fadeIn" data-wow-delay=".2s">
		<?php the_content(); ?>
	</div>
</article>
