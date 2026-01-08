<?php
/**
 * Direct Login Endpoint
 * Handles login requests without WordPress theme
 */

// Check if this is a login request
if ( isset( $_GET['leave_manager_login'] ) || isset( $_POST['email'] ) ) {
    // Load WordPress
    define( 'WP_USE_THEMES', false );
    
    // Find wp-load.php
    $wp_load = dirname( __FILE__ );
    while ( $wp_load !== '/' ) {
        if ( file_exists( $wp_load . '/wp-load.php' ) ) {
            require_once( $wp_load . '/wp-load.php' );
            break;
        }
        $wp_load = dirname( $wp_load );
    }
    
    // Include the login page
    include dirname( __FILE__ ) . '/pages/login-polished.php';
    exit;
}
