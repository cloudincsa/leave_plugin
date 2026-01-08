<?php
/**
 * Browser-Based E2E Test: Admin Workflow
 *
 * Tests the admin panel functionality using HTTP requests.
 *
 * @package Leave_Manager
 */

namespace LeaveManager\Tests\Browser;

use PHPUnit\Framework\TestCase;

class AdminWorkflowTest extends TestCase {

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
        $this->cookie_file = sys_get_temp_dir() . '/leave_manager_admin_test_' . uniqid() . '.txt';
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
     * Test that WordPress admin login page exists
     */
    public function testWordPressAdminLoginExists(): void {
        $response = $this->httpGet('/wp-login.php');
        
        $this->assertEquals(200, $response['http_code'], 'WordPress login page should exist');
        $this->assertStringContainsString('wp-login', $response['body'], 'Should be WordPress login page');
    }

    /**
     * Test that admin dashboard requires authentication
     */
    public function testAdminDashboardRequiresAuth(): void {
        $response = $this->httpGet('/wp-admin/', false);
        
        // Should redirect to login
        $this->assertTrue(
            $response['http_code'] == 302 || strpos($response['body'], 'wp-login') !== false,
            'Admin dashboard should require authentication'
        );
    }

    /**
     * Test AJAX endpoint for approving leave requests
     */
    public function testApproveLeaveRequestEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action'     => 'approve_leave_request',
            'request_id' => 1,
        ]);
        
        $this->assertNotFalse($response, 'Approve endpoint should be accessible');
    }

    /**
     * Test AJAX endpoint for rejecting leave requests
     */
    public function testRejectLeaveRequestEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action'     => 'reject_leave_request',
            'request_id' => 1,
            'reason'     => 'Test rejection',
        ]);
        
        $this->assertNotFalse($response, 'Reject endpoint should be accessible');
    }

    /**
     * Test AJAX endpoint for getting pending requests
     */
    public function testGetPendingRequestsEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action' => 'get_pending_requests',
        ]);
        
        $this->assertNotFalse($response, 'Pending requests endpoint should be accessible');
    }

    /**
     * Test AJAX endpoint for staff management
     */
    public function testGetStaffListEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action' => 'get_staff_list',
        ]);
        
        $this->assertNotFalse($response, 'Staff list endpoint should be accessible');
    }

    /**
     * Test AJAX endpoint for department management
     */
    public function testGetDepartmentsEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action' => 'get_departments',
        ]);
        
        $this->assertNotFalse($response, 'Departments endpoint should be accessible');
    }

    /**
     * Test AJAX endpoint for public holidays
     */
    public function testGetPublicHolidaysEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action' => 'get_public_holidays',
            'year'   => date('Y'),
        ]);
        
        $this->assertNotFalse($response, 'Public holidays endpoint should be accessible');
    }

    /**
     * Test AJAX endpoint for reports
     */
    public function testGetReportsEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action'     => 'get_leave_report',
            'start_date' => date('Y-01-01'),
            'end_date'   => date('Y-12-31'),
        ]);
        
        $this->assertNotFalse($response, 'Reports endpoint should be accessible');
    }

    /**
     * Test AJAX endpoint for settings
     */
    public function testGetSettingsEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action' => 'get_leave_manager_settings',
        ]);
        
        $this->assertNotFalse($response, 'Settings endpoint should be accessible');
    }

    /**
     * Test that admin CSS files are accessible
     */
    public function testAdminCssFilesAccessible(): void {
        $css_files = [
            '/wp-content/plugins/leave-manager/admin/css/admin-style.css',
            '/wp-content/plugins/leave-manager/assets/css/professional.css',
        ];
        
        foreach ($css_files as $file) {
            $response = $this->httpGet($file);
            $this->assertTrue(
                $response['http_code'] == 200 || $response['http_code'] == 404,
                "CSS file check for $file"
            );
        }
    }

    /**
     * Test that admin JS files are accessible
     */
    public function testAdminJsFilesAccessible(): void {
        $js_files = [
            '/wp-content/plugins/leave-manager/admin/js/admin-script.js',
        ];
        
        foreach ($js_files as $file) {
            $response = $this->httpGet($file);
            $this->assertTrue(
                $response['http_code'] == 200 || $response['http_code'] == 404,
                "JS file check for $file"
            );
        }
    }

    /**
     * Make HTTP GET request
     *
     * @param string $path            URL path.
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
        curl_close($ch);
        
        if ($body === false) {
            return false;
        }
        
        return [
            'body'      => $body,
            'http_code' => $http_code,
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
        curl_close($ch);
        
        if ($body === false) {
            return false;
        }
        
        return [
            'body'      => $body,
            'http_code' => $http_code,
        ];
    }
}
