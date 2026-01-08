<?php
/**
 * Leave Approval AJAX Handler (Refactored)
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
use LeaveManager\Security\InputValidator;
use LeaveManager\Security\OutputEscaper;

/**
 * Leave_Manager_Leave_Approval_Handler_V2 class
 *
 * Refactored to use the new service layer architecture.
 */
class Leave_Manager_Leave_Approval_Handler_V2 {

    /**
     * Service container
     *
     * @var ServiceContainer
     */
    private $container;

    /**
     * Constructor
     */
    public function __construct() {
        $this->container = ServiceContainer::getInstance();

        // Register AJAX actions
        add_action( 'wp_ajax_leave_manager_get_pending_requests_v2', array( $this, 'get_pending_requests' ) );
        add_action( 'wp_ajax_leave_manager_approve_request_v2', array( $this, 'approve_request' ) );
        add_action( 'wp_ajax_leave_manager_reject_request_v2', array( $this, 'reject_request' ) );
        add_action( 'wp_ajax_leave_manager_get_team_requests_v2', array( $this, 'get_team_requests' ) );
    }

    /**
     * Get pending requests for approval
     */
    public function get_pending_requests() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        if ( ! in_array( $current_user->role, array( 'admin', 'hr', 'manager' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $requestRepo = $this->container->getLeaveRequestRepository();
        $userRepo = $this->container->getLeaveUserRepository();

        if ( $current_user->role === 'manager' ) {
            $requests = $requestRepo->findPendingForApprover( $current_user->department ?? '' );
        } else {
            $requests = $requestRepo->findByStatus( 'pending' );
        }

        $safe_requests = array();
        foreach ( $requests as $request ) {
            $user = $userRepo->find( (int) $request->user_id );
            $safe_requests[] = array(
                'request_id'  => (int) $request->request_id,
                'user_id'     => (int) $request->user_id,
                'user_name'   => $user ? OutputEscaper::html( $user->getFullName() ) : 'Unknown',
                'department'  => $user ? OutputEscaper::html( $user->department ?? '' ) : '',
                'leave_type'  => OutputEscaper::html( $request->leave_type ?? '' ),
                'start_date'  => OutputEscaper::attr( $request->start_date ),
                'end_date'    => OutputEscaper::attr( $request->end_date ),
                'days'        => (float) $request->getDays(),
                'reason'      => OutputEscaper::html( $request->reason ?? '' ),
                'created_at'  => OutputEscaper::attr( $request->created_at ),
            );
        }

        wp_send_json_success( array( 'requests' => $safe_requests ) );
    }

    /**
     * Approve a leave request
     */
    public function approve_request() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        if ( ! in_array( $current_user->role, array( 'admin', 'hr', 'manager' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $request_id = InputValidator::getInt( 'request_id', 'post', array( 'required' => true ) );
        $notes = InputValidator::getString( 'notes', 'post', array( 'default' => '' ) );

        if ( empty( $request_id ) ) {
            wp_send_json_error( array( 'message' => 'Request ID is required.' ) );
        }

        $service = $this->container->getLeaveRequestService();
        $result = $service->approve( $request_id, (int) $current_user->user_id, $notes );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        if ( class_exists( 'Leave_Manager_Email_Handler' ) ) {
            $email_handler = new Leave_Manager_Email_Handler();
            $email_handler->send_approval_notification( $request_id );
        }

        wp_send_json_success( array( 'message' => 'Leave request approved successfully.' ) );
    }

    /**
     * Reject a leave request
     */
    public function reject_request() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        if ( ! in_array( $current_user->role, array( 'admin', 'hr', 'manager' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $request_id = InputValidator::getInt( 'request_id', 'post', array( 'required' => true ) );
        $reason = InputValidator::getString( 'reason', 'post', array( 'required' => true ) );

        if ( empty( $request_id ) ) {
            wp_send_json_error( array( 'message' => 'Request ID is required.' ) );
        }

        if ( empty( $reason ) ) {
            wp_send_json_error( array( 'message' => 'Rejection reason is required.' ) );
        }

        $service = $this->container->getLeaveRequestService();
        $result = $service->reject( $request_id, (int) $current_user->user_id, $reason );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        if ( class_exists( 'Leave_Manager_Email_Handler' ) ) {
            $email_handler = new Leave_Manager_Email_Handler();
            $email_handler->send_rejection_notification( $request_id, $reason );
        }

        wp_send_json_success( array( 'message' => 'Leave request rejected.' ) );
    }

    /**
     * Get team requests (for managers)
     */
    public function get_team_requests() {
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        if ( ! in_array( $current_user->role, array( 'admin', 'hr', 'manager' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $status = InputValidator::getString( 'status', 'get', array( 'default' => '' ) );
        $department = InputValidator::getString( 'department', 'get', array( 'default' => '' ) );

        $requestRepo = $this->container->getLeaveRequestRepository();
        $userRepo = $this->container->getLeaveUserRepository();

        if ( ! empty( $department ) ) {
            $requests = $requestRepo->findByDepartment( $department, $status );
        } elseif ( $current_user->role === 'manager' ) {
            $requests = $requestRepo->findByDepartment( $current_user->department ?? '', $status );
        } else {
            $requests = ! empty( $status ) ? $requestRepo->findByStatus( $status ) : $requestRepo->findAll();
        }

        $safe_requests = array();
        foreach ( $requests as $request ) {
            $user = $userRepo->find( (int) $request->user_id );
            $safe_requests[] = array(
                'request_id'  => (int) $request->request_id,
                'user_id'     => (int) $request->user_id,
                'user_name'   => $user ? OutputEscaper::html( $user->getFullName() ) : 'Unknown',
                'department'  => $user ? OutputEscaper::html( $user->department ?? '' ) : '',
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
}

// Instantiate the handler
new Leave_Manager_Leave_Approval_Handler_V2();
