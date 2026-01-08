<?php
/**
 * Plugin Name: Leave Manager
 * Plugin URI: https://www.cloudinc.co.za/leave-manager
 * Description: A comprehensive leave management system for WordPress with user management, leave requests, approvals, and reporting. Suitable for any business size.
 * Version: 3.0.0
 * Author: CIT Solutions
 * Author URI: https://www.cloudinc.co.za
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: leave-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package Leave_Manager
 */

// Prevent direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Debug logging
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/leave_manager_debug.log');
error_log('Leave Manager plugin loaded at ' . date('Y-m-d H:i:s'));

// Define plugin constants
define( 'LEAVE_MANAGER_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LEAVE_MANAGER_PLUGIN_URL', set_url_scheme( plugin_dir_url( __FILE__ ) ) );
define( 'LEAVE_MANAGER_PLUGIN_VERSION', '3.0.0' );
define( 'LEAVE_MANAGER_DB_VERSION', '3.0.0' );
define( 'LEAVE_MANAGER_PLUGIN_FILE', __FILE__ );
define( 'LEAVE_MANAGER_DEBUG_MODE', false ); // Debug logging disabled in production

// Auto-load plugin classes
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-autoloader.php';

// Load logo and branding
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-logo-branding.php';

// Load authentication classes
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/auth/class-custom-auth.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/auth/class-session-manager.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/auth/functions-auth.php';

// Include helper functions
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/helpers.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/functions-phase-classes.php';

// Load advanced features classes (Phase 1: Foundation)
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-database-migration.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-transaction-manager.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-concurrency-control.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-security-framework.php';

// Load Unified Approval Engine
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-approval-request-manager.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-approval-task-manager.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-approval-delegation-manager.php';

// Load Phase 2A: Pro-Rata & Public Holidays
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-prorata-calculator.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-public-holiday-manager.php';

// Load Phase 2B: Carry-Over & Reports
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-carryover-manager.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-custom-report-builder.php';

// Load Phase 2C: Scheduled Reports & Visualization
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-scheduled-reports-manager.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-data-visualization-manager.php';

// Load Phase 3: Performance Optimization & Security Hardening
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-performance-optimizer.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-advanced-security-manager.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-api-integration-manager.php';

// Load Phase 4: Testing, Documentation & Release
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-testing-framework.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-documentation-generator.php';

// Load Report AJAX Handler
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-report-ajax-handler.php';

// Load Admin AJAX Handler
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-admin-ajax-handler.php';

// Load Complete AJAX Handler
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-complete-ajax-handler.php';

// Load new AJAX handlers for features
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/handlers/class-leave-request-handler.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/handlers/class-leave-approval-handler.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/handlers/class-holiday-api-handler.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/handlers/class-public-holidays-handler.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/handlers/class-leave-type-day-selector-handler.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/handlers/class-calendar-events-handler.php';

// Instantiate AJAX handlers to register their actions
new Leave_Manager_Report_Ajax_Handler();
new Leave_Manager_Admin_AJAX_Handler();
// Note: Complete_Ajax_Handler has duplicate actions, skip to avoid conflicts

// Load Phase 5 classes - Profile & Department Manager
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-profile-manager.php';
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-department-approval-system.php';



// Include the main plugin class
require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Initialize the plugin
 */
function leave_manager_init() {
	// Load debug logger
	if ( defined( 'LEAVE_MANAGER_DEBUG_MODE' ) && LEAVE_MANAGER_DEBUG_MODE ) {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-debug-logger.php';
		Leave_Manager_Debug_Logger::log( 'Plugin initialization started', 'INFO' );
		Leave_Manager_Debug_Logger::log_user_info( 'plugins_loaded hook' );
	}
	
	// Hide WordPress admin bar on frontend
	add_filter( 'show_admin_bar', function( $show ) {
		if ( is_admin() ) {
			return $show;
		}
		return false;
	});
	
	$plugin = new Leave_Manager_Plugin();
	$plugin->run();
	
	if ( defined( 'LEAVE_MANAGER_DEBUG_MODE' ) && LEAVE_MANAGER_DEBUG_MODE ) {
		Leave_Manager_Debug_Logger::log( 'Plugin initialization completed', 'INFO' );
	}
}

// Hook plugin initialization to WordPress
add_action( 'plugins_loaded', 'leave_manager_init' );

/**
 * Activation hook
 */
