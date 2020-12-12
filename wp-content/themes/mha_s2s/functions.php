<?php
/**
 * MHA S2S functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package MHA S2S
 * @subpackage MHA S2S
 * @since 1.0
 */

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function mha_s2s_setup() {

	/*
	 * Make theme available for translation.
	 */
	load_theme_textdomain( 'mha_s2s' );

	/*
	 * Title tag support
	 */
	add_theme_support( 'title-tag' );

	/*
	 * Switch default core markup to output valid HTML5.
	 */
	add_theme_support(
		'html5',
		array(
			'search-form',
			'gallery',
			'caption',
			'script',
			'style',
		)
	);

	/*
	 * Custom image sizes
	 */
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'banner', 2000, 1200, true );
	add_image_size( 'large', 1200, 800, true );
	add_image_size( 'medium', 800, 600, true );
	add_image_size( 'small', 400, 300, true );
	add_image_size( 'square', 250, 250, true );

	// This theme uses wp_nav_menu() in two locations.
	register_nav_menus( array(
		'main'    	=> __( 'Main Menu', 'mha_s2s' ),
		'secondary' => __( 'Secondary Menu', 'mha_s2s' ),
		'social' 	=> __( 'Social Links Menu', 'mha_s2s' ),
	) );

}
add_action( 'after_setup_theme', 'mha_s2s_setup' );

/**
 * Custom Editor Styles
 */
add_editor_style( 
	array( 
		'assets/css/editor.css', // Site specific styles
		//esc_url_raw('https://use.typekit.net/###.css') // Add additional webfonts to the editor
	) 
);


/**
 * Enqueue scripts and styles.
 */
function mha_s2s_scripts() {

	// Load our main styles
	wp_enqueue_style( 'mha_s2s-style', get_stylesheet_uri() );
    //wp_enqueue_style( 'mha_s2s-bootstrap-full-css', get_template_directory_uri() . '/assets/bootstrap/css/bootstrap.min.css', array(), '4.3.1' ); // Optional Full Bootstrap
    wp_enqueue_style( 'mha_s2s-bootstrap-grid-css', get_template_directory_uri() . '/assets/bootstrap/css/bootstrap-grid.min.css', array(), '4.3.1' ); // Bootstrap grid only
	wp_enqueue_style( 'mha_s2s-main-style', get_template_directory_uri() . '/assets/css/main.css', array(), time() );
	
	// Add print CSS.
	// wp_enqueue_style( 'mha_s2s-print-style', get_template_directory_uri() . '/print.css', null, '1.0', 'print' );
    
	// Scripts
	wp_enqueue_script( 'mha_s2s-skip-link-focus-fix', get_template_directory_uri() . '/assets/js/skip-link-focus-fix.js', array(), '1.0', true );
	wp_enqueue_script( 'mha_s2s-hover-intent', get_template_directory_uri() . '/assets/js/hoverIntent.js', array(), '0.7', true );
	wp_enqueue_script( 'mha_s2s-superfish', get_template_directory_uri() . '/assets/js/superfish.min.js', array(), '1.7.10.1', true );
	wp_enqueue_script( 'mha_s2s-wow', get_template_directory_uri() . '/assets/js/wow.min.js', array(), '1.3', true );
	//wp_enqueue_script( 'mha_s2s-bootstrap-js', get_template_directory_uri() . '/assets/bootstrap/js/bootstrap.bundle.min.js', array(), '4.3.1', true );

	// Load the html5 shiv.
	wp_enqueue_script( 'html5', get_theme_file_uri( '/assets/js/html5.js' ), array(), '3.7.3' );
	wp_script_add_data( 'html5', 'conditional', 'lt IE 9' );

	// Global Javascript
	wp_enqueue_script( 'mha_s2s-global', get_theme_file_uri( '/assets/js/global.js' ), array( 'jquery' ), '1.0.0', true );
	
}
add_action( 'wp_enqueue_scripts', 'mha_s2s_scripts' );


/**
 * Use front-page.php when Front page displays is set to a static page.
 */
function mha_s2s_front_page_template( $template ) {
	return is_home() ? '' : $template;
}
add_filter( 'frontpage_template',  'mha_s2s_front_page_template' );


/**
 * Custom single page templates
 */
function get_custom_cat_template($single_template) {
	global $post;

	if ( in_category( 'something' )) {
		$single_template = dirname( __FILE__ ) . '/single-something.php';
	}
	return $single_template;
}
add_filter( "single_template", "get_custom_cat_template" ) ;

