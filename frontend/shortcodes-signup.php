<?php
/**
 * Employee Signup Shortcodes
 *
 * Registers shortcodes for employee signup functionality.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register employee signup shortcode
 *
 * Usage: [leave_manager_employee_signup]
 */
function leave_manager_register_employee_signup_shortcode() {
	add_shortcode( 'leave_manager_employee_signup', 'leave_manager_render_employee_signup' );
}

/**
 * Render employee signup form
 *
 * @return string HTML output
 */
function leave_manager_render_employee_signup() {
	// Redirect if user is already logged in
	if ( is_user_logged_in() ) {
		return '<div class="alert alert-info"><p>You are already logged in. <a href="' . esc_url( home_url( '/dashboard/' ) ) . '">Go to dashboard</a></p></div>';
	}

	ob_start();
	include LEAVE_MANAGER_PLUGIN_DIR . 'frontend/pages/employee-signup.php';
	return ob_get_clean();
}

// Register shortcode
add_action( 'init', 'leave_manager_register_employee_signup_shortcode' );

/**
 * Create employee signup page on plugin activation
 *
 * @return void
 */
function leave_manager_create_employee_signup_page() {
	// Check if page already exists
	$page = get_page_by_path( 'employee-signup' );
	if ( $page ) {
		return;
	}

	// Create page
	$page_id = wp_insert_post(
		array(
			'post_title'    => 'Employee Signup',
			'post_name'     => 'employee-signup',
			'post_content'  => '[leave_manager_employee_signup]',
			'post_type'     => 'page',
			'post_status'   => 'publish',
			'comment_status' => 'closed',
			'ping_status'   => 'closed',
		)
	);

	if ( ! is_wp_error( $page_id ) ) {
		// Store page ID in options
		update_option( 'leave_manager_employee_signup_page_id', $page_id );
	}
}

// Create page on plugin activation
add_action( 'leave_manager_leave_plugin_activated', 'leave_manager_create_employee_signup_page' );
