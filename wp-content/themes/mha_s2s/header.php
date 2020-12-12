<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js no-svg">
<head>

<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">	
<link rel="profile" href="http://gmpg.org/xfn/11">

<link rel="shortcut icon" href="/favicon.ico">
<link rel="icon" href="/favicon.png">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<meta name="msapplication-TileColor" content="#FFFFFF">
<meta name="msapplication-TileImage" content="/favicon-144x144.png">

<link rel="preconnect" href="https://fonts.gstatic.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&display=swap" rel="stylesheet"> 
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet"> 

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">

	<a class="skip-link screen-reader-text" href="#content"><?php _e( 'Skip to content', 'mha_s2s' ); ?></a>

	<header id="header" class="clearfix">
	<div class="wrap normal">

		<a id="logo" href="/"><img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/images/mha-logo.png" alt="<?php bloginfo( 'name' ); ?>" /></a>

		<div id="utility-menu">	

			<div id="search-header">
				<button id="search-toggle"
					aria-expanded="false"
					aria-controls="search-form">
					<strong class="screen-reader-text">Search</strong>
					<span class="icon"></span>
				</button>
				<?php get_search_form(); ?>
			</div>
			
			<?php if(is_user_logged_in()): ?>						
				<a class="my-account-link button" href="/my-account">My Account</a>
			<?php else: ?>							
				<button id="sign-in-toggle"
					class="button"
					aria-haspopup="true"
					aria-expanded="false"
					aria-controls="sign-in-container">			
					<strong>Log In</strong>
				</button>
			<?php endif; ?>

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
