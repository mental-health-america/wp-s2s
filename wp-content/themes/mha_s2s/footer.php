		
		<?php 
			// Check if supporters should be displayed
			$show_supporters = get_field('display_supporters');
			
			// Check for partner referrer and hide_mha_partners field
			$ref_var = get_query_var('ref');
			$iframe_var = get_query_var('iframe');
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
						if ($partner_details['hide_mha_partners'] && $iframe_var) {
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
			<li class="menu-item"><a href="/my-account">My Account</a></li>
			<li class="menu-item"><a href="<?php echo wp_logout_url(); ?>">Log Out</a></li>
		<?php else: ?>						
			<li class="menu-item"><a href="/log-in">Log In</a></li>
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
		<p>We use cookies and similar technologies to help personalize content and provide a better experience. By clicking "Accept", you consent to our use of cookies and data collection. You can learn more about our <a href="https://mhanational.org/privacy-policy" target="_blank">Privacy Policy</a>.</p>
	</div>
	<div class="cookie-banner-buttons">
		<button id="cookie-deny" class="button small thin ghost purple cookie-btn-deny">Deny</button>
		<button id="cookie-accept" class="button small thin ghost teal cookie-btn-accept">Accept</button>
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

<!-- SiteImprove -->
<script type="text/javascript">
/*<![CDATA[*/
(function() {
var sz = document.createElement('script'); sz.type = 'text/javascript'; sz.async = true;
sz.src = '//siteimproveanalytics.com/js/siteanalyze_6229968.js';
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(sz, s);
})();
/*]]>*/
</script>

</body>
</html>
