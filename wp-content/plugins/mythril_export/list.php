<?php
// List Page
function mythrilexport(){
    // Load Scripts
	//wp_enqueue_script( 'mythrilexport-scripts', plugin_dir_url(__FILE__) . 'mythril_export.js', array('jquery'), '1.0', true );
    
?>

<div class="wrap">
    <h1>Signup Export</h1>		
    <p>Select the export you would like to download a CSV for.</p>

	<?php $snonce = wp_create_nonce('mythrilexport'); ?>
	<p><a class="button" target="_blank" href="<?php echo admin_url( 'admin-post.php?action=mythrilexport_export&&snonce='.$snonce ); ?>">Export Signups</a></p>

</div>	
<?php } ?>