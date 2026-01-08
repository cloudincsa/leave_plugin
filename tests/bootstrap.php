<?php
/**
 * PHPUnit Bootstrap File for Leave Manager Plugin
 *
 * This file sets up the testing environment for the Leave Manager plugin.
 *
 * @package Leave_Manager
 * @subpackage Tests
 */

// Prevent redefining constants
if ( ! defined( 'LEAVE_MANAGER_TESTING' ) ) {
    define( 'LEAVE_MANAGER_TESTING', true );
}

// Get the plugin directory
if ( ! defined( 'LEAVE_MANAGER_PLUGIN_DIR' ) ) {
    define( 'LEAVE_MANAGER_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'LEAVE_MANAGER_PLUGIN_FILE' ) ) {
    define( 'LEAVE_MANAGER_PLUGIN_FILE', LEAVE_MANAGER_PLUGIN_DIR . 'leave-manager.php' );
}

// Load Composer autoloader
require_once LEAVE_MANAGER_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * Determine if we should load WordPress
 */
$load_wordpress = getenv( 'LOAD_WORDPRESS' ) !== 'false';

if ( $load_wordpress ) {
    // Try to load WordPress directly
    $wp_load = '/var/www/html/wp-load.php';
    
    if ( file_exists( $wp_load ) ) {
        // Suppress the plugin's debug output during tests
        ob_start();
        require_once $wp_load;
        ob_end_clean();
        
        echo "WordPress loaded successfully.\n";
        echo "Site URL: " . get_option( 'siteurl' ) . "\n";
    } else {
        echo "WordPress not found at {$wp_load}. Running in standalone mode.\n";
        _load_wordpress_stubs();
    }
} else {
    echo "Running in standalone mode (LOAD_WORDPRESS=false).\n";
    _load_wordpress_stubs();
}

/**
 * Load WordPress function stubs for standalone testing
 */
function _load_wordpress_stubs() {
    // Define common WordPress constants if not defined
    if ( ! defined( 'ABSPATH' ) ) {
        define( 'ABSPATH', '/var/www/html/' );
    }
    
    if ( ! defined( 'WPINC' ) ) {
        define( 'WPINC', 'wp-includes' );
    }
    
    // Load our stub file
    require_once __DIR__ . '/stubs/wordpress-stubs.php';
}

/**
 * Helper function to get test fixtures
 *
 * @param string $name Fixture name
 * @return mixed Fixture data
 */
function get_test_fixture( $name ) {
    $fixture_file = __DIR__ . '/fixtures/' . $name . '.php';
    
    if ( file_exists( $fixture_file ) ) {
        return require $fixture_file;
    }
    
    return null;
}

echo "Leave Manager Test Bootstrap loaded.\n";
echo "Plugin Directory: " . LEAVE_MANAGER_PLUGIN_DIR . "\n\n";
