<?php
/**
 * Profile Manager class for Leave Manager Plugin
 *
 * Handles staff profile editing and department manager functionality
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Profile_Manager class
 */
class Leave_Manager_Profile_Manager {

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
		add_action( 'wp_ajax_leave_manager_update_profile', array( $this, 'update_profile' ) );
		add_action( 'wp_ajax_leave_manager_get_profile', array( $this, 'get_profile' ) );
		add_action( 'wp_ajax_leave_manager_assign_department_manager', array( $this, 'assign_department_manager' ) );
		add_action( 'wp_ajax_leave_manager_get_department_managers', array( $this, 'get_department_managers' ) );

		// Register department manager role
		add_action( 'init', array( $this, 'register_department_manager_role' ) );
	}

	/**
	 * Register Department Manager Role
	 *
	 * @return void
	 */
	public function register_department_manager_role() {
		$wp_roles = wp_roles();

		if ( ! $wp_roles->is_role( 'leave_manager_department_manager' ) ) {
			add_role(
				'leave_manager_department_manager',
				__( 'Leave Manager Department Manager', 'leave-manager' ),
				array(
					'read'                                  => true,
					'leave_manager_view_own_requests'       => true,
					'leave_manager_submit_leave_request'    => true,
					'leave_manager_view_own_balance'        => true,
					'leave_manager_view_calendar'           => true,
					'leave_manager_approve_department_requests' => true,
					'leave_manager_view_department_requests' => true,
					'leave_manager_view_department_staff'   => true,
					'leave_manager_view_department_reports' => true,
				)
			);

			$this->logger->info( 'Department Manager role registered' );
		}
	}

	/**
	 * Update staff profile
	 *
	 * @return void
	 */
	public function update_profile() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_update_profile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed', 'leave-manager' ) ) );
		}

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in', 'leave-manager' ) ) );
		}

		// Get and validate input
		$user_id   = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		$first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
		$last_name  = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		$phone      = isset( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';

		// Validate required fields
		if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'First name, last name, and email are required', 'leave-manager' ) ) );
		}

		// Validate email format
		if ( ! is_email( $email ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid email address', 'leave-manager' ) ) );
		}

		// Get current user's profile
		$current_user = wp_get_current_user();
		global $wpdb;
		$user_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_leave_users WHERE id = %d",
			$user_id
		) );

		// Verify user can only edit their own profile
		if ( ! $user_data || $user_data->email !== $current_user->user_email ) {
			wp_send_json_error( array( 'message' => __( 'You can only edit your own profile', 'leave-manager' ) ) );
		}

		// Check if email is already taken by another user
		if ( $email !== $user_data->email ) {
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}leave_manager_leave_users WHERE email = %s AND id != %d",
				$email,
				$user_id
			) );

			if ( $existing ) {
				wp_send_json_error( array( 'message' => __( 'This email is already in use', 'leave-manager' ) ) );
			}
		}

		// Update profile
		$update_data = array(
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'email'      => $email,
			'phone'      => $phone,
			'updated_at' => current_time( 'mysql' ),
		);

		$result = $wpdb->update(
			"{$wpdb->prefix}leave_manager_leave_users",
			$update_data,
			array( 'id' => $user_id )
		);

		if ( $result !== false ) {
			// Update WordPress user email if changed
			if ( $email !== $current_user->user_email ) {
				wp_update_user( array(
					'ID'         => $current_user->ID,
					'user_email' => $email,
				) );
			}

			$this->logger->info( 'Profile updated', array( 'user_id' => $user_id ) );

			wp_send_json_success( array(
				'message' => __( 'Your profile has been updated successfully', 'leave-manager' ),
			) );
		} else {
			$this->logger->error( 'Profile update failed', array( 'user_id' => $user_id ) );

			wp_send_json_error( array(
				'message' => __( 'Failed to update profile. Please try again.', 'leave-manager' ),
			) );
		}
	}

	/**
	 * Get staff profile
	 *
	 * @return void
	 */
	public function get_profile() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_get_profile' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed', 'leave-manager' ) ) );
		}

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You must be logged in', 'leave-manager' ) ) );
		}

		$current_user = wp_get_current_user();
		global $wpdb;

		$user_data = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_leave_users WHERE email = %s",
			$current_user->user_email
		) );

		if ( $user_data ) {
			wp_send_json_success( $user_data );
		} else {
			wp_send_json_error( array( 'message' => __( 'Profile not found', 'leave-manager' ) ) );
		}
	}

	/**
	 * Assign department manager
	 *
	 * @return void
	 */
	public function assign_department_manager() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_manage_staff' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed', 'leave-manager' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'leave_manager_manage_users' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action', 'leave-manager' ) ) );
		}

		$staff_id   = isset( $_POST['staff_id'] ) ? intval( $_POST['staff_id'] ) : 0;
		$manager_id = isset( $_POST['manager_id'] ) ? intval( $_POST['manager_id'] ) : 0;

		if ( ! $staff_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid staff ID', 'leave-manager' ) ) );
		}

		global $wpdb;

		// Update manager assignment
		$result = $wpdb->update(
			"{$wpdb->prefix}leave_manager_leave_users",
			array( 'manager_id' => $manager_id ),
			array( 'id' => $staff_id )
		);

		if ( $result !== false ) {
			$this->logger->info( 'Department manager assigned', array(
				'staff_id'   => $staff_id,
				'manager_id' => $manager_id,
			) );

			wp_send_json_success( array(
				'message' => __( 'Department manager assigned successfully', 'leave-manager' ),
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to assign department manager', 'leave-manager' ),
			) );
		}
	}

	/**
	 * Get department managers
	 *
	 * @return void
	 */
	public function get_department_managers() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_manage_staff' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security verification failed', 'leave-manager' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'leave_manager_manage_users' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action', 'leave-manager' ) ) );
		}

		$department = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : '';

		global $wpdb;

		// Get department managers
		$managers = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, first_name, last_name, email, department 
			 FROM {$wpdb->prefix}leave_manager_leave_users 
			 WHERE role = %s AND (department = %s OR department = '')",
			'manager',
			$department
		) );

		wp_send_json_success( $managers );
	}

	/**
	 * Get profile by user ID
	 *
	 * @param int $user_id User ID
	 * @return object|null User profile or null
	 */
	public function get_profile_by_id( $user_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_leave_users WHERE id = %d",
			$user_id
		) );
	}

	/**
	 * Update profile
	 *
	 * @param int   $user_id User ID
	 * @param array $data Profile data
	 * @return bool Success status
	 */
	public function update_profile_data( $user_id, $data ) {
		global $wpdb;

		$update_data = array();

		if ( isset( $data['first_name'] ) ) {
			$update_data['first_name'] = sanitize_text_field( $data['first_name'] );
		}

		if ( isset( $data['last_name'] ) ) {
			$update_data['last_name'] = sanitize_text_field( $data['last_name'] );
		}

		if ( isset( $data['email'] ) ) {
			$update_data['email'] = sanitize_email( $data['email'] );
		}

		if ( isset( $data['phone'] ) ) {
			$update_data['phone'] = sanitize_text_field( $data['phone'] );
		}

		if ( isset( $data['department'] ) ) {
			$update_data['department'] = sanitize_text_field( $data['department'] );
		}

		if ( isset( $data['position'] ) ) {
			$update_data['position'] = sanitize_text_field( $data['position'] );
		}

		if ( isset( $data['manager_id'] ) ) {
			$update_data['manager_id'] = intval( $data['manager_id'] );
		}

		if ( isset( $data['role'] ) ) {
			$update_data['role'] = sanitize_text_field( $data['role'] );
		}

		$update_data['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->update(
			"{$wpdb->prefix}leave_manager_leave_users",
			$update_data,
			array( 'id' => $user_id )
		);

		if ( $result !== false ) {
			$this->logger->info( 'Profile updated', array( 'user_id' => $user_id ) );
			return true;
		} else {
			$this->logger->error( 'Profile update failed', array( 'user_id' => $user_id ) );
			return false;
		}
	}

	/**
	 * Get department staff
	 *
	 * @param string $department Department name
	 * @return array Staff list
	 */
	public function get_department_staff( $department ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_leave_users 
			 WHERE department = %s AND status = 'active'
			 ORDER BY first_name, last_name ASC",
			$department
		) );
	}

	/**
	 * Get manager's department staff
	 *
	 * @param int $manager_id Manager user ID
	 * @return array Staff list
	 */
	public function get_manager_staff( $manager_id ) {
		global $wpdb;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_leave_users 
			 WHERE manager_id = %d AND status = 'active'
			 ORDER BY first_name, last_name ASC",
			$manager_id
		) );
	}
}

// Global function to access the profile manager
function leave_manager_profile_manager() {
	static $instance = null;

	if ( null === $instance ) {
		$instance = new Leave_Manager_Profile_Manager(
			leave_manager_database(),
			leave_manager_logger()
		);
	}

	return $instance;
}
