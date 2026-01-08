<?php
/**
 * Database Connection Class
 *
 * Wrapper around WordPress $wpdb providing a cleaner interface
 * and additional functionality for the Leave Manager plugin.
 *
 * @package LeaveManager\Database
 */

namespace LeaveManager\Database;

/**
 * Class Connection
 */
class Connection {

    /**
     * WordPress database instance
     *
     * @var \wpdb
     */
    private \wpdb $wpdb;

    /**
     * Table prefix for Leave Manager tables
     *
     * @var string
     */
    private string $prefix;

    /**
     * Singleton instance
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Constructor
     *
     * @throws \RuntimeException If WordPress database is not available.
     */
    private function __construct() {
        global $wpdb;
        
        if ( ! $wpdb instanceof \wpdb ) {
            throw new \RuntimeException( 'WordPress database is not available. Ensure WordPress is loaded before using this class.' );
        }
        
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . 'leave_manager_';
    }

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
     * Get the WordPress database instance
     *
     * @return \wpdb
     */
    public function getWpdb(): \wpdb {
        return $this->wpdb;
    }

    /**
     * Get the full table name with prefix
     *
     * @param string $table Table name without prefix.
     * @return string
     */
    public function getTableName( string $table ): string {
        return $this->prefix . $table;
    }

    /**
     * Get the table prefix
     *
     * @return string
     */
    public function getPrefix(): string {
        return $this->prefix;
    }

    /**
     * Execute a raw query
     *
     * @param string $query SQL query.
     * @return int|bool
     */
    public function query( string $query ) {
        return $this->wpdb->query( $query );
    }

    /**
     * Get results from a query
     *
     * @param string $query  SQL query.
     * @param string $output Output type (OBJECT, ARRAY_A, ARRAY_N).
     * @return array|null
     */
    public function getResults( string $query, string $output = OBJECT ): ?array {
        return $this->wpdb->get_results( $query, $output );
    }

    /**
     * Get a single row from a query
     *
     * @param string $query  SQL query.
     * @param string $output Output type.
     * @param int    $y      Row offset.
     * @return object|array|null
     */
    public function getRow( string $query, string $output = OBJECT, int $y = 0 ) {
        return $this->wpdb->get_row( $query, $output, $y );
    }

    /**
     * Get a single column from a query
     *
     * @param string $query SQL query.
     * @param int    $x     Column offset.
     * @return array
     */
    public function getCol( string $query, int $x = 0 ): array {
        return $this->wpdb->get_col( $query, $x );
    }

    /**
     * Get a single variable from a query
     *
     * @param string $query SQL query.
     * @param int    $x     Column offset.
     * @param int    $y     Row offset.
     * @return string|null
     */
    public function getVar( string $query, int $x = 0, int $y = 0 ): ?string {
        return $this->wpdb->get_var( $query, $x, $y );
    }

    /**
     * Insert a row into a table
     *
     * @param string       $table  Table name (without prefix).
     * @param array        $data   Data to insert.
     * @param array|string $format Data format.
     * @return int|false Insert ID or false on failure.
     */
    public function insert( string $table, array $data, $format = null ) {
        $result = $this->wpdb->insert( $this->getTableName( $table ), $data, $format );
        return $result ? $this->wpdb->insert_id : false;
    }

    /**
     * Update rows in a table
     *
     * @param string       $table        Table name (without prefix).
     * @param array        $data         Data to update.
     * @param array        $where        Where conditions.
     * @param array|string $format       Data format.
     * @param array|string $where_format Where format.
     * @return int|false Number of rows updated or false on failure.
     */
    public function update( string $table, array $data, array $where, $format = null, $where_format = null ) {
        return $this->wpdb->update( $this->getTableName( $table ), $data, $where, $format, $where_format );
    }

    /**
     * Delete rows from a table
     *
     * @param string       $table        Table name (without prefix).
     * @param array        $where        Where conditions.
     * @param array|string $where_format Where format.
     * @return int|false Number of rows deleted or false on failure.
     */
    public function delete( string $table, array $where, $where_format = null ) {
        return $this->wpdb->delete( $this->getTableName( $table ), $where, $where_format );
    }

    /**
     * Prepare a SQL query for safe execution
     *
     * @param string $query SQL query with placeholders.
     * @param mixed  ...$args Values to substitute.
     * @return string
     */
    public function prepare( string $query, ...$args ): string {
        return $this->wpdb->prepare( $query, ...$args );
    }

    /**
     * Get the last error message
     *
     * @return string
     */
    public function getLastError(): string {
        return $this->wpdb->last_error;
    }

    /**
     * Get the last query executed
     *
     * @return string
     */
    public function getLastQuery(): string {
        return $this->wpdb->last_query;
    }

    /**
     * Get the last insert ID
     *
     * @return int
     */
    public function getInsertId(): int {
        return $this->wpdb->insert_id;
    }

    /**
     * Start a transaction
     *
     * @return bool
     */
    public function beginTransaction(): bool {
        return $this->wpdb->query( 'START TRANSACTION' ) !== false;
    }

    /**
     * Commit a transaction
     *
     * @return bool
     */
    public function commit(): bool {
        return $this->wpdb->query( 'COMMIT' ) !== false;
    }

    /**
     * Rollback a transaction
     *
     * @return bool
     */
    public function rollback(): bool {
        return $this->wpdb->query( 'ROLLBACK' ) !== false;
    }

    /**
     * Escape a string for use in a query
     *
     * @param string $data String to escape.
     * @return string
     */
    public function escape( string $data ): string {
        return $this->wpdb->_real_escape( $data );
    }
}
