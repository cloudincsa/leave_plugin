<?php
/**
 * Roles and Capabilities class for Leave Manager Plugin
 *
 * Registers custom roles and capabilities for the plugin.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Roles_Capabilities class
 */
class Leave_Manager_Roles_Capabilities {

	/**
	 * Register roles and capabilities
	 *
	 * @return void
	 */
	public static function register_roles() {
		// Get WordPress roles object
		$wp_roles = wp_roles();

		// Define Leave Manager Staff role
		if ( ! $wp_roles->is_role( 'leave_manager_staff' ) ) {
			add_role(
				'leave_manager_staff',
				__( 'Leave Manager Staff', 'leave-manager-management' ),
				array(
					'read'                      => true,
					'leave_manager_view_own_requests'    => true,
					'leave_manager_submit_leave_request' => true,
					'leave_manager_view_own_balance'     => true,
					'leave_manager_view_calendar'        => true,
				)
			);
		}

		// Define Leave Manager HR role
		if ( ! $wp_roles->is_role( 'leave_manager_hr' ) ) {
			add_role(
				'leave_manager_hr',
				__( 'Leave Manager HR', 'leave-manager-management' ),
				array(
					'read'                           => true,
					'leave_manager_view_all_requests'         => true,
					'leave_manager_approve_requests'          => true,
					'leave_manager_reject_requests'           => true,
					'leave_manager_view_all_balances'         => true,
					'leave_manager_manage_users'              => true,
					'leave_manager_view_reports'              => true,
					'leave_manager_view_calendar'             => true,
					'leave_manager_submit_leave_request'      => true,
					'leave_manager_view_own_requests'         => true,
					'leave_manager_view_own_balance'          => true,
				)
			);
		}

		// Define Leave Manager Admin role
		if ( ! $wp_roles->is_role( 'leave_manager_admin' ) ) {
			add_role(
				'leave_manager_admin',
				__( 'Leave Manager Administrator', 'leave-manager-management' ),
				array(
					'read'                           => true,
					'leave_manager_manage_plugin'             => true,
					'leave_manager_manage_users'              => true,
					'leave_manager_manage_settings'           => true,
					'leave_manager_view_all_requests'         => true,
					'leave_manager_approve_requests'          => true,
					'leave_manager_reject_requests'           => true,
					'leave_manager_view_all_balances'         => true,
					'leave_manager_view_reports'              => true,
					'leave_manager_view_logs'                 => true,
					'leave_manager_manage_policies'           => true,
					'leave_manager_manage_workflows'          => true,
					'leave_manager_manage_teams'              => true,
					'leave_manager_bulk_operations'           => true,
					'leave_manager_view_calendar'             => true,
					'leave_manager_submit_leave_request'      => true,
					'leave_manager_view_own_requests'         => true,
					'leave_manager_view_own_balance'          => true,
				)
			);
		}

		// Add capabilities to administrator role
		$admin_role = $wp_roles->get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_capabilities = array(
				'leave_manager_manage_plugin'             => true,
				'leave_manager_manage_users'              => true,
				'leave_manager_manage_settings'           => true,
				'leave_manager_view_all_requests'         => true,
				'leave_manager_approve_requests'          => true,
				'leave_manager_reject_requests'           => true,
				'leave_manager_view_all_balances'         => true,
				'leave_manager_view_reports'              => true,
				'leave_manager_view_logs'                 => true,
				'leave_manager_manage_policies'           => true,
				'leave_manager_manage_workflows'          => true,
				'leave_manager_manage_teams'              => true,
				'leave_manager_bulk_operations'           => true,
				'leave_manager_view_calendar'             => true,
				'leave_manager_submit_leave_request'      => true,
				'leave_manager_view_own_requests'         => true,
				'leave_manager_view_own_balance'          => true,
			);

			foreach ( $admin_capabilities as $cap => $grant ) {
				if ( ! $admin_role->has_cap( $cap ) ) {
					$admin_role->add_cap( $cap, $grant );
				}
			}
		}
	}

	/**
	 * Remove roles and capabilities
	 *
	 * @return void
	 */
	public static function remove_roles() {
		// Get WordPress roles object
		$wp_roles = wp_roles();

		// Remove Leave Manager roles
		remove_role( 'leave_manager_staff' );
		remove_role( 'leave_manager_hr' );
		remove_role( 'leave_manager_admin' );

		// Remove capabilities from administrator role
		$admin_role = $wp_roles->get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_capabilities = array(
				'leave_manager_manage_plugin',
				'leave_manager_manage_users',
				'leave_manager_manage_settings',
				'leave_manager_view_all_requests',
				'leave_manager_approve_requests',
				'leave_manager_reject_requests',
				'leave_manager_view_all_balances',
				'leave_manager_view_reports',
				'leave_manager_view_logs',
				'leave_manager_manage_policies',
				'leave_manager_manage_workflows',
				'leave_manager_manage_teams',
				'leave_manager_bulk_operations',
				'leave_manager_view_calendar',
				'leave_manager_submit_leave_request',
				'leave_manager_view_own_requests',
				'leave_manager_view_own_balance',
			);

			foreach ( $admin_capabilities as $cap ) {
				if ( $admin_role->has_cap( $cap ) ) {
					$admin_role->remove_cap( $cap );
				}
			}
		}
	}

	/**
	 * Check if user has capability
	 *
	 * @param int    $user_id User ID
	 * @param string $capability Capability to check
	 * @return bool True if user has capability
	 */
	public static function user_can( $user_id, $capability ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return false;
		}

		return user_can( $user, $capability );
	}

	/**
	 * Get user role
	 *
	 * @param int $user_id User ID
	 * @return string|null User role or null
	 */
	public static function get_user_role( $user_id ) {
		$user = get_user_by( 'ID', $user_id );
		if ( ! $user ) {
			return null;
		}

		$roles = $user->roles;
		if ( empty( $roles ) ) {
			return null;
		}

		// Return first Leave Manager role if exists
		foreach ( $roles as $role ) {
			if ( strpos( $role, 'leave_manager_' ) === 0 ) {
				return $role;
			}
		}

		// Return first role otherwise
		return $roles[0];
	}

	/**
	 * Get all Leave Manager roles
	 *
	 * @return array Array of Leave Manager roles
	 */
	public static function get_leave_manager_roles() {
		$wp_roles = wp_roles();
		$leave_manager_roles = array();

		foreach ( $wp_roles->roles as $role_name => $role_info ) {
			if ( strpos( $role_name, 'leave_manager_' ) === 0 ) {
				$leave_manager_roles[ $role_name ] = $role_info;
			}
		}

		return $leave_manager_roles;
	}

	/**
	 * Get all Leave Manager capabilities
	 *
	 * @return array Array of Leave Manager capabilities
	 */
	public static function get_leave_manager_capabilities() {
		return array(
			'leave_manager_manage_plugin'             => __( 'Manage Leave Management Plugin', 'leave-manager-management' ),
			'leave_manager_manage_users'              => __( 'Manage Users', 'leave-manager-management' ),
			'leave_manager_manage_settings'           => __( 'Manage Settings', 'leave-manager-management' ),
			'leave_manager_view_all_requests'         => __( 'View All Leave Requests', 'leave-manager-management' ),
			'leave_manager_approve_requests'          => __( 'Approve Leave Requests', 'leave-manager-management' ),
			'leave_manager_reject_requests'           => __( 'Reject Leave Requests', 'leave-manager-management' ),
			'leave_manager_view_all_balances'         => __( 'View All Leave Balances', 'leave-manager-management' ),
			'leave_manager_view_reports'              => __( 'View Reports', 'leave-manager-management' ),
			'leave_manager_view_logs'                 => __( 'View System Logs', 'leave-manager-management' ),
			'leave_manager_manage_policies'           => __( 'Manage Leave Policies', 'leave-manager-management' ),
			'leave_manager_manage_workflows'          => __( 'Manage Approval Workflows', 'leave-manager-management' ),
			'leave_manager_manage_teams'              => __( 'Manage Teams', 'leave-manager-management' ),
			'leave_manager_bulk_operations'           => __( 'Perform Bulk Operations', 'leave-manager-management' ),
			'leave_manager_view_calendar'             => __( 'View Leave Calendar', 'leave-manager-management' ),
			'leave_manager_submit_leave_request'      => __( 'Submit Leave Requests', 'leave-manager-management' ),
			'leave_manager_view_own_requests'         => __( 'View Own Leave Requests', 'leave-manager-management' ),
			'leave_manager_view_own_balance'          => __( 'View Own Leave Balance', 'leave-manager-management' ),
		);
	}
}
