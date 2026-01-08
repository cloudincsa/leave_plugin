<?php
/**
 * Email Templates Class - Professional email designs with ChatPanel branding
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Email_Templates class
 */
class Leave_Manager_Email_Templates {

	/**
	 * Branding instance
	 *
	 * @var Leave_Manager_Branding
	 */
	private $branding;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Branding $branding Branding instance
	 */
	public function __construct( $branding ) {
		$this->branding = $branding;
	}

	/**
	 * Get email header HTML
	 *
	 * @return string Header HTML
	 */
	private function get_email_header() {
		$logo = $this->branding->get_logo_html( 'email-logo' );
		$org_name = $this->branding->get_organization_name();
		$primary_color = $this->branding->get_setting( 'primary_color' );

		return "
		<div style=\"background: linear-gradient(135deg, {$primary_color} 0%, #667eea 100%); padding: 40px 20px; text-align: center; border-radius: 12px 12px 0 0;\">
			<div style=\"max-width: 600px; margin: 0 auto;\">
				{$logo}
				<h1 style=\"color: white; margin: 20px 0 0 0; font-size: 28px; font-weight: 700; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;\">
					{$org_name}
				</h1>
			</div>
		</div>";
	}

	/**
	 * Get email footer HTML
	 *
	 * @return string Footer HTML
	 */
	private function get_email_footer() {
		$org_name = $this->branding->get_organization_name();
		$site_url = get_bloginfo( 'url' );
		$current_year = date( 'Y' );

		return "
		<div style=\"background: #f8f9fa; padding: 40px 20px; text-align: center; border-radius: 0 0 12px 12px; border-top: 1px solid #e0e0e0;\">
			<div style=\"max-width: 600px; margin: 0 auto;\">
				<p style=\"color: #666; font-size: 13px; margin: 0 0 15px 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;\">
					<strong>Connecting Communities, One Message</strong>
				</p>
				<p style=\"color: #999; font-size: 12px; margin: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;\">
					© {$current_year} {$org_name}. All rights reserved.
				</p>
				<p style=\"color: #667eea; font-size: 12px; margin: 15px 0 0 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;\">
					<a href=\"{$site_url}\" style=\"color: #667eea; text-decoration: none;\">Website</a> | 
					<a href=\"{$site_url}/privacy\" style=\"color: #667eea; text-decoration: none;\">Privacy</a> | 
					<a href=\"{$site_url}/terms\" style=\"color: #667eea; text-decoration: none;\">Terms</a>
				</p>
			</div>
		</div>";
	}

