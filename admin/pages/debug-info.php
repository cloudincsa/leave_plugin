<?php
/**
 * Debug Information Page - NO PERMISSION CHECKS
 * 
 * This page outputs diagnostic information to help debug permission issues.
 * Access via: /wp-admin/admin.php?page=leave-manager-debug
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include debug logger
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-debug-logger.php';

// Log this access
Leave_Manager_Debug_Logger::full_diagnostic( 'debug-info.php page access' );

// Get current user
$current_user = wp_get_current_user();

// Get database info
global $wpdb;

// Check tables
$tables_to_check = array(
	'leave_manager_leave_users',
	'leave_manager_leave_requests',
	'leave_manager_leave_policies',
	'leave_manager_email_queue',
	'leave_manager_settings',
	'leave_manager_email_logs',
	'leave_manager_employee_signups',
	'leave_manager_webhooks',
	'leave_manager_two_factor_auth',
	'leave_manager_leave_balances',
	'leave_manager_leave_types',
	'leave_manager_departments',
	'leave_manager_audit_logs',
	'leave_manager_sms_logs',
	'leave_manager_rate_limits',
	'leave_manager_request_history',
	'leave_manager_policy_assignments',
);

$table_status = array();
foreach ( $tables_to_check as $table ) {
	$full_table = $wpdb->prefix . $table;
	$exists = $wpdb->get_var( "SHOW TABLES LIKE '$full_table'" ) === $full_table;
	$table_status[ $table ] = $exists;
}

// Check if setup is needed
$needs_setup = false;
$required_tables = array( 'leave_manager_leave_users', 'leave_manager_leave_requests', 'leave_manager_leave_policies', 'leave_manager_email_queue', 'leave_manager_settings' );
foreach ( $required_tables as $table ) {
	if ( ! $table_status[ $table ] ) {
		$needs_setup = true;
		break;
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<title>Leave Manager - Debug Information</title>
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
			background: #f1f1f1;
			padding: 20px;
			margin: 0;
		}
		.debug-container {
			max-width: 1200px;
			margin: 0 auto;
			background: white;
			padding: 30px;
			border-radius: 5px;
			box-shadow: 0 1px 3px rgba(0,0,0,0.1);
		}
		h1 {
			color: #23282d;
			border-bottom: 2px solid #0073aa;
			padding-bottom: 10px;
		}
		h2 {
			color: #23282d;
			margin-top: 30px;
			border-bottom: 1px solid #ddd;
			padding-bottom: 5px;
		}
		table {
			width: 100%;
			border-collapse: collapse;
			margin: 15px 0;
		}
		th, td {
			padding: 12px;
			text-align: left;
			border: 1px solid #ddd;
		}
		th {
			background: #f7f7f7;
			font-weight: 600;
		}
		.status-ok {
			color: #46b450;
			font-weight: bold;
		}
		.status-error {
			color: #dc3232;
			font-weight: bold;
		}
		.status-warning {
			color: #ffb900;
			font-weight: bold;
		}
		.capability-list {
			background: #f7f7f7;
			padding: 15px;
			border-radius: 3px;
			max-height: 300px;
			overflow-y: auto;
			font-family: monospace;
			font-size: 12px;
		}
		.log-output {
			background: #1e1e1e;
			color: #d4d4d4;
			padding: 15px;
			border-radius: 3px;
			max-height: 400px;
			overflow-y: auto;
			font-family: monospace;
			font-size: 12px;
			white-space: pre-wrap;
		}
		.action-buttons {
			margin: 20px 0;
		}
		.action-buttons a {
			display: inline-block;
			padding: 10px 20px;
			background: #0073aa;
			color: white;
			text-decoration: none;
			border-radius: 3px;
			margin-right: 10px;
		}
		.action-buttons a:hover {
			background: #005a87;
		}
		.action-buttons a.danger {
			background: #dc3232;
		}
		.action-buttons a.danger:hover {
			background: #a00;
		}
		.summary-box {
			background: #f7f7f7;
			border-left: 4px solid #0073aa;
			padding: 15px;
			margin: 20px 0;
		}
		.summary-box.error {
			border-left-color: #dc3232;
			background: #fef7f7;
		}
		.summary-box.success {
			border-left-color: #46b450;
			background: #f7fef7;
		}
	</style>
</head>
<body>
	<div class="debug-container">
		<h1>Leave Manager - Debug Information</h1>
		
		<p><strong>Generated:</strong> <?php echo esc_html( date( 'Y-m-d H:i:s' ) ); ?></p>
		<p><strong>Plugin Version:</strong> <?php echo esc_html( defined( 'LEAVE_MANAGER_PLUGIN_VERSION' ) ? LEAVE_MANAGER_PLUGIN_VERSION : 'Unknown' ); ?></p>
		
		<!-- Summary -->
		<div class="summary-box <?php echo $needs_setup ? 'error' : 'success'; ?>">
			<strong>Setup Status:</strong> 
			<?php if ( $needs_setup ) : ?>
				<span class="status-error">SETUP REQUIRED - Some database tables are missing</span>
			<?php else : ?>
				<span class="status-ok">SETUP COMPLETE - All required tables exist</span>
			<?php endif; ?>
		</div>
		
		<!-- User Information -->
		<h2>Current User Information</h2>
		<table>
			<tr>
				<th>Property</th>
				<th>Value</th>
				<th>Status</th>
			</tr>
			<tr>
				<td>User ID</td>
				<td><?php echo esc_html( $current_user->ID ); ?></td>
				<td><?php echo $current_user->ID > 0 ? '<span class="status-ok">OK</span>' : '<span class="status-error">NOT LOGGED IN</span>'; ?></td>
			</tr>
			<tr>
				<td>Username</td>
				<td><?php echo esc_html( $current_user->user_login ); ?></td>
				<td>-</td>
			</tr>
			<tr>
				<td>Email</td>
				<td><?php echo esc_html( $current_user->user_email ); ?></td>
				<td>-</td>
			</tr>
			<tr>
				<td>Display Name</td>
				<td><?php echo esc_html( $current_user->display_name ); ?></td>
				<td>-</td>
			</tr>
			<tr>
				<td>Roles</td>
				<td><?php echo esc_html( implode( ', ', $current_user->roles ) ); ?></td>
				<td><?php echo in_array( 'administrator', $current_user->roles ) ? '<span class="status-ok">ADMIN</span>' : '<span class="status-warning">NOT ADMIN</span>'; ?></td>
			</tr>
			<tr>
				<td>is_user_logged_in()</td>
				<td><?php echo is_user_logged_in() ? 'TRUE' : 'FALSE'; ?></td>
				<td><?php echo is_user_logged_in() ? '<span class="status-ok">OK</span>' : '<span class="status-error">NOT LOGGED IN</span>'; ?></td>
			</tr>
			<tr>
				<td>current_user_can('manage_options')</td>
				<td><?php echo current_user_can( 'manage_options' ) ? 'TRUE' : 'FALSE'; ?></td>
				<td><?php echo current_user_can( 'manage_options' ) ? '<span class="status-ok">OK</span>' : '<span class="status-error">NO ACCESS</span>'; ?></td>
			</tr>
			<tr>
				<td>current_user_can('administrator')</td>
				<td><?php echo current_user_can( 'administrator' ) ? 'TRUE' : 'FALSE'; ?></td>
				<td><?php echo current_user_can( 'administrator' ) ? '<span class="status-ok">OK</span>' : '<span class="status-warning">NOT ADMIN</span>'; ?></td>
			</tr>
			<tr>
				<td>is_super_admin()</td>
				<td><?php echo is_super_admin() ? 'TRUE' : 'FALSE'; ?></td>
				<td>-</td>
			</tr>
			<tr>
				<td>current_user_can('view_all_leave_requests')</td>
				<td><?php echo current_user_can( 'view_all_leave_requests' ) ? 'TRUE' : 'FALSE'; ?></td>
				<td><span class="status-warning">Custom capability</span></td>
			</tr>
		</table>
		
		<!-- Permission Check Simulation -->
		<h2>Permission Check Simulation</h2>
		<table>
			<tr>
				<th>Check</th>
				<th>Code</th>
				<th>Result</th>
			</tr>
			<tr>
				<td>Dashboard Access (Current)</td>
				<td><code>current_user_can('manage_options')</code></td>
				<td><?php echo current_user_can( 'manage_options' ) ? '<span class="status-ok">WOULD ALLOW ACCESS</span>' : '<span class="status-error">WOULD DENY ACCESS</span>'; ?></td>
			</tr>
			<tr>
				<td>Dashboard Access (Old Logic)</td>
				<td><code>!current_user_can('view_all_leave_requests') && !current_user_can('manage_options')</code></td>
				<td><?php 
				$old_check = ! current_user_can( 'view_all_leave_requests' ) && ! current_user_can( 'manage_options' );
				echo $old_check ? '<span class="status-error">WOULD DENY ACCESS</span>' : '<span class="status-ok">WOULD ALLOW ACCESS</span>'; 
				?></td>
			</tr>
			<tr>
				<td>Menu Registration</td>
				<td><code>add_menu_page(..., 'manage_options', ...)</code></td>
				<td><?php echo current_user_can( 'manage_options' ) ? '<span class="status-ok">MENU VISIBLE</span>' : '<span class="status-error">MENU HIDDEN</span>'; ?></td>
			</tr>
		</table>
		
		<!-- Database Tables -->
		<h2>Database Tables Status</h2>
		<table>
			<tr>
				<th>Table Name</th>
				<th>Full Name</th>
				<th>Status</th>
			</tr>
			<?php foreach ( $table_status as $table => $exists ) : ?>
			<tr>
				<td><?php echo esc_html( $table ); ?></td>
				<td><?php echo esc_html( $wpdb->prefix . $table ); ?></td>
				<td><?php echo $exists ? '<span class="status-ok">EXISTS</span>' : '<span class="status-error">MISSING</span>'; ?></td>
			</tr>
			<?php endforeach; ?>
		</table>
		
		<!-- WordPress Environment -->
		<h2>WordPress Environment</h2>
		<table>
			<tr>
				<th>Property</th>
				<th>Value</th>
			</tr>
			<tr>
				<td>WordPress Version</td>
				<td><?php echo esc_html( get_bloginfo( 'version' ) ); ?></td>
			</tr>
			<tr>
				<td>PHP Version</td>
				<td><?php echo esc_html( phpversion() ); ?></td>
			</tr>
			<tr>
				<td>MySQL Version</td>
				<td><?php echo esc_html( $wpdb->db_version() ); ?></td>
			</tr>
			<tr>
				<td>Site URL</td>
				<td><?php echo esc_html( get_site_url() ); ?></td>
			</tr>
			<tr>
				<td>Admin URL</td>
				<td><?php echo esc_html( admin_url() ); ?></td>
			</tr>
			<tr>
				<td>Is Multisite</td>
				<td><?php echo is_multisite() ? 'YES' : 'NO'; ?></td>
			</tr>
			<tr>
				<td>Is Admin</td>
				<td><?php echo is_admin() ? 'YES' : 'NO'; ?></td>
			</tr>
			<tr>
				<td>WP_DEBUG</td>
				<td><?php echo defined( 'WP_DEBUG' ) && WP_DEBUG ? 'ENABLED' : 'DISABLED'; ?></td>
			</tr>
			<tr>
				<td>WP_DEBUG_LOG</td>
				<td><?php echo defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? 'ENABLED' : 'DISABLED'; ?></td>
			</tr>
		</table>
		
		<!-- User Capabilities -->
		<h2>All User Capabilities</h2>
		<div class="capability-list">
			<?php
			if ( $current_user->ID > 0 ) {
				$caps = array();
				foreach ( $current_user->allcaps as $cap => $granted ) {
					if ( $granted ) {
						$caps[] = $cap;
					}
				}
				sort( $caps );
				echo esc_html( implode( "\n", $caps ) );
			} else {
				echo 'No user logged in - no capabilities to display';
			}
			?>
		</div>
		
		<!-- Debug Log -->
		<h2>Debug Log (Last 50 Lines)</h2>
		<div class="log-output"><?php 
			$log_content = Leave_Manager_Debug_Logger::get_log( 50 );
			echo esc_html( $log_content );
		?></div>
		
		<!-- Action Buttons -->
		<h2>Actions</h2>
		<div class="action-buttons">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-management' ) ); ?>">Go to Dashboard</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-setup' ) ); ?>">Go to Setup Wizard</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-debug&action=clear_log' ) ); ?>" class="danger">Clear Debug Log</a>
			<a href="<?php echo esc_url( admin_url( 'index.php' ) ); ?>">Go to WP Dashboard</a>
		</div>
		
		<!-- Raw PHP Info -->
		<h2>Request Information</h2>
		<table>
			<tr>
				<th>Property</th>
				<th>Value</th>
			</tr>
			<tr>
				<td>REQUEST_URI</td>
				<td><?php echo esc_html( isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : 'N/A' ); ?></td>
			</tr>
			<tr>
				<td>$_GET['page']</td>
				<td><?php echo esc_html( isset( $_GET['page'] ) ? $_GET['page'] : 'N/A' ); ?></td>
			</tr>
			<tr>
				<td>SCRIPT_NAME</td>
				<td><?php echo esc_html( isset( $_SERVER['SCRIPT_NAME'] ) ? $_SERVER['SCRIPT_NAME'] : 'N/A' ); ?></td>
			</tr>
			<tr>
				<td>HTTP_HOST</td>
				<td><?php echo esc_html( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : 'N/A' ); ?></td>
			</tr>
		</table>
		
		<hr style="margin-top: 40px;">
		<p style="color: #666; font-size: 12px;">
			This debug page is provided to help diagnose permission issues with the Leave Manager plugin.
			If you continue to experience issues, please share this information with support.
		</p>
	</div>
</body>
</html>
<?php
// Handle clear log action
if ( isset( $_GET['action'] ) && $_GET['action'] === 'clear_log' ) {
	Leave_Manager_Debug_Logger::clear_log();
	echo '<script>window.location.href = "' . esc_url( admin_url( 'admin.php?page=leave-manager-debug' ) ) . '";</script>';
}
?>
