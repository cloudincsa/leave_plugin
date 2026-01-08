<?php
/**
 * User Management Page
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
$logger = new Leave_Manager_Logger( $db );
$users_class = new Leave_Manager_Users( $db, $logger );
$permissions = new Leave_Manager_Permissions( $db, $logger );

// Handle form submissions
$message = '';
$error = '';

if ( isset( $_POST['action'] ) && 'add_user' === $_POST['action'] ) {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_add_user' ) ) {
		$error = 'Security check failed.';
	} else {
		$user_data = array(
			'first_name' => sanitize_text_field( $_POST['first_name'] ?? '' ),
			'last_name'  => sanitize_text_field( $_POST['last_name'] ?? '' ),
			'email'      => sanitize_email( $_POST['email'] ?? '' ),
			'phone'      => sanitize_text_field( $_POST['phone'] ?? '' ),
			'role'       => sanitize_text_field( $_POST['role'] ?? 'staff' ),
			'department' => sanitize_text_field( $_POST['department'] ?? '' ),
			'position'   => sanitize_text_field( $_POST['position'] ?? '' ),
		);

		if ( empty( $user_data['email'] ) ) {
			$error = 'Email is required.';
		} else {
			$result = $users_class->create_user( $user_data );
			if ( $result ) {
				$message = 'User created successfully.';
			} else {
				$error = 'Failed to create user.';
			}
		}
	}
}

// Get all users
$all_users = $users_class->get_users();
$roles = $permissions->get_roles();
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

	<div class="user-management-container">
		<h2>Add New User</h2>
		
		<form method="post" class="user-form">
			<?php wp_nonce_field( 'leave_manager_add_user', 'nonce' ); ?>
			<input type="hidden" name="action" value="add_user">

			<div class="form-row">
				<div class="form-group">
					<label for="first_name">First Name:</label>
					<input type="text" id="first_name" name="first_name" required>
				</div>
				<div class="form-group">
					<label for="last_name">Last Name:</label>
					<input type="text" id="last_name" name="last_name" required>
				</div>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label for="email">Email:</label>
					<input type="email" id="email" name="email" required>
				</div>
				<div class="form-group">
					<label for="phone">Phone:</label>
					<input type="tel" id="phone" name="phone">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label for="role">Role:</label>
					<select id="role" name="role" required>
						<?php foreach ( $roles as $role_key => $role_data ) : ?>
							<option value="<?php echo esc_attr( $role_key ); ?>">
								<?php echo esc_html( $role_data['label'] ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="form-group">
					<label for="department">Department:</label>
					<input type="text" id="department" name="department">
				</div>
			</div>

			<div class="form-row">
				<div class="form-group">
					<label for="position">Position:</label>
					<input type="text" id="position" name="position">
				</div>
			</div>

			<button type="submit" class="button button-primary">Add User</button>
		</form>

		<h2>Existing Users</h2>

		<?php if ( ! empty( $all_users ) ) : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th>Name</th>
						<th>Email</th>
						<th>Role</th>
						<th>Department</th>
						<th>Position</th>
						<th>Status</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $all_users as $user ) : ?>
						<tr>
							<td><?php echo esc_html( $user->first_name . ' ' . $user->last_name ); ?></td>
							<td><?php echo esc_html( $user->email ); ?></td>
							<td>
								<span class="role-badge role-<?php echo esc_attr( $user->role ); ?>">
									<?php echo esc_html( $permissions->get_role_label( $user->role ) ); ?>
								</span>
							</td>
							<td><?php echo esc_html( $user->department ?? '-' ); ?></td>
							<td><?php echo esc_html( $user->position ?? '-' ); ?></td>
							<td>
								<span class="status-badge status-<?php echo esc_attr( $user->status ); ?>">
									<?php echo esc_html( ucfirst( $user->status ) ); ?>
								</span>
							</td>
							<td>
								<a href="#" class="edit-user" data-user-id="<?php echo esc_attr( $user->user_id ); ?>">Edit</a> |
								<a href="#" class="delete-user" data-user-id="<?php echo esc_attr( $user->user_id ); ?>">Delete</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p>No users found.</p>
		<?php endif; ?>
	</div>
</div>

<style>
	.user-management-container {
		margin-top: 20px;
	}

	.user-form {
		background: white;
		border: 1px solid #ccc;
		border-radius: 5px;
		padding: 20px;
		margin-bottom: 30px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.form-row {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		gap: 20px;
		margin-bottom: 20px;
	}

	.form-group {
		display: flex;
		flex-direction: column;
	}

	.form-group label {
		font-weight: 600;
		margin-bottom: 5px;
		color: #333;
	}

	.form-group input,
	.form-group select {
		padding: 10px;
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

	.role-badge {
		display: inline-block;
		padding: 5px 10px;
		border-radius: 3px;
		font-size: 12px;
		font-weight: 600;
	}

	.role-badge.role-staff {
		background-color: #e7f3ff;
		color: #0073aa;
	}

	.role-badge.role-hr {
		background-color: #fff3cd;
		color: #856404;
	}

	.role-badge.role-admin {
		background-color: #f8d7da;
		color: #721c24;
	}

	.status-badge {
		display: inline-block;
		padding: 5px 10px;
		border-radius: 3px;
		font-size: 12px;
		font-weight: 600;
	}

	.status-badge.status-active {
		background-color: #d4edda;
		color: #155724;
	}

	.status-badge.status-inactive {
		background-color: #f8d7da;
		color: #721c24;
	}

	.widefat a {
		color: #0073aa;
		text-decoration: none;
	}

	.widefat a:hover {
		text-decoration: underline;
	}
</style>
