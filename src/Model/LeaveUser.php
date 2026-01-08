<?php
/**
 * Leave User Model
 *
 * Represents a user in the Leave Manager plugin.
 *
 * @package LeaveManager\Model
 */

namespace LeaveManager\Model;

/**
 * Class LeaveUser
 *
 * @property int    $user_id       Primary key.
 * @property string $username      Username.
 * @property string $email         Email address.
 * @property string $password_hash Password hash.
 * @property string $first_name    First name.
 * @property string $last_name     Last name.
 * @property string $role          User role.
 * @property int    $department_id Department ID.
 * @property string $hire_date     Hire date.
 * @property string $status        Account status.
 * @property string $created_at    Creation timestamp.
 * @property string $updated_at    Last update timestamp.
 */
class LeaveUser extends AbstractModel {

    /**
     * The primary key column name
     *
     * @var string
     */
    protected string $primary_key = 'user_id';

    /**
     * The table name (without prefix)
     *
     * @var string
     */
    protected string $table = 'leave_users';

    /**
     * Fillable attributes
     *
     * @var array
     */
    protected array $fillable = array(
        'user_id',
        'wp_user_id',
        'first_name',
        'last_name',
        'email',
        'password_hash',
        'phone',
        'role',
        'department',
        'position',
        'policy_id',
        'annual_leave_balance',
        'sick_leave_balance',
        'other_leave_balance',
        'status',
        'account_locked',
        'login_attempts',
        'last_login',
    );

    /**
     * Hidden attributes (excluded from toArray)
     *
     * @var array
     */
    protected array $hidden = array(
        'password_hash',
    );

    /**
     * Role constants
     */
    public const ROLE_EMPLOYEE = 'employee';
    public const ROLE_MANAGER  = 'manager';
    public const ROLE_ADMIN    = 'admin';

    /**
     * Status constants
     */
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_PENDING  = 'pending';

    /**
     * Get the user's full name
     *
     * @return string
     */
    public function getFullName(): string {
        return trim( $this->first_name . ' ' . $this->last_name );
    }

    /**
     * Check if the user is an admin
     *
     * @return bool
     */
    public function isAdmin(): bool {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if the user is a manager
     *
     * @return bool
     */
    public function isManager(): bool {
        return $this->role === self::ROLE_MANAGER || $this->isAdmin();
    }

    /**
     * Check if the user is active
     *
     * @return bool
     */
    public function isActive(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Verify a password against the stored hash
     *
     * @param string $password Plain text password.
     * @return bool
     */
    public function verifyPassword( string $password ): bool {
        return password_verify( $password, $this->password_hash );
    }

    /**
     * Set a new password (hashes it automatically)
     *
     * @param string $password Plain text password.
     * @return self
     */
    public function setPassword( string $password ): self {
        $this->password_hash = password_hash( $password, PASSWORD_DEFAULT );
        return $this;
    }
}
