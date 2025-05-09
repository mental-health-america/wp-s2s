<?php
/*
* Plugin Name: MHA Iframe Controls
* Plugin URI: https://screening.mhanational.org
* Description: Create an allow list for iframes
* Version: 1.0.2
* Author: MHA
* Author URI: https://screening.mhanational.org
*/

function ckfa_settings_page(){
	add_submenu_page(
		'options-general.php',			// top level menu page
		'iFrame Allow List',			// title of the settings page
		'iFrame Allow List',			// title of the submenu
		'manage_options',				// capability of the user to see this page
		'ckfa-settings-page',			// slug of the settings page
		'ckfa_settings_page_html'			// callback function when rendering the page
	);
}
add_action('admin_menu', 'ckfa_settings_page');



function ckfa_settings_page_html(){
	if(!current_user_can('manage_options')){
		return;
	}
	?>

	<div class="wrap">
		<h1>iFrame Allow List</h1>
		<p>This area is for giving other sites the ability to show this site embedded on another site through an iFrame</p>
		<label for="ckfa-level">Choose your level:</label>
		<br>
		<form name="ckfa-form" method="POST" action="<?php echo esc_url(admin_url('admin-post.php'));?>" >
			<div>
				<select name="allowlevel" id="allowlevel">
					<option value="allowall" <?=get_option('ckfa_allowlevel') == 'allowall' ? ' selected="selected"' : '';?>>Allow ALL SITES to embed this site</option>
					<option value="allownone" <?=get_option('ckfa_allowlevel') == 'allownone' ? ' selected="selected"' : '';?>>Allow NO SITES to embed this site</option>
					<option value="allowsome" <?=get_option('ckfa_allowlevel') == 'allowsome' ? ' selected="selected"' : '';?>>Allow LIMITED SITES to embed this site</option>
				</select>
			</div>
			<br><br>
			<div id="ckfa-url-input-con" <?php if(get_option('ckfa_allowlevel') != 'allowsome'){?> style="display:none" <?php } ?>>
				<div>
					<strong>Currently Allowed Sites:</strong><br>
					<?php if(get_option('ckfa_allowlist') && count(get_option('ckfa_allowlist')) >0){ 
						$sites = get_option('ckfa_allowlist');
						for($x=0; $x<count($sites); $x++){
							echo '<input type="checkbox" class="ckfaSite" id="site'.$x.'" name="ckfaSite[]" value="'.$sites[$x].'" checked><label for="site'.$x.'"> '.$sites[$x].'</label><br>';
						}
					}else{
						echo 'None.';
					}?>	
				</div>
				<br><br>
				<div>
					<label for="ckfa-url-input">Add a url to the allow list for iFrames:</label>
					<input type="url" id="ckfa-url-input" name="ckfa-url-input">
				</div>
			</div>
			<input type="hidden" name="action" value="update_ckfa_options">
			<input type='submit' id="ckfaupdate" class='button-primary' name='ckfaupdate' value='Save Changes' disabled />
		</form>
	</div>

<?php }


function ckfa_enqueue_scripts(){   
	wp_enqueue_script('mha_iframe_control-js', plugin_dir_url( __FILE__ ).'mha_iframe_control.js', array('jquery'), '1.0.1' );    
	wp_localize_script('mha_iframe_control-js', 'ckfa_ajax_url', array(admin_url('admin-ajax.php')));
}
add_action('admin_enqueue_scripts', 'ckfa_enqueue_scripts');

add_action('admin_post_update_ckfa_options','ckfa_update_ckfa_options');
function ckfa_update_ckfa_options(){
	update_option('ckfa_allowlevel', $_POST['allowlevel']);

	// var_dump($_POST); die();

	if($_POST['allowlevel'] == 'allowall'){
		//remove all rules
		delete_option('ckfa_allowlist');
	}elseif($_POST['allowlevel'] == 'allownone'){
		//hard protect
		delete_option('ckfa_allowlist');
	}else{
		$urlList = Array();
		if(isset($_POST['ckfaSite']) && !is_null($_POST['ckfaSite'])){
			foreach($_POST['ckfaSite'] as $site){
				array_push($urlList, $site);
			}
		}
		if(isset($_POST['ckfa-url-input']) && $_POST['ckfa-url-input'] != ''){
			array_push($urlList, $_POST['ckfa-url-input']);
		}
		update_option('ckfa_allowlist', $urlList);
	}
	wp_redirect( $_SERVER['HTTP_REFERER'] );
	exit();
}

/**
 * iframe allowlist
 */
function ckfa_add_csp_header(){
	if(get_option('ckfa_allowlevel')){
		if(get_option('ckfa_allowlevel') != 'allowall'){
			$csp_fa = "frame-ancestors 'self'"; //set to self until there are actual sites in the list OR we are set to allownone
			if(get_option('ckfa_allowlevel') == 'allowsome'){
				
				// Plugin whitelisted domains
				if(get_option('ckfa_allowlist')){
					$csp_fa = "frame-ancestors ";
					foreach(get_option('ckfa_allowlist') as $url){
						// $csp_fa .= $url.' ';
						$theseDomains = getWildcardSubdomains($url);
						$csp_fa .= $theseDomains[0].' '.$theseDomains[1].' ';
					}
				}

				// Whitelisted partners
				$current_date = date('Ymd');
				$partner_args = [
					'post_type'      => 'partners',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
				];
				$partner_ids = get_posts($partner_args);
				foreach ($partner_ids as $partner_id) {
					$partner_information = get_field('partner_information', $partner_id);
					$partner_expiration_date = $partner_information['end_date_iframe'] ?? ''; // Check if expiration is valid
					if( empty($partner_expiration_date) || $partner_expiration_date > $current_date ){
						if( !empty($partner_information['partner_domain']) ) {
							$theseDomains = getWildcardSubdomains($partner_information['partner_domain']);
							$csp_fa .= $theseDomains[0].' '.$theseDomains[1].' ';
						}
					}
				}

			}

			header("Content-Security-Policy: $csp_fa");

		}
	}
}
add_action('send_headers', 'ckfa_add_csp_header');


// helper
function getWildcardSubdomains($url) {
	// Remove https:// or http:// from the URL, if present
	$url = preg_replace('#^https?://#', '', $url);

	// Split the URL into domain and path
	$parts = explode('/', $url);
	$domain = $parts[0];
	$path = implode('/', array_slice($parts, 1));

	// Add the HTTP and HTTPS prefixes with wildcard subdomains
	$httpUrl = "http://*.$domain/$path";
	$httpsUrl = "https://*.$domain/$path";

	return array($httpUrl, $httpsUrl);
}