<?php
/**
 * Cron Jobs class for Leave Manager Plugin
 *
 * Handles automated tasks and scheduled events.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Cron_Jobs class
 */
class Leave_Manager_Cron_Jobs {

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
	 * Cache instance
	 *
	 * @var Leave_Manager_Cache
	 */
	private $cache;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 * @param Leave_Manager_Cache    $cache Cache instance
	 */
	public function __construct( $db, $logger, $cache ) {
		$this->db     = $db;
		$this->logger = $logger;
		$this->cache  = $cache;
	}

	/**
	 * Register cron jobs
	 *
	 * @return void
	 */
	public function register_cron_jobs() {
		// Daily leave balance reset
		if ( ! wp_next_scheduled( 'leave_manager_daily_reset_leave_balances' ) ) {
			wp_schedule_event( time(), 'daily', 'leave_manager_daily_reset_leave_balances' );
		}

		// Daily expiry notifications
		if ( ! wp_next_scheduled( 'leave_manager_daily_send_expiry_notifications' ) ) {
			wp_schedule_event( time(), 'daily', 'leave_manager_daily_send_expiry_notifications' );
		}

		// Weekly report generation
		if ( ! wp_next_scheduled( 'leave_manager_weekly_generate_reports' ) ) {
			wp_schedule_event( time(), 'weekly', 'leave_manager_weekly_generate_reports' );
		}

		// Hourly email queue processing
		if ( ! wp_next_scheduled( 'leave_manager_hourly_process_email_queue' ) ) {
			wp_schedule_event( time(), 'hourly', 'leave_manager_hourly_process_email_queue' );
		}

		// Twice daily cache cleanup
		if ( ! wp_next_scheduled( 'leave_manager_twice_daily_cleanup_cache' ) ) {
			wp_schedule_event( time(), 'twicedaily', 'leave_manager_twice_daily_cleanup_cache' );
		}

		// Hook into cron events
		add_action( 'leave_manager_daily_reset_leave_balances', array( $this, 'reset_leave_balances' ) );
		add_action( 'leave_manager_daily_send_expiry_notifications', array( $this, 'send_expiry_notifications' ) );
		add_action( 'leave_manager_weekly_generate_reports', array( $this, 'generate_reports' ) );
		add_action( 'leave_manager_hourly_process_email_queue', array( $this, 'process_email_queue' ) );
		add_action( 'leave_manager_twice_daily_cleanup_cache', array( $this, 'cleanup_cache' ) );
	}

