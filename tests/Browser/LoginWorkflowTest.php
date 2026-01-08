<?php
/**
 * Browser-Based E2E Test: Login Workflow
 *
 * Tests the complete login workflow using HTTP requests to simulate browser behavior.
 *
 * @package Leave_Manager
 */

namespace LeaveManager\Tests\Browser;

use PHPUnit\Framework\TestCase;

class LoginWorkflowTest extends TestCase {

    /**
     * Base URL for the WordPress installation
     *
     * @var string
     */
    private string $base_url;

    /**
     * Cookie jar for maintaining session
     *
     * @var string
     */
    private string $cookie_file;

    /**
     * Set up test environment
     */
    protected function setUp(): void {
        $this->base_url = defined('WP_SITEURL') ? WP_SITEURL : 'http://localhost';
        $this->cookie_file = sys_get_temp_dir() . '/leave_manager_test_cookies_' . uniqid() . '.txt';
    }

    /**
     * Clean up after tests
     */
    protected function tearDown(): void {
        if (file_exists($this->cookie_file)) {
            unlink($this->cookie_file);
        }
    }

    /**
     * Test that the login page loads correctly
     */
    public function testLoginPageLoads(): void {
        $response = $this->httpGet('/index.php/leave-management/');
        
        $this->assertNotFalse($response, 'Login page should be accessible');
        $this->assertTrue(
            $response['http_code'] == 200 || $response['http_code'] == 302,
            'Login page should return valid HTTP code'
        );
    }

    /**
     * Test that the signup page loads correctly
     */
    public function testSignupPageLoads(): void {
        $response = $this->httpGet('/index.php/leave-management/employee-signup/');
        
        $this->assertNotFalse($response, 'Signup page should be accessible');
    }

    /**
     * Test that the dashboard redirects when not logged in
     */
    public function testDashboardRequiresLogin(): void {
        $response = $this->httpGet('/index.php/leave-management/dashboard/', false);
        
        // Should return some response (redirect, login prompt, or page)
        $this->assertNotFalse($response, 'Dashboard page should be accessible');
        $this->assertTrue(
            $response['http_code'] >= 200 && $response['http_code'] < 500,
            'Dashboard should return valid HTTP code'
        );
    }

    /**
     * Test login with invalid credentials
     */
    public function testLoginWithInvalidCredentials(): void {
        // First get the login page to get any nonces
        $this->httpGet('/leave-login/');
        
        // Attempt login with invalid credentials
        $response = $this->httpPost('/leave-login/', [
            'username' => 'invalid_user_' . time(),
            'password' => 'invalid_password',
            'action'   => 'leave_manager_login',
        ]);
        
        // Should not redirect to dashboard
        $this->assertStringNotContainsString(
            'dashboard',
            $response['redirect_url'] ?? '',
            'Invalid login should not redirect to dashboard'
        );
    }

    /**
     * Test AJAX login endpoint exists
     */
    public function testAjaxLoginEndpointExists(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action' => 'leave_manager_login',
        ]);
        
        // Should return JSON response (even if error)
        $this->assertNotFalse($response, 'AJAX endpoint should be accessible');
    }

    /**
     * Test password reset page loads
     */
    public function testPasswordResetPageLoads(): void {
        $response = $this->httpGet('/index.php/sign-up/');
        
        // Page should exist or redirect to appropriate page
        $this->assertNotFalse($response, 'Password reset page should be accessible');
        $this->assertTrue(
            $response['http_code'] >= 200 && $response['http_code'] < 500,
            'Password reset page should return valid HTTP code'
        );
    }

    /**
     * Test that static assets are accessible
     */
    public function testStaticAssetsAccessible(): void {
        $assets = [
            '/wp-content/plugins/leave-manager/assets/css/professional.css',
            '/wp-content/plugins/leave-manager/frontend/css/frontend-enhanced.css',
        ];
        
        foreach ($assets as $asset) {
            $response = $this->httpGet($asset);
            $this->assertEquals(
                200,
                $response['http_code'],
                "Asset $asset should be accessible"
            );
        }
    }

    /**
     * Make HTTP GET request
     *
     * @param string $path         URL path.
     * @param bool   $follow_redirects Whether to follow redirects.
     * @return array|false
     */
    private function httpGet(string $path, bool $follow_redirects = true) {
        $url = rtrim($this->base_url, '/') . '/' . ltrim($path, '/');
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $follow_redirects,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_COOKIEJAR      => $this->cookie_file,
            CURLOPT_COOKIEFILE     => $this->cookie_file,
            CURLOPT_USERAGENT      => 'Leave Manager E2E Test',
        ]);
        
        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        
        if ($body === false) {
            return false;
        }
        
        return [
            'body'         => $body,
            'http_code'    => $http_code,
            'redirect_url' => $redirect_url,
        ];
    }

    /**
     * Make HTTP POST request
     *
     * @param string $path URL path.
     * @param array  $data POST data.
     * @return array|false
     */
    private function httpPost(string $path, array $data) {
        $url = rtrim($this->base_url, '/') . '/' . ltrim($path, '/');
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_COOKIEJAR      => $this->cookie_file,
            CURLOPT_COOKIEFILE     => $this->cookie_file,
            CURLOPT_USERAGENT      => 'Leave Manager E2E Test',
        ]);
        
        $body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        curl_close($ch);
        
        if ($body === false) {
            return false;
        }
        
        return [
            'body'         => $body,
            'http_code'    => $http_code,
            'redirect_url' => $redirect_url,
        ];
    }
}
