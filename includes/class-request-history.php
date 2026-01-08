<?php
/**
 * Request History Handler class for Leave Manager Plugin
 *
 * Tracks all changes to leave requests for audit purposes.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Request_History class
 */
class Leave_Manager_Request_History {

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
	 * History table name
	 *
	 * @var string
	 */
	private $history_table;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 */
	public function __construct( $db, $logger ) {
		$this->db             = $db;
		$this->logger         = $logger;
		$this->history_table  = $db->wpdb->prefix . 'leave_manager_request_history';
	}

	/**
	 * Log a request action
	 *
	 * @param int    $request_id Request ID
	 * @param string $action Action performed
	 * @param int    $changed_by User ID who made the change
	 * @param mixed  $old_value Old value
	 * @param mixed  $new_value New value
	 * @return int|false History ID or false
	 */
	public function log_action( $request_id, $action, $changed_by = null, $old_value = null, $new_value = null ) {
		$data = array(
			'request_id' => intval( $request_id ),
			'action'     => sanitize_text_field( $action ),
			'changed_by' => ! empty( $changed_by ) ? intval( $changed_by ) : null,
			'old_value'  => ! empty( $old_value ) ? wp_json_encode( $old_value ) : null,
			'new_value'  => ! empty( $new_value ) ? wp_json_encode( $new_value ) : null,
			'created_at' => current_time( 'mysql' ),
		);

		$result = $this->db->insert(
			$this->history_table,
			$data,
			array( '%d', '%s', '%d', '%s', '%s', '%s' )
		);

		if ( $result ) {
			$this->logger->info( 'Request history logged', array(
				'request_id' => $request_id,
				'action'     => $action,
				'changed_by' => $changed_by,
			) );
		}

		return $result;
	}

	/**
	 * Get history for a request
	 *
	 * @param int $request_id Request ID
	 * @return array History entries
	 */
	public function get_request_history( $request_id ) {
		global $wpdb;

		$request_id = intval( $request_id );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->history_table}
				 WHERE request_id = %d
				 ORDER BY created_at DESC",
				$request_id
			)
		);
	}

	/**
	 * Get history for a user
	 *
	 * @param int $user_id User ID
	 * @return array History entries
	 */
	public function get_user_history( $user_id ) {
		global $wpdb;

		$user_id = intval( $user_id );
		$requests_table = $this->db->leave_requests_table;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT h.* FROM {$this->history_table} h
				 JOIN {$requests_table} r ON h.request_id = r.request_id
				 WHERE r.user_id = %d
				 ORDER BY h.created_at DESC",
				$user_id
			)
		);
	}

	/**
	 * Get history by action
	 *
	 * @param string $action Action name
	 * @param int    $limit Number of records
	 * @return array History entries
	 */
	public function get_history_by_action( $action, $limit = 100 ) {
		global $wpdb;

		$action = sanitize_text_field( $action );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->history_table}
				 WHERE action = %s
				 ORDER BY created_at DESC
				 LIMIT %d",
				$action,
				$limit
			)
		);
	}

	/**
	 * Get history by date range
	 *
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return array History entries
	 */
	public function get_history_by_date_range( $start_date, $end_date ) {
		global $wpdb;

		$start_date = date( 'Y-m-d 00:00:00', strtotime( $start_date ) );
		$end_date   = date( 'Y-m-d 23:59:59', strtotime( $end_date ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->history_table}
				 WHERE created_at BETWEEN %s AND %s
				 ORDER BY created_at DESC",
				$start_date,
				$end_date
			)
		);
	}

	/**
	 * Get history statistics
	 *
	 * @return array Statistics
	 */
	public function get_statistics() {
		global $wpdb;

		$actions = $wpdb->get_results(
			"SELECT action, COUNT(*) as count FROM {$this->history_table}
			 GROUP BY action"
		);

		$stats = array(
			'total' => intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$this->history_table}" ) ),
			'by_action' => array(),
		);

		foreach ( $actions as $action ) {
			$stats['by_action'][ $action->action ] = intval( $action->count );
		}

		return $stats;
	}

	/**
	 * Clear old history entries
	 *
	 * @param int $days Number of days to keep
	 * @return int Number of entries deleted
	 */
	public function cleanup_old_entries( $days = 90 ) {
		global $wpdb;

		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->history_table}
				 WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		$this->logger->info( 'Request history cleaned up', array( 'deleted' => $result ) );

		return $result;
	}
}
