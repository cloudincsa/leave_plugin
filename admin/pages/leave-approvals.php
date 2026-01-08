<?php
/**
 * Leave Request Approvals Admin Page
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user permissions
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized access' );
}

global $wpdb;

// Get pending leave requests
$pending_requests = get_posts( array(
	'post_type' => 'leave_request',
	'posts_per_page' => -1,
	'meta_query' => array(
		array(
			'key' => 'status',
			'value' => 'pending',
			'compare' => '=',
		),
	),
	'orderby' => 'date',
	'order' => 'DESC',
) );

// Get approved leave requests
$approved_requests = get_posts( array(
	'post_type' => 'leave_request',
	'posts_per_page' => 20,
	'meta_query' => array(
		array(
			'key' => 'status',
			'value' => 'approved',
			'compare' => '=',
		),
	),
	'orderby' => 'date',
	'order' => 'DESC',
) );

// Get rejected leave requests
$rejected_requests = get_posts( array(
	'post_type' => 'leave_request',
	'posts_per_page' => 20,
	'meta_query' => array(
		array(
			'key' => 'status',
			'value' => 'rejected',
			'compare' => '=',
		),
	),
	'orderby' => 'date',
	'order' => 'DESC',
) );
?>

<div class="wrap">
	<h1>Leave Request Approvals</h1>

	<div class="leave-approvals-container" style="margin-top: 20px;">
		<!-- Pending Approvals Tab -->
		<div class="leave-approvals-section">
			<h2>Pending Approvals (<?php echo count( $pending_requests ); ?>)</h2>

			<?php if ( empty( $pending_requests ) ) : ?>
				<p style="color: #6b7280;">No pending leave requests.</p>
			<?php else : ?>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th>Employee</th>
							<th>Leave Type</th>
							<th>Start Date</th>
							<th>End Date</th>
							<th>Days</th>
							<th>Reason</th>
							<th>Submitted</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pending_requests as $request ) : ?>
							<?php
							$user = get_user_by( 'id', $request->post_author );
							$leave_type = get_post_meta( $request->ID, 'leave_type', true );
							$start_date = get_post_meta( $request->ID, 'start_date', true );
							$end_date = get_post_meta( $request->ID, 'end_date', true );
							$reason = $request->post_content;

							// Calculate days
							$start = strtotime( $start_date );
							$end = strtotime( $end_date );
							$days = floor( ( $end - $start ) / 86400 ) + 1;
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $user->display_name ); ?></strong><br>
									<small><?php echo esc_html( $user->user_email ); ?></small>
								</td>
								<td><?php echo esc_html( ucfirst( $leave_type ) ); ?></td>
								<td><?php echo esc_html( date( 'M d, Y', strtotime( $start_date ) ) ); ?></td>
								<td><?php echo esc_html( date( 'M d, Y', strtotime( $end_date ) ) ); ?></td>
								<td><?php echo intval( $days ); ?></td>
								<td>
									<details>
										<summary style="cursor: pointer; color: #667eea;">View</summary>
										<p style="margin-top: 10px; color: #6b7280;"><?php echo esc_html( $reason ); ?></p>
									</details>
								</td>
								<td><?php echo esc_html( date( 'M d, Y', strtotime( $request->post_date ) ) ); ?></td>
								<td>
									<button class="button button-primary approve-leave" data-request-id="<?php echo intval( $request->ID ); ?>">Approve</button>
									<button class="button button-secondary reject-leave" data-request-id="<?php echo intval( $request->ID ); ?>">Reject</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<!-- Approved Requests Tab -->
		<div class="leave-approvals-section" style="margin-top: 40px;">
			<h2>Recently Approved (<?php echo count( $approved_requests ); ?>)</h2>

			<?php if ( empty( $approved_requests ) ) : ?>
				<p style="color: #6b7280;">No approved leave requests.</p>
			<?php else : ?>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th>Employee</th>
							<th>Leave Type</th>
							<th>Start Date</th>
							<th>End Date</th>
							<th>Days</th>
							<th>Approved By</th>
							<th>Approved Date</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $approved_requests as $request ) : ?>
							<?php
							$user = get_user_by( 'id', $request->post_author );
							$leave_type = get_post_meta( $request->ID, 'leave_type', true );
							$start_date = get_post_meta( $request->ID, 'start_date', true );
							$end_date = get_post_meta( $request->ID, 'end_date', true );
							$approved_by = get_post_meta( $request->ID, 'approved_by', true );
							$approved_date = get_post_meta( $request->ID, 'approved_date', true );

							// Calculate days
							$start = strtotime( $start_date );
							$end = strtotime( $end_date );
							$days = floor( ( $end - $start ) / 86400 ) + 1;

							$approved_by_user = get_user_by( 'id', $approved_by );
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $user->display_name ); ?></strong><br>
									<small><?php echo esc_html( $user->user_email ); ?></small>
								</td>
								<td><?php echo esc_html( ucfirst( $leave_type ) ); ?></td>
								<td><?php echo esc_html( date( 'M d, Y', strtotime( $start_date ) ) ); ?></td>
								<td><?php echo esc_html( date( 'M d, Y', strtotime( $end_date ) ) ); ?></td>
								<td><?php echo intval( $days ); ?></td>
								<td><?php echo esc_html( $approved_by_user ? $approved_by_user->display_name : 'Unknown' ); ?></td>
								<td><?php echo esc_html( date( 'M d, Y', strtotime( $approved_date ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<!-- Rejected Requests Tab -->
		<div class="leave-approvals-section" style="margin-top: 40px;">
			<h2>Recently Rejected (<?php echo count( $rejected_requests ); ?>)</h2>

			<?php if ( empty( $rejected_requests ) ) : ?>
				<p style="color: #6b7280;">No rejected leave requests.</p>
			<?php else : ?>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th>Employee</th>
							<th>Leave Type</th>
							<th>Start Date</th>
							<th>End Date</th>
							<th>Days</th>
							<th>Rejected By</th>
							<th>Rejected Date</th>
							<th>Reason</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rejected_requests as $request ) : ?>
							<?php
							$user = get_user_by( 'id', $request->post_author );
							$leave_type = get_post_meta( $request->ID, 'leave_type', true );
							$start_date = get_post_meta( $request->ID, 'start_date', true );
							$end_date = get_post_meta( $request->ID, 'end_date', true );
							$rejected_by = get_post_meta( $request->ID, 'rejected_by', true );
							$rejected_date = get_post_meta( $request->ID, 'rejected_date', true );
							$rejection_reason = get_post_meta( $request->ID, 'rejection_reason', true );

							// Calculate days
							$start = strtotime( $start_date );
							$end = strtotime( $end_date );
							$days = floor( ( $end - $start ) / 86400 ) + 1;

							$rejected_by_user = get_user_by( 'id', $rejected_by );
							?>
							<tr>
								<td>
									<strong><?php echo esc_html( $user->display_name ); ?></strong><br>
									<small><?php echo esc_html( $user->user_email ); ?></small>
								</td>
								<td><?php echo esc_html( ucfirst( $leave_type ) ); ?></td>
								<td><?php echo esc_html( date( 'M d, Y', strtotime( $start_date ) ) ); ?></td>
								<td><?php echo esc_html( date( 'M d, Y', strtotime( $end_date ) ) ); ?></td>
								<td><?php echo intval( $days ); ?></td>
								<td><?php echo esc_html( $rejected_by_user ? $rejected_by_user->display_name : 'Unknown' ); ?></td>
								<td><?php echo esc_html( date( 'M d, Y', strtotime( $rejected_date ) ) ); ?></td>
								<td>
									<details>
										<summary style="cursor: pointer; color: #667eea;">View</summary>
										<p style="margin-top: 10px; color: #6b7280;"><?php echo esc_html( $rejection_reason ); ?></p>
									</details>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
</div>

<style>
	.leave-approvals-container {
		background: #fff;
		padding: 20px;
		border-radius: 8px;
		box-shadow: 0 1px 3px rgba(0,0,0,0.1);
	}

	.leave-approvals-section {
		margin-bottom: 30px;
	}

	.leave-approvals-section h2 {
		color: #1f2937;
		margin-bottom: 15px;
		font-size: 18px;
		font-weight: 600;
	}

	.wp-list-table {
		margin-top: 15px;
	}

	.wp-list-table th {
		background: #f3f4f6;
		color: #374151;
		font-weight: 600;
		padding: 12px;
		text-align: left;
		border-bottom: 2px solid #e5e7eb;
	}

	.wp-list-table td {
		padding: 12px;
		border-bottom: 1px solid #e5e7eb;
	}

	.wp-list-table tbody tr:hover {
		background: #f9fafb;
	}

	.button {
		margin-right: 5px;
		padding: 6px 12px;
		font-size: 12px;
	}

	.button-primary {
		background: #667eea;
		border-color: #667eea;
		color: white;
	}

	.button-primary:hover {
		background: #5568d3;
		border-color: #5568d3;
	}

	.button-secondary {
		background: #e5e7eb;
		border-color: #d1d5db;
		color: #374151;
	}

	.button-secondary:hover {
		background: #d1d5db;
		border-color: #9ca3af;
	}

	details summary {
		outline: none;
	}

	details summary::-webkit-details-marker {
		color: #667eea;
	}
</style>

<script>
	jQuery(document).ready(function($) {
		// Approve leave
		$(document).on('click', '.approve-leave', function() {
			var requestId = $(this).data('request-id');
			if (confirm('Are you sure you want to approve this leave request?')) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'leave_manager_approve_leave',
						request_id: requestId,
						_wpnonce: '<?php echo wp_create_nonce( 'leave_manager_nonce' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							alert('Leave request approved successfully!');
							location.reload();
						} else {
							alert('Error: ' + response.data.message);
						}
					},
					error: function() {
						alert('An error occurred. Please try again.');
					}
				});
			}
		});

		// Reject leave
		$(document).on('click', '.reject-leave', function() {
			var requestId = $(this).data('request-id');
			var reason = prompt('Please provide a reason for rejection:');
			if (reason !== null) {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'leave_manager_reject_leave',
						request_id: requestId,
						reason: reason,
						_wpnonce: '<?php echo wp_create_nonce( 'leave_manager_nonce' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							alert('Leave request rejected successfully!');
							location.reload();
						} else {
							alert('Error: ' + response.data.message);
						}
					},
					error: function() {
						alert('An error occurred. Please try again.');
					}
				});
			}
		});
	});
</script>
