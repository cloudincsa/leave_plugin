<?php
/**
 * Custom Report Builder Class
 * Builds custom leave reports with various filters and formats
 *
 * @package LeaveManager
 * @subpackage Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Custom_Report_Builder {

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
	 * Report types
	 *
	 * @var array
	 */
	private $report_types = array(
		'leave_summary' => 'Leave Summary Report',
		'employee_balance' => 'Employee Leave Balance Report',
		'department_summary' => 'Department Summary Report',
		'approval_status' => 'Approval Status Report',
		'leave_trends' => 'Leave Trends Report',
		'compliance' => 'Compliance Report',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->transaction_manager = leave_manager_transaction();
		$this->security_framework = leave_manager_security();
	}

	/**
	 * Create custom report
	 *
	 * @param string $name Report name
	 * @param string $type Report type
	 * @param array  $filters Report filters
	 * @param array  $columns Report columns
	 * @return int|WP_Error Report ID or error
	 */
	public function create_custom_report( $name, $type, $filters, $columns ) {
		global $wpdb;

		// Validate inputs
		if ( empty( $name ) || empty( $type ) ) {
			return new WP_Error( 'invalid_input', 'Required fields are missing' );
		}

		// Validate report type
		if ( ! isset( $this->report_types[ $type ] ) ) {
			return new WP_Error( 'invalid_type', 'Invalid report type' );
		}

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to create reports' );
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $name, $type, $filters, $columns ) {
				$insert_result = $wpdb->insert(
					$wpdb->prefix . 'leave_manager_custom_reports',
					array(
						'name' => $name,
						'type' => $type,
						'filters' => wp_json_encode( $filters ),
						'columns' => wp_json_encode( $columns ),
						'created_by' => get_current_user_id(),
						'created_at' => current_time( 'mysql' ),
						'updated_at' => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
				);

				return $insert_result ? $wpdb->insert_id : false;
			},
			'create_custom_report'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to create report' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'create_custom_report',
			'custom_report',
			$result,
			array(),
			array( 'name' => $name, 'type' => $type )
		);

		do_action( 'leave_manager_custom_report_created', $result, $name );

		return $result;
	}

	/**
	 * Generate report data
	 *
	 * @param int $report_id Report ID
	 * @return array|WP_Error Report data or error
	 */
	public function generate_report( $report_id ) {
		global $wpdb;

		// Get report
		$report = $this->get_custom_report( $report_id );
		if ( null === $report ) {
			return new WP_Error( 'not_found', 'Report not found' );
		}

		// Parse filters and columns
		$filters = json_decode( $report->filters, true );
		$columns = json_decode( $report->columns, true );

		// Generate data based on report type
		switch ( $report->type ) {
			case 'leave_summary':
				$data = $this->generate_leave_summary( $filters, $columns );
				break;

			case 'employee_balance':
				$data = $this->generate_employee_balance( $filters, $columns );
				break;

			case 'department_summary':
				$data = $this->generate_department_summary( $filters, $columns );
				break;

			case 'approval_status':
				$data = $this->generate_approval_status( $filters, $columns );
				break;

			case 'leave_trends':
				$data = $this->generate_leave_trends( $filters, $columns );
				break;

			case 'compliance':
				$data = $this->generate_compliance_report( $filters, $columns );
				break;

			default:
				return new WP_Error( 'invalid_type', 'Invalid report type' );
		}

		// Store report execution
		$this->store_report_execution( $report_id, $data );

		return $data;
	}

	/**
	 * Generate leave summary report
	 *
	 * @param array $filters Filters
	 * @param array $columns Columns to include
	 * @return array Report data
	 */
	private function generate_leave_summary( $filters, $columns ) {
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests WHERE 1=1";
		$params = array();

		// Apply filters
		if ( ! empty( $filters['start_date'] ) ) {
			$query .= " AND date_from >= %s";
			$params[] = $filters['start_date'];
		}

		if ( ! empty( $filters['end_date'] ) ) {
			$query .= " AND date_to <= %s";
			$params[] = $filters['end_date'];
		}

		if ( ! empty( $filters['leave_type'] ) ) {
			$query .= " AND leave_type = %s";
			$params[] = $filters['leave_type'];
		}

		if ( ! empty( $filters['status'] ) ) {
			$query .= " AND status = %s";
			$params[] = $filters['status'];
		}

		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		$results = $wpdb->get_results( $query );

		// Format data
		$data = array();
		foreach ( $results as $row ) {
			$row_data = array();

			foreach ( $columns as $column ) {
				if ( isset( $row->$column ) ) {
					$row_data[ $column ] = $row->$column;
				}
			}

			$data[] = $row_data;
		}

		return $data;
	}

	/**
	 * Generate employee balance report
	 *
	 * @param array $filters Filters
	 * @param array $columns Columns to include
	 * @return array Report data
	 */
	private function generate_employee_balance( $filters, $columns ) {
		global $wpdb;

		$users = get_users(
			array(
				'role__in' => array( 'employee', 'manager', 'administrator' ),
				'fields' => 'ID',
			)
		);

		$data = array();
		$prorata = leave_manager_prorata();

		foreach ( $users as $user_id ) {
			$user = get_user_by( 'id', $user_id );
			$balance = $prorata->calculate_leave_balance( $user_id, 1 );

			if ( ! is_wp_error( $balance ) ) {
				$row_data = array(
					'user_id' => $user_id,
					'user_name' => $user->display_name,
					'email' => $user->user_email,
					'balance' => $balance,
				);

				// Add requested columns
				foreach ( $columns as $column ) {
					if ( ! isset( $row_data[ $column ] ) ) {
						$row_data[ $column ] = '';
					}
				}

				$data[] = $row_data;
			}
		}

		return $data;
	}

	/**
	 * Generate department summary report
	 *
	 * @param array $filters Filters
	 * @param array $columns Columns to include
	 * @return array Report data
	 */
	private function generate_department_summary( $filters, $columns ) {
		global $wpdb;

		$query = "SELECT DISTINCT user_id FROM {$wpdb->prefix}leave_manager_leave_requests WHERE 1=1";
		$params = array();

		if ( ! empty( $filters['start_date'] ) ) {
			$query .= " AND date_from >= %s";
			$params[] = $filters['start_date'];
		}

		if ( ! empty( $filters['end_date'] ) ) {
			$query .= " AND date_to <= %s";
			$params[] = $filters['end_date'];
		}

		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		$user_ids = $wpdb->get_col( $query );

		$data = array();
		$departments = array();

		foreach ( $user_ids as $user_id ) {
			$user = get_user_by( 'id', $user_id );
			$department = get_user_meta( $user_id, 'department', true );

			if ( ! isset( $departments[ $department ] ) ) {
				$departments[ $department ] = array(
					'department' => $department,
					'total_leaves' => 0,
					'total_days' => 0,
					'employee_count' => 0,
				);
			}

			$leaves = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT SUM(days) as total_days, COUNT(*) as count FROM {$wpdb->prefix}leave_manager_leave_requests WHERE user_id = %d",
					$user_id
				)
			);

			if ( ! empty( $leaves ) ) {
				$departments[ $department ]['total_leaves'] += $leaves[0]->count;
				$departments[ $department ]['total_days'] += $leaves[0]->total_days;
				$departments[ $department ]['employee_count']++;
			}
		}

		foreach ( $departments as $dept_data ) {
			$data[] = $dept_data;
		}

		return $data;
	}

	/**
	 * Generate approval status report
	 *
	 * @param array $filters Filters
	 * @param array $columns Columns to include
	 * @return array Report data
	 */
	private function generate_approval_status( $filters, $columns ) {
		global $wpdb;

		$query = "SELECT * FROM {$wpdb->prefix}leave_manager_approval_requests WHERE 1=1";
		$params = array();

		if ( ! empty( $filters['status'] ) ) {
			$query .= " AND status = %s";
			$params[] = $filters['status'];
		}

		if ( ! empty( $filters['start_date'] ) ) {
			$query .= " AND created_at >= %s";
			$params[] = $filters['start_date'];
		}

		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		$results = $wpdb->get_results( $query );

		$data = array();
		foreach ( $results as $row ) {
			$row_data = array();

			foreach ( $columns as $column ) {
				if ( isset( $row->$column ) ) {
					$row_data[ $column ] = $row->$column;
				}
			}

			$data[] = $row_data;
		}

		return $data;
	}

	/**
	 * Generate leave trends report
	 *
	 * @param array $filters Filters
	 * @param array $columns Columns to include
	 * @return array Report data
	 */
	private function generate_leave_trends( $filters, $columns ) {
		global $wpdb;

		$query = "SELECT DATE_FORMAT(date_from, '%Y-%m') as month, leave_type, COUNT(*) as count, SUM(days) as total_days 
				FROM {$wpdb->prefix}leave_manager_leave_requests WHERE 1=1";
		$params = array();

		if ( ! empty( $filters['start_date'] ) ) {
			$query .= " AND date_from >= %s";
			$params[] = $filters['start_date'];
		}

		if ( ! empty( $filters['end_date'] ) ) {
			$query .= " AND date_to <= %s";
			$params[] = $filters['end_date'];
		}

		$query .= " GROUP BY month, leave_type ORDER BY month DESC";

		if ( ! empty( $params ) ) {
			$query = $wpdb->prepare( $query, $params );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Generate compliance report
	 *
	 * @param array $filters Filters
	 * @param array $columns Columns to include
	 * @return array Report data
	 */
	private function generate_compliance_report( $filters, $columns ) {
		global $wpdb;

		$data = array();

		// Get all users
		$users = get_users(
			array(
				'role__in' => array( 'employee', 'manager', 'administrator' ),
				'fields' => 'ID',
			)
		);

		$prorata = leave_manager_prorata();

		foreach ( $users as $user_id ) {
			$user = get_user_by( 'id', $user_id );
			$balance = $prorata->calculate_leave_balance( $user_id, 1 );

			$row_data = array(
				'user_id' => $user_id,
				'user_name' => $user->display_name,
				'email' => $user->user_email,
				'balance' => is_wp_error( $balance ) ? 0 : $balance,
				'compliance_status' => is_wp_error( $balance ) ? 'error' : ( $balance >= 0 ? 'compliant' : 'non-compliant' ),
			);

			$data[] = $row_data;
		}

		return $data;
	}

	/**
	 * Get custom report
	 *
	 * @param int $report_id Report ID
	 * @return object|null
	 */
	public function get_custom_report( $report_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_custom_reports WHERE id = %d",
				$report_id
			)
		);
	}

	/**
	 * Get all custom reports
	 *
	 * @return array
	 */
	public function get_all_custom_reports() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}leave_manager_custom_reports ORDER BY created_at DESC"
		);
	}

	/**
	 * Update custom report
	 *
	 * @param int   $report_id Report ID
	 * @param array $data Data to update
	 * @return bool|WP_Error
	 */
	public function update_custom_report( $report_id, $data ) {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to update reports' );
		}

		// Validate report exists
		$report = $this->get_custom_report( $report_id );
		if ( null === $report ) {
			return new WP_Error( 'not_found', 'Report not found' );
		}

		$update_data = array( 'updated_at' => current_time( 'mysql' ) );
		$format = array( '%s' );

		if ( isset( $data['name'] ) ) {
			$update_data['name'] = $data['name'];
			$format[] = '%s';
		}

		if ( isset( $data['filters'] ) ) {
			$update_data['filters'] = wp_json_encode( $data['filters'] );
			$format[] = '%s';
		}

		if ( isset( $data['columns'] ) ) {
			$update_data['columns'] = wp_json_encode( $data['columns'] );
			$format[] = '%s';
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $report_id, $update_data, $format ) {
				return $wpdb->update(
					$wpdb->prefix . 'leave_manager_custom_reports',
					$update_data,
					array( 'id' => $report_id ),
					$format,
					array( '%d' )
				);
			},
			'update_custom_report'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to update report' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'update_custom_report',
			'custom_report',
			$report_id,
			(array) $report,
			$data
		);

		do_action( 'leave_manager_custom_report_updated', $report_id );

		return true;
	}

	/**
	 * Delete custom report
	 *
	 * @param int $report_id Report ID
	 * @return bool|WP_Error
	 */
	public function delete_custom_report( $report_id ) {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to delete reports' );
		}

		// Validate report exists
		$report = $this->get_custom_report( $report_id );
		if ( null === $report ) {
			return new WP_Error( 'not_found', 'Report not found' );
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $report_id ) {
				return $wpdb->delete(
					$wpdb->prefix . 'leave_manager_custom_reports',
					array( 'id' => $report_id ),
					array( '%d' )
				);
			},
			'delete_custom_report'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to delete report' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'delete_custom_report',
			'custom_report',
			$report_id,
			(array) $report,
			array()
		);

		do_action( 'leave_manager_custom_report_deleted', $report_id );

		return true;
	}

	/**
	 * Store report execution
	 *
	 * @param int   $report_id Report ID
	 * @param array $data Report data
	 * @return int|WP_Error Execution ID or error
	 */
	private function store_report_execution( $report_id, $data ) {
		global $wpdb;

		return $wpdb->insert(
			$wpdb->prefix . 'leave_manager_report_executions',
			array(
				'report_id' => $report_id,
				'executed_by' => get_current_user_id(),
				'data' => wp_json_encode( $data ),
				'executed_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
	}

	/**
	 * Export report to CSV
	 *
	 * @param int $report_id Report ID
	 * @return string|WP_Error CSV content or error
	 */
	public function export_to_csv( $report_id ) {
		$data = $this->generate_report( $report_id );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$csv = '';

		// Add headers
		if ( ! empty( $data ) ) {
			$headers = array_keys( $data[0] );
			$csv .= implode( ',', $headers ) . "\n";

			// Add data
			foreach ( $data as $row ) {
				$csv .= implode( ',', $row ) . "\n";
			}
		}

		return $csv;
	}

	/**
	 * Get report types
	 *
	 * @return array
	 */
	public function get_report_types() {
		return $this->report_types;
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_custom_report' ) ) {
	/**
	 * Get custom report builder instance
	 *
	 * @return Leave_Manager_Custom_Report_Builder
	 */
	function leave_manager_custom_report() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Custom_Report_Builder();
		}

		return $instance;
	}
}
