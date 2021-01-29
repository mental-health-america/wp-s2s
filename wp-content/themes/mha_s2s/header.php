<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js no-svg">
<head>

<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">	
<link rel="profile" href="http://gmpg.org/xfn/11">
<meta http-equiv="X-UA-Compatible" content="IE=edge">

<link rel="shortcut icon" href="/favicon.ico">
<link rel="icon" href="/favicon.png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta name="msapplication-TileColor" content="#FFFFFF">
<meta name="msapplication-TileImage" content="/favicon-144x144.png">

<link rel="preconnect" href="https://fonts.gstatic.com">
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&family=Noto+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet"> 

<?php /*
// For screening.mhanational.org
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-45375759-2"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-45375759-2');
</script>
*/ ?>

<!-- Global site tag (gtag.js) - Google Analytics -->
<?php
// For s2s.mhanational.org
?>
<script async src="https://www.googletagmanager.com/gtag/js?id=G-RGT73CT3W0"></script>
<script>
	window.dataLayer = window.dataLayer || [];
	function gtag(){dataLayer.push(arguments);}
	gtag('js', new Date());
	gtag('config', 'G-RGT73CT3W0', {
		'user_id': '<?php echo get_ipiden().'_'.get_current_user_id(); ?>'
	});
	gtag('event', 'screen_view', {
		'screen_name' : '<?php echo get_the_permalink(); ?>'
	});
</script>

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">

	<a class="skip-link screen-reader-text" href="#content"><?php _e( 'Skip to content', 'mha_s2s' ); ?></a>

	<header id="header" class="clearfix">
	<div class="wrap normal">

		<a id="logo" href="/"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/mha-logo.png" alt="<?php bloginfo( 'name' ); ?>" /></a>

		<div id="utility-menu" class="utility-menu relative">	

			<div id="search-header">
				<button id="search-toggle"
					aria-expanded="false"
					aria-controls="search-form"
					data-href="<?php echo get_search_link(); ?>">
					<strong class="screen-reader-text">Search</strong>
					<span class="icon"></span>
				</button>
				<?php get_search_form(); ?>
			</div>
			
			<span id="sign-in-container">	
				<?php if(is_user_logged_in()): ?>						
					<a class="my-account-link button" href="/my-account?cb=<?php echo date('U'); ?>">My Account</a>
					&nbsp;|
					<a class="my-account-link button" href="<?php echo wp_logout_url(); ?>">Log Out</a>
				<?php else: ?>						
					<button id="sign-in-toggle"
						class="button"
						aria-haspopup="true"
						aria-expanded="false"
						aria-controls="sign-in-container">			
						<strong>Log In</strong>
					</button>
					<div id="sign-in-hover" aria-controls="sign-in-toggle" aria-label="Toggle Sign In Form" aria-expanded="false">
						<div class="bubble round-tr bubble-border narrow dark light-blue">
						<div class="inner clearfix">
							<div class="sign-up-form form-container line-form blue text-left wide">	
								<div class="intro text-blue">
									<?php the_field('log_in_introduction', 'options'); ?>
								</div>
								<?php
									$args = array( 
										'label_username' => 'Email Address',
										'remember' => false,
										'echo' => false
									);
									$login_form = wp_login_form($args); 
									$login_form = str_replace('login-username', 'login-username float-label', $login_form);
									$login_form = str_replace('login-password', 'login-password float-label', $login_form);
									echo $login_form;
								?>
								<div class="right existing-account small">
									<a class="plain" href="<?php echo wp_lostpassword_url(); ?>">Forgot Password</a> |
									<a class="plain" href="/sign-up">Sign Up</a>
								</div>	
							</div>
						</div>
						</div>
					</div>
				<?php endif; ?>	
			</span>

			<button id="mobile-menu-button" class="menu-toggle button" aria-controls="main-menu" aria-label="Toggle Menu" aria-expanded="false">
				<span></span>
				<span></span>
				<span></span>
				<span></span>
				<span></span>
				<span></span>
				<strong class="text">Menu</strong>
			</button>
			
		</div>
		
		<nav id="navigation" class="main-navigation" role="navigation" aria-label="<?php _e( 'Top Menu', 'mha_s2s' ); ?>">
			<?php 
				// Main Navigation
				wp_nav_menu([
					'theme_location' => 'main',
					'menu_id'        => 'main-menu',
					'menu_class'     => 'sf-menu',
					//'walker' 		 => new Dropdown_Walker_Nav_Menu()
				]);
				
				// CTA Buttons
				wp_nav_menu([
					'theme_location' => 'secondary',
					'menu_id'        => 'main-menu-buttons'
				]);
			?>			
		</nav>
	
	</div>
	</header>

	<main id="content" class="site-content">
