<?php
/**
 * Leave Requests Page
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
$leave_requests_class = new Leave_Manager_Leave_Requests( $db, $logger );
$users_class = new Leave_Manager_Users( $db, $logger );
$permissions = new Leave_Manager_Permissions( $db, $logger );

// Handle approval/rejection
$message = '';
$error = '';

if ( isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'approve', 'reject' ), true ) ) {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_leave_action' ) ) {
		$error = 'Security check failed.';
	} else {
		$request_id = intval( $_POST['request_id'] ?? 0 );
		$action = sanitize_text_field( $_POST['action'] );

		if ( 'approve' === $action ) {
			$result = $leave_requests_class->approve_request( $request_id, get_current_user_id() );
			if ( $result ) {
				$message = 'Leave request approved successfully.';
			} else {
				$error = 'Failed to approve leave request.';
			}
		} elseif ( 'reject' === $action ) {
			$rejection_reason = sanitize_textarea_field( $_POST['rejection_reason'] ?? '' );
			$result = $leave_requests_class->reject_request( $request_id, $rejection_reason, get_current_user_id() );
			if ( $result ) {
				$message = 'Leave request rejected successfully.';
			} else {
				$error = 'Failed to reject leave request.';
			}
		}
	}
}

// Get all leave requests
global $wpdb;
$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
$users_table = $wpdb->prefix . 'leave_manager_leave_users';

$all_requests = $wpdb->get_results(
	"SELECT r.*, u.first_name, u.last_name, u.email, u.department
	 FROM $requests_table r
	 JOIN $users_table u ON r.user_id = u.user_id
	 ORDER BY r.created_at DESC"
);
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<div class="leave-requests-container">
		<h2>Leave Requests</h2>

		<?php if ( ! empty( $all_requests ) ) : ?>
			<table class="widefat striped">
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
					<?php foreach ( $all_requests as $request ) : ?>
						<tr>
							<td>
								<strong><?php echo esc_html( $request->first_name . ' ' . $request->last_name ); ?></strong><br>
								<small><?php echo esc_html( $request->email ); ?></small>
							</td>
							<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $request->leave_type ) ) ); ?></td>
							<td><?php echo esc_html( date_i18n( 'M d, Y', strtotime( $request->start_date ) ) ); ?></td>
							<td><?php echo esc_html( date_i18n( 'M d, Y', strtotime( $request->end_date ) ) ); ?></td>
							<td>
								<?php
								$start = new DateTime( $request->start_date );
								$end = new DateTime( $request->end_date );
								$days = $end->diff( $start )->days + 1;
								echo esc_html( $days );
								?>
							</td>
							<td>
								<span class="status-badge status-<?php echo esc_attr( $request->status ); ?>">
									<?php echo esc_html( ucfirst( $request->status ) ); ?>
								</span>
							</td>
							<td><?php echo esc_html( date_i18n( 'M d, Y', strtotime( $request->created_at ) ) ); ?></td>
							<td>
								<?php if ( 'pending' === $request->status ) : ?>
									<form method="post" style="display: inline;">
										<?php wp_nonce_field( 'leave_manager_leave_action', 'nonce' ); ?>
										<input type="hidden" name="action" value="approve">
										<input type="hidden" name="request_id" value="<?php echo esc_attr( $request->request_id ); ?>">
										<button type="submit" class="button button-small button-primary">Approve</button>
									</form>
									<button type="button" class="button button-small button-secondary reject-btn" data-request-id="<?php echo esc_attr( $request->request_id ); ?>">Reject</button>
								<?php else : ?>
									<span class="text-muted">No actions</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p>No leave requests found.</p>
		<?php endif; ?>
	</div>
</div>

<!-- Rejection Modal -->
<div id="rejection-modal" class="modal" style="display: none;">
	<div class="modal-content">
		<span class="close">&times;</span>
		<h2>Reject Leave Request</h2>
		<form method="post">
			<?php wp_nonce_field( 'leave_manager_leave_action', 'nonce' ); ?>
			<input type="hidden" name="action" value="reject">
			<input type="hidden" id="modal-request-id" name="request_id" value="">
			
			<div class="form-group">
				<label for="rejection_reason">Rejection Reason:</label>
				<textarea id="rejection_reason" name="rejection_reason" rows="4" required></textarea>
			</div>

			<div class="modal-actions">
				<button type="submit" class="button button-primary">Reject Request</button>
				<button type="button" class="button button-secondary close-modal">Cancel</button>
			</div>
		</form>
	</div>
</div>

<style>
	.leave-requests-container {
		margin-top: 20px;
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

	.text-muted {
		color: #999;
	}

	.modal {
		position: fixed;
		z-index: 1000;
		left: 0;
		top: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
	}

	.modal-content {
		background-color: white;
		margin: 5% auto;
		padding: 20px;
		border: 1px solid #888;
		border-radius: 5px;
		width: 500px;
		max-width: 90%;
		box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
	}

	.close {
		color: #aaa;
		float: right;
		font-size: 28px;
		font-weight: bold;
		cursor: pointer;
	}

	.close:hover {
		color: black;
	}

	.form-group {
		margin-bottom: 15px;
	}

	.form-group label {
		display: block;
		font-weight: 600;
		margin-bottom: 5px;
	}

	.form-group textarea {
		width: 100%;
		padding: 10px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-family: inherit;
		box-sizing: border-box;
	}

	.modal-actions {
		margin-top: 20px;
		text-align: right;
	}

	.modal-actions .button {
		margin-left: 10px;
	}
</style>

<script>
	jQuery(document).ready(function($) {
		var modal = document.getElementById('rejection-modal');
		var closeBtn = document.querySelector('.close');
		var closeModalBtn = document.querySelector('.close-modal');

		$('.reject-btn').on('click', function() {
			var requestId = $(this).data('request-id');
			$('#modal-request-id').val(requestId);
			modal.style.display = 'block';
		});

		closeBtn.onclick = function() {
			modal.style.display = 'none';
		};

		closeModalBtn.onclick = function() {
			modal.style.display = 'none';
		};

		$(window).on('click', function(event) {
			if (event.target === modal) {
				modal.style.display = 'none';
			}
		});
	});
</script>
