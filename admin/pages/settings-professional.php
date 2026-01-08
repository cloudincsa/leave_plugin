<?php
/**
 * Professional Settings Page - ChatPanel Design with WordPress Media Integration
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$branding = new Leave_Manager_Branding();
$settings = $branding->get_settings();

// Include admin page template styles
include 'admin-page-template.php';
?>

<div class="leave-manager-admin-container">
<div class="lm-page-content">

<div class="page-header">
	<div>
		<h1><?php esc_html_e( 'Settings', 'leave-manager' ); ?></h1>
		<p class="subtitle"><?php esc_html_e( 'Configure your Leave Management System', 'leave-manager' ); ?></p>
	</div>
</div>

	<div class="admin-tabs">
		<button class="admin-tab active" data-tab="general"><?php esc_html_e( 'General', 'leave-manager' ); ?></button>
		<button class="admin-tab" data-tab="branding"><?php esc_html_e( 'Branding', 'leave-manager' ); ?></button>
		<button class="admin-tab" data-tab="email"><?php esc_html_e( 'Email', 'leave-manager' ); ?></button>
		<button class="admin-tab" data-tab="notifications"><?php esc_html_e( 'Notifications', 'leave-manager' ); ?></button>
		<button class="admin-tab" data-tab="holiday-api"><?php esc_html_e( 'Holiday API', 'leave-manager' ); ?></button>
		<button class="admin-tab" data-tab="advanced"><?php esc_html_e( 'Advanced', 'leave-manager' ); ?></button>
	</div>

<div class="content-wrapper">
	<div class="content-main">
		<!-- General Settings Tab -->
		<div class="lm-tab-content active" id="general">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Organization Settings', 'leave-manager' ); ?></h2>
				
				<div class="lm-form-group">
					<label for="org-name"><?php esc_html_e( 'Organization Name', 'leave-manager' ); ?> *</label>
					<input type="text" id="org-name" name="organization_name" value="<?php echo esc_attr( $settings['organization_name'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Enter your organization name', 'leave-manager' ); ?>">
					<div class="lm-form-description"><?php esc_html_e( 'This name will appear in emails and throughout the system', 'leave-manager' ); ?></div>
				</div>

				<div class="lm-form-row">
					<div class="lm-form-group">
						<label for="org-email"><?php esc_html_e( 'Admin Email', 'leave-manager' ); ?> *</label>
						<input type="email" id="org-email" name="admin_email" value="<?php echo esc_attr( get_bloginfo( 'admin_email' ) ); ?>" placeholder="admin@example.com">
						<div class="lm-form-description"><?php esc_html_e( 'Email address for system notifications', 'leave-manager' ); ?></div>
					</div>
					<div class="lm-form-group">
						<label for="org-phone"><?php esc_html_e( 'Phone Number', 'leave-manager' ); ?></label>
						<input type="text" id="org-phone" name="phone_number" placeholder="+1 (555) 123-4567">
						<div class="lm-form-description"><?php esc_html_e( 'Optional contact number', 'leave-manager' ); ?></div>
					</div>
				</div>

				<div class="lm-form-group">
					<label for="org-address"><?php esc_html_e( 'Address', 'leave-manager' ); ?></label>
					<textarea id="org-address" name="address" placeholder="<?php esc_attr_e( 'Enter your organization address', 'leave-manager' ); ?>"></textarea>
					<div class="lm-form-description"><?php esc_html_e( 'Office address for reference', 'leave-manager' ); ?></div>
				</div>

				<hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

				<h3><?php esc_html_e( 'Department Management', 'leave-manager' ); ?></h3>

				<div class="lm-form-group">
					<label class="lm-toggle-switch">
						<input type="checkbox" id="enable-departments" name="enable_departments">
						<span><?php esc_html_e( 'Enable Department Management', 'leave-manager' ); ?></span>
					</label>
					<div class="lm-form-description"><?php esc_html_e( 'Enable department-based leave management and allocations', 'leave-manager' ); ?></div>
				</div>

				<div class="lm-form-row">
					<div class="lm-form-group">
						<label for="default-department"><?php esc_html_e( 'Default Department', 'leave-manager' ); ?></label>
						<select id="default-department" name="default_department">
							<option value="">Select Department</option>
							<?php 
								global $wpdb;
								$departments = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}leave_manager_departments WHERE status = 'active' ORDER BY department_name ASC");
								foreach ($departments as $dept) {
									echo '<option value="' . esc_attr($dept->department_id) . '">' . esc_html($dept->department_name) . '</option>';
								}
							?>
						</select>
						<div class="lm-form-description"><?php esc_html_e( 'Default department for new staff members', 'leave-manager' ); ?></div>
					</div>
					<div class="lm-form-group">
						<label class="lm-toggle-switch">
							<input type="checkbox" id="department-based-allocation" name="department_based_allocation">
							<span><?php esc_html_e( 'Department-based Leave Allocation', 'leave-manager' ); ?></span>
						</label>
						<div class="lm-form-description"><?php esc_html_e( 'Allow different leave allocations per department', 'leave-manager' ); ?></div>
					</div>
				</div>

				<div class="lm-form-actions">
					<button class="lm-btn-primary" onclick="saveSettings()"><?php esc_html_e( 'Save Settings', 'leave-manager' ); ?></button>
					<button class="lm-btn-secondary" onclick="resetSettings()"><?php esc_html_e( 'Reset', 'leave-manager' ); ?></button>
				</div>
			</div>
		</div>

		<!-- Branding Settings Tab -->
		<div class="lm-tab-content" id="branding">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Branding Settings', 'leave-manager' ); ?></h2>
				
				<div class="lm-form-group">
					<label><?php esc_html_e( 'Logo', 'leave-manager' ); ?></label>
					<div class="lm-media-upload-section">
						<div class="lm-media-preview" id="logo-preview">
							<div class="lm-media-preview-placeholder">📷</div>
						</div>
						<button class="lm-btn-upload" onclick="selectLogo()"><?php esc_html_e( 'Upload Logo', 'leave-manager' ); ?></button>
						<button class="lm-btn-remove" onclick="removeLogo()"><?php esc_html_e( 'Remove Logo', 'leave-manager' ); ?></button>
					</div>
				</div>

				<div class="lm-form-group">
					<label><?php esc_html_e( 'Favicon', 'leave-manager' ); ?></label>
					<div class="lm-media-upload-section">
						<div class="lm-media-preview" id="favicon-preview">
							<div class="lm-media-preview-placeholder">🔗</div>
						</div>
						<button class="lm-btn-upload" onclick="selectFavicon()"><?php esc_html_e( 'Upload Favicon', 'leave-manager' ); ?></button>
						<button class="lm-btn-remove" onclick="removeFavicon()"><?php esc_html_e( 'Remove Favicon', 'leave-manager' ); ?></button>
					</div>
				</div>

					<div class="lm-form-group">
						<label><?php esc_html_e( 'Primary Color', 'leave-manager' ); ?></label>
						<div class="lm-color-input-wrapper">
							<input type="color" id="primary-color" value="#4A5FFF" onchange="syncColorInputs('primary-color', 'primary-color-text')">
							<input type="text" id="primary-color-text" value="#4A5FFF" placeholder="#4A5FFF" onchange="syncColorInputs('primary-color-text', 'primary-color')">
						</div>
						<div class="lm-form-description"><?php esc_html_e( 'Color for calendar and UI elements', 'leave-manager' ); ?></div>
					</div>

				<div class="lm-form-actions">
					<button class="lm-btn-primary" onclick="saveBranding()"><?php esc_html_e( 'Save Branding', 'leave-manager' ); ?></button>
				</div>
			</div>
		</div>

		<!-- Email Settings Tab -->
		<div class="lm-tab-content" id="email">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Email Settings', 'leave-manager' ); ?></h2>
				
				<div class="lm-form-group">
					<label for="smtp-host"><?php esc_html_e( 'SMTP Host', 'leave-manager' ); ?></label>
					<input type="text" id="smtp-host" name="smtp_host" placeholder="smtp.gmail.com">
					<div class="lm-form-description"><?php esc_html_e( 'SMTP server address', 'leave-manager' ); ?></div>
				</div>

				<div class="lm-form-row">
					<div class="lm-form-group">
						<label for="smtp-port"><?php esc_html_e( 'SMTP Port', 'leave-manager' ); ?></label>
						<input type="number" id="smtp-port" name="smtp_port" placeholder="587">
						<div class="lm-form-description"><?php esc_html_e( 'Usually 587 or 465', 'leave-manager' ); ?></div>
					</div>
					<div class="lm-form-group">
						<label for="smtp-encryption"><?php esc_html_e( 'Encryption', 'leave-manager' ); ?></label>
						<select id="smtp-encryption" name="smtp_encryption">
							<option value="tls"><?php esc_html_e( 'TLS', 'leave-manager' ); ?></option>
							<option value="ssl"><?php esc_html_e( 'SSL', 'leave-manager' ); ?></option>
							<option value="none"><?php esc_html_e( 'None', 'leave-manager' ); ?></option>
						</select>
					</div>
				</div>

				<div class="lm-form-row">
					<div class="lm-form-group">
						<label for="smtp-username"><?php esc_html_e( 'Username', 'leave-manager' ); ?></label>
						<input type="text" id="smtp-username" name="smtp_username" placeholder="your-email@gmail.com">
					</div>
					<div class="lm-form-group">
						<label for="smtp-password"><?php esc_html_e( 'Password', 'leave-manager' ); ?></label>
						<input type="password" id="smtp-password" name="smtp_password" placeholder="••••••••">
					</div>
				</div>

				<div class="lm-form-actions">
					<button class="lm-btn-primary" onclick="saveEmailSettings()"><?php esc_html_e( 'Save Email Settings', 'leave-manager' ); ?></button>
					<button class="lm-btn-secondary" onclick="testEmail()"><?php esc_html_e( 'Test Email', 'leave-manager' ); ?></button>
				</div>
			</div>
		</div>

		<!-- Notifications Settings Tab -->
		<div class="lm-tab-content" id="notifications">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Notification Settings', 'leave-manager' ); ?></h2>
				
				<div class="lm-form-group">
					<label class="lm-toggle-switch">
									<input type="checkbox" id="notify-on-request" name="notify_on_request" checked>
						<span><?php esc_html_e( 'Notify on Leave Request', 'leave-manager' ); ?></span>
					</label>
					<div class="lm-form-description"><?php esc_html_e( 'Send email when employee submits leave request', 'leave-manager' ); ?></div>
				</div>

				<div class="lm-form-group">
					<label class="lm-toggle-switch">
									<input type="checkbox" id="notify-on-approval" name="notify_on_approval" checked>
						<span><?php esc_html_e( 'Notify on Approval', 'leave-manager' ); ?></span>
					</label>
					<div class="lm-form-description"><?php esc_html_e( 'Send email when request is approved', 'leave-manager' ); ?></div>
				</div>

				<div class="lm-form-group">
					<label class="lm-toggle-switch">
									<input type="checkbox" id="notify-on-rejection" name="notify_on_rejection" checked>
						<span><?php esc_html_e( 'Notify on Rejection', 'leave-manager' ); ?></span>
					</label>
					<div class="lm-form-description"><?php esc_html_e( 'Send email when request is rejected', 'leave-manager' ); ?></div>
				</div>

					<div class="lm-form-actions">
						<button class="lm-btn-primary" onclick="saveNotifications()"><?php esc_html_e( 'Save Notifications', 'leave-manager' ); ?></button>
					</div>
				</div>
			</div>

			<!-- Holiday API Settings Tab -->
			<div class="lm-tab-content" id="holiday-api">
				<div class="lm-card">
					<h2><?php esc_html_e( 'Holiday API Configuration', 'leave-manager' ); ?></h2>
					<p class="lm-form-description"><?php esc_html_e( 'Configure your Holiday API credentials to automatically fetch public holidays', 'leave-manager' ); ?></p>

					<div class="lm-form-group">
						<label class="lm-toggle-switch">
							<input type="checkbox" id="enable-holiday-api" name="enable_holiday_api">
							<span><?php esc_html_e( 'Enable Holiday API Integration', 'leave-manager' ); ?></span>
						</label>
						<div class="lm-form-description"><?php esc_html_e( 'Enable automatic holiday fetching from API', 'leave-manager' ); ?></div>
					</div>

					<div class="lm-form-row">
						<div class="lm-form-group">
							<label for="holiday-api-provider"><?php esc_html_e( 'API Provider', 'leave-manager' ); ?></label>
							<select id="holiday-api-provider" name="holiday_api_provider">
								<option value="">Select Provider</option>
								<option value="calendarific">Calendarific</option>
								<option value="abstractapi">Abstract API</option>
								<option value="holidays-api">Holidays API</option>
								<option value="custom">Custom Endpoint</option>
							</select>
							<div class="lm-form-description"><?php esc_html_e( 'Choose your holiday API provider', 'leave-manager' ); ?></div>
						</div>
						<div class="lm-form-group">
							<label for="holiday-api-key"><?php esc_html_e( 'API Key', 'leave-manager' ); ?></label>
							<input type="password" id="holiday-api-key" name="holiday_api_key" placeholder="Enter your API key">
							<div class="lm-form-description"><?php esc_html_e( 'Your API authentication key', 'leave-manager' ); ?></div>
						</div>
					</div>

					<div class="lm-form-row">
						<div class="lm-form-group">
							<label for="holiday-api-endpoint"><?php esc_html_e( 'API Endpoint', 'leave-manager' ); ?></label>
							<input type="text" id="holiday-api-endpoint" name="holiday_api_endpoint" placeholder="https://api.example.com/holidays">
							<div class="lm-form-description"><?php esc_html_e( 'Custom API endpoint URL (if using custom provider)', 'leave-manager' ); ?></div>
						</div>
						<div class="lm-form-group">
							<label for="holiday-default-country"><?php esc_html_e( 'Default Country Code', 'leave-manager' ); ?></label>
							<input type="text" id="holiday-default-country" name="holiday_default_country" placeholder="ZA" maxlength="2">
							<div class="lm-form-description"><?php esc_html_e( 'ISO 3166-1 alpha-2 country code (e.g., ZA, US, GB)', 'leave-manager' ); ?></div>
						</div>
					</div>

					<div class="lm-form-group">
						<label for="holiday-sync-frequency"><?php esc_html_e( 'Sync Frequency', 'leave-manager' ); ?></label>
						<select id="holiday-sync-frequency" name="holiday_sync_frequency">
							<option value="daily">Daily</option>
							<option value="weekly">Weekly</option>
							<option value="monthly">Monthly</option>
							<option value="manual">Manual Only</option>
						</select>
						<div class="lm-form-description"><?php esc_html_e( 'How often to automatically sync holidays', 'leave-manager' ); ?></div>
					</div>

					<hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;">

					<h3><?php esc_html_e( 'Public Holiday Behavior', 'leave-manager' ); ?></h3>

					<div class="lm-form-group">
						<label class="lm-toggle-switch">
							<input type="checkbox" id="holidays-count-as-leave" name="holidays_count_as_leave">
							<span><?php esc_html_e( 'Public Holidays Count as Leave', 'leave-manager' ); ?></span>
						</label>
						<div class="lm-form-description">
							<?php esc_html_e( 'When ENABLED: Public holidays deduct from staff leave balance', 'leave-manager' ); ?><br>
							<?php esc_html_e( 'When DISABLED: Public holidays are free days off (do not count as leave)', 'leave-manager' ); ?>
						</div>
					</div>

					<div class="lm-form-group">
						<label class="lm-toggle-switch">
							<input type="checkbox" id="exclude-holidays-from-balance" name="exclude_holidays_from_balance" checked>
							<span><?php esc_html_e( 'Exclude Public Holidays from Leave Calculations', 'leave-manager' ); ?></span>
						</label>
						<div class="lm-form-description">
							<?php esc_html_e( 'When ENABLED: Public holidays are not counted as working days', 'leave-manager' ); ?><br>
							<?php esc_html_e( 'When DISABLED: Public holidays are treated as regular working days', 'leave-manager' ); ?>
						</div>
					</div>

					<div class="lm-form-group">
						<label class="lm-toggle-switch">
							<input type="checkbox" id="allow-optional-holidays" name="allow_optional_holidays" checked>
							<span><?php esc_html_e( 'Allow Optional Holidays', 'leave-manager' ); ?></span>
						</label>
						<div class="lm-form-description">
							<?php esc_html_e( 'Staff can choose whether to take optional public holidays', 'leave-manager' ); ?>
						</div>
					</div>

					<div class="lm-form-actions">
						<button class="lm-btn-primary" onclick="saveHolidayAPI()"><?php esc_html_e( 'Save Holiday API Settings', 'leave-manager' ); ?></button>
						<button class="lm-btn-secondary" onclick="testHolidayAPI()"><?php esc_html_e( 'Test Connection', 'leave-manager' ); ?></button>
						<button class="lm-btn-secondary" onclick="syncHolidaysNow()"><?php esc_html_e( 'Sync Now', 'leave-manager' ); ?></button>
					</div>

					<div style="margin-top: 30px; padding: 15px; background-color: #f0f4ff; border-left: 4px solid #4A5FFF; border-radius: 4px;">
						<h4><?php esc_html_e( 'Configuration Examples', 'leave-manager' ); ?></h4>
						<ul style="margin: 10px 0; padding-left: 20px;">
							<li><strong><?php esc_html_e( 'Real Holidays (Recommended):', 'leave-manager' ); ?></strong> Uncheck "Count as Leave" - Staff gets day off free</li>
							<li><strong><?php esc_html_e( 'Counted Leave:', 'leave-manager' ); ?></strong> Check "Count as Leave" - Deducts from leave balance</li>
							<li><strong><?php esc_html_e( 'Optional Holidays:', 'leave-manager' ); ?></strong> Check "Allow Optional" - Staff chooses to take them</li>
						</ul>
					</div>
				</div>
			</div>

			<!-- Advanced Settings Tab -->
		<div class="lm-tab-content" id="advanced">
			<div class="lm-card">
				<h2><?php esc_html_e( 'Advanced Settings', 'leave-manager' ); ?></h2>
				
					<div class="lm-form-group">
						<label class="lm-toggle-switch">
							<input type="checkbox" id="debug-mode" name="debug_mode">
							<span><?php esc_html_e( 'Debug Mode', 'leave-manager' ); ?></span>
						</label>
						<div class="lm-form-description"><?php esc_html_e( 'Enable debug logging for troubleshooting', 'leave-manager' ); ?></div>
					</div>

					<div class="lm-form-group">
						<label class="lm-toggle-switch">
							<input type="checkbox" id="enable-leave-notifications" name="enable_leave_notifications" checked>
							<span><?php esc_html_e( 'Enable Leave Notifications', 'leave-manager' ); ?></span>
						</label>
						<div class="lm-form-description"><?php esc_html_e( 'Send email notifications for leave requests', 'leave-manager' ); ?></div>
					</div>

				<div class="lm-form-group">
					<label for="max-upload-size"><?php esc_html_e( 'Max Upload Size (MB)', 'leave-manager' ); ?></label>
					<input type="number" id="max-upload-size" name="max_upload_size" value="10" min="1" max="100">
					<div class="lm-form-description"><?php esc_html_e( 'Maximum file size for attachments', 'leave-manager' ); ?></div>
				</div>

				<div class="lm-form-group">
					<label for="session-timeout"><?php esc_html_e( 'Session Timeout (minutes)', 'leave-manager' ); ?></label>
					<input type="number" id="session-timeout" name="session_timeout" value="30" min="5" max="480">
					<div class="lm-form-description"><?php esc_html_e( 'Automatic logout after inactivity', 'leave-manager' ); ?></div>
				</div>

				<div class="lm-form-actions">
					<button class="lm-btn-primary" onclick="saveAdvanced()"><?php esc_html_e( 'Save Advanced Settings', 'leave-manager' ); ?></button>
				</div>
			</div>
		</div>
	</div>

	<!-- Sidebar -->
	<div class="content-sidebar">
		<div class="lm-card">
			<h3><?php esc_html_e( 'Settings Help', 'leave-manager' ); ?></h3>
			<p><?php esc_html_e( 'Configure your Leave Management System settings here. Changes are saved automatically.', 'leave-manager' ); ?></p>
		</div>

		<div class="lm-card">
			<h3><?php esc_html_e( 'Email Configuration', 'leave-manager' ); ?></h3>
			<p><?php esc_html_e( 'Set up SMTP to send emails. Contact your email provider for SMTP details.', 'leave-manager' ); ?></p>
		</div>

		<div class="lm-card">
			<h3><?php esc_html_e( 'Quick Links', 'leave-manager' ); ?></h3>
			<ul style="list-style: none; padding: 0;">
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-management' ) ); ?>"><?php esc_html_e( 'Dashboard', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-requests' ) ); ?>"><?php esc_html_e( 'Requests', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-staff' ) ); ?>"><?php esc_html_e( 'Staff', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-help' ) ); ?>"><?php esc_html_e( 'Help', 'leave-manager' ); ?></a></li>
			</ul>
		</div>
	</div>
</div>

<script>
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
var lm_nonce = '<?php echo wp_create_nonce('leave_manager_admin_nonce'); ?>';

document.querySelectorAll('.admin-tab').forEach(button => {
	button.addEventListener('click', function() {
		const tabId = this.getAttribute('data-tab');
		document.querySelectorAll('.lm-tab-content').forEach(tab => tab.classList.remove('active'));
		document.querySelectorAll('.admin-tab').forEach(btn => btn.classList.remove('active'));
		document.getElementById(tabId).classList.add('active');
		this.classList.add('active');
	});
});

function lmShowNotice(message, type) {
	var notice = document.createElement('div');
	notice.className = 'lm-notice lm-notice-' + type;
	notice.innerHTML = '<p>' + message + '</p>';
	notice.style.cssText = 'position:fixed;top:50px;right:20px;padding:15px 20px;border-radius:5px;z-index:9999;' + 
		(type === 'success' ? 'background:#10B981;color:white;' : 'background:#EF4444;color:white;');
	document.body.appendChild(notice);
	setTimeout(function() { notice.remove(); }, 3000);
}

function saveSettings() {
	var data = {
		action: 'leave_manager_save_settings',
		nonce: lm_nonce,
		organization_name: document.getElementById('org-name').value,
		admin_email: document.getElementById('org-email').value,
		phone_number: document.getElementById('org-phone').value,
		address: document.getElementById('org-address').value
	};
	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams(data)
	}).then(r => r.json()).then(response => {
		if(response.success) {
			lmShowNotice('Settings saved successfully!', 'success');
		} else {
			lmShowNotice(response.data || 'Error saving settings', 'error');
		}
	}).catch(e => lmShowNotice('Error: ' + e.message, 'error'));
}

function resetSettings() {
	if(!confirm('Are you sure you want to reset settings?')) return;
	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams({action: 'leave_manager_reset_settings', nonce: lm_nonce})
	}).then(r => r.json()).then(response => {
		if(response.success) {
			lmShowNotice('Settings reset successfully!', 'success');
			location.reload();
		} else {
			lmShowNotice(response.data || 'Error resetting settings', 'error');
		}
	}).catch(e => lmShowNotice('Error: ' + e.message, 'error'));
}

function selectLogo() {
	if(typeof wp !== 'undefined' && wp.media) {
		var frame = wp.media({title: 'Select Logo', multiple: false});
		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			document.getElementById('logo-preview').innerHTML = '<img src="' + attachment.url + '" style="max-width:100%;max-height:100px;">';
			fetch(ajaxurl, {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({action: 'leave_manager_save_branding', nonce: lm_nonce, logo_url: attachment.url})
			}).then(r => r.json()).then(response => {
				if(response.success) lmShowNotice('Logo saved!', 'success');
			});
		});
		frame.open();
	} else {
		lmShowNotice('Media library not available', 'error');
	}
}

function removeLogo() {
	document.getElementById('logo-preview').innerHTML = '<div class="lm-media-preview-placeholder">📷</div>';
	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams({action: 'leave_manager_save_branding', nonce: lm_nonce, logo_url: ''})
	}).then(r => r.json()).then(response => {
		if(response.success) lmShowNotice('Logo removed!', 'success');
	});
}

function selectFavicon() {
	if(typeof wp !== 'undefined' && wp.media) {
		var frame = wp.media({title: 'Select Favicon', multiple: false});
		frame.on('select', function() {
			var attachment = frame.state().get('selection').first().toJSON();
			document.getElementById('favicon-preview').innerHTML = '<img src="' + attachment.url + '" style="max-width:100%;max-height:50px;">';
			fetch(ajaxurl, {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({action: 'leave_manager_save_branding', nonce: lm_nonce, favicon_url: attachment.url})
			}).then(r => r.json()).then(response => {
				if(response.success) lmShowNotice('Favicon saved!', 'success');
			});
		});
		frame.open();
	} else {
		lmShowNotice('Media library not available', 'error');
	}
}

function removeFavicon() {
	document.getElementById('favicon-preview').innerHTML = '<div class="lm-media-preview-placeholder">🔗</div>';
	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams({action: 'leave_manager_save_branding', nonce: lm_nonce, favicon_url: ''})
	}).then(r => r.json()).then(response => {
		if(response.success) lmShowNotice('Favicon removed!', 'success');
	});
}

function saveBranding() {
	var data = {
		action: 'leave_manager_save_branding',
		nonce: lm_nonce,
		primary_color: document.getElementById('primary-color') ? document.getElementById('primary-color').value : '',
		secondary_color: document.getElementById('secondary-color') ? document.getElementById('secondary-color').value : ''
	};
	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams(data)
	}).then(r => r.json()).then(response => {
		if(response.success) lmShowNotice('Branding saved!', 'success');
		else lmShowNotice(response.data || 'Error saving branding', 'error');
	}).catch(e => lmShowNotice('Error: ' + e.message, 'error'));
}

function saveEmailSettings() {
	var data = {
		action: 'leave_manager_save_email_settings',
		nonce: lm_nonce,
		smtp_host: document.getElementById('smtp-host') ? document.getElementById('smtp-host').value : '',
		smtp_port: document.getElementById('smtp-port') ? document.getElementById('smtp-port').value : '',
		smtp_user: document.getElementById('smtp-user') ? document.getElementById('smtp-user').value : '',
		smtp_pass: document.getElementById('smtp-pass') ? document.getElementById('smtp-pass').value : '',
		from_email: document.getElementById('from-email') ? document.getElementById('from-email').value : '',
		from_name: document.getElementById('from-name') ? document.getElementById('from-name').value : ''
	};
	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams(data)
	}).then(r => r.json()).then(response => {
		if(response.success) lmShowNotice('Email settings saved!', 'success');
		else lmShowNotice(response.data || 'Error saving email settings', 'error');
	}).catch(e => lmShowNotice('Error: ' + e.message, 'error'));
}

function testEmail() {
	var testEmail = prompt('Enter email address to send test email:');
	if(!testEmail) return;
	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams({action: 'leave_manager_test_email', nonce: lm_nonce, test_email: testEmail})
	}).then(r => r.json()).then(response => {
		if(response.success) lmShowNotice('Test email sent!', 'success');
		else lmShowNotice(response.data || 'Error sending test email', 'error');
	}).catch(e => lmShowNotice('Error: ' + e.message, 'error'));
}

function saveNotifications() {
	var data = {
		action: 'leave_manager_save_notifications',
		nonce: lm_nonce,
		email_on_request: document.getElementById('email-on-request') ? document.getElementById('email-on-request').checked : false,
		email_on_approval: document.getElementById('email-on-approval') ? document.getElementById('email-on-approval').checked : false,
		email_on_rejection: document.getElementById('email-on-rejection') ? document.getElementById('email-on-rejection').checked : false
	};
	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams(data)
	}).then(r => r.json()).then(response => {
		if(response.success) lmShowNotice('Notification settings saved!', 'success');
		else lmShowNotice(response.data || 'Error saving notifications', 'error');
	}).catch(e => lmShowNotice('Error: ' + e.message, 'error'));
}

	function syncColorInputs(sourceId, targetId) {
		var source = document.getElementById(sourceId);
		var target = document.getElementById(targetId);
		if (sourceId.includes('text')) {
			if (/^#[0-9A-F]{6}$/i.test(source.value)) {
				target.value = source.value;
			}
		} else {
			target.value = source.value;
		}
	}

	function saveHolidayAPI() {
		var data = {
			action: 'leave_manager_save_holiday_api',
			nonce: lm_nonce,
			enable_holiday_api: document.getElementById('enable-holiday-api') ? document.getElementById('enable-holiday-api').checked : false,
			holiday_api_provider: document.getElementById('holiday-api-provider') ? document.getElementById('holiday-api-provider').value : '',
			holiday_api_key: document.getElementById('holiday-api-key') ? document.getElementById('holiday-api-key').value : '',
			holiday_api_endpoint: document.getElementById('holiday-api-endpoint') ? document.getElementById('holiday-api-endpoint').value : '',
			holiday_default_country: document.getElementById('holiday-default-country') ? document.getElementById('holiday-default-country').value : '',
			holiday_sync_frequency: document.getElementById('holiday-sync-frequency') ? document.getElementById('holiday-sync-frequency').value : 'manual',
			holidays_count_as_leave: document.getElementById('holidays-count-as-leave') ? document.getElementById('holidays-count-as-leave').checked : false,
			exclude_holidays_from_balance: document.getElementById('exclude-holidays-from-balance') ? document.getElementById('exclude-holidays-from-balance').checked : true,
			allow_optional_holidays: document.getElementById('allow-optional-holidays') ? document.getElementById('allow-optional-holidays').checked : true
		};
		fetch(ajaxurl, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			body: new URLSearchParams(data)
		}).then(r => r.json()).then(response => {
			if(response.success) lmShowNotice('Holiday API settings saved!', 'success');
			else lmShowNotice(response.data || 'Error saving settings', 'error');
		}).catch(e => lmShowNotice('Error: ' + e.message, 'error'));
	}

	function testHolidayAPI() {
		fetch(ajaxurl, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			body: new URLSearchParams({action: 'leave_manager_test_holiday_api', nonce: lm_nonce})
		}).then(r => r.json()).then(response => {
			if(response.success) lmShowNotice('API connection successful!', 'success');
			else lmShowNotice(response.data || 'API connection failed', 'error');
		}).catch(e => lmShowNotice('Error: ' + e.message, 'error'));
	}

	function syncHolidaysNow() {
		fetch(ajaxurl, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			body: new URLSearchParams({action: 'leave_manager_sync_holidays_now', nonce: lm_nonce})
		}).then(r => r.json()).then(response => {
			if(response.success) lmShowNotice('Holidays synced! ' + response.data + ' holidays imported.', 'success');
			else lmShowNotice(response.data || 'Sync failed', 'error');
		}).catch(e => lmShowNotice('Error: ' + e.message, 'error'));
	}

	function saveAdvanced() {
		var data = {
			action: 'leave_manager_save_advanced',
			nonce: lm_nonce,
			date_format: document.getElementById('date-format') ? document.getElementById('date-format').value : 'Y-m-d',
		week_starts: document.getElementById('week-starts') ? document.getElementById('week-starts').value : '1',
		max_days_advance: document.getElementById('max-days-advance') ? document.getElementById('max-days-advance').value : '90'
	};
	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams(data)
	}).then(r => r.json()).then(response => {
		if(response.success) lmShowNotice('Advanced settings saved!', 'success');
		else lmShowNotice(response.data || 'Error saving advanced settings', 'error');
	}).catch(e => lmShowNotice('Error: ' + e.message, 'error'));
}
</script>
