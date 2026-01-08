<?php
/**
 * Staff Page - Professional Design with Full AJAX Functionality
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Get current tab
$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'users';

// Get data from the correct tables
$users_table = $wpdb->prefix . 'leave_manager_leave_users';
$policies_table = $wpdb->prefix . 'leave_manager_leave_policies';

$users = $wpdb->get_results( "SELECT * FROM {$users_table} ORDER BY last_name, first_name" );
$policies = $wpdb->get_results( "SELECT * FROM {$policies_table} ORDER BY policy_name" );

$total_users = count( $users );
$total_policies = count( $policies );

// Generate nonce for AJAX
$nonce = wp_create_nonce( 'leave_manager_admin_nonce' );

// Include admin page template styles
include 'admin-page-template.php';
?>

<div class="leave-manager-admin-container">
<div class="lm-page-content">
<!-- Page Header -->
<div class="page-header">
	<div>
		<h1><?php esc_html_e( 'Staff Management', 'leave-manager' ); ?></h1>
		<p class="subtitle"><?php esc_html_e( 'Manage users and leave policies', 'leave-manager' ); ?></p>
	</div>
</div>

<div class="admin-tabs">
	<button class="admin-tab <?php echo $current_tab === 'users' ? 'active' : ''; ?>" data-tab="users"><?php esc_html_e( 'Users', 'leave-manager' ); ?></button>
	<button class="admin-tab <?php echo $current_tab === 'policies' ? 'active' : ''; ?>" data-tab="policies"><?php esc_html_e( 'Leave Policies', 'leave-manager' ); ?></button>
</div>

<div class="content-wrapper">
	<div class="content-main">
		<!-- Users Tab -->
		<div class="lm-tab-content <?php echo $current_tab === 'users' ? 'active' : ''; ?>" id="users">
			<div class="lm-card">
				<h3><?php esc_html_e( 'Add New User', 'leave-manager' ); ?></h3>
				<form id="add-user-form" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
					<div>
						<label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php esc_html_e( 'First Name', 'leave-manager' ); ?> <span style="color: red;">*</span></label>
						<input type="text" name="first_name" id="new_first_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
					</div>
					<div>
						<label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php esc_html_e( 'Last Name', 'leave-manager' ); ?> <span style="color: red;">*</span></label>
						<input type="text" name="last_name" id="new_last_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
					</div>
					<div>
						<label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php esc_html_e( 'Email', 'leave-manager' ); ?> <span style="color: red;">*</span></label>
						<input type="email" name="email" id="new_email" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
					</div>
					<div>
						<label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php esc_html_e( 'Department', 'leave-manager' ); ?></label>
						<input type="text" name="department" id="new_department" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
					</div>
					<div>
						<label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php esc_html_e( 'Role', 'leave-manager' ); ?></label>
						<select name="role" id="new_role" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
							<option value="staff"><?php esc_html_e( 'Employee', 'leave-manager' ); ?></option>
							<option value="manager"><?php esc_html_e( 'Manager', 'leave-manager' ); ?></option>
							<option value="admin"><?php esc_html_e( 'Admin', 'leave-manager' ); ?></option>
						</select>
					</div>
					<div style="display: flex; align-items: flex-end;">
						<button type="submit" class="lm-btn-primary" style="width: 100%;"><?php esc_html_e( 'Add User', 'leave-manager' ); ?></button>
					</div>
				</form>
			</div>

			<div class="lm-card">
				<div class="lm-stat-grid">
					<div class="lm-stat-card">
						<div class="lm-stat-label"><?php esc_html_e( 'TOTAL USERS', 'leave-manager' ); ?></div>
						<div class="lm-stat-value" id="total-users-count"><?php echo esc_html( $total_users ); ?></div>
					</div>
				</div>

				<div class="lm-table-wrapper">
					<table class="lm-table" id="users-table">
						<thead>
							<tr>
<th><?php esc_html_e( 'Name', 'leave-manager' ); ?></th>
									<th><?php esc_html_e( 'Email', 'leave-manager' ); ?></th>
										<?php if ( class_exists( 'Leave_Manager_Department_Toggle') && Leave_Manager_Department_Toggle::is_enabled() ) { ?>
										<th><?php esc_html_e( 'Department', 'leave-manager' ); ?></th>
										<?php } ?>
									<th><?php esc_html_e( 'Role', 'leave-manager' ); ?></th>
									<th><?php esc_html_e( 'Leave Balance', 'leave-manager' ); ?></th>
									<th><?php esc_html_e( 'Actions', 'leave-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $users ) ) : ?>
								<?php foreach ( $users as $user ) : ?>
									<tr id="user-row-<?php echo esc_attr( $user->user_id ); ?>">
										<td><strong><?php echo esc_html( $user->first_name . ' ' . $user->last_name ); ?></strong></td>
										<td><?php echo esc_html( $user->email ); ?></td>
										<td><?php echo esc_html( $user->department ?? 'N/A' ); ?></td>
<td><span class="lm-badge lm-badge-<?php echo esc_attr( strtolower( $user->role ?? 'staff' ) ); ?>"><?php echo esc_html( ucfirst( $user->role ?? 'Employee' ) ); ?></span></td>
											<td>
												<div style="font-size: 12px; line-height: 1.6;">
													<span style="color: #2271b1;" title="Annual Leave">🏖️ <?php echo esc_html( number_format( $user->annual_leave_balance ?? 0, 1 ) ); ?></span>
													<span style="color: #d63638; margin-left: 8px;" title="Sick Leave">🏥 <?php echo esc_html( number_format( $user->sick_leave_balance ?? 0, 1 ) ); ?></span>
													<span style="color: #dba617; margin-left: 8px;" title="Other Leave">📋 <?php echo esc_html( number_format( $user->other_leave_balance ?? 0, 1 ) ); ?></span>
												</div>
											</td>
											<td>
												<div class="lm-action-buttons">
												<button class="lm-btn-view" onclick="editUser(<?php echo esc_attr( $user->user_id ); ?>, '<?php echo esc_js( $user->first_name ); ?>', '<?php echo esc_js( $user->last_name ); ?>', '<?php echo esc_js( $user->email ); ?>', '<?php echo esc_js( $user->department ?? '' ); ?>', '<?php echo esc_js( $user->role ?? 'staff' ); ?>')"><?php esc_html_e( 'Edit', 'leave-manager' ); ?></button>
												<button class="lm-btn-reject" onclick="deleteUser(<?php echo esc_attr( $user->user_id ); ?>, '<?php echo esc_js( $user->first_name . ' ' . $user->last_name ); ?>')"><?php esc_html_e( 'Delete', 'leave-manager' ); ?></button>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr id="no-users-row">
									<td colspan="6" style="text-align: center; padding: 40px;">
										<div class="lm-empty-state">
											<div class="lm-empty-state-icon">👥</div>
											<h3><?php esc_html_e( 'No Users', 'leave-manager' ); ?></h3>
											<p><?php esc_html_e( 'No staff members found. Add your first user above.', 'leave-manager' ); ?></p>
										</div>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<!-- Policies Tab -->
		<div class="lm-tab-content <?php echo $current_tab === 'policies' ? 'active' : ''; ?>" id="policies">
			<div class="lm-card">
				<div class="lm-stat-grid">
					<div class="lm-stat-card">
						<div class="lm-stat-label"><?php esc_html_e( 'TOTAL POLICIES', 'leave-manager' ); ?></div>
						<div class="lm-stat-value"><?php echo esc_html( $total_policies ); ?></div>
					</div>
				</div>

				<div class="lm-table-wrapper">
					<table class="lm-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Policy Name', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Description', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Annual Days', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Sick Days', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Other Days', 'leave-manager' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'leave-manager' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( ! empty( $policies ) ) : ?>
								<?php foreach ( $policies as $policy ) : ?>
									<tr>
										<td><strong><?php echo esc_html( $policy->policy_name ); ?></strong></td>
										<td><?php echo esc_html( substr( $policy->description, 0, 50 ) ); ?></td>
										<td><?php echo esc_html( $policy->annual_days ?? 0 ); ?></td>
										<td><?php echo esc_html( $policy->sick_days ?? 0 ); ?></td>
										<td><?php echo esc_html( $policy->other_days ?? 0 ); ?></td>
										<td>
											<div class="lm-action-buttons">
												<button class="lm-btn-view" onclick="editPolicy(<?php echo esc_attr( $policy->policy_id ); ?>)"><?php esc_html_e( 'Edit', 'leave-manager' ); ?></button>
												<button class="lm-btn-reject" onclick="deletePolicy(<?php echo esc_attr( $policy->policy_id ); ?>)"><?php esc_html_e( 'Delete', 'leave-manager' ); ?></button>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php else : ?>
								<tr>
									<td colspan="6" style="text-align: center; padding: 40px;">
										<div class="lm-empty-state">
											<div class="lm-empty-state-icon">📋</div>
											<h3><?php esc_html_e( 'No Policies', 'leave-manager' ); ?></h3>
											<p><?php esc_html_e( 'No leave policies found', 'leave-manager' ); ?></p>
										</div>
									</td>
								</tr>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>

	<!-- Sidebar -->
	<div class="content-sidebar">
		<div class="lm-card">
			<h3><?php esc_html_e( 'Staff Summary', 'leave-manager' ); ?></h3>
			<p><?php esc_html_e( 'Total Users: ', 'leave-manager' ); ?><strong id="sidebar-total-users"><?php echo esc_html( $total_users ); ?></strong></p>
			<p><?php esc_html_e( 'Total Policies: ', 'leave-manager' ); ?><strong><?php echo esc_html( $total_policies ); ?></strong></p>
		</div>

		<div class="lm-card">
			<h3><?php esc_html_e( 'Quick Links', 'leave-manager' ); ?></h3>
			<ul style="list-style: none; padding: 0;">
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-management' ) ); ?>"><?php esc_html_e( 'Dashboard', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-requests' ) ); ?>"><?php esc_html_e( 'Requests', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-reports' ) ); ?>"><?php esc_html_e( 'Reports', 'leave-manager' ); ?></a></li>
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'leave-manager' ); ?></a></li>
			</ul>
		</div>
	</div>
</div>

<!-- Edit User Modal -->
<div id="edit-user-modal" class="lm-modal" style="display: none;">
	<div class="lm-modal-content">
		<div class="lm-modal-header">
			<h3><?php esc_html_e( 'Edit User', 'leave-manager' ); ?></h3>
			<button class="lm-modal-close" onclick="closeEditModal()">&times;</button>
		</div>
		<div class="lm-modal-body">
			<form id="edit-user-form">
				<input type="hidden" id="edit_user_id" name="staff_id">
				<div style="margin-bottom: 15px;">
					<label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php esc_html_e( 'First Name', 'leave-manager' ); ?> <span style="color: red;">*</span></label>
					<input type="text" name="first_name" id="edit_first_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
				</div>
				<div style="margin-bottom: 15px;">
					<label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php esc_html_e( 'Last Name', 'leave-manager' ); ?> <span style="color: red;">*</span></label>
					<input type="text" name="last_name" id="edit_last_name" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
				</div>
				<div style="margin-bottom: 15px;">
					<label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php esc_html_e( 'Email', 'leave-manager' ); ?> <span style="color: red;">*</span></label>
					<input type="email" name="email" id="edit_email" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
				</div>
				<div style="margin-bottom: 15px;">
					<label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php esc_html_e( 'Department', 'leave-manager' ); ?></label>
					<input type="text" name="department" id="edit_department" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
				</div>
				<div style="margin-bottom: 15px;">
					<label style="display: block; margin-bottom: 5px; font-weight: 500;"><?php esc_html_e( 'Role', 'leave-manager' ); ?></label>
					<select name="role" id="edit_role" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
						<option value="staff"><?php esc_html_e( 'Employee', 'leave-manager' ); ?></option>
						<option value="manager"><?php esc_html_e( 'Manager', 'leave-manager' ); ?></option>
						<option value="admin"><?php esc_html_e( 'Admin', 'leave-manager' ); ?></option>
					</select>
				</div>
				<div style="display: flex; gap: 10px; justify-content: flex-end;">
					<button type="button" class="lm-btn-secondary" onclick="closeEditModal()"><?php esc_html_e( 'Cancel', 'leave-manager' ); ?></button>
					<button type="submit" class="lm-btn-primary"><?php esc_html_e( 'Save Changes', 'leave-manager' ); ?></button>
				</div>
			</form>
		</div>
	</div>
</div>

<!-- Notification Container -->
<div id="lm-notification" style="display: none; position: fixed; top: 50px; right: 20px; padding: 15px 25px; border-radius: 4px; z-index: 10000; font-weight: 500;"></div>

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
.lm-badge {
	padding: 4px 8px;
	border-radius: 4px;
	font-size: 12px;
	font-weight: 500;
}
.lm-badge-staff { background: #E0F2FE; color: #0369A1; }
.lm-badge-manager { background: #FEF3C7; color: #92400E; }
.lm-badge-admin { background: #FCE7F3; color: #9D174D; }
</style>

<script>
jQuery(document).ready(function($) {
    var lm_nonce = '<?php echo esc_js( $nonce ); ?>';
    var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
    
    // Make functions globally available
    window.lm_nonce = lm_nonce;
    window.ajaxurl = ajaxurl;

// Tab switching
document.querySelectorAll('.admin-tab').forEach(button => {
	button.addEventListener('click', function() {
		const tabId = this.getAttribute('data-tab');
		const url = new URL(window.location);
		url.searchParams.set('tab', tabId);
		window.location = url.toString();
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

// Add User Form Submit
document.getElementById('add-user-form').addEventListener('submit', function(e) {
	e.preventDefault();
	
	const formData = new FormData();
	formData.append('action', 'leave_manager_add_staff');
	formData.append('nonce', lm_nonce);
	formData.append('first_name', document.getElementById('new_first_name').value);
	formData.append('last_name', document.getElementById('new_last_name').value);
	formData.append('email', document.getElementById('new_email').value);
	formData.append('department', document.getElementById('new_department').value);
	formData.append('role', document.getElementById('new_role').value);
	
	fetch(ajaxurl, {
		method: 'POST',
		body: formData,
		credentials: 'same-origin'
	})
	.then(response => response.json())
	.then(data => {
		if (data.success) {
			showNotification('User added successfully!', 'success');
			// Reload page to show new user
			setTimeout(() => location.reload(), 1000);
		} else {
			showNotification(data.data.message || 'Failed to add user', 'error');
		}
	})
	.catch(error => {
		console.error('Error:', error);
		showNotification('An error occurred', 'error');
	});
});

// Edit User
window.editUser = function(id, firstName, lastName, email, department, role) {
	document.getElementById('edit_user_id').value = id;
	document.getElementById('edit_first_name').value = firstName;
	document.getElementById('edit_last_name').value = lastName;
	document.getElementById('edit_email').value = email;
	document.getElementById('edit_department').value = department;
	document.getElementById('edit_role').value = role;
	document.getElementById('edit-user-modal').style.display = 'flex';
};

window.closeEditModal = function() {
	document.getElementById('edit-user-modal').style.display = 'none';
};

// Edit User Form Submit
document.getElementById('edit-user-form').addEventListener('submit', function(e) {
	e.preventDefault();
	
	const formData = new FormData();
	formData.append('action', 'leave_manager_edit_staff');
	formData.append('nonce', lm_nonce);
	formData.append('staff_id', document.getElementById('edit_user_id').value);
	formData.append('first_name', document.getElementById('edit_first_name').value);
	formData.append('last_name', document.getElementById('edit_last_name').value);
	formData.append('email', document.getElementById('edit_email').value);
	formData.append('department', document.getElementById('edit_department').value);
	formData.append('role', document.getElementById('edit_role').value);
	
	fetch(ajaxurl, {
		method: 'POST',
		body: formData,
		credentials: 'same-origin'
	})
	.then(response => response.json())
	.then(data => {
		if (data.success) {
			showNotification('User updated successfully!', 'success');
			closeEditModal();
			setTimeout(() => location.reload(), 1000);
		} else {
			showNotification(data.data.message || 'Failed to update user', 'error');
		}
	})
	.catch(error => {
		console.error('Error:', error);
		showNotification('An error occurred', 'error');
	});
});

// Delete User
window.deleteUser = function(id, name) {
	if (!confirm('Are you sure you want to delete ' + name + '? This action cannot be undone.')) {
		return;
	}
	
	const formData = new FormData();
	formData.append('action', 'leave_manager_delete_staff');
	formData.append('nonce', lm_nonce);
	formData.append('staff_id', id);
	
	fetch(ajaxurl, {
		method: 'POST',
		body: formData,
		credentials: 'same-origin'
	})
	.then(response => response.json())
	.then(data => {
		if (data.success) {
			showNotification('User deleted successfully!', 'success');
			// Remove row from table
			const row = document.getElementById('user-row-' + id);
			if (row) row.remove();
			// Update count
			const countEl = document.getElementById('total-users-count');
			const sidebarCountEl = document.getElementById('sidebar-total-users');
			if (countEl) countEl.textContent = parseInt(countEl.textContent) - 1;
			if (sidebarCountEl) sidebarCountEl.textContent = parseInt(sidebarCountEl.textContent) - 1;
		} else {
			showNotification(data.data.message || 'Failed to delete user', 'error');
		}
	})
	.catch(error => {
		console.error('Error:', error);
		showNotification('An error occurred', 'error');
	});
};

// Policy functions (placeholder for now)
window.editPolicy = function(id) {
	alert('Edit policy ' + id + ' - Feature coming soon');
};

window.deletePolicy = function(id) {
	alert('Delete policy ' + id + ' - Feature coming soon');
};

}); // End jQuery document ready
</script>
<?php
// Close the container divs
echo '</div>';
echo '</div>';
?>