/**
 * Render SVG inline
 */
function getSVG($svg){
	$upload_dir = wp_upload_dir()['path'];
	return file_get_contents($upload_dir.'/'.$svg);
}


/**
 * Customize the 'Format' select dropdown
 */
function my_mce_buttons_2( $buttons ) {
    array_unshift( $buttons, 'styleselect' );
    return $buttons;
}
// Register our callback to the appropriate filter
add_filter('mce_buttons_2', 'my_mce_buttons_2');

// Callback function to filter the MCE settings
function my_mce_before_init_insert_formats( $init_array ) {  
    // Define the style_formats array
    $style_formats = array(  
        // Each array child is a format with it's own settings
        array(  
            'title' => 'Button',  
            'selector' => 'a',  
            'classes' => 'button'             
        )
    );  
    // Insert the array, JSON ENCODED, into 'style_formats'
    $init_array['style_formats'] = json_encode( $style_formats );  

    return $init_array;  

} 
// Attach callback to 'tiny_mce_before_init' 
add_filter( 'tiny_mce_before_init', 'my_mce_before_init_insert_formats' );


/**
 * Backward Compatibility
 */

 // Shim for new wp_body_open() function
 // https://make.wordpress.org/themes/2019/03/29/addition-of-new-wp_body_open-hook/
 if ( ! function_exists( 'wp_body_open' ) ) {
	function wp_body_open() {
			do_action( 'wp_body_open' );
	}
}


/**
 * Pre Populate Tokens
 */
// apply to all forms
add_filter( 'gform_field_input', 'hidden_token_field', 10, 5 );
function hidden_token_field( $input, $field, $value, $lead_id, $form_id ) {
	
	// Gererate custom unique ID (that's not the ID) for this submission
	if ( $field->label == 'Token' ) {
		$input = '<input name="input_'.$field->id.'" id="input_'.$form_id.'_'.$field->id.'" type="hidden" class="gform_hidden" aria-invalid="false" value="'.wp_generate_uuid4().'">';
	}

   // Prepopulate with screen page's ID
   if ( $field->label == 'Screen ID' ) {
		$input = '<input name="input_'.$field->id.'" id="input_'.$form_id.'_'.$field->id.'" type="hidden" class="gform_hidden" aria-invalid="false" value="'.get_the_ID().'">';
	}

	// Legacy IP Identifier
	if ( $field->label == 'ipiden' ) {
		$input = '<input name="input_'.$field->id.'" id="input_'.$form_id.'_'.$field->id.'" type="hidden" class="gform_hidden" aria-invalid="false" value="'.md5($_SERVER['REMOTE_ADDR']).'">';
	}
	
    return $input;
}


/**
 * Allowed additional query vars
 */
function mha_s2s_query_vars( $qvars ) {
    $qvars[] = 'sid';
    $qvars[] = 'login_error';
    $qvars[] = 'pathway';
    return $qvars;
}
add_filter( 'query_vars', 'mha_s2s_query_vars' );


/**
 * 0 based ACF index for updating rows
 */
add_filter('acf/settings/row_index_offset', '__return_zero');


/**
 * Customized queries for repeater fields
 */
function responses_where( $where ) {
	$where = str_replace("meta_key = 'responses_$", "meta_key LIKE 'responses_%", $where);
	return $where;
}
add_filter('posts_where', 'responses_where');

function admin_seeds_where( $where ) {
	$where = str_replace("meta_key = 'admin_pre_seeded_thought_$", "meta_key LIKE 'admin_pre_seeded_thought_%", $where);
	return $where;
}
add_filter('posts_where', 'admin_seeds_where');

function user_seeds_where( $where ) {
	$where = str_replace("meta_key = 'user_pre_seeded_thought_$", "meta_key LIKE 'user_pre_seeded_thought_%", $where);
	return $where;
}
add_filter('posts_where', 'user_seeds_where');


/**
 * Custom Progress Bar
 */
add_filter( 'gform_progress_bar', 'custom_screen_progress_bar', 10, 3 );
function custom_screen_progress_bar( $progress_bar, $form, $confirmation_message ) {

	$current_page = GFFormDisplay::get_current_page( $form['id'] );
	$page_count = GFFormDisplay::get_max_page_number( $form ) + 1;
	
    $progress_bar = '<ol class="screen-progress-bar clearfix step-'.$current_page.'-of-'.$page_count.'">
        <li class="step-1"><span>Test<br />Questions</span></li>
        <li class="step-2"><span>Demographic<br />Information</span></li>
        <li class="step-3"><span>Your<br />Results</span></li>
    </ol>';
 
    return $progress_bar;
}

