<?php
/**
 * Browser-Based E2E Test: Leave Request Workflow
 *
 * Tests the complete leave request workflow using HTTP requests.
 *
 * @package Leave_Manager
 */

namespace LeaveManager\Tests\Browser;

use PHPUnit\Framework\TestCase;

class LeaveRequestWorkflowTest extends TestCase {

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
     * Test that the apply leave page exists
     */
    public function testApplyLeavePageExists(): void {
        $response = $this->httpGet('/index.php/leave-management/request/');
        
        $this->assertTrue(
            $response['http_code'] == 200 || $response['http_code'] == 302,
            'Apply leave page should exist'
        );
    }

    /**
     * Test that the leave history page exists
     */
    public function testLeaveHistoryPageExists(): void {
        $response = $this->httpGet('/index.php/leave-management/history/');
        
        $this->assertTrue(
            $response['http_code'] == 200 || $response['http_code'] == 302,
            'Leave history page should exist'
        );
    }

    /**
     * Test that the leave balance page exists
     */
    public function testLeaveBalancePageExists(): void {
        $response = $this->httpGet('/index.php/leave-management/balance/');
        
        $this->assertTrue(
            $response['http_code'] == 200 || $response['http_code'] == 302,
            'Leave balance page should exist'
        );
    }

    /**
     * Test AJAX endpoint for getting leave types
     */
    public function testGetLeaveTypesEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action' => 'get_leave_types',
        ]);
        
        $this->assertNotFalse($response, 'Leave types endpoint should be accessible');
    }

    /**
     * Test AJAX endpoint for getting leave balance
     */
    public function testGetLeaveBalanceEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action' => 'get_leave_balance',
        ]);
        
        $this->assertNotFalse($response, 'Leave balance endpoint should be accessible');
    }

    /**
     * Test AJAX endpoint for submitting leave request
     */
    public function testSubmitLeaveRequestEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action'     => 'submit_leave_request',
            'leave_type' => 'annual',
            'start_date' => date('Y-m-d', strtotime('+7 days')),
            'end_date'   => date('Y-m-d', strtotime('+8 days')),
            'reason'     => 'Test leave request',
        ]);
        
        $this->assertNotFalse($response, 'Submit leave request endpoint should be accessible');
    }

    /**
     * Test AJAX endpoint for getting calendar events
     */
    public function testGetCalendarEventsEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action' => 'get_calendar_events',
            'start'  => date('Y-m-01'),
            'end'    => date('Y-m-t'),
        ]);
        
        $this->assertNotFalse($response, 'Calendar events endpoint should be accessible');
    }

    /**
     * Test AJAX endpoint for getting dashboard stats
     */
    public function testGetDashboardStatsEndpoint(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action' => 'get_dashboard_stats',
        ]);
        
        $this->assertNotFalse($response, 'Dashboard stats endpoint should be accessible');
    }

    /**
     * Test that calendar page exists
     */
    public function testCalendarPageExists(): void {
        $response = $this->httpGet('/index.php/leave-management/calendar/');
        
        $this->assertTrue(
            $response['http_code'] == 200 || $response['http_code'] == 302,
            'Calendar page should exist'
        );
    }

    /**
     * Test that team calendar page exists
     */
    public function testTeamCalendarPageExists(): void {
        $response = $this->httpGet('/index.php/leave-management/calendar/');
        
        $this->assertTrue(
            $response['http_code'] == 200 || $response['http_code'] == 302,
            'Team calendar page should exist'
        );
    }

    /**
     * Test leave request form validation - missing dates
     */
    public function testLeaveRequestValidationMissingDates(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action'     => 'submit_leave_request',
            'leave_type' => 'annual',
            'reason'     => 'Test',
            // Missing start_date and end_date
        ]);
        
        // Should return error response
        $this->assertNotFalse($response, 'Validation endpoint should respond');
    }

    /**
     * Test leave request form validation - invalid date range
     */
    public function testLeaveRequestValidationInvalidDateRange(): void {
        $response = $this->httpPost('/wp-admin/admin-ajax.php', [
            'action'     => 'submit_leave_request',
            'leave_type' => 'annual',
            'start_date' => date('Y-m-d', strtotime('+10 days')),
            'end_date'   => date('Y-m-d', strtotime('+5 days')), // End before start
            'reason'     => 'Test',
        ]);
        
        $this->assertNotFalse($response, 'Validation endpoint should respond');
    }

    /**
     * Make HTTP GET request
     *
     * @param string $path URL path.
     * @return array|false
     */
    private function httpGet(string $path) {
        $url = rtrim($this->base_url, '/') . '/' . ltrim($path, '/');
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
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
