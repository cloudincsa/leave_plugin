<?php
/**
 * Schema Standardization Migration
 *
 * This migration standardizes the database schema naming conventions.
 * It should be run during plugin update from version 1.x to 2.0.
 *
 * @package LeaveManager\Migrations
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Leave_Manager_Schema_Migration
 */
class Leave_Manager_Schema_Migration {

    /**
     * Database prefix
     *
     * @var string
     */
    private $prefix;

    /**
     * WordPress database object
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Migration log
     *
     * @var array
     */
    private $log = array();

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb   = $wpdb;
        $this->prefix = $wpdb->prefix . 'leave_manager_';
    }

    /**
     * Run the migration
     *
     * @return array Migration log.
     */
    public function run(): array {
        $this->log( 'Starting schema standardization migration...' );

        // Phase 1: Create departments table if not exists
        $this->create_departments_table();

        // Phase 2: Migrate department data
        $this->migrate_department_data();

        // Phase 3: Add department_id foreign key columns
        $this->add_department_fk_columns();

        // Phase 4: Populate department_id values
        $this->populate_department_ids();

        // Phase 5: Standardize primary key naming
        $this->standardize_primary_keys();

        // Phase 6: Create leave_type_id foreign key
        $this->standardize_leave_type_references();

        $this->log( 'Schema standardization migration completed.' );

        return $this->log;
    }

    /**
     * Create departments table if it doesn't exist
     */
    private function create_departments_table(): void {
        $table = $this->prefix . 'departments';
        
        $exists = $this->wpdb->get_var( "SHOW TABLES LIKE '$table'" );
        
        if ( $exists ) {
            $this->log( "Table $table already exists, skipping creation." );
            return;
        }

        $charset_collate = $this->wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            department_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            department_name VARCHAR(255) NOT NULL,
            department_code VARCHAR(50),
            description TEXT,
            manager_id BIGINT UNSIGNED,
            parent_department_id BIGINT UNSIGNED,
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (department_id),
            UNIQUE KEY department_name (department_name),
            KEY manager_id (manager_id),
            KEY parent_department_id (parent_department_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        $this->log( "Created table: $table" );
    }

    /**
     * Migrate department data from varchar columns to departments table
     */
    private function migrate_department_data(): void {
        $departments_table = $this->prefix . 'departments';
        $users_table       = $this->prefix . 'leave_users';
        $staff_table       = $this->prefix . 'staff';
        $teams_table       = $this->prefix . 'teams';

        // Collect unique department names from all sources
        $sources = array(
            array( 'table' => $users_table, 'column' => 'department' ),
            array( 'table' => $staff_table, 'column' => 'department' ),
            array( 'table' => $teams_table, 'column' => 'department' ),
        );

        $all_departments = array();

        foreach ( $sources as $source ) {
            $table_exists = $this->wpdb->get_var( "SHOW TABLES LIKE '{$source['table']}'" );
            if ( ! $table_exists ) {
                continue;
            }

            $column_exists = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = %s 
                     AND COLUMN_NAME = %s",
                    $source['table'],
                    $source['column']
                )
            );

            if ( ! $column_exists ) {
                continue;
            }

            $departments = $this->wpdb->get_col(
                "SELECT DISTINCT {$source['column']} FROM {$source['table']} 
                 WHERE {$source['column']} IS NOT NULL AND {$source['column']} != ''"
            );

            $all_departments = array_merge( $all_departments, $departments );
        }

        $all_departments = array_unique( array_filter( $all_departments ) );

        // Insert unique departments
        $inserted = 0;
        foreach ( $all_departments as $dept_name ) {
            // Check if already exists
            $exists = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT department_id FROM $departments_table WHERE department_name = %s",
                    $dept_name
                )
            );

            if ( ! $exists ) {
                $this->wpdb->insert(
                    $departments_table,
                    array(
                        'department_name' => $dept_name,
                        'department_code' => sanitize_title( $dept_name ),
                        'status'          => 'active',
                    ),
                    array( '%s', '%s', '%s' )
                );
                $inserted++;
            }
        }

        $this->log( "Migrated $inserted unique departments to departments table." );
    }

    /**
     * Add department_id foreign key columns to relevant tables
     */
    private function add_department_fk_columns(): void {
        $tables_to_update = array(
            $this->prefix . 'leave_users',
            $this->prefix . 'staff',
            $this->prefix . 'teams',
        );

        foreach ( $tables_to_update as $table ) {
            $table_exists = $this->wpdb->get_var( "SHOW TABLES LIKE '$table'" );
            if ( ! $table_exists ) {
                continue;
            }

            // Check if department_id column already exists
            $column_exists = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = %s 
                     AND COLUMN_NAME = 'department_id'",
                    $table
                )
            );

            if ( $column_exists ) {
                $this->log( "Column department_id already exists in $table, skipping." );
                continue;
            }

            // Add department_id column
            $this->wpdb->query( "ALTER TABLE $table ADD COLUMN department_id BIGINT UNSIGNED AFTER department" );
            $this->wpdb->query( "ALTER TABLE $table ADD INDEX idx_department_id (department_id)" );

            $this->log( "Added department_id column to $table" );
        }
    }

    /**
     * Populate department_id values based on department name
     */
    private function populate_department_ids(): void {
        $departments_table = $this->prefix . 'departments';
        $tables_to_update  = array(
            $this->prefix . 'leave_users',
            $this->prefix . 'staff',
            $this->prefix . 'teams',
        );

        foreach ( $tables_to_update as $table ) {
            $table_exists = $this->wpdb->get_var( "SHOW TABLES LIKE '$table'" );
            if ( ! $table_exists ) {
                continue;
            }

            // Check if both columns exist
            $has_department = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = %s 
                     AND COLUMN_NAME = 'department'",
                    $table
                )
            );

            $has_department_id = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = %s 
                     AND COLUMN_NAME = 'department_id'",
                    $table
                )
            );

            if ( ! $has_department || ! $has_department_id ) {
                continue;
            }

            // Update department_id based on department name
            $updated = $this->wpdb->query(
                "UPDATE $table t
                 INNER JOIN $departments_table d ON t.department = d.department_name
                 SET t.department_id = d.department_id
                 WHERE t.department_id IS NULL"
            );

            $this->log( "Updated $updated rows in $table with department_id" );
        }
    }

    /**
     * Standardize primary key naming (document only, actual changes are risky)
     */
    private function standardize_primary_keys(): void {
        // This is a documentation step - actually renaming PKs is very risky
        // and requires updating all foreign key references
        
        $inconsistent_pks = array(
            'public_holidays' => 'id -> holiday_id',
            'rate_limits'     => 'id -> rate_limit_id',
            'report_logs'     => 'id -> report_log_id',
            'sessions'        => 'id -> session_pk_id (to avoid confusion with session_id)',
            'sms_logs'        => 'id -> sms_log_id',
            'staff'           => 'id -> staff_id',
            'users'           => 'id -> user_id',
        );

        $this->log( 'Primary key standardization recommendations (not applied):' );
        foreach ( $inconsistent_pks as $table => $change ) {
            $this->log( "  - {$this->prefix}$table: $change" );
        }
        $this->log( 'Note: PK renaming requires careful FK updates and is deferred.' );
    }

    /**
     * Standardize leave_type references
     */
    private function standardize_leave_type_references(): void {
        $leave_types_table = $this->prefix . 'leave_types';
        $requests_table    = $this->prefix . 'leave_requests';
        $balances_table    = $this->prefix . 'leave_balances';

        // Check if leave_types table exists
        $types_exists = $this->wpdb->get_var( "SHOW TABLES LIKE '$leave_types_table'" );
        if ( ! $types_exists ) {
            $this->log( 'leave_types table does not exist, skipping leave_type standardization.' );
            return;
        }

        // Add leave_type_id column to leave_requests if not exists
        $tables = array( $requests_table, $balances_table );

        foreach ( $tables as $table ) {
            $table_exists = $this->wpdb->get_var( "SHOW TABLES LIKE '$table'" );
            if ( ! $table_exists ) {
                continue;
            }

            $column_exists = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME = %s 
                     AND COLUMN_NAME = 'leave_type_id'",
                    $table
                )
            );

            if ( $column_exists ) {
                $this->log( "Column leave_type_id already exists in $table, skipping." );
                continue;
            }

            // Add leave_type_id column
            $this->wpdb->query( "ALTER TABLE $table ADD COLUMN leave_type_id BIGINT UNSIGNED AFTER leave_type" );
            $this->wpdb->query( "ALTER TABLE $table ADD INDEX idx_leave_type_id (leave_type_id)" );

            // Populate based on leave_type name
            $this->wpdb->query(
                "UPDATE $table t
                 INNER JOIN $leave_types_table lt ON t.leave_type = lt.type_name
                 SET t.leave_type_id = lt.type_id
                 WHERE t.leave_type_id IS NULL"
            );

            $this->log( "Added and populated leave_type_id column in $table" );
        }
    }

    /**
     * Add a log entry
     *
     * @param string $message Log message.
     */
    private function log( string $message ): void {
        $this->log[] = array(
            'time'    => current_time( 'mysql' ),
            'message' => $message,
        );
    }

    /**
     * Get the migration log
     *
     * @return array
     */
    public function get_log(): array {
        return $this->log;
    }
}

/**
 * Run the migration
 *
 * @return array Migration log.
 */
function leave_manager_run_schema_migration(): array {
    $migration = new Leave_Manager_Schema_Migration();
    return $migration->run();
}
