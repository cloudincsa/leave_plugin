<?php
/**
 * Frontend Dashboard Page
 *
 * @package Leave_Manager
 */

if ( ! is_user_logged_in() ) {
	echo '<p>Please log in to view your leave dashboard.</p>';
	return;
}
?>

<div class="leave-manager-dashboard">
	<h2>Leave Management Dashboard</h2>
	
	<div class="leave-balance-section">
		<h3>Your Leave Balance</h3>
		<div class="balance-cards">
			<div class="balance-card">
				<div class="balance-type">Annual Leave</div>
				<div class="balance-amount" id="annual-balance">--</div>
			</div>
			<div class="balance-card">
				<div class="balance-type">Sick Leave</div>
				<div class="balance-amount" id="sick-balance">--</div>
			</div>
			<div class="balance-card">
				<div class="balance-type">Other Leave</div>
				<div class="balance-amount" id="other-balance">--</div>
			</div>
		</div>
	</div>

	<div class="leave-requests-section">
		<h3>Your Leave Requests</h3>
		<table class="leave-requests-table">
			<thead>
				<tr>
					<th>Leave Type</th>
					<th>Start Date</th>
					<th>End Date</th>
					<th>Status</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody id="requests-tbody">
				<tr><td colspan="5">Loading...</td></tr>
			</tbody>
		</table>
	</div>
</div>

<script>
	jQuery(document).ready(function($) {
		// Load leave balance
		$.ajax({
			url: leave_managerLeaveData.ajaxUrl,
			type: 'GET',
			dataType: 'json',
			success: function(data) {
				if (data.annual_leave) {
					$('#annual-balance').text(data.annual_leave);
				}
				if (data.sick_leave) {
					$('#sick-balance').text(data.sick_leave);
				}
				if (data.other_leave) {
					$('#other-balance').text(data.other_leave);
				}
			}
		});

		// Load leave requests
		$.ajax({
			url: leave_managerLeaveData.ajaxUrl,
			type: 'GET',
			dataType: 'json',
			success: function(data) {
				var tbody = $('#requests-tbody');
				tbody.empty();

				if (data.length === 0) {
					tbody.html('<tr><td colspan="5">No leave requests found</td></tr>');
					return;
				}

				data.forEach(function(request) {
					var row = '<tr>';
					row += '<td>' + request.leave_type + '</td>';
					row += '<td>' + request.start_date + '</td>';
					row += '<td>' + request.end_date + '</td>';
					row += '<td><span class="status-' + request.status + '">' + request.status + '</span></td>';
					row += '<td><a href="#" class="edit-request" data-id="' + request.request_id + '">Edit</a></td>';
					row += '</tr>';
					tbody.append(row);
				});
			}
		});
	});
</script>
