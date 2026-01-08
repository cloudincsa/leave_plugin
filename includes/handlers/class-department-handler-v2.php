<?php
/**
 * Department AJAX Handler (Refactored)
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

use LeaveManager\Security\InputValidator;
use LeaveManager\Security\OutputEscaper;

/**
 * Leave_Manager_Department_Handler_V2 class
 *
 * Refactored to use the new service layer architecture.
 */
class Leave_Manager_Department_Handler_V2 {

    /**
     * Table name
     *
     * @var string
     */
    private $table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'leave_manager_departments';

        // Register AJAX actions
        add_action( 'wp_ajax_leave_manager_get_departments', array( $this, 'get_departments' ) );
        add_action( 'wp_ajax_nopriv_leave_manager_get_departments', array( $this, 'get_departments' ) );
        add_action( 'wp_ajax_leave_manager_add_department', array( $this, 'add_department' ) );
        add_action( 'wp_ajax_leave_manager_update_department', array( $this, 'update_department' ) );
        add_action( 'wp_ajax_leave_manager_delete_department', array( $this, 'delete_department' ) );
    }

    /**
     * Get all departments
     */
    public function get_departments() {
        // Verify nonce (optional for public access)
        global $wpdb;

        $departments = $wpdb->get_results(
            "SELECT * FROM {$this->table} ORDER BY name ASC"
        );

        $safe_departments = array();
        foreach ( $departments as $dept ) {
            $safe_departments[] = array(
                'department_id' => (int) $dept->department_id,
                'name'          => OutputEscaper::html( $dept->name ),
                'description'   => OutputEscaper::html( $dept->description ?? '' ),
                'manager_id'    => (int) ( $dept->manager_id ?? 0 ),
                'status'        => OutputEscaper::html( $dept->status ?? 'active' ),
            );
        }

        wp_send_json_success( array( 'departments' => $safe_departments ) );
    }

    /**
     * Add a new department
     */
    public function add_department() {
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
        $name = InputValidator::getString( 'name', 'post', array( 'required' => true ) );
        $description = InputValidator::getString( 'description', 'post', array( 'default' => '' ) );
        $manager_id = InputValidator::getInt( 'manager_id', 'post', array( 'default' => 0 ) );

        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => 'Department name is required.' ) );
        }

        global $wpdb;

        // Check for duplicate
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT department_id FROM {$this->table} WHERE name = %s",
            $name
        ) );

        if ( $existing ) {
            wp_send_json_error( array( 'message' => 'A department with this name already exists.' ) );
        }

        // Insert department
        $result = $wpdb->insert(
            $this->table,
            array(
                'name'        => $name,
                'description' => $description,
                'manager_id'  => $manager_id,
                'status'      => 'active',
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%d', '%s', '%s' )
        );

        if ( $result ) {
            wp_send_json_success( array(
                'message'       => 'Department added successfully.',
                'department_id' => $wpdb->insert_id,
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to add department.' ) );
        }
    }

    /**
     * Update a department
     */
    public function update_department() {
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
        $department_id = InputValidator::getInt( 'department_id', 'post', array( 'required' => true ) );
        $name = InputValidator::getString( 'name', 'post' );
        $description = InputValidator::getString( 'description', 'post' );
        $manager_id = InputValidator::getInt( 'manager_id', 'post' );
        $status = InputValidator::getString( 'status', 'post' );

        if ( empty( $department_id ) ) {
            wp_send_json_error( array( 'message' => 'Department ID is required.' ) );
        }

        global $wpdb;

        // Build update data
        $update_data = array();
        $format = array();

        if ( ! empty( $name ) ) {
            $update_data['name'] = $name;
            $format[] = '%s';
        }
        if ( $description !== null ) {
            $update_data['description'] = $description;
            $format[] = '%s';
        }
        if ( $manager_id !== null ) {
            $update_data['manager_id'] = $manager_id;
            $format[] = '%d';
        }
        if ( ! empty( $status ) ) {
            $update_data['status'] = $status;
            $format[] = '%s';
        }

        if ( empty( $update_data ) ) {
            wp_send_json_error( array( 'message' => 'No data to update.' ) );
        }

        $update_data['updated_at'] = current_time( 'mysql' );
        $format[] = '%s';

        $result = $wpdb->update(
            $this->table,
            $update_data,
            array( 'department_id' => $department_id ),
            $format,
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => 'Department updated successfully.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update department.' ) );
        }
    }

    /**
     * Delete a department
     */
    public function delete_department() {
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
            wp_send_json_error( array( 'message' => 'Only administrators can delete departments.' ) );
        }

        $department_id = InputValidator::getInt( 'department_id', 'post', array( 'required' => true ) );

        if ( empty( $department_id ) ) {
            wp_send_json_error( array( 'message' => 'Department ID is required.' ) );
        }

        global $wpdb;

        // Check if department has staff
        $users_table = $wpdb->prefix . 'leave_manager_leave_users';
        $staff_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$users_table} WHERE department_id = %d",
            $department_id
        ) );

        if ( $staff_count > 0 ) {
            wp_send_json_error( array(
                'message' => 'Cannot delete department with assigned staff. Please reassign staff first.',
            ) );
        }

        $result = $wpdb->delete(
            $this->table,
            array( 'department_id' => $department_id ),
            array( '%d' )
        );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Department deleted successfully.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to delete department.' ) );
        }
    }
}

// Instantiate the handler
new Leave_Manager_Department_Handler_V2();
