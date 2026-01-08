<?php
/**
 * Data Visualization Manager Class
 * Manages data visualization with Chart.js and analytics dashboards
 *
 * @package LeaveManager
 * @subpackage DataVisualization
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Data_Visualization_Manager {

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
	 * Chart types
	 *
	 * @var array
	 */
	private $chart_types = array(
		'line' => 'Line Chart',
		'bar' => 'Bar Chart',
		'pie' => 'Pie Chart',
		'doughnut' => 'Doughnut Chart',
		'radar' => 'Radar Chart',
		'scatter' => 'Scatter Chart',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->security_framework = leave_manager_security();
		$this->prorata_calculator = leave_manager_prorata();
	}

	/**
	 * Get leave trends data
	 *
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return array Chart data
	 */
	public function get_leave_trends_data( $start_date, $end_date ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT DATE_FORMAT(date_from, '%Y-%m') as month, COUNT(*) as count, SUM(days) as total_days 
			FROM {$wpdb->prefix}leave_manager_leave_requests 
			WHERE date_from BETWEEN %s AND %s AND status = 'approved'
			GROUP BY month ORDER BY month ASC",
			$start_date,
			$end_date
		);

		$results = $wpdb->get_results( $query );

		$labels = array();
		$data = array();

		foreach ( $results as $row ) {
			$labels[] = $row->month;
			$data[] = $row->total_days;
		}

		return array(
			'type' => 'line',
			'labels' => $labels,
			'datasets' => array(
				array(
					'label' => 'Leave Days Used',
					'data' => $data,
					'borderColor' => '#4CAF50',
					'backgroundColor' => 'rgba(76, 175, 80, 0.1)',
					'tension' => 0.4,
				),
			),
		);
	}

	/**
	 * Get leave type distribution data
	 *
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return array Chart data
	 */
	public function get_leave_type_distribution( $start_date, $end_date ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT leave_type, COUNT(*) as count, SUM(days) as total_days 
			FROM {$wpdb->prefix}leave_manager_leave_requests 
			WHERE date_from BETWEEN %s AND %s AND status = 'approved'
			GROUP BY leave_type ORDER BY total_days DESC",
			$start_date,
			$end_date
		);

		$results = $wpdb->get_results( $query );

		$labels = array();
		$data = array();
		$colors = array( '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40' );

		foreach ( $results as $index => $row ) {
			$labels[] = $row->leave_type;
			$data[] = $row->total_days;
		}

		return array(
			'type' => 'doughnut',
			'labels' => $labels,
			'datasets' => array(
				array(
					'label' => 'Leave Days by Type',
					'data' => $data,
					'backgroundColor' => array_slice( $colors, 0, count( $data ) ),
					'borderColor' => '#fff',
					'borderWidth' => 2,
				),
			),
		);
	}

	/**
	 * Get department leave summary
	 *
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return array Chart data
	 */
	public function get_department_leave_summary( $start_date, $end_date ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT u.meta_value as department, COUNT(lr.id) as count, SUM(lr.days) as total_days 
			FROM {$wpdb->prefix}leave_manager_leave_requests lr
			JOIN {$wpdb->prefix}users u ON lr.user_id = u.ID
			JOIN {$wpdb->prefix}usermeta um ON u.ID = um.user_id AND um.meta_key = 'department'
			WHERE lr.date_from BETWEEN %s AND %s AND lr.status = 'approved'
			GROUP BY department ORDER BY total_days DESC",
			$start_date,
			$end_date
		);

		$results = $wpdb->get_results( $query );

		$labels = array();
		$data = array();

		foreach ( $results as $row ) {
			$labels[] = $row->department;
			$data[] = $row->total_days;
		}

		return array(
			'type' => 'bar',
			'labels' => $labels,
			'datasets' => array(
				array(
					'label' => 'Leave Days by Department',
					'data' => $data,
					'backgroundColor' => '#36A2EB',
					'borderColor' => '#2196F3',
					'borderWidth' => 1,
				),
			),
		);
	}

	/**
	 * Get employee leave balance distribution
	 *
	 * @return array Chart data
	 */
	public function get_employee_balance_distribution() {
		$users = get_users(
			array(
				'role__in' => array( 'employee', 'manager', 'administrator' ),
				'fields' => 'ID',
			)
		);

		$balance_ranges = array(
			'0-5' => 0,
			'5-10' => 0,
			'10-15' => 0,
			'15-20' => 0,
			'20+' => 0,
		);

		foreach ( $users as $user_id ) {
			$balance = $this->prorata_calculator->calculate_leave_balance( $user_id, 1 );

			if ( ! is_wp_error( $balance ) ) {
				if ( $balance < 5 ) {
					$balance_ranges['0-5']++;
				} elseif ( $balance < 10 ) {
					$balance_ranges['5-10']++;
				} elseif ( $balance < 15 ) {
					$balance_ranges['10-15']++;
				} elseif ( $balance < 20 ) {
					$balance_ranges['15-20']++;
				} else {
					$balance_ranges['20+']++;
				}
			}
		}

		return array(
			'type' => 'pie',
			'labels' => array_keys( $balance_ranges ),
			'datasets' => array(
				array(
					'label' => 'Employee Balance Distribution',
					'data' => array_values( $balance_ranges ),
					'backgroundColor' => array(
						'#FF6384',
						'#36A2EB',
						'#FFCE56',
						'#4BC0C0',
						'#9966FF',
					),
				),
			),
		);
	}

	/**
	 * Get approval status overview
	 *
	 * @return array Chart data
	 */
	public function get_approval_status_overview() {
		global $wpdb;

		$query = "SELECT status, COUNT(*) as count 
				FROM {$wpdb->prefix}leave_manager_approval_requests 
				GROUP BY status";

		$results = $wpdb->get_results( $query );

		$labels = array();
		$data = array();
		$colors = array(
			'pending' => '#FFC107',
			'approved' => '#4CAF50',
			'rejected' => '#F44336',
			'cancelled' => '#9E9E9E',
		);

		foreach ( $results as $row ) {
			$labels[] = ucfirst( $row->status );
			$data[] = $row->count;
		}

		return array(
			'type' => 'pie',
			'labels' => $labels,
			'datasets' => array(
				array(
					'label' => 'Approval Status Overview',
					'data' => $data,
					'backgroundColor' => array_values( $colors ),
				),
			),
		);
	}

	/**
	 * Get monthly leave requests trend
	 *
	 * @param int $months Number of months to include
	 * @return array Chart data
	 */
	public function get_monthly_leave_requests_trend( $months = 12 ) {
		global $wpdb;

		$start_date = date( 'Y-m-d', strtotime( "-{$months} months" ) );
		$end_date = date( 'Y-m-d' );

		$query = $wpdb->prepare(
			"SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
			FROM {$wpdb->prefix}leave_manager_leave_requests 
			WHERE created_at BETWEEN %s AND %s
			GROUP BY month ORDER BY month ASC",
			$start_date,
			$end_date
		);

		$results = $wpdb->get_results( $query );

		$labels = array();
		$data = array();

		foreach ( $results as $row ) {
			$labels[] = $row->month;
			$data[] = $row->count;
		}

		return array(
			'type' => 'line',
			'labels' => $labels,
			'datasets' => array(
				array(
					'label' => 'Leave Requests per Month',
					'data' => $data,
					'borderColor' => '#2196F3',
					'backgroundColor' => 'rgba(33, 150, 243, 0.1)',
					'tension' => 0.4,
					'fill' => true,
				),
			),
		);
	}

	/**
	 * Get approval turnaround time analysis
	 *
	 * @return array Chart data
	 */
	public function get_approval_turnaround_analysis() {
		global $wpdb;

		$query = "SELECT 
				DATEDIFF(updated_at, created_at) as days_to_approve,
				COUNT(*) as count
			FROM {$wpdb->prefix}leave_manager_approval_requests
			WHERE status IN ('approved', 'rejected')
			GROUP BY days_to_approve
			ORDER BY days_to_approve ASC
			LIMIT 30";

		$results = $wpdb->get_results( $query );

		$labels = array();
		$data = array();

		foreach ( $results as $row ) {
			$labels[] = $row->days_to_approve . ' days';
			$data[] = $row->count;
		}

		return array(
			'type' => 'bar',
			'labels' => $labels,
			'datasets' => array(
				array(
					'label' => 'Approval Turnaround Time',
					'data' => $data,
					'backgroundColor' => '#4CAF50',
					'borderColor' => '#388E3C',
					'borderWidth' => 1,
				),
			),
		);
	}

	/**
	 * Get dashboard analytics
	 *
	 * @return array Dashboard data
	 */
	public function get_dashboard_analytics() {
		global $wpdb;

		// Total leave requests
		$total_requests = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests"
		);

		// Pending approvals
		$pending_approvals = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_approval_requests WHERE status = 'pending'"
		);

		// Approved leave days this month
		$approved_this_month = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(days) FROM {$wpdb->prefix}leave_manager_leave_requests 
				WHERE status = 'approved' AND MONTH(date_from) = %d AND YEAR(date_from) = %d",
				date( 'm' ),
				date( 'Y' )
			)
		);

		// Total employees
		$total_employees = count(
			get_users(
				array(
					'role__in' => array( 'employee', 'manager', 'administrator' ),
					'fields' => 'ID',
				)
			)
		);

		return array(
			'total_requests' => intval( $total_requests ),
			'pending_approvals' => intval( $pending_approvals ),
			'approved_this_month' => floatval( $approved_this_month ),
			'total_employees' => intval( $total_employees ),
			'approval_rate' => $total_requests > 0 ? round( ( ( $total_requests - $pending_approvals ) / $total_requests ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Get chart types
	 *
	 * @return array
	 */
	public function get_chart_types() {
		return $this->chart_types;
	}

	/**
	 * Export chart data to JSON
	 *
	 * @param array $chart_data Chart data
	 * @return string JSON data
	 */
	public function export_chart_to_json( $chart_data ) {
		return wp_json_encode( $chart_data );
	}

	/**
	 * Generate analytics report
	 *
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return array Analytics report
	 */
	public function generate_analytics_report( $start_date, $end_date ) {
		return array(
			'period' => array(
				'start_date' => $start_date,
				'end_date' => $end_date,
			),
			'dashboard' => $this->get_dashboard_analytics(),
			'charts' => array(
				'leave_trends' => $this->get_leave_trends_data( $start_date, $end_date ),
				'leave_type_distribution' => $this->get_leave_type_distribution( $start_date, $end_date ),
				'department_summary' => $this->get_department_leave_summary( $start_date, $end_date ),
				'employee_balance' => $this->get_employee_balance_distribution(),
				'approval_status' => $this->get_approval_status_overview(),
				'monthly_trend' => $this->get_monthly_leave_requests_trend(),
				'approval_turnaround' => $this->get_approval_turnaround_analysis(),
			),
		);
	}

	/**
	 * Render chart HTML
	 *
	 * @param array  $chart_data Chart data
	 * @param string $canvas_id Canvas element ID
	 * @return string HTML
	 */
	public function render_chart_html( $chart_data, $canvas_id = 'chart' ) {
		$json_data = $this->export_chart_to_json( $chart_data );

		$html = '<canvas id="' . esc_attr( $canvas_id ) . '"></canvas>';
		$html .= '<script>';
		$html .= 'var ctx = document.getElementById("' . esc_attr( $canvas_id ) . '").getContext("2d");';
		$html .= 'var chartData = ' . $json_data . ';';
		$html .= 'new Chart(ctx, chartData);';
		$html .= '</script>';

		return $html;
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_data_visualization' ) ) {
	/**
	 * Get data visualization manager instance
	 *
	 * @return Leave_Manager_Data_Visualization_Manager
	 */
	function leave_manager_data_visualization() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Data_Visualization_Manager();
		}

		return $instance;
	}
}
