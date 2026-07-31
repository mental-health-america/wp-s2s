<?php
/**
 * Plugin Name: MHA Error Telemetry
 * Description: Temporary production instrumentation. Logs fatals, slow requests, and admin-ajax failures to uploads/mha-telemetry/. Disable with define( 'MHA_ERROR_TELEMETRY', false ); in wp-config.php.
 * Version: 1.0.0
 * Author: MHA Web Team
 *
 * Must-use plugin so it cannot be deactivated from the Plugins screen.
 * Safe to leave running for a few days of observation; remove or disable when done.
 */

if ( defined( 'MHA_ERROR_TELEMETRY' ) && ! MHA_ERROR_TELEMETRY ) {
	return;
}

/**
 * Shared helpers for the telemetry log.
 */
final class MHA_Error_Telemetry {

	const SLOW_THRESHOLD_SECONDS = 5.0;
	const MAX_LOG_BYTES          = 26214400; // 25 MB
	const WARNING_SAMPLE_RATE    = 0.05;     // 5% of warnings/notices
	const EXPORT_IMPORT_ACTIONS  = array(
		'mha_aggregate_data_export',
		'mha_nonaggregate_data_export',
		'mha_user_data_export',
		'mha_export_screen_data',
		'mha_export_diy_tool_data',
		'mha_export_ab_testing_data',
		'mha_export_feedback_data',
		'mha_export_click_monitor_data',
		'mha_export_cta_codes',
		'mha_export_callrailcta',
		'mha_result_updater_looper',
		'mhaImporterUploader',
		'mhaImporterLooper',
		'mhaCtaCodeImporter',
		'mha_get_api_log_entries_SSP',
	);

	/** @var float */
	private static $request_start;

	/** @var bool */
	private static $booted = false;

	public static function boot() {
		if ( self::$booted ) {
			return;
		}
		self::$booted        = true;
		self::$request_start = isset( $_SERVER['REQUEST_TIME_FLOAT'] )
			? (float) $_SERVER['REQUEST_TIME_FLOAT']
			: microtime( true );

		// Capture warnings without requiring WP_DEBUG, and without displaying them.
		set_error_handler( array( __CLASS__, 'handle_php_error' ) );
		register_shutdown_function( array( __CLASS__, 'on_shutdown' ) );

		add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
	}

	/**
	 * Log directory under uploads (writable on WP Engine, not executed as PHP).
	 */
	public static function log_dir() {
		$base = WP_CONTENT_DIR . '/uploads';
		if ( function_exists( 'wp_upload_dir' ) ) {
			$uploads = wp_upload_dir( null, false );
			if ( empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ) {
				$base = $uploads['basedir'];
			}
		}
		return rtrim( $base, '/\\' ) . '/mha-telemetry';
	}

	public static function log_file() {
		return self::log_dir() . '/telemetry.log';
	}

	public static function ensure_log_dir() {
		$dir = self::log_dir();
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$index = $dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}

