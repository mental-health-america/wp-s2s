		
		<?php 
			// Check if supporters should be displayed
			$show_supporters = get_field('display_supporters');
			
			// Check for partner referrer and hide_mha_partners field
			$ref_var = get_query_var('ref');
			if ($ref_var) {
				$partner_args = array(
					'post_type' => 'partners',
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'fields' => 'ids',
				);
				$partners = get_posts($partner_args);
				
				foreach ($partners as $partner_id) {
					$partner_details = get_field('partner_information', $partner_id);
					if ($ref_var == $partner_details['partner_code']) {
						if ($partner_details['hide_mha_partners']) {
							$show_supporters = false;
						}
						break;
					}
				}
				wp_reset_postdata();
			}
			
			if ($show_supporters): 
		?>
			<div id="supporters" class="pt-5">
			<div class="wrap wide">
				
				<div class="wrap narrow">
					<?php the_field('supporter_introduction','options'); ?>
				</div>

			
				<?php
					if( have_rows('supporters', 'options') ):
					echo '<div id="supporter-logos">';
					while( have_rows('supporters', 'options') ) : the_row();

						$image = get_sub_field('logo');
						$link = get_sub_field('link');

						if($link){
							echo '<a class="supporter-logo plain" href="">';
						} else {
							echo '<span class="supporter-logo">';						
						}

						echo '<img src="'.$image['url'].'" alt="'.$image['alt'].'" />';

						if($link){
							echo '</a>';
						} else {
							echo '</span>';
						}

					endwhile;
					echo '</div>';
					endif;
				?>

			</div>
			</div>
		<?php endif; ?>

		<?php
			// Exit Modals
			get_template_part( 'templates/partials/modal', 'exit' );
		?>
		
	</main>

	<footer id="footer" class="clear">

		<div id="smart-footer">
		<div class="wrap normal">

			<div id="footer-left">

				<div id="footer-sign-up" class="bubble round-bl cerulean">
				<div class="inner">
					<div class="text-blue">
						<?php the_field('footer_sign_up_form', 'options'); ?>
					</div>

					<div class="form-container line-form blue">
						<?php echo do_shortcode('[gravityform id="4" title="false" description="false" ajax="true"]'); ?>
					</div>
				</div>
				</div>

				<div id="footer-social-top" class="footer-social-top footer-social">
					<?php 
						// Footer Menu
						wp_nav_menu([
							'menu'           => 'social-icons',
							'menu_id'        => 'social-menu-top',
						]);
					?>
				</div>

			</div>

			<div id="footer-right">
				<?php 
					// Footer Menu
					wp_nav_menu([
						'menu'           => 'main-menu',
						'menu_id'        => 'footer-menu',
						//'walker' 		 => new Dropdown_Walker_Nav_Menu()
					]);
				?>
			</div>
			
			<div id="footer-social-bottom" class="footer-social-top footer-social">
				<?php 
					// Footer Menu
					wp_nav_menu([
						'menu'           => 'social-icons',
						'menu_id'        => 'social-menu-bottom',
					]);
				?>
			</div>
			
			<div class="clear"></div>
		</div>
		</div>

		<div id="disclaimer">
		<div class="wrap normal">
			<?php the_field('copyright_disclaimer','options'); ?>
			<div class="clear"></div>
		</div>
		</div>
	</footer>

</div>


<div id="mobile-menu-container">
<div class="inner">
	
	<?php /*
	<ul class="header-language mobile menu">
		<li class="menu-item"><?php echo do_shortcode( '[mha_language_switcher]' ); ?></li>
	</ul>
	*/ ?>

	<ul id="mobile-menu-footer" class="menu last secondary">
		
		<?php if(is_user_logged_in()): ?>						
			<li class="menu-item"><a href="/my-account"><?php _e('My Account', 'mhas2s'); ?></a></li>
			<li class="menu-item"><a href="<?php echo wp_logout_url(); ?>"><?php _e('Log Out', 'mhas2s'); ?></a></li>
		<?php else: ?>						
			<li class="menu-item"><a href="/log-in"><?php _e('Log In', 'mhas2s'); ?></a></li>
		<?php endif; ?>
	</ul>
	
	<?php 
		// Mobile Slider Menu
		wp_nav_menu([
			'menu'           => 'main-menu',
			'menu_id'        => 'mobile-menu',
		]);
	?>

</div>
</div>

<?php wp_footer(); ?>

<!-- Cookie Consent Banner -->
<?php if(get_field('enable_cookie_banner', 'options')): ?>
	<div id="cookie-consent-banner" class="cookie-banner" style="display: none;" role="alert" aria-label="Cookie Consent Banner">
	<div class="cookie-banner-inner">
	<div class="cookie-banner-content">
		<div class="cookie-banner-text">
			<?php echo get_field('cookie_banner_text', 'options'); ?>
		</div>
		<div class="cookie-banner-buttons">
			<button id="cookie-deny" class="button small thin ghost purple cookie-btn-deny"><?php _e('Deny', 'mhas2s'); ?></button>
			<button id="cookie-accept" class="button small thin ghost teal cookie-btn-accept"><?php _e('Accept', 'mhas2s'); ?></button>
		</div>
	</div>
	</div>
	</div>

	<script>
		// Add event listeners to cookie banner buttons
		document.addEventListener('DOMContentLoaded', function() {
			var acceptButton = document.getElementById('cookie-accept');
			var denyButton = document.getElementById('cookie-deny');		
			if (acceptButton) {
				acceptButton.addEventListener('click', window.handleCookieAccept);
			}		
			if (denyButton) {
				denyButton.addEventListener('click', window.handleCookieDeny);
			}
		});
	</script>
<?php endif; ?>

<!-- Language Help Bar -->
<?php
if ( get_field( 'display_translation_help_bar', 'options' ) && function_exists( 'mha_is_non_default_language_page' ) && mha_is_non_default_language_page() ) :
	$language_help_bar_text = get_field( 'translation_help_bar_text', 'options' );

	if ( $language_help_bar_text ) :
		?>
	<div id="language-help-bar" class="language-help-bar" style="display: none;" role="region" aria-label="<?php esc_attr_e( 'Language Assistance', 'mhas2s' ); ?>">
		<div class="language-help-bar-inner">
			<div class="language-help-bar-content">
				<div class="language-help-bar-text">
					<span class="translation-block trp-language-help-bar-text"><?php echo wp_kses_post( $language_help_bar_text ); ?></span>
				</div>
				<div class="language-help-bar-buttons">
					<button id="language-help-bar-dismiss" type="button" class="plain dark-blue bold caps language-help-bar-btn-dismiss">
						<span class="translation-block trp-language-help-bar-dismiss"><?php esc_html_e( 'Dismiss', 'mhas2s' ); ?></span>
					</button>
				</div>
			</div>
		</div>
	</div>

	<button id="language-help-bar-toggle" type="button" class="language-help-bar-toggle button small thin purple" style="display: none;" aria-controls="language-help-bar" aria-expanded="false">
		<span class="translation-block trp-language-help-bar-toggle"><?php esc_html_e( 'Language Assistance', 'mhas2s' ); ?></span>
	</button>
		<?php
	endif;
endif;
?>

</body>
</html>