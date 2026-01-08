<?php
/**
 * Export Page
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
$export = new Leave_Manager_Export( $db, $logger );

// Handle export requests
if ( isset( $_POST['action'] ) && in_array( $_POST['action'], array( 'export_requests_csv', 'export_users_csv' ), true ) ) {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_export' ) ) {
		wp_die( 'Security check failed.' );
	}

	$action = sanitize_text_field( $_POST['action'] );
	$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
	$end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
	$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

	$args = array();
	if ( ! empty( $start_date ) ) {
		$args['start_date'] = $start_date;
	}
	if ( ! empty( $end_date ) ) {
		$args['end_date'] = $end_date;
	}
	if ( ! empty( $status ) ) {
		$args['status'] = $status;
	}

	if ( 'export_requests_csv' === $action ) {
		$csv_content = $export->export_leave_requests_csv( $args );
		$filename = 'leave-requests-' . date( 'Y-m-d-H-i-s' ) . '.csv';
		$export->download_csv( $filename, $csv_content );
	} elseif ( 'export_users_csv' === $action ) {
		$csv_content = $export->export_users_csv( $args );
		$filename = 'users-' . date( 'Y-m-d-H-i-s' ) . '.csv';
		$export->download_csv( $filename, $csv_content );
	}
}
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="export-container">
		<h2>Export Data</h2>
		<p>Export your leave management data in CSV format for further analysis or backup purposes.</p>

		<!-- Export Leave Requests -->
		<div class="export-section">
			<h3>Export Leave Requests</h3>
			<p>Download all leave requests data including employee information, dates, and status.</p>

			<form method="post" class="export-form">
				<?php wp_nonce_field( 'leave_manager_export', 'nonce' ); ?>
				<input type="hidden" name="action" value="export_requests_csv">

				<div class="form-row">
					<div class="form-group">
						<label for="requests_start_date">Start Date (Optional):</label>
						<input type="date" id="requests_start_date" name="start_date">
					</div>

					<div class="form-group">
						<label for="requests_end_date">End Date (Optional):</label>
						<input type="date" id="requests_end_date" name="end_date">
					</div>

					<div class="form-group">
						<label for="requests_status">Status (Optional):</label>
						<select id="requests_status" name="status">
							<option value="">All Statuses</option>
							<option value="pending">Pending</option>
							<option value="approved">Approved</option>
							<option value="rejected">Rejected</option>
						</select>
					</div>
				</div>

				<button type="submit" class="button button-primary">
					<span class="dashicons dashicons-download"></span> Export as CSV
				</button>
			</form>
		</div>

		<!-- Export Users -->
		<div class="export-section">
			<h3>Export Users</h3>
			<p>Download all user data including contact information, department, role, and leave balances.</p>

			<form method="post" class="export-form">
				<?php wp_nonce_field( 'leave_manager_export', 'nonce' ); ?>
				<input type="hidden" name="action" value="export_users_csv">

				<div class="form-row">
					<div class="form-group">
						<label for="users_role">Role (Optional):</label>
						<select id="users_role" name="role">
							<option value="">All Roles</option>
							<option value="staff">Staff</option>
							<option value="hr">HR</option>
							<option value="admin">Admin</option>
						</select>
					</div>

					<div class="form-group">
						<label for="users_status">Status (Optional):</label>
						<select id="users_status" name="status">
							<option value="">All Statuses</option>
							<option value="active">Active</option>
							<option value="inactive">Inactive</option>
						</select>
					</div>
				</div>

				<button type="submit" class="button button-primary">
					<span class="dashicons dashicons-download"></span> Export as CSV
				</button>
			</form>
		</div>

		<!-- Export Information -->
		<div class="export-info">
			<h3>Export Information</h3>
			<div class="info-box">
				<h4>What's Included in Leave Requests Export?</h4>
				<ul>
					<li>Request ID</li>
					<li>Employee Name and Email</li>
					<li>Department</li>
					<li>Leave Type (Annual, Sick, Other)</li>
					<li>Start and End Dates</li>
					<li>Reason for Leave</li>
					<li>Status (Pending, Approved, Rejected)</li>
					<li>Submission and Approval Dates</li>
				</ul>
			</div>

			<div class="info-box">
				<h4>What's Included in Users Export?</h4>
				<ul>
					<li>User ID and Name</li>
					<li>Email and Phone</li>
					<li>Department and Position</li>
					<li>Role (Staff, HR, Admin)</li>
					<li>Account Status</li>
					<li>Leave Balances (Annual, Sick, Other)</li>
					<li>Account Creation Date</li>
				</ul>
			</div>

			<div class="info-box">
				<h4>CSV Format Details</h4>
				<p>All exports are provided in CSV (Comma-Separated Values) format, which can be opened in:</p>
				<ul>
					<li>Microsoft Excel</li>
					<li>Google Sheets</li>
					<li>LibreOffice Calc</li>
					<li>Any text editor</li>
				</ul>
			</div>
		</div>
	</div>
</div>

<style>
	.export-container {
		max-width: 900px;
		margin-top: 20px;
	}

	.export-section {
		background: white;
		border: 1px solid #ddd;
		border-radius: 5px;
		padding: 20px;
		margin-bottom: 20px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.export-section h3 {
		margin-top: 0;
		color: #0073aa;
		border-bottom: 2px solid #0073aa;
		padding-bottom: 10px;
	}

	.export-section p {
		color: #666;
		margin-bottom: 15px;
	}

	.export-form {
		background: #f9f9f9;
		padding: 15px;
		border-radius: 4px;
	}

	.form-row {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
		gap: 15px;
		margin-bottom: 15px;
	}

	.form-group {
		display: flex;
		flex-direction: column;
	}

	.form-group label {
		font-weight: 600;
		margin-bottom: 5px;
		font-size: 14px;
	}

	.form-group input,
	.form-group select {
		padding: 8px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-size: 14px;
	}

	.form-group input:focus,
	.form-group select:focus {
		outline: none;
		border-color: #0073aa;
		box-shadow: 0 0 5px rgba(0, 115, 170, 0.3);
	}

	.export-form .button {
		margin-top: 10px;
	}

	.export-info {
		background: white;
		border: 1px solid #ddd;
		border-radius: 5px;
		padding: 20px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.export-info h3 {
		margin-top: 0;
		color: #0073aa;
		border-bottom: 2px solid #0073aa;
		padding-bottom: 10px;
	}

	.info-box {
		background: #f9f9f9;
		padding: 15px;
		border-radius: 4px;
		margin-bottom: 15px;
		border-left: 4px solid #0073aa;
	}

	.info-box h4 {
		margin-top: 0;
		color: #333;
	}

	.info-box ul {
		margin: 10px 0;
		padding-left: 20px;
	}

	.info-box li {
		margin: 5px 0;
		color: #666;
	}

	.dashicons {
		margin-right: 5px;
	}
</style>
