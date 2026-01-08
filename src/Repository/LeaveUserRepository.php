<?php
/**
 * Leave User Repository
 *
 * Handles all database operations for leave users.
 *
 * @package LeaveManager\Repository
 */

namespace LeaveManager\Repository;

use LeaveManager\Model\LeaveUser;

/**
 * Class LeaveUserRepository
 */
class LeaveUserRepository extends AbstractRepository {

    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected string $table = 'leave_users';

    /**
     * Primary key column name
     *
     * @var string
     */
    protected string $primary_key = 'user_id';

    /**
     * Model class name
     *
     * @var string
     */
    protected string $model_class = LeaveUser::class;

    /**
     * Find a user by username
     *
     * @param string $username Username.
     * @return LeaveUser|null
     */
    public function findByUsername( string $username ): ?LeaveUser {
        return $this->findBy( 'username', $username );
    }

    /**
     * Find a user by email
     *
     * @param string $email Email address.
     * @return LeaveUser|null
     */
    public function findByEmail( string $email ): ?LeaveUser {
        return $this->findBy( 'email', $email );
    }

    /**
     * Find users by department
     *
     * @param int   $department_id Department ID.
     * @param array $order_by Order by columns.
     * @return array Array of LeaveUser models.
     */
    public function findByDepartment( int $department_id, array $order_by = array( 'last_name' => 'ASC' ) ): array {
        return $this->findAll( array( 'department_id' => $department_id ), $order_by );
    }

    /**
     * Find users by role
     *
     * @param string $role User role.
     * @param array  $order_by Order by columns.
     * @return array Array of LeaveUser models.
     */
    public function findByRole( string $role, array $order_by = array( 'last_name' => 'ASC' ) ): array {
        return $this->findAll( array( 'role' => $role ), $order_by );
    }

    /**
     * Find all active users
     *
     * @param array $order_by Order by columns.
     * @return array Array of LeaveUser models.
     */
    public function findActive( array $order_by = array( 'last_name' => 'ASC' ) ): array {
        return $this->findAll( array( 'status' => LeaveUser::STATUS_ACTIVE ), $order_by );
    }

    /**
     * Find managers for a department
     *
     * @param int $department_id Department ID.
     * @return array Array of LeaveUser models.
     */
    public function findManagersForDepartment( string $department ): array {
        $table = $this->getTableName();
        
        // Note: The schema uses 'department' (varchar) instead of 'department_id' (foreign key)
        $query = $this->db->prepare(
            "SELECT * FROM {$table}
             WHERE department = %s
             AND role IN ('manager', 'admin')
             AND status = %s
             ORDER BY last_name ASC",
            $department,
            LeaveUser::STATUS_ACTIVE
        );
        
        return $this->rawQuery( $query );
    }

    /**
     * Authenticate a user
     *
     * @param string $username Username or email.
     * @param string $password Plain text password.
     * @return LeaveUser|null User if authenticated, null otherwise.
     */
    public function authenticate( string $username, string $password ): ?LeaveUser {
        // Try to find by username first, then by email
        $user = $this->findByUsername( $username );
        
        if ( ! $user ) {
            $user = $this->findByEmail( $username );
        }
        
        if ( ! $user ) {
            return null;
        }
        
        if ( ! $user->isActive() ) {
            return null;
        }
        
        if ( ! $user->verifyPassword( $password ) ) {
            return null;
        }
        
        return $user;
    }

    /**
     * Check if a username is available
     *
     * @param string $username Username to check.
     * @param int    $exclude_id User ID to exclude (for updates).
     * @return bool True if available.
     */
    public function isUsernameAvailable( string $username, int $exclude_id = 0 ): bool {
        $table = $this->getTableName();
        
        $query = $this->db->prepare(
            "SELECT 1 FROM {$table} WHERE username = %s",
            $username
        );
        
        if ( $exclude_id > 0 ) {
            $query .= $this->db->prepare( " AND user_id != %d", $exclude_id );
        }
        
        $query .= " LIMIT 1";
        
        return $this->db->getVar( $query ) === null;
    }

    /**
     * Check if an email is available
     *
     * @param string $email Email to check.
     * @param int    $exclude_id User ID to exclude (for updates).
     * @return bool True if available.
     */
    public function isEmailAvailable( string $email, int $exclude_id = 0 ): bool {
        $table = $this->getTableName();
        
        $query = $this->db->prepare(
            "SELECT 1 FROM {$table} WHERE email = %s",
            $email
        );
        
        if ( $exclude_id > 0 ) {
            $query .= $this->db->prepare( " AND user_id != %d", $exclude_id );
        }
        
        $query .= " LIMIT 1";
        
        return $this->db->getVar( $query ) === null;
    }

    /**
     * Update last login timestamp
     *
     * @param int $user_id User ID.
     * @return bool True on success.
     */
    public function updateLastLogin( int $user_id ): bool {
        return $this->update( $user_id, array(
            'last_login' => current_time( 'mysql' ),
        ) );
    }

    /**
     * Search users by name or email
     *
     * @param string $search Search term.
     * @param int    $limit Maximum results.
     * @return array Array of LeaveUser models.
     */
    public function search( string $search, int $limit = 20 ): array {
        $table = $this->getTableName();
        $search_term = '%' . $this->db->escape( $search ) . '%';
        
        $query = $this->db->prepare(
            "SELECT * FROM {$table}
             WHERE (first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR username LIKE %s)
             AND status = %s
             ORDER BY last_name ASC
             LIMIT %d",
            $search_term,
            $search_term,
            $search_term,
            $search_term,
            LeaveUser::STATUS_ACTIVE,
            $limit
        );
        
        return $this->rawQuery( $query );
    }
}
