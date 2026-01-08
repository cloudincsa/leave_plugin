<?php
/**
 * FullCalendar Integration Class
 *
 * Manages FullCalendar.js integration for advanced calendar view
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_FullCalendar class
 */
class Leave_Manager_FullCalendar {

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
	 * Get calendar events for a user
	 *
	 * @param int    $user_id User ID
	 * @param string $start Start date (Y-m-d)
	 * @param string $end End date (Y-m-d)
	 * @return array Events array
	 */
	public function get_user_events( $user_id, $start = null, $end = null ) {
		global $wpdb;

		if ( ! $start ) {
			$start = date( 'Y-m-01' );
		}
		if ( ! $end ) {
			$end = date( 'Y-m-t' );
		}

		$requests_table = $this->db->requests_table;
		$results        = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, user_id, start_date, end_date, leave_type, status, reason
				FROM {$requests_table}
				WHERE user_id = %d AND start_date >= %s AND end_date <= %s
				ORDER BY start_date ASC",
				$user_id,
				$start,
				$end
			)
		);

		$events = array();

		foreach ( $results as $request ) {
			$events[] = array(
				'id'       => 'leave-' . $request->id,
				'title'    => ucfirst( str_replace( '_', ' ', $request->leave_type ) ),
				'start'    => $request->start_date,
				'end'      => date( 'Y-m-d', strtotime( $request->end_date . ' +1 day' ) ), // FullCalendar uses exclusive end date
				'extendedProps' => array(
					'type'   => $request->leave_type,
					'status' => $request->status,
					'reason' => $request->reason,
					'userId' => $request->user_id,
				),
				'backgroundColor' => $this->get_color_for_type( $request->leave_type ),
				'borderColor'     => $this->get_border_color_for_type( $request->leave_type ),
				'textColor'       => '#ffffff',
				'classNames'      => array( 'leave-event', 'leave-' . $request->status ),
			);
		}

		return $events;
	}

	/**
	 * Get public holidays
	 *
	 * @param string $start Start date (Y-m-d)
	 * @param string $end End date (Y-m-d)
	 * @return array Events array
	 */
	public function get_public_holidays( $start = null, $end = null ) {
		global $wpdb;

		if ( ! $start ) {
			$start = date( 'Y-m-01' );
		}
		if ( ! $end ) {
			$end = date( 'Y-m-t' );
		}

		$holidays_table = $wpdb->prefix . 'leave_manager_public_holidays';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, holiday_name, holiday_date, is_optional
				FROM {$holidays_table}
				WHERE holiday_date >= %s AND holiday_date <= %s
				ORDER BY holiday_date ASC",
				$start,
				$end
			)
		);

		$events = array();

		foreach ( $results as $holiday ) {
			$events[] = array(
				'id'       => 'holiday-' . $holiday->id,
				'title'    => '🏖️ ' . $holiday->holiday_name,
				'start'    => $holiday->holiday_date,
				'end'      => date( 'Y-m-d', strtotime( $holiday->holiday_date . ' +1 day' ) ),
				'extendedProps' => array(
					'type'       => 'public_holiday',
					'isOptional' => $holiday->is_optional,
					'holidayId'  => $holiday->id,
				),
				'backgroundColor' => '#FFC107',
				'borderColor'     => '#FF9800',
				'textColor'       => '#333333',
				'classNames'      => array( 'holiday-event', $holiday->is_optional ? 'holiday-optional' : 'holiday-mandatory' ),
			);
		}

		return $events;
	}

	/**
	 * Get all team events
	 *
	 * @param string $start Start date (Y-m-d)
	 * @param string $end End date (Y-m-d)
	 * @return array Events array
	 */
	public function get_team_events( $start = null, $end = null ) {
		global $wpdb;

		if ( ! $start ) {
			$start = date( 'Y-m-01' );
		}
		if ( ! $end ) {
			$end = date( 'Y-m-t' );
		}

		$requests_table = $this->db->requests_table;
		$users_table    = $this->db->users_table;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.id, r.user_id, r.start_date, r.end_date, r.leave_type, r.status, u.user_name
				FROM {$requests_table} r
				JOIN {$users_table} u ON r.user_id = u.id
				WHERE r.start_date >= %s AND r.end_date <= %s AND r.status = 'approved'
				ORDER BY r.start_date ASC",
				$start,
				$end
			)
		);

		$events = array();

		foreach ( $results as $request ) {
			$events[] = array(
				'id'       => 'leave-' . $request->id,
				'title'    => $request->user_name . ' - ' . ucfirst( str_replace( '_', ' ', $request->leave_type ) ),
				'start'    => $request->start_date,
				'end'      => date( 'Y-m-d', strtotime( $request->end_date . ' +1 day' ) ),
				'extendedProps' => array(
					'type'     => $request->leave_type,
					'status'   => $request->status,
					'userName' => $request->user_name,
					'userId'   => $request->user_id,
				),
				'backgroundColor' => $this->get_color_for_type( $request->leave_type ),
				'borderColor'     => $this->get_border_color_for_type( $request->leave_type ),
				'textColor'       => '#ffffff',
			);
		}

		return $events;
	}

	/**
	 * Get color for leave type
	 *
	 * @param string $type Leave type
	 * @return string Color hex code
	 */
	private function get_color_for_type( $type ) {
		$colors = array(
			'annual' => '#4A5FFF',
			'sick'   => '#f44336',
			'other'  => '#667eea',
		);

		return isset( $colors[ $type ] ) ? $colors[ $type ] : '#999999';
	}

	/**
	 * Get border color for leave type
	 *
	 * @param string $type Leave type
	 * @return string Color hex code
	 */
	private function get_border_color_for_type( $type ) {
		$colors = array(
			'annual' => '#ff9800',
			'sick'   => '#d32f2f',
			'other'  => '#5e35b1',
		);

		return isset( $colors[ $type ] ) ? $colors[ $type ] : '#666666';
	}

	/**
	 * Get FullCalendar configuration
	 *
	 * @return array Calendar configuration
	 */
	public static function get_calendar_config() {
		return array(
			'initialView'          => 'dayGridMonth',
			'headerToolbar'        => array(
				'left'   => 'prev,next today',
				'center' => 'title',
				'right'  => 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
			),
			'editable'             => false,
			'eventClick'           => true,
			'selectConstraint'     => 'businessHours',
			'eventDisplay'         => 'block',
			'eventTextColor'       => '#ffffff',
			'eventBorderColor'     => 'transparent',
			'dayMaxEventRows'      => 3,
			'moreLinkText'         => '+{0} more',
			'eventTimeFormat'      => array(
				'hour'     => '2-digit',
				'minute'   => '2-digit',
				'meridiem' => 'short',
			),
			'slotLabelFormat'      => array(
				'hour'     => '2-digit',
				'minute'   => '2-digit',
				'meridiem' => 'short',
			),
			'slotDuration'         => '00:30:00',
			'slotLabelInterval'    => '00:30',
			'scrollTime'           => '09:00:00',
			'nowIndicator'         => true,
			'weekends'             => true,
			'weekNumbers'          => true,
			'weekNumberCalculation' => 'ISO',
			'businessHours'        => array(
				array(
					'daysOfWeek' => array( 1, 2, 3, 4, 5 ),
					'startTime'  => '09:00',
					'endTime'    => '17:00',
				),
			),
			'contentHeight'        => 'auto',
			'height'               => 'auto',
		);
	}

	/**
	 * Get FullCalendar styling options
	 *
	 * @return array Styling options
	 */
	public static function get_calendar_styling() {
		return array(
			'dayCellClassNames' => 'fc-day-cell-custom',
			'eventClassNames'   => 'fc-event-custom',
			'slotLabelClassNames' => 'fc-slot-label-custom',
		);
	}

	/**
	 * Format events for FullCalendar JSON
	 *
	 * @param array $events Events array
	 * @return string JSON string
	 */
	public static function format_events_json( $events ) {
		return wp_json_encode( $events );
	}
}
