<?php
/**
 * API Handler class for Leave Manager Plugin
 *
 * Handles REST API endpoints for frontend communication.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_API_Handler class
 */
class Leave_Manager_API_Handler {

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
	 * Settings instance
	 *
	 * @var Leave_Manager_Settings
	 */
	private $settings;

	/**
	 * API namespace
	 *
	 * @var string
	 */
	private $namespace = 'leave-manager/v1';

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 * @param Leave_Manager_Settings $settings Settings instance
	 */
	public function __construct( $db, $logger, $settings ) {
		$this->db       = $db;
		$this->logger   = $logger;
		$this->settings = $settings;
	}

	/**
	 * Register API endpoints
	 *
	 * @return void
	 */
	public function register_endpoints() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register REST routes
	 *
	 * @return void
	 */
	public function register_routes() {
		// Authentication endpoints
		register_rest_route(
			$this->namespace,
			'/auth/login',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_login' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'/auth/logout',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_logout' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);

		// User endpoints
		register_rest_route(
			$this->namespace,
			'/user/profile',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_get_user_data' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/user/profile',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'handle_update_profile' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);

		// Leave request endpoints
		register_rest_route(
			$this->namespace,
			'/leave-requests',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_get_leave_requests' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/leave-requests',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_submit_leave_request' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/leave-requests/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'handle_update_leave_request' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/leave-requests/(?P<id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'handle_delete_leave_request' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);

		// Leave balance endpoint
		register_rest_route(
			$this->namespace,
			'/leave-balance',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_get_leave_balance' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);

		// Calendar endpoint
		register_rest_route(
			$this->namespace,
			'/calendar',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_get_calendar_data' ),
				'permission_callback' => array( $this, 'check_auth' ),
			)
		);
	}

	/**
	 * Check authentication
	 *
	 * @return bool True if authenticated
	 */
	public function check_auth() {
		return is_user_logged_in();
	}

	/**
	 * Handle login request
	 *
	 * @param WP_REST_Request $request REST request
	 * @return WP_REST_Response API response
	 */
	public function handle_login( WP_REST_Request $request ) {
		$email    = $request->get_param( 'email' );
		$password = $request->get_param( 'password' );

		if ( empty( $email ) || empty( $password ) ) {
			return new WP_REST_Response(
				array( 'message' => 'Email and password are required' ),
				400
			);
		}

		// Get user by email
		$users_class = new Leave_Manager_Users( $this->db, $this->logger );
		$user = $users_class->get_user_by_email( $email );

		if ( ! $user ) {
			$this->logger->log_user_auth( $email, 'login', false, 'User not found' );
			return new WP_REST_Response(
				array( 'message' => 'Invalid credentials' ),
				401
			);
		}

		// Verify password (simplified - in production use proper hashing)
		if ( $user->wp_user_id ) {
			$wp_user = get_user_by( 'id', $user->wp_user_id );
			if ( $wp_user && wp_check_password( $password, $wp_user->user_pass ) ) {
				wp_set_current_user( $wp_user->ID );
				wp_set_auth_cookie( $wp_user->ID );

				$this->logger->log_user_auth( $email, 'login', true );

				return new WP_REST_Response(
					array(
						'message'  => 'Login successful',
						'user_id'  => $user->user_id,
						'email'    => $user->email,
						'role'     => $user->role,
					),
					200
				);
			}
		}

		$this->logger->log_user_auth( $email, 'login', false, 'Invalid password' );
		return new WP_REST_Response(
			array( 'message' => 'Invalid credentials' ),
			401
		);
	}

	/**
	 * Handle logout request
	 *
	 * @param WP_REST_Request $request REST request
	 * @return WP_REST_Response API response
	 */
	public function handle_logout( WP_REST_Request $request ) {
		wp_logout();
		return new WP_REST_Response(
			array( 'message' => 'Logout successful' ),
			200
		);
	}

	/**
	 * Handle get user data request
	 *
	 * @param WP_REST_Request $request REST request
	 * @return WP_REST_Response API response
	 */
	public function handle_get_user_data( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->ID ) {
			return new WP_REST_Response(
				array( 'message' => 'Unauthorized' ),
				401
			);
		}

		$users_class = new Leave_Manager_Users( $this->db, $this->logger );
		$user = $users_class->get_user_by_email( $current_user->user_email );

		if ( ! $user ) {
			return new WP_REST_Response(
				array( 'message' => 'User not found' ),
				404
			);
		}

		return new WP_REST_Response( $user, 200 );
	}

