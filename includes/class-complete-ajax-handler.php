<?php
/**
 * Complete AJAX Handler for Leave Manager
 * Handles all admin functionality
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Leave_Manager_Complete_AJAX_Handler {

    public function __construct() {
        // Staff actions
        add_action( 'wp_ajax_leave_manager_add_staff', array( $this, 'add_staff' ) );
        add_action( 'wp_ajax_leave_manager_edit_staff', array( $this, 'edit_staff' ) );
        add_action( 'wp_ajax_leave_manager_delete_staff', array( $this, 'delete_staff' ) );

        // Request actions
        add_action( 'wp_ajax_leave_manager_create_request', array( $this, 'create_request' ) );
        add_action( 'wp_ajax_leave_manager_approve_request', array( $this, 'approve_request' ) );
        add_action( 'wp_ajax_leave_manager_reject_request', array( $this, 'reject_request' ) );
        add_action( 'wp_ajax_leave_manager_delete_request', array( $this, 'delete_request' ) );

        // Report actions
        add_action( 'wp_ajax_leave_manager_generate_leave_report', array( $this, 'generate_leave_report' ) );
        add_action( 'wp_ajax_leave_manager_export_leave_report', array( $this, 'export_leave_report' ) );
        add_action( 'wp_ajax_leave_manager_generate_user_report', array( $this, 'generate_user_report' ) );
        add_action( 'wp_ajax_leave_manager_export_user_report', array( $this, 'export_user_report' ) );
        add_action( 'wp_ajax_leave_manager_generate_department_report', array( $this, 'generate_department_report' ) );
        add_action( 'wp_ajax_leave_manager_export_department_report', array( $this, 'export_department_report' ) );

        // Template actions
        add_action( 'wp_ajax_leave_manager_preview_template', array( $this, 'preview_template' ) );
        add_action( 'wp_ajax_leave_manager_edit_template', array( $this, 'edit_template' ) );
        add_action( 'wp_ajax_leave_manager_delete_template', array( $this, 'delete_template' ) );

        // Settings actions
        add_action( 'wp_ajax_leave_manager_save_general_settings', array( $this, 'save_general_settings' ) );
        add_action( 'wp_ajax_leave_manager_save_email_settings', array( $this, 'save_email_settings' ) );
        add_action( 'wp_ajax_leave_manager_save_leave_policies', array( $this, 'save_leave_policies' ) );
        add_action( 'wp_ajax_leave_manager_save_notification_settings', array( $this, 'save_notification_settings' ) );
        add_action( 'wp_ajax_leave_manager_save_appearance_settings', array( $this, 'save_appearance_settings' ) );
        add_action( 'wp_ajax_leave_manager_save_integration_settings', array( $this, 'save_integration_settings' ) );
        add_action( 'wp_ajax_leave_manager_test_email_settings', array( $this, 'test_email_settings' ) );
    }

    private function verify_nonce() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
            wp_send_json_error( 'Invalid nonce' );
        }
    }

    private function check_permission() {
        if ( ! current_user_can( 'manage_leave_manager' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }
    }

    // ============================================
    // STAFF FUNCTIONS
    // ============================================

    public function add_staff() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $name = sanitize_text_field( $_POST['name'] ?? '' );
        $email = sanitize_email( $_POST['email'] ?? '' );
        $department = sanitize_text_field( $_POST['department'] ?? '' );

        if ( ! $name || ! $email ) {
            wp_send_json_error( 'Missing required fields' );
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'leave_manager_users',
            array(
                'name' => $name,
                'email' => $email,
                'department' => $department,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s' )
        );

        if ( $result ) {
            wp_send_json_success( 'Staff member added' );
        } else {
            wp_send_json_error( 'Failed to add staff member' );
        }
    }

    public function edit_staff() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $id = intval( $_POST['id'] ?? 0 );
        $name = sanitize_text_field( $_POST['name'] ?? '' );
        $email = sanitize_email( $_POST['email'] ?? '' );
        $department = sanitize_text_field( $_POST['department'] ?? '' );

        if ( ! $id ) {
            wp_send_json_error( 'Invalid staff ID' );
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'leave_manager_users',
            array(
                'name' => $name,
                'email' => $email,
                'department' => $department,
            ),
            array( 'id' => $id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( 'Staff member updated' );
        } else {
            wp_send_json_error( 'Failed to update staff member' );
        }
    }

    public function delete_staff() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $id = intval( $_POST['id'] ?? 0 );

        if ( ! $id ) {
            wp_send_json_error( 'Invalid staff ID' );
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'leave_manager_users',
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( $result ) {
            wp_send_json_success( 'Staff member deleted' );
        } else {
            wp_send_json_error( 'Failed to delete staff member' );
        }
    }

    // ============================================
    // REQUEST FUNCTIONS
    // ============================================

    public function create_request() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $type = sanitize_text_field( $_POST['type'] ?? '' );
        $start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
        $end_date = sanitize_text_field( $_POST['end_date'] ?? '' );
        $reason = sanitize_textarea_field( $_POST['reason'] ?? '' );

        if ( ! $type || ! $start_date || ! $end_date ) {
            wp_send_json_error( 'Missing required fields' );
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'leave_manager_leave_requests',
            array(
                'user_id' => get_current_user_id(),
                'leave_type' => $type,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'reason' => $reason,
                'status' => 'pending',
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $result ) {
            wp_send_json_success( 'Request created' );
        } else {
            wp_send_json_error( 'Failed to create request' );
        }
    }

    public function approve_request() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $id = intval( $_POST['id'] ?? 0 );

        if ( ! $id ) {
            wp_send_json_error( 'Invalid request ID' );
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'leave_manager_leave_requests',
            array( 'status' => 'approved' ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( 'Request approved' );
        } else {
            wp_send_json_error( 'Failed to approve request' );
        }
    }

    public function reject_request() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $id = intval( $_POST['id'] ?? 0 );
        $reason = sanitize_textarea_field( $_POST['reason'] ?? '' );

        if ( ! $id ) {
            wp_send_json_error( 'Invalid request ID' );
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'leave_manager_leave_requests',
            array( 'status' => 'rejected', 'rejection_reason' => $reason ),
            array( 'id' => $id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( 'Request rejected' );
        } else {
            wp_send_json_error( 'Failed to reject request' );
        }
    }

    public function delete_request() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $id = intval( $_POST['id'] ?? 0 );

        if ( ! $id ) {
            wp_send_json_error( 'Invalid request ID' );
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'leave_manager_leave_requests',
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( $result ) {
            wp_send_json_success( 'Request deleted' );
        } else {
            wp_send_json_error( 'Failed to delete request' );
        }
    }

    // ============================================
    // REPORT FUNCTIONS
    // ============================================

    public function generate_leave_report() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
        $end_date = sanitize_text_field( $_POST['end_date'] ?? '' );

        $query = "SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests WHERE 1=1";

        if ( $start_date ) {
            $query .= $wpdb->prepare( " AND start_date >= %s", $start_date );
        }

        if ( $end_date ) {
            $query .= $wpdb->prepare( " AND end_date <= %s", $end_date );
        }

        $results = $wpdb->get_results( $query );

        wp_send_json_success( $results );
    }

    public function export_leave_report() {
        $this->verify_nonce();
        $this->check_permission();

        $results = $this->get_leave_report_data();
        $csv = $this->array_to_csv( $results );

        wp_send_json_success( $csv );
    }

    public function generate_user_report() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $user_id = intval( $_POST['user_id'] ?? 0 );

        if ( ! $user_id ) {
            wp_send_json_error( 'Invalid user ID' );
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests WHERE user_id = %d",
                $user_id
            )
        );

        wp_send_json_success( $results );
    }

    public function export_user_report() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $user_id = intval( $_POST['user_id'] ?? 0 );

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests WHERE user_id = %d",
                $user_id
            )
        );

        $csv = $this->array_to_csv( $results );

        wp_send_json_success( $csv );
    }

    public function generate_department_report() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $department_id = intval( $_POST['department_id'] ?? 0 );

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT lr.* FROM {$wpdb->prefix}leave_manager_leave_requests lr 
                 JOIN {$wpdb->prefix}leave_manager_users u ON lr.user_id = u.id 
                 WHERE u.department = %d",
                $department_id
            )
        );

        wp_send_json_success( $results );
    }

    public function export_department_report() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $department_id = intval( $_POST['department_id'] ?? 0 );

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT lr.* FROM {$wpdb->prefix}leave_manager_leave_requests lr 
                 JOIN {$wpdb->prefix}leave_manager_users u ON lr.user_id = u.id 
                 WHERE u.department = %d",
                $department_id
            )
        );

        $csv = $this->array_to_csv( $results );

        wp_send_json_success( $csv );
    }

    // ============================================
    // TEMPLATE FUNCTIONS
    // ============================================

    public function preview_template() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $id = intval( $_POST['id'] ?? 0 );

        $template = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}leave_manager_settings WHERE id = %d",
                $id
            )
        );

        if ( $template ) {
            wp_send_json_success( $template->value );
        } else {
            wp_send_json_error( 'Template not found' );
        }
    }

    public function edit_template() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $id = intval( $_POST['id'] ?? 0 );
        $content = wp_kses_post( $_POST['content'] ?? '' );

        if ( ! $id ) {
            wp_send_json_error( 'Invalid template ID' );
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'leave_manager_settings',
            array( 'value' => $content ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( 'Template updated' );
        } else {
            wp_send_json_error( 'Failed to update template' );
        }
    }

    public function delete_template() {
        $this->verify_nonce();
        $this->check_permission();

        global $wpdb;

        $id = intval( $_POST['id'] ?? 0 );

        if ( ! $id ) {
            wp_send_json_error( 'Invalid template ID' );
        }

        $result = $wpdb->delete(
            $wpdb->prefix . 'leave_manager_settings',
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( $result ) {
            wp_send_json_success( 'Template deleted' );
        } else {
            wp_send_json_error( 'Failed to delete template' );
        }
    }

    // ============================================
    // SETTINGS FUNCTIONS
    // ============================================

    public function save_general_settings() {
        $this->verify_nonce();
        $this->check_permission();

        $site_name = sanitize_text_field( $_POST['site_name'] ?? '' );
        $site_email = sanitize_email( $_POST['site_email'] ?? '' );

        update_option( 'leave_manager_site_name', $site_name );
        update_option( 'leave_manager_site_email', $site_email );

        wp_send_json_success( 'Settings saved' );
    }

    public function save_email_settings() {
        $this->verify_nonce();
        $this->check_permission();

        $smtp_host = sanitize_text_field( $_POST['smtp_host'] ?? '' );
        $smtp_port = intval( $_POST['smtp_port'] ?? 587 );
        $smtp_user = sanitize_text_field( $_POST['smtp_user'] ?? '' );
        $smtp_pass = sanitize_text_field( $_POST['smtp_pass'] ?? '' );

        update_option( 'leave_manager_smtp_host', $smtp_host );
        update_option( 'leave_manager_smtp_port', $smtp_port );
        update_option( 'leave_manager_smtp_user', $smtp_user );
        update_option( 'leave_manager_smtp_pass', $smtp_pass );

        wp_send_json_success( 'Email settings saved' );
    }

    public function save_leave_policies() {
        $this->verify_nonce();
        $this->check_permission();

        $annual_days = intval( $_POST['annual_days'] ?? 20 );
        $sick_days = intval( $_POST['sick_days'] ?? 10 );
        $casual_days = intval( $_POST['casual_days'] ?? 5 );

        update_option( 'leave_manager_annual_days', $annual_days );
        update_option( 'leave_manager_sick_days', $sick_days );
        update_option( 'leave_manager_casual_days', $casual_days );

        wp_send_json_success( 'Leave policies saved' );
    }

    public function save_notification_settings() {
        $this->verify_nonce();
        $this->check_permission();

        $notify_approver = isset( $_POST['notify_approver'] ) ? 1 : 0;
        $notify_employee = isset( $_POST['notify_employee'] ) ? 1 : 0;

        update_option( 'leave_manager_notify_approver', $notify_approver );
        update_option( 'leave_manager_notify_employee', $notify_employee );

        wp_send_json_success( 'Notification settings saved' );
    }

    public function save_appearance_settings() {
        $this->verify_nonce();
        $this->check_permission();

        $primary_color = sanitize_hex_color( $_POST['primary_color'] ?? '#0073aa' );
        $logo_url = esc_url( $_POST['logo_url'] ?? '' );

        update_option( 'leave_manager_primary_color', $primary_color );
        update_option( 'leave_manager_logo_url', $logo_url );

        wp_send_json_success( 'Appearance settings saved' );
    }

    public function save_integration_settings() {
        $this->verify_nonce();
        $this->check_permission();

        $api_key = sanitize_text_field( $_POST['api_key'] ?? '' );
        $api_url = esc_url( $_POST['api_url'] ?? '' );

        update_option( 'leave_manager_api_key', $api_key );
        update_option( 'leave_manager_api_url', $api_url );

        wp_send_json_success( 'Integration settings saved' );
    }

    public function test_email_settings() {
        $this->verify_nonce();
        $this->check_permission();

        $admin_email = get_option( 'admin_email' );

        $result = wp_mail(
            $admin_email,
            'Leave Manager - Email Test',
            'This is a test email from Leave Manager plugin.'
        );

        if ( $result ) {
            wp_send_json_success( 'Test email sent' );
        } else {
            wp_send_json_error( 'Failed to send test email' );
        }
    }

    // ============================================
    // HELPER FUNCTIONS
    // ============================================

    private function get_leave_report_data() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests"
        );
    }

    private function array_to_csv( $array ) {
        if ( empty( $array ) ) {
            return '';
        }

        $csv = '';

        // Add headers
        $headers = array_keys( (array) $array[0] );
        $csv .= implode( ',', $headers ) . "\n";

        // Add rows
        foreach ( $array as $row ) {
            $values = array();
            foreach ( (array) $row as $value ) {
                $values[] = '"' . str_replace( '"', '""', $value ) . '"';
            }
            $csv .= implode( ',', $values ) . "\n";
        }

        return $csv;
    }
}

// Initialize
new Leave_Manager_Complete_AJAX_Handler();
