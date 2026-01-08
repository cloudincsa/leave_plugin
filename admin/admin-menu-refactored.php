<?php
/**
 * Admin Menu class for Leave Manager Plugin (Refactored)
 *
 * Handles admin menu structure and page registration with simplified,
 * consolidated menu structure for better UX.
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
	 * Register admin menu with simplified structure
	 *
	 * New structure:
	 * - Dashboard
	 * - Leave Requests
	 * - Users & Policies
	 *   - User Management
	 *   - Leave Policies
	 *   - Roles & Permissions
	 * - Configuration
	 *   - General Settings
	 *   - Email Templates
	 *   - System Logs
	 *   - System Health
	 * - Reports & Export
	 *   - Leave Reports
	 *   - User Reports
	 *   - Department Reports
	 *   - Export Data
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

		// 1. Dashboard submenu
		add_submenu_page(
			'leave-manager-management',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'leave-manager-management',
			array( $this, 'render_dashboard' )
		);

		// 2. Leave Requests submenu
		add_submenu_page(
			'leave-manager-management',
			'Leave Requests',
			'Leave Requests',
			'manage_options',
			'leave-manager-requests',
			array( $this, 'render_leave_requests' )
		);

		// 3. Users & Policies submenu group
		add_submenu_page(
			'leave-manager-management',
			'Users & Policies',
			'Users & Policies',
			'manage_options',
			'leave-manager-users-policies',
			array( $this, 'render_users_policies_overview' )
		);

		// 3.1 User Management (under Users & Policies)
		add_submenu_page(
			'leave-manager-management',
			'User Management',
			'  ├─ User Management',
			'manage_options',
			'leave-manager-users',
			array( $this, 'render_user_management' )
		);

		// 3.2 Leave Policies (under Users & Policies)
		add_submenu_page(
			'leave-manager-management',
			'Leave Policies',
			'  ├─ Leave Policies',
			'manage_options',
			'leave-manager-policies',
			array( $this, 'render_leave_policies' )
		);

		// 3.3 Roles & Permissions (under Users & Policies)
		add_submenu_page(
			'leave-manager-management',
			'Roles & Permissions',
			'  └─ Roles & Permissions',
			'manage_options',
			'leave-manager-roles-permissions',
			array( $this, 'render_roles_permissions' )
		);

		// 4. Configuration submenu group
		add_submenu_page(
			'leave-manager-management',
			'Configuration',
			'Configuration',
			'manage_options',
			'leave-manager-configuration',
			array( $this, 'render_configuration_overview' )
		);

		// 4.1 General Settings (under Configuration)
		add_submenu_page(
			'leave-manager-management',
			'General Settings',
			'  ├─ General Settings',
			'manage_options',
			'leave-manager-settings',
			array( $this, 'render_settings' )
		);

		// 4.2 Email Templates (merged, under Configuration)
		add_submenu_page(
			'leave-manager-management',
			'Email Templates',
			'  ├─ Email Templates',
			'manage_options',
			'leave-manager-email-templates',
			array( $this, 'render_email_templates_merged' )
		);

		// 4.3 System Logs (under Configuration)
		add_submenu_page(
			'leave-manager-management',
			'System Logs',
			'  ├─ System Logs',
			'manage_options',
			'leave-manager-logs',
			array( $this, 'render_system_logs' )
		);

		// 4.4 System Health (Diagnostics renamed, under Configuration)
		add_submenu_page(
			'leave-manager-management',
			'System Health',
			'  └─ System Health',
			'manage_options',
			'leave-manager-diagnostics',
			array( $this, 'render_diagnostics' )
		);

		// 5. Reports & Export submenu group
		add_submenu_page(
			'leave-manager-management',
			'Reports & Export',
			'Reports & Export',
			'manage_options',
			'leave-manager-reports-export',
			array( $this, 'render_reports_export_overview' )
		);

		// 5.1 Leave Reports (under Reports & Export)
		add_submenu_page(
			'leave-manager-management',
			'Leave Reports',
			'  ├─ Leave Reports',
			'manage_options',
			'leave-manager-reports',
			array( $this, 'render_reports' )
		);

		// 5.2 User Reports (under Reports & Export)
		add_submenu_page(
			'leave-manager-management',
			'User Reports',
			'  ├─ User Reports',
			'manage_options',
			'leave-manager-user-reports',
			array( $this, 'render_user_reports' )
		);

		// 5.3 Department Reports (under Reports & Export)
		add_submenu_page(
			'leave-manager-management',
			'Department Reports',
			'  ├─ Department Reports',
			'manage_options',
			'leave-manager-department-reports',
			array( $this, 'render_department_reports' )
		);

		// 5.4 Export Data (merged, under Reports & Export)
		add_submenu_page(
			'leave-manager-management',
			'Export Data',
			'  └─ Export Data',
			'manage_options',
			'leave-manager-export',
			array( $this, 'render_export_data_merged' )
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
	 * Render Users & Policies overview page
	 *
	 * @return void
	 */
	public function render_users_policies_overview() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Users & Policies', 'leave-manager-management' ); ?></h1>
			<div class="leave-manager-overview-cards">
				<div class="leave-manager-card">
					<h2><?php esc_html_e( 'User Management', 'leave-manager-management' ); ?></h2>
					<p><?php esc_html_e( 'Manage staff members, assign roles, and configure user permissions.', 'leave-manager-management' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-users' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Manage Users', 'leave-manager-management' ); ?></a>
				</div>
				<div class="leave-manager-card">
					<h2><?php esc_html_e( 'Leave Policies', 'leave-manager-management' ); ?></h2>
					<p><?php esc_html_e( 'Create and manage leave policies with different allocations for different roles.', 'leave-manager-management' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-policies' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Manage Policies', 'leave-manager-management' ); ?></a>
				</div>
				<div class="leave-manager-card">
					<h2><?php esc_html_e( 'Roles & Permissions', 'leave-manager-management' ); ?></h2>
					<p><?php esc_html_e( 'Configure user roles and their associated permissions.', 'leave-manager-management' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-roles-permissions' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Manage Roles', 'leave-manager-management' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Configuration overview page
	 *
	 * @return void
	 */
	public function render_configuration_overview() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Configuration', 'leave-manager-management' ); ?></h1>
			<div class="leave-manager-overview-cards">
				<div class="leave-manager-card">
					<h2><?php esc_html_e( 'General Settings', 'leave-manager-management' ); ?></h2>
					<p><?php esc_html_e( 'Configure plugin settings, email notifications, and system preferences.', 'leave-manager-management' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-settings' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Configure', 'leave-manager-management' ); ?></a>
				</div>
				<div class="leave-manager-card">
					<h2><?php esc_html_e( 'Email Templates', 'leave-manager-management' ); ?></h2>
					<p><?php esc_html_e( 'Manage and customize email notification templates.', 'leave-manager-management' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-email-templates' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Manage Templates', 'leave-manager-management' ); ?></a>
				</div>
				<div class="leave-manager-card">
					<h2><?php esc_html_e( 'System Logs', 'leave-manager-management' ); ?></h2>
					<p><?php esc_html_e( 'View system logs and audit trail for all activities.', 'leave-manager-management' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-logs' ) ); ?>" class="button button-primary"><?php esc_html_e( 'View Logs', 'leave-manager-management' ); ?></a>
				</div>
				<div class="leave-manager-card">
					<h2><?php esc_html_e( 'System Health', 'leave-manager-management' ); ?></h2>
					<p><?php esc_html_e( 'Check system health, database integrity, and plugin diagnostics.', 'leave-manager-management' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-diagnostics' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Check Health', 'leave-manager-management' ); ?></a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Reports & Export overview page
	 *
	 * @return void
	 */
	public function render_reports_export_overview() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Reports & Export', 'leave-manager-management' ); ?></h1>
			<div class="leave-manager-overview-cards">
				<div class="leave-manager-card">
					<h2><?php esc_html_e( 'Leave Reports', 'leave-manager-management' ); ?></h2>
					<p><?php esc_html_e( 'View detailed reports on leave requests, approvals, and usage.', 'leave-manager-management' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-reports' ) ); ?>" class="button button-primary"><?php esc_html_e( 'View Reports', 'leave-manager-management' ); ?></a>
				</div>
				<div class="leave-manager-card">
					<h2><?php esc_html_e( 'User Reports', 'leave-manager-management' ); ?></h2>
					<p><?php esc_html_e( 'View reports on individual user leave balances and history.', 'leave-manager-management' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-user-reports' ) ); ?>" class="button button-primary"><?php esc_html_e( 'View Reports', 'leave-manager-management' ); ?></a>
				</div>
				<div class="leave-manager-card">
					<h2><?php esc_html_e( 'Department Reports', 'leave-manager-management' ); ?></h2>
					<p><?php esc_html_e( 'View reports on leave usage by department.', 'leave-manager-management' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-department-reports' ) ); ?>" class="button button-primary"><?php esc_html_e( 'View Reports', 'leave-manager-management' ); ?></a>
				</div>
				<div class="leave-manager-card">
					<h2><?php esc_html_e( 'Export Data', 'leave-manager-management' ); ?></h2>
					<p><?php esc_html_e( 'Export leave data to CSV, Excel, or PDF formats.', 'leave-manager-management' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-export' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Export Data', 'leave-manager-management' ); ?></a>
				</div>
			</div>
		</div>
		<?php
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
	 * Render merged email templates page (combines Email Templates and Email Template Editor)
	 *
	 * @return void
	 */
	public function render_email_templates_merged() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/email-templates-merged.php';
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
	 * Render user reports page
	 *
	 * @return void
	 */
	public function render_user_reports() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/user-reports.php';
	}

	/**
	 * Render department reports page
	 *
	 * @return void
	 */
	public function render_department_reports() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/department-reports.php';
	}

	/**
	 * Render merged export data page (combines Export and Reports export functionality)
	 *
	 * @return void
	 */
	public function render_export_data_merged() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/export-data-merged.php';
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
