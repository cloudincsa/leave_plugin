<?php
/**
 * Professional Email Templates Page - ChatPanel Design
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
		<h1><?php esc_html_e( 'Email Templates', 'leave-manager' ); ?></h1>
		<p class="subtitle"><?php esc_html_e( 'Manage email templates for system notifications', 'leave-manager' ); ?></p>
	</div>
</div>

<div class="content-wrapper">
	<div class="content-main">
		<div class="lm-templates-grid">
			<!-- Welcome Email Template -->
			<div class="lm-template-card">
				<div class="lm-template-header">
					<h3><?php esc_html_e( 'Welcome Email', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'New user account created', 'leave-manager' ); ?></p>
				</div>
				<div class="lm-template-body">
					<div class="lm-template-description">
						<?php esc_html_e( 'Sent when a new employee account is created in the system.', 'leave-manager' ); ?>
					</div>
					<div class="lm-template-variables">
						<h4><?php esc_html_e( 'Available Variables', 'leave-manager' ); ?></h4>
						<code>{user_name}</code>
						<code>{email}</code>
						<code>{password}</code>
						<code>{login_url}</code>
					</div>
					<div class="lm-template-actions">
						<button class="lm-btn-preview" onclick="previewTemplate('welcome')"><?php esc_html_e( 'Preview', 'leave-manager' ); ?></button>
						<button class="lm-btn-edit" onclick="editTemplate('welcome')"><?php esc_html_e( 'Edit', 'leave-manager' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Leave Request Template -->
			<div class="lm-template-card">
				<div class="lm-template-header">
					<h3><?php esc_html_e( 'Leave Request', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'New leave request submitted', 'leave-manager' ); ?></p>
				</div>
				<div class="lm-template-body">
					<div class="lm-template-description">
						<?php esc_html_e( 'Sent to managers when an employee submits a leave request.', 'leave-manager' ); ?>
					</div>
					<div class="lm-template-variables">
						<h4><?php esc_html_e( 'Available Variables', 'leave-manager' ); ?></h4>
						<code>{employee_name}</code>
						<code>{leave_type}</code>
						<code>{start_date}</code>
						<code>{end_date}</code>
						<code>{reason}</code>
					</div>
					<div class="lm-template-actions">
						<button class="lm-btn-preview" onclick="previewTemplate('request')"><?php esc_html_e( 'Preview', 'leave-manager' ); ?></button>
						<button class="lm-btn-edit" onclick="editTemplate('request')"><?php esc_html_e( 'Edit', 'leave-manager' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Approval Template -->
			<div class="lm-template-card">
				<div class="lm-template-header">
					<h3><?php esc_html_e( 'Approval Email', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'Request approved', 'leave-manager' ); ?></p>
				</div>
				<div class="lm-template-body">
					<div class="lm-template-description">
						<?php esc_html_e( 'Sent to employee when their leave request is approved.', 'leave-manager' ); ?>
					</div>
					<div class="lm-template-variables">
						<h4><?php esc_html_e( 'Available Variables', 'leave-manager' ); ?></h4>
						<code>{employee_name}</code>
						<code>{leave_type}</code>
						<code>{start_date}</code>
						<code>{end_date}</code>
						<code>{days}</code>
					</div>
					<div class="lm-template-actions">
						<button class="lm-btn-preview" onclick="previewTemplate('approval')"><?php esc_html_e( 'Preview', 'leave-manager' ); ?></button>
						<button class="lm-btn-edit" onclick="editTemplate('approval')"><?php esc_html_e( 'Edit', 'leave-manager' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Rejection Template -->
			<div class="lm-template-card">
				<div class="lm-template-header">
					<h3><?php esc_html_e( 'Rejection Email', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'Request rejected', 'leave-manager' ); ?></p>
				</div>
				<div class="lm-template-body">
					<div class="lm-template-description">
						<?php esc_html_e( 'Sent to employee when their leave request is rejected.', 'leave-manager' ); ?>
					</div>
					<div class="lm-template-variables">
						<h4><?php esc_html_e( 'Available Variables', 'leave-manager' ); ?></h4>
						<code>{employee_name}</code>
						<code>{leave_type}</code>
						<code>{reason}</code>
						<code>{manager_name}</code>
					</div>
					<div class="lm-template-actions">
						<button class="lm-btn-preview" onclick="previewTemplate('rejection')"><?php esc_html_e( 'Preview', 'leave-manager' ); ?></button>
						<button class="lm-btn-edit" onclick="editTemplate('rejection')"><?php esc_html_e( 'Edit', 'leave-manager' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Password Reset Template -->
			<div class="lm-template-card">
				<div class="lm-template-header">
					<h3><?php esc_html_e( 'Password Reset', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'Password reset request', 'leave-manager' ); ?></p>
				</div>
				<div class="lm-template-body">
					<div class="lm-template-description">
						<?php esc_html_e( 'Sent when an employee requests to reset their password.', 'leave-manager' ); ?>
					</div>
					<div class="lm-template-variables">
						<h4><?php esc_html_e( 'Available Variables', 'leave-manager' ); ?></h4>
						<code>{user_name}</code>
						<code>{reset_link}</code>
						<code>{expiry_time}</code>
					</div>
					<div class="lm-template-actions">
						<button class="lm-btn-preview" onclick="previewTemplate('password-reset')"><?php esc_html_e( 'Preview', 'leave-manager' ); ?></button>
						<button class="lm-btn-edit" onclick="editTemplate('password-reset')"><?php esc_html_e( 'Edit', 'leave-manager' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Account Created Template -->
			<div class="lm-template-card">
				<div class="lm-template-header">
					<h3><?php esc_html_e( 'Account Created', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'New account notification', 'leave-manager' ); ?></p>
				</div>
				<div class="lm-template-body">
					<div class="lm-template-description">
						<?php esc_html_e( 'Sent to admin when a new account is created.', 'leave-manager' ); ?>
					</div>
					<div class="lm-template-variables">
						<h4><?php esc_html_e( 'Available Variables', 'leave-manager' ); ?></h4>
						<code>{user_name}</code>
						<code>{email}</code>
						<code>{role}</code>
						<code>{created_date}</code>
					</div>
					<div class="lm-template-actions">
						<button class="lm-btn-preview" onclick="previewTemplate('account-created')"><?php esc_html_e( 'Preview', 'leave-manager' ); ?></button>
						<button class="lm-btn-edit" onclick="editTemplate('account-created')"><?php esc_html_e( 'Edit', 'leave-manager' ); ?></button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Sidebar -->
	<div class="content-sidebar">
		<div class="lm-card">
			<h3><?php esc_html_e( 'Template Help', 'leave-manager' ); ?></h3>
			<p><?php esc_html_e( 'Customize email templates to match your organization branding and communication style.', 'leave-manager' ); ?></p>
		</div>

		<div class="lm-card">
			<h3><?php esc_html_e( 'Variables', 'leave-manager' ); ?></h3>
			<p><?php esc_html_e( 'Use variables in curly braces to insert dynamic content into templates.', 'leave-manager' ); ?></p>
		</div>

		<div class="lm-card">
			<h3><?php esc_html_e( 'Quick Links', 'leave-manager' ); ?></h3>
			<ul style="list-style: none; padding: 0;">
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-management' ) ); ?>"><?php esc_html_e( 'Dashboard', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-requests' ) ); ?>"><?php esc_html_e( 'Requests', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-help' ) ); ?>"><?php esc_html_e( 'Help', 'leave-manager' ); ?></a></li>
			</ul>
		</div>
	</div>
</div>

<script>
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
var leaveManagerNonce = '<?php echo wp_create_nonce('leave_manager_admin_nonce'); ?>';

function escapeHtml(text) {
	var div = document.createElement('div');
	div.appendChild(document.createTextNode(text));
	return div.innerHTML;
}

// Preview Template
function previewTemplate(template) {
	const formData = new FormData();
	formData.append('action', 'leave_manager_preview_template');
	formData.append('nonce', leaveManagerNonce);
	formData.append('template', template);

	fetch(ajaxurl, {
		method: 'POST',
		body: formData
	})
	.then(response => response.json())
	.then(data => {
		if (data.success) {
			showTemplatePreview(data.data.html, template);
		} else {
			alert('Error: ' + data.data.message);
		}
	})
	.catch(error => {
		console.error('Error:', error);
		alert('Failed to preview template');
	});
}

// Edit Template
function editTemplate(template) {
	const formData = new FormData();
	formData.append('action', 'leave_manager_edit_template');
	formData.append('nonce', leaveManagerNonce);
	formData.append('template', template);

	fetch(ajaxurl, {
		method: 'POST',
		body: formData
	})
	.then(response => response.json())
	.then(data => {
		if (data.success) {
			showTemplateEditor(data.data.content, template);
		} else {
			alert('Error: ' + data.data.message);
		}
	})
	.catch(error => {
		console.error('Error:', error);
		alert('Failed to edit template');
	});
}

// Show Template Preview Modal
function showTemplatePreview(html, template) {
	const modal = document.createElement('div');
	modal.className = 'lm-modal';
	modal.innerHTML = `
		<div class="lm-modal-content">
			<div class="lm-modal-header">
				<h2>Template Preview: ${template}</h2>
				<button class="lm-modal-close" onclick="this.closest('.lm-modal').remove()">×</button>
			</div>
			<div class="lm-modal-body">
				${html}
			</div>
			<div class="lm-modal-footer">
				<button class="lm-btn-secondary" onclick="this.closest('.lm-modal').remove()">Close</button>
			</div>
		</div>
	`;
	document.body.appendChild(modal);
}

// Show Template Editor Modal
function showTemplateEditor(content, template) {
	const modal = document.createElement('div');
	modal.className = 'lm-modal';
	modal.innerHTML = `
		<div class="lm-modal-content lm-modal-large">
			<div class="lm-modal-header">
				<h2>Edit Template: ${template}</h2>
				<button class="lm-modal-close" onclick="this.closest('.lm-modal').remove()">×</button>
			</div>
			<div class="lm-modal-body">
				<textarea id="template-content" class="lm-textarea" style="width: 100%; height: 400px;">${escapeHtml(content)}</textarea>
			</div>
			<div class="lm-modal-footer">
				<button class="lm-btn-primary" onclick="saveTemplateContent('${template}')">Save</button>
				<button class="lm-btn-secondary" onclick="this.closest('.lm-modal').remove()">Cancel</button>
			</div>
		</div>
	`;
	document.body.appendChild(modal);
}

// Save Template Content
function saveTemplateContent(template) {
	const content = document.getElementById('template-content').value;

	if (!content.trim()) {
		alert('Template content cannot be empty');
		return;
	}

	const formData = new FormData();
	formData.append('action', 'leave_manager_save_template');
	formData.append('nonce', leaveManagerNonce);
	formData.append('template', template);
	formData.append('content', content);

	fetch(ajaxurl, {
		method: 'POST',
		body: formData
	})
	.then(response => response.json())
	.then(data => {
		if (data.success) {
			alert('Template saved successfully!');
			document.querySelector('.lm-modal').remove();
		} else {
			alert('Error: ' + data.data.message);
		}
	})
	.catch(error => {
		console.error('Error:', error);
		alert('Failed to save template');
	});
}
</script>

</div>
</div>
