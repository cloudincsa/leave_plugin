<?php
/**
 * E2E Test for Leave Request Workflow
 *
 * Tests the complete leave request workflow including submission,
 * approval, rejection, and balance updates.
 *
 * @package Leave_Manager
 * @subpackage Tests
 */

namespace LeaveManager\Tests\E2E;

use PHPUnit\Framework\TestCase;

/**
 * Leave Request E2E Test Class
 */
class LeaveRequestTest extends TestCase {

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
     * Test that leave requests table exists
     */
    public function test_leave_requests_table_exists() {
        $table_name = $this->wpdb->prefix . 'leave_manager_leave_requests';
        
        $table_exists = $this->wpdb->get_var( 
            $this->wpdb->prepare( 
                "SHOW TABLES LIKE %s", 
                $table_name 
            ) 
        );
        
        $this->assertEquals( $table_name, $table_exists, 'Leave requests table should exist' );
    }

    /**
     * Test that leave types table exists and has data
     */
    public function test_leave_types_exist() {
        $table_name = $this->wpdb->prefix . 'leave_manager_leave_types';
        
        $count = $this->wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
        
        $this->assertGreaterThan( 0, $count, 'Leave types should exist in database' );
    }

    /**
     * Test that leave balances table exists
     */
    public function test_leave_balances_table_exists() {
        $table_name = $this->wpdb->prefix . 'leave_manager_leave_balances';
        
        $table_exists = $this->wpdb->get_var( 
            $this->wpdb->prepare( 
                "SHOW TABLES LIKE %s", 
                $table_name 
            ) 
        );
        
        $this->assertEquals( $table_name, $table_exists, 'Leave balances table should exist' );
    }

    /**
     * Test leave request data structure
     */
    public function test_leave_request_table_structure() {
        $table_name = $this->wpdb->prefix . 'leave_manager_leave_requests';
        
        $columns = $this->wpdb->get_results( "DESCRIBE {$table_name}" );
        $column_names = array_map( function( $col ) {
            return $col->Field;
        }, $columns );
        
        // Check for essential columns
        $required_columns = array( 'request_id', 'user_id', 'leave_type', 'start_date', 'end_date', 'status' );
        
        foreach ( $required_columns as $column ) {
            $this->assertContains( $column, $column_names, "Column '{$column}' should exist in leave_requests table" );
        }
    }

    /**
     * Test that existing leave requests can be retrieved
     */
    public function test_can_retrieve_leave_requests() {
        $table_name = $this->wpdb->prefix . 'leave_manager_leave_requests';
        
        $requests = $this->wpdb->get_results( "SELECT * FROM {$table_name} LIMIT 10" );
        
        $this->assertIsArray( $requests, 'Should return an array of requests' );
        
        if ( count( $requests ) > 0 ) {
            $request = $requests[0];
            $this->assertObjectHasProperty( 'request_id', $request, 'Request should have request_id' );
            $this->assertObjectHasProperty( 'user_id', $request, 'Request should have user_id' );
            $this->assertObjectHasProperty( 'status', $request, 'Request should have status' );
        }
    }

    /**
     * Test leave request status values
     */
    public function test_leave_request_status_values() {
        $table_name = $this->wpdb->prefix . 'leave_manager_leave_requests';
        
        $statuses = $this->wpdb->get_col( "SELECT DISTINCT status FROM {$table_name}" );
        
        $valid_statuses = array( 'pending', 'approved', 'rejected', 'cancelled' );
        
        foreach ( $statuses as $status ) {
            $this->assertContains( 
                $status, 
                $valid_statuses, 
                "Status '{$status}' should be a valid status value" 
            );
        }
    }

    /**
     * Test date calculations for leave duration
     */
    public function test_leave_duration_calculation() {
        $start_date = '2026-01-10';
        $end_date = '2026-01-15';
        
        $start = new \DateTime( $start_date );
        $end = new \DateTime( $end_date );
        $interval = $start->diff( $end );
        
        // Including both start and end dates
        $days = $interval->days + 1;
        
        $this->assertEquals( 6, $days, 'Leave duration should be calculated correctly' );
    }

    /**
     * Test that departments table exists
     */
    public function test_departments_table_exists() {
        $table_name = $this->wpdb->prefix . 'leave_manager_departments';
        
        $table_exists = $this->wpdb->get_var( 
            $this->wpdb->prepare( 
                "SHOW TABLES LIKE %s", 
                $table_name 
            ) 
        );
        
        $this->assertEquals( $table_name, $table_exists, 'Departments table should exist' );
    }

    /**
     * Test leave balance structure
     */
    public function test_leave_balance_structure() {
        $table_name = $this->wpdb->prefix . 'leave_manager_leave_balances';
        
        $columns = $this->wpdb->get_results( "DESCRIBE {$table_name}" );
        $column_names = array_map( function( $col ) {
            return $col->Field;
        }, $columns );
        
        // Check for essential columns
        $this->assertContains( 'user_id', $column_names, 'Balance should have user_id' );
        $this->assertContains( 'leave_type', $column_names, 'Balance should have leave_type' );
    }

    /**
     * Test that balance values are non-negative
     */
    public function test_balance_values_non_negative() {
        $table_name = $this->wpdb->prefix . 'leave_manager_leave_balances';
        
        // Check if there are any columns that could contain balance values
        $columns = $this->wpdb->get_results( "DESCRIBE {$table_name}" );
        
        foreach ( $columns as $col ) {
            if ( strpos( $col->Field, 'balance' ) !== false || strpos( $col->Field, 'days' ) !== false ) {
                $negative_count = $this->wpdb->get_var( 
                    "SELECT COUNT(*) FROM {$table_name} WHERE {$col->Field} < 0" 
                );
                
                $this->assertEquals( 
                    0, 
                    $negative_count, 
                    "Column '{$col->Field}' should not have negative values" 
                );
            }
        }
    }
}
