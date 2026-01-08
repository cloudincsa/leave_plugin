<?php
/**
 * Setup Detector class for Leave Manager Plugin
 *
 * Detects if the plugin needs initialization and prompts the user.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Setup_Detector class
 */
class Leave_Manager_Setup_Detector {

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
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 */
	public function __construct( $db, $logger ) {
		$this->db     = $db;
		$this->logger = $logger;
	}

	/**
	 * Initialize setup detection
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'check_setup_status' ) );
		add_action( 'admin_notices', array( $this, 'display_setup_notice' ) );
		add_action( 'wp_ajax_leave_manager_initialize_plugin', array( $this, 'handle_initialization' ) );
	}

	/**
	 * Check if plugin needs setup
	 *
	 * @return bool True if setup is needed
	 */
	public function needs_setup() {
		global $wpdb;

		// Check if main tables exist
		$tables_to_check = array(
			'leave_manager_leave_users',
			'leave_manager_leave_requests',
			'leave_manager_leave_policies',
			'leave_manager_email_queue',
			'leave_manager_settings',
		);

		foreach ( $tables_to_check as $table ) {
			$full_table = $wpdb->prefix . $table;
			if ( $wpdb->get_var( "SHOW TABLES LIKE '$full_table'" ) !== $full_table ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check setup status and set transient
	 *
	 * @return void
	 */
	public function check_setup_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$needs_setup = $this->needs_setup();
		set_transient( 'leave_manager_needs_setup', $needs_setup, HOUR_IN_SECONDS );
	}

	/**
	 * Display setup notice
	 *
	 * @return void
	 */
	public function display_setup_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$needs_setup = get_transient( 'leave_manager_needs_setup' );

		if ( ! $needs_setup ) {
			return;
		}

		// Don't show on setup page
		if ( isset( $_GET['page'] ) && 'leave-manager-setup' === $_GET['page'] ) {
			return;
		}

		?>
		<div class="notice notice-warning is-dismissible" id="leave-manager-setup-notice">
			<p>
				<strong><?php echo esc_html( 'Leave Manager Plugin' ); ?></strong><br>
				<?php echo esc_html( 'The plugin requires initialization to create necessary database tables and configure settings.' ); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-setup' ) ); ?>" class="button button-primary">
					<?php echo esc_html( 'Initialize Plugin' ); ?>
				</a>
				<a href="#" class="button" id="leave-manager-dismiss-notice">
					<?php echo esc_html( 'Dismiss' ); ?>
				</a>
			</p>
		</div>

		<script>
			document.getElementById('leave_manager-dismiss-notice').addEventListener('click', function(e) {
				e.preventDefault();
				document.getElementById('leave_manager-setup-notice').style.display = 'none';
				// Mark as dismissed for this session
				localStorage.setItem('leave_manager_setup_notice_dismissed', 'true');
			});
		</script>

		<style>
			#leave-manager-setup-notice {
				border-left: 4px solid #ff9800;
				background-color: #fff3e0;
			}
			#leave-manager-setup-notice p {
				margin: 10px 0;
			}
			#leave-manager-setup-notice .button {
				margin-right: 10px;
			}
		</style>
		<?php
	}

	/**
	 * Handle AJAX initialization request
	 *
	 * @return void
	 */
	public function handle_initialization() {
		check_ajax_referer( 'leave_manager_setup_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Create all tables
		$db = new Leave_Manager_Database();
		$db->create_tables();

		// Initialize default settings
		$settings = new Leave_Manager_Settings( $db );
		$settings->init_defaults();

		// Clear the setup transient
		delete_transient( 'leave_manager_needs_setup' );

		wp_send_json_success( array(
			'message' => 'Plugin initialized successfully',
			'redirect' => admin_url( 'admin.php?page=leave-manager-management' ),
		) );
	}

	/**
	 * Get setup status information
	 *
	 * @return array Setup status information
	 */
	public function get_setup_status() {
		global $wpdb;

		$status = array(
			'needs_setup' => $this->needs_setup(),
			'tables' => array(),
			'settings' => array(),
		);

		// Check each table
		$tables = array(
			'leave_manager_leave_users',
			'leave_manager_leave_requests',
			'leave_manager_leave_policies',
			'leave_manager_email_queue',
			'leave_manager_email_logs',
			'leave_manager_settings',
			'leave_manager_employee_signups',
			'leave_manager_webhooks',
			'leave_manager_two_factor_auth',
		);

		foreach ( $tables as $table ) {
			$full_table = $wpdb->prefix . $table;
			$exists = $wpdb->get_var( "SHOW TABLES LIKE '$full_table'" ) === $full_table;
			$status['tables'][ $table ] = $exists;
		}

		// Check settings
		$settings_check = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_settings" );
		$status['settings']['configured'] = intval( $settings_check ) > 0;

		return $status;
	}
}
