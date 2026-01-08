<?php
/**
 * Frontend Leave Form Page
 *
 * @package Leave_Manager
 */

if ( ! is_user_logged_in() ) {
	echo '<p>Please log in to submit a leave request.</p>';
	return;
}
?>

<div class="leave-manager-form">
	<h2>Submit Leave Request</h2>
	
	<form id="leave-request-form" class="leave-form">
		<div class="form-group">
			<label for="leave_type">Leave Type:</label>
			<select id="leave_type" name="leave_type" required>
				<option value="">-- Select Leave Type --</option>
				<option value="annual">Annual Leave</option>
				<option value="sick">Sick Leave</option>
				<option value="other">Other Leave</option>
			</select>
		</div>

		<div class="form-group">
			<label for="start_date">Start Date:</label>
			<input type="date" id="start_date" name="start_date" required>
		</div>

		<div class="form-group">
			<label for="end_date">End Date:</label>
			<input type="date" id="end_date" name="end_date" required>
		</div>

		<div class="form-group">
			<label for="reason">Reason:</label>
			<textarea id="reason" name="reason" rows="4"></textarea>
		</div>

		<div class="form-group">
			<button type="submit" class="button button-primary">Submit Request</button>
		</div>
	</form>

	<div id="form-message" class="form-message" style="display: none;"></div>
</div>

<script>
	jQuery(document).ready(function($) {
		$('#leave-request-form').on('submit', function(e) {
			e.preventDefault();

			var formData = {
				leave_type: $('#leave_type').val(),
				start_date: $('#start_date').val(),
				end_date: $('#end_date').val(),
				reason: $('#reason').val(),
				_wpnonce: leave_managerLeaveData.nonce
			};

			$.ajax({
				url: leave_managerLeaveData.ajaxUrl,
				type: 'POST',
				dataType: 'json',
				data: formData,
				success: function(data) {
					$('#form-message').html('<p class="success">Leave request submitted successfully!</p>').show();
					$('#leave-request-form')[0].reset();
					setTimeout(function() {
						$('#form-message').hide();
					}, 5000);
				},
				error: function(error) {
					$('#form-message').html('<p class="error">Error submitting leave request. Please try again.</p>').show();
				}
			});
		});
	});
</script>

<style>
	.leave-manager-leave-form {
		max-width: 500px;
		margin: 20px 0;
	}

	.form-group {
		margin-bottom: 15px;
	}

	.form-group label {
		display: block;
		margin-bottom: 5px;
		font-weight: bold;
	}

	.form-group input,
	.form-group select,
	.form-group textarea {
		width: 100%;
		padding: 8px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-size: 14px;
	}

	.form-group textarea {
		resize: vertical;
	}

	.form-message {
		margin-top: 15px;
		padding: 10px;
		border-radius: 4px;
	}

	.form-message.success {
		background-color: #d4edda;
		color: #155724;
		border: 1px solid #c3e6cb;
	}

	.form-message.error {
		background-color: #f8d7da;
		color: #721c24;
		border: 1px solid #f5c6cb;
	}
</style>
