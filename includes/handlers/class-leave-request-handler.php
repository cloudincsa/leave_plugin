<?php
/**
 * Leave Request AJAX Handler
 *
 * Handles leave request submission, retrieval, and cancellation
 * Uses custom database tables and custom authentication
 *
 * @package Leave_Manager
 * @version 2.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Leave_Manager_Leave_Request_Handler class
 */
class Leave_Manager_Leave_Request_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX actions for logged-in WordPress users
        add_action( 'wp_ajax_leave_manager_submit_leave_request', array( $this, 'submit_leave_request' ) );
        add_action( 'wp_ajax_leave_manager_get_leave_requests', array( $this, 'get_leave_requests' ) );
        add_action( 'wp_ajax_leave_manager_get_leave_balance', array( $this, 'get_leave_balance' ) );
        add_action( 'wp_ajax_leave_manager_cancel_leave_request', array( $this, 'cancel_leave_request' ) );
        
        // Register AJAX actions for non-logged-in users (custom auth)
        add_action( 'wp_ajax_nopriv_leave_manager_submit_leave_request', array( $this, 'submit_leave_request' ) );
        add_action( 'wp_ajax_nopriv_leave_manager_get_leave_requests', array( $this, 'get_leave_requests' ) );
        add_action( 'wp_ajax_nopriv_leave_manager_get_leave_balance', array( $this, 'get_leave_balance' ) );
        add_action( 'wp_ajax_nopriv_leave_manager_cancel_leave_request', array( $this, 'cancel_leave_request' ) );
    }

    /**
     * Verify custom authentication
     * 
     * @return object|false User object or false if not authenticated
     */
    private function verify_custom_auth() {
        $custom_auth = new Leave_Manager_Custom_Auth();
        
        if ( ! $custom_auth->is_logged_in() ) {
            return false;
        }
        
        return $custom_auth->get_current_user();
    }

    /**
     * Submit leave request
     */
    public function submit_leave_request() {
        // Verify nonce - accept multiple nonce names for compatibility
        $nonce_valid = false;
        if ( isset( $_POST['_wpnonce'] ) ) {
            $nonce_valid = wp_verify_nonce( $_POST['_wpnonce'], 'leave_manager_nonce' ) ||
                          wp_verify_nonce( $_POST['_wpnonce'], 'leave_manager_leave_request_nonce' );
        }
        if ( isset( $_POST['nonce'] ) && ! $nonce_valid ) {
            $nonce_valid = wp_verify_nonce( $_POST['nonce'], 'leave_manager_nonce' ) ||
                          wp_verify_nonce( $_POST['nonce'], 'leave_manager_leave_request_nonce' );
        }
        
        if ( ! $nonce_valid ) {
            wp_send_json_error( array( 'message' => 'Security check failed. Please refresh the page and try again.' ) );
        }

        // Verify custom authentication
        $current_user = $this->verify_custom_auth();
        if ( ! $current_user ) {
            wp_send_json_error( array( 'message' => 'You must be logged in to submit a leave request.' ) );
        }

        // Get and validate form data
        $start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( $_POST['start_date'] ) : '';
        $end_date = isset( $_POST['end_date'] ) ? sanitize_text_field( $_POST['end_date'] ) : '';
        $leave_type = isset( $_POST['leave_type'] ) ? sanitize_text_field( $_POST['leave_type'] ) : '';
        $reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( $_POST['reason'] ) : '';

        // Validate required fields
        if ( empty( $start_date ) || empty( $end_date ) || empty( $leave_type ) ) {
            wp_send_json_error( array( 'message' => 'Please fill in all required fields (Start Date, End Date, Leave Type).' ) );
        }

        // Validate dates
        $start = strtotime( $start_date );
        $end = strtotime( $end_date );
        
        if ( ! $start || ! $end ) {
            wp_send_json_error( array( 'message' => 'Invalid date format. Please use YYYY-MM-DD format.' ) );
        }
        
        if ( $start > $end ) {
            wp_send_json_error( array( 'message' => 'End date must be after or equal to start date.' ) );
        }
        
        // Check if start date is in the past
        $today = strtotime( date( 'Y-m-d' ) );
        if ( $start < $today ) {
            wp_send_json_error( array( 'message' => 'Start date cannot be in the past.' ) );
        }

        // Calculate number of days
        $days_requested = $this->calculate_working_days( $start_date, $end_date );

        // Check leave balance
        $balance = $this->get_user_leave_balance( $current_user->user_id, $leave_type );
        if ( $balance !== null && $days_requested > $balance ) {
            wp_send_json_error( array( 
                'message' => "Insufficient leave balance. You have {$balance} days available but requested {$days_requested} days." 
            ) );
        }

        // Check for overlapping requests
        if ( $this->has_overlapping_request( $current_user->user_id, $start_date, $end_date ) ) {
            wp_send_json_error( array( 'message' => 'You already have a leave request for this date range.' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        // Insert leave request
        $result = $wpdb->insert(
            $table,
            array(
                'user_id'      => $current_user->user_id,
                'leave_type'   => $leave_type,
                'start_date'   => $start_date,
                'end_date'     => $end_date,
                'days'         => $days_requested,
                'reason'       => $reason,
                'status'       => 'pending',
                'created_at'   => current_time( 'mysql' ),
                'updated_at'   => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
        );

        if ( ! $result ) {
            wp_send_json_error( array( 'message' => 'Failed to submit leave request. Please try again.' ) );
        }

        $request_id = $wpdb->insert_id;

        // Send email notification
        $this->send_leave_request_email( $request_id, $current_user );

        wp_send_json_success( array(
            'message'    => 'Leave request submitted successfully! You will be notified once it is reviewed.',
            'request_id' => $request_id,
            'days'       => $days_requested,
        ) );
    }

    /**
     * Get leave requests for current user
     */
    public function get_leave_requests() {
        // Verify nonce
        $nonce_valid = false;
        $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : ( isset( $_POST['nonce'] ) ? $_POST['nonce'] : '' );
        
        if ( $nonce ) {
            $nonce_valid = wp_verify_nonce( $nonce, 'leave_manager_nonce' );
        }
        
        if ( ! $nonce_valid ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        // Verify custom authentication
        $current_user = $this->verify_custom_auth();
        if ( ! $current_user ) {
            wp_send_json_error( array( 'message' => 'You must be logged in' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC",
                $current_user->user_id
            )
        );

        $data = array();
        foreach ( $results as $request ) {
            $data[] = array(
                'request_id' => $request->request_id,
                'leave_type' => $request->leave_type,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
                'days'       => $request->days,
                'status'     => $request->status,
                'reason'     => $request->reason,
                'created_at' => $request->created_at,
            );
        }

        wp_send_json_success( $data );
    }

    /**
     * Get leave balance for current user
     */
    public function get_leave_balance() {
        // Verify nonce
        $nonce_valid = false;
        $nonce = isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : ( isset( $_POST['nonce'] ) ? $_POST['nonce'] : '' );
        
        if ( $nonce ) {
            $nonce_valid = wp_verify_nonce( $nonce, 'leave_manager_nonce' );
        }
        
        if ( ! $nonce_valid ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        // Verify custom authentication
        $current_user = $this->verify_custom_auth();
        if ( ! $current_user ) {
            wp_send_json_error( array( 'message' => 'You must be logged in' ) );
        }

        global $wpdb;
        $balance_table = $wpdb->prefix . 'leave_manager_leave_balances';
        $current_year = date( 'Y' );

        $balances = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT leave_type, allocated, used, carried_over, adjustment FROM {$balance_table} WHERE user_id = %d AND year = %d",
                $current_user->user_id,
                $current_year
            )
        );

        $data = array(
            'annual' => array( 'allocated' => 20, 'used' => 0, 'remaining' => 20 ),
            'sick'   => array( 'allocated' => 10, 'used' => 0, 'remaining' => 10 ),
            'other'  => array( 'allocated' => 5, 'used' => 0, 'remaining' => 5 ),
        );

        foreach ( $balances as $balance ) {
            $type = strtolower( $balance->leave_type );
            if ( isset( $data[ $type ] ) ) {
                $total_available = floatval( $balance->allocated ) + floatval( $balance->carried_over ) + floatval( $balance->adjustment );
                $used = floatval( $balance->used );
                $data[ $type ] = array(
                    'allocated' => $total_available,
                    'used'      => $used,
                    'remaining' => $total_available - $used,
                );
            }
        }

        // Calculate totals
        $total_allocated = 0;
        $total_used = 0;
        foreach ( $data as $type => $values ) {
            $total_allocated += $values['allocated'];
            $total_used += $values['used'];
        }

        wp_send_json_success( array(
            'balances' => $data,
            'total'    => array(
                'allocated' => $total_allocated,
                'used'      => $total_used,
                'remaining' => $total_allocated - $total_used,
            ),
        ) );
    }

    /**
     * Cancel leave request
     */
    public function cancel_leave_request() {
        // Verify nonce
        $nonce_valid = false;
        if ( isset( $_POST['nonce'] ) ) {
            $nonce_valid = wp_verify_nonce( $_POST['nonce'], 'leave_manager_nonce' );
        }
        
        if ( ! $nonce_valid ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }

        // Verify custom authentication
        $current_user = $this->verify_custom_auth();
        if ( ! $current_user ) {
            wp_send_json_error( array( 'message' => 'You must be logged in' ) );
        }

        $request_id = isset( $_POST['request_id'] ) ? intval( $_POST['request_id'] ) : 0;
        if ( ! $request_id ) {
            wp_send_json_error( array( 'message' => 'Invalid request ID' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        // Verify ownership and status
        $request = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE request_id = %d AND user_id = %d",
                $request_id,
                $current_user->user_id
            )
        );

        if ( ! $request ) {
            wp_send_json_error( array( 'message' => 'Leave request not found' ) );
        }

        if ( $request->status !== 'pending' ) {
            wp_send_json_error( array( 'message' => 'Only pending requests can be cancelled' ) );
        }

        // Update status to cancelled
        $result = $wpdb->update(
            $table,
            array( 
                'status'     => 'cancelled',
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'request_id' => $request_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( $result === false ) {
            wp_send_json_error( array( 'message' => 'Failed to cancel request' ) );
        }

        wp_send_json_success( array( 'message' => 'Leave request cancelled successfully' ) );
    }

    /**
     * Calculate working days between two dates (excluding weekends)
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
        foreach ( $period as $date ) {
            $day_of_week = $date->format( 'N' );
            // Skip weekends (6 = Saturday, 7 = Sunday)
            if ( $day_of_week < 6 ) {
                $working_days++;
            }
        }
        
        return $working_days;
    }

    /**
     * Get user's leave balance for a specific type
     *
     * @param int $user_id User ID
     * @param string $leave_type Leave type
     * @return int|null Balance or null if not found
     */
    private function get_user_leave_balance( $user_id, $leave_type ) {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_balances';
        $current_year = date( 'Y' );
        
        $balance = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT (allocated + carried_over + adjustment - used) as remaining FROM {$table} WHERE user_id = %d AND leave_type = %s AND year = %d",
                $user_id,
                $leave_type,
                $current_year
            )
        );
        
        return $balance !== null ? intval( $balance ) : null;
    }

    /**
     * Check if user has overlapping leave request
     *
     * @param int $user_id User ID
     * @param string $start_date Start date
     * @param string $end_date End date
     * @return bool True if overlapping request exists
     */
    private function has_overlapping_request( $user_id, $start_date, $end_date ) {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} 
                WHERE user_id = %d 
                AND status IN ('pending', 'approved')
                AND ((start_date <= %s AND end_date >= %s) 
                     OR (start_date <= %s AND end_date >= %s)
                     OR (start_date >= %s AND end_date <= %s))",
                $user_id,
                $end_date,
                $start_date,
                $start_date,
                $start_date,
                $start_date,
                $end_date
            )
        );
        
        return $count > 0;
    }

    /**
     * Send leave request email notification
     *
     * @param int $request_id Request ID
     * @param object $user User object
     */
    private function send_leave_request_email( $request_id, $user ) {
        global $wpdb;
        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';
        
        // Get request details
        $request = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$requests_table} WHERE request_id = %d", $request_id )
        );
        
        if ( ! $request ) {
            return;
        }
        
        // Prepare email content
        $subject = 'Leave Request Submitted - ' . ucfirst( $request->leave_type ) . ' Leave';
        
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
            <h2 style='color: #333;'>Leave Request Submitted</h2>
            <p>Dear {$user->first_name},</p>
            <p>Your leave request has been submitted successfully and is pending approval.</p>
            
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
                    <td style='padding: 10px; border: 1px solid #ddd;'>{$request->days}</td>
                </tr>
                <tr>
                    <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Status:</strong></td>
                    <td style='padding: 10px; border: 1px solid #ddd;'><span style='color: #f57c00;'>Pending</span></td>
                </tr>
            </table>
            
            <p style='margin-top: 20px;'>You will receive another email once your request has been reviewed.</p>
            
            <p style='color: #666; font-size: 12px; margin-top: 30px;'>
                This is an automated message from the Leave Management System.
            </p>
        </body>
        </html>
        ";
        
        // Send email to user
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $user->email, $subject, $message, $headers );
        
        // Send notification to HR/Admin
        $admin_users = $wpdb->get_results(
            "SELECT email, first_name FROM {$users_table} WHERE role IN ('admin', 'hr') AND status = 'active'"
        );
        
        if ( $admin_users ) {
            $admin_subject = 'New Leave Request - ' . $user->first_name . ' ' . $user->last_name;
            $admin_message = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6;'>
                <h2 style='color: #333;'>New Leave Request</h2>
                <p>A new leave request has been submitted and requires your review.</p>
                
                <table style='border-collapse: collapse; width: 100%; max-width: 500px;'>
                    <tr>
                        <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Employee:</strong></td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>{$user->first_name} {$user->last_name}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Leave Type:</strong></td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>" . ucfirst( $request->leave_type ) . "</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Dates:</strong></td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>{$request->start_date} to {$request->end_date}</td>
                    </tr>
                    <tr>
                        <td style='padding: 10px; border: 1px solid #ddd; background: #f9f9f9;'><strong>Days:</strong></td>
                        <td style='padding: 10px; border: 1px solid #ddd;'>{$request->days}</td>
                    </tr>
                </table>
                
                <p style='margin-top: 20px;'>Please log in to the Leave Management System to review this request.</p>
                
                <p style='color: #666; font-size: 12px; margin-top: 30px;'>
                    This is an automated message from the Leave Management System.
                </p>
            </body>
            </html>
            ";
            
            foreach ( $admin_users as $admin ) {
                wp_mail( $admin->email, $admin_subject, $admin_message, $headers );
            }
        }
    }

    /**
     * Get leave request by ID (public method for other handlers)
     *
     * @param int $request_id Request ID
     * @return object|null Request object or null
     */
    public function get_leave_request( $request_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';
        
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE request_id = %d", $request_id )
        );
    }

    /**
     * Get user leave requests (public method for other handlers)
     *
     * @param int $user_id User ID
     * @return array Array of request objects
     */
    public function get_user_leave_requests( $user_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';
        
        $results = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC", $user_id )
        );
        
        return $results ?: array();
    }
}

// Instantiate the handler
new Leave_Manager_Leave_Request_Handler();
