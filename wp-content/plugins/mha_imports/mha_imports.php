<?php
/**
 * Plugin Name: MHA - Imports
 * Plugin URI: https://screening.mhanational.org
 * Version: 1.0
 * Author:  MHA Web Team
 * Author URI: https://screening.mhanational.org
 * Description: Import tools for MHA
 */

add_action( 'admin_menu', 'mha_import_menu' );
function mha_import_menu() {
	add_menu_page(
		'MHA Importer', 
		'MHA Importer', 
		'manage_options', 
		'mhaimporterpage', 
		'mhaimporterpage', 
		'dashicons-list-view', 
		26
	);

}


require_once('importer.php');

/** 
 * Init Scripts
 */
add_action('init', 'mhaImportPageScripts');
function mhaImportPageScripts() {
    if(current_user_can('manage_options')){
        wp_enqueue_style( 'process_mhaacfeuii', '/wp-content/plugins/acf-extended/assets/css/acfe-ui.min.css', array(), time() );
        wp_enqueue_style( 'process_mhaImports', plugin_dir_url(__FILE__) . 'mha_imports.css', array(), time() );
    }
}


// Upload Page
function mhaimporterpage(){
    ?>
    
    <div id="poststuff" class="wrap">
    
        <h1>MHA Import Tools</h1>

        <?php /*
        <form id="mha-provider-import" action="#" method="POST" enctype="multipart/form-data">
            <div class="acf-columns-2">
            <div class="acf-column-1">
            
                <div id="provider-import-error"></div>
                <h2>Provider Importer</h2>
                <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="import_provider_file">CSV File</label></th>
                        <td>
                            <input type="file" name="import_provider_file" id="import_provider_file" required />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">    
                            <p>
                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mhaimportsnonce'); ?>" />
                                <input type="submit" class="button button-primary" id="mha-provider-import-submit"  value="Import CSV to Providers">
                            </p>
                            
                            <div id="provider-imports-progress" style="display: none; margin-top: 20px;">
                                <div class="bar-wrapper"><div class="bar"></div></div>            
                                <strong class="label"><span class="label-number">0</span>%</strong>
                            </div>
                            <p id="provider-imports-status"></p>      
                            <br /><br />
                        </td>
                    </tr>
                </tbody>
                </table>
            </div>
            </div>
        </form>
        */ ?>


    <form id="mha-cta-code-importer" action="#" method="POST">
        <div class="acf-columns-2">
        <div class="acf-column-1">
        
            <div id="mha-cta-code-importer-error"></div>
            <h2>Unique CTA Code Importer</h2>
            <p>This tool is designed to upload a CSV of unique codes in a single column with the header "code" to the 'cta_codes' table to be used with the 'mha_recruit_link' shortcode.</p>
            <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="cta_codes_file">CSV File</label></th>
                    <td>                        
                        <input type="file" name="cta_codes_file" id="cta_codes_file" required />
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <p>
                            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mhactacodeimporter'); ?>" />
                            <input type="submit" class="button button-primary" id="cta_code_importer"  value="Upload Codes">
                        </p>                        
                        <div id="cta-code-imports-progress" class="mha-progress-bar" style="display: none; margin-top: 20px;">
                            <div class="bar-wrapper"><div class="bar"></div></div>            
                            <strong class="label"><span class="label-number">0</span>%</strong>
                        </div>
                        <p id="cta-code-imports-status" class="mha-progress-status"></p>      
                        <br /><br />
                    </td>
                </tr>
            </tbody>
            </table>
        </div>
        </div>
    </form>
    <br />
    
    </div>	
<?php } 
    