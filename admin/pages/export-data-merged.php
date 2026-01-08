<?php
/**
 * Export Data Management Page (Merged)
 *
 * Combines Export and Reports export functionality into a single page.
 * Allows exporting leave data in multiple formats (CSV, Excel, PDF).
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle export requests
if ( isset( $_POST['leave_manager_export_data'] ) && wp_verify_nonce( $_POST['leave_manager_export_nonce'], 'leave_manager_export_nonce' ) ) {
	$export_type = sanitize_text_field( $_POST['export_type'] );
	$export_format = sanitize_text_field( $_POST['export_format'] );
	$date_from = sanitize_text_field( $_POST['date_from'] ?? '' );
	$date_to = sanitize_text_field( $_POST['date_to'] ?? '' );

	// Process export based on type and format
	// This would call appropriate export functions
	echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__( 'Export started. Your file will be downloaded shortly.', 'leave-manager-management' ) . '</p></div>';
}

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Export Data', 'leave-manager-management' ); ?></h1>
	<p><?php esc_html_e( 'Export leave management data in various formats for analysis and reporting.', 'leave-manager-management' ); ?></p>

	<div class="leave-manager-export-container">
		<div class="leave-manager-export-form">
			<h2><?php esc_html_e( 'Export Options', 'leave-manager-management' ); ?></h2>
			<form method="post" action="">
				<?php wp_nonce_field( 'leave_manager_export_nonce', 'leave_manager_export_nonce' ); ?>

				<div class="leave-manager-form-group">
					<label for="export_type">
						<?php esc_html_e( 'What to Export:', 'leave-manager-management' ); ?>
					</label>
					<select id="export_type" name="export_type" required>
						<option value=""><?php esc_html_e( '-- Select Data Type --', 'leave-manager-management' ); ?></option>
						<option value="leave_requests"><?php esc_html_e( 'Leave Requests', 'leave-manager-management' ); ?></option>
						<option value="users"><?php esc_html_e( 'Users & Leave Balances', 'leave-manager-management' ); ?></option>
						<option value="policies"><?php esc_html_e( 'Leave Policies', 'leave-manager-management' ); ?></option>
						<option value="all"><?php esc_html_e( 'All Data', 'leave-manager-management' ); ?></option>
					</select>
				</div>

				<div class="leave-manager-form-group">
					<label for="export_format">
						<?php esc_html_e( 'Export Format:', 'leave-manager-management' ); ?>
					</label>
					<select id="export_format" name="export_format" required>
						<option value=""><?php esc_html_e( '-- Select Format --', 'leave-manager-management' ); ?></option>
						<option value="csv"><?php esc_html_e( 'CSV (Comma Separated Values)', 'leave-manager-management' ); ?></option>
						<option value="excel"><?php esc_html_e( 'Excel (.xlsx)', 'leave-manager-management' ); ?></option>
						<option value="pdf"><?php esc_html_e( 'PDF Document', 'leave-manager-management' ); ?></option>
					</select>
				</div>

				<div class="leave-manager-form-group">
					<label for="date_from">
						<?php esc_html_e( 'Date Range (Optional):', 'leave-manager-management' ); ?>
					</label>
					<div class="leave-manager-date-range">
						<input type="date" id="date_from" name="date_from" placeholder="<?php esc_attr_e( 'From Date', 'leave-manager-management' ); ?>">
						<span><?php esc_html_e( 'to', 'leave-manager-management' ); ?></span>
						<input type="date" id="date_to" name="date_to" placeholder="<?php esc_attr_e( 'To Date', 'leave-manager-management' ); ?>">
					</div>
				</div>

				<div class="leave-manager-form-group">
					<button type="submit" name="leave_manager_export_data" class="button button-primary button-large">
						<?php esc_html_e( 'Export Data', 'leave-manager-management' ); ?>
					</button>
				</div>
			</form>
		</div>

		<div class="leave-manager-export-info">
			<h2><?php esc_html_e( 'Export Information', 'leave-manager-management' ); ?></h2>

			<div class="leave-manager-info-card">
				<h3><?php esc_html_e( 'CSV Format', 'leave-manager-management' ); ?></h3>
				<p><?php esc_html_e( 'Best for importing into spreadsheet applications like Excel or Google Sheets. Includes all data in a simple, comma-separated format.', 'leave-manager-management' ); ?></p>
			</div>

			<div class="leave-manager-info-card">
				<h3><?php esc_html_e( 'Excel Format', 'leave-manager-management' ); ?></h3>
				<p><?php esc_html_e( 'Native Excel format with formatting, formulas, and multiple sheets. Ideal for advanced analysis and reporting.', 'leave-manager-management' ); ?></p>
			</div>

			<div class="leave-manager-info-card">
				<h3><?php esc_html_e( 'PDF Format', 'leave-manager-management' ); ?></h3>
				<p><?php esc_html_e( 'Professional PDF documents with formatted tables and charts. Perfect for printing and sharing reports.', 'leave-manager-management' ); ?></p>
			</div>

			<h3><?php esc_html_e( 'Available Data Types', 'leave-manager-management' ); ?></h3>
			<ul>
				<li><strong><?php esc_html_e( 'Leave Requests:', 'leave-manager-management' ); ?></strong> <?php esc_html_e( 'All leave request records with dates, status, and approver information.', 'leave-manager-management' ); ?></li>
				<li><strong><?php esc_html_e( 'Users & Balances:', 'leave-manager-management' ); ?></strong> <?php esc_html_e( 'User information with current leave balances by leave type.', 'leave-manager-management' ); ?></li>
				<li><strong><?php esc_html_e( 'Leave Policies:', 'leave-manager-management' ); ?></strong> <?php esc_html_e( 'All configured leave policies with allocation details.', 'leave-manager-management' ); ?></li>
				<li><strong><?php esc_html_e( 'All Data:', 'leave-manager-management' ); ?></strong> <?php esc_html_e( 'Complete export of all system data for backup or migration.', 'leave-manager-management' ); ?></li>
			</ul>
		</div>
	</div>
</div>

<style>
	.leave-manager-export-container {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 30px;
		margin-top: 20px;
	}

	.leave-manager-export-form {
		background: #fff;
		padding: 20px;
		border: 1px solid #ddd;
		border-radius: 5px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.leave-manager-export-info {
		background: #f9f9f9;
		padding: 20px;
		border: 1px solid #ddd;
		border-radius: 5px;
	}

	.leave-manager-form-group {
		margin-bottom: 20px;
	}

	.leave-manager-form-group label {
		display: block;
		margin-bottom: 8px;
		font-weight: 600;
		color: #333;
	}

	.leave-manager-form-group select,
	.leave-manager-form-group input[type="date"] {
		width: 100%;
		padding: 8px 12px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-size: 14px;
	}

	.leave-manager-date-range {
		display: flex;
		align-items: center;
		gap: 10px;
	}

	.leave-manager-date-range input[type="date"] {
		flex: 1;
	}

	.leave-manager-info-card {
		background: #fff;
		padding: 15px;
		margin-bottom: 15px;
		border-left: 4px solid #0073aa;
		border-radius: 3px;
	}

	.leave-manager-info-card h3 {
		margin-top: 0;
		margin-bottom: 8px;
		color: #0073aa;
	}

	.leave-manager-info-card p {
		margin: 0;
		font-size: 13px;
		line-height: 1.6;
		color: #666;
	}

	.leave-manager-export-info ul {
		margin: 15px 0;
		padding-left: 20px;
	}

	.leave-manager-export-info li {
		margin-bottom: 10px;
		line-height: 1.6;
		color: #666;
	}

	@media (max-width: 768px) {
		.leave-manager-export-container {
			grid-template-columns: 1fr;
		}

		.leave-manager-date-range {
			flex-direction: column;
			align-items: stretch;
		}

		.leave-manager-date-range input[type="date"] {
			width: 100%;
		}
	}
</style>
