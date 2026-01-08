<?php
/**
 * Professional Frontend Leave Request Page - ChatPanel Design
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$branding = new Leave_Manager_Branding();
$primary_color = $branding->get_setting( 'primary_color' );
$secondary_color = $branding->get_setting( 'primary_dark_color' );
?>

<style>
body {
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
	background: #f5f5f5;
	color: #333;
}

.leave-manager-container {
	max-width: 800px;
	margin: 0 auto;
	padding: 20px;
}

.professional-header {
	background: linear-gradient(135deg, <?php echo esc_attr( $primary_color ); ?> 0%, <?php echo esc_attr( $secondary_color ); ?> 100%);
	color: white;
	padding: 40px;
	border-radius: 12px;
	margin-bottom: 30px;
}

.professional-header h1 {
	margin: 0 0 10px 0;
	font-size: 32px;
	font-weight: 700;
}

.professional-header p {
	margin: 0;
	font-size: 16px;
	opacity: 0.95;
}

.form-card {
	background: white;
	padding: 30px;
	border-radius: 12px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.form-group {
	margin-bottom: 25px;
}

.form-group label {
	display: block;
	margin-bottom: 10px;
	font-weight: 600;
	color: #333;
	font-size: 14px;
}

.form-group input,
.form-group select,
.form-group textarea {
	width: 100%;
	padding: 12px 15px;
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	font-family: inherit;
	font-size: 14px;
	box-sizing: border-box;
	transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
	outline: none;
	border-color: <?php echo esc_attr( $primary_color ); ?>;
	box-shadow: 0 0 0 3px rgba(74, 95, 255, 0.1);
}

.form-group textarea {
	min-height: 120px;
	resize: vertical;
}

.form-row {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 20px;
}

.form-row .form-group {
	margin-bottom: 0;
}

.leave-balance {
	background: #f0f4ff;
	border-left: 4px solid <?php echo esc_attr( $primary_color ); ?>;
	padding: 20px;
	border-radius: 8px;
	margin-bottom: 25px;
}

.leave-balance h3 {
	margin: 0 0 15px 0;
	color: <?php echo esc_attr( $primary_color ); ?>;
	font-size: 14px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.balance-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
	gap: 15px;
}

.balance-item {
	text-align: center;
}

.balance-item-label {
	font-size: 12px;
	color: #666;
	margin-bottom: 5px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	font-weight: 600;
}

.balance-item-value {
	font-size: 24px;
	font-weight: 700;
	color: <?php echo esc_attr( $primary_color ); ?>;
}

.form-actions {
	display: flex;
	gap: 15px;
	margin-top: 30px;
	padding-top: 20px;
	border-top: 1px solid #e0e0e0;
}

.btn-submit {
	flex: 1;
	padding: 14px 20px;
	background: linear-gradient(135deg, <?php echo esc_attr( $primary_color ); ?> 0%, <?php echo esc_attr( $secondary_color ); ?> 100%);
	color: white;
	border: none;
	border-radius: 8px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.3s ease;
	font-size: 14px;
}

.btn-submit:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(74, 95, 255, 0.3);
}

.btn-cancel {
	flex: 1;
	padding: 14px 20px;
	background: #f0f0f0;
	color: #666;
	border: none;
	border-radius: 8px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.3s ease;
	font-size: 14px;
}

.btn-cancel:hover {
	background: #e0e0e0;
}

.info-box {
	background: #f0f4ff;
	border-left: 4px solid <?php echo esc_attr( $primary_color ); ?>;
	padding: 20px;
	border-radius: 8px;
	margin-bottom: 25px;
	font-size: 13px;
	color: #333;
	line-height: 1.6;
}

.info-box strong {
	color: <?php echo esc_attr( $primary_color ); ?>;
}

.info-box ul {
	margin: 10px 0 0 0;
	padding-left: 20px;
}

.info-box li {
	margin: 5px 0;
}

.date-picker-hint {
	font-size: 12px;
	color: #999;
	margin-top: 5px;
}

.success-message {
	background: #d4edda;
	border-left: 4px solid #4caf50;
	padding: 20px;
	border-radius: 8px;
	margin-bottom: 25px;
	color: #155724;
	display: none;
}

.success-message.show {
	display: block;
}

.error-message {
	background: #f8d7da;
	border-left: 4px solid #f44336;
	padding: 20px;
	border-radius: 8px;
	margin-bottom: 25px;
	color: #721c24;
	display: none;
}

.error-message.show {
	display: block;
}

@media (max-width: 600px) {
	.form-row {
		grid-template-columns: 1fr;
	}
	
	.form-actions {
		flex-direction: column;
	}
	
	.balance-grid {
		grid-template-columns: repeat(2, 1fr);
	}
}
</style>

<div class="leave-manager-container">
	<div class="professional-header">
		<h1>Request Leave</h1>
		<p>Submit a new leave request</p>
	</div>

	<div class="success-message" id="success-message">
		<strong>✓ Success!</strong> Your leave request has been submitted successfully. Your manager will review it shortly.
	</div>

	<div class="error-message" id="error-message">
		<strong>✗ Error!</strong> There was an error submitting your request. Please try again.
	</div>

	<div class="leave-balance">
		<h3>Your Leave Balance</h3>
		<div class="balance-grid">
			<div class="balance-item">
				<div class="balance-item-label">Annual Leave</div>
				<div class="balance-item-value">15</div>
			</div>
			<div class="balance-item">
				<div class="balance-item-label">Sick Leave</div>
				<div class="balance-item-value">10</div>
			</div>
			<div class="balance-item">
				<div class="balance-item-label">Other Leave</div>
				<div class="balance-item-value">5</div>
			</div>
		</div>
	</div>

	<div class="info-box">
		<strong>📋 Important:</strong> Please note the following guidelines when submitting a leave request:
		<ul>
			<li>Submit requests at least 7 days in advance when possible</li>
			<li>Ensure your team is informed about your absence</li>
			<li>Handover your work before going on leave</li>
			<li>You will receive a confirmation email once your request is processed</li>
		</ul>
	</div>

	<form class="form-card" id="leave-request-form" onsubmit="submitLeaveRequest(event)">
		<div class="form-group">
			<label for="leave-type">Leave Type *</label>
			<select id="leave-type" name="leave_type" required>
				<option value="">Select leave type</option>
				<option value="annual">Annual Leave</option>
				<option value="sick">Sick Leave</option>
				<option value="other">Other Leave</option>
			</select>
		</div>

		<div class="form-row">
			<div class="form-group">
				<label for="start-date">Start Date *</label>
				<input type="date" id="start-date" name="start_date" required>
				<div class="date-picker-hint">When does your leave start?</div>
			</div>
			<div class="form-group">
				<label for="end-date">End Date *</label>
				<input type="date" id="end-date" name="end_date" required>
				<div class="date-picker-hint">When does your leave end?</div>
			</div>
		</div>

		<div class="form-group">
			<label for="duration">Duration (Days)</label>
			<input type="number" id="duration" name="duration" readonly placeholder="Calculated automatically">
		</div>

		<div class="form-group">
			<label for="reason">Reason for Leave</label>
			<textarea id="reason" name="reason" placeholder="Please provide a reason for your leave request (optional)"></textarea>
		</div>

		<div class="form-group">
			<label for="notes">Additional Notes</label>
			<textarea id="notes" name="notes" placeholder="Any additional information for your manager (optional)"></textarea>
		</div>

		<div class="form-actions">
			<button type="submit" class="btn-submit">Submit Request</button>
			<button type="reset" class="btn-cancel">Clear Form</button>
		</div>
	</form>
</div>

<script>
// Calculate duration when dates change
document.getElementById('start-date').addEventListener('change', calculateDuration);
document.getElementById('end-date').addEventListener('change', calculateDuration);

function calculateDuration() {
	const startDate = new Date(document.getElementById('start-date').value);
	const endDate = new Date(document.getElementById('end-date').value);
	
	if (startDate && endDate && startDate <= endDate) {
		const diffTime = Math.abs(endDate - startDate);
		const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
		document.getElementById('duration').value = diffDays;
	}
}

function submitLeaveRequest(e) {
	e.preventDefault();
	
	const formData = new FormData(document.getElementById('leave-request-form'));
	
	// Validate form
	const leaveType = formData.get('leave_type');
	const startDate = formData.get('start_date');
	const endDate = formData.get('end_date');
	
	if (!leaveType || !startDate || !endDate) {
		showError('Please fill in all required fields');
		return;
	}
	
	// Show success message (in real implementation, this would be an AJAX call)
	console.log('Submitting leave request:', Object.fromEntries(formData));
	
	document.getElementById('success-message').classList.add('show');
	document.getElementById('error-message').classList.remove('show');
	
	// Reset form after 2 seconds
	setTimeout(() => {
		document.getElementById('leave-request-form').reset();
		document.getElementById('duration').value = '';
	}, 2000);
}

function showError(message) {
	const errorEl = document.getElementById('error-message');
	errorEl.textContent = '✗ Error! ' + message;
	errorEl.classList.add('show');
	document.getElementById('success-message').classList.remove('show');
}
</script>
