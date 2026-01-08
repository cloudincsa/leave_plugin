<?php
/**
 * Leave Request Service
 *
 * Handles business logic for leave requests, including validation,
 * approval workflows, and balance management.
 *
 * @package LeaveManager\Service
 */

namespace LeaveManager\Service;

use LeaveManager\Repository\LeaveRequestRepository;
use LeaveManager\Repository\LeaveBalanceRepository;
use LeaveManager\Repository\LeaveUserRepository;
use LeaveManager\Model\LeaveRequest;
use LeaveManager\Model\LeaveUser;

/**
 * Class LeaveRequestService
 */
class LeaveRequestService {

    /**
     * Leave request repository
     *
     * @var LeaveRequestRepository
     */
    private LeaveRequestRepository $request_repo;

    /**
     * Leave balance repository
     *
     * @var LeaveBalanceRepository
     */
    private LeaveBalanceRepository $balance_repo;

    /**
     * Leave user repository
     *
     * @var LeaveUserRepository
     */
    private LeaveUserRepository $user_repo;

    /**
     * Constructor
     *
     * @param LeaveRequestRepository|null $request_repo Request repository.
     * @param LeaveBalanceRepository|null $balance_repo Balance repository.
     * @param LeaveUserRepository|null    $user_repo    User repository.
     */
    public function __construct(
        ?LeaveRequestRepository $request_repo = null,
        ?LeaveBalanceRepository $balance_repo = null,
        ?LeaveUserRepository $user_repo = null
    ) {
        $this->request_repo = $request_repo ?? new LeaveRequestRepository();
        $this->balance_repo = $balance_repo ?? new LeaveBalanceRepository();
        $this->user_repo    = $user_repo ?? new LeaveUserRepository();
    }

    /**
     * Submit a new leave request
     *
     * @param int    $user_id    User ID.
     * @param string $leave_type Leave type.
     * @param string $start_date Start date (Y-m-d).
     * @param string $end_date   End date (Y-m-d).
     * @param string $reason     Reason for leave.
     * @return array Result with success status and message/data.
     */
    public function submit( int $user_id, string $leave_type, string $start_date, string $end_date, string $reason = '' ): array {
        // Validate user exists
        $user = $this->user_repo->find( $user_id );
        if ( ! $user ) {
            return $this->error( 'User not found.' );
        }

        // Validate dates
        $validation = $this->validateDates( $start_date, $end_date );
        if ( ! $validation['success'] ) {
            return $validation;
        }

        $days = $this->calculateBusinessDays( $start_date, $end_date );

        // Check for overlapping requests
        if ( $this->request_repo->hasOverlap( $user_id, $start_date, $end_date ) ) {
            return $this->error( 'You already have a leave request for this period.' );
        }

        // Check balance
        $balance = $this->balance_repo->findByUserAndType( $user_id, $leave_type );
        if ( $balance && ! $balance->hasEnoughBalance( $days ) ) {
            return $this->error( sprintf(
                'Insufficient leave balance. You have %.1f days available but requested %.1f days.',
                $balance->getAvailable(),
                $days
            ) );
        }

        // Create the request
        $request = $this->request_repo->create( array(
            'user_id'        => $user_id,
            'leave_type'     => $leave_type,
            'start_date'     => $start_date,
            'end_date'       => $end_date,
            'days_requested' => $days,
            'reason'         => $reason,
            'status'         => LeaveRequest::STATUS_PENDING,
        ) );

        if ( ! $request ) {
            return $this->error( 'Failed to create leave request.' );
        }

        // Add to pending balance
        if ( $balance ) {
            $this->balance_repo->addPendingDays( $user_id, $leave_type, $days );
        }

        return $this->success( 'Leave request submitted successfully.', array(
            'request_id' => $request->request_id,
            'days'       => $days,
        ) );
    }

    /**
     * Approve a leave request
     *
     * @param int $request_id Request ID.
     * @param int $approver_id Approver user ID.
     * @return array Result with success status and message.
     */
    public function approve( int $request_id, int $approver_id ): array {
        $request = $this->request_repo->find( $request_id );
        
        if ( ! $request ) {
            return $this->error( 'Leave request not found.' );
        }

        if ( ! $request->isPending() ) {
            return $this->error( 'This request has already been processed.' );
        }

        // Verify approver has permission
        $approver = $this->user_repo->find( $approver_id );
        if ( ! $approver || ! $approver->isManager() ) {
            return $this->error( 'You do not have permission to approve this request.' );
        }

        // Approve the request
        $approved = $this->request_repo->approve( $request_id, $approver_id );
        
        if ( ! $approved ) {
            return $this->error( 'Failed to approve leave request.' );
        }

        // Update balance (move from pending to used)
        $this->balance_repo->approvePendingDays(
            $request->user_id,
            $request->leave_type,
            $request->getDays()
        );

        // Trigger notification
        $this->notifyUser( $request->user_id, 'approved', $request );

        return $this->success( 'Leave request approved successfully.' );
    }

