<?php
/**
 * Pro-Rata Calculations Manager Class
 * Handles pro-rata leave calculations based on joining date and employment period
 *
 * @package LeaveManager
 * @subpackage ProRata
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_ProRata_Calculator {

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
	 * Public holiday manager instance
	 *
	 * @var Leave_Manager_Public_Holiday_Manager
	 */
	private $public_holiday_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->transaction_manager = leave_manager_transaction();
		$this->security_framework = leave_manager_security();
		$this->public_holiday_manager = leave_manager_public_holiday();
	}

	/**
	 * Calculate pro-rata leave entitlement
	 *
	 * @param int    $user_id User ID
	 * @param string $joining_date Joining date (Y-m-d)
	 * @param int    $annual_leave_days Annual leave days
	 * @param string $calculation_method Calculation method (daily, monthly, yearly)
	 * @param string $end_date Optional end date for calculation (Y-m-d)
	 * @return float|WP_Error Pro-rata days or error
	 */
	public function calculate_prorata_entitlement( $user_id, $joining_date, $annual_leave_days, $calculation_method = 'daily', $end_date = null ) {
		// Validate inputs
		$joining_timestamp = strtotime( $joining_date );
		if ( false === $joining_timestamp ) {
			return new WP_Error( 'invalid_date', 'Invalid joining date format' );
		}

		// Set end date to today if not provided
		if ( null === $end_date ) {
			$end_date = current_time( 'Y-m-d' );
		}

		$end_timestamp = strtotime( $end_date );
		if ( false === $end_timestamp ) {
			return new WP_Error( 'invalid_date', 'Invalid end date format' );
		}

		// Validate joining date is not in future
		if ( $joining_timestamp > $end_timestamp ) {
			return new WP_Error( 'invalid_date', 'Joining date cannot be in the future' );
		}

		// Validate annual leave days
		if ( $annual_leave_days <= 0 ) {
			return new WP_Error( 'invalid_input', 'Annual leave days must be greater than 0' );
		}

		// Calculate based on method
		switch ( $calculation_method ) {
			case 'daily':
				return $this->calculate_daily_prorata( $joining_date, $end_date, $annual_leave_days );

			case 'monthly':
				return $this->calculate_monthly_prorata( $joining_date, $end_date, $annual_leave_days );

			case 'yearly':
				return $this->calculate_yearly_prorata( $joining_date, $end_date, $annual_leave_days );

			default:
				return new WP_Error( 'invalid_method', 'Invalid calculation method' );
		}
	}

	/**
	 * Calculate daily pro-rata
	 *
	 * @param string $joining_date Joining date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param int    $annual_leave_days Annual leave days
	 * @return float Pro-rata days
	 */
	private function calculate_daily_prorata( $joining_date, $end_date, $annual_leave_days ) {
		$joining_timestamp = strtotime( $joining_date );
		$end_timestamp = strtotime( $end_date );

		// Calculate total days worked
		$total_days = ( $end_timestamp - $joining_timestamp ) / ( 24 * 60 * 60 ) + 1;

		// Get working days (excluding weekends and public holidays)
		$working_days = $this->get_working_days( $joining_date, $end_date );

		// Get total working days in a year
		$year_start = date( 'Y-01-01', $joining_timestamp );
		$year_end = date( 'Y-12-31', $joining_timestamp );
		$total_working_days_in_year = $this->get_working_days( $year_start, $year_end );

		// Calculate pro-rata
		$prorata = ( $working_days / $total_working_days_in_year ) * $annual_leave_days;

		return round( $prorata, 2 );
	}

	/**
	 * Calculate monthly pro-rata
	 *
	 * @param string $joining_date Joining date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param int    $annual_leave_days Annual leave days
	 * @return float Pro-rata days
	 */
	private function calculate_monthly_prorata( $joining_date, $end_date, $annual_leave_days ) {
		$joining_timestamp = strtotime( $joining_date );
		$end_timestamp = strtotime( $end_date );

		// Calculate months worked
		$start_year = (int) date( 'Y', $joining_timestamp );
		$start_month = (int) date( 'm', $joining_timestamp );
		$end_year = (int) date( 'Y', $end_timestamp );
		$end_month = (int) date( 'm', $end_timestamp );

		$months_worked = ( $end_year - $start_year ) * 12 + ( $end_month - $start_month ) + 1;

		// Calculate pro-rata
		$prorata = ( $months_worked / 12 ) * $annual_leave_days;

		return round( $prorata, 2 );
	}

	/**
	 * Calculate yearly pro-rata
	 *
	 * @param string $joining_date Joining date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param int    $annual_leave_days Annual leave days
	 * @return float Pro-rata days
	 */
	private function calculate_yearly_prorata( $joining_date, $end_date, $annual_leave_days ) {
		$joining_timestamp = strtotime( $joining_date );
		$end_timestamp = strtotime( $end_date );

		// Calculate years worked
		$years_worked = ( $end_timestamp - $joining_timestamp ) / ( 365.25 * 24 * 60 * 60 );

		// Calculate pro-rata
		$prorata = $years_worked * $annual_leave_days;

		return round( $prorata, 2 );
	}

	/**
	 * Get working days between two dates
	 *
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return int Working days count
	 */
	public function get_working_days( $start_date, $end_date ) {
		$start_timestamp = strtotime( $start_date );
		$end_timestamp = strtotime( $end_date );

		$working_days = 0;
		$current_timestamp = $start_timestamp;

		while ( $current_timestamp <= $end_timestamp ) {
			$day_of_week = (int) date( 'w', $current_timestamp );

			// Skip weekends (0 = Sunday, 6 = Saturday)
			if ( 0 !== $day_of_week && 6 !== $day_of_week ) {
				$current_date = date( 'Y-m-d', $current_timestamp );

				// Skip public holidays
				if ( ! $this->public_holiday_manager->is_public_holiday( $current_date ) ) {
					$working_days++;
				}
			}

			$current_timestamp += 24 * 60 * 60;
		}

		return $working_days;
	}

	/**
	 * Store pro-rata calculation
	 *
	 * @param int    $user_id User ID
	 * @param string $joining_date Joining date (Y-m-d)
	 * @param int    $annual_leave_days Annual leave days
	 * @param string $calculation_method Calculation method
	 * @param float  $prorata_days Calculated pro-rata days
	 * @param array  $metadata Additional metadata
	 * @return int|WP_Error Calculation record ID or error
	 */
	public function store_prorata_calculation( $user_id, $joining_date, $annual_leave_days, $calculation_method, $prorata_days, $metadata = array() ) {
		global $wpdb;

		// Validate inputs
		if ( empty( $user_id ) || empty( $joining_date ) || empty( $calculation_method ) ) {
			return new WP_Error( 'invalid_input', 'Required fields are missing' );
		}

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to store calculations' );
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $user_id, $joining_date, $annual_leave_days, $calculation_method, $prorata_days, $metadata ) {
				$insert_result = $wpdb->insert(
					$wpdb->prefix . 'leave_manager_prorata_calculations',
					array(
						'user_id' => $user_id,
						'joining_date' => $joining_date,
						'annual_leave_days' => $annual_leave_days,
						'calculation_method' => $calculation_method,
						'prorata_days' => $prorata_days,
						'metadata' => wp_json_encode( $metadata ),
						'created_at' => current_time( 'mysql' ),
						'updated_at' => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%d', '%s', '%f', '%s', '%s', '%s' )
				);

				return $insert_result ? $wpdb->insert_id : false;
			},
			'store_prorata_calculation'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to store calculation' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'store_prorata_calculation',
			'prorata_calculation',
			$result,
			array(),
			array(
				'user_id' => $user_id,
				'joining_date' => $joining_date,
				'annual_leave_days' => $annual_leave_days,
				'prorata_days' => $prorata_days,
			)
		);

		do_action( 'leave_manager_prorata_calculated', $result, $user_id, $prorata_days );

		return $result;
	}

	/**
	 * Get pro-rata calculation for user
	 *
	 * @param int $user_id User ID
	 * @return object|null
	 */
	public function get_prorata_calculation( $user_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_prorata_calculations WHERE user_id = %d ORDER BY created_at DESC LIMIT 1",
				$user_id
			)
		);
	}

	/**
	 * Calculate leave balance with pro-rata
	 *
	 * @param int $user_id User ID
	 * @param int $leave_policy_id Leave policy ID
	 * @return float|WP_Error Leave balance or error
	 */
	public function calculate_leave_balance( $user_id, $leave_policy_id ) {
		global $wpdb;

		// Get user joining date
		$user_meta = get_user_meta( $user_id, 'leave_joining_date', true );
		if ( empty( $user_meta ) ) {
			return new WP_Error( 'no_joining_date', 'User joining date not found' );
		}

		// Get leave policy
		$policy = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_leave_policies WHERE id = %d",
				$leave_policy_id
			)
		);

		if ( null === $policy ) {
			return new WP_Error( 'policy_not_found', 'Leave policy not found' );
		}

		// Calculate pro-rata entitlement
		$prorata = $this->calculate_prorata_entitlement(
			$user_id,
			$user_meta,
			$policy->annual_leave_days,
			'daily'
		);

		if ( is_wp_error( $prorata ) ) {
			return $prorata;
		}

		// Get used leave
		$used_leave = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(days) FROM {$wpdb->prefix}leave_manager_leave_requests 
				WHERE user_id = %d AND status = 'approved'",
				$user_id
			)
		);

		$used_leave = $used_leave ? floatval( $used_leave ) : 0;

		// Calculate balance
		$balance = $prorata - $used_leave;

		return round( $balance, 2 );
	}

	/**
	 * Get pro-rata calculation history for user
	 *
	 * @param int $user_id User ID
	 * @return array
	 */
	public function get_prorata_history( $user_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_prorata_calculations WHERE user_id = %d ORDER BY created_at DESC",
				$user_id
			)
		);
	}

	/**
	 * Recalculate pro-rata for all users
	 *
	 * @return int Number of users recalculated
	 */
	public function recalculate_all_prorata() {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to recalculate' );
		}

		$users = get_users(
			array(
				'role__in' => array( 'employee', 'manager', 'administrator' ),
				'fields' => 'ID',
			)
		);

		$count = 0;

		foreach ( $users as $user_id ) {
			$joining_date = get_user_meta( $user_id, 'leave_joining_date', true );

			if ( ! empty( $joining_date ) ) {
				// Get default leave policy
				$policy = $wpdb->get_row(
					"SELECT * FROM {$wpdb->prefix}leave_manager_leave_policies WHERE is_default = 1 LIMIT 1"
				);

				if ( $policy ) {
					$prorata = $this->calculate_prorata_entitlement(
						$user_id,
						$joining_date,
						$policy->annual_leave_days,
						'daily'
					);

					if ( ! is_wp_error( $prorata ) ) {
						$this->store_prorata_calculation(
							$user_id,
							$joining_date,
							$policy->annual_leave_days,
							'daily',
							$prorata
						);

						$count++;
					}
				}
			}
		}

		return $count;
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_prorata' ) ) {
	/**
	 * Get pro-rata calculator instance
	 *
	 * @return Leave_Manager_ProRata_Calculator
	 */
	function leave_manager_prorata() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_ProRata_Calculator();
		}

		return $instance;
	}
}
