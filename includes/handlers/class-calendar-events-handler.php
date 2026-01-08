<?php
/**
 * Calendar Events Handler
 * 
 * Handles AJAX requests for calendar event data
 * Uses custom authentication system instead of WordPress auth
 * 
 * @package Leave_Manager
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Leave_Manager_Calendar_Events_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        // AJAX handlers for logged-in users (WordPress)
        add_action( 'wp_ajax_leave_manager_get_staff_leave_events', array( $this, 'get_staff_leave_events' ) );
        add_action( 'wp_ajax_leave_manager_get_team_leave_events', array( $this, 'get_team_leave_events' ) );
        add_action( 'wp_ajax_leave_manager_get_public_holidays_events', array( $this, 'get_public_holidays_events' ) );
        
        // AJAX handlers for non-logged-in users (custom auth)
        add_action( 'wp_ajax_nopriv_leave_manager_get_staff_leave_events', array( $this, 'get_staff_leave_events' ) );
        add_action( 'wp_ajax_nopriv_leave_manager_get_team_leave_events', array( $this, 'get_team_leave_events' ) );
        add_action( 'wp_ajax_nopriv_leave_manager_get_public_holidays_events', array( $this, 'get_public_holidays_events' ) );
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
     * Get Staff Leave Events
     * Returns leave events for the current user
     */
    public function get_staff_leave_events() {
        // Verify nonce - accept both nonce names for compatibility
        $nonce_valid = false;
        if ( isset( $_POST['nonce'] ) ) {
            $nonce_valid = wp_verify_nonce( $_POST['nonce'], 'leave_manager_calendar_nonce' ) ||
                          wp_verify_nonce( $_POST['nonce'], 'leave_manager_nonce' );
        }
        
        if ( ! $nonce_valid ) {
            wp_send_json_error( 'Invalid security token' );
        }

        // Verify custom authentication
        $current_user = $this->verify_custom_auth();
        if ( ! $current_user ) {
            wp_send_json_error( 'Authentication required' );
        }

        global $wpdb;

        // Get user ID from POST or use current user
        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : $current_user->user_id;
        
        // Security: Only allow users to view their own events (unless admin)
        if ( $user_id !== $current_user->user_id && ! current_user_can( 'manage_options' ) ) {
            $user_id = $current_user->user_id;
        }
        
        $start = isset( $_POST['start'] ) ? sanitize_text_field( $_POST['start'] ) : date( 'Y-m-01' );
        $end   = isset( $_POST['end'] ) ? sanitize_text_field( $_POST['end'] ) : date( 'Y-m-t' );

        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
        $users_table    = $wpdb->prefix . 'leave_manager_leave_users';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT r.request_id, r.user_id, r.start_date, r.end_date, r.leave_type, r.status, u.first_name, u.last_name
                FROM {$requests_table} r
                JOIN {$users_table} u ON r.user_id = u.user_id
                WHERE r.user_id = %d 
                AND ((r.start_date >= %s AND r.start_date <= %s) OR (r.end_date >= %s AND r.end_date <= %s) OR (r.start_date <= %s AND r.end_date >= %s))
                ORDER BY r.start_date ASC",
                $user_id,
                $start,
                $end,
                $start,
                $end,
                $start,
                $end
            )
        );

        $events = array();

        foreach ( $results as $request ) {
            $events[] = array(
                'id'         => 'leave-' . $request->id,
                'title'      => ucfirst( str_replace( '_', ' ', $request->leave_type ) ),
                'start'      => $request->start_date,
                'end'        => $request->end_date,
                'leave_type' => $request->leave_type,
                'status'     => $request->status,
                'user_name'  => $request->first_name . ' ' . $request->last_name,
                'backgroundColor' => $this->get_leave_color_by_status( $request->status ),
                'borderColor'     => $this->get_leave_border_color_by_status( $request->status ),
            );
        }

        wp_send_json_success( $events );
    }

    /**
     * Get Team Leave Events
     * Returns leave events for team members (with filtering)
     */
    public function get_team_leave_events() {
        // Verify nonce
        $nonce_valid = false;
        if ( isset( $_POST['nonce'] ) ) {
            $nonce_valid = wp_verify_nonce( $_POST['nonce'], 'leave_manager_calendar_nonce' ) ||
                          wp_verify_nonce( $_POST['nonce'], 'leave_manager_nonce' );
        }
        
        if ( ! $nonce_valid ) {
            wp_send_json_error( 'Invalid security token' );
        }

        // Verify custom authentication
        $current_user = $this->verify_custom_auth();
        if ( ! $current_user ) {
            wp_send_json_error( 'Authentication required' );
        }

        global $wpdb;

        $filter     = isset( $_POST['filter'] ) ? sanitize_text_field( $_POST['filter'] ) : 'all';
        $department = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : '';
        $start      = isset( $_POST['start'] ) ? sanitize_text_field( $_POST['start'] ) : date( 'Y-m-01' );
        $end        = isset( $_POST['end'] ) ? sanitize_text_field( $_POST['end'] ) : date( 'Y-m-t' );

        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
        $users_table    = $wpdb->prefix . 'leave_manager_leave_users';

        // Build query based on filter
        $where_clauses = array( "r.status IN ('approved', 'pending')" );
        $values = array();

        // Filter by department if requested
        if ( $filter === 'department' && ! empty( $department ) ) {
            $where_clauses[] = "u.department = %s";
            $values[] = $department;
        }

        // Filter by on-leave status (currently on leave)
        if ( $filter === 'on-leave' ) {
            $today = date( 'Y-m-d' );
            $where_clauses[] = "r.start_date <= %s AND r.end_date >= %s AND r.status = 'approved'";
            $values[] = $today;
            $values[] = $today;
        }

        $where_sql = implode( ' AND ', $where_clauses );

        $query = "SELECT r.request_id, r.user_id, r.start_date, r.end_date, r.leave_type, r.status, 
                         u.first_name, u.last_name, u.department
                  FROM {$requests_table} r
                  JOIN {$users_table} u ON r.user_id = u.user_id
                  WHERE {$where_sql}
                  ORDER BY r.start_date ASC
                  LIMIT 50";

        if ( ! empty( $values ) ) {
            $results = $wpdb->get_results( $wpdb->prepare( $query, $values ) );
        } else {
            $results = $wpdb->get_results( $query );
        }

        $team_data = array();
        $today = date( 'Y-m-d' );

        foreach ( $results as $request ) {
            $is_on_leave = ( $request->start_date <= $today && $request->end_date >= $today && $request->status === 'approved' );
            $is_pending = ( $request->status === 'pending' );
            
            $status = 'available';
            if ( $is_on_leave ) {
                $status = 'on-leave';
            } elseif ( $is_pending ) {
                $status = 'pending';
            }
            
            // Format dates
            $start_date = date( 'M j', strtotime( $request->start_date ) );
            $end_date = date( 'M j, Y', strtotime( $request->end_date ) );
            $dates = $start_date . ' - ' . $end_date;

            $team_data[] = array(
                'id'         => $request->id,
                'user_id'    => $request->user_id,
                'name'       => $request->first_name . ' ' . $request->last_name,
                'department' => $request->department ?: 'Unassigned',
                'dates'      => $dates,
                'leave_type' => ucfirst( str_replace( '_', ' ', $request->leave_type ) ),
                'status'     => $status,
                'start_date' => $request->start_date,
                'end_date'   => $request->end_date,
            );
        }

        // If no results, return empty with message
        if ( empty( $team_data ) ) {
            wp_send_json_success( array() );
        }

        wp_send_json_success( $team_data );
    }

    /**
     * Get Public Holidays Events
     * Returns public holidays for the specified date range
     */
    public function get_public_holidays_events() {
        // Verify nonce
        $nonce_valid = false;
        if ( isset( $_POST['nonce'] ) ) {
            $nonce_valid = wp_verify_nonce( $_POST['nonce'], 'leave_manager_calendar_nonce' ) ||
                          wp_verify_nonce( $_POST['nonce'], 'leave_manager_nonce' );
        }
        
        if ( ! $nonce_valid ) {
            wp_send_json_error( 'Invalid security token' );
        }

        global $wpdb;

        $start = isset( $_POST['start'] ) ? sanitize_text_field( $_POST['start'] ) : date( 'Y-m-01' );
        $end   = isset( $_POST['end'] ) ? sanitize_text_field( $_POST['end'] ) : date( 'Y-m-t' );

        $holidays_table = $wpdb->prefix . 'leave_manager_public_holidays';

        // Check if table exists
        $table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$holidays_table}'" );
        
        if ( ! $table_exists ) {
            wp_send_json_success( array() );
        }

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, holiday_name, holiday_date, is_optional
                FROM {$holidays_table}
                WHERE holiday_date >= %s AND holiday_date <= %s
                ORDER BY holiday_date ASC",
                $start,
                $end
            )
        );

        $events = array();

        foreach ( $results as $holiday ) {
            $events[] = array(
                'id'          => 'holiday-' . $holiday->id,
                'title'       => $holiday->holiday_name,
                'start'       => $holiday->holiday_date,
                'end'         => $holiday->holiday_date,
                'is_optional' => (bool) $holiday->is_optional,
                'type'        => 'public_holiday',
                'backgroundColor' => '#FFC107',
                'borderColor'     => '#FF9800',
            );
        }

        wp_send_json_success( $events );
    }

    /**
     * Get leave color by type
     *
     * @param string $type Leave type
     * @return string Color hex code
     */
    private function get_leave_color( $type ) {
        $colors = array(
            'annual' => '#4A5FFF',
            'sick'   => '#f44336',
            'study'  => '#667eea',
            'other'  => '#9c27b0',
        );

        return isset( $colors[ $type ] ) ? $colors[ $type ] : '#999999';
    }

    /**
     * Get leave border color by type
     *
     * @param string $type Leave type
     * @return string Color hex code
     */
    private function get_leave_border_color( $type ) {
        $colors = array(
            'annual' => '#3d4dcc',
            'sick'   => '#d32f2f',
            'study'  => '#5e35b1',
            'other'  => '#7b1fa2',
        );

        return isset( $colors[ $type ] ) ? $colors[ $type ] : '#666666';
    }

    /**
     * Get leave color by status
     *
     * @param string $status Leave status
     * @return string Color hex code
     */
    private function get_leave_color_by_status( $status ) {
        $colors = array(
            'approved' => '#4CAF50',
            'pending'  => '#FF9800',
            'rejected' => '#f44336',
        );

        return isset( $colors[ $status ] ) ? $colors[ $status ] : '#999999';
    }

    /**
     * Get leave border color by status
     *
     * @param string $status Leave status
     * @return string Color hex code
     */
    private function get_leave_border_color_by_status( $status ) {
        $colors = array(
            'approved' => '#388E3C',
            'pending'  => '#F57C00',
            'rejected' => '#d32f2f',
        );

        return isset( $colors[ $status ] ) ? $colors[ $status ] : '#666666';
    }
}

// Initialize the handler
new Leave_Manager_Calendar_Events_Handler();
