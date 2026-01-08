<?php
/**
 * Main Plugin class for Leave Manager Plugin
 *
 * Orchestrates all plugin functionality and initialization.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Plugin class
 */
class Leave_Manager_Plugin {

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
	 * Permissions instance
	 *
	 * @var Leave_Manager_Permissions
	 */
	private $permissions;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->init_instances();
	}

	/**
	 * Load plugin dependencies
	 *
	 * @return void
	 */
	private function load_dependencies() {
		// Auth classes (must be loaded first)
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/auth/class-session-manager.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/auth/class-custom-auth.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/auth/functions-auth.php';
		
		// Core classes
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-database.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-logger.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-cache.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-security.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-settings.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-permissions.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-roles-capabilities.php';
		
		// Functional classes
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-users.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-leave-requests.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-email-handler.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-email-queue.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-email-templates.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-request-history.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-audit-logger.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-api-handler.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-export.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-calendar.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-frontend-pages.php';
		
		// Automation and caching
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-cron-jobs.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-webhooks.php';
		
		// Advanced features
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-leave-accrual.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-two-factor-auth.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-user-impersonation.php';
		
		// High priority feature classes
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-leave-policies.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-advanced-workflow.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-team-management.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-bulk-operations.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-setup-wizard.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-setup-detector.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-sms-handler.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-employee-signup.php';
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-weekly-summary.php';
		
		// Frontend shortcodes
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'frontend/shortcodes-signup.php';
	}

	/**
	 * Initialize class instances
	 *
	 * @return void
	 */
	private function init_instances() {
		$this->db             = new Leave_Manager_Database();
		$this->logger         = new Leave_Manager_Logger( $this->db );
		$this->settings       = new Leave_Manager_Settings( $this->db, $this->logger );
		$this->permissions    = new Leave_Manager_Permissions( $this->db, $this->logger );
		$this->employee_signup = new Leave_Manager_Employee_Signup( $this->db, $this->logger );
	}

	/**
	 * Run the plugin
	 *
	 * @return void
	 */
	public function run() {
		// Register activation hook
		register_activation_hook( LEAVE_MANAGER_PLUGIN_FILE, array( $this, 'activate' ) );

		// Register deactivation hook
		register_deactivation_hook( LEAVE_MANAGER_PLUGIN_FILE, array( $this, 'deactivate' ) );

		// Load text domain for translations
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Load frontend shortcodes
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'frontend/shortcodes.php';

		// Initialize branding system
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-branding.php';
		$branding = new Leave_Manager_Branding();
		
		// Initialize email templates
		$email_templates = new Leave_Manager_Email_Templates( $branding );

		// Initialize library enqueuer (Chart.js, FullCalendar)
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-library-enqueuer.php';
		new Leave_Manager_Library_Enqueuer();
		
		// Initialize user impersonation for testing
		new Leave_Manager_User_Impersonation();
		
		// Store email templates instance for use throughout plugin
		global $leave_manager_email_templates;
		$leave_manager_email_templates = $email_templates;

		// Initialize dashboard charts
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-dashboard-charts.php';

		// Initialize FullCalendar
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-fullcalendar.php';

		// Initialize frontend wrapper
		// require_once LEAVE_MANAGER_PLUGIN_DIR . 'frontend/class-frontend-wrapper.php';
		// Leave_Manager_Frontend_Wrapper::init(); // Disabled - using WordPress theme instead

		// Initialize admin functionality
		if ( is_admin() ) {
			$this->init_admin();
		}

		// Initialize API endpoints
		$this->init_api();

		// Initialize frontend functionality
		$this->init_frontend();

		// Initialize frontend pages
		$frontend_pages = new Leave_Manager_Frontend_Pages( $this->db, $this->logger );
		$frontend_pages->init();

		// Initialize setup wizard
		$setup_wizard = new Leave_Manager_Setup_Wizard( $this->db, $this->logger );

		// Initialize setup detector
		$setup_detector = new Leave_Manager_Setup_Detector( $this->db, $this->logger );
		$setup_detector->init();

		// Log plugin initialization
		$this->logger->info( 'Leave Manager Plugin initialized' );
	}
	
	/**
	 * Enqueue admin AJAX script
	 *
	 * @return void
	 */
	public function enqueue_admin_ajax() {
		wp_enqueue_script(
			'leave-manager-admin-ajax',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/js/admin-ajax.js',
			array( 'jquery' ),
			LEAVE_MANAGER_PLUGIN_VERSION,
			true
		);
		
		wp_localize_script(
			'leave-manager-admin-ajax',
			'leaveManagerNonce',
			wp_create_nonce( 'leave_manager_nonce' )
		);
	}

	/**
	 * Activate the plugin
	 *
	 * @return void
	 */
	public function activate() {
		// Create ALL database tables (consolidated in class-database.php)
		$results = $this->db->create_tables();

		// Register roles and capabilities
		Leave_Manager_Roles_Capabilities::register_roles();

		// Initialize settings
		$this->settings->init_defaults();

		// Create frontend pages
		$frontend_pages = new Leave_Manager_Frontend_Pages( $this->db, $this->logger );
		$frontend_pages->create_pages();

		// Clear any setup transients
		delete_transient( 'leave_manager_needs_setup' );

		// Log activation with table creation results
		$this->logger->info( 'Leave Manager Plugin activated with roles, tables, and frontend pages' );
	}

	/**
	 * Create tables for new high-priority features
	 *
	 * @return void
	 */
	private function create_new_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		// Leave Policies table
		$policies_table = $wpdb->prefix . 'leave_manager_leave_policies';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$policies_table}'" ) !== $policies_table ) {
			$sql = "CREATE TABLE {$policies_table} (
				policy_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				policy_name VARCHAR(255) NOT NULL,
				description LONGTEXT,
				leave_type VARCHAR(50) NOT NULL,
				annual_days DECIMAL(5,2) DEFAULT 20,
				carryover_days DECIMAL(5,2) DEFAULT 5,
				expiry_days INT DEFAULT 365,
				status VARCHAR(20) DEFAULT 'active',
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				KEY status (status),
				KEY leave_type (leave_type)
			) {$charset_collate}";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}

		// Policy Rules table
		$rules_table = $wpdb->prefix . 'leave_manager_policy_rules';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$rules_table}'" ) !== $rules_table ) {
			$sql = "CREATE TABLE {$rules_table} (
				rule_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				policy_id BIGINT UNSIGNED NOT NULL,
				rule_name VARCHAR(255) NOT NULL,
				rule_type VARCHAR(50) NOT NULL,
				rule_value VARCHAR(255),
				description LONGTEXT,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				KEY policy_id (policy_id)
			) {$charset_collate}";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}

		// Approval Workflows table
		$workflows_table = $wpdb->prefix . 'leave_manager_approval_workflows';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$workflows_table}'" ) !== $workflows_table ) {
			$sql = "CREATE TABLE {$workflows_table} (
				workflow_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				workflow_name VARCHAR(255) NOT NULL,
				description LONGTEXT,
				leave_type VARCHAR(50) NOT NULL,
				approval_chain LONGTEXT,
				status VARCHAR(20) DEFAULT 'active',
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				KEY leave_type (leave_type),
				KEY status (status)
			) {$charset_collate}";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}

		// Approvals table
		$approvals_table = $wpdb->prefix . 'leave_manager_approvals';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$approvals_table}'" ) !== $approvals_table ) {
			$sql = "CREATE TABLE {$approvals_table} (
				approval_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				request_id BIGINT UNSIGNED NOT NULL,
				workflow_id BIGINT UNSIGNED,
				approvver_id BIGINT UNSIGNED NOT NULL,
				approval_level INT DEFAULT 1,
				status VARCHAR(20) DEFAULT 'pending',
				comments LONGTEXT,
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				KEY request_id (request_id),
				KEY approver_id (approvver_id),
				KEY status (status)
			) {$charset_collate}";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}

		// Teams table
		$teams_table = $wpdb->prefix . 'leave_manager_teams';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$teams_table}'" ) !== $teams_table ) {
			$sql = "CREATE TABLE {$teams_table} (
				team_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				team_name VARCHAR(255) NOT NULL,
				description LONGTEXT,
				department VARCHAR(255),
				manager_id BIGINT UNSIGNED,
				status VARCHAR(20) DEFAULT 'active',
				created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				KEY manager_id (manager_id),
				KEY status (status)
			) {$charset_collate}";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}

		// Team Members table
		$members_table = $wpdb->prefix . 'leave_manager_team_members';
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$members_table}'" ) !== $members_table ) {
			$sql = "CREATE TABLE {$members_table} (
				member_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
				team_id BIGINT UNSIGNED NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				role VARCHAR(50) DEFAULT 'member',
				joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
				UNIQUE KEY team_user (team_id, user_id),
				KEY user_id (user_id)
			) {$charset_collate}";
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
		}
	}

	/**
	 * Deactivate the plugin
	 *
	 * @return void
	 */
	public function deactivate() {
		// Remove roles and capabilities
		Leave_Manager_Roles_Capabilities::remove_roles();

		// Log deactivation
		$this->logger->info( 'Leave Manager Plugin deactivated' );
	}

	/**
	 * Load text domain for translations
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'leave-manager-management',
			false,
			dirname( plugin_basename( LEAVE_MANAGER_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Initialize admin functionality
	 *
	 * @return void
	 */
	private function init_admin() {
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/admin-menu.php';
		new Leave_Manager_Admin_Menu( $this->db, $this->logger, $this->settings );
		
		// Initialize branding menu handler
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'admin/class-admin-menu.php';
		new Leave_Manager_Branding_Menu();
		
		// Initialize settings AJAX handler
		require_once LEAVE_MANAGER_PLUGIN_DIR . 'includes/class-settings-ajax.php';
		
		// Enqueue admin AJAX script
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_ajax' ) );
		
		// Register AJAX handlers
		add_action( 'wp_ajax_leave_manager_send_test_weekly_summary', array( $this, 'ajax_send_test_weekly_summary' ) );
	}
	
	/**
	 * AJAX handler for sending test weekly summary
	 *
	 * @return void
	 */
	public function ajax_send_test_weekly_summary() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_test_summary' ) ) {
			wp_send_json_error( 'Invalid security token' );
		}
		
		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}
		
		// Get email address
		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
		if ( ! is_email( $email ) ) {
			wp_send_json_error( 'Invalid email address' );
		}
		
		// Send test summary
		$weekly_summary = new Leave_Manager_Weekly_Summary( $this->db, $this->logger, $this->settings );
		$result = $weekly_summary->send_test_summary( $email );
		
		if ( $result ) {
			wp_send_json_success( 'Test summary sent successfully' );
		} else {
			wp_send_json_error( 'Failed to send test summary. Check your email configuration.' );
		}
	}

	/**
	 * Initialize API endpoints
	 *
	 * @return void
	 */
	private function init_api() {
		$api_handler = new Leave_Manager_API_Handler( $this->db, $this->logger, $this->settings );
		$api_handler->register_endpoints();
	}

	/**
	 * Initialize frontend functionality
	 *
	 * @return void
	 */
	private function init_frontend() {
		// Shortcodes are registered in frontend/shortcodes.php with custom auth support
		// Do not register here to avoid conflicts
		// add_shortcode( 'leave_manager_leave_dashboard', array( $this, 'render_dashboard_shortcode' ) );
		// add_shortcode( 'leave_manager_leave_form', array( $this, 'render_form_shortcode' ) );

		// Enqueue frontend assets
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

		// Display navigation menu on frontend pages
		add_action( 'wp_body_open', array( $this, 'display_frontend_navigation_menu' ), 5 );

		// Setup notification hooks
		if ( class_exists( 'Leave_Manager_Email_Handler' ) ) {
			$email_handler = new Leave_Manager_Email_Handler( $this->db, $this->logger, $this->settings );
			$email_handler->setup_notification_hooks();
		}
	}

	/**
	 * Render dashboard shortcode
	 *
	 * @return string Shortcode output
	 */
	public function render_dashboard_shortcode() {
		ob_start();
		include LEAVE_MANAGER_PLUGIN_DIR . 'frontend/pages/dashboard.php';
		return ob_get_clean();
	}

	/**
	 * Render form shortcode
	 *
	 * @return string Shortcode output
	 */
	public function render_form_shortcode() {
		ob_start();
		include LEAVE_MANAGER_PLUGIN_DIR . 'frontend/pages/leave-form.php';
		return ob_get_clean();
	}

	/**
	 * Enqueue frontend assets
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets() {
		// Enqueue design system CSS
		wp_enqueue_style(
			'leave-manager-design-system',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/css/design-system.css',
			array(),
			LEAVE_MANAGER_PLUGIN_VERSION
		);

		wp_enqueue_style(
			'leave-manager-frontend',
			LEAVE_MANAGER_PLUGIN_URL . 'frontend/css/frontend-styles.css',
			array( 'leave-manager-design-system' ),
			LEAVE_MANAGER_PLUGIN_VERSION
		);

		// Enqueue navigation menu CSS
		wp_enqueue_style(
			'leave-manager-nav-menu',
			LEAVE_MANAGER_PLUGIN_URL . 'frontend/css/navigation-menu.css',
			array( 'leave-manager-design-system' ),
			LEAVE_MANAGER_PLUGIN_VERSION
		);

		// Enqueue WordPress Admin UI CSS
		wp_enqueue_style(
			'leave-manager-wp-admin-style',
			LEAVE_MANAGER_PLUGIN_URL . 'frontend/css/wordpress-admin-style.css',
			array(),
			LEAVE_MANAGER_PLUGIN_VERSION
		);

		wp_enqueue_script(
			'leave-manager-frontend',
			LEAVE_MANAGER_PLUGIN_URL . 'frontend/js/frontend-scripts.js',
			array( 'jquery' ),
			LEAVE_MANAGER_PLUGIN_VERSION,
			true
		);

		// Enqueue navigation menu JS
		wp_enqueue_script(
			'leave-manager-nav-menu',
			LEAVE_MANAGER_PLUGIN_URL . 'frontend/js/navigation-menu.js',
			array(),
			LEAVE_MANAGER_PLUGIN_VERSION,
			true
		);

		// Localize script with AJAX URL
		wp_localize_script(
			'leave-manager-frontend',
			'lfccLeaveData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'leave_manager_nonce' ),
			)
		);
	}

	/**
	 * Display frontend navigation menu
	 *
	 * @return void
	 */
	public function display_frontend_navigation_menu() {
		// Only display on Leave Manager frontend pages
		if ( ! $this->is_leave_manager_frontend_page() ) {
			return;
		}

		// Include navigation menu template
		include LEAVE_MANAGER_PLUGIN_DIR . 'frontend/templates/navigation-menu.php';
	}

	/**
	 * Check if current page is a Leave Manager frontend page
	 *
	 * @return bool
	 */
	private function is_leave_manager_frontend_page() {
		// Check if we're on a page with Leave Manager shortcodes or post type
		global $post;

		if ( ! isset( $post ) ) {
			return false;
		}

		// Check if post contains Leave Manager shortcodes
		if ( has_shortcode( $post->post_content, 'leave_manager_leave_dashboard' ) ||
			 has_shortcode( $post->post_content, 'leave_manager_leave_form' ) ) {
			return true;
		}

		// Check if page slug contains 'leave'
		if ( strpos( $post->post_name, 'leave' ) !== false ) {
			return true;
		}

		return false;
	}

	/**
	 * Get database instance
	 *
	 * @return Leave_Manager_Database Database instance
	 */
	public function get_db() {
		return $this->db;
	}

	/**
	 * Get logger instance
	 *
	 * @return Leave_Manager_Logger Logger instance
	 */
	public function get_logger() {
		return $this->logger;
	}

	/**
	 * Get settings instance
	 *
	 * @return Leave_Manager_Settings Settings instance
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Get permissions instance
	 *
	 * @return Leave_Manager_Permissions Permissions instance
	 */
	public function get_permissions() {
		return $this->permissions;
	}
}
