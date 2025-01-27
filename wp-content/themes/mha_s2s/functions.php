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
  * Approved partner list
  */
function mha_approved_partners() {
	$partners = array(
		'own',
		'easterseals'
	);
	return $partners;
}

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
		'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600;1,700&display=swap',
		'https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap'
	) 
);


/**
 * Enqueue scripts and styles.
 */
function mha_s2s_scripts() {

	// Load our main styles
	wp_enqueue_style( 'mha_s2s-style', get_stylesheet_uri() );
    wp_enqueue_style( 'mha_s2s-bootstrap-grid-css', get_template_directory_uri() . '/assets/bootstrap/css/bootstrap-grid.min.css', array(), '4.3.1.20220722' ); // Bootstrap grid only
	wp_enqueue_style( 'mha_s2s-main-style', get_template_directory_uri() . '/assets/css/main.css', array(), 'v20241204' );
	//wp_enqueue_style( 'mha_s2s-main-style', get_template_directory_uri() . '/assets/css/main.css', array(), time() );
	
	// Add print CSS.
	wp_enqueue_style( 'mha_s2s-print-style', get_template_directory_uri() . '/assets/css/print.css', null, 'v20240702', 'print' );
	//wp_enqueue_style( 'mha_s2s-print-style', get_template_directory_uri() . '/assets/css/print.css', null, time(), 'print' );
    
	// Scripts
	wp_enqueue_script( 'mha_s2s-skip-link-focus-fix', get_template_directory_uri() . '/assets/js/skip-link-focus-fix.js', array(), '1.0', true );
	wp_enqueue_script( 'mha_s2s-hover-intent', get_template_directory_uri() . '/assets/js/hoverIntent.js', array(), '0.7', true );
	wp_enqueue_script( 'mha_s2s-superfish', get_template_directory_uri() . '/assets/js/superfish.min.js', array(), '1.7.10.1', true );
	//wp_enqueue_script( 'mha_s2s-wow', get_template_directory_uri() . '/assets/js/aos.min.js', array(), '1.3', true );
	wp_enqueue_script( 'mha_s2s-macy', get_template_directory_uri() . '/assets/js/macy.min.js', array(), '1.0', true );
	wp_enqueue_script( 'mha_s2s-popper', get_template_directory_uri() . '/assets/js/popper.min.js', array(), '1.12.9', true );
	wp_enqueue_script( 'mha_s2s-bootstrap-js', get_template_directory_uri() . '/assets/bootstrap/js/bootstrap.bundle.min.js', array(), '4.3.1', true );
	wp_enqueue_script( 'mha_s2s-jqueryui', get_template_directory_uri() . '/assets/js/jquery.ui.custom.min.js', array( 'jquery' ), '1.13.1', true );
	wp_enqueue_script( 'mha_s2s-glide', get_template_directory_uri() . '/assets/js/glide.js', array(), 'v20241204', true );
	wp_enqueue_script( 'mha_s2s-aos', get_template_directory_uri() . '/assets/js/aos.min.js', array(), '3.0.0v2', true );
	wp_enqueue_script( 'mha_s2s-iframeresizer', get_template_directory_uri() . '/assets/js/iframe-resizer.min.js', array(), '4.3.2', true );
	
	if(get_page_template_slug() == 'templates/page-my-account.php'){
		wp_enqueue_script( 'mha_s2s-chart-js', get_template_directory_uri() . '/assets/js/chart.js', array(), '2.7.2', false );
	}

	wp_enqueue_script( 'mha_s2s-sticky', get_template_directory_uri() . '/assets/js/jquery.sticky-sidebar.min.js', array(), '1.1.2_ck.1', true );

	// Load the html5 shiv.
	wp_enqueue_script( 'html5', get_theme_file_uri( '/assets/js/html5.js' ), array(), '3.7.3' );
	wp_script_add_data( 'html5', 'conditional', 'lt IE 9' );

	// Global Javascript
	wp_enqueue_script( 'mha_s2s-global', get_theme_file_uri( '/assets/js/global.js' ), array( 'jquery' ), 'v20241204', true );
	//wp_enqueue_script( 'mha_s2s-global', get_theme_file_uri( '/assets/js/global.js' ), array( 'jquery' ), time(), true );
	
	// Partner Overrides
	$partner_var = get_query_var('partner');
	if(in_array($partner_var, mha_approved_partners() )){
		switch ($partner_var) {
			case 'own':
				$partner_css = get_template_directory_uri() . '/assets/css/partner/own.css';
				$partner_js = get_template_directory_uri() . '/assets/js/partner/own.js';
				break;
			case 'easterseals':
				$partner_css = get_template_directory_uri() . '/assets/css/partner/easterseals.css';
				$partner_js = get_template_directory_uri() . '/assets/js/partner/easterseals.js';
				break;
			default:
				$partner_css = null;
				$partner_js = null;
				break;
		}
		if($partner_css){
			wp_enqueue_style( 'mha_s2s-partner-style', $partner_css, array(), '1.0.20241031' );
		}
		if($partner_js){
			wp_enqueue_script( 'mha_s2s-partner-js', $partner_js, array(), '1.0.20241031', false );
		}
	}
	
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
            'classes' => 'button round'             
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
 * Custom Body Classes
 */
function wp_body_classes( $classes ) {

	// Iframe mode classes
	if(isset($_GET['iframe']) && $_GET['iframe'] == 'true'){
		$classes[] = 'iframe-mode';
	}      

	// Partner classes
	$partner_var = get_query_var('partner');
	if(isset($_GET['partner']) && in_array($partner_var, mha_approved_partners() )){
		$classes[] = 'partner-'.$partner_var;
	}     
	
	// Layout classes
	if(isset($_GET['layout'])){
		$layout_classes = get_layout_array( get_query_var('layout') );
		foreach($layout_classes as $l){
			$classes[] = 'layout-'.$l;
		}
	}      
    return $classes;
}
add_filter( 'body_class','wp_body_classes' );

/**
 * Remove Dashicons From Front End
 */
add_action( 'wp_print_styles', 'my_deregister_styles', 100 );
function my_deregister_styles()    { 
	if (!is_admin_bar_showing()){
		//wp_deregister_style( 'dashicons' ); 
	}
}


/**
 * Pre Populate Tokens
 */
// apply to all forms
add_filter( 'gform_field_input', 'hidden_token_field', 10, 5 );
function hidden_token_field( $input, $field, $value, $lead_id, $form_id ) {
	
	// Gererate custom unique ID (that's not the ID) for this submission
	// Replaced this with a pre submission handler mha_screening_pre_submission_handler() to avoid duplicates
	/*
	if ( $field->label == 'Token' ) {
		$input = '<input name="input_'.$field->id.'" id="input_'.$form_id.'_'.$field->id.'" type="hidden" class="gform_hidden" aria-invalid="false" value="'.wp_generate_uuid4().'">';
	}
	*/

	// Prepopulate with screen page's ID
	if ( $field->label == 'Screen ID' ) {
		$input = '<input name="input_'.$field->id.'" id="input_'.$form_id.'_'.$field->id.'" type="hidden" class="gform_hidden" aria-invalid="false" value="'.get_the_ID().'">';
	}
	
	// Legacy IP Identifier
	if ( $field->label == 'ipiden' ) {
		$input = '<input name="input_'.$field->id.'" id="input_'.$form_id.'_'.$field->id.'" type="hidden" class="gform_hidden" aria-invalid="false" value="'.get_ipiden().'">';
	}
	
    return $input;
}


/**
 * Number field validation
 */

// Force number fields to increment by 1
add_filter( 'gform_field_content', function ( $field_content, $field ) {	

	if ( 'number' === $field->type ) {		
		$field_content = str_replace( "step='any'", "step='1'  pattern='\d*' ", $field_content );
	}	
	if ( strpos($field->cssClass, 'number-stepper') !== false ) {        
		$field_content = str_replace('<input', '<div class="step-buttons"><button type="button" class="step-down" title="Increase by 1" aria-label="Increase by 1">-</button><button type="button" class="step-up" aria-label="Decrease by 1" title="Decrease by 1">+</button></div><input', $field_content);
	}

	// Un-encode HTML inside a field label
    if ( $field->label != strip_tags($field->label) ):
		
		//$pattern = '/(<legend[^>]*>)(.*?)(<\/legend>)/is';
		$pattern = '/(<(legend|label)[^>]*>)(.*?)(<\/\2>)/is';
		$field_content = preg_replace_callback($pattern, function($matches) {
			$decoded_content = html_entity_decode($matches[3]);
			return $matches[1] . $decoded_content . $matches[4];
		}, $field_content);
	endif;

	return $field_content;
}, 10, 2 );

// Round the number field with .rounded-number
add_filter( 'gform_field_validation', 'number_field_rounded_value_validation', 10, 4 );
function number_field_rounded_value_validation( $result, $value, $form, $field ) {
	if ( strpos($field->cssClass, 'rounded-number') !== false ) {
		if( is_numeric( $value ) && round( $value ) != $value ){
			$result['is_valid'] = false;
			$result['message'] = 'Please enter a whole number.';
		}
    }
    return $result;
}


/**
 * Hash the UID field if its an email address
 */
/*
add_filter( 'gform_pre_render', 'mha_screening_uid_hash' );
function mha_screening_uid_hash( $form ) {
    foreach( $form['fields'] as &$field )  {
		if ( strtolower($field->label) == 'uid' ) {
			if(strpos($_POST['input_'.$field->id], '@') !== false){
				$_POST['input_'.$field->id] = md5( $_POST[$input]);
			}
			continue;
		}
    }
    return $form;
}
*/


/**
 * Pre-fill start time of fields
 */
add_filter( 'gform_pre_render', 'mha_prefill_time_field' );
function mha_prefill_time_field( $form ) {

	// $current_page = GFFormDisplay::get_current_page( $form['id'] );
	// Only fills the first one
	foreach ( $form['fields'] as &$field ) {
		if( $field->defaultValue == '{datetime}' && $field->pageNumber == 1 ){
			$dateTime = new DateTime('now', new DateTimeZone('America/New_York'));
			$field->defaultValue = $dateTime->format('Y-m-d h:i:sa');
		}
	}
	
    return $form;
}


/**
 * Add iframe mode to screening results page
 */
add_filter( 'gform_confirmation', function ( $confirmation, $form, $entry ) {

	// A/B Testing Redirect
	if( isset($confirmation['redirect']) && str_contains( $confirmation['redirect'], '/screening-results/' ) ){

		$new_redirect_args = array(
			'current_pid'            => 27,
			'return_redirect'        => true,
			'current_url'           => $confirmation['redirect'],
			'current_referrer'      => $entry['source_url'],
		); 
		$new_redirect = mha_ab_redirects($new_redirect_args);

		if($new_redirect){
			$confirmation['redirect'] = $new_redirect;
		}

	}

	// Default behavior
    if ( ! is_array( $confirmation ) || empty( $confirmation['redirect'] ) ) {
        return $confirmation;
    }

	// Check for the iframe parameter and include it on results page
	if($confirmation['redirect'] && !empty($confirmation['redirect']) && isset($_GET['iframe']) && $_GET['iframe'] == 'true'){

		// Set up query args
		$query_args = [];
		$query_args['iframe'] = 'true'; // Pass iframe parameter to results page
		if(isset($_GET['partner'])){
			$query_args['partner'] = get_query_var('partner'); // Pass partner code to results page
		}

		$confirmation['redirect'] = add_query_arg( $query_args, $confirmation['redirect'] );
		
	}

    return $confirmation;

}, 11, 3 );


/**
 * Allowed additional query vars
 */
function mha_s2s_query_vars( $qvars ) {
    $qvars[] = 'sid'; // Test/Screen var
    $qvars[] = 'usid'; // Test/Screen var
    $qvars[] = 'search'; // Archive page search
    $qvars[] = 'search_tag'; // Archive page search
    $qvars[] = 'search_term'; // Archive page search
    $qvars[] = 'search_tax'; // Archive page search
    $qvars[] = 'ref'; // Archive referral links for breadcrumbs
    $qvars[] = 'login_error'; // User log in error
    $qvars[] = 'pathway'; // Articl reading paths
    $qvars[] = 'filter_order'; // Filter ordering pages
    $qvars[] = 'filter_orderby'; // Filter ordering pages
    $qvars[] = 'type'; // Admin column filter
    $qvars[] = 'geo'; // Zip search
    $qvars[] = 'action'; // Special my-account actions
    $qvars[] = 'redirect_to'; // Redirect for logins
    $qvars[] = 'paged'; // Pagination
    $qvars[] = 'iframe'; // Custom header/footer for iframe usage
    $qvars[] = 'updated'; // Custom validation for form submissions
    $qvars[] = 'partner'; // Approved partner code
    $qvars[] = 'admin_uid'; // User ID override for admins
    $qvars[] = 'layout'; // A/B testing override
    $qvars[] = 'internaltraffic'; // Staff exclusions for analytics
    $qvars[] = 'first_login'; // New registered users
    $qvars[] = 'exclude_ids'; // URL exclusions for articles
    $qvars[] = 'include_ids'; // URL inclusions for articles
    $qvars[] = 'diy_continue'; // Flag for a continuing DIY tool submission from an embed
    $qvars[] = 'code'; // Used for Google SSO
    $qvars[] = 'fb_id'; // Used for Facebook SSO
    $qvars[] = 'sso'; // Used for successful SSO logins
    $qvars[] = 'state'; // Used for SSO logins; passed from Google to contain additional data

	// Resource filters
	$qvars[] = 'treatment';
	$qvars[] = 'tags';
	$qvars[] = 'condition';
	$qvars[] = 'language';
	//$qvars[] = 'type'; // Dupe
	$qvars[] = 'service_type';

    return $qvars;
}
add_filter( 'query_vars', 'mha_s2s_query_vars' );

function get_layout_array( $vars ){
	$arr = explode(',', get_query_var('layout'));
	return $arr;
}


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
	
	// Helpers
	$form_fields = isset($form['fields']) ? $form['fields'] : false;
	$form_classes = isset($form['cssClass']) ? $form['cssClass'] : '';

	// Get max pages
	$form_pages = [];
	/*
	foreach($form_fields as $ff){
		if(isset($ff['cssClass']) && str_contains($ff['cssClass'], 'page-label')){
			$form_pages[$ff['pageNumber']] = $ff['label'];
		}
	}
	*/
	
	foreach($form['pagination']['pages'] as $k => $v){
		$form_pages[ ($k + 1) ] = $v;
	}

    $layout = get_layout_array(get_query_var('layout')); // Used for A/B testing
	
	$last_progress_label = get_field('survey') ? 'Submit<br /> Survey' : 'Your<br />Results';

	if( in_array('show_progress', $layout) && !in_array('hide_progress', $layout) || !in_array('hide_progress', $layout) ){

		if(isset($form['cssClass']) && str_contains($form['cssClass'], 'full-pager')){

			// Custom progress bar (all pages)			
			$progress_bar = '<ol class="full-progress-bar clearfix step-'.$current_page.'-of-'.$page_count.'">';
			foreach($form_pages as $k => $v){
				$pager_class = '';
				if($current_page == $k){
					$pager_class = 'active';
				} elseif($current_page > $k) {
					$pager_class = 'filled';
				} else {
					$pager_class = 'empty';
				}
				$progress_bar .= '<li class="step-'.$k.' '.$pager_class.'"><span>'.$v.'</span></li>';
			}
			// $progress_bar .= '<li class="step-'.(count($form_pages) + 1).'"><span>'.$last_progress_label.'</span></li>';
			$progress_bar .= '</ol>';

			if($confirmation_message != ''){
				$progress_bar .= '<div class="form-confirmation-container">'.$confirmation_message.'</div>';
			}


		} else {

			// Test progress bar
			$progress_bar = '';
	
			if( in_array('side_progress', $layout) ){
				$progress_bar .= '<div class="progress-container sticky">';
			}
	
			if(get_field('espanol')){
				$progress_bar .= '<ol class="screen-progress-bar clearfix step-'.$current_page.'-of-'.$page_count.'">
					<li class="step-1"><span>Preguntas<br />de la Prueba</span></li>
					<li class="step-2"><span>Preguntas<br />Opcionales</span></li>
					<li class="step-3"><span>Sus<br />Resultados</span></li>
				</ol>';
			} else {
				$demo_label = in_array('alt_demo_label', $layout) ? 'Optional<br />Questions' : 'Optional<br />Questions';
				$progress_bar .= '<ol class="screen-progress-bar clearfix step-'.$current_page.'-of-'.$page_count.'">
					<li class="step-1"><span>Test<br />Questions</span></li>
					<li class="step-2"><span>'.$demo_label.'</span></li>
					<li class="step-3"><span>'.$last_progress_label.'</span></li>
				</ol>';
			}
	
			if( in_array('side_progress', $layout) ){
				$progress_bar .= '</div>';
			}

		}

	} else {

		$progress_bar = '';

	}

	/**
	 * Get video titles from global options to populate hidden fields for specific forms 
	 */
	if(isset($form['cssClass']) && str_contains($form['cssClass'], 'get-video-titles')){
		$video_titles = get_field('video_title_references','options');
		if($video_titles){
			$progress_bar .= '<textarea style="display: none; visibility: hidden;" id="mha-global-video-titles">'.wp_json_encode( $video_titles ).'</textarea>';
		}
	}

    return $progress_bar;
}

