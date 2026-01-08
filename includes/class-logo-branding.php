<?php
/**
 * Logo and Branding Manager
 *
 * Integrates LFCC logo throughout the plugin
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Logo_Branding {

	/**
	 * Get logo URL
	 *
	 * @return string
	 */
	public static function get_logo_url() {
		return '/wp-content/uploads/2025/12/lfcc-logo-transparent.png';
	}

	/**
	 * Get logo white URL
	 *
	 * @return string
	 */
	public static function get_logo_white_url() {
		return '/wp-content/uploads/2025/12/lfcc-logo-white.png';
	}

	/**
	 * Get favicon URL
	 *
	 * @return string
	 */
	public static function get_favicon_url() {
		return '/favicon.ico';
	}

	/**
	 * Output logo HTML
	 *
	 * @param string $variant 'transparent' or 'white'
	 * @param int    $width Width in pixels
	 * @return void
	 */
	public static function display_logo( $variant = 'transparent', $width = 200 ) {
		$url = ( 'white' === $variant ) ? self::get_logo_white_url() : self::get_logo_url();
		echo '<img src="' . esc_url( $url ) . '" alt="LFCC Logo" style="max-width: ' . intval( $width ) . 'px; height: auto;">';
	}

	/**
	 * Add favicon to frontend
	 *
	 * @return void
	 */
	public static function add_favicon() {
		echo '<link rel="icon" href="' . esc_url( self::get_favicon_url() ) . '" type="image/x-icon">' . "\n";
	}

	/**
	 * Initialize branding
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_head', array( __CLASS__, 'add_favicon' ) );
		add_action( 'admin_head', array( __CLASS__, 'add_favicon' ) );
		add_action( 'login_head', array( __CLASS__, 'add_favicon' ) );
	}
}

// Initialize on load
Leave_Manager_Logo_Branding::init();
