<?php
/**
 * Leave Balance Repository
 *
 * Handles all database operations for leave balances.
 *
 * @package LeaveManager\Repository
 */

namespace LeaveManager\Repository;

use LeaveManager\Model\LeaveBalance;

/**
 * Class LeaveBalanceRepository
 */
class LeaveBalanceRepository extends AbstractRepository {

    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected string $table = 'leave_balances';

    /**
     * Primary key column name
     *
     * @var string
     */
    protected string $primary_key = 'balance_id';

    /**
     * Model class name
     *
     * @var string
     */
    protected string $model_class = LeaveBalance::class;

    /**
     * Find all balances for a user
     *
     * @param int $user_id User ID.
     * @param int $year    Year (defaults to current year).
     * @return array Array of LeaveBalance models.
     */
    public function findByUser( int $user_id, int $year = 0 ): array {
        if ( $year === 0 ) {
            $year = (int) date( 'Y' );
        }
        
        return $this->findAll(
            array( 'user_id' => $user_id, 'year' => $year ),
            array( 'leave_type' => 'ASC' )
        );
    }

    /**
     * Find a specific balance for a user and leave type
     *
     * @param int    $user_id    User ID.
     * @param string $leave_type Leave type.
     * @param int    $year       Year (defaults to current year).
     * @return LeaveBalance|null
     */
    public function findByUserAndType( int $user_id, string $leave_type, int $year = 0 ): ?LeaveBalance {
        if ( $year === 0 ) {
            $year = (int) date( 'Y' );
        }
        
        $table = $this->getTableName();
        
        $query = $this->db->prepare(
            "SELECT * FROM {$table}
             WHERE user_id = %d AND leave_type = %s AND year = %d
             LIMIT 1",
            $user_id,
            $leave_type,
            $year
        );
        
        return $this->rawQueryOne( $query );
    }

    /**
     * Get or create a balance for a user and leave type
     *
     * @param int    $user_id    User ID.
     * @param string $leave_type Leave type.
     * @param int    $year       Year.
     * @param float  $total_days Default total days if creating.
     * @return LeaveBalance
     */
    public function getOrCreate( int $user_id, string $leave_type, int $year = 0, float $total_days = 0 ): LeaveBalance {
        if ( $year === 0 ) {
            $year = (int) date( 'Y' );
        }
        
        $balance = $this->findByUserAndType( $user_id, $leave_type, $year );
        
        if ( $balance ) {
            return $balance;
        }
        
        // Create new balance
        return $this->create( array(
            'user_id'      => $user_id,
            'leave_type'   => $leave_type,
            'year'         => $year,
            'total_days'   => $total_days,
            'used_days'    => 0,
            'pending_days' => 0,
            'carried_over' => 0,
        ) );
    }

    /**
     * Deduct days from a user's balance
     *
     * @param int    $user_id    User ID.
     * @param string $leave_type Leave type.
     * @param float  $days       Days to deduct.
     * @param int    $year       Year.
     * @return bool True on success.
     */
    public function deductDays( int $user_id, string $leave_type, float $days, int $year = 0 ): bool {
        $balance = $this->findByUserAndType( $user_id, $leave_type, $year );
        
        if ( ! $balance ) {
            return false;
        }
        
        $balance->deduct( $days );
        
        return $this->save( $balance );
    }

    /**
     * Add pending days to a user's balance
     *
     * @param int    $user_id    User ID.
     * @param string $leave_type Leave type.
     * @param float  $days       Days to add as pending.
     * @param int    $year       Year.
     * @return bool True on success.
     */
    public function addPendingDays( int $user_id, string $leave_type, float $days, int $year = 0 ): bool {
        $balance = $this->findByUserAndType( $user_id, $leave_type, $year );
        
        if ( ! $balance ) {
            return false;
        }
        
        $balance->addPending( $days );
        
        return $this->save( $balance );
    }

    /**
     * Approve pending days (move from pending to used)
     *
     * @param int    $user_id    User ID.
     * @param string $leave_type Leave type.
     * @param float  $days       Days to approve.
     * @param int    $year       Year.
     * @return bool True on success.
     */
    public function approvePendingDays( int $user_id, string $leave_type, float $days, int $year = 0 ): bool {
        $balance = $this->findByUserAndType( $user_id, $leave_type, $year );
        
        if ( ! $balance ) {
            return false;
        }
        
        $balance->approvePending( $days );
        
        return $this->save( $balance );
    }

    /**
     * Remove pending days (when request is rejected)
     *
     * @param int    $user_id    User ID.
     * @param string $leave_type Leave type.
     * @param float  $days       Days to remove from pending.
     * @param int    $year       Year.
     * @return bool True on success.
     */
    public function removePendingDays( int $user_id, string $leave_type, float $days, int $year = 0 ): bool {
        $balance = $this->findByUserAndType( $user_id, $leave_type, $year );
        
        if ( ! $balance ) {
            return false;
        }
        
        $balance->removePending( $days );
        
        return $this->save( $balance );
    }

    /**
     * Get total available days for a user across all leave types
     *
     * @param int $user_id User ID.
     * @param int $year    Year.
     * @return float Total available days.
     */
    public function getTotalAvailable( int $user_id, int $year = 0 ): float {
        $balances = $this->findByUser( $user_id, $year );
        
        $total = 0;
        foreach ( $balances as $balance ) {
            $total += $balance->getAvailable();
        }
        
        return $total;
    }

    /**
     * Initialize balances for a new user based on leave types
     *
     * @param int   $user_id User ID.
     * @param array $leave_types Array of leave type => days pairs.
     * @param int   $year Year.
     * @return bool True on success.
     */
    public function initializeForUser( int $user_id, array $leave_types, int $year = 0 ): bool {
        if ( $year === 0 ) {
            $year = (int) date( 'Y' );
        }
        
        $this->db->beginTransaction();
        
        try {
            foreach ( $leave_types as $type => $days ) {
                $this->create( array(
                    'user_id'      => $user_id,
                    'leave_type'   => $type,
                    'year'         => $year,
                    'total_days'   => $days,
                    'used_days'    => 0,
                    'pending_days' => 0,
                    'carried_over' => 0,
                ) );
            }
            
            $this->db->commit();
            return true;
        } catch ( \Exception $e ) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Carry over balances from previous year
     *
     * @param int   $user_id  User ID.
     * @param int   $from_year Year to carry over from.
     * @param float $max_days Maximum days to carry over per type.
     * @return bool True on success.
     */
    public function carryOver( int $user_id, int $from_year, float $max_days = 5 ): bool {
        $to_year = $from_year + 1;
        $old_balances = $this->findByUser( $user_id, $from_year );
        
        $this->db->beginTransaction();
        
        try {
            foreach ( $old_balances as $old ) {
                $available = $old->getAvailable();
                $carry = min( $available, $max_days );
                
                if ( $carry > 0 ) {
                    $new = $this->findByUserAndType( $user_id, $old->leave_type, $to_year );
                    
                    if ( $new ) {
                        $new->carried_over = $carry;
                        $this->save( $new );
                    }
                }
            }
            
            $this->db->commit();
            return true;
        } catch ( \Exception $e ) {
            $this->db->rollback();
            return false;
        }
    }
}
