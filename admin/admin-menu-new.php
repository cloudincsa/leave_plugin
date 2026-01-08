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
		// Check if setup is needed
		$detector = new Leave_Manager_Setup_Detector( $this->db, $this->logger );
		$needs_setup = $detector->needs_setup();
		
		if ( $needs_setup ) {
		// Main menu - points to setup wizard when setup is needed
		add_menu_page(
			'Leave Management',
			'Leave',
				'manage_options',
				'leave-manager-management',
				array( $this, 'render_setup_redirect' ),
				'dashicons-calendar-alt',
				30
			);
			
			// Setup wizard submenu
			add_submenu_page(
				'leave-manager-management',
				'Setup',
				'Setup',
				'manage_options',
				'leave-manager-setup',
				array( $this, 'render_setup_wizard' )
			);
			
			return; // Don't register other menus until setup is complete
		}

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

		// 3.5 Public Holidays
		add_submenu_page(
			'leave-manager-management',
			'Public Holidays',
			'Public Holidays',
			'manage_options',
			'leave-manager-public-holidays',
			array( $this, 'render_public_holidays' )
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

		// 5. System (tabs: Health | Logs | Export)
		add_submenu_page(
			'leave-manager-management',
			'System',
			'System',
			'manage_options',
			'leave-manager-system',
			array( $this, 'render_system' )
		);
	}

	/**
	 * Render Public Holidays page
	 *
	 * @return void
	 */
	public function render_public_holidays() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/public-holidays.php';
	}

	/**
	 * Render setup redirect page
	 *
	 * @return void
	 */
	public function render_setup_redirect() {
		$setup_url = admin_url( 'admin.php?page=leave-manager-setup' );
		?>
		<div class="wrap leave_manager-wrap">
			<h1>Leave Manager</h1>
			<div class="leave-manager-setup-notice">
				<div class="leave-manager-setup-icon">⚙️</div>
				<h2>Setup Required</h2>
				<p>The plugin needs to be initialized before you can use it.</p>
				<a href="<?php echo esc_url( $setup_url ); ?>" class="leave-manager-btn leave_manager-btn-primary">
					Go to Setup Wizard
				</a>
			</div>
		</div>
		<script>
			setTimeout(function() {
				window.location.href = '<?php echo esc_url( $setup_url ); ?>';
			}, 2000);
		</script>
		<?php
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
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/dashboard-new.php';
	}

	/**
	 * Render requests page with tabs
	 *
	 * @return void
	 */
	public function render_requests() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/requests-new.php';
	}

	/**
	 * Render staff page with tabs (Users | Policies)
	 *
	 * @return void
	 */
	public function render_staff() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/staff-new.php';
	}

	/**
	 * Render settings page with tabs (General | Email | Notifications)
	 *
	 * @return void
	 */
	public function render_settings() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/settings-new.php';
	}

	/**
	 * Render system page with tabs (Health | Logs | Export)
	 *
	 * @return void
	 */
	public function render_system() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/system-new.php';
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

		// Enqueue new streamlined CSS
		wp_enqueue_style(
			'leave_manager-admin-styles',
			LEAVE_MANAGER_PLUGIN_URL . 'admin/css/admin-styles-v3.css',
			array(),
			LEAVE_MANAGER_PLUGIN_VERSION
		);

		// Enqueue admin scripts
		wp_enqueue_script(
			'leave_manager-admin-scripts',
			LEAVE_MANAGER_PLUGIN_URL . 'admin/js/admin-scripts.js',
			array( 'jquery' ),
			LEAVE_MANAGER_PLUGIN_VERSION,
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
