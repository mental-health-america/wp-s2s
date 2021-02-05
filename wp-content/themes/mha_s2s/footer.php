		<?php if(get_field('display_supporters')): ?>
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

	</main>

	<footer id="footer" class="clear">

		<div id="smart-footer">
		<div class="wrap normal">

			<div id="footer-left">

				<div id="footer-sign-up" class="bubble round-bl">
				<div class="inner">
					<?php the_field('footer_sign_up_form', 'options'); ?>

					<div class="form-container line-form">
						<?php echo do_shortcode('[gravityform id="4" title="false" description="false" ajax="true"]'); ?>
					</div>
				</div>
				</div>

				<div id="footer-social-top" class="footer-social-top footer-social">
					<?php 
						// Footer Menu
						wp_nav_menu([
							'menu'           => 'social-icons',
							'menu_id'        => 'social-menu',
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
						'menu_id'        => 'social-menu',
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

	<?php 
		// Mobile Slider Menu
		wp_nav_menu([
			'menu'           => 'main-menu',
			'menu_id'        => 'mobile-menu',
		]);
	?>

	<ul id="mobile-menu-footer" class="menu last secondary">
		<?php if(is_user_logged_in()): ?>						
			<li class="menu-item"><a href="/my-account">My Account</a></li>
			<li class="menu-item"><a href="<?php echo wp_logout_url(); ?>">Log Out</a></li>
		<?php else: ?>						
			<li class="menu-item"><a href="/log-in">Log In</a></li>
		<?php endif; ?>
	</ul>
	
</div>
</div>

<?php wp_footer(); ?>

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
