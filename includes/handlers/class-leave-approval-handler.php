<?php
/**
 * Leave Request Approval AJAX Handler
 *
 * Handles leave request approval and rejection
 * Uses custom database tables and custom authentication
 *
 * @package Leave_Manager
 * @version 2.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Leave_Manager_Leave_Approval_Handler class
 */
class Leave_Manager_Leave_Approval_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX actions for WordPress logged-in users
        add_action( 'wp_ajax_leave_manager_approve_leave', array( $this, 'approve_leave' ) );
        add_action( 'wp_ajax_leave_manager_reject_leave', array( $this, 'reject_leave' ) );
        add_action( 'wp_ajax_leave_manager_get_pending_approvals', array( $this, 'get_pending_approvals' ) );
        
        // Register AJAX actions for non-logged-in users (custom auth)
        add_action( 'wp_ajax_nopriv_leave_manager_approve_leave', array( $this, 'approve_leave' ) );
        add_action( 'wp_ajax_nopriv_leave_manager_reject_leave', array( $this, 'reject_leave' ) );
        add_action( 'wp_ajax_nopriv_leave_manager_get_pending_approvals', array( $this, 'get_pending_approvals' ) );
    }

    /**
     * Verify custom authentication and check admin/manager role
     * 
     * @return object|false User object or false if not authorized
     */
    private function verify_admin_auth() {
        $custom_auth = new Leave_Manager_Custom_Auth();
        
        if ( ! $custom_auth->is_logged_in() ) {
            return false;
        }
        
        $user = $custom_auth->get_current_user();
        
        // Check if user has admin or manager role
        if ( ! in_array( $user->role, array( 'admin', 'manager', 'hr' ), true ) ) {
            return false;
        }
        
        return $user;
    }

    /**
     * Approve leave request
     */
    public function approve_leave() {
        // Verify nonce
        $nonce_valid = false;
        if ( isset( $_POST['_wpnonce'] ) ) {
            $nonce_valid = wp_verify_nonce( $_POST['_wpnonce'], 'leave_manager_nonce' ) ||
                          wp_verify_nonce( $_POST['_wpnonce'], 'leave_manager_admin_nonce' );
        }
        if ( isset( $_POST['nonce'] ) && ! $nonce_valid ) {
            $nonce_valid = wp_verify_nonce( $_POST['nonce'], 'leave_manager_nonce' ) ||
                          wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' );
        }
        
        if ( ! $nonce_valid ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        // Check permissions
        $current_user = $this->verify_admin_auth();
        if ( ! $current_user ) {
            wp_send_json_error( array( 'message' => 'You do not have permission to approve leave requests' ) );
        }

        // Get request ID
        $request_id = isset( $_POST['request_id'] ) ? intval( $_POST['request_id'] ) : 0;
        if ( ! $request_id ) {
            wp_send_json_error( array( 'message' => 'Invalid request ID' ) );
        }

        global $wpdb;
        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';

        // Get the leave request - using correct column name 'request_id'
        $request = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$requests_table} WHERE request_id = %d", $request_id )
        );

        if ( ! $request ) {
            wp_send_json_error( array( 'message' => 'Leave request not found' ) );
        }

        if ( $request->status !== 'pending' ) {
            wp_send_json_error( array( 'message' => 'This request has already been processed' ) );
        }

        // Calculate days if not stored
        $days = $this->calculate_working_days( $request->start_date, $request->end_date );

        // Update request status - using correct column name 'request_id'
        $result = $wpdb->update(
            $requests_table,
            array(
                'status'        => 'approved',
                'approved_by'   => $current_user->user_id,
                'approval_date' => current_time( 'mysql' ),
                'updated_at'    => current_time( 'mysql' ),
            ),
            array( 'request_id' => $request_id ),
            array( '%s', '%d', '%s', '%s' ),
            array( '%d' )
        );

        if ( $result === false ) {
            wp_send_json_error( array( 'message' => 'Failed to approve request' ) );
        }

        // Deduct from leave balance
        $this->deduct_leave_balance( $request->user_id, $request->leave_type, $days );

        // Get user details for email - using correct column name 'user_id'
        $user = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$users_table} WHERE user_id = %d", $request->user_id )
        );

        // Send approval notification email
        if ( $user ) {
            $this->send_approval_email( $user, $request, $current_user, $days );
        }

        wp_send_json_success( array(
            'message'    => 'Leave request approved successfully',
            'request_id' => $request_id,
        ) );
    }

    /**
     * Reject leave request
     */
    public function reject_leave() {
        // Verify nonce
        $nonce_valid = false;
        if ( isset( $_POST['_wpnonce'] ) ) {
            $nonce_valid = wp_verify_nonce( $_POST['_wpnonce'], 'leave_manager_nonce' ) ||
                          wp_verify_nonce( $_POST['_wpnonce'], 'leave_manager_admin_nonce' );
        }
        if ( isset( $_POST['nonce'] ) && ! $nonce_valid ) {
            $nonce_valid = wp_verify_nonce( $_POST['nonce'], 'leave_manager_nonce' ) ||
                          wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' );
        }
        
        if ( ! $nonce_valid ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        // Check permissions
        $current_user = $this->verify_admin_auth();
        if ( ! $current_user ) {
            wp_send_json_error( array( 'message' => 'You do not have permission to reject leave requests' ) );
        }

        // Get request ID and reason
        $request_id = isset( $_POST['request_id'] ) ? intval( $_POST['request_id'] ) : 0;
        $reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( $_POST['reason'] ) : '';
        
        if ( ! $request_id ) {
            wp_send_json_error( array( 'message' => 'Invalid request ID' ) );
        }

        global $wpdb;
        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';

        // Get the leave request - using correct column name 'request_id'
        $request = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$requests_table} WHERE request_id = %d", $request_id )
        );

        if ( ! $request ) {
            wp_send_json_error( array( 'message' => 'Leave request not found' ) );
        }

        if ( $request->status !== 'pending' ) {
            wp_send_json_error( array( 'message' => 'This request has already been processed' ) );
        }

        // Update request status - using correct column name 'request_id'
        $result = $wpdb->update(
            $requests_table,
            array(
                'status'           => 'rejected',
                'rejection_reason' => $reason,
                'updated_at'       => current_time( 'mysql' ),
            ),
            array( 'request_id' => $request_id ),
            array( '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( $result === false ) {
            wp_send_json_error( array( 'message' => 'Failed to reject request' ) );
        }

        // Get user details for email - using correct column name 'user_id'
        $user = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$users_table} WHERE user_id = %d", $request->user_id )
        );

        // Send rejection notification email
        if ( $user ) {
            $this->send_rejection_email( $user, $request, $current_user, $reason );
        }

        wp_send_json_success( array(
            'message'    => 'Leave request rejected',
            'request_id' => $request_id,
        ) );
    }

    /**
     * Get pending approvals
     */
    public function get_pending_approvals() {
        // Verify nonce
        $nonce_valid = false;
        $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : ( isset( $_POST['nonce'] ) ? $_POST['nonce'] : '' );
        
        if ( $nonce ) {
            $nonce_valid = wp_verify_nonce( $nonce, 'leave_manager_nonce' ) ||
                          wp_verify_nonce( $nonce, 'leave_manager_admin_nonce' );
        }
        
        if ( ! $nonce_valid ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        // Check permissions
        $current_user = $this->verify_admin_auth();
        if ( ! $current_user ) {
            wp_send_json_error( array( 'message' => 'You do not have permission to view approvals' ) );
        }

        global $wpdb;
        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';

        // Get pending requests with user details - using correct column names
        $results = $wpdb->get_results(
            "SELECT r.*, u.first_name, u.last_name, u.email, u.department
             FROM {$requests_table} r
             JOIN {$users_table} u ON r.user_id = u.user_id
             WHERE r.status = 'pending'
             ORDER BY r.created_at DESC"
        );

        $data = array();
        foreach ( $results as $request ) {
            // Calculate days
            $days = $this->calculate_working_days( $request->start_date, $request->end_date );
            
            $data[] = array(
                'request_id'     => $request->request_id,
                'user_id'        => $request->user_id,
                'user_name'      => $request->first_name . ' ' . $request->last_name,
                'user_email'     => $request->email,
                'department'     => $request->department ?: 'Unassigned',
                'leave_type'     => $request->leave_type,
                'start_date'     => $request->start_date,
                'end_date'       => $request->end_date,
                'days'           => $days,
                'reason'         => $request->reason,
                'submitted_date' => $request->created_at,
                'status'         => $request->status,
            );
        }

        wp_send_json_success( $data );
    }

    /**
     * Calculate working days between two dates
     *
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return int Number of working days
     */
    private function calculate_working_days( $start_date, $end_date ) {
        $start = new DateTime( $start_date );
        $end = new DateTime( $end_date );
        $end->modify( '+1 day' ); // Include end date
        
        $interval = new DateInterval( 'P1D' );
        $period = new DatePeriod( $start, $interval, $end );
        
        $working_days = 0;
        foreach ( $period as $day ) {
            // Skip weekends (Saturday = 6, Sunday = 0)
            $day_of_week = $day->format( 'w' );
            if ( $day_of_week != 0 && $day_of_week != 6 ) {
                $working_days++;
            }
        }
        
        return max( 1, $working_days ); // At least 1 day
    }

    /**
     * Deduct leave balance
     *
     * @param int $user_id User ID
     * @param string $leave_type Leave type
     * @param int $days Number of days to deduct
     * @return bool Success
     */
    private function deduct_leave_balance( $user_id, $leave_type, $days ) {
        global $wpdb;
        $balances_table = $wpdb->prefix . 'leave_manager_leave_balances';
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';
        $current_year = date( 'Y' );

        // Check if balance record exists for this year
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$balances_table} WHERE user_id = %d AND leave_type = %s AND year = %d",
                $user_id,
                $leave_type,
                $current_year
            )
        );

        if ( $existing ) {
            // Update existing balance - add to used column
            $result = $wpdb->update(
                $balances_table,
                array(
                    'used' => floatval( $existing->used ) + $days,
                ),
                array(
                    'balance_id' => $existing->balance_id,
                ),
                array( '%f' ),
                array( '%d' )
            );
        } else {
            // Create new balance record with default values
            $default_balance = $this->get_default_balance( $leave_type );
            $result = $wpdb->insert(
                $balances_table,
                array(
                    'user_id'      => $user_id,
                    'leave_type'   => $leave_type,
                    'year'         => $current_year,
                    'allocated'    => $default_balance,
                    'used'         => $days,
                    'carried_over' => 0,
                    'adjustment'   => 0,
                ),
                array( '%d', '%s', '%d', '%f', '%f', '%f', '%f' )
            );
        }

        // Also update the user's balance in the leave_users table
        $balance_column = $this->get_balance_column( $leave_type );
        if ( $balance_column ) {
            $current_balance = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT {$balance_column} FROM {$users_table} WHERE user_id = %d",
                    $user_id
                )
            );
            
            if ( $current_balance !== null ) {
                $new_balance = max( 0, floatval( $current_balance ) - $days );
                $wpdb->update(
                    $users_table,
                    array( $balance_column => $new_balance ),
                    array( 'user_id' => $user_id ),
                    array( '%f' ),
                    array( '%d' )
                );
            }
        }

        return $result !== false;
    }

    /**
     * Get balance column name for leave type
     *
     * @param string $leave_type Leave type
     * @return string|null Column name or null
     */
    private function get_balance_column( $leave_type ) {
        $columns = array(
            'annual' => 'annual_leave_balance',
            'sick'   => 'sick_leave_balance',
            'study'  => 'other_leave_balance',
            'other'  => 'other_leave_balance',
        );
        
        return isset( $columns[ $leave_type ] ) ? $columns[ $leave_type ] : null;
    }

    /**
     * Get default balance for leave type
     *
     * @param string $leave_type Leave type
     * @return int Default balance
     */
    private function get_default_balance( $leave_type ) {
        $defaults = array(
            'annual' => 20,
            'sick'   => 10,
            'study'  => 5,
            'other'  => 5,
        );
        
        return isset( $defaults[ $leave_type ] ) ? $defaults[ $leave_type ] : 5;
    }

    /**
     * Send approval notification email
     *
     * @param object $user User object
     * @param object $request Request object
     * @param object $approver Approver user object
     * @param int $days Number of days
     */
    private function send_approval_email( $user, $request, $approver, $days ) {
        $subject = 'Your Leave Request Has Been Approved';
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h2 style='color: #4CAF50;'>Leave Request Approved</h2>
            <p>Dear {$user->first_name},</p>
            <p>Great news! Your leave request has been approved.</p>
            
            <table style='border-collapse: collapse; width: 100%; max-width: 500px;'>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Leave Type:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>" . ucfirst( $request->leave_type ) . "</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Start Date:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$request->start_date}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>End Date:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$request->end_date}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Days:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$days}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Status:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'><span style='color: #4CAF50; font-weight: bold;'>Approved</span></td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Approved By:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$approver->first_name} {$approver->last_name}</td>
                </tr>
            </table>
            
            <p style='margin-top: 20px;'>Your leave balance has been updated accordingly. You can view your updated balance in the dashboard.</p>
            
            <p style='color: #666; font-size: 12px; margin-top: 30px;'>
                This is an automated message from the Leave Management System.
            </p>
        </body>
        </html>
        ";
        
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $user->email, $subject, $message, $headers );
    }

    /**
     * Send rejection notification email
     *
     * @param object $user User object
     * @param object $request Request object
     * @param object $rejector Rejector user object
     * @param string $reason Rejection reason
     */
    private function send_rejection_email( $user, $request, $rejector, $reason = '' ) {
        $subject = 'Your Leave Request Has Been Rejected';
        
        $reason_html = '';
        if ( ! empty( $reason ) ) {
            $reason_html = "
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Reason:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>" . esc_html( $reason ) . "</td>
                </tr>
            ";
        }
        
        // Calculate days
        $days = $this->calculate_working_days( $request->start_date, $request->end_date );
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h2 style='color: #f44336;'>Leave Request Rejected</h2>
            <p>Dear {$user->first_name},</p>
            <p>Unfortunately, your leave request has been rejected.</p>
            
            <table style='border-collapse: collapse; width: 100%; max-width: 500px;'>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Leave Type:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>" . ucfirst( $request->leave_type ) . "</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Start Date:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$request->start_date}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>End Date:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$request->end_date}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Days:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$days}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Status:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'><span style='color: #f44336; font-weight: bold;'>Rejected</span></td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Rejected By:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$rejector->first_name} {$rejector->last_name}</td>
                </tr>
                {$reason_html}
            </table>
            
            <p style='margin-top: 20px;'>If you have any questions, please contact your manager or HR department.</p>
            
            <p style='color: #666; font-size: 12px; margin-top: 30px;'>
                This is an automated message from the Leave Management System.
            </p>
        </body>
        </html>
        ";
        
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $user->email, $subject, $message, $headers );
    }
}

// Instantiate the handler
new Leave_Manager_Leave_Approval_Handler();
