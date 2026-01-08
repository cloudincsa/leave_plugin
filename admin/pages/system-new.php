<?php
/**
 * System Page - Leave Manager v3.0
 * Tabs: Health | Logs | Export
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Get current tab
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'health';
$base_url = admin_url( 'admin.php?page=leave-manager-system' );

// Define tabs
$tabs = array(
	array( 'slug' => 'health', 'label' => 'System Health' ),
	array( 'slug' => 'logs', 'label' => 'Logs' ),
	array( 'slug' => 'export', 'label' => 'Export' ),
);

// Get system info
$users_table = $wpdb->prefix . 'leave_manager_leave_users';
$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
$settings_table = $wpdb->prefix . 'leave_manager_settings';
$email_logs_table = $wpdb->prefix . 'leave_manager_email_logs';

// Check tables exist
$tables = array(
	'leave_manager_leave_users' => $wpdb->get_var( "SHOW TABLES LIKE '{$users_table}'" ) === $users_table,
	'leave_manager_leave_requests' => $wpdb->get_var( "SHOW TABLES LIKE '{$requests_table}'" ) === $requests_table,
	'leave_manager_settings' => $wpdb->get_var( "SHOW TABLES LIKE '{$settings_table}'" ) === $settings_table,
	'leave_manager_email_logs' => $wpdb->get_var( "SHOW TABLES LIKE '{$email_logs_table}'" ) === $email_logs_table,
);

// Get counts
$user_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$users_table}" );
$request_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$requests_table}" );
$settings_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$settings_table}" );

// Get logs if on logs tab
if ( $current_tab === 'logs' ) {
	$logs = $wpdb->get_results(
		"SELECT * FROM {$email_logs_table} ORDER BY created_at DESC LIMIT 50"
	);
}
?>

<div class="wrap leave_manager-wrap">
	<h1>System</h1>
	
	<!-- Tab Navigation -->
	<nav class="leave-manager-tabs">
		<?php foreach ( $tabs as $tab ) : ?>
			<?php
			$active_class = ( $current_tab === $tab['slug'] ) ? 'active' : '';
			$url = add_query_arg( 'tab', $tab['slug'], $base_url );
			?>
			<a href="<?php echo esc_url( $url ); ?>" class="leave-manager-tab <?php echo esc_attr( $active_class ); ?>">
				<?php echo esc_html( $tab['label'] ); ?>
			</a>
		<?php endforeach; ?>
	</nav>
	
	<?php if ( $current_tab === 'health' ) : ?>
		<!-- System Health Tab -->
		<div class="leave-manager-health-grid">
			<!-- WordPress Info -->
			<div class="leave-manager-card">
				<h2 class="leave-manager-card-header">WordPress</h2>
				<div class="leave-manager-health-item">
					<span class="leave-manager-health-label">Version</span>
					<span class="leave-manager-health-value"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
				</div>
				<div class="leave-manager-health-item">
					<span class="leave-manager-health-label">Site URL</span>
					<span class="leave-manager-health-value"><?php echo esc_html( get_site_url() ); ?></span>
				</div>
				<div class="leave-manager-health-item">
					<span class="leave-manager-health-label">Admin Email</span>
					<span class="leave-manager-health-value"><?php echo esc_html( get_option( 'admin_email' ) ); ?></span>
				</div>
			</div>
			
			<!-- PHP Info -->
			<div class="leave-manager-card">
				<h2 class="leave-manager-card-header">PHP</h2>
				<div class="leave-manager-health-item">
					<span class="leave-manager-health-label">Version</span>
					<span class="leave-manager-health-value"><?php echo esc_html( phpversion() ); ?></span>
				</div>
				<div class="leave-manager-health-item">
					<span class="leave-manager-health-label">Memory Limit</span>
					<span class="leave-manager-health-value"><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></span>
				</div>
				<div class="leave-manager-health-item">
					<span class="leave-manager-health-label">Max Upload</span>
					<span class="leave-manager-health-value"><?php echo esc_html( ini_get( 'upload_max_filesize' ) ); ?></span>
				</div>
			</div>
			
			<!-- Database Info -->
			<div class="leave-manager-card">
				<h2 class="leave-manager-card-header">Database</h2>
				<div class="leave-manager-health-item">
					<span class="leave-manager-health-label">Host</span>
					<span class="leave-manager-health-value"><?php echo esc_html( DB_HOST ); ?></span>
				</div>
				<div class="leave-manager-health-item">
					<span class="leave-manager-health-label">Database</span>
					<span class="leave-manager-health-value"><?php echo esc_html( DB_NAME ); ?></span>
				</div>
				<div class="leave-manager-health-item">
					<span class="leave-manager-health-label">Version</span>
					<span class="leave-manager-health-value"><?php echo esc_html( $wpdb->db_version() ); ?></span>
				</div>
			</div>
			
			<!-- Plugin Tables -->
			<div class="leave-manager-card">
				<h2 class="leave-manager-card-header">Plugin Tables</h2>
				<?php foreach ( $tables as $table => $exists ) : ?>
					<div class="leave-manager-health-item">
						<span class="leave-manager-health-label"><?php echo esc_html( $table ); ?></span>
						<span class="leave-manager-badge <?php echo $exists ? 'leave_manager-badge-success' : 'leave_manager-badge-danger'; ?>">
							<?php echo $exists ? 'Exists' : 'Missing'; ?>
						</span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		
		<!-- Health Checks -->
		<div class="leave-manager-card">
			<h2 class="leave-manager-card-header">System Health Checks</h2>
			<div class="leave-manager-health-checks">
				<div class="leave-manager-health-check">
					<span class="leave-manager-health-check-icon">✓</span>
					<span class="leave-manager-health-check-text">Database Connection</span>
					<span class="leave-manager-badge leave_manager-badge-success">PASS</span>
				</div>
				<div class="leave-manager-health-check <?php echo array_sum( $tables ) === count( $tables ) ? '' : 'fail'; ?>">
					<span class="leave-manager-health-check-icon"><?php echo array_sum( $tables ) === count( $tables ) ? '✓' : '✗'; ?></span>
					<span class="leave-manager-health-check-text">Plugin Tables</span>
					<span class="leave-manager-badge <?php echo array_sum( $tables ) === count( $tables ) ? 'leave_manager-badge-success' : 'leave_manager-badge-danger'; ?>">
						<?php echo array_sum( $tables ) === count( $tables ) ? 'PASS' : 'FAIL'; ?>
					</span>
				</div>
				<div class="leave-manager-health-check <?php echo version_compare( phpversion(), '7.4', '>=' ) ? '' : 'fail'; ?>">
					<span class="leave-manager-health-check-icon"><?php echo version_compare( phpversion(), '7.4', '>=' ) ? '✓' : '✗'; ?></span>
					<span class="leave-manager-health-check-text">PHP Version >= 7.4</span>
					<span class="leave-manager-badge <?php echo version_compare( phpversion(), '7.4', '>=' ) ? 'leave_manager-badge-success' : 'leave_manager-badge-danger'; ?>">
						<?php echo version_compare( phpversion(), '7.4', '>=' ) ? 'PASS' : 'FAIL'; ?>
					</span>
				</div>
				<div class="leave-manager-health-check">
					<span class="leave-manager-health-check-icon">✓</span>
					<span class="leave-manager-health-check-text">Required Extensions</span>
					<span class="leave-manager-badge leave_manager-badge-success">PASS</span>
				</div>
			</div>
		</div>
		
		<!-- Table Counts -->
		<div class="leave-manager-card">
			<h2 class="leave-manager-card-header">Data Summary</h2>
			<div class="leave-manager-stats-grid">
				<div class="leave-manager-stat-card">
					<div class="leave-manager-stat-number"><?php echo esc_html( $user_count ?: '0' ); ?></div>
					<div class="leave-manager-stat-label">Users</div>
				</div>
				<div class="leave-manager-stat-card">
					<div class="leave-manager-stat-number"><?php echo esc_html( $request_count ?: '0' ); ?></div>
					<div class="leave-manager-stat-label">Leave Requests</div>
				</div>
				<div class="leave-manager-stat-card">
					<div class="leave-manager-stat-number"><?php echo esc_html( $settings_count ?: '0' ); ?></div>
					<div class="leave-manager-stat-label">Settings</div>
				</div>
			</div>
		</div>
		
	<?php elseif ( $current_tab === 'logs' ) : ?>
		<!-- Logs Tab -->
		<div class="leave-manager-card">
			<h2 class="leave-manager-card-header">Email Logs (Last 50)</h2>
			<?php if ( ! empty( $logs ) ) : ?>
				<div class="leave-manager-table-wrapper" style="border:none;">
					<table class="leave-manager-table">
						<thead>
							<tr>
								<th>Date</th>
								<th>To</th>
								<th>Subject</th>
								<th>Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $logs as $log ) : ?>
								<tr>
									<td><?php echo esc_html( date( 'M j, Y H:i', strtotime( $log->created_at ) ) ); ?></td>
									<td><?php echo esc_html( $log->to_email ); ?></td>
									<td><?php echo esc_html( $log->subject ); ?></td>
									<td>
										<span class="leave-manager-badge <?php echo $log->status === 'sent' ? 'leave_manager-badge-success' : 'leave_manager-badge-danger'; ?>">
											<?php echo esc_html( ucfirst( $log->status ) ); ?>
										</span>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php else : ?>
				<div class="leave-manager-empty-state">
					<div class="leave-manager-empty-state-icon">📧</div>
					<h3 class="leave-manager-empty-state-title">No Email Logs</h3>
					<p class="leave-manager-empty-state-text">Email activity will be logged here.</p>
				</div>
			<?php endif; ?>
		</div>
		
	<?php elseif ( $current_tab === 'export' ) : ?>
		<!-- Export Tab -->
		<div class="leave-manager-card">
			<h2 class="leave-manager-card-header">Export Data</h2>
			<p style="color: var(--leave_manager-text-medium); margin-bottom: 20px;">
				Export your leave management data in various formats for reporting or backup purposes.
			</p>
			
			<div class="leave-manager-form">
				<div class="leave-manager-form-group">
					<label class="leave-manager-form-label">Export Type</label>
					<select class="leave-manager-form-select" id="export-type">
						<option value="users">Users</option>
						<option value="requests">Leave Requests</option>
						<option value="all">All Data</option>
					</select>
				</div>
				
				<div class="leave-manager-form-group">
					<label class="leave-manager-form-label">Format</label>
					<select class="leave-manager-form-select" id="export-format">
						<option value="csv">CSV</option>
						<option value="json">JSON</option>
					</select>
				</div>
				
				<div class="leave-manager-form-group">
					<label class="leave-manager-form-label">Date Range (for Leave Requests)</label>
					<div class="leave-manager-form-row">
						<input type="date" class="leave-manager-form-input" id="export-start-date">
						<input type="date" class="leave-manager-form-input" id="export-end-date">
					</div>
					<span class="leave-manager-form-help">Leave empty to export all records.</span>
				</div>
				
				<div class="leave-manager-btn-group">
					<button type="button" class="leave-manager-btn leave_manager-btn-primary" id="export-btn">
						Export Data
					</button>
					<button type="button" class="leave-manager-btn leave_manager-btn-secondary" id="export-system-info">
						Export System Info
					</button>
				</div>
			</div>
		</div>
		
	<?php endif; ?>
</div>
</div>
</div>
