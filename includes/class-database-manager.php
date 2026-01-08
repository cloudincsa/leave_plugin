<?php
/**
 * Database Manager Class
 * Handles database table creation and management
 *
 * @package LeaveManager
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Leave_Manager_Database_Manager {

    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Create holidays table if it doesn't exist
        $holidays_table = $wpdb->prefix . 'leave_manager_holidays';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$holidays_table}'" ) !== $holidays_table ) {
            $sql = "CREATE TABLE {$holidays_table} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                holiday_date DATE NOT NULL,
                holiday_name VARCHAR(255) NOT NULL,
                holiday_type VARCHAR(50),
                country_code VARCHAR(2) NOT NULL,
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY idx_date (holiday_date),
                KEY idx_country (country_code),
                UNIQUE KEY unique_holiday (holiday_date, country_code)
            ) {$charset_collate};";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }
    }

    public static function drop_tables() {
        global $wpdb;
        $holidays_table = $wpdb->prefix . 'leave_manager_holidays';
        $wpdb->query( "DROP TABLE IF EXISTS {$holidays_table}" );
    }
}

// Create tables on plugin activation
register_activation_hook( LEAVE_MANAGER_PLUGIN_FILE, array( 'Leave_Manager_Database_Manager', 'create_tables' ) );
register_deactivation_hook( LEAVE_MANAGER_PLUGIN_FILE, array( 'Leave_Manager_Database_Manager', 'drop_tables' ) );
