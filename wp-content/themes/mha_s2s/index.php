<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package MHA S2S
 * @subpackage MHA S2S
 * @since 1.0
 * @version 1.0
 */

get_header(); ?>

<?php if ( is_home() && ! is_front_page() ) : ?>
	<header class="page-header">
		<h1 class="page-title"><?php single_post_title(); ?></h1>
	</header>
<?php else : ?>
	<header class="page-header">
		<h2 class="page-title"><?php _e( 'Posts', 'mha_s2s' ); ?></h2>
	</header>
<?php endif; ?>

<div id="primary" class="content-area">

	<?php
		if ( have_posts() ) :

			/* Start the Loop */
			while ( have_posts() ) : the_post();

				get_template_part( 'templates/blocks/content', 'page' );

			endwhile;

			the_posts_pagination( array(
				'prev_text' => '<span class="screen-reader-text">' . __( 'Previous page', 'mha_s2s' ) . '</span>',
				'next_text' => '<span class="screen-reader-text">' . __( 'Next page', 'mha_s2s' ) . '</span>',
				'before_page_number' => '<span class="meta-nav screen-reader-text">' . __( 'Page', 'mha_s2s' ) . ' </span>',
			) );

		else :

			get_template_part( 'template-parts/post/content', 'page' );

		endif;
	?>

</div>

<?php get_footer();
