<?php
/**
 * System Logs Page
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
$logger = new Leave_Manager_Logger( $db );

// Handle clear logs action
$message = '';
if ( isset( $_POST['action'] ) && 'clear_logs' === $_POST['action'] ) {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_clear_logs' ) ) {
		wp_die( 'Security check failed.' );
	}
	
	global $wpdb;
	$wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}leave_manager_email_logs" );
	$message = 'Logs cleared successfully.';
}

// Get filter parameters
$log_level = isset( $_GET['level'] ) ? sanitize_text_field( $_GET['level'] ) : '';
$search = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : '';
$per_page = 50;
$paged = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
$offset = ( $paged - 1 ) * $per_page;

// Get logs from database
global $wpdb;
$logs_table = $wpdb->prefix . 'leave_manager_email_logs';

$query = $wpdb->prepare( "SELECT * FROM {$logs_table} WHERE 1=1" );
$count_query = $wpdb->prepare( "SELECT COUNT(*) FROM {$logs_table} WHERE 1=1" );

if ( ! empty( $search ) ) {
	$search_like = '%' . $wpdb->esc_like( $search ) . '%';
	$query = $wpdb->prepare( $query . " AND (subject LIKE %s OR recipient_email LIKE %s)", $search_like, $search_like );
	$count_query = $wpdb->prepare( $count_query . " AND (subject LIKE %s OR recipient_email LIKE %s)", $search_like, $search_like );
}

$query .= " ORDER BY created_at DESC LIMIT %d OFFSET %d";
$logs = $wpdb->get_results( $wpdb->prepare( $query, $per_page, $offset ) );
$total_logs = $wpdb->get_var( $count_query );
$total_pages = ceil( $total_logs / $per_page );
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<div class="logs-container">
		<div class="logs-header">
			<form method="get" class="logs-filter">
				<input type="hidden" name="page" value="leave-manager-logs">
				<input type="text" name="search" placeholder="Search logs..." value="<?php echo esc_attr( $search ); ?>">
				<button type="submit" class="button">Search</button>
			</form>

			<form method="post" class="clear-logs-form">
				<?php wp_nonce_field( 'leave_manager_clear_logs', 'nonce' ); ?>
				<input type="hidden" name="action" value="clear_logs">
				<button type="submit" class="button button-secondary" onclick="return confirm('Are you sure you want to clear all logs?');">Clear All Logs</button>
			</form>
		</div>

		<div class="logs-info">
			<p>Total Logs: <strong><?php echo esc_html( $total_logs ); ?></strong></p>
		</div>

		<?php if ( ! empty( $logs ) ) : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Date & Time</th>
						<th>Recipient</th>
						<th>Subject</th>
						<th>Template</th>
						<th>Status</th>
						<th>Error</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', strtotime( $log->created_at ) ) ); ?></td>
							<td><?php echo esc_html( $log->recipient_email ); ?></td>
							<td><?php echo esc_html( $log->subject ); ?></td>
							<td><?php echo esc_html( $log->template_used ); ?></td>
							<td>
								<span class="status-badge status-<?php echo esc_attr( $log->status ); ?>">
									<?php echo esc_html( ucfirst( $log->status ) ); ?>
								</span>
							</td>
							<td>
								<?php if ( ! empty( $log->error_message ) ) : ?>
									<span class="error-message" title="<?php echo esc_attr( $log->error_message ); ?>">
										<?php echo esc_html( substr( $log->error_message, 0, 50 ) ); ?>...
									</span>
								<?php else : ?>
									<span class="text-muted">-</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="pagination">
					<?php
					for ( $i = 1; $i <= $total_pages; $i++ ) {
						$url = add_query_arg( 'paged', $i );
						if ( ! empty( $search ) ) {
							$url = add_query_arg( 'search', $search, $url );
						}
						if ( $i === $paged ) {
							echo '<span class="page-numbers current">' . esc_html( $i ) . '</span>';
						} else {
							echo '<a class="page-numbers" href="' . esc_url( $url ) . '">' . esc_html( $i ) . '</a>';
						}
					}
					?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<p>No logs found.</p>
		<?php endif; ?>
	</div>
</div>

<style>
	.logs-container {
		margin-top: 20px;
	}

	.logs-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20px;
		background: white;
		padding: 15px;
		border: 1px solid #ccc;
		border-radius: 5px;
	}

	.logs-filter {
		display: flex;
		gap: 10px;
	}

	.logs-filter input {
		padding: 8px 12px;
		border: 1px solid #ddd;
		border-radius: 4px;
		min-width: 300px;
	}

	.logs-info {
		background: white;
		padding: 15px;
		border: 1px solid #ccc;
		border-radius: 5px;
		margin-bottom: 20px;
	}

	.status-badge {
		display: inline-block;
		padding: 5px 10px;
		border-radius: 3px;
		font-size: 12px;
		font-weight: 600;
	}

	.status-badge.status-sent {
		background-color: #d4edda;
		color: #155724;
	}

	.status-badge.status-failed {
		background-color: #f8d7da;
		color: #721c24;
	}

	.status-badge.status-pending {
		background-color: #fff3cd;
		color: #856404;
	}

	.error-message {
		color: #721c24;
		font-size: 12px;
		cursor: help;
	}

	.text-muted {
		color: #999;
	}

	.pagination {
		margin-top: 20px;
		text-align: center;
	}

	.page-numbers {
		display: inline-block;
		padding: 8px 12px;
		margin: 0 3px;
		border: 1px solid #ddd;
		border-radius: 4px;
		text-decoration: none;
		color: #0073aa;
	}

	.page-numbers:hover {
		background-color: #f5f5f5;
	}

	.page-numbers.current {
		background-color: #0073aa;
		color: white;
		border-color: #0073aa;
	}

	.clear-logs-form {
		margin-left: auto;
	}
</style>