/**
 * Useful array search function
 */
function in_multiarray($needle, $haystack, $strict = false) {
    foreach ($haystack as $item) {
        if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_multiarray($needle, $item, $strict))) {
            return true;
        }
    }
    return false;
}

/**
 * Function Name: front_end_login_fail.
 * Description: This redirects the failed login to the custom login page instead of default login page with a modified url
**/
add_action( 'wp_login_failed', 'front_end_login_fail' );
function front_end_login_fail( $username ) {
	// Getting URL of the login page
	$referrer = $_SERVER['HTTP_REFERER'];    
	// if there's a valid referrer, and it's not the default log-in screen
	if( !empty( $referrer ) && !strstr( $referrer,'wp-login' ) && !strstr( $referrer,'wp-admin' ) ) {
		wp_redirect( get_permalink( 566 ) . "?login_error=true" ); 
		exit;
	}
}

/**
 * Function Name: check_username_password.
 * Description: This redirects to the custom login page if user name or password is   empty with a modified url
**/
add_action( 'authenticate', 'check_username_password', 1, 3);
function check_username_password( $login, $username, $password ) {
	// Getting URL of the login page
	$referrer = $_SERVER['HTTP_REFERER'];

	// if there's a valid referrer, and it's not the default log-in screen
	if( !empty( $referrer ) && !strstr( $referrer,'wp-login' ) && !strstr( $referrer,'wp-admin' ) ) { 
		if( $username == "" || $password == "" ){
			wp_redirect( get_permalink( 566 ) . "?login_error=true" );
			exit;
		}
	}
}

/**
 * Custom archive titles
 */
add_filter( 'get_the_archive_title', function ($title) {    
	if ( is_category() ) {    
		$title = single_cat_title( '', false );    
	} elseif ( is_tag() ) {    
		$title = single_tag_title( '', false );    
	} elseif ( is_author() ) {    
		$title = '<span class="vcard">' . get_the_author() . '</span>' ;    
	} elseif ( is_tax() ) { //for custom post types
		$title = sprintf( __( '%1$s' ), single_term_title( '', false ) );
	} elseif (is_post_type_archive()) {
		$title = post_type_archive_title( '', false );
	}
	return $title;    
});


/**
 * Remove slug from taxonomy archives
 */
/*
add_filter('request', 'mha_s2s_change_term_request', 1, 1 );
function mha_s2s_change_term_request($query){
 
	$tax_name = 'condition'; // specify you taxonomy name here, it can be also 'category' or 'post_tag'
 
	// Request for child terms differs, we should make an additional check
	if( $query['attachment'] ) :
		$include_children = true;
		$name = $query['attachment'];
	else:
		$include_children = false;
		$name = $query['name'];
	endif;
 
 
	$term = get_term_by('slug', $name, $tax_name); // get the current term to make sure it exists
 
	if (isset($name) && $term && !is_wp_error($term)): // check it here
 
		if( $include_children ) {
			unset($query['attachment']);
			$parent = $term->parent;
			while( $parent ) {
				$parent_term = get_term( $parent, $tax_name);
				$name = $parent_term->slug . '/' . $name;
				$parent = $parent_term->parent;
			}
		} else {
			unset($query['name']);
		}
 
		switch( $tax_name ):
			default:{
				$query[$tax_name] = $name; // for another taxonomies
				break;
			}
		endswitch;
 
	endif;
 
	return $query;
 
}
 
 
add_filter( 'term_link', 'mha_s2s_term_permalink', 10, 3 );
function mha_s2s_term_permalink( $url, $term, $taxonomy ){ 
	$taxonomy_name = 'condition'; // your taxonomy name here
	$taxonomy_slug = 'condition'; // the taxonomy slug can be different with the taxonomy name (like 'post_tag' and 'tag' )

	// exit the function if taxonomy slug is not in URL
	if ( strpos($url, $taxonomy_slug) === FALSE || $taxonomy != $taxonomy_name ) return $url;

	$url = str_replace('/' . $taxonomy_slug, '', $url);

	return $url;
}
*/