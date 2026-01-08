<?php
/**
 * Public Holidays Management Page
 * 
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get holidays from database
global $wpdb;
$holidays = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}leave_manager_public_holidays ORDER BY holiday_date DESC" );
?>

<div class="leave-manager-admin-container">
	<div class="lm-page-content">
		<div class="page-header">
			<div>
				<h1><?php esc_html_e( 'Public Holidays', 'leave-manager' ); ?></h1>
				<p class="subtitle"><?php esc_html_e( 'Manage public holidays and special days', 'leave-manager' ); ?></p>
			</div>
		</div>

		<div class="content-wrapper">
			<div class="content-main">
				<!-- Add Holiday Form -->
				<div class="lm-card">
					<h2><?php esc_html_e( 'Add New Holiday', 'leave-manager' ); ?></h2>
					
					<form id="add-holiday-form" class="lm-form">
						<div class="lm-form-row">
							<div class="lm-form-group">
								<label for="holiday-name"><?php esc_html_e( 'Holiday Name', 'leave-manager' ); ?> *</label>
								<input type="text" id="holiday-name" name="holiday_name" placeholder="e.g., Christmas Day" required>
								<div class="lm-form-description"><?php esc_html_e( 'Name of the public holiday', 'leave-manager' ); ?></div>
							</div>
							<div class="lm-form-group">
								<label for="holiday-date"><?php esc_html_e( 'Date', 'leave-manager' ); ?> *</label>
								<input type="date" id="holiday-date" name="holiday_date" required>
								<div class="lm-form-description"><?php esc_html_e( 'Date of the holiday', 'leave-manager' ); ?></div>
							</div>
						</div>

						<div class="lm-form-row">
							<div class="lm-form-group">
								<label for="country-code"><?php esc_html_e( 'Country Code', 'leave-manager' ); ?></label>
								<select id="country-code" name="country_code">
									<option value="">Select Country</option>
									<option value="ZA">South Africa</option>
									<option value="US">United States</option>
									<option value="GB">United Kingdom</option>
									<option value="AU">Australia</option>
									<option value="CA">Canada</option>
									<option value="IN">India</option>
									<option value="ZW">Zimbabwe</option>
									<option value="NG">Nigeria</option>
									<option value="KE">Kenya</option>
									<option value="UG">Uganda</option>
								</select>
								<div class="lm-form-description"><?php esc_html_e( 'Country for which this holiday applies', 'leave-manager' ); ?></div>
							</div>
							<div class="lm-form-group">
								<label for="holiday-year"><?php esc_html_e( 'Year', 'leave-manager' ); ?></label>
								<input type="number" id="holiday-year" name="holiday_year" min="2020" max="2099" placeholder="2026">
								<div class="lm-form-description"><?php esc_html_e( 'Leave empty for all years', 'leave-manager' ); ?></div>
							</div>
						</div>

						<div class="lm-form-row">
							<div class="lm-form-group">
								<label class="lm-toggle-switch">
									<input type="checkbox" id="is-recurring" name="is_recurring">
									<span><?php esc_html_e( 'Recurring Holiday', 'leave-manager' ); ?></span>
								</label>
								<div class="lm-form-description"><?php esc_html_e( 'Check if this holiday repeats every year', 'leave-manager' ); ?></div>
							</div>
							<div class="lm-form-group">
								<label class="lm-toggle-switch">
									<input type="checkbox" id="is-optional" name="is_optional">
									<span><?php esc_html_e( 'Optional Holiday', 'leave-manager' ); ?></span>
								</label>
								<div class="lm-form-description"><?php esc_html_e( 'Staff can choose to take this day', 'leave-manager' ); ?></div>
							</div>
						</div>

						<div class="lm-form-group">
							<label for="holiday-description"><?php esc_html_e( 'Description', 'leave-manager' ); ?></label>
							<textarea id="holiday-description" name="description" placeholder="Additional details about this holiday"></textarea>
						</div>

						<div class="lm-form-actions">
							<button type="submit" class="lm-btn-primary"><?php esc_html_e( 'Add Holiday', 'leave-manager' ); ?></button>
							<button type="reset" class="lm-btn-secondary"><?php esc_html_e( 'Clear', 'leave-manager' ); ?></button>
						</div>
					</form>
				</div>

				<!-- Holidays List -->
				<div class="lm-card">
					<h2><?php esc_html_e( 'Existing Holidays', 'leave-manager' ); ?></h2>
					
					<?php if ( ! empty( $holidays ) ) : ?>
						<div class="lm-table-responsive">
							<table class="lm-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Holiday Name', 'leave-manager' ); ?></th>
										<th><?php esc_html_e( 'Date', 'leave-manager' ); ?></th>
										<th><?php esc_html_e( 'Country', 'leave-manager' ); ?></th>
										<th><?php esc_html_e( 'Type', 'leave-manager' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'leave-manager' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $holidays as $holiday ) : ?>
										<tr>
											<td><strong><?php echo esc_html( $holiday->holiday_name ); ?></strong></td>
											<td><?php echo esc_html( $holiday->holiday_date ); ?></td>
											<td><?php echo esc_html( $holiday->country_code ?? 'All' ); ?></td>
											<td>
												<?php 
													$type = '';
													if ( $holiday->is_recurring ) {
														$type .= 'Recurring ';
													}
													if ( $holiday->is_optional ) {
														$type .= 'Optional';
													}
													if ( empty( $type ) ) {
														$type = 'Fixed';
													}
													echo esc_html( $type );
												?>
											</td>
											<td>
												<button class="lm-btn-small lm-btn-delete" onclick="deleteHoliday(<?php echo intval( $holiday->id ); ?>)"><?php esc_html_e( 'Delete', 'leave-manager' ); ?></button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else : ?>
						<div class="lm-empty-state">
							<p><?php esc_html_e( 'No holidays configured yet. Add one to get started.', 'leave-manager' ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Sidebar -->
			<div class="content-sidebar">
				<div class="lm-card">
					<h3><?php esc_html_e( 'Holiday Management', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'Configure public holidays that apply to your organization. These days will be excluded from leave calculations.', 'leave-manager' ); ?></p>
				</div>

				<div class="lm-card">
					<h3><?php esc_html_e( 'Holiday Types', 'leave-manager' ); ?></h3>
					<ul style="list-style: none; padding: 0;">
						<li><strong><?php esc_html_e( 'Fixed:', 'leave-manager' ); ?></strong> <?php esc_html_e( 'Same date every year', 'leave-manager' ); ?></li>
						<li><strong><?php esc_html_e( 'Recurring:', 'leave-manager' ); ?></strong> <?php esc_html_e( 'Repeats annually', 'leave-manager' ); ?></li>
						<li><strong><?php esc_html_e( 'Optional:', 'leave-manager' ); ?></strong> <?php esc_html_e( 'Staff choice', 'leave-manager' ); ?></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
var lm_nonce = '<?php echo wp_create_nonce( 'leave_manager_admin_nonce' ); ?>';

document.getElementById('add-holiday-form').addEventListener('submit', function(e) {
	e.preventDefault();
	
	var data = {
		action: 'leave_manager_add_public_holiday',
		nonce: lm_nonce,
		holiday_name: document.getElementById('holiday-name').value,
		holiday_date: document.getElementById('holiday-date').value,
		country_code: document.getElementById('country-code').value,
		holiday_year: document.getElementById('holiday-year').value,
		is_recurring: document.getElementById('is-recurring').checked ? 1 : 0,
		is_optional: document.getElementById('is-optional').checked ? 1 : 0
	};

	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams(data)
	}).then(r => r.json()).then(response => {
		if (response.success) {
			alert('Holiday added successfully!');
			location.reload();
		} else {
			alert('Error: ' + (response.data || 'Failed to add holiday'));
		}
	}).catch(e => alert('Error: ' + e.message));
});

function deleteHoliday(id) {
	if (!confirm('Are you sure you want to delete this holiday?')) return;
	
	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams({
			action: 'leave_manager_delete_public_holiday',
			nonce: lm_nonce,
			id: id
		})
	}).then(r => r.json()).then(response => {
		if (response.success) {
			alert('Holiday deleted successfully!');
			location.reload();
		} else {
			alert('Error: ' + (response.data || 'Failed to delete holiday'));
		}
	}).catch(e => alert('Error: ' + e.message));
}
</script>

<style>
.lm-table-responsive {
	overflow-x: auto;
}

.lm-table {
	width: 100%;
	border-collapse: collapse;
	margin-top: 15px;
}

.lm-table thead {
	background-color: #f5f5f5;
	border-bottom: 2px solid #ddd;
}

.lm-table th {
	padding: 12px;
	text-align: left;
	font-weight: 600;
	color: #333;
}

.lm-table td {
	padding: 12px;
	border-bottom: 1px solid #eee;
}

.lm-table tbody tr:hover {
	background-color: #f9f9f9;
}

.lm-btn-small {
	padding: 6px 12px;
	font-size: 12px;
	margin-right: 5px;
	border: none;
	border-radius: 4px;
	cursor: pointer;
	transition: all 0.3s;
}

.lm-btn-delete {
	background-color: #EF4444;
	color: white;
}

.lm-btn-delete:hover {
	background-color: #dc2626;
}

.lm-empty-state {
	text-align: center;
	padding: 40px;
	color: #999;
}
</style>
