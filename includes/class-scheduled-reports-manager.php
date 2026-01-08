<?php
/**
 * Scheduled Reports Manager Class
 * Manages scheduled report generation and distribution
 *
 * @package LeaveManager
 * @subpackage ScheduledReports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Scheduled_Reports_Manager {

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
	 * Custom report builder instance
	 *
	 * @var Leave_Manager_Custom_Report_Builder
	 */
	private $report_builder;

	/**
	 * Schedule frequencies
	 *
	 * @var array
	 */
	private $frequencies = array(
		'daily' => 'Daily',
		'weekly' => 'Weekly',
		'monthly' => 'Monthly',
		'quarterly' => 'Quarterly',
		'yearly' => 'Yearly',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->transaction_manager = leave_manager_transaction();
		$this->security_framework = leave_manager_security();
		$this->report_builder = leave_manager_custom_report();
	}

	/**
	 * Create scheduled report
	 *
	 * @param string $name Report name
	 * @param int    $report_id Custom report ID
	 * @param string $frequency Schedule frequency
	 * @param array  $recipients Email recipients
	 * @param array  $config Additional configuration
	 * @return int|WP_Error Scheduled report ID or error
	 */
	public function create_scheduled_report( $name, $report_id, $frequency, $recipients, $config = array() ) {
		global $wpdb;

		// Validate inputs
		if ( empty( $name ) || empty( $report_id ) || empty( $frequency ) || empty( $recipients ) ) {
			return new WP_Error( 'invalid_input', 'Required fields are missing' );
		}

		// Validate frequency
		if ( ! isset( $this->frequencies[ $frequency ] ) ) {
			return new WP_Error( 'invalid_frequency', 'Invalid frequency' );
		}

		// Validate report exists
		if ( null === $this->report_builder->get_custom_report( $report_id ) ) {
			return new WP_Error( 'report_not_found', 'Report not found' );
		}

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to create scheduled reports' );
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $name, $report_id, $frequency, $recipients, $config ) {
				$insert_result = $wpdb->insert(
					$wpdb->prefix . 'leave_manager_scheduled_reports',
					array(
						'name' => $name,
						'report_id' => $report_id,
						'frequency' => $frequency,
						'recipients' => wp_json_encode( $recipients ),
						'config' => wp_json_encode( $config ),
						'is_active' => 1,
						'last_run' => null,
						'next_run' => $this->calculate_next_run( $frequency ),
						'created_by' => get_current_user_id(),
						'created_at' => current_time( 'mysql' ),
						'updated_at' => current_time( 'mysql' ),
					),
					array( '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s' )
				);

				return $insert_result ? $wpdb->insert_id : false;
			},
			'create_scheduled_report'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to create scheduled report' );
		}

		// Schedule the first run
		$this->schedule_report_run( $result );

		// Log audit event
		$this->security_framework->log_audit_event(
			'create_scheduled_report',
			'scheduled_report',
			$result,
			array(),
			array( 'name' => $name, 'frequency' => $frequency )
		);

		do_action( 'leave_manager_scheduled_report_created', $result, $name );

		return $result;
	}

	/**
	 * Calculate next run time
	 *
	 * @param string $frequency Frequency
	 * @return string Next run time (Y-m-d H:i:s)
	 */
	private function calculate_next_run( $frequency ) {
		$current_time = current_time( 'timestamp' );

		switch ( $frequency ) {
			case 'daily':
				$next_run = strtotime( '+1 day', $current_time );
				break;

			case 'weekly':
				$next_run = strtotime( '+1 week', $current_time );
				break;

			case 'monthly':
				$next_run = strtotime( '+1 month', $current_time );
				break;

			case 'quarterly':
				$next_run = strtotime( '+3 months', $current_time );
				break;

			case 'yearly':
				$next_run = strtotime( '+1 year', $current_time );
				break;

			default:
				$next_run = strtotime( '+1 day', $current_time );
		}

		return date( 'Y-m-d H:i:s', $next_run );
	}

	/**
	 * Schedule report run
	 *
	 * @param int $scheduled_report_id Scheduled report ID
	 * @return bool
	 */
	private function schedule_report_run( $scheduled_report_id ) {
		$report = $this->get_scheduled_report( $scheduled_report_id );

		if ( null === $report ) {
			return false;
		}

		$timestamp = strtotime( $report->next_run );

		wp_schedule_single_event( $timestamp, 'leave_manager_run_scheduled_report', array( $scheduled_report_id ) );

		return true;
	}

	/**
	 * Execute scheduled report
	 *
	 * @param int $scheduled_report_id Scheduled report ID
	 * @return bool|WP_Error
	 */
	public function execute_scheduled_report( $scheduled_report_id ) {
		global $wpdb;

		// Get scheduled report
		$scheduled_report = $this->get_scheduled_report( $scheduled_report_id );
		if ( null === $scheduled_report ) {
			return new WP_Error( 'not_found', 'Scheduled report not found' );
		}

		// Generate report
		$report_data = $this->report_builder->generate_report( $scheduled_report->report_id );
		if ( is_wp_error( $report_data ) ) {
			return $report_data;
		}

		// Export to CSV
		$csv_content = $this->report_builder->export_to_csv( $scheduled_report->report_id );
		if ( is_wp_error( $csv_content ) ) {
			return $csv_content;
		}

		// Send email
		$recipients = json_decode( $scheduled_report->recipients, true );
		$result = $this->send_report_email( $scheduled_report->name, $csv_content, $recipients );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Update last run
		$next_run = $this->calculate_next_run( $scheduled_report->frequency );

		$update_result = $wpdb->update(
			$wpdb->prefix . 'leave_manager_scheduled_reports',
			array(
				'last_run' => current_time( 'mysql' ),
				'next_run' => $next_run,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $scheduled_report_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $update_result ) {
			return new WP_Error( 'db_error', 'Failed to update scheduled report' );
		}

		// Reschedule next run
		$this->schedule_report_run( $scheduled_report_id );

		// Log audit event
		$this->security_framework->log_audit_event(
			'execute_scheduled_report',
			'scheduled_report',
			$scheduled_report_id,
			array(),
			array( 'recipients' => count( $recipients ) )
		);

		do_action( 'leave_manager_scheduled_report_executed', $scheduled_report_id );

		return true;
	}

	/**
	 * Send report email
	 *
	 * @param string $report_name Report name
	 * @param string $csv_content CSV content
	 * @param array  $recipients Email recipients
	 * @return bool|WP_Error
	 */
	private function send_report_email( $report_name, $csv_content, $recipients ) {
		if ( empty( $recipients ) ) {
			return new WP_Error( 'no_recipients', 'No recipients specified' );
		}

		// Create temporary file
		$temp_file = wp_tempnam( 'leave-report-' . time() . '.csv' );
		file_put_contents( $temp_file, $csv_content );

		// Prepare email
		$subject = 'Leave Manager Report: ' . $report_name;
		$message = 'Please find attached the scheduled report: ' . $report_name;

		// Send email with attachment
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$sent = wp_mail( $recipients, $subject, $message, $headers, array( $temp_file ) );

		// Clean up
		unlink( $temp_file );

		if ( ! $sent ) {
			return new WP_Error( 'email_failed', 'Failed to send email' );
		}

		return true;
	}

	/**
	 * Get scheduled report
	 *
	 * @param int $scheduled_report_id Scheduled report ID
	 * @return object|null
	 */
	public function get_scheduled_report( $scheduled_report_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_scheduled_reports WHERE id = %d",
				$scheduled_report_id
			)
		);
	}

	/**
	 * Get all scheduled reports
	 *
	 * @return array
	 */
	public function get_all_scheduled_reports() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}leave_manager_scheduled_reports ORDER BY created_at DESC"
		);
	}

	/**
	 * Get active scheduled reports
	 *
	 * @return array
	 */
	public function get_active_scheduled_reports() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}leave_manager_scheduled_reports WHERE is_active = 1 ORDER BY next_run ASC"
		);
	}

	/**
	 * Update scheduled report
	 *
	 * @param int   $scheduled_report_id Scheduled report ID
	 * @param array $data Data to update
	 * @return bool|WP_Error
	 */
	public function update_scheduled_report( $scheduled_report_id, $data ) {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to update scheduled reports' );
		}

		// Validate report exists
		$report = $this->get_scheduled_report( $scheduled_report_id );
		if ( null === $report ) {
			return new WP_Error( 'not_found', 'Scheduled report not found' );
		}

		$update_data = array( 'updated_at' => current_time( 'mysql' ) );
		$format = array( '%s' );

		if ( isset( $data['name'] ) ) {
			$update_data['name'] = $data['name'];
			$format[] = '%s';
		}

		if ( isset( $data['frequency'] ) ) {
			$update_data['frequency'] = $data['frequency'];
			$format[] = '%s';
		}

		if ( isset( $data['recipients'] ) ) {
			$update_data['recipients'] = wp_json_encode( $data['recipients'] );
			$format[] = '%s';
		}

		if ( isset( $data['is_active'] ) ) {
			$update_data['is_active'] = $data['is_active'] ? 1 : 0;
			$format[] = '%d';
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $scheduled_report_id, $update_data, $format ) {
				return $wpdb->update(
					$wpdb->prefix . 'leave_manager_scheduled_reports',
					$update_data,
					array( 'id' => $scheduled_report_id ),
					$format,
					array( '%d' )
				);
			},
			'update_scheduled_report'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to update scheduled report' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'update_scheduled_report',
			'scheduled_report',
			$scheduled_report_id,
			(array) $report,
			$data
		);

		do_action( 'leave_manager_scheduled_report_updated', $scheduled_report_id );

		return true;
	}

	/**
	 * Delete scheduled report
	 *
	 * @param int $scheduled_report_id Scheduled report ID
	 * @return bool|WP_Error
	 */
	public function delete_scheduled_report( $scheduled_report_id ) {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to delete scheduled reports' );
		}

		// Validate report exists
		$report = $this->get_scheduled_report( $scheduled_report_id );
		if ( null === $report ) {
			return new WP_Error( 'not_found', 'Scheduled report not found' );
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $scheduled_report_id ) {
				return $wpdb->delete(
					$wpdb->prefix . 'leave_manager_scheduled_reports',
					array( 'id' => $scheduled_report_id ),
					array( '%d' )
				);
			},
			'delete_scheduled_report'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to delete scheduled report' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'delete_scheduled_report',
			'scheduled_report',
			$scheduled_report_id,
			(array) $report,
			array()
		);

		do_action( 'leave_manager_scheduled_report_deleted', $scheduled_report_id );

		return true;
	}

	/**
	 * Get report frequencies
	 *
	 * @return array
	 */
	public function get_frequencies() {
		return $this->frequencies;
	}

	/**
	 * Process overdue scheduled reports
	 *
	 * @return int Number of reports processed
	 */
	public function process_overdue_reports() {
		global $wpdb;

		$current_time = current_time( 'mysql' );

		$reports = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}leave_manager_scheduled_reports 
				WHERE is_active = 1 AND next_run <= %s",
				$current_time
			)
		);

		$count = 0;

		foreach ( $reports as $report ) {
			$result = $this->execute_scheduled_report( $report->id );

			if ( ! is_wp_error( $result ) ) {
				$count++;
			}
		}

		return $count;
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_scheduled_reports' ) ) {
	/**
	 * Get scheduled reports manager instance
	 *
	 * @return Leave_Manager_Scheduled_Reports_Manager
	 */
	function leave_manager_scheduled_reports() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Scheduled_Reports_Manager();
		}

		return $instance;
	}
}
