<?php
/**
 * Abstract Repository Class
 *
 * Base class for all repository classes in the Leave Manager plugin.
 * Provides common CRUD operations and query building functionality.
 *
 * @package LeaveManager\Repository
 */

namespace LeaveManager\Repository;

use LeaveManager\Database\Connection;
use LeaveManager\Model\AbstractModel;

/**
 * Class AbstractRepository
 */
abstract class AbstractRepository {

    /**
     * Database connection instance
     *
     * @var Connection
     */
    protected Connection $db;

    /**
     * Table name (without prefix)
     *
     * @var string
     */
    protected string $table = '';

    /**
     * Primary key column name
     *
     * @var string
     */
    protected string $primary_key = 'id';

    /**
     * Model class name
     *
     * @var string
     */
    protected string $model_class = '';

    /**
     * Constructor
     *
     * @param Connection|null $connection Database connection instance.
     */
    public function __construct( ?Connection $connection = null ) {
        $this->db = $connection ?? Connection::getInstance();
    }

    /**
     * Get the full table name with prefix
     *
     * @return string
     */
    protected function getTableName(): string {
        return $this->db->getTableName( $this->table );
    }

    /**
     * Find a record by its primary key
     *
     * @param int|string $id Primary key value.
     * @return AbstractModel|null
     */
    public function find( $id ): ?AbstractModel {
        $table = $this->getTableName();
        $query = $this->db->prepare(
            "SELECT * FROM {$table} WHERE {$this->primary_key} = %d LIMIT 1",
            $id
        );
        
        $row = $this->db->getRow( $query );
        
        if ( ! $row ) {
            return null;
        }
        
        return $this->hydrate( $row );
    }

    /**
     * Find a record by a specific column
     *
     * @param string $column Column name.
     * @param mixed  $value  Column value.
     * @return AbstractModel|null
     */
    public function findBy( string $column, $value ): ?AbstractModel {
        $table = $this->getTableName();
        $query = $this->db->prepare(
            "SELECT * FROM {$table} WHERE {$column} = %s LIMIT 1",
            $value
        );
        
        $row = $this->db->getRow( $query );
        
        if ( ! $row ) {
            return null;
        }
        
        return $this->hydrate( $row );
    }

    /**
     * Find all records matching criteria
     *
     * @param array $criteria Column => value pairs.
     * @param array $order_by Column => direction pairs.
     * @param int   $limit    Maximum records to return.
     * @param int   $offset   Number of records to skip.
     * @return array Array of models.
     */
    public function findAll( array $criteria = array(), array $order_by = array(), int $limit = 0, int $offset = 0 ): array {
        $table = $this->getTableName();
        $query = "SELECT * FROM {$table}";
        
        // Build WHERE clause
        if ( ! empty( $criteria ) ) {
            $conditions = array();
            foreach ( $criteria as $column => $value ) {
                if ( is_null( $value ) ) {
                    $conditions[] = "{$column} IS NULL";
                } else {
                    $conditions[] = $this->db->prepare( "{$column} = %s", $value );
                }
            }
            $query .= ' WHERE ' . implode( ' AND ', $conditions );
        }
        
        // Build ORDER BY clause
        if ( ! empty( $order_by ) ) {
            $orders = array();
            foreach ( $order_by as $column => $direction ) {
                $direction = strtoupper( $direction ) === 'DESC' ? 'DESC' : 'ASC';
                $orders[] = "{$column} {$direction}";
            }
            $query .= ' ORDER BY ' . implode( ', ', $orders );
        }
        
        // Build LIMIT clause
        if ( $limit > 0 ) {
            $query .= $this->db->prepare( ' LIMIT %d', $limit );
            if ( $offset > 0 ) {
                $query .= $this->db->prepare( ' OFFSET %d', $offset );
            }
        }
        
        $rows = $this->db->getResults( $query );
        
        if ( ! $rows ) {
            return array();
        }
        
        return array_map( array( $this, 'hydrate' ), $rows );
    }

    /**
     * Get all records
     *
     * @return array Array of models.
     */
    public function all(): array {
        return $this->findAll();
    }

