<?php
/**
 * Service Container
 *
 * Simple dependency injection container for the Leave Manager plugin.
 * Provides singleton access to services and repositories.
 *
 * @package LeaveManager\Core
 */

namespace LeaveManager\Core;

use LeaveManager\Database\Connection;
use LeaveManager\Repository\LeaveRequestRepository;
use LeaveManager\Repository\LeaveUserRepository;
use LeaveManager\Repository\LeaveBalanceRepository;
use LeaveManager\Service\LeaveRequestService;

/**
 * Class ServiceContainer
 */
class ServiceContainer {

    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Service instances
     *
     * @var array
     */
    private array $services = array();

    /**
     * Private constructor for singleton
     */
    private function __construct() {}

    /**
     * Get singleton instance
     *
     * @return self
     */
    public static function getInstance(): self {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the database connection
     *
     * @return Connection
     */
    public function getConnection(): Connection {
        return Connection::getInstance();
    }

    /**
     * Get the leave request repository
     *
     * @return LeaveRequestRepository
     */
    public function getLeaveRequestRepository(): LeaveRequestRepository {
        if ( ! isset( $this->services['leave_request_repo'] ) ) {
            $this->services['leave_request_repo'] = new LeaveRequestRepository();
        }
        return $this->services['leave_request_repo'];
    }

    /**
     * Get the leave user repository
     *
     * @return LeaveUserRepository
     */
    public function getLeaveUserRepository(): LeaveUserRepository {
        if ( ! isset( $this->services['leave_user_repo'] ) ) {
            $this->services['leave_user_repo'] = new LeaveUserRepository();
        }
        return $this->services['leave_user_repo'];
    }

    /**
     * Get the leave balance repository
     *
     * @return LeaveBalanceRepository
     */
    public function getLeaveBalanceRepository(): LeaveBalanceRepository {
        if ( ! isset( $this->services['leave_balance_repo'] ) ) {
            $this->services['leave_balance_repo'] = new LeaveBalanceRepository();
        }
        return $this->services['leave_balance_repo'];
    }

    /**
     * Get the leave request service
     *
     * @return LeaveRequestService
     */
    public function getLeaveRequestService(): LeaveRequestService {
        if ( ! isset( $this->services['leave_request_service'] ) ) {
            $this->services['leave_request_service'] = new LeaveRequestService(
                $this->getLeaveRequestRepository(),
                $this->getLeaveBalanceRepository(),
                $this->getLeaveUserRepository()
            );
        }
        return $this->services['leave_request_service'];
    }

    /**
     * Register a custom service
     *
     * @param string   $name     Service name.
     * @param callable $factory  Factory function to create the service.
     * @return self
     */
    public function register( string $name, callable $factory ): self {
        $this->services[ $name ] = $factory( $this );
        return $this;
    }

    /**
     * Get a registered service
     *
     * @param string $name Service name.
     * @return mixed|null
     */
    public function get( string $name ) {
        return $this->services[ $name ] ?? null;
    }

    /**
     * Check if a service is registered
     *
     * @param string $name Service name.
     * @return bool
     */
    public function has( string $name ): bool {
        return isset( $this->services[ $name ] );
    }

    /**
     * Reset all services (useful for testing)
     *
     * @return void
     */
    public function reset(): void {
        $this->services = array();
    }
}

/**
 * Helper function to get the service container
 *
 * @return ServiceContainer
 */
function container(): ServiceContainer {
    return ServiceContainer::getInstance();
}