/**
 * Gravity Forms <form> tag overrides
 */
add_filter( 'gform_form_tag', function ( $form_tag, $form ) {

	// Add clearfix to form tags
	$form_tag = preg_replace( "|action='|", "class='clearfix' action='", $form_tag );

	// Don't encode commas in the URL
	// $form_tag = str_replace( '%2C', ',', $form_tag);

	return $form_tag;
}, 10, 2 );

/**
 * Export labels instead of values for excel exports
 */

// GF excel exports
add_filter('gfexcel_export_field_value_checkbox', function ($gform_value, $form_id, $input_id, $entry) {
    $field = \GFAPI::get_field($form_id, $input_id);
    return $field->get_value_export($entry, $input_id, true);
}, 10, 4);
add_filter('gfexcel_export_field_value_radio', function ($gform_value, $form_id, $input_id, $entry) {
    $field = \GFAPI::get_field($form_id, $input_id);
    return $field->get_value_export($entry, $input_id, true);
}, 10, 4);

// Normal GF exports
add_filter( 'gform_export_field_value', 'export_choice_text', 10, 4 );
function export_choice_text( $value, $form_id, $field_id, $entry ) {
    $field = GFAPI::get_field( $form_id, $field_id );
    return is_object( $field ) && is_array( $field->choices ) ? $field->get_value_export( $entry, $field_id, true ) : $value;
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
	if(isset($_SERVER['HTTP_REFERER'])){
		$referrer = $_SERVER['HTTP_REFERER'];    
		// if there's a valid referrer, and it's not the default log-in screen
		if( !empty( $referrer ) && !strstr( $referrer,'wp-login' ) && !strstr( $referrer,'wp-admin' ) ) {
			// Custom Redirect/Args
			$query_args = array( 'login_error' => 'true' );

			// Custom Referral Check
			$ref_query = parse_url($referrer, PHP_URL_QUERY);
			parse_str($ref_query, $ref_query_params);
			if(isset($ref_query_params['redirect_to']) && $ref_query_params['redirect_to'] != ''){
				$query_args['redirect_to'] = $ref_query_params['redirect_to'];
			}

			// Set our URL parameters
			$custom_redirect = add_query_arg( $query_args, get_permalink( 566 ) );
			wp_redirect( $custom_redirect ); 
			exit;
		}
	}
}

