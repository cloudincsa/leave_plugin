<?php
/**
 * Professional Requests Page - ChatPanel Design
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get database instance
$db = new Leave_Manager_Database();

// Get requests with user names
global $wpdb;
$requests_table = $db->leave_requests_table;
$users_table = $db->users_table;
$requests = $wpdb->get_results( "
    SELECT r.*, 
           CONCAT(u.first_name, ' ', u.last_name) as user_name,
           DATEDIFF(r.end_date, r.start_date) + 1 as days
    FROM $requests_table r
    LEFT JOIN $users_table u ON r.user_id = u.user_id
    ORDER BY r.created_at DESC
" );

// Count by status
$total = count( $requests );
$pending = count( array_filter( $requests, function( $r ) { return $r->status === 'pending'; } ) );
$approved = count( array_filter( $requests, function( $r ) { return $r->status === 'approved'; } ) );
$rejected = count( array_filter( $requests, function( $r ) { return $r->status === 'rejected'; } ) );

// Include admin page template styles
include 'admin-page-template.php';
?>

<div class="leave-manager-admin-container">
<div class="lm-page-content">
<!-- Page Header -->
<div class="page-header">
	<div>
		<h1><?php esc_html_e( 'Leave Requests', 'leave-manager' ); ?></h1>
		<p class="subtitle"><?php esc_html_e( 'Manage and approve employee leave requests', 'leave-manager' ); ?></p>
	</div>
</div>

<div class="admin-tabs">
	<button class="admin-tab active" data-tab="all-requests"><?php esc_html_e( 'All Requests', 'leave-manager' ); ?></button>
	<button class="admin-tab" data-tab="pending"><?php esc_html_e( 'Pending', 'leave-manager' ); ?></button>
	<button class="admin-tab" data-tab="approved"><?php esc_html_e( 'Approved', 'leave-manager' ); ?></button>
	<button class="admin-tab" data-tab="rejected"><?php esc_html_e( 'Rejected', 'leave-manager' ); ?></button>
</div>

<div class="content-wrapper">
	<div class="content-main">
		<!-- All Requests Tab -->
		<div class="lm-tab-content active" id="all-requests">
			<div class="lm-card">
				<div class="lm-stat-grid">
					<div class="lm-stat-card">
						<div class="lm-stat-label"><?php esc_html_e( 'TOTAL REQUESTS', 'leave-manager' ); ?></div>
						<div class="lm-stat-value"><?php echo esc_html( $total ); ?></div>
					</div>
					<div class="lm-stat-card">
						<div class="lm-stat-label"><?php esc_html_e( 'PENDING', 'leave-manager' ); ?></div>
						<div class="lm-stat-value" style="color: #F59E0B;"><?php echo esc_html( $pending ); ?></div>
					</div>
					<div class="lm-stat-card">
						<div class="lm-stat-label"><?php esc_html_e( 'APPROVED', 'leave-manager' ); ?></div>
						<div class="lm-stat-value" style="color: #10B981;"><?php echo esc_html( $approved ); ?></div>
					</div>
					<div class="lm-stat-card">
						<div class="lm-stat-label"><?php esc_html_e( 'REJECTED', 'leave-manager' ); ?></div>
						<div class="lm-stat-value" style="color: #EF4444;"><?php echo esc_html( $rejected ); ?></div>
					</div>
				</div>

				<div class="lm-table-wrapper">
					<table class="lm-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Employee', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Leave Type', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Dates', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Days', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Status', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'leave-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $requests ) ) : ?>
								<?php foreach ( $requests as $request ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $request->user_name ?? 'Unknown' ); ?></strong></td>
										<td><?php echo esc_html( $request->leave_type ?? 'N/A' ); ?></td>
										<td><?php echo esc_html( $request->start_date ?? 'N/A' ); ?> - <?php echo esc_html( $request->end_date ?? 'N/A' ); ?></td>
										<td><?php echo esc_html( $request->days ?? 0 ); ?></td>
										<td>
											<?php
											$status_class = 'status-pending';
											if ( $request->status === 'approved' ) {
												$status_class = 'status-approved';
											} elseif ( $request->status === 'rejected' ) {
												$status_class = 'status-rejected';
											}
											?>
											<span class="lm-status-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( ucfirst( $request->status ) ); ?></span>
										</td>
										<td>
											<div class="lm-action-buttons">
												<?php if ( $request->status === 'pending' ) : ?>
													<button class="lm-btn-approve" onclick="approveRequest(<?php echo esc_attr( $request->request_id ); ?>)"><?php esc_html_e( 'Approve', 'leave-manager' ); ?></button>
													<button class="lm-btn-reject" onclick="rejectRequest(<?php echo esc_attr( $request->request_id ); ?>)"><?php esc_html_e( 'Reject', 'leave-manager' ); ?></button>
												<?php endif; ?>
												<button class="lm-btn-view" onclick="viewRequest(<?php echo esc_attr( $request->request_id ); ?>)"><?php esc_html_e( 'View', 'leave-manager' ); ?></button>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="6" style="text-align: center; padding: 40px;">
										<div class="lm-empty-state">
											<div class="lm-empty-state-icon">📭</div>
											<h3><?php esc_html_e( 'No Requests', 'leave-manager' ); ?></h3>
											<p><?php esc_html_e( 'No leave requests found', 'leave-manager' ); ?></p>
										</div>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Pending Tab -->
		<div class="lm-tab-content" id="pending">
			<div class="lm-card">
				<div class="lm-table-wrapper">
					<table class="lm-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Employee', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Leave Type', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Dates', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Days', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'leave-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$pending_requests = array_filter( $requests, function( $r ) { return $r->status === 'pending'; } );
							if ( ! empty( $pending_requests ) ) :
								foreach ( $pending_requests as $request ) :
									?>
									<tr>
										<td><strong><?php echo esc_html( $request->user_name ?? 'Unknown' ); ?></strong></td>
										<td><?php echo esc_html( $request->leave_type ?? 'N/A' ); ?></td>
										<td><?php echo esc_html( $request->start_date ?? 'N/A' ); ?> - <?php echo esc_html( $request->end_date ?? 'N/A' ); ?></td>
										<td><?php echo esc_html( $request->days ?? 0 ); ?></td>
										<td>
											<div class="lm-action-buttons">
												<button class="lm-btn-approve" onclick="approveRequest(<?php echo esc_attr( $request->request_id ); ?>)"><?php esc_html_e( 'Approve', 'leave-manager' ); ?></button>
												<button class="lm-btn-reject" onclick="rejectRequest(<?php echo esc_attr( $request->request_id ); ?>)"><?php esc_html_e( 'Reject', 'leave-manager' ); ?></button>
											</div>
										</td>
									</tr>
									<?php
								endforeach;
							else :
								?>
								<tr>
									<td colspan="5" style="text-align: center; padding: 40px;">
										<div class="lm-empty-state">
											<div class="lm-empty-state-icon">✅</div>
											<h3><?php esc_html_e( 'All Caught Up!', 'leave-manager' ); ?></h3>
											<p><?php esc_html_e( 'No pending requests', 'leave-manager' ); ?></p>
										</div>
									</td>
								</tr>
								<?php
							endif;
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Approved Tab -->
		<div class="lm-tab-content" id="approved">
			<div class="lm-card">
				<div class="lm-table-wrapper">
					<table class="lm-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Employee', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Leave Type', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Dates', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Days', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'leave-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$approved_requests = array_filter( $requests, function( $r ) { return $r->status === 'approved'; } );
							if ( ! empty( $approved_requests ) ) :
								foreach ( $approved_requests as $request ) :
									?>
									<tr>
										<td><strong><?php echo esc_html( $request->user_name ?? 'Unknown' ); ?></strong></td>
										<td><?php echo esc_html( $request->leave_type ?? 'N/A' ); ?></td>
										<td><?php echo esc_html( $request->start_date ?? 'N/A' ); ?> - <?php echo esc_html( $request->end_date ?? 'N/A' ); ?></td>
										<td><?php echo esc_html( $request->days ?? 0 ); ?></td>
										<td>
											<button class="lm-btn-view" onclick="viewRequest(<?php echo esc_attr( $request->request_id ); ?>)"><?php esc_html_e( 'View', 'leave-manager' ); ?></button>
										</td>
									</tr>
									<?php
								endforeach;
							else :
								?>
								<tr>
									<td colspan="5" style="text-align: center; padding: 40px;">
										<div class="lm-empty-state">
											<div class="lm-empty-state-icon">📭</div>
											<h3><?php esc_html_e( 'No Approved Requests', 'leave-manager' ); ?></h3>
										</div>
									</td>
								</tr>
								<?php
							endif;
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Rejected Tab -->
		<div class="lm-tab-content" id="rejected">
			<div class="lm-card">
				<div class="lm-table-wrapper">
					<table class="lm-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Employee', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Leave Type', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Dates', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Days', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'leave-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$rejected_requests = array_filter( $requests, function( $r ) { return $r->status === 'rejected'; } );
							if ( ! empty( $rejected_requests ) ) :
								foreach ( $rejected_requests as $request ) :
									?>
									<tr>
										<td><strong><?php echo esc_html( $request->user_name ?? 'Unknown' ); ?></strong></td>
										<td><?php echo esc_html( $request->leave_type ?? 'N/A' ); ?></td>
										<td><?php echo esc_html( $request->start_date ?? 'N/A' ); ?> - <?php echo esc_html( $request->end_date ?? 'N/A' ); ?></td>
										<td><?php echo esc_html( $request->days ?? 0 ); ?></td>
										<td>
											<button class="lm-btn-view" onclick="viewRequest(<?php echo esc_attr( $request->request_id ); ?>)"><?php esc_html_e( 'View', 'leave-manager' ); ?></button>
										</td>
									</tr>
									<?php
								endforeach;
							else :
								?>
								<tr>
									<td colspan="5" style="text-align: center; padding: 40px;">
										<div class="lm-empty-state">
											<div class="lm-empty-state-icon">📭</div>
											<h3><?php esc_html_e( 'No Rejected Requests', 'leave-manager' ); ?></h3>
										</div>
									</td>
								</tr>
								<?php
							endif;
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- Sidebar -->
	<div class="content-sidebar">
		<div class="lm-card">
			<h3><?php esc_html_e( 'Request Summary', 'leave-manager' ); ?></h3>
			<p><?php esc_html_e( 'Total Requests: ', 'leave-manager' ); ?><strong><?php echo esc_html( $total ); ?></strong></p>
			<p><?php esc_html_e( 'Pending: ', 'leave-manager' ); ?><strong style="color: #F59E0B;"><?php echo esc_html( $pending ); ?></strong></p>
			<p><?php esc_html_e( 'Approved: ', 'leave-manager' ); ?><strong style="color: #10B981;"><?php echo esc_html( $approved ); ?></strong></p>
			<p><?php esc_html_e( 'Rejected: ', 'leave-manager' ); ?><strong style="color: #EF4444;"><?php echo esc_html( $rejected ); ?></strong></p>
		</div>

		<div class="lm-card">
			<h3><?php esc_html_e( 'Quick Actions', 'leave-manager' ); ?></h3>
			<button class="lm-btn-primary" style="width: 100%; margin-bottom: 10px;" onclick="exportRequests()"><?php esc_html_e( 'Export Requests', 'leave-manager' ); ?></button>
			<button class="lm-btn-secondary" style="width: 100%;" onclick="printRequests()"><?php esc_html_e( 'Print', 'leave-manager' ); ?></button>
		</div>

		<div class="lm-card">
			<h3><?php esc_html_e( 'Quick Links', 'leave-manager' ); ?></h3>
			<ul style="list-style: none; padding: 0;">
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-management' ) ); ?>"><?php esc_html_e( 'Dashboard', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-staff' ) ); ?>"><?php esc_html_e( 'Staff', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-reports' ) ); ?>"><?php esc_html_e( 'Reports', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'leave-manager' ); ?></a></li>
			</ul>
		</div>
	</div>
</div>

<!-- Notification Container -->
<div id="lm-notification" style="display: none; position: fixed; top: 50px; right: 20px; padding: 15px 25px; border-radius: 4px; z-index: 10000; font-weight: 500;"></div>

<!-- View Request Modal -->
<div id="view-request-modal" class="lm-modal" style="display: none;">
	<div class="lm-modal-content">
		<div class="lm-modal-header">
			<h3><?php esc_html_e( 'Request Details', 'leave-manager' ); ?></h3>
			<button class="lm-modal-close" onclick="closeViewModal()">&times;</button>
		</div>
		<div class="lm-modal-body" id="view-request-content">
			<p>Loading...</p>
		</div>
	</div>
</div>

<!-- Reject Reason Modal -->
<div id="reject-modal" class="lm-modal" style="display: none;">
	<div class="lm-modal-content">
		<div class="lm-modal-header">
			<h3><?php esc_html_e( 'Reject Request', 'leave-manager' ); ?></h3>
			<button class="lm-modal-close" onclick="closeRejectModal()">&times;</button>
		</div>
		<div class="lm-modal-body">
			<input type="hidden" id="reject_request_id">
			<div style="margin-bottom: 15px;">
				<label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php esc_html_e( 'Reason for Rejection (optional)', 'leave-manager' ); ?></label>
				<textarea id="reject_reason" rows="4" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Enter reason for rejection..."></textarea>
			</div>
			<div style="display: flex; gap: 10px; justify-content: flex-end;">
				<button type="button" class="lm-btn-secondary" onclick="closeRejectModal()"><?php esc_html_e( 'Cancel', 'leave-manager' ); ?></button>
				<button type="button" class="lm-btn-reject" onclick="confirmReject()"><?php esc_html_e( 'Reject Request', 'leave-manager' ); ?></button>
			</div>
		</div>
	</div>
</div>

<style>
.lm-modal {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0,0,0,0.5);
	z-index: 9999;
	display: flex;
	align-items: center;
	justify-content: center;
}
.lm-modal-content {
	background: white;
	border-radius: 8px;
	width: 90%;
	max-width: 500px;
	max-height: 90vh;
	overflow-y: auto;
}
.lm-modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 15px 20px;
	border-bottom: 1px solid #eee;
}
.lm-modal-header h3 {
	margin: 0;
}
.lm-modal-close {
	background: none;
	border: none;
	font-size: 24px;
	cursor: pointer;
	color: #666;
}
.lm-modal-body {
	padding: 20px;
}
</style>

<script>
var lm_nonce = '<?php echo esc_js( wp_create_nonce( 'leave_manager_admin_nonce' ) ); ?>';
var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';

jQuery(document).ready(function($) {

// Request data for view modal
window.requestData = <?php echo json_encode( array_map( function( $r ) {
	return array(
		'id' => $r->request_id,
		'user_name' => $r->user_name ?? 'Unknown',
		'leave_type' => $r->leave_type ?? 'N/A',
		'start_date' => $r->start_date ?? 'N/A',
		'end_date' => $r->end_date ?? 'N/A',
		'days' => $r->days ?? 0,
		'status' => $r->status ?? 'pending',
		'reason' => $r->reason ?? '',
		'created_at' => $r->created_at ?? ''
	);
}, $requests ) ); ?>;

document.querySelectorAll('.admin-tab').forEach(button => {
	button.addEventListener('click', function() {
		const tabId = this.getAttribute('data-tab');
		
		// Hide all tabs
		document.querySelectorAll('.lm-tab-content').forEach(tab => {
			tab.classList.remove('active');
		});
		
		// Remove active class from all buttons
		document.querySelectorAll('.admin-tab').forEach(btn => {
			btn.classList.remove('active');
		});
		
		// Show selected tab and mark button as active
		document.getElementById(tabId).classList.add('active');
		this.classList.add('active');
	});
});

// Show notification
function showNotification(message, type = 'success') {
	const notification = document.getElementById('lm-notification');
	notification.textContent = message;
	notification.style.display = 'block';
	notification.style.background = type === 'success' ? '#10B981' : '#EF4444';
	notification.style.color = 'white';
	
	setTimeout(() => {
		notification.style.display = 'none';
	}, 3000);
}

window.approveRequest = function(id) {
	if (!confirm('Are you sure you want to approve this request?')) {
		return;
	}
	
	const formData = new FormData();
	formData.append('action', 'leave_manager_approve_request');
	formData.append('nonce', lm_nonce);
	formData.append('request_id', id);
	
	fetch(ajaxurl, {
		method: 'POST',
		body: formData,
		credentials: 'same-origin'
	})
	.then(response => response.json())
	.then(data => {
		if (data.success) {
			showNotification('Request approved successfully!', 'success');
			setTimeout(() => location.reload(), 1000);
		} else {
			showNotification(data.data.message || 'Failed to approve request', 'error');
		}
	})
	.catch(error => {
		console.error('Error:', error);
		showNotification('An error occurred', 'error');
	});
}

window.rejectRequest = function(id) {
	document.getElementById('reject_request_id').value = id;
	document.getElementById('reject_reason').value = '';
	document.getElementById('reject-modal').style.display = 'flex';
}

window.closeRejectModal = function() {
	document.getElementById('reject-modal').style.display = 'none';
}

window.confirmReject = function() {
	const id = document.getElementById('reject_request_id').value;
	const reason = document.getElementById('reject_reason').value;
	
	const formData = new FormData();
	formData.append('action', 'leave_manager_reject_request');
	formData.append('nonce', lm_nonce);
	formData.append('request_id', id);
	formData.append('reason', reason);
	
	fetch(ajaxurl, {
		method: 'POST',
		body: formData,
		credentials: 'same-origin'
	})
	.then(response => response.json())
	.then(data => {
		if (data.success) {
			showNotification('Request rejected successfully!', 'success');
			closeRejectModal();
			setTimeout(() => location.reload(), 1000);
		} else {
			showNotification(data.data.message || 'Failed to reject request', 'error');
		}
	})
	.catch(error => {
		console.error('Error:', error);
		showNotification('An error occurred', 'error');
	});
}

window.viewRequest = function(id) {
	const request = requestData.find(r => r.id == id);
	if (!request) {
		alert('Request not found');
		return;
	}
	
	const statusColors = {
		'pending': '#F59E0B',
		'approved': '#10B981',
		'rejected': '#EF4444'
	};
	
	const content = `
		<div style="margin-bottom: 15px;">
			<strong>Employee:</strong> ${request.user_name}
		</div>
		<div style="margin-bottom: 15px;">
			<strong>Leave Type:</strong> ${request.leave_type}
		</div>
		<div style="margin-bottom: 15px;">
			<strong>Dates:</strong> ${request.start_date} to ${request.end_date}
		</div>
		<div style="margin-bottom: 15px;">
			<strong>Days:</strong> ${request.days}
		</div>
		<div style="margin-bottom: 15px;">
			<strong>Status:</strong> <span style="color: ${statusColors[request.status] || '#666'}; font-weight: bold;">${request.status.toUpperCase()}</span>
		</div>
		${request.reason ? `<div style="margin-bottom: 15px;"><strong>Reason:</strong> ${request.reason}</div>` : ''}
		<div style="margin-bottom: 15px;">
			<strong>Submitted:</strong> ${request.created_at}
		</div>
	`;
	
	document.getElementById('view-request-content').innerHTML = content;
	document.getElementById('view-request-modal').style.display = 'flex';
}

window.closeViewModal = function() {
	document.getElementById('view-request-modal').style.display = 'none';
}

window.exportRequests = function() {
	// Generate CSV from the requests data
	let csv = 'Employee,Leave Type,Start Date,End Date,Days,Status,Reason,Submitted\n';
	requestData.forEach(r => {
		csv += `"${r.user_name}","${r.leave_type}","${r.start_date}","${r.end_date}",${r.days},"${r.status}","${r.reason || ''}","${r.created_at}"\n`;
	});
	
	const blob = new Blob([csv], { type: 'text/csv' });
	const url = window.URL.createObjectURL(blob);
	const a = document.createElement('a');
	a.href = url;
	a.download = 'leave_requests_' + new Date().toISOString().split('T')[0] + '.csv';
	a.click();
	window.URL.revokeObjectURL(url);
	
	showNotification('Requests exported successfully!', 'success');
}

window.printRequests = function() {
	window.print();
};
}); // End jQuery document ready
</script>

</div>
</div>
