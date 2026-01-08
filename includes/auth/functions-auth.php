<?php
/**
 * Authentication Helper Functions
 */

/**
 * Check if user is logged in
 */
function leave_manager_is_logged_in() {
    return Leave_Manager_Custom_Auth::is_logged_in();
}

/**
 * Get current user
 */
function leave_manager_get_current_user() {
    return Leave_Manager_Custom_Auth::get_current_user();
}

/**
 * Check user capability
 */
function leave_manager_user_can( $capability ) {
    return Leave_Manager_Custom_Auth::user_can( $capability );
}

/**
 * Logout user
 */
function leave_manager_logout() {
    Leave_Manager_Custom_Auth::logout();
}

/**
 * Get login URL
 */
function leave_manager_login_url() {
    return home_url( '/leave-manager/login/' );
}

/**
 * Get logout URL
 */
function leave_manager_logout_url() {
    return home_url( '/leave-manager/logout/' );
}

/**
 * Get dashboard URL
 */
function leave_manager_dashboard_url() {
    return home_url( '/leave-management/dashboard/' );
}

/**
 * Require login
 */
function leave_manager_require_login() {
    if ( ! leave_manager_is_logged_in() ) {
        wp_redirect( leave_manager_login_url() );
        exit;
    }
}

/**
 * Require capability
 */
function leave_manager_require_capability( $capability ) {
    if ( ! leave_manager_user_can( $capability ) ) {
        wp_die( 'Access denied' );
    }
}
