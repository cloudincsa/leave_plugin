<?php
/**
 * Requests Page - Leave Manager v3.0
 * Tabs: All | Pending | Approved | Rejected
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Get current tab
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'all';
$base_url = admin_url( 'admin.php?page=leave-manager-requests' );

// Define tabs
$tabs = array(
	array( 'slug' => 'all', 'label' => 'All Requests' ),
	array( 'slug' => 'pending', 'label' => 'Pending' ),
	array( 'slug' => 'approved', 'label' => 'Approved' ),
	array( 'slug' => 'rejected', 'label' => 'Rejected' ),
);

// Build query based on tab
$users_table = $wpdb->prefix . 'leave_manager_leave_users';
$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

$where_clause = '';
if ( $current_tab !== 'all' ) {
	$where_clause = $wpdb->prepare( "WHERE r.status = %s", $current_tab );
}

$requests = $wpdb->get_results(
	"SELECT r.*, u.first_name, u.last_name, u.email 
	FROM {$requests_table} r 
	LEFT JOIN {$users_table} u ON r.user_id = u.user_id 
	{$where_clause}
	ORDER BY r.created_at DESC"
);

// Get counts for badges
$counts = array(
	'all' => $wpdb->get_var( "SELECT COUNT(*) FROM {$requests_table}" ),
	'pending' => $wpdb->get_var( "SELECT COUNT(*) FROM {$requests_table} WHERE status = 'pending'" ),
	'approved' => $wpdb->get_var( "SELECT COUNT(*) FROM {$requests_table} WHERE status = 'approved'" ),
	'rejected' => $wpdb->get_var( "SELECT COUNT(*) FROM {$requests_table} WHERE status = 'rejected'" ),
);
?>

<div class="wrap leave_manager-wrap">
	<h1>Leave Requests</h1>
	
	<!-- Tab Navigation -->
	<nav class="leave-manager-tabs">
		<?php foreach ( $tabs as $tab ) : ?>
			<?php
			$active_class = ( $current_tab === $tab['slug'] ) ? 'active' : '';
			$url = ( $tab['slug'] === 'all' ) ? $base_url : add_query_arg( 'tab', $tab['slug'], $base_url );
			$count = $counts[ $tab['slug'] ] ?: 0;
			?>
			<a href="<?php echo esc_url( $url ); ?>" class="leave-manager-tab <?php echo esc_attr( $active_class ); ?>">
				<?php echo esc_html( $tab['label'] ); ?>
				<span class="leave-manager-badge leave_manager-badge-secondary" style="margin-left:6px;"><?php echo esc_html( $count ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>
	
	<!-- Requests Table -->
	<div class="leave-manager-card">
		<?php if ( ! empty( $requests ) ) : ?>
			<div class="leave-manager-table-wrapper">
				<table class="leave-manager-table">
					<thead>
						<tr>
							<th>Employee</th>
							<th>Leave Type</th>
							<th>Start Date</th>
							<th>End Date</th>
							<th>Days</th>
							<th>Status</th>
							<th>Submitted</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $requests as $request ) : ?>
							<?php
							$start = new DateTime( $request->start_date );
							$end = new DateTime( $request->end_date );
							$days = $start->diff( $end )->days + 1;
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $request->first_name . ' ' . $request->last_name ); ?></strong>
									<br>
									<small style="color:var(--leave_manager-text-light);"><?php echo esc_html( $request->email ); ?></small>
								</td>
								<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $request->leave_type ) ) ); ?></td>
								<td><?php echo esc_html( date( 'M j, Y', strtotime( $request->start_date ) ) ); ?></td>
								<td><?php echo esc_html( date( 'M j, Y', strtotime( $request->end_date ) ) ); ?></td>
								<td><?php echo esc_html( $days ); ?></td>
								<td>
									<?php
									$status_class = 'secondary';
									if ( $request->status === 'approved' ) $status_class = 'success';
									if ( $request->status === 'pending' ) $status_class = 'warning';
									if ( $request->status === 'rejected' ) $status_class = 'danger';
									?>
									<span class="leave-manager-badge leave_manager-badge-<?php echo esc_attr( $status_class ); ?>">
										<?php echo esc_html( ucfirst( $request->status ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( date( 'M j, Y', strtotime( $request->created_at ) ) ); ?></td>
								<td>
									<div class="leave-manager-table-actions">
										<?php if ( $request->status === 'pending' ) : ?>
											<button class="leave-manager-btn leave_manager-btn-sm leave_manager-btn-success" data-action="approve" data-id="<?php echo esc_attr( $request->id ); ?>">
												Approve
											</button>
											<button class="leave-manager-btn leave_manager-btn-sm leave_manager-btn-danger" data-action="reject" data-id="<?php echo esc_attr( $request->id ); ?>">
												Reject
											</button>
										<?php else : ?>
											<button class="leave-manager-btn leave_manager-btn-sm leave_manager-btn-secondary" data-action="view" data-id="<?php echo esc_attr( $request->id ); ?>">
												View
											</button>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php else : ?>
			<div class="leave-manager-empty-state">
				<div class="leave-manager-empty-state-icon">📋</div>
				<h3 class="leave-manager-empty-state-title">No <?php echo esc_html( $current_tab === 'all' ? '' : ucfirst( $current_tab ) . ' ' ); ?>Requests</h3>
				<p class="leave-manager-empty-state-text">
					<?php if ( $current_tab === 'pending' ) : ?>
						There are no pending leave requests to review.
					<?php elseif ( $current_tab === 'approved' ) : ?>
						No leave requests have been approved yet.
					<?php elseif ( $current_tab === 'rejected' ) : ?>
						No leave requests have been rejected.
					<?php else : ?>
						When employees submit leave requests, they will appear here.
					<?php endif; ?>
				</p>
			</div>
		<?php endif; ?>
	</div>
</div>
