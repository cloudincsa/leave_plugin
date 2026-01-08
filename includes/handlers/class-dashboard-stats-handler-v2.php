<?php
/**
 * Dashboard Stats AJAX Handler (Refactored)
 *
 * @package Leave_Manager
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once LEAVE_MANAGER_PLUGIN_DIR . 'vendor/autoload.php';

use LeaveManager\Core\ServiceContainer;
use LeaveManager\Security\InputValidator;

class Leave_Manager_Dashboard_Stats_Handler_V2 {

    private $container;

    public function __construct() {
        $this->container = ServiceContainer::getInstance();

        add_action( 'wp_ajax_leave_manager_get_dashboard_stats_v2', array( $this, 'get_stats' ) );
        add_action( 'wp_ajax_leave_manager_get_admin_stats_v2', array( $this, 'get_admin_stats' ) );
    }

    public function get_stats() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        $service = $this->container->getLeaveRequestService();

        $stats = $service->getUserStats( (int) $current_user->user_id );

        wp_send_json_success( $stats );
    }

    public function get_admin_stats() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        if ( ! in_array( $current_user->role, array( 'admin', 'hr' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        global $wpdb;

        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';

        $total_users = $wpdb->get_var( "SELECT COUNT(*) FROM {$users_table} WHERE status = 'active'" );
        $pending_requests = $wpdb->get_var( "SELECT COUNT(*) FROM {$requests_table} WHERE status = 'pending'" );
        $approved_this_month = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$requests_table} WHERE status = 'approved' AND MONTH(updated_at) = %d AND YEAR(updated_at) = %d",
            date( 'n' ), date( 'Y' )
        ) );

        $on_leave_today = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$requests_table} WHERE status = 'approved' AND %s BETWEEN start_date AND end_date",
            date( 'Y-m-d' )
        ) );

        wp_send_json_success( array(
            'total_users'          => (int) $total_users,
            'pending_requests'     => (int) $pending_requests,
            'approved_this_month'  => (int) $approved_this_month,
            'on_leave_today'       => (int) $on_leave_today,
        ) );
    }
}

new Leave_Manager_Dashboard_Stats_Handler_V2();