	/**
	 * Handle update profile request
	 *
	 * @param WP_REST_Request $request REST request
	 * @return WP_REST_Response API response
	 */
	public function handle_update_profile( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->ID ) {
			return new WP_REST_Response(
				array( 'message' => 'Unauthorized' ),
				401
			);
		}

		$users_class = new Leave_Manager_Users( $this->db, $this->logger );
		$user = $users_class->get_user_by_email( $current_user->user_email );

		if ( ! $user ) {
			return new WP_REST_Response(
				array( 'message' => 'User not found' ),
				404
			);
		}

		$update_data = array();
		if ( $request->get_param( 'first_name' ) ) {
			$update_data['first_name'] = $request->get_param( 'first_name' );
		}
		if ( $request->get_param( 'last_name' ) ) {
			$update_data['last_name'] = $request->get_param( 'last_name' );
		}
		if ( $request->get_param( 'phone' ) ) {
			$update_data['phone'] = $request->get_param( 'phone' );
		}

		if ( $users_class->update_user( $user->user_id, $update_data ) ) {
			return new WP_REST_Response(
				array( 'message' => 'Profile updated successfully' ),
				200
			);
		}

		return new WP_REST_Response(
			array( 'message' => 'Failed to update profile' ),
			500
		);
	}

	/**
	 * Handle submit leave request
	 *
	 * @param WP_REST_Request $request REST request
	 * @return WP_REST_Response API response
	 */
	public function handle_submit_leave_request( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->ID ) {
			return new WP_REST_Response(
				array( 'message' => 'Unauthorized' ),
				401
			);
		}

		$users_class = new Leave_Manager_Users( $this->db, $this->logger );
		$user = $users_class->get_user_by_email( $current_user->user_email );

		if ( ! $user ) {
			return new WP_REST_Response(
				array( 'message' => 'User not found' ),
				404
			);
		}

		$leave_requests_class = new Leave_Manager_Leave_Requests( $this->db, $this->logger );
		$request_id = $leave_requests_class->submit_request( array(
			'user_id'    => $user->user_id,
			'leave_type' => $request->get_param( 'leave_type' ),
			'start_date' => $request->get_param( 'start_date' ),
			'end_date'   => $request->get_param( 'end_date' ),
			'reason'     => $request->get_param( 'reason' ),
		) );

		if ( $request_id ) {
			return new WP_REST_Response(
				array(
					'message'    => 'Leave request submitted successfully',
					'request_id' => $request_id,
				),
				201
			);
		}

		return new WP_REST_Response(
			array( 'message' => 'Failed to submit leave request' ),
			500
		);
	}

	/**
	 * Handle get leave requests
	 *
	 * @param WP_REST_Request $request REST request
	 * @return WP_REST_Response API response
	 */
	public function handle_get_leave_requests( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->ID ) {
			return new WP_REST_Response(
				array( 'message' => 'Unauthorized' ),
				401
			);
		}

		$users_class = new Leave_Manager_Users( $this->db, $this->logger );
		$user = $users_class->get_user_by_email( $current_user->user_email );

		if ( ! $user ) {
			return new WP_REST_Response(
				array( 'message' => 'User not found' ),
				404
			);
		}

		$leave_requests_class = new Leave_Manager_Leave_Requests( $this->db, $this->logger );
		$requests = $leave_requests_class->get_requests( array(
			'user_id' => $user->user_id,
		) );

		return new WP_REST_Response( $requests, 200 );
	}

	/**
	 * Handle update leave request
	 *
	 * @param WP_REST_Request $request REST request
	 * @return WP_REST_Response API response
	 */
	public function handle_update_leave_request( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->ID ) {
			return new WP_REST_Response(
				array( 'message' => 'Unauthorized' ),
				401
			);
		}

		$request_id = $request->get_url_params()['id'];
		$leave_requests_class = new Leave_Manager_Leave_Requests( $this->db, $this->logger );

		if ( $leave_requests_class->update_request( $request_id, array(
			'reason'     => $request->get_param( 'reason' ),
			'start_date' => $request->get_param( 'start_date' ),
			'end_date'   => $request->get_param( 'end_date' ),
		) ) ) {
			return new WP_REST_Response(
				array( 'message' => 'Leave request updated successfully' ),
				200
			);
		}

		return new WP_REST_Response(
			array( 'message' => 'Failed to update leave request' ),
			500
		);
	}

	/**
	 * Handle delete leave request
	 *
	 * @param WP_REST_Request $request REST request
	 * @return WP_REST_Response API response
	 */
	public function handle_delete_leave_request( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->ID ) {
			return new WP_REST_Response(
				array( 'message' => 'Unauthorized' ),
				401
			);
		}

		$request_id = $request->get_url_params()['id'];
		$leave_requests_class = new Leave_Manager_Leave_Requests( $this->db, $this->logger );

		if ( $leave_requests_class->delete_request( $request_id ) ) {
			return new WP_REST_Response(
				array( 'message' => 'Leave request deleted successfully' ),
				200
			);
		}

		return new WP_REST_Response(
			array( 'message' => 'Failed to delete leave request' ),
			500
		);
	}

	/**
	 * Handle get leave balance
	 *
	 * @param WP_REST_Request $request REST request
	 * @return WP_REST_Response API response
	 */
	public function handle_get_leave_balance( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->ID ) {
			return new WP_REST_Response(
				array( 'message' => 'Unauthorized' ),
				401
			);
		}

		$users_class = new Leave_Manager_Users( $this->db, $this->logger );
		$user = $users_class->get_user_by_email( $current_user->user_email );

		if ( ! $user ) {
			return new WP_REST_Response(
				array( 'message' => 'User not found' ),
				404
			);
		}

		return new WP_REST_Response(
			array(
				'annual_leave'  => $user->annual_leave_balance,
				'sick_leave'    => $user->sick_leave_balance,
				'other_leave'   => $user->other_leave_balance,
			),
			200
		);
	}

	/**
	 * Handle get calendar data
	 *
	 * @param WP_REST_Request $request REST request
	 * @return WP_REST_Response API response
	 */
	public function handle_get_calendar_data( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		if ( ! $current_user->ID ) {
			return new WP_REST_Response(
				array( 'message' => 'Unauthorized' ),
				401
			);
		}

		$users_class = new Leave_Manager_Users( $this->db, $this->logger );
		$user = $users_class->get_user_by_email( $current_user->user_email );

		if ( ! $user ) {
			return new WP_REST_Response(
				array( 'message' => 'User not found' ),
				404
			);
		}

		$leave_requests_class = new Leave_Manager_Leave_Requests( $this->db, $this->logger );
		$requests = $leave_requests_class->get_requests( array(
			'user_id' => $user->user_id,
			'status'  => 'approved',
		) );

		$calendar_data = array();
		foreach ( $requests as $req ) {
			$calendar_data[] = array(
				'title'       => ucfirst( $req->leave_type ) . ' Leave',
				'start'       => $req->start_date,
				'end'         => date( 'Y-m-d', strtotime( $req->end_date . ' +1 day' ) ),
				'request_id'  => $req->request_id,
				'leave_type'  => $req->leave_type,
			);
		}

		return new WP_REST_Response( $calendar_data, 200 );
	}
}
