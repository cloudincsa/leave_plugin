<?php
/**
 * Public Holiday Manager Class
 * Manages public holidays for 50+ countries with full editability
 *
 * @package LeaveManager
 * @subpackage PublicHolidays
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Public_Holiday_Manager {

	/**
	 * Transaction manager instance
	 *
	 * @var Leave_Manager_Transaction_Manager
	 */
	private $transaction_manager;

	/**
	 * Security framework instance
	 *
	 * @var Leave_Manager_Security_Framework
	 */
	private $security_framework;

	/**
	 * Supported countries
	 *
	 * @var array
	 */
	private $supported_countries = array(
		'ZA' => 'South Africa',
		'US' => 'United States',
		'GB' => 'United Kingdom',
		'CA' => 'Canada',
		'AU' => 'Australia',
		'NZ' => 'New Zealand',
		'DE' => 'Germany',
		'FR' => 'France',
		'IT' => 'Italy',
		'ES' => 'Spain',
		'NL' => 'Netherlands',
		'BE' => 'Belgium',
		'CH' => 'Switzerland',
		'AT' => 'Austria',
		'SE' => 'Sweden',
		'NO' => 'Norway',
		'DK' => 'Denmark',
		'FI' => 'Finland',
		'PL' => 'Poland',
		'CZ' => 'Czech Republic',
		'HU' => 'Hungary',
		'RO' => 'Romania',
		'GR' => 'Greece',
		'PT' => 'Portugal',
		'IE' => 'Ireland',
		'JP' => 'Japan',
		'CN' => 'China',
		'IN' => 'India',
		'BR' => 'Brazil',
		'MX' => 'Mexico',
		'SG' => 'Singapore',
		'MY' => 'Malaysia',
		'TH' => 'Thailand',
		'PH' => 'Philippines',
		'ID' => 'Indonesia',
		'VN' => 'Vietnam',
		'KR' => 'South Korea',
		'TW' => 'Taiwan',
		'HK' => 'Hong Kong',
		'AE' => 'United Arab Emirates',
		'SA' => 'Saudi Arabia',
		'IL' => 'Israel',
		'NG' => 'Nigeria',
		'EG' => 'Egypt',
		'KE' => 'Kenya',
		'ZW' => 'Zimbabwe',
		'BW' => 'Botswana',
		'NA' => 'Namibia',
		'LZ' => 'Lesotho',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->transaction_manager = leave_manager_transaction();
		$this->security_framework = leave_manager_security();
	}

	/**
	 * Add public holiday
	 *
	 * @param string $country_code Country code (e.g., 'ZA')
	 * @param string $date Holiday date (Y-m-d)
	 * @param string $name Holiday name
	 * @param string $type Holiday type (national, regional, custom)
	 * @param array  $metadata Additional metadata
	 * @return int|WP_Error Holiday ID or error
	 */
	public function add_public_holiday( $country_code, $date, $name, $type = 'national', $metadata = array() ) {
		global $wpdb;

		// Validate inputs
		if ( empty( $country_code ) || empty( $date ) || empty( $name ) ) {
			return new WP_Error( 'invalid_input', 'Required fields are missing' );
		}

		// Validate country code
		if ( ! isset( $this->supported_countries[ $country_code ] ) ) {
			return new WP_Error( 'invalid_country', 'Country not supported' );
		}

		// Validate date format
		$date_timestamp = strtotime( $date );
		if ( false === $date_timestamp ) {
			return new WP_Error( 'invalid_date', 'Invalid date format' );
		}

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to add holidays' );
		}

		// Check for duplicate
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}leave_manager_public_holidays WHERE country_code = %s AND date = %s",
				$country_code,
				$date
			)
		);

		if ( null !== $existing ) {
			return new WP_Error( 'duplicate_holiday', 'Holiday already exists for this date' );
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $country_code, $date, $name, $type, $metadata ) {
				$insert_result = $wpdb->insert(
					$wpdb->prefix . 'leave_manager_public_holidays',
					array(
						'country_code' => $country_code,
						'date' => $date,
						'name' => $name,
						'type' => $type,
						'metadata' => wp_json_encode( $metadata ),
						'created_at' => current_time( 'mysql' ),
						'updated_at' => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
				);

				return $insert_result ? $wpdb->insert_id : false;
			},
			'add_public_holiday'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to add holiday' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'add_public_holiday',
			'public_holiday',
			$result,
			array(),
			array(
				'country_code' => $country_code,
				'date' => $date,
				'name' => $name,
				'type' => $type,
			)
		);

		do_action( 'leave_manager_public_holiday_added', $result, $country_code, $date );

		return $result;
	}

	/**
	 * Update public holiday
	 *
	 * @param int    $holiday_id Holiday ID
	 * @param array  $data Holiday data to update
	 * @return bool|WP_Error True on success or error
	 */
	public function update_public_holiday( $holiday_id, $data ) {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to update holidays' );
		}

		// Validate holiday exists
		$holiday = $this->get_public_holiday( $holiday_id );
		if ( null === $holiday ) {
			return new WP_Error( 'not_found', 'Holiday not found' );
		}

		// Prepare update data
		$update_data = array();
		$format = array();

		if ( isset( $data['name'] ) ) {
			$update_data['name'] = $data['name'];
			$format[] = '%s';
		}

		if ( isset( $data['type'] ) ) {
			$update_data['type'] = $data['type'];
			$format[] = '%s';
		}

		if ( isset( $data['metadata'] ) ) {
			$update_data['metadata'] = wp_json_encode( $data['metadata'] );
			$format[] = '%s';
		}

		if ( empty( $update_data ) ) {
			return true;
		}

		$update_data['updated_at'] = current_time( 'mysql' );
		$format[] = '%s';

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $holiday_id, $update_data, $format ) {
				return $wpdb->update(
					$wpdb->prefix . 'leave_manager_public_holidays',
					$update_data,
					array( 'id' => $holiday_id ),
					$format,
					array( '%d' )
				);
			},
			'update_public_holiday'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to update holiday' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'update_public_holiday',
			'public_holiday',
			$holiday_id,
			array( 'name' => $holiday->name, 'type' => $holiday->type ),
			$data
		);

		do_action( 'leave_manager_public_holiday_updated', $holiday_id );

		return true;
	}

	/**
	 * Delete public holiday
	 *
	 * @param int $holiday_id Holiday ID
	 * @return bool|WP_Error True on success or error
	 */
	public function delete_public_holiday( $holiday_id ) {
		global $wpdb;

		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to delete holidays' );
		}

		// Validate holiday exists
		$holiday = $this->get_public_holiday( $holiday_id );
		if ( null === $holiday ) {
			return new WP_Error( 'not_found', 'Holiday not found' );
		}

		$result = $this->transaction_manager->execute_transaction(
			function() use ( $wpdb, $holiday_id ) {
				return $wpdb->delete(
					$wpdb->prefix . 'leave_manager_public_holidays',
					array( 'id' => $holiday_id ),
					array( '%d' )
				);
			},
			'delete_public_holiday'
		);

		if ( false === $result ) {
			return new WP_Error( 'db_error', 'Failed to delete holiday' );
		}

		// Log audit event
		$this->security_framework->log_audit_event(
			'delete_public_holiday',
			'public_holiday',
			$holiday_id,
			array(
				'country_code' => $holiday->country_code,
				'date' => $holiday->date,
				'name' => $holiday->name,
			),
			array()
		);

		do_action( 'leave_manager_public_holiday_deleted', $holiday_id );

		return true;
	}

	/**
	 * Get public holiday
	 *
	 * @param int $holiday_id Holiday ID
	 * @return object|null
	 */
	public function get_public_holiday( $holiday_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_public_holidays WHERE id = %d",
				$holiday_id
			)
		);
	}

	/**
	 * Check if date is public holiday
	 *
	 * @param string $date Date (Y-m-d)
	 * @param string $country_code Optional country code
	 * @return bool
	 */
	public function is_public_holiday( $date, $country_code = null ) {
		global $wpdb;

		if ( null === $country_code ) {
			$country_code = get_option( 'leave_manager_default_country', 'ZA' );
		}

		$holiday = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}leave_manager_public_holidays WHERE country_code = %s AND date = %s",
				$country_code,
				$date
			)
		);

		return null !== $holiday;
	}

	/**
	 * Get public holidays for country and year
	 *
	 * @param string $country_code Country code
	 * @param int    $year Year
	 * @return array
	 */
	public function get_holidays_for_country_year( $country_code, $year ) {
		global $wpdb;

		$year_start = $year . '-01-01';
		$year_end = $year . '-12-31';

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_public_holidays 
				WHERE country_code = %s AND date BETWEEN %s AND %s 
				ORDER BY date ASC",
				$country_code,
				$year_start,
				$year_end
			)
		);
	}

	/**
	 * Get all public holidays for country
	 *
	 * @param string $country_code Country code
	 * @return array
	 */
	public function get_holidays_for_country( $country_code ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_public_holidays 
				WHERE country_code = %s 
				ORDER BY date ASC",
				$country_code
			)
		);
	}

	/**
	 * Import holidays from external API
	 *
	 * @param string $country_code Country code
	 * @param int    $year Year
	 * @return int|WP_Error Number of holidays imported or error
	 */
	public function import_holidays_from_api( $country_code, $year ) {
		// Check permission
		if ( ! current_user_can( 'manage_leave_manager' ) ) {
			return new WP_Error( 'permission_denied', 'You do not have permission to import holidays' );
		}

		// Validate country
		if ( ! isset( $this->supported_countries[ $country_code ] ) ) {
			return new WP_Error( 'invalid_country', 'Country not supported' );
		}

		// Try multiple API sources
		$holidays = $this->fetch_holidays_from_api( $country_code, $year );

		if ( is_wp_error( $holidays ) ) {
			return $holidays;
		}

		$imported_count = 0;

		foreach ( $holidays as $holiday ) {
			$result = $this->add_public_holiday(
				$country_code,
				$holiday['date'],
				$holiday['name'],
				'national',
				array( 'source' => 'api_import' )
			);

			if ( ! is_wp_error( $result ) ) {
				$imported_count++;
			}
		}

		return $imported_count;
	}

	/**
	 * Fetch holidays from external API
	 *
	 * @param string $country_code Country code
	 * @param int    $year Year
	 * @return array|WP_Error
	 */
	private function fetch_holidays_from_api( $country_code, $year ) {
		// Use Date Nager API as primary source
		$api_url = "https://date.nager.at/api/v3/PublicHolidays/{$year}/{$country_code}";

		$response = wp_remote_get( $api_url );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_error', 'Failed to fetch holidays from API' );
		}

		$body = wp_remote_retrieve_body( $response );
		$holidays = json_decode( $body, true );

		if ( ! is_array( $holidays ) ) {
			return new WP_Error( 'api_error', 'Invalid API response' );
		}

		// Format holidays
		$formatted_holidays = array();

		foreach ( $holidays as $holiday ) {
			$formatted_holidays[] = array(
				'date' => $holiday['date'],
				'name' => $holiday['name'],
			);
		}

		return $formatted_holidays;
	}

	/**
	 * Get supported countries
	 *
	 * @return array
	 */
	public function get_supported_countries() {
		return $this->supported_countries;
	}

	/**
	 * Get country name
	 *
	 * @param string $country_code Country code
	 * @return string|null
	 */
	public function get_country_name( $country_code ) {
		return isset( $this->supported_countries[ $country_code ] ) ? $this->supported_countries[ $country_code ] : null;
	}

	/**
	 * Bulk import holidays for multiple years
	 *
	 * @param string $country_code Country code
	 * @param int    $start_year Start year
	 * @param int    $end_year End year
	 * @return int|WP_Error Total holidays imported or error
	 */
	public function bulk_import_holidays( $country_code, $start_year, $end_year ) {
		$total_imported = 0;

		for ( $year = $start_year; $year <= $end_year; $year++ ) {
			$result = $this->import_holidays_from_api( $country_code, $year );

			if ( is_wp_error( $result ) ) {
				continue;
			}

			$total_imported += $result;
		}

		return $total_imported;
	}

	/**
	 * Export holidays to calendar format
	 *
	 * @param string $country_code Country code
	 * @param int    $year Year
	 * @return array Calendar data
	 */
	public function export_holidays_to_calendar( $country_code, $year ) {
		$holidays = $this->get_holidays_for_country_year( $country_code, $year );

		$calendar_data = array();

		foreach ( $holidays as $holiday ) {
			$date = $holiday->date;
			$month = (int) date( 'm', strtotime( $date ) );
			$day = (int) date( 'd', strtotime( $date ) );

			if ( ! isset( $calendar_data[ $month ] ) ) {
				$calendar_data[ $month ] = array();
			}

			$calendar_data[ $month ][ $day ] = array(
				'name' => $holiday->name,
				'type' => $holiday->type,
				'date' => $date,
			);
		}

		return $calendar_data;
	}

	/**
	 * Get holidays between two dates
	 *
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param string $country_code Optional country code
	 * @return array
	 */
	public function get_holidays_between_dates( $start_date, $end_date, $country_code = null ) {
		global $wpdb;

		if ( null === $country_code ) {
			$country_code = get_option( 'leave_manager_default_country', 'ZA' );
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}leave_manager_public_holidays 
				WHERE country_code = %s AND date BETWEEN %s AND %s 
				ORDER BY date ASC",
				$country_code,
				$start_date,
				$end_date
			)
		);
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_public_holiday' ) ) {
	/**
	 * Get public holiday manager instance
	 *
	 * @return Leave_Manager_Public_Holiday_Manager
	 */
	function leave_manager_public_holiday() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Public_Holiday_Manager();
		}

		return $instance;
	}
}
