<?php
/**
 * Leave Types Management Page
 * 
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get leave types from database
global $wpdb;
$leave_types = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}leave_manager_leave_types ORDER BY type_name ASC" );
?>

<div class="leave-manager-admin-container">
	<div class="lm-page-content">
		<div class="page-header">
			<div>
				<h1><?php esc_html_e( 'Leave Types', 'leave-manager' ); ?></h1>
				<p class="subtitle"><?php esc_html_e( 'Configure types of leave available to your staff', 'leave-manager' ); ?></p>
			</div>
		</div>

		<div class="content-wrapper">
			<div class="content-main">
				<!-- Add Leave Type Form -->
				<div class="lm-card">
					<h2><?php esc_html_e( 'Add New Leave Type', 'leave-manager' ); ?></h2>
					
					<form id="add-leave-type-form" class="lm-form">
						<div class="lm-form-row">
							<div class="lm-form-group">
								<label for="type-name"><?php esc_html_e( 'Leave Type Name', 'leave-manager' ); ?> *</label>
								<input type="text" id="type-name" name="type_name" placeholder="e.g., Annual Leave" required>
								<div class="lm-form-description"><?php esc_html_e( 'Name of the leave type', 'leave-manager' ); ?></div>
							</div>
							<div class="lm-form-group">
								<label for="type-code"><?php esc_html_e( 'Leave Type Code', 'leave-manager' ); ?> *</label>
								<input type="text" id="type-code" name="type_code" placeholder="e.g., annual" required>
								<div class="lm-form-description"><?php esc_html_e( 'Unique code for this leave type', 'leave-manager' ); ?></div>
							</div>
						</div>

						<div class="lm-form-row">
							<div class="lm-form-group">
								<label for="default-days"><?php esc_html_e( 'Default Days Per Year', 'leave-manager' ); ?> *</label>
								<input type="number" id="default-days" name="default_days" min="0" max="365" step="0.5" placeholder="21" required>
								<div class="lm-form-description"><?php esc_html_e( 'Number of days allocated per year', 'leave-manager' ); ?></div>
							</div>
							<div class="lm-form-group">
								<label for="leave-color"><?php esc_html_e( 'Calendar Color', 'leave-manager' ); ?></label>
								<div class="lm-color-input-wrapper">
									<input type="color" id="leave-color" name="color" value="#4A5FFF">
									<input type="text" id="leave-color-text" value="#4A5FFF" placeholder="#4A5FFF">
								</div>
								<div class="lm-form-description"><?php esc_html_e( 'Color for calendar display', 'leave-manager' ); ?></div>
							</div>
						</div>

						<div class="lm-form-row">
							<div class="lm-form-group">
								<label class="lm-toggle-switch">
									<input type="checkbox" id="requires-approval" name="requires_approval" checked>
									<span><?php esc_html_e( 'Requires Approval', 'leave-manager' ); ?></span>
								</label>
								<div class="lm-form-description"><?php esc_html_e( 'Manager must approve this leave type', 'leave-manager' ); ?></div>
							</div>
							<div class="lm-form-group">
								<label class="lm-toggle-switch">
									<input type="checkbox" id="is-paid" name="is_paid" checked>
									<span><?php esc_html_e( 'Paid Leave', 'leave-manager' ); ?></span>
								</label>
								<div class="lm-form-description"><?php esc_html_e( 'This is paid leave', 'leave-manager' ); ?></div>
							</div>
						</div>

						<div class="lm-form-group">
							<label><?php esc_html_e( 'Allow Half Day Selection', 'leave-manager' ); ?></label>
							<div class="lm-form-row">
								<div class="lm-form-group">
									<label class="lm-toggle-switch">
										<input type="checkbox" id="allow-half-day" name="allow_half_day" checked>
										<span><?php esc_html_e( 'Allow Half Day Leave', 'leave-manager' ); ?></span>
									</label>
									<div class="lm-form-description"><?php esc_html_e( 'Staff can take half day for this leave type', 'leave-manager' ); ?></div>
								</div>
								<div class="lm-form-group">
									<label for="half-day-value"><?php esc_html_e( 'Half Day Value', 'leave-manager' ); ?></label>
									<input type="number" id="half-day-value" name="half_day_value" min="0" max="1" step="0.25" value="0.5">
									<div class="lm-form-description"><?php esc_html_e( 'How many days counts as half day', 'leave-manager' ); ?></div>
								</div>
							</div>
						</div>

						<div class="lm-form-group">
							<label for="description"><?php esc_html_e( 'Description', 'leave-manager' ); ?></label>
							<textarea id="description" name="description" placeholder="Brief description of this leave type"></textarea>
						</div>

						<div class="lm-form-actions">
							<button type="submit" class="lm-btn-primary"><?php esc_html_e( 'Add Leave Type', 'leave-manager' ); ?></button>
							<button type="reset" class="lm-btn-secondary"><?php esc_html_e( 'Clear', 'leave-manager' ); ?></button>
						</div>
					</form>
				</div>

				<!-- Leave Types List -->
				<div class="lm-card">
					<h2><?php esc_html_e( 'Existing Leave Types', 'leave-manager' ); ?></h2>
					
					<?php if ( ! empty( $leave_types ) ) : ?>
						<div class="lm-table-responsive">
							<table class="lm-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Leave Type', 'leave-manager' ); ?></th>
										<th><?php esc_html_e( 'Code', 'leave-manager' ); ?></th>
										<th><?php esc_html_e( 'Days/Year', 'leave-manager' ); ?></th>
										<th><?php esc_html_e( 'Type', 'leave-manager' ); ?></th>
										<th><?php esc_html_e( 'Status', 'leave-manager' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'leave-manager' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $leave_types as $type ) : ?>
										<tr>
											<td>
												<div style="display: flex; align-items: center;">
													<div style="width: 20px; height: 20px; background-color: <?php echo esc_attr( $type->color ); ?>; border-radius: 3px; margin-right: 10px;"></div>
													<strong><?php echo esc_html( $type->type_name ); ?></strong>
												</div>
											</td>
											<td><?php echo esc_html( $type->type_code ); ?></td>
											<td><?php echo esc_html( $type->default_days ); ?></td>
											<td>
												<?php 
													$leave_type = '';
													if ( $type->is_paid ) {
														$leave_type .= 'Paid ';
													} else {
														$leave_type .= 'Unpaid ';
													}
													if ( $type->requires_approval ) {
														$leave_type .= '(Approval Required)';
													}
													echo esc_html( $leave_type );
												?>
											</td>
											<td>
												<span class="lm-badge <?php echo $type->status === 'active' ? 'lm-badge-success' : 'lm-badge-warning'; ?>">
													<?php echo esc_html( ucfirst( $type->status ) ); ?>
												</span>
											</td>
											<td>
												<button class="lm-btn-small lm-btn-delete" onclick="deleteLeaveType(<?php echo intval( $type->type_id ); ?>)"><?php esc_html_e( 'Delete', 'leave-manager' ); ?></button>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else : ?>
						<div class="lm-empty-state">
							<p><?php esc_html_e( 'No leave types configured yet. Add one to get started.', 'leave-manager' ); ?></p>
							<button class="lm-btn-primary" onclick="document.querySelector('#add-leave-type-form').scrollIntoView({behavior: 'smooth'})"><?php esc_html_e( 'Create First Leave Type', 'leave-manager' ); ?></button>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Sidebar -->
			<div class="content-sidebar">
				<div class="lm-card">
					<h3><?php esc_html_e( 'Leave Type Management', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'Configure the types of leave available in your organization. Each leave type can have different rules and allocations.', 'leave-manager' ); ?></p>
				</div>

				<div class="lm-card">
					<h3><?php esc_html_e( 'Common Leave Types', 'leave-manager' ); ?></h3>
					<ul style="list-style: none; padding: 0;">
						<li><strong><?php esc_html_e( 'Annual Leave:', 'leave-manager' ); ?></strong> <?php esc_html_e( '21 days/year', 'leave-manager' ); ?></li>
						<li><strong><?php esc_html_e( 'Sick Leave:', 'leave-manager' ); ?></strong> <?php esc_html_e( '10 days/year', 'leave-manager' ); ?></li>
						<li><strong><?php esc_html_e( 'Study Leave:', 'leave-manager' ); ?></strong> <?php esc_html_e( '5 days/year', 'leave-manager' ); ?></li>
						<li><strong><?php esc_html_e( 'Other:', 'leave-manager' ); ?></strong> <?php esc_html_e( 'Custom', 'leave-manager' ); ?></li>
					</ul>
				</div>

				<div class="lm-card">
					<h3><?php esc_html_e( 'Day Selection', 'leave-manager' ); ?></h3>
					<p><?php esc_html_e( 'Staff can select full or half day leave when requesting time off. Half day values are customizable per leave type.', 'leave-manager' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
var lm_nonce = '<?php echo wp_create_nonce( 'leave_manager_admin_nonce' ); ?>';

// Color sync
document.getElementById('leave-color').addEventListener('change', function() {
	document.getElementById('leave-color-text').value = this.value;
});

document.getElementById('leave-color-text').addEventListener('change', function() {
	if (/^#[0-9A-F]{6}$/i.test(this.value)) {
		document.getElementById('leave-color').value = this.value;
	}
});

document.getElementById('add-leave-type-form').addEventListener('submit', function(e) {
	e.preventDefault();
	
	var data = {
		action: 'leave_manager_add_leave_type',
		nonce: lm_nonce,
		type_name: document.getElementById('type-name').value,
		type_code: document.getElementById('type-code').value,
		default_days: document.getElementById('default-days').value,
		color: document.getElementById('leave-color').value,
		requires_approval: document.getElementById('requires-approval').checked ? 1 : 0,
		is_paid: document.getElementById('is-paid').checked ? 1 : 0,
		allow_half_day: document.getElementById('allow-half-day').checked ? 1 : 0,
		half_day_value: document.getElementById('half-day-value').value,
		description: document.getElementById('description').value
	};

	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams(data)
	}).then(r => r.json()).then(response => {
		if (response.success) {
			alert('Leave type added successfully!');
			location.reload();
		} else {
			alert('Error: ' + (response.data || 'Failed to add leave type'));
		}
	}).catch(e => alert('Error: ' + e.message));
});

function deleteLeaveType(id) {
	if (!confirm('Are you sure you want to delete this leave type?')) return;
	
	fetch(ajaxurl, {
		method: 'POST',
		headers: {'Content-Type': 'application/x-www-form-urlencoded'},
		body: new URLSearchParams({
			action: 'leave_manager_delete_leave_type',
			nonce: lm_nonce,
			type_id: id
		})
	}).then(r => r.json()).then(response => {
		if (response.success) {
			alert('Leave type deleted successfully!');
			location.reload();
		} else {
			alert('Error: ' + (response.data || 'Failed to delete leave type'));
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

.lm-badge {
	padding: 4px 8px;
	border-radius: 3px;
	font-size: 12px;
	font-weight: 600;
}

.lm-badge-success {
	background-color: #10B981;
	color: white;
}

.lm-badge-warning {
	background-color: #F59E0B;
	color: white;
}

.lm-empty-state {
	text-align: center;
	padding: 40px;
	color: #999;
}

.lm-color-input-wrapper {
	display: flex;
	gap: 10px;
	align-items: center;
}

.lm-color-input-wrapper input[type="color"] {
	width: 50px;
	height: 40px;
	border: 1px solid #ddd;
	border-radius: 4px;
	cursor: pointer;
}

.lm-color-input-wrapper input[type="text"] {
	flex: 1;
	padding: 8px 12px;
	border: 1px solid #ddd;
	border-radius: 4px;
}
</style>