	/**
	 * Get email wrapper HTML
	 *
	 * @param string $content Email content
	 * @return string Wrapped HTML
	 */
	private function wrap_email( $content ) {
		$primary_color = $this->branding->get_setting( 'primary_color' );

		return "
		<!DOCTYPE html>
		<html>
		<head>
			<meta charset=\"UTF-8\">
			<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
			<style>
				body {
					font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
					line-height: 1.6;
					color: #333;
					background-color: #f5f5f5;
					margin: 0;
					padding: 0;
				}
				.email-container {
					max-width: 600px;
					margin: 20px auto;
					background: white;
					border-radius: 12px;
					box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
					overflow: hidden;
				}
				.email-content {
					padding: 40px;
					color: #333;
				}
				.email-content h2 {
					color: {$primary_color};
					font-size: 24px;
					margin: 0 0 20px 0;
					font-weight: 700;
				}
				.email-content p {
					margin: 0 0 15px 0;
					font-size: 14px;
					line-height: 1.8;
				}
				.email-button {
					display: inline-block;
					background: linear-gradient(135deg, {$primary_color} 0%, #667eea 100%);
					color: white;
					padding: 14px 32px;
					border-radius: 8px;
					text-decoration: none;
					font-weight: 600;
					font-size: 14px;
					margin: 20px 0;
					transition: transform 0.2s;
				}
				.email-button:hover {
					transform: translateY(-2px);
				}
				.info-box {
					background: #f0f4ff;
					border-left: 4px solid {$primary_color};
					padding: 20px;
					margin: 20px 0;
					border-radius: 4px;
				}
				.info-box h4 {
					color: {$primary_color};
					margin: 0 0 10px 0;
					font-size: 14px;
					font-weight: 700;
				}
				.info-box ul {
					margin: 0;
					padding-left: 20px;
				}
				.info-box li {
					margin: 8px 0;
					font-size: 13px;
				}
				.email-logo {
					max-width: 200px;
					height: auto;
					margin: 0 auto;
				}
				.email-logo-text {
					font-size: 24px;
					font-weight: 700;
					color: white;
				}
			</style>
		</head>
		<body>
			<div class=\"email-container\">
				{$this->get_email_header()}
				<div class=\"email-content\">
					{$content}
				</div>
				{$this->get_email_footer()}
			</div>
		</body>
		</html>";
	}

	/**
	 * Get welcome email template
	 *
	 * @param array $user_data User data
	 * @return string Email HTML
	 */
	public function get_welcome_email( $user_data ) {
		$first_name = isset( $user_data['first_name'] ) ? $user_data['first_name'] : 'User';
		$organization = $this->branding->get_organization_name();
		$primary_color = $this->branding->get_setting( 'primary_color' );
		$dashboard_url = get_site_url() . '/leave-management/dashboard';

		$content = "
		<h2>Welcome to {$organization}!</h2>
		
		<p>Hi {$first_name},</p>
		
		<p>Thank you for signing up for our Leave Management System! We're excited to help you manage your time off efficiently and transparently.</p>
		
		<p>To get started, please confirm your email address by clicking the button below:</p>
		
		<div style=\"text-align: center;\">
			<a href=\"{$dashboard_url}\" class=\"email-button\">Access Your Dashboard</a>
		</div>
		
		<div class=\"info-box\">
			<h4>What's next?</h4>
			<ul>
				<li>Set up your account profile</li>
				<li>Review your leave balance and policies</li>
				<li>Submit your first leave request</li>
				<li>Track your leave history</li>
			</ul>
		</div>
		
		<p>If you didn't create an account with us, you can safely ignore this email.</p>
		
		<p>If you're having trouble with the button above, copy and paste the following link into your browser:</p>
		
		<p style=\"word-break: break-all; color: #667eea; font-size: 12px;\">{$dashboard_url}</p>
		
		<p style=\"margin-top: 30px; color: #666; font-size: 13px;\">
			Welcome aboard!<br>
			<strong>The {$organization} Team</strong>
		</p>";

		return $this->wrap_email( $content );
	}

	/**
	 * Get leave request notification email
	 *
	 * @param array $request_data Request data
	 * @return string Email HTML
	 */
	public function get_leave_request_notification( $request_data ) {
		$employee_name = isset( $request_data['employee_name'] ) ? $request_data['employee_name'] : 'Employee';
		$leave_type = isset( $request_data['leave_type'] ) ? $request_data['leave_type'] : 'Leave';
		$start_date = isset( $request_data['start_date'] ) ? $request_data['start_date'] : '';
		$end_date = isset( $request_data['end_date'] ) ? $request_data['end_date'] : '';
		$days = isset( $request_data['days'] ) ? $request_data['days'] : '';
		$reason = isset( $request_data['reason'] ) ? $request_data['reason'] : '';
		$requests_url = get_site_url() . '/wp-admin/admin.php?page=leave-manager-requests';

		$content = "
		<h2>New Leave Request Submitted</h2>
		
		<p>A new leave request has been submitted and requires your attention.</p>
		
		<div class=\"info-box\">
			<h4>Request Details</h4>
			<ul>
				<li><strong>Employee:</strong> {$employee_name}</li>
				<li><strong>Leave Type:</strong> " . ucfirst( str_replace( '_', ' ', $leave_type ) ) . "</li>
				<li><strong>Start Date:</strong> " . date( 'F j, Y', strtotime( $start_date ) ) . "</li>
				<li><strong>End Date:</strong> " . date( 'F j, Y', strtotime( $end_date ) ) . "</li>
				<li><strong>Duration:</strong> {$days} day(s)</li>
			</ul>
		</div>
		
		" . ( $reason ? "<p><strong>Reason:</strong></p><p>{$reason}</p>" : '' ) . "
		
		<div style=\"text-align: center;\">
			<a href=\"{$requests_url}\" class=\"email-button\">Review Request</a>
		</div>
		
		<p style=\"color: #666; font-size: 13px; margin-top: 30px;\">
			Please review and approve or reject this request at your earliest convenience.
		</p>";

		return $this->wrap_email( $content );
	}

	/**
	 * Get leave approval email
	 *
	 * @param array $request_data Request data
	 * @return string Email HTML
	 */
	public function get_leave_approval( $request_data ) {
		$employee_name = isset( $request_data['employee_name'] ) ? $request_data['employee_name'] : 'User';
		$leave_type = isset( $request_data['leave_type'] ) ? $request_data['leave_type'] : 'Leave';
		$start_date = isset( $request_data['start_date'] ) ? $request_data['start_date'] : '';
		$end_date = isset( $request_data['end_date'] ) ? $request_data['end_date'] : '';
		$approval_date = isset( $request_data['approval_date'] ) ? $request_data['approval_date'] : date( 'F j, Y' );
		$dashboard_url = get_site_url() . '/leave-management/dashboard';

		$content = "
		<h2>Your Leave Request Has Been Approved!</h2>
		
		<p>Hi {$employee_name},</p>
		
		<p>Great news! Your leave request has been approved.</p>
		
		<div class=\"info-box\">
			<h4>Approved Leave Details</h4>
			<ul>
				<li><strong>Leave Type:</strong> " . ucfirst( str_replace( '_', ' ', $leave_type ) ) . "</li>
				<li><strong>Start Date:</strong> " . date( 'F j, Y', strtotime( $start_date ) ) . "</li>
				<li><strong>End Date:</strong> " . date( 'F j, Y', strtotime( $end_date ) ) . "</li>
				<li><strong>Approved On:</strong> {$approval_date}</li>
			</ul>
		</div>
		
		<p>You're all set! Make sure to mark these dates on your calendar.</p>
		
		<div style=\"text-align: center;\">
			<a href=\"{$dashboard_url}\" class=\"email-button\">View Dashboard</a>
		</div>";

		return $this->wrap_email( $content );
	}

	/**
	 * Get leave rejection email
	 *
	 * @param array $request_data Request data
	 * @return string Email HTML
	 */
	public function get_leave_rejection( $request_data ) {
		$employee_name = isset( $request_data['employee_name'] ) ? $request_data['employee_name'] : 'User';
		$leave_type = isset( $request_data['leave_type'] ) ? $request_data['leave_type'] : 'Leave';
		$start_date = isset( $request_data['start_date'] ) ? $request_data['start_date'] : '';
		$end_date = isset( $request_data['end_date'] ) ? $request_data['end_date'] : '';
		$reason = isset( $request_data['reason'] ) ? $request_data['reason'] : 'No reason provided';
		$dashboard_url = get_site_url() . '/leave-management/dashboard';

		$content = "
		<h2>Your Leave Request Has Been Rejected</h2>
		
		<p>Hi {$employee_name},</p>
		
		<p>Unfortunately, your leave request has been rejected.</p>
		
		<div class=\"info-box\">
			<h4>Request Details</h4>
			<ul>
				<li><strong>Leave Type:</strong> " . ucfirst( str_replace( '_', ' ', $leave_type ) ) . "</li>
				<li><strong>Start Date:</strong> " . date( 'F j, Y', strtotime( $start_date ) ) . "</li>
				<li><strong>End Date:</strong> " . date( 'F j, Y', strtotime( $end_date ) ) . "</li>
				<li><strong>Reason:</strong> {$reason}</li>
			</ul>
		</div>
		
		<p>Please contact your manager if you would like to discuss this decision or submit a new request.</p>
		
		<div style=\"text-align: center;\">
			<a href=\"{$dashboard_url}\" class=\"email-button\">View Dashboard</a>
		</div>";

		return $this->wrap_email( $content );
	}

	/**
	 * Get password reset email
	 *
	 * @param array $user_data User data
	 * @param string $reset_link Password reset link
	 * @return string Email HTML
	 */
	public function get_password_reset( $user_data, $reset_link ) {
		$first_name = isset( $user_data['first_name'] ) ? $user_data['first_name'] : 'User';

		$content = "
		<h2>Reset Your Password</h2>
		
		<p>Hi {$first_name},</p>
		
		<p>We received a request to reset your password. Click the button below to create a new password:</p>
		
		<div style=\"text-align: center;\">
			<a href=\"{$reset_link}\" class=\"email-button\">Reset Password</a>
		</div>
		
		<p>If you didn't request a password reset, you can safely ignore this email.</p>
		
		<p>If you're having trouble with the button above, copy and paste the following link into your browser:</p>
		
		<p style=\"word-break: break-all; color: #667eea; font-size: 12px;\">{$reset_link}</p>
		
		<p style=\"color: #999; font-size: 12px; margin-top: 30px;\">
			This link will expire in 24 hours.
		</p>";

		return $this->wrap_email( $content );
	}

	/**
	 * Get account created email
	 *
	 * @param array $user_data User data
	 * @param string $temp_password Temporary password
	 * @return string Email HTML
	 */
	public function get_account_created( $user_data, $temp_password ) {
		$first_name = isset( $user_data['first_name'] ) ? $user_data['first_name'] : 'User';
		$email = isset( $user_data['email'] ) ? $user_data['email'] : '';
		$login_url = get_site_url() . '/wp-login.php';

		$content = "
		<h2>Your Account Has Been Created</h2>
		
		<p>Hi {$first_name},</p>
		
		<p>Your account has been successfully created in our Leave Management System. Here are your login credentials:</p>
		
		<div class=\"info-box\">
			<h4>Login Information</h4>
			<ul>
				<li><strong>Email:</strong> {$email}</li>
				<li><strong>Temporary Password:</strong> {$temp_password}</li>
			</ul>
		</div>
		
		<p>Please log in and change your password immediately for security purposes.</p>
		
		<div style=\"text-align: center;\">
			<a href=\"{$login_url}\" class=\"email-button\">Log In Now</a>
		</div>
		
		<p style=\"color: #666; font-size: 13px; margin-top: 30px;\">
			If you have any questions, please contact your administrator.
		</p>";

		return $this->wrap_email( $content );
	}
}
