<?php
/**
 * Security class for Leave Manager Plugin
 *
 * Handles rate limiting, security headers, and other security measures.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Security class
 */
class Leave_Manager_Security {

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
	 * Rate limit configuration
	 *
	 * @var array
	 */
	private $rate_limits = array(
		'api_login' => array( 'limit' => 5, 'window' => 300 ),      // 5 attempts per 5 minutes
		'api_request' => array( 'limit' => 100, 'window' => 3600 ),  // 100 requests per hour
		'password_reset' => array( 'limit' => 3, 'window' => 3600 ), // 3 attempts per hour
		'signup' => array( 'limit' => 10, 'window' => 86400 ),       // 10 signups per day
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
	 * Initialize security measures
	 *
	 * @return void
	 */
	public function init() {
		// Add security headers
		add_action( 'wp_headers', array( $this, 'add_security_headers' ) );
		add_action( 'rest_pre_dispatch', array( $this, 'add_rest_security_headers' ) );
	}

	/**
	 * Add security headers to HTTP response
	 *
	 * @param array $headers HTTP headers
	 * @return array Modified headers
	 */
	public function add_security_headers( $headers ) {
		// Content Security Policy
		$headers['Content-Security-Policy'] = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:";

		// X-Frame-Options
		$headers['X-Frame-Options'] = 'SAMEORIGIN';

		// X-Content-Type-Options
		$headers['X-Content-Type-Options'] = 'nosniff';

		// X-XSS-Protection
		$headers['X-XSS-Protection'] = '1; mode=block';

		// Referrer-Policy
		$headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';

		// Permissions-Policy
		$headers['Permissions-Policy'] = 'geolocation=(), microphone=(), camera=()';

		// Strict-Transport-Security (if HTTPS)
		if ( is_ssl() ) {
			$headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
		}

		return $headers;
	}

	/**
	 * Add security headers to REST API responses
	 *
	 * @param mixed $result REST response
	 * @return mixed Unmodified result
	 */
	public function add_rest_security_headers( $result ) {
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-XSS-Protection: 1; mode=block' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );

		return $result;
	}

	/**
	 * Check rate limit
	 *
	 * @param string $action Action name
	 * @param string $identifier Unique identifier (IP, user ID, etc.)
	 * @return bool True if within limit, false if exceeded
	 */
	public function check_rate_limit( $action, $identifier ) {
		if ( ! isset( $this->rate_limits[ $action ] ) ) {
			return true; // No limit configured
		}

		$limit_config = $this->rate_limits[ $action ];
		$cache_key = 'rate_limit_' . $action . '_' . md5( $identifier );
		$current_count = get_transient( $cache_key );

		if ( false === $current_count ) {
			$current_count = 0;
		}

		$current_count++;

		// Check if limit exceeded
		if ( $current_count > $limit_config['limit'] ) {
			$this->logger->warning( 'Rate limit exceeded', array(
				'action' => $action,
				'identifier' => $identifier,
				'count' => $current_count,
				'limit' => $limit_config['limit'],
			) );
			return false;
		}

		// Update counter
		set_transient( $cache_key, $current_count, $limit_config['window'] );

		return true;
	}

	/**
	 * Get remaining rate limit attempts
	 *
	 * @param string $action Action name
	 * @param string $identifier Unique identifier
	 * @return int Remaining attempts
	 */
	public function get_remaining_attempts( $action, $identifier ) {
		if ( ! isset( $this->rate_limits[ $action ] ) ) {
			return -1; // No limit
		}

		$limit_config = $this->rate_limits[ $action ];
		$cache_key = 'rate_limit_' . $action . '_' . md5( $identifier );
		$current_count = get_transient( $cache_key );

		if ( false === $current_count ) {
			return $limit_config['limit'];
		}

		return max( 0, $limit_config['limit'] - $current_count );
	}

	/**
	 * Reset rate limit
	 *
	 * @param string $action Action name
	 * @param string $identifier Unique identifier
	 * @return bool True on success
	 */
	public function reset_rate_limit( $action, $identifier ) {
		$cache_key = 'rate_limit_' . $action . '_' . md5( $identifier );
		return delete_transient( $cache_key );
	}

	/**
	 * Get client IP address
	 *
	 * @return string Client IP address
	 */
	public function get_client_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		// Validate IP
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return '0.0.0.0';
	}

	/**
	 * Sanitize input data
	 *
	 * @param mixed $data Data to sanitize
	 * @param string $type Data type (string, email, url, int, array)
	 * @return mixed Sanitized data
	 */
	public function sanitize_input( $data, $type = 'string' ) {
		switch ( $type ) {
			case 'email':
				return sanitize_email( $data );
			case 'url':
				return esc_url_raw( $data );
			case 'int':
				return intval( $data );
			case 'array':
				return array_map( array( $this, 'sanitize_input' ), $data );
			case 'string':
			default:
				return sanitize_text_field( $data );
		}
	}

	/**
	 * Escape output data
	 *
	 * @param mixed  $data Data to escape
	 * @param string $type Escape type (html, attr, url, js)
	 * @return mixed Escaped data
	 */
	public function escape_output( $data, $type = 'html' ) {
		switch ( $type ) {
			case 'attr':
				return esc_attr( $data );
			case 'url':
				return esc_url( $data );
			case 'js':
				return wp_json_encode( $data );
			case 'html':
			default:
				return esc_html( $data );
		}
	}

	/**
	 * Verify nonce
	 *
	 * @param string $nonce Nonce to verify
	 * @param string $action Action name
	 * @return bool True if nonce is valid
	 */
	public function verify_nonce( $nonce, $action ) {
		if ( ! wp_verify_nonce( $nonce, $action ) ) {
			$this->logger->warning( 'Nonce verification failed', array( 'action' => $action ) );
			return false;
		}

		return true;
	}

	/**
	 * Create nonce
	 *
	 * @param string $action Action name
	 * @return string Nonce
	 */
	public function create_nonce( $action ) {
		return wp_create_nonce( $action );
	}

	/**
	 * Hash password
	 *
	 * @param string $password Password to hash
	 * @return string Hashed password
	 */
	public function hash_password( $password ) {
		return wp_hash_password( $password );
	}

	/**
	 * Verify password
	 *
	 * @param string $password Password to verify
	 * @param string $hash Password hash
	 * @return bool True if password matches
	 */
	public function verify_password( $password, $hash ) {
		return wp_check_password( $password, $hash );
	}

	/**
	 * Set custom rate limit
	 *
	 * @param string $action Action name
	 * @param int    $limit Rate limit
	 * @param int    $window Time window in seconds
	 * @return bool True on success
	 */
	public function set_rate_limit( $action, $limit, $window ) {
		$this->rate_limits[ $action ] = array(
			'limit' => $limit,
			'window' => $window,
		);

		$this->logger->info( 'Rate limit configured', array(
			'action' => $action,
			'limit' => $limit,
			'window' => $window,
		) );

		return true;
	}

	/**
	 * Get rate limit configuration
	 *
	 * @return array Rate limit configuration
	 */
	public function get_rate_limits() {
		return $this->rate_limits;
	}

	/**
	 * Log security event
	 *
	 * @param string $event Event name
	 * @param array  $details Event details
	 * @return void
	 */
	public function log_security_event( $event, $details = array() ) {
		$details['ip_address'] = $this->get_client_ip();
		$details['timestamp'] = current_time( 'mysql' );

		$this->logger->warning( 'Security event: ' . $event, $details );
	}
}
