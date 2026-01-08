<?php
/**
 * Department Approval System class for Leave Manager Plugin
 *
 * Handles multi-level approval workflow with department managers
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Department_Approval_System class
 */
class Leave_Manager_Department_Approval_System {

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
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 */
	public function __construct( $db, $logger ) {
		$this->db     = $db;
		$this->logger = $logger;

		// Register AJAX handlers
		add_action( 'wp_ajax_leave_manager_get_pending_approvals', array( $this, 'get_pending_approvals' ) );
		add_action( 'wp_ajax_leave_manager_approve_request_by_manager', array( $this, 'approve_request_by_manager' ) );
		add_action( 'wp_ajax_leave_manager_reject_request_by_manager', array( $this, 'reject_request_by_manager' ) );
	}

	/**
	 * Create approval for department manager
	 *
	 * @param int    $request_id Request ID
	 * @param int    $manager_id Manager user ID
	 * @param string $leave_type Leave type
	 * @return int|false Approval ID or false
	 */
	public function create_manager_approval( $request_id, $manager_id, $leave_type = 'annual' ) {
		global $wpdb;

		// Get or create workflow for this leave type
		$workflow = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_approval_workflows 
			 WHERE leave_type = %s AND status = 'active'",
			$leave_type
		) );

		$workflow_id = $workflow ? $workflow->workflow_id : 0;

		// Create approval record for Level 1 (Department Manager)
		$approval_data = array(
			'request_id'     => $request_id,
			'workflow_id'    => $workflow_id,
			'approver_id'    => $manager_id,
			'approval_level' => 1,
			'status'         => 'pending',
			'created_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
		);

		$result = $wpdb->insert(
			"{$wpdb->prefix}leave_manager_approvals",
			$approval_data
		);

		if ( $result ) {
			$this->logger->info( 'Manager approval created', array(
				'request_id' => $request_id,
				'manager_id' => $manager_id,
				'approval_id' => $wpdb->insert_id,
			) );

			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Create final admin approval
	 *
	 * @param int $request_id Request ID
	 * @param int $admin_id Admin user ID
	 * @return int|false Approval ID or false
	 */
	public function create_admin_approval( $request_id, $admin_id ) {
		global $wpdb;

		// Get workflow
		$request = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests WHERE request_id = %d",
			$request_id
		) );

		if ( ! $request ) {
			return false;
		}

		$workflow = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_approval_workflows 
			 WHERE leave_type = %s AND status = 'active'",
			$request->leave_type
		) );

		$workflow_id = $workflow ? $workflow->workflow_id : 0;

		// Create approval record for Level 2 (Admin)
		$approval_data = array(
			'request_id'     => $request_id,
			'workflow_id'    => $workflow_id,
			'approver_id'    => $admin_id,
			'approval_level' => 2,
			'status'         => 'pending',
			'created_at'     => current_time( 'mysql' ),
			'updated_at'     => current_time( 'mysql' ),
		);

		$result = $wpdb->insert(
			"{$wpdb->prefix}leave_manager_approvals",
			$approval_data
		);

		if ( $result ) {
			$this->logger->info( 'Admin approval created', array(
				'request_id' => $request_id,
				'admin_id'   => $admin_id,
				'approval_id' => $wpdb->insert_id,
			) );

			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Get pending approvals for manager
	 *
	 * @return void
	 */
	public function get_pending_approvals() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_dashboard' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed', 'leave-manager' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'leave_manager_approve_department_requests' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to view approvals', 'leave-manager' ) ) );
		}

		$current_user = wp_get_current_user();
		global $wpdb;

		// Get manager's staff
		$manager_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}leave_manager_leave_users WHERE email = %s",
			$current_user->user_email
		) );

		if ( ! $manager_data ) {
			wp_send_json_error( array( 'message' => __( 'Manager profile not found', 'leave-manager' ) ) );
		}

		// Get pending approvals for this manager
		$pending = $wpdb->get_results( $wpdb->prepare(
			"SELECT a.*, r.user_id, r.leave_type, r.start_date, r.end_date, r.reason,
			        u.first_name, u.last_name, u.email, u.department
			 FROM {$wpdb->prefix}leave_manager_approvals a
			 INNER JOIN {$wpdb->prefix}leave_manager_leave_requests r ON a.request_id = r.request_id
			 INNER JOIN {$wpdb->prefix}leave_manager_leave_users u ON r.user_id = u.id
			 WHERE a.approver_id = %d AND a.status = 'pending' AND a.approval_level = 1
			 ORDER BY a.created_at DESC",
			$manager_data->id
		) );

		wp_send_json_success( array(
			'count'     => count( $pending ),
			'approvals' => $pending,
		) );
	}

	/**
	 * Approve request by department manager
	 *
	 * @return void
	 */
	public function approve_request_by_manager() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_dashboard' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed', 'leave-manager' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'leave_manager_approve_department_requests' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to approve requests', 'leave-manager' ) ) );
		}

		$approval_id = isset( $_POST['approval_id'] ) ? intval( $_POST['approval_id'] ) : 0;
		$comments    = isset( $_POST['comments'] ) ? sanitize_textarea_field( $_POST['comments'] ) : '';

		if ( ! $approval_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid approval ID', 'leave-manager' ) ) );
		}

		global $wpdb;

		// Get approval
		$approval = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_approvals WHERE approval_id = %d",
			$approval_id
		) );

		if ( ! $approval ) {
			wp_send_json_error( array( 'message' => __( 'Approval not found', 'leave-manager' ) ) );
		}

		// Verify approval is pending
		if ( $approval->status !== 'pending' ) {
			wp_send_json_error( array( 'message' => __( 'This approval has already been processed', 'leave-manager' ) ) );
		}

		// Update approval status
		$result = $wpdb->update(
			"{$wpdb->prefix}leave_manager_approvals",
			array(
				'status'     => 'approved',
				'comments'   => $comments,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'approval_id' => $approval_id )
		);

		if ( $result !== false ) {
			// Create admin approval for Level 2
			$this->create_admin_approval( $approval->request_id, 1 ); // Admin user ID = 1

			$this->logger->info( 'Request approved by manager', array(
				'approval_id' => $approval_id,
				'request_id'  => $approval->request_id,
			) );

			wp_send_json_success( array(
				'message' => __( 'Request approved successfully. Awaiting final admin approval.', 'leave-manager' ),
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to approve request', 'leave-manager' ),
			) );
		}
	}

	/**
	 * Reject request by department manager
	 *
	 * @return void
	 */
	public function reject_request_by_manager() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_dashboard' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed', 'leave-manager' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'leave_manager_approve_department_requests' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to reject requests', 'leave-manager' ) ) );
		}

		$approval_id = isset( $_POST['approval_id'] ) ? intval( $_POST['approval_id'] ) : 0;
		$reason      = isset( $_POST['reason'] ) ? sanitize_textarea_field( $_POST['reason'] ) : '';

		if ( ! $approval_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid approval ID', 'leave-manager' ) ) );
		}

		if ( empty( $reason ) ) {
			wp_send_json_error( array( 'message' => __( 'Rejection reason is required', 'leave-manager' ) ) );
		}

		global $wpdb;

		// Get approval
		$approval = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_approvals WHERE approval_id = %d",
			$approval_id
		) );

		if ( ! $approval ) {
			wp_send_json_error( array( 'message' => __( 'Approval not found', 'leave-manager' ) ) );
		}

		// Update approval status
		$result = $wpdb->update(
			"{$wpdb->prefix}leave_manager_approvals",
			array(
				'status'     => 'rejected',
				'comments'   => $reason,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'approval_id' => $approval_id )
		);

		if ( $result !== false ) {
			// Update request status to rejected
			$wpdb->update(
				"{$wpdb->prefix}leave_manager_leave_requests",
				array( 'status' => 'rejected' ),
				array( 'request_id' => $approval->request_id )
			);

			$this->logger->info( 'Request rejected by manager', array(
				'approval_id' => $approval_id,
				'request_id'  => $approval->request_id,
			) );

			wp_send_json_success( array(
				'message' => __( 'Request rejected successfully', 'leave-manager' ),
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to reject request', 'leave-manager' ),
			) );
		}
	}

	/**
	 * Route request to appropriate approvers
	 *
	 * @param int $request_id Request ID
	 * @return bool Success status
	 */
	public function route_request_to_approvers( $request_id ) {
		global $wpdb;

		// Get request details
		$request = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests WHERE request_id = %d",
			$request_id
		) );

		if ( ! $request ) {
			return false;
		}

		// Get employee details
		$employee = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_leave_users WHERE id = %d",
			$request->user_id
		) );

		if ( ! $employee ) {
			return false;
		}

		// If employee has a manager, create manager approval first
		if ( $employee->manager_id ) {
			$this->create_manager_approval( $request_id, $employee->manager_id, $request->leave_type );
		} else {
			// Otherwise, go directly to admin
			$this->create_admin_approval( $request_id, 1 );
		}

		return true;
	}

	/**
	 * Check if request is ready for admin approval
	 *
	 * @param int $request_id Request ID
	 * @return bool True if ready for admin approval
	 */
	public function is_ready_for_admin_approval( $request_id ) {
		global $wpdb;

		// Check if all manager approvals are done
		$pending_manager = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_approvals 
			 WHERE request_id = %d AND approval_level = 1 AND status = 'pending'",
			$request_id
		) );

		// If no pending manager approvals, it's ready for admin
		return $pending_manager == 0;
	}

	/**
	 * Get approval chain for request
	 *
	 * @param int $request_id Request ID
	 * @return array Approval chain
	 */
	public function get_approval_chain( $request_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT a.*, u.first_name, u.last_name, u.email, u.position
			 FROM {$wpdb->prefix}leave_manager_approvals a
			 LEFT JOIN {$wpdb->prefix}leave_manager_leave_users u ON a.approver_id = u.id
			 WHERE a.request_id = %d
			 ORDER BY a.approval_level ASC, a.created_at ASC",
			$request_id
		) );
	}
}

// Global function to access the department approval system
function leave_manager_department_approval_system() {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new Leave_Manager_Department_Approval_System(
			leave_manager_database(),
			leave_manager_logger()
		);
	}

	return $instance;
}
