<?php
/**
 * Email Handler class for Leave Manager Plugin
 *
 * Handles email operations including template-based sending and logging.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Email_Handler class
 */
class Leave_Manager_Email_Handler {

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
	 * Email logs table name
	 *
	 * @var string
	 */
	private $logs_table;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 * @param Leave_Manager_Settings $settings Settings instance
	 */
	public function __construct( $db, $logger, $settings ) {
		$this->db        = $db;
		$this->logger    = $logger;
		$this->settings  = $settings;
		$this->logs_table = $db->email_logs_table;
	}

	/**
	 * Send an email using a template
	 *
	 * @param string $recipient Recipient email address
	 * @param string $template Template name
	 * @param array  $variables Template variables
	 * @return bool True on success
	 */
	public function send_template_email( $recipient, $template, $variables = array() ) {
		// Get template file
		$template_file = LEAVE_MANAGER_PLUGIN_DIR . "templates/emails/{$template}.html";

		if ( ! file_exists( $template_file ) ) {
			$this->logger->error( 'Email template not found', array( 'template' => $template ) );
			return false;
		}

		// Load template content
		$content = file_get_contents( $template_file );

		// Replace variables
		foreach ( $variables as $key => $value ) {
			$content = str_replace( '{{' . $key . '}}', $value, $content );
		}

		// Extract subject from template
		preg_match( '/<subject>(.*?)<\/subject>/s', $content, $matches );
		$subject = isset( $matches[1] ) ? trim( $matches[1] ) : 'Leave Manager';

		// Remove subject tags from content
		$content = preg_replace( '/<subject>.*?<\/subject>/s', '', $content );

		// Send email
		return $this->send_email( $recipient, $subject, $content, $template );
	}

	/**
	 * Send a plain email
	 *
	 * @param string $recipient Recipient email address
	 * @param string $subject Email subject
	 * @param string $message Email message
	 * @param string $template Template name for logging
	 * @return bool True on success
	 */
	public function send_email( $recipient, $subject, $message, $template = '' ) {
		$recipient = sanitize_email( $recipient );

		// Prepare email headers
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $this->settings->get( 'smtp_from_name' ) . ' <' . $this->settings->get( 'smtp_from_email' ) . '>',
		);

		// Send email
		$result = wp_mail( $recipient, $subject, $message, $headers );

		// Log email
		$this->log_email( $recipient, $subject, $template, $result ? 'sent' : 'failed' );

		if ( $result ) {
			$this->logger->info( 'Email sent successfully', array(
				'recipient' => $recipient,
				'subject'   => $subject,
				'template'  => $template,
			) );
		} else {
			$this->logger->error( 'Email sending failed', array(
				'recipient' => $recipient,
				'subject'   => $subject,
				'template'  => $template,
			) );
		}

