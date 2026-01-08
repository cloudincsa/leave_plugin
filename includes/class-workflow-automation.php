<?php
/**
 * Workflow Automation Class
 * 
 * Implements automated workflows for leave request management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Leave_Manager_Workflow_Automation {

    private $db;
    private $logger;
    private $settings;

    /**
     * Constructor
     */
    public function __construct($db, $logger, $settings) {
        $this->db = $db;
        $this->logger = $logger;
        $this->settings = $settings;
        $this->init();
    }

    /**
     * Initialize workflow automation
     */
    public function init() {
        // Register workflow hooks
        add_action('leave_manager_request_submitted', array($this, 'handle_request_submitted'));
        add_action('leave_manager_request_approved', array($this, 'handle_request_approved'));
        add_action('leave_manager_request_rejected', array($this, 'handle_request_rejected'));
        add_action('leave_manager_request_updated', array($this, 'handle_request_updated'));
        
        // Register cron jobs for automated tasks
        add_action('leave_manager_auto_approve_cron', array($this, 'auto_approve_eligible_requests'));
        add_action('leave_manager_send_reminders_cron', array($this, 'send_pending_reminders'));
        add_action('leave_manager_process_accruals_cron', array($this, 'process_leave_accruals'));
        add_action('leave_manager_archive_old_requests_cron', array($this, 'archive_old_requests'));

        // Register AJAX handlers
        add_action('wp_ajax_leave_manager_create_workflow', array($this, 'create_workflow'));
        add_action('wp_ajax_leave_manager_update_workflow', array($this, 'update_workflow'));
        add_action('wp_ajax_leave_manager_delete_workflow', array($this, 'delete_workflow'));
    }

    /**
     * Handle request submitted
     */
    public function handle_request_submitted($request_id) {
        $request = $this->db->get_leave_request($request_id);
        
        // Check if auto-approval is enabled
        if ($this->is_auto_approval_eligible($request)) {
            $this->auto_approve_request($request_id);
            return;
        }

        // Route to appropriate approver
        $this->route_to_approver($request);

        // Send notification to requester
        $this->send_submission_confirmation($request);

        $this->logger->info("Leave request {$request_id} submitted and routed for approval");
    }

    /**
     * Handle request approved
     */
    public function handle_request_approved($request_id) {
        $request = $this->db->get_leave_request($request_id);

        // Update leave balance
        $this->update_leave_balance($request);

        // Send approval notification
        $this->send_approval_notification($request);

        // Check for downstream workflows
        $this->trigger_downstream_workflows($request, 'approved');

        // Log action
        $this->logger->info("Leave request {$request_id} approved");
    }

    /**
     * Handle request rejected
     */
    public function handle_request_rejected($request_id) {
        $request = $this->db->get_leave_request($request_id);

        // Send rejection notification
        $this->send_rejection_notification($request);

        // Check for escalation workflows
        $this->trigger_escalation_workflows($request);

        // Log action
        $this->logger->info("Leave request {$request_id} rejected");
    }

    /**
     * Handle request updated
     */
    public function handle_request_updated($request_id) {
        $request = $this->db->get_leave_request($request_id);

        // Re-validate against current policies
        $this->revalidate_request($request);

        // Notify affected parties
        $this->notify_update($request);

        $this->logger->info("Leave request {$request_id} updated");
    }

    /**
     * Check if request is eligible for auto-approval
     */
    private function is_auto_approval_eligible($request) {
        // Check auto-approval settings
        $auto_approve = get_option('leave_manager_auto_approve_enabled', false);
        if (!$auto_approve) {
            return false;
        }

        // Check if within auto-approval threshold
        $threshold = get_option('leave_manager_auto_approve_days', 2);
        $days = $this->calculate_business_days($request->start_date, $request->end_date);

        if ($days > $threshold) {
            return false;
        }

        // Check leave balance
        $balance = $this->get_leave_balance($request->user_id, $request->leave_type);
        $required = $days;

        return $balance >= $required;
    }

    /**
     * Auto-approve eligible requests
     */
    public function auto_approve_eligible_requests() {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        // Get pending requests
        $requests = $wpdb->get_results(
            "SELECT * FROM {$table} WHERE status='pending' ORDER BY created_at ASC"
        );

        foreach ($requests as $request) {
            if ($this->is_auto_approval_eligible($request)) {
                $this->auto_approve_request($request->request_id);
            }
        }

        $this->logger->info("Auto-approval cron job completed");
    }

    /**
     * Auto-approve a request
     */
    private function auto_approve_request($request_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        $wpdb->update(
            $table,
            array(
                'status' => 'approved',
                'approved_by' => 0, // System approval
                'approved_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('request_id' => $request_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );

        // Trigger approval hooks
        do_action('leave_manager_request_approved', $request_id);

        $this->logger->info("Leave request {$request_id} auto-approved by system");
    }

    /**
     * Route request to appropriate approver
     */
    private function route_to_approver($request) {
        // Get approval workflow for leave type
        $workflow = $this->get_approval_workflow($request->leave_type);

        if (!$workflow) {
            // Default: route to direct manager
            $manager_id = $this->get_user_manager($request->user_id);
            if ($manager_id) {
                $this->assign_approval($request->request_id, $manager_id);
            }
            return;
        }

        // Route through workflow chain
        $approvers = $this->get_workflow_approvers($workflow);
        foreach ($approvers as $approver) {
            $this->assign_approval($request->request_id, $approver->user_id, $approver->level);
        }
    }

    /**
     * Send submission confirmation
     */
    private function send_submission_confirmation($request) {
        $user = get_user_by('ID', $request->user_id);
        
        // Send confirmation email
        // Implementation would use email handler
    }

    /**
     * Send approval notification
     */
    private function send_approval_notification($request) {
        $user = get_user_by('ID', $request->user_id);
        
        // Send approval email
        // Implementation would use email handler
    }

    /**
     * Send rejection notification
     */
    private function send_rejection_notification($request) {
        $user = get_user_by('ID', $request->user_id);
        
        // Send rejection email with reason
        // Implementation would use email handler
    }

    /**
     * Update leave balance
     */
    private function update_leave_balance($request) {
        $days = $this->calculate_business_days($request->start_date, $request->end_date);
        
        // Update user meta with new balance
        $current_balance = get_user_meta($request->user_id, 'leave_balance_' . $request->leave_type, true);
        $new_balance = $current_balance - $days;
        
        update_user_meta($request->user_id, 'leave_balance_' . $request->leave_type, $new_balance);
    }

    /**
     * Trigger downstream workflows
     */
    private function trigger_downstream_workflows($request, $status) {
        // Check for any dependent workflows
        // Example: Send calendar invite, update team schedule, etc.
    }

    /**
     * Trigger escalation workflows
     */
    private function trigger_escalation_workflows($request) {
        // Check if request should be escalated
        // Example: Escalate to HR if rejected multiple times
    }

    /**
     * Revalidate request
     */
    private function revalidate_request($request) {
        // Re-check against current policies
        // Check for conflicts
        // Verify leave balance
    }

    /**
     * Notify update
     */
    private function notify_update($request) {
        // Notify approvers and affected parties of changes
    }

    /**
     * Send pending reminders
     */
    public function send_pending_reminders() {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        // Get pending requests older than X days
        $pending = $wpdb->get_results(
            "SELECT * FROM {$table} 
             WHERE status='pending' 
             AND created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)"
        );

        foreach ($pending as $request) {
            // Send reminder to approver
            $this->send_approver_reminder($request);
        }

        $this->logger->info("Pending reminders sent");
    }

    /**
     * Send approver reminder
     */
    private function send_approver_reminder($request) {
        // Implementation to send reminder email to approver
    }

    /**
     * Process leave accruals
     */
    public function process_leave_accruals() {
        // Get all users
        $users = get_users();

        foreach ($users as $user) {
            $this->accrue_leave_for_user($user->ID);
        }

        $this->logger->info("Leave accruals processed");
    }

    /**
     * Accrue leave for user
     */
    private function accrue_leave_for_user($user_id) {
        // Calculate and add accrued leave based on policies
        // Update user meta with new balance
    }

    /**
     * Archive old requests
     */
    public function archive_old_requests() {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        // Archive requests older than 1 year
        $wpdb->update(
            $table,
            array('archived' => 1),
            array('created_at' => array('<', date('Y-m-d', strtotime('-1 year')))),
            array('%d'),
            array('%s')
        );

        $this->logger->info("Old requests archived");
    }

    /**
     * Create workflow
     */
    public function create_workflow() {
        // Verify nonce and permissions
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'leave_manager_workflows')) {
            wp_send_json_error('Invalid security token');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
        }

        // Get workflow data
        $workflow_name = sanitize_text_field($_POST['workflow_name']);
        $leave_type = sanitize_text_field($_POST['leave_type']);
        $approvers = isset($_POST['approvers']) ? array_map('intval', $_POST['approvers']) : array();

        // Save workflow to database
        // Implementation would store in custom table

        wp_send_json_success('Workflow created successfully');
    }

    /**
     * Update workflow
     */
    public function update_workflow() {
        // Implementation for updating workflows
    }

    /**
     * Delete workflow
     */
    public function delete_workflow() {
        // Implementation for deleting workflows
    }

    /**
     * Helper: Get user's manager
     */
    private function get_user_manager($user_id) {
        return get_user_meta($user_id, 'manager_id', true);
    }

    /**
     * Helper: Get approval workflow
     */
    private function get_approval_workflow($leave_type) {
        // Get workflow from database
        return null;
    }

    /**
     * Helper: Get workflow approvers
     */
    private function get_workflow_approvers($workflow) {
        // Get approvers from workflow
        return array();
    }

    /**
     * Helper: Assign approval
     */
    private function assign_approval($request_id, $approver_id, $level = 1) {
        // Create approval record
    }

    /**
     * Helper: Calculate business days
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
     * Helper: Get leave balance
     */
    private function get_leave_balance($user_id, $leave_type) {
        return get_user_meta($user_id, 'leave_balance_' . $leave_type, true) ?: 0;
    }
}
