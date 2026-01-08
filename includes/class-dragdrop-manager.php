<?php
/**
 * Drag-and-Drop Manager Class
 * 
 * Handles drag-and-drop functionality for leave management
 * Integrates with FullCalendar for interactive leave management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Leave_Manager_DragDrop_Manager {

    private $db;
    private $logger;

    /**
     * Constructor
     */
    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
        $this->init();
    }

    /**
     * Initialize drag-and-drop functionality
     */
    public function init() {
        // Register AJAX handlers
        add_action('wp_ajax_leave_manager_drag_drop_update', array($this, 'handle_drag_drop_update'));
        add_action('wp_ajax_leave_manager_drag_drop_validate', array($this, 'handle_drag_drop_validate'));
        add_action('wp_ajax_leave_manager_bulk_drag_drop', array($this, 'handle_bulk_drag_drop'));
        
        // Enqueue drag-and-drop scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_dragdrop_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_dragdrop_assets'));
    }

    /**
     * Enqueue drag-and-drop assets
     */
    public function enqueue_dragdrop_assets() {
        wp_enqueue_script(
            'leave-manager-dragdrop',
            LEAVE_MANAGER_PLUGIN_URL . 'assets/js/dragdrop.js',
            array('jquery', 'fullcalendar'),
            LEAVE_MANAGER_PLUGIN_VERSION,
            true
        );

        wp_localize_script('leave-manager-dragdrop', 'leaveManagerDragDrop', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('leave_manager_dragdrop'),
            'messages' => array(
                'updating' => __('Updating leave request...', 'leave-manager'),
                'success' => __('Leave request updated successfully', 'leave-manager'),
                'error' => __('Error updating leave request', 'leave-manager'),
                'conflictWarning' => __('This date conflicts with another leave request', 'leave-manager'),
                'balanceWarning' => __('This exceeds your available leave balance', 'leave-manager'),
            )
        ));
    }

    /**
     * Handle drag-and-drop update
     */
    public function handle_drag_drop_update() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'leave_manager_dragdrop')) {
            wp_send_json_error('Invalid security token');
        }

        // Get request data
        $request_id = intval($_POST['request_id']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        $action = sanitize_text_field($_POST['action_type']); // 'update', 'approve', 'reject'

        // Validate dates
        if (!strtotime($start_date) || !strtotime($end_date)) {
            wp_send_json_error('Invalid dates');
        }

        // Get current user
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('Not authenticated');
        }

        // Get leave request
        $request = $this->db->get_leave_request($request_id);
        if (!$request) {
            wp_send_json_error('Leave request not found');
        }

        // Check permissions
        if (!$this->can_modify_request($user_id, $request)) {
            wp_send_json_error('Permission denied');
        }

        // Perform action
        switch ($action) {
            case 'update':
                $result = $this->update_leave_dates($request_id, $start_date, $end_date);
                break;
            case 'approve':
                $result = $this->approve_leave_request($request_id, $user_id);
                break;
            case 'reject':
                $result = $this->reject_leave_request($request_id, $user_id);
                break;
            default:
                wp_send_json_error('Invalid action');
        }

        if ($result) {
            wp_send_json_success(array(
                'message' => 'Leave request updated successfully',
                'request' => $this->db->get_leave_request($request_id)
            ));
        } else {
            wp_send_json_error('Failed to update leave request');
        }
    }

    /**
     * Handle drag-and-drop validation
     */
    public function handle_drag_drop_validate() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'leave_manager_dragdrop')) {
            wp_send_json_error('Invalid security token');
        }

        $request_id = intval($_POST['request_id']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);

        // Validate dates
        if (!strtotime($start_date) || !strtotime($end_date)) {
            wp_send_json_error('Invalid dates');
        }

        $user_id = get_current_user_id();
        $request = $this->db->get_leave_request($request_id);

        if (!$request) {
            wp_send_json_error('Leave request not found');
        }

        // Check for conflicts
        $conflicts = $this->check_date_conflicts($request->user_id, $start_date, $end_date, $request_id);
        
        // Check leave balance
        $balance_check = $this->check_leave_balance($request->user_id, $start_date, $end_date, $request->leave_type);

        $warnings = array();
        if ($conflicts) {
            $warnings[] = array(
                'type' => 'conflict',
                'message' => 'This date conflicts with another leave request',
                'conflicts' => $conflicts
            );
        }

        if (!$balance_check['sufficient']) {
            $warnings[] = array(
                'type' => 'balance',
                'message' => 'This exceeds your available leave balance',
                'required' => $balance_check['required'],
                'available' => $balance_check['available']
            );
        }

        wp_send_json_success(array(
            'valid' => empty($warnings),
            'warnings' => $warnings,
            'days' => $this->calculate_business_days($start_date, $end_date)
        ));
    }

    /**
     * Handle bulk drag-and-drop operations
     */
    public function handle_bulk_drag_drop() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'leave_manager_dragdrop')) {
            wp_send_json_error('Invalid security token');
        }

        $requests = isset($_POST['requests']) ? json_decode(stripslashes($_POST['requests']), true) : array();
        $action = sanitize_text_field($_POST['action_type']);
        $user_id = get_current_user_id();

        if (!$user_id || empty($requests)) {
            wp_send_json_error('Invalid request');
        }

        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );

        foreach ($requests as $req) {
            $request_id = intval($req['id']);
            $request = $this->db->get_leave_request($request_id);

            if (!$request || !$this->can_modify_request($user_id, $request)) {
                $results['failed']++;
                $results['errors'][] = "Cannot modify request {$request_id}";
                continue;
            }

            switch ($action) {
                case 'approve':
                    if ($this->approve_leave_request($request_id, $user_id)) {
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }
                    break;
                case 'reject':
                    if ($this->reject_leave_request($request_id, $user_id)) {
                        $results['success']++;
                    } else {
                        $results['failed']++;
                    }
                    break;
            }
        }

        wp_send_json_success($results);
    }

    /**
     * Update leave request dates
     */
    private function update_leave_dates($request_id, $start_date, $end_date) {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        $result = $wpdb->update(
            $table,
            array(
                'start_date' => $start_date,
                'end_date' => $end_date,
                'updated_at' => current_time('mysql')
            ),
            array('request_id' => $request_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Approve leave request
     */
    private function approve_leave_request($request_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        $result = $wpdb->update(
            $table,
            array(
                'status' => 'approved',
                'approved_by' => $user_id,
                'approved_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('request_id' => $request_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );

        if ($result !== false) {
            // Send notification email
            $request = $this->db->get_leave_request($request_id);
            $user = get_user_by('ID', $request->user_id);
            
            // Log action
            $this->logger->info("Leave request {$request_id} approved by user {$user_id}");
        }

        return $result !== false;
    }

    /**
     * Reject leave request
     */
    private function reject_leave_request($request_id, $user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        $result = $wpdb->update(
            $table,
            array(
                'status' => 'rejected',
                'rejected_by' => $user_id,
                'rejected_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('request_id' => $request_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );

        return $result !== false;
    }

    /**
     * Check if user can modify request
     */
    private function can_modify_request($user_id, $request) {
        // Employee can modify their own pending requests
        if ($request->user_id == $user_id && $request->status == 'pending') {
            return true;
        }

        // Manager/Admin can modify any request
        if (current_user_can('manage_leave_requests')) {
            return true;
        }

        return false;
    }

    /**
     * Check for date conflicts
     */
    private function check_date_conflicts($user_id, $start_date, $end_date, $exclude_request_id = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        $query = $wpdb->prepare(
            "SELECT * FROM {$table} 
             WHERE user_id = %d 
             AND status IN ('approved', 'pending')
             AND request_id != %d
             AND (
                (start_date <= %s AND end_date >= %s) OR
                (start_date >= %s AND start_date <= %s) OR
                (end_date >= %s AND end_date <= %s)
             )",
            $user_id,
            $exclude_request_id,
            $end_date,
            $start_date,
            $start_date,
            $end_date,
            $start_date,
            $end_date
        );

        return $wpdb->get_results($query);
    }

    /**
     * Check leave balance
     */
    private function check_leave_balance($user_id, $start_date, $end_date, $leave_type) {
        $days_required = $this->calculate_business_days($start_date, $end_date);
        $available_balance = $this->get_leave_balance($user_id, $leave_type);

        return array(
            'sufficient' => $available_balance >= $days_required,
            'required' => $days_required,
            'available' => $available_balance
        );
    }

    /**
     * Calculate business days between dates
     */
    private function calculate_business_days($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $days = 0;

        while ($start <= $end) {
            $day_of_week = $start->format('N');
            if ($day_of_week < 6) { // Monday to Friday
                $days++;
            }
            $start->modify('+1 day');
        }

        return $days;
    }

    /**
     * Get leave balance for user
     */
    private function get_leave_balance($user_id, $leave_type) {
        // This would fetch from the database
        // For now, returning a placeholder
        return 20; // Default annual leave balance
    }
}
