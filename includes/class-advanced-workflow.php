<?php
/**
 * Advanced Leave Request Workflow class for Leave Manager Plugin
 *
 * Handles multi-level approval workflow, approval chains, and delegations.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Advanced_Workflow class
 */
class Leave_Manager_Advanced_Workflow {

	/**
	 * Database instance
	 *
	 * @var Leave_Manager_Database
	 */
	private $db;

	/**
	 * Logger instance
	 *
	 * @var Leave_Manager_Logger
	 */
	private $logger;

	/**
	 * Workflow table name
	 *
	 * @var string
	 */
	private $workflow_table;

	/**
	 * Approvals table name
	 *
	 * @var string
	 */
	private $approvals_table;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 */
	public function __construct( $db, $logger ) {
		global $wpdb;
		$this->db               = $db;
		$this->logger           = $logger;
		$this->workflow_table   = $wpdb->prefix . 'leave_manager_approval_workflows';
		$this->approvals_table  = $wpdb->prefix . 'leave_manager_approvals';
	}

	/**
	 * Create approval workflow
	 *
	 * @param array $workflow_data Workflow data
	 * @return int|false Workflow ID or false on failure
	 */
	public function create_workflow( $workflow_data ) {
		global $wpdb;

		$workflow = array(
			'workflow_name'  => sanitize_text_field( $workflow_data['workflow_name'] ),
			'description'    => sanitize_textarea_field( $workflow_data['description'] ?? '' ),
			'leave_type'     => sanitize_text_field( $workflow_data['leave_type'] ),
			'approval_chain' => wp_json_encode( $workflow_data['approval_chain'] ?? array() ),
			'status'         => 'active',
			'created_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( $this->workflow_table, $workflow );

		if ( $result ) {
			$this->logger->info( 'Approval workflow created', array( 'workflow_id' => $wpdb->insert_id ) );
			return $wpdb->insert_id;
		} else {
			$this->logger->error( 'Workflow creation failed', array( 'error' => $wpdb->last_error ) );
			return false;
		}
	}

	/**
	 * Get workflow by ID
	 *
	 * @param int $workflow_id Workflow ID
	 * @return object|null Workflow object or null
	 */
	public function get_workflow( $workflow_id ) {
		global $wpdb;

		$workflow_id = intval( $workflow_id );

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->workflow_table} WHERE workflow_id = %d",
				$workflow_id
			)
		);
	}

	/**
	 * Get workflow for leave type
	 *
	 * @param string $leave_type Leave type
	 * @return object|null Workflow object or null
	 */
	public function get_workflow_by_leave_type( $leave_type ) {
		global $wpdb;

		$leave_type = sanitize_text_field( $leave_type );

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->workflow_table} WHERE leave_type = %s AND status = 'active'",
				$leave_type
			)
		);
	}

	/**
	 * Submit leave request for approval
	 *
	 * @param int   $request_id Leave request ID
	 * @param int   $workflow_id Workflow ID
	 * @return bool True on success
	 */
	public function submit_for_approval( $request_id, $workflow_id ) {
		global $wpdb;

		$request_id  = intval( $request_id );
		$workflow_id = intval( $workflow_id );

		// Get workflow
		$workflow = $this->get_workflow( $workflow_id );
		if ( ! $workflow ) {
			return false;
		}

		// Get approval chain
		$approval_chain = json_decode( $workflow->approval_chain, true );

		if ( empty( $approval_chain ) ) {
			// No approval chain, auto-approve
			return $this->auto_approve_request( $request_id );
		}

		// Create approval records for each level
		$level = 1;
		foreach ( $approval_chain as $approver_id ) {
			$approval = array(
				'request_id'   => $request_id,
				'workflow_id'  => $workflow_id,
				'approver_id'  => intval( $approver_id ),
				'approval_level' => $level,
				'status'       => 'pending',
				'created_at'   => current_time( 'mysql' ),
			);

			$wpdb->insert( $this->approvals_table, $approval );
			$level++;
		}

		$this->logger->info( 'Leave request submitted for approval', array( 'request_id' => $request_id ) );
		return true;
	}

	/**
	 * Get pending approvals for user
	 *
	 * @param int $user_id Approver user ID
	 * @return array Array of approval objects
	 */
	public function get_pending_approvals( $user_id ) {
		global $wpdb;

		$user_id = intval( $user_id );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, r.*, u.first_name, u.last_name, u.email
				 FROM {$this->approvals_table} a
				 INNER JOIN {$wpdb->prefix}leave_manager_leave_requests r ON a.request_id = r.request_id
				 INNER JOIN {$wpdb->prefix}leave_manager_leave_users u ON r.user_id = u.user_id
				 WHERE a.approver_id = %d AND a.status = 'pending'
				 ORDER BY a.created_at DESC",
				$user_id
			)
		);
	}

	/**
	 * Approve request at current level
	 *
	 * @param int    $approval_id Approval ID
	 * @param int    $approver_id Approver user ID
	 * @param string $comments Optional comments
	 * @return bool True on success
	 */
	public function approve_at_level( $approval_id, $approver_id, $comments = '' ) {
		global $wpdb;

		$approval_id = intval( $approval_id );
		$approver_id = intval( $approver_id );

		// Get approval
		$approval = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->approvals_table} WHERE approval_id = %d",
				$approval_id
			)
		);

		if ( ! $approval || intval( $approval->approver_id ) !== $approver_id ) {
			return false;
		}

		// Update approval status
		$wpdb->update(
			$this->approvals_table,
			array(
				'status'     => 'approved',
				'comments'   => sanitize_textarea_field( $comments ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'approval_id' => $approval_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		// Check if all approvals at this level are done
		$pending_at_level = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->approvals_table}
				 WHERE request_id = %d AND approval_level = %d AND status = 'pending'",
				$approval->request_id,
				$approval->approval_level
			)
		);

		if ( intval( $pending_at_level ) === 0 ) {
			// All approvals at this level are done, move to next level
			$next_level_pending = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$this->approvals_table}
					 WHERE request_id = %d AND approval_level > %d",
					$approval->request_id,
					$approval->approval_level
				)
			);

			if ( intval( $next_level_pending ) === 0 ) {
				// All approvals done, auto-approve request
				$this->auto_approve_request( $approval->request_id );
			}
		}

		$this->logger->info( 'Approval granted', array( 'approval_id' => $approval_id, 'approver_id' => $approver_id ) );
		return true;
	}

	/**
	 * Reject request at current level
	 *
	 * @param int    $approval_id Approval ID
	 * @param int    $approver_id Approver user ID
	 * @param string $reason Rejection reason
	 * @return bool True on success
	 */
	public function reject_at_level( $approval_id, $approver_id, $reason = '' ) {
		global $wpdb;

		$approval_id = intval( $approval_id );
		$approver_id = intval( $approver_id );

		// Get approval
		$approval = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->approvals_table} WHERE approval_id = %d",
				$approval_id
			)
		);

		if ( ! $approval || intval( $approval->approver_id ) !== $approver_id ) {
			return false;
		}

		// Update approval status
		$wpdb->update(
			$this->approvals_table,
			array(
				'status'     => 'rejected',
				'comments'   => sanitize_textarea_field( $reason ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'approval_id' => $approval_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		// Reject all pending approvals for this request
		$wpdb->update(
			$this->approvals_table,
			array(
				'status'     => 'rejected',
				'comments'   => 'Rejected at earlier level',
				'updated_at' => current_time( 'mysql' ),
			),
			array(
				'request_id' => $approval->request_id,
				'approval_level' => array( '>', $approval->approval_level ),
			)
		);

		// Update leave request status
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
		$wpdb->update(
			$requests_table,
			array(
				'status'            => 'rejected',
				'rejection_reason'  => sanitize_textarea_field( $reason ),
				'updated_at'        => current_time( 'mysql' ),
			),
			array( 'request_id' => $approval->request_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		$this->logger->info( 'Request rejected', array( 'approval_id' => $approval_id, 'approver_id' => $approver_id ) );
		return true;
	}

	/**
	 * Auto-approve leave request
	 *
	 * @param int $request_id Leave request ID
	 * @return bool True on success
	 */
	private function auto_approve_request( $request_id ) {
		global $wpdb;

		$request_id = intval( $request_id );
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		$result = $wpdb->update(
			$requests_table,
			array(
				'status'       => 'approved',
				'approved_by'  => get_current_user_id(),
				'approval_date' => current_time( 'mysql' ),
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'request_id' => $request_id ),
			array( '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			$this->logger->info( 'Leave request auto-approved', array( 'request_id' => $request_id ) );
			return true;
		}

		return false;
	}

	/**
	 * Delegate approval to another user
	 *
	 * @param int $approval_id Approval ID
	 * @param int $current_approver_id Current approver ID
	 * @param int $new_approver_id New approver ID
	 * @return bool True on success
	 */
	public function delegate_approval( $approval_id, $current_approver_id, $new_approver_id ) {
		global $wpdb;

		$approval_id          = intval( $approval_id );
		$current_approver_id  = intval( $current_approver_id );
		$new_approver_id      = intval( $new_approver_id );

		// Get approval
		$approval = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->approvals_table} WHERE approval_id = %d",
				$approval_id
			)
		);

		if ( ! $approval || intval( $approval->approver_id ) !== $current_approver_id ) {
			return false;
		}

		// Update approver
		$result = $wpdb->update(
			$this->approvals_table,
			array(
				'approver_id' => $new_approver_id,
				'updated_at'  => current_time( 'mysql' ),
			),
			array( 'approval_id' => $approval_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			$this->logger->info( 'Approval delegated', array( 'approval_id' => $approval_id, 'from' => $current_approver_id, 'to' => $new_approver_id ) );
			return true;
		}

		return false;
	}

	/**
	 * Get approval history for request
	 *
	 * @param int $request_id Leave request ID
	 * @return array Array of approval records
	 */
	public function get_approval_history( $request_id ) {
		global $wpdb;

		$request_id = intval( $request_id );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT a.*, u.first_name, u.last_name, u.email
				 FROM {$this->approvals_table} a
				 LEFT JOIN {$wpdb->prefix}leave_manager_leave_users u ON a.approver_id = u.user_id
				 WHERE a.request_id = %d
				 ORDER BY a.approval_level ASC, a.created_at ASC",
				$request_id
			)
		);
	}
}
