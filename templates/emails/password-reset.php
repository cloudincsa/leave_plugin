<?php
/**
 * Password Reset Email Template - ChatPanel Leave Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_name = isset($user_name) ? $user_name : 'User';
$reset_link = isset($reset_link) ? $reset_link : '#';
$expiry_time = isset($expiry_time) ? $expiry_time : '24 hours';
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
        <h2 style="color: #111827; font-size: 24px; margin-bottom: 20px;">Password Reset Request</h2>

        <p style="color: #374151; line-height: 1.6; margin-bottom: 20px; font-size: 14px;">
            Hi <?php echo esc_html($user_name); ?>,
        </p>

        <p style="color: #374151; line-height: 1.6; margin-bottom: 20px; font-size: 14px;">
            We received a request to reset your ChatPanel Leave Manager password. Click the button below to create a new password.
        </p>

        <!-- CTA Button -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?php echo esc_url($reset_link); ?>" style="background-color: #4A5FFF; color: white; padding: 14px 30px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;">
                Reset Password
            </a>
        </div>

        <!-- Link Copy -->
        <p style="color: #6B7280; font-size: 12px; text-align: center; margin-bottom: 20px;">
            Or copy this link: <br>
            <a href="<?php echo esc_url($reset_link); ?>" style="color: #4A5FFF; word-break: break-all;">
                <?php echo esc_url($reset_link); ?>
            </a>
        </p>

        <!-- Security Notice -->
        <div style="background-color: #FEF3C7; border: 1px solid #FCD34D; border-radius: 6px; padding: 15px; margin: 20px 0;">
            <p style="color: #92400E; margin: 0; font-size: 13px;">
                <strong>Security Notice:</strong> This link will expire in <?php echo esc_html($expiry_time); ?>. If you did not request a password reset, please ignore this email and your password will remain unchanged.
            </p>
        </div>

        <!-- Footer -->
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <div style="text-align: center; color: #6B7280; font-size: 12px;">
            <p style="margin: 0 0 10px 0;">
                For security reasons, never share this link with anyone else.
            </p>
            <p style="margin: 0;">
                © <?php echo date('Y'); ?> ChatPanel. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
