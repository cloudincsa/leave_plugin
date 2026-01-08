<?php
/**
 * Leave Request AJAX Handler (Refactored)
 *
 * Uses the new service layer and repository pattern for database operations.
 *
 * @package Leave_Manager
 * @version 3.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load Composer autoloader for new architecture
require_once LEAVE_MANAGER_PLUGIN_DIR . 'vendor/autoload.php';

use LeaveManager\Core\ServiceContainer;
use LeaveManager\Service\LeaveRequestService;
use LeaveManager\Security\InputValidator;
use LeaveManager\Security\OutputEscaper;

/**
 * Leave_Manager_Leave_Request_Handler_V2 class
 *
 * Refactored to use the new service layer architecture.
 */
class Leave_Manager_Leave_Request_Handler_V2 {

    /**
     * Service container
     *
     * @var ServiceContainer
     */
    private $container;

    /**
     * Leave request service
     *
     * @var LeaveRequestService
     */
    private $service;

    /**
     * Constructor
     */
    public function __construct() {
        $this->container = ServiceContainer::getInstance();
        $this->service = $this->container->getLeaveRequestService();

        // Register AJAX actions
        add_action( 'wp_ajax_leave_manager_submit_request_v2', array( $this, 'submit_request' ) );
        add_action( 'wp_ajax_leave_manager_get_my_requests_v2', array( $this, 'get_my_requests' ) );
        add_action( 'wp_ajax_leave_manager_get_request_details_v2', array( $this, 'get_request_details' ) );
        add_action( 'wp_ajax_leave_manager_cancel_request_v2', array( $this, 'cancel_request' ) );
        add_action( 'wp_ajax_leave_manager_get_user_stats_v2', array( $this, 'get_user_stats' ) );
    }

    /**
     * Submit a new leave request
     */
    public function submit_request() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();

        $leave_type_id = InputValidator::getInt( 'leave_type_id', 'post', array( 'required' => true ) );
        $start_date = InputValidator::getDate( 'start_date', 'post', array( 'required' => true ) );
        $end_date = InputValidator::getDate( 'end_date', 'post', array( 'required' => true ) );
        $reason = InputValidator::getString( 'reason', 'post', array( 'default' => '' ) );
        $half_day = InputValidator::getBool( 'half_day', 'post', array( 'default' => false ) );

        if ( empty( $leave_type_id ) || empty( $start_date ) || empty( $end_date ) ) {
            wp_send_json_error( array( 'message' => 'Leave type, start date, and end date are required.' ) );
        }

        $result = $this->service->submit(
            (int) $current_user->user_id,
            $leave_type_id,
            $start_date,
            $end_date,
            $reason,
            $half_day
        );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message'    => 'Leave request submitted successfully.',
            'request_id' => $result,
        ) );
    }

    /**
     * Get current user's leave requests
     */
    public function get_my_requests() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        $status = InputValidator::getString( 'status', 'get', array( 'default' => '' ) );

        $requestRepo = $this->container->getLeaveRequestRepository();
        $requests = $requestRepo->findByUser( (int) $current_user->user_id, $status );

        $safe_requests = array();
        foreach ( $requests as $request ) {
            $safe_requests[] = array(
                'request_id'  => (int) $request->request_id,
                'leave_type'  => OutputEscaper::html( $request->leave_type ?? '' ),
                'start_date'  => OutputEscaper::attr( $request->start_date ),
                'end_date'    => OutputEscaper::attr( $request->end_date ),
                'days'        => (float) $request->getDays(),
                'status'      => OutputEscaper::html( $request->status ),
                'reason'      => OutputEscaper::html( $request->reason ?? '' ),
                'created_at'  => OutputEscaper::attr( $request->created_at ),
            );
        }

        wp_send_json_success( array( 'requests' => $safe_requests ) );
    }

    /**
     * Get details of a specific request
     */
    public function get_request_details() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        $request_id = InputValidator::getInt( 'request_id', 'get', array( 'required' => true ) );

        if ( empty( $request_id ) ) {
            wp_send_json_error( array( 'message' => 'Request ID is required.' ) );
        }

        $requestRepo = $this->container->getLeaveRequestRepository();
        $request = $requestRepo->find( $request_id );

        if ( ! $request ) {
            wp_send_json_error( array( 'message' => 'Request not found.' ) );
        }

        $is_owner = (int) $request->user_id === (int) $current_user->user_id;
        $is_manager = in_array( $current_user->role, array( 'admin', 'hr', 'manager' ), true );

        if ( ! $is_owner && ! $is_manager ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        wp_send_json_success( array(
            'request' => array(
                'request_id'  => (int) $request->request_id,
                'user_id'     => (int) $request->user_id,
                'leave_type'  => OutputEscaper::html( $request->leave_type ?? '' ),
                'start_date'  => OutputEscaper::attr( $request->start_date ),
                'end_date'    => OutputEscaper::attr( $request->end_date ),
                'days'        => (float) $request->getDays(),
                'status'      => OutputEscaper::html( $request->status ),
                'reason'      => OutputEscaper::html( $request->reason ?? '' ),
                'created_at'  => OutputEscaper::attr( $request->created_at ),
            ),
        ) );
    }

    /**
     * Cancel a leave request
     */
    public function cancel_request() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        $request_id = InputValidator::getInt( 'request_id', 'post', array( 'required' => true ) );

        if ( empty( $request_id ) ) {
            wp_send_json_error( array( 'message' => 'Request ID is required.' ) );
        }

        $requestRepo = $this->container->getLeaveRequestRepository();
        $request = $requestRepo->find( $request_id );

        if ( ! $request ) {
            wp_send_json_error( array( 'message' => 'Request not found.' ) );
        }

        if ( (int) $request->user_id !== (int) $current_user->user_id ) {
            wp_send_json_error( array( 'message' => 'You can only cancel your own requests.' ) );
        }

        if ( ! $request->canBeCancelled() ) {
            wp_send_json_error( array( 'message' => 'This request cannot be cancelled.' ) );
        }

        $result = $this->service->cancel( $request_id, (int) $current_user->user_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array( 'message' => 'Leave request cancelled successfully.' ) );
    }

    /**
     * Get user statistics
     */
    public function get_user_stats() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        $stats = $this->service->getUserStats( (int) $current_user->user_id );

        wp_send_json_success( $stats );
    }
}

// Instantiate the handler
new Leave_Manager_Leave_Request_Handler_V2();
