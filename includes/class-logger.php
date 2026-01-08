<?php
/**
 * Logger class for Leave Manager Plugin
 *
 * Handles system logging with multiple log levels.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Logger class
 */
class Leave_Manager_Logger {

	/**
	 * Log levels
	 */
	const EMERGENCY = 'emergency';
	const ALERT     = 'alert';
	const CRITICAL  = 'critical';
	const ERROR     = 'error';
	const WARNING   = 'warning';
	const NOTICE    = 'notice';
	const INFO      = 'info';
	const DEBUG     = 'debug';

	/**
	 * Log file path
	 *
	 * @var string
	 */
	private $log_file;

	/**
	 * Database instance
	 *
	 * @var Leave_Manager_Database|null
	 */
	private $db;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database|null $db Database instance (optional)
	 */
	public function __construct( $db = null ) {
		$this->db = $db;
		$upload_dir = wp_upload_dir();
		$this->log_file = $upload_dir['basedir'] . '/leave-manager-management.log';
	}

	/**
	 * Log a message
	 *
	 * @param string $level Log level
	 * @param string $message Log message
	 * @param array  $context Additional context
	 * @return bool True on success
	 */
	public function log( $level, $message, $context = array() ) {
		$timestamp = current_time( 'Y-m-d H:i:s' );
		$context_str = ! empty( $context ) ? ' | Context: ' . wp_json_encode( $context ) : '';
		$log_message = sprintf(
			'[%s] [%s] %s%s',
			$timestamp,
			strtoupper( $level ),
			$message,
			$context_str
		);

		// Write to file
		error_log( $log_message, 3, $this->log_file );

		return true;
	}

	/**
	 * Emergency log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 * @return bool True on success
	 */
	public function emergency( $message, $context = array() ) {
		return $this->log( self::EMERGENCY, $message, $context );
	}

	/**
	 * Alert log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 * @return bool True on success
	 */
	public function alert( $message, $context = array() ) {
		return $this->log( self::ALERT, $message, $context );
	}

	/**
	 * Critical log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 * @return bool True on success
	 */
	public function critical( $message, $context = array() ) {
		return $this->log( self::CRITICAL, $message, $context );
	}

	/**
	 * Error log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 * @return bool True on success
	 */
	public function error( $message, $context = array() ) {
		return $this->log( self::ERROR, $message, $context );
	}

	/**
	 * Warning log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 * @return bool True on success
	 */
	public function warning( $message, $context = array() ) {
		return $this->log( self::WARNING, $message, $context );
	}

	/**
	 * Notice log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 * @return bool True on success
	 */
	public function notice( $message, $context = array() ) {
		return $this->log( self::NOTICE, $message, $context );
	}

	/**
	 * Info log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 * @return bool True on success
	 */
	public function info( $message, $context = array() ) {
		return $this->log( self::INFO, $message, $context );
	}

	/**
	 * Debug log
	 *
	 * @param string $message Log message
	 * @param array  $context Additional context
	 * @return bool True on success
	 */
	public function debug( $message, $context = array() ) {
		return $this->log( self::DEBUG, $message, $context );
	}

	/**
	 * Log database operation
	 *
	 * @param string $operation Operation type (insert, update, delete, select)
	 * @param string $table Table name
	 * @param array  $data Data involved
	 * @return bool True on success
	 */
	public function log_database_operation( $operation, $table, $data = array() ) {
		return $this->debug(
			"Database operation: $operation on table $table",
			array( 'operation' => $operation, 'table' => $table, 'data' => $data )
		);
	}

	/**
	 * Log email operation
	 *
	 * @param string $recipient Recipient email
	 * @param string $subject Email subject
	 * @param string $status Email status
	 * @param string $error Error message if any
	 * @return bool True on success
	 */
	public function log_email_operation( $recipient, $subject, $status, $error = '' ) {
		return $this->info(
			"Email operation: $status to $recipient",
			array(
				'recipient' => $recipient,
				'subject'   => $subject,
				'status'    => $status,
				'error'     => $error,
			)
		);
	}

	/**
	 * Log user authentication
	 *
	 * @param string $user_email User email
	 * @param string $action Action (login, logout, register, password_reset)
	 * @param bool   $success Success or failure
	 * @param string $reason Reason for failure if any
	 * @return bool True on success
	 */
	public function log_user_auth( $user_email, $action, $success, $reason = '' ) {
		$level = $success ? self::INFO : self::WARNING;
		return $this->log(
			$level,
			"User authentication: $action for $user_email - " . ( $success ? 'Success' : 'Failed' ),
			array(
				'user_email' => $user_email,
				'action'     => $action,
				'success'    => $success,
				'reason'     => $reason,
			)
		);
	}

	/**
	 * Log API request
	 *
	 * @param string $endpoint API endpoint
	 * @param string $method HTTP method
	 * @param array  $data Request data
	 * @param int    $response_code Response code
	 * @return bool True on success
	 */
	public function log_api_request( $endpoint, $method, $data = array(), $response_code = 0 ) {
		return $this->debug(
			"API request: $method $endpoint",
			array(
				'endpoint'      => $endpoint,
				'method'        => $method,
				'data'          => $data,
				'response_code' => $response_code,
			)
		);
	}

	/**
	 * Get recent logs
	 *
	 * @param int $limit Number of logs to retrieve
	 * @return array Array of log entries
	 */
	public function get_recent_logs( $limit = 100 ) {
		if ( ! file_exists( $this->log_file ) ) {
			return array();
		}

		$lines = file( $this->log_file, FILE_IGNORE_NEW_LINES );
		$lines = array_reverse( $lines );
		return array_slice( $lines, 0, $limit );
	}

	/**
	 * Clear logs
	 *
	 * @return bool True on success
	 */
	public function clear_logs() {
		if ( file_exists( $this->log_file ) ) {
			return unlink( $this->log_file );
		}
		return true;
	}

	/**
	 * Get log file path
	 *
	 * @return string Log file path
	 */
	public function get_log_file() {
		return $this->log_file;
	}
}
