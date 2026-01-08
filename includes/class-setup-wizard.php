<?php
/**
 * Setup Wizard Class
 *
 * Handles plugin initialization and setup
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Setup Wizard class
 */
class Leave_Manager_Setup_Wizard {

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

		// Setup wizard disabled - all setup happens on activation
		// add_action( 'admin_menu', array( $this, 'add_setup_menu' ) );
		add_action( 'wp_ajax_leave_manager_initialize_database', array( $this, 'initialize_database' ) );
		add_action( 'wp_ajax_leave_manager_check_setup_status', array( $this, 'check_setup_status' ) );
	}

	/**
	 * Add setup menu
	 */
	public function add_setup_menu() {
		// Setup wizard disabled - all setup happens on activation
		return;
	}

	/**
	 * Check if setup is complete
	 *
	 * @return bool
	 */
	public function is_setup_complete() {
		$setup_complete = get_option( 'leave_manager_setup_complete', false );
		return $setup_complete === 'yes';
	}

	/**
	 * Render setup page
	 */
	public function render_setup_page() {
		?>
		<div class="wrap">
			<h1>Leave Manager - Setup Wizard</h1>
			<p style="font-size: 16px; color: #666; margin: 20px 0;">Welcome to Leave Manager! This wizard will help you set up the plugin for your organization.</p>
			<div id="leave-manager-setup-container" style="max-width: 800px; margin: 30px 0;">
				<div id="leave-manager-setup-status"></div>
				<div id="leave-manager-setup-form">
					<div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
						<h2 style="margin-top: 0;">Step 1: Initialize Database</h2>
						<p>Leave Manager uses a database to store employee information, leave requests, policies, and reports. Click the button below to create all necessary database tables.</p>
						<p><strong>What will be created:</strong></p>
						<ul style="margin: 10px 0;">
							<li>Employee and user management tables</li>
							<li>Leave request and approval workflow tables</li>
							<li>Leave policies and balance tracking</li>
							<li>Email templates and notification queues</li>
							<li>Audit logs and reporting tables</li>
						</ul>
					</div>
						<button id="leave-manager-init-btn" class="button button-primary button-large" style="padding: 10px 20px; font-size: 16px;">
							✓ Initialize Database
						</button>
					<div id="leave-manager-init-progress" style="margin-top: 20px; display: none;">
						<p><strong>Initializing...</strong></p>
						<div class="leave-manager-progress-bar" style="width: 100%; height: 20px; background: #f0f0f0; border-radius: 3px; overflow: hidden;">
							<div class="leave-manager-progress-fill" style="width: 0%; height: 100%; background: #0073aa; transition: width 0.3s;"></div>
						</div>
						<p id="leave-manager-init-message"></p>
					</div>
				</div>
			</div>
		</div>
		<script>
			jQuery(document).ready(function($) {
				$('#leave-manager-init-btn').click(function() {
					var $btn = $(this);
					$btn.prop('disabled', true);
					$('#leave-manager-init-progress').show();

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'leave_manager_initialize_database',
							nonce: '<?php echo wp_create_nonce( 'leave_manager_init_nonce' ); ?>'
						},
						success: function(response) {
							if (response.success) {
								$('#leave-manager-init-message').html('<span style="color: green;">✓ ' + response.data.message + '</span>');
								$('.leave-manager-progress-fill').css('width', '100%');
								setTimeout(function() {
									location.reload();
								}, 2000);
							} else {
								$('#leave-manager-init-message').html('<span style="color: red;">✗ ' + response.data.message + '</span>');
								$btn.prop('disabled', false);
							}
						},
						error: function() {
							$('#leave-manager-init-message').html('<span style="color: red;">✗ An error occurred</span>');
							$btn.prop('disabled', false);
						}
					});
				});

				// Check setup status on page load
				checkSetupStatus();

				function checkSetupStatus() {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'leave_manager_check_setup_status',
							nonce: '<?php echo wp_create_nonce( 'leave_manager_check_nonce' ); ?>'
						},
						success: function(response) {
							if (response.success && response.data.complete) {
								$('#leave-manager-setup-form').hide();
								$('#leave-manager-setup-status').html('<div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 3px; color: #155724;"><strong>✓ Setup Complete!</strong><p>The plugin has been successfully initialized. You can now use all features.</p></div>');
							}
						}
					});
				}
			});
		</script>
		<?php
	}

	/**
	 * Initialize database via AJAX
	 */
	public function initialize_database() {
		check_ajax_referer( 'leave_manager_init_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		try {
			// Create all tables
			$this->db->create_tables();

			// Initialize default settings
			$settings = new Leave_Manager_Settings( $this->db, $this->logger );
			$settings->initialize_defaults();

			// Mark setup as complete
			update_option( 'leave_manager_setup_complete', 'yes' );

			$this->logger->info( 'Plugin setup completed successfully' );

			wp_send_json_success( array( 'message' => 'Plugin initialized successfully!' ) );
		} catch ( Exception $e ) {
			$this->logger->error( 'Setup failed: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => 'Setup failed: ' . $e->getMessage() ) );
		}
	}

	/**
	 * Check setup status via AJAX
	 */
	public function check_setup_status() {
		check_ajax_referer( 'leave_manager_check_nonce' );

		wp_send_json_success( array( 'complete' => $this->is_setup_complete() ) );
	}

	/**
	 * Get setup status for diagnostics
	 *
	 * @return array
	 */
	public function get_setup_status() {
		$status = array(
			'setup_complete' => $this->is_setup_complete(),
			'tables_exist'   => $this->check_all_tables_exist(),
			'settings_exist' => $this->check_settings_exist(),
		);

		return $status;
	}

	/**
	 * Check if all tables exist
	 *
	 * @return bool
	 */
	private function check_all_tables_exist() {
		global $wpdb;

		$tables = array(
			$wpdb->prefix . 'leave_manager_leave_users',
			$wpdb->prefix . 'leave_manager_leave_requests',
			$wpdb->prefix . 'leave_manager_email_logs',
			$wpdb->prefix . 'leave_manager_settings',
			$wpdb->prefix . 'leave_manager_email_queue',
			$wpdb->prefix . 'leave_manager_request_history',
			$wpdb->prefix . 'leave_manager_audit_log',
			$wpdb->prefix . 'leave_manager_leave_policies',
			$wpdb->prefix . 'leave_manager_policy_rules',
			$wpdb->prefix . 'leave_manager_approval_workflows',
			$wpdb->prefix . 'leave_manager_approvals',
			$wpdb->prefix . 'leave_manager_teams',
			$wpdb->prefix . 'leave_manager_team_members',
		);

		foreach ( $tables as $table ) {
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if settings exist
	 *
	 * @return bool
	 */
	private function check_settings_exist() {
		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_settings';
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		return $count > 0;
	}
}
