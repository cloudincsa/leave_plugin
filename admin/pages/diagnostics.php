<?php
/**
 * Diagnostics Page
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized' );
}

// Get instances
$db = new Leave_Manager_Database();
$logger = new Leave_Manager_Logger();
$settings = new Leave_Manager_Settings( $db );

// Collect diagnostics information
$diagnostics = array();

// WordPress Information
$diagnostics['wordpress'] = array(
	'Version' => get_bloginfo( 'version' ),
	'Site URL' => get_bloginfo( 'url' ),
	'Admin Email' => get_option( 'admin_email' ),
	'Timezone' => get_option( 'timezone_string' ),
);

// PHP Information
$diagnostics['php'] = array(
	'Version' => phpversion(),
	'Memory Limit' => ini_get( 'memory_limit' ),
	'Max Upload Size' => size_format( wp_max_upload_size() ),
	'Execution Time' => ini_get( 'max_execution_time' ) . 's',
);

// Database Information
global $wpdb;
$diagnostics['database'] = array(
	'Host' => DB_HOST,
	'Database' => DB_NAME,
	'Charset' => $wpdb->charset,
	'Collation' => $wpdb->collate,
	'Version' => $wpdb->db_version(),
);

// Plugin Tables
$tables = array(
	$db->users_table,
	$db->leave_requests_table,
	$db->email_logs_table,
	$db->settings_table,
);

$diagnostics['plugin_tables'] = array();
foreach ( $tables as $table ) {
	$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	$diagnostics['plugin_tables'][ $table ] = $exists ? 'Exists' : 'Missing';
}

// Table Row Counts
$diagnostics['table_counts'] = array(
	'Users' => $wpdb->get_var( "SELECT COUNT(*) FROM {$db->users_table}" ),
	'Leave Requests' => $wpdb->get_var( "SELECT COUNT(*) FROM {$db->leave_requests_table}" ),
	'Email Logs' => $wpdb->get_var( "SELECT COUNT(*) FROM {$db->email_logs_table}" ),
	'Settings' => $wpdb->get_var( "SELECT COUNT(*) FROM {$db->settings_table}" ),
);

// Plugin Settings
$diagnostics['plugin_settings'] = array(
	'Organization Name' => $settings->get( 'organization_name' ) ?: 'Not set',
	'Annual Leave Days' => $settings->get( 'annual_leave_days' ) ?: 'Not set',
	'Sick Leave Days' => $settings->get( 'sick_leave_days' ) ?: 'Not set',
	'Email Notifications' => $settings->get( 'send_notifications' ) ? 'Enabled' : 'Disabled',
);

// Check for required extensions
$extensions = array(
	'mysqli' => extension_loaded( 'mysqli' ),
	'curl' => extension_loaded( 'curl' ),
	'json' => extension_loaded( 'json' ),
	'mbstring' => extension_loaded( 'mbstring' ),
);

$diagnostics['php_extensions'] = array();
foreach ( $extensions as $ext => $loaded ) {
	$diagnostics['php_extensions'][ ucfirst( $ext ) ] = $loaded ? 'Loaded' : 'Not Loaded';
}

// Check file permissions
$plugin_dir = LEAVE_MANAGER_PLUGIN_DIR;
$diagnostics['file_permissions'] = array(
	'Plugin Directory' => is_writable( $plugin_dir ) ? 'Writable' : 'Read-only',
	'Uploads Directory' => is_writable( wp_upload_dir()['basedir'] ) ? 'Writable' : 'Read-only',
);
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="diagnostics-container">
		<?php foreach ( $diagnostics as $section_title => $section_data ) : ?>
			<div class="diagnostics-section">
				<h2><?php echo esc_html( str_replace( '_', ' ', ucfirst( $section_title ) ) ); ?></h2>
				
				<table class="widefat">
					<tbody>
						<?php foreach ( $section_data as $key => $value ) : ?>
							<tr>
								<td style="width: 40%; font-weight: 600;"><?php echo esc_html( $key ); ?></td>
								<td>
									<?php
									if ( is_array( $value ) ) {
										echo esc_html( implode( ', ', $value ) );
									} else {
										echo esc_html( $value );
									}
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endforeach; ?>

		<div class="diagnostics-section">
			<h2>System Health Check</h2>
			
			<div class="health-checks">
				<?php
				$checks = array(
					'Database Connection' => $wpdb->get_var( 'SELECT 1' ) === '1',
					'Plugin Tables Exist' => ! in_array( 'Missing', $diagnostics['plugin_tables'], true ),
					'PHP Version >= 7.4' => version_compare( phpversion(), '7.4', '>=' ),
					'MySQL Version >= 5.7' => version_compare( $wpdb->db_version(), '5.7', '>=' ),
					'Required Extensions' => ! in_array( 'Not Loaded', $diagnostics['php_extensions'], true ),
				);

				foreach ( $checks as $check_name => $check_result ) {
					$status_class = $check_result ? 'success' : 'error';
					$status_text = $check_result ? 'PASS' : 'FAIL';
					?>
					<div class="health-check health-check-<?php echo esc_attr( $status_class ); ?>">
						<span class="status-indicator"></span>
						<span class="check-name"><?php echo esc_html( $check_name ); ?></span>
						<span class="status-text"><?php echo esc_html( $status_text ); ?></span>
					</div>
					<?php
				}
				?>
			</div>
		</div>

		<div class="diagnostics-section">
			<h2>System Information Export</h2>
			<p>You can export this diagnostic information for support purposes.</p>
			<form method="post" action="">
				<button type="button" class="button button-secondary" onclick="exportDiagnostics()">Export as JSON</button>
			</form>
		</div>
	</div>
</div>

<style>
	.diagnostics-container {
		margin-top: 20px;
	}

	.diagnostics-section {
		background: white;
		border: 1px solid #ccc;
		border-radius: 5px;
		padding: 20px;
		margin-bottom: 20px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.diagnostics-section h2 {
		margin-top: 0;
		border-bottom: 2px solid #0073aa;
		padding-bottom: 10px;
		color: #0073aa;
	}

	.widefat {
		margin-top: 15px;
	}

	.widefat td {
		padding: 10px;
		border-bottom: 1px solid #eee;
	}

	.health-checks {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		gap: 15px;
		margin-top: 15px;
	}

	.health-check {
		padding: 15px;
		border-radius: 4px;
		display: flex;
		align-items: center;
		gap: 10px;
	}

	.health-check-success {
		background-color: #d4edda;
		border: 1px solid #c3e6cb;
	}

	.health-check-error {
		background-color: #f8d7da;
		border: 1px solid #f5c6cb;
	}

	.status-indicator {
		display: inline-block;
		width: 12px;
		height: 12px;
		border-radius: 50%;
	}

	.health-check-success .status-indicator {
		background-color: #28a745;
	}

	.health-check-error .status-indicator {
		background-color: #dc3545;
	}

	.check-name {
		flex-grow: 1;
		font-weight: 600;
	}

	.health-check-success .check-name {
		color: #155724;
	}

	.health-check-error .check-name {
		color: #721c24;
	}

	.status-text {
		font-weight: 600;
		font-size: 12px;
	}

	.health-check-success .status-text {
		color: #155724;
	}

	.health-check-error .status-text {
		color: #721c24;
	}
</style>

<script>
	function exportDiagnostics() {
		var diagnostics = <?php echo wp_json_encode( $diagnostics ); ?>;
		var dataStr = JSON.stringify( diagnostics, null, 2 );
		var dataBlob = new Blob( [ dataStr ], { type: 'application/json' } );
		var url = URL.createObjectURL( dataBlob );
		var link = document.createElement( 'a' );
		link.href = url;
		link.download = 'leave-manager-diagnostics-' + new Date().toISOString().split( 'T' )[0] + '.json';
		document.body.appendChild( link );
		link.click();
		document.body.removeChild( link );
	}
</script>