		$htaccess = $dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			$rules  = "# Deny direct HTTP access to telemetry logs.\n";
			$rules .= "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n";
			$rules .= "<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
			@file_put_contents( $htaccess, $rules );
		}
	}

	/**
	 * Append one JSON line. Rotates the file when it exceeds MAX_LOG_BYTES.
	 */
	public static function write( $type, array $context = array() ) {
		self::ensure_log_dir();
		$file = self::log_file();

		if ( file_exists( $file ) && filesize( $file ) >= self::MAX_LOG_BYTES ) {
			@rename( $file, $file . '.' . gmdate( 'Ymd-His' ) . '.bak' );
		}

		$entry = array_merge(
			array(
				'ts'   => gmdate( 'c' ),
				'type' => $type,
			),
			self::request_context(),
			$context
		);

		$line = wp_json_encode( $entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $line ) {
			return;
		}

		@file_put_contents( $file, $line . "\n", FILE_APPEND | LOCK_EX );
	}

	/**
	 * Context shared by every log line.
	 */
	private static function request_context() {
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? (string) $_SERVER['REQUEST_METHOD'] : '';
		$ua     = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( (string) $_SERVER['HTTP_USER_AGENT'], 0, 200 ) : '';
		$action = self::ajax_action();

		$user_id   = 0;
		$logged_in = false;
		if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) {
			$logged_in = true;
			$user_id   = (int) get_current_user_id();
		}

		return array(
			'method'    => $method,
			'uri'       => substr( $uri, 0, 500 ),
			'action'    => $action,
			'logged_in' => $logged_in,
			'user_id'   => $user_id,
			'ua'        => $ua,
		);
	}

	private static function ajax_action() {
		if ( isset( $_REQUEST['action'] ) ) {
			return sanitize_key( (string) $_REQUEST['action'] );
		}
		return '';
	}

	private static function is_admin_ajax_request() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}
		$script = isset( $_SERVER['SCRIPT_FILENAME'] ) ? (string) $_SERVER['SCRIPT_FILENAME'] : '';
		return ( false !== strpos( $script, 'admin-ajax.php' ) )
			|| ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( (string) $_SERVER['REQUEST_URI'], 'admin-ajax.php' ) );
	}

	/**
	 * PHP error handler for warnings (sampled). Fatals are handled on shutdown.
	 *
	 * @return bool False so PHP's normal handling still runs.
	 */
	public static function handle_php_error( $errno, $errstr, $errfile = '', $errline = 0 ) {
		// Respect @-suppression.
		if ( ! ( error_reporting() & $errno ) ) {
			return false;
		}

		// Fatals are captured more reliably via error_get_last() on shutdown.
		$fatal_types = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR );
		if ( in_array( $errno, $fatal_types, true ) ) {
			return false;
		}

		// Sample non-fatal errors so high-traffic sites do not flood the log.
		if ( mt_rand() / mt_getrandmax() > self::WARNING_SAMPLE_RATE ) {
			return false;
		}

		self::write(
			'php_error',
			array(
				'errno'   => $errno,
				'message' => substr( (string) $errstr, 0, 500 ),
				'file'    => self::short_path( (string) $errfile ),
				'line'    => (int) $errline,
			)
		);

		return false;
	}

	public static function on_shutdown() {
		// 1) Fatals / uncaught parse/compile errors.
		$last = error_get_last();
		if ( $last && in_array( (int) $last['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR ), true ) ) {
			self::write(
				'fatal',
				array(
					'errno'   => (int) $last['type'],
					'message' => substr( (string) $last['message'], 0, 800 ),
					'file'    => self::short_path( (string) $last['file'] ),
					'line'    => (int) $last['line'],
				)
			);
		}

		$elapsed = microtime( true ) - self::$request_start;
		$status  = (int) http_response_code();
		$action  = self::ajax_action();

		// 2) Slow requests.
		if ( $elapsed >= self::SLOW_THRESHOLD_SECONDS ) {
			global $wpdb;
			$template = '';
			if ( function_exists( 'get_page_template_slug' ) ) {
				$template = (string) get_page_template_slug();
			}
			self::write(
				'slow',
				array(
					'elapsed_s'   => round( $elapsed, 3 ),
					'status'      => $status,
					'num_queries' => ( isset( $wpdb ) && is_object( $wpdb ) ) ? (int) $wpdb->num_queries : null,
					'peak_mem_mb' => round( memory_get_peak_usage( true ) / 1048576, 2 ),
					'template'    => $template,
				)
			);
		}

		// 3) admin-ajax non-200 responses.
		if ( self::is_admin_ajax_request() && $status >= 400 ) {
			self::write(
				'admin_ajax_error',
				array(
					'status'    => $status,
					'elapsed_s' => round( $elapsed, 3 ),
				)
			);
		}

		// 4) Always log export/import (and API log) hits so we can see non-admin use.
		if ( $action && in_array( $action, self::EXPORT_IMPORT_ACTIONS, true ) ) {
			self::write(
				'admin_ajax_sensitive',
				array(
					'status'    => $status,
					'elapsed_s' => round( $elapsed, 3 ),
					'can_manage'=> function_exists( 'current_user_can' ) ? (bool) current_user_can( 'manage_options' ) : false,
				)
			);
		}
	}

	private static function short_path( $path ) {
		$path = str_replace( '\\', '/', $path );
		$content = defined( 'WP_CONTENT_DIR' ) ? str_replace( '\\', '/', WP_CONTENT_DIR ) : '';
		$abspath = defined( 'ABSPATH' ) ? str_replace( '\\', '/', ABSPATH ) : '';
		if ( $content && 0 === strpos( $path, $content ) ) {
			return 'wp-content' . substr( $path, strlen( $content ) );
		}
		if ( $abspath && 0 === strpos( $path, $abspath ) ) {
			return substr( $path, strlen( $abspath ) );
		}
		return $path;
	}

	public static function register_admin_page() {
		add_management_page(
			'MHA Error Telemetry',
			'MHA Error Telemetry',
			'manage_options',
			'mha-error-telemetry',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	public static function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$file = self::log_file();
		$exists = file_exists( $file );
		$size   = $exists ? size_format( filesize( $file ) ) : '0';
		$lines  = array();

		if ( $exists && is_readable( $file ) ) {
			$raw   = @file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			$lines = is_array( $raw ) ? array_slice( $raw, -50 ) : array();
		}

		$counts = array();
		foreach ( $lines as $line ) {
			$data = json_decode( $line, true );
			$type = is_array( $data ) && isset( $data['type'] ) ? $data['type'] : 'unknown';
			if ( ! isset( $counts[ $type ] ) ) {
				$counts[ $type ] = 0;
			}
			$counts[ $type ]++;
		}
		krsort( $counts );

		echo '<div class="wrap">';
		echo '<h1>MHA Error Telemetry</h1>';
		echo '<p>Temporary instrumentation for diagnosing elevated 4xx/5xx rates. Safe to leave on for a few days. Disable with <code>define( \'MHA_ERROR_TELEMETRY\', false );</code> in <code>wp-config.php</code>, or delete this mu-plugin file.</p>';
		echo '<p><strong>Log file:</strong> <code>' . esc_html( $file ) . '</code> (' . esc_html( $size ) . ')</p>';
		echo '<p>Thresholds: fatals always; slow requests &ge; ' . esc_html( (string) self::SLOW_THRESHOLD_SECONDS ) . 's; admin-ajax status &ge; 400; export/import actions always; PHP warnings sampled at ' . esc_html( (string) ( self::WARNING_SAMPLE_RATE * 100 ) ) . '%.</p>';

		if ( $counts ) {
			echo '<h2>Counts in last ' . count( $lines ) . ' lines</h2><ul>';
			foreach ( $counts as $type => $n ) {
				echo '<li><code>' . esc_html( $type ) . '</code>: ' . (int) $n . '</li>';
			}
			echo '</ul>';
		}

		echo '<h2>Last ' . count( $lines ) . ' log lines</h2>';
		if ( ! $lines ) {
			echo '<p><em>No entries yet. Hit a page (or wait for traffic) and refresh.</em></p>';
		} else {
			echo '<pre style="max-height:480px;overflow:auto;background:#1d2327;color:#f0f0f1;padding:12px;font-size:12px;">';
			foreach ( $lines as $line ) {
				echo esc_html( $line ) . "\n";
			}
			echo '</pre>';
		}
		echo '</div>';
	}
}

MHA_Error_Telemetry::boot();
