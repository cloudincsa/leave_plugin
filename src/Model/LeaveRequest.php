<?php
/**
 * Leave Request Model
 *
 * Represents a leave request entity in the Leave Manager plugin.
 *
 * @package LeaveManager\Model
 */

namespace LeaveManager\Model;

/**
 * Class LeaveRequest
 *
 * @property int    $request_id     Primary key.
 * @property int    $user_id        User who submitted the request.
 * @property string $leave_type     Type of leave.
 * @property string $start_date     Start date of leave.
 * @property string $end_date       End date of leave.
 * @property float  $days_requested Number of days requested.
 * @property string $reason         Reason for leave.
 * @property string $status         Request status (pending, approved, rejected).
 * @property int    $approved_by    User who approved/rejected.
 * @property string $approved_at    Approval timestamp.
 * @property string $created_at     Creation timestamp.
 * @property string $updated_at     Last update timestamp.
 */
class LeaveRequest extends AbstractModel {

    /**
     * The primary key column name
     *
     * @var string
     */
    protected string $primary_key = 'request_id';

    /**
     * The table name (without prefix)
     *
     * @var string
     */
    protected string $table = 'leave_requests';

    /**
     * Fillable attributes
     *
     * @var array
     */
    protected array $fillable = array(
        'request_id',
        'user_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_requested',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'half_day',
    );

    /**
     * Status constants
     */
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Check if the request is pending
     *
     * @return bool
     */
    public function isPending(): bool {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if the request is approved
     *
     * @return bool
     */
    public function isApproved(): bool {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the request is rejected
     *
     * @return bool
     */
    public function isRejected(): bool {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Calculate the number of days between start and end date
     *
     * @return int
     */
    public function calculateDays(): int {
        $start = new \DateTime( $this->start_date );
        $end = new \DateTime( $this->end_date );
        $diff = $start->diff( $end );
        return $diff->days + 1; // Include both start and end dates
    }

    /**
     * Get the number of days (calculated if not set)
     *
     * @return float
     */
    public function getDays(): float {
        if ( ! empty( $this->days_requested ) ) {
            return (float) $this->days_requested;
        }

        // Calculate from dates
        if ( ! empty( $this->start_date ) && ! empty( $this->end_date ) ) {
            $days = $this->calculateDays();
            if ( ! empty( $this->half_day ) && $this->half_day ) {
                return 0.5;
            }
            return (float) $days;
        }

        return 0.0;
    }

    /**
     * Check if the request can be cancelled
     *
     * @return bool
     */
    public function canBeCancelled(): bool {
        // Cannot cancel if already cancelled or rejected
        if ( in_array( $this->status, array( 'cancelled', 'rejected' ), true ) ) {
            return false;
        }

        // Can cancel pending requests
        if ( $this->status === self::STATUS_PENDING ) {
            return true;
        }

        // For approved requests, check if leave has started
        if ( $this->status === self::STATUS_APPROVED && ! empty( $this->start_date ) ) {
            $start = new \DateTime( $this->start_date );
            $today = new \DateTime();
            $today->setTime( 0, 0, 0 );
            return $start > $today;
        }

        return false;
    }
}
