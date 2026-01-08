<?php
/**
 * Admin Notification Email Template - ChatPanel Leave Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

$employee_name = isset($employee_name) ? $employee_name : 'New Employee';
$employee_email = isset($employee_email) ? $employee_email : '';
$department = isset($department) ? $department : 'Not specified';
$action_link = isset($action_link) ? $action_link : admin_url('admin.php?page=leave-manager-pending');
$action_text = isset($action_text) ? $action_text : 'Review Pending Approvals';
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
        <h2 style="color: #111827; font-size: 24px; margin-bottom: 20px;">New Employee Signup - Action Required</h2>

        <p style="color: #374151; line-height: 1.6; margin-bottom: 20px; font-size: 14px;">
            A new employee has signed up and is awaiting your approval to access the ChatPanel Leave Manager system.
        </p>

        <!-- Employee Details -->
        <div style="background-color: #f3f4f6; border-left: 4px solid #4A5FFF; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="color: #374151; margin: 5px 0; font-size: 14px;">
                <strong>Name:</strong> <?php echo esc_html($employee_name); ?>
            </p>
            <p style="color: #374151; margin: 5px 0; font-size: 14px;">
                <strong>Email:</strong> <a href="mailto:<?php echo esc_attr($employee_email); ?>" style="color: #4A5FFF;"><?php echo esc_html($employee_email); ?></a>
            </p>
            <p style="color: #374151; margin: 5px 0; font-size: 14px;">
                <strong>Department:</strong> <?php echo esc_html($department); ?>
            </p>
        </div>

        <!-- CTA Button -->
        <div style="text-align: center; margin: 30px 0;">
            <a href="<?php echo esc_url($action_link); ?>" style="background-color: #4A5FFF; color: white; padding: 14px 30px; text-decoration: none; border-radius: 6px; display: inline-block; font-weight: 600;">
                <?php echo esc_html($action_text); ?>
            </a>
        </div>

        <!-- Next Steps -->
        <h3 style="color: #111827; font-size: 16px; margin-bottom: 15px; margin-top: 30px;">Next Steps:</h3>
        <ol style="color: #374151; line-height: 1.8; padding-left: 20px; font-size: 14px;">
            <li>Review the employee's information</li>
            <li>Verify their department and role</li>
            <li>Approve or reject their account</li>
            <li>They will be notified of your decision</li>
        </ol>

        <!-- Footer -->
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        <div style="text-align: center; color: #6B7280; font-size: 12px;">
            <p style="margin: 0 0 10px 0;">
                This is an automated notification from ChatPanel Leave Manager.
            </p>
            <p style="margin: 0;">
                © <?php echo date('Y'); ?> ChatPanel. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>
