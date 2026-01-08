<?php
/**
 * Bulk Operations class for Leave Manager Plugin
 *
 * Handles bulk operations like approve/reject, user import, balance updates, etc.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Bulk_Operations class
 */
class Leave_Manager_Bulk_Operations {

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
	 * Bulk approve leave requests
	 *
	 * @param array $request_ids Array of request IDs
	 * @param int   $approver_id Approver user ID
	 * @return array Result array with success/failure counts
	 */
	public function bulk_approve_requests( $request_ids, $approver_id ) {
		global $wpdb;

		$approver_id = intval( $approver_id );
		$success     = 0;
		$failed      = 0;

		foreach ( $request_ids as $request_id ) {
			$request_id = intval( $request_id );

			$result = $wpdb->update(
				$wpdb->prefix . 'leave_manager_leave_requests',
				array(
					'status'         => 'approved',
					'approved_by'    => $approver_id,
					'approval_date'  => current_time( 'mysql' ),
					'updated_at'     => current_time( 'mysql' ),
				),
				array( 'request_id' => $request_id ),
				array( '%s', '%d', '%s', '%s' ),
				array( '%d' )
			);

			if ( $result !== false ) {
				$success++;
			} else {
				$failed++;
			}
		}

		$this->logger->info( 'Bulk approve requests', array( 'success' => $success, 'failed' => $failed ) );

		return array(
			'success' => $success,
			'failed'  => $failed,
		);
	}

	/**
	 * Bulk reject leave requests
	 *
	 * @param array  $request_ids Array of request IDs
	 * @param int    $approver_id Approver user ID
	 * @param string $reason Rejection reason
	 * @return array Result array with success/failure counts
	 */
	public function bulk_reject_requests( $request_ids, $approver_id, $reason = '' ) {
		global $wpdb;

		$approver_id = intval( $approver_id );
		$reason      = sanitize_textarea_field( $reason );
		$success     = 0;
		$failed      = 0;

		foreach ( $request_ids as $request_id ) {
			$request_id = intval( $request_id );

			$result = $wpdb->update(
				$wpdb->prefix . 'leave_manager_leave_requests',
				array(
					'status'            => 'rejected',
					'approved_by'       => $approver_id,
					'rejection_reason'  => $reason,
					'updated_at'        => current_time( 'mysql' ),
				),
				array( 'request_id' => $request_id ),
				array( '%s', '%d', '%s', '%s' ),
				array( '%d' )
			);

			if ( $result !== false ) {
				$success++;
			} else {
				$failed++;
			}
		}

		$this->logger->info( 'Bulk reject requests', array( 'success' => $success, 'failed' => $failed ) );

		return array(
			'success' => $success,
			'failed'  => $failed,
		);
	}

	/**
	 * Bulk update leave balance
	 *
	 * @param array $user_ids Array of user IDs
	 * @param string $leave_type Leave type
	 * @param float  $balance New balance
	 * @return array Result array with success/failure counts
	 */
	public function bulk_update_balance( $user_ids, $leave_type, $balance ) {
		global $wpdb;

		$balance = floatval( $balance );
		$success = 0;
		$failed  = 0;

		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		foreach ( $user_ids as $user_id ) {
			$user_id = intval( $user_id );

			$column = $leave_type . '_leave_balance';

			// Verify column exists
			$columns = $wpdb->get_results( "DESCRIBE {$users_table}" );
			$column_exists = false;
			foreach ( $columns as $col ) {
				if ( $col->Field === $column ) {
					$column_exists = true;
					break;
				}
			}

			if ( ! $column_exists ) {
				$failed++;
				continue;
			}

			$result = $wpdb->update(
				$users_table,
				array( $column => $balance ),
				array( 'user_id' => $user_id ),
				array( '%f' ),
				array( '%d' )
			);

			if ( $result !== false ) {
				$success++;
			} else {
				$failed++;
			}
		}

		$this->logger->info( 'Bulk update balance', array( 'success' => $success, 'failed' => $failed, 'leave_type' => $leave_type ) );

		return array(
			'success' => $success,
			'failed'  => $failed,
		);
	}

