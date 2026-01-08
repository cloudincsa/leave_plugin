<?php
/**
 * Webhooks class for Leave Manager Plugin
 *
 * Handles webhook events and subscriptions for third-party integrations.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Webhooks class
 */
class Leave_Manager_Webhooks {

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
	 * Webhook events
	 *
	 * @var array
	 */
	private $events = array(
		'leave_request.created',
		'leave_request.updated',
		'leave_request.approved',
		'leave_request.rejected',
		'leave_request.cancelled',
		'user.created',
		'user.updated',
		'user.deleted',
		'policy.created',
		'policy.updated',
		'policy.deleted',
	);

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
	 * Register webhook hooks
	 *
	 * @return void
	 */
	public function register_hooks() {
		// Leave request hooks
		add_action( 'leave_manager_leave_request_created', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'leave_manager_leave_request_updated', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'leave_manager_leave_request_approved', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'leave_manager_leave_request_rejected', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'leave_manager_leave_request_cancelled', array( $this, 'trigger_webhook' ), 10, 2 );

		// User hooks
		add_action( 'leave_manager_user_created', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'leave_manager_user_updated', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'leave_manager_user_deleted', array( $this, 'trigger_webhook' ), 10, 2 );

		// Policy hooks
		add_action( 'leave_manager_policy_created', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'leave_manager_policy_updated', array( $this, 'trigger_webhook' ), 10, 2 );
		add_action( 'leave_manager_policy_deleted', array( $this, 'trigger_webhook' ), 10, 2 );
	}

	/**
	 * Register a webhook subscription
	 *
	 * @param string $event Event name
	 * @param string $url Webhook URL
	 * @param array  $headers Additional headers
	 * @return int|false Webhook ID or false
	 */
	public function register_webhook( $event, $url, $headers = array() ) {
		if ( ! in_array( $event, $this->events, true ) ) {
			$this->logger->error( 'Invalid webhook event', array( 'event' => $event ) );
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_webhooks';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			$this->create_webhooks_table();
		}

		$result = $wpdb->insert(
			$table,
			array(
				'event'   => $event,
				'url'     => esc_url_raw( $url ),
				'headers' => wp_json_encode( $headers ),
				'status'  => 'active',
			),
			array( '%s', '%s', '%s', '%s' )
		);

		if ( $result ) {
			$this->logger->info( 'Webhook registered', array( 'event' => $event, 'url' => $url ) );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Unregister a webhook
	 *
	 * @param int $webhook_id Webhook ID
	 * @return bool True on success
	 */
	public function unregister_webhook( $webhook_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_webhooks';

		$result = $wpdb->delete(
			$table,
			array( 'webhook_id' => $webhook_id ),
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( 'Webhook unregistered', array( 'webhook_id' => $webhook_id ) );
		}

		return $result;
	}

	/**
	 * Get webhooks for an event
	 *
	 * @param string $event Event name
	 * @return array Webhooks
	 */
	public function get_webhooks( $event ) {
		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_webhooks';

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE event = %s AND status = 'active'",
			$event
		) );
	}

	/**
	 * Trigger webhook event
	 *
	 * @param string $event Event name
	 * @param array  $data Event data
	 * @return void
	 */
	public function trigger_webhook( $event, $data ) {
		$webhooks = $this->get_webhooks( $event );

		foreach ( $webhooks as $webhook ) {
			$this->send_webhook( $webhook, $event, $data );
		}
	}

	/**
	 * Send webhook to URL
	 *
	 * @param object $webhook Webhook object
	 * @param string $event Event name
	 * @param array  $data Event data
	 * @return bool True on success
	 */
	private function send_webhook( $webhook, $event, $data ) {
		$payload = array(
			'event'       => $event,
			'timestamp'   => current_time( 'mysql' ),
			'data'        => $data,
			'webhook_id'  => $webhook->webhook_id,
		);

		$headers = array(
			'Content-Type'  => 'application/json',
			'X-Webhook-ID'  => $webhook->webhook_id,
			'X-Webhook-Event' => $event,
			'X-Webhook-Signature' => hash_hmac( 'sha256', wp_json_encode( $payload ), wp_salt() ),
		);

		// Merge custom headers
		if ( ! empty( $webhook->headers ) ) {
			$custom_headers = json_decode( $webhook->headers, true );
			$headers = array_merge( $headers, $custom_headers );
		}

		$response = wp_remote_post(
			$webhook->url,
			array(
				'headers'   => $headers,
				'body'      => wp_json_encode( $payload ),
				'timeout'   => 30,
				'sslverify' => true,
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'Webhook delivery failed', array(
				'webhook_id' => $webhook->webhook_id,
				'error'      => $response->get_error_message(),
			) );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code < 200 || $response_code >= 300 ) {
			$this->logger->warning( 'Webhook delivery failed with status code', array(
				'webhook_id' => $webhook->webhook_id,
				'status_code' => $response_code,
			) );
			return false;
		}

		$this->logger->info( 'Webhook delivered successfully', array(
			'webhook_id' => $webhook->webhook_id,
			'event'      => $event,
		) );

		return true;
	}

	/**
	 * Create webhooks table
	 *
	 * @return void
	 */
	private function create_webhooks_table() {
		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_webhooks';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table (
			webhook_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			event VARCHAR(100) NOT NULL,
			url VARCHAR(500) NOT NULL,
			headers LONGTEXT,
			status VARCHAR(20) DEFAULT 'active',
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (webhook_id),
			KEY event (event),
			KEY status (status)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Get all webhooks
	 *
	 * @return array All webhooks
	 */
	public function get_all_webhooks() {
		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_webhooks';

		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC" );
	}

	/**
	 * Update webhook status
	 *
	 * @param int    $webhook_id Webhook ID
	 * @param string $status New status
	 * @return bool True on success
	 */
	public function update_webhook_status( $webhook_id, $status ) {
		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_webhooks';

		return $wpdb->update(
			$table,
			array( 'status' => $status ),
			array( 'webhook_id' => $webhook_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get available webhook events
	 *
	 * @return array Available events
	 */
	public function get_available_events() {
		return $this->events;
	}
}
