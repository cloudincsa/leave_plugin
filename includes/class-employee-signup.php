<?php
/**
 * Employee Self-Service Signup class for Leave Manager Plugin
 *
 * Handles employee registration, verification, and account creation.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Employee_Signup class
 */
class Leave_Manager_Employee_Signup {

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
	 * Signup table name
	 *
	 * @var string
	 */
	private $signup_table;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 */
	public function __construct( $db, $logger ) {
		global $wpdb;
		$this->db           = $db;
		$this->logger       = $logger;
		$this->signup_table = $wpdb->prefix . 'leave_manager_employee_signups';

		// Register AJAX handlers
		add_action( 'wp_ajax_nopriv_leave_manager_submit_signup', array( $this, 'handle_signup_submission' ) );
		add_action( 'wp_ajax_nopriv_leave_manager_verify_email', array( $this, 'handle_email_verification' ) );
		add_action( 'wp_ajax_nopriv_leave_manager_complete_signup', array( $this, 'handle_complete_signup' ) );
	}

	/**
	 * Create signup record
	 *
	 * @param array $signup_data Signup data
	 * @return int|false Signup ID or false on failure
	 */
	public function create_signup( $signup_data ) {
		global $wpdb;

		// Validate required fields
		if ( empty( $signup_data['first_name'] ) || empty( $signup_data['last_name'] ) || empty( $signup_data['email'] ) ) {
			return false;
		}

		// Check if email already exists in WordPress
		if ( email_exists( $signup_data['email'] ) ) {
			$this->logger->warning( 'Signup failed: email already registered', array( 'email' => $signup_data['email'] ) );
			return false;
		}

		// Check if email already has pending signup
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT signup_id FROM {$this->signup_table} WHERE email = %s AND status IN ('pending', 'verified')",
				$signup_data['email']
			)
		);

		if ( $existing ) {
			return false;
		}

		// Generate verification code
		$verification_code = wp_generate_password( 32, false );

		$signup = array(
			'first_name'          => sanitize_text_field( $signup_data['first_name'] ),
			'last_name'           => sanitize_text_field( $signup_data['last_name'] ),
			'email'               => sanitize_email( $signup_data['email'] ),
			'phone'               => sanitize_text_field( $signup_data['phone'] ?? '' ),
			'department'          => sanitize_text_field( $signup_data['department'] ?? '' ),
			'position'            => sanitize_text_field( $signup_data['position'] ?? '' ),
			'policy_id'           => intval( $signup_data['policy_id'] ?? 0 ),
			'verification_code'   => $verification_code,
			'status'              => 'pending',
			'ip_address'          => $this->get_client_ip(),
			'created_at'          => current_time( 'mysql' ),
			'verified_at'         => null,
			'completed_at'        => null,
		);

		$result = $wpdb->insert( $this->signup_table, $signup );

		if ( $result ) {
			$signup_id = $wpdb->insert_id;
			$this->logger->info( 'Employee signup created', array( 'signup_id' => $signup_id, 'email' => $signup_data['email'] ) );

			// Send verification email
			$this->send_verification_email( $signup_id, $signup_data['email'], $verification_code );

			return $signup_id;
		} else {
			$this->logger->error( 'Employee signup creation failed', array( 'error' => $wpdb->last_error ) );
			return false;
		}
	}

	/**
	 * Get signup record
	 *
	 * @param int $signup_id Signup ID
	 * @return object|null Signup object or null
	 */
	public function get_signup( $signup_id ) {
		global $wpdb;
		$signup_id = intval( $signup_id );

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->signup_table} WHERE signup_id = %d",
				$signup_id
			)
		);
	}

	/**
	 * Get signup by email
	 *
	 * @param string $email Email address
	 * @return object|null Signup object or null
	 */
	public function get_signup_by_email( $email ) {
		global $wpdb;
		$email = sanitize_email( $email );

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->signup_table} WHERE email = %s ORDER BY created_at DESC LIMIT 1",
				$email
			)
		);
	}

	/**
	 * Verify email with code
	 *
	 * @param int    $signup_id Signup ID
	 * @param string $code Verification code
	 * @return bool True on success
	 */
	public function verify_email( $signup_id, $code ) {
		global $wpdb;
		$signup_id = intval( $signup_id );
		$code      = sanitize_text_field( $code );

		$signup = $this->get_signup( $signup_id );

		if ( ! $signup || $signup->status !== 'pending' ) {
			return false;
		}

		if ( ! hash_equals( $signup->verification_code, $code ) ) {
			$this->logger->warning( 'Email verification failed: invalid code', array( 'signup_id' => $signup_id ) );
			return false;
		}

		// Check if code is not expired (24 hours)
		$created_time = strtotime( $signup->created_at );
		$current_time = current_time( 'timestamp' );
		if ( $current_time - $created_time > 86400 ) {
			$this->logger->warning( 'Email verification failed: code expired', array( 'signup_id' => $signup_id ) );
			return false;
		}

		$result = $wpdb->update(
			$this->signup_table,
			array(
				'status'      => 'verified',
				'verified_at' => current_time( 'mysql' ),
			),
			array( 'signup_id' => $signup_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		if ( $result !== false ) {
			$this->logger->info( 'Email verified', array( 'signup_id' => $signup_id ) );
			return true;
		}

		return false;
	}

	/**
	 * Complete signup and create user account
	 *
	 * @param int    $signup_id Signup ID
	 * @param string $username Username
	 * @param string $password Password
	 * @return int|false User ID or false on failure
	 */
	public function complete_signup( $signup_id, $username, $password ) {
		global $wpdb;
		$signup_id = intval( $signup_id );
		$username  = sanitize_user( $username );
		$password  = sanitize_text_field( $password );

		$signup = $this->get_signup( $signup_id );

		if ( ! $signup || $signup->status !== 'verified' ) {
			return false;
		}

		// Validate username
		if ( empty( $username ) || strlen( $username ) < 3 ) {
			return false;
		}

		if ( username_exists( $username ) ) {
			return false;
		}

		// Validate password
		if ( empty( $password ) || strlen( $password ) < 8 ) {
			return false;
		}

		// Create WordPress user
		$user_id = wp_create_user( $username, $password, $signup->email );

		if ( is_wp_error( $user_id ) ) {
			$this->logger->error( 'User creation failed', array( 'error' => $user_id->get_error_message() ) );
			return false;
		}

		// Add user to Leave Manager staff role
		$user = new WP_User( $user_id );
		$user->add_role( 'leave_manager_staff' );

		// Create leave user record
		$leave_user = array(
			'wp_user_id'             => $user_id,
			'first_name'             => $signup->first_name,
			'last_name'              => $signup->last_name,
			'email'                  => $signup->email,
			'phone'                  => $signup->phone,
			'role'                   => 'staff',
			'department'             => $signup->department,
			'position'               => $signup->position,
			'policy_id'              => $signup->policy_id > 0 ? $signup->policy_id : null,
			'annual_leave_balance'   => 0,
			'sick_leave_balance'     => 0,
			'other_leave_balance'    => 0,
			'status'                 => 'active',
			'created_at'             => current_time( 'mysql' ),
		);

		$users_table = $wpdb->prefix . 'leave_manager_leave_users';
		$result      = $wpdb->insert( $users_table, $leave_user );

		if ( ! $result ) {
			// Delete WordPress user if leave user creation fails
			wp_delete_user( $user_id );
			$this->logger->error( 'Leave user creation failed', array( 'user_id' => $user_id ) );
			return false;
		}

		// Update signup status
		$wpdb->update(
			$this->signup_table,
			array(
				'status'       => 'completed',
				'completed_at' => current_time( 'mysql' ),
			),
			array( 'signup_id' => $signup_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$this->logger->info( 'Employee signup completed', array( 'user_id' => $user_id, 'signup_id' => $signup_id ) );

		// Send welcome email
		$this->send_welcome_email( $user_id, $signup->email, $username );

		return $user_id;
	}

	/**
	 * Send verification email
	 *
	 * @param int    $signup_id Signup ID
	 * @param string $email Email address
	 * @param string $code Verification code
	 * @return bool True on success
	 */
	private function send_verification_email( $signup_id, $email, $code ) {
		$verification_link = add_query_arg(
			array(
				'action' => 'verify_email',
				'signup' => $signup_id,
				'code'   => $code,
			),
			home_url( '/employee-signup/' )
		);

		$subject = 'Verify Your Email - Leave Manager';
		$message = sprintf(
			'<p>Hello,</p>
			<p>Thank you for signing up for the Leave Manager System. Please verify your email address by clicking the link below:</p>
			<p><a href="%s" style="background-color: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Verify Email</a></p>
			<p>Or copy and paste this link in your browser:</p>
			<p>%s</p>
			<p>This link will expire in 24 hours.</p>
			<p>Best regards,<br>Leave Manager Team</p>',
			esc_url( $verification_link ),
			esc_url( $verification_link )
		);

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		return wp_mail( $email, $subject, $message, $headers );
	}

	/**
	 * Send welcome email
	 *
	 * @param int    $user_id User ID
	 * @param string $email Email address
	 * @param string $username Username
	 * @return bool True on success
	 */
	private function send_welcome_email( $user_id, $email, $username ) {
		$login_url = wp_login_url();

		$subject = 'Welcome to Leave Manager System';
		$message = sprintf(
			'<p>Hello %s,</p>
			<p>Your account has been successfully created! You can now log in to the Leave Manager System.</p>
			<p><a href="%s" style="background-color: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Log In</a></p>
			<p><strong>Your Login Details:</strong></p>
			<ul>
				<li><strong>Username:</strong> %s</li>
				<li><strong>Email:</strong> %s</li>
			</ul>
			<p>You can now submit leave requests, view your leave balance, and manage your leave through the system.</p>
			<p>Best regards,<br>Leave Manager Team</p>',
			esc_html( $username ),
			esc_url( $login_url ),
			esc_html( $username ),
			esc_html( $email )
		);

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		return wp_mail( $email, $subject, $message, $headers );
	}

	/**
	 * Handle signup form submission via AJAX
	 *
	 * @return void
	 */
	public function handle_signup_submission() {
		check_ajax_referer( 'leave_manager_signup_nonce' );

		$signup_data = array(
			'first_name' => sanitize_text_field( $_POST['first_name'] ?? '' ),
			'last_name'  => sanitize_text_field( $_POST['last_name'] ?? '' ),
			'email'      => sanitize_email( $_POST['email'] ?? '' ),
			'phone'      => sanitize_text_field( $_POST['phone'] ?? '' ),
			'department' => sanitize_text_field( $_POST['department'] ?? '' ),
			'position'   => sanitize_text_field( $_POST['position'] ?? '' ),
			'policy_id'  => intval( $_POST['policy_id'] ?? 0 ),
		);

		$signup_id = $this->create_signup( $signup_data );

		if ( $signup_id ) {
			wp_send_json_success(
				array(
					'message'   => 'Signup submitted successfully. Please check your email to verify your address.',
					'signup_id' => $signup_id,
				)
			);
		} else {
			wp_send_json_error( array( 'message' => 'Failed to submit signup. Please try again.' ) );
		}
	}

	/**
	 * Handle email verification via AJAX
	 *
	 * @return void
	 */
	public function handle_email_verification() {
		check_ajax_referer( 'leave_manager_signup_nonce' );

		$signup_id = intval( $_POST['signup_id'] ?? 0 );
		$code      = sanitize_text_field( $_POST['code'] ?? '' );

		if ( $this->verify_email( $signup_id, $code ) ) {
			wp_send_json_success( array( 'message' => 'Email verified successfully!' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Invalid or expired verification code.' ) );
		}
	}

	/**
	 * Handle signup completion via AJAX
	 *
	 * @return void
	 */
	public function handle_complete_signup() {
		check_ajax_referer( 'leave_manager_signup_nonce' );

		$signup_id = intval( $_POST['signup_id'] ?? 0 );
		$username  = sanitize_user( $_POST['username'] ?? '' );
		$password  = sanitize_text_field( $_POST['password'] ?? '' );
		$password2 = sanitize_text_field( $_POST['password2'] ?? '' );

		if ( $password !== $password2 ) {
			wp_send_json_error( array( 'message' => 'Passwords do not match.' ) );
		}

		$user_id = $this->complete_signup( $signup_id, $username, $password );

		if ( $user_id ) {
			wp_send_json_success(
				array(
					'message'  => 'Account created successfully! You can now log in.',
					'login_url' => wp_login_url(),
				)
			);
		} else {
			wp_send_json_error( array( 'message' => 'Failed to create account. Please try again.' ) );
		}
	}

	/**
	 * Get client IP address
	 *
	 * @return string IP address
	 */
	private function get_client_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return sanitize_text_field( $ip );
	}
}
