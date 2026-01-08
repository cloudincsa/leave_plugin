<?php
/**
 * Admin Menu class for Leave Manager Plugin
 *
 * Handles admin menu structure and page registration.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Admin_Menu class
 */
class Leave_Manager_Admin_Menu {

	/**
	 * Database instance
	 *
	 * @var Leave_Manager_Database
	 */
	private $db;

	/**
	 * Logger instance
	 *
	 * @var Leave_Manager_Logger
	 */
	private $logger;

	/**
	 * Settings instance
	 *
	 * @var Leave_Manager_Settings
	 */
	private $settings;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 * @param Leave_Manager_Settings $settings Settings instance
	 */
	public function __construct( $db, $logger, $settings ) {
		$this->db       = $db;
		$this->logger   = $logger;
		$this->settings = $settings;

		// Register admin menu
		add_action( 'admin_menu', array( $this, 'register_menu' ) );

		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register admin menu
	 *
	 * @return void
	 */
	public function register_menu() {
		// Check if setup is needed
		$detector = new Leave_Manager_Setup_Detector( $this->db, $this->logger );
		if ( $detector->needs_setup() ) {
			// Show setup wizard as main menu if setup is needed
			add_menu_page(
				'Leave Manager Setup',
				'Leave Management Setup',
				'manage_options',
				'leave-manager-setup',
				array( $this, 'render_setup_wizard' ),
				'dashicons-admin-generic',
				30
			);
			return; // Don't register other menus until setup is complete
		}

		// Main menu
		add_menu_page(
			'Leave Management',
			'Leave Management',
			'manage_options',
			'leave-manager-management',
			array( $this, 'render_dashboard' ),
			'dashicons-calendar',
			30
		);

		// Dashboard submenu
		add_submenu_page(
			'leave-manager-management',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'leave-manager-management',
			array( $this, 'render_dashboard' )
		);

		// Settings submenu
		add_submenu_page(
			'leave-manager-management',
			'Settings',
			'Settings',
			'manage_options',
			'leave-manager-settings',
			array( $this, 'render_settings' )
		);

		// Email Templates submenu
		add_submenu_page(
			'leave-manager-management',
			'Email Templates',
			'Email Templates',
			'manage_options',
			'leave-manager-email-templates',
			array( $this, 'render_email_templates' )
		);

		// User Management submenu
		add_submenu_page(
			'leave-manager-management',
			'User Management',
			'User Management',
			'manage_options',
			'leave-manager-users',
			array( $this, 'render_user_management' )
		);

		// Leave Policies submenu
		add_submenu_page(
			'leave-manager-management',
			'Leave Policies',
			'Leave Policies',
			'manage_options',
			'leave-manager-policies',
			array( $this, 'render_leave_policies' )
		);

		// Leave Requests submenu
		add_submenu_page(
			'leave-manager-management',
			'Leave Requests',
			'Leave Requests',
			'manage_options',
			'leave-manager-requests',
			array( $this, 'render_leave_requests' )
		);

		// System Logs submenu
		add_submenu_page(
			'leave-manager-management',
			'System Logs',
			'System Logs',
			'manage_options',
			'leave-manager-logs',
			array( $this, 'render_system_logs' )
		);

		// Diagnostics submenu
		add_submenu_page(
			'leave-manager-management',
			'Diagnostics',
			'Diagnostics',
			'manage_options',
			'leave-manager-diagnostics',
			array( $this, 'render_diagnostics' )
		);

		// Roles and Permissions submenu
		add_submenu_page(
			'leave-manager-management',
			'Roles & Permissions',
			'Roles & Permissions',
			'manage_options',
			'leave-manager-roles-permissions',
			array( $this, 'render_roles_permissions' )
		);

		// Reports submenu
		add_submenu_page(
			'leave-manager-management',
			'Reports',
			'Reports',
			'manage_options',
			'leave-manager-reports',
			array( $this, 'render_reports' )
		);

		// Export submenu
		add_submenu_page(
			'leave-manager-management',
			'Export',
			'Export',
			'manage_options',
			'leave-manager-export',
			array( $this, 'render_export' )
		);

		// Email Template Editor submenu
		add_submenu_page(
			'leave-manager-management',
			'Email Template Editor',
			'Email Template Editor',
			'manage_options',
			'leave-manager-email-editor',
			array( $this, 'render_email_template_editor' )
		);
	}

	/**
	 * Render setup wizard page
	 *
	 * @return void
	 */
	public function render_setup_wizard() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/setup-wizard.php';
	}

	/**
	 * Render dashboard page
	 *
	 * @return void
	 */
	public function render_dashboard() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/dashboard.php';
	}

	/**
	 * Render leave policies page
	 *
	 * @return void
	 */
	public function render_leave_policies() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/leave-policies.php';
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_settings() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/settings.php';
	}

	/**
	 * Render email templates page
	 *
	 * @return void
	 */
	public function render_email_templates() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/email-templates.php';
	}

	/**
	 * Render user management page
	 *
	 * @return void
	 */
	public function render_user_management() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/user-management.php';
	}

	/**
	 * Render leave requests page
	 *
	 * @return void
	 */
	public function render_leave_requests() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/leave-requests.php';
	}

	/**
	 * Render system logs page
	 *
	 * @return void
	 */
	public function render_system_logs() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/system-logs.php';
	}

	/**
	 * Render diagnostics page
	 *
	 * @return void
	 */
	public function render_diagnostics() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/diagnostics.php';
	}

	/**
	 * Render roles and permissions page
	 *
	 * @return void
	 */
	public function render_roles_permissions() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/roles-permissions.php';
	}

	/**
	 * Render reports page
	 *
	 * @return void
	 */
	public function render_reports() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/reports.php';
	}

	/**
	 * Render export page
	 *
	 * @return void
	 */
	public function render_export() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/export.php';
	}

	/**
	 * Render email template editor page
	 *
	 * @return void
	 */
	public function render_email_template_editor() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/email-template-editor.php';
	}

	/**
	 * Enqueue admin assets
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_style(
			'leave-manager-admin',
			LEAVE_MANAGER_PLUGIN_URL . 'admin/css/admin-styles.css',
			array(),
			LEAVE_MANAGER_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'leave-manager-admin',
			LEAVE_MANAGER_PLUGIN_URL . 'admin/js/admin-scripts.js',
			array( 'jquery' ),
			LEAVE_MANAGER_PLUGIN_VERSION,
			true
		);
	}
}
