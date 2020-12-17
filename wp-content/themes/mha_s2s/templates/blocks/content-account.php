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

<article id="my-account" <?php post_class(); ?>>

	<div class="page-heading bar">	
		<div class="wrap normal relative">		
			<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
			
			<div class="bubble narrow round-small-bl blue width-50" id="account-settings">
			<div class="inner">
				<div class="caps large">DISPLAY NAME:</div>
				<?php
					global $current_user;
					get_currentuserinfo();
					echo '<h3 class="text-white">'.$current_user->nickname.'</h3>';
				?>

				<div class="pt-2">
					<button class="button white plain caps p-0 hover-bar reveal-button" data-reveal="account-settings-form">Account Settings</button> | 
					<a class="button white plain caps p-0 hover-bar" href="<?php echo wp_logout_url( home_url() ); ?>">Log Out</a>
				</div>

				<div id="account-settings-form" class="form-container line-form">
					<?php echo do_shortcode('[gravityform id="3" title="false" description="false"]'); ?>
				</div>

			</div>
			</div>

		</div>
	</div>

	<div class="page-intro clear">
		<div class="wrap normal">	
			<?php the_content(); ?>				
		</div>
	</div>

</article>