	/**
	 * Reset leave balances for all users (annual reset)
	 *
	 * @return void
	 */
	public function reset_leave_balances() {
		global $wpdb;

		$users_table = $wpdb->prefix . 'leave_manager_leave_users';
		$policies_table = $wpdb->prefix . 'leave_manager_leave_policies';

		// Get all active users with their policies
		$users = $wpdb->get_results( "
			SELECT lu.user_id, lu.policy_id, lp.annual_days, lp.sick_days, lp.other_days
			FROM {$users_table} lu
			LEFT JOIN {$policies_table} lp ON lu.policy_id = lp.policy_id
			WHERE lu.status = 'active'
		" );

		foreach ( $users as $user ) {
			$annual_days = $user->annual_days ?? 20;
			$sick_days = $user->sick_days ?? 10;
			$other_days = $user->other_days ?? 5;

			// Update leave balances
			$wpdb->update(
				$users_table,
				array(
					'annual_leave_balance' => $annual_days,
					'sick_leave_balance'   => $sick_days,
					'other_leave_balance'  => $other_days,
				),
				array( 'user_id' => $user->user_id ),
				array( '%f', '%f', '%f' ),
				array( '%d' )
			);

			// Clear cache
			$this->cache->clear_leave_balance( $user->user_id );
		}

		$this->logger->info( 'Daily leave balance reset completed', array( 'users_updated' => count( $users ) ) );
	}

	/**
	 * Send expiry notifications for leave about to expire
	 *
	 * @return void
	 */
	public function send_expiry_notifications() {
		global $wpdb;

		$users_table = $wpdb->prefix . 'leave_manager_leave_users';
		$policies_table = $wpdb->prefix . 'leave_manager_leave_policies';

		// Get users with leave expiring in 7 days
		$expiry_date = date( 'Y-m-d', strtotime( '+7 days' ) );

		$users = $wpdb->get_results( $wpdb->prepare(
			"SELECT lu.user_id, lu.email, lu.first_name, lp.expiry_days
			FROM {$users_table} lu
			LEFT JOIN {$policies_table} lp ON lu.policy_id = lp.policy_id
			WHERE lu.status = 'active'
			AND DATE_ADD(lu.created_at, INTERVAL COALESCE(lp.expiry_days, 365) DAY) = %s",
			$expiry_date
		) );

		foreach ( $users as $user ) {
			// Send notification email
			$to = $user->email;
			$subject = 'Leave Balance Expiry Notification';
			$message = sprintf(
				'Hello %s,\n\nYour leave balance will expire in 7 days. Please use your remaining leave before the expiry date.\n\nBest regards,\nLeave Management System',
				$user->first_name
			);

			wp_mail( $to, $subject, $message );
		}

		$this->logger->info( 'Expiry notifications sent', array( 'notifications_sent' => count( $users ) ) );
	}

	/**
	 * Generate and send weekly summary reports
	 *
	 * @return void
	 */
	public function generate_reports() {
		// Check if it's the correct day to send
		$settings = new Leave_Manager_Settings( $this->db );
		$scheduled_day = (int) $settings->get( 'weekly_summary_day', 1 );
		$current_day = (int) date( 'w' ); // 0 = Sunday, 1 = Monday, etc.
		
		// Only send on the scheduled day
		if ( $current_day !== $scheduled_day ) {
			$this->logger->info( 'Weekly summary skipped - not scheduled day', array(
				'scheduled_day' => $scheduled_day,
				'current_day' => $current_day,
			) );
			return;
		}
		
		// Create and send the weekly summary
		$weekly_summary = new Leave_Manager_Weekly_Summary( $this->db, $this->logger, $settings );
		$result = $weekly_summary->send_summary();
		
		if ( $result === false ) {
			$this->logger->info( 'Weekly summary is disabled' );
		} elseif ( is_array( $result ) && isset( $result['error'] ) ) {
			$this->logger->error( 'Weekly summary failed', $result );
		} else {
			$this->logger->info( 'Weekly summary sent successfully', array( 'results' => $result ) );
		}
		
		// Clear cache
		$this->cache->clear_requests( 'weekly_report' );
	}

	/**
	 * Process email queue
	 *
	 * @return void
	 */
	public function process_email_queue() {
		global $wpdb;

		$queue_table = $wpdb->prefix . 'leave_manager_email_queue';

		// Get pending emails (limit to 50 per run)
		$emails = $wpdb->get_results( "
			SELECT * FROM {$queue_table}
			WHERE status = 'pending'
			ORDER BY created_at ASC
			LIMIT 50
		" );

		$processed = 0;
		foreach ( $emails as $email ) {
			$headers = array();
			if ( ! empty( $email->reply_to ) ) {
				$headers[] = 'Reply-To: ' . $email->reply_to;
			}

			$sent = wp_mail( $email->recipient_email, $email->subject, $email->body, $headers );

			// Update email status
			$wpdb->update(
				$queue_table,
				array(
					'status'      => $sent ? 'sent' : 'failed',
					'sent_at'     => current_time( 'mysql' ),
					'retry_count' => $email->retry_count + 1,
				),
				array( 'queue_id' => $email->queue_id ),
				array( '%s', '%s', '%d' ),
				array( '%d' )
			);

			$processed++;
		}

		$this->logger->info( 'Email queue processed', array( 'emails_processed' => $processed ) );
	}

	/**
	 * Cleanup cache and remove expired transients
	 *
	 * @return void
	 */
	public function cleanup_cache() {
		global $wpdb;

		// Remove expired transients
		$wpdb->query( "
			DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_timeout_leave_manager_leave_%'
			AND option_value < UNIX_TIMESTAMP()
		" );

		// Clear object cache
		wp_cache_flush();

		$this->logger->info( 'Cache cleanup completed' );
	}

	/**
	 * Unregister all cron jobs
	 *
	 * @return void
	 */
	public function unregister_cron_jobs() {
		wp_clear_scheduled_hook( 'leave_manager_daily_reset_leave_balances' );
		wp_clear_scheduled_hook( 'leave_manager_daily_send_expiry_notifications' );
		wp_clear_scheduled_hook( 'leave_manager_weekly_generate_reports' );
		wp_clear_scheduled_hook( 'leave_manager_hourly_process_email_queue' );
		wp_clear_scheduled_hook( 'leave_manager_twice_daily_cleanup_cache' );
	}
}