/**
 * Function Name: check_username_password.
 * Description: This redirects to the custom login page if user name or password is   empty with a modified url
**/
add_action( 'authenticate', 'check_username_password', 1, 3);
function check_username_password( $login, $username, $password ) {
	// Getting URL of the login page
	if(isset($_SERVER['HTTP_REFERER'])){
		$referrer = $_SERVER['HTTP_REFERER'];
	} else {
		$referrer = '';
	}

	// if there's a valid referrer, and it's not the default log-in screen
	if( !empty( $referrer ) && !strstr( $referrer,'wp-login' ) && !strstr( $referrer,'wp-admin' ) ) { 
		if( $username == "" || $password == "" ){
			// Custom Redirect/Args
			$query_args = array( 'login_error' => 'true' );
	
			// Custom Referral Check
			$ref_query = parse_url($referrer, PHP_URL_QUERY);
			parse_str($ref_query, $ref_query_params);
			if(isset($ref_query_params['redirect_to']) && $ref_query_params['redirect_to'] != ''){
				$query_args['redirect_to'] = $ref_query_params['redirect_to'];
			}
	
			// Set our URL parameters
			$custom_redirect = add_query_arg( $query_args, get_permalink( 566 ) );
			wp_redirect( $custom_redirect ); 
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
 * Search Post Type Filter
 */
function wpse331647_alter_query($query) {
	
	// Search result overrides
    if ($query->is_search && !is_admin() ) {

		// Show only specific post types
        $query->set('post_type',array('page','article','screen','thought_activity', 'diy'));
		
		// Exclude specific pages
		$exclude_ids = get_field('exclude_from_search', 'options');
		if($exclude_ids){
			$query->set('post__not_in', $exclude_ids);
		}

	}
	
	if ( !is_admin() && $query->is_main_query() ) {
		if(get_query_var('filter_order')){
			$query->set( 'order', get_query_var('filter_order') );
		}
		if(get_query_var('filter_orderby')){
			$query->set( 'orderby', get_query_var('filter_orderby') );
		}
	}

	return $query;
}

add_action( 'pre_get_posts', 'wpse331647_alter_query' ); 


/**
 * Custom Admin Columns
 */
// Column Set
add_filter( 'manage_article_posts_columns', 'mha_s2s_filter_posts_columns' );
function mha_s2s_filter_posts_columns( $columns ) {
	$columns['tags'] = __( 'Tags' );
	$columns['type'] = __( 'Type' );
	return $columns;
}

// Column Content
add_action( 'manage_article_posts_custom_column', 'mha_s2s_article_column', 10, 2);
function mha_s2s_article_column( $column, $post_id ) {
	// Article Type
	if ( 'type' === $column ) {
		$types = get_field('type', $post_id);
		if($types){
			$type_array = [];
			foreach($types as $type){
				$type_array[] = str_replace('Diy','DIY', ucfirst($type));
			}
			echo implode(', ', $type_array);
		}
	}
	
}

// Column orderby
add_filter( 'manage_edit-article_sortable_columns', 'mha_s2s_article_sortable_columns');
function mha_s2s_article_sortable_columns( $columns ) {
	$columns['type'] = 'type';
	$columns['tags'] = 'tags';
	return $columns;
}

add_action( 'pre_get_posts', 'mha_s2s_posts_orderby' );
function mha_s2s_posts_orderby( $query ) {
	if( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( 'type' === $query->get( 'orderby') ) {
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'meta_key', 'type' );
	}
}


/**
 * Include custom post types in tag pages
 */
function wpa82763_custom_type_in_categories( $query ) {
    if ( $query->is_main_query() && ( $query->is_category() || $query->is_tag() ) ) {
        $query->set( 'post_type', array( 'page','article','screen','thought_activity' ) );
    }
}
add_action( 'pre_get_posts', 'wpa82763_custom_type_in_categories' );


/**
 * Filter condition and tag pages to only the proper articles
 */
function archive_meta_query( $query ) {
	
	// For only archive pages
    if ( !is_admin() && $query->is_main_query() && $query->is_archive() ){

		// Limit articles displayed by specific types
		if($query->is_tax('condition') || $query->is_tag()){

			/*
			$query->query_vars["meta_key"] = 'type';
			$query->query_vars["meta_value"] = array('condition','diy','connect','treatment');
			*/
			$query->set( 'posts_per_page', '-1' ); 

			$meta_query = array('relation' => 'AND');
		
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key' => 'type',
					'value' => 'condition',
					'compare' => 'LIKE'
				),
				array(
					'key' => 'type',
					'value' => 'treatment',
					'compare' => 'LIKE'
				),
				array(
					'key' => 'type',
					'value' => 'connect',
					'compare' => 'LIKE'
				),
				array(
					'key' => 'type',
					'value' => 'diy',
					'compare' => 'LIKE'
				)
			);
			
			$meta_query[] = array(
				'relation' => 'OR',
				array(
					'key' => 'espanol',
					'value' => '1',
					'compare' => '!='
				),
				array(
					'key' => 'espanol',
					'value' => '1',
					'compare' => 'NOT EXISTS'
				)
			);
			$query->set('meta_query',$meta_query);

			/*
			$query->set('orderby', array(
				'all_conditions' => 'ASC', 
			));
			*/

			// All Condition inclusion override
			/*
			$og_term = get_queried_object();
			$taxquery = array(
				'relation' => 'OR',
				array(
					'taxonomy' => $og_term->taxonomy,
					'field' => 'id',
					'terms' => $og_term->terM_id
				),
				array(
					'taxonomy' => 'condition',
					'field' => 'id',
					'terms' => 119
				)
			);		
			$query->set( 'tax_query', $taxquery );
			*/

		}
	
		// Filter - Keyword
		if(get_query_var('search')){
			$query->query_vars["s"] = get_query_var('search');
		}
	
		// Anchor tags to search for condition pages
		if(get_query_var('search_tax') && get_query_var('search_term')){
			$taxquery = array(
				array(
					'taxonomy' => 'post_tag',
					'field' => 'id',
					'terms' => get_query_var('search_term')
				)
			);		
			$query->query_vars["tax_query"] = $taxquery;			
		}
		
	}
}
add_action( 'pre_get_posts', 'archive_meta_query', 1 );

/**
 * Hide toolbar for non-admins
 */
add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
	if (!current_user_can('edit_posts') && !is_admin()) {
		show_admin_bar(false);
	}
}

