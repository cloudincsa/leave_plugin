<?php
/**
 * Employee Self-Service Signup Page
 *
 * Displays signup form for new employees to register.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Redirect if user is already logged in
if ( is_user_logged_in() ) {
	wp_redirect( home_url( '/dashboard/' ) );
	exit;
}

// Initialize classes
$db                = new Leave_Manager_Database();
$logger            = new Leave_Manager_Logger( $db );
$employee_signup   = new Leave_Manager_Employee_Signup( $db, $logger );
$leave_policies    = new Leave_Manager_Leave_Policies( $db, $logger );

// Get all active policies
$policies = $leave_policies->get_policies( array( 'status' => 'active', 'limit' => -1 ) );

// Handle email verification
if ( isset( $_GET['action'] ) && $_GET['action'] === 'verify_email' ) {
	$signup_id = intval( $_GET['signup'] ?? 0 );
	$code      = sanitize_text_field( $_GET['code'] ?? '' );

	if ( $employee_signup->verify_email( $signup_id, $code ) ) {
		$signup = $employee_signup->get_signup( $signup_id );
		$verification_success = true;
	} else {
		$verification_error = 'Invalid or expired verification code.';
	}
}

?>

<div class="leave-manager-signup-container">
	<div class="leave-manager-signup-wrapper">
		<div class="leave-manager-signup-header">
			<h1>Employee Self-Service Registration</h1>
			<p>Create your account to access the Leave Manager System</p>
		</div>

		<?php if ( isset( $verification_success ) && $verification_success ) : ?>
			<!-- Email Verified - Account Setup Form -->
			<div class="leave-manager-signup-form-section">
				<h2>Complete Your Account Setup</h2>
				<p>Your email has been verified. Now create your login credentials.</p>

				<form id="leave-manager-complete-signup-form" class="leave-manager-signup-form">
					<input type="hidden" name="signup_id" value="<?php echo esc_attr( $signup_id ); ?>">

					<div class="form-group">
						<label for="username">Username *</label>
						<input type="text" id="username" name="username" required minlength="3" 
							   placeholder="Choose a username (3+ characters)" class="form-control">
						<small class="form-text">Letters, numbers, and underscores only</small>
					</div>

					<div class="form-group">
						<label for="password">Password *</label>
						<input type="password" id="password" name="password" required minlength="8" 
							   placeholder="Create a strong password (8+ characters)" class="form-control">
						<small class="form-text">Use a mix of uppercase, lowercase, numbers, and symbols</small>
					</div>

					<div class="form-group">
						<label for="password2">Confirm Password *</label>
						<input type="password" id="password2" name="password2" required minlength="8" 
							   placeholder="Confirm your password" class="form-control">
					</div>

					<div class="form-group">
						<label>
							<input type="checkbox" id="terms" name="terms" required>
							I agree to the terms and conditions
						</label>
					</div>

					<button type="submit" class="btn btn-primary btn-block">Create Account</button>
					<div id="leave-manager-complete-message" class="alert" style="display: none; margin-top: 15px;"></div>
				</form>
			</div>

		<?php elseif ( isset( $verification_error ) ) : ?>
			<!-- Verification Error -->
			<div class="alert alert-danger">
				<p><?php echo esc_html( $verification_error ); ?></p>
				<p><a href="<?php echo esc_url( home_url( '/employee-signup/' ) ); ?>" class="btn btn-primary">Back to Signup</a></p>
			</div>

		<?php else : ?>
			<!-- Initial Signup Form -->
			<div class="leave-manager-signup-form-section">
				<h2>Step 1: Create Your Profile</h2>
				<p>Please provide your information to get started.</p>

				<form id="leave-manager-signup-form" class="leave-manager-signup-form">
					<div class="form-row">
						<div class="form-group col-md-6">
							<label for="first_name">First Name *</label>
							<input type="text" id="first_name" name="first_name" required 
								   placeholder="Your first name" class="form-control">
						</div>
						<div class="form-group col-md-6">
							<label for="last_name">Last Name *</label>
							<input type="text" id="last_name" name="last_name" required 
								   placeholder="Your last name" class="form-control">
						</div>
					</div>

					<div class="form-group">
						<label for="email">Email Address *</label>
						<input type="email" id="email" name="email" required 
							   placeholder="your.email@littlefalls.co.za" class="form-control">
						<small class="form-text">We'll send a verification link to this email</small>
					</div>

					<div class="form-group">
						<label for="phone">Phone Number</label>
						<input type="tel" id="phone" name="phone" 
							   placeholder="+27 (0) 123 456 789" class="form-control">
					</div>

					<div class="form-row">
						<div class="form-group col-md-6">
							<label for="department">Department</label>
							<input type="text" id="department" name="department" 
								   placeholder="e.g., Operations, HR, Finance" class="form-control">
						</div>
						<div class="form-group col-md-6">
							<label for="position">Position</label>
							<input type="text" id="position" name="position" 
								   placeholder="e.g., Manager, Coordinator" class="form-control">
						</div>
					</div>

					<div class="form-group">
						<label for="policy_id">Leave Policy *</label>
						<select id="policy_id" name="policy_id" required class="form-control">
							<option value="">-- Select Your Leave Policy --</option>
							<?php foreach ( $policies as $policy ) : ?>
								<option value="<?php echo esc_attr( $policy->policy_id ); ?>">
									<?php echo esc_html( $policy->policy_name . ' (' . $policy->annual_days . ' days/year)' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<small class="form-text">Select the leave policy that applies to your position</small>
					</div>

					<button type="submit" class="btn btn-primary btn-block">Submit Registration</button>
					<div id="leave-manager-signup-message" class="alert" style="display: none; margin-top: 15px;"></div>
				</form>

				<div class="leave-manager-signup-help">
					<p>Already have an account? <a href="<?php echo esc_url( wp_login_url() ); ?>">Log in here</a></p>
				</div>
			</div>

		<?php endif; ?>
	</div>
</div>

<style>
	.leave-manager-signup-container {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		min-height: 100vh;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20px;
		font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
	}

	.leave-manager-signup-wrapper {
		background: white;
		border-radius: 10px;
		box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
		max-width: 500px;
		width: 100%;
		padding: 40px;
	}

	.leave-manager-signup-header {
		text-align: center;
		margin-bottom: 40px;
	}

	.leave-manager-signup-header h1 {
		margin: 0 0 10px 0;
		color: #333;
		font-size: 28px;
		font-weight: 600;
	}

	.leave-manager-signup-header p {
		margin: 0;
		color: #666;
		font-size: 14px;
	}

	.leave-manager-signup-form-section h2 {
		color: #333;
		font-size: 20px;
		margin-bottom: 10px;
		font-weight: 600;
	}

	.leave-manager-signup-form-section > p {
		color: #666;
		margin-bottom: 25px;
		font-size: 14px;
	}

	.leave-manager-signup-form {
		margin-bottom: 20px;
	}

	.form-group {
		margin-bottom: 20px;
	}

	.form-row {
		display: flex;
		gap: 15px;
		margin-bottom: 20px;
	}

	.form-row .form-group {
		flex: 1;
		margin-bottom: 0;
	}

	.form-group label {
		display: block;
		margin-bottom: 8px;
		color: #333;
		font-weight: 500;
		font-size: 14px;
	}

	.form-control {
		width: 100%;
		padding: 10px 12px;
		border: 1px solid #ddd;
		border-radius: 5px;
		font-size: 14px;
		font-family: inherit;
		transition: border-color 0.3s, box-shadow 0.3s;
		box-sizing: border-box;
	}

	.form-control:focus {
		outline: none;
		border-color: #667eea;
		box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
	}

	.form-text {
		display: block;
		margin-top: 5px;
		color: #999;
		font-size: 12px;
	}

	.btn {
		padding: 12px 20px;
		border: none;
		border-radius: 5px;
		font-size: 14px;
		font-weight: 600;
		cursor: pointer;
		transition: all 0.3s;
		text-decoration: none;
		display: inline-block;
	}

	.btn-primary {
		background-color: #667eea;
		color: white;
	}

	.btn-primary:hover {
		background-color: #5568d3;
		transform: translateY(-2px);
		box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
	}

	.btn-block {
		width: 100%;
		display: block;
	}

	.alert {
		padding: 12px 15px;
		border-radius: 5px;
		font-size: 14px;
		margin-bottom: 20px;
	}

	.alert-success {
		background-color: #d4edda;
		color: #155724;
		border: 1px solid #c3e6cb;
	}

	.alert-danger {
		background-color: #f8d7da;
		color: #721c24;
		border: 1px solid #f5c6cb;
	}

	.alert-info {
		background-color: #d1ecf1;
		color: #0c5460;
		border: 1px solid #bee5eb;
	}

	.leave-manager-signup-help {
		text-align: center;
		margin-top: 25px;
		padding-top: 20px;
		border-top: 1px solid #eee;
	}

	.leave-manager-signup-help p {
		margin: 0;
		color: #666;
		font-size: 14px;
	}

	.leave-manager-signup-help a {
		color: #667eea;
		text-decoration: none;
		font-weight: 600;
	}

	.leave-manager-signup-help a:hover {
		text-decoration: underline;
	}

	@media (max-width: 600px) {
		.leave-manager-signup-wrapper {
			padding: 25px;
		}

		.leave-manager-signup-header h1 {
			font-size: 24px;
		}

		.form-row {
			flex-direction: column;
			gap: 0;
		}
	}
</style>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		const signupForm = document.getElementById('leave_manager-signup-form');
		const completeForm = document.getElementById('leave_manager-complete-signup-form');

		if (signupForm) {
			signupForm.addEventListener('submit', function(e) {
				e.preventDefault();
				submitSignup();
			});
		}

		if (completeForm) {
			completeForm.addEventListener('submit', function(e) {
				e.preventDefault();
				completeSignup();
			});
		}

		function submitSignup() {
			const formData = new FormData(signupForm);
			formData.append('action', 'leave_manager_submit_signup');
			formData.append('nonce', '<?php echo wp_create_nonce( 'leave_manager_signup_nonce' ); ?>');

			const messageDiv = document.getElementById('leave_manager-signup-message');
			const submitBtn = signupForm.querySelector('button[type="submit"]');

			submitBtn.disabled = true;
			submitBtn.textContent = 'Submitting...';

			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					messageDiv.className = 'alert alert-success';
					messageDiv.innerHTML = '<p>' + data.data.message + '</p>';
					messageDiv.style.display = 'block';
					signupForm.style.display = 'none';
					
					// Show verification form after 2 seconds
					setTimeout(() => {
						location.reload();
					}, 2000);
				} else {
					messageDiv.className = 'alert alert-danger';
					messageDiv.innerHTML = '<p>' + data.data.message + '</p>';
					messageDiv.style.display = 'block';
					submitBtn.disabled = false;
					submitBtn.textContent = 'Submit Registration';
				}
			})
			.catch(error => {
				messageDiv.className = 'alert alert-danger';
				messageDiv.innerHTML = '<p>An error occurred. Please try again.</p>';
				messageDiv.style.display = 'block';
				submitBtn.disabled = false;
				submitBtn.textContent = 'Submit Registration';
			});
		}

		function completeSignup() {
			const formData = new FormData(completeForm);
			formData.append('action', 'leave_manager_complete_signup');
			formData.append('nonce', '<?php echo wp_create_nonce( 'leave_manager_signup_nonce' ); ?>');

			const messageDiv = document.getElementById('leave_manager-complete-message');
			const submitBtn = completeForm.querySelector('button[type="submit"]');

			submitBtn.disabled = true;
			submitBtn.textContent = 'Creating Account...';

			fetch('<?php echo admin_url( 'admin-ajax.php' ); ?>', {
				method: 'POST',
				body: formData
			})
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					messageDiv.className = 'alert alert-success';
					messageDiv.innerHTML = '<p>' + data.data.message + '</p>';
					messageDiv.style.display = 'block';
					completeForm.style.display = 'none';
					
					// Redirect to login after 2 seconds
					setTimeout(() => {
						window.location.href = data.data.login_url;
					}, 2000);
				} else {
					messageDiv.className = 'alert alert-danger';
					messageDiv.innerHTML = '<p>' + data.data.message + '</p>';
					messageDiv.style.display = 'block';
					submitBtn.disabled = false;
					submitBtn.textContent = 'Create Account';
				}
			})
			.catch(error => {
				messageDiv.className = 'alert alert-danger';
				messageDiv.innerHTML = '<p>An error occurred. Please try again.</p>';
				messageDiv.style.display = 'block';
				submitBtn.disabled = false;
				submitBtn.textContent = 'Create Account';
			});
		}
	});
</script>
