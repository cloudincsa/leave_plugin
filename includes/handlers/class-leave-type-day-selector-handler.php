<?php
/**
 * Leave Type Day Selector Handler
 * 
 * Handles AJAX requests for leave type day selection configuration
 * Manages full day, half day, and quarter day options per leave type
 * 
 * @package Leave_Manager
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Leave_Type_Day_Selector_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_leave_manager_save_day_selector', array( $this, 'save_day_selector' ) );
		add_action( 'wp_ajax_leave_manager_get_day_selector', array( $this, 'get_day_selector' ) );
	}

	/**
	 * Save Day Selector Configuration
	 */
	public function save_day_selector() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Validate required fields
		if ( empty( $_POST['leave_type_id'] ) ) {
			wp_send_json_error( 'Leave type ID is required' );
		}

		global $wpdb;

		$leave_type_id           = intval( $_POST['leave_type_id'] );
		$allow_full_day          = isset( $_POST['allow_full_day'] ) ? (bool) $_POST['allow_full_day'] : true;
		$allow_half_day          = isset( $_POST['allow_half_day'] ) ? (bool) $_POST['allow_half_day'] : false;
		$half_day_value          = isset( $_POST['half_day_value'] ) ? floatval( $_POST['half_day_value'] ) : 0.5;
		$allow_quarter_day       = isset( $_POST['allow_quarter_day'] ) ? (bool) $_POST['allow_quarter_day'] : false;
		$quarter_day_value       = isset( $_POST['quarter_day_value'] ) ? floatval( $_POST['quarter_day_value'] ) : 0.25;
		$full_day_value          = isset( $_POST['full_day_value'] ) ? floatval( $_POST['full_day_value'] ) : 1.0;

		// Validate values
		if ( $half_day_value <= 0 || $half_day_value >= 1 ) {
			wp_send_json_error( 'Half day value must be between 0 and 1' );
		}

		if ( $quarter_day_value <= 0 || $quarter_day_value >= 1 ) {
			wp_send_json_error( 'Quarter day value must be between 0 and 1' );
		}

		if ( $full_day_value <= 0 ) {
			wp_send_json_error( 'Full day value must be greater than 0' );
		}

		// Check if leave type exists
		$leave_type = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}leave_manager_leave_types WHERE type_id = %d",
			$leave_type_id
		) );

		if ( ! $leave_type ) {
			wp_send_json_error( 'Leave type not found' );
		}

		// Check if day selector config exists
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}leave_manager_leave_type_day_selectors WHERE leave_type_id = %d",
			$leave_type_id
		) );

		if ( $existing ) {
			// Update existing configuration
			$result = $wpdb->update(
				"{$wpdb->prefix}leave_manager_leave_type_day_selectors",
				array(
					'allow_full_day'      => $allow_full_day,
					'allow_half_day'      => $allow_half_day,
					'half_day_value'      => $half_day_value,
					'allow_quarter_day'   => $allow_quarter_day,
					'quarter_day_value'   => $quarter_day_value,
					'full_day_value'      => $full_day_value,
					'updated_at'          => current_time( 'mysql' ),
				),
				array( 'leave_type_id' => $leave_type_id )
			);

			if ( $result === false ) {
				wp_send_json_error( 'Failed to update day selector configuration' );
			}
		} else {
			// Create new configuration
			$result = $wpdb->insert(
				"{$wpdb->prefix}leave_manager_leave_type_day_selectors",
				array(
					'leave_type_id'       => $leave_type_id,
					'allow_full_day'      => $allow_full_day,
					'allow_half_day'      => $allow_half_day,
					'half_day_value'      => $half_day_value,
					'allow_quarter_day'   => $allow_quarter_day,
					'quarter_day_value'   => $quarter_day_value,
					'full_day_value'      => $full_day_value,
					'created_at'          => current_time( 'mysql' ),
				)
			);

			if ( ! $result ) {
				wp_send_json_error( 'Failed to create day selector configuration' );
			}
		}

		// Log the action
		do_action( 'leave_manager_log_action', 'Leave type day selector updated', array(
			'leave_type_id'     => $leave_type_id,
			'allow_half_day'    => $allow_half_day,
			'allow_quarter_day' => $allow_quarter_day,
		) );

		wp_send_json_success( 'Day selector configuration saved successfully' );
	}

	/**
	 * Get Day Selector Configuration
	 */
	public function get_day_selector() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Validate required fields
		if ( empty( $_POST['leave_type_id'] ) ) {
			wp_send_json_error( 'Leave type ID is required' );
		}

		global $wpdb;

		$leave_type_id = intval( $_POST['leave_type_id'] );

		$config = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_leave_type_day_selectors WHERE leave_type_id = %d",
			$leave_type_id
		) );

		if ( ! $config ) {
			// Return default configuration if not found
			$config = (object) array(
				'leave_type_id'       => $leave_type_id,
				'allow_full_day'      => true,
				'allow_half_day'      => false,
				'half_day_value'      => 0.5,
				'allow_quarter_day'   => false,
				'quarter_day_value'   => 0.25,
				'full_day_value'      => 1.0,
			);
		}

		wp_send_json_success( $config );
	}
}

// Initialize the handler
new Leave_Manager_Leave_Type_Day_Selector_Handler();
