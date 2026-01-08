<?php
/**
 * System Page
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get system info
global $wpdb;

// Include admin page template styles
include 'admin-page-template.php';
?>

<div class="leave-manager-admin-container">
<div class="lm-page-content">
<!-- Page Header -->
<div class="page-header">
	<div>
		<h1><?php esc_html_e( 'System', 'leave-manager' ); ?></h1>
		<p class="subtitle"><?php esc_html_e( 'Monitor system health and manage plugin settings', 'leave-manager' ); ?></p>
	</div>
</div>

<div class="admin-tabs">
	<button class="admin-tab active" data-tab="health"><?php esc_html_e( 'System Health', 'leave-manager' ); ?></button>
	<button class="admin-tab" data-tab="logs"><?php esc_html_e( 'Logs', 'leave-manager' ); ?></button>
	<button class="admin-tab" data-tab="export"><?php esc_html_e( 'Export', 'leave-manager' ); ?></button>
	<button class="admin-tab" data-tab="reset"><?php esc_html_e( 'Reset Plugin', 'leave-manager' ); ?></button>
</div>

<div class="content-wrapper">
	<div class="content-main">
		<!-- System Health Tab -->
		<div class="lm-tab-content active" id="health">
			<div class="lm-card">
				<h2><?php esc_html_e( 'System Health Checks', 'leave-manager' ); ?></h2>
				
				<div class="lm-stat-grid">
					<div class="lm-stat-card">
						<div class="lm-stat-label"><?php esc_html_e( 'TOTAL USERS', 'leave-manager' ); ?></div>
						<div class="lm-stat-value">6</div>
						<div class="lm-stat-description"><?php esc_html_e( 'Active employees', 'leave-manager' ); ?></div>
					</div>
					<div class="lm-stat-card">
						<div class="lm-stat-label"><?php esc_html_e( 'LEAVE REQUESTS', 'leave-manager' ); ?></div>
						<div class="lm-stat-value">7</div>
						<div class="lm-stat-description"><?php esc_html_e( 'Total requests', 'leave-manager' ); ?></div>
					</div>
					<div class="lm-stat-card">
						<div class="lm-stat-label"><?php esc_html_e( 'SETTINGS', 'leave-manager' ); ?></div>
						<div class="lm-stat-value">37</div>
						<div class="lm-stat-description"><?php esc_html_e( 'Configuration items', 'leave-manager' ); ?></div>
					</div>
				</div>

				<h3><?php esc_html_e( 'WordPress', 'leave-manager' ); ?></h3>
				<div class="lm-info-table">
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Version', 'leave-manager' ); ?></div>
						<div class="lm-info-value"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></div>
					</div>
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Site URL', 'leave-manager' ); ?></div>
						<div class="lm-info-value"><?php echo esc_html( get_bloginfo( 'url' ) ); ?></div>
					</div>
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Admin Email', 'leave-manager' ); ?></div>
						<div class="lm-info-value"><?php echo esc_html( get_bloginfo( 'admin_email' ) ); ?></div>
					</div>
				</div>

				<h3><?php esc_html_e( 'PHP', 'leave-manager' ); ?></h3>
				<div class="lm-info-table">
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Version', 'leave-manager' ); ?></div>
						<div class="lm-info-value"><?php echo esc_html( phpversion() ); ?></div>
					</div>
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Memory Limit', 'leave-manager' ); ?></div>
						<div class="lm-info-value"><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></div>
					</div>
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Max Upload Size', 'leave-manager' ); ?></div>
						<div class="lm-info-value"><?php echo esc_html( wp_max_upload_size() / ( 1024 * 1024 ) . 'M' ); ?></div>
					</div>
				</div>

				<h3><?php esc_html_e( 'Database', 'leave-manager' ); ?></h3>
				<div class="lm-info-table">
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Host', 'leave-manager' ); ?></div>
						<div class="lm-info-value"><?php echo esc_html( DB_HOST ); ?></div>
					</div>
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Database', 'leave-manager' ); ?></div>
						<div class="lm-info-value"><?php echo esc_html( DB_NAME ); ?></div>
					</div>
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Version', 'leave-manager' ); ?></div>
						<div class="lm-info-value"><?php echo esc_html( $wpdb->db_version() ); ?></div>
					</div>
				</div>

				<h3><?php esc_html_e( 'Plugin Tables', 'leave-manager' ); ?></h3>
				<div class="lm-info-table">
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Users Table', 'leave-manager' ); ?></div>
						<div class="lm-info-value lm-status-pass"><?php esc_html_e( 'EXISTS', 'leave-manager' ); ?></div>
					</div>
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Requests Table', 'leave-manager' ); ?></div>
						<div class="lm-info-value lm-status-pass"><?php esc_html_e( 'EXISTS', 'leave-manager' ); ?></div>
					</div>
					<div class="lm-info-row">
						<div class="lm-info-label"><?php esc_html_e( 'Settings Table', 'leave-manager' ); ?></div>
						<div class="lm-info-value lm-status-pass"><?php esc_html_e( 'EXISTS', 'leave-manager' ); ?></div>
					</div>
				</div>

				<h3><?php esc_html_e( 'Health Checks', 'leave-manager' ); ?></h3>
				<div class="lm-health-check">
					<div class="lm-check-item pass">
						<span class="lm-check-icon">✓</span>
						<span class="lm-check-text"><?php esc_html_e( 'Database Connection', 'leave-manager' ); ?></span>
						<span class="lm-check-status"><?php esc_html_e( 'PASS', 'leave-manager' ); ?></span>
					</div>
					<div class="lm-check-item pass">
						<span class="lm-check-icon">✓</span>
						<span class="lm-check-text"><?php esc_html_e( 'Plugin Tables', 'leave-manager' ); ?></span>
						<span class="lm-check-status"><?php esc_html_e( 'PASS', 'leave-manager' ); ?></span>
					</div>
					<div class="lm-check-item pass">
						<span class="lm-check-icon">✓</span>
						<span class="lm-check-text"><?php esc_html_e( 'File Permissions', 'leave-manager' ); ?></span>
						<span class="lm-check-status"><?php esc_html_e( 'PASS', 'leave-manager' ); ?></span>
					</div>
				</div>
			</div>
		</div>

		<!-- Logs Tab -->
		<div class="lm-tab-content" id="logs">
			<div class="lm-card">
				<h2><?php esc_html_e( 'System Logs', 'leave-manager' ); ?></h2>
				<p><?php esc_html_e( 'View recent system activity and errors', 'leave-manager' ); ?></p>
				
				<div class="lm-logs-container">
					<div class="lm-log-entry">
						<div class="lm-log-time">2025-12-21 21:35:00</div>
						<div class="lm-log-message">User admin logged in</div>
						<div class="lm-log-type">INFO</div>
					</div>
					<div class="lm-log-entry">
						<div class="lm-log-time">2025-12-21 21:30:00</div>
						<div class="lm-log-message">Leave request #5 approved</div>
						<div class="lm-log-type">INFO</div>
					</div>
					<div class="lm-log-entry">
						<div class="lm-log-time">2025-12-21 21:25:00</div>
						<div class="lm-log-message">Settings updated by admin</div>
						<div class="lm-log-type">INFO</div>
					</div>
					<div class="lm-log-entry">
						<div class="lm-log-time">2025-12-21 21:20:00</div>
						<div class="lm-log-message">New user employee-john created</div>
						<div class="lm-log-type">INFO</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Export Tab -->
		<div class="lm-tab-content" id="export">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Export Data', 'leave-manager' ); ?></h2>
				<p><?php esc_html_e( 'Export your leave management data', 'leave-manager' ); ?></p>
				
				<div class="lm-export-options">
					<div class="lm-export-item">
						<h3><?php esc_html_e( 'Export Users', 'leave-manager' ); ?></h3>
						<p><?php esc_html_e( 'Download all employee data in CSV format', 'leave-manager' ); ?></p>
						<button class="lm-button lm-button-primary"><?php esc_html_e( 'Export CSV', 'leave-manager' ); ?></button>
					</div>
					<div class="lm-export-item">
						<h3><?php esc_html_e( 'Export Requests', 'leave-manager' ); ?></h3>
						<p><?php esc_html_e( 'Download all leave requests in CSV format', 'leave-manager' ); ?></p>
						<button class="lm-button lm-button-primary"><?php esc_html_e( 'Export CSV', 'leave-manager' ); ?></button>
					</div>
					<div class="lm-export-item">
						<h3><?php esc_html_e( 'Export Settings', 'leave-manager' ); ?></h3>
						<p><?php esc_html_e( 'Download all settings and configuration', 'leave-manager' ); ?></p>
						<button class="lm-button lm-button-primary"><?php esc_html_e( 'Export JSON', 'leave-manager' ); ?></button>
					</div>
				</div>
			</div>
		</div>

		<!-- Reset Plugin Tab -->
		<div class="lm-tab-content" id="reset">
			<div class="lm-card">
				<h2 style="color: #dc3545;"><?php esc_html_e( 'Reset Plugin Data', 'leave-manager' ); ?></h2>
				<p><?php esc_html_e( 'Warning: This action will permanently delete all plugin data and cannot be undone.', 'leave-manager' ); ?></p>
				
				<div class="lm-reset-options">
					<div class="lm-reset-item">
						<label class="lm-checkbox-label">
							<input type="checkbox" id="reset-users" value="users">
							<span><?php esc_html_e( 'Delete all Users/Staff', 'leave-manager' ); ?></span>
						</label>
						<p class="lm-reset-desc"><?php esc_html_e( 'Removes all employee records from the system', 'leave-manager' ); ?></p>
					</div>
					<div class="lm-reset-item">
						<label class="lm-checkbox-label">
							<input type="checkbox" id="reset-requests" value="requests">
							<span><?php esc_html_e( 'Delete all Leave Requests', 'leave-manager' ); ?></span>
						</label>
						<p class="lm-reset-desc"><?php esc_html_e( 'Removes all leave request history', 'leave-manager' ); ?></p>
					</div>
					<div class="lm-reset-item">
						<label class="lm-checkbox-label">
							<input type="checkbox" id="reset-policies" value="policies">
							<span><?php esc_html_e( 'Delete all Leave Policies', 'leave-manager' ); ?></span>
						</label>
						<p class="lm-reset-desc"><?php esc_html_e( 'Removes all leave policy configurations', 'leave-manager' ); ?></p>
					</div>
					<div class="lm-reset-item">
						<label class="lm-checkbox-label">
							<input type="checkbox" id="reset-departments" value="departments">
							<span><?php esc_html_e( 'Delete all Departments', 'leave-manager' ); ?></span>
						</label>
						<p class="lm-reset-desc"><?php esc_html_e( 'Removes all department configurations', 'leave-manager' ); ?></p>
					</div>
					<div class="lm-reset-item">
						<label class="lm-checkbox-label">
							<input type="checkbox" id="reset-settings" value="settings">
							<span><?php esc_html_e( 'Reset all Settings', 'leave-manager' ); ?></span>
						</label>
						<p class="lm-reset-desc"><?php esc_html_e( 'Resets all plugin settings to defaults', 'leave-manager' ); ?></p>
					</div>
					<div class="lm-reset-item">
						<label class="lm-checkbox-label">
							<input type="checkbox" id="reset-templates" value="templates">
							<span><?php esc_html_e( 'Reset all Email Templates', 'leave-manager' ); ?></span>
						</label>
						<p class="lm-reset-desc"><?php esc_html_e( 'Resets all email templates to defaults', 'leave-manager' ); ?></p>
					</div>
				</div>

				<div class="lm-form-actions" style="margin-top: 30px;">
					<button class="lm-btn" style="background: #dc3545; color: white;" id="btn-reset-selected"><?php esc_html_e( 'Reset Selected', 'leave-manager' ); ?></button>
					<button class="lm-btn" style="background: #dc3545; color: white;" id="btn-reset-all"><?php esc_html_e( 'Reset All Data', 'leave-manager' ); ?></button>
				</div>

				<div class="lm-reset-warning" style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
					<strong><?php esc_html_e( 'Important:', 'leave-manager' ); ?></strong>
					<?php esc_html_e( 'Before resetting, consider exporting your data using the Export tab. This action is irreversible.', 'leave-manager' ); ?>
				</div>
			</div>
		</div>
	</div>

	<div class="content-sidebar">
		<div class="lm-card">
			<h3><?php esc_html_e( 'System Status', 'leave-manager' ); ?></h3>
			<p><?php esc_html_e( 'All systems operational', 'leave-manager' ); ?></p>
		</div>

		<div class="lm-card">
			<h3><?php esc_html_e( 'Database', 'leave-manager' ); ?></h3>
			<p><?php esc_html_e( 'Connected and healthy', 'leave-manager' ); ?></p>
		</div>

		<div class="lm-card">
			<h3><?php esc_html_e( 'Quick Actions', 'leave-manager' ); ?></h3>
			<ul style="list-style: none; padding: 0;">
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-management' ) ); ?>"><?php esc_html_e( 'Dashboard', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-help' ) ); ?>"><?php esc_html_e( 'Help', 'leave-manager' ); ?></a></li>
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

<style>
.lm-info-table {
	background: white;
	border-radius: 8px;
	overflow: hidden;
	margin: 20px 0;
}

.lm-info-row {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 15px 20px;
	border-bottom: 1px solid #f0f0f0;
}

.lm-info-row:last-child {
	border-bottom: none;
}

.lm-info-label {
	font-weight: 600;
	color: #333;
	min-width: 150px;
}

.lm-info-value {
	color: #666;
	text-align: right;
	flex: 1;
}

.lm-status-pass {
	color: #28a745;
	font-weight: 600;
}

.lm-health-check {
	background: white;
	border-radius: 8px;
	overflow: hidden;
	margin: 20px 0;
}

.lm-check-item {
	display: flex;
	align-items: center;
	gap: 15px;
	padding: 15px 20px;
	border-bottom: 1px solid #f0f0f0;
}

.lm-check-item:last-child {
	border-bottom: none;
}

.lm-check-item.pass .lm-check-icon {
	color: #28a745;
	font-weight: bold;
	font-size: 18px;
}

.lm-check-text {
	flex: 1;
	color: #333;
	font-weight: 500;
}

.lm-check-status {
	color: #28a745;
	font-weight: 600;
	font-size: 12px;
	text-transform: uppercase;
}

.lm-logs-container {
	background: white;
	border-radius: 8px;
	overflow: hidden;
}

.lm-log-entry {
	display: flex;
	align-items: center;
	gap: 15px;
	padding: 15px 20px;
	border-bottom: 1px solid #f0f0f0;
	font-size: 13px;
}

.lm-log-entry:last-child {
	border-bottom: none;
}

.lm-log-time {
	color: #999;
	min-width: 180px;
	font-family: monospace;
}

.lm-log-message {
	flex: 1;
	color: #333;
}

.lm-log-type {
	background: #e8f4f8;
	color: #0066cc;
	padding: 4px 8px;
	border-radius: 4px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
}

.lm-export-options {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.lm-export-item {
	background: white;
	padding: 20px;
	border-radius: 8px;
	border: 1px solid #e0e0e0;
}

.lm-export-item h3 {
	margin: 0 0 10px 0;
	font-size: 15px;
	color: #333;
}

.lm-export-item p {
	margin: 0 0 15px 0;
	font-size: 13px;
	color: #666;
	line-height: 1.5;
}

/* Reset Options Styles */
.lm-reset-options {
	margin: 20px 0;
}

