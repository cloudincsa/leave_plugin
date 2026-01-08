<?php
/**
 * Transaction Manager Class
 * Handles database transactions with rollback and error handling
 *
 * @package LeaveManager
 * @subpackage Database
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Transaction_Manager {

	/**
	 * Transaction stack
	 *
	 * @var array
	 */
	private $transaction_stack = array();

	/**
	 * Maximum retry attempts
	 *
	 * @var int
	 */
	private $max_retries = 3;

	/**
	 * Retry delay in milliseconds
	 *
	 * @var int
	 */
	private $retry_delay = 100;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize transaction manager
	}

	/**
	 * Begin a transaction
	 *
	 * @param string $transaction_name Transaction identifier
	 * @return bool
	 */
	public function begin_transaction( $transaction_name = 'default' ) {
		global $wpdb;

		try {
			$wpdb->query( 'START TRANSACTION' );
			$this->transaction_stack[] = $transaction_name;

			do_action( 'leave_manager_transaction_started', $transaction_name );

			return true;
		} catch ( Exception $e ) {
			$this->log_error( 'Transaction start failed', $e );
			return false;
		}
	}

	/**
	 * Commit a transaction
	 *
	 * @return bool
	 */
	public function commit() {
		global $wpdb;

		try {
			$wpdb->query( 'COMMIT' );
			$transaction_name = array_pop( $this->transaction_stack );

			do_action( 'leave_manager_transaction_committed', $transaction_name );

			return true;
		} catch ( Exception $e ) {
			$this->log_error( 'Transaction commit failed', $e );
			return false;
		}
	}

	/**
	 * Rollback a transaction
	 *
	 * @return bool
	 */
	public function rollback() {
		global $wpdb;

		try {
			$wpdb->query( 'ROLLBACK' );
			$transaction_name = array_pop( $this->transaction_stack );

			do_action( 'leave_manager_transaction_rolled_back', $transaction_name );

			return true;
		} catch ( Exception $e ) {
			$this->log_error( 'Transaction rollback failed', $e );
			return false;
		}
	}

	/**
	 * Execute a callback within a transaction
	 *
	 * @param callable $callback Function to execute
	 * @param string   $transaction_name Transaction identifier
	 * @return mixed Result of callback or false on failure
	 */
	public function execute_transaction( $callback, $transaction_name = 'default' ) {
		if ( ! $this->begin_transaction( $transaction_name ) ) {
			return false;
		}

		try {
			$result = call_user_func( $callback );

			if ( false === $result ) {
				$this->rollback();
				return false;
			}

			if ( ! $this->commit() ) {
				return false;
			}

			return $result;
		} catch ( Exception $e ) {
			$this->rollback();
			$this->log_error( 'Transaction execution failed', $e );
			return false;
		}
	}

	/**
	 * Execute a callback with automatic retry on failure
	 *
	 * @param callable $callback Function to execute
	 * @param string   $transaction_name Transaction identifier
	 * @return mixed Result of callback or false on all retries failed
	 */
	public function execute_with_retry( $callback, $transaction_name = 'default' ) {
		for ( $attempt = 1; $attempt <= $this->max_retries; $attempt++ ) {
			try {
				$result = $this->execute_transaction( $callback, $transaction_name . '_attempt_' . $attempt );

				if ( false !== $result ) {
					return $result;
				}
			} catch ( Exception $e ) {
				$this->log_error( "Retry attempt $attempt failed", $e );

				if ( $attempt < $this->max_retries ) {
					usleep( $this->retry_delay * 1000 );
				}
			}
		}

		return false;
	}

	/**
	 * Lock a row for update
	 *
	 * @param string $table Table name
	 * @param int    $id Row ID
	 * @param int    $timeout Lock timeout in seconds
	 * @return bool
	 */
	public function lock_row_for_update( $table, $id, $timeout = 30 ) {
		global $wpdb;

		try {
			$query = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d FOR UPDATE",
				$id
			);

			$result = $wpdb->get_row( $query );

			if ( null === $result ) {
				$this->log_error( 'Row lock failed: row not found', new Exception( "Table: $table, ID: $id" ) );
				return false;
			}

			return true;
		} catch ( Exception $e ) {
			$this->log_error( 'Row lock failed', $e );
			return false;
		}
	}

	/**
	 * Check if row is locked
	 *
	 * @param string $table Table name
	 * @param int    $id Row ID
	 * @return bool
	 */
	public function is_row_locked( $table, $id ) {
		global $wpdb;

		try {
			$query = $wpdb->prepare(
				"SELECT locked_by, locked_at FROM {$table} WHERE id = %d",
				$id
			);

			$result = $wpdb->get_row( $query );

			if ( null === $result || null === $result->locked_by ) {
				return false;
			}

			// Check if lock has expired (30 minutes)
			$locked_time = strtotime( $result->locked_at );
			$current_time = current_time( 'timestamp' );

			if ( ( $current_time - $locked_time ) > 1800 ) {
				// Lock expired, release it
				$wpdb->update(
					$table,
					array( 'locked_by' => null, 'locked_at' => null ),
					array( 'id' => $id ),
					array( '%d', '%s' ),
					array( '%d' )
				);
				return false;
			}

			return true;
		} catch ( Exception $e ) {
			$this->log_error( 'Row lock check failed', $e );
			return false;
		}
	}

	/**
	 * Release row lock
	 *
	 * @param string $table Table name
	 * @param int    $id Row ID
	 * @return bool
	 */
	public function release_row_lock( $table, $id ) {
		global $wpdb;

		try {
			$result = $wpdb->update(
				$table,
				array( 'locked_by' => null, 'locked_at' => null ),
				array( 'id' => $id ),
				array( '%d', '%s' ),
				array( '%d' )
			);

			return false !== $result;
		} catch ( Exception $e ) {
			$this->log_error( 'Row lock release failed', $e );
			return false;
		}
	}

	/**
	 * Get current transaction depth
	 *
	 * @return int
	 */
	public function get_transaction_depth() {
		return count( $this->transaction_stack );
	}

	/**
	 * Check if in transaction
	 *
	 * @return bool
	 */
	public function is_in_transaction() {
		return count( $this->transaction_stack ) > 0;
	}

	/**
	 * Log error
	 *
	 * @param string    $message Error message
	 * @param Exception $exception Exception object
	 * @return void
	 */
	private function log_error( $message, $exception ) {
		$error_data = array(
			'message' => $message,
			'exception' => $exception->getMessage(),
			'trace' => $exception->getTraceAsString(),
			'timestamp' => current_time( 'mysql' ),
		);

		do_action( 'leave_manager_transaction_error', $error_data );

		// Log to WordPress error log if WP_DEBUG is enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Leave Manager Transaction Error: ' . wp_json_encode( $error_data ) );
		}
	}

	/**
	 * Set maximum retry attempts
	 *
	 * @param int $max_retries Maximum number of retries
	 * @return void
	 */
	public function set_max_retries( $max_retries ) {
		$this->max_retries = max( 1, intval( $max_retries ) );
	}

	/**
	 * Set retry delay
	 *
	 * @param int $delay Delay in milliseconds
	 * @return void
	 */
	public function set_retry_delay( $delay ) {
		$this->retry_delay = max( 10, intval( $delay ) );
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_transaction' ) ) {
	/**
	 * Get transaction manager instance
	 *
	 * @return Leave_Manager_Transaction_Manager
	 */
	function leave_manager_transaction() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Transaction_Manager();
		}

		return $instance;
	}
}