function leave_manager_activate() {
	require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-autoloader.php';
	require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-plugin.php';
	$plugin = new Leave_Manager_Plugin();
	$plugin->activate();
}

register_activation_hook( LEAVE_MANAGER_PLUGIN_FILE, 'leave_manager_activate' );

/**
 * Deactivation hook
 */
function leave_manager_deactivate() {
	require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-autoloader.php';
	require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-plugin.php';
	$plugin = new Leave_Manager_Plugin();
	$plugin->deactivate();
}

register_deactivation_hook( LEAVE_MANAGER_PLUGIN_FILE, 'leave_manager_deactivate' );

/**
 * Get plugin instance
 *
 * @return Leave_Manager_Plugin Plugin instance
 */
function leave_manager() {
	static $plugin = null;
	if ( $plugin === null ) {
		$plugin = new Leave_Manager_Plugin();
	}
	return $plugin;
}

// Add custom dashboard shortcode
add_shortcode( 'leave_manager_dashboard_custom', function() {
    ob_start();
    include LEAVE_MANAGER_PLUGIN_DIR . 'frontend/pages/dashboard-new.php';
    return ob_get_clean();
});

// Add custom login shortcode

// Add polished login shortcode
add_shortcode( 'leave_manager_login', function() {
    ob_start();
    include LEAVE_MANAGER_PLUGIN_DIR . 'frontend/pages/login-polished.php';
    return ob_get_clean();
});

// Add rewrite rule for login page
add_action( 'init', function() {
    add_rewrite_rule( '^leave-manager-login/?$', 'index.php?leave_manager_login=1', 'top' );
});

// Add query var
add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'leave_manager_login';
    return $vars;
});

// Override template for login page
add_filter( 'template_include', function( $template ) {
    if ( get_query_var( 'leave_manager_login' ) ) {
        return LEAVE_MANAGER_PLUGIN_DIR . 'frontend/pages/login-polished.php';
    }
    return $template;
}, 99 );
// Add signup shortcode
add_shortcode( 'leave_manager_signup', function() {
    ob_start();
    include LEAVE_MANAGER_PLUGIN_DIR . 'frontend/signup.php';
    return ob_get_clean();
});

// Add email verification shortcode
add_shortcode( 'leave_manager_verify_email', function() {
    ob_start();
    include LEAVE_MANAGER_PLUGIN_DIR . 'frontend/verify-email.php';
    return ob_get_clean();
});

// Add rewrite rules for signup and verification
add_action( 'init', function() {
    add_rewrite_rule( '^signup/?$', 'index.php?leave_manager_signup=1', 'top' );
    add_rewrite_rule( '^verify-email/?$', 'index.php?leave_manager_verify=1', 'top' );
});

// Add query vars
add_filter( 'query_vars', function( $vars ) {
    $vars[] = 'leave_manager_signup';
    $vars[] = 'leave_manager_verify';
    return $vars;
});

// Override template for signup and verification pages
add_filter( 'template_include', function( $template ) {
    if ( get_query_var( 'leave_manager_signup' ) ) {
        return LEAVE_MANAGER_PLUGIN_DIR . 'frontend/signup.php';
    }
    if ( get_query_var( 'leave_manager_verify' ) ) {
        return LEAVE_MANAGER_PLUGIN_DIR . 'frontend/verify-email.php';
    }
    return $template;
}, 99 );

// Add favicon
add_action( 'wp_head', 'leave_manager_add_favicon' );
add_action( 'admin_head', 'leave_manager_add_favicon' );

function leave_manager_add_favicon() {
    $favicon_path = LEAVE_MANAGER_PLUGIN_DIR . 'assets/images/favicon.ico';
    if ( file_exists( $favicon_path ) ) {
        echo '<link rel="icon" href="' . esc_url( LEAVE_MANAGER_PLUGIN_URL . 'assets/images/favicon.ico' ) . '" type="image/x-icon">';
    }
}

// All login barriers removed - users can log in immediately without verification or approval

// Security headers
add_action( 'send_headers', 'leave_manager_security_headers' );

function leave_manager_security_headers() {
    if ( is_admin() || strpos( $_SERVER['REQUEST_URI'], 'signup' ) !== false ) {
        header( 'X-Content-Type-Options: nosniff' );
        header( 'X-Frame-Options: SAMEORIGIN' );
        header( 'X-XSS-Protection: 1; mode=block' );
    }
}

// HTTPS enforcement disabled for local testing
