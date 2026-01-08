<?php
/**
 * Leave Balance Model
 *
 * Represents a user's leave balance for a specific leave type.
 *
 * @package LeaveManager\Model
 */

namespace LeaveManager\Model;

/**
 * Class LeaveBalance
 *
 * @property int    $balance_id     Primary key.
 * @property int    $user_id        User ID.
 * @property string $leave_type     Leave type.
 * @property float  $total_days     Total days allocated.
 * @property float  $used_days      Days used.
 * @property float  $pending_days   Days pending approval.
 * @property float  $carried_over   Days carried over from previous year.
 * @property int    $year           Year for this balance.
 * @property string $created_at     Creation timestamp.
 * @property string $updated_at     Last update timestamp.
 */
class LeaveBalance extends AbstractModel {

    /**
     * The primary key column name
     *
     * @var string
     */
    protected string $primary_key = 'balance_id';

    /**
     * The table name (without prefix)
     *
     * @var string
     */
    protected string $table = 'leave_balances';

    /**
     * Fillable attributes
     *
     * @var array
     */
    protected array $fillable = array(
        'balance_id',
        'user_id',
        'leave_type',
        'total_days',
        'used_days',
        'pending_days',
        'carried_over',
        'year',
    );

    /**
     * Get the available balance
     *
     * @return float
     */
    public function getAvailable(): float {
        return (float) $this->total_days - (float) $this->used_days - (float) $this->pending_days;
    }

    /**
     * Get the total including carried over
     *
     * @return float
     */
    public function getTotalWithCarryOver(): float {
        return (float) $this->total_days + (float) $this->carried_over;
    }

    /**
     * Check if the user has enough balance for a request
     *
     * @param float $days Number of days to check.
     * @return bool
     */
    public function hasEnoughBalance( float $days ): bool {
        return $this->getAvailable() >= $days;
    }

    /**
     * Deduct days from the balance
     *
     * @param float $days Number of days to deduct.
     * @return self
     */
    public function deduct( float $days ): self {
        $this->used_days = (float) $this->used_days + $days;
        return $this;
    }

    /**
     * Add pending days
     *
     * @param float $days Number of days to add as pending.
     * @return self
     */
    public function addPending( float $days ): self {
        $this->pending_days = (float) $this->pending_days + $days;
        return $this;
    }

    /**
     * Remove pending days (when approved or rejected)
     *
     * @param float $days Number of days to remove from pending.
     * @return self
     */
    public function removePending( float $days ): self {
        $this->pending_days = max( 0, (float) $this->pending_days - $days );
        return $this;
    }

    /**
     * Approve pending days (move from pending to used)
     *
     * @param float $days Number of days to approve.
     * @return self
     */
    public function approvePending( float $days ): self {
        $this->removePending( $days );
        $this->deduct( $days );
        return $this;
    }

    /**
     * Get remaining days (total - used)
     *
     * @return float
     */
    public function getRemainingDays(): float {
        return (float) $this->total_days - (float) $this->used_days;
    }

    /**
     * Check if user has balance for specified days
     *
     * @param float $days Number of days to check.
     * @return bool
     */
    public function hasBalance( float $days ): bool {
        return $this->getRemainingDays() >= $days;
    }
}