/**
 * Auto login after registration.
 */
add_action( 'gform_user_registered', 'wpc_gravity_registration_autologin',  10, 4 );
function wpc_gravity_registration_autologin( $user_id, $feed, $entry, $user_pass ) {
	
	$source = $entry['source_url'];
	GFCommon::log_debug( __METHOD__ .'Source URL: '.$source );

	wp_set_auth_cookie( $user_id, false, is_ssl() );

}

/**
 * Gravity Forms
 * Adjust form edit screen columns
 */
add_filter( 'gform_form_list_columns', 'change_columns', 10, 1 );
function change_columns( $columns ){
    $columns = array(
        'is_active'  => '',
        'title'      => esc_html__( 'Form Title', 'gravityforms' ),
        'id'         => esc_html__( 'ID', 'gravityforms' ),
    );
    return $columns;
}


/**
 * Gravity Wiz // Gravity Forms // Export Multi-input Fields in a Single Column
 *
 * By default, Gravity Forms only allows you to export each input of a multi-input field (e.g. Checkbox field,
 * Name Field, etc) as a separate column. This snippet allows you to export all inputs (of a specific field) in a
 * single column.
 *
 * @version   1.2
 * @author    David Smith <david@gravitywiz.com>
 * @license   GPL-2.0+
 * @link      http://gravitywiz.com/how-do-i-export-multi-input-fields-in-a-single-column-with-gravity-forms/
 * @copyright 2015 Gravity Wiz
 *
 * Plugin Name: Gravity Forms - Export Multi-input Fields in Single Column
 * Plugin URI: http://gravitywiz.com/how-do-i-export-multi-input-fields-in-a-single-column-with-gravity-forms/
 * Description: Export multi-input Gravity Forms fields as a single column.
 * Author: David Smith
 * Version: 1.2
 * Author URI: http://gravitywiz.com
 */
