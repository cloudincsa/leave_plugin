<?php
/**
 * Help & Documentation Page
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include admin page template styles
include 'admin-page-template.php';
?>

<div class="leave-manager-admin-container">
<div class="lm-page-content">
<!-- Page Header -->
<div class="page-header">
	<div>
		<h1><?php esc_html_e( 'Help & Documentation', 'leave-manager' ); ?></h1>
		<p class="subtitle"><?php esc_html_e( 'Get started with the Leave Management System', 'leave-manager' ); ?></p>
	</div>
</div>

<div class="admin-tabs">
	<button class="admin-tab active" data-tab="getting-started"><?php esc_html_e( 'Getting Started', 'leave-manager' ); ?></button>
	<button class="admin-tab" data-tab="admin-guide"><?php esc_html_e( 'Admin Guide', 'leave-manager' ); ?></button>
	<button class="admin-tab" data-tab="manager-guide"><?php esc_html_e( 'Manager Guide', 'leave-manager' ); ?></button>
	<button class="admin-tab" data-tab="employee-guide"><?php esc_html_e( 'Employee Guide', 'leave-manager' ); ?></button>
	<button class="admin-tab" data-tab="faq"><?php esc_html_e( 'FAQ', 'leave-manager' ); ?></button>
	<button class="admin-tab" data-tab="troubleshooting"><?php esc_html_e( 'Troubleshooting', 'leave-manager' ); ?></button>
</div>

<div class="content-wrapper">
	<div class="content-main">
		<!-- Getting Started Tab -->
		<div class="lm-tab-content active" id="getting-started">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Quick Start Guide', 'leave-manager' ); ?></h2>
				<p><?php esc_html_e( 'Get up and running with Leave Manager in just 5 minutes!', 'leave-manager' ); ?></p>

				<div class="lm-guide-step">
					<h3><?php esc_html_e( 'STEP 1: INSTALLATION', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'The plugin has already been activated. You can see it in the admin menu as "Leave".', 'leave-manager' ); ?></p>
				</div>

				<div class="lm-guide-step">
					<h3><?php esc_html_e( 'STEP 2: CONFIGURE SETTINGS', 'leave-manager' ); ?></h3>
					<ol>
						<li><?php esc_html_e( 'Go to Leave → Settings', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Enter your organization name', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Configure email settings (SMTP)', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Set default leave policies', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Save settings', 'leave-manager' ); ?></li>
					</ol>
				</div>

				<div class="lm-guide-step">
					<h3><?php esc_html_e( 'STEP 3: CREATE LEAVE POLICIES', 'leave-manager' ); ?></h3>
					<ol>
						<li><?php esc_html_e( 'Go to Leave → Staff → Leave Policies', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Click "Add Policy"', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Configure policy details (name, days, etc.)', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Save policy', 'leave-manager' ); ?></li>
					</ol>
				</div>

				<div class="lm-guide-step">
					<h3><?php esc_html_e( 'STEP 4: ADD EMPLOYEES', 'leave-manager' ); ?></h3>
					<ol>
						<li><?php esc_html_e( 'Go to Leave → Staff → Users', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Click "Add New User"', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Fill in employee details', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Assign leave policies', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Save user', 'leave-manager' ); ?></li>
					</ol>
				</div>

				<div class="lm-guide-step">
					<h3><?php esc_html_e( 'STEP 5: ENABLE FRONTEND', 'leave-manager' ); ?></h3>
					<ol>
						<li><?php esc_html_e( 'Create a new page in WordPress', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Add shortcode: [leave_manager]', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Publish the page', 'leave-manager' ); ?></li>
						<li><?php esc_html_e( 'Employees can now submit leave requests', 'leave-manager' ); ?></li>
					</ol>
				</div>
			</div>
		</div>

		<!-- Admin Guide Tab -->
		<div class="lm-tab-content" id="admin-guide">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Administrator Guide', 'leave-manager' ); ?></h2>
				<p><?php esc_html_e( 'Complete guide for system administrators', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'Dashboard', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'The dashboard provides an overview of your leave management system including total users, pending requests, approved leaves, and days used.', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'Managing Users', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'Add, edit, and remove employees from the Staff section. Assign leave policies and set department information.', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'Leave Policies', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'Create and manage leave types (Annual Leave, Sick Leave, etc.) with configurable days and rules.', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'Approving Requests', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'Review and approve/reject leave requests from the Requests section. Add comments for rejected requests.', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'System Settings', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'Configure organization details, email settings, notification preferences, and advanced options.', 'leave-manager' ); ?></p>
			</div>
		</div>

		<!-- Manager Guide Tab -->
		<div class="lm-tab-content" id="manager-guide">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Manager Guide', 'leave-manager' ); ?></h2>
				<p><?php esc_html_e( 'Guide for department managers and approvers', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'Approving Leave Requests', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'Managers can view and approve/reject leave requests from their team members. Access the Requests section to manage pending approvals.', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'Viewing Team Schedule', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'Use the Calendar view to see all team members\' leave schedules and plan accordingly.', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'Generating Reports', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'Access the Reports section to generate leave analytics, department reports, and user statistics.', 'leave-manager' ); ?></p>
			</div>
		</div>

		<!-- Employee Guide Tab -->
		<div class="lm-tab-content" id="employee-guide">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Employee Guide', 'leave-manager' ); ?></h2>
				<p><?php esc_html_e( 'Guide for employees using the Leave Management System', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'Submitting Leave Requests', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'Navigate to the Leave Management page and click "Request Leave". Select the leave type, dates, and add any notes. Submit for approval.', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'Checking Leave Balance', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'View your available leave balance on the dashboard. The system shows remaining days for each leave type.', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'Viewing Request History', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'Check the status of your submitted requests (Pending, Approved, Rejected) on the dashboard.', 'leave-manager' ); ?></p>

				<h3><?php esc_html_e( 'Team Calendar', 'leave-manager' ); ?></h3>
				<p><?php esc_html_e( 'View the team calendar to see when your colleagues are on leave.', 'leave-manager' ); ?></p>
			</div>
		</div>

		<!-- FAQ Tab -->
		<div class="lm-tab-content" id="faq">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Frequently Asked Questions', 'leave-manager' ); ?></h2>

				<div class="lm-faq-item">
					<h3><?php esc_html_e( 'Q: How do I reset my password?', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'A: Use the password reset link on the login page or contact your administrator.', 'leave-manager' ); ?></p>
				</div>

				<div class="lm-faq-item">
					<h3><?php esc_html_e( 'Q: Can I cancel a submitted request?', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'A: You can cancel pending requests. Contact your manager for approved requests.', 'leave-manager' ); ?></p>
				</div>

				<div class="lm-faq-item">
					<h3><?php esc_html_e( 'Q: How long does approval take?', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'A: Approval time depends on your manager\'s availability. You\'ll receive an email notification when approved/rejected.', 'leave-manager' ); ?></p>
				</div>

				<div class="lm-faq-item">
					<h3><?php esc_html_e( 'Q: What leave types are available?', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'A: Available leave types are configured by your administrator. Check the Request Leave page for options.', 'leave-manager' ); ?></p>
				</div>

				<div class="lm-faq-item">
					<h3><?php esc_html_e( 'Q: Can I request leave for multiple days?', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'A: Yes, you can select a date range when submitting a request.', 'leave-manager' ); ?></p>
				</div>
			</div>
		</div>

		<!-- Troubleshooting Tab -->
		<div class="lm-tab-content" id="troubleshooting">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Troubleshooting', 'leave-manager' ); ?></h2>

				<div class="lm-troubleshoot-item">
					<h3><?php esc_html_e( 'Problem: Not receiving email notifications', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'Solution: Check email settings in Leave → Settings → Email tab. Verify SMTP configuration.', 'leave-manager' ); ?></p>
				</div>

				<div class="lm-troubleshoot-item">
					<h3><?php esc_html_e( 'Problem: Cannot submit leave request', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'Solution: Ensure you have available leave balance. Contact your administrator if the issue persists.', 'leave-manager' ); ?></p>
				</div>

				<div class="lm-troubleshoot-item">
					<h3><?php esc_html_e( 'Problem: Leave balance not updating', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'Solution: Check System → Health Checks. Verify database connection and plugin tables.', 'leave-manager' ); ?></p>
				</div>

				<div class="lm-troubleshoot-item">
					<h3><?php esc_html_e( 'Problem: Page not loading', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'Solution: Clear browser cache and try again. Check System → Logs for errors.', 'leave-manager' ); ?></p>
				</div>

				<div class="lm-troubleshoot-item">
					<h3><?php esc_html_e( 'Problem: Permission denied errors', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'Solution: Verify your user role has appropriate permissions. Contact administrator for access.', 'leave-manager' ); ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="content-sidebar">
		<div class="lm-card">
			<h3><?php esc_html_e( 'Need Help?', 'leave-manager' ); ?></h3>
			<p><?php esc_html_e( 'Contact your administrator or check the documentation above.', 'leave-manager' ); ?></p>
		</div>

		<div class="lm-card">
			<h3><?php esc_html_e( 'System Status', 'leave-manager' ); ?></h3>
			<p><?php esc_html_e( 'Check system health in the System section.', 'leave-manager' ); ?></p>
		</div>

		<div class="lm-card">
			<h3><?php esc_html_e( 'Quick Links', 'leave-manager' ); ?></h3>
			<ul style="list-style: none; padding: 0;">
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-management' ) ); ?>"><?php esc_html_e( 'Dashboard', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-requests' ) ); ?>"><?php esc_html_e( 'Requests', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-staff' ) ); ?>"><?php esc_html_e( 'Staff', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'leave-manager' ); ?></a></li>
			</ul>
		</div>
	</div>
</div>

<script>
document.querySelectorAll('.admin-tab').forEach(button => {
	button.addEventListener('click', function() {
		const tabId = this.getAttribute('data-tab');
		
		// Hide all tabs
		document.querySelectorAll('.lm-tab-content').forEach(tab => {
			tab.classList.remove('active');
		});
		
		// Remove active class from all buttons
		document.querySelectorAll('.admin-tab').forEach(btn => {
			btn.classList.remove('active');
		});
		
		// Show selected tab and mark button as active
		document.getElementById(tabId).classList.add('active');
		this.classList.add('active');
	});
});
</script>

</div>
</div>
