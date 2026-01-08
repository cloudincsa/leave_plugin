<?php
/**
 * Leave Manager - SMS Handler Class
 * 
 * Handles SMS notifications via Infobip API
 * 
 * @package Leave_Manager
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Leave_Manager_SMS_Handler {
    
    private $db;
    private $logger;
    private $settings;
    private $infobip_api_key;
    private $infobip_base_url = 'https://api.infobip.com';
    private $infobip_sender = 'Leave Manager';
    
    /**
     * Constructor
     */
    public function __construct($db, $logger, $settings) {
        $this->db = $db;
        $this->logger = $logger;
        $this->settings = $settings;
        $this->infobip_api_key = $this->settings->get('infobip_api_key', '');
    }
    
    /**
     * Send SMS notification
     */
    public function send_sms($phone_number, $message, $notification_type = 'general') {
        if (empty($this->infobip_api_key)) {
            $this->logger->log('SMS API key not configured', 'WARNING');
            return false;
        }
        
        if (!$this->validate_phone_number($phone_number)) {
            $this->logger->log('Invalid phone number: ' . $phone_number, 'ERROR');
            return false;
        }
        
        $payload = array(
            'messages' => array(
                array(
                    'destinations' => array(
                        array('to' => $phone_number)
                    ),
                    'from' => $this->infobip_sender,
                    'text' => $message
                )
            )
        );
        
        $response = $this->make_api_request('/sms/2/text/advanced', $payload);
        
        if ($response && isset($response['messages'][0]['status']['groupId'])) {
            $this->log_sms($phone_number, $message, 'sent', $notification_type, $response);
            $this->logger->log('SMS sent to ' . $phone_number, 'INFO');
            return true;
        } else {
            $error_message = isset($response['messages'][0]['status']['description']) ? 
                $response['messages'][0]['status']['description'] : 'Unknown error';
            $this->log_sms($phone_number, $message, 'failed', $notification_type, array('error' => $error_message));
            $this->logger->log('SMS failed to ' . $phone_number . ': ' . $error_message, 'ERROR');
            return false;
        }
    }
    
    /**
     * Send SMS for leave request approval
     */
    public function send_leave_approval_sms($user_phone, $user_name, $leave_type, $start_date, $end_date) {
        $message = sprintf(
            "Hi %s, your %s leave request from %s to %s has been approved. Enjoy your leave!",
            $user_name,
            $leave_type,
            $start_date,
            $end_date
        );
        
        return $this->send_sms($user_phone, $message, 'leave_approval');
    }
    
    /**
     * Send SMS for leave request rejection
     */
    public function send_leave_rejection_sms($user_phone, $user_name, $leave_type, $reason = '') {
        $message = sprintf(
            "Hi %s, your %s leave request has been reviewed. %s Please contact HR for details.",
            $user_name,
            $leave_type,
            !empty($reason) ? 'Reason: ' . $reason . '.' : ''
        );
        
        return $this->send_sms($user_phone, $message, 'leave_rejection');
    }
    
    /**
     * Send SMS for new leave request (to HR)
     */
    public function send_new_request_notification_sms($hr_phone, $employee_name, $leave_type, $start_date) {
        $message = sprintf(
            "New leave request: %s has requested %s leave starting %s. Please review in the system.",
            $employee_name,
            $leave_type,
            $start_date
        );
        
        return $this->send_sms($hr_phone, $message, 'new_request_notification');
    }
    
    /**
     * Send SMS for low leave balance alert
     */
    public function send_low_balance_alert_sms($user_phone, $user_name, $leave_type, $remaining_days) {
        $message = sprintf(
            "Hi %s, your %s leave balance is running low. You have %d days remaining.",
            $user_name,
            $leave_type,
            $remaining_days
        );
        
        return $this->send_sms($user_phone, $message, 'low_balance_alert');
    }
    
    /**
     * Send SMS for leave balance reset notification
     */
    public function send_balance_reset_sms($user_phone, $user_name, $leave_type, $new_balance) {
        $message = sprintf(
            "Hi %s, your %s leave balance has been reset. New balance: %d days.",
            $user_name,
            $leave_type,
            $new_balance
        );
        
        return $this->send_sms($user_phone, $message, 'balance_reset');
    }
    
    /**
     * Send SMS for upcoming leave reminder
     */
    public function send_upcoming_leave_reminder_sms($user_phone, $user_name, $days_until_leave) {
        $message = sprintf(
            "Hi %s, reminder: your approved leave starts in %d days. Please ensure handover is complete.",
            $user_name,
            $days_until_leave
        );
        
        return $this->send_sms($user_phone, $message, 'upcoming_leave_reminder');
    }
    
    /**
     * Send SMS for leave return reminder
     */
    public function send_return_reminder_sms($user_phone, $user_name) {
        $message = sprintf(
            "Hi %s, welcome back! Please check your emails and catch up on any urgent matters.",
            $user_name
        );
        
        return $this->send_sms($user_phone, $message, 'return_reminder');
    }
    
    /**
     * Make API request to Infobip
     */
    private function make_api_request($endpoint, $payload) {
        $url = $this->infobip_base_url . $endpoint;
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'App ' . $this->infobip_api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            $this->logger->log('SMS API Error: ' . $response->get_error_message(), 'ERROR');
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Validate phone number format
     */
    private function validate_phone_number($phone) {
        // Remove common formatting characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Check if it starts with + and has at least 10 digits
        if (preg_match('/^\+[0-9]{10,}$/', $phone)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Log SMS in database
     */
    private function log_sms($phone, $message, $status, $type, $response_data) {
        $table = $this->db->prefix . 'leave_manager_sms_logs';
        
        $this->db->insert($table, array(
            'phone_number' => $phone,
            'message' => $message,
            'status' => $status,
            'notification_type' => $type,
            'response_data' => json_encode($response_data),
            'sent_at' => current_time('mysql'),
            'created_at' => current_time('mysql')
        ), array('%s', '%s', '%s', '%s', '%s', '%s', '%s'));
    }
    
    /**
     * Get SMS logs
     */
    public function get_sms_logs($limit = 50, $offset = 0) {
        $table = $this->db->prefix . 'leave_manager_sms_logs';
        $query = $this->db->prepare(
            "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        );
        
        return $this->db->get_results($query);
    }
    
    /**
     * Get SMS log count
     */
    public function get_sms_log_count() {
        $table = $this->db->prefix . 'leave_manager_sms_logs';
        return $this->db->get_var("SELECT COUNT(*) FROM {$table}");
    }
    
    /**
     * Clear SMS logs
     */
    public function clear_sms_logs() {
        $table = $this->db->prefix . 'leave_manager_sms_logs';
        return $this->db->query("TRUNCATE TABLE {$table}");
    }
}
