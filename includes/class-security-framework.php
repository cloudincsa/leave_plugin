<?php
/**
 * Security Framework Class
 * Handles permissions, capabilities, and audit logging
 *
 * @package LeaveManager
 * @subpackage Security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Security_Framework {

	/**
	 * Custom capabilities
	 *
	 * @var array
	 */
	private $capabilities = array(
		'manage_leave_manager' => 'Manage Leave Manager',
		'manage_leave_policies' => 'Manage Leave Policies',
		'manage_leave_requests' => 'Manage Leave Requests',
		'approve_leave_requests' => 'Approve Leave Requests',
		'view_leave_reports' => 'View Leave Reports',
		'export_leave_data' => 'Export Leave Data',
		'manage_public_holidays' => 'Manage Public Holidays',
		'manage_leave_templates' => 'Manage Leave Templates',
		'view_audit_logs' => 'View Audit Logs',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'leave_manager_activate', array( $this, 'register_capabilities' ) );
		add_action( 'leave_manager_deactivate', array( $this, 'remove_capabilities' ) );
	}

	/**
	 * Register custom capabilities
	 *
	 * @return void
	 */
	public function register_capabilities() {
		$admin_role = get_role( 'administrator' );

		if ( $admin_role ) {
			foreach ( $this->capabilities as $capability => $label ) {
				if ( ! $admin_role->has_cap( $capability ) ) {
					$admin_role->add_cap( $capability );
				}
			}
		}

		// Add capabilities to manager role
		$manager_role = get_role( 'leave_manager' );
		if ( $manager_role ) {
			$manager_capabilities = array(
				'manage_leave_requests',
				'approve_leave_requests',
				'view_leave_reports',
				'manage_leave_policies',
			);

			foreach ( $manager_capabilities as $capability ) {
				if ( ! $manager_role->has_cap( $capability ) ) {
					$manager_role->add_cap( $capability );
				}
			}
		}

		// Add capabilities to employee role
		$employee_role = get_role( 'leave_employee' );
		if ( $employee_role ) {
			$employee_capabilities = array(
				'manage_leave_requests',
				'view_leave_reports',
			);

			foreach ( $employee_capabilities as $capability ) {
				if ( ! $employee_role->has_cap( $capability ) ) {
					$employee_role->add_cap( $capability );
				}
			}
		}
	}

	/**
	 * Remove custom capabilities
	 *
	 * @return void
	 */
	public function remove_capabilities() {
		$admin_role = get_role( 'administrator' );

		if ( $admin_role ) {
			foreach ( $this->capabilities as $capability => $label ) {
				if ( $admin_role->has_cap( $capability ) ) {
					$admin_role->remove_cap( $capability );
				}
			}
		}
	}

	/**
	 * Check user capability
	 *
	 * @param int    $user_id User ID
	 * @param string $capability Capability to check
	 * @return bool
	 */
	public function user_can( $user_id, $capability ) {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return false;
		}

		return user_can( $user_id, $capability );
	}

	/**
	 * Check if user can approve leave request
	 *
	 * @param int $user_id User ID
	 * @param int $leave_request_id Leave request ID
	 * @return bool
	 */
	public function can_approve_request( $user_id, $leave_request_id ) {
		// Check basic capability
		if ( ! $this->user_can( $user_id, 'approve_leave_requests' ) ) {
			return false;
		}

		// Check if user is assigned as approver for this request
		global $wpdb;

		$table = $wpdb->prefix . 'leave_manager_approval_tasks';

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE approver_id = %d AND approval_request_id IN (
					SELECT id FROM {$wpdb->prefix}leave_manager_approval_requests WHERE leave_request_id = %d
				)",
				$user_id,
				$leave_request_id
			)
		);

		return intval( $result ) > 0;
	}

	/**
	 * Log audit event
	 *
	 * @param string $action Action performed
	 * @param string $entity_type Entity type
	 * @param int    $entity_id Entity ID
	 * @param array  $old_values Old values
	 * @param array  $new_values New values
	 * @return int|false Audit log ID or false on failure
	 */
	public function log_audit_event( $action, $entity_type, $entity_id, $old_values = array(), $new_values = array() ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$ip_address = $this->get_client_ip();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$result = $wpdb->insert(
			$wpdb->prefix . 'leave_manager_audit_log',
			array(
				'user_id' => $user_id,
				'action' => $action,
				'entity_type' => $entity_type,
				'entity_id' => $entity_id,
				'old_values' => wp_json_encode( $old_values ),
				'new_values' => wp_json_encode( $new_values ),
				'ip_address' => $ip_address,
				'user_agent' => $user_agent,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false !== $result ) {
			do_action( 'leave_manager_audit_logged', $wpdb->insert_id, $action, $entity_type, $entity_id );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get audit logs
	 *
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_audit_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'user_id' => null,
			'action' => null,
			'entity_type' => null,
			'entity_id' => null,
			'start_date' => null,
			'end_date' => null,
			'limit' => 100,
			'offset' => 0,
			'orderby' => 'created_at',
			'order' => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where_clauses = array( '1=1' );
		$prepare_args = array();

		if ( ! empty( $args['user_id'] ) ) {
			$where_clauses[] = 'user_id = %d';
			$prepare_args[] = $args['user_id'];
		}

		if ( ! empty( $args['action'] ) ) {
			$where_clauses[] = 'action = %s';
			$prepare_args[] = $args['action'];
		}

		if ( ! empty( $args['entity_type'] ) ) {
			$where_clauses[] = 'entity_type = %s';
			$prepare_args[] = $args['entity_type'];
		}

		if ( ! empty( $args['entity_id'] ) ) {
			$where_clauses[] = 'entity_id = %d';
			$prepare_args[] = $args['entity_id'];
		}

		if ( ! empty( $args['start_date'] ) ) {
			$where_clauses[] = 'created_at >= %s';
			$prepare_args[] = $args['start_date'];
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where_clauses[] = 'created_at <= %s';
			$prepare_args[] = $args['end_date'];
		}

		$where = implode( ' AND ', $where_clauses );
		$order = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';
		$orderby = sanitize_sql_orderby( $args['orderby'] ) ? $args['orderby'] : 'created_at';

		$query = "SELECT * FROM {$wpdb->prefix}leave_manager_audit_log WHERE {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$prepare_args[] = $args['limit'];
		$prepare_args[] = $args['offset'];

		$query = $wpdb->prepare( $query, $prepare_args );

		return $wpdb->get_results( $query );
	}

	/**
	 * Sanitize input
	 *
	 * @param mixed $input Input to sanitize
	 * @param string $type Input type
	 * @return mixed Sanitized input
	 */
	public function sanitize_input( $input, $type = 'text' ) {
		switch ( $type ) {
			case 'email':
				return sanitize_email( $input );
			case 'url':
				return esc_url( $input );
			case 'int':
				return intval( $input );
			case 'float':
				return floatval( $input );
			case 'date':
				return sanitize_text_field( $input );
			case 'json':
				return wp_json_encode( json_decode( $input, true ) );
			default:
				return sanitize_text_field( $input );
		}
	}

	/**
	 * Verify nonce
	 *
	 * @param string $nonce Nonce to verify
	 * @param string $action Action name
	 * @return bool
	 */
	public function verify_nonce( $nonce, $action = 'leave_manager_action' ) {
		return wp_verify_nonce( $nonce, $action ) !== false;
	}

	/**
	 * Create nonce
	 *
	 * @param string $action Action name
	 * @return string
	 */
	public function create_nonce( $action = 'leave_manager_action' ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Get client IP address
	 *
	 * @return string
	 */
	private function get_client_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} else {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	/**
	 * Get custom capabilities
	 *
	 * @return array
	 */
	public function get_capabilities() {
		return $this->capabilities;
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_security' ) ) {
	/**
	 * Get security framework instance
	 *
	 * @return Leave_Manager_Security_Framework
	 */
	function leave_manager_security() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Security_Framework();
		}

		return $instance;
	}
}