add_filter( 'gform_export_fields', function( $form ) {

	// only modify the form object when the form is loaded for field selection; not when actually exporting
	if ( rgpost( 'export_lead' ) || rgpost( 'action' ) == 'gf_process_export' ) {
		return $form;
	}

	$fields = array();

	foreach( $form['fields'] as $field ) {
		if( is_a( $field, 'GF_Field' ) && is_array( $field->inputs ) ) {
			$orig_field = clone $field;
			$field->inputs = null;
			$fields[] = $field;
			$fields[] = $orig_field;
		} else {
			$fields[] = $field;
		}
	}

	$form['fields'] = $fields;

	return $form;
} );


/**
 * Default Pathway Override
 * Only show Reading Paths that this post is a part of
 */
/*
add_filter('acf/fields/post_object/result/name=default_pathway', 'my_acf_fields_post_object_result', 10, 4);
function my_acf_fields_post_object_result( $text, $post, $field, $post_id ) {

	$paths = get_field('path', $post->ID);
	if( $paths ) {
		$counter = 0;
		foreach($paths as $path){
			if($path['article'] == $post_id){
				$counter++;
			}
		}		
		if($counter == 0){
			return false;
		}
	}

    return $text;
}
*/

/**
 * Updated query for repeater fields
 */
add_filter('acf/fields/post_object/query/name=default_pathway', 'my_acf_fields_post_object_query', 10, 3);
function my_acf_fields_post_object_query( $args, $field, $post_id ) {
	$meta_query = array();
    $meta_query[] = array(
		'key' 		=> 'path_$_article',
		'value' 	=> $post_id,
		'compare'	=> '=',
	);

    $args['meta_query'] = $meta_query;

    return $args;
} 
function path_article_where( $where ) {	
	$where = str_replace("meta_key = 'path_$", "meta_key LIKE 'path_%", $where);
	return $where;
}
add_filter('posts_where', 'path_article_where');


