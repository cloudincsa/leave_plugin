<?php
/**
 * Frontend Leave History Page
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get instances
$db = new Leave_Manager_Database();
$logger = new Leave_Manager_Logger();

// Get current user
$current_user_id = get_current_user_id();
if ( empty( $current_user_id ) ) {
	wp_die( 'You must be logged in to view this page.' );
}

// Get user data
global $wpdb;
$users_table = $db->users_table;
$requests_table = $db->leave_requests_table;

$user = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT * FROM $users_table WHERE wp_user_id = %d",
		intval( $current_user_id )
	)
);

if ( ! $user ) {
	echo '<p>User information not found.</p>';
	return;
}

// Get pagination
$page = isset( $_GET['paged'] ) ? intval( $_GET['paged'] ) : 1;
$per_page = 10;
$offset = ( $page - 1 ) * $per_page;

// Get total requests
$total = $wpdb->get_var(
	$wpdb->prepare(
		"SELECT COUNT(*) FROM $requests_table WHERE user_id = %d",
		intval( $user->user_id )
	)
);

// Get requests
$requests = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM $requests_table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
		intval( $user->user_id ),
		intval( $per_page ),
		intval( $offset )
	)
);

// Calculate pagination
$total_pages = ceil( $total / $per_page );
?>

<div class="leave-manager-history">
	<h2>Leave Request History</h2>

	<?php if ( ! empty( $requests ) ) : ?>
		<table class="history-table">
			<thead>
				<tr>
					<th>Leave Type</th>
					<th>Start Date</th>
					<th>End Date</th>
					<th>Days</th>
					<th>Status</th>
					<th>Submitted</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $requests as $request ) : ?>
					<?php
					$calendar = new Leave_Manager_Calendar( $db, $logger );
					$days = $calendar->calculate_leave_days( $request->start_date, $request->end_date );
					?>
					<tr>
						<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $request->leave_type ) ) ); ?></td>
						<td><?php echo esc_html( date_i18n( 'M d, Y', strtotime( $request->start_date ) ) ); ?></td>
						<td><?php echo esc_html( date_i18n( 'M d, Y', strtotime( $request->end_date ) ) ); ?></td>
						<td><?php echo esc_html( $days ); ?></td>
						<td>
							<span class="status-badge status-<?php echo esc_attr( $request->status ); ?>">
								<?php echo esc_html( ucfirst( $request->status ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( date_i18n( 'M d, Y', strtotime( $request->created_at ) ) ); ?></td>
						<td>
							<?php if ( 'pending' === $request->status ) : ?>
								<a href="#" class="button button-small button-secondary" onclick="return confirm('Cancel this request?')">Cancel</a>
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
					if ( $i === $page ) {
						echo '<span class="page-number current">' . esc_html( $i ) . '</span>';
					} else {
						$url = add_query_arg( 'paged', $i );
						echo '<a href="' . esc_url( $url ) . '" class="page-number">' . esc_html( $i ) . '</a>';
					}
				}
				?>
			</div>
		<?php endif; ?>
	<?php else : ?>
		<p class="no-data">No leave requests found.</p>
	<?php endif; ?>
</div>

<style>
	.leave-manager-leave-history {
		background: white;
		border: 1px solid #ddd;
		border-radius: 5px;
		padding: 20px;
		margin: 20px 0;
	}

	.leave-manager-leave-history h2 {
		color: #0073aa;
		border-bottom: 2px solid #0073aa;
		padding-bottom: 10px;
	}

	.history-table {
		width: 100%;
		border-collapse: collapse;
		margin-top: 20px;
	}

	.history-table thead {
		background-color: #f5f5f5;
	}

	.history-table th {
		padding: 12px;
		text-align: left;
		font-weight: 600;
		color: #333;
		border-bottom: 2px solid #0073aa;
	}

	.history-table td {
		padding: 12px;
		border-bottom: 1px solid #ddd;
	}

	.history-table tbody tr:hover {
		background-color: #f9f9f9;
	}

	.status-badge {
		display: inline-block;
		padding: 5px 10px;
		border-radius: 3px;
		font-size: 12px;
		font-weight: 600;
	}

	.status-badge.status-pending {
		background-color: #fff3cd;
		color: #856404;
	}

	.status-badge.status-approved {
		background-color: #d4edda;
		color: #155724;
	}

	.status-badge.status-rejected {
		background-color: #f8d7da;
		color: #721c24;
	}

	.pagination {
		margin-top: 20px;
		text-align: center;
	}

	.page-number {
		display: inline-block;
		padding: 8px 12px;
		margin: 0 3px;
		border: 1px solid #ddd;
		border-radius: 3px;
		text-decoration: none;
		color: #0073aa;
	}

	.page-number:hover {
		background-color: #f5f5f5;
	}

	.page-number.current {
		background-color: #0073aa;
		color: white;
		border-color: #0073aa;
	}

	.no-data {
		color: #666;
		font-size: 14px;
		padding: 20px;
		text-align: center;
	}

	.button-small {
		padding: 5px 10px;
		font-size: 12px;
	}
</style>
