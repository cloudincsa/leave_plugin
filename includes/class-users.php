<?php
/**
 * Users class for Leave Manager Plugin
 *
 * Handles user management operations including CRUD, roles, and statistics.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Users class
 */
class Leave_Manager_Users {

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
	 * Users table name
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Valid user roles
	 *
	 * @var array
	 */
	private $valid_roles = array( 'staff', 'hr', 'admin' );

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 */
	public function __construct( $db, $logger ) {
		$this->db     = $db;
		$this->logger = $logger;
		$this->table  = $db->users_table;
	}

	/**
	 * Create a new user
	 *
	 * @param array $user_data User data
	 * @return int|false User ID or false on error
	 */
	public function create_user( $user_data ) {
		// Validate required fields
		if ( empty( $user_data['email'] ) ) {
			$this->logger->error( 'User creation failed: email is required' );
			return false;
		}

		// Check if user already exists
		if ( $this->get_user_by_email( $user_data['email'] ) ) {
			$this->logger->warning( 'User creation failed: email already exists', array( 'email' => $user_data['email'] ) );
			return false;
		}

		// Prepare user data
		$data = array(
			'email'                  => sanitize_email( $user_data['email'] ),
			'first_name'             => isset( $user_data['first_name'] ) ? sanitize_text_field( $user_data['first_name'] ) : '',
			'last_name'              => isset( $user_data['last_name'] ) ? sanitize_text_field( $user_data['last_name'] ) : '',
			'phone'                  => isset( $user_data['phone'] ) ? sanitize_text_field( $user_data['phone'] ) : '',
			'role'                   => isset( $user_data['role'] ) && in_array( $user_data['role'], $this->valid_roles, true ) ? $user_data['role'] : 'staff',
			'department'             => isset( $user_data['department'] ) ? sanitize_text_field( $user_data['department'] ) : '',
			'position'               => isset( $user_data['position'] ) ? sanitize_text_field( $user_data['position'] ) : '',
			'annual_leave_balance'   => isset( $user_data['annual_leave_balance'] ) ? floatval( $user_data['annual_leave_balance'] ) : 20,
			'sick_leave_balance'     => isset( $user_data['sick_leave_balance'] ) ? floatval( $user_data['sick_leave_balance'] ) : 10,
			'other_leave_balance'    => isset( $user_data['other_leave_balance'] ) ? floatval( $user_data['other_leave_balance'] ) : 5,
			'status'                 => isset( $user_data['status'] ) ? sanitize_text_field( $user_data['status'] ) : 'active',
		);

		// If WordPress user ID provided, add it
		if ( ! empty( $user_data['wp_user_id'] ) ) {
			$data['wp_user_id'] = intval( $user_data['wp_user_id'] );
		}

		// Insert user
		$user_id = $this->db->insert(
			$this->table,
			$data,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s' )
		);

		if ( $user_id ) {
			$this->logger->info( 'User created successfully', array( 'user_id' => $user_id, 'email' => $data['email'] ) );
		} else {
			$this->logger->error( 'User creation failed', array( 'email' => $data['email'], 'error' => $this->db->get_last_error() ) );
		}

		return $user_id;
	}

	/**
	 * Update a user
	 *
	 * @param int   $user_id User ID
	 * @param array $user_data User data to update
	 * @return bool True on success
	 */
	public function update_user( $user_id, $user_data ) {
		$user_id = intval( $user_id );

		// Check if user exists
		if ( ! $this->get_user( $user_id ) ) {
			$this->logger->warning( 'User update failed: user not found', array( 'user_id' => $user_id ) );
			return false;
		}

		// Prepare update data
		$data = array();
		if ( isset( $user_data['first_name'] ) ) {
			$data['first_name'] = sanitize_text_field( $user_data['first_name'] );
		}
		if ( isset( $user_data['last_name'] ) ) {
			$data['last_name'] = sanitize_text_field( $user_data['last_name'] );
		}
		if ( isset( $user_data['phone'] ) ) {
			$data['phone'] = sanitize_text_field( $user_data['phone'] );
		}
		if ( isset( $user_data['role'] ) && in_array( $user_data['role'], $this->valid_roles, true ) ) {
			$data['role'] = $user_data['role'];
		}
		if ( isset( $user_data['department'] ) ) {
			$data['department'] = sanitize_text_field( $user_data['department'] );
		}
		if ( isset( $user_data['position'] ) ) {
			$data['position'] = sanitize_text_field( $user_data['position'] );
		}
		if ( isset( $user_data['status'] ) ) {
			$data['status'] = sanitize_text_field( $user_data['status'] );
		}
		if ( isset( $user_data['annual_leave_balance'] ) ) {
			$data['annual_leave_balance'] = floatval( $user_data['annual_leave_balance'] );
		}
		if ( isset( $user_data['sick_leave_balance'] ) ) {
			$data['sick_leave_balance'] = floatval( $user_data['sick_leave_balance'] );
		}
		if ( isset( $user_data['other_leave_balance'] ) ) {
			$data['other_leave_balance'] = floatval( $user_data['other_leave_balance'] );
		}

		if ( empty( $data ) ) {
			return true;
		}

		$result = $this->db->update(
			$this->table,
			$data,
			array( 'user_id' => $user_id ),
			null,
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'User updated successfully', array( 'user_id' => $user_id ) );
		} else {
			$this->logger->error( 'User update failed', array( 'user_id' => $user_id, 'error' => $this->db->get_last_error() ) );
		}