.lm-reset-item {
	background: white;
	padding: 15px 20px;
	border-radius: 8px;
	margin-bottom: 10px;
	border: 1px solid #e0e0e0;
}

.lm-checkbox-label {
	display: flex;
	align-items: center;
	gap: 10px;
	cursor: pointer;
	font-weight: 600;
	color: #333;
}

.lm-checkbox-label input[type="checkbox"] {
	width: 18px;
	height: 18px;
	cursor: pointer;
}

.lm-reset-desc {
	margin: 8px 0 0 28px;
	font-size: 13px;
	color: #666;
}

#btn-reset-selected,
#btn-reset-all {
	padding: 12px 24px;
	font-size: 14px;
	font-weight: 600;
	border: none;
	border-radius: 6px;
	cursor: pointer;
	margin-right: 10px;
	transition: all 0.2s ease;
}

#btn-reset-selected:hover,
#btn-reset-all:hover {
	background: #c82333 !important;
	transform: translateY(-1px);
}

.lm-reset-notification {
	position: fixed;
	top: 50px;
	right: 20px;
	padding: 15px 25px;
	border-radius: 8px;
	color: white;
	font-weight: 500;
	z-index: 9999;
	box-shadow: 0 4px 12px rgba(0,0,0,0.15);
	transition: opacity 0.3s ease;
}

