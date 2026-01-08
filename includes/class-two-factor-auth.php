<?php
/**
 * Two-Factor Authentication class for Leave Manager Plugin
 *
 * Handles two-factor authentication using TOTP (Time-based One-Time Password).
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Two_Factor_Auth class
 */
class Leave_Manager_Two_Factor_Auth {

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
	 * TOTP time step (30 seconds)
	 *
	 * @var int
	 */
	private $time_step = 30;

	/**
	 * TOTP digits
	 *
	 * @var int
	 */
	private $digits = 6;

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
	 * Generate a secret key for TOTP
	 *
	 * @return string Base32 encoded secret
	 */
	public function generate_secret() {
		$secret = bin2hex( random_bytes( 20 ) );
		return $this->base32_encode( hex2bin( $secret ) );
	}

	/**
	 * Enable 2FA for a user
	 *
	 * @param int    $user_id User ID
	 * @param string $secret Secret key
	 * @return bool True on success
	 */
	public function enable_2fa( $user_id, $secret ) {
		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_two_factor_auth';

		// Check if table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
			$this->create_2fa_table();
		}

		// Check if user already has 2FA enabled
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE user_id = %d",
			$user_id
		) );

		if ( $existing ) {
			$result = $wpdb->update(
				$table,
				array(
					'secret' => $secret,
					'enabled' => 1,
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'user_id' => $user_id ),
				array( '%s', '%d', '%s' ),
				array( '%d' )
			);
		} else {
			$result = $wpdb->insert(
				$table,
				array(
					'user_id' => $user_id,
					'secret' => $secret,
					'enabled' => 1,
				),
				array( '%d', '%s', '%d' )
			);
		}

		if ( $result ) {
			$this->logger->info( '2FA enabled for user', array( 'user_id' => $user_id ) );
		}

		return $result;
	}

	/**
	 * Disable 2FA for a user
	 *
	 * @param int $user_id User ID
	 * @return bool True on success
	 */
	public function disable_2fa( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_two_factor_auth';

		$result = $wpdb->update(
			$table,
			array( 'enabled' => 0 ),
			array( 'user_id' => $user_id ),
			array( '%d' ),
			array( '%d' )
		);

		if ( $result ) {
			$this->logger->info( '2FA disabled for user', array( 'user_id' => $user_id ) );
		}

		return $result;
	}

	/**
	 * Verify TOTP code
	 *
	 * @param int    $user_id User ID
	 * @param string $code TOTP code to verify
	 * @return bool True if code is valid
	 */
	public function verify_code( $user_id, $code ) {
		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_two_factor_auth';

		$auth = $wpdb->get_row( $wpdb->prepare(
			"SELECT secret, enabled FROM {$table} WHERE user_id = %d",
			$user_id
		) );

		if ( ! $auth || ! $auth->enabled ) {
			return false;
		}

		// Verify code (allow for time drift of ±1 time step)
		for ( $i = -1; $i <= 1; $i++ ) {
			$time = floor( time() / $this->time_step ) + $i;
			$expected_code = $this->generate_code( $auth->secret, $time );

			if ( $this->constant_time_compare( $code, $expected_code ) ) {
				$this->logger->info( '2FA code verified', array( 'user_id' => $user_id ) );
				return true;
			}
		}

		$this->logger->warning( '2FA code verification failed', array( 'user_id' => $user_id ) );
		return false;
	}

	/**
	 * Generate TOTP code
	 *
	 * @param string $secret Base32 encoded secret
	 * @param int    $time Time counter (optional)
	 * @return string TOTP code
	 */
	private function generate_code( $secret, $time = null ) {
		if ( null === $time ) {
			$time = floor( time() / $this->time_step );
		}

		$secret_bin = $this->base32_decode( $secret );
		$time_bin = pack( 'N', $time );

		$hmac = hash_hmac( 'sha1', $time_bin, $secret_bin, true );
		$offset = ord( substr( $hmac, -1 ) ) & 0x0f;
		$code = unpack( 'N', substr( $hmac, $offset, 4 ) )[1] & 0x7fffffff;

		return str_pad( $code % pow( 10, $this->digits ), $this->digits, '0', STR_PAD_LEFT );
	}

	/**
	 * Check if 2FA is enabled for user
	 *
	 * @param int $user_id User ID
	 * @return bool True if 2FA is enabled
	 */
	public function is_enabled( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_two_factor_auth';

		$result = $wpdb->get_var( $wpdb->prepare(
			"SELECT enabled FROM {$table} WHERE user_id = %d",
			$user_id
		) );

		return (bool) $result;
	}

	/**
	 * Get QR code URL for authenticator app
	 *
	 * @param int    $user_id User ID
	 * @param string $secret Secret key
	 * @param string $label Label for the authenticator app
	 * @return string QR code URL
	 */
	public function get_qr_code_url( $user_id, $secret, $label = 'Leave Manager' ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return '';
		}

		$account_name = $user->user_email;
		$issuer = $label;

		$otpauth_url = sprintf(
			'otpauth://totp/%s:%s?secret=%s&issuer=%s',
			rawurlencode( $issuer ),
			rawurlencode( $account_name ),
			$secret,
			rawurlencode( $issuer )
		);

		// Generate QR code using Google Charts API
		$qr_code_url = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . urlencode( $otpauth_url );

		return $qr_code_url;
	}

	/**
	 * Generate backup codes
	 *
	 * @param int $count Number of backup codes
	 * @return array Backup codes
	 */
	public function generate_backup_codes( $count = 10 ) {
		$codes = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$code = bin2hex( random_bytes( 4 ) );
			$codes[] = strtoupper( substr( $code, 0, 8 ) );
		}

		return $codes;
	}

	/**
	 * Base32 encode
	 *
	 * @param string $data Data to encode
	 * @return string Base32 encoded data
	 */
	private function base32_encode( $data ) {
		$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$output = '';
		$v = 0;
		$vbits = 0;

		for ( $i = 0; $i < strlen( $data ); $i++ ) {
			$v = ( $v << 8 ) | ord( $data[ $i ] );
			$vbits += 8;

			while ( $vbits >= 5 ) {
				$vbits -= 5;
				$output .= $alphabet[ ( $v >> $vbits ) & 31 ];
			}
		}

		if ( $vbits > 0 ) {
			$output .= $alphabet[ ( $v << ( 5 - $vbits ) ) & 31 ];
		}

		return $output;
	}

	/**
	 * Base32 decode
	 *
	 * @param string $data Base32 encoded data
	 * @return string Decoded data
	 */
	private function base32_decode( $data ) {
		$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
		$output = '';
		$v = 0;
		$vbits = 0;

		for ( $i = 0; $i < strlen( $data ); $i++ ) {
			$c = strpos( $alphabet, $data[ $i ] );
			if ( false === $c ) {
				continue;
			}

			$v = ( $v << 5 ) | $c;
			$vbits += 5;

			if ( $vbits >= 8 ) {
				$vbits -= 8;
				$output .= chr( ( $v >> $vbits ) & 255 );
			}
		}

		return $output;
	}

	/**
	 * Constant time string comparison
	 *
	 * @param string $a First string
	 * @param string $b Second string
	 * @return bool True if strings are equal
	 */
	private function constant_time_compare( $a, $b ) {
		if ( function_exists( 'hash_equals' ) ) {
			return hash_equals( $a, $b );
		}

		$result = 0;
		$len = min( strlen( $a ), strlen( $b ) );

		for ( $i = 0; $i < $len; $i++ ) {
			$result |= ord( $a[ $i ] ) ^ ord( $b[ $i ] );
		}

		return 0 === $result && strlen( $a ) === strlen( $b );
	}

	/**
	 * Create 2FA table
	 *
	 * @return void
	 */
	private function create_2fa_table() {
		global $wpdb;
		$table = $wpdb->prefix . 'leave_manager_two_factor_auth';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table (
			auth_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			secret VARCHAR(255) NOT NULL,
			enabled TINYINT(1) DEFAULT 0,
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (auth_id),
			UNIQUE KEY user_id (user_id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
