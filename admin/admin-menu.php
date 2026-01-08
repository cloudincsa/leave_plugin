<?php
/**
 * Admin Menu class for Leave Manager Plugin (v3.0 - Streamlined UI)
 *
 * Simplified menu structure with tabs for sub-navigation.
 * Menu items: Dashboard, Requests, Staff, Settings, System
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
	 * Register admin menu with streamlined structure
	 *
	 * New structure (5 items with tabs):
	 * - Dashboard (overview)
	 * - Requests (tabs: All | Pending | Approved | Rejected)
	 * - Staff (tabs: Users | Policies)
	 * - Settings (tabs: General | Email | Notifications)
	 * - System (tabs: Health | Logs | Export)
	 *
	 * @return void
	 */
	public function register_menu() {
		// Main menu is always available

		// Main menu
		add_menu_page(
			'Leave Management',
			'Leave',
			'manage_options',
			'leave-manager-management',
			array( $this, 'render_dashboard' ),
			'dashicons-calendar-alt',
			30
		);

		// 1. Dashboard (default landing page)
		add_submenu_page(
			'leave-manager-management',
			'Dashboard',
			'Dashboard',
			'manage_options',
			'leave-manager-management',
			array( $this, 'render_dashboard' )
		);

		// 2. Requests (with tabs for filtering)
		add_submenu_page(
			'leave-manager-management',
			'Requests',
			'Requests',
			'manage_options',
			'leave-manager-requests',
			array( $this, 'render_requests' )
		);

		// 3. Staff (tabs: Users | Policies)
		add_submenu_page(
			'leave-manager-management',
			'Staff',
			'Staff',
			'manage_options',
			'leave-manager-staff',
			array( $this, 'render_staff' )
		);

		// 4. Settings (tabs: General | Email | Notifications)
		add_submenu_page(
			'leave-manager-management',
			'Settings',
			'Settings',
			'manage_options',
			'leave-manager-settings',
			array( $this, 'render_settings' )
		);

		// 5. Templates (email templates)
		add_submenu_page(
			'leave-manager-management',
			'Templates',
			'Templates',
			'manage_options',
			'leave-manager-templates',
			array( $this, 'render_templates' )
		);

		// 6. Reports (tabs: Leave | User | Department)
		add_submenu_page(
			'leave-manager-management',
			'Reports',
			'Reports',
			'manage_options',
			'leave-manager-reports',
			array( $this, 'render_reports' )
		);

		// 7. System (tabs: Health | Logs | Export)
		add_submenu_page(
			'leave-manager-management',
			'System',
			'System',
			'manage_options',
			'leave-manager-system',
			array( $this, 'render_system' )
		);

		// 8. Leave Types (manage leave type definitions)
		add_submenu_page(
			'leave-manager-management',
			'Leave Types',
			'Leave Types',
			'manage_options',
			'leave-manager-leave-types',
			array( $this, 'render_leave_types' )
		);

		// 9. Leave Policies (manage leave policies)
		add_submenu_page(
			'leave-manager-management',
			'Leave Policies',
			'Leave Policies',
			'manage_options',
			'leave-manager-policies',
			array( $this, 'render_policies' )
		);

// 10. Departments (manage organizational departments)
			add_submenu_page(
				'leave-manager-management',
				'Departments',
				'Departments',
				'manage_options',
				'leave-manager-departments',
				array( $this, 'render_departments' )
			);

			// 11. Help (documentation and guides)
			add_submenu_page(
				'leave-manager-management',
				'Help',
				'Help',
				'manage_options',
				'leave-manager-help',
				array( $this, 'render_help' )
			);
		}



	/**
	 * Render dashboard page
	 *
	 * @return void
	 */
	public function render_dashboard() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/dashboard-professional.php';
	}

	/**
	 * Render requests page with tabs
	 *
	 * @return void
	 */
	public function render_requests() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/requests-professional.php';
	}

	/**
	 * Render staff page with tabs (Users | Policies)
	 *
	 * @return void
	 */
	public function render_staff() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/staff-professional.php';
	}

	/**
	 * Render settings page with tabs (General | Email | Notifications)
	 *
	 * @return void
	 */
	public function render_settings() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/settings-professional.php';
	}

	/**
	 * Render templates page
	 *
	 * @return void
	 */
	public function render_templates() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/templates-professional.php';
	}

	/**
	 * Render reports page with tabs (Leave | User | Department)
	 *
	 * @return void
	 */
	public function render_reports() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/reports-professional.php';
	}

	/**
	 * Render system page with tabs (Health | Logs | Export)
	 *
	 * @return void
	 */
	public function render_system() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/system-professional.php';
	}

	/**
	 * Render leave types page
	 *
	 * @return void
	 */
	public function render_leave_types() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/leave-types-professional.php';
	}

	/**
	 * Render policies page
	 *
	 * @return void
	 */
	public function render_policies() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/policies-professional.php';
	}

	/**
	 * Render departments page
	 *
	 * @return void
	 */
	public function render_departments() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/departments-professional.php';
	}

	/**
	 * Render help page
	 *
	 * @return void
	 */
	public function render_help() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/help-professional.php';
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our plugin pages
		if ( strpos( $hook, 'leave-manager' ) === false ) {
			return;
		}

		// Enqueue design system CSS
		wp_enqueue_style(
			'leave-manager-design-system',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/css/design-system.css',
			array(),
			'1.0.1'
		);

		// Enqueue unified admin CSS
		wp_enqueue_style(
			'leave-manager-admin-unified',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/css/admin-unified.css',
			array(),
			'1.0.1'
		);

		// Enqueue professional CSS framework
		wp_enqueue_style(
			'leave-manager-professional',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/css/professional.css',
			array(),
			time()
		);

		// Enqueue unified layout system CSS
		wp_enqueue_style(
			'leave-manager-layout-system',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/css/layout-system.css',
			array(),
			time()
		);

		// Enqueue new streamlined CSS
		// Use set_url_scheme to ensure proper protocol
			$css_url = set_url_scheme( LEAVE_MANAGER_PLUGIN_URL . 'admin/css/admin-styles-v3.css' );
			wp_enqueue_style(
				'leave_manager-admin-styles',
				$css_url,
				array(),
				time()
			);

		// Enqueue admin scripts
		// Use set_url_scheme for JS as well
		$js_url = set_url_scheme( LEAVE_MANAGER_PLUGIN_URL . 'admin/js/admin-scripts.js' );
		wp_enqueue_script(
			'leave_manager-admin-scripts',
			$js_url,
			array( 'jquery' ),
			time(),
			true
		);

		// Localize script with AJAX URL and nonce
		wp_localize_script(
			'leave_manager-admin-scripts',
			'leave_manager_ajax',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'leave_manager_ajax_nonce' ),
			)
		);
	}

	/**
	 * Helper function to render tab navigation
	 *
	 * @param array  $tabs Array of tabs with 'slug' and 'label' keys
	 * @param string $current_tab Currently active tab
	 * @param string $base_url Base URL for tab links
	 * @return void
	 */
	public static function render_tabs( $tabs, $current_tab, $base_url ) {
		echo '<nav class="leave-manager-tabs">';
		foreach ( $tabs as $tab ) {
			$active_class = ( $current_tab === $tab['slug'] ) ? 'active' : '';
			$url = add_query_arg( 'tab', $tab['slug'], $base_url );
			printf(
				'<a href="%s" class="leave-manager-tab %s">%s</a>',
				esc_url( $url ),
				esc_attr( $active_class ),
				esc_html( $tab['label'] )
			);
		}
		echo '</nav>';
	}
}
