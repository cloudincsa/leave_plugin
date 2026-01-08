<?php
/**
 * Public Holidays AJAX Handler (Refactored)
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
 * Leave_Manager_Public_Holidays_Handler_V2 class
 *
 * Refactored to use the new service layer architecture.
 */
class Leave_Manager_Public_Holidays_Handler_V2 {

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
        $this->table = $wpdb->prefix . 'leave_manager_public_holidays';

        // Register AJAX actions
        add_action( 'wp_ajax_leave_manager_get_public_holidays', array( $this, 'get_holidays' ) );
        add_action( 'wp_ajax_nopriv_leave_manager_get_public_holidays', array( $this, 'get_holidays' ) );
        add_action( 'wp_ajax_leave_manager_add_public_holiday', array( $this, 'add_holiday' ) );
        add_action( 'wp_ajax_leave_manager_update_public_holiday', array( $this, 'update_holiday' ) );
        add_action( 'wp_ajax_leave_manager_delete_public_holiday', array( $this, 'delete_holiday' ) );
        add_action( 'wp_ajax_leave_manager_import_holidays', array( $this, 'import_holidays' ) );
    }

    /**
     * Get all public holidays
     */
    public function get_holidays() {
        // Verify nonce
        if ( ! InputValidator::verifyNonce( 'leave_manager_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ) );
        }

        global $wpdb;

        $year = InputValidator::getInt( 'year', 'request', array( 'default' => date( 'Y' ) ) );

        $holidays = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE YEAR(holiday_date) = %d ORDER BY holiday_date ASC",
            $year
        ) );

        // Escape output
        $safe_holidays = array();
        foreach ( $holidays as $holiday ) {
            $safe_holidays[] = array(
                'id'           => (int) $holiday->id,
                'name'         => OutputEscaper::html( $holiday->name ),
                'holiday_date' => OutputEscaper::attr( $holiday->holiday_date ),
                'description'  => OutputEscaper::html( $holiday->description ?? '' ),
                'country'      => OutputEscaper::html( $holiday->country ?? '' ),
                'recurring'    => (bool) ( $holiday->recurring ?? false ),
            );
        }

        wp_send_json_success( array( 'holidays' => $safe_holidays ) );
    }

    /**
     * Add a new public holiday
     */
    public function add_holiday() {
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
        $date = InputValidator::getDate( 'holiday_date', 'post', array( 'required' => true ) );
        $description = InputValidator::getString( 'description', 'post', array( 'default' => '' ) );
        $country = InputValidator::getString( 'country', 'post', array( 'default' => '' ) );
        $recurring = InputValidator::getBool( 'recurring', 'post', array( 'default' => false ) );

        if ( empty( $name ) || empty( $date ) ) {
            wp_send_json_error( array( 'message' => 'Name and date are required.' ) );
        }

        global $wpdb;

        // Check for duplicate
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$this->table} WHERE holiday_date = %s",
            $date
        ) );

        if ( $existing ) {
            wp_send_json_error( array( 'message' => 'A holiday already exists on this date.' ) );
        }

        // Insert holiday
        $result = $wpdb->insert(
            $this->table,
            array(
                'name'         => $name,
                'holiday_date' => $date,
                'description'  => $description,
                'country'      => $country,
                'recurring'    => $recurring ? 1 : 0,
                'created_at'   => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%d', '%s' )
        );

        if ( $result ) {
            wp_send_json_success( array(
                'message' => 'Holiday added successfully.',
                'id'      => $wpdb->insert_id,
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to add holiday.' ) );
        }
    }

    /**
     * Update a public holiday
     */
    public function update_holiday() {
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
        $id = InputValidator::getInt( 'id', 'post', array( 'required' => true ) );
        $name = InputValidator::getString( 'name', 'post', array( 'required' => true ) );
        $date = InputValidator::getDate( 'holiday_date', 'post', array( 'required' => true ) );
        $description = InputValidator::getString( 'description', 'post', array( 'default' => '' ) );
        $country = InputValidator::getString( 'country', 'post', array( 'default' => '' ) );
        $recurring = InputValidator::getBool( 'recurring', 'post', array( 'default' => false ) );

        if ( empty( $id ) || empty( $name ) || empty( $date ) ) {
            wp_send_json_error( array( 'message' => 'ID, name and date are required.' ) );
        }

        global $wpdb;

        // Update holiday
        $result = $wpdb->update(
            $this->table,
            array(
                'name'         => $name,
                'holiday_date' => $date,
                'description'  => $description,
                'country'      => $country,
                'recurring'    => $recurring ? 1 : 0,
                'updated_at'   => current_time( 'mysql' ),
            ),
            array( 'id' => $id ),
            array( '%s', '%s', '%s', '%s', '%d', '%s' ),
            array( '%d' )
        );

        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => 'Holiday updated successfully.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update holiday.' ) );
        }
    }

    /**
     * Delete a public holiday
     */
    public function delete_holiday() {
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

        $id = InputValidator::getInt( 'id', 'post', array( 'required' => true ) );

        if ( empty( $id ) ) {
            wp_send_json_error( array( 'message' => 'Holiday ID is required.' ) );
        }

        global $wpdb;

        $result = $wpdb->delete(
            $this->table,
            array( 'id' => $id ),
            array( '%d' )
        );

        if ( $result ) {
            wp_send_json_success( array( 'message' => 'Holiday deleted successfully.' ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to delete holiday.' ) );
        }
    }

    /**
     * Import holidays from external API
     */
    public function import_holidays() {
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

        $country = InputValidator::getString( 'country', 'post', array( 'default' => 'ZA' ) );
        $year = InputValidator::getInt( 'year', 'post', array( 'default' => date( 'Y' ) ) );

        // Use the holiday API handler to fetch holidays
        if ( class_exists( 'Leave_Manager_Holiday_API_Handler' ) ) {
            $api_handler = new Leave_Manager_Holiday_API_Handler();
            $holidays = $api_handler->fetch_holidays( $country, $year );

            if ( is_wp_error( $holidays ) ) {
                wp_send_json_error( array( 'message' => $holidays->get_error_message() ) );
            }

            global $wpdb;
            $imported = 0;

            foreach ( $holidays as $holiday ) {
                // Check if already exists
                $existing = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$this->table} WHERE holiday_date = %s",
                    $holiday['date']
                ) );

                if ( ! $existing ) {
                    $wpdb->insert(
                        $this->table,
                        array(
                            'name'         => $holiday['name'],
                            'holiday_date' => $holiday['date'],
                            'description'  => $holiday['description'] ?? '',
                            'country'      => $country,
                            'recurring'    => 0,
                            'created_at'   => current_time( 'mysql' ),
                        ),
                        array( '%s', '%s', '%s', '%s', '%d', '%s' )
                    );
                    $imported++;
                }
            }

            wp_send_json_success( array(
                'message'  => sprintf( '%d holidays imported successfully.', $imported ),
                'imported' => $imported,
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Holiday API handler not available.' ) );
        }
    }
}

// Instantiate the handler
new Leave_Manager_Public_Holidays_Handler_V2();