    /**
     * Reject a leave request
     *
     * @param int    $request_id Request ID.
     * @param int    $approver_id Approver user ID.
     * @param string $reason Rejection reason.
     * @return array Result with success status and message.
     */
    public function reject( int $request_id, int $approver_id, string $reason = '' ): array {
        $request = $this->request_repo->find( $request_id );
        
        if ( ! $request ) {
            return $this->error( 'Leave request not found.' );
        }

        if ( ! $request->isPending() ) {
            return $this->error( 'This request has already been processed.' );
        }

        // Verify approver has permission
        $approver = $this->user_repo->find( $approver_id );
        if ( ! $approver || ! $approver->isManager() ) {
            return $this->error( 'You do not have permission to reject this request.' );
        }

        // Reject the request
        $rejected = $this->request_repo->reject( $request_id, $approver_id, $reason );
        
        if ( ! $rejected ) {
            return $this->error( 'Failed to reject leave request.' );
        }

        // Remove from pending balance
        $this->balance_repo->removePendingDays(
            $request->user_id,
            $request->leave_type,
            $request->getDays()
        );

        // Trigger notification
        $this->notifyUser( $request->user_id, 'rejected', $request, $reason );

        return $this->success( 'Leave request rejected.' );
    }

    /**
     * Cancel a leave request (by the user)
     *
     * @param int $request_id Request ID.
     * @param int $user_id User ID (must be the request owner).
     * @return array Result with success status and message.
     */
    public function cancel( int $request_id, int $user_id ): array {
        $request = $this->request_repo->find( $request_id );
        
        if ( ! $request ) {
            return $this->error( 'Leave request not found.' );
        }

        if ( (int) $request->user_id !== $user_id ) {
            return $this->error( 'You can only cancel your own requests.' );
        }

        if ( ! $request->isPending() ) {
            return $this->error( 'Only pending requests can be cancelled.' );
        }

        // Delete the request
        $deleted = $this->request_repo->delete( $request_id );
        
        if ( ! $deleted ) {
            return $this->error( 'Failed to cancel leave request.' );
        }

        // Remove from pending balance
        $this->balance_repo->removePendingDays(
            $request->user_id,
            $request->leave_type,
            $request->getDays()
        );

        return $this->success( 'Leave request cancelled.' );
    }

    /**
     * Get leave requests for a user
     *
     * @param int    $user_id User ID.
     * @param string $status  Optional status filter.
     * @return array Array of LeaveRequest models.
     */
    public function getForUser( int $user_id, string $status = '' ): array {
        if ( ! empty( $status ) ) {
            return $this->request_repo->findAll(
                array( 'user_id' => $user_id, 'status' => $status ),
                array( 'created_at' => 'DESC' )
            );
        }
        
        return $this->request_repo->findByUser( $user_id );
    }

    /**
     * Get pending requests for approval
     *
     * @param int $approver_id Approver user ID.
     * @return array Array of LeaveRequest models.
     */
    public function getPendingForApproval( int $approver_id ): array {
        return $this->request_repo->findPendingForApprover( $approver_id );
    }

    /**
     * Validate date range
     *
     * @param string $start_date Start date.
     * @param string $end_date   End date.
     * @return array Validation result.
     */
    private function validateDates( string $start_date, string $end_date ): array {
        $start = strtotime( $start_date );
        $end   = strtotime( $end_date );
        $today = strtotime( 'today' );

        if ( ! $start || ! $end ) {
            return $this->error( 'Invalid date format.' );
        }

        if ( $start < $today ) {
            return $this->error( 'Start date cannot be in the past.' );
        }

        if ( $end < $start ) {
            return $this->error( 'End date must be after start date.' );
        }

        return $this->success( 'Dates are valid.' );
    }

    /**
     * Calculate business days between two dates
     *
     * @param string $start_date Start date.
     * @param string $end_date   End date.
     * @return float Number of business days.
     */
    private function calculateBusinessDays( string $start_date, string $end_date ): float {
        $start = new \DateTime( $start_date );
        $end   = new \DateTime( $end_date );
        $days  = 0;

        while ( $start <= $end ) {
            $day_of_week = (int) $start->format( 'N' );
            if ( $day_of_week < 6 ) { // Monday to Friday
                $days++;
            }
            $start->modify( '+1 day' );
        }

        return (float) $days;
    }

    /**
     * Notify user about request status change
     *
     * @param int          $user_id User ID.
     * @param string       $status  New status.
     * @param LeaveRequest $request Leave request.
     * @param string       $reason  Optional reason.
     */
    private function notifyUser( int $user_id, string $status, LeaveRequest $request, string $reason = '' ): void {
        // This would integrate with the email handler
        // For now, we'll use WordPress hooks
        do_action( 'leave_manager_request_status_changed', $user_id, $status, $request, $reason );
    }

    /**
     * Create a success response
     *
     * @param string $message Success message.
     * @param array  $data    Additional data.
     * @return array
     */
    private function success( string $message, array $data = array() ): array {
        return array_merge( array(
            'success' => true,
            'message' => $message,
        ), $data );
    }

    /**
     * Create an error response
     *
     * @param string $message Error message.
     * @return array
     */
    private function error( string $message ): array {
        return array(
            'success' => false,
            'message' => $message,
        );
    }
}
