<?php
/**
 * Holiday API Handler
 * 
 * Handles AJAX requests for Holiday API configuration and operations
 * 
 * @package Leave_Manager
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Holiday_API_Handler {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_leave_manager_save_holiday_api', array( $this, 'save_holiday_api_settings' ) );
		add_action( 'wp_ajax_leave_manager_test_holiday_api', array( $this, 'test_holiday_api_connection' ) );
		add_action( 'wp_ajax_leave_manager_sync_holidays_now', array( $this, 'sync_holidays_now' ) );
	}

	/**
	 * Save Holiday API Settings
	 */
	public function save_holiday_api_settings() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Get settings
		$settings = array(
			'enable_holiday_api'           => isset( $_POST['enable_holiday_api'] ) ? (bool) $_POST['enable_holiday_api'] : false,
			'holiday_api_provider'         => isset( $_POST['holiday_api_provider'] ) ? sanitize_text_field( $_POST['holiday_api_provider'] ) : '',
			'holiday_api_key'              => isset( $_POST['holiday_api_key'] ) ? sanitize_text_field( $_POST['holiday_api_key'] ) : '',
			'holiday_api_endpoint'         => isset( $_POST['holiday_api_endpoint'] ) ? esc_url_raw( $_POST['holiday_api_endpoint'] ) : '',
			'holiday_default_country'      => isset( $_POST['holiday_default_country'] ) ? sanitize_text_field( $_POST['holiday_default_country'] ) : '',
			'holiday_sync_frequency'       => isset( $_POST['holiday_sync_frequency'] ) ? sanitize_text_field( $_POST['holiday_sync_frequency'] ) : 'manual',
			'holidays_count_as_leave'      => isset( $_POST['holidays_count_as_leave'] ) ? (bool) $_POST['holidays_count_as_leave'] : false,
			'exclude_holidays_from_balance' => isset( $_POST['exclude_holidays_from_balance'] ) ? (bool) $_POST['exclude_holidays_from_balance'] : true,
			'allow_optional_holidays'      => isset( $_POST['allow_optional_holidays'] ) ? (bool) $_POST['allow_optional_holidays'] : true,
		);

		// Save to options
		update_option( 'leave_manager_holiday_api_settings', $settings );

		// Log the action
		do_action( 'leave_manager_log_action', 'Holiday API settings updated', $settings );

		wp_send_json_success( 'Holiday API settings saved successfully' );
	}

	/**
	 * Test Holiday API Connection
	 */
	public function test_holiday_api_connection() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Get settings
		$settings = get_option( 'leave_manager_holiday_api_settings', array() );

		if ( empty( $settings['enable_holiday_api'] ) ) {
			wp_send_json_error( 'Holiday API is not enabled' );
		}

		if ( empty( $settings['holiday_api_provider'] ) ) {
			wp_send_json_error( 'No API provider configured' );
		}

		if ( empty( $settings['holiday_api_key'] ) ) {
			wp_send_json_error( 'No API key configured' );
		}

		// Test connection based on provider
		$provider = $settings['holiday_api_provider'];
		$api_key  = $settings['holiday_api_key'];
		$country  = $settings['holiday_default_country'] ?? 'ZA';

		try {
			$response = $this->test_api_connection( $provider, $api_key, $country );
			
			if ( $response['success'] ) {
				wp_send_json_success( 'API connection successful! ' . $response['message'] );
			} else {
				wp_send_json_error( $response['message'] );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( 'Connection error: ' . $e->getMessage() );
		}
	}

	/**
	 * Sync Holidays Now
	 */
	public function sync_holidays_now() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_admin_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Get settings
		$settings = get_option( 'leave_manager_holiday_api_settings', array() );

		if ( empty( $settings['enable_holiday_api'] ) ) {
			wp_send_json_error( 'Holiday API is not enabled' );
		}

		if ( empty( $settings['holiday_api_provider'] ) ) {
			wp_send_json_error( 'No API provider configured' );
		}

		// Sync holidays
		try {
			$count = $this->sync_holidays_from_api( $settings );
			wp_send_json_success( $count );
		} catch ( Exception $e ) {
			wp_send_json_error( 'Sync error: ' . $e->getMessage() );
		}
	}

	/**
	 * Test API Connection
	 */
	private function test_api_connection( $provider, $api_key, $country ) {
		switch ( $provider ) {
			case 'calendarific':
				return $this->test_calendarific( $api_key, $country );
			case 'abstractapi':
				return $this->test_abstractapi( $api_key, $country );
			case 'holidays-api':
				return $this->test_holidays_api( $country );
			case 'custom':
				return $this->test_custom_endpoint( $api_key, $country );
			default:
				return array( 'success' => false, 'message' => 'Unknown provider' );
		}
	}

	/**
	 * Test Calendarific API
	 */
	private function test_calendarific( $api_key, $country ) {
		$url = 'https://calendarific.com/api/v2/holidays?api_key=' . urlencode( $api_key ) . '&country=' . urlencode( $country ) . '&year=' . date( 'Y' );
		
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['response']['holidays'] ) ) {
			return array( 'success' => true, 'message' => 'Connected successfully' );
		} else {
			return array( 'success' => false, 'message' => $body['response']['error'] ?? 'API error' );
		}
	}

	/**
	 * Test Abstract API
	 */
	private function test_abstractapi( $api_key, $country ) {
		$url = 'https://holidays.abstractapi.com/holidays?api_key=' . urlencode( $api_key ) . '&country=' . urlencode( $country ) . '&year=' . date( 'Y' );
		
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( is_array( $body ) && ! isset( $body['error'] ) ) {
			return array( 'success' => true, 'message' => 'Connected successfully' );
		} else {
			return array( 'success' => false, 'message' => $body['error']['message'] ?? 'API error' );
		}
	}

	/**
	 * Test Holidays API
	 */
	private function test_holidays_api( $country ) {
		$url = 'https://date.nager.at/api/v3/PublicHolidays/' . date( 'Y' ) . '/' . urlencode( $country );
		
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => $response->get_error_message() );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( is_array( $body ) && ! isset( $body['error'] ) ) {
			return array( 'success' => true, 'message' => 'Connected successfully' );
		} else {
			return array( 'success' => false, 'message' => 'API error' );
		}
	}

	/**
	 * Test Custom Endpoint
	 */
	private function test_custom_endpoint( $api_key, $country ) {
		$settings = get_option( 'leave_manager_holiday_api_settings', array() );
		$endpoint = $settings['holiday_api_endpoint'] ?? '';

		if ( empty( $endpoint ) ) {
			return array( 'success' => false, 'message' => 'Custom endpoint not configured' );
		}

		$response = wp_remote_get( $endpoint, array(
			'timeout' => 10,
			'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
		) );
		
		if ( is_wp_error( $response ) ) {
			return array( 'success' => false, 'message' => $response->get_error_message() );
		}

		return array( 'success' => true, 'message' => 'Connected successfully' );
	}

	/**
	 * Sync Holidays from API
	 */
	private function sync_holidays_from_api( $settings ) {
		global $wpdb;

		$provider = $settings['holiday_api_provider'];
		$country  = $settings['holiday_default_country'] ?? 'ZA';
		$year     = date( 'Y' );

		$holidays = $this->fetch_holidays_from_api( $provider, $settings, $country, $year );

		if ( empty( $holidays ) ) {
			return 0;
		}

		$count = 0;
		foreach ( $holidays as $holiday ) {
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}leave_manager_public_holidays WHERE holiday_date = %s AND country_code = %s",
				$holiday['date'],
				$country
			) );

			if ( ! $existing ) {
				$wpdb->insert(
					"{$wpdb->prefix}leave_manager_public_holidays",
					array(
						'country_code'   => $country,
						'holiday_name'   => $holiday['name'],
						'holiday_date'   => $holiday['date'],
						'holiday_year'   => $year,
						'is_recurring'   => 0,
						'is_optional'    => 0,
						'source'         => $provider,
						'created_by'     => get_current_user_id(),
						'created_at'     => current_time( 'mysql' ),
					)
				);
				$count++;
			}
		}

		return $count;
	}

	/**
	 * Fetch Holidays from API
	 */
	private function fetch_holidays_from_api( $provider, $settings, $country, $year ) {
		switch ( $provider ) {
			case 'calendarific':
				return $this->fetch_calendarific( $settings['holiday_api_key'], $country, $year );
			case 'abstractapi':
				return $this->fetch_abstractapi( $settings['holiday_api_key'], $country, $year );
			case 'holidays-api':
				return $this->fetch_holidays_api( $country, $year );
			case 'custom':
				return $this->fetch_custom_endpoint( $settings, $country, $year );
			default:
				return array();
		}
	}

	/**
	 * Fetch from Calendarific
	 */
	private function fetch_calendarific( $api_key, $country, $year ) {
		$url = 'https://calendarific.com/api/v2/holidays?api_key=' . urlencode( $api_key ) . '&country=' . urlencode( $country ) . '&year=' . $year;
		
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$holidays = array();

		if ( isset( $body['response']['holidays'] ) ) {
			foreach ( $body['response']['holidays'] as $holiday ) {
				$holidays[] = array(
					'name' => $holiday['name'],
					'date' => $holiday['date']['iso'],
				);
			}
		}

		return $holidays;
	}

	/**
	 * Fetch from Abstract API
	 */
	private function fetch_abstractapi( $api_key, $country, $year ) {
		$url = 'https://holidays.abstractapi.com/holidays?api_key=' . urlencode( $api_key ) . '&country=' . urlencode( $country ) . '&year=' . $year;
		
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$holidays = array();

		if ( is_array( $body ) ) {
			foreach ( $body as $holiday ) {
				$holidays[] = array(
					'name' => $holiday['name'],
					'date' => $holiday['date'],
				);
			}
		}

		return $holidays;
	}

	/**
	 * Fetch from Holidays API
	 */
	private function fetch_holidays_api( $country, $year ) {
		$url = 'https://date.nager.at/api/v3/PublicHolidays/' . $year . '/' . urlencode( $country );
		
		$response = wp_remote_get( $url, array( 'timeout' => 10 ) );
		
		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		$holidays = array();

		if ( is_array( $body ) ) {
			foreach ( $body as $holiday ) {
				$holidays[] = array(
					'name' => $holiday['name'],
					'date' => $holiday['date'],
				);
			}
		}

		return $holidays;
	}

	/**
	 * Fetch from Custom Endpoint
	 */
	private function fetch_custom_endpoint( $settings, $country, $year ) {
		$endpoint = $settings['holiday_api_endpoint'] ?? '';
		$api_key  = $settings['holiday_api_key'] ?? '';

		if ( empty( $endpoint ) ) {
			return array();
		}

		$response = wp_remote_get( $endpoint, array(
			'timeout' => 10,
			'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
		) );
		
		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Assume custom endpoint returns array of holidays with 'name' and 'date' keys
		return is_array( $body ) ? $body : array();
	}
}

// Initialize the handler
new Leave_Manager_Holiday_API_Handler();
