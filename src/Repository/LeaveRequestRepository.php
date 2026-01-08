<?php
/**
 * Leave Request Repository
 *
 * Handles all database operations for leave requests.
 *
 * @package LeaveManager\Repository
 */

namespace LeaveManager\Repository;

use LeaveManager\Model\LeaveRequest;

/**
 * Class LeaveRequestRepository
 */
class LeaveRequestRepository extends AbstractRepository {

    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected string $table = 'leave_requests';

    /**
     * Primary key column name
     *
     * @var string
     */
    protected string $primary_key = 'request_id';

    /**
     * Model class name
     *
     * @var string
     */
    protected string $model_class = LeaveRequest::class;

    /**
     * Find all requests for a specific user
     *
     * @param int   $user_id User ID.
     * @param array $order_by Order by columns.
     * @return array Array of LeaveRequest models.
     */
    public function findByUser( int $user_id, array $order_by = array( 'created_at' => 'DESC' ) ): array {
        return $this->findAll( array( 'user_id' => $user_id ), $order_by );
    }

    /**
     * Find all pending requests
     *
     * @param array $order_by Order by columns.
     * @return array Array of LeaveRequest models.
     */
    public function findPending( array $order_by = array( 'created_at' => 'ASC' ) ): array {
        return $this->findAll( array( 'status' => LeaveRequest::STATUS_PENDING ), $order_by );
    }

    /**
     * Find pending requests for a specific approver (manager)
     *
     * @param int $approver_id Approver user ID.
     * @return array Array of LeaveRequest models.
     */
    public function findPendingForApprover( int $approver_id ): array {
        $table = $this->getTableName();
        $users_table = $this->db->getTableName( 'leave_users' );
        
        // Note: The schema uses 'department' (varchar) instead of 'department_id' (foreign key)
        // This is a schema design issue that should be addressed in Phase 2.5
        $query = $this->db->prepare(
            "SELECT r.* FROM {$table} r
             INNER JOIN {$users_table} u ON r.user_id = u.user_id
             WHERE r.status = %s
             AND u.department IN (
                 SELECT department FROM {$users_table} 
                 WHERE user_id = %d AND role IN ('manager', 'admin')
             )
             ORDER BY r.created_at ASC",
            LeaveRequest::STATUS_PENDING,
            $approver_id
        );
        
        return $this->rawQuery( $query );
    }

    /**
     * Find requests by status
     *
     * @param string $status Request status.
     * @param array  $order_by Order by columns.
     * @return array Array of LeaveRequest models.
     */
    public function findByStatus( string $status, array $order_by = array( 'created_at' => 'DESC' ) ): array {
        return $this->findAll( array( 'status' => $status ), $order_by );
    }

    /**
     * Find requests within a date range
     *
     * @param string $start_date Start date (Y-m-d).
     * @param string $end_date   End date (Y-m-d).
     * @param string $status     Optional status filter.
     * @return array Array of LeaveRequest models.
     */
    public function findByDateRange( string $start_date, string $end_date, string $status = '' ): array {
        $table = $this->getTableName();
        
        $query = "SELECT * FROM {$table} WHERE 
                  (start_date BETWEEN %s AND %s OR end_date BETWEEN %s AND %s)";
        
        $params = array( $start_date, $end_date, $start_date, $end_date );
        
        if ( ! empty( $status ) ) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY start_date ASC";
        
        $prepared = $this->db->prepare( $query, ...$params );
        
        return $this->rawQuery( $prepared );
    }

    /**
     * Approve a leave request
     *
     * @param int $request_id Request ID.
     * @param int $approver_id Approver user ID.
     * @return bool True on success.
     */
    public function approve( int $request_id, int $approver_id ): bool {
        return $this->update( $request_id, array(
            'status'      => LeaveRequest::STATUS_APPROVED,
            'approved_by' => $approver_id,
            'approved_at' => current_time( 'mysql' ),
        ) );
    }

    /**
     * Reject a leave request
     *
     * @param int    $request_id Request ID.
     * @param int    $approver_id Approver user ID.
     * @param string $reason Rejection reason.
     * @return bool True on success.
     */
    public function reject( int $request_id, int $approver_id, string $reason = '' ): bool {
        $data = array(
            'status'      => LeaveRequest::STATUS_REJECTED,
            'approved_by' => $approver_id,
            'approved_at' => current_time( 'mysql' ),
        );
        
        if ( ! empty( $reason ) ) {
            $data['rejection_reason'] = $reason;
        }
        
        return $this->update( $request_id, $data );
    }

    /**
     * Get leave statistics for a user
     *
     * @param int $user_id User ID.
     * @param int $year    Year to get stats for.
     * @return array Statistics array.
     */
    public function getUserStats( int $user_id, int $year = 0 ): array {
        if ( $year === 0 ) {
            $year = (int) date( 'Y' );
        }
        
        $table = $this->getTableName();
        
        $query = $this->db->prepare(
            "SELECT 
                status,
                COUNT(*) as count,
                SUM(days_requested) as total_days
             FROM {$table}
             WHERE user_id = %d
             AND YEAR(start_date) = %d
             GROUP BY status",
            $user_id,
            $year
        );
        
        $results = $this->db->getResults( $query );
        
        $stats = array(
            'pending'  => array( 'count' => 0, 'days' => 0 ),
            'approved' => array( 'count' => 0, 'days' => 0 ),
            'rejected' => array( 'count' => 0, 'days' => 0 ),
        );
        
        foreach ( $results as $row ) {
            if ( isset( $stats[ $row->status ] ) ) {
                $stats[ $row->status ] = array(
                    'count' => (int) $row->count,
                    'days'  => (float) $row->total_days,
                );
            }
        }
        
        return $stats;
    }

    /**
     * Check for overlapping requests
     *
     * @param int    $user_id    User ID.
     * @param string $start_date Start date.
     * @param string $end_date   End date.
     * @param int    $exclude_id Request ID to exclude (for updates).
     * @return bool True if overlapping request exists.
     */
    public function hasOverlap( int $user_id, string $start_date, string $end_date, int $exclude_id = 0 ): bool {
        $table = $this->getTableName();
        
        $query = $this->db->prepare(
            "SELECT 1 FROM {$table}
             WHERE user_id = %d
             AND status != %s
             AND (
                 (start_date <= %s AND end_date >= %s)
                 OR (start_date <= %s AND end_date >= %s)
                 OR (start_date >= %s AND end_date <= %s)
             )",
            $user_id,
            LeaveRequest::STATUS_REJECTED,
            $end_date,
            $start_date,
            $start_date,
            $start_date,
            $start_date,
            $end_date
        );
        
        if ( $exclude_id > 0 ) {
            $query .= $this->db->prepare( " AND request_id != %d", $exclude_id );
        }
        
        $query .= " LIMIT 1";
        
        return $this->db->getVar( $query ) !== null;
    }
}
