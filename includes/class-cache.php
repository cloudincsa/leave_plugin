<?php
/**
 * Cache class for Leave Manager Plugin
 *
 * Handles caching of frequently accessed data using WordPress transients.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Cache class
 */
class Leave_Manager_Cache {

	/**
	 * Cache prefix
	 *
	 * @var string
	 */
	private $prefix = 'leave_manager_leave_';

	/**
	 * Default cache duration (1 hour)
	 *
	 * @var int
	 */
	private $default_duration = 3600;

	/**
	 * Get cached value
	 *
	 * @param string $key Cache key
	 * @param mixed  $default Default value if not found
	 * @return mixed Cached value or default
	 */
	public function get( $key, $default = false ) {
		$cache_key = $this->prefix . $key;
		$value = wp_cache_get( $cache_key );
		
		if ( false === $value ) {
			$value = get_transient( $cache_key );
		}
		
		return false === $value ? $default : $value;
	}

	/**
	 * Set cache value
	 *
	 * @param string $key Cache key
	 * @param mixed  $value Value to cache
	 * @param int    $duration Cache duration in seconds
	 * @return bool True on success
	 */
	public function set( $key, $value, $duration = null ) {
		if ( null === $duration ) {
			$duration = $this->default_duration;
		}

		$cache_key = $this->prefix . $key;
		
		// Set both object cache and transient for redundancy
		wp_cache_set( $cache_key, $value, '', $duration );
		set_transient( $cache_key, $value, $duration );
		
		return true;
	}

	/**
	 * Delete cached value
	 *
	 * @param string $key Cache key
	 * @return bool True on success
	 */
	public function delete( $key ) {
		$cache_key = $this->prefix . $key;
		wp_cache_delete( $cache_key );
		delete_transient( $cache_key );
		return true;
	}

	/**
	 * Clear all plugin cache
	 *
	 * @return bool True on success
	 */
	public function flush() {
		global $wpdb;
		
		// Clear object cache
		wp_cache_flush();
		
		// Clear transients
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			'_transient_' . $this->prefix . '%',
			'_transient_timeout_' . $this->prefix . '%'
		) );
		
		return true;
	}

	/**
	 * Cache leave balance
	 *
	 * @param int   $user_id User ID
	 * @param array $balance Leave balance data
	 * @return bool True on success
	 */
	public function cache_leave_balance( $user_id, $balance ) {
		return $this->set( 'balance_' . $user_id, $balance, 3600 ); // 1 hour
	}

	/**
	 * Get cached leave balance
	 *
	 * @param int $user_id User ID
	 * @return array|false Cached balance or false
	 */
	public function get_leave_balance( $user_id ) {
		return $this->get( 'balance_' . $user_id );
	}

	/**
	 * Clear leave balance cache
	 *
	 * @param int $user_id User ID
	 * @return bool True on success
	 */
	public function clear_leave_balance( $user_id ) {
		return $this->delete( 'balance_' . $user_id );
	}

	/**
	 * Cache leave policy
	 *
	 * @param int   $policy_id Policy ID
	 * @param array $policy Policy data
	 * @return bool True on success
	 */
	public function cache_policy( $policy_id, $policy ) {
		return $this->set( 'policy_' . $policy_id, $policy, 86400 ); // 24 hours
	}

	/**
	 * Get cached policy
	 *
	 * @param int $policy_id Policy ID
	 * @return array|false Cached policy or false
	 */
	public function get_policy( $policy_id ) {
		return $this->get( 'policy_' . $policy_id );
	}

	/**
	 * Clear policy cache
	 *
	 * @param int $policy_id Policy ID
	 * @return bool True on success
	 */
	public function clear_policy( $policy_id ) {
		return $this->delete( 'policy_' . $policy_id );
	}

	/**
	 * Cache user data
	 *
	 * @param int   $user_id User ID
	 * @param array $user_data User data
	 * @return bool True on success
	 */
	public function cache_user( $user_id, $user_data ) {
		return $this->set( 'user_' . $user_id, $user_data, 3600 ); // 1 hour
	}

	/**
	 * Get cached user data
	 *
	 * @param int $user_id User ID
	 * @return array|false Cached user data or false
	 */
	public function get_user( $user_id ) {
		return $this->get( 'user_' . $user_id );
	}

	/**
	 * Clear user cache
	 *
	 * @param int $user_id User ID
	 * @return bool True on success
	 */
	public function clear_user( $user_id ) {
		return $this->delete( 'user_' . $user_id );
	}

	/**
	 * Cache leave requests list
	 *
	 * @param string $cache_key Cache key identifier
	 * @param array  $requests Leave requests data
	 * @return bool True on success
	 */
	public function cache_requests( $cache_key, $requests ) {
		return $this->set( 'requests_' . $cache_key, $requests, 1800 ); // 30 minutes
	}

	/**
	 * Get cached leave requests
	 *
	 * @param string $cache_key Cache key identifier
	 * @return array|false Cached requests or false
	 */
	public function get_requests( $cache_key ) {
		return $this->get( 'requests_' . $cache_key );
	}

	/**
	 * Clear requests cache
	 *
	 * @param string $cache_key Cache key identifier
	 * @return bool True on success
	 */
	public function clear_requests( $cache_key = null ) {
		if ( null === $cache_key ) {
			// Clear all requests cache
			global $wpdb;
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_' . $this->prefix . 'requests_%'
			) );
			return true;
		}
		return $this->delete( 'requests_' . $cache_key );
	}

	/**
	 * Cache API response
	 *
	 * @param string $endpoint API endpoint
	 * @param array  $response Response data
	 * @return bool True on success
	 */
	public function cache_api_response( $endpoint, $response ) {
		return $this->set( 'api_' . md5( $endpoint ), $response, 300 ); // 5 minutes
	}

	/**
	 * Get cached API response
	 *
	 * @param string $endpoint API endpoint
	 * @return array|false Cached response or false
	 */
	public function get_api_response( $endpoint ) {
		return $this->get( 'api_' . md5( $endpoint ) );
	}

	/**
	 * Clear API response cache
	 *
	 * @param string $endpoint API endpoint (optional)
	 * @return bool True on success
	 */
	public function clear_api_response( $endpoint = null ) {
		if ( null === $endpoint ) {
			// Clear all API cache
			global $wpdb;
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				'_transient_' . $this->prefix . 'api_%'
			) );
			return true;
		}
		return $this->delete( 'api_' . md5( $endpoint ) );
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Cache statistics
	 */
	public function get_stats() {
		global $wpdb;
		
		$transient_count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
			'_transient_' . $this->prefix . '%'
		) );

		return array(
			'transient_count' => intval( $transient_count ),
			'cache_prefix'    => $this->prefix,
			'default_duration' => $this->default_duration,
		);
	}
}
