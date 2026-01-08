<?php
/**
 * Employee Signup Page - ChatPanel Leave Manager
 * Professional branded signup form for new employees
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check if user is already logged in
if (is_user_logged_in()) {
    echo '<div style="text-align: center; padding: 40px;"><p>You are already logged in. <a href="' . esc_url(home_url('/leave-manager')) . '">Go to dashboard</a></p></div>';
    return;
}

// Get plugin directory for assets
$plugin_dir = plugin_dir_url(dirname(dirname(__FILE__)));

// Handle form submission
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup_nonce'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['signup_nonce'], 'leave_manager_signup')) {
        $error = 'Security verification failed. Please try again.';
    } else {
        // Get form data
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $department = sanitize_text_field($_POST['department'] ?? '');

        // Validate required fields
        if (empty($first_name)) {
            $error = 'First name is required.';
        } elseif (empty($last_name)) {
            $error = 'Last name is required.';
        } elseif (empty($email) || !is_email($email)) {
            $error = 'Valid email address is required.';
        } elseif (empty($password)) {
            $error = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($password !== $password_confirm) {
            $error = 'Passwords do not match.';
        } elseif (email_exists($email)) {
            $error = 'This email address is already registered.';
        } else {
            // Validate password strength
            if (!preg_match('/[A-Z]/', $password)) {
                $error = 'Password must contain at least one uppercase letter.';
            } elseif (!preg_match('/[0-9]/', $password)) {
                $error = 'Password must contain at least one number.';
            } elseif (!preg_match('/[!@#$%^&*]/', $password)) {
                $error = 'Password must contain at least one special character (!@#$%^&*).';
            } else {
                // Create WordPress user
                $user_data = array(
                    'user_login' => $email,
                    'user_email' => $email,
                    'user_pass' => $password,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'role' => 'subscriber',
                );

                $user_id = wp_insert_user($user_data);

                if (is_wp_error($user_id)) {
                    $error = 'Error creating account: ' . $user_id->get_error_message();
                } else {
                    // Add user to Leave Manager database
                    global $wpdb;
                    $users_table = $wpdb->prefix . 'leave_manager_users';

                    $wpdb->insert(
                        $users_table,
                        array(
                            'user_id' => $user_id,
                            'first_name' => $first_name,
                            'last_name' => $last_name,
                            'email' => $email,
                            'department' => $department,
                            'status' => 'pending',
                            'created_at' => current_time('mysql'),
                        ),
                        array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
                    );

                    // Send verification email
                    $verification_token = wp_generate_password(32, false);
                    update_user_meta($user_id, 'email_verification_token', $verification_token);
                    update_user_meta($user_id, 'email_verified', false);

                    $verification_link = add_query_arg(
                        array(
                            'action' => 'verify_email',
                            'token' => $verification_token,
                            'user_id' => $user_id,
                        ),
                        home_url()
                    );

                    $subject = 'Verify Your ChatPanel Leave Manager Account';
                    $message = sprintf(
                        '<html><body style="font-family: Arial, sans-serif; background-color: #f9fafb;">
                        <div style="max-width: 600px; margin: 0 auto; background-color: white; padding: 40px; border-radius: 8px;">
                            <div style="text-align: center; margin-bottom: 30px;">
                                <h1 style="color: #4A5FFF; margin: 0;">ChatPanel</h1>
                                <p style="color: #6B7280; margin: 5px 0 0 0;">Leave Manager</p>
                            </div>
                            <h2 style="color: #111827; margin-bottom: 20px;">Welcome, %s!</h2>
                            <p style="color: #374151; line-height: 1.6; margin-bottom: 20px;">
                                Thank you for signing up for ChatPanel Leave Manager. Please verify your email address to activate your account.
                            </p>
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="%s" style="background-color: #4A5FFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block;">
                                    Verify Email Address
                                </a>
                            </div>
                            <p style="color: #6B7280; font-size: 14px; margin-bottom: 20px;">
                                Or copy this link: <br><a href="%s" style="color: #4A5FFF;">%s</a>
                            </p>
                            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
                            <p style="color: #6B7280; font-size: 12px; margin: 0;">
                                This link will expire in 24 hours. If you did not create this account, please ignore this email.
                            </p>
                        </div>
                        </body></html>',
                        esc_html($first_name),
                        esc_url($verification_link),
                        esc_url($verification_link),
                        esc_url($verification_link)
                    );

                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    wp_mail($email, $subject, $message, $headers);

                    // Send admin notification
                    $admin_email = get_option('admin_email');
                    $admin_subject = 'New Employee Signup - Approval Required';
                    $admin_message = sprintf(
                        '<html><body style="font-family: Arial, sans-serif; background-color: #f9fafb;">
                        <div style="max-width: 600px; margin: 0 auto; background-color: white; padding: 40px; border-radius: 8px;">
                            <h2 style="color: #111827;">New Employee Signup</h2>
                            <p style="color: #374151; line-height: 1.6;">
                                A new employee has signed up and is awaiting approval:
                            </p>
                            <div style="background-color: #f3f4f6; padding: 20px; border-radius: 6px; margin: 20px 0;">
                                <p style="margin: 5px 0;"><strong>Name:</strong> %s %s</p>
                                <p style="margin: 5px 0;"><strong>Email:</strong> %s</p>
                                <p style="margin: 5px 0;"><strong>Department:</strong> %s</p>
                            </div>
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="%s" style="background-color: #4A5FFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block;">
                                    Review in Admin Panel
                                </a>
                            </div>
                        </div>
                        </body></html>',
                        esc_html($first_name),
                        esc_html($last_name),
                        esc_html($email),
                        esc_html($department),
                        esc_url(admin_url('admin.php?page=leave-manager-staff'))
                    );

                    wp_mail($admin_email, $admin_subject, $admin_message, $headers);

                    $success = true;
                }
            }
        }
    }
}

// Get departments for dropdown
$departments = array(
    'Engineering' => 'Engineering',
    'Sales' => 'Sales',
    'Marketing' => 'Marketing',
    'HR' => 'HR',
    'Finance' => 'Finance',
    'Operations' => 'Operations',
    'Other' => 'Other',
);

// Load CSS and JS
wp_enqueue_style('leave-manager-signup', plugin_dir_url(dirname(__FILE__)) . 'assets/css/signup.css');
wp_enqueue_script('leave-manager-signup', plugin_dir_url(dirname(__FILE__)) . 'assets/js/signup.js', array('jquery'), '1.0', true);
?>

<style>
.lm-signup-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background-color: #f9fafb;
}

.lm-signup-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 60px;
  max-width: 1200px;
  width: 100%;
  align-items: center;
}

.lm-signup-branding {
  padding: 40px;
}

.lm-signup-logo h1 {
  font-size: 48px;
  font-weight: 700;
  color: #4A5FFF;
  margin: 0;
  letter-spacing: -1px;
}

.lm-signup-form-container {
  background: white;
  border-radius: 12px;
  padding: 40px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.form-group {
  margin-bottom: 20px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
}

.form-input,
.form-select {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 16px;
  color: #374151;
  transition: all 0.2s ease;
  font-family: inherit;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #4A5FFF;
  box-shadow: 0 0 0 3px rgba(74, 95, 255, 0.1);
}

.form-hint {
  font-size: 13px;
  color: #6B7280;
  margin-top: 6px;
  line-height: 1.4;
}

.lm-signup-error {
  background-color: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 12px 16px;
  margin-bottom: 20px;
  color: #991b1b;
}

.lm-btn-primary {
  background-color: #4A5FFF;
  color: white;
  padding: 14px 20px;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  text-decoration: none;
  display: inline-block;
  width: 100%;
  text-align: center;
}

.lm-btn-primary:hover {
  background-color: #3a4fe8;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(74, 95, 255, 0.3);
}

input[type="checkbox"] {
  appearance: none;
  -webkit-appearance: none;
  width: 18px;
  height: 18px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
  background-color: white;
  margin-right: 10px;
}

input[type="checkbox"]:checked {
  background-color: #4A5FFF;
  border-color: #4A5FFF;
}

@media (max-width: 1024px) {
  .lm-signup-container {
    grid-template-columns: 1fr;
    gap: 40px;
  }
}

@media (max-width: 768px) {
  .lm-signup-container {
    gap: 30px;
  }
  
  .lm-signup-form-container {
    padding: 25px;
  }
  
  .form-input,
  .form-select {
    padding: 12px 14px;
    font-size: 16px;
  }
}
</style>

<div class="lm-signup-wrapper">
    <div class="lm-signup-container">
        <!-- Left Side - Branding -->
        <div class="lm-signup-branding">
            <div class="lm-signup-logo">
                <h1 style="color: #4A5FFF; font-size: 48px; margin: 0; font-weight: 700;">ChatPanel</h1>
            </div>
            <h2 style="color: #111827; font-size: 28px; margin: 30px 0 15px 0;">Leave Manager</h2>
            <p style="color: #6B7280; font-size: 16px; line-height: 1.6; margin: 0;">
                Manage your leave requests, check your balance, and stay organized with ChatPanel's professional leave management system.
            </p>
            <div style="margin-top: 40px;">
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background-color: #E8ECFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                        <span style="color: #4A5FFF; font-size: 20px;">✓</span>
                    </div>
                    <p style="color: #374151; margin: 0;">Easy leave request submission</p>
                </div>
                <div style="display: flex; align-items: center; margin-bottom: 20px;">
                    <div style="width: 40px; height: 40px; background-color: #E8ECFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                        <span style="color: #4A5FFF; font-size: 20px;">✓</span>
                    </div>
                    <p style="color: #374151; margin: 0;">Real-time approval tracking</p>
                </div>
                <div style="display: flex; align-items: center;">
                    <div style="width: 40px; height: 40px; background-color: #E8ECFF; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                        <span style="color: #4A5FFF; font-size: 20px;">✓</span>
                    </div>
                    <p style="color: #374151; margin: 0;">Team calendar and scheduling</p>
                </div>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="lm-signup-form-container">
            <?php if ($success): ?>
                <div class="lm-signup-success">
                    <div style="text-align: center; padding: 40px 20px;">
                        <div style="font-size: 48px; margin-bottom: 20px;">✓</div>
                        <h2 style="color: #111827; margin-bottom: 15px;">Account Created Successfully!</h2>
                        <p style="color: #6B7280; margin-bottom: 20px;">
                            A verification email has been sent to <strong><?php echo esc_html($email); ?></strong>
                        </p>
                        <p style="color: #6B7280; margin-bottom: 30px;">
                            Please check your email and click the verification link to activate your account. Your account will then be reviewed by an administrator.
                        </p>
                        <a href="<?php echo esc_url(wp_login_url()); ?>" class="lm-btn-primary" style="display: inline-block; padding: 12px 30px; background-color: #4A5FFF; color: white; text-decoration: none; border-radius: 6px;">
                            Back to Login
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <h2 style="color: #111827; font-size: 24px; margin-bottom: 10px;">Create Your Account</h2>
                <p style="color: #6B7280; margin-bottom: 30px;">Join ChatPanel Leave Manager and start managing your leave requests today.</p>

                <?php if (!empty($error)): ?>
                    <div class="lm-signup-error">
                        <p style="color: #991B1B; margin: 0;"><?php echo esc_html($error); ?></p>
                    </div>
                <?php endif; ?>

                <form method="POST" class="lm-signup-form" id="signup-form">
                    <?php wp_nonce_field('leave_manager_signup', 'signup_nonce'); ?>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                        <div class="form-group">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" id="first_name" name="first_name" class="form-input" required placeholder="John" value="<?php echo isset($_POST['first_name']) ? esc_attr($_POST['first_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" class="form-input" required placeholder="Doe" value="<?php echo isset($_POST['last_name']) ? esc_attr($_POST['last_name']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-input" required placeholder="john@example.com" value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>">
                        <p class="form-hint">We'll use this to send you verification and account updates.</p>
                    </div>

                    <div class="form-group">
                        <label for="department" class="form-label">Department</label>
                        <select id="department" name="department" class="form-input">
                            <option value="">Select a department...</option>
                            <?php foreach ($departments as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($_POST['department'] ?? '', $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" id="password" name="password" class="form-input" required placeholder="••••••••">
                        <div id="password-strength" style="margin-top: 8px;">
                            <div style="height: 4px; background-color: #e5e7eb; border-radius: 2px; overflow: hidden;">
                                <div id="password-strength-bar" style="height: 100%; width: 0%; background-color: #ef4444; transition: all 0.3s ease;"></div>
                            </div>
                            <p id="password-strength-text" style="font-size: 12px; color: #6B7280; margin: 5px 0 0 0;">Password strength: Weak</p>
                        </div>
                        <p class="form-hint">
                            Minimum 8 characters, 1 uppercase letter, 1 number, 1 special character (!@#$%^&*)
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm" class="form-label">Confirm Password *</label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-input" required placeholder="••••••••">
                    </div>

                    <div class="form-group" style="margin-bottom: 30px;">
                        <label style="display: flex; align-items: center; font-weight: normal; cursor: pointer;">
                            <input type="checkbox" name="terms" required style="margin-right: 10px; width: 18px; height: 18px; cursor: pointer;">
                            <span style="color: #374151;">I agree to the <a href="#" style="color: #4A5FFF; text-decoration: none;">Terms of Service</a> and <a href="#" style="color: #4A5FFF; text-decoration: none;">Privacy Policy</a></span>
                        </label>
                    </div>

                    <button type="submit" class="lm-btn-primary" style="width: 100%; padding: 14px; font-size: 16px; margin-bottom: 15px;">
                        Create Account
                    </button>

                    <p style="text-align: center; color: #6B7280;">
                        Already have an account? <a href="<?php echo esc_url(wp_login_url()); ?>" style="color: #4A5FFF; text-decoration: none;">Sign in here</a>
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
