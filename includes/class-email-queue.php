<?php
/**
 * Email Queue Handler class for Leave Manager Plugin
 *
 * Handles asynchronous email processing with queue management and retry logic.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Email_Queue class
 */
class Leave_Manager_Email_Queue {

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
	 * Email Handler instance
	 *
	 * @var Leave_Manager_Email_Handler
	 */
	private $email_handler;

	/**
	 * Queue table name
	 *
	 * @var string
	 */
	private $queue_table;

	/**
	 * Maximum retry count
	 *
	 * @var int
	 */
	private $max_retries = 3;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database      $db Database instance
	 * @param Leave_Manager_Logger        $logger Logger instance
	 * @param Leave_Manager_Email_Handler $email_handler Email handler instance
	 */
	public function __construct( $db, $logger, $email_handler ) {
		$this->db             = $db;
		$this->logger         = $logger;
		$this->email_handler  = $email_handler;
		$this->queue_table    = $db->wpdb->prefix . 'leave_manager_email_queue';
	}

	/**
	 * Queue an email for sending
	 *
	 * @param string $recipient Recipient email
	 * @param string $template Template name
	 * @param array  $variables Template variables
	 * @return int|false Queue ID or false
	 */
	public function queue_email( $recipient, $template, $variables = array() ) {
		$recipient = sanitize_email( $recipient );

		if ( ! is_email( $recipient ) ) {
			$this->logger->error( 'Invalid email address for queue', array( 'email' => $recipient ) );
			return false;
		}

		$data = array(
			'recipient_email' => $recipient,
			'template_used'   => sanitize_text_field( $template ),
			'variables'       => wp_json_encode( $variables ),
			'status'          => 'pending',
			'retry_count'     => 0,
			'created_at'      => current_time( 'mysql' ),
		);

		$result = $this->db->insert(
			$this->queue_table,
			$data,
			array( '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		if ( $result ) {
			$this->logger->info( 'Email queued successfully', array(
				'queue_id'  => $result,
				'recipient' => $recipient,
				'template'  => $template,
			) );
		} else {
			$this->logger->error( 'Failed to queue email', array(
				'recipient' => $recipient,
				'template'  => $template,
			) );
		}

		return $result;
	}

	/**
	 * Process pending emails in queue
	 *
	 * @param int $batch_size Number of emails to process
	 * @return int Number of emails processed
	 */
	public function process_queue( $batch_size = 10 ) {
		global $wpdb;

		// Get pending emails
		$pending_emails = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->queue_table}
				 WHERE status = 'pending'
				 ORDER BY created_at ASC
				 LIMIT %d",
				$batch_size
			)
		);

		if ( empty( $pending_emails ) ) {
			return 0;
		}

		$processed = 0;

		foreach ( $pending_emails as $email ) {
			$variables = json_decode( $email->variables, true );

			// Send email
			$result = $this->email_handler->send_template_email(
				$email->recipient_email,
				$email->template_used,
				$variables
			);

			if ( $result ) {
				// Mark as sent
				$this->update_queue_status( $email->queue_id, 'sent' );
				$processed++;
			} else {
				// Increment retry count
				$new_retry_count = $email->retry_count + 1;

				if ( $new_retry_count >= $this->max_retries ) {
					// Mark as failed after max retries
					$this->update_queue_status( $email->queue_id, 'failed', 'Max retries exceeded' );
				} else {
					// Keep as pending for retry
					$this->update_retry_count( $email->queue_id, $new_retry_count );
				}
			}
		}

		$this->logger->info( 'Email queue processed', array( 'processed' => $processed ) );

		return $processed;
	}

	/**
	 * Retry failed emails
	 *
	 * @return int Number of emails retried
	 */
	public function retry_failed_emails() {
		global $wpdb;

		// Get failed emails that haven't exceeded max retries
		$failed_emails = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->queue_table}
				 WHERE status = 'failed'
				 AND retry_count < %d
				 AND updated_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
				 ORDER BY updated_at ASC
				 LIMIT 10",
				$this->max_retries
			)
		);

		if ( empty( $failed_emails ) ) {
			return 0;
		}

		$retried = 0;

		foreach ( $failed_emails as $email ) {
			// Reset status to pending for retry
			$wpdb->update(
				$this->queue_table,
				array(
					'status'      => 'pending',
					'retry_count' => $email->retry_count + 1,
					'updated_at'  => current_time( 'mysql' ),
				),
				array( 'queue_id' => $email->queue_id ),
				array( '%s', '%d', '%s' ),
				array( '%d' )
			);

			$retried++;
		}

		$this->logger->info( 'Failed emails reset for retry', array( 'count' => $retried ) );

		return $retried;
	}

	/**
	 * Update queue status
	 *
	 * @param int    $queue_id Queue ID
	 * @param string $status New status
	 * @param string $error_message Error message if any
	 * @return bool True on success
	 */
	private function update_queue_status( $queue_id, $status, $error_message = '' ) {
		global $wpdb;

		$data = array(
			'status'      => $status,
			'updated_at'  => current_time( 'mysql' ),
		);

		if ( ! empty( $error_message ) ) {
			$data['error_message'] = $error_message;
		}

		return (bool) $wpdb->update(
			$this->queue_table,
			$data,
			array( 'queue_id' => $queue_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Update retry count
	 *
	 * @param int $queue_id Queue ID
	 * @param int $retry_count New retry count
	 * @return bool True on success
	 */
	private function update_retry_count( $queue_id, $retry_count ) {
		global $wpdb;

		return (bool) $wpdb->update(
			$this->queue_table,
			array(
				'retry_count' => $retry_count,
				'updated_at'  => current_time( 'mysql' ),
			),
			array( 'queue_id' => $queue_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get queue statistics
	 *
	 * @return array Queue statistics
	 */
	public function get_statistics() {
		global $wpdb;

		return array(
			'pending'  => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->queue_table} WHERE status = 'pending'" ) ),
			'sent'     => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->queue_table} WHERE status = 'sent'" ) ),
			'failed'   => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->queue_table} WHERE status = 'failed'" ) ),
			'total'    => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->queue_table}" ) ),
		);
	}

	/**
	 * Clear old queue entries
	 *
	 * @param int $days Number of days to keep
	 * @return int Number of entries deleted
	 */
	public function cleanup_old_entries( $days = 30 ) {
		global $wpdb;

		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->queue_table}
				 WHERE status IN ('sent', 'failed')
				 AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		$this->logger->info( 'Email queue cleaned up', array( 'deleted' => $result ) );

		return $result;
	}
}
