<?php
/**
 * Autoloader class for Leave Manager Plugin
 *
 * Automatically loads plugin classes on demand.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Autoloader class
 */
class Leave_Manager_Autoloader {

	/**
	 * Plugin directory path
	 *
	 * @var string
	 */
	private static $plugin_dir = '';

	/**
	 * Class mapping
	 *
	 * @var array
	 */
	private static $class_map = array(
		'Leave_Manager_Database'         => 'includes/class-database.php',
		'Leave_Manager_Logger'           => 'includes/class-logger.php',
		'Leave_Manager_Settings'         => 'includes/class-settings.php',
		'Leave_Manager_Permissions'      => 'includes/class-permissions.php',
		'Leave_Manager_Users'            => 'includes/class-users.php',
		'Leave_Manager_Leave_Requests'   => 'includes/class-leave-requests.php',
		'Leave_Manager_Email_Handler'    => 'includes/class-email-handler.php',
		'Leave_Manager_Email_Queue'      => 'includes/class-email-queue.php',
		'Leave_Manager_Request_History'  => 'includes/class-request-history.php',
		'Leave_Manager_Audit_Logger'     => 'includes/class-audit-logger.php',
		'Leave_Manager_API_Handler'      => 'includes/class-api-handler.php',
		'Leave_Manager_Export'           => 'includes/class-export.php',
		'Leave_Manager_Calendar'         => 'includes/class-calendar.php',
		'Leave_Manager_Frontend_Pages'   => 'includes/class-frontend-pages.php',
		'Leave_Manager_Leave_Policies'   => 'includes/class-leave-policies.php',
		'Leave_Manager_Leave_Types'      => 'includes/class-leave-types.php',
		'Leave_Manager_Departments'      => 'includes/class-departments.php',
		'Leave_Manager_Advanced_Workflow' => 'includes/class-advanced-workflow.php',
		'Leave_Manager_Team_Management'  => 'includes/class-team-management.php',
		'Leave_Manager_Bulk_Operations'  => 'includes/class-bulk-operations.php',
		'Leave_Manager_Plugin'           => 'includes/class-plugin.php',
		'Leave_Manager_Admin_Menu'       => 'admin/admin-menu.php',
	);

	/**
	 * Initialize autoloader
	 *
	 * @return void
	 */
	public static function init() {
		self::$plugin_dir = defined( 'LEAVE_MANAGER_PLUGIN_DIR' ) ? LEAVE_MANAGER_PLUGIN_DIR : dirname( dirname( __FILE__ ) ) . '/';
		spl_autoload_register( array( __CLASS__, 'load_class' ) );
	}

	/**
	 * Load class file
	 *
	 * @param string $class_name Class name to load
	 * @return bool True if class was loaded
	 */
	public static function load_class( $class_name ) {
		// Check if this is an Leave Manager class
		if ( strpos( $class_name, 'Leave_Manager_' ) !== 0 ) {
			return false;
		}

		// Check if class is in our map
		if ( ! isset( self::$class_map[ $class_name ] ) ) {
			return false;
		}

		$file_path = self::$plugin_dir . self::$class_map[ $class_name ];

		// Load the file if it exists
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
			return true;
		}

		return false;
	}

	/**
	 * Preload all classes
	 *
	 * @return void
	 */
	public static function preload_all() {
		foreach ( self::$class_map as $class_name => $file_path ) {
			if ( ! class_exists( $class_name ) ) {
				self::load_class( $class_name );
			}
		}
	}
}

// Initialize autoloader
Leave_Manager_Autoloader::init();
