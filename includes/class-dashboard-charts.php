<?php
/**
 * Dashboard Charts Class
 *
 * Manages chart data and visualization for the dashboard
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Dashboard_Charts class
 */
class Leave_Manager_Dashboard_Charts {

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
	 * Get leave requests by type (for pie/doughnut chart)
	 *
	 * @param string $period Period: 'month', 'quarter', 'year'
	 * @return array Chart data
	 */
	public function get_leave_by_type( $period = 'month' ) {
		global $wpdb;

		// Calculate date range
		$date_range = $this->get_date_range( $period );

		// Query leave requests by type
		$requests_table = $this->db->requests_table;
		$results        = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT leave_type, COUNT(*) as count FROM {$requests_table}
				WHERE start_date >= %s AND start_date <= %s AND status = 'approved'
				GROUP BY leave_type",
				$date_range['start'],
				$date_range['end']
			)
		);

		$labels = array();
		$data   = array();
		$colors = array(
			'annual' => '#4A5FFF',
			'sick'   => '#f44336',
			'other'  => '#667eea',
		);

		foreach ( $results as $result ) {
			$labels[] = ucfirst( str_replace( '_', ' ', $result->leave_type ) );
			$data[]   = intval( $result->count );
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => 'Leave Requests by Type',
					'data'            => $data,
					'backgroundColor' => array_values( $colors ),
					'borderColor'     => '#ffffff',
					'borderWidth'     => 2,
				),
			),
		);
	}

	/**
	 * Get leave requests over time (for line/bar chart)
	 *
	 * @param string $period Period: 'month', 'quarter', 'year'
	 * @return array Chart data
	 */
	public function get_leave_over_time( $period = 'month' ) {
		global $wpdb;

		// Calculate date range
		$date_range = $this->get_date_range( $period );

		// Query leave requests by date
		$requests_table = $this->db->requests_table;

		if ( 'month' === $period ) {
			// Group by day for monthly view
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE(start_date) as date, COUNT(*) as count FROM {$requests_table}
					WHERE start_date >= %s AND start_date <= %s AND status = 'approved'
					GROUP BY DATE(start_date)
					ORDER BY date ASC",
					$date_range['start'],
					$date_range['end']
				)
			);
		} else {
			// Group by week for longer periods
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE_TRUNC(start_date, WEEK) as date, COUNT(*) as count FROM {$requests_table}
					WHERE start_date >= %s AND start_date <= %s AND status = 'approved'
					GROUP BY DATE_TRUNC(start_date, WEEK)
					ORDER BY date ASC",
					$date_range['start'],
					$date_range['end']
				)
			);
		}

		$labels = array();
		$data   = array();

		foreach ( $results as $result ) {
			$labels[] = date_i18n( 'M d', strtotime( $result->date ) );
			$data[]   = intval( $result->count );
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'                => 'Leave Requests',
					'data'                 => $data,
					'borderColor'          => '#4A5FFF',
					'backgroundColor'     => 'rgba(255, 193, 7, 0.1)',
					'borderWidth'          => 2,
					'fill'                 => true,
					'tension'              => 0.4,
					'pointBackgroundColor' => '#4A5FFF',
					'pointBorderColor'     => '#ffffff',
					'pointBorderWidth'     => 2,
					'pointRadius'          => 5,
					'pointHoverRadius'     => 7,
				),
			),
		);
	}

	/**
	 * Get leave requests by status (for bar chart)
	 *
	 * @return array Chart data
	 */
	public function get_leave_by_status() {
		global $wpdb;

		$requests_table = $this->db->requests_table;
		$results        = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$requests_table}
			GROUP BY status"
		);

		$labels = array();
		$data   = array();
		$colors = array(
			'pending'  => '#ff9800',
			'approved' => '#4caf50',
			'rejected' => '#f44336',
		);

		foreach ( $results as $result ) {
			$labels[] = ucfirst( $result->status );
			$data[]   = intval( $result->count );
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => 'Leave Requests by Status',
					'data'            => $data,
					'backgroundColor' => array(
						'#ff9800',
						'#4caf50',
						'#f44336',
					),
					'borderColor'     => '#ffffff',
					'borderWidth'     => 2,
				),
			),
		);
	}

	/**
	 * Get employee leave balance (for horizontal bar chart)
	 *
	 * @param int $limit Number of employees to show
	 * @return array Chart data
	 */
	public function get_employee_balance( $limit = 10 ) {
		global $wpdb;

		$users_table = $this->db->users_table;
		$results     = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_name, annual_balance, sick_balance, other_balance FROM {$users_table}
				WHERE status = 'active'
				ORDER BY annual_balance DESC
				LIMIT %d",
				$limit
			)
		);

		$labels = array();
		$annual = array();
		$sick   = array();
		$other  = array();

		foreach ( $results as $result ) {
			$labels[] = $result->user_name;
			$annual[] = floatval( $result->annual_balance );
			$sick[]   = floatval( $result->sick_balance );
			$other[]  = floatval( $result->other_balance );
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => 'Annual Leave',
					'data'            => $annual,
					'backgroundColor' => '#4A5FFF',
				),
				array(
					'label'           => 'Sick Leave',
					'data'            => $sick,
					'backgroundColor' => '#f44336',
				),
				array(
					'label'           => 'Other Leave',
					'data'            => $other,
					'backgroundColor' => '#667eea',
				),
			),
		);
	}

	/**
	 * Get monthly leave statistics
	 *
	 * @return array Chart data
	 */
	public function get_monthly_statistics() {
		global $wpdb;

		$requests_table = $this->db->requests_table;
		$results        = $wpdb->get_results(
			"SELECT 
				MONTH(start_date) as month,
				COUNT(*) as total,
				SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
				SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
				SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
			FROM {$requests_table}
			WHERE YEAR(start_date) = YEAR(NOW())
			GROUP BY MONTH(start_date)
			ORDER BY month ASC"
		);

		$labels   = array();
		$approved = array();
		$pending  = array();
		$rejected = array();

		for ( $i = 1; $i <= 12; $i++ ) {
			$labels[] = date_i18n( 'M', mktime( 0, 0, 0, $i, 1 ) );

			$found = false;
			foreach ( $results as $result ) {
				if ( intval( $result->month ) === $i ) {
					$approved[] = intval( $result->approved );
					$pending[]  = intval( $result->pending );
					$rejected[] = intval( $result->rejected );
					$found      = true;
					break;
				}
			}

			if ( ! $found ) {
				$approved[] = 0;
				$pending[]  = 0;
				$rejected[] = 0;
			}
		}

		return array(
			'labels'   => $labels,
			'datasets' => array(
				array(
					'label'           => 'Approved',
					'data'            => $approved,
					'backgroundColor' => '#4caf50',
				),
				array(
					'label'           => 'Pending',
					'data'            => $pending,
					'backgroundColor' => '#ff9800',
				),
				array(
					'label'           => 'Rejected',
					'data'            => $rejected,
					'backgroundColor' => '#f44336',
				),
			),
		);
	}

	/**
	 * Get date range for period
	 *
	 * @param string $period Period: 'month', 'quarter', 'year'
	 * @return array Start and end dates
	 */
	private function get_date_range( $period = 'month' ) {
		$today = current_time( 'Y-m-d' );

		switch ( $period ) {
			case 'quarter':
				$month = intval( date( 'm' ) );
				$start = intval( floor( ( $month - 1 ) / 3 ) * 3 + 1 );
				$start = date( 'Y-m-d', mktime( 0, 0, 0, $start, 1 ) );
				$end   = date( 'Y-m-t', mktime( 0, 0, 0, $start + 2, 1 ) );
				break;

			case 'year':
				$start = date( 'Y-01-01' );
				$end   = date( 'Y-12-31' );
				break;

			case 'month':
			default:
				$start = date( 'Y-m-01' );
				$end   = date( 'Y-m-t' );
				break;
		}

		return array(
			'start' => $start,
			'end'   => $end,
		);
	}

	/**
	 * Get chart options for Chart.js
	 *
	 * @param string $type Chart type: 'line', 'bar', 'pie', 'doughnut'
	 * @return array Chart options
	 */
	public static function get_chart_options( $type = 'line' ) {
		$common_options = array(
			'responsive'          => true,
			'maintainAspectRatio' => true,
			'plugins'             => array(
				'legend' => array(
					'position' => 'top',
					'labels'   => array(
						'font'      => array(
							'family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
							'size'   => 12,
						),
						'color'     => '#333333',
						'padding'   => 15,
						'usePointStyle' => true,
					),
				),
				'tooltip' => array(
					'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
					'titleFont'       => array( 'size' => 14 ),
					'bodyFont'        => array( 'size' => 13 ),
					'padding'         => 12,
					'cornerRadius'    => 6,
					'displayColors'   => true,
				),
			),
		);

		switch ( $type ) {
			case 'line':
				return array_merge(
					$common_options,
					array(
						'scales' => array(
							'y' => array(
								'beginAtZero' => true,
								'grid'        => array(
									'color'       => 'rgba(0, 0, 0, 0.05)',
									'drawBorder'  => false,
								),
								'ticks'       => array(
									'font'  => array( 'size' => 11 ),
									'color' => '#999999',
								),
							),
							'x' => array(
								'grid'  => array(
									'display' => false,
								),
								'ticks' => array(
									'font'  => array( 'size' => 11 ),
									'color' => '#999999',
								),
							),
						),
					)
				);

			case 'bar':
				return array_merge(
					$common_options,
					array(
						'scales' => array(
							'y' => array(
								'beginAtZero' => true,
								'grid'        => array(
									'color'       => 'rgba(0, 0, 0, 0.05)',
									'drawBorder'  => false,
								),
								'ticks'       => array(
									'font'  => array( 'size' => 11 ),
									'color' => '#999999',
								),
							),
							'x' => array(
								'grid'  => array(
									'display' => false,
								),
								'ticks' => array(
									'font'  => array( 'size' => 11 ),
									'color' => '#999999',
								),
							),
						),
					)
				);

			case 'pie':
			case 'doughnut':
				return array_merge(
					$common_options,
					array(
						'plugins' => array_merge(
							$common_options['plugins'],
							array(
								'tooltip' => array(
									'callbacks' => array(
										'label' => 'function(context) { return context.label + ": " + context.parsed + " requests"; }',
									),
								),
							)
						),
					)
				);

			default:
				return $common_options;
		}
	}
}
