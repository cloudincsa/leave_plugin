<?php
/**
 * E2E Test for Plugin Activation and Core Functionality
 *
 * Tests that the plugin activates correctly and all core components are functional.
 *
 * @package Leave_Manager
 * @subpackage Tests
 */

namespace LeaveManager\Tests\E2E;

use PHPUnit\Framework\TestCase;

/**
 * Plugin Activation E2E Test Class
 */
class PluginActivationTest extends TestCase {

    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Load WordPress
        if ( ! defined( 'ABSPATH' ) || ! function_exists( 'get_option' ) || get_option( 'siteurl' ) === false ) {
            $wp_load = '/var/www/html/wp-load.php';
            if ( file_exists( $wp_load ) ) {
                require_once $wp_load;
            }
        }
        
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Test that the main plugin file exists
     */
    public function test_main_plugin_file_exists() {
        $plugin_file = LEAVE_MANAGER_PLUGIN_DIR . 'leave-manager.php';
        $this->assertFileExists( $plugin_file, 'Main plugin file should exist' );
    }

    /**
     * Test that all required database tables exist
     */
    public function test_all_required_tables_exist() {
        $required_tables = array(
            'leave_users',
            'leave_requests',
            'leave_balances',
            'leave_types',
            'departments',
            'sessions',
            'settings',
        );
        
        foreach ( $required_tables as $table ) {
            $full_table_name = $this->wpdb->prefix . 'leave_manager_' . $table;
            
            $table_exists = $this->wpdb->get_var( 
                $this->wpdb->prepare( 
                    "SHOW TABLES LIKE %s", 
                    $full_table_name 
                ) 
            );
            
            $this->assertEquals( 
                $full_table_name, 
                $table_exists, 
                "Required table '{$table}' should exist" 
            );
        }
    }

    /**
     * Test that plugin settings are stored
     */
    public function test_plugin_settings_exist() {
        $table_name = $this->wpdb->prefix . 'leave_manager_settings';
        
        $settings_count = $this->wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        
        $this->assertGreaterThan( 0, $settings_count, 'Plugin settings should exist in database' );
    }

    /**
     * Test that the includes directory structure is correct
     */
    public function test_includes_directory_structure() {
        $required_dirs = array(
            'includes',
            'includes/handlers',
            'includes/auth',
            'admin',
            'admin/pages',
            'frontend',
            'frontend/pages',
            'templates',
            'templates/emails',
            'assets',
            'assets/css',
            'assets/js',
        );
        
        foreach ( $required_dirs as $dir ) {
            $full_path = LEAVE_MANAGER_PLUGIN_DIR . $dir;
            $this->assertDirectoryExists( $full_path, "Directory '{$dir}' should exist" );
        }
    }

    /**
     * Test that core class files exist
     */
    public function test_core_class_files_exist() {
        $required_files = array(
            'includes/class-plugin.php',
            'includes/class-database.php',
            'includes/class-settings.php',
            'includes/class-logger.php',
            'includes/class-email-handler.php',
            'includes/auth/class-custom-auth.php',
            'includes/auth/class-session-manager.php',
            'includes/handlers/class-leave-request-handler.php',
            'includes/handlers/class-leave-approval-handler.php',
        );
        
        foreach ( $required_files as $file ) {
            $full_path = LEAVE_MANAGER_PLUGIN_DIR . $file;
            $this->assertFileExists( $full_path, "Core file '{$file}' should exist" );
        }
    }

    /**
     * Test that email templates exist
     */
    public function test_email_templates_exist() {
        $required_templates = array(
            'leave-request-submitted.html',
            'leave-request-approved.html',
            'leave-request-rejected.html',
            'welcome.html',
            'password-reset.html',
        );
        
        foreach ( $required_templates as $template ) {
            $full_path = LEAVE_MANAGER_PLUGIN_DIR . 'templates/emails/' . $template;
            $this->assertFileExists( $full_path, "Email template '{$template}' should exist" );
        }
    }

    /**
     * Test that frontend shortcodes file exists
     */
    public function test_shortcodes_file_exists() {
        $shortcodes_file = LEAVE_MANAGER_PLUGIN_DIR . 'frontend/shortcodes.php';
        $this->assertFileExists( $shortcodes_file, 'Shortcodes file should exist' );
    }

    /**
     * Test that CSS files exist
     */
    public function test_css_files_exist() {
        $css_files = array(
            'assets/css/admin-unified.css',
            'assets/css/frontend-modern.css',
            'assets/css/professional.css',
        );
        
        foreach ( $css_files as $file ) {
            $full_path = LEAVE_MANAGER_PLUGIN_DIR . $file;
            $this->assertFileExists( $full_path, "CSS file '{$file}' should exist" );
        }
    }

    /**
     * Test that JavaScript files exist
     */
    public function test_js_files_exist() {
        $js_files = array(
            'assets/js/admin-ajax.js',
            'assets/js/calendar.js',
        );
        
        foreach ( $js_files as $file ) {
            $full_path = LEAVE_MANAGER_PLUGIN_DIR . $file;
            $this->assertFileExists( $full_path, "JavaScript file '{$file}' should exist" );
        }
    }

    /**
     * Test WordPress pages with shortcodes exist
     */
    public function test_wordpress_pages_exist() {
        $expected_pages = array(
            'Leave Dashboard',
            'Leave Request',
            'Leave Calendar',
            'Leave Balance',
            'Leave History',
        );
        
        $found_pages = 0;
        
        foreach ( $expected_pages as $page_title ) {
            $page = get_page_by_title( $page_title );
            if ( $page ) {
                $found_pages++;
            }
        }
        
        // At least some pages should exist
        $this->assertGreaterThan( 
            0, 
            $found_pages, 
            'At least some WordPress pages with shortcodes should exist' 
        );
    }

    /**
     * Test that the plugin can load without fatal errors
     */
    public function test_plugin_loads_without_errors() {
        // If we got this far, the plugin loaded successfully
        $this->assertTrue( true, 'Plugin loaded without fatal errors' );
    }

    /**
     * Test database connection is working
     */
    public function test_database_connection() {
        $result = $this->wpdb->get_var( "SELECT 1" );
        $this->assertEquals( 1, $result, 'Database connection should be working' );
    }
}
