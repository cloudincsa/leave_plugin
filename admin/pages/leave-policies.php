<?php
/**
 * Leave Policies Management Page
 *
 * Allows admins to create, edit, and assign leave policies to staff members.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user permissions
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized access' );
}

// Initialize classes
$db                = new Leave_Manager_Database();
$logger            = new Leave_Manager_Logger( $db );
$leave_policies    = new Leave_Manager_Leave_Policies( $db, $logger );
$users_class       = new Leave_Manager_Users( $db, $logger );

// Handle form submissions
$message = '';
$error   = '';

// Create new policy
if ( isset( $_POST['create_policy'] ) && check_admin_referer( 'create_policy_nonce' ) ) {
	$policy_data = array(
		'policy_name'    => sanitize_text_field( $_POST['policy_name'] ?? '' ),
		'description'    => sanitize_textarea_field( $_POST['description'] ?? '' ),
		'leave_type'     => sanitize_text_field( $_POST['leave_type'] ?? 'annual' ),
		'annual_days'    => floatval( $_POST['annual_days'] ?? 20 ),
		'carryover_days' => floatval( $_POST['carryover_days'] ?? 5 ),
		'expiry_days'    => intval( $_POST['expiry_days'] ?? 365 ),
		'status'         => 'active',
	);

	if ( ! empty( $policy_data['policy_name'] ) ) {
		$policy_id = $leave_policies->create_policy( $policy_data );
		if ( $policy_id ) {
			$message = 'Leave policy created successfully!';
		} else {
			$error = 'Failed to create leave policy. Please try again.';
		}
	} else {
		$error = 'Policy name is required.';
	}
}

// Update policy
if ( isset( $_POST['update_policy'] ) && check_admin_referer( 'update_policy_nonce' ) ) {
	$policy_id   = intval( $_POST['policy_id'] ?? 0 );
	$policy_data = array(
		'policy_name'    => sanitize_text_field( $_POST['policy_name'] ?? '' ),
		'description'    => sanitize_textarea_field( $_POST['description'] ?? '' ),
		'annual_days'    => floatval( $_POST['annual_days'] ?? 20 ),
		'carryover_days' => floatval( $_POST['carryover_days'] ?? 5 ),
		'expiry_days'    => intval( $_POST['expiry_days'] ?? 365 ),
		'status'         => sanitize_text_field( $_POST['status'] ?? 'active' ),
	);

	if ( $leave_policies->update_policy( $policy_id, $policy_data ) ) {
		$message = 'Leave policy updated successfully!';
	} else {
		$error = 'Failed to update leave policy.';
	}
}

// Assign policy to user
if ( isset( $_POST['assign_policy'] ) && check_admin_referer( 'assign_policy_nonce' ) ) {
	$user_id   = intval( $_POST['user_id'] ?? 0 );
	$policy_id = intval( $_POST['policy_id'] ?? 0 );

	if ( $user_id && $policy_id ) {
		if ( $leave_policies->assign_policy_to_user( $user_id, $policy_id ) ) {
			$message = 'Policy assigned to staff member successfully!';
		} else {
			$error = 'Failed to assign policy to staff member.';
		}
	} else {
		$error = 'Please select both a staff member and a policy.';
	}
}

// Delete policy
if ( isset( $_GET['delete_policy'] ) && check_admin_referer( 'delete_policy_nonce' ) ) {
	$policy_id = intval( $_GET['delete_policy'] );
	if ( $leave_policies->delete_policy( $policy_id ) ) {
		$message = 'Leave policy deleted successfully!';
	} else {
		$error = 'Failed to delete leave policy.';
	}
}

// Get all policies
$all_policies = $leave_policies->get_all_policies( array( 'status' => '', 'limit' => -1 ) );

// Get all staff members
$staff_members = $users_class->get_users( array( 'role' => 'staff', 'limit' => -1 ) );

// Get policy for editing
$edit_policy = null;
if ( isset( $_GET['edit_policy'] ) ) {
	$edit_policy = $leave_policies->get_policy( intval( $_GET['edit_policy'] ) );
}

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

	<div class="leave-manager-container">
		<!-- Create/Edit Policy Form -->
		<div class="leave-manager-panel" style="margin-bottom: 30px;">
			<h2><?php echo $edit_policy ? 'Edit Leave Policy' : 'Create New Leave Policy'; ?></h2>
			<form method="post" class="leave-manager-form">
				<?php wp_nonce_field( $edit_policy ? 'update_policy_nonce' : 'create_policy_nonce' ); ?>

				<?php if ( $edit_policy ) : ?>
					<input type="hidden" name="policy_id" value="<?php echo esc_attr( $edit_policy->policy_id ); ?>">
				<?php endif; ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="policy_name">Policy Name *</label>
						</th>
						<td>
							<input type="text" id="policy_name" name="policy_name" 
								   value="<?php echo $edit_policy ? esc_attr( $edit_policy->policy_name ) : ''; ?>" 
								   required class="regular-text">
							<p class="description">e.g., Default, Executive, Part-time, Contractor</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="description">Description</label>
						</th>
						<td>
							<textarea id="description" name="description" rows="3" class="large-text"><?php echo $edit_policy ? esc_textarea( $edit_policy->description ) : ''; ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="leave_type">Leave Type</label>
						</th>
						<td>
							<select id="leave_type" name="leave_type" class="regular-text">
								<option value="annual" <?php selected( $edit_policy ? $edit_policy->leave_type : '', 'annual' ); ?>>Annual Leave</option>
								<option value="sick" <?php selected( $edit_policy ? $edit_policy->leave_type : '', 'sick' ); ?>>Sick Leave</option>
								<option value="other" <?php selected( $edit_policy ? $edit_policy->leave_type : '', 'other' ); ?>>Other Leave</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="annual_days">Annual Leave Days</label>
						</th>
						<td>
							<input type="number" id="annual_days" name="annual_days" step="0.5"
								   value="<?php echo $edit_policy ? esc_attr( $edit_policy->annual_days ) : '20'; ?>" 
								   class="small-text">
							<p class="description">Number of leave days per year</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="carryover_days">Carryover Days</label>
						</th>
						<td>
							<input type="number" id="carryover_days" name="carryover_days" step="0.5"
								   value="<?php echo $edit_policy ? esc_attr( $edit_policy->carryover_days ) : '5'; ?>" 
								   class="small-text">
							<p class="description">Maximum days that can be carried over to next year</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="expiry_days">Expiry Days</label>
						</th>
						<td>
							<input type="number" id="expiry_days" name="expiry_days"
								   value="<?php echo $edit_policy ? esc_attr( $edit_policy->expiry_days ) : '365'; ?>" 
								   class="small-text">
							<p class="description">Days before leave expires (0 = no expiry)</p>
						</td>
					</tr>
					<?php if ( $edit_policy ) : ?>
						<tr>
							<th scope="row">
								<label for="status">Status</label>
							</th>
							<td>
								<select id="status" name="status" class="regular-text">
									<option value="active" <?php selected( $edit_policy->status, 'active' ); ?>>Active</option>
									<option value="inactive" <?php selected( $edit_policy->status, 'inactive' ); ?>>Inactive</option>
								</select>
							</td>
						</tr>
					<?php endif; ?>
				</table>

				<p class="submit">
					<button type="submit" name="<?php echo $edit_policy ? 'update_policy' : 'create_policy'; ?>" 
							class="button button-primary">
						<?php echo $edit_policy ? 'Update Policy' : 'Create Policy'; ?>
					</button>
					<?php if ( $edit_policy ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-policies' ) ); ?>" class="button">Cancel</a>
					<?php endif; ?>
				</p>
			</form>
		</div>

		<!-- Assign Policy to Staff -->
		<div class="leave-manager-panel" style="margin-bottom: 30px;">
			<h2>Assign Policy to Staff Member</h2>
			<form method="post" class="leave-manager-form">
				<?php wp_nonce_field( 'assign_policy_nonce' ); ?>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="user_id">Staff Member *</label>
						</th>
						<td>
							<select id="user_id" name="user_id" required class="regular-text">
								<option value="">-- Select Staff Member --</option>
								<?php foreach ( $staff_members as $staff ) : ?>
									<option value="<?php echo esc_attr( $staff->user_id ); ?>">
										<?php echo esc_html( $staff->first_name . ' ' . $staff->last_name . ' (' . $staff->email . ')' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="policy_id">Leave Policy *</label>
						</th>
						<td>
							<select id="policy_id" name="policy_id" required class="regular-text">
								<option value="">-- Select Policy --</option>
								<?php foreach ( $all_policies as $policy ) : ?>
									<option value="<?php echo esc_attr( $policy->policy_id ); ?>">
										<?php echo esc_html( $policy->policy_name . ' (' . $policy->annual_days . ' days)' ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</table>

				<p class="submit">
					<button type="submit" name="assign_policy" class="button button-primary">Assign Policy</button>
				</p>
			</form>
		</div>

		<!-- Policies List -->
		<div class="leave-manager-panel">
			<h2>Leave Policies</h2>
			<?php if ( ! empty( $all_policies ) ) : ?>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th>Policy Name</th>
							<th>Leave Type</th>
							<th>Annual Days</th>
							<th>Carryover</th>
							<th>Expiry</th>
							<th>Status</th>
							<th>Created</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $all_policies as $policy ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $policy->policy_name ); ?></strong></td>
								<td><?php echo esc_html( ucfirst( $policy->leave_type ) ); ?></td>
								<td><?php echo esc_html( $policy->annual_days ); ?></td>
								<td><?php echo esc_html( $policy->carryover_days ); ?></td>
								<td><?php echo esc_html( $policy->expiry_days ); ?> days</td>
								<td>
									<span class="badge <?php echo $policy->status === 'active' ? 'badge-success' : 'badge-danger'; ?>">
										<?php echo esc_html( ucfirst( $policy->status ) ); ?>
									</span>
								</td>
								<td><?php echo esc_html( date_i18n( 'Y-m-d', strtotime( $policy->created_at ) ) ); ?></td>
								<td>
									<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-policies&edit_policy=' . $policy->policy_id ) ); ?>" 
									   class="button button-small">Edit</a>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=leave-manager-policies&delete_policy=' . $policy->policy_id ), 'delete_policy_nonce' ) ); ?>" 
									   class="button button-small button-link-delete" 
									   onclick="return confirm('Are you sure you want to delete this policy?');">Delete</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>No leave policies found. <a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-policies' ) ); ?>">Create one now</a>.</p>
			<?php endif; ?>
		</div>

		<!-- Staff Policy Assignments -->
		<div class="leave-manager-panel">
			<h2>Staff Policy Assignments</h2>
			<?php if ( ! empty( $staff_members ) ) : ?>
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th>Staff Member</th>
							<th>Email</th>
							<th>Department</th>
							<th>Assigned Policy</th>
							<th>Annual Days</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $staff_members as $staff ) : ?>
							<?php $policy = $leave_policies->get_user_policy( $staff->user_id ); ?>
							<tr>
								<td><strong><?php echo esc_html( $staff->first_name . ' ' . $staff->last_name ); ?></strong></td>
								<td><?php echo esc_html( $staff->email ); ?></td>
								<td><?php echo esc_html( $staff->department ?? 'N/A' ); ?></td>
								<td>
									<?php if ( $policy ) : ?>
										<span class="badge badge-info"><?php echo esc_html( $policy->policy_name ); ?></span>
									<?php else : ?>
										<span class="badge badge-warning">Not Assigned</span>
									<?php endif; ?>
								</td>
								<td><?php echo $policy ? esc_html( $policy->annual_days ) : 'N/A'; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p>No staff members found.</p>
			<?php endif; ?>
		</div>
	</div>
</div>

<style>
	.leave-manager-leave-container {
		max-width: 1200px;
	}

	.leave-manager-leave-panel {
		background: #fff;
		border: 1px solid #ccc;
		border-radius: 4px;
		padding: 20px;
		box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
	}

	.leave-manager-leave-panel h2 {
		margin-top: 0;
		margin-bottom: 20px;
		font-size: 1.3em;
	}

	.leave-manager-leave-form {
		max-width: 600px;
	}

	.badge {
		display: inline-block;
		padding: 4px 8px;
		border-radius: 3px;
		font-size: 12px;
		font-weight: 600;
		color: #fff;
	}

	.badge-success {
		background-color: #28a745;
	}

	.badge-danger {
		background-color: #dc3545;
	}

	.badge-info {
		background-color: #17a2b8;
	}

	.badge-warning {
		background-color: #4A5FFF;
		color: #333;
	}

	.wp-list-table th {
		background-color: #f5f5f5;
		font-weight: 600;
	}

	.wp-list-table td {
		padding: 10px;
	}
</style>
