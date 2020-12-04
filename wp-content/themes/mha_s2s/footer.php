	</main>

	<footer id="footer" class="clear">

		<div id="smart-footer">
		<div class="wrap normal">

			<div id="footer-left">

				<div id="footer-sign-up" class="bubble round-bl">
				<div class="inner">
					<?php the_field('footer_sign_up_form', 'options'); ?>
				</div>
				</div>

				<div id="footer-social">
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
</div>
</div>

<?php wp_footer(); ?>

</body>
</html>
