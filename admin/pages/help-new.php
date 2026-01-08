<?php
/**
 * Help Page for Leave Manager Plugin
 *
 * Displays comprehensive help documentation and guides.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user permissions
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'You do not have permission to access this page.' );
}

// Get current tab
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'getting-started';
?>

<div class="wrap leave-manager-help">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<!-- Help Navigation Tabs -->
	<nav class="nav-tab-wrapper wp-clearfix">
		<a href="?page=leave-manager-help&tab=getting-started" class="nav-tab <?php echo $current_tab === 'getting-started' ? 'nav-tab-active' : ''; ?>">
			Getting Started
		</a>
		<a href="?page=leave-manager-help&tab=admin-guide" class="nav-tab <?php echo $current_tab === 'admin-guide' ? 'nav-tab-active' : ''; ?>">
			Admin Guide
		</a>
		<a href="?page=leave-manager-help&tab=manager-guide" class="nav-tab <?php echo $current_tab === 'manager-guide' ? 'nav-tab-active' : ''; ?>">
			Manager Guide
		</a>
		<a href="?page=leave-manager-help&tab=staff-guide" class="nav-tab <?php echo $current_tab === 'staff-guide' ? 'nav-tab-active' : ''; ?>">
			Staff Guide
		</a>
		<a href="?page=leave-manager-help&tab=implementation" class="nav-tab <?php echo $current_tab === 'implementation' ? 'nav-tab-active' : ''; ?>">
			Implementation
		</a>
		<a href="?page=leave-manager-help&tab=faq" class="nav-tab <?php echo $current_tab === 'faq' ? 'nav-tab-active' : ''; ?>">
			FAQ
		</a>
		<a href="?page=leave-manager-help&tab=troubleshooting" class="nav-tab <?php echo $current_tab === 'troubleshooting' ? 'nav-tab-active' : ''; ?>">
			Troubleshooting
		</a>
	</nav>

	<!-- Tab Content -->
	<div class="tab-content">

		<?php if ( $current_tab === 'getting-started' ) : ?>
			<!-- Getting Started Tab -->
			<div class="help-section">
				<h2>Quick Start Guide</h2>
				<p>Get up and running with Leave Manager in just 5 minutes!</p>

				<div class="help-steps">
					<div class="help-step">
						<h3>Step 1: Installation</h3>
						<p>The plugin has already been activated. You can see it in the admin menu as "Leave".</p>
					</div>

					<div class="help-step">
						<h3>Step 2: Configure Settings</h3>
						<ol>
							<li>Go to <strong>Leave → Settings</strong></li>
							<li>Enter your organization name</li>
							<li>Configure email settings (SMTP)</li>
							<li>Save settings</li>
						</ol>
					</div>

					<div class="help-step">
						<h3>Step 3: Create Leave Policies</h3>
						<ol>
							<li>Go to <strong>Leave → Staff → Leave Policies</strong></li>
							<li>Click "Add Policy"</li>
							<li>Enter policy name and leave days</li>
							<li>Save policy</li>
						</ol>
					</div>

					<div class="help-step">
						<h3>Step 4: Add Staff Members</h3>
						<ol>
							<li>Go to <strong>Leave → Staff → Users</strong></li>
							<li>Click "Add User"</li>
							<li>Enter staff information</li>
							<li>Assign leave policy</li>
							<li>Save</li>
						</ol>
					</div>

					<div class="help-step">
						<h3>Step 5: Share Signup Link</h3>
						<p>Share <code><?php echo esc_html( home_url( '/employee-signup/' ) ); ?></code> with new staff for self-registration.</p>
					</div>
				</div>

				<div class="help-info">
					<h3>Frontend Pages Created</h3>
					<p>The following pages are automatically created for staff:</p>
					<ul>
						<li><strong>Dashboard:</strong> <code><?php echo esc_html( home_url( '/leave-management/dashboard/' ) ); ?></code></li>
						<li><strong>Request Leave:</strong> <code><?php echo esc_html( home_url( '/leave-management/request/' ) ); ?></code></li>
						<li><strong>Leave Balance:</strong> <code><?php echo esc_html( home_url( '/leave-management/balance/' ) ); ?></code></li>
						<li><strong>Leave Calendar:</strong> <code><?php echo esc_html( home_url( '/leave-management/calendar/' ) ); ?></code></li>
						<li><strong>Leave History:</strong> <code><?php echo esc_html( home_url( '/leave-management/history/' ) ); ?></code></li>
						<li><strong>Employee Signup:</strong> <code><?php echo esc_html( home_url( '/employee-signup/' ) ); ?></code></li>
					</ul>
				</div>
			</div>

		<?php elseif ( $current_tab === 'admin-guide' ) : ?>
			<!-- Admin Guide Tab -->
			<div class="help-section">
				<h2>Administrator Guide</h2>

				<h3>Dashboard Overview</h3>
				<p>The admin dashboard shows:</p>
				<ul>
					<li>Total staff count</li>
					<li>Pending leave requests</li>
					<li>Approved leaves this period</li>
					<li>Recent leave requests</li>
				</ul>

				<h3>Staff Management</h3>
				<h4>Adding Users</h4>
				<ol>
					<li>Go to <strong>Leave → Staff → Users</strong></li>
					<li>Click "Add New User"</li>
					<li>Fill in required fields</li>
					<li>Select a leave policy</li>
					<li>Click "Add User"</li>
				</ol>

				<h4>Bulk Actions</h4>
				<p>Select multiple users and perform bulk actions:</p>
				<ul>
					<li><strong>Assign Policy:</strong> Assign same policy to multiple users</li>
					<li><strong>Change Role:</strong> Change role for multiple users</li>
					<li><strong>Deactivate:</strong> Deactivate multiple users</li>
				</ul>

				<h3>Leave Policies</h3>
				<p>Create and manage leave policies:</p>
				<ol>
					<li>Go to <strong>Leave → Staff → Leave Policies</strong></li>
					<li>Click "Add Policy"</li>
					<li>Enter policy details:
						<ul>
							<li>Policy Name</li>
							<li>Annual Leave Days</li>
							<li>Sick Leave Days</li>
							<li>Other Leave Days</li>
						</ul>
					</li>
					<li>Click "Add Policy"</li>
				</ol>

				<h3>Leave Requests</h3>
				<p>Review and manage all leave requests:</p>
				<ol>
					<li>Go to <strong>Leave → Requests</strong></li>
					<li>View all requests or filter by status</li>
					<li>Click on request to view details</li>
					<li>Click "Approve" or "Reject"</li>
					<li>Add optional notes</li>
					<li>Staff receives email notification</li>
				</ol>

				<h3>Settings</h3>
				<p>Configure plugin settings:</p>
				<ul>
					<li>Organization name and email</li>
					<li>SMTP server settings</li>
					<li>Email notification preferences</li>
					<li>Leave year start date</li>
					<li>Default leave policy</li>
				</ul>

				<h3>Reports</h3>
				<p>Generate various reports:</p>
				<ul>
					<li>Staff leave summary</li>
					<li>Department leave report</li>
					<li>Leave type breakdown</li>
					<li>Approval status report</li>
					<li>Leave balance report</li>
				</ul>
				<p>Export to CSV, Excel, or PDF format.</p>

				<h3>System</h3>
				<p>System management tools:</p>
				<ul>
					<li><strong>Health Check:</strong> Verify system status</li>
					<li><strong>System Logs:</strong> View activity logs</li>
					<li><strong>Export Data:</strong> Export all data</li>
				</ul>
			</div>

		<?php elseif ( $current_tab === 'manager-guide' ) : ?>
			<!-- Manager Guide Tab -->
			<div class="help-section">
				<h2>Manager Guide</h2>

				<h3>Manager Dashboard</h3>
				<p>Managers see a filtered view showing only their team's information:</p>
				<ul>
					<li>Team members list</li>
					<li>Team leave balance</li>
					<li>Pending requests from team</li>
					<li>Team leave calendar</li>
				</ul>

				<h3>Approving Leave Requests</h3>
				<ol>
					<li>Go to <strong>Leave → Requests</strong></li>
					<li>View pending requests from your team</li>
					<li>Click on request to view details</li>
					<li>Review staff member information</li>
					<li>Review requested dates and reason</li>
					<li>Click "Approve" or "Reject"</li>
					<li>Add optional notes</li>
					<li>Staff receives email notification</li>
				</ol>

				<h3>Viewing Team Leave</h3>
				<ol>
					<li>Go to <strong>Leave → Staff</strong></li>
					<li>View all team members</li>
					<li>Click on staff member name</li>
					<li>View their:
						<ul>
							<li>Current leave balance</li>
							<li>Leave history</li>
							<li>Pending requests</li>
							<li>Approved leaves</li>
						</ul>
					</li>
				</ol>

				<h3>Team Reports</h3>
				<p>Generate reports for your team:</p>
				<ol>
					<li>Go to <strong>Leave → Reports</strong></li>
					<li>Select report type</li>
					<li>Choose date range</li>
					<li>View or export report</li>
				</ol>

				<h3>Best Practices</h3>
				<ul>
					<li>Review requests within 24 hours</li>
					<li>Provide feedback on rejections</li>
					<li>Communicate decisions promptly</li>
					<li>Plan for team coverage</li>
					<li>Apply policies consistently</li>
				</ul>
			</div>

		<?php elseif ( $current_tab === 'staff-guide' ) : ?>
			<!-- Staff Guide Tab -->
			<div class="help-section">
				<h2>Staff Guide</h2>

				<h3>Getting Started</h3>
				<p>New staff members should:</p>
				<ol>
					<li>Visit <code><?php echo esc_html( home_url( '/employee-signup/' ) ); ?></code></li>
					<li>Complete 3-step registration:
						<ul>
							<li>Enter personal information</li>
							<li>Verify email address</li>
							<li>Create login credentials</li>
						</ul>
					</li>
					<li>Log in to access leave management portal</li>
				</ol>

				<h3>Staff Self-Service Portal</h3>
				<p>After logging in, staff can access:</p>

				<h4>Dashboard</h4>
				<p>View overview of leave information:</p>
				<ul>
					<li>Current leave balance</li>
					<li>Recent leave requests</li>
					<li>Quick links to other pages</li>
				</ul>

				<h4>Request Leave</h4>
				<p>Apply for leave in 4 steps:</p>
				<ol>
					<li>Click "Request Leave"</li>
					<li>Select leave type (Annual, Sick, Other)</li>
					<li>Pick start and end dates</li>
					<li>Add reason (optional)</li>
					<li>Click "Submit Request"</li>
				</ol>

				<h4>Leave Balance</h4>
				<p>View remaining leave days:</p>
				<ul>
					<li>Annual leave remaining</li>
					<li>Sick leave remaining</li>
					<li>Other leave remaining</li>
				</ul>

				<h4>Leave Calendar</h4>
				<p>Visual calendar showing:</p>
				<ul>
					<li>All approved leave dates</li>
					<li>Color-coded by leave type</li>
					<li>Monthly navigation</li>
				</ul>

				<h4>Leave History</h4>
				<p>View all leave requests:</p>
				<ul>
					<li>Past leave requests</li>
					<li>Current pending requests</li>
					<li>Approved and rejected requests</li>
					<li>Rejection reasons</li>
				</ul>

				<h3>Applying for Leave</h3>
				<p>When you need to take leave:</p>
				<ol>
					<li>Log in to your account</li>
					<li>Go to "Request Leave"</li>
					<li>Select the type of leave</li>
					<li>Choose your dates</li>
					<li>Add a reason (optional)</li>
					<li>Submit the request</li>
					<li>Your manager will review and approve/reject</li>
					<li>You'll receive an email notification</li>
				</ol>

				<h3>Checking Your Balance</h3>
				<p>Before requesting leave:</p>
				<ol>
					<li>Log in to your account</li>
					<li>Go to "Leave Balance"</li>
					<li>Check your remaining days</li>
					<li>Plan your leave accordingly</li>
				</ol>

				<h3>Best Practices</h3>
				<ul>
					<li>Plan leave in advance</li>
					<li>Check your balance before requesting</li>
					<li>Submit requests early</li>
					<li>Provide clear reasons for leave</li>
					<li>Keep your profile updated</li>
					<li>Check your email for notifications</li>
				</ul>

				<h3>Frontend Pages</h3>
				<p>Access these pages after logging in:</p>
				<ul>
					<li><strong>Dashboard:</strong> <code><?php echo esc_html( home_url( '/leave-management/dashboard/' ) ); ?></code></li>
					<li><strong>Request Leave:</strong> <code><?php echo esc_html( home_url( '/leave-management/request/' ) ); ?></code></li>
					<li><strong>Leave Balance:</strong> <code><?php echo esc_html( home_url( '/leave-management/balance/' ) ); ?></code></li>
					<li><strong>Leave Calendar:</strong> <code><?php echo esc_html( home_url( '/leave-management/calendar/' ) ); ?></code></li>
					<li><strong>Leave History:</strong> <code><?php echo esc_html( home_url( '/leave-management/history/' ) ); ?></code></li>
				</ul>
			</div>

		<?php elseif ( $current_tab === 'implementation' ) : ?>
			<!-- Implementation Tab -->
			<div class="help-section">
				<h2>Implementation Guide</h2>

				<h3>Step-by-Step Implementation</h3>

				<h4>Step 1: Configure Settings</h4>
				<ol>
					<li>Go to <strong>Leave → Settings</strong></li>
					<li>Enter organization name</li>
					<li>Configure SMTP email settings</li>
					<li>Enable email notifications</li>
					<li>Save settings</li>
				</ol>

				<h4>Step 2: Create Leave Policies</h4>
				<ol>
					<li>Go to <strong>Leave → Staff → Leave Policies</strong></li>
					<li>Create policies for different roles:
						<ul>
							<li>Standard Employee (21 annual, 10 sick, 3 other)</li>
							<li>Manager (25 annual, 10 sick, 5 other)</li>
							<li>Executive (30 annual, 12 sick, 5 other)</li>
						</ul>
					</li>
				</ol>

				<h4>Step 3: Add Staff Members</h4>
				<ol>
					<li>Go to <strong>Leave → Staff → Users</strong></li>
					<li>Add staff members manually or import from CSV</li>
					<li>Assign appropriate leave policies</li>
					<li>Set user roles (Employee, Manager, Admin)</li>
				</ol>

				<h4>Step 4: Configure Email Templates</h4>
				<ol>
					<li>Go to <strong>Leave → Settings → Email Templates</strong></li>
					<li>Customize email templates for:
						<ul>
							<li>Leave request submission</li>
							<li>Leave approval</li>
							<li>Leave rejection</li>
							<li>Manager notifications</li>
						</ul>
					</li>
				</ol>

				<h4>Step 5: Set Up User Roles</h4>
				<ol>
					<li>Go to <strong>Leave → Settings → Roles</strong></li>
					<li>Configure permissions for:
						<ul>
							<li>Admin - Full access</li>
							<li>Manager - Team management</li>
							<li>Employee - Self-service only</li>
						</ul>
					</li>
				</ol>

				<h4>Step 6: Enable Frontend Pages</h4>
				<p>Frontend pages are created automatically:</p>
				<ul>
					<li>Dashboard - Staff overview</li>
					<li>Request Leave - Leave application form</li>
					<li>Leave Balance - Balance view</li>
					<li>Leave Calendar - Visual calendar</li>
					<li>Leave History - Request history</li>
					<li>Employee Signup - Registration page</li>
				</ul>

				<h4>Step 7: Test the System</h4>
				<ol>
					<li>Create test staff account</li>
					<li>Assign leave policy</li>
					<li>Submit test leave request</li>
					<li>Approve request as manager</li>
					<li>Verify email notifications</li>
					<li>Check leave balance updated</li>
				</ol>

				<h4>Step 8: Train Users</h4>
				<ol>
					<li>Train administrators on system management</li>
					<li>Train managers on approval process</li>
					<li>Train staff on leave application</li>
					<li>Share documentation and guides</li>
				</ol>

				<h4>Step 9: Go Live</h4>
				<ol>
					<li>Add all staff members</li>
					<li>Assign appropriate policies</li>
					<li>Send staff signup links</li>
					<li>Monitor system for issues</li>
					<li>Adjust settings as needed</li>
				</ol>

				<h3>Database Tables</h3>
				<p>The plugin creates the following tables:</p>
				<ul>
					<li><code>wp_leave_manager_leave_users</code> - Staff information</li>
					<li><code>wp_leave_manager_leave_requests</code> - Leave requests</li>
					<li><code>wp_leave_manager_leave_policies</code> - Leave policies</li>
					<li><code>wp_leave_manager_employee_signups</code> - Signup records</li>
				</ul>

				<h3>Frontend Pages</h3>
				<p>The following pages are created automatically:</p>
				<ul>
					<li><code>/leave-management/</code> - Main page</li>
					<li><code>/leave-management/dashboard/</code> - Staff dashboard</li>
					<li><code>/leave-management/request/</code> - Leave request form</li>
					<li><code>/leave-management/balance/</code> - Leave balance</li>
					<li><code>/leave-management/calendar/</code> - Leave calendar</li>
					<li><code>/leave-management/history/</code> - Leave history</li>
					<li><code>/employee-signup/</code> - Staff registration</li>
				</ul>
			</div>

		<?php elseif ( $current_tab === 'faq' ) : ?>
			<!-- FAQ Tab -->
			<div class="help-section">
				<h2>Frequently Asked Questions</h2>

				<div class="faq-item">
					<h4>Q: How do I install the plugin?</h4>
					<p>A: The plugin is already installed and activated. You can access it from the admin menu as "Leave".</p>
				</div>

				<div class="faq-item">
					<h4>Q: Can I customize leave policies?</h4>
					<p>A: Yes! Go to Leave → Staff → Leave Policies to create custom policies with different leave day allocations.</p>
				</div>

				<div class="faq-item">
					<h4>Q: How do staff apply for leave?</h4>
					<p>A: Staff log in and go to <?php echo esc_html( home_url( '/leave-management/request/' ) ); ?> to submit leave requests.</p>
				</div>

				<div class="faq-item">
					<h4>Q: How are leave balances calculated?</h4>
					<p>A: Balances are set when a policy is assigned to a staff member. When a request is approved, the balance is automatically deducted.</p>
				</div>

				<div class="faq-item">
					<h4>Q: Can I bulk assign policies?</h4>
					<p>A: Yes! Select multiple users, choose "Assign Policy" from bulk actions, select the policy, and click "Apply".</p>
				</div>

				<div class="faq-item">
					<h4>Q: How do I generate reports?</h4>
					<p>A: Go to Leave → Reports to generate various reports and export to CSV, Excel, or PDF.</p>
				</div>

				<div class="faq-item">
					<h4>Q: Can staff cancel leave requests?</h4>
					<p>A: Staff can cancel pending requests. Contact admin to cancel approved requests.</p>
				</div>

				<div class="faq-item">
					<h4>Q: How do I export data?</h4>
					<p>A: Go to Leave → System → Export Data to export staff, requests, policies, or settings.</p>
				</div>

				<div class="faq-item">
					<h4>Q: What if emails are not being sent?</h4>
					<p>A: Check SMTP settings in Leave → Settings. Verify email address and test SMTP connection.</p>
				</div>

				<div class="faq-item">
					<h4>Q: How do I reset a staff member's password?</h4>
					<p>A: Use WordPress user management to reset passwords. Go to Users in WordPress admin.</p>
				</div>
			</div>

		<?php elseif ( $current_tab === 'troubleshooting' ) : ?>
			<!-- Troubleshooting Tab -->
			<div class="help-section">
				<h2>Troubleshooting</h2>

				<div class="troubleshooting-item">
					<h4>Issue: Pages not showing content</h4>
					<p><strong>Solution:</strong></p>
					<ol>
						<li>Verify pages exist in WordPress Pages admin</li>
						<li>Check page slugs are correct</li>
						<li>Verify shortcodes are spelled correctly</li>
						<li>Clear WordPress cache</li>
						<li>Reactivate plugin to recreate pages</li>
					</ol>
				</div>

				<div class="troubleshooting-item">
					<h4>Issue: Emails not being sent</h4>
					<p><strong>Solution:</strong></p>
					<ol>
						<li>Check SMTP settings in Leave → Settings</li>
						<li>Verify email address is correct</li>
						<li>Test SMTP connection</li>
						<li>Check WordPress mail configuration</li>
						<li>Review error logs</li>
					</ol>
				</div>

				<div class="troubleshooting-item">
					<h4>Issue: Signup page not working</h4>
					<p><strong>Solution:</strong></p>
					<ol>
						<li>Verify /employee-signup/ page exists</li>
						<li>Check email configuration</li>
						<li>Test SMTP settings</li>
						<li>Verify email is not in spam</li>
						<li>Check error logs</li>
					</ol>
				</div>

				<div class="troubleshooting-item">
					<h4>Issue: Managers cannot approve requests</h4>
					<p><strong>Solution:</strong></p>
					<ol>
						<li>Verify manager role is assigned</li>
						<li>Check permissions for manager role</li>
						<li>Verify requests are from their team</li>
						<li>Check database permissions</li>
						<li>Reactivate plugin</li>
					</ol>
				</div>

				<div class="troubleshooting-item">
					<h4>Issue: Slow page loading</h4>
					<p><strong>Solution:</strong></p>
					<ol>
						<li>Enable WordPress caching plugin</li>
						<li>Optimize database</li>
						<li>Check for large datasets</li>
						<li>Enable pagination</li>
						<li>Use CDN for static assets</li>
					</ol>
				</div>

				<div class="troubleshooting-item">
					<h4>Issue: Database tables not created</h4>
					<p><strong>Solution:</strong></p>
					<ol>
						<li>Check database permissions</li>
						<li>Verify MySQL version (must be 5.7+)</li>
						<li>Reactivate plugin</li>
						<li>Run database migration manually</li>
						<li>Check error logs</li>
					</ol>
				</div>

				<div class="troubleshooting-item">
					<h4>Issue: Staff can see other staff's data</h4>
					<p><strong>Solution:</strong></p>
					<ol>
						<li>Check user roles are correct</li>
						<li>Verify permissions are set properly</li>
						<li>Check database for permission issues</li>
						<li>Review security settings</li>
						<li>Contact support if issue persists</li>
					</ol>
				</div>

				<h3>Error Logs</h3>
				<p>Check error logs for detailed information:</p>
				<p><code><?php echo esc_html( WP_CONTENT_DIR . '/debug.log' ); ?></code></p>

				<h3>System Health Check</h3>
				<p>Run a system health check:</p>
				<ol>
					<li>Go to <strong>Leave → System → Health Check</strong></li>
					<li>Review system status</li>
					<li>Fix any issues reported</li>
				</ol>
			</div>

		<?php endif; ?>

	</div>

</div>

<style>
	.leave-manager-help {
		background: #fff;
		padding: 20px;
	}

	.help-section {
		background: #fff;
		padding: 20px;
		margin-top: 20px;
		border: 1px solid #ddd;
		border-radius: 5px;
	}

	.help-section h2 {
		color: #333;
		border-bottom: 2px solid #667eea;
		padding-bottom: 10px;
		margin-bottom: 20px;
	}

	.help-section h3 {
		color: #555;
		margin-top: 25px;
		margin-bottom: 15px;
	}

	.help-section h4 {
		color: #666;
		margin-top: 15px;
		margin-bottom: 10px;
	}

	.help-section p {
		line-height: 1.6;
		color: #555;
		margin-bottom: 15px;
	}

	.help-section ul,
	.help-section ol {
		margin-left: 20px;
		margin-bottom: 15px;
	}

	.help-section li {
		margin-bottom: 8px;
		line-height: 1.6;
	}

	.help-section code {
		background: #f5f5f5;
		padding: 2px 6px;
		border-radius: 3px;
		font-family: monospace;
		color: #d63384;
	}

	.help-steps {
		margin: 20px 0;
	}

	.help-step {
		background: #f9f9f9;
		padding: 15px;
		margin-bottom: 15px;
		border-left: 4px solid #667eea;
		border-radius: 3px;
	}

	.help-step h3 {
		margin-top: 0;
		color: #667eea;
	}

	.help-info {
		background: #e7f3ff;
		padding: 15px;
		border-left: 4px solid #2196F3;
		border-radius: 3px;
		margin-top: 20px;
	}

	.help-info h3 {
		margin-top: 0;
		color: #1976D2;
	}

	.faq-item {
		background: #f9f9f9;
		padding: 15px;
		margin-bottom: 15px;
		border-radius: 3px;
		border-left: 4px solid #667eea;
	}

	.faq-item h4 {
		color: #667eea;
		margin-top: 0;
	}

	.troubleshooting-item {
		background: #fff3cd;
		padding: 15px;
		margin-bottom: 15px;
		border-radius: 3px;
		border-left: 4px solid #4A5FFF;
	}

	.troubleshooting-item h4 {
		color: #856404;
		margin-top: 0;
	}

	.troubleshooting-item ol {
		background: #fff;
		padding: 10px 20px;
		border-radius: 3px;
		margin: 10px 0;
	}

	@media (max-width: 768px) {
		.help-section {
			padding: 15px;
		}

		.help-section h2 {
			font-size: 18px;
		}

		.help-section h3 {
			font-size: 16px;
		}
	}
</style>
