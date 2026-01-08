<?php
/**
 * Sample Unit Test
 *
 * This test verifies that the PHPUnit testing framework is properly configured.
 *
 * @package Leave_Manager
 * @subpackage Tests
 */

namespace LeaveManager\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Sample Test Class
 */
class SampleTest extends TestCase {

    /**
     * Test that true is true (sanity check)
     */
    public function test_true_is_true() {
        $this->assertTrue( true );
    }

    /**
     * Test that the plugin directory constant is defined
     */
    public function test_plugin_directory_defined() {
        $this->assertTrue( defined( 'LEAVE_MANAGER_PLUGIN_DIR' ) );
        $this->assertDirectoryExists( LEAVE_MANAGER_PLUGIN_DIR );
    }

    /**
     * Test that the main plugin file exists
     */
    public function test_main_plugin_file_exists() {
        $this->assertFileExists( LEAVE_MANAGER_PLUGIN_DIR . 'leave-manager.php' );
    }

    /**
     * Test WordPress stub functions are available
     */
    public function test_wordpress_stubs_loaded() {
        $this->assertTrue( function_exists( 'sanitize_text_field' ) );
        $this->assertTrue( function_exists( 'esc_html' ) );
        $this->assertTrue( function_exists( 'wp_create_nonce' ) );
    }

    /**
     * Test sanitize_text_field stub works correctly
     */
    public function test_sanitize_text_field() {
        $input = '  <script>alert("xss")</script>Hello World  ';
        $expected = 'Hello World';
        $this->assertEquals( $expected, sanitize_text_field( $input ) );
    }

    /**
     * Test nonce creation and verification
     */
    public function test_nonce_functions() {
        $action = 'test_action';
        $nonce = wp_create_nonce( $action );
        
        $this->assertNotEmpty( $nonce );
        $this->assertEquals( 1, wp_verify_nonce( $nonce, $action ) );
        $this->assertFalse( wp_verify_nonce( 'invalid_nonce', $action ) );
    }
}