		return $result;
	}

	/**
	 * Log an email operation
	 *
	 * @param string $recipient Recipient email
	 * @param string $subject Email subject
	 * @param string $template Template used
	 * @param string $status Email status
	 * @param string $error Error message if any
	 * @return int|false Log ID or false
	 */
	public function log_email( $recipient, $subject, $template, $status, $error = '' ) {
		$log_data = array(
			'recipient_email' => sanitize_email( $recipient ),
			'subject'         => sanitize_text_field( $subject ),
			'template_used'   => sanitize_text_field( $template ),
			'status'          => sanitize_text_field( $status ),
			'error_message'   => sanitize_textarea_field( $error ),
		);

		return $this->db->insert(
			$this->logs_table,
			$log_data,
			array( '%s', '%s', '%s', '%s', '%s' )
		);
	}

	/**
	 * Get user variables for email template
	 *
	 * @param int $user_id User ID
	 * @return array User variables
	 */
	public function get_user_variables( $user_id ) {
		global $wpdb;
		$users_table = $wpdb->prefix . 'leave_manager_leave_users';

		$user = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $users_table WHERE user_id = %d",
				intval( $user_id )
			)
		);

		if ( ! $user ) {
			return array();
		}

		return array(
			'user_id'      => $user->user_id,
			'first_name'   => $user->first_name,
			'last_name'    => $user->last_name,
			'email'        => $user->email,
			'full_name'    => $user->first_name . ' ' . $user->last_name,
			'department'   => $user->department,
			'position'     => $user->position,
		);
	}

	/**
	 * Get leave request variables for email template
	 *
	 * @param int $request_id Request ID
	 * @return array Request variables
	 */
	public function get_leave_request_variables( $request_id ) {
		global $wpdb;
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		$request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $requests_table WHERE request_id = %d",
				intval( $request_id )
			)
		);

		if ( ! $request ) {
			return array();
		}

		return array(
			'request_id'  => $request->request_id,
			'leave_type'  => $request->leave_type,
			'start_date'  => $request->start_date,
			'end_date'    => $request->end_date,
			'reason'      => $request->reason,
			'status'      => $request->status,
		);
	}

	/**
	 * Send welcome email
	 *
	 * @param int $user_id User ID
	 * @return bool True on success
	 */
	public function send_welcome_email( $user_id ) {
		$user_vars = $this->get_user_variables( $user_id );

		if ( empty( $user_vars ) ) {
			return false;
		}

		$variables = array_merge(
			$user_vars,
			array(
				'organization_name' => $this->settings->get( 'organization_name' ),
				'organization_email' => $this->settings->get( 'organization_email' ),
			)
		);

		return $this->send_template_email( $user_vars['email'], 'welcome', $variables );
	}

	/**
	 * Send leave request notification
	 *
	 * @param int $request_id Request ID
	 * @return bool True on success
	 */
	public function send_leave_request_notification( $request_id ) {
		global $wpdb;
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
		$users_table    = $wpdb->prefix . 'leave_manager_leave_users';

		$request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $requests_table WHERE request_id = %d",
				intval( $request_id )
			)
		);

		if ( ! $request ) {
			return false;
		}

		$user = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $users_table WHERE user_id = %d",
				$request->user_id
			)
		);

		if ( ! $user ) {
			return false;
		}

		$variables = array_merge(
			$this->get_user_variables( $user->user_id ),
			$this->get_leave_request_variables( $request_id ),
			array(
				'organization_name' => $this->settings->get( 'organization_name' ),
			)
		);

		// Get HR emails
		$hr_users = $wpdb->get_results( "SELECT email FROM $users_table WHERE role IN ('hr', 'admin')" );

		$success = true;
		foreach ( $hr_users as $hr_user ) {
			if ( ! $this->send_template_email( $hr_user->email, 'leave-request', $variables ) ) {
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Send leave approval notification
	 *
	 * @param int $request_id Request ID
	 * @return bool True on success
	 */
	public function send_leave_approval_notification( $request_id ) {
		global $wpdb;
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
		$users_table    = $wpdb->prefix . 'leave_manager_leave_users';

		$request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $requests_table WHERE request_id = %d",
				intval( $request_id )
			)
		);

		if ( ! $request ) {
			return false;
		}

		$user = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $users_table WHERE user_id = %d",
				$request->user_id
			)
		);

		if ( ! $user ) {
			return false;
		}

		$variables = array_merge(
			$this->get_user_variables( $user->user_id ),
			$this->get_leave_request_variables( $request_id ),
			array(
				'organization_name' => $this->settings->get( 'organization_name' ),
				'approval_date'     => current_time( 'Y-m-d' ),
			)
		);

		return $this->send_template_email( $user->email, 'leave-approval', $variables );
	}

	/**
	 * Send leave rejection notification
	 *
	 * @param int    $request_id Request ID
	 * @param string $reason Rejection reason
	 * @return bool True on success
	 */
	public function send_leave_rejection_notification( $request_id, $reason = '' ) {
		global $wpdb;
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
		$users_table    = $wpdb->prefix . 'leave_manager_leave_users';

		$request = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $requests_table WHERE request_id = %d",
				intval( $request_id )
			)
		);

		if ( ! $request ) {
			return false;
		}

		$user = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $users_table WHERE user_id = %d",
				$request->user_id
			)
		);

		if ( ! $user ) {
			return false;
		}

		$variables = array_merge(
			$this->get_user_variables( $user->user_id ),
			$this->get_leave_request_variables( $request_id ),
			array(
				'organization_name' => $this->settings->get( 'organization_name' ),
				'rejection_reason'  => $reason,
			)
		);

		return $this->send_template_email( $user->email, 'leave-rejection', $variables );
	}

	/**
	 * Send password reset email
	 *
	 * @param int    $user_id User ID
	 * @param string $reset_link Password reset link
	 * @return bool True on success
	 */
	public function send_password_reset_email( $user_id, $reset_link ) {
		$user_vars = $this->get_user_variables( $user_id );

		if ( empty( $user_vars ) ) {
			return false;
		}

		$variables = array_merge(
			$user_vars,
			array(
				'organization_name' => $this->settings->get( 'organization_name' ),
				'reset_link'        => $reset_link,
			)
		);

		return $this->send_template_email( $user_vars['email'], 'password-reset', $variables );
	}

	/**
	 * Test email configuration
	 *
	 * @param string $test_email Test email address
	 * @return bool True on success
	 */
	public function test_email_configuration( $test_email ) {
		$test_email = sanitize_email( $test_email );

		$subject = 'Leave Manager - Email Test';
		$message = '<h2>Email Configuration Test</h2>';
		$message .= '<p>This is a test email from Leave Manager Plugin.</p>';
		$message .= '<p>If you received this email, your email configuration is working correctly.</p>';

		return $this->send_email( $test_email, $subject, $message, 'test' );
	}

	/**
	 * Get email logs
	 *
	 * @param array $args Query arguments
	 * @return array Email logs
	 */
	public function get_email_logs( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'  => '',
			'limit'   => 100,
			'offset'  => 0,
			'orderby' => 'created_at',
			'order'   => 'DESC',
		);

		$args = wp_parse_args( $args, $defaults );

		$where = array( '1=1' );
		$values = array();

		if ( ! empty( $args['status'] ) ) {
			$where[] = 'status = %s';
			$values[] = $args['status'];
		}

		$where_clause = implode( ' AND ', $where );
		$query = "SELECT * FROM {$this->logs_table} WHERE {$where_clause} ORDER BY {$args['orderby']} {$args['order']} LIMIT {$args['offset']}, {$args['limit']}";

		if ( ! empty( $values ) ) {
			$query = $wpdb->prepare( $query, $values );
		}

		return $wpdb->get_results( $query );
	}

	/**
	 * Preview email template
	 *
	 * @param string $template Template name
	 * @param array  $variables Template variables
	 * @return string Template content
	 */
	public function preview_email_template( $template, $variables = array() ) {
		$template_file = LEAVE_MANAGER_PLUGIN_DIR . "templates/emails/{$template}.html";

		if ( ! file_exists( $template_file ) ) {
			return 'Template not found';
		}

		$content = file_get_contents( $template_file );

		// Replace variables
		foreach ( $variables as $key => $value ) {
			$content = str_replace( '{{' . $key . '}}', $value, $content );
		}

		return $content;
	}

	/**
	 * Setup notification hooks
	 *
	 * @return void
	 */
	public function setup_notification_hooks() {
		// Hook for leave request submission
		add_action( 'leave_manager_leave_request_submitted', array( $this, 'on_leave_request_submitted' ) );

		// Hook for leave request approval
		add_action( 'leave_manager_leave_request_approved', array( $this, 'on_leave_request_approved' ) );

		// Hook for leave request rejection
		add_action( 'leave_manager_leave_request_rejected', array( $this, 'on_leave_request_rejected' ) );

		// Hook for user creation
		add_action( 'leave_manager_user_created', array( $this, 'on_user_created' ) );
	}

	/**
	 * Handle leave request submitted event
	 *
	 * @param int $request_id Request ID
	 * @return void
	 */
	public function on_leave_request_submitted( $request_id ) {
		if ( $this->settings->get( 'notify_on_request' ) ) {
			$this->send_leave_request_notification( $request_id );
		}
	}

	/**
	 * Handle leave request approved event
	 *
	 * @param int $request_id Request ID
	 * @return void
	 */
	public function on_leave_request_approved( $request_id ) {
		if ( $this->settings->get( 'notify_on_approval' ) ) {
			$this->send_leave_approval_notification( $request_id );
		}
	}

	/**
	 * Handle leave request rejected event
	 *
	 * @param int $request_id Request ID
	 * @return void
	 */
	public function on_leave_request_rejected( $request_id ) {
		if ( $this->settings->get( 'notify_on_rejection' ) ) {
			$this->send_leave_rejection_notification( $request_id );
		}
	}

	/**
	 * Handle user created event
	 *
	 * @param int $user_id User ID
	 * @return void
	 */
	public function on_user_created( $user_id ) {
		$this->send_welcome_email( $user_id );
	}
}
