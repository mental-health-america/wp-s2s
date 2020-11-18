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
/*
function mha_s2s_query_vars( $qvars ) {
    $qvars[] = 'source';
    $qvars[] = 'path';
    $qvars[] = 'step';
    return $qvars;
}
add_filter( 'query_vars', 'mha_s2s_query_vars' );
*/


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