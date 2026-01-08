<?php
/**
 * Report AJAX Handler Class
 * Handles AJAX requests for report generation and CSV export
 *
 * @package LeaveManager
 * @subpackage Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Report_AJAX_Handler {

	/**
	 * Custom Report Builder instance
	 *
	 * @var Leave_Manager_Custom_Report_Builder
	 */
	private $report_builder;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->report_builder = leave_manager_custom_report();
		
		// Register AJAX handlers
		add_action( 'wp_ajax_leave_manager_generate_leave_report', array( $this, 'generate_leave_report' ) );
		add_action( 'wp_ajax_leave_manager_export_leave_report', array( $this, 'export_leave_report' ) );
		add_action( 'wp_ajax_leave_manager_generate_user_report', array( $this, 'generate_user_report' ) );
		add_action( 'wp_ajax_leave_manager_export_user_report', array( $this, 'export_user_report' ) );
		add_action( 'wp_ajax_leave_manager_generate_department_report', array( $this, 'generate_department_report' ) );
		add_action( 'wp_ajax_leave_manager_export_department_report', array( $this, 'export_department_report' ) );
	}

	/**
	 * Generate leave report via AJAX
	 *
	 * @return void
	 */
	public function generate_leave_report() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ), 403 );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
		}

		// Get filters
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		$leave_type = isset( $_POST['leave_type'] ) ? sanitize_text_field( $_POST['leave_type'] ) : '';

		// Validate dates
		if ( ! $this->validate_date( $start_date ) || ! $this->validate_date( $end_date ) ) {
			wp_send_json_error( array( 'message' => 'Invalid date format' ), 400 );
		}

		// Generate report data
		$report_data = $this->get_leave_report_data( $start_date, $end_date, $leave_type );

		if ( is_wp_error( $report_data ) ) {
			wp_send_json_error( array( 'message' => $report_data->get_error_message() ), 400 );
		}

		wp_send_json_success( array(
			'data' => $report_data,
			'message' => 'Report generated successfully',
		) );
	}

	/**
	 * Export leave report to CSV via AJAX
	 *
	 * @return void
	 */
	public function export_leave_report() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ), 403 );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
		}

		// Get filters
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		$leave_type = isset( $_POST['leave_type'] ) ? sanitize_text_field( $_POST['leave_type'] ) : '';

		// Generate report data
		$report_data = $this->get_leave_report_data( $start_date, $end_date, $leave_type );

		if ( is_wp_error( $report_data ) ) {
			wp_send_json_error( array( 'message' => $report_data->get_error_message() ), 400 );
		}

		// Convert to CSV
		$csv = $this->array_to_csv( $report_data );

		// Send CSV file
		wp_send_json_success( array(
			'csv' => $csv,
			'filename' => 'leave-report-' . date( 'Y-m-d' ) . '.csv',
			'message' => 'Report exported successfully',
		) );
	}

	/**
	 * Generate user report via AJAX
	 *
	 * @return void
	 */
	public function generate_user_report() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ), 403 );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
		}

		// Get filters
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		$department = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : '';

		// Generate report data
		$report_data = $this->get_user_report_data( $start_date, $end_date, $department );

		if ( is_wp_error( $report_data ) ) {
			wp_send_json_error( array( 'message' => $report_data->get_error_message() ), 400 );
		}

		wp_send_json_success( array(
			'data' => $report_data,
			'message' => 'Report generated successfully',
		) );
	}

	/**
	 * Export user report to CSV via AJAX
	 *
	 * @return void
	 */
	public function export_user_report() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ), 403 );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
		}

		// Get filters
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		$department = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : '';

		// Generate report data
		$report_data = $this->get_user_report_data( $start_date, $end_date, $department );

		if ( is_wp_error( $report_data ) ) {
			wp_send_json_error( array( 'message' => $report_data->get_error_message() ), 400 );
		}

		// Convert to CSV
		$csv = $this->array_to_csv( $report_data );

		// Send CSV file
		wp_send_json_success( array(
			'csv' => $csv,
			'filename' => 'user-report-' . date( 'Y-m-d' ) . '.csv',
			'message' => 'Report exported successfully',
		) );
	}

	/**
	 * Generate department report via AJAX
	 *
	 * @return void
	 */
	public function generate_department_report() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ), 403 );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
		}

		// Get filters
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		$department = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : '';

		// Generate report data
		$report_data = $this->get_department_report_data( $start_date, $end_date, $department );

		if ( is_wp_error( $report_data ) ) {
			wp_send_json_error( array( 'message' => $report_data->get_error_message() ), 400 );
		}

		wp_send_json_success( array(
			'data' => $report_data,
			'message' => 'Report generated successfully',
		) );
	}

	/**
	 * Export department report to CSV via AJAX
	 *
	 * @return void
	 */
	public function export_department_report() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Security check failed' ), 403 );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
		}

		// Get filters
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
		$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
		$department = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : '';

		// Generate report data
		$report_data = $this->get_department_report_data( $start_date, $end_date, $department );

		if ( is_wp_error( $report_data ) ) {
			wp_send_json_error( array( 'message' => $report_data->get_error_message() ), 400 );
		}

		// Convert to CSV
		$csv = $this->array_to_csv( $report_data );

		// Send CSV file
		wp_send_json_success( array(
			'csv' => $csv,
			'filename' => 'department-report-' . date( 'Y-m-d' ) . '.csv',
			'message' => 'Report exported successfully',
		) );
	}

	/**
	 * Get leave report data
	 *
	 * @param string $start_date Start date
	 * @param string $end_date End date
	 * @param string $leave_type Leave type filter
	 * @return array|WP_Error Report data or error
	 */
	private function get_leave_report_data( $start_date, $end_date, $leave_type ) {
		global $wpdb;
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		$query = "SELECT 
			leave_type,
			COUNT(*) as total_requests,
			SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
			SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
			SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
			SUM(CASE WHEN status = 'approved' THEN DATEDIFF(end_date, start_date) + 1 ELSE 0 END) as days_taken
		FROM $requests_table
		WHERE 1=1";

		$params = array();

		if ( ! empty( $start_date ) ) {
			$query .= " AND start_date >= %s";
			$params[] = $start_date;
		}

		if ( ! empty( $end_date ) ) {
			$query .= " AND end_date <= %s";
			$params[] = $end_date;
		}

		if ( ! empty( $leave_type ) ) {
			$query .= " AND leave_type = %s";
			$params[] = $leave_type;
		}

		$query .= " GROUP BY leave_type ORDER BY leave_type ASC";

		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( null === $results ) {
			return new WP_Error( 'database_error', 'Failed to retrieve report data' );
		}

		return $results;
	}

	/**
	 * Get user report data
	 *
	 * @param string $start_date Start date
	 * @param string $end_date End date
	 * @param int    $user_id User ID
	 * @return array|WP_Error Report data or error
	 */
	private function get_user_report_data( $start_date, $end_date, $department ) {
		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		$query = "SELECT 
			u.first_name,
			u.last_name,
			u.department,
			COUNT(r.request_id) as total_requests,
			SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved,
			SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
			SUM(CASE WHEN r.status = 'approved' THEN DATEDIFF(r.end_date, r.start_date) + 1 ELSE 0 END) as days_taken
		FROM $users_table u
		LEFT JOIN $requests_table r ON u.user_id = r.user_id
		WHERE 1=1";

		$params = array();

		if ( ! empty( $department ) && $department !== 'all' ) {
			$query .= " AND u.department = %s";
			$params[] = $department;
		}

		$query .= " GROUP BY u.user_id, u.first_name, u.last_name, u.department ORDER BY u.first_name ASC";

		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( null === $results ) {
			return new WP_Error( 'database_error', 'Failed to retrieve report data' );
		}

		return $results;
	}

	/**
	 * Get department report data
	 *
	 * @param string $start_date Start date
	 * @param string $end_date End date
	 * @param string $department Department filter
	 * @return array|WP_Error Report data or error
	 */
	private function get_department_report_data( $start_date, $end_date, $department ) {
		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		$query = "SELECT 
			u.department,
			COUNT(DISTINCT u.user_id) as total_employees,
			COUNT(r.request_id) as total_requests,
			SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved,
			SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
			SUM(CASE WHEN r.status = 'approved' THEN DATEDIFF(r.end_date, r.start_date) + 1 ELSE 0 END) as days_taken
		FROM $users_table u
		LEFT JOIN $requests_table r ON u.user_id = r.user_id
		WHERE u.department IS NOT NULL AND u.department != ''";

		$params = array();

		if ( ! empty( $department ) && $department !== 'all' ) {
			$query .= " AND u.department = %s";
			$params[] = $department;
		}

		$query .= " GROUP BY u.department ORDER BY u.department ASC";

		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		$results = $wpdb->get_results( $query, ARRAY_A );

		if ( null === $results ) {
			return new WP_Error( 'database_error', 'Failed to retrieve report data' );
		}

		return $results;
	}

	/**
	 * Convert array to CSV string
	 *
	 * @param array $data Data to convert
	 * @return string CSV string
	 */
	private function array_to_csv( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		$csv = '';
		$headers = array_keys( $data[0] );

		// Add headers
		$csv .= implode( ',', array_map( array( $this, 'escape_csv_value' ), $headers ) ) . "\n";

		// Add data rows
		foreach ( $data as $row ) {
			$csv .= implode( ',', array_map( array( $this, 'escape_csv_value' ), $row ) ) . "\n";
		}

		return $csv;
	}

	/**
	 * Escape CSV value
	 *
	 * @param mixed $value Value to escape
	 * @return string Escaped value
	 */
	private function escape_csv_value( $value ) {
		if ( null === $value ) {
			return '';
		}

		$value = (string) $value;

		if ( strpos( $value, ',' ) !== false || strpos( $value, '"' ) !== false || strpos( $value, "\n" ) !== false ) {
			$value = '"' . str_replace( '"', '""', $value ) . '"';
		}

		return $value;
	}

	/**
	 * Validate date format (YYYY-MM-DD)
	 *
	 * @param string $date Date string
	 * @return bool True if valid, false otherwise
	 */
	private function validate_date( $date ) {
		if ( empty( $date ) ) {
			return true;
		}

		$d = \DateTime::createFromFormat( 'Y-m-d', $date );
		return $d && $d->format( 'Y-m-d' ) === $date;
	}
}

// Initialize AJAX handler
new Leave_Manager_Report_AJAX_Handler();
