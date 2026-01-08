<?php
/**
 * Performance Optimization Manager Class
 * Handles caching, query optimization, and performance improvements
 *
 * @package LeaveManager
 * @subpackage Performance
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Performance_Optimizer {

	/**
	 * Cache prefix
	 *
	 * @var string
	 */
	private $cache_prefix = 'leave_manager_';

	/**
	 * Cache duration (in seconds)
	 *
	 * @var int
	 */
	private $cache_duration = 3600; // 1 hour

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize caching
		$this->init_caching();
	}

	/**
	 * Initialize caching
	 */
	private function init_caching() {
		// Enable object caching
		wp_cache_add_global_groups( array( 'leave_manager' ) );
	}

	/**
	 * Get cached data
	 *
	 * @param string $key Cache key
	 * @param string $group Cache group
	 * @return mixed|false Cached data or false
	 */
	public function get_cache( $key, $group = 'leave_manager' ) {
		return wp_cache_get( $this->cache_prefix . $key, $group );
	}

	/**
	 * Set cached data
	 *
	 * @param string $key Cache key
	 * @param mixed  $data Data to cache
	 * @param string $group Cache group
	 * @param int    $duration Cache duration in seconds
	 * @return bool
	 */
	public function set_cache( $key, $data, $group = 'leave_manager', $duration = null ) {
		if ( null === $duration ) {
			$duration = $this->cache_duration;
		}

		return wp_cache_set( $this->cache_prefix . $key, $data, $group, $duration );
	}

	/**
	 * Delete cached data
	 *
	 * @param string $key Cache key
	 * @param string $group Cache group
	 * @return bool
	 */
	public function delete_cache( $key, $group = 'leave_manager' ) {
		return wp_cache_delete( $this->cache_prefix . $key, $group );
	}

	/**
	 * Clear all cache
	 *
	 * @return bool
	 */
	public function clear_all_cache() {
		wp_cache_flush();
		return true;
	}

	/**
	 * Cache user leave balance
	 *
	 * @param int   $user_id User ID
	 * @param float $balance Leave balance
	 * @return bool
	 */
	public function cache_user_balance( $user_id, $balance ) {
		return $this->set_cache( 'user_balance_' . $user_id, $balance, 'leave_manager', 1800 );
	}

	/**
	 * Get cached user balance
	 *
	 * @param int $user_id User ID
	 * @return float|false
	 */
	public function get_cached_user_balance( $user_id ) {
		return $this->get_cache( 'user_balance_' . $user_id, 'leave_manager' );
	}

	/**
	 * Invalidate user balance cache
	 *
	 * @param int $user_id User ID
	 * @return bool
	 */
	public function invalidate_user_balance_cache( $user_id ) {
		return $this->delete_cache( 'user_balance_' . $user_id, 'leave_manager' );
	}

	/**
	 * Cache leave requests
	 *
	 * @param array $requests Leave requests
	 * @return bool
	 */
	public function cache_leave_requests( $requests ) {
		return $this->set_cache( 'leave_requests', $requests, 'leave_manager', 1800 );
	}

	/**
	 * Get cached leave requests
	 *
	 * @return array|false
	 */
	public function get_cached_leave_requests() {
		return $this->get_cache( 'leave_requests', 'leave_manager' );
	}

	/**
	 * Invalidate leave requests cache
	 *
	 * @return bool
	 */
	public function invalidate_leave_requests_cache() {
		return $this->delete_cache( 'leave_requests', 'leave_manager' );
	}

	/**
	 * Cache public holidays
	 *
	 * @param string $country_code Country code
	 * @param array  $holidays Holidays
	 * @return bool
	 */
	public function cache_public_holidays( $country_code, $holidays ) {
		return $this->set_cache( 'holidays_' . $country_code, $holidays, 'leave_manager', 86400 );
	}

	/**
	 * Get cached public holidays
	 *
	 * @param string $country_code Country code
	 * @return array|false
	 */
	public function get_cached_public_holidays( $country_code ) {
		return $this->get_cache( 'holidays_' . $country_code, 'leave_manager' );
	}

	/**
	 * Optimize database queries
	 *
	 * @return array Optimization results
	 */
	public function optimize_database_queries() {
		global $wpdb;

		$results = array(
			'indexes_created' => 0,
			'queries_optimized' => 0,
			'status' => 'success',
		);

		// Add indexes for frequently queried columns
		$indexes = array(
			$wpdb->prefix . 'leave_manager_leave_requests' => array(
				'user_id',
				'status',
				'date_from',
				'date_to',
				'leave_type',
			),
			$wpdb->prefix . 'leave_manager_approval_requests' => array(
				'user_id',
				'status',
				'created_at',
			),
			$wpdb->prefix . 'leave_manager_approval_tasks' => array(
				'approval_request_id',
				'approver_id',
				'status',
			),
			$wpdb->prefix . 'leave_manager_public_holidays' => array(
				'country_code',
				'date',
			),
		);

		foreach ( $indexes as $table => $columns ) {
			foreach ( $columns as $column ) {
				$index_name = 'idx_' . $column;
				$result = $wpdb->query(
					$wpdb->prepare(
						"ALTER TABLE {$table} ADD INDEX {$index_name} ({$column})",
						array()
					)
				);

				if ( false !== $result ) {
					$results['indexes_created']++;
				}
			}
		}

		return $results;
	}

	/**
	 * Enable query result caching
	 *
	 * @param string $query Query to cache
	 * @param string $cache_key Cache key
	 * @param int    $duration Cache duration
	 * @return mixed Query result
	 */
	public function cache_query_result( $query, $cache_key, $duration = null ) {
		global $wpdb;

		// Check cache first
		$cached = $this->get_cache( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Execute query
		$result = $wpdb->get_results( $query );

		// Cache result
		$this->set_cache( $cache_key, $result, 'leave_manager', $duration );

		return $result;
	}

	/**
	 * Enable lazy loading for large datasets
	 *
	 * @param int    $total_items Total items
	 * @param int    $items_per_page Items per page
	 * @param int    $current_page Current page
	 * @return array Pagination data
	 */
	public function get_pagination_data( $total_items, $items_per_page = 20, $current_page = 1 ) {
		$total_pages = ceil( $total_items / $items_per_page );
		$offset = ( $current_page - 1 ) * $items_per_page;

		return array(
			'total_items' => $total_items,
			'items_per_page' => $items_per_page,
			'current_page' => $current_page,
			'total_pages' => $total_pages,
			'offset' => $offset,
			'has_next' => $current_page < $total_pages,
			'has_previous' => $current_page > 1,
		);
	}

	/**
	 * Batch process items
	 *
	 * @param array    $items Items to process
	 * @param callable $callback Callback function
	 * @param int      $batch_size Batch size
	 * @return array Results
	 */
	public function batch_process( $items, $callback, $batch_size = 100 ) {
		$results = array(
			'total' => count( $items ),
			'processed' => 0,
			'failed' => 0,
			'details' => array(),
		);

		$batches = array_chunk( $items, $batch_size );

		foreach ( $batches as $batch ) {
			foreach ( $batch as $item ) {
				$result = call_user_func( $callback, $item );

				if ( is_wp_error( $result ) ) {
					$results['failed']++;
				} else {
					$results['processed']++;
				}

				$results['details'][] = $result;
			}

			// Allow WordPress to handle other tasks
			wp_cache_flush();
		}

		return $results;
	}

	/**
	 * Enable async processing
	 *
	 * @param string $action Action hook
	 * @param array  $args Arguments
	 * @return bool
	 */
	public function schedule_async_task( $action, $args = array() ) {
		// Use WordPress cron for async processing
		return wp_schedule_single_event( time(), 'leave_manager_async_' . $action, $args );
	}

	/**
	 * Compress database
	 *
	 * @return array Compression results
	 */
	public function compress_database() {
		global $wpdb;

		$results = array(
			'tables_optimized' => 0,
			'status' => 'success',
		);

		// Get all Leave Manager tables
		$tables = $wpdb->get_results(
			"SHOW TABLES LIKE '{$wpdb->prefix}leave_manager_%'"
		);

		foreach ( $tables as $table ) {
			$table_name = array_values( (array) $table )[0];

			// Optimize table
			$wpdb->query( "OPTIMIZE TABLE {$table_name}" );
			$results['tables_optimized']++;
		}

		return $results;
	}

	/**
	 * Get performance metrics
	 *
	 * @return array Performance metrics
	 */
	public function get_performance_metrics() {
		global $wpdb;

		$metrics = array(
			'database_size' => 0,
			'cache_hit_ratio' => 0,
			'query_count' => 0,
			'page_load_time' => 0,
		);

		// Get database size
		$size_result = $wpdb->get_results(
			"SELECT SUM(data_length + index_length) as size FROM information_schema.TABLES 
			WHERE table_schema = '" . DB_NAME . "' AND table_name LIKE '{$wpdb->prefix}leave_manager_%'"
		);

		if ( ! empty( $size_result ) ) {
			$metrics['database_size'] = $size_result[0]->size;
		}

		// Get query count
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$metrics['query_count'] = count( $GLOBALS['wpdb']->queries );
		}

		return $metrics;
	}

	/**
	 * Generate performance report
	 *
	 * @return array Performance report
	 */
	public function generate_performance_report() {
		return array(
			'metrics' => $this->get_performance_metrics(),
			'optimization_status' => array(
				'caching_enabled' => true,
				'query_optimization' => true,
				'lazy_loading' => true,
				'batch_processing' => true,
			),
			'recommendations' => $this->get_optimization_recommendations(),
		);
	}

	/**
	 * Get optimization recommendations
	 *
	 * @return array Recommendations
	 */
	private function get_optimization_recommendations() {
		$recommendations = array();

		$metrics = $this->get_performance_metrics();

		// Check database size
		if ( $metrics['database_size'] > 100 * 1024 * 1024 ) { // 100MB
			$recommendations[] = 'Consider archiving old leave records to reduce database size';
		}

		// Check query count
		if ( $metrics['query_count'] > 100 ) {
			$recommendations[] = 'Consider enabling query caching to reduce database queries';
		}

		return $recommendations;
	}

	/**
	 * Enable CDN for static assets
	 *
	 * @param string $asset_url Asset URL
	 * @return string CDN URL
	 */
	public function get_cdn_url( $asset_url ) {
		$cdn_url = get_option( 'leave_manager_cdn_url', '' );

		if ( ! empty( $cdn_url ) ) {
			return $cdn_url . str_replace( LEAVE_MANAGER_PLUGIN_URL, '', $asset_url );
		}

		return $asset_url;
	}

	/**
	 * Minify CSS
	 *
	 * @param string $css CSS content
	 * @return string Minified CSS
	 */
	public function minify_css( $css ) {
		// Remove comments
		$css = preg_replace( '!/\*[^*]*\*+(?:[^/*][^*]*\*+)*/!', '', $css );

		// Remove whitespace
		$css = preg_replace( '/\s+/', ' ', $css );
		$css = preg_replace( '/\s*([{}:;,])\s*/', '$1', $css );

		return trim( $css );
	}

	/**
	 * Minify JavaScript
	 *
	 * @param string $js JavaScript content
	 * @return string Minified JavaScript
	 */
	public function minify_javascript( $js ) {
		// Remove comments
		$js = preg_replace( '!/\*[^*]*\*+(?:[^/*][^*]*\*+)*/!', '', $js );
		$js = preg_replace( '!//.*?[\r\n]!', '', $js );

		// Remove whitespace
		$js = preg_replace( '/\s+/', ' ', $js );

		return trim( $js );
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_performance' ) ) {
	/**
	 * Get performance optimizer instance
	 *
	 * @return Leave_Manager_Performance_Optimizer
	 */
	function leave_manager_performance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Performance_Optimizer();
		}

		return $instance;
	}
}
