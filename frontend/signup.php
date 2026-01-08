<?php
/**
 * Employee Signup Page - LFCC Leave Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if user is already logged in
if ( is_user_logged_in() ) {
	wp_redirect( home_url( '/dashboard' ) );
	exit;
}

// Handle form submission
$signup_error = '';
$signup_success = false;

if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
	if ( ! isset( $_POST['leave_manager_signup_nonce'] ) || ! wp_verify_nonce( $_POST['leave_manager_signup_nonce'], 'leave_manager_signup' ) ) {
		$signup_error = 'Security verification failed. Please try again.';
	} else {
		$first_name = sanitize_text_field( $_POST['first_name'] ?? '' );
		$last_name = sanitize_text_field( $_POST['last_name'] ?? '' );
		$email = sanitize_email( $_POST['email'] ?? '' );
		$password = $_POST['password'] ?? '';
		$password_confirm = $_POST['password_confirm'] ?? '';
		$department = sanitize_text_field( $_POST['department'] ?? '' );

		if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) || empty( $password ) ) {
			$signup_error = 'All fields are required.';
		} elseif ( $password !== $password_confirm ) {
			$signup_error = 'Passwords do not match.';
		} elseif ( strlen( $password ) < 8 ) {
			$signup_error = 'Password must be at least 8 characters long.';
		} elseif ( email_exists( $email ) ) {
			$signup_error = 'An account with this email already exists.';
		} else {
			$user_id = wp_create_user( $email, $password, $email );

			if ( is_wp_error( $user_id ) ) {
				$signup_error = 'Error creating account: ' . $user_id->get_error_message();
			} else {
				update_user_meta( $user_id, 'first_name', $first_name );
				update_user_meta( $user_id, 'last_name', $last_name );
				update_user_meta( $user_id, 'department', $department );
				update_user_meta( $user_id, 'email_verified', 1 );

				$user = new WP_User( $user_id );
				$user->set_role( 'subscriber' );

				global $wpdb;
				$wpdb->insert(
					$wpdb->prefix . 'leave_manager_users',
					array(
						'user_id'   => $user_id,
						'first_name' => $first_name,
						'last_name' => $last_name,
						'email'     => $email,
						'department' => $department,
						'status'    => 'approved',
						'created_at' => current_time( 'mysql' ),
					),
					array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
				);

				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id );

				$signup_success = true;
			}
		}
	}
}

$logo_url = '/wp-content/uploads/2025/12/lfcc-logo-transparent.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Employee Signup - Leave Manager</title>
	<link rel="icon" href="/favicon.ico" type="image/x-icon">
	<style>
		* { margin: 0; padding: 0; box-sizing: border-box; }
		body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
		.signup-container { background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); overflow: hidden; max-width: 900px; width: 100%; display: grid; grid-template-columns: 1fr 1fr; }
		.signup-branding { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 60px 40px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; }
		.signup-branding img { max-width: 150px; margin-bottom: 30px; }
		.signup-branding h1 { font-size: 28px; margin-bottom: 15px; font-weight: 600; }
		.signup-branding p { font-size: 16px; opacity: 0.9; line-height: 1.6; }
		.signup-form { padding: 60px 40px; display: flex; flex-direction: column; justify-content: center; }
		.signup-form h2 { font-size: 24px; color: #1f2937; margin-bottom: 30px; font-weight: 600; }
		.form-group { margin-bottom: 20px; }
		.form-group label { display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 8px; }
		.form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; transition: border-color 0.2s; }
		.form-group input:focus, .form-group select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
		.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
		.error-message { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; border-left: 4px solid #dc2626; }
		.submit-btn { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; margin-top: 10px; width: 100%; }
		.submit-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
		.login-link { text-align: center; margin-top: 20px; font-size: 14px; color: #6b7280; }
		.login-link a { color: #667eea; text-decoration: none; font-weight: 600; }
		.success-page { text-align: center; padding: 60px 40px; }
		.success-page h2 { color: #22c55e; font-size: 28px; margin-bottom: 15px; }
		.success-page p { color: #6b7280; font-size: 16px; margin-bottom: 30px; }
		.success-page a { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; border-radius: 6px; text-decoration: none; font-weight: 600; }
		@media (max-width: 768px) { .signup-container { grid-template-columns: 1fr; } .signup-branding { padding: 40px 30px; } .signup-form { padding: 40px 30px; } .form-row { grid-template-columns: 1fr; } }
	</style>
</head>
<body>
	<div class="signup-container">
		<div class="signup-branding">
			<img src="<?php echo esc_url( $logo_url ); ?>" alt="LFCC Logo">
			<h1>Leave Manager</h1>
			<p>Manage your leave requests and track your time off with ease. Join your organization's leave management system today.</p>
		</div>

		<div class="signup-form">
			<?php if ( $signup_success ) : ?>
				<div class="success-page">
					<h2>✓ Account Created Successfully!</h2>
					<p>Your account has been created and you are now logged in. You can now access the Leave Manager dashboard.</p>
					<a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>">Go to Dashboard</a>
				</div>
			<?php else : ?>
				<h2>Create Your Account</h2>

				<?php if ( ! empty( $signup_error ) ) : ?>
					<div class="error-message"><?php echo esc_html( $signup_error ); ?></div>
				<?php endif; ?>

				<form method="POST">
					<?php wp_nonce_field( 'leave_manager_signup', 'leave_manager_signup_nonce' ); ?>

					<div class="form-row">
						<div class="form-group">
							<label for="first_name">First Name *</label>
							<input type="text" id="first_name" name="first_name" required>
						</div>
						<div class="form-group">
							<label for="last_name">Last Name *</label>
							<input type="text" id="last_name" name="last_name" required>
						</div>
					</div>

					<div class="form-group">
						<label for="email">Email Address *</label>
						<input type="email" id="email" name="email" required>
					</div>

					<div class="form-group">
						<label for="department">Department</label>
						<select id="department" name="department">
							<option value="">Select Department</option>
							<option value="Engineering">Engineering</option>
							<option value="Sales">Sales</option>
							<option value="Marketing">Marketing</option>
							<option value="HR">HR</option>
							<option value="Finance">Finance</option>
							<option value="Operations">Operations</option>
						</select>
					</div>

					<div class="form-group">
						<label for="password">Password *</label>
						<input type="password" id="password" name="password" required>
					</div>

					<div class="form-group">
						<label for="password_confirm">Confirm Password *</label>
						<input type="password" id="password_confirm" name="password_confirm" required>
					</div>

					<button type="submit" class="submit-btn">Create Account</button>

					<div class="login-link">
						Already have an account? <a href="<?php echo esc_url( wp_login_url() ); ?>">Log In</a>
					</div>
				</form>
			<?php endif; ?>
		</div>
	</div>
</body>
</html>
