<?php
/**
 * Leave Requests class for Leave Manager Plugin
 *
 * Handles leave request operations including submission, approval, and management.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Leave_Requests class
 */
class Leave_Manager_Leave_Requests {

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
	 * Leave requests table name
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Valid leave types
	 *
	 * @var array
	 */
	private $valid_types = array( 'annual', 'sick', 'other' );

	/**
	 * Valid statuses
	 *
	 * @var array
	 */
	private $valid_statuses = array( 'pending', 'approved', 'rejected' );

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 */
	public function __construct( $db, $logger ) {
		$this->db     = $db;
		$this->logger = $logger;
		$this->table  = $db->leave_requests_table;
	}

	/**
	 * Submit a leave request
	 *
	 * @param array $request_data Leave request data
	 * @return int|false Request ID or false on error
	 */
	public function submit_request( $request_data ) {
		// Validate required fields
		if ( empty( $request_data['user_id'] ) || empty( $request_data['leave_type'] ) || 
			 empty( $request_data['start_date'] ) || empty( $request_data['end_date'] ) ) {
			$this->logger->error( 'Leave request submission failed: missing required fields' );
			return false;
		}

		// Validate leave type
		if ( ! in_array( $request_data['leave_type'], $this->valid_types, true ) ) {
			$this->logger->error( 'Leave request submission failed: invalid leave type', array( 'type' => $request_data['leave_type'] ) );
			return false;
		}

		// Validate dates
		$start_date = strtotime( $request_data['start_date'] );
		$end_date   = strtotime( $request_data['end_date'] );

		if ( ! $start_date || ! $end_date || $start_date > $end_date ) {
			$this->logger->error( 'Leave request submission failed: invalid dates' );
			return false;
		}

		// Check leave balance
		if ( ! $this->has_sufficient_balance( $request_data['user_id'], $request_data['leave_type'], $start_date, $end_date ) ) {
			$this->logger->warning( 'Leave request submission failed: insufficient balance' );
			return false;
		}

		// Prepare data
		$data = array(
			'user_id'    => intval( $request_data['user_id'] ),
			'leave_type' => sanitize_text_field( $request_data['leave_type'] ),
			'start_date' => date( 'Y-m-d', $start_date ),
			'end_date'   => date( 'Y-m-d', $end_date ),
			'reason'     => isset( $request_data['reason'] ) ? sanitize_textarea_field( $request_data['reason'] ) : '',
			'status'     => 'pending',
		);

		// Insert request
		$request_id = $this->db->insert(
			$this->table,
			$data,
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( $request_id ) {
			$this->logger->info( 'Leave request submitted successfully', array(
				'request_id' => $request_id,
				'user_id'    => $data['user_id'],
				'leave_type' => $data['leave_type'],
			) );
		} else {
			$this->logger->error( 'Leave request submission failed', array( 'error' => $this->db->get_last_error() ) );
		}

		return $request_id;
	}

	/**
	 * Get a leave request by ID
	 *
	 * @param int $request_id Request ID
	 * @return object|null Request object or null
	 */
	public function get_request( $request_id ) {
		$request_id = intval( $request_id );
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE request_id = %d",
				$request_id
			)
		);
	}

	/**
	 * Get leave requests
	 *
	 * @param array $args Query arguments
	 * @return array Array of request objects
	 */
	public function get_requests( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'user_id'    => '',
			'leave_type' => '',
			'status'     => '',
			'start_date' => '',
			'end_date'   => '',
			'orderby'    => 'created_at',
			'order'      => 'DESC',
			'limit'      => -1,
			'offset'     => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$values = array();

		if ( ! empty( $args['user_id'] ) ) {
			$where[] = 'user_id = %d';
			$values[] = intval( $args['user_id'] );
		}

		if ( ! empty( $args['leave_type'] ) ) {
			$where[] = 'leave_type = %s';
			$values[] = $args['leave_type'];
		}

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$values[] = $args['status'];
		}

		if ( ! empty( $args['start_date'] ) ) {
			$where[] = 'start_date >= %s';
			$values[] = date( 'Y-m-d', strtotime( $args['start_date'] ) );
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where[] = 'end_date <= %s';
			$values[] = date( 'Y-m-d', strtotime( $args['end_date'] ) );
		}

		$where_clause = implode( ' AND ', $where );
		$query = "SELECT * FROM {$this->table} WHERE {$where_clause} ORDER BY {$args['orderby']} {$args['order']}";

		if ( $args['limit'] > 0 ) {
			$query .= " LIMIT {$args['offset']}, {$args['limit']}";
		}

		if ( ! empty( $values ) ) {
			$query = $wpdb->prepare( $query, $values );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Update a leave request
	 *
	 * @param int   $request_id Request ID
	 * @param array $request_data Request data to update
	 * @return bool True on success
	 */
	public function update_request( $request_id, $request_data ) {
		$request_id = intval( $request_id );

		// Check if request exists
		if ( ! $this->get_request( $request_id ) ) {
			$this->logger->warning( 'Leave request update failed: request not found', array( 'request_id' => $request_id ) );
			return false;
		}

		// Prepare update data
		$data = array();
		if ( isset( $request_data['reason'] ) ) {
			$data['reason'] = sanitize_textarea_field( $request_data['reason'] );
		}
		if ( isset( $request_data['start_date'] ) ) {
			$data['start_date'] = date( 'Y-m-d', strtotime( $request_data['start_date'] ) );
		}
		if ( isset( $request_data['end_date'] ) ) {
			$data['end_date'] = date( 'Y-m-d', strtotime( $request_data['end_date'] ) );
		}

		if ( empty( $data ) ) {
			return true;
		}

		$result = $this->db->update(
			$this->table,
			$data,
			array( 'request_id' => $request_id ),
			null,
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'Leave request updated successfully', array( 'request_id' => $request_id ) );
		}

		return $result;
	}

	/**
	 * Delete a leave request
	 *
	 * @param int $request_id Request ID
	 * @return bool True on success
	 */
	public function delete_request( $request_id ) {
		$request_id = intval( $request_id );

		// Check if request exists
		if ( ! $this->get_request( $request_id ) ) {
			$this->logger->warning( 'Leave request deletion failed: request not found', array( 'request_id' => $request_id ) );
			return false;
		}

		$result = $this->db->delete(
			$this->table,
			array( 'request_id' => $request_id ),
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'Leave request deleted successfully', array( 'request_id' => $request_id ) );
		}

		return $result;
	}

	/**
	 * Approve a leave request
	 *
	 * @param int $request_id Request ID
	 * @param int $approved_by User ID of approver
	 * @return bool True on success
	 */
	public function approve_request( $request_id, $approved_by ) {
		$request_id = intval( $request_id );
		$approved_by = intval( $approved_by );

		// Check if request exists
		$request = $this->get_request( $request_id );
		if ( ! $request ) {
			$this->logger->warning( 'Leave request approval failed: request not found', array( 'request_id' => $request_id ) );
			return false;
		}

		// Update request status
		$result = $this->db->update(
			$this->table,
			array(
				'status'        => 'approved',
				'approved_by'   => $approved_by,
				'approval_date' => current_time( 'mysql' ),
			),
			array( 'request_id' => $request_id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'Leave request approved successfully', array(
				'request_id' => $request_id,
				'approved_by' => $approved_by,
			) );
		}

		return $result;
	}

	/**
	 * Reject a leave request
	 *
	 * @param int    $request_id Request ID
	 * @param string $reason Rejection reason
	 * @param int    $rejected_by User ID of rejector
	 * @return bool True on success
	 */
	public function reject_request( $request_id, $reason = '', $rejected_by = 0 ) {
		$request_id = intval( $request_id );
		$rejected_by = intval( $rejected_by );

		// Check if request exists
		if ( ! $this->get_request( $request_id ) ) {
			$this->logger->warning( 'Leave request rejection failed: request not found', array( 'request_id' => $request_id ) );
			return false;
		}

		// Update request status
		$result = $this->db->update(
			$this->table,
			array(
				'status'              => 'rejected',
				'rejection_reason'    => sanitize_textarea_field( $reason ),
				'approved_by'         => $rejected_by,
				'approval_date'       => current_time( 'mysql' ),
			),
			array( 'request_id' => $request_id ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'Leave request rejected successfully', array(
				'request_id' => $request_id,
				'reason'     => $reason,
				'rejected_by' => $rejected_by,
			) );
		}

		return $result;
	}

	/**
	 * Calculate leave days between two dates
	 *
	 * @param string $start_date Start date
	 * @param string $end_date End date
	 * @param bool   $include_weekends Include weekends in calculation
	 * @return int Number of days
	 */
	public function calculate_leave_days( $start_date, $end_date, $include_weekends = false ) {
		$start = new DateTime( $start_date );
		$end   = new DateTime( $end_date );
		$end->modify( '+1 day' ); // Include the end date

		$interval = $start->diff( $end );
		$days     = $interval->days;

		if ( ! $include_weekends ) {
			$period = new DatePeriod( $start, new DateInterval( 'P1D' ), $end );
			$days   = 0;

			foreach ( $period as $date ) {
				// 0 = Sunday, 6 = Saturday
				if ( ! in_array( $date->format( 'w' ), array( 0, 6 ), true ) ) {
					$days++;
				}
			}
		}

		return $days;
	}

	/**
	 * Get user leave balance
	 *
	 * @param int    $user_id User ID
	 * @param string $leave_type Leave type
	 * @return float Leave balance
	 */
	public function get_leave_balance( $user_id, $leave_type = 'annual' ) {
		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		$balance_column = $leave_type . '_leave_balance';
		$balance = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT $balance_column FROM $users_table WHERE user_id = %d",
				intval( $user_id )
			)
		);

		return floatval( $balance );
	}

	/**
	 * Check for conflicting leave requests
	 *
	 * @param int    $user_id User ID
	 * @param string $start_date Start date
	 * @param string $end_date End date
	 * @return bool True if conflict exists
	 */
	public function has_conflicting_request( $user_id, $start_date, $end_date ) {
		global $wpdb;

		$conflict = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table}
				 WHERE user_id = %d
				 AND status IN ('pending', 'approved')
				 AND (
					(start_date <= %s AND end_date >= %s) OR
					(start_date <= %s AND end_date >= %s) OR
					(start_date >= %s AND end_date <= %s)
				 )",
				intval( $user_id ),
				$end_date,
				$start_date,
				$end_date,
				$start_date,
				$start_date,
				$end_date
			)
		);

		return intval( $conflict ) > 0;
	}

	/**
	 * Check if user has sufficient leave balance
	 *
	 * @param int   $user_id User ID
	 * @param string $leave_type Type of leave
	 * @param int   $start_timestamp Start date timestamp
	 * @param int   $end_timestamp End date timestamp
	 * @return bool True if sufficient balance
	 */
	public function has_sufficient_balance( $user_id, $leave_type, $start_timestamp, $end_timestamp ) {
		global $wpdb;

		// Calculate business days
		$days_requested = $this->count_business_days( $start_timestamp, $end_timestamp );

		// Get user balance
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';
		$balance_column = str_replace( '-', '_', $leave_type ) . '_leave_balance';

		$user = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT $balance_column FROM $users_table WHERE user_id = %d",
				intval( $user_id )
			)
		);

		if ( ! $user ) {
			return false;
		}

		$current_balance = floatval( $user->$balance_column );

		return $current_balance >= $days_requested;
	}

	/**
	 * Count business days between two dates
	 *
	 * @param int $start_timestamp Start date timestamp
	 * @param int $end_timestamp End date timestamp
	 * @return int Number of business days
	 */
	private function count_business_days( $start_timestamp, $end_timestamp ) {
		$business_days = 0;
		$current = $start_timestamp;

		while ( $current <= $end_timestamp ) {
			$day_of_week = date( 'w', $current );
			if ( $day_of_week != 0 && $day_of_week != 6 ) { // 0 = Sunday, 6 = Saturday
				$business_days++;
			}
			$current += 86400; // 24 hours in seconds
		}

		return $business_days;
	}

	/**
	 * Deduct leave balance
	 *
	 * @param int    $user_id User ID
	 * @param string $leave_type Type of leave
	 * @param float  $days Days to deduct
	 * @return bool True on success
	 */
	public function deduct_leave_balance( $user_id, $leave_type, $days ) {
		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		$balance_column = str_replace( '-', '_', $leave_type ) . '_leave_balance';

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE $users_table SET $balance_column = $balance_column - %f WHERE user_id = %d",
				floatval( $days ),
				intval( $user_id )
			)
		);

		if ( false !== $result ) {
			$this->logger->info( 'Leave balance deducted', array(
				'user_id'     => $user_id,
				'leave_type'  => $leave_type,
				'days_deducted' => $days,
			) );
			return true;
		}

		return false;
	}

	/**
	 * Get leave statistics
	 *
	 * @return array Statistics data
	 */
	public function get_leave_statistics() {
		global $wpdb;

		return array(
			'total_requests'    => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" ) ),
			'pending_requests'  => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE status = 'pending'" ) ),
			'approved_requests' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE status = 'approved'" ) ),
			'rejected_requests' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table} WHERE status = 'rejected'" ) ),
		);
	}
}
