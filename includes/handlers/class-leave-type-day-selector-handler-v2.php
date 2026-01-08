<?php
/**
 * Leave Type Day Selector AJAX Handler (Refactored)
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
 * Leave_Manager_Leave_Type_Day_Selector_Handler_V2 class
 *
 * Refactored to use the new service layer architecture.
 */
class Leave_Manager_Leave_Type_Day_Selector_Handler_V2 {

    /**
     * Leave types table name
     *
     * @var string
     */
    private $types_table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->types_table = $wpdb->prefix . 'leave_manager_leave_types';

        // Register AJAX actions
        add_action( 'wp_ajax_leave_manager_get_leave_types', array( $this, 'get_leave_types' ) );
        add_action( 'wp_ajax_nopriv_leave_manager_get_leave_types', array( $this, 'get_leave_types' ) );
        add_action( 'wp_ajax_leave_manager_add_leave_type', array( $this, 'add_leave_type' ) );
        add_action( 'wp_ajax_leave_manager_update_leave_type', array( $this, 'update_leave_type' ) );
        add_action( 'wp_ajax_leave_manager_delete_leave_type', array( $this, 'delete_leave_type' ) );
        add_action( 'wp_ajax_leave_manager_calculate_leave_days', array( $this, 'calculate_leave_days' ) );
    }

    /**
     * Get all leave types
     */
    public function get_leave_types() {
        global $wpdb;

        $leave_types = $wpdb->get_results(
            "SELECT * FROM {$this->types_table} WHERE status = 'active' ORDER BY name ASC"
        );

        $safe_types = array();
        foreach ( $leave_types as $type ) {
            $safe_types[] = array(
                'type_id'           => (int) $type->type_id,
                'name'              => OutputEscaper::html( $type->name ),
                'description'       => OutputEscaper::html( $type->description ?? '' ),
                'default_days'      => (float) ( $type->default_days ?? 0 ),
                'max_days'          => (float) ( $type->max_days ?? 0 ),
                'requires_approval' => (bool) ( $type->requires_approval ?? true ),
                'is_paid'           => (bool) ( $type->is_paid ?? true ),
                'color'             => OutputEscaper::attr( $type->color ?? '#3498db' ),
                'status'            => OutputEscaper::html( $type->status ?? 'active' ),
            );
        }

        wp_send_json_success( array( 'leave_types' => $safe_types ) );
    }

    /**
     * Add a new leave type
     */
    public function add_leave_type() {
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
        $default_days = InputValidator::getFloat( 'default_days', 'post', array( 'default' => 0 ) );
        $max_days = InputValidator::getFloat( 'max_days', 'post', array( 'default' => 0 ) );
        $requires_approval = InputValidator::getBool( 'requires_approval', 'post', array( 'default' => true ) );
        $is_paid = InputValidator::getBool( 'is_paid', 'post', array( 'default' => true ) );
        $color = InputValidator::getString( 'color', 'post', array( 'default' => '#3498db' ) );

        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => 'Leave type name is required.' ) );
        }

        global $wpdb;

        // Check for duplicate
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT type_id FROM {$this->types_table} WHERE name = %s",
            $name
        ) );

        if ( $existing ) {
            wp_send_json_error( array( 'message' => 'A leave type with this name already exists.' ) );
        }

        // Insert leave type
        $result = $wpdb->insert(
            $this->types_table,
            array(
                'name'              => $name,
                'description'       => $description,
                'default_days'      => $default_days,
                'max_days'          => $max_days,
                'requires_approval' => $requires_approval ? 1 : 0,
                'is_paid'           => $is_paid ? 1 : 0,
                'color'             => $color,
                'status'            => 'active',
                'created_at'        => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%f', '%f', '%d', '%d', '%s', '%s', '%s' )
        );

        if ( $result ) {
            wp_send_json_success( array(
                'message' => 'Leave type added successfully.',
                'type_id' => $wpdb->insert_id,
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to add leave type.' ) );
        }
    }

    /**
     * Update a leave type
     */
    public function update_leave_type() {
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
        $type_id = InputValidator::getInt( 'type_id', 'post', array( 'required' => true ) );

        if ( empty( $type_id ) ) {
            wp_send_json_error( array( 'message' => 'Leave type ID is required.' ) );
        }

        global $wpdb;

        // Build update data
        $update_data = array();
        $format = array();

        $fields = array(
            'name'              => array( 'type' => 'string', 'format' => '%s' ),
            'description'       => array( 'type' => 'string', 'format' => '%s' ),
            'default_days'      => array( 'type' => 'float', 'format' => '%f' ),
            'max_days'          => array( 'type' => 'float', 'format' => '%f' ),
            'requires_approval' => array( 'type' => 'bool', 'format' => '%d' ),
            'is_paid'           => array( 'type' => 'bool', 'format' => '%d' ),
            'color'             => array( 'type' => 'string', 'format' => '%s' ),
            'status'            => array( 'type' => 'string', 'format' => '%s' ),
        );

        foreach ( $fields as $field => $config ) {
            $value = null;
            switch ( $config['type'] ) {
                case 'string':
                    $value = InputValidator::getString( $field, 'post' );
                    break;
                case 'float':
                    $value = InputValidator::getFloat( $field, 'post' );
                    break;
                case 'bool':
                    $value = InputValidator::getBool( $field, 'post' );
                    if ( $value !== null ) {
                        $value = $value ? 1 : 0;
                    }
                    break;
            }

            if ( $value !== null && $value !== '' ) {
                $update_data[ $field ] = $value;
                $format[] = $config['format'];
            }
        }

        if ( empty( $update_data ) ) {
            wp_send_json_error( array( 'message' => 'No data to update.' ) );
        }

        $update_data['updated_at'] = current_time( 'mysql' );
        $format[] = '%s';

        $result = $wpdb->update(
            $this->types_table,
            $update_data,
            array( 'type_id' => $type_id ),
            $format,
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => 'Leave type updated successfully.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update leave type.' ) );
        }
    }

    /**
     * Delete a leave type
     */
    public function delete_leave_type() {
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
            wp_send_json_error( array( 'message' => 'Only administrators can delete leave types.' ) );
        }

        $type_id = InputValidator::getInt( 'type_id', 'post', array( 'required' => true ) );

        if ( empty( $type_id ) ) {
            wp_send_json_error( array( 'message' => 'Leave type ID is required.' ) );
        }

        global $wpdb;

        // Check if leave type is in use
        $requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
        $usage_count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$requests_table} WHERE leave_type_id = %d",
            $type_id
        ) );

        if ( $usage_count > 0 ) {
            // Soft delete instead
            $result = $wpdb->update(
                $this->types_table,
                array( 'status' => 'inactive' ),
                array( 'type_id' => $type_id ),
                array( '%s' ),
                array( '%d' )
            );

            if ( $result !== false ) {
                wp_send_json_success( array(
                    'message' => 'Leave type deactivated (in use by existing requests).',
                ) );
            }
        } else {
            $result = $wpdb->delete(
                $this->types_table,
                array( 'type_id' => $type_id ),
                array( '%d' )
            );

            if ( $result ) {
                wp_send_json_success( array( 'message' => 'Leave type deleted successfully.' ) );
            }
        }

        wp_send_json_error( array( 'message' => 'Failed to delete leave type.' ) );
    }

    /**
     * Calculate leave days between two dates
     */
    public function calculate_leave_days() {
        // Verify nonce
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        $start_date = InputValidator::getDate( 'start_date', 'post', array( 'required' => true ) );
        $end_date = InputValidator::getDate( 'end_date', 'post', array( 'required' => true ) );
        $exclude_weekends = InputValidator::getBool( 'exclude_weekends', 'post', array( 'default' => true ) );
        $exclude_holidays = InputValidator::getBool( 'exclude_holidays', 'post', array( 'default' => true ) );

        if ( empty( $start_date ) || empty( $end_date ) ) {
            wp_send_json_error( array( 'message' => 'Start and end dates are required.' ) );
        }

        $start = new DateTime( $start_date );
        $end = new DateTime( $end_date );

        if ( $end < $start ) {
            wp_send_json_error( array( 'message' => 'End date must be after start date.' ) );
        }

        // Get public holidays if needed
        $holidays = array();
        if ( $exclude_holidays ) {
            global $wpdb;
            $holidays_table = $wpdb->prefix . 'leave_manager_public_holidays';
            $holiday_dates = $wpdb->get_col( $wpdb->prepare(
                "SELECT holiday_date FROM {$holidays_table} WHERE holiday_date BETWEEN %s AND %s",
                $start_date,
                $end_date
            ) );
            $holidays = array_flip( $holiday_dates );
        }

        // Calculate days
        $days = 0;
        $current = clone $start;

        while ( $current <= $end ) {
            $day_of_week = (int) $current->format( 'N' );
            $date_str = $current->format( 'Y-m-d' );

            $is_weekend = $day_of_week >= 6;
            $is_holiday = isset( $holidays[ $date_str ] );

            if ( ( ! $exclude_weekends || ! $is_weekend ) && ( ! $exclude_holidays || ! $is_holiday ) ) {
                $days++;
            }

            $current->modify( '+1 day' );
        }

        wp_send_json_success( array(
            'days'       => $days,
            'start_date' => $start_date,
            'end_date'   => $end_date,
        ) );
    }
}

// Instantiate the handler
new Leave_Manager_Leave_Type_Day_Selector_Handler_V2();
