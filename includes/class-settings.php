<?php
/**
 * Settings class for Leave Manager Plugin
 *
 * Handles all plugin settings and configuration.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Settings class
 */
class Leave_Manager_Settings {

	/**
	 * Database instance
	 *
	 * @var Leave_Manager_Database
	 */
	private $db;

	/**
	 * Settings table name
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Logger instance
	 *
	 * @var Leave_Manager_Logger|null
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database      $db Database instance
	 * @param Leave_Manager_Logger|null $logger Logger instance (optional)
	 */
	public function __construct( $db, $logger = null ) {
		$this->db     = $db;
		$this->logger = $logger;
		$this->table  = $db->settings_table;
	}

	/**
	 * Initialize default settings
	 *
	 * @return void
	 */
	public function init_defaults() {
		$defaults = array(
			// Organization settings
			'organization_name'      => 'Leave Manager',
			'organization_email'     => get_option( 'admin_email' ),
			'organization_phone'     => '',
			'organization_address'   => '',
			'organization_website'   => get_option( 'siteurl' ),

			// Subdomain settings
			'subdomain_enabled'      => false,
			'subdomain_name'         => 'leave',

			// Email (SMTP) settings
			'smtp_host'              => '',
			'smtp_port'              => 587,
			'smtp_username'          => '',
			'smtp_password'          => '',
			'smtp_encryption'        => 'tls',
			'smtp_from_name'         => 'Leave Manager',
			'smtp_from_email'        => get_option( 'admin_email' ),

			// Leave settings
			'annual_leave_default'   => 20,
			'sick_leave_default'     => 10,
			'other_leave_default'    => 5,
			'weekend_counting'       => false,
			'allow_edit_requests'    => true,
			'require_reapproval'     => false,

			// Notification settings
			'notify_welcome_email'   => true,
			'notify_leave_request'   => true,
			'notify_leave_approval'  => true,
			'notify_leave_rejection' => true,
			'notify_password_reset'  => true,

			// Calendar settings
			'calendar_start_day'     => 0, // 0 = Sunday, 1 = Monday

			// Security settings
			'session_timeout'        => 3600,
			'password_min_length'    => 8,

			// Display settings
			'date_format'            => 'Y-m-d',
			'time_format'            => 'H:i:s',
			'timezone'               => get_option( 'timezone_string' ),
			'items_per_page'         => 20,
		);

		foreach ( $defaults as $key => $value ) {
			if ( ! $this->get( $key ) ) {
				$this->set( $key, $value );
			}
		}
	}

	/**
	 * Get a setting value
	 *
	 * @param string $key Setting key
	 * @param mixed  $default Default value if not found
	 * @return mixed Setting value
	 */
	public function get( $key, $default = null ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT setting_value, setting_type FROM {$this->table} WHERE setting_key = %s",
			$key
		);
		$result = $wpdb->get_row( $query );

		if ( ! $result ) {
			return $default;
		}

		// Decode based on type
		switch ( $result->setting_type ) {
			case 'boolean':
				return (bool) $result->setting_value;
			case 'number':
				return (int) $result->setting_value;
			case 'array':
				return maybe_unserialize( $result->setting_value );
			default:
				return $result->setting_value;
		}
	}

	/**
	 * Set a setting value
	 *
	 * @param string $key Setting key
	 * @param mixed  $value Setting value
	 * @param string $type Setting type (string, number, boolean, array)
	 * @return bool True on success
	 */
	public function set( $key, $value, $type = 'string' ) {
		global $wpdb;

		// Determine type if not specified
		if ( 'string' === $type ) {
			if ( is_bool( $value ) ) {
				$type = 'boolean';
				$value = $value ? '1' : '0';
			} elseif ( is_numeric( $value ) && ! is_string( $value ) ) {
				$type = 'number';
			} elseif ( is_array( $value ) || is_object( $value ) ) {
				$type = 'array';
				$value = maybe_serialize( $value );
			}
		}

		// Check if setting exists
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE setting_key = %s",
				$key
			)
		);

		if ( $exists ) {
			return $wpdb->update(
				$this->table,
				array(
					'setting_value' => $value,
					'setting_type'  => $type,
				),
				array( 'setting_key' => $key ),
				array( '%s', '%s' ),
				array( '%s' )
			);
		} else {
			return $wpdb->insert(
				$this->table,
				array(
					'setting_key'   => $key,
					'setting_value' => $value,
					'setting_type'  => $type,
				),
				array( '%s', '%s', '%s' )
			);
		}
	}

	/**
	 * Delete a setting
	 *
	 * @param string $key Setting key
	 * @return bool True on success
	 */
	public function delete( $key ) {
		global $wpdb;
		return $wpdb->delete(
			$this->table,
			array( 'setting_key' => $key ),
			array( '%s' )
		);
	}

	/**
	 * Get all settings
	 *
	 * @return array All settings
	 */
	public function get_all() {
		global $wpdb;
		$results = $wpdb->get_results( $wpdb->prepare( "SELECT setting_key, setting_value, setting_type FROM {$this->table}" ) );

		$settings = array();
		foreach ( $results as $result ) {
			$value = $result->setting_value;

			// Decode based on type
			switch ( $result->setting_type ) {
				case 'boolean':
					$value = (bool) $value;
					break;
				case 'number':
					$value = (int) $value;
					break;
				case 'array':
					$value = maybe_unserialize( $value );
					break;
			}

			$settings[ $result->setting_key ] = $value;
		}

		return $settings;
	}

	/**
	 * Reset settings to defaults
	 *
	 * @return bool True on success
	 */
	public function reset_to_defaults() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "TRUNCATE TABLE {$this->table}" ) );
		$this->init_defaults();
		return true;
	}
}
