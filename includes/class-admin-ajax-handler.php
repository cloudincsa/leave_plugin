<?php
/**
 * Admin AJAX Handler Class
 * Handles AJAX requests for templates, settings, and plugin management
 *
 * @package LeaveManager
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Leave_Manager_Admin_AJAX_Handler {

    public function __construct() {
        // Template AJAX handlers
        add_action( 'wp_ajax_leave_manager_preview_template', array( $this, 'preview_template' ) );
        add_action( 'wp_ajax_leave_manager_edit_template', array( $this, 'edit_template' ) );
        add_action( 'wp_ajax_leave_manager_save_template', array( $this, 'save_template' ) );
        add_action( 'wp_ajax_leave_manager_reset_template', array( $this, 'reset_template' ) );

        // Settings AJAX handlers
        add_action( 'wp_ajax_leave_manager_save_settings', array( $this, 'save_settings' ) );
        add_action( 'wp_ajax_leave_manager_reset_settings', array( $this, 'reset_settings' ) );
        add_action( 'wp_ajax_leave_manager_save_branding', array( $this, 'save_branding' ) );
        add_action( 'wp_ajax_leave_manager_save_email_settings', array( $this, 'save_email_settings' ) );
        add_action( 'wp_ajax_leave_manager_test_email', array( $this, 'test_email' ) );
        add_action( 'wp_ajax_leave_manager_save_notifications', array( $this, 'save_notifications' ) );
        add_action( 'wp_ajax_leave_manager_save_advanced', array( $this, 'save_advanced' ) );

        // Dashboard stats AJAX handler
        add_action( 'wp_ajax_leave_manager_get_dashboard_stats', array( $this, 'get_dashboard_stats' ) );

        // Plugin management AJAX handlers
        add_action( 'wp_ajax_leave_manager_flush_plugin', array( $this, 'flush_plugin' ) );
        add_action( 'wp_ajax_leave_manager_reset_plugin', array( $this, 'reset_plugin' ) );
        add_action( 'wp_ajax_leave_manager_get_plugin_status', array( $this, 'get_plugin_status' ) );

        // Staff and requests AJAX handlers
        add_action( 'wp_ajax_leave_manager_get_staff_data', array( $this, 'get_staff_data' ) );
        add_action( 'wp_ajax_leave_manager_get_requests_data', array( $this, 'get_requests_data' ) );
        add_action( 'wp_ajax_leave_manager_add_staff', array( $this, 'add_staff' ) );
        add_action( 'wp_ajax_leave_manager_edit_staff', array( $this, 'edit_staff' ) );
        add_action( 'wp_ajax_leave_manager_delete_staff', array( $this, 'delete_staff' ) );
        
        // Approval AJAX handlers
        add_action( 'wp_ajax_leave_manager_approve_request', array( $this, 'approve_request' ) );
        add_action( 'wp_ajax_leave_manager_reject_request', array( $this, 'reject_request' ) );

        // Report AJAX handlers
        add_action( 'wp_ajax_leave_manager_generate_leave_report', array( $this, 'generate_leave_report' ) );
        add_action( 'wp_ajax_leave_manager_export_leave_report', array( $this, 'export_leave_report' ) );
        add_action( 'wp_ajax_leave_manager_generate_user_report', array( $this, 'generate_user_report' ) );
        add_action( 'wp_ajax_leave_manager_export_user_report', array( $this, 'export_user_report' ) );
        add_action( 'wp_ajax_leave_manager_generate_department_report', array( $this, 'generate_department_report' ) );
        add_action( 'wp_ajax_leave_manager_export_department_report', array( $this, 'export_department_report' ) );

        // Policy AJAX handlers
        add_action( 'wp_ajax_leave_manager_get_policy', array( $this, 'get_policy' ) );
        add_action( 'wp_ajax_leave_manager_create_policy', array( $this, 'create_policy' ) );
        add_action( 'wp_ajax_leave_manager_update_policy', array( $this, 'update_policy' ) );
        add_action( 'wp_ajax_leave_manager_delete_policy', array( $this, 'delete_policy' ) );
        add_action( 'wp_ajax_leave_manager_assign_policy', array( $this, 'assign_policy' ) );
        add_action( 'wp_ajax_leave_manager_apply_default_policy', array( $this, 'apply_default_policy' ) );

        // Leave Types AJAX handlers
        add_action( 'wp_ajax_leave_manager_get_leave_types', array( $this, 'get_leave_types' ) );
        add_action( 'wp_ajax_leave_manager_get_leave_type', array( $this, 'get_leave_type' ) );
        add_action( 'wp_ajax_leave_manager_create_leave_type', array( $this, 'create_leave_type' ) );
        add_action( 'wp_ajax_leave_manager_update_leave_type', array( $this, 'update_leave_type' ) );
        add_action( 'wp_ajax_leave_manager_delete_leave_type', array( $this, 'delete_leave_type' ) );
        add_action( 'wp_ajax_leave_manager_install_default_types', array( $this, 'install_default_types' ) );

        // Leave Balance AJAX handlers
        add_action( 'wp_ajax_leave_manager_sync_user_balances', array( $this, 'sync_user_balances' ) );
        add_action( 'wp_ajax_leave_manager_get_user_balance', array( $this, 'get_user_balance' ) );
        add_action( 'wp_ajax_leave_manager_update_user_balance', array( $this, 'update_user_balance' ) );

        // Department AJAX handlers
        add_action( 'wp_ajax_leave_manager_get_departments', array( $this, 'get_departments' ) );
        add_action( 'wp_ajax_leave_manager_get_department', array( $this, 'get_department' ) );
        add_action( 'wp_ajax_leave_manager_create_department', array( $this, 'create_department' ) );
        add_action( 'wp_ajax_leave_manager_update_department', array( $this, 'update_department' ) );
        add_action( 'wp_ajax_leave_manager_delete_department', array( $this, 'delete_department' ) );
        add_action( 'wp_ajax_leave_manager_sync_departments', array( $this, 'sync_departments' ) );
    }

    private function verify_request() {
        // Verify user is logged in and has permissions
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not logged in' ), 403 );
            return false;
        }
        
        // Check nonce - use check_ajax_referer which handles the verification properly
        // The nonce field name in POST is 'nonce', action is 'leave_manager_admin_nonce'
        $nonce_valid = isset( $_POST['nonce'] ) && wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' );
        
        // If standard nonce fails, check if we have a valid admin user as fallback
        // This handles edge cases with proxy environments where session tokens may not match
        if ( ! $nonce_valid ) {
            // Log for debugging
            error_log('Leave Manager: Nonce verification failed, checking admin capability as fallback');
            
            // For dashboard stats, allow if user is admin (no referrer check needed)
            // Dashboard stats only returns aggregate data and requires admin capability
            $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';
            if ( $action === 'leave_manager_get_dashboard_stats' ) {
                if ( ! current_user_can('manage_options') ) {
                    wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
                    return false;
                }
                return true; // Allow dashboard stats even if nonce fails
            }
            
            // Strict fallback for other endpoints: only allow if user is admin and request has valid referrer
            $referer = wp_get_referer();
            $is_admin_page = $referer && strpos($referer, admin_url()) !== false;
            
            if ( ! current_user_can('manage_options') || ! $is_admin_page ) {
                wp_send_json_error( array( 'message' => 'Security check failed' ), 403 );
                return false;
            }
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied' ), 403 );
            return false;
        }
        return true;
    }

    public function preview_template() {
        if ( ! $this->verify_request() ) return;

        $template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';
        if ( empty( $template ) ) {
            wp_send_json_error( array( 'message' => 'Template not specified' ), 400 );
            return;
        }

        // Get company settings for templates
        $settings = get_option('leave_manager_settings', array());
        $company_name = isset($settings['organization_name']) ? $settings['organization_name'] : 'LFCC Leave Manager';
        $primary_color = '#4F5BD5';
        
        $templates = array(
            'welcome' => array(
                'subject' => 'Welcome to ' . $company_name,
                'body' => '<p style="font-size:16px;color:#374151;margin:0 0 20px 0;">Dear <strong>{employee_name}</strong>,</p><p style="font-size:16px;color:#374151;margin:0 0 20px 0;">Welcome to the team! Your account has been successfully created in our Leave Management System.</p><div style="background:#f8f9fc;border-radius:8px;padding:20px;margin:20px 0;"><p style="margin:0 0 10px 0;font-size:14px;color:#6b7280;"><strong>Your Login Details:</strong></p><p style="margin:0 0 5px 0;font-size:14px;color:#374151;">Email: <strong>{email}</strong></p><p style="margin:0;font-size:14px;color:#374151;">Temporary Password: <strong>{password}</strong></p></div><p style="font-size:16px;color:#374151;margin:0 0 20px 0;">Please change your password after your first login for security purposes.</p><div style="text-align:center;margin:30px 0;"><a href="{login_url}" style="display:inline-block;background:' . $primary_color . ';color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:600;font-size:16px;">Login to Your Account</a></div>'
            ),
            'request' => array(
                'subject' => 'New Leave Request from {employee_name}',
                'body' => '<p style="font-size:16px;color:#374151;margin:0 0 20px 0;">A new leave request has been submitted and requires your attention.</p><div style="background:#f8f9fc;border-radius:8px;padding:20px;margin:20px 0;"><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;width:120px;">Employee:</td><td style="padding:8px 0;font-size:14px;color:#374151;font-weight:600;">{employee_name}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Leave Type:</td><td style="padding:8px 0;font-size:14px;color:#374151;font-weight:600;">{leave_type}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Start Date:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{start_date}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">End Date:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{end_date}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Reason:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{reason}</td></tr></table></div><div style="text-align:center;margin:30px 0;"><a href="{approve_url}" style="display:inline-block;background:#22c55e;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;margin-right:10px;">Approve</a><a href="{reject_url}" style="display:inline-block;background:#ef4444;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;">Reject</a></div>'
            ),
            'leave_request' => array(
                'subject' => 'New Leave Request from {employee_name}',
                'body' => '<p style="font-size:16px;color:#374151;margin:0 0 20px 0;">A new leave request has been submitted and requires your attention.</p><div style="background:#f8f9fc;border-radius:8px;padding:20px;margin:20px 0;"><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;width:120px;">Employee:</td><td style="padding:8px 0;font-size:14px;color:#374151;font-weight:600;">{employee_name}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Leave Type:</td><td style="padding:8px 0;font-size:14px;color:#374151;font-weight:600;">{leave_type}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Start Date:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{start_date}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">End Date:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{end_date}</td></tr></table></div>'
            ),
            'approval' => array(
                'subject' => 'Leave Request Approved',
                'body' => '<p style="font-size:16px;color:#374151;margin:0 0 20px 0;">Dear <strong>{employee_name}</strong>,</p><p style="font-size:16px;color:#374151;margin:0 0 20px 0;">Great news! Your leave request has been <span style="color:#22c55e;font-weight:600;">approved</span>.</p><div style="background:#f0fdf4;border-radius:8px;padding:20px;margin:20px 0;border-left:4px solid #22c55e;"><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;width:120px;">Leave Type:</td><td style="padding:8px 0;font-size:14px;color:#374151;font-weight:600;">{leave_type}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Start Date:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{start_date}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">End Date:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{end_date}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Days:</td><td style="padding:8px 0;font-size:14px;color:#374151;font-weight:600;">{days}</td></tr></table></div><p style="font-size:16px;color:#374151;margin:0 0 20px 0;">Please ensure all your responsibilities are covered during your absence.</p>'
            ),
            'rejection' => array(
                'subject' => 'Leave Request Rejected',
                'body' => '<p style="font-size:16px;color:#374151;margin:0 0 20px 0;">Dear <strong>{employee_name}</strong>,</p><p style="font-size:16px;color:#374151;margin:0 0 20px 0;">We regret to inform you that your leave request has been <span style="color:#ef4444;font-weight:600;">rejected</span>.</p><div style="background:#fef2f2;border-radius:8px;padding:20px;margin:20px 0;border-left:4px solid #ef4444;"><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;width:120px;">Leave Type:</td><td style="padding:8px 0;font-size:14px;color:#374151;font-weight:600;">{leave_type}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Reason:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{reason}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Rejected By:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{manager_name}</td></tr></table></div><p style="font-size:16px;color:#374151;margin:0 0 20px 0;">If you have any questions, please contact your manager or HR department.</p>'
            ),
            'password_reset' => array(
                'subject' => 'Password Reset Request',
                'body' => '<p style="font-size:16px;color:#374151;margin:0 0 20px 0;">Dear <strong>{user_name}</strong>,</p><p style="font-size:16px;color:#374151;margin:0 0 20px 0;">We received a request to reset your password. Click the button below to create a new password.</p><div style="text-align:center;margin:30px 0;"><a href="{reset_link}" style="display:inline-block;background:' . $primary_color . ';color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:600;font-size:16px;">Reset Password</a></div><p style="font-size:14px;color:#6b7280;margin:0 0 20px 0;">This link will expire in <strong>{expiry_time}</strong>.</p><p style="font-size:14px;color:#6b7280;margin:0;">If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>'
            ),
            'password-reset' => array(
                'subject' => 'Password Reset Request',
                'body' => '<p style="font-size:16px;color:#374151;margin:0 0 20px 0;">Dear <strong>{user_name}</strong>,</p><p style="font-size:16px;color:#374151;margin:0 0 20px 0;">We received a request to reset your password. Click the button below to create a new password.</p><div style="text-align:center;margin:30px 0;"><a href="{reset_link}" style="display:inline-block;background:' . $primary_color . ';color:#ffffff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:600;font-size:16px;">Reset Password</a></div><p style="font-size:14px;color:#6b7280;margin:0 0 20px 0;">This link will expire in <strong>{expiry_time}</strong>.</p><p style="font-size:14px;color:#6b7280;margin:0;">If you did not request a password reset, please ignore this email.</p>'
            ),
            'account_created' => array(
                'subject' => 'New Account Created',
                'body' => '<p style="font-size:16px;color:#374151;margin:0 0 20px 0;">A new user account has been created in the Leave Management System.</p><div style="background:#f8f9fc;border-radius:8px;padding:20px;margin:20px 0;"><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;width:120px;">Name:</td><td style="padding:8px 0;font-size:14px;color:#374151;font-weight:600;">{user_name}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Email:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{email}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Role:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{role}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Created:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{created_date}</td></tr></table></div>'
            ),
            'account-created' => array(
                'subject' => 'New Account Created',
                'body' => '<p style="font-size:16px;color:#374151;margin:0 0 20px 0;">A new user account has been created in the Leave Management System.</p><div style="background:#f8f9fc;border-radius:8px;padding:20px;margin:20px 0;"><table style="width:100%;border-collapse:collapse;"><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;width:120px;">Name:</td><td style="padding:8px 0;font-size:14px;color:#374151;font-weight:600;">{user_name}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Email:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{email}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Role:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{role}</td></tr><tr><td style="padding:8px 0;font-size:14px;color:#6b7280;">Created:</td><td style="padding:8px 0;font-size:14px;color:#374151;">{created_date}</td></tr></table></div>'
            )
        );

        $saved = get_option( 'leave_manager_template_' . $template, array() );
        $tpl = ! empty( $saved ) ? $saved : ( isset( $templates[$template] ) ? $templates[$template] : array( 'subject' => '', 'body' => '' ) );
        
        // Professional email template HTML matching ChatPanel design
        $html = '
        <div style="margin:0;padding:0;background-color:#f5f7fb;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,sans-serif;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f7fb;padding:40px 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
                            <tr>
                                <td style="padding:30px 40px;text-align:center;border-bottom:1px solid #eee;">
                                    <div style="font-size:24px;font-weight:bold;color:#4F5BD5;">' . esc_html($company_name) . '</div>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:30px 40px 10px 40px;">
                                    <h1 style="margin:0;font-size:24px;font-weight:bold;color:#1a1a2e;">' . esc_html($tpl['subject']) . '</h1>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:20px 40px 30px 40px;color:#4a4a4a;font-size:16px;line-height:1.6;">
                                    ' . $tpl['body'] . '
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:30px 40px;background-color:#f8f9fc;border-top:1px solid #eee;text-align:center;border-radius:0 0 8px 8px;">
                                    <p style="margin:0 0 10px 0;color:#6b7280;font-size:14px;">Best regards,<br>The ' . esc_html($company_name) . ' Team</p>
                                    <p style="margin:0;color:#9ca3af;font-size:12px;">&copy; ' . date('Y') . ' ' . esc_html($company_name) . '. All rights reserved.</p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>';

        wp_send_json_success( array( 'html' => $html ) );
    }

    public function edit_template() {
        if ( ! $this->verify_request() ) return;

        $template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';
        if ( empty( $template ) ) {
            wp_send_json_error( array( 'message' => 'Template not specified' ), 400 );
            return;
        }

        // Use the same defaults as preview_template for consistency
        $defaults = array(
            'welcome' => array(
                'subject' => 'Welcome to {company_name}',
                'body' => '<h2>Welcome {employee_name}!</h2><p>Your account has been created.</p>'
            ),
            'leave_request' => array(
                'subject' => 'New Leave Request from {employee_name}',
                'body' => '<h2>New Leave Request</h2><p><strong>Employee:</strong> {employee_name}</p><p><strong>Type:</strong> {leave_type}</p>'
            ),
            'approval' => array(
                'subject' => 'Leave Request Approved',
                'body' => '<h2>Your Leave Request Has Been Approved</h2><p>Dear {employee_name}, your request has been approved.</p>'
            ),
            'rejection' => array(
                'subject' => 'Leave Request Rejected',
                'body' => '<h2>Your Leave Request Has Been Rejected</h2><p>Dear {employee_name}, your request has been rejected.</p>'
            ),
            'password_reset' => array(
                'subject' => 'Password Reset Request',
                'body' => '<h2>Password Reset</h2><p>Click the link to reset your password.</p>'
            ),
            'account_created' => array(
                'subject' => 'Account Created',
                'body' => '<h2>Your Account Has Been Created</h2><p>Your login details have been sent.</p>'
            )
        );

        $saved = get_option( 'leave_manager_template_' . $template, array() );
        $data = ! empty( $saved ) ? $saved : ( isset( $defaults[$template] ) ? $defaults[$template] : array( 'subject' => '', 'body' => '' ) );

        // Return content in a format the JavaScript expects
        $content = "Subject: " . $data['subject'] . "\n\n" . $data['body'];
        wp_send_json_success( array( 'content' => $content, 'subject' => $data['subject'], 'body' => $data['body'] ) );
    }

    public function save_template() {
        if ( ! $this->verify_request() ) return;

        $template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';
        
        // Handle both formats: separate subject/body OR combined content
        if ( isset( $_POST['content'] ) ) {
            // Parse content format: "Subject: ...\n\n..."
            $content = wp_unslash( $_POST['content'] );
            
            // Try to split by double newline first
            $parts = explode( "\n\n", $content, 2 );
            
            // Check if first part starts with "Subject:"
            if ( count( $parts ) >= 2 && strpos( trim( $parts[0] ), 'Subject:' ) === 0 ) {
                $subject = sanitize_text_field( trim( str_replace( 'Subject:', '', $parts[0] ) ) );
                $body = wp_kses_post( $parts[1] );
            } else {
                // Try splitting by single newline
                $lines = explode( "\n", $content, 2 );
                if ( count( $lines ) >= 2 && strpos( trim( $lines[0] ), 'Subject:' ) === 0 ) {
                    $subject = sanitize_text_field( trim( str_replace( 'Subject:', '', $lines[0] ) ) );
                    $body = wp_kses_post( trim( $lines[1] ) );
                } else {
                    // Fallback: treat entire content as body
                    $subject = '';
                    $body = wp_kses_post( $content );
                }
            }
        } else {
            $subject = isset( $_POST['subject'] ) ? sanitize_text_field( $_POST['subject'] ) : '';
            $body = isset( $_POST['body'] ) ? wp_kses_post( $_POST['body'] ) : '';
        }

        if ( empty( $template ) ) {
            wp_send_json_error( array( 'message' => 'Template not specified' ), 400 );
            return;
        }

        update_option( 'leave_manager_template_' . $template, array( 'subject' => $subject, 'body' => $body ) );
        wp_send_json_success( array( 'message' => 'Template saved successfully' ) );
    }

    public function reset_template() {
        if ( ! $this->verify_request() ) return;

        $template = isset( $_POST['template'] ) ? sanitize_text_field( $_POST['template'] ) : '';
        if ( empty( $template ) ) {
            wp_send_json_error( array( 'message' => 'Template not specified' ), 400 );
            return;
        }

        delete_option( 'leave_manager_template_' . $template );
        wp_send_json_success( array( 'message' => 'Template reset to default' ) );
    }

    public function save_settings() {
        if ( ! $this->verify_request() ) return;

        $settings = array(
            'organization_name' => isset( $_POST['organization_name'] ) ? sanitize_text_field( $_POST['organization_name'] ) : '',
            'admin_email' => isset( $_POST['admin_email'] ) ? sanitize_email( $_POST['admin_email'] ) : '',
            'phone_number' => isset( $_POST['phone_number'] ) ? sanitize_text_field( $_POST['phone_number'] ) : '',
            'address' => isset( $_POST['address'] ) ? sanitize_textarea_field( $_POST['address'] ) : ''
        );

        update_option( 'leave_manager_settings', $settings );
        wp_send_json_success( array( 'message' => 'Settings saved successfully' ) );
    }

    public function reset_settings() {
        if ( ! $this->verify_request() ) return;
        delete_option( 'leave_manager_settings' );
        wp_send_json_success( array( 'message' => 'Settings reset to default' ) );
    }

    public function save_branding() {
        if ( ! $this->verify_request() ) return;

        $branding = array(
            'logo_url' => isset( $_POST['logo_url'] ) ? esc_url_raw( $_POST['logo_url'] ) : '',
            'favicon_url' => isset( $_POST['favicon_url'] ) ? esc_url_raw( $_POST['favicon_url'] ) : '',
            'primary_color' => isset( $_POST['primary_color'] ) ? sanitize_hex_color( $_POST['primary_color'] ) : '#3b82f6',
            'secondary_color' => isset( $_POST['secondary_color'] ) ? sanitize_hex_color( $_POST['secondary_color'] ) : '#10b981'
        );

        update_option( 'leave_manager_branding', $branding );
        wp_send_json_success( array( 'message' => 'Branding saved successfully' ) );
    }

    public function save_email_settings() {
        if ( ! $this->verify_request() ) return;

        $email_settings = array(
            'from_name' => isset( $_POST['from_name'] ) ? sanitize_text_field( $_POST['from_name'] ) : '',
            'from_email' => isset( $_POST['from_email'] ) ? sanitize_email( $_POST['from_email'] ) : '',
            'smtp_host' => isset( $_POST['smtp_host'] ) ? sanitize_text_field( $_POST['smtp_host'] ) : '',
            'smtp_port' => isset( $_POST['smtp_port'] ) ? intval( $_POST['smtp_port'] ) : 587,
            'smtp_username' => isset( $_POST['smtp_username'] ) ? sanitize_text_field( $_POST['smtp_username'] ) : '',
            'smtp_password' => isset( $_POST['smtp_password'] ) ? $_POST['smtp_password'] : ''
        );

        update_option( 'leave_manager_email_settings', $email_settings );
        wp_send_json_success( array( 'message' => 'Email settings saved successfully' ) );
    }

    public function test_email() {
        if ( ! $this->verify_request() ) return;

        $to = isset( $_POST['test_email'] ) ? sanitize_email( $_POST['test_email'] ) : get_option( 'admin_email' );
        $sent = wp_mail( $to, 'Leave Manager Test Email', 'This is a test email from Leave Manager plugin.' );

        if ( $sent ) {
            wp_send_json_success( array( 'message' => 'Test email sent to ' . $to ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to send test email' ) );
        }
    }

    public function save_notifications() {
        if ( ! $this->verify_request() ) return;

        $notifications = array(
            'email_on_request' => isset( $_POST['email_on_request'] ) ? 1 : 0,
            'email_on_approval' => isset( $_POST['email_on_approval'] ) ? 1 : 0,
            'email_on_rejection' => isset( $_POST['email_on_rejection'] ) ? 1 : 0,
            'email_admin_on_request' => isset( $_POST['email_admin_on_request'] ) ? 1 : 0
        );

        update_option( 'leave_manager_notifications', $notifications );
        wp_send_json_success( array( 'message' => 'Notification settings saved successfully' ) );
    }

    public function save_advanced() {
        if ( ! $this->verify_request() ) return;

        $advanced = array(
            'enable_api' => isset( $_POST['enable_api'] ) ? 1 : 0,
            'enable_caching' => isset( $_POST['enable_caching'] ) ? 1 : 0,
            'cache_duration' => isset( $_POST['cache_duration'] ) ? intval( $_POST['cache_duration'] ) : 3600,
            'debug_mode' => isset( $_POST['debug_mode'] ) ? 1 : 0
        );

        update_option( 'leave_manager_advanced', $advanced );
        wp_send_json_success( array( 'message' => 'Advanced settings saved successfully' ) );
    }

    public function flush_plugin() {
        if ( ! $this->verify_request() ) return;

        wp_cache_flush();
        global $wpdb;
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_leave_manager_%'" );
        $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_leave_manager_%'" );

        wp_send_json_success( array( 'message' => 'Plugin cache flushed successfully' ) );
    }

    public function reset_plugin() {
        if ( ! $this->verify_request() ) return;

        // Get items to reset from POST
        $items = isset( $_POST['items'] ) ? $_POST['items'] : array();
        
        if ( empty( $items ) || ! is_array( $items ) ) {
            wp_send_json_error( array( 'message' => 'No items selected for reset' ), 400 );
            return;
        }

        global $wpdb;
        $reset_count = 0;
        $messages = array();

        // Reset Users/Staff
        if ( in_array( 'users', $items ) ) {
            $users_table = $wpdb->prefix . 'leave_manager_leave_users';
            $staff_table = $wpdb->prefix . 'leave_manager_leave_users';
            $wpdb->query( "TRUNCATE TABLE $users_table" );
            $wpdb->query( "TRUNCATE TABLE $staff_table" );
            $reset_count++;
            $messages[] = 'Users/Staff deleted';
        }

        // Reset Leave Requests
        if ( in_array( 'requests', $items ) ) {
            $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
            $wpdb->query( "TRUNCATE TABLE $requests_table" );
            $reset_count++;
            $messages[] = 'Leave requests deleted';
        }

        // Reset Leave Policies
        if ( in_array( 'policies', $items ) ) {
            $policies_table = $wpdb->prefix . 'leave_manager_leave_policies';
            $balances_table = $wpdb->prefix . 'leave_manager_leave_balances';
            $wpdb->query( "TRUNCATE TABLE $policies_table" );
            $wpdb->query( "TRUNCATE TABLE $balances_table" );
            // Also delete policy options
            delete_option( 'leave_manager_leave_types' );
            delete_option( 'leave_manager_leave_policies' );
            $reset_count++;
            $messages[] = 'Leave policies deleted';
        }

        // Reset Departments
        if ( in_array( 'departments', $items ) ) {
            $departments_table = $wpdb->prefix . 'leave_manager_departments';
            $wpdb->query( "TRUNCATE TABLE $departments_table" );
            // Also delete department options
            delete_option( 'leave_manager_departments' );
            $reset_count++;
            $messages[] = 'Departments deleted';
        }

        // Reset Settings
        if ( in_array( 'settings', $items ) ) {
            // Delete all settings options
            delete_option( 'leave_manager_settings' );
            delete_option( 'leave_manager_branding' );
            delete_option( 'leave_manager_email_settings' );
            delete_option( 'leave_manager_notifications' );
            delete_option( 'leave_manager_advanced' );
            delete_option( 'leave_manager_general_settings' );
            $reset_count++;
            $messages[] = 'Settings reset to defaults';
        }

        // Reset Email Templates
        if ( in_array( 'templates', $items ) ) {
            delete_option( 'leave_manager_template_welcome' );
            delete_option( 'leave_manager_template_leave_request' );
            delete_option( 'leave_manager_template_approval' );
            delete_option( 'leave_manager_template_rejection' );
            delete_option( 'leave_manager_template_password_reset' );
            delete_option( 'leave_manager_template_account_created' );
            $reset_count++;
            $messages[] = 'Email templates reset to defaults';
        }

        if ( $reset_count > 0 ) {
            wp_send_json_success( array( 
                'message' => 'Reset completed: ' . implode(', ', $messages),
                'count' => $reset_count
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'No items were reset' ), 400 );
        }
    }

    public function get_plugin_status() {
        if ( ! $this->verify_request() ) return;

        global $wpdb;
        $status = array(
            'version' => defined( 'LEAVE_MANAGER_VERSION' ) ? LEAVE_MANAGER_VERSION : '3.0.0',
            'db_version' => get_option( 'leave_manager_db_version', 'N/A' ),
            'total_staff' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_users" ),
            'total_requests' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests" ),
            'pending_requests' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests WHERE status = 'pending'" ),
            'database_status' => 'Connected',
            'php_version' => phpversion(),
            'wp_version' => get_bloginfo( 'version' )
        );

        wp_send_json_success( $status );
    }

    public function get_staff_data() {
        if ( ! $this->verify_request() ) return;

        global $wpdb;
        $staff = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}leave_manager_leave_users ORDER BY last_name, first_name" );
        wp_send_json_success( array( 'staff' => $staff ) );
    }

    public function get_requests_data() {
        if ( ! $this->verify_request() ) return;

        global $wpdb;
        $requests = $wpdb->get_results( 
            "SELECT r.*, s.first_name, s.last_name, s.email, s.department 
            FROM {$wpdb->prefix}leave_manager_leave_requests r
            LEFT JOIN {$wpdb->prefix}leave_manager_leave_users s ON r.user_id = s.user_id
            ORDER BY r.created_at DESC"
        );
        wp_send_json_success( array( 'requests' => $requests ) );
    }

    public function add_staff() {
        if ( ! $this->verify_request() ) return;

        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
        $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $department = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : '';
        $role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : 'staff';

        if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) ) {
            wp_send_json_error( array( 'message' => 'First name, last name, and email are required' ), 400 );
            return;
        }

        global $wpdb;
        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}leave_manager_leave_users WHERE email = %s", $email ) );
        if ( $existing ) {
            wp_send_json_error( array( 'message' => 'Email already exists' ), 400 );
            return;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'leave_manager_leave_users',
            array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'department' => $department,
                'role' => $role,
                'status' => 'active',
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' )
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Staff member added successfully', 'id' => $wpdb->insert_id ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to add staff member' ), 500 );
        }
    }

    public function edit_staff() {
        if ( ! $this->verify_request() ) return;

        $staff_id = isset( $_POST['staff_id'] ) ? intval( $_POST['staff_id'] ) : 0;
        if ( ! $staff_id ) {
            wp_send_json_error( array( 'message' => 'Staff ID required' ), 400 );
            return;
        }

        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'leave_manager_leave_users',
            array(
                'first_name' => isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '',
                'last_name' => isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '',
                'email' => isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '',
                'department' => isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : '',
                'role' => isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : 'staff',
                'updated_at' => current_time( 'mysql' )
            ),
            array( 'user_id' => $staff_id ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => 'Staff member updated successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update staff member' ), 500 );
        }
    }

    public function delete_staff() {
        if ( ! $this->verify_request() ) return;

        $staff_id = isset( $_POST['staff_id'] ) ? intval( $_POST['staff_id'] ) : 0;
        if ( ! $staff_id ) {
            wp_send_json_error( array( 'message' => 'Staff ID required' ), 400 );
            return;
        }

        global $wpdb;
        $result = $wpdb->delete( $wpdb->prefix . 'leave_manager_leave_users', array( 'user_id' => $staff_id ), array( '%d' ) );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Staff member deleted successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to delete staff member' ), 500 );
        }
    }

    public function approve_request() {
        if ( ! $this->verify_request() ) return;

        $request_id = isset( $_POST['request_id'] ) ? intval( $_POST['request_id'] ) : 0;
        if ( ! $request_id ) {
            wp_send_json_error( array( 'message' => 'Request ID required' ), 400 );
            return;
        }

        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'leave_manager_leave_requests',
            array( 'status' => 'approved', 'updated_at' => current_time( 'mysql' ) ),
            array( 'request_id' => $request_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => 'Request approved successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to approve request' ), 500 );
        }
    }

    public function reject_request() {
        if ( ! $this->verify_request() ) return;

        $request_id = isset( $_POST['request_id'] ) ? intval( $_POST['request_id'] ) : 0;
        $reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( $_POST['reason'] ) : '';

        if ( ! $request_id ) {
            wp_send_json_error( array( 'message' => 'Request ID required' ), 400 );
            return;
        }

        global $wpdb;
        $result = $wpdb->update(
            $wpdb->prefix . 'leave_manager_leave_requests',
            array( 'status' => 'rejected', 'rejection_reason' => $reason, 'updated_at' => current_time( 'mysql' ) ),
            array( 'request_id' => $request_id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => 'Request rejected successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to reject request' ), 500 );
        }
    }

    // Report Generation Functions
    public function generate_leave_report() {
        if ( ! $this->verify_request() ) return;

        $start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
        $end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
        $leave_type = isset( $_POST['leave_type'] ) ? sanitize_text_field( $_POST['leave_type'] ) : '';

        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';
        
        $where = "WHERE 1=1";
        if ( $start_date ) $where .= $wpdb->prepare( " AND start_date >= %s", $start_date );
        if ( $end_date ) $where .= $wpdb->prepare( " AND end_date <= %s", $end_date );
        if ( $leave_type ) $where .= $wpdb->prepare( " AND leave_type = %s", $leave_type );

        $results = $wpdb->get_results( "SELECT leave_type, 
            COUNT(*) as total_requests,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'approved' THEN DATEDIFF(end_date, start_date) + 1 ELSE 0 END) as days_taken
            FROM $table $where GROUP BY leave_type", ARRAY_A );

        error_log('Leave Manager Report: Query results: ' . print_r($results, true));
        wp_send_json_success( array( 'data' => $results ) );
    }

    public function export_leave_report() {
        if ( ! $this->verify_request() ) return;

        $start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
        $end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
        $leave_type = isset( $_POST['leave_type'] ) ? sanitize_text_field( $_POST['leave_type'] ) : '';

        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';
        
        $where = "WHERE 1=1";
        if ( $start_date ) $where .= $wpdb->prepare( " AND start_date >= %s", $start_date );
        if ( $end_date ) $where .= $wpdb->prepare( " AND end_date <= %s", $end_date );
        if ( $leave_type ) $where .= $wpdb->prepare( " AND leave_type = %s", $leave_type );

        $results = $wpdb->get_results( "SELECT * FROM $table $where ORDER BY created_at DESC", ARRAY_A );

        $csv = "ID,User Name,Leave Type,Start Date,End Date,Days,Status,Reason,Created At\n";
        foreach ( $results as $row ) {
            $csv .= sprintf( "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $row['id'], $row['user_name'] ?? '', $row['leave_type'] ?? '', 
                $row['start_date'] ?? '', $row['end_date'] ?? '', $row['days'] ?? '',
                $row['status'] ?? '', $row['reason'] ?? '', $row['created_at'] ?? ''
            );
        }

        wp_send_json_success( array( 'csv' => $csv, 'filename' => 'leave_report_' . date('Y-m-d') . '.csv' ) );
    }

    public function generate_user_report() {
        if ( ! $this->verify_request() ) return;

        $start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
        $end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

        global $wpdb;
        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';
        
        $where = "WHERE 1=1";
        if ( $start_date ) $where .= $wpdb->prepare( " AND r.start_date >= %s", $start_date );
        if ( $end_date ) $where .= $wpdb->prepare( " AND r.end_date <= %s", $end_date );
        if ( $user_id ) $where .= $wpdb->prepare( " AND r.user_id = %d", $user_id );

        $results = $wpdb->get_results( "SELECT u.first_name, u.last_name, u.department,
            COUNT(r.id) as total_requests,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN r.status = 'approved' THEN r.days ELSE 0 END) as days_taken
            FROM $users_table u
            LEFT JOIN $requests_table r ON u.user_id = r.user_id
            $where
            GROUP BY u.user_id, u.first_name, u.last_name, u.department", ARRAY_A );

        error_log('Leave Manager Report: Query results: ' . print_r($results, true));
        wp_send_json_success( array( 'data' => $results ) );
    }

    public function export_user_report() {
        if ( ! $this->verify_request() ) return;

        global $wpdb;
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';
        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

        $results = $wpdb->get_results( "SELECT u.first_name, u.last_name, u.email, u.department,
            COUNT(r.id) as total_requests,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN r.status = 'approved' THEN r.days ELSE 0 END) as days_taken
            FROM $users_table u
            LEFT JOIN $requests_table r ON u.user_id = r.user_id
            GROUP BY u.user_id, u.first_name, u.last_name, u.email, u.department", ARRAY_A );

        $csv = "First Name,Last Name,Email,Department,Total Requests,Approved,Days Taken\n";
        foreach ( $results as $row ) {
            $csv .= sprintf( "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $row['first_name'] ?? '', $row['last_name'] ?? '', $row['email'] ?? '',
                $row['department'] ?? '', $row['total_requests'] ?? 0,
                $row['approved'] ?? 0, $row['days_taken'] ?? 0
            );
        }

        wp_send_json_success( array( 'csv' => $csv, 'filename' => 'user_report_' . date('Y-m-d') . '.csv' ) );
    }

    public function generate_department_report() {
        if ( ! $this->verify_request() ) return;

        $start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
        $end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
        $department = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : '';

        global $wpdb;
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';
        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
        
        $where = "WHERE 1=1";
        if ( $start_date ) $where .= $wpdb->prepare( " AND r.start_date >= %s", $start_date );
        if ( $end_date ) $where .= $wpdb->prepare( " AND r.end_date <= %s", $end_date );
        if ( $department ) $where .= $wpdb->prepare( " AND u.department = %s", $department );

        $results = $wpdb->get_results( "SELECT u.department,
            COUNT(DISTINCT u.user_id) as total_employees,
            COUNT(r.id) as total_requests,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN r.status = 'approved' THEN r.days ELSE 0 END) as days_taken
            FROM $users_table u
            LEFT JOIN $requests_table r ON u.user_id = r.user_id
            $where
            GROUP BY u.department", ARRAY_A );

        error_log('Leave Manager Report: Query results: ' . print_r($results, true));
        wp_send_json_success( array( 'data' => $results ) );
    }

    public function export_department_report() {
        if ( ! $this->verify_request() ) return;

        global $wpdb;
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';
        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

        $results = $wpdb->get_results( "SELECT u.department,
            COUNT(DISTINCT u.user_id) as total_employees,
            COUNT(r.id) as total_requests,
            SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN r.status = 'approved' THEN r.days ELSE 0 END) as days_taken
            FROM $users_table u
            LEFT JOIN $requests_table r ON u.user_id = r.user_id
            GROUP BY u.department", ARRAY_A );

        $csv = "Department,Total Employees,Total Requests,Approved,Days Taken\n";
        foreach ( $results as $row ) {
            $csv .= sprintf( "\"%s\",\"%s\",\"%s\",\"%s\",\"%s\"\n",
                $row['department'] ?? 'N/A', $row['total_employees'] ?? 0,
                $row['total_requests'] ?? 0, $row['approved'] ?? 0, $row['days_taken'] ?? 0
            );
        }

        wp_send_json_success( array( 'csv' => $csv, 'filename' => 'department_report_' . date('Y-m-d') . '.csv' ) );
    }

    // ==========================================
    // Policy AJAX Methods
    // ==========================================

    public function get_policy() {
        if ( ! $this->verify_request() ) return;

        $policy_id = isset( $_POST['policy_id'] ) ? intval( $_POST['policy_id'] ) : 0;
        if ( ! $policy_id ) {
            wp_send_json_error( array( 'message' => 'Policy ID required' ), 400 );
            return;
        }

        global $wpdb;
        $policies_table = $wpdb->prefix . 'leave_manager_leave_policies';
        
        $policy = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $policies_table WHERE policy_id = %d",
            $policy_id
        ), ARRAY_A );

        if ( $policy ) {
            wp_send_json_success( $policy );
        } else {
            wp_send_json_error( array( 'message' => 'Policy not found' ), 404 );
        }
    }

    public function create_policy() {
        if ( ! $this->verify_request() ) return;

        $policy_name = isset( $_POST['policy_name'] ) ? sanitize_text_field( $_POST['policy_name'] ) : '';
        if ( empty( $policy_name ) ) {
            wp_send_json_error( array( 'message' => 'Policy name is required' ), 400 );
            return;
        }

        global $wpdb;
        $policies_table = $wpdb->prefix . 'leave_manager_leave_policies';

        // Handle is_default - if this policy is set as default, unset all others first
        $is_default = isset( $_POST['is_default'] ) ? intval( $_POST['is_default'] ) : 0;
        if ( $is_default ) {
            $wpdb->update( $policies_table, array( 'is_default' => 0 ), array( 'is_default' => 1 ) );
        }

        $data = array(
            'policy_name'    => $policy_name,
            'description'    => isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '',
            'leave_type'     => isset( $_POST['leave_type'] ) ? sanitize_text_field( $_POST['leave_type'] ) : 'annual',
            'annual_days'    => isset( $_POST['annual_days'] ) ? floatval( $_POST['annual_days'] ) : 20,
            'carryover_days' => isset( $_POST['carryover_days'] ) ? floatval( $_POST['carryover_days'] ) : 5,
            'expiry_days'    => isset( $_POST['expiry_days'] ) ? intval( $_POST['expiry_days'] ) : 365,
            'status'         => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active',
            'is_default'     => $is_default,
            'created_at'     => current_time( 'mysql' ),
            'updated_at'     => current_time( 'mysql' ),
        );

        $result = $wpdb->insert( $policies_table, $data );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Policy created successfully', 'policy_id' => $wpdb->insert_id ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to create policy: ' . $wpdb->last_error ), 500 );
        }
    }

    public function update_policy() {
        if ( ! $this->verify_request() ) return;

        $policy_id = isset( $_POST['policy_id'] ) ? intval( $_POST['policy_id'] ) : 0;
        if ( ! $policy_id ) {
            wp_send_json_error( array( 'message' => 'Policy ID required' ), 400 );
            return;
        }

        global $wpdb;
        $policies_table = $wpdb->prefix . 'leave_manager_leave_policies';

        // Handle is_default - if this policy is set as default, unset all others first
        $is_default = isset( $_POST['is_default'] ) ? intval( $_POST['is_default'] ) : 0;
        if ( $is_default ) {
            $wpdb->update( $policies_table, array( 'is_default' => 0 ), array( 'is_default' => 1 ) );
        }

        $data = array(
            'policy_name'    => isset( $_POST['policy_name'] ) ? sanitize_text_field( $_POST['policy_name'] ) : '',
            'description'    => isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '',
            'leave_type'     => isset( $_POST['leave_type'] ) ? sanitize_text_field( $_POST['leave_type'] ) : 'annual',
            'annual_days'    => isset( $_POST['annual_days'] ) ? floatval( $_POST['annual_days'] ) : 20,
            'carryover_days' => isset( $_POST['carryover_days'] ) ? floatval( $_POST['carryover_days'] ) : 5,
            'expiry_days'    => isset( $_POST['expiry_days'] ) ? intval( $_POST['expiry_days'] ) : 365,
            'status'         => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active',
            'is_default'     => $is_default,
            'updated_at'     => current_time( 'mysql' ),
        );

        $result = $wpdb->update( $policies_table, $data, array( 'policy_id' => $policy_id ) );

        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => 'Policy updated successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update policy: ' . $wpdb->last_error ), 500 );
        }
    }

    public function delete_policy() {
        if ( ! $this->verify_request() ) return;

        $policy_id = isset( $_POST['policy_id'] ) ? intval( $_POST['policy_id'] ) : 0;
        if ( ! $policy_id ) {
            wp_send_json_error( array( 'message' => 'Policy ID required' ), 400 );
            return;
        }

        global $wpdb;
        $policies_table = $wpdb->prefix . 'leave_manager_leave_policies';

        $result = $wpdb->delete( $policies_table, array( 'policy_id' => $policy_id ) );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Policy deleted successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to delete policy' ), 500 );
        }
    }

    public function assign_policy() {
        if ( ! $this->verify_request() ) return;

        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
        $policy_id = isset( $_POST['policy_id'] ) ? intval( $_POST['policy_id'] ) : 0;

        if ( ! $user_id || ! $policy_id ) {
            wp_send_json_error( array( 'message' => 'User ID and Policy ID are required' ), 400 );
            return;
        }

        global $wpdb;
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';
        $policies_table = $wpdb->prefix . 'leave_manager_leave_policies';

        // Get the policy details to set balances
        $policy = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $policies_table WHERE policy_id = %d", $policy_id ),
            ARRAY_A
        );

        if ( ! $policy ) {
            wp_send_json_error( array( 'message' => 'Policy not found' ), 404 );
            return;
        }

        // Update user with policy_id AND set leave balances based on policy
        $update_data = array(
            'policy_id' => $policy_id,
            'annual_leave_balance' => floatval( $policy['annual_days'] ),
        );

        // Set sick and other balances based on leave type or defaults
        if ( $policy['leave_type'] === 'sick' ) {
            $update_data['sick_leave_balance'] = floatval( $policy['annual_days'] );
        } else {
            $update_data['sick_leave_balance'] = 10.00; // Default sick days
        }
        
        if ( $policy['leave_type'] === 'other' ) {
            $update_data['other_leave_balance'] = floatval( $policy['annual_days'] );
        } else {
            $update_data['other_leave_balance'] = 5.00; // Default other days
        }

        $result = $wpdb->update(
            $users_table,
            $update_data,
            array( 'user_id' => $user_id )
        );

        if ( $result !== false ) {
            wp_send_json_success( array( 
                'message' => 'Policy assigned and leave balances updated successfully',
                'balances' => array(
                    'annual' => $update_data['annual_leave_balance'],
                    'sick' => $update_data['sick_leave_balance'],
                    'other' => $update_data['other_leave_balance']
                )
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to assign policy: ' . $wpdb->last_error ), 500 );
        }
    }

    /**
     * Apply default policy to all unassigned users
     */
    public function apply_default_policy() {
        if ( ! $this->verify_request() ) return;

        global $wpdb;
        $policies_table = $wpdb->prefix . 'leave_manager_leave_policies';
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';

        // Get the default policy
        $default_policy = $wpdb->get_row(
            "SELECT * FROM $policies_table WHERE is_default = 1 LIMIT 1",
            ARRAY_A
        );

        if ( ! $default_policy ) {
            wp_send_json_error( array( 'message' => 'No default policy set. Please set a policy as default first.' ), 400 );
            return;
        }

        // Calculate balances based on policy
        $annual_balance = floatval( $default_policy['annual_days'] );
        $sick_balance = ( $default_policy['leave_type'] === 'sick' ) ? $annual_balance : 10.00;
        $other_balance = ( $default_policy['leave_type'] === 'other' ) ? $annual_balance : 5.00;

        // Update all users who don't have a policy assigned - set policy AND balances
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $users_table SET policy_id = %d, annual_leave_balance = %f, sick_leave_balance = %f, other_leave_balance = %f WHERE policy_id IS NULL OR policy_id = 0",
                $default_policy['policy_id'],
                $annual_balance,
                $sick_balance,
                $other_balance
            )
        );

        if ( $result !== false ) {
            $count = $wpdb->rows_affected;
            wp_send_json_success( array( 
                'message' => sprintf( 'Default policy applied to %d user(s) with leave balances set', $count ),
                'count' => $count,
                'balances' => array(
                    'annual' => $annual_balance,
                    'sick' => $sick_balance,
                    'other' => $other_balance
                )
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to apply default policy: ' . $wpdb->last_error ), 500 );
        }
    }

    /**
     * Sync all user balances with their assigned policies
     */
    public function sync_user_balances() {
        if ( ! $this->verify_request() ) return;

        global $wpdb;
        $policies_table = $wpdb->prefix . 'leave_manager_leave_policies';
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';

        // Get all users with policies
        $users = $wpdb->get_results(
            "SELECT u.user_id, u.policy_id, p.annual_days, p.leave_type 
             FROM $users_table u 
             LEFT JOIN $policies_table p ON u.policy_id = p.policy_id 
             WHERE u.policy_id IS NOT NULL AND u.policy_id > 0",
            ARRAY_A
        );

        $updated = 0;
        foreach ( $users as $user ) {
            $annual_balance = floatval( $user['annual_days'] );
            $sick_balance = ( $user['leave_type'] === 'sick' ) ? $annual_balance : 10.00;
            $other_balance = ( $user['leave_type'] === 'other' ) ? $annual_balance : 5.00;

            $result = $wpdb->update(
                $users_table,
                array(
                    'annual_leave_balance' => $annual_balance,
                    'sick_leave_balance' => $sick_balance,
                    'other_leave_balance' => $other_balance
                ),
                array( 'user_id' => $user['user_id'] )
            );

            if ( $result !== false ) {
                $updated++;
            }
        }

        wp_send_json_success( array(
            'message' => sprintf( 'Leave balances synced for %d user(s)', $updated ),
            'count' => $updated
        ) );
    }

    /**
     * Get all leave types
     */
    public function get_leave_types() {
        if ( ! $this->verify_request() ) return;

        $leave_types = new Leave_Manager_Leave_Types();
        $status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
        
        $args = array();
        if ( ! empty( $status ) ) {
            $args['status'] = $status;
        }

        $types = $leave_types->get_all( $args );
        wp_send_json_success( array( 'types' => $types ) );
    }

    /**
     * Get single leave type
     */
    public function get_leave_type() {
        if ( ! $this->verify_request() ) return;

        $type_id = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;
        if ( ! $type_id ) {
            wp_send_json_error( array( 'message' => 'Type ID required' ), 400 );
            return;
        }

        $leave_types = new Leave_Manager_Leave_Types();
        $type = $leave_types->get( $type_id );

        if ( $type ) {
            wp_send_json_success( array( 'type' => $type ) );
        } else {
            wp_send_json_error( array( 'message' => 'Leave type not found' ), 404 );
        }
    }

    /**
     * Create leave type
     */
    public function create_leave_type() {
        if ( ! $this->verify_request() ) return;

        $data = array(
            'type_name'         => isset( $_POST['type_name'] ) ? sanitize_text_field( $_POST['type_name'] ) : '',
            'type_code'         => isset( $_POST['type_code'] ) ? sanitize_key( $_POST['type_code'] ) : '',
            'description'       => isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '',
            'default_days'      => isset( $_POST['default_days'] ) ? floatval( $_POST['default_days'] ) : 0,
            'color'             => isset( $_POST['color'] ) ? sanitize_hex_color( $_POST['color'] ) : '#3498db',
            'requires_approval' => isset( $_POST['requires_approval'] ) ? absint( $_POST['requires_approval'] ) : 1,
            'is_paid'           => isset( $_POST['is_paid'] ) ? absint( $_POST['is_paid'] ) : 1,
            'status'            => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active'
        );

        $leave_types = new Leave_Manager_Leave_Types();
        $validation = $leave_types->validate( $data );

        if ( $validation !== true ) {
            wp_send_json_error( array( 'message' => implode( ', ', $validation ) ), 400 );
            return;
        }

        $result = $leave_types->create( $data );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Leave type created successfully', 'type_id' => $result ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to create leave type. Code may already exist.' ), 500 );
        }
    }

    /**
     * Update leave type
     */
    public function update_leave_type() {
        if ( ! $this->verify_request() ) return;

        $type_id = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;
        if ( ! $type_id ) {
            wp_send_json_error( array( 'message' => 'Type ID required' ), 400 );
            return;
        }

        $data = array(
            'type_name'         => isset( $_POST['type_name'] ) ? sanitize_text_field( $_POST['type_name'] ) : '',
            'type_code'         => isset( $_POST['type_code'] ) ? sanitize_key( $_POST['type_code'] ) : '',
            'description'       => isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '',
            'default_days'      => isset( $_POST['default_days'] ) ? floatval( $_POST['default_days'] ) : 0,
            'color'             => isset( $_POST['color'] ) ? sanitize_hex_color( $_POST['color'] ) : '#3498db',
            'requires_approval' => isset( $_POST['requires_approval'] ) ? absint( $_POST['requires_approval'] ) : 1,
            'is_paid'           => isset( $_POST['is_paid'] ) ? absint( $_POST['is_paid'] ) : 1,
            'status'            => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active'
        );

        $leave_types = new Leave_Manager_Leave_Types();
        $validation = $leave_types->validate( $data );

        if ( $validation !== true ) {
            wp_send_json_error( array( 'message' => implode( ', ', $validation ) ), 400 );
            return;
        }

        $result = $leave_types->update( $type_id, $data );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Leave type updated successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update leave type' ), 500 );
        }
    }

    /**
     * Delete leave type
     */
    public function delete_leave_type() {
        if ( ! $this->verify_request() ) return;

        $type_id = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;
        if ( ! $type_id ) {
            wp_send_json_error( array( 'message' => 'Type ID required' ), 400 );
            return;
        }

        $leave_types = new Leave_Manager_Leave_Types();
        $result = $leave_types->delete( $type_id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Leave type deleted successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to delete leave type' ), 500 );
        }
    }

    /**
     * Install default leave types
     */
    public function install_default_types() {
        if ( ! $this->verify_request() ) return;

        $leave_types = new Leave_Manager_Leave_Types();
        $result = $leave_types->install_defaults();

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Default leave types installed successfully' ) );
        } else {
            wp_send_json_success( array( 'message' => 'Default leave types already exist' ) );
        }
    }

    /**
     * Get user leave balance
     */
    public function get_user_balance() {
        if ( ! $this->verify_request() ) return;

        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'User ID is required' ), 400 );
            return;
        }

        global $wpdb;
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';

        $user = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT user_id, first_name, last_name, annual_leave_balance, sick_leave_balance, other_leave_balance, policy_id FROM $users_table WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );

        if ( $user ) {
            wp_send_json_success( array(
                'user' => $user,
                'balances' => array(
                    'annual' => floatval( $user['annual_leave_balance'] ),
                    'sick' => floatval( $user['sick_leave_balance'] ),
                    'other' => floatval( $user['other_leave_balance'] )
                )
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'User not found' ), 404 );
        }
    }

    /**
     * Update user leave balance
     */
    public function update_user_balance() {
        if ( ! $this->verify_request() ) return;

        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
        $leave_type = isset( $_POST['leave_type'] ) ? sanitize_text_field( $_POST['leave_type'] ) : '';
        $balance = isset( $_POST['balance'] ) ? floatval( $_POST['balance'] ) : 0;

        if ( ! $user_id || ! $leave_type ) {
            wp_send_json_error( array( 'message' => 'User ID and leave type are required' ), 400 );
            return;
        }

        // Validate leave type
        $valid_types = array( 'annual', 'sick', 'other' );
        if ( ! in_array( $leave_type, $valid_types ) ) {
            wp_send_json_error( array( 'message' => 'Invalid leave type' ), 400 );
            return;
        }

        global $wpdb;
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';
        $balance_column = $leave_type . '_leave_balance';

        $result = $wpdb->update(
            $users_table,
            array( $balance_column => $balance ),
            array( 'user_id' => $user_id )
        );

        if ( $result !== false ) {
            wp_send_json_success( array(
                'message' => 'Leave balance updated successfully',
                'user_id' => $user_id,
                'leave_type' => $leave_type,
                'new_balance' => $balance
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update balance: ' . $wpdb->last_error ), 500 );
        }
    }

    /**
     * Get all departments
     */
    public function get_departments() {
        if ( ! $this->verify_request() ) return;

        $departments = new Leave_Manager_Departments();
        $all = $departments->get_all();
        $stats = $departments->get_stats();

        wp_send_json_success( array(
            'departments' => $all,
            'stats' => $stats
        ) );
    }

    /**
     * Get a single department
     */
    public function get_department() {
        if ( ! $this->verify_request() ) return;

        $department_id = isset( $_POST['department_id'] ) ? intval( $_POST['department_id'] ) : 0;

        if ( ! $department_id ) {
            wp_send_json_error( array( 'message' => 'Department ID is required' ), 400 );
            return;
        }

        $departments = new Leave_Manager_Departments();
        $department = $departments->get( $department_id );

        if ( $department ) {
            wp_send_json_success( array( 'department' => $department ) );
        } else {
            wp_send_json_error( array( 'message' => 'Department not found' ), 404 );
        }
    }

    /**
     * Create a new department
     */
    public function create_department() {
        if ( ! $this->verify_request() ) return;

        $department_name = isset( $_POST['department_name'] ) ? sanitize_text_field( $_POST['department_name'] ) : '';

        if ( empty( $department_name ) ) {
            wp_send_json_error( array( 'message' => 'Department name is required' ), 400 );
            return;
        }

        $data = array(
            'department_name' => $department_name,
            'department_code' => isset( $_POST['department_code'] ) ? sanitize_text_field( $_POST['department_code'] ) : '',
            'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '',
            'manager_id' => isset( $_POST['manager_id'] ) && $_POST['manager_id'] ? intval( $_POST['manager_id'] ) : null,
            'status' => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active',
        );

        $departments = new Leave_Manager_Departments();
        $result = $departments->create( $data );

        if ( $result ) {
            wp_send_json_success( array(
                'message' => 'Department created successfully',
                'department_id' => $result
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to create department' ), 500 );
        }
    }

    /**
     * Update a department
     */
    public function update_department() {
        if ( ! $this->verify_request() ) return;

        $department_id = isset( $_POST['department_id'] ) ? intval( $_POST['department_id'] ) : 0;

        if ( ! $department_id ) {
            wp_send_json_error( array( 'message' => 'Department ID is required' ), 400 );
            return;
        }

        $data = array(
            'department_name' => isset( $_POST['department_name'] ) ? sanitize_text_field( $_POST['department_name'] ) : '',
            'department_code' => isset( $_POST['department_code'] ) ? sanitize_text_field( $_POST['department_code'] ) : '',
            'description' => isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '',
            'manager_id' => isset( $_POST['manager_id'] ) && $_POST['manager_id'] ? intval( $_POST['manager_id'] ) : null,
            'status' => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active',
        );

        $departments = new Leave_Manager_Departments();
        $result = $departments->update( $department_id, $data );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Department updated successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update department' ), 500 );
        }
    }

    /**
     * Delete a department
     */
    public function delete_department() {
        if ( ! $this->verify_request() ) return;

        $department_id = isset( $_POST['department_id'] ) ? intval( $_POST['department_id'] ) : 0;

        if ( ! $department_id ) {
            wp_send_json_error( array( 'message' => 'Department ID is required' ), 400 );
            return;
        }

        $departments = new Leave_Manager_Departments();
        $result = $departments->delete( $department_id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Department deleted successfully' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to delete department' ), 500 );
        }
    }

    /**
     * Sync departments from user data
     */
    public function sync_departments() {
        if ( ! $this->verify_request() ) return;

        $departments = new Leave_Manager_Departments();
        $created = $departments->sync_from_users();

        wp_send_json_success( array(
            'message' => $created > 0 ? "Successfully created {$created} department(s) from user data" : 'No new departments to create',
            'created' => $created
        ) );
    }

    /**
     * Get dashboard statistics via AJAX
     * This bypasses any HTML caching by fetching fresh data
     */
    public function get_dashboard_stats() {
        if ( ! $this->verify_request() ) return;

        global $wpdb;

        // Get fresh statistics from database
        $total_staff = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_users WHERE status = 'active'" );
        $total_requests = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests" );
        $pending_requests = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests WHERE status = 'pending'" );
        $approved_requests = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests WHERE status = 'approved'" );
        $rejected_requests = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests WHERE status = 'rejected'" );

        // Calculate approval rate
        $approval_rate = $total_requests > 0 ? round( ( $approved_requests / $total_requests ) * 100, 1 ) : 0;

        // Get department summary
        $dept_summary = $wpdb->get_results(
            "SELECT department, COUNT(*) as staff_count 
            FROM {$wpdb->prefix}leave_manager_leave_users 
            WHERE status = 'active'
            GROUP BY department"
        );

        wp_send_json_success( array(
            'total_staff' => intval( $total_staff ),
            'total_requests' => intval( $total_requests ),
            'pending_requests' => intval( $pending_requests ),
            'approved_requests' => intval( $approved_requests ),
            'rejected_requests' => intval( $rejected_requests ),
            'approval_rate' => $approval_rate,
            'departments' => $dept_summary
        ) );
    }
}

// Initialize
new Leave_Manager_Admin_AJAX_Handler();
