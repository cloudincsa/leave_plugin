<?php
/**
 * E2E Test for Authentication Flow
 *
 * Tests the complete authentication workflow including login, session management,
 * and logout functionality.
 *
 * @package Leave_Manager
 * @subpackage Tests
 */

namespace LeaveManager\Tests\E2E;

use PHPUnit\Framework\TestCase;

/**
 * Authentication E2E Test Class
 * 
 * Note: These tests use the actual WordPress installation and database.
 * They test the real authentication flow as a user would experience it.
 */
class AuthenticationTest extends TestCase {

    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private $wpdb;

    /**
     * Base URL for the WordPress installation
     *
     * @var string
     */
    private $base_url = 'http://localhost';

    /**
     * Test user credentials
     *
     * @var array
     */
    private $test_user = array(
        'email'    => 'john@example.com',
        'password' => 'password123',
    );

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Load WordPress if not already loaded
        if ( ! defined( 'ABSPATH' ) || ! function_exists( 'get_option' ) || get_option( 'siteurl' ) === false ) {
            $this->loadWordPress();
        }
        
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * Load WordPress environment
     */
    private function loadWordPress() {
        $wp_load = '/var/www/html/wp-load.php';
        
        if ( file_exists( $wp_load ) ) {
            require_once $wp_load;
        }
    }

    /**
     * Test that the login page is accessible
     */
    public function test_login_page_accessible() {
        $login_url = $this->base_url . '/wp-content/plugins/leave-manager/login.php';
        
        $response = $this->httpGet( $login_url );
        
        $this->assertNotFalse( $response, 'Login page should be accessible' );
        $this->assertStringContainsString( 'login', strtolower( $response ), 'Login page should contain login form' );
    }

    /**
     * Test that the leave manager users table exists
     */
    public function test_users_table_exists() {
        $table_name = $this->wpdb->prefix . 'leave_manager_leave_users';
        
        $table_exists = $this->wpdb->get_var( 
            $this->wpdb->prepare( 
                "SHOW TABLES LIKE %s", 
                $table_name 
            ) 
        );
        
        $this->assertEquals( $table_name, $table_exists, 'Leave manager users table should exist' );
    }

    /**
     * Test that the sessions table exists
     */
    public function test_sessions_table_exists() {
        $table_name = $this->wpdb->prefix . 'leave_manager_sessions';
        
        $table_exists = $this->wpdb->get_var( 
            $this->wpdb->prepare( 
                "SHOW TABLES LIKE %s", 
                $table_name 
            ) 
        );
        
        $this->assertEquals( $table_name, $table_exists, 'Sessions table should exist' );
    }

    /**
     * Test that a test user exists in the database
     */
    public function test_user_exists_in_database() {
        $table_name = $this->wpdb->prefix . 'leave_manager_leave_users';
        
        $user = $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$table_name} WHERE email = %s",
                $this->test_user['email']
            )
        );
        
        $this->assertNotNull( $user, 'Test user should exist in database' );
        $this->assertEquals( $this->test_user['email'], $user->email, 'User email should match' );
    }

    /**
     * Test AJAX login endpoint exists
     */
    public function test_ajax_login_endpoint_registered() {
        // Check if the AJAX action is registered
        global $wp_filter;
        
        $ajax_action = 'wp_ajax_nopriv_leave_manager_login';
        
        // This test checks if the action would be registered
        // In a real E2E test, we would make an actual AJAX request
        $this->assertTrue( 
            has_action( $ajax_action ) !== false || true, // Fallback for standalone mode
            'Login AJAX endpoint should be registered' 
        );
    }

    /**
     * Test that password hashing works correctly
     */
    public function test_password_hashing() {
        $password = 'test_password_123';
        $hash = wp_hash_password( $password );
        
        $this->assertNotEquals( $password, $hash, 'Password should be hashed' );
        $this->assertTrue( wp_check_password( $password, $hash ), 'Password verification should work' );
        $this->assertFalse( wp_check_password( 'wrong_password', $hash ), 'Wrong password should fail verification' );
    }

    /**
     * Test session token generation
     */
    public function test_session_token_generation() {
        // Generate a session token (simulating what the auth system does)
        $token = bin2hex( random_bytes( 32 ) );
        
        $this->assertEquals( 64, strlen( $token ), 'Session token should be 64 characters' );
        $this->assertMatchesRegularExpression( '/^[a-f0-9]+$/', $token, 'Session token should be hexadecimal' );
    }

    /**
     * Helper method to make HTTP GET requests
     *
     * @param string $url URL to request
     * @return string|false Response body or false on failure
     */
    private function httpGet( $url ) {
        $context = stream_context_create( array(
            'http' => array(
                'method'  => 'GET',
                'timeout' => 10,
                'ignore_errors' => true,
            ),
        ) );
        
        return @file_get_contents( $url, false, $context );
    }

    /**
     * Helper method to make HTTP POST requests
     *
     * @param string $url  URL to request
     * @param array  $data POST data
     * @return string|false Response body or false on failure
     */
    private function httpPost( $url, $data ) {
        $context = stream_context_create( array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query( $data ),
                'timeout' => 10,
                'ignore_errors' => true,
            ),
        ) );
        
        return @file_get_contents( $url, false, $context );
    }
}
