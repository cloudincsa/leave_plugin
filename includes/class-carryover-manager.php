<?php
/**
 * Carry-Over Management Class
 * Handles leave carry-over, year-end processing, and carry-over policies
 *
 * @package LeaveManager
 * @subpackage CarryOver
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_CarryOver_Manager {

	/**
	 * Transaction manager instance
	 *
	 * @var Leave_Manager_Transaction_Manager
	 */
	private $transaction_manager;

	/**
	 * Security framework instance
	 *
	 * @var Leave_Manager_Security_Framework
	 */
	private $security_framework;

	/**
	 * Pro-rata calculator instance
	 *
	 * @var Leave_Manager_ProRata_Calculator
	 */
	private $prorata_calculator;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->transaction_manager = leave_manager_transaction();
		$this->security_framework = leave_manager_security();
		$this->prorata_calculator = leave_manager_prorata();
	}

	/**
	 * Create carry-over policy
	 *
	 * @param string $name Policy name
	 * @param array  $config Policy configuration
	 * @return int|WP_Error Policy ID or error
	 */
	public function create_carryover_policy( $name, $config ) {
		global $wpdb;

		// Validate inputs
		if ( empty( $name ) || empty( $config ) ) {
			return new WP_Error( 'invalid_input', 'Required fields are missing' );
		}

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to create policies' );
		}

		// Validate configuration
		$validation = $this->validate_carryover_config( $config );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $name, $config ) {
				$insert_result = $wpdb->insert(
					$wpdb->prefix . 'leave_manager_carryover_policies',
					array(
						'name' => $name,
						'max_carryover_days' => $config['max_carryover_days'],
						'carryover_expiry_months' => $config['carryover_expiry_months'],
						'allow_carryover' => $config['allow_carryover'] ? 1 : 0,
						'allow_encashment' => $config['allow_encashment'] ? 1 : 0,
						'encashment_rate' => $config['encashment_rate'],
						'year_end_date' => $config['year_end_date'],
						'config' => wp_json_encode( $config ),
						'created_at' => current_time( 'mysql' ),
						'updated_at' => current_time( 'mysql' ),
					),
					array( '%s', '%d', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s' )
				);

				return $insert_result ? $wpdb->insert_id : false;
			},
			'create_carryover_policy'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to create policy' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'create_carryover_policy',
			'carryover_policy',
			$result,
			array(),
			array( 'name' => $name, 'config' => $config )
		);

		do_action( 'leave_manager_carryover_policy_created', $result, $name );

		return $result;
	}

	/**
	 * Validate carry-over configuration
	 *
	 * @param array $config Configuration
	 * @return bool|WP_Error
	 */
	private function validate_carryover_config( $config ) {
		if ( ! isset( $config['max_carryover_days'] ) || $config['max_carryover_days'] < 0 ) {
			return new WP_Error( 'invalid_config', 'Invalid max_carryover_days' );
		}

		if ( ! isset( $config['carryover_expiry_months'] ) || $config['carryover_expiry_months'] < 0 ) {
			return new WP_Error( 'invalid_config', 'Invalid carryover_expiry_months' );
		}

		if ( ! isset( $config['allow_carryover'] ) ) {
			return new WP_Error( 'invalid_config', 'allow_carryover is required' );
		}

		if ( ! isset( $config['allow_encashment'] ) ) {
			return new WP_Error( 'invalid_config', 'allow_encashment is required' );
		}

		if ( ! isset( $config['encashment_rate'] ) || $config['encashment_rate'] < 0 ) {
			return new WP_Error( 'invalid_config', 'Invalid encashment_rate' );
		}

		if ( ! isset( $config['year_end_date'] ) || ! strtotime( $config['year_end_date'] ) ) {
			return new WP_Error( 'invalid_config', 'Invalid year_end_date' );
		}

		return true;
	}

	/**
	 * Process year-end carry-over
	 *
	 * @param int    $user_id User ID
	 * @param int    $policy_id Carry-over policy ID
	 * @param int    $year Year to process
	 * @return array|WP_Error Processing result or error
	 */
	public function process_year_end_carryover( $user_id, $policy_id, $year ) {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to process carry-over' );
		}

		// Get policy
		$policy = $this->get_carryover_policy( $policy_id );
		if ( null === $policy ) {
			return new WP_Error( 'policy_not_found', 'Carry-over policy not found' );
		}

		// Get user's leave balance
		$balance = $this->prorata_calculator->calculate_leave_balance( $user_id, 1 );
		if ( is_wp_error( $balance ) ) {
			return $balance;
		}

		$result = array(
			'user_id' => $user_id,
			'year' => $year,
			'previous_balance' => $balance,
			'carryover_days' => 0,
			'expired_days' => 0,
			'encashment_amount' => 0,
			'new_balance' => 0,
		);

		// Calculate carry-over
		if ( $policy->allow_carryover && $balance > 0 ) {
			$carryover_days = min( $balance, $policy->max_carryover_days );
			$result['carryover_days'] = $carryover_days;

			// Calculate expired days
			$expired_days = max( 0, $balance - $policy->max_carryover_days );
			$result['expired_days'] = $expired_days;

			// Calculate encashment
			if ( $policy->allow_encashment && $expired_days > 0 ) {
				$result['encashment_amount'] = $expired_days * $policy->encashment_rate;
			}

			$result['new_balance'] = $carryover_days;
		}

		// Store carry-over record
		$carryover_id = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $user_id, $policy_id, $year, $result ) {
				$insert_result = $wpdb->insert(
					$wpdb->prefix . 'leave_manager_carryover_records',
					array(
						'user_id' => $user_id,
						'policy_id' => $policy_id,
						'year' => $year,
						'previous_balance' => $result['previous_balance'],
						'carryover_days' => $result['carryover_days'],
						'expired_days' => $result['expired_days'],
						'encashment_amount' => $result['encashment_amount'],
						'new_balance' => $result['new_balance'],
						'processed_at' => current_time( 'mysql' ),
						'created_at' => current_time( 'mysql' ),
					),
					array( '%d', '%d', '%d', '%f', '%f', '%f', '%f', '%f', '%s', '%s' )
				);

				return $insert_result ? $wpdb->insert_id : false;
			},
			'process_year_end_carryover'
		);

		if ( false === $carryover_id ) {
			return new WP_Error( 'db_error', 'Failed to process carry-over' );
		}

		$result['carryover_id'] = $carryover_id;

		// Log audit event
		$this->security_framework->log_audit_event(
			'process_year_end_carryover',
			'carryover_record',
			$carryover_id,
			array(),
			$result
		);

		do_action( 'leave_manager_year_end_carryover_processed', $carryover_id, $user_id, $year );

		return $result;
	}

	/**
	 * Bulk process year-end carry-over for all users
	 *
	 * @param int $policy_id Carry-over policy ID
	 * @param int $year Year to process
	 * @return array Processing results
	 */
	public function bulk_process_year_end_carryover( $policy_id, $year ) {
		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to process carry-over' );
		}

		$users = get_users(
			array(
				'role__in' => array( 'employee', 'manager', 'administrator' ),
				'fields' => 'ID',
			)
		);

		$results = array(
			'total_users' => count( $users ),
			'processed' => 0,
			'failed' => 0,
			'details' => array(),
		);

		foreach ( $users as $user_id ) {
			$result = $this->process_year_end_carryover( $user_id, $policy_id, $year );

			if ( is_wp_error( $result ) ) {
				$results['failed']++;
				$results['details'][ $user_id ] = array( 'error' => $result->get_error_message() );
			} else {
				$results['processed']++;
				$results['details'][ $user_id ] = $result;
			}
		}

		return $results;
	}

	/**
	 * Get carry-over policy
	 *
	 * @param int $policy_id Policy ID
	 * @return object|null
	 */
	public function get_carryover_policy( $policy_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_carryover_policies WHERE id = %d",
				$policy_id
			)
		);
	}

	/**
	 * Get all carry-over policies
	 *
	 * @return array
	 */
	public function get_all_carryover_policies() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}leave_manager_carryover_policies ORDER BY created_at DESC"
		);
	}

	/**
	 * Update carry-over policy
	 *
	 * @param int   $policy_id Policy ID
	 * @param array $data Data to update
	 * @return bool|WP_Error
	 */
	public function update_carryover_policy( $policy_id, $data ) {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to update policies' );
		}

		// Validate policy exists
		$policy = $this->get_carryover_policy( $policy_id );
		if ( null === $policy ) {
			return new WP_Error( 'not_found', 'Policy not found' );
		}

		// Prepare update data
		$update_data = array( 'updated_at' => current_time( 'mysql' ) );
		$format = array( '%s' );

		if ( isset( $data['name'] ) ) {
			$update_data['name'] = $data['name'];
			$format[] = '%s';
		}

		if ( isset( $data['max_carryover_days'] ) ) {
			$update_data['max_carryover_days'] = $data['max_carryover_days'];
			$format[] = '%d';
		}

		if ( isset( $data['config'] ) ) {
			$update_data['config'] = wp_json_encode( $data['config'] );
			$format[] = '%s';
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $policy_id, $update_data, $format ) {
				return $wpdb->update(
					$wpdb->prefix . 'leave_manager_carryover_policies',
					$update_data,
					array( 'id' => $policy_id ),
					$format,
					array( '%d' )
				);
			},
			'update_carryover_policy'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to update policy' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'update_carryover_policy',
			'carryover_policy',
			$policy_id,
			(array) $policy,
			$data
		);

		do_action( 'leave_manager_carryover_policy_updated', $policy_id );

		return true;
	}

	/**
	 * Get carry-over records for user
	 *
	 * @param int $user_id User ID
	 * @return array
	 */
	public function get_carryover_records_for_user( $user_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_carryover_records WHERE user_id = %d ORDER BY year DESC",
				$user_id
			)
		);
	}

	/**
	 * Get carry-over record for user and year
	 *
	 * @param int $user_id User ID
	 * @param int $year Year
	 * @return object|null
	 */
	public function get_carryover_record_for_year( $user_id, $year ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_carryover_records WHERE user_id = %d AND year = %d",
				$user_id,
				$year
			)
		);
	}

	/**
	 * Get carry-over balance for user
	 *
	 * @param int $user_id User ID
	 * @return float
	 */
	public function get_carryover_balance( $user_id ) {
		global $wpdb;

		$record = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT new_balance FROM {$wpdb->prefix}leave_manager_carryover_records WHERE user_id = %d ORDER BY year DESC LIMIT 1",
				$user_id
			)
		);

		return $record ? floatval( $record->new_balance ) : 0;
	}

	/**
	 * Delete carry-over policy
	 *
	 * @param int $policy_id Policy ID
	 * @return bool|WP_Error
	 */
	public function delete_carryover_policy( $policy_id ) {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to delete policies' );
		}

		// Validate policy exists
		$policy = $this->get_carryover_policy( $policy_id );
		if ( null === $policy ) {
			return new WP_Error( 'not_found', 'Policy not found' );
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $policy_id ) {
				return $wpdb->delete(
					$wpdb->prefix . 'leave_manager_carryover_policies',
					array( 'id' => $policy_id ),
					array( '%d' )
				);
			},
			'delete_carryover_policy'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to delete policy' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'delete_carryover_policy',
			'carryover_policy',
			$policy_id,
			(array) $policy,
			array()
		);

		do_action( 'leave_manager_carryover_policy_deleted', $policy_id );

		return true;
	}

	/**
	 * Schedule year-end carry-over processing
	 *
	 * @param int $policy_id Carry-over policy ID
	 * @param int $year Year to process
	 * @return bool
	 */
	public function schedule_year_end_processing( $policy_id, $year ) {
		// Get policy to find year-end date
		$policy = $this->get_carryover_policy( $policy_id );
		if ( null === $policy ) {
			return false;
		}

		// Schedule event
		$timestamp = strtotime( $policy->year_end_date . ' ' . $year );
		wp_schedule_single_event( $timestamp, 'leave_manager_process_year_end_carryover', array( $policy_id, $year ) );

		return true;
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_carryover' ) ) {
	/**
	 * Get carry-over manager instance
	 *
	 * @return Leave_Manager_CarryOver_Manager
	 */
	function leave_manager_carryover() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_CarryOver_Manager();
		}

		return $instance;
	}
}