		return $result;
	}

	/**
	 * Set user password
	 *
	 * @param int    $user_id User ID
	 * @param string $password New password
	 * @return bool True on success
	 */
	public function set_password( $user_id, $password ) {
		if ( empty( $password ) || strlen( $password ) < 8 ) {
			$this->logger->error( 'Password must be at least 8 characters' );
			return false;
		}

		$hashed = wp_hash_password( $password );
		return $this->update_user( $user_id, array( 'password' => $hashed ) );
	}

	/**
	 * Change user status
	 *
	 * @param int    $user_id User ID
	 * @param string $new_status New status
	 * @return bool True on success
	 */
	public function change_status( $user_id, $new_status ) {
		$valid_statuses = array( 'active', 'inactive', 'suspended' );

		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			$this->logger->error( 'Invalid status', array( 'status' => $new_status ) );
			return false;
		}

		return $this->update_user( $user_id, array( 'status' => $new_status ) );
	}

	/**
	 * Delete a user
	 *
	 * @param int $user_id User ID
	 * @return bool True on success
	 */
	public function delete_user( $user_id ) {
		$user_id = intval( $user_id );

		// Check if user exists
		if ( ! $this->get_user( $user_id ) ) {
			$this->logger->warning( 'User deletion failed: user not found', array( 'user_id' => $user_id ) );
			return false;
		}

		$result = $this->db->delete(
			$this->table,
			array( 'user_id' => $user_id ),
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'User deleted successfully', array( 'user_id' => $user_id ) );
		} else {
			$this->logger->error( 'User deletion failed', array( 'user_id' => $user_id, 'error' => $this->db->get_last_error() ) );
		}

		return $result;
	}

	/**
	 * Assign a leave policy to a user
	 *
	 * @param int $user_id User ID
	 * @param int $policy_id Policy ID
	 * @return bool True on success
	 */
	public function assign_policy( $user_id, $policy_id ) {
		global $wpdb;

		$user_id = intval( $user_id );
		$policy_id = intval( $policy_id );

		// Verify user exists
		if ( ! $this->get_user( $user_id ) ) {
			$this->logger->warning( 'Policy assignment failed: user not found', array( 'user_id' => $user_id ) );
			return false;
		}

		// Verify policy exists
		$policies_table = $wpdb->prefix . 'leave_manager_leave_policies';
		$policy = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$policies_table} WHERE policy_id = %d",
				$policy_id
			)
		);

		if ( ! $policy ) {
			$this->logger->warning( 'Policy assignment failed: policy not found', array( 'policy_id' => $policy_id ) );
			return false;
		}

		// Update user with policy and apply policy balances
		$update_data = array(
			'policy_id'              => $policy_id,
			'annual_leave_balance'   => floatval( $policy->annual_days ),
			'sick_leave_balance'     => floatval( $policy->sick_days ),
			'other_leave_balance'    => floatval( $policy->other_days ),
		);

		$result = $this->update_user( $user_id, $update_data );

		if ( $result ) {
			$this->logger->info( 'Policy assigned to user', array( 'user_id' => $user_id, 'policy_id' => $policy_id ) );
		}

		return $result;
	}

	/**
	 * Update leave balance
	 *
	 * @param int    $user_id User ID
	 * @param string $leave_type Leave type (annual, sick, other)
	 * @param float  $balance New balance
	 * @return bool True on success
	 */
	public function update_leave_balance( $user_id, $leave_type, $balance ) {
		$valid_types = array( 'annual', 'sick', 'other' );

		if ( ! in_array( $leave_type, $valid_types, true ) ) {
			return false;
		}

		$field = $leave_type . '_leave_balance';
		return $this->update_user( $user_id, array( $field => floatval( $balance ) ) );
	}

	/**
	 * Get a user by ID
	 *
	 * @param int $user_id User ID
	 * @return object|null User object or null
	 */
	public function get_user( $user_id ) {
		$user_id = intval( $user_id );
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE user_id = %d",
				$user_id
			)
		);
	}

	/**
	 * Get a user by email
	 *
	 * @param string $email User email
	 * @return object|null User object or null
	 */
	public function get_user_by_email( $email ) {
		$email = sanitize_email( $email );
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE email = %s",
				$email
			)
		);
	}

	/**
	 * Get all users
	 *
	 * @param array $args Query arguments
	 * @return array Array of user objects
	 */
	public function get_users( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'role'     => '',
			'status'   => '',
			'search'   => '',
			'orderby'  => 'created_at',
			'order'    => 'DESC',
			'limit'    => -1,
			'offset'   => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$values = array();

		if ( ! empty( $args['role'] ) ) {
			$where[] = 'role = %s';
			$values[] = $args['role'];
		}

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$values[] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$search = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)';
			$values[] = $search;
			$values[] = $search;
			$values[] = $search;
		}

		$where_clause = implode( ' AND ', $where );
		$query = "SELECT * FROM {$this->table} WHERE {$where_clause} ORDER BY {$args['orderby']} {$args['order']}";

		if ( $args['limit'] > 0 ) {
			$query .= " LIMIT {$args['offset']}, {$args['limit']}";
		}

		if ( ! empty( $values ) ) {
			return $wpdb->get_results( $wpdb->prepare( $query, $values ) );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Get user statistics
	 *
	 * @return array Statistics data
	 */
	public function get_user_statistics() {
		global $wpdb;

		return array(
			'total_users'    => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" ) ),
			'active_users'   => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE status = 'active'" ) ),
			'inactive_users' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE status = 'inactive'" ) ),
			'staff_count'    => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE role = 'staff'" ) ),
			'hr_count'       => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE role = 'hr'" ) ),
			'admin_count'    => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE role = 'admin'" ) ),
		);
	}

	/**
	 * Reset leave balances for all users
	 *
	 * @param float $annual_balance Annual leave balance
	 * @param float $sick_balance Sick leave balance
	 * @param float $other_balance Other leave balance
	 * @return bool True on success
	 */
	public function reset_leave_balances( $annual_balance = 20, $sick_balance = 10, $other_balance = 5 ) {
		global $wpdb;

		$result = $wpdb->update(
			$this->table,
			array(
				'annual_leave_balance' => floatval( $annual_balance ),
				'sick_leave_balance'   => floatval( $sick_balance ),
				'other_leave_balance'  => floatval( $other_balance ),
			),
			array(),
			array( '%f', '%f', '%f' )
		);

		if ( $result ) {
			$this->logger->info( 'Leave balances reset for all users', array(
				'annual' => $annual_balance,
				'sick'   => $sick_balance,
				'other'  => $other_balance,
			) );
		}

		return $result;
	}

	/**
	 * Bulk update users
	 *
	 * @param array $user_ids User IDs to update
	 * @param array $data Data to update
	 * @return int Number of users updated
	 */
	public function bulk_update_users( $user_ids, $data ) {
		$count = 0;
		foreach ( $user_ids as $user_id ) {
			if ( $this->update_user( $user_id, $data ) ) {
				$count++;
			}
		}
		return $count;
	}

	/**
	 * Export users to CSV
	 *
	 * @param array $args Query arguments
	 * @return string CSV content
	 */
	public function export_users_csv( $args = array() ) {
		$users = $this->get_users( array_merge( $args, array( 'limit' => -1 ) ) );

		$csv = "User ID,First Name,Last Name,Email,Phone,Role,Department,Position,Annual Leave,Sick Leave,Other Leave,Status,Created At\n";

		foreach ( $users as $user ) {
			$csv .= sprintf(
				"%d,%s,%s,%s,%s,%s,%s,%s,%.2f,%.2f,%.2f,%s,%s\n",
				$user->user_id,
				$user->first_name,
				$user->last_name,
				$user->email,
				$user->phone,
				$user->role,
				$user->department,
				$user->position,
				$user->annual_leave_balance,
				$user->sick_leave_balance,
				$user->other_leave_balance,
				$user->status,
				$user->created_at
			);
		}

		return $csv;
	}

	/**
	 * Sync WordPress user with plugin user
	 *
	 * @param int $wp_user_id WordPress user ID
	 * @return int|false Plugin user ID or false
	 */
	public function sync_wp_user_login( $wp_user_id ) {
		$wp_user = get_user_by( 'id', $wp_user_id );

		if ( ! $wp_user ) {
			return false;
		}

		$plugin_user = $this->get_user_by_email( $wp_user->user_email );

		if ( $plugin_user ) {
			// Update WordPress user ID
			$this->update_user( $plugin_user->user_id, array( 'wp_user_id' => $wp_user_id ) );
			return $plugin_user->user_id;
		}

		// Create new plugin user from WordPress user
		return $this->create_user( array(
			'email'      => $wp_user->user_email,
			'first_name' => $wp_user->first_name,
			'last_name'  => $wp_user->last_name,
			'wp_user_id' => $wp_user_id,
		) );
	}

	/**
	 * Sync new WordPress user
	 *
	 * @param int $wp_user_id WordPress user ID
	 * @return void
	 */
	public function sync_new_wp_user( $wp_user_id ) {
		$this->sync_wp_user_login( $wp_user_id );
	}

	/**
	 * Get a user by WordPress user ID
	 *
	 * @param int $wp_user_id WordPress user ID
	 * @return object|null User object or null
	 */
	public function get_user_by_wp_id( $wp_user_id ) {
		$wp_user_id = intval( $wp_user_id );
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE wp_user_id = %d",
				$wp_user_id
			)
		);
	}
}
