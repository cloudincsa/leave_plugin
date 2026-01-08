<?php
/**
 * Database Migration Class
 * Handles database schema creation and migrations for advanced features
 *
 * @package LeaveManager
 * @subpackage Database
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Database_Migration {

	/**
	 * Database version
	 *
	 * @var string
	 */
	private $db_version = '3.0.0';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'leave_manager_activate', array( $this, 'run_migrations' ) );
	}

	/**
	 * Run all database migrations
	 *
	 * @return void
	 */
	public function run_migrations() {
		global $wpdb;

		$current_version = get_option( 'leave_manager_db_version', '1.0.0' );

		if ( version_compare( $current_version, '3.0.0', '<' ) ) {
			$this->create_approval_tables();
			$this->create_prorata_tables();
			$this->create_carryover_tables();
			$this->create_report_tables();
			$this->create_public_holiday_tables();
			$this->create_audit_tables();
			$this->create_indexes();

			update_option( 'leave_manager_db_version', $this->db_version );
		}
	}

	/**
	 * Create approval workflow tables
	 *
	 * @return void
	 */
	private function create_approval_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Approval Requests Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_approval_requests (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			leave_request_id BIGINT UNSIGNED NOT NULL,
			approval_mode ENUM('workflow', 'hierarchy', 'hybrid') DEFAULT 'workflow',
			current_stage INT DEFAULT 1,
			status ENUM('pending', 'approved', 'rejected', 'escalated') DEFAULT 'pending',
			escalation_count INT DEFAULT 0,
			locked_by BIGINT UNSIGNED,
			locked_at DATETIME NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			FOREIGN KEY (leave_request_id) REFERENCES {$wpdb->prefix}leave_manager_leave_requests(id) ON DELETE CASCADE,
			FOREIGN KEY (locked_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL,
			INDEX idx_leave_request (leave_request_id),
			INDEX idx_status (status),
			INDEX idx_approval_mode (approval_mode)
		) $charset_collate;";

		$wpdb->query( $sql );

		// Approval Tasks Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_approval_tasks (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			approval_request_id BIGINT UNSIGNED NOT NULL,
			stage INT NOT NULL,
			approver_id BIGINT UNSIGNED NOT NULL,
			delegated_to_id BIGINT UNSIGNED,
			status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
			comments LONGTEXT,
			approved_at DATETIME NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			FOREIGN KEY (approval_request_id) REFERENCES {$wpdb->prefix}leave_manager_approval_requests(id) ON DELETE CASCADE,
			FOREIGN KEY (approver_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			FOREIGN KEY (delegated_to_id) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL,
			INDEX idx_approval_request (approval_request_id),
			INDEX idx_approver (approver_id),
			INDEX idx_status (status),
			INDEX idx_stage (stage)
		) $charset_collate;";

		$wpdb->query( $sql );

		// Approval Delegations Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_approval_delegations (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			from_user_id BIGINT UNSIGNED NOT NULL,
			to_user_id BIGINT UNSIGNED NOT NULL,
			start_date DATE NOT NULL,
			end_date DATE NOT NULL,
			reason TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			FOREIGN KEY (from_user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			FOREIGN KEY (to_user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			INDEX idx_from_user (from_user_id),
			INDEX idx_to_user (to_user_id),
			INDEX idx_date_range (start_date, end_date)
		) $charset_collate;";

		$wpdb->query( $sql );

		// Approval Audit Trail Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_approval_audit (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			approval_request_id BIGINT UNSIGNED NOT NULL,
			action VARCHAR(50) NOT NULL,
			actor_id BIGINT UNSIGNED NOT NULL,
			old_status VARCHAR(50),
			new_status VARCHAR(50),
			details LONGTEXT,
			ip_address VARCHAR(45),
			user_agent TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			FOREIGN KEY (approval_request_id) REFERENCES {$wpdb->prefix}leave_manager_approval_requests(id) ON DELETE CASCADE,
			FOREIGN KEY (actor_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			INDEX idx_approval_request (approval_request_id),
			INDEX idx_action (action),
			INDEX idx_actor (actor_id),
			INDEX idx_created (created_at)
		) $charset_collate;";

		$wpdb->query( $sql );
	}

	/**
	 * Create pro-rata calculation tables
	 *
	 * @return void
	 */
	private function create_prorata_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Pro-Rata Calculations Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_prorata_calculations (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			leave_policy_id BIGINT UNSIGNED NOT NULL,
			calculation_method ENUM('daily', 'monthly', 'annual') DEFAULT 'daily',
			start_date DATE NOT NULL,
			end_date DATE NOT NULL,
			total_days_entitled DECIMAL(10, 2),
			days_used DECIMAL(10, 2),
			days_remaining DECIMAL(10, 2),
			public_holidays_excluded INT DEFAULT 0,
			weekends_excluded INT DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			FOREIGN KEY (leave_policy_id) REFERENCES {$wpdb->prefix}leave_manager_leave_policies(id) ON DELETE CASCADE,
			INDEX idx_user (user_id),
			INDEX idx_policy (leave_policy_id),
			INDEX idx_date_range (start_date, end_date),
			INDEX idx_calculation_method (calculation_method)
		) $charset_collate;";

		$wpdb->query( $sql );
	}

	/**
	 * Create carry-over management tables
	 *
	 * @return void
	 */
	private function create_carryover_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Carry-Over Records Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_carryover (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			leave_type_id BIGINT UNSIGNED NOT NULL,
			fiscal_year INT NOT NULL,
			days_carried_over DECIMAL(10, 2),
			max_carryover_days DECIMAL(10, 2),
			expiry_date DATE,
			reason TEXT,
			approved_by BIGINT UNSIGNED,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			FOREIGN KEY (leave_type_id) REFERENCES {$wpdb->prefix}leave_manager_leave_types(id) ON DELETE CASCADE,
			FOREIGN KEY (approved_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL,
			INDEX idx_user (user_id),
			INDEX idx_fiscal_year (fiscal_year),
			INDEX idx_expiry (expiry_date)
		) $charset_collate;";

		$wpdb->query( $sql );
	}

	/**
	 * Create report tables
	 *
	 * @return void
	 */
	private function create_report_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Custom Reports Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_custom_reports (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(255) NOT NULL,
			description TEXT,
			report_type VARCHAR(50) NOT NULL,
			created_by BIGINT UNSIGNED NOT NULL,
			filters LONGTEXT,
			columns_selected LONGTEXT,
			sort_by VARCHAR(100),
			sort_order ENUM('ASC', 'DESC') DEFAULT 'ASC',
			is_public TINYINT(1) DEFAULT 0,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			INDEX idx_report_type (report_type),
			INDEX idx_created_by (created_by),
			INDEX idx_is_public (is_public)
		) $charset_collate;";

		$wpdb->query( $sql );

		// Scheduled Reports Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_scheduled_reports (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			custom_report_id BIGINT UNSIGNED NOT NULL,
			frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'annually') DEFAULT 'monthly',
			recipients TEXT NOT NULL,
			next_run_date DATETIME,
			last_run_date DATETIME,
			is_active TINYINT(1) DEFAULT 1,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			FOREIGN KEY (custom_report_id) REFERENCES {$wpdb->prefix}leave_manager_custom_reports(id) ON DELETE CASCADE,
			INDEX idx_frequency (frequency),
			INDEX idx_next_run (next_run_date),
			INDEX idx_is_active (is_active)
		) $charset_collate;";

		$wpdb->query( $sql );

		// Report Execution Log Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_report_logs (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			scheduled_report_id BIGINT UNSIGNED NOT NULL,
			execution_date DATETIME NOT NULL,
			status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
			rows_generated INT,
			execution_time_ms INT,
			error_message TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			FOREIGN KEY (scheduled_report_id) REFERENCES {$wpdb->prefix}leave_manager_scheduled_reports(id) ON DELETE CASCADE,
			INDEX idx_scheduled_report (scheduled_report_id),
			INDEX idx_status (status),
			INDEX idx_execution_date (execution_date)
		) $charset_collate;";

		$wpdb->query( $sql );
	}

	/**
	 * Create public holiday tables
	 *
	 * @return void
	 */
	private function create_public_holiday_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Public Holidays Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_public_holidays (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			country_code VARCHAR(2) NOT NULL,
			holiday_name VARCHAR(255) NOT NULL,
			holiday_date DATE NOT NULL,
			holiday_year INT NOT NULL,
			is_recurring TINYINT(1) DEFAULT 0,
			recurring_month INT,
			recurring_day INT,
			is_optional TINYINT(1) DEFAULT 0,
			description TEXT,
			source VARCHAR(50),
			created_by BIGINT UNSIGNED,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			FOREIGN KEY (created_by) REFERENCES {$wpdb->users}(ID) ON DELETE SET NULL,
			INDEX idx_country (country_code),
			INDEX idx_date (holiday_date),
			INDEX idx_year (holiday_year),
			INDEX idx_recurring (is_recurring),
			UNIQUE KEY unique_holiday (country_code, holiday_date)
		) $charset_collate;";

		$wpdb->query( $sql );

		// Country Holiday Settings Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_holiday_settings (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			country_code VARCHAR(2) NOT NULL PRIMARY KEY,
			country_name VARCHAR(255) NOT NULL,
			region VARCHAR(100),
			is_enabled TINYINT(1) DEFAULT 1,
			auto_sync TINYINT(1) DEFAULT 1,
			last_sync_date DATETIME,
			api_source VARCHAR(50),
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_enabled (is_enabled)
		) $charset_collate;";

		$wpdb->query( $sql );
	}

	/**
	 * Create audit tables
	 *
	 * @return void
	 */
	private function create_audit_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// System Audit Log Table
		$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}leave_manager_audit_log (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT UNSIGNED NOT NULL,
			action VARCHAR(100) NOT NULL,
			entity_type VARCHAR(50) NOT NULL,
			entity_id BIGINT UNSIGNED,
			old_values LONGTEXT,
			new_values LONGTEXT,
			ip_address VARCHAR(45),
			user_agent TEXT,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			INDEX idx_user (user_id),
			INDEX idx_action (action),
			INDEX idx_entity (entity_type, entity_id),
			INDEX idx_created (created_at)
		) $charset_collate;";

		$wpdb->query( $sql );
	}

	/**
	 * Create database indexes for performance
	 *
	 * @return void
	 */
	private function create_indexes() {
		global $wpdb;

		// Add indexes to existing tables if they don't exist
		$indexes = array(
			"ALTER TABLE {$wpdb->prefix}leave_manager_leave_requests ADD INDEX idx_user_status (user_id, status) IF NOT EXISTS",
			"ALTER TABLE {$wpdb->prefix}leave_manager_leave_requests ADD INDEX idx_dates (start_date, end_date) IF NOT EXISTS",
			"ALTER TABLE {$wpdb->prefix}leave_manager_leave_requests ADD INDEX idx_created (created_at) IF NOT EXISTS",
		);

		foreach ( $indexes as $index ) {
			$wpdb->query( $index );
		}
	}

	/**
	 * Get database version
	 *
	 * @return string
	 */
	public function get_db_version() {
		return $this->db_version;
	}
}

// Initialize migration
new Leave_Manager_Database_Migration();
