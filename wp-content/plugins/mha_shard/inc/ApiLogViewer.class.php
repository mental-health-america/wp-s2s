<?php

namespace MHA;

use DataTables\SSP;
use DateTimeZone;

class ApiLogViewer {

  private const API_LOG_DB_TABLE = 'mha_api_log';

  public function __construct() {
    add_action( 'admin_menu', [ $this, 'add_admin_menu_items' ] );
    add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
    add_action( 'wp_ajax_mha_get_api_log_entries_SSP', [ $this, 'get_log_entries_SSP' ], 10, 1 );
  }

  public function add_admin_menu_items(): void
  {
    add_submenu_page(
      'tools.php',
      'MHA API Access Log',
      'MHA API Log',
      'manage_options',
      'mha-api-log',
      [ $this, 'render' ],
      1
    );
  }

  public function enqueue_admin_scripts(): void
  {
    wp_enqueue_style(
      'datatables-styles',
      '//cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css',
      [],
    );

    wp_enqueue_script(
      'datatables-scripts',
      '//cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js',
      ['jquery'],
      '1.13.6',
      true
    );

    wp_enqueue_script(
      'jszip',
      '//cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js',
      [],
      '3.10.1',
      true
    );

    wp_enqueue_style(
      'datatables-btns-styles',
      '//cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css',
      [],
      '2.4.2'
    );

    wp_enqueue_script(
      'datatables-btns-scripts',
      '//cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js',
      ['jquery', 'datatables-scripts'],
      '2.4.2',
      true
    );
    wp_enqueue_script(
      'datatables-btns-html5',
      '//cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js',
      ['jquery', 'datatables-btns-scripts', 'jszip'],
      '2.4.2',
      true
    );

    wp_enqueue_style(
      'datatables-datetime-styles',
      '//cdn.datatables.net/datetime/1.5.3/css/dataTables.dateTime.min.css',
      [],
      '1.5.3'
    );
    wp_enqueue_script(
      'datatables-datetime',
      '//cdn.datatables.net/datetime/1.5.3/js/dataTables.dateTime.min.js',
      ['jquery', 'datatables-scripts'],
      '1.5.3',
      true
    );

    wp_enqueue_script(
      'moment-js',
      'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment.min.js',
      [],
      '2.30.1',
      true
    );

    wp_enqueue_script(
      'moment-timezone',
      'https://cdnjs.cloudflare.com/ajax/libs/moment-timezone/0.5.45/moment-timezone-with-data-10-year-range.min.js',
      ['moment-js'],
      '0.5.45',
      true
    );

    // Scripts added to mha_admin.js, enqueued elsewhere
  }

  public function render()
  {
    //  global $wpdb;
    //  $rows = $wpdb->get_results("SELECT * FROM `wp_anonymized_gf_entry` ORDER BY submission_date DESC" );
    ?>
    <style type="text/css">
        .content-wrap { margin-bottom: 30px !important; }
        .datatables-wrap { margin-top: 15px; }
        .datatables-wrap select { padding-right: 25px !important; }
        .ajax-loading-text { font-style: italic; }
        .ajax-error-text { font-weight: bold; color: #AA3333; }
        .dt-buttons {
            float: none !important;
            margin: 50px 0 15px;
            display: block;
        }
        #anon-tracking-data_filter input { background-color: #FFF !important; }
        #anon-tracking-data { margin-bottom: 14px; }
        #anon-tracking-data_length, #anon-tracking-data_filter {
            margin-top: 10px;
            margin-bottom: 14px;
        }
    </style>
  <div class="wrap">
  <h2>MHA API Log Viewer</h2>

  <div class="content-wrap">
    <p><strong>Note:</strong> This system ONLY tracks requests to the custom API endpoint for viewing MHA submission data.</p>
  </div>

  <table>
    <thead>
    <tr>
      <th>Data Filters</th>
    </tr>
    </thead>
    <tbody>
    <tr>
      <td>Start date:</td>
      <td><input type="text" id="min" name="min"></td>
    </tr>
    <tr>
      <td>End date:</td>
      <td><input type="text" id="max" name="max"></td>
    </tr>
    </tbody>
  </table>

  <div class="wrap datatables-wrap">
    <table id="anon-tracking-data" class="wp-list-table widefat fixed striped table-view-list" datatables-enable>
      <thead>
      <tr>
        <th>ID</th>
        <th>API Key</th>
        <th>Source</th>
        <th>SID</th>
        <th>HTTP Response</th>
        <th>Message</th>
        <th>Access DateTime</th>
      </tr>
      </thead>
      <tbody>
      <tr>
        <td colspan="12" style="text-align: center"><span class="ajax-loading-text">Loading...</span></td>
      </tr>
      </tbody>
    </table>
  </div>
  </div>

    <?php
  }

