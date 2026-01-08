<?php
/**
 * Approval Task Manager Class
 * Manages individual approval tasks and approver actions
 *
 * @package LeaveManager
 * @subpackage Approvals
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Approval_Task_Manager {

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
	 * Approval request manager instance
	 *
	 * @var Leave_Manager_Approval_Request_Manager
	 */
	private $approval_request_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->transaction_manager = leave_manager_transaction();
		$this->concurrency_control = leave_manager_concurrency();
		$this->security_framework = leave_manager_security();
		$this->approval_request_manager = leave_manager_approval_request();
	}

	/**
	 * Approve task
	 *
	 * @param int    $task_id Task ID
	 * @param int    $approver_id Approver user ID
	 * @param string $comments Optional comments
	 * @return bool|WP_Error
	 */
	public function approve_task( $task_id, $approver_id, $comments = '' ) {
		global $wpdb;

		// Get task
		$task = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_approval_tasks WHERE id = %d",
				$task_id
			)
		);

		if ( null === $task ) {
			return new WP_Error( 'not_found', 'Approval task not found' );
		}

		// Check if task is already completed
		if ( 'pending' !== $task->status ) {
			return new WP_Error( 'invalid_status', 'Task is already ' . $task->status );
		}

		// Check permission
		if ( ! $this->security_framework->can_approve_request( $approver_id, $task->approval_request_id ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to approve this task' );
		}

		// Acquire lock
		$lock = $this->concurrency_control->acquire_approval_lock( $task->approval_request_id, $approver_id );
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}

		try {
			$result = $this->transaction_manager->execute_transaction(
				function() use ( $wpdb, $task_id, $approver_id, $comments, $task ) {
					// Update task status
					$update_result = $wpdb->update(
						$wpdb->prefix . 'leave_manager_approval_tasks',
						array(
							'status' => 'approved',
							'approver_id' => $approver_id,
							'comments' => $comments,
							'updated_at' => current_time( 'mysql' ),
						),
						array( 'id' => $task_id ),
						array( '%s', '%d', '%s', '%s' ),
						array( '%d' )
					);

					if ( false === $update_result ) {
						return false;
					}

					// Log to approval audit
					$wpdb->insert(
						$wpdb->prefix . 'leave_manager_approval_audit',
						array(
							'approval_request_id' => $task->approval_request_id,
							'task_id' => $task_id,
							'action' => 'approved',
							'approver_id' => $approver_id,
							'comments' => $comments,
							'created_at' => current_time( 'mysql' ),
						),
						array( '%d', '%d', '%s', '%d', '%s', '%s' )
					);

					// Check if all tasks are approved (for sequential approval)
					$approval_request = $this->approval_request_manager->get_approval_request( $task->approval_request_id );

					if ( 'sequential' === $approval_request->approval_type ) {
						// Check if this is the last task
						$remaining_tasks = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_approval_tasks WHERE approval_request_id = %d AND status = 'pending'",
								$task->approval_request_id
							)
						);

						if ( 0 === intval( $remaining_tasks ) ) {
							// All tasks approved, update approval request status
							$wpdb->update(
								$wpdb->prefix . 'leave_manager_approval_requests',
								array(
									'status' => 'approved',
									'updated_at' => current_time( 'mysql' ),
									'completed_at' => current_time( 'mysql' ),
								),
								array( 'id' => $task->approval_request_id ),
								array( '%s', '%s', '%s' ),
								array( '%d' )
							);
						}
					} elseif ( 'parallel' === $approval_request->approval_type ) {
						// Check if all tasks are approved
						$total_tasks = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_approval_tasks WHERE approval_request_id = %d",
								$task->approval_request_id
							)
						);

						$approved_tasks = $wpdb->get_var(
							$wpdb->prepare(
								"SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_approval_tasks WHERE approval_request_id = %d AND status = 'approved'",
								$task->approval_request_id
							)
						);

						if ( intval( $total_tasks ) === intval( $approved_tasks ) ) {
							// All tasks approved
							$wpdb->update(
								$wpdb->prefix . 'leave_manager_approval_requests',
								array(
									'status' => 'approved',
									'updated_at' => current_time( 'mysql' ),
									'completed_at' => current_time( 'mysql' ),
								),
								array( 'id' => $task->approval_request_id ),
								array( '%s', '%s', '%s' ),
								array( '%d' )
							);
						}
					}

					return true;
				},
				'approve_task'
			);

			if ( false === $result ) {
				return new WP_Error( 'db_error', 'Failed to approve task' );
			}

			// Log audit event
			$this->security_framework->log_audit_event(
				'approve_task',
				'approval_task',
				$task_id,
				array( 'status' => 'pending' ),
				array( 'status' => 'approved', 'comments' => $comments )
			);

			do_action( 'leave_manager_task_approved', $task_id, $approver_id, $comments );

			return true;
		} finally {
			$this->concurrency_control->release_approval_lock( $task->approval_request_id );
		}
	}

	/**
	 * Reject task
	 *
	 * @param int    $task_id Task ID
	 * @param int    $approver_id Approver user ID
	 * @param string $reason Rejection reason
	 * @return bool|WP_Error
	 */
	public function reject_task( $task_id, $approver_id, $reason = '' ) {
		global $wpdb;

		// Get task
		$task = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_approval_tasks WHERE id = %d",
				$task_id
			)
		);

		if ( null === $task ) {
			return new WP_Error( 'not_found', 'Approval task not found' );
		}

		// Check if task is already completed
		if ( 'pending' !== $task->status ) {
			return new WP_Error( 'invalid_status', 'Task is already ' . $task->status );
		}

		// Check permission
		if ( ! $this->security_framework->can_approve_request( $approver_id, $task->approval_request_id ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to reject this task' );
		}

		// Acquire lock
		$lock = $this->concurrency_control->acquire_approval_lock( $task->approval_request_id, $approver_id );
		if ( is_wp_error( $lock ) ) {
			return $lock;
		}

		try {
			$result = $this->transaction_manager->execute_transaction(
				function() use ( $wpdb, $task_id, $approver_id, $reason, $task ) {
					// Update task status
					$update_result = $wpdb->update(
						$wpdb->prefix . 'leave_manager_approval_tasks',
						array(
							'status' => 'rejected',
							'approver_id' => $approver_id,
							'comments' => $reason,
							'updated_at' => current_time( 'mysql' ),
						),
						array( 'id' => $task_id ),
						array( '%s', '%d', '%s', '%s' ),
						array( '%d' )
					);

					if ( false === $update_result ) {
						return false;
					}

					// Log to approval audit
					$wpdb->insert(
						$wpdb->prefix . 'leave_manager_approval_audit',
						array(
							'approval_request_id' => $task->approval_request_id,
							'task_id' => $task_id,
							'action' => 'rejected',
							'approver_id' => $approver_id,
							'comments' => $reason,
							'created_at' => current_time( 'mysql' ),
						),
						array( '%d', '%d', '%s', '%d', '%s', '%s' )
					);

					// Update approval request status to rejected
					$wpdb->update(
						$wpdb->prefix . 'leave_manager_approval_requests',
						array(
							'status' => 'rejected',
							'updated_at' => current_time( 'mysql' ),
							'completed_at' => current_time( 'mysql' ),
						),
						array( 'id' => $task->approval_request_id ),
						array( '%s', '%s', '%s' ),
						array( '%d' )
					);

					return true;
				},
				'reject_task'
			);

			if ( false === $result ) {
				return new WP_Error( 'db_error', 'Failed to reject task' );
			}

			// Log audit event
			$this->security_framework->log_audit_event(
				'reject_task',
				'approval_task',
				$task_id,
				array( 'status' => 'pending' ),
				array( 'status' => 'rejected', 'reason' => $reason )
			);

			do_action( 'leave_manager_task_rejected', $task_id, $approver_id, $reason );

			return true;
		} finally {
			$this->concurrency_control->release_approval_lock( $task->approval_request_id );
		}
	}

	/**
	 * Get task
	 *
	 * @param int $task_id Task ID
	 * @return object|null
	 */
	public function get_task( $task_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_approval_tasks WHERE id = %d",
				$task_id
			)
		);
	}

	/**
	 * Get tasks for approval request
	 *
	 * @param int $approval_request_id Approval request ID
	 * @return array
	 */
	public function get_tasks_for_approval( $approval_request_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_approval_tasks WHERE approval_request_id = %d ORDER BY sequence_order ASC",
				$approval_request_id
			)
		);
	}

	/**
	 * Get pending tasks for approver
	 *
	 * @param int $approver_id Approver user ID
	 * @return array
	 */
	public function get_pending_tasks_for_approver( $approver_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT at.*, ar.leave_request_id, ar.priority, ar.created_at as request_created_at
				FROM {$wpdb->prefix}leave_manager_approval_tasks at
				JOIN {$wpdb->prefix}leave_manager_approval_requests ar ON at.approval_request_id = ar.id
				WHERE at.approver_id = %d AND at.status = 'pending'
				ORDER BY ar.priority DESC, ar.created_at ASC",
				$approver_id
			)
		);
	}

	/**
	 * Reassign task to another approver
	 *
	 * @param int $task_id Task ID
	 * @param int $new_approver_id New approver user ID
	 * @return bool|WP_Error
	 */
	public function reassign_task( $task_id, $new_approver_id ) {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to reassign tasks' );
		}

		$task = $this->get_task( $task_id );

		if ( null === $task ) {
			return new WP_Error( 'not_found', 'Task not found' );
		}

		if ( 'pending' !== $task->status ) {
			return new WP_Error( 'invalid_status', 'Only pending tasks can be reassigned' );
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'leave_manager_approval_tasks',
			array(
				'approver_id' => $new_approver_id,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $task_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		if ( false !== $result ) {
			// Log audit event
			$this->security_framework->log_audit_event(
				'reassign_task',
				'approval_task',
				$task_id,
				array( 'approver_id' => $task->approver_id ),
				array( 'approver_id' => $new_approver_id )
			);

			do_action( 'leave_manager_task_reassigned', $task_id, $task->approver_id, $new_approver_id );
			return true;
		}

		return new WP_Error( 'db_error', 'Failed to reassign task' );
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_approval_task' ) ) {
	/**
	 * Get approval task manager instance
	 *
	 * @return Leave_Manager_Approval_Task_Manager
	 */
	function leave_manager_approval_task() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Approval_Task_Manager();
		}

		return $instance;
	}
}