    /**
     * Count records matching criteria
     *
     * @param array $criteria Column => value pairs.
     * @return int
     */
    public function count( array $criteria = array() ): int {
        $table = $this->getTableName();
        $query = "SELECT COUNT(*) FROM {$table}";
        
        if ( ! empty( $criteria ) ) {
            $conditions = array();
            foreach ( $criteria as $column => $value ) {
                if ( is_null( $value ) ) {
                    $conditions[] = "{$column} IS NULL";
                } else {
                    $conditions[] = $this->db->prepare( "{$column} = %s", $value );
                }
            }
            $query .= ' WHERE ' . implode( ' AND ', $conditions );
        }
        
        return (int) $this->db->getVar( $query );
    }

    /**
     * Check if a record exists
     *
     * @param int|string $id Primary key value.
     * @return bool
     */
    public function exists( $id ): bool {
        $table = $this->getTableName();
        $query = $this->db->prepare(
            "SELECT 1 FROM {$table} WHERE {$this->primary_key} = %d LIMIT 1",
            $id
        );
        
        return $this->db->getVar( $query ) !== null;
    }

    /**
     * Create a new record
     *
     * @param array $data Data to insert.
     * @return AbstractModel|null Created model or null on failure.
     */
    public function create( array $data ): ?AbstractModel {
        // Add timestamps if not present
        if ( ! isset( $data['created_at'] ) ) {
            $data['created_at'] = current_time( 'mysql' );
        }
        if ( ! isset( $data['updated_at'] ) ) {
            $data['updated_at'] = current_time( 'mysql' );
        }
        
        $id = $this->db->insert( $this->table, $data );
        
        if ( ! $id ) {
            return null;
        }
        
        return $this->find( $id );
    }

    /**
     * Update an existing record
     *
     * @param int|string $id   Primary key value.
     * @param array      $data Data to update.
     * @return bool True on success, false on failure.
     */
    public function update( $id, array $data ): bool {
        // Update timestamp
        $data['updated_at'] = current_time( 'mysql' );
        
        $result = $this->db->update(
            $this->table,
            $data,
            array( $this->primary_key => $id )
        );
        
        return $result !== false;
    }

    /**
     * Save a model (create or update)
     *
     * @param AbstractModel $model Model to save.
     * @return bool True on success, false on failure.
     */
    public function save( AbstractModel $model ): bool {
        if ( $model->exists() ) {
            return $this->update( $model->getId(), $model->getDirty() );
        }
        
        $created = $this->create( $model->getAttributes() );
        
        if ( $created ) {
            $model->setAttribute( $this->primary_key, $created->getId() );
            $model->syncOriginal();
            return true;
        }
        
        return false;
    }

    /**
     * Delete a record by its primary key
     *
     * @param int|string $id Primary key value.
     * @return bool True on success, false on failure.
     */
    public function delete( $id ): bool {
        $result = $this->db->delete(
            $this->table,
            array( $this->primary_key => $id )
        );
        
        return $result !== false;
    }

    /**
     * Delete records matching criteria
     *
     * @param array $criteria Column => value pairs.
     * @return int Number of deleted records.
     */
    public function deleteWhere( array $criteria ): int {
        $result = $this->db->delete( $this->table, $criteria );
        return $result !== false ? $result : 0;
    }

    /**
     * Hydrate a database row into a model
     *
     * @param object|array $row Database row.
     * @return AbstractModel
     */
    protected function hydrate( $row ): AbstractModel {
        $class = $this->model_class;
        return $class::fromRow( $row );
    }

    /**
     * Execute a raw query and return models
     *
     * @param string $query SQL query.
     * @return array Array of models.
     */
    protected function rawQuery( string $query ): array {
        $rows = $this->db->getResults( $query );
        
        if ( ! $rows ) {
            return array();
        }
        
        return array_map( array( $this, 'hydrate' ), $rows );
    }

    /**
     * Execute a raw query and return a single model
     *
     * @param string $query SQL query.
     * @return AbstractModel|null
     */
    protected function rawQueryOne( string $query ): ?AbstractModel {
        $row = $this->db->getRow( $query );
        
        if ( ! $row ) {
            return null;
        }
        
        return $this->hydrate( $row );
    }
}
