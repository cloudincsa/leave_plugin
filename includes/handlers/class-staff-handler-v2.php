<?php
/**
 * Staff AJAX Handler (Refactored)
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
 * Leave_Manager_Staff_Handler_V2 class
 *
 * Refactored to use the new service layer architecture.
 */
class Leave_Manager_Staff_Handler_V2 {

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
        add_action( 'wp_ajax_leave_manager_get_staff', array( $this, 'get_staff' ) );
        add_action( 'wp_ajax_leave_manager_get_staff_member', array( $this, 'get_staff_member' ) );
        add_action( 'wp_ajax_leave_manager_update_staff', array( $this, 'update_staff' ) );
        add_action( 'wp_ajax_leave_manager_delete_staff', array( $this, 'delete_staff' ) );
        add_action( 'wp_ajax_leave_manager_get_staff_by_department', array( $this, 'get_staff_by_department' ) );
    }

    /**
     * Get all staff members
     */
    public function get_staff() {
        // Verify nonce
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        // Check permissions
        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        if ( ! in_array( $current_user->role, array( 'admin', 'hr', 'manager' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $userRepo = $this->container->getLeaveUserRepository();

        // Get filters
        $status = InputValidator::getString( 'status', 'get', array( 'default' => '' ) );
        $department = InputValidator::getInt( 'department', 'get', array( 'default' => 0 ) );
        $role = InputValidator::getString( 'role', 'get', array( 'default' => '' ) );

        // Build conditions
        $conditions = array();
        if ( ! empty( $status ) ) {
            $conditions['status'] = $status;
        }
        if ( $department > 0 ) {
            $conditions['department_id'] = $department;
        }
        if ( ! empty( $role ) ) {
            $conditions['role'] = $role;
        }

        $staff = $userRepo->findAll( $conditions, array( 'first_name' => 'ASC' ) );

        // Format response
        $safe_staff = array();
        foreach ( $staff as $member ) {
            $safe_staff[] = array(
                'user_id'       => (int) $member->user_id,
                'username'      => OutputEscaper::html( $member->username ),
                'email'         => OutputEscaper::html( $member->email ),
                'first_name'    => OutputEscaper::html( $member->first_name ),
                'last_name'     => OutputEscaper::html( $member->last_name ),
                'full_name'     => OutputEscaper::html( $member->getFullName() ),
                'role'          => OutputEscaper::html( $member->role ),
                'department'    => OutputEscaper::html( $member->department ?? '' ),
                'department_id' => (int) ( $member->department_id ?? 0 ),
                'status'        => OutputEscaper::html( $member->status ),
                'is_active'     => $member->isActive(),
                'is_manager'    => $member->isManager(),
            );
        }

        wp_send_json_success( array( 'staff' => $safe_staff ) );
    }

    /**
     * Get a single staff member
     */
    public function get_staff_member() {
        // Verify nonce
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        // Check permissions
        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $user_id = InputValidator::getInt( 'user_id', 'get', array( 'required' => true ) );

        if ( empty( $user_id ) ) {
            wp_send_json_error( array( 'message' => 'User ID is required.' ) );
        }

        $userRepo = $this->container->getLeaveUserRepository();
        $member = $userRepo->find( $user_id );

        if ( ! $member ) {
            wp_send_json_error( array( 'message' => 'Staff member not found.' ) );
        }

        // Get balances
        $balanceRepo = $this->container->getLeaveBalanceRepository();
        $balances = $balanceRepo->findByUser( $user_id );

        $balance_data = array();
        foreach ( $balances as $balance ) {
            $balance_data[] = array(
                'type'      => $balance->leave_type,
                'total'     => (float) $balance->total_days,
                'used'      => (float) $balance->used_days,
                'pending'   => (float) $balance->pending_days,
                'available' => $balance->getAvailable(),
            );
        }

        wp_send_json_success( array(
            'member'   => array(
                'user_id'       => (int) $member->user_id,
                'username'      => OutputEscaper::html( $member->username ),
                'email'         => OutputEscaper::html( $member->email ),
                'first_name'    => OutputEscaper::html( $member->first_name ),
                'last_name'     => OutputEscaper::html( $member->last_name ),
                'full_name'     => OutputEscaper::html( $member->getFullName() ),
                'role'          => OutputEscaper::html( $member->role ),
                'department'    => OutputEscaper::html( $member->department ?? '' ),
                'department_id' => (int) ( $member->department_id ?? 0 ),
                'status'        => OutputEscaper::html( $member->status ),
            ),
            'balances' => $balance_data,
        ) );
    }

    /**
     * Update a staff member
     */
    public function update_staff() {
        // Verify nonce
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        // Check permissions
        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        if ( ! in_array( $current_user->role, array( 'admin', 'hr' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        // Validate input
        $user_id = InputValidator::getInt( 'user_id', 'post', array( 'required' => true ) );
        $first_name = InputValidator::getString( 'first_name', 'post' );
        $last_name = InputValidator::getString( 'last_name', 'post' );
        $role = InputValidator::getString( 'role', 'post' );
        $department_id = InputValidator::getInt( 'department_id', 'post' );
        $status = InputValidator::getString( 'status', 'post' );

        if ( empty( $user_id ) ) {
            wp_send_json_error( array( 'message' => 'User ID is required.' ) );
        }

        $userRepo = $this->container->getLeaveUserRepository();
        $member = $userRepo->find( $user_id );

        if ( ! $member ) {
            wp_send_json_error( array( 'message' => 'Staff member not found.' ) );
        }

        // Build update data
        $update_data = array();
        if ( ! empty( $first_name ) ) {
            $update_data['first_name'] = $first_name;
        }
        if ( ! empty( $last_name ) ) {
            $update_data['last_name'] = $last_name;
        }
        if ( ! empty( $role ) ) {
            $update_data['role'] = $role;
        }
        if ( $department_id > 0 ) {
            $update_data['department_id'] = $department_id;
        }
        if ( ! empty( $status ) ) {
            $update_data['status'] = $status;
        }

        if ( empty( $update_data ) ) {
            wp_send_json_error( array( 'message' => 'No data to update.' ) );
        }

        $result = $userRepo->update( $user_id, $update_data );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Staff member updated successfully.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update staff member.' ) );
        }
    }

    /**
     * Delete a staff member
     */
    public function delete_staff() {
        // Verify nonce
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        // Check permissions
        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $current_user = $custom_auth->get_current_user();
        if ( $current_user->role !== 'admin' ) {
            wp_send_json_error( array( 'message' => 'Only administrators can delete staff.' ) );
        }

        $user_id = InputValidator::getInt( 'user_id', 'post', array( 'required' => true ) );

        if ( empty( $user_id ) ) {
            wp_send_json_error( array( 'message' => 'User ID is required.' ) );
        }

        // Prevent self-deletion
        if ( (int) $current_user->user_id === $user_id ) {
            wp_send_json_error( array( 'message' => 'You cannot delete your own account.' ) );
        }

        $userRepo = $this->container->getLeaveUserRepository();
        $result = $userRepo->delete( $user_id );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Staff member deleted successfully.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to delete staff member.' ) );
        }
    }

    /**
     * Get staff by department
     */
    public function get_staff_by_department() {
        // Verify nonce
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        // Check permissions
        $custom_auth = new Leave_Manager_Custom_Auth();
        if ( ! $custom_auth->is_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Not authenticated.' ) );
        }

        $department_id = InputValidator::getInt( 'department_id', 'get', array( 'required' => true ) );

        if ( empty( $department_id ) ) {
            wp_send_json_error( array( 'message' => 'Department ID is required.' ) );
        }

        $userRepo = $this->container->getLeaveUserRepository();
        $staff = $userRepo->findByDepartment( $department_id );

        $safe_staff = array();
        foreach ( $staff as $member ) {
            $safe_staff[] = array(
                'user_id'    => (int) $member->user_id,
                'full_name'  => OutputEscaper::html( $member->getFullName() ),
                'role'       => OutputEscaper::html( $member->role ),
                'status'     => OutputEscaper::html( $member->status ),
            );
        }

        wp_send_json_success( array( 'staff' => $safe_staff ) );
    }
}

// Instantiate the handler
new Leave_Manager_Staff_Handler_V2();
