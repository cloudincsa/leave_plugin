<?php
/**
 * Leave Accrual and Encashment class for Leave Manager Plugin
 *
 * Handles automatic leave accrual based on tenure and leave encashment calculations.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Leave_Accrual class
 */
class Leave_Manager_Leave_Accrual {

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
	 * Accrual rules
	 *
	 * @var array
	 */
	private $accrual_rules = array(
		'0-6'   => 1.67,   // 0-6 months: 1.67 days per month
		'6-12'  => 1.67,   // 6-12 months: 1.67 days per month
		'12-24' => 1.75,   // 1-2 years: 1.75 days per month
		'24+'   => 2.08,   // 2+ years: 2.08 days per month
	);

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
	 * Calculate accrued leave for a user
	 *
	 * @param int $user_id User ID
	 * @return float Accrued leave days
	 */
	public function calculate_accrued_leave( $user_id ) {
		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		$user = $wpdb->get_row( $wpdb->prepare(
			"SELECT created_at FROM {$users_table} WHERE user_id = %d",
			$user_id
		) );

		if ( ! $user ) {
			return 0;
		}

		// Calculate months employed
		$start_date = strtotime( $user->created_at );
		$current_date = current_time( 'timestamp' );
		$months_employed = floor( ( $current_date - $start_date ) / ( 30 * 24 * 60 * 60 ) );

		// Get accrual rate based on tenure
		$accrual_rate = $this->get_accrual_rate( $months_employed );

		// Calculate accrued days
		$accrued_days = ( $months_employed / 12 ) * $accrual_rate * 12;

		return round( $accrued_days, 2 );
	}

	/**
	 * Get accrual rate based on tenure
	 *
	 * @param int $months_employed Months employed
	 * @return float Accrual rate (days per year)
	 */
	private function get_accrual_rate( $months_employed ) {
		if ( $months_employed < 6 ) {
			return $this->accrual_rules['0-6'] * 12;
		} elseif ( $months_employed < 12 ) {
			return $this->accrual_rules['6-12'] * 12;
		} elseif ( $months_employed < 24 ) {
			return $this->accrual_rules['12-24'] * 12;
		} else {
			return $this->accrual_rules['24+'] * 12;
		}
	}

	/**
	 * Apply accrual to user
	 *
	 * @param int $user_id User ID
	 * @return bool True on success
	 */
	public function apply_accrual( $user_id ) {
		$accrued_days = $this->calculate_accrued_leave( $user_id );

		if ( $accrued_days <= 0 ) {
			return false;
		}

		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		$result = $wpdb->update(
			$users_table,
			array( 'annual_leave_balance' => $accrued_days ),
			array( 'user_id' => $user_id ),
			array( '%f' ),
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'Leave accrual applied', array(
				'user_id' => $user_id,
				'accrued_days' => $accrued_days,
			) );
		}

		return $result;
	}

	/**
	 * Calculate leave encashment value
	 *
	 * @param int   $user_id User ID
	 * @param float $annual_salary Annual salary
	 * @param float $minimum_carryover Minimum carryover days (not encashed)
	 * @return array Encashment calculation
	 */
	public function calculate_encashment( $user_id, $annual_salary, $minimum_carryover = 5 ) {
		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		$user = $wpdb->get_row( $wpdb->prepare(
			"SELECT annual_leave_balance FROM {$users_table} WHERE user_id = %d",
			$user_id
		) );

		if ( ! $user ) {
			return array(
				'success' => false,
				'message' => 'User not found',
			);
		}

		$unused_days = max( 0, $user->annual_leave_balance - $minimum_carryover );
		$daily_rate = $annual_salary / 365;
		$encashment_value = $unused_days * $daily_rate;

		return array(
			'success' => true,
			'user_id' => $user_id,
			'total_balance' => $user->annual_leave_balance,
			'minimum_carryover' => $minimum_carryover,
			'unused_days' => $unused_days,
			'daily_rate' => round( $daily_rate, 2 ),
			'encashment_value' => round( $encashment_value, 2 ),
			'annual_salary' => $annual_salary,
		);
	}

	/**
	 * Process leave encashment
	 *
	 * @param int   $user_id User ID
	 * @param float $annual_salary Annual salary
	 * @param float $minimum_carryover Minimum carryover days
	 * @return array Result with encashment details
	 */
	public function process_encashment( $user_id, $annual_salary, $minimum_carryover = 5 ) {
		$calculation = $this->calculate_encashment( $user_id, $annual_salary, $minimum_carryover );

		if ( ! $calculation['success'] ) {
			return $calculation;
		}

		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		// Update leave balance to minimum carryover
		$result = $wpdb->update(
			$users_table,
			array( 'annual_leave_balance' => $minimum_carryover ),
			array( 'user_id' => $user_id ),
			array( '%f' ),
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'Leave encashment processed', array(
				'user_id' => $user_id,
				'encashment_value' => $calculation['encashment_value'],
				'unused_days' => $calculation['unused_days'],
			) );

			$calculation['status'] = 'success';
			$calculation['message'] = 'Encashment processed successfully';
		} else {
			$calculation['status'] = 'failed';
			$calculation['message'] = 'Failed to update leave balance';
		}

		return $calculation;
	}

	/**
	 * Set custom accrual rules
	 *
	 * @param array $rules Accrual rules
	 * @return bool True on success
	 */
	public function set_accrual_rules( $rules ) {
		if ( ! is_array( $rules ) ) {
			return false;
		}

		$this->accrual_rules = array_merge( $this->accrual_rules, $rules );
		$this->logger->info( 'Accrual rules updated', array( 'rules' => $this->accrual_rules ) );

		return true;
	}

	/**
	 * Get accrual rules
	 *
	 * @return array Accrual rules
	 */
	public function get_accrual_rules() {
		return $this->accrual_rules;
	}

	/**
	 * Bulk apply accrual to all users
	 *
	 * @return array Results
	 */
	public function bulk_apply_accrual() {
		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		$users = $wpdb->get_results( "
			SELECT user_id FROM {$users_table}
			WHERE status = 'active'
		" );

		$results = array(
			'total' => count( $users ),
			'processed' => 0,
			'failed' => 0,
		);

		foreach ( $users as $user ) {
			if ( $this->apply_accrual( $user->user_id ) ) {
				$results['processed']++;
			} else {
				$results['failed']++;
			}
		}

		$this->logger->info( 'Bulk accrual completed', $results );

		return $results;
	}
}
