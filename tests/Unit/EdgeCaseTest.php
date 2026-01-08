<?php
/**
 * Edge Case Tests
 *
 * Tests for boundary conditions and edge cases.
 *
 * @package Leave_Manager
 */

namespace LeaveManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use LeaveManager\Model\LeaveRequest;
use LeaveManager\Model\LeaveUser;
use LeaveManager\Model\LeaveBalance;
use LeaveManager\Security\InputValidator;

class EdgeCaseTest extends TestCase {

    /**
     * Test LeaveRequest with zero days
     */
    public function testLeaveRequestZeroDays() {
        $request = new LeaveRequest();
        $request->fill([
            'start_date' => '2025-01-15',
            'end_date'   => '2025-01-15',
            'half_day'   => true,
        ]);

        $this->assertEquals(0.5, $request->getDays());
    }

    /**
     * Test LeaveRequest spanning multiple months
     */
    public function testLeaveRequestMultipleMonths() {
        $request = new LeaveRequest();
        $request->fill([
            'start_date' => '2025-01-28',
            'end_date'   => '2025-02-03',
        ]);

        $this->assertEquals(7, $request->getDays());
    }

    /**
     * Test LeaveRequest spanning year boundary
     */
    public function testLeaveRequestYearBoundary() {
        $request = new LeaveRequest();
        $request->fill([
            'start_date' => '2024-12-30',
            'end_date'   => '2025-01-02',
        ]);

        $this->assertEquals(4, $request->getDays());
    }

    /**
     * Test LeaveUser with empty name
     */
    public function testLeaveUserEmptyName() {
        $user = new LeaveUser();
        $user->fill([
            'first_name' => '',
            'last_name'  => '',
        ]);

        $this->assertEquals('', $user->getFullName());
    }

    /**
     * Test LeaveUser with only first name
     */
    public function testLeaveUserOnlyFirstName() {
        $user = new LeaveUser();
        $user->fill([
            'first_name' => 'John',
            'last_name'  => '',
        ]);

        $this->assertEquals('John', trim($user->getFullName()));
    }

    /**
     * Test LeaveBalance with negative values
     */
    public function testLeaveBalanceNegativeUsed() {
        $balance = new LeaveBalance();
        $balance->fill([
            'total_days' => 20,
            'used_days'  => -5,
        ]);

        $this->assertEquals(25, $balance->getRemainingDays());
    }

    /**
     * Test LeaveBalance with zero total
     */
    public function testLeaveBalanceZeroTotal() {
        $balance = new LeaveBalance();
        $balance->fill([
            'total_days' => 0,
            'used_days'  => 0,
        ]);

        $this->assertEquals(0, $balance->getRemainingDays());
        $this->assertFalse($balance->hasBalance(1));
    }

    /**
     * Test LeaveRequest cancellation eligibility - already cancelled
     */
    public function testCannotCancelAlreadyCancelled() {
        $request = new LeaveRequest();
        $request->fill([
            'status' => 'cancelled',
        ]);

        $this->assertFalse($request->canBeCancelled());
    }

    /**
     * Test LeaveRequest cancellation eligibility - rejected
     */
    public function testCannotCancelRejected() {
        $request = new LeaveRequest();
        $request->fill([
            'status' => 'rejected',
        ]);

        $this->assertFalse($request->canBeCancelled());
    }

    /**
     * Test LeaveRequest cancellation eligibility - pending
     */
    public function testCanCancelPending() {
        $request = new LeaveRequest();
        $request->fill([
            'status'     => 'pending',
            'start_date' => date('Y-m-d', strtotime('+7 days')),
        ]);

        $this->assertTrue($request->canBeCancelled());
    }

    /**
     * Test LeaveRequest cancellation eligibility - approved but started
     */
    public function testCannotCancelStartedLeave() {
        $request = new LeaveRequest();
        $request->fill([
            'status'     => 'approved',
            'start_date' => date('Y-m-d', strtotime('-1 day')),
        ]);

        $this->assertFalse($request->canBeCancelled());
    }

    /**
     * Test LeaveRequest with same start and end date
     */
    public function testLeaveRequestSameDayFullDay() {
        $request = new LeaveRequest();
        $request->fill([
            'start_date' => '2025-01-15',
            'end_date'   => '2025-01-15',
            'half_day'   => false,
        ]);

        $this->assertEquals(1, $request->getDays());
    }

    /**
     * Test LeaveBalance exact boundary
     */
    public function testLeaveBalanceExactBoundary() {
        $balance = new LeaveBalance();
        $balance->fill([
            'total_days' => 20,
            'used_days'  => 19,
        ]);

        $this->assertTrue($balance->hasBalance(1));
        $this->assertFalse($balance->hasBalance(2));
    }

    /**
     * Test LeaveUser special characters in name
     */
    public function testLeaveUserSpecialCharacters() {
        $user = new LeaveUser();
        $user->fill([
            'first_name' => "O'Brien",
            'last_name'  => 'Van Der Berg',
        ]);

        $this->assertEquals("O'Brien Van Der Berg", $user->getFullName());
    }

    /**
     * Test LeaveRequest very long duration
     */
    public function testLeaveRequestLongDuration() {
        $request = new LeaveRequest();
        $request->fill([
            'start_date' => '2025-01-01',
            'end_date'   => '2025-12-31',
        ]);

        $this->assertEquals(365, $request->getDays());
    }
}
