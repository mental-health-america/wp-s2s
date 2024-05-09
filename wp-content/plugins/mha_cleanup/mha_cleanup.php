<?php
/**
 * Plugin Name: MHA - Cleanup
 * Plugin URI: https://screening.mhanational.org
 * Version: 1.0
 * Author:  MHA Web Team
 * Author URI: https://screening.mhanational.org
 * Description: Data maintenance tools for MHA
 */

add_action( 'admin_menu', 'mha_cleanup_menu' );
function mha_cleanup_menu() {
	add_menu_page(
		'MHA Cleanup', 
		'MHA Cleanup', 
		'manage_options', 
		'mhacleanuppage', 
		'mhacleanuppage', 
		'dashicons-list-view', 
		26
	);

}


require_once('cleanup.php');

/** 
 * Init Scripts
 */
add_action('init', 'mhacleanupPageScripts');
function mhacleanupPageScripts() {
    if(current_user_can('manage_options')){
        wp_enqueue_style( 'process_mhaacfeuii', '/wp-content/plugins/acf-extended/assets/css/acfe-ui.min.css', array(), time() );
        wp_enqueue_style( 'process_mhacleanup', plugin_dir_url(__FILE__) . 'mha_cleanup.css', array(), time() );
    }
}


// Upload Page
function mhacleanuppage(){
?>
    
    <div id="poststuff" class="wrap">
    
        <h1>MHA Data Cleanup Tools</h1>

        <form id="mha-cleanup" action="#" method="POST">
            <div class="acf-columns-2">
            <div class="acf-column-1">
            
                <div id="mha-cleanup-error" class="error fade hidden"></div>

                <h2>Anonymous Data Removal</h2>
                <p>This tool will delete any anonymous (Screens with no User ID or User ID of #4) screening data for the selected date range.</p>
                
                <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="start_date">Start Date</label></th>
                        <td>
                            <input type="text" name="start_date" id="start_date" value="<?php echo date('Y-m', strtotime('now - 6 months')); ?>-01" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="end_date">End Date</label></th>
                        <td>
                            <input type="text" name="end_date" id="end_date" value="<?php echo date('Y-m-t', strtotime('now - 3 month')); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="end_date">Form IDs</label></th>
                        <td>
                            <input type="text" name="form_ids" id="form_ids" value="15,8,10,1,13,12,5,18,17,9,11,16,14,27" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">    
                            <p>
                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mhacleanupsnonce'); ?>" />
                                <button id="mha-start-clean-up" class="button button-primary button-large">Clean Up Data</button>
                                <button id="mha-cleanup-data-begin" type="submit" class="button button-secondary button-large hidden">Are you Sure?</button>
                            </p>
                            
                            <div id="cleanup-progress" style="display: none; margin-top: 20px;">
                                <div class="bar-wrapper"><div class="bar"></div></div>            
                                <strong class="label"><span class="label-number">0</span>%</strong>
                            </div>

                            <p id="cleanup-deleted-container" style="display: none;">
                                <span id="cleanup-deleted">0</span> Entries Removed
                            </p>
                            <p id="cleanup-status"></p>      
                            <br /><br />
                        </td>
                    </tr>
                </tbody>
                </table>
            </div>
            </div>
        </form>

        
        <form id="mha-usercleanup" action="#" method="POST">
            <div class="acf-columns-2">
            <div class="acf-column-1">
            
                <div id="mha-usercleanup-error" class="error fade hidden"></div>

                <h2>User Data Removal Tool</h2>
                <p>Enter the user's ID to delete that user and remove their data.</p>
                
                <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="user_data">User ID</label></th>
                        <td>
                            <input type="text" name="user_data" id="user_data" value="" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">    
                            <p>
                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('mhausercleanupsnonce'); ?>" />
                                <button id="mha-start-userclean-up-review" class="button button-primary button-large">Review User Data</button>

                                <button id="mha-start-userclean-up" class="button button-primary button-large hidden">Remove User Data</button>
                                <button id="mha-usercleanup-data-begin" type="submit" class="button button-secondary button-large hidden">Are you Sure?</button>
                            </p>
                            <div id="usercleanup-status" class="hidden"></div>      

                            <br /><br />
                        </td>
                    </tr>
                </tbody>
                </table>
            </div>
            </div>
        </form>

        <div id="cleanup-json-storage" style="display: none;"></div>
    
    </div>	
<?php } 
    