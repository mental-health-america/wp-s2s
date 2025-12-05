<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

global $wpdb;

if ( isset( $_POST['generate_api_key'] ) && check_admin_referer( 'generate_api_key_nonce' ) ) {
	$name     = sanitize_text_field( $_POST['api_key_name'] );
	$raw_key  = wp_generate_password( 32, false );
	$hash_key = password_hash( $raw_key, PASSWORD_BCRYPT );

	// Store the hashed key in the database.
	$wpdb->insert(
		$wpdb->options,
		array(
			'option_name'  => '_api_key_' . sanitize_title( $name ),
			'option_value' => $hash_key,
			'autoload'     => 'no',
		)
	);

	$generated_key = $raw_key; // Show raw key once.
}

if ( isset( $_POST['delete_api_key'] ) && check_admin_referer( 'delete_api_key_nonce' ) ) {
	$key_name = sanitize_text_field( $_POST['api_key_name'] );
	$wpdb->delete( $wpdb->options, array( 'option_name' => '_api_key_' . sanitize_title( $key_name ) ) );
}

$api_keys = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE '_api_key_%'", ARRAY_A );
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Manage API Keys', 'rest-api-key-authentication' ); ?></h1>

	<form method="post">
		<?php wp_nonce_field( 'generate_api_key_nonce' ); ?>
		<table class="form-table">
			<tr>
				<th><label for="api_key_name"><?php esc_html_e( 'API Key Name', 'rest-api-key-authentication' ); ?></label></th>
				<td><input type="text" name="api_key_name" id="api_key_name" required></td>
			</tr>
		</table>
		<?php submit_button( __( 'Generate API Key', 'rest-api-key-authentication' ), 'primary', 'generate_api_key' ); ?>
	</form>

	<?php if ( ! empty( $generated_key ) ) : ?>
		<div id="api-key-popup" class="api-key-popup">
			<div class="api-key-popup-content">
				<h2><?php esc_html_e( 'Your API Key', 'rest-api-key-authentication' ); ?></h2>
				<p><?php esc_html_e( 'Copy this API key. It will not be shown again.', 'rest-api-key-authentication' ); ?></p>
				<pre id="generated-api-key"><?php echo esc_html( $generated_key ); ?></pre>
				<button id="copy-api-key" class="button button-primary"><?php esc_html_e( 'Copy to Clipboard', 'rest-api-key-authentication' ); ?></button>
				<button id="close-popup" class="button"><?php esc_html_e( 'Close', 'rest-api-key-authentication' ); ?></button>
			</div>
		</div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Existing API Keys', 'rest-api-key-authentication' ); ?></h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'rest-api-key-authentication' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'rest-api-key-authentication' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $api_keys as $key ) : ?>
				<tr>
					<td><?php echo esc_html( str_replace( '_api_key_', '', $key['option_name'] ) ); ?></td>
					<td>
						<form method="post" style="display:inline;">
							<?php wp_nonce_field( 'delete_api_key_nonce' ); ?>
							<input type="hidden" name="api_key_name" value="<?php echo esc_attr( str_replace( '_api_key_', '', $key['option_name'] ) ); ?>">
							<?php submit_button( __( 'Delete', 'rest-api-key-authentication' ), 'delete', 'delete_api_key', false ); ?>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
