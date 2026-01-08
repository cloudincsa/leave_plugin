<?php
/**
 * Approval Delegation Manager Class
 * Manages temporary approval delegation when approvers are unavailable
 *
 * @package LeaveManager
 * @subpackage Approvals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Approval_Delegation_Manager {

	/**
	 * Transaction manager instance
	 *
	 * @var Leave_Manager_Transaction_Manager
	 */
	private $transaction_manager;

	/**
	 * Security framework instance
	 *
	 * @var Leave_Manager_Security_Framework
	 */
	private $security_framework;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->transaction_manager = leave_manager_transaction();
		$this->security_framework = leave_manager_security();
	}

	/**
	 * Create delegation
	 *
	 * @param int    $from_user_id User delegating approvals
	 * @param int    $to_user_id User receiving delegated approvals
	 * @param string $start_date Delegation start date (Y-m-d)
	 * @param string $end_date Delegation end date (Y-m-d)
	 * @param array  $config Delegation configuration
	 * @return int|WP_Error Delegation ID or error
	 */
	public function create_delegation( $from_user_id, $to_user_id, $start_date, $end_date, $config = array() ) {
		global $wpdb;

		// Validate inputs
		if ( empty( $from_user_id ) || empty( $to_user_id ) ) {
			return new WP_Error( 'invalid_input', 'From user and to user are required' );
		}

		if ( $from_user_id === $to_user_id ) {
			return new WP_Error( 'invalid_input', 'Cannot delegate to yourself' );
		}

		// Validate dates
		$start_timestamp = strtotime( $start_date );
		$end_timestamp = strtotime( $end_date );

		if ( false === $start_timestamp || false === $end_timestamp ) {
			return new WP_Error( 'invalid_date', 'Invalid date format' );
		}

		if ( $start_timestamp > $end_timestamp ) {
			return new WP_Error( 'invalid_date', 'Start date must be before end date' );
		}

		// Check if users exist
		if ( ! get_userdata( $from_user_id ) || ! get_userdata( $to_user_id ) ) {
			return new WP_Error( 'user_not_found', 'One or both users not found' );
		}

		// Check permission
		if ( get_current_user_id() !== $from_user_id && ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to create delegations' );
		}

		// Set default configuration
		$defaults = array(
			'include_pending_only' => true,
			'auto_approve' => false,
			'notify_from_user' => true,
			'notify_to_user' => true,
		);

		$config = wp_parse_args( $config, $defaults );

		// Create delegation within transaction
		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $from_user_id, $to_user_id, $start_date, $end_date, $config ) {
				// Check for overlapping delegations
				$existing = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_approval_delegations 
						WHERE from_user_id = %d AND status = 'active'
						AND NOT (end_date < %s OR start_date > %s)",
						$from_user_id,
						$start_date,
						$end_date
					)
				);

				if ( intval( $existing ) > 0 ) {
					return false;
				}

				// Create delegation
				$insert_result = $wpdb->insert(
					$wpdb->prefix . 'leave_manager_approval_delegations',
					array(
						'from_user_id' => $from_user_id,
						'to_user_id' => $to_user_id,
						'start_date' => $start_date,
						'end_date' => $end_date,
						'status' => 'active',
						'include_pending_only' => $config['include_pending_only'] ? 1 : 0,
						'auto_approve' => $config['auto_approve'] ? 1 : 0,
						'created_by' => get_current_user_id(),
						'created_at' => current_time( 'mysql' ),
						'updated_at' => current_time( 'mysql' ),
					),
					array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
				);

				return $insert_result ? $wpdb->insert_id : false;
			},
			'create_delegation'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to create delegation' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'create_delegation',
			'delegation',
			$result,
			array(),
			array(
				'from_user_id' => $from_user_id,
				'to_user_id' => $to_user_id,
				'start_date' => $start_date,
				'end_date' => $end_date,
			)
		);

		// Send notifications
		if ( $config['notify_from_user'] ) {
			$this->send_delegation_notification( $from_user_id, 'created', $result );
		}

		if ( $config['notify_to_user'] ) {
			$this->send_delegation_notification( $to_user_id, 'received', $result );
		}

		do_action( 'leave_manager_delegation_created', $result, $from_user_id, $to_user_id );

		return $result;
	}

	/**
	 * Get delegation
	 *
	 * @param int $delegation_id Delegation ID
	 * @return object|null
	 */
	public function get_delegation( $delegation_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_approval_delegations WHERE id = %d",
				$delegation_id
			)
		);
	}

	/**
	 * Get active delegations for user
	 *
	 * @param int $user_id User ID
	 * @return array
	 */
	public function get_active_delegations_for_user( $user_id ) {
		global $wpdb;

		$today = current_time( 'Y-m-d' );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_approval_delegations 
				WHERE from_user_id = %d AND status = 'active' 
				AND start_date <= %s AND end_date >= %s
				ORDER BY end_date ASC",
				$user_id,
				$today,
				$today
			)
		);
	}

	/**
	 * Get delegated approvals for user
	 *
	 * @param int $user_id User ID (delegate)
	 * @return array
	 */
	public function get_delegated_approvals_for_user( $user_id ) {
		global $wpdb;

		$today = current_time( 'Y-m-d' );

		$delegations = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_approval_delegations 
				WHERE to_user_id = %d AND status = 'active'
				AND start_date <= %s AND end_date >= %s",
				$user_id,
				$today,
				$today
			)
		);

		$approvals = array();

		foreach ( $delegations as $delegation ) {
			$query = "SELECT at.*, ar.leave_request_id, ar.priority
				FROM {$wpdb->prefix}leave_manager_approval_tasks at
				JOIN {$wpdb->prefix}leave_manager_approval_requests ar ON at.approval_request_id = ar.id
				WHERE at.approver_id = %d AND at.status = 'pending'";

			if ( $delegation->include_pending_only ) {
				$query .= " AND ar.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
			}

			$query .= " ORDER BY ar.priority DESC, ar.created_at ASC";

			$delegated = $wpdb->get_results(
				$wpdb->prepare( $query, $delegation->from_user_id )
			);

			foreach ( $delegated as $task ) {
				$task->delegation_id = $delegation->id;
				$task->delegated_from = $delegation->from_user_id;
				$approvals[] = $task;
			}
		}

		return $approvals;
	}

	/**
	 * Revoke delegation
	 *
	 * @param int $delegation_id Delegation ID
	 * @return bool|WP_Error
	 */
	public function revoke_delegation( $delegation_id ) {
		global $wpdb;

		$delegation = $this->get_delegation( $delegation_id );

		if ( null === $delegation ) {
			return new WP_Error( 'not_found', 'Delegation not found' );
		}

		// Check permission
		if ( get_current_user_id() !== $delegation->from_user_id && ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to revoke this delegation' );
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'leave_manager_approval_delegations',
			array(
				'status' => 'revoked',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $delegation_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false !== $result ) {
			// Log audit event
			$this->security_framework->log_audit_event(
				'revoke_delegation',
				'delegation',
				$delegation_id,
				array( 'status' => 'active' ),
				array( 'status' => 'revoked' )
			);

			do_action( 'leave_manager_delegation_revoked', $delegation_id );
			return true;
		}

		return new WP_Error( 'db_error', 'Failed to revoke delegation' );
	}

	/**
	 * Get delegations for user
	 *
	 * @param int $user_id User ID
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_delegations_for_user( $user_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status' => null,
			'limit' => 50,
			'offset' => 0,
			'orderby' => 'created_at',
			'order' => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = "from_user_id = %d";
		$prepare_args = array( $user_id );

		if ( ! empty( $args['status'] ) ) {
			$where .= " AND status = %s";
			$prepare_args[] = $args['status'];
		}

		$order = 'DESC' === strtoupper( $args['order'] ) ? 'DESC' : 'ASC';
		$orderby = sanitize_sql_orderby( $args['orderby'] ) ? $args['orderby'] : 'created_at';

		$query = "SELECT * FROM {$wpdb->prefix}leave_manager_approval_delegations 
			WHERE {$where} 
			ORDER BY {$orderby} {$order} 
			LIMIT %d OFFSET %d";

		$prepare_args[] = $args['limit'];
		$prepare_args[] = $args['offset'];

		$query = $wpdb->prepare( $query, $prepare_args );

		return $wpdb->get_results( $query );
	}

	/**
	 * Send delegation notification
	 *
	 * @param int    $user_id User ID
	 * @param string $type Notification type (created, received, revoked)
	 * @param int    $delegation_id Delegation ID
	 * @return bool
	 */
	private function send_delegation_notification( $user_id, $type, $delegation_id ) {
		$delegation = $this->get_delegation( $delegation_id );

		if ( null === $delegation ) {
			return false;
		}

		$user = get_userdata( $user_id );
		$from_user = get_userdata( $delegation->from_user_id );
		$to_user = get_userdata( $delegation->to_user_id );

		$subject = '';
		$message = '';

		switch ( $type ) {
			case 'created':
				$subject = 'Leave Approval Delegation Created';
				$message = sprintf(
					'You have delegated your leave approvals to %s from %s to %s.',
					$to_user->display_name,
					$delegation->start_date,
					$delegation->end_date
				);
				break;

			case 'received':
				$subject = 'Leave Approval Delegation Received';
				$message = sprintf(
					'You have received delegated leave approvals from %s from %s to %s.',
					$from_user->display_name,
					$delegation->start_date,
					$delegation->end_date
				);
				break;

			case 'revoked':
				$subject = 'Leave Approval Delegation Revoked';
				$message = sprintf(
					'Your leave approval delegation from %s has been revoked.',
					$from_user->display_name
				);
				break;
		}

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		return wp_mail( $user->user_email, $subject, $message, $headers );
	}

	/**
	 * Clean up expired delegations
	 *
	 * @return int Number of delegations updated
	 */
	public function cleanup_expired_delegations() {
		global $wpdb;

		$today = current_time( 'Y-m-d' );

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}leave_manager_approval_delegations 
				SET status = 'expired' 
				WHERE status = 'active' AND end_date < %s",
				$today
			)
		);

		return $result ? $result : 0;
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_approval_delegation' ) ) {
	/**
	 * Get approval delegation manager instance
	 *
	 * @return Leave_Manager_Approval_Delegation_Manager
	 */
	function leave_manager_approval_delegation() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Approval_Delegation_Manager();
		}

		return $instance;
	}
}
