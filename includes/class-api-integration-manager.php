<?php
/**
 * API Integration Manager Class
 * Handles REST API endpoints and external API integrations
 *
 * @package LeaveManager
 * @subpackage API
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_API_Integration_Manager {

	/**
	 * Security framework instance
	 *
	 * @var Leave_Manager_Security_Framework
	 */
	private $security_framework;

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private $api_namespace = 'leave-manager/v1';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->security_framework = leave_manager_security();
		$this->register_routes();
	}

	/**
	 * Register REST API routes
	 */
	private function register_routes() {
		// Leave requests endpoints
		register_rest_route(
			$this->api_namespace,
			'/leave-requests',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_leave_requests' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			$this->api_namespace,
			'/leave-requests',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'create_leave_request' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Approval endpoints
		register_rest_route(
			$this->api_namespace,
			'/approvals',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_approvals' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			$this->api_namespace,
			'/approvals/(?P<id>\d+)',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'update_approval' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Report endpoints
		register_rest_route(
			$this->api_namespace,
			'/reports',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_reports' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			$this->api_namespace,
			'/reports/(?P<id>\d+)/generate',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'generate_report' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Analytics endpoints
		register_rest_route(
			$this->api_namespace,
			'/analytics/dashboard',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_dashboard_analytics' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			$this->api_namespace,
			'/analytics/charts',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_chart_data' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Public holidays endpoints
		register_rest_route(
			$this->api_namespace,
			'/holidays',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_holidays' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			$this->api_namespace,
			'/holidays',
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'create_holiday' ),
				'permission_callback' => array( $this, 'check_admin_permission' ),
			)
		);
	}

	/**
	 * Check API permission
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_forbidden', 'You must be logged in', array( 'status' => 401 ) );
		}

		if ( ! current_user_can( 'access_leave_manager' ) ) {
			return new WP_Error( 'rest_forbidden', 'You do not have permission', array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Check admin permission
	 *
	 * @return bool|WP_Error
	 */
	public function check_admin_permission() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'rest_forbidden', 'You must be logged in', array( 'status' => 401 ) );
		}

		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'rest_forbidden', 'You do not have permission', array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Get leave requests endpoint
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function get_leave_requests( WP_REST_Request $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$status = $request->get_param( 'status' );
		$page = $request->get_param( 'page' ) ? intval( $request->get_param( 'page' ) ) : 1;
		$per_page = $request->get_param( 'per_page' ) ? intval( $request->get_param( 'per_page' ) ) : 20;

		$query = "SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests WHERE user_id = %d";
		$params = array( $user_id );

		if ( ! empty( $status ) ) {
			$query .= " AND status = %s";
			$params[] = sanitize_text_field( $status );
		}

		$query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = ( $page - 1 ) * $per_page;

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $results,
				'page' => $page,
				'per_page' => $per_page,
			),
			200
		);
	}

	/**
	 * Create leave request endpoint
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function create_leave_request( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$date_from = $request->get_param( 'date_from' );
		$date_to = $request->get_param( 'date_to' );
		$leave_type = $request->get_param( 'leave_type' );
		$reason = $request->get_param( 'reason' );

		// Validate required fields
		if ( empty( $date_from ) || empty( $date_to ) || empty( $leave_type ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Missing required fields',
				),
				400
			);
		}

		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . 'leave_manager_leave_requests',
			array(
				'user_id' => $user_id,
				'date_from' => $date_from,
				'date_to' => $date_to,
				'leave_type' => $leave_type,
				'reason' => $reason,
				'status' => 'pending',
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Failed to create leave request',
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Leave request created successfully',
				'id' => $wpdb->insert_id,
			),
			201
		);
	}

	/**
	 * Get approvals endpoint
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function get_approvals( WP_REST_Request $request ) {
		global $wpdb;

		$user_id = get_current_user_id();
		$status = $request->get_param( 'status' );
		$page = $request->get_param( 'page' ) ? intval( $request->get_param( 'page' ) ) : 1;
		$per_page = $request->get_param( 'per_page' ) ? intval( $request->get_param( 'per_page' ) ) : 20;

		$query = "SELECT * FROM {$wpdb->prefix}leave_manager_approval_requests WHERE user_id = %d";
		$params = array( $user_id );

		if ( ! empty( $status ) ) {
			$query .= " AND status = %s";
			$params[] = sanitize_text_field( $status );
		}

		$query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = ( $page - 1 ) * $per_page;

		$results = $wpdb->get_results( $wpdb->prepare( $query, $params ) );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $results,
				'page' => $page,
				'per_page' => $per_page,
			),
			200
		);
	}

	/**
	 * Update approval endpoint
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function update_approval( WP_REST_Request $request ) {
		$approval_id = $request->get_param( 'id' );
		$action = $request->get_param( 'action' );
		$comment = $request->get_param( 'comment' );

		if ( ! in_array( $action, array( 'approve', 'reject' ), true ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Invalid action',
				),
				400
			);
		}

		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'leave_manager_approval_requests',
			array(
				'status' => $action === 'approve' ? 'approved' : 'rejected',
				'comment' => $comment,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $approval_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $result ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Failed to update approval',
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Approval updated successfully',
			),
			200
		);
	}

	/**
	 * Get reports endpoint
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function get_reports( WP_REST_Request $request ) {
		$report_builder = leave_manager_custom_report();
		$reports = $report_builder->get_all_custom_reports();

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $reports,
			),
			200
		);
	}

	/**
	 * Generate report endpoint
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function generate_report( WP_REST_Request $request ) {
		$report_id = $request->get_param( 'id' );
		$report_builder = leave_manager_custom_report();

		$data = $report_builder->generate_report( $report_id );

		if ( is_wp_error( $data ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $data->get_error_message(),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $data,
			),
			200
		);
	}

	/**
	 * Get dashboard analytics endpoint
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function get_dashboard_analytics( WP_REST_Request $request ) {
		$visualization = leave_manager_data_visualization();
		$analytics = $visualization->get_dashboard_analytics();

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $analytics,
			),
			200
		);
	}

	/**
	 * Get chart data endpoint
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function get_chart_data( WP_REST_Request $request ) {
		$chart_type = $request->get_param( 'type' );
		$start_date = $request->get_param( 'start_date' );
		$end_date = $request->get_param( 'end_date' );

		$visualization = leave_manager_data_visualization();

		switch ( $chart_type ) {
			case 'leave_trends':
				$data = $visualization->get_leave_trends_data( $start_date, $end_date );
				break;

			case 'leave_type_distribution':
				$data = $visualization->get_leave_type_distribution( $start_date, $end_date );
				break;

			case 'department_summary':
				$data = $visualization->get_department_leave_summary( $start_date, $end_date );
				break;

			case 'employee_balance':
				$data = $visualization->get_employee_balance_distribution();
				break;

			case 'approval_status':
				$data = $visualization->get_approval_status_overview();
				break;

			default:
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => 'Invalid chart type',
					),
					400
				);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $data,
			),
			200
		);
	}

	/**
	 * Get holidays endpoint
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function get_holidays( WP_REST_Request $request ) {
		$country_code = $request->get_param( 'country_code' );
		$year = $request->get_param( 'year' );

		if ( empty( $country_code ) || empty( $year ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Missing required parameters',
				),
				400
			);
		}

		$holiday_manager = leave_manager_public_holiday();
		$holidays = $holiday_manager->get_holidays_for_country_year( $country_code, intval( $year ) );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data' => $holidays,
			),
			200
		);
	}

	/**
	 * Create holiday endpoint
	 *
	 * @param WP_REST_Request $request Request object
	 * @return WP_REST_Response
	 */
	public function create_holiday( WP_REST_Request $request ) {
		$country_code = $request->get_param( 'country_code' );
		$date = $request->get_param( 'date' );
		$name = $request->get_param( 'name' );

		if ( empty( $country_code ) || empty( $date ) || empty( $name ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => 'Missing required fields',
				),
				400
			);
		}

		$holiday_manager = leave_manager_public_holiday();
		$result = $holiday_manager->add_public_holiday( $country_code, $date, $name );

		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $result->get_error_message(),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Holiday created successfully',
				'id' => $result,
			),
			201
		);
	}

	/**
	 * Get API namespace
	 *
	 * @return string
	 */
	public function get_api_namespace() {
		return $this->api_namespace;
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_api' ) ) {
	/**
	 * Get API integration manager instance
	 *
	 * @return Leave_Manager_API_Integration_Manager
	 */
	function leave_manager_api() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_API_Integration_Manager();
		}

		return $instance;
	}
}
