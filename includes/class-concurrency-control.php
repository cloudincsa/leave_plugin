<?php
/**
 * Concurrency Control Class
 * Handles row-level locking and concurrent access management
 *
 * @package LeaveManager
 * @subpackage Database
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Concurrency_Control {

	/**
	 * Lock timeout in seconds
	 *
	 * @var int
	 */
	private $lock_timeout = 30;

	/**
	 * Lock check interval in milliseconds
	 *
	 * @var int
	 */
	private $lock_check_interval = 100;

	/**
	 * Maximum lock wait time in seconds
	 *
	 * @var int
	 */
	private $max_lock_wait = 300;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize concurrency control
	}

	/**
	 * Acquire lock on approval request
	 *
	 * @param int $approval_request_id Approval request ID
	 * @param int $user_id User ID attempting to lock
	 * @return bool|WP_Error
	 */
	public function acquire_approval_lock( $approval_request_id, $user_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'leave_manager_approval_requests';
		$start_time = current_time( 'timestamp' );

		while ( true ) {
			// Check if row is already locked
			$locked_row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT locked_by, locked_at FROM {$table} WHERE id = %d",
					$approval_request_id
				)
			);

			if ( null === $locked_row ) {
				return new WP_Error( 'not_found', 'Approval request not found' );
			}

			// If not locked, acquire lock
			if ( null === $locked_row->locked_by ) {
				$result = $wpdb->update(
					$table,
					array(
						'locked_by' => $user_id,
						'locked_at' => current_time( 'mysql' ),
					),
					array( 'id' => $approval_request_id ),
					array( '%d', '%s' ),
					array( '%d' )
				);

				if ( false !== $result ) {
					do_action( 'leave_manager_lock_acquired', $approval_request_id, $user_id );
					return true;
				}
			} elseif ( $locked_row->locked_by === $user_id ) {
				// User already has the lock
				return true;
			}

			// Check if lock has expired
			$locked_time = strtotime( $locked_row->locked_at );
			$current_time = current_time( 'timestamp' );

			if ( ( $current_time - $locked_time ) > $this->lock_timeout ) {
				// Lock expired, release it and retry
				$this->release_approval_lock( $approval_request_id );
				continue;
			}

			// Check if we've waited too long
			if ( ( $current_time - $start_time ) > $this->max_lock_wait ) {
				return new WP_Error( 'lock_timeout', 'Could not acquire lock within timeout period' );
			}

			// Wait before retrying
			usleep( $this->lock_check_interval * 1000 );
		}
	}

	/**
	 * Release lock on approval request
	 *
	 * @param int $approval_request_id Approval request ID
	 * @return bool
	 */
	public function release_approval_lock( $approval_request_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'leave_manager_approval_requests';

		$result = $wpdb->update(
			$table,
			array(
				'locked_by' => null,
				'locked_at' => null,
			),
			array( 'id' => $approval_request_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);

		if ( false !== $result ) {
			do_action( 'leave_manager_lock_released', $approval_request_id );
			return true;
		}

		return false;
	}

	/**
	 * Check if approval request is locked
	 *
	 * @param int $approval_request_id Approval request ID
	 * @return bool
	 */
	public function is_approval_locked( $approval_request_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'leave_manager_approval_requests';

		$locked_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT locked_by, locked_at FROM {$table} WHERE id = %d",
				$approval_request_id
			)
		);

		if ( null === $locked_row || null === $locked_row->locked_by ) {
			return false;
		}

		// Check if lock has expired
		$locked_time = strtotime( $locked_row->locked_at );
		$current_time = current_time( 'timestamp' );

		if ( ( $current_time - $locked_time ) > $this->lock_timeout ) {
			// Lock expired
			$this->release_approval_lock( $approval_request_id );
			return false;
		}

		return true;
	}

	/**
	 * Get lock holder information
	 *
	 * @param int $approval_request_id Approval request ID
	 * @return array|null
	 */
	public function get_lock_holder( $approval_request_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'leave_manager_approval_requests';

		$locked_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT locked_by, locked_at FROM {$table} WHERE id = %d",
				$approval_request_id
			)
		);

		if ( null === $locked_row || null === $locked_row->locked_by ) {
			return null;
		}

		$user = get_userdata( $locked_row->locked_by );

		return array(
			'user_id' => $locked_row->locked_by,
			'user_name' => $user ? $user->display_name : 'Unknown',
			'locked_at' => $locked_row->locked_at,
			'lock_duration' => current_time( 'timestamp' ) - strtotime( $locked_row->locked_at ),
		);
	}

	/**
	 * Force release lock (admin only)
	 *
	 * @param int $approval_request_id Approval request ID
	 * @return bool|WP_Error
	 */
	public function force_release_lock( $approval_request_id ) {
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to force release locks' );
		}

		return $this->release_approval_lock( $approval_request_id );
	}

	/**
	 * Clean up expired locks
	 *
	 * @return int Number of locks released
	 */
	public function cleanup_expired_locks() {
		global $wpdb;

		$table = $wpdb->prefix . 'leave_manager_approval_requests';
		$cutoff_time = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) - $this->lock_timeout );

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET locked_by = NULL, locked_at = NULL WHERE locked_by IS NOT NULL AND locked_at < %s",
				$cutoff_time
			)
		);

		if ( false !== $result ) {
			do_action( 'leave_manager_locks_cleaned_up', $result );
		}

		return $result ? $result : 0;
	}

	/**
	 * Acquire lock with callback
	 *
	 * @param int      $approval_request_id Approval request ID
	 * @param int      $user_id User ID
	 * @param callable $callback Callback to execute while locked
	 * @return mixed|WP_Error Result of callback or error
	 */
	public function with_lock( $approval_request_id, $user_id, $callback ) {
		$lock = $this->acquire_approval_lock( $approval_request_id, $user_id );

		if ( is_wp_error( $lock ) ) {
			return $lock;
		}

		try {
			$result = call_user_func( $callback );
			return $result;
		} finally {
			$this->release_approval_lock( $approval_request_id );
		}
	}

	/**
	 * Set lock timeout
	 *
	 * @param int $timeout Timeout in seconds
	 * @return void
	 */
	public function set_lock_timeout( $timeout ) {
		$this->lock_timeout = max( 5, intval( $timeout ) );
	}

	/**
	 * Set lock check interval
	 *
	 * @param int $interval Interval in milliseconds
	 * @return void
	 */
	public function set_lock_check_interval( $interval ) {
		$this->lock_check_interval = max( 10, intval( $interval ) );
	}

	/**
	 * Set maximum lock wait time
	 *
	 * @param int $max_wait Maximum wait time in seconds
	 * @return void
	 */
	public function set_max_lock_wait( $max_wait ) {
		$this->max_lock_wait = max( 30, intval( $max_wait ) );
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_concurrency' ) ) {
	/**
	 * Get concurrency control instance
	 *
	 * @return Leave_Manager_Concurrency_Control
	 */
	function leave_manager_concurrency() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Concurrency_Control();
		}

		return $instance;
	}
}
