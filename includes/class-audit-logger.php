<?php
/**
 * Audit Logger class for Leave Manager Plugin
 *
 * Logs all sensitive operations for security and compliance.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Audit_Logger class
 */
class Leave_Manager_Audit_Logger {

	/**
	 * Database instance
	 *
	 * @var Leave_Manager_Database
	 */
	private $db;

	/**
	 * Audit log table name
	 *
	 * @var string
	 */
	private $audit_table;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 */
	public function __construct( $db ) {
		$this->db           = $db;
		$this->audit_table  = $db->wpdb->prefix . 'leave_manager_audit_log';
	}

	/**
	 * Log an action
	 *
	 * @param string $action Action name
	 * @param int    $user_id User ID
	 * @param array  $details Action details
	 * @return int|false Log ID or false
	 */
	public function log( $action, $user_id = null, $details = array() ) {
		$data = array(
			'action'      => sanitize_text_field( $action ),
			'user_id'     => ! empty( $user_id ) ? intval( $user_id ) : null,
			'ip_address'  => $this->get_client_ip(),
			'details'     => ! empty( $details ) ? wp_json_encode( $details ) : null,
			'created_at'  => current_time( 'mysql' ),
		);

		return $this->db->insert(
			$this->audit_table,
			$data,
			array( '%s', '%d', '%s', '%s', '%s' )
		);
	}

	/**
	 * Log user login
	 *
	 * @param int $user_id User ID
	 * @return int|false Log ID or false
	 */
	public function log_login( $user_id ) {
		return $this->log( 'user_login', $user_id, array(
			'user_id' => $user_id,
		) );
	}

	/**
	 * Log user logout
	 *
	 * @param int $user_id User ID
	 * @return int|false Log ID or false
	 */
	public function log_logout( $user_id ) {
		return $this->log( 'user_logout', $user_id, array(
			'user_id' => $user_id,
		) );
	}

	/**
	 * Log user creation
	 *
	 * @param int   $user_id User ID
	 * @param array $user_data User data
	 * @return int|false Log ID or false
	 */
	public function log_user_creation( $user_id, $user_data ) {
		return $this->log( 'user_created', get_current_user_id(), array(
			'created_user_id' => $user_id,
			'email'           => isset( $user_data['email'] ) ? $user_data['email'] : '',
			'role'            => isset( $user_data['role'] ) ? $user_data['role'] : '',
		) );
	}

	/**
	 * Log user modification
	 *
	 * @param int   $user_id User ID
	 * @param array $changes Changed fields
	 * @return int|false Log ID or false
	 */
	public function log_user_modification( $user_id, $changes ) {
		return $this->log( 'user_modified', get_current_user_id(), array(
			'modified_user_id' => $user_id,
			'changes'          => $changes,
		) );
	}

	/**
	 * Log user deletion
	 *
	 * @param int $user_id User ID
	 * @return int|false Log ID or false
	 */
	public function log_user_deletion( $user_id ) {
		return $this->log( 'user_deleted', get_current_user_id(), array(
			'deleted_user_id' => $user_id,
		) );
	}

	/**
	 * Log leave request approval
	 *
	 * @param int $request_id Request ID
	 * @param int $approver_id Approver user ID
	 * @return int|false Log ID or false
	 */
	public function log_request_approval( $request_id, $approver_id ) {
		return $this->log( 'request_approved', $approver_id, array(
			'request_id' => $request_id,
		) );
	}

	/**
	 * Log leave request rejection
	 *
	 * @param int    $request_id Request ID
	 * @param int    $approver_id Approver user ID
	 * @param string $reason Rejection reason
	 * @return int|false Log ID or false
	 */
	public function log_request_rejection( $request_id, $approver_id, $reason ) {
		return $this->log( 'request_rejected', $approver_id, array(
			'request_id' => $request_id,
			'reason'     => $reason,
		) );
	}

	/**
	 * Log settings change
	 *
	 * @param string $setting_key Setting key
	 * @param mixed  $old_value Old value
	 * @param mixed  $new_value New value
	 * @return int|false Log ID or false
	 */
	public function log_settings_change( $setting_key, $old_value, $new_value ) {
		return $this->log( 'settings_changed', get_current_user_id(), array(
			'setting_key' => $setting_key,
			'old_value'   => $old_value,
			'new_value'   => $new_value,
		) );
	}

	/**
	 * Log data export
	 *
	 * @param string $export_type Type of export
	 * @param int    $record_count Number of records exported
	 * @return int|false Log ID or false
	 */
	public function log_data_export( $export_type, $record_count ) {
		return $this->log( 'data_exported', get_current_user_id(), array(
			'export_type'  => $export_type,
			'record_count' => $record_count,
		) );
	}

	/**
	 * Log failed login attempt
	 *
	 * @param string $username Username attempted
	 * @return int|false Log ID or false
	 */
	public function log_failed_login( $username ) {
		return $this->log( 'failed_login', null, array(
			'username' => $username,
		) );
	}

	/**
	 * Log permission denied
	 *
	 * @param string $action Action attempted
	 * @param int    $user_id User ID
	 * @return int|false Log ID or false
	 */
	public function log_permission_denied( $action, $user_id ) {
		return $this->log( 'permission_denied', $user_id, array(
			'action' => $action,
		) );
	}

	/**
	 * Get audit logs
	 *
	 * @param array $args Query arguments
	 * @return array Audit logs
	 */
	public function get_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'action'   => '',
			'user_id'  => '',
			'start_date' => '',
			'end_date'   => '',
			'limit'    => 100,
			'offset'   => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$query = "SELECT * FROM {$this->audit_table} WHERE 1=1";
		$values = array();

		if ( ! empty( $args['action'] ) ) {
			$query .= " AND action = %s";
			$values[] = $args['action'];
		}

		if ( ! empty( $args['user_id'] ) ) {
			$query .= " AND user_id = %d";
			$values[] = intval( $args['user_id'] );
		}

		if ( ! empty( $args['start_date'] ) ) {
			$query .= " AND created_at >= %s";
			$values[] = date( 'Y-m-d 00:00:00', strtotime( $args['start_date'] ) );
		}

		if ( ! empty( $args['end_date'] ) ) {
			$query .= " AND created_at <= %s";
			$values[] = date( 'Y-m-d 23:59:59', strtotime( $args['end_date'] ) );
		}

		$query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$values[] = intval( $args['limit'] );
		$values[] = intval( $args['offset'] );

		if ( ! empty( $values ) ) {
			return $wpdb->get_results( $wpdb->prepare( $query, $values ) );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Get audit statistics
	 *
	 * @return array Statistics
	 */
	public function get_statistics() {
		global $wpdb;

		$actions = $wpdb->get_results(
			"SELECT action, COUNT(*) as count FROM {$this->audit_table}
			 GROUP BY action
			 ORDER BY count DESC"
		);

		$stats = array(
			'total' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->audit_table}" ) ),
			'by_action' => array(),
		);

		foreach ( $actions as $action ) {
			$stats['by_action'][ $action->action ] = intval( $action->count );
		}

		return $stats;
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP
	 */
	private function get_client_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		}

		// Validate IP
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return '';
	}

	/**
	 * Clear old audit logs
	 *
	 * @param int $days Number of days to keep
	 * @return int Number of entries deleted
	 */
	public function cleanup_old_logs( $days = 180 ) {
		global $wpdb;

		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->audit_table}
				 WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);
	}
}
