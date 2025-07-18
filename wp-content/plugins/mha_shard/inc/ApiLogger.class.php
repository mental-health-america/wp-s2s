<?php

namespace MHA;

/**
 * Logs API request data to a custom `mha_api_logs` database table.
 * SQL to create this table provided below for reference. IP addresses
 * must be converted to fit in the VARBINARY(16) using INET6_ATON() during insertion and
 * INET6_NTOA() when retrieving them.
 */

/* Log DB Table SQL:

CREATE TABLE `mha_api_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `api_key` varchar(255) NOT NULL,
  `source_ip` varbinary(16) NOT NULL,
  `sid` varchar(255) NOT NULL,
  `http_response_code` int NOT NULL,
  `http_response_message` varchar(255) DEFAULT NULL,
  `accessed_on` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `source_ip` (`source_ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
*/

use Exception;
use InvalidArgumentException;
use WP_Error;

class ApiLogger {
  private const LOGGING_DB_TABLE_NAME = 'mha_api_log';

  public function __construct() {}

  /**
   * Retrieves a specific log from the database.
   *
   * @param  int  $log_id
   *
   * @return ApiLogEntry
   */
  public static function get( int $log_id = 0 ): ApiLogEntry|false
  {
    global $wpdb;
    $table_name = self::LOGGING_DB_TABLE_NAME;

    $sql = $wpdb->prepare( "
      SELECT *
      FROM {$table_name}
      WHERE id = %d
    ", $log_id );

    $result = $wpdb->get_row( $sql );

    if ( ! is_null( $result ) ):
      return new ApiLogEntry(
        $result->id,
        $result->api_key,
        $result->source_ip,
        $result->sid,
        $result->http_response_code,
        $result->http_response_message,
        $result->accessed_on
      );
    else:
      return false;
    endif;
  }

  /**
   * Retrieves all logs from the database.
   *
   * @param  string  $orderby
   *
   * @return array
   */
  public static function get_all( string $orderby = 'id DESC' ): array
  {
    global $wpdb;
    $table_name = self::LOGGING_DB_TABLE_NAME;
    $result     = $wpdb->get_results( "
      SELECT 
        id,
        api_key,
        INET6_NTOA(source_ip) as source_ip,
        sid,
        http_response_code,
        http_response_message,
        accessed_on
      FROM {$table_name} 
      ORDER BY {$orderby}
    " );

    if ( 0 < $wpdb->num_rows ):
      $objectified_array = [];
      foreach ( $result as $log ) {
        $objectified_array[] = new ApiLogEntry(
          $log->api_key,
          $log->source_ip,
          $log->sid,
          (int) $log->http_response_code,
          $log->http_response_message,
          $log->accessed_on,
          $log->id
        );
      }
      return $objectified_array;
    else:
      return [];
    endif;
  }

  /**
   * Saves the log to the database.
   *
   * @return int|WP_Error The ID of the inserted row or WP_Error on failure.
   *
   * @throws Exception When attempting to save over an already existing log entry.
   */
  public static function save( ApiLogEntry $entry ): int|WP_Error
  {
    global $wpdb;

    // Should only have an ID if it's been loaded from the database.
    // We don't want to allow them to be edited.
    if ( 0 < $entry->id ):
      return new WP_Error( 'api_log_entry_overwrite_prevention', "You can not overwrite previously saved logs!" );

    else:
      $sql = $wpdb->prepare(
        "INSERT INTO " . self::LOGGING_DB_TABLE_NAME . " 
      (api_key, source_ip, sid, http_response_code, http_response_message, accessed_on)
      VALUES (%s, INET6_ATON(%s), %s, %d, %s, %s)",
        $entry->api_key,
        $entry->source_ip,
        $entry->sid,
        $entry->http_response_code,
        $entry->http_response_message,
        $entry->accessed_on
      );

      $result = $wpdb->query($sql);

      if ( ! $result ) {
        error_log( $wpdb->last_error );
        return new WP_Error( 'save_api_log_entry_fail', $wpdb->last_error );
      } else {
        return $wpdb->insert_id;
      }
    endif;
  }

  /**
   * Deletes the log entry from the database pending a capability check.
   *
   * @param ApiLogEntry $entry
   *
   * @return true|WP_Error Returns true on success or a WP_Error on failure
   */
  public static function delete( ApiLogEntry $entry ): bool|WP_Error
  {
    global $wpdb;

    if ( 0 === $entry->id )
      throw new InvalidArgumentException( 'Cannot delete a log entry without an ID.' );

    if ( current_user_can( 'manage_options' ) ):
      if ( ! $wpdb->delete( self::LOGGING_DB_TABLE_NAME, [ 'id' => $entry->id ] ) ) {
        error_log( $wpdb->last_error );
        return new WP_Error( 'delete_api_log_entry_fail', $wpdb->last_error );
      } else {
        return true;
      }
    else:
      return new WP_Error( 'invalid_user_permissions', 'You do not have sufficient privileges to delete API logs.' );
    endif;
  }

}

class ApiLogEntry {
  public int $id;
  public string $api_key;
  public string $source_ip = '';
  public string $sid;
  public int $http_response_code;
  public ?string $http_response_message = null;
  public string $accessed_on;

  public function __construct( string $api_key_label, string $source_ip, string $sid, int $http_response_code, string $http_response_message = null, string $timestamp = null, $id = null )
  {
    // Populate ID -- mainly for retrieving rows
    if ( is_null( $id ) || $id === 0 ) $this->id = 0;
    elseif ( $id > 0 ) $this->id = $id;
    else throw new InvalidArgumentException( 'Invalid log ID.' );

    $this->api_key   = $api_key_label;
    $this->source_ip = $source_ip;
    $this->sid       = $sid;

    // Ensure a valid, 3 digit response code
    if ( strlen( (string) $http_response_code ) === 3 )
      $this->http_response_code = $http_response_code;
    else throw new InvalidArgumentException( 'HTTP response code must be a 3 digit integer.' );

    $this->http_response_message = $http_response_message;
    $this->accessed_on = $timestamp ?? current_datetime()->format('Y-m-d H:i:s');
  }

  /**
   * Update the object's `accessed_on` timestamp with the current datetime.
   * @return void
   */
  public function updateTimestamp()
  {
    $this->accessed_on = current_datetime()->format('Y-m-d H:i:s');
  }
}