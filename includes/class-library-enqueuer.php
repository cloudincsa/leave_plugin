<?php
/**
 * Library Enqueuer Class
 *
 * Manages enqueuing of third-party libraries (Chart.js, FullCalendar)
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Library_Enqueuer class
 */
class Leave_Manager_Library_Enqueuer {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_libraries' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_libraries' ) );
	}

	/**
	 * Enqueue frontend libraries
	 */
	public function enqueue_frontend_libraries() {
		// Enqueue Chart.js
		wp_enqueue_script(
			'leave-manager-chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			array(),
			'4.4.0',
			true
		);

		// Enqueue FullCalendar Core
		wp_enqueue_script(
			'leave-manager-fullcalendar-core',
			'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
			array(),
			'6.1.10',
			true
		);

		// Enqueue FullCalendar CSS
		wp_enqueue_style(
			'leave-manager-fullcalendar-css',
			'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css',
			array(),
			'6.1.10'
		);

		// Enqueue custom calendar styles
		wp_enqueue_style(
			'leave-manager-calendar-custom',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/css/calendar-custom.css',
			array( 'leave-manager-fullcalendar-css' ),
			LEAVE_MANAGER_PLUGIN_VERSION
		);

		// Enqueue custom chart styles
		wp_enqueue_style(
			'leave-manager-charts-custom',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/css/charts-custom.css',
			array(),
			LEAVE_MANAGER_PLUGIN_VERSION
		);

		// Enqueue custom scripts
		wp_enqueue_script(
			'leave-manager-calendar-script',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/js/calendar.js',
			array( 'leave-manager-fullcalendar-core' ),
			LEAVE_MANAGER_PLUGIN_VERSION,
			true
		);

		wp_enqueue_script(
			'leave-manager-charts-script',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/js/charts.js',
			array( 'leave-manager-chartjs' ),
			LEAVE_MANAGER_PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Enqueue admin libraries
	 */
	public function enqueue_admin_libraries() {
		// Enqueue Chart.js for admin dashboard
		wp_enqueue_script(
			'leave-manager-admin-chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
			array(),
			'4.4.0',
			true
		);

		// Enqueue admin chart styles
		wp_enqueue_style(
			'leave-manager-admin-charts-custom',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/css/admin-charts-custom.css',
			array(),
			LEAVE_MANAGER_PLUGIN_VERSION
		);

		// Enqueue admin chart script
		wp_enqueue_script(
			'leave-manager-admin-charts-script',
			LEAVE_MANAGER_PLUGIN_URL . 'assets/js/admin-charts.js',
			array( 'leave-manager-admin-chartjs' ),
			LEAVE_MANAGER_PLUGIN_VERSION,
			true
		);
	}
}
