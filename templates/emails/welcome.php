<?php
/**
 * Welcome Email Template - ChatPanel Leave Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_name = isset($user_name) ? $user_name : 'User';
$login_url = isset($login_url) ? $login_url : wp_login_url();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: Arial, sans-serif; background-color: #f9fafb; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #4A5FFF; padding-bottom: 20px;">
            <h1 style="color: #4A5FFF; margin: 0; font-size: 32px; font-weight: 700;">ChatPanel</h1>
            <p style="color: #6B7280; margin: 5px 0 0 0; font-size: 14px;">Leave Manager</p>
        </div>

        <!-- Main Content -->
        <h2 style="color: #111827; font-size: 24px; margin-bottom: 20px;">Welcome to ChatPanel Leave Manager, <?php echo esc_html($user_name); ?>!</h2>

        <p style="color: #374151; line-height: 1.6; margin-bottom: 20px; font-size: 14px;">
            Your account has been approved and is now active. You can now log in and start managing your leave requests with ChatPanel's professional leave management system.
        </p>

        <!-- CTA Button -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?php echo esc_url($login_url); ?>" style="background-color: #4A5FFF; color: white; padding: 14px 30px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;">
                Log In to Your Account
            </a>
        </div>

        <!-- Features -->
        <h3 style="color: #111827; font-size: 16px; margin-bottom: 15px; margin-top: 30px;">What You Can Do:</h3>
        <ul style="color: #374151; line-height: 1.8; padding-left: 20px; font-size: 14px;">
            <li>Submit leave requests and track their status</li>
            <li>View your leave balance and available days</li>
            <li>Check the team calendar to see colleagues' schedules</li>
            <li>Receive notifications about approvals and updates</li>
            <li>Download leave reports and certificates</li>
        </ul>

        <!-- Footer -->
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <div style="text-align: center; color: #6B7280; font-size: 12px;">
            <p style="margin: 0 0 10px 0;">
                If you have any questions, please contact your administrator or visit our <a href="#" style="color: #4A5FFF; text-decoration: none;">Help Center</a>.
            </p>
            <p style="margin: 0;">
                © <?php echo date('Y'); ?> ChatPanel. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
