<?php 
/* Template Name: Example Form */
get_header(); 
?>

<?php 
	while ( have_posts() ) : the_post();
		get_template_part( 'templates/blocks/content', 'page' );
	endwhile;
?>

<form action="#" method="POST" class="form-container" autocomplete="off">   

	<div class="form-message"></div>
	<div class="form-content">
	
	<div class="form-group">
		<label class="form-label" for="first_name">first name</label>
		<input type="text" name="first_name" id="first_name" class="form-input required" />
	</div>

	<div class="form-group">
		<label class="form-label" for="last_name">last name</label>
		<input type="text" name="last_name" id="last_name" class="form-input required" />
	</div>

	<div class="form-group">
		<label class="form-label" for="email">email</label>
		<input type="text" name="email" id="email" class="form-input required" /><input type="text" autocomplete="off" name="email_doublecheck" value="" class="email_doublecheck" tabindex="-1" />
	</div>

	<div class="form-group">
		<label class="form-label" for="zip">zip</label>
		<input type="text" name="zip" id="zip" class="form-input required postal-code" />
	</div>

	<?php 						
		wp_reset_query();			
		$customSource = '';
		if (isset($_GET['src'])) {
			$customSource = $_GET['src'];
		}
		global $post;
		$postSlug = $post->post_name;
	?>
	
	<div class="form-actions">
		<input type="hidden" name="nonce" value="<?php $nonce = wp_create_nonce('mha_s2sSignup'); echo $nonce; ?>" />
		<input type="hidden" name="page" value="<?php echo $postSlug; ?>" />
		<input type="hidden" name="source" value="<?php echo $customSource; ?>" />
		<input type="submit" class="submit button yellow" value="Get Involved&nbsp;&raquo;" />
	</div>
	
</div>
</form>
	
<?php
get_footer();