  public static function get_log_entries_SSP(): void
  {
    // A wp_ajax_ hook only requires an authenticated request, not an authorized one, so
    // the capability has to be checked here as well as on the admin page itself.
    if ( ! current_user_can( 'manage_options' ) ) {
      wp_send_json_error( array( 'error' => 'You do not have permission to view the API log.' ), 403 );
    }

    if ( ! check_ajax_referer( 'mha_api_log_entries', '_ajax_nonce', false ) ) {
      wp_send_json_error( array( 'error' => 'Security check failed. Refresh the page and try again.' ), 403 );
    }

    // Array of database columns which should be read and sent back to DataTables.
    // The `db` parameter represents the column name in the database, while the `dt`
    // parameter represents the DataTables column identifier. In this case simple
    // indexes
    $columns = [
      [ 'db' => 'id', 'dt' => 'id' ],
      [ 'db' => 'api_key', 'dt' => 'api_key' ],
      [
        'db' => 'source_ip',
        'dt' => 'source_ip',
        'formatter' => function( $d, $row ) {
          return long2ip( sprintf( "%d", $d ) );
        }
      ],
      [ 'db' => 'sid', 'dt' => 'sid' ],
      [ 'db' => 'http_response_code', 'dt' => 'http_response_code' ],
      [ 'db' => 'http_response_message', 'dt' => 'http_response_message' ],
      [
        'db' => 'accessed_on',
        'dt' => 'accessed_on',
        'formatter' => function( $d, $row ) {
          $access_date = new \DateTime( $d, current_datetime()->getTimezone() );
          return $access_date->format( 'Y-m-d H:i:s T' );
        }
      ],
    ];

    // SQL server connection information
    $sql_details = array(
      'user' => DB_USER,
      'pass' => DB_PASSWORD,
      'db'   => DB_NAME,
      'host' => DB_HOST,
      'charset' => DB_CHARSET // Depending on your PHP and MySQL config, you may need this
    );

    // Date Filtering
    $_startDate = ( ! empty( $_POST['minDate'] ) )
      ? new \DateTime( $_POST['minDate'] )
      : null;
    $_endDate = ( ! empty( $_POST['maxDate'] ) )
      ? new \DateTime( $_POST['maxDate'] )
      : null;

    global $wpdb;
    $_whereConditions = [];
    if ( $_startDate ) $_whereConditions[] = $wpdb->prepare( 'accessed_on >= %s', $_startDate->format( 'Y-m-d H:i:s' ) );
    if ( $_endDate )   $_whereConditions[] = $wpdb->prepare( 'accessed_on <= %s', $_endDate->format( 'Y-m-d H:i:s' ) );
    $whereResult = ( ! empty( $_whereConditions ) ) ? implode( ' AND ', $_whereConditions ) : null;

    // wp_send_json( SSP::simple( $_POST, $sql_details, 'mha_api_log', 'id', $columns ) );
    wp_send_json( SSP::complex( $_POST, $sql_details, self::API_LOG_DB_TABLE, 'id', $columns, $whereResult, null ) );
  }
}

new ApiLogViewer();