	/**
	 * Bulk import users from CSV
	 *
	 * @param array $csv_data CSV data array
	 * @return array Result array with success/failure counts
	 */
	public function bulk_import_users( $csv_data ) {
		$success = 0;
		$failed  = 0;
		$errors  = array();

		$users_class = new Leave_Manager_Users( $this->db, $this->logger );

		foreach ( $csv_data as $row_index => $row ) {
			// Skip header row
			if ( $row_index === 0 ) {
				continue;
			}

			// Validate required fields
			if ( empty( $row['first_name'] ) || empty( $row['email'] ) ) {
				$failed++;
				$errors[] = "Row " . ( $row_index + 1 ) . ": Missing required fields";
				continue;
			}

			$user_data = array(
				'first_name'  => $row['first_name'],
				'last_name'   => $row['last_name'] ?? '',
				'email'       => $row['email'],
				'role'        => $row['role'] ?? 'staff',
				'department'  => $row['department'] ?? '',
				'position'    => $row['position'] ?? '',
			);

			$result = $users_class->create_user( $user_data );

			if ( $result ) {
				$success++;
			} else {
				$failed++;
				$errors[] = "Row " . ( $row_index + 1 ) . ": Failed to create user";
			}
		}

		$this->logger->info( 'Bulk import users', array( 'success' => $success, 'failed' => $failed, 'errors' => count( $errors ) ) );

		return array(
			'success' => $success,
			'failed'  => $failed,
			'errors'  => $errors,
		);
	}

	/**
	 * Bulk send emails
	 *
	 * @param array  $user_ids Array of user IDs
	 * @param string $subject Email subject
	 * @param string $message Email message
	 * @return array Result array with success/failure counts
	 */
	public function bulk_send_emails( $user_ids, $subject, $message ) {
		global $wpdb;

		$subject = sanitize_text_field( $subject );
		$message = wp_kses_post( $message );
		$success = 0;
		$failed  = 0;

		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		foreach ( $user_ids as $user_id ) {
			$user_id = intval( $user_id );

			$user = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$users_table} WHERE user_id = %d",
					$user_id
				)
			);

			if ( ! $user ) {
				$failed++;
				continue;
			}

			$headers = array( 'Content-Type: text/html; charset=UTF-8' );
			$result  = wp_mail( $user->email, $subject, $message, $headers );

			if ( $result ) {
				$success++;
			} else {
				$failed++;
			}
		}

		$this->logger->info( 'Bulk send emails', array( 'success' => $success, 'failed' => $failed ) );

		return array(
			'success' => $success,
			'failed'  => $failed,
		);
	}

	/**
	 * Bulk delete requests
	 *
	 * @param array $request_ids Array of request IDs
	 * @return array Result array with success/failure counts
	 */
	public function bulk_delete_requests( $request_ids ) {
		global $wpdb;

		$success = 0;
		$failed  = 0;

		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		foreach ( $request_ids as $request_id ) {
			$request_id = intval( $request_id );

			$result = $wpdb->delete(
				$requests_table,
				array( 'request_id' => $request_id ),
				array( '%d' )
			);

			if ( $result ) {
				$success++;
			} else {
				$failed++;
			}
		}

		$this->logger->info( 'Bulk delete requests', array( 'success' => $success, 'failed' => $failed ) );

		return array(
			'success' => $success,
			'failed'  => $failed,
		);
	}

	/**
	 * Bulk update user status
	 *
	 * @param array  $user_ids Array of user IDs
	 * @param string $status New status
	 * @return array Result array with success/failure counts
	 */
	public function bulk_update_user_status( $user_ids, $status ) {
		global $wpdb;

		$status  = sanitize_text_field( $status );
		$success = 0;
		$failed  = 0;

		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		foreach ( $user_ids as $user_id ) {
			$user_id = intval( $user_id );

			$result = $wpdb->update(
				$users_table,
				array( 'status' => $status ),
				array( 'user_id' => $user_id ),
				array( '%s' ),
				array( '%d' )
			);

			if ( $result !== false ) {
				$success++;
			} else {
				$failed++;
			}
		}

		$this->logger->info( 'Bulk update user status', array( 'success' => $success, 'failed' => $failed, 'status' => $status ) );

		return array(
			'success' => $success,
			'failed'  => $failed,
		);
	}
}
