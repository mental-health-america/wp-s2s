<?php
/* Template Name: Sign Up */
get_header();

$login_base     = home_url( '/log-in' );
$redirect_query = get_query_var( 'redirect_to' );
$signup_url     = $login_base;
if ( $redirect_query ) {
	$safe_redirect = wp_validate_redirect( $redirect_query, false );
	if ( $safe_redirect ) {
		$signup_url = add_query_arg( 'redirect_to', $safe_redirect, $login_base );
	}
}
?>

<div class="wrap medium">
	<?php
	while ( have_posts() ) :
		the_post();
		get_template_part( 'templates/blocks/content', 'plain' );
	endwhile;
	wp_reset_postdata();
	?>
</div>

<div class="wrap medium">
	<div class="bubble round-small bubble-border light-blue">
	<div class="inner">

		<div id="sign-up-form" class="form-container line-form blue">

			<div class="existing-account right">
				<p class="w-100">
					<a class="button round-small-br small w-100" href="<?php echo esc_url( $signup_url ); ?>">Have an account?<br /> <strong>Log in here</strong></a>
				</p>

				<p class="w-100">
					<?php
					if ( function_exists( 'mha_sso_google' ) ) {
						echo mha_sso_google( $redirect_query );
					}
					?>
				</p>
			</div>

			<?php echo do_shortcode( '[gravityform id="2" title="false" description="false"]' ); ?>
		</div>

	</div>
	</div>
</div>

<div class="wrap normal">
	<div class="clear pt-4">
		<?php
		if ( have_rows( 'block' ) ) :
			while ( have_rows( 'block' ) ) :
				the_row();
				$layout = get_row_layout();
				get_template_part( 'templates/blocks/block', $layout );
			endwhile;
		endif;
		?>
	</div>
</div>

<?php
get_footer();
