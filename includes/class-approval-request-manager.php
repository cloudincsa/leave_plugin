<?php
/**
 * Approval Request Manager Class
 * Manages approval workflow creation, tracking, and completion
 *
 * @package LeaveManager
 * @subpackage Approvals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Approval_Request_Manager {

	/**
	 * Transaction manager instance
	 *
	 * @var Leave_Manager_Transaction_Manager
	 */
	private $transaction_manager;

	/**
	 * Concurrency control instance
	 *
	 * @var Leave_Manager_Concurrency_Control
	 */
	private $concurrency_control;

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
		$this->concurrency_control = leave_manager_concurrency();
		$this->security_framework = leave_manager_security();
	}

	/**
	 * Create approval request
	 *
	 * @param int   $leave_request_id Leave request ID
	 * @param array $approvers Array of approver user IDs
	 * @param array $config Approval configuration
	 * @return int|WP_Error Approval request ID or error
	 */
	public function create_approval_request( $leave_request_id, $approvers, $config = array() ) {
		global $wpdb;

		// Validate inputs
		if ( empty( $leave_request_id ) || empty( $approvers ) ) {
			return new WP_Error( 'invalid_input', 'Leave request ID and approvers are required' );
		}

		// Check if leave request exists
		$leave_request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests WHERE id = %d",
				$leave_request_id
			)
		);

		if ( null === $leave_request ) {
			return new WP_Error( 'not_found', 'Leave request not found' );
		}

		// Set default configuration
		$defaults = array(
			'approval_type' => 'sequential', // sequential or parallel
			'auto_approve_on_all_approved' => true,
			'auto_reject_on_any_rejected' => true,
			'require_comments' => false,
			'escalation_days' => 0,
			'priority' => 'normal', // low, normal, high
		);

		$config = wp_parse_args( $config, $defaults );

		// Create approval request within transaction
		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $leave_request_id, $approvers, $config ) {
				$insert_result = $wpdb->insert(
					$wpdb->prefix . 'leave_manager_approval_requests',
					array(
						'leave_request_id' => $leave_request_id,
						'approval_type' => $config['approval_type'],
						'status' => 'pending',
						'priority' => $config['priority'],
						'require_comments' => $config['require_comments'] ? 1 : 0,
						'auto_approve_on_all' => $config['auto_approve_on_all_approved'] ? 1 : 0,
						'auto_reject_on_any' => $config['auto_reject_on_any_rejected'] ? 1 : 0,
						'escalation_days' => $config['escalation_days'],
						'created_by' => get_current_user_id(),
						'created_at' => current_time( 'mysql' ),
						'updated_at' => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s' )
				);

				if ( false === $insert_result ) {
					return false;
				}

				$approval_request_id = $wpdb->insert_id;

				// Create approval tasks for each approver
				foreach ( $approvers as $index => $approver_id ) {
					$task_result = $wpdb->insert(
						$wpdb->prefix . 'leave_manager_approval_tasks',
						array(
							'approval_request_id' => $approval_request_id,
							'approver_id' => $approver_id,
							'sequence_order' => $index + 1,
							'status' => 'pending',
							'created_at' => current_time( 'mysql' ),
							'updated_at' => current_time( 'mysql' ),
						),
						array( '%d', '%d', '%d', '%s', '%s', '%s' )
					);

					if ( false === $task_result ) {
						return false;
					}
				}

				return $approval_request_id;
			},
			'create_approval_request'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to create approval request' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'create_approval_request',
			'approval_request',
			$result,
			array(),
			array(
				'leave_request_id' => $leave_request_id,
				'approvers_count' => count( $approvers ),
				'approval_type' => $config['approval_type'],
			)
		);

		do_action( 'leave_manager_approval_request_created', $result, $leave_request_id, $approvers );

		return $result;
	}

	/**
	 * Get approval request
	 *
	 * @param int $approval_request_id Approval request ID
	 * @return object|null
	 */
	public function get_approval_request( $approval_request_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_approval_requests WHERE id = %d",
				$approval_request_id
			)
		);
	}

	/**
	 * Get approval request status
	 *
	 * @param int $approval_request_id Approval request ID
	 * @return array
	 */
	public function get_approval_status( $approval_request_id ) {
		global $wpdb;

		$approval_request = $this->get_approval_request( $approval_request_id );

		if ( null === $approval_request ) {
			return array();
		}

		$tasks = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_approval_tasks WHERE approval_request_id = %d ORDER BY sequence_order ASC",
				$approval_request_id
			)
		);

		$status = array(
			'approval_request_id' => $approval_request_id,
			'overall_status' => $approval_request->status,
			'approval_type' => $approval_request->approval_type,
			'tasks' => array(),
			'completed_count' => 0,
			'pending_count' => 0,
			'rejected_count' => 0,
			'approved_count' => 0,
		);

		foreach ( $tasks as $task ) {
			$user = get_userdata( $task->approver_id );

			$task_info = array(
				'task_id' => $task->id,
				'approver_id' => $task->approver_id,
				'approver_name' => $user ? $user->display_name : 'Unknown',
				'status' => $task->status,
				'sequence_order' => $task->sequence_order,
				'comments' => $task->comments,
				'created_at' => $task->created_at,
				'updated_at' => $task->updated_at,
			);

			$status['tasks'][] = $task_info;

			// Count statuses
			if ( 'approved' === $task->status ) {
				$status['approved_count']++;
			} elseif ( 'rejected' === $task->status ) {
				$status['rejected_count']++;
			} elseif ( 'pending' === $task->status ) {
				$status['pending_count']++;
			}

			$status['completed_count'] += ( 'pending' !== $task->status ) ? 1 : 0;
		}

		return $status;
	}

	/**
	 * Update approval request status
	 *
	 * @param int    $approval_request_id Approval request ID
	 * @param string $status New status
	 * @param array  $data Additional data
	 * @return bool|WP_Error
	 */
	public function update_approval_status( $approval_request_id, $status, $data = array() ) {
		global $wpdb;

		// Validate status
		$valid_statuses = array( 'pending', 'approved', 'rejected', 'escalated', 'cancelled' );
		if ( ! in_array( $status, $valid_statuses, true ) ) {
			return new WP_Error( 'invalid_status', 'Invalid approval status' );
		}

		$update_data = array(
			'status' => $status,
			'updated_at' => current_time( 'mysql' ),
		);

		if ( ! empty( $data['completed_at'] ) ) {
			$update_data['completed_at'] = $data['completed_at'];
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'leave_manager_approval_requests',
			$update_data,
			array( 'id' => $approval_request_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( false !== $result ) {
			// Log audit event
			$this->security_framework->log_audit_event(
				'update_approval_status',
				'approval_request',
				$approval_request_id,
				array( 'status' => 'pending' ),
				array( 'status' => $status )
			);

			do_action( 'leave_manager_approval_status_updated', $approval_request_id, $status );
			return true;
		}

		return new WP_Error( 'db_error', 'Failed to update approval status' );
	}

	/**
	 * Get approval requests for leave request
	 *
	 * @param int $leave_request_id Leave request ID
	 * @return array
	 */
	public function get_approval_requests_for_leave( $leave_request_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_approval_requests WHERE leave_request_id = %d ORDER BY created_at DESC",
				$leave_request_id
			)
		);
	}

	/**
	 * Get pending approvals for user
	 *
	 * @param int $user_id User ID
	 * @param array $args Query arguments
	 * @return array
	 */
	public function get_pending_approvals_for_user( $user_id, $args = array() ) {
		global $wpdb;

		$defaults = array(
			'limit' => 50,
			'offset' => 0,
			'orderby' => 'created_at',
			'order' => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$query = $wpdb->prepare(
			"SELECT ar.*, lt.employee_id, lt.start_date, lt.end_date 
			FROM {$wpdb->prefix}leave_manager_approval_requests ar
			JOIN {$wpdb->prefix}leave_manager_approval_tasks at ON ar.id = at.approval_request_id
			JOIN {$wpdb->prefix}leave_manager_leave_requests lt ON ar.leave_request_id = lt.id
			WHERE at.approver_id = %d AND at.status = 'pending'
			ORDER BY ar.{$args['orderby']} {$args['order']}
			LIMIT %d OFFSET %d",
			$user_id,
			$args['limit'],
			$args['offset']
		);

		return $wpdb->get_results( $query );
	}

	/**
	 * Cancel approval request
	 *
	 * @param int $approval_request_id Approval request ID
	 * @return bool|WP_Error
	 */
	public function cancel_approval_request( $approval_request_id ) {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to cancel approvals' );
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $approval_request_id ) {
				// Update approval request status
				$update_result = $wpdb->update(
					$wpdb->prefix . 'leave_manager_approval_requests',
					array(
						'status' => 'cancelled',
						'updated_at' => current_time( 'mysql' ),
					),
					array( 'id' => $approval_request_id ),
					array( '%s', '%s' ),
					array( '%d' )
				);

				if ( false === $update_result ) {
					return false;
				}

				// Cancel all pending tasks
				$task_result = $wpdb->update(
					$wpdb->prefix . 'leave_manager_approval_tasks',
					array(
						'status' => 'cancelled',
						'updated_at' => current_time( 'mysql' ),
					),
					array(
						'approval_request_id' => $approval_request_id,
						'status' => 'pending',
					),
					array( '%s', '%s' ),
					array( '%d', '%s' )
				);

				return true;
			},
			'cancel_approval_request'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to cancel approval request' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'cancel_approval_request',
			'approval_request',
			$approval_request_id,
			array( 'status' => 'pending' ),
			array( 'status' => 'cancelled' )
		);

		do_action( 'leave_manager_approval_request_cancelled', $approval_request_id );

		return true;
	}

	/**
	 * Check if all approvals are complete
	 *
	 * @param int $approval_request_id Approval request ID
	 * @return bool
	 */
	public function is_approval_complete( $approval_request_id ) {
		global $wpdb;

		$pending_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_approval_tasks WHERE approval_request_id = %d AND status = 'pending'",
				$approval_request_id
			)
		);

		return 0 === intval( $pending_count );
	}

	/**
	 * Get approval request history
	 *
	 * @param int $approval_request_id Approval request ID
	 * @return array
	 */
	public function get_approval_history( $approval_request_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_approval_audit WHERE approval_request_id = %d ORDER BY created_at DESC",
				$approval_request_id
			)
		);
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_approval_request' ) ) {
	/**
	 * Get approval request manager instance
	 *
	 * @return Leave_Manager_Approval_Request_Manager
	 */
	function leave_manager_approval_request() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Approval_Request_Manager();
		}

		return $instance;
	}
}
