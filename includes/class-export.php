<?php
/**
 * Export class for Leave Manager Plugin
 *
 * Handles exporting data to CSV and PDF formats.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Export class
 */
class Leave_Manager_Export {

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
	 * Export leave requests to CSV
	 *
	 * @param array $args Query arguments
	 * @return string CSV content
	 */
	public function export_leave_requests_csv( $args = array() ) {
		global $wpdb;

		$requests_table = $this->db->leave_requests_table;
		$users_table = $this->db->users_table;

		// Build query
		$query = "SELECT r.*, u.first_name, u.last_name, u.email, u.department
		          FROM $requests_table r
		          JOIN $users_table u ON r.user_id = u.user_id
		          WHERE 1=1";

		if ( ! empty( $args['status'] ) ) {
			$query .= $wpdb->prepare( " AND r.status = %s", $args['status'] );
		}

		if ( ! empty( $args['user_id'] ) ) {
			$query .= $wpdb->prepare( " AND r.user_id = %d", intval( $args['user_id'] ) );
		}

		if ( ! empty( $args['start_date'] ) ) {
			$query .= $wpdb->prepare( " AND r.created_at >= %s", $args['start_date'] . ' 00:00:00' );
		}

		if ( ! empty( $args['end_date'] ) ) {
			$query .= $wpdb->prepare( " AND r.created_at <= %s", $args['end_date'] . ' 23:59:59' );
		}

		$query .= " ORDER BY r.created_at DESC";

		$requests = $wpdb->get_results( $query );

		// Create CSV
		$csv = $this->array_to_csv(
			array(
				'Request ID',
				'Employee Name',
				'Email',
				'Department',
				'Leave Type',
				'Start Date',
				'End Date',
				'Reason',
				'Status',
				'Submitted Date',
				'Approved Date',
			),
			$requests,
			array(
				'request_id',
				'first_name',
				'email',
				'department',
				'leave_type',
				'start_date',
				'end_date',
				'reason',
				'status',
				'created_at',
				'approval_date',
			)
		);

		$this->logger->info( 'Leave requests exported to CSV', array( 'count' => count( $requests ) ) );

		return $csv;
	}

	/**
	 * Export users to CSV
	 *
	 * @param array $args Query arguments
	 * @return string CSV content
	 */
	public function export_users_csv( $args = array() ) {
		global $wpdb;

		$users_table = $this->db->users_table;

		$query = "SELECT * FROM $users_table WHERE 1=1";

		if ( ! empty( $args['role'] ) ) {
			$query .= $wpdb->prepare( " AND role = %s", $args['role'] );
		}

		if ( ! empty( $args['status'] ) ) {
			$query .= $wpdb->prepare( " AND status = %s", $args['status'] );
		}

		$query .= " ORDER BY created_at DESC";

		$users = $wpdb->get_results( $query );

		// Create CSV
		$csv = $this->array_to_csv(
			array(
				'User ID',
				'First Name',
				'Last Name',
				'Email',
				'Phone',
				'Department',
				'Position',
				'Role',
				'Status',
				'Annual Leave Balance',
				'Sick Leave Balance',
				'Other Leave Balance',
				'Created Date',
			),
			$users,
			array(
				'user_id',
				'first_name',
				'last_name',
				'email',
				'phone',
				'department',
				'position',
				'role',
				'status',
				'annual_leave_balance',
				'sick_leave_balance',
				'other_leave_balance',
				'created_at',
			)
		);

		$this->logger->info( 'Users exported to CSV', array( 'count' => count( $users ) ) );

		return $csv;
	}

	/**
	 * Convert array to CSV format
	 *
	 * @param array $headers Column headers
	 * @param array $data Data rows
	 * @param array $columns Column mapping
	 * @return string CSV content
	 */
	private function array_to_csv( $headers, $data, $columns ) {
		$csv = '';

		// Add headers
		$csv .= implode( ',', array_map( array( $this, 'escape_csv' ), $headers ) ) . "\n";

		// Add data rows
		foreach ( $data as $row ) {
			$row_data = array();
			foreach ( $columns as $column ) {
				$value = isset( $row->$column ) ? $row->$column : '';
				$row_data[] = $this->escape_csv( $value );
			}
			$csv .= implode( ',', $row_data ) . "\n";
		}

		return $csv;
	}

	/**
	 * Escape CSV value
	 *
	 * @param string $value Value to escape
	 * @return string Escaped value
	 */
	private function escape_csv( $value ) {
		if ( strpos( $value, ',' ) !== false || strpos( $value, '"' ) !== false || strpos( $value, "\n" ) !== false ) {
			return '"' . str_replace( '"', '""', $value ) . '"';
		}
		return $value;
	}

	/**
	 * Generate CSV file download
	 *
	 * @param string $filename Filename
	 * @param string $content CSV content
	 * @return void
	 */
	public function download_csv( $filename, $content ) {
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );
		echo $content;
		exit;
	}

	/**
	 * Get leave statistics for report
	 *
	 * @param array $args Query arguments
	 * @return array Statistics
	 */
	public function get_report_statistics( $args = array() ) {
		global $wpdb;

		$requests_table = $this->db->leave_requests_table;
		$users_table = $this->db->users_table;

		$where = '1=1';
		$values = array();

		if ( ! empty( $args['start_date'] ) ) {
			$where .= ' AND r.created_at >= %s';
			$values[] = $args['start_date'] . ' 00:00:00';
		}

		if ( ! empty( $args['end_date'] ) ) {
			$where .= ' AND r.created_at <= %s';
			$values[] = $args['end_date'] . ' 23:59:59';
		}

		$query = $wpdb->prepare(
			"SELECT 
			 COUNT(*) as total_requests,
			 SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
			 SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved,
			 SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END) as rejected,
			 COUNT(DISTINCT r.user_id) as unique_employees
			 FROM $requests_table r
			 WHERE $where",
			$values
		);

		return $wpdb->get_row( $query );
	}

	/**
	 * Generate summary report
	 *
	 * @param array $args Query arguments
	 * @return string Report content
	 */
	public function generate_summary_report( $args = array() ) {
		$stats = $this->get_report_statistics( $args );

		$report = "LEAVE MANAGEMENT SUMMARY REPORT\n";
		$report .= "Generated: " . current_time( 'Y-m-d H:i:s' ) . "\n";
		$report .= str_repeat( '=', 50 ) . "\n\n";

		$report .= "STATISTICS:\n";
		$report .= "Total Requests: " . $stats->total_requests . "\n";
		$report .= "Pending: " . $stats->pending . "\n";
		$report .= "Approved: " . $stats->approved . "\n";
		$report .= "Rejected: " . $stats->rejected . "\n";
		$report .= "Unique Employees: " . $stats->unique_employees . "\n\n";

		if ( $stats->total_requests > 0 ) {
			$approval_rate = round( ( $stats->approved / $stats->total_requests ) * 100, 2 );
			$report .= "Approval Rate: " . $approval_rate . "%\n";
		}

		return $report;
	}
}
