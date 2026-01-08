<?php
/**
 * Debug Logger class for Leave Manager Plugin
 *
 * Provides detailed logging for debugging permission and access issues.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Debug_Logger class
 */
class Leave_Manager_Debug_Logger {

	/**
	 * Log file path
	 *
	 * @var string
	 */
	private static $log_file = null;

	/**
	 * Get log file path
	 *
	 * @return string Log file path
	 */
	private static function get_log_file() {
		if ( self::$log_file === null ) {
			self::$log_file = WP_CONTENT_DIR . '/leave_manager-debug.log';
		}
		return self::$log_file;
	}

	/**
	 * Log a message
	 *
	 * @param string $message Message to log
	 * @param string $level Log level (INFO, DEBUG, ERROR, WARNING)
	 * @return void
	 */
	public static function log( $message, $level = 'INFO' ) {
		$log_file = self::get_log_file();
		$timestamp = date( 'Y-m-d H:i:s' );
		$log_entry = "[{$timestamp}] [{$level}] {$message}\n";
		
		// Append to log file
		file_put_contents( $log_file, $log_entry, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Log current user information
	 *
	 * @param string $context Where this is being called from
	 * @return void
	 */
	public static function log_user_info( $context = '' ) {
		$current_user = wp_get_current_user();
		
		$info = array(
			'context' => $context,
			'user_id' => $current_user->ID,
			'user_login' => $current_user->user_login,
			'user_email' => $current_user->user_email,
			'roles' => implode( ', ', $current_user->roles ),
			'is_logged_in' => is_user_logged_in() ? 'YES' : 'NO',
			'can_manage_options' => current_user_can( 'manage_options' ) ? 'YES' : 'NO',
			'can_view_all_leave_requests' => current_user_can( 'view_all_leave_requests' ) ? 'YES' : 'NO',
			'is_admin' => current_user_can( 'administrator' ) ? 'YES' : 'NO',
			'is_super_admin' => is_super_admin() ? 'YES' : 'NO',
		);
		
		self::log( 'USER INFO: ' . json_encode( $info ), 'DEBUG' );
	}

	/**
	 * Log all user capabilities
	 *
	 * @return void
	 */
	public static function log_user_capabilities() {
		$current_user = wp_get_current_user();
		
		if ( $current_user->ID === 0 ) {
			self::log( 'USER CAPABILITIES: No user logged in', 'WARNING' );
			return;
		}
		
		$caps = array();
		foreach ( $current_user->allcaps as $cap => $granted ) {
			if ( $granted ) {
				$caps[] = $cap;
			}
		}
		
		self::log( 'USER CAPABILITIES for ' . $current_user->user_login . ': ' . implode( ', ', $caps ), 'DEBUG' );
	}

	/**
	 * Log database table status
	 *
	 * @return void
	 */
	public static function log_table_status() {
		global $wpdb;
		
		$tables = array(
			'leave_manager_leave_users',
			'leave_manager_leave_requests',
			'leave_manager_leave_policies',
			'leave_manager_email_queue',
			'leave_manager_settings',
		);
		
		$status = array();
		foreach ( $tables as $table ) {
			$full_table = $wpdb->prefix . $table;
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '$full_table'" ) === $full_table;
			$status[ $table ] = $exists ? 'EXISTS' : 'MISSING';
		}
		
		self::log( 'DATABASE TABLES: ' . json_encode( $status ), 'DEBUG' );
	}

	/**
	 * Log request information
	 *
	 * @return void
	 */
	public static function log_request_info() {
		$info = array(
			'request_uri' => isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : 'N/A',
			'page' => isset( $_GET['page'] ) ? $_GET['page'] : 'N/A',
			'action' => isset( $_GET['action'] ) ? $_GET['action'] : 'N/A',
			'method' => isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'N/A',
			'is_admin' => is_admin() ? 'YES' : 'NO',
			'doing_ajax' => wp_doing_ajax() ? 'YES' : 'NO',
		);
		
		self::log( 'REQUEST INFO: ' . json_encode( $info ), 'DEBUG' );
	}

	/**
	 * Log WordPress environment info
	 *
	 * @return void
	 */
	public static function log_wp_environment() {
		$info = array(
			'wp_version' => get_bloginfo( 'version' ),
			'php_version' => phpversion(),
			'multisite' => is_multisite() ? 'YES' : 'NO',
			'site_url' => get_site_url(),
			'admin_url' => admin_url(),
			'plugins_loaded' => did_action( 'plugins_loaded' ) ? 'YES' : 'NO',
			'init_done' => did_action( 'init' ) ? 'YES' : 'NO',
			'admin_init_done' => did_action( 'admin_init' ) ? 'YES' : 'NO',
		);
		
		self::log( 'WP ENVIRONMENT: ' . json_encode( $info ), 'DEBUG' );
	}

	/**
	 * Clear the log file
	 *
	 * @return bool True on success
	 */
	public static function clear_log() {
		$log_file = self::get_log_file();
		return file_put_contents( $log_file, '' ) !== false;
	}

	/**
	 * Get log contents
	 *
	 * @param int $lines Number of lines to return (0 for all)
	 * @return string Log contents
	 */
	public static function get_log( $lines = 100 ) {
		$log_file = self::get_log_file();
		
		if ( ! file_exists( $log_file ) ) {
			return 'Log file does not exist yet.';
		}
		
		$content = file_get_contents( $log_file );
		
		if ( $lines > 0 ) {
			$all_lines = explode( "\n", $content );
			$all_lines = array_slice( $all_lines, -$lines );
			$content = implode( "\n", $all_lines );
		}
		
		return $content;
	}

	/**
	 * Full diagnostic dump
	 *
	 * @param string $context Context description
	 * @return void
	 */
	public static function full_diagnostic( $context = 'Unknown' ) {
		self::log( '========== FULL DIAGNOSTIC START: ' . $context . ' ==========', 'INFO' );
		self::log_wp_environment();
		self::log_request_info();
		self::log_user_info( $context );
		self::log_user_capabilities();
		self::log_table_status();
		self::log( '========== FULL DIAGNOSTIC END: ' . $context . ' ==========', 'INFO' );
	}
}