/**
 * Redirect subscribers to MY Account  
 */
add_action( 'load-profile.php', function() {
    if(!current_user_can('manage_options')){
        exit( wp_safe_redirect('/my-account?cb='.date('U')) );
	}
});


/**
 * Custom HTML Lang Attribute Overrides
 */
function mha_language_attributes($lang){
	if (get_field('espanol')) {
		return "lang=\"es-US\"";
    }
    return $lang;
}
add_filter('language_attributes', 'mha_language_attributes');



/**
 * Modify fields before printing on the submit form
 */

// Change the Content field label
function mha_submit_resource_content( $field ) {	
	if ( wp_doing_ajax() || is_page_template('templates/page-submit-article.php') ) { 
		$field['label'] = "Describe your resource in 150 words or less."; 
	}    
	if ( $field ) { return $field; } else { exit; } 
}
add_filter('acf/prepare_field/name=_post_content', 'mha_submit_resource_content');


// Change the featured image label
function mha_submit_resource_featured_image( $field ) {	
	if ( wp_doing_ajax() || is_page_template('templates/page-submit-article.php') ) { 
		$field['label'] = "Featured Image (logo or thumbnail)</label><p class=\"description\">Please provide a high-quality, high-resolution image.</p>"; 
	}    
	if ( $field ) { return $field; } else { exit; } 
}
add_filter('acf/prepare_field/name=featured_image', 'mha_submit_resource_featured_image');


