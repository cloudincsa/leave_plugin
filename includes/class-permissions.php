<?php
/**
 * Permissions and Roles class for Leave Manager Plugin
 *
 * Handles role-based access control and permission management.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Permissions class
 */
class Leave_Manager_Permissions {

	/**
	 * Available roles
	 *
	 * @var array
	 */
	private $roles = array(
		'staff' => array(
			'label'        => 'Staff',
			'description'  => 'Regular employee who can submit and manage their own leave requests',
			'capabilities' => array(
				'view_own_leave_requests',
				'submit_leave_request',
				'edit_own_leave_request',
				'delete_own_leave_request',
				'view_own_leave_balance',
				'view_own_profile',
				'edit_own_profile',
			),
		),
		'hr' => array(
			'label'        => 'HR',
			'description'  => 'Human Resources personnel who can manage leave requests and view reports',
			'capabilities' => array(
				'view_all_leave_requests',
				'approve_leave_request',
				'reject_leave_request',
				'view_all_users',
				'view_all_leave_balances',
				'view_reports',
				'manage_leave_settings',
				'view_system_logs',
				'edit_user_profile',
			),
		),
		'admin' => array(
			'label'        => 'Administrator',
			'description'  => 'System administrator with full access to all features',
			'capabilities' => array(
				'manage_users',
				'create_user',
				'edit_user',
				'delete_user',
				'manage_leave_requests',
				'view_all_leave_requests',
				'approve_leave_request',
				'reject_leave_request',
				'manage_settings',
				'manage_email_templates',
				'view_all_users',
				'view_all_leave_balances',
				'view_reports',
				'manage_leave_settings',
				'view_system_logs',
				'manage_system_logs',
				'view_diagnostics',
				'reset_leave_balances',
				'export_data',
				'view_own_leave_requests',
				'submit_leave_request',
				'edit_own_leave_request',
				'delete_own_leave_request',
				'view_own_leave_balance',
				'view_own_profile',
				'edit_own_profile',
			),
		),
	);

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
	}

	/**
	 * Get all available roles
	 *
	 * @return array Available roles
	 */
	public function get_roles() {
		return $this->roles;
	}

	/**
	 * Get a specific role
	 *
	 * @param string $role Role name
	 * @return array|null Role data or null
	 */
	public function get_role( $role ) {
		return isset( $this->roles[ $role ] ) ? $this->roles[ $role ] : null;
	}

	/**
	 * Get role label
	 *
	 * @param string $role Role name
	 * @return string Role label
	 */
	public function get_role_label( $role ) {
		$role_data = $this->get_role( $role );
		return $role_data ? $role_data['label'] : ucfirst( $role );
	}

	/**
	 * Get capabilities for a role
	 *
	 * @param string $role Role name
	 * @return array Role capabilities
	 */
	public function get_role_capabilities( $role ) {
		$role_data = $this->get_role( $role );
		return $role_data ? $role_data['capabilities'] : array();
	}

	/**
	 * Check if user has a capability
	 *
	 * @param int    $user_id User ID
	 * @param string $capability Capability to check
	 * @return bool True if user has capability
	 */
	public function user_has_capability( $user_id, $capability ) {
		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		// Get user role
		$user_role = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT role FROM $users_table WHERE user_id = %d",
				intval( $user_id )
			)
		);

		if ( ! $user_role ) {
			return false;
		}

		// Get role capabilities
		$capabilities = $this->get_role_capabilities( $user_role );

		return in_array( $capability, $capabilities, true );
	}

	/**
	 * Check if user has any of the given capabilities
	 *
	 * @param int   $user_id User ID
	 * @param array $capabilities Capabilities to check
	 * @return bool True if user has any capability
	 */
	public function user_has_any_capability( $user_id, $capabilities ) {
		foreach ( $capabilities as $capability ) {
			if ( $this->user_has_capability( $user_id, $capability ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if user has all given capabilities
	 *
	 * @param int   $user_id User ID
	 * @param array $capabilities Capabilities to check
	 * @return bool True if user has all capabilities
	 */
	public function user_has_all_capabilities( $user_id, $capabilities ) {
		foreach ( $capabilities as $capability ) {
			if ( ! $this->user_has_capability( $user_id, $capability ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if user is admin
	 *
	 * @param int $user_id User ID
	 * @return bool True if user is admin
	 */
	public function is_admin( $user_id ) {
		return $this->user_has_capability( $user_id, 'manage_users' );
	}

	/**
	 * Check if user is HR
	 *
	 * @param int $user_id User ID
	 * @return bool True if user is HR
	 */
	public function is_hr( $user_id ) {
		return $this->user_has_capability( $user_id, 'approve_leave_request' );
	}

	/**
	 * Check if user is staff
	 *
	 * @param int $user_id User ID
	 * @return bool True if user is staff
	 */
	public function is_staff( $user_id ) {
		return $this->user_has_capability( $user_id, 'submit_leave_request' );
	}

	/**
	 * Check if user can view leave request
	 *
	 * @param int $user_id User ID
	 * @param int $request_id Request ID
	 * @return bool True if user can view request
	 */
	public function can_view_leave_request( $user_id, $request_id ) {
		global $wpdb;
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		// Get request owner
		$request_user_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM $requests_table WHERE request_id = %d",
				intval( $request_id )
			)
		);

		if ( ! $request_user_id ) {
			return false;
		}

		// Admin and HR can view all requests
		if ( $this->user_has_any_capability( $user_id, array( 'manage_users', 'approve_leave_request' ) ) ) {
			return true;
		}

		// Staff can only view their own requests
		return intval( $user_id ) === intval( $request_user_id );
	}

	/**
	 * Check if user can edit leave request
	 *
	 * @param int $user_id User ID
	 * @param int $request_id Request ID
	 * @return bool True if user can edit request
	 */
	public function can_edit_leave_request( $user_id, $request_id ) {
		global $wpdb;
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		// Get request
		$request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, status FROM $requests_table WHERE request_id = %d",
				intval( $request_id )
			)
		);

		if ( ! $request ) {
			return false;
		}

		// Admin can edit any request
		if ( $this->user_has_capability( $user_id, 'manage_users' ) ) {
			return true;
		}

		// Staff can only edit their own pending requests
		if ( intval( $user_id ) === intval( $request->user_id ) && 'pending' === $request->status ) {
			return $this->user_has_capability( $user_id, 'edit_own_leave_request' );
		}

		return false;
	}

	/**
	 * Check if user can delete leave request
	 *
	 * @param int $user_id User ID
	 * @param int $request_id Request ID
	 * @return bool True if user can delete request
	 */
	public function can_delete_leave_request( $user_id, $request_id ) {
		global $wpdb;
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		// Get request
		$request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, status FROM $requests_table WHERE request_id = %d",
				intval( $request_id )
			)
		);

		if ( ! $request ) {
			return false;
		}

		// Admin can delete any request
		if ( $this->user_has_capability( $user_id, 'manage_users' ) ) {
			return true;
		}

		// Staff can only delete their own pending requests
		if ( intval( $user_id ) === intval( $request->user_id ) && 'pending' === $request->status ) {
			return $this->user_has_capability( $user_id, 'delete_own_leave_request' );
		}

		return false;
	}

	/**
	 * Check if user can approve leave request
	 *
	 * @param int $user_id User ID
	 * @param int $request_id Request ID
	 * @return bool True if user can approve request
	 */
	public function can_approve_leave_request( $user_id, $request_id ) {
		global $wpdb;
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		// Get request
		$request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT status FROM $requests_table WHERE request_id = %d",
				intval( $request_id )
			)
		);

		if ( ! $request || 'pending' !== $request->status ) {
			return false;
		}

		// Only HR and Admin can approve
		return $this->user_has_capability( $user_id, 'approve_leave_request' );
	}

	/**
	 * Check if user can reject leave request
	 *
	 * @param int $user_id User ID
	 * @param int $request_id Request ID
	 * @return bool True if user can reject request
	 */
	public function can_reject_leave_request( $user_id, $request_id ) {
		global $wpdb;
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		// Get request
		$request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT status FROM $requests_table WHERE request_id = %d",
				intval( $request_id )
			)
		);

		if ( ! $request || 'pending' !== $request->status ) {
			return false;
		}

		// Only HR and Admin can reject
		return $this->user_has_capability( $user_id, 'approve_leave_request' );
	}

	/**
	 * Check if user can view user profile
	 *
	 * @param int $user_id Current user ID
	 * @param int $profile_user_id Profile user ID
	 * @return bool True if user can view profile
	 */
	public function can_view_user_profile( $user_id, $profile_user_id ) {
		// Admin and HR can view all profiles
		if ( $this->user_has_any_capability( $user_id, array( 'manage_users', 'approve_leave_request' ) ) ) {
			return true;
		}

		// Users can view their own profile
		return intval( $user_id ) === intval( $profile_user_id );
	}

	/**
	 * Check if user can edit user profile
	 *
	 * @param int $user_id Current user ID
	 * @param int $profile_user_id Profile user ID
	 * @return bool True if user can edit profile
	 */
	public function can_edit_user_profile( $user_id, $profile_user_id ) {
		// Admin can edit all profiles
		if ( $this->user_has_capability( $user_id, 'manage_users' ) ) {
			return true;
		}

		// Users can edit their own profile
		if ( intval( $user_id ) === intval( $profile_user_id ) ) {
			return $this->user_has_capability( $user_id, 'edit_own_profile' );
		}

		return false;
	}

	/**
	 * Check if user can manage settings
	 *
	 * @param int $user_id User ID
	 * @return bool True if user can manage settings
	 */
	public function can_manage_settings( $user_id ) {
		return $this->user_has_capability( $user_id, 'manage_settings' );
	}

	/**
	 * Check if user can view reports
	 *
	 * @param int $user_id User ID
	 * @return bool True if user can view reports
	 */
	public function can_view_reports( $user_id ) {
		return $this->user_has_capability( $user_id, 'view_reports' );
	}

	/**
	 * Check if user can view system logs
	 *
	 * @param int $user_id User ID
	 * @return bool True if user can view system logs
	 */
	public function can_view_system_logs( $user_id ) {
		return $this->user_has_capability( $user_id, 'view_system_logs' );
	}

	/**
	 * Check if user can manage system logs
	 *
	 * @param int $user_id User ID
	 * @return bool True if user can manage system logs
	 */
	public function can_manage_system_logs( $user_id ) {
		return $this->user_has_capability( $user_id, 'manage_system_logs' );
	}

	/**
	 * Check if user can view diagnostics
	 *
	 * @param int $user_id User ID
	 * @return bool True if user can view diagnostics
	 */
	public function can_view_diagnostics( $user_id ) {
		return $this->user_has_capability( $user_id, 'view_diagnostics' );
	}

	/**
	 * Get user role
	 *
	 * @param int $user_id User ID
	 * @return string|null User role
	 */
	public function get_user_role( $user_id ) {
		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT role FROM $users_table WHERE user_id = %d",
				intval( $user_id )
			)
		);
	}

	/**
	 * Log permission check
	 *
	 * @param int    $user_id User ID
	 * @param string $capability Capability checked
	 * @param bool   $result Permission result
	 * @return void
	 */
	public function log_permission_check( $user_id, $capability, $result ) {
		$this->logger->debug(
			'Permission check: ' . $capability . ' - ' . ( $result ? 'Allowed' : 'Denied' ),
			array(
				'user_id'     => $user_id,
				'capability'  => $capability,
				'result'      => $result,
			)
		);
	}
}
