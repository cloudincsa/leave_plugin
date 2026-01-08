<?php
/**
 * Public Holidays Handler
 * 
 * Handles AJAX requests for public holidays management (Create, Read, Update, Delete)
 * 
 * @package Leave_Manager
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Public_Holidays_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_leave_manager_add_public_holiday', array( $this, 'add_public_holiday' ) );
		add_action( 'wp_ajax_leave_manager_get_public_holidays', array( $this, 'get_public_holidays' ) );
		add_action( 'wp_ajax_leave_manager_update_public_holiday', array( $this, 'update_public_holiday' ) );
		add_action( 'wp_ajax_leave_manager_delete_public_holiday', array( $this, 'delete_public_holiday' ) );
		add_action( 'wp_ajax_leave_manager_get_holiday_details', array( $this, 'get_holiday_details' ) );
	}

	/**
	 * Add Public Holiday
	 */
	public function add_public_holiday() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Validate required fields
		if ( empty( $_POST['holiday_name'] ) || empty( $_POST['holiday_date'] ) ) {
			wp_send_json_error( 'Holiday name and date are required' );
		}

		global $wpdb;

		$holiday_name = sanitize_text_field( $_POST['holiday_name'] );
		$holiday_date = sanitize_text_field( $_POST['holiday_date'] );
		$country_code = isset( $_POST['country_code'] ) ? sanitize_text_field( $_POST['country_code'] ) : 'ZA';
		$is_optional  = isset( $_POST['is_optional'] ) ? (bool) $_POST['is_optional'] : 0;
		$is_recurring = isset( $_POST['is_recurring'] ) ? (bool) $_POST['is_recurring'] : 0;

		// Check if holiday already exists
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}leave_manager_public_holidays WHERE holiday_date = %s AND country_code = %s",
			$holiday_date,
			$country_code
		) );

		if ( $existing ) {
			wp_send_json_error( 'Holiday on this date already exists' );
		}

		// Insert holiday
		$result = $wpdb->insert(
			"{$wpdb->prefix}leave_manager_public_holidays",
			array(
				'country_code'   => $country_code,
				'holiday_name'   => $holiday_name,
				'holiday_date'   => $holiday_date,
				'holiday_year'   => date( 'Y', strtotime( $holiday_date ) ),
				'is_recurring'   => $is_recurring,
				'is_optional'    => $is_optional,
				'source'         => 'manual',
				'created_by'     => get_current_user_id(),
				'created_at'     => current_time( 'mysql' ),
			)
		);

		if ( ! $result ) {
			wp_send_json_error( 'Failed to add holiday' );
		}

		// Log the action
		do_action( 'leave_manager_log_action', 'Public holiday added: ' . $holiday_name, array(
			'holiday_name' => $holiday_name,
			'holiday_date' => $holiday_date,
		) );

		wp_send_json_success( array(
			'id'   => $wpdb->insert_id,
			'message' => 'Holiday added successfully',
		) );
	}

	/**
	 * Get Public Holidays
	 */
	public function get_public_holidays() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		global $wpdb;

		$country_code = isset( $_POST['country_code'] ) ? sanitize_text_field( $_POST['country_code'] ) : '';
		$year         = isset( $_POST['year'] ) ? intval( $_POST['year'] ) : date( 'Y' );

		$query = "SELECT * FROM {$wpdb->prefix}leave_manager_public_holidays WHERE 1=1";

		if ( ! empty( $country_code ) ) {
			$query .= $wpdb->prepare( " AND country_code = %s", $country_code );
		}

		$query .= $wpdb->prepare( " AND holiday_year = %d", $year );
		$query .= " ORDER BY holiday_date ASC";

		$holidays = $wpdb->get_results( $query );

		wp_send_json_success( $holidays );
	}

	/**
	 * Update Public Holiday
	 */
	public function update_public_holiday() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Validate required fields
		if ( empty( $_POST['id'] ) ) {
			wp_send_json_error( 'Holiday ID is required' );
		}

		global $wpdb;

		$id              = intval( $_POST['id'] );
		$holiday_name    = isset( $_POST['holiday_name'] ) ? sanitize_text_field( $_POST['holiday_name'] ) : '';
		$holiday_date    = isset( $_POST['holiday_date'] ) ? sanitize_text_field( $_POST['holiday_date'] ) : '';
		$is_optional     = isset( $_POST['is_optional'] ) ? (bool) $_POST['is_optional'] : 0;
		$is_recurring    = isset( $_POST['is_recurring'] ) ? (bool) $_POST['is_recurring'] : 0;

		// Update holiday
		$result = $wpdb->update(
			"{$wpdb->prefix}leave_manager_public_holidays",
			array(
				'holiday_name' => $holiday_name,
				'holiday_date' => $holiday_date,
				'is_optional'  => $is_optional,
				'is_recurring' => $is_recurring,
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => $id )
		);

		if ( $result === false ) {
			wp_send_json_error( 'Failed to update holiday' );
		}

		// Log the action
		do_action( 'leave_manager_log_action', 'Public holiday updated: ' . $holiday_name, array(
			'holiday_id'   => $id,
			'holiday_name' => $holiday_name,
		) );

		wp_send_json_success( 'Holiday updated successfully' );
	}

	/**
	 * Delete Public Holiday
	 */
	public function delete_public_holiday() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Validate required fields
		if ( empty( $_POST['id'] ) ) {
			wp_send_json_error( 'Holiday ID is required' );
		}

		global $wpdb;

		$id = intval( $_POST['id'] );

		// Get holiday details before deletion
		$holiday = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_public_holidays WHERE id = %d",
			$id
		) );

		if ( ! $holiday ) {
			wp_send_json_error( 'Holiday not found' );
		}

		// Delete holiday
		$result = $wpdb->delete(
			"{$wpdb->prefix}leave_manager_public_holidays",
			array( 'id' => $id )
		);

		if ( ! $result ) {
			wp_send_json_error( 'Failed to delete holiday' );
		}

		// Log the action
		do_action( 'leave_manager_log_action', 'Public holiday deleted: ' . $holiday->holiday_name, array(
			'holiday_id'   => $id,
			'holiday_name' => $holiday->holiday_name,
		) );

		wp_send_json_success( 'Holiday deleted successfully' );
	}

	/**
	 * Get Holiday Details
	 */
	public function get_holiday_details() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Validate required fields
		if ( empty( $_POST['id'] ) ) {
			wp_send_json_error( 'Holiday ID is required' );
		}

		global $wpdb;

		$id = intval( $_POST['id'] );

		$holiday = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}leave_manager_public_holidays WHERE id = %d",
			$id
		) );

		if ( ! $holiday ) {
			wp_send_json_error( 'Holiday not found' );
		}

		wp_send_json_success( $holiday );
	}
}

// Initialize the handler
new Leave_Manager_Public_Holidays_Handler();
