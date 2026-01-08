<?php
/**
 * Advanced Security Manager Class
 * Handles advanced security features including encryption, 2FA, and threat detection
 *
 * @package LeaveManager
 * @subpackage Security
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Advanced_Security_Manager {

	/**
	 * Security framework instance
	 *
	 * @var Leave_Manager_Security_Framework
	 */
	private $security_framework;

	/**
	 * Transaction manager instance
	 *
	 * @var Leave_Manager_Transaction_Manager
	 */
	private $transaction_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->security_framework = leave_manager_security();
		$this->transaction_manager = leave_manager_transaction();
	}

	/**
	 * Enable two-factor authentication for user
	 *
	 * @param int $user_id User ID
	 * @return array|WP_Error 2FA setup data or error
	 */
	public function enable_two_factor_auth( $user_id ) {
		// Generate secret key
		$secret = $this->generate_totp_secret();

		// Store secret (encrypted)
		$encrypted_secret = $this->encrypt_data( $secret );

		update_user_meta( $user_id, 'leave_manager_2fa_secret', $encrypted_secret );
		update_user_meta( $user_id, 'leave_manager_2fa_enabled', 1 );

		// Log audit event
		$this->security_framework->log_audit_event(
			'enable_two_factor_auth',
			'user',
			$user_id,
			array(),
			array( 'action' => 'enabled' )
		);

		return array(
			'secret' => $secret,
			'qr_code' => $this->generate_qr_code( $secret, $user_id ),
		);
	}

	/**
	 * Disable two-factor authentication for user
	 *
	 * @param int $user_id User ID
	 * @return bool
	 */
	public function disable_two_factor_auth( $user_id ) {
		delete_user_meta( $user_id, 'leave_manager_2fa_secret' );
		delete_user_meta( $user_id, 'leave_manager_2fa_enabled' );

		// Log audit event
		$this->security_framework->log_audit_event(
			'disable_two_factor_auth',
			'user',
			$user_id,
			array(),
			array( 'action' => 'disabled' )
		);

		return true;
	}

	/**
	 * Verify two-factor authentication code
	 *
	 * @param int    $user_id User ID
	 * @param string $code 2FA code
	 * @return bool|WP_Error
	 */
	public function verify_two_factor_code( $user_id, $code ) {
		$encrypted_secret = get_user_meta( $user_id, 'leave_manager_2fa_secret', true );

		if ( empty( $encrypted_secret ) ) {
			return new WP_Error( '2fa_not_enabled', '2FA not enabled for this user' );
		}

		$secret = $this->decrypt_data( $encrypted_secret );

		// Verify TOTP code
		$is_valid = $this->verify_totp_code( $secret, $code );

		if ( ! $is_valid ) {
			// Log failed attempt
			$this->security_framework->log_audit_event(
				'failed_2fa_verification',
				'user',
				$user_id,
				array(),
				array( 'reason' => 'invalid_code' )
			);

			return new WP_Error( 'invalid_code', 'Invalid 2FA code' );
		}

		return true;
	}

	/**
	 * Generate TOTP secret
	 *
	 * @return string Secret key
	 */
	private function generate_totp_secret() {
		$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$secret = '';

		for ( $i = 0; $i < 32; $i++ ) {
			$secret .= $characters[ wp_rand( 0, strlen( $characters ) - 1 ) ];
		}

		return $secret;
	}

	/**
	 * Verify TOTP code
	 *
	 * @param string $secret Secret key
	 * @param string $code Code to verify
	 * @return bool
	 */
	private function verify_totp_code( $secret, $code ) {
		$time = floor( time() / 30 );

		// Check current and previous time windows
		for ( $i = -1; $i <= 1; $i++ ) {
			$hash = hash_hmac( 'SHA1', pack( 'N*', 0, $time + $i ), $this->base32_decode( $secret ), true );
			$offset = ord( $hash[19] ) & 0xf;
			$totp = ( unpack( 'N', substr( $hash, $offset, 4 ) )[1] & 0x7fffffff ) % 1000000;

			if ( intval( $totp ) === intval( $code ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate QR code for 2FA
	 *
	 * @param string $secret Secret key
	 * @param int    $user_id User ID
	 * @return string QR code URL
	 */
	private function generate_qr_code( $secret, $user_id ) {
		$user = get_user_by( 'id', $user_id );
		$issuer = get_bloginfo( 'name' );

		$label = urlencode( $issuer . ':' . $user->user_email );
		$secret_encoded = urlencode( $secret );

		return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/{$label}?secret={$secret_encoded}&issuer={$issuer}";
	}

	/**
	 * Encrypt data
	 *
	 * @param string $data Data to encrypt
	 * @return string Encrypted data
	 */
	public function encrypt_data( $data ) {
		$key = wp_hash( 'leave_manager_encryption_key' );
		$iv = substr( wp_hash( 'leave_manager_encryption_iv' ), 0, 16 );

		return base64_encode( openssl_encrypt( $data, 'AES-256-CBC', $key, 0, $iv ) );
	}

	/**
	 * Decrypt data
	 *
	 * @param string $encrypted_data Encrypted data
	 * @return string Decrypted data
	 */
	public function decrypt_data( $encrypted_data ) {
		$key = wp_hash( 'leave_manager_encryption_key' );

		return openssl_decrypt( base64_decode( $encrypted_data ), 'AES-256-CBC', $key, 0 );
	}

	/**
	 * Base32 decode
	 *
	 * @param string $data Data to decode
	 * @return string Decoded data
	 */
	private function base32_decode( $data ) {
		$base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$bits = '';
		$bitstring = '';

		for ( $i = 0; $i < strlen( $data ); $i++ ) {
			$val = strpos( $base32chars, $data[ $i ] );
			$bits .= str_pad( base_convert( $val, 10, 2 ), 5, '0', STR_PAD_LEFT );
		}

		for ( $i = 0; $i + 8 <= strlen( $bits ); $i += 8 ) {
			$bitstring .= chr( base_convert( substr( $bits, $i, 8 ), 2, 10 ) );
		}

		return $bitstring;
	}

	/**
	 * Detect suspicious activity
	 *
	 * @param int $user_id User ID
	 * @return array|bool Suspicious activity or false
	 */
	public function detect_suspicious_activity( $user_id ) {
		global $wpdb;

		$user_ip = $this->get_user_ip();

		// Check for multiple failed login attempts
		$failed_attempts = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_audit_logs 
				WHERE user_id = %d AND action = 'failed_login' AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
				$user_id
			)
		);

		if ( $failed_attempts > 5 ) {
			return array(
				'type' => 'multiple_failed_logins',
				'severity' => 'high',
				'details' => "Multiple failed login attempts: {$failed_attempts}",
			);
		}

		// Check for unusual access time
		$last_login = get_user_meta( $user_id, 'leave_manager_last_login_time', true );
		if ( ! empty( $last_login ) ) {
			$time_diff = time() - strtotime( $last_login );

			if ( $time_diff < 60 ) { // Less than 1 minute since last login
				return array(
					'type' => 'rapid_successive_logins',
					'severity' => 'medium',
					'details' => 'Rapid successive login attempts detected',
				);
			}
		}

		// Check for IP change
		$last_ip = get_user_meta( $user_id, 'leave_manager_last_login_ip', true );
		if ( ! empty( $last_ip ) && $last_ip !== $user_ip ) {
			return array(
				'type' => 'ip_change',
				'severity' => 'low',
				'details' => "Login from new IP: {$user_ip}",
			);
		}

		return false;
	}

	/**
	 * Get user IP address
	 *
	 * @return string IP address
	 */
	private function get_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return sanitize_text_field( $ip );
	}

	/**
	 * Implement rate limiting
	 *
	 * @param string $identifier Identifier (e.g., user ID, IP)
	 * @param int    $max_attempts Maximum attempts
	 * @param int    $time_window Time window in seconds
	 * @return bool|WP_Error True if within limit, error if exceeded
	 */
	public function check_rate_limit( $identifier, $max_attempts = 5, $time_window = 3600 ) {
		$cache_key = 'rate_limit_' . md5( $identifier );
		$attempts = wp_cache_get( $cache_key );

		if ( false === $attempts ) {
			$attempts = 0;
		}

		if ( $attempts >= $max_attempts ) {
			return new WP_Error( 'rate_limit_exceeded', 'Too many attempts. Please try again later.' );
		}

		$attempts++;
		wp_cache_set( $cache_key, $attempts, 'leave_manager', $time_window );

		return true;
	}

	/**
	 * Generate security token
	 *
	 * @param int $length Token length
	 * @return string Security token
	 */
	public function generate_security_token( $length = 32 ) {
		return bin2hex( random_bytes( $length / 2 ) );
	}

	/**
	 * Validate security token
	 *
	 * @param string $token Token to validate
	 * @param string $stored_token Stored token
	 * @return bool
	 */
	public function validate_security_token( $token, $stored_token ) {
		return hash_equals( $token, $stored_token );
	}

	/**
	 * Implement CSRF protection
	 *
	 * @param string $action Action identifier
	 * @return string CSRF token
	 */
	public function generate_csrf_token( $action ) {
		$token = $this->generate_security_token();

		set_transient( 'leave_manager_csrf_' . $action, $token, HOUR_IN_SECONDS );

		return $token;
	}

	/**
	 * Verify CSRF token
	 *
	 * @param string $action Action identifier
	 * @param string $token Token to verify
	 * @return bool|WP_Error
	 */
	public function verify_csrf_token( $action, $token ) {
		$stored_token = get_transient( 'leave_manager_csrf_' . $action );

		if ( false === $stored_token ) {
			return new WP_Error( 'csrf_token_expired', 'CSRF token expired' );
		}

		if ( ! $this->validate_security_token( $token, $stored_token ) ) {
			return new WP_Error( 'invalid_csrf_token', 'Invalid CSRF token' );
		}

		// Delete token after verification
		delete_transient( 'leave_manager_csrf_' . $action );

		return true;
	}

	/**
	 * Sanitize user input
	 *
	 * @param mixed  $input Input to sanitize
	 * @param string $type Input type (text, email, url, etc.)
	 * @return mixed Sanitized input
	 */
	public function sanitize_input( $input, $type = 'text' ) {
		switch ( $type ) {
			case 'email':
				return sanitize_email( $input );

			case 'url':
				return esc_url( $input );

			case 'textarea':
				return sanitize_textarea_field( $input );

			case 'number':
				return intval( $input );

			case 'text':
			default:
				return sanitize_text_field( $input );
		}
	}

	/**
	 * Log security event
	 *
	 * @param string $event Event type
	 * @param int    $user_id User ID
	 * @param array  $details Event details
	 * @return bool
	 */
	public function log_security_event( $event, $user_id, $details = array() ) {
		return $this->security_framework->log_audit_event(
			'security_event_' . $event,
			'security',
			$user_id,
			array(),
			$details
		);
	}

	/**
	 * Get security audit log
	 *
	 * @param int $limit Number of records to retrieve
	 * @return array Audit log
	 */
	public function get_security_audit_log( $limit = 100 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_audit_logs 
				WHERE action LIKE %s 
				ORDER BY created_at DESC 
				LIMIT %d",
				'%security%',
				$limit
			)
		);
	}

	/**
	 * Generate security report
	 *
	 * @return array Security report
	 */
	public function generate_security_report() {
		global $wpdb;

		$report = array(
			'total_users' => count( get_users() ),
			'users_with_2fa' => intval(
				$wpdb->get_var(
					"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}usermeta 
					WHERE meta_key = 'leave_manager_2fa_enabled' AND meta_value = 1"
				)
			),
			'failed_login_attempts_24h' => intval(
				$wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_audit_logs 
					WHERE action = 'failed_login' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
				)
			),
			'security_events_24h' => intval(
				$wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_audit_logs 
					WHERE action LIKE '%security%' AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
				)
			),
		);

		return $report;
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_advanced_security' ) ) {
	/**
	 * Get advanced security manager instance
	 *
	 * @return Leave_Manager_Advanced_Security_Manager
	 */
	function leave_manager_advanced_security() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Advanced_Security_Manager();
		}

		return $instance;
	}
}