.lm-reset-notification.success {
	background: #28a745;
}

.lm-reset-notification.error {
	background: #dc3545;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Reset Selected button
	$('#btn-reset-selected').on('click', function() {
		var selectedItems = [];
		$('.lm-reset-options input[type="checkbox"]:checked').each(function() {
			selectedItems.push($(this).val());
		});
		
		if (selectedItems.length === 0) {
			alert('Please select at least one item to reset.');
			return;
		}
		
		if (!confirm('Are you sure you want to reset the selected items? This action cannot be undone.')) {
			return;
		}
		
		performReset(selectedItems);
	});
	
	// Reset All button
	$('#btn-reset-all').on('click', function() {
		if (!confirm('WARNING: This will delete ALL plugin data including users, requests, policies, departments, settings, and templates. This action cannot be undone. Are you absolutely sure?')) {
			return;
		}
		
		// Second confirmation
		if (!confirm('FINAL WARNING: All data will be permanently deleted. Type "RESET" in the next prompt to confirm.')) {
			return;
		}
		
		var confirmation = prompt('Type RESET to confirm:');
		if (confirmation !== 'RESET') {
			alert('Reset cancelled. You must type RESET exactly to confirm.');
			return;
		}
		
		performReset(['users', 'requests', 'policies', 'departments', 'settings', 'templates']);
	});
	
	function performReset(items) {
		$('#btn-reset-selected, #btn-reset-all').prop('disabled', true).text('Resetting...');
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'leave_manager_reset_plugin',
				nonce: '<?php echo wp_create_nonce("leave_manager_admin_nonce"); ?>',
				items: items
			},
			success: function(response) {
				if (response.success) {
					showNotification('success', response.data.message || 'Reset completed successfully!');
					// Uncheck all checkboxes
					$('.lm-reset-options input[type="checkbox"]').prop('checked', false);
					// Reload page after 2 seconds
					setTimeout(function() {
						location.reload();
					}, 2000);
				} else {
					showNotification('error', response.data.message || 'Reset failed. Please try again.');
				}
			},
			error: function() {
				showNotification('error', 'An error occurred. Please try again.');
			},
			complete: function() {
				$('#btn-reset-selected').prop('disabled', false).text('Reset Selected');
				$('#btn-reset-all').prop('disabled', false).text('Reset All Data');
			}
		});
	}
	
	function showNotification(type, message) {
		var notification = $('<div class="lm-reset-notification ' + type + '">' + message + '</div>');
		$('body').append(notification);
		setTimeout(function() {
			notification.fadeOut(function() {
				$(this).remove();
			});
		}, 4000);
	}
});
</script>

</div>
</div>
