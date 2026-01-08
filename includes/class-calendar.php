<?php
/**
 * Calendar class for Leave Manager Plugin
 *
 * Handles calendar display and leave visualization.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Calendar class
 */
class Leave_Manager_Calendar {

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
	 * Get leave events for a specific month
	 *
	 * @param int $year Year
	 * @param int $month Month
	 * @param int $user_id User ID (optional, for filtering)
	 * @return array Leave events
	 */
	public function get_month_events( $year, $month, $user_id = null ) {
		global $wpdb;

		$requests_table = $this->db->leave_requests_table;
		$users_table = $this->db->users_table;

		// Calculate month boundaries
		$start_date = date( 'Y-m-01', mktime( 0, 0, 0, $month, 1, $year ) );
		$end_date = date( 'Y-m-t', mktime( 0, 0, 0, $month, 1, $year ) );

		// Build query
		$query = "SELECT r.*, u.first_name, u.last_name, u.email
		          FROM $requests_table r
		          JOIN $users_table u ON r.user_id = u.user_id
		          WHERE r.status = 'approved'
		          AND (
		            (r.start_date <= %s AND r.end_date >= %s)
		            OR (r.start_date >= %s AND r.start_date <= %s)
		            OR (r.end_date >= %s AND r.end_date <= %s)
		          )";

		$values = array( $end_date, $start_date, $start_date, $end_date, $start_date, $end_date );

		if ( ! empty( $user_id ) ) {
			$query .= " AND r.user_id = %d";
			$values[] = intval( $user_id );
		}

		$query .= " ORDER BY r.start_date ASC";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $values ) );

		// Format results
		$events = array();
		foreach ( $results as $request ) {
			$events[] = array(
				'id' => $request->request_id,
				'title' => $request->first_name . ' ' . $request->last_name,
				'start' => $request->start_date,
				'end' => date( 'Y-m-d', strtotime( $request->end_date . ' +1 day' ) ),
				'type' => $request->leave_type,
				'user_id' => $request->user_id,
				'email' => $request->email,
				'className' => 'leave-' . $request->leave_type,
			);
		}

		return $events;
	}

	/**
	 * Get leave events for a date range
	 *
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @param int    $user_id User ID (optional)
	 * @return array Leave events
	 */
	public function get_range_events( $start_date, $end_date, $user_id = null ) {
		global $wpdb;

		$requests_table = $this->db->leave_requests_table;
		$users_table = $this->db->users_table;

		$query = "SELECT r.*, u.first_name, u.last_name, u.email
		          FROM $requests_table r
		          JOIN $users_table u ON r.user_id = u.user_id
		          WHERE r.status = 'approved'
		          AND (
		            (r.start_date <= %s AND r.end_date >= %s)
		            OR (r.start_date >= %s AND r.start_date <= %s)
		            OR (r.end_date >= %s AND r.end_date <= %s)
		          )";

		$values = array( $end_date, $start_date, $start_date, $end_date, $start_date, $end_date );

		if ( ! empty( $user_id ) ) {
			$query .= " AND r.user_id = %d";
			$values[] = intval( $user_id );
		}

		$query .= " ORDER BY r.start_date ASC";

		$results = $wpdb->get_results( $wpdb->prepare( $query, $values ) );

		$events = array();
		foreach ( $results as $request ) {
			$events[] = array(
				'id' => $request->request_id,
				'title' => $request->first_name . ' ' . $request->last_name,
				'start' => $request->start_date,
				'end' => date( 'Y-m-d', strtotime( $request->end_date . ' +1 day' ) ),
				'type' => $request->leave_type,
				'user_id' => $request->user_id,
				'email' => $request->email,
				'className' => 'leave-' . $request->leave_type,
			);
		}

		return $events;
	}

	/**
	 * Get leave events for a specific user
	 *
	 * @param int $user_id User ID
	 * @param int $year Year (optional)
	 * @return array Leave events
	 */
	public function get_user_events( $user_id, $year = null ) {
		global $wpdb;

		$requests_table = $this->db->leave_requests_table;

		if ( empty( $year ) ) {
			$year = intval( date( 'Y' ) );
		}

		$start_date = $year . '-01-01';
		$end_date = $year . '-12-31';

		$query = "SELECT *
		          FROM $requests_table
		          WHERE user_id = %d
		          AND status = 'approved'
		          AND start_date >= %s
		          AND end_date <= %s
		          ORDER BY start_date ASC";

		$results = $wpdb->get_results(
			$wpdb->prepare( $query, intval( $user_id ), $start_date, $end_date )
		);

		$events = array();
		foreach ( $results as $request ) {
			$events[] = array(
				'id' => $request->request_id,
				'title' => ucfirst( str_replace( '_', ' ', $request->leave_type ) ),
				'start' => $request->start_date,
				'end' => date( 'Y-m-d', strtotime( $request->end_date . ' +1 day' ) ),
				'type' => $request->leave_type,
				'className' => 'leave-' . $request->leave_type,
			);
		}

		return $events;
	}

	/**
	 * Get leave days count for a date range
	 *
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return int Number of leave days
	 */
	public function calculate_leave_days( $start_date, $end_date ) {
		$start = new DateTime( $start_date );
		$end = new DateTime( $end_date );
		$end->modify( '+1 day' ); // Include end date

		$days = 0;
		$interval = new DateInterval( 'P1D' );
		$period = new DatePeriod( $start, $interval, $end );

		foreach ( $period as $date ) {
			// Skip weekends (Saturday = 6, Sunday = 0)
			if ( $date->format( 'w' ) != 0 && $date->format( 'w' ) != 6 ) {
				$days++;
			}
		}

		return $days;
	}

	/**
	 * Check if date is a leave day for user
	 *
	 * @param int    $user_id User ID
	 * @param string $date Date (Y-m-d)
	 * @return bool True if user is on leave
	 */
	public function is_leave_day( $user_id, $date ) {
		global $wpdb;

		$requests_table = $this->db->leave_requests_table;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM $requests_table
				 WHERE user_id = %d
				 AND status = 'approved'
				 AND start_date <= %s
				 AND end_date >= %s",
				intval( $user_id ),
				$date,
				$date
			)
		);

		return intval( $result ) > 0;
	}

	/**
	 * Get leave statistics for a date range
	 *
	 * @param string $start_date Start date (Y-m-d)
	 * @param string $end_date End date (Y-m-d)
	 * @return array Statistics
	 */
	public function get_statistics( $start_date, $end_date ) {
		global $wpdb;

		$requests_table = $this->db->leave_requests_table;

		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
				 COUNT(*) as total_leaves,
				 COUNT(DISTINCT user_id) as employees_on_leave,
				 SUM(CASE WHEN leave_type = 'annual' THEN 1 ELSE 0 END) as annual_count,
				 SUM(CASE WHEN leave_type = 'sick' THEN 1 ELSE 0 END) as sick_count,
				 SUM(CASE WHEN leave_type = 'other' THEN 1 ELSE 0 END) as other_count
				 FROM $requests_table
				 WHERE status = 'approved'
				 AND (
				   (start_date <= %s AND end_date >= %s)
				   OR (start_date >= %s AND start_date <= %s)
				   OR (end_date >= %s AND end_date <= %s)
				 )",
				$end_date,
				$start_date,
				$start_date,
				$end_date,
				$start_date,
				$end_date
			)
		);

		return array(
			'total_leaves' => intval( $stats->total_leaves ),
			'employees_on_leave' => intval( $stats->employees_on_leave ),
			'annual_count' => intval( $stats->annual_count ),
			'sick_count' => intval( $stats->sick_count ),
			'other_count' => intval( $stats->other_count ),
		);
	}

	/**
	 * Get upcoming leaves
	 *
	 * @param int $days Number of days to look ahead
	 * @param int $limit Limit results
	 * @return array Upcoming leaves
	 */
	public function get_upcoming_leaves( $days = 30, $limit = 10 ) {
		global $wpdb;

		$requests_table = $this->db->leave_requests_table;
		$users_table = $this->db->users_table;

		$today = date( 'Y-m-d' );
		$future_date = date( 'Y-m-d', strtotime( "+{$days} days" ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, u.first_name, u.last_name, u.department
				 FROM $requests_table r
				 JOIN $users_table u ON r.user_id = u.user_id
				 WHERE r.status = 'approved'
				 AND r.start_date >= %s
				 AND r.start_date <= %s
				 ORDER BY r.start_date ASC
				 LIMIT %d",
				$today,
				$future_date,
				intval( $limit )
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Get department leave schedule
	 *
	 * @param string $department Department name
	 * @param int    $month Month
	 * @param int    $year Year
	 * @return array Department schedule
	 */
	public function get_department_schedule( $department, $month, $year ) {
		global $wpdb;

		$requests_table = $this->db->leave_requests_table;
		$users_table = $this->db->users_table;

		$start_date = date( 'Y-m-01', mktime( 0, 0, 0, $month, 1, $year ) );
		$end_date = date( 'Y-m-t', mktime( 0, 0, 0, $month, 1, $year ) );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, u.first_name, u.last_name, u.email
				 FROM $requests_table r
				 JOIN $users_table u ON r.user_id = u.user_id
				 WHERE u.department = %s
				 AND r.status = 'approved'
				 AND (
				   (r.start_date <= %s AND r.end_date >= %s)
				   OR (r.start_date >= %s AND r.start_date <= %s)
				   OR (r.end_date >= %s AND r.end_date <= %s)
				 )
				 ORDER BY r.start_date ASC",
				$department,
				$end_date,
				$start_date,
				$start_date,
				$end_date,
				$start_date,
				$end_date
			)
		);

		return $results ? $results : array();
	}

	/**
	 * Render calendar HTML
	 *
	 * @param int $month Month
	 * @param int $year Year
	 * @param int $user_id User ID (optional)
	 * @return string Calendar HTML
	 */
	public function render_calendar( $month = null, $year = null, $user_id = null ) {
		if ( empty( $month ) ) {
			$month = intval( date( 'm' ) );
		}
		if ( empty( $year ) ) {
			$year = intval( date( 'Y' ) );
		}

		$events = $this->get_month_events( $year, $month, $user_id );
		$first_day = date( 'w', mktime( 0, 0, 0, $month, 1, $year ) );
		$days_in_month = date( 't', mktime( 0, 0, 0, $month, 1, $year ) );

		$calendar = '<div class="leave-manager-calendar">';
		$calendar .= '<div class="calendar-header">';
		$calendar .= '<h3>' . date( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) . '</h3>';
		$calendar .= '</div>';

		$calendar .= '<table class="calendar-table">';
		$calendar .= '<thead>';
		$calendar .= '<tr>';
		$calendar .= '<th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>';
		$calendar .= '</tr>';
		$calendar .= '</thead>';

		$calendar .= '<tbody>';
		$day = 1;
		for ( $i = 0; $i < 6; $i++ ) {
			$calendar .= '<tr>';
			for ( $j = 0; $j < 7; $j++ ) {
				if ( $i === 0 && $j < $first_day ) {
					$calendar .= '<td class="empty"></td>';
				} elseif ( $day > $days_in_month ) {
					$calendar .= '<td class="empty"></td>';
				} else {
					$date = sprintf( '%04d-%02d-%02d', $year, $month, $day );
					$has_leave = false;

					foreach ( $events as $event ) {
						if ( $date >= $event['start'] && $date < $event['end'] ) {
							$has_leave = true;
							$calendar .= '<td class="leave-day ' . $event['className'] . '">';
							$calendar .= '<span class="day-number">' . $day . '</span>';
							$calendar .= '<span class="leave-type">' . ucfirst( $event['type'] ) . '</span>';
							$calendar .= '</td>';
							break;
						}
					}

					if ( ! $has_leave ) {
						$calendar .= '<td class="normal-day">';
						$calendar .= '<span class="day-number">' . $day . '</span>';
						$calendar .= '</td>';
					}

					$day++;
				}
			}
			$calendar .= '</tr>';

			if ( $day > $days_in_month ) {
				break;
			}
		}
		$calendar .= '</tbody>';
		$calendar .= '</table>';
		$calendar .= '</div>';

		return $calendar;
	}
}
