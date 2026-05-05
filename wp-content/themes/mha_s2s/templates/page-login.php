<?php
/* Template Name: Log In */

$signup_base    = home_url( '/sign-up' );
$redirect_query = get_query_var( 'redirect_to' );
$signup_url     = $signup_base;
if ( $redirect_query ) {
	$safe_redirect = wp_validate_redirect( $redirect_query, false );
	if ( $safe_redirect ) {
		$signup_url = add_query_arg( 'redirect_to', $safe_redirect, $signup_base );
	}
}

if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/my-account' ) );
	exit();
}

get_header();
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
					<?php
					if ( function_exists( 'mha_sso_google' ) ) {
						echo mha_sso_google( $redirect_query );
					}
					?>
				</p>

				<p class="w-100">
					<a class="button round-small-br small w-100" href="<?php echo esc_url( $signup_url ); ?>">Don't have an account?<br /> <strong>Sign up here</strong></a>
				</p>
			</div>

			<?php if ( isset( $_GET['login_error'] ) && 'true' === $_GET['login_error'] ) : ?>
				<div class="validation_error mt-4">
					Please supply a valid username and/or password.
				</div>
			<?php endif; ?>

			<?php
				$redirect = get_query_var( 'redirect_to' ) ? get_query_var( 'redirect_to' ) : site_url();
				$redirect = wp_validate_redirect( $redirect, site_url() );
				$args     = array(
					'label_username' => 'Email Address',
					'id_username'    => 'user_login_page',
					'id_password'    => 'user_pass_page',
					'id_remember'    => 'user_page_remember',
					'id_submit'      => 'user_page_submit',
					'remember'       => true,
					'redirect'       => add_query_arg( 'logged_in', 'true', $redirect ),
				);
				wp_login_form( $args );
			?>

			<div class="existing-account small">
				<a class="plain" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Forgot Password?</a>
			</div>

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