// All Conditions headings
function mha_submit_resource_all_conditions( $field ) {	
	if ( wp_doing_ajax() || is_page_template('templates/page-submit-article.php') ) { 
		echo '<div class="acf-field pb-0"><strong class="large">Related Mental Health Conditions</strong></div>';
		$field['instructions'] = "Think about the mental health conditions that your resource can help with. <strong>Check this box if your resource is helpful for all common mental health conditions</strong>."; 
	}    
	if ( $field ) { return $field; } else { exit; } 
}
add_filter('acf/prepare_field/name=all_conditions', 'mha_submit_resource_all_conditions');

// Condition headings
function mha_submit_resource_conditions( $field ) {	
	if ( wp_doing_ajax() || is_page_template('templates/page-submit-article.php') ) { 
		$field['label'] = "Specializations"; 
		$field['instructions'] = "Which of the following mental health conditions does your resource <strong>specialize</strong> in?<br /><em>Please note: if your resource is not appropriate for all of the common conditions listed below, please uncheck the \"All Conditions\" box above.</em>"; 
	}    
	if ( $field ) { return $field; } else { exit; } 
}
add_filter('acf/prepare_field/name=related_condition', 'mha_submit_resource_conditions');

// Remove Condition and Treatment from the Type options
function mha_submit_resource_type( $field ) {
	if ( wp_doing_ajax() || is_page_template('templates/page-submit-article.php') ) {
		unset($field['choices']['condition']);
		unset($field['choices']['treatment']);		
	}    
	if ( $field ) { return $field; } else { exit; } 
}
add_filter('acf/prepare_field/name=type', 'mha_submit_resource_type');

// Update the excerpt on article saves
add_action('acf/save_post', 'mha_save_resource_article');
function mha_save_resource_article( $post_id ) {
	
	/** Articles */
	if(get_post_type($post_id) == 'article'){
		
		// Update excerpt from user's submitted tagline if the excerpt is empty
		if(get_field('tagline', $post_id) && !has_excerpt($post_id)){
			$the_post = array(
				'ID'           => $post_id,
				'post_excerpt' => get_field('tagline', $post_id),
			);
			wp_update_post( $the_post );
		}		
		
		// Send email notification
		if( !current_user_can('editor') && !current_user_can('administrator') ) {
			$to = 'screening@mhanational.org';
			$subject = 'MHA Screening Resource Submission';
			$body = '<p>The following resource was submitted for review:<br /><a href="https://screening.mhanational.org/wp-admin/post.php?post='.$post_id.'&action=edit">'.get_the_title($post_id).'</a>';
			$headers = array('Content-Type: text/html; charset=UTF-8');
			wp_mail( $to, $subject, $body, $headers );
		}

	}
}


