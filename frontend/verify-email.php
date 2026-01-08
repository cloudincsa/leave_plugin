<?php
/**
 * Email Verification Handler - ChatPanel Leave Manager
 * Verifies user email addresses after signup
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get verification parameters
$action = sanitize_text_field($_GET['action'] ?? '');
$token = sanitize_text_field($_GET['token'] ?? '');
$user_id = intval($_GET['user_id'] ?? 0);

$verified = false;
$error = '';

if ($action === 'verify_email' && !empty($token) && $user_id > 0) {
    // Get user
    $user = get_user_by('ID', $user_id);

    if (!$user) {
        $error = 'User not found.';
    } else {
        // Get stored token
        $stored_token = get_user_meta($user_id, 'email_verification_token', true);

        if ($stored_token !== $token) {
            $error = 'Invalid verification token. Please try signing up again.';
        } else {
            // Mark email as verified
            update_user_meta($user_id, 'email_verified', true);
            delete_user_meta($user_id, 'email_verification_token');

            // Update user status in Leave Manager database
            global $wpdb;
            $users_table = $wpdb->prefix . 'leave_manager_users';

            $wpdb->update(
                $users_table,
                array('status' => 'pending_approval'),
                array('user_id' => $user_id),
                array('%s'),
                array('%d')
            );

            // Send admin notification
            $admin_email = get_option('admin_email');
            $user_email = $user->user_email;
            $user_name = $user->first_name . ' ' . $user->last_name;

            $subject = 'Email Verified - Admin Approval Needed';
            $message = sprintf(
                '<html><body style="font-family: Arial, sans-serif; background-color: #f9fafb;">
                <div style="max-width: 600px; margin: 0 auto; background-color: white; padding: 40px; border-radius: 8px;">
                    <h2 style="color: #111827;">Email Verified</h2>
                    <p style="color: #374151; line-height: 1.6;">
                        %s has verified their email address and is ready for admin approval.
                    </p>
                    <div style="background-color: #f3f4f6; padding: 20px; border-radius: 6px; margin: 20px 0;">
                        <p style="margin: 5px 0;"><strong>User:</strong> %s</p>
                        <p style="margin: 5px 0;"><strong>Email:</strong> %s</p>
                    </div>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="%s" style="background-color: #4A5FFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block;">
                            Review in Admin Panel
                        </a>
                    </div>
                </div>
                </body></html>',
                esc_html($user_name),
                esc_html($user_name),
                esc_html($user_email),
                esc_url(admin_url('admin.php?page=leave-manager-staff'))
            );

            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($admin_email, $subject, $message, $headers);

            $verified = true;
        }
    }
}

// Load CSS
wp_enqueue_style('leave-manager-professional', plugin_dir_url(dirname(__FILE__)) . 'assets/css/professional.css');
wp_enqueue_style('leave-manager-signup', plugin_dir_url(dirname(__FILE__)) . 'assets/css/signup.css');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChatPanel Leave Manager - Email Verification</title>
    <?php wp_head(); ?>
</head>
<body style="background-color: #f9fafb;">
    <div class="lm-signup-wrapper">
        <div style="max-width: 500px; width: 100%; background: white; border-radius: 12px; padding: 40px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); text-align: center;">
            <?php if ($verified): ?>
                <div style="font-size: 48px; margin-bottom: 20px; color: #10b981;">✓</div>
                <h1 style="color: #111827; font-size: 28px; margin-bottom: 15px;">Email Verified!</h1>
                <p style="color: #6B7280; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
                    Your email has been successfully verified. Your account is now pending administrator approval. You will receive an email once your account has been approved.
                </p>
                <a href="<?php echo esc_url(wp_login_url()); ?>" style="background-color: #4A5FFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block;">
                    Back to Login
                </a>
            <?php else: ?>
                <div style="font-size: 48px; margin-bottom: 20px; color: #ef4444;">✕</div>
                <h1 style="color: #111827; font-size: 28px; margin-bottom: 15px;">Verification Failed</h1>
                <p style="color: #6B7280; font-size: 16px; line-height: 1.6; margin-bottom: 30px;">
                    <?php echo esc_html($error ?: 'An error occurred during email verification. Please try signing up again.'); ?>
                </p>
                <a href="<?php echo esc_url(home_url('/signup')); ?>" style="background-color: #4A5FFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block;">
                    Sign Up Again
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
