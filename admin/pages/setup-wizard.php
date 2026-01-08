<?php
/**
 * Setup Wizard Page
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized' );
}

// Get setup status
$db = new Leave_Manager_Database();
$logger = new Leave_Manager_Logger( $db );
$detector = new Leave_Manager_Setup_Detector( $db, $logger );
$status = $detector->get_setup_status();

?>

<div class="wrap leave_manager-setup-wizard">
	<h1><?php echo esc_html( 'Leave Manager - Setup Wizard' ); ?></h1>

	<div class="leave-manager-setup-container">
		<div class="leave-manager-setup-header">
			<h2><?php echo esc_html( 'Welcome to Leave Manager' ); ?></h2>
			<p><?php echo esc_html( 'This wizard will help you initialize the plugin and set up the necessary database tables.' ); ?></p>
		</div>

		<div class="leave-manager-setup-content">
			<!-- Step 1: Database Tables -->
			<div class="leave-manager-setup-step">
				<h3><?php echo esc_html( 'Step 1: Database Tables' ); ?></h3>
				<p><?php echo esc_html( 'The plugin requires the following database tables:' ); ?></p>

				<table class="leave-manager-table-status">
					<thead>
						<tr>
							<th><?php echo esc_html( 'Table Name' ); ?></th>
							<th><?php echo esc_html( 'Status' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $status['tables'] as $table => $exists ) : ?>
							<tr>
								<td><?php echo esc_html( $table ); ?></td>
								<td>
									<?php if ( $exists ) : ?>
										<span class="status-badge status-success">✓ Created</span>
									<?php else : ?>
										<span class="status-badge status-pending">○ Pending</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<!-- Step 2: Configuration -->
			<div class="leave-manager-setup-step">
				<h3><?php echo esc_html( 'Step 2: Configuration' ); ?></h3>
				<p><?php echo esc_html( 'Default settings will be configured:' ); ?></p>

				<ul class="leave-manager-setup-checklist">
					<li>✓ Organization settings</li>
					<li>✓ Email (SMTP) configuration</li>
					<li>✓ Leave policy defaults</li>
					<li>✓ User roles and permissions</li>
					<li>✓ Email templates</li>
				</ul>
			</div>

			<!-- Step 3: Initialization -->
			<div class="leave-manager-setup-step">
				<h3><?php echo esc_html( 'Step 3: Initialize Plugin' ); ?></h3>
				<p><?php echo esc_html( 'Click the button below to create all necessary database tables and initialize the plugin.' ); ?></p>

				<?php if ( $status['needs_setup'] ) : ?>
					<button id="leave-manager-initialize-btn" class="button button-primary button-large">
						<?php echo esc_html( 'Initialize Plugin' ); ?>
					</button>
					<p class="description"><?php echo esc_html( 'This process will take a few seconds.' ); ?></p>
				<?php else : ?>
					<div class="notice notice-success inline">
						<p><?php echo esc_html( 'Plugin is already initialized!' ); ?></p>
					</div>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-management' ) ); ?>" class="button button-primary button-large">
						<?php echo esc_html( 'Go to Dashboard' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<!-- Progress Indicator -->
			<div id="leave-manager-progress" style="display: none; margin-top: 20px;">
				<div class="leave-manager-progress-bar">
					<div class="leave-manager-progress-fill"></div>
				</div>
				<p id="leave-manager-progress-text" style="text-align: center; margin-top: 10px;">
					<?php echo esc_html( 'Initializing plugin...' ); ?>
				</p>
			</div>

			<!-- Status Messages -->
			<div id="leave-manager-status-messages" style="margin-top: 20px;"></div>
		</div>
	</div>
</div>

<style>
	.leave-manager-setup-wizard {
		max-width: 800px;
		margin: 20px auto;
	}

	.leave-manager-setup-container {
		background: white;
		border: 1px solid #ddd;
		border-radius: 5px;
		padding: 30px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.leave-manager-setup-header {
		text-align: center;
		margin-bottom: 30px;
		padding-bottom: 20px;
		border-bottom: 2px solid #0073aa;
	}

	.leave-manager-setup-header h2 {
		color: #0073aa;
		margin: 0 0 10px 0;
	}

	.leave-manager-setup-header p {
		color: #666;
		margin: 0;
	}

	.leave-manager-setup-step {
		margin-bottom: 30px;
		padding: 20px;
		background: #f9f9f9;
		border: 1px solid #eee;
		border-radius: 4px;
	}

	.leave-manager-setup-step h3 {
		margin-top: 0;
		color: #333;
	}

	.leave-manager-table-status {
		width: 100%;
		border-collapse: collapse;
		margin-top: 15px;
	}

	.leave-manager-table-status th,
	.leave-manager-table-status td {
		padding: 10px;
		text-align: left;
		border-bottom: 1px solid #ddd;
	}

	.leave-manager-table-status th {
		background: #f0f0f0;
		font-weight: bold;
	}

	.status-badge {
		display: inline-block;
		padding: 5px 10px;
		border-radius: 3px;
		font-size: 12px;
		font-weight: 600;
	}

	.status-success {
		background-color: #d4edda;
		color: #155724;
	}

	.status-pending {
		background-color: #fff3cd;
		color: #856404;
	}

	.leave-manager-setup-checklist {
		list-style: none;
		padding: 0;
		margin: 15px 0;
	}

	.leave-manager-setup-checklist li {
		padding: 8px 0;
		color: #333;
	}

	#leave-manager-initialize-btn {
		min-width: 200px;
		height: 40px;
		font-size: 16px;
	}

	.leave-manager-progress-bar {
		width: 100%;
		height: 20px;
		background: #eee;
		border-radius: 10px;
		overflow: hidden;
		border: 1px solid #ddd;
	}

	.leave-manager-progress-fill {
		height: 100%;
		background: linear-gradient(90deg, #0073aa, #005a87);
		width: 0%;
		transition: width 0.3s ease;
	}

	.leave-manager-status-message {
		padding: 10px;
		margin: 5px 0;
		border-radius: 4px;
		border-left: 4px solid #0073aa;
	}

	.leave-manager-status-message.success {
		background: #d4edda;
		border-left-color: #28a745;
		color: #155724;
	}

	.leave-manager-status-message.error {
		background: #f8d7da;
		border-left-color: #dc3545;
		color: #721c24;
	}

	.leave-manager-status-message.info {
		background: #d1ecf1;
		border-left-color: #17a2b8;
		color: #0c5460;
	}
</style>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		const initBtn = document.getElementById('leave-manager-initialize-btn');
		const progress = document.getElementById('leave-manager-progress');
		const progressFill = document.querySelector('.leave-manager-progress-fill');
		const statusMessages = document.getElementById('leave-manager-status-messages');

		if (initBtn) {
			initBtn.addEventListener('click', function() {
				initBtn.disabled = true;
				progress.style.display = 'block';
				statusMessages.innerHTML = '';

				// Simulate progress
				let currentProgress = 0;
				const progressInterval = setInterval(function() {
					if (currentProgress < 90) {
						currentProgress += Math.random() * 30;
						progressFill.style.width = Math.min(currentProgress, 90) + '%';
					}
				}, 200);

				// Send AJAX request
				fetch(ajaxurl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: 'action=leave_manager_initialize_database&_wpnonce=<?php echo esc_js( wp_create_nonce( 'leave_manager_init_nonce' ) ); ?>'
				})
				.then(response => response.json())
				.then(data => {
					clearInterval(progressInterval);
					progressFill.style.width = '100%';

					if (data.success) {
						addMessage('Plugin initialized successfully!', 'success');
						setTimeout(function() {
							window.location.href = data.data.redirect;
						}, 2000);
					} else {
						addMessage('Error: ' + data.data, 'error');
						initBtn.disabled = false;
					}
				})
				.catch(error => {
					clearInterval(progressInterval);
					addMessage('Error: ' + error.message, 'error');
					initBtn.disabled = false;
				});
			});
		}

		function addMessage(text, type) {
			const message = document.createElement('div');
			message.className = 'leave_manager-status-message ' + type;
			message.textContent = text;
			statusMessages.appendChild(message);
		}
	});
</script>
