<?php
/**
 * Database class for Leave Manager Plugin
 *
 * Handles all database operations including table creation and management.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Database class
 */
class Leave_Manager_Database {

	/**
	 * WordPress database object
	 *
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * Table names with prefix
	 *
	 * @var string
	 */
	public $users_table;
	public $leave_requests_table;
	public $email_logs_table;
	public $settings_table;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		// Set table names with prefix
		$this->users_table          = $wpdb->prefix . 'leave_manager_leave_users';
		$this->leave_requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
		$this->email_logs_table     = $wpdb->prefix . 'leave_manager_email_logs';
		$this->settings_table       = $wpdb->prefix . 'leave_manager_settings';
	}

	/**
	 * Create all database tables
	 * 
	 * IMPORTANT: dbDelta() has strict formatting requirements:
	 * - DO NOT use "CREATE TABLE IF NOT EXISTS" - just use "CREATE TABLE"
	 * - Use TWO spaces before "PRIMARY KEY"
	 * - Put each column on its own line
	 * - Use lowercase for column types
	 *
	 * @return array Results from dbDelta
	 */
	public function create_tables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $this->wpdb->get_charset_collate();
		$results = array();

		// Users table
		$sql = "CREATE TABLE {$this->users_table} (
			user_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			wp_user_id bigint(20) unsigned DEFAULT NULL,
			first_name varchar(100) DEFAULT NULL,
			last_name varchar(100) DEFAULT NULL,
			email varchar(100) NOT NULL,
			phone varchar(20) DEFAULT NULL,
			role varchar(20) NOT NULL DEFAULT 'staff',
			department varchar(100) DEFAULT NULL,
			position varchar(100) DEFAULT NULL,
			policy_id bigint(20) unsigned DEFAULT NULL,
			annual_leave_balance decimal(10,2) DEFAULT 0,
			sick_leave_balance decimal(10,2) DEFAULT 0,
			other_leave_balance decimal(10,2) DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (user_id),
			UNIQUE KEY email (email),
			KEY role (role),
			KEY status (status),
			KEY wp_user_id (wp_user_id),
			KEY policy_id (policy_id),
			KEY idx_role_status (role,status)
		) $charset_collate;";
		$results['users'] = dbDelta( $sql );

		// Leave Requests table
		$sql = "CREATE TABLE {$this->leave_requests_table} (
			request_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			leave_type varchar(50) NOT NULL,
			start_date date NOT NULL,
			end_date date NOT NULL,
			reason text DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			approved_by bigint(20) unsigned DEFAULT NULL,
			approval_date datetime DEFAULT NULL,
			rejection_reason text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (request_id),
			KEY user_id (user_id),
			KEY status (status),
			KEY leave_type (leave_type),
			KEY start_date (start_date),
			KEY end_date (end_date),
			KEY idx_user_status (user_id,status),
			KEY idx_dates (start_date,end_date),
			KEY idx_user_dates (user_id,start_date,end_date)
		) $charset_collate;";
		$results['leave_requests'] = dbDelta( $sql );

		// Email Logs table
		$sql = "CREATE TABLE {$this->email_logs_table} (
			log_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			recipient_email varchar(100) NOT NULL,
			subject varchar(255) DEFAULT NULL,
			template_used varchar(100) DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			error_message text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (log_id),
			KEY recipient_email (recipient_email),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";
		$results['email_logs'] = dbDelta( $sql );

		// Settings table
		$sql = "CREATE TABLE {$this->settings_table} (
			setting_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			setting_key varchar(100) NOT NULL,
			setting_value longtext DEFAULT NULL,
			setting_type varchar(50) NOT NULL DEFAULT 'string',
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (setting_id),
			UNIQUE KEY setting_key (setting_key)
		) $charset_collate;";
		$results['settings'] = dbDelta( $sql );

		// Email Queue table
		$table = $this->wpdb->prefix . 'leave_manager_email_queue';
		$sql = "CREATE TABLE {$table} (
			queue_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			recipient_email varchar(100) NOT NULL,
			subject varchar(255) DEFAULT NULL,
			template_used varchar(100) DEFAULT NULL,
			variables longtext DEFAULT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			retry_count int DEFAULT 0,
			error_message text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (queue_id),
			KEY status (status),
			KEY created_at (created_at),
			KEY idx_status_retry (status,retry_count)
		) $charset_collate;";
		$results['email_queue'] = dbDelta( $sql );

		// Request History table
		$table = $this->wpdb->prefix . 'leave_manager_request_history';
		$sql = "CREATE TABLE {$table} (
			history_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_id bigint(20) unsigned NOT NULL,
			action varchar(50) NOT NULL,
			changed_by bigint(20) unsigned DEFAULT NULL,
			old_value longtext DEFAULT NULL,
			new_value longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (history_id),
			KEY request_id (request_id),
			KEY action (action),
			KEY created_at (created_at)
		) $charset_collate;";
		$results['request_history'] = dbDelta( $sql );

		// Audit Log table
		$table = $this->wpdb->prefix . 'leave_manager_audit_logs';
		$sql = "CREATE TABLE {$table} (
			log_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			action varchar(50) NOT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			details longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (log_id),
			KEY action (action),
			KEY user_id (user_id),
			KEY created_at (created_at)
		) $charset_collate;";
		$results['audit_logs'] = dbDelta( $sql );

		// SMS Logs table
		$table = $this->wpdb->prefix . 'leave_manager_sms_logs';
		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			phone_number varchar(20) NOT NULL,
			message text NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			notification_type varchar(50) DEFAULT NULL,
			response_data longtext DEFAULT NULL,
			sent_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY phone_number (phone_number),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";
		$results['sms_logs'] = dbDelta( $sql );

		// Leave Policies table
		$table = $this->wpdb->prefix . 'leave_manager_leave_policies';
		$sql = "CREATE TABLE {$table} (
			policy_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			policy_name varchar(255) NOT NULL,
			description longtext DEFAULT NULL,
			leave_type varchar(50) NOT NULL,
			annual_days decimal(5,2) DEFAULT 20,
			carryover_days decimal(5,2) DEFAULT 5,
			expiry_days int DEFAULT 365,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (policy_id),
			KEY status (status),
			KEY leave_type (leave_type)
		) $charset_collate;";
		$results['leave_policies'] = dbDelta( $sql );

		// Policy Rules table
		$table = $this->wpdb->prefix . 'leave_manager_policy_rules';
		$sql = "CREATE TABLE {$table} (
			rule_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			policy_id bigint(20) unsigned NOT NULL,
			rule_name varchar(255) NOT NULL,
			rule_type varchar(50) NOT NULL,
			rule_value varchar(255) DEFAULT NULL,
			description longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (rule_id),
			KEY policy_id (policy_id)
		) $charset_collate;";
		$results['policy_rules'] = dbDelta( $sql );

		// Approval Workflows table
		$table = $this->wpdb->prefix . 'leave_manager_approval_workflows';
		$sql = "CREATE TABLE {$table} (
			workflow_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			workflow_name varchar(255) NOT NULL,
			description longtext DEFAULT NULL,
			leave_type varchar(50) NOT NULL,
			approval_chain longtext DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (workflow_id),
			KEY leave_type (leave_type),
			KEY status (status)
		) $charset_collate;";
		$results['approval_workflows'] = dbDelta( $sql );

		// Approvals table
		$table = $this->wpdb->prefix . 'leave_manager_approvals';
		$sql = "CREATE TABLE {$table} (
			approval_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_id bigint(20) unsigned NOT NULL,
			workflow_id bigint(20) unsigned DEFAULT NULL,
			approver_id bigint(20) unsigned NOT NULL,
			approval_level int DEFAULT 1,
			status varchar(20) DEFAULT 'pending',
			comments longtext DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (approval_id),
			KEY request_id (request_id),
			KEY approver_id (approver_id),
			KEY status (status)
		) $charset_collate;";
		$results['approvals'] = dbDelta( $sql );

		// Teams table
		$table = $this->wpdb->prefix . 'leave_manager_teams';
		$sql = "CREATE TABLE {$table} (
			team_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			team_name varchar(255) NOT NULL,
			description longtext DEFAULT NULL,
			department varchar(255) DEFAULT NULL,
			manager_id bigint(20) unsigned DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (team_id),
			KEY manager_id (manager_id),
			KEY status (status)
		) $charset_collate;";
		$results['teams'] = dbDelta( $sql );

		// Team Members table
		$table = $this->wpdb->prefix . 'leave_manager_team_members';
		$sql = "CREATE TABLE {$table} (
			member_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			team_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			role varchar(50) DEFAULT 'member',
			joined_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (member_id),
			UNIQUE KEY team_user (team_id,user_id),
			KEY user_id (user_id)
		) $charset_collate;";
		$results['team_members'] = dbDelta( $sql );

		// Employee Signups table
		$table = $this->wpdb->prefix . 'leave_manager_employee_signups';
		$sql = "CREATE TABLE {$table} (
			signup_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			first_name varchar(100) NOT NULL,
			last_name varchar(100) NOT NULL,
			email varchar(100) NOT NULL,
			phone varchar(20) DEFAULT NULL,
			department varchar(100) DEFAULT NULL,
			position varchar(100) DEFAULT NULL,
			status varchar(20) DEFAULT 'pending',
			token varchar(64) DEFAULT NULL,
			token_expires datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (signup_id),
			UNIQUE KEY email (email),
			KEY status (status),
			KEY token (token)
		) $charset_collate;";
		$results['employee_signups'] = dbDelta( $sql );

		// Webhooks table
		$table = $this->wpdb->prefix . 'leave_manager_webhooks';
		$sql = "CREATE TABLE {$table} (
			webhook_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			url varchar(500) NOT NULL,
			events longtext DEFAULT NULL,
			secret varchar(255) DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (webhook_id),
			KEY status (status)
		) $charset_collate;";
		$results['webhooks'] = dbDelta( $sql );

		// Two Factor Auth table
		$table = $this->wpdb->prefix . 'leave_manager_two_factor_auth';
		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			secret varchar(255) NOT NULL,
			backup_codes longtext DEFAULT NULL,
			enabled tinyint(1) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_id (user_id)
		) $charset_collate;";
		$results['two_factor_auth'] = dbDelta( $sql );

		// Leave Balances table
		$table = $this->wpdb->prefix . 'leave_manager_leave_balances';
		$sql = "CREATE TABLE {$table} (
			balance_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			leave_type varchar(50) NOT NULL,
			year int NOT NULL,
			allocated decimal(10,2) DEFAULT 0,
			used decimal(10,2) DEFAULT 0,
			carried_over decimal(10,2) DEFAULT 0,
			adjustment decimal(10,2) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (balance_id),
			UNIQUE KEY user_type_year (user_id,leave_type,year),
			KEY user_id (user_id),
			KEY leave_type (leave_type),
			KEY year (year)
		) $charset_collate;";
		$results['leave_balances'] = dbDelta( $sql );

		// Leave Types table
		$table = $this->wpdb->prefix . 'leave_manager_leave_types';
		$sql = "CREATE TABLE {$table} (
			type_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			type_name varchar(100) NOT NULL,
			type_code varchar(50) NOT NULL,
			description text DEFAULT NULL,
			default_days decimal(5,2) DEFAULT 0,
			color varchar(20) DEFAULT '#3498db',
			requires_approval tinyint(1) DEFAULT 1,
			is_paid tinyint(1) DEFAULT 1,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (type_id),
			UNIQUE KEY type_code (type_code),
			KEY status (status)
		) $charset_collate;";
		$results['leave_types'] = dbDelta( $sql );

		// Departments table
		$table = $this->wpdb->prefix . 'leave_manager_departments';
		$sql = "CREATE TABLE {$table} (
			department_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			department_name varchar(255) NOT NULL,
			department_code varchar(50) DEFAULT NULL,
			description text DEFAULT NULL,
			manager_id bigint(20) unsigned DEFAULT NULL,
			parent_id bigint(20) unsigned DEFAULT NULL,
			status varchar(20) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (department_id),
			KEY manager_id (manager_id),
			KEY parent_id (parent_id),
			KEY status (status)
		) $charset_collate;";
		$results['departments'] = dbDelta( $sql );

		// Rate Limits table
		$table = $this->wpdb->prefix . 'leave_manager_rate_limits';
		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			identifier varchar(255) NOT NULL,
			action varchar(100) NOT NULL,
			count int DEFAULT 1,
			window_start datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY identifier_action (identifier,action),
			KEY window_start (window_start)
		) $charset_collate;";
		$results['rate_limits'] = dbDelta( $sql );

		// Policy Assignments table
		$table = $this->wpdb->prefix . 'leave_manager_policy_assignments';
		$sql = "CREATE TABLE {$table} (
			assignment_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			policy_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			department_id bigint(20) unsigned DEFAULT NULL,
			team_id bigint(20) unsigned DEFAULT NULL,
			priority int DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (assignment_id),
			KEY policy_id (policy_id),
			KEY user_id (user_id),
			KEY department_id (department_id),
			KEY team_id (team_id)
		) $charset_collate;";
		$results['policy_assignments'] = dbDelta( $sql );

		return $results;
	}

	/**
	 * Drop database tables
	 *
	 * @return void
	 */
	public function drop_tables() {
		$tables = array(
			'leave_manager_leave_requests',
			'leave_manager_leave_users',
			'leave_manager_email_logs',
			'leave_manager_settings',
			'leave_manager_email_queue',
			'leave_manager_request_history',
			'leave_manager_audit_logs',
			'leave_manager_sms_logs',
			'leave_manager_leave_policies',
			'leave_manager_policy_rules',
			'leave_manager_approval_workflows',
			'leave_manager_approvals',
			'leave_manager_teams',
			'leave_manager_team_members',
			'leave_manager_employee_signups',
			'leave_manager_webhooks',
			'leave_manager_two_factor_auth',
			'leave_manager_leave_balances',
			'leave_manager_leave_types',
			'leave_manager_departments',
			'leave_manager_rate_limits',
			'leave_manager_policy_assignments',
		);

		foreach ( $tables as $table ) {
			$this->wpdb->query( "DROP TABLE IF EXISTS {$this->wpdb->prefix}{$table}" );
		}
	}

	/**
	 * Begin database transaction
	 *
	 * @return void
	 */
	public function begin_transaction() {
		$this->wpdb->query( 'START TRANSACTION' );
	}

	/**
	 * Commit database transaction
	 *
	 * @return void
	 */
	public function commit() {
		$this->wpdb->query( 'COMMIT' );
	}

	/**
	 * Rollback database transaction
	 *
	 * @return void
	 */
	public function rollback() {
		$this->wpdb->query( 'ROLLBACK' );
	}

	/**
	 * Get last database error
	 *
	 * @return string Last error message
	 */
	public function get_last_error() {
		return $this->wpdb->last_error;
	}

	/**
	 * Get last executed query
	 *
	 * @return string Last query
	 */
	public function get_last_query() {
		return $this->wpdb->last_query;
	}

	/**
	 * Count records in a table
	 *
	 * @param string $table Table name
	 * @param array  $where WHERE conditions
	 * @return int Record count
	 */
	public function count_records( $table, $where = array() ) {
		$query = "SELECT COUNT(*) FROM $table WHERE 1=1";
		$values = array();

		foreach ( $where as $column => $value ) {
			$query .= " AND $column = %s";
			$values[] = $value;
		}

		if ( ! empty( $values ) ) {
			return intval( $this->wpdb->get_var( $this->wpdb->prepare( $query, $values ) ) );
		}

		return intval( $this->wpdb->get_var( $query ) );
	}

	/**
	 * Insert a record into a table
	 *
	 * @param string $table Table name
	 * @param array  $data Data to insert
	 * @param array  $format Data format
	 * @return int|false Insert ID or false on error
	 */
	public function insert( $table, $data, $format = null ) {
		$result = $this->wpdb->insert( $table, $data, $format );
		return $result ? $this->wpdb->insert_id : false;
	}

	/**
	 * Update a record in a table
	 *
	 * @param string $table Table name
	 * @param array  $data Data to update
	 * @param array  $where Where clause
	 * @param array  $format Data format
	 * @param array  $where_format Where clause format
	 * @return int|false Rows affected or false on error
	 */
	public function update( $table, $data, $where, $format = null, $where_format = null ) {
		return $this->wpdb->update( $table, $data, $where, $format, $where_format );
	}

	/**
	 * Delete a record from a table
	 *
	 * @param string $table Table name
	 * @param array  $where Where clause
	 * @param array  $where_format Where clause format
	 * @return int|false Rows affected or false on error
	 */
	public function delete( $table, $where, $where_format = null ) {
		return $this->wpdb->delete( $table, $where, $where_format );
	}

	/**
	 * Get a single row from a table
	 *
	 * @param string $query SQL query
	 * @return object|null Row object or null
	 */
	public function get_row( $query ) {
		return $this->wpdb->get_row( $query );
	}

	/**
	 * Get multiple rows from a table
	 *
	 * @param string $query SQL query
	 * @return array Array of row objects
	 */
	public function get_results( $query ) {
		return $this->wpdb->get_results( $query );
	}

	/**
	 * Get a single value from a query
	 *
	 * @param string $query SQL query
	 * @return mixed Single value
	 */
	public function get_var( $query ) {
		return $this->wpdb->get_var( $query );
	}

	/**
	 * Execute a custom query
	 *
	 * @param string $query SQL query
	 * @return mixed Query result
	 */
	public function query( $query ) {
		return $this->wpdb->query( $query );
	}

	/**
	 * Get WordPress database object
	 *
	 * @return wpdb WordPress database object
	 */
	public function get_wpdb() {
		return $this->wpdb;
	}

	/**
	 * Get SMS logs table name
	 *
	 * @return string SMS logs table name
	 */
	public function get_sms_logs_table() {
		return $this->wpdb->prefix . 'leave_manager_sms_logs';
	}

	/**
	 * Check if all required tables exist
	 *
	 * @return array Array of table names and their existence status
	 */
	public function check_tables_exist() {
		$tables = array(
			'leave_manager_leave_users',
			'leave_manager_leave_requests',
			'leave_manager_email_logs',
			'leave_manager_settings',
			'leave_manager_email_queue',
			'leave_manager_request_history',
			'leave_manager_audit_logs',
			'leave_manager_sms_logs',
			'leave_manager_leave_policies',
			'leave_manager_policy_rules',
			'leave_manager_approval_workflows',
			'leave_manager_approvals',
			'leave_manager_teams',
			'leave_manager_team_members',
			'leave_manager_employee_signups',
			'leave_manager_webhooks',
			'leave_manager_two_factor_auth',
			'leave_manager_leave_balances',
			'leave_manager_leave_types',
			'leave_manager_departments',
			'leave_manager_rate_limits',
			'leave_manager_policy_assignments',
		);

		$status = array();
		foreach ( $tables as $table ) {
			$full_table = $this->wpdb->prefix . $table;
			$exists = $this->wpdb->get_var( "SHOW TABLES LIKE '$full_table'" ) === $full_table;
			$status[ $table ] = $exists;
		}

		return $status;
	}
}