/**
 * Custom Session Length override exemption for admins
 */
function loginpress_exclude_role_session_callback() {
	return array( 'administrator', 'editor', 'contributor' );
}
add_filter( 'loginpress_exclude_role_session', 'loginpress_exclude_role_session_callback' );


/**
 * Dashboard Widget Removals
 */
function mha_remove_dashboard_widgets() {
	remove_meta_box( 'rg_forms_dashboard', 'dashboard', 'side' );
}
add_action('wp_dashboard_setup', 'mha_remove_dashboard_widgets' );

/**
 * Relevanssi Override
 */
add_filter( 'relevanssi_excerpt_gap', function( $gap, $count_words, $excerpt_length ) {
	return floor( $count_words / 100 - $excerpt_length );
}, 10, 3 );

add_filter( 'relevanssi_post_content', 'rlv_pre_code', 10 );
add_filter( 'relevanssi_excerpt_content', 'rlv_pre_code', 10 );
function rlv_pre_code( $content ) {
	if( isset($content) && $content != null && $content != '' ){
		$content = preg_replace( '#<(.*) class=".*?references".*?</\1>#mis', '', $content );
		$content = preg_replace( '#<(.*) class=".*?noindex".*?</\1>#mis', '', $content );
		$content = preg_replace( '#<(.*) class=".*?layout-action".*?</\1>#mis', '', $content );
		$content = preg_replace( '#<(.*) id=".*?references".*?</\1>#mis', '', $content );
		$content = preg_replace( '#<pre.*?</pre>#mis', '', $content );
		$content = preg_replace( '#<code.*?</code>#mis', '', $content );
	}
	return $content;
}
add_filter( 'relevanssi_disable_shortcodes_excerpt', 'rlv_add_shortcode' );
function rlv_add_shortcode( $shortcodes ) {
	return array_merge( $shortcodes, array(  'gravityform', 'mha_diy', 'mha_popular_articles' ) );
}

add_filter( 'relevanssi_excerpt', 'rlv_modify_excerpt', 10, 2 );
function rlv_modify_excerpt( $excerpt, $post_id ) {	
	$excerpt = get_post_type($post_id) == 'screen' ? get_the_excerpt( $post_id ) : $excerpt;
	return $excerpt;
}


/**
 * Remove javascript from submit button so we can manually submit it on radio button changes
 */
/*
add_filter( 'gform_submit_button', 'mha_autosubmit_submit_onclick', 10, 2 );
function mha_autosubmit_submit_onclick( $button, $form ) {
	// Only if form has .auto-submit class
	if (strpos($form['cssClass'], 'auto-submit') !== false) {
		return "<button class='button gform_button' id='gform_submit_button_{$form['id']}'><span>Submit</span></button>";
	}	
	return $button;
}
*/

/**
 * Remove the anchor jump after submit
 */
add_filter( 'gform_confirmation_anchor', '__return_false' );
add_filter( 'gform_confirmation_anchor_56', '__return_true' );

/**
 * Disable spam check on auto-submit forms
 */
/*
add_filter( 'gform_entry_is_spam', 'it_is_all_spam', 11, 3 );
function it_is_all_spam( $is_spam, $form, $entry ) {
	GFCommon::log_debug( 'SPAM CHECKER: $is_spam => ' . var_export( $is_spam, 1 ) );
	GFCommon::log_debug( 'SPAM CHECKER ENTRY: $entry => ' . var_export( $entry, 1 ) );
	return $is_spam;
}
*/
add_filter( 'gform_entry_is_spam', 'gf_admin_is_not_spam', 10, 3 );
function gf_admin_is_not_spam( $is_spam, $form, $entry ) {
	if ( strpos($form['cssClass'], 'auto-submit') !== false ) {
        //$is_spam = false;
	}
    return $is_spam;
}

/**
 * Disable custom field aggregation to help with Gravity Forms Performance
 */
add_filter( 'gform_disable_custom_field_names_query', 'disable_gf_fieldnames_query', 10, 1 );
function disable_gf_fieldnames_query( $disable_query ){
	return true;
}

// FacetWP Options
include_once('inc/functions_facetwp.php');


/**
 * Set admin user logins to expire every "next" saturday
 */
function set_admin_cookie_expiration($expiration, $user_id, $remember) {
    if (user_can($user_id, 'administrator')) {
        // Calculate the timestamp for 2 Saturdays from now
        $next_saturday = strtotime('next Saturday', strtotime('+1 week'));
        
        // Set the expiration to 2 Saturdays from now
        $expiration = $next_saturday - time();
    }

    return $expiration;
}
add_filter('auth_cookie_expiration', 'set_admin_cookie_expiration', 10, 3);
