<?php
/**
 * Helper functions for Leave Manager Plugin
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if current page is a leave manager page
 *
 * @return bool
 */
function is_leave_manager_page() {
    if ( ! is_page() ) {
        return false;
    }

    global $post;
    if ( ! $post ) {
        return false;
    }

    $leave_pages = array(
        'leave-management',
        'dashboard',
        'calendar',
        'request',
        'balance',
        'history',
        'employee-signup'
    );

    return in_array( $post->post_name, $leave_pages ) || 
           strpos( $post->post_name, 'leave-management' ) === 0;
}
