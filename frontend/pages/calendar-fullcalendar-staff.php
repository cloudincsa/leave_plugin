<?php
/**
 * Staff Calendar - FullCalendar.js Integration
 * 
 * Displays staff's own leave requests and public holidays
 * 
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current user
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get branding
$branding = new Leave_Manager_Branding();
$primary_color = $branding->get_setting( 'primary_color' );
$secondary_color = $branding->get_setting( 'primary_dark_color' );

// Get user info from leave manager database
global $wpdb;
$users_table = $wpdb->prefix . 'leave_manager_leave_users';
$user_info = $wpdb->get_row( $wpdb->prepare(
	"SELECT * FROM {$users_table} WHERE id = %d",
	$user_id
) );

$user_name = $user_info ? $user_info->first_name . ' ' . $user_info->last_name : $current_user->display_name;
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset='utf-8' />
	<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
	<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
	<style>
		* {
			margin: 0;
			padding: 0;
			box-sizing: border-box;
		}

		body {
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			background: #f5f5f5;
			color: #333;
			padding: 20px;
		}

		.calendar-container {
			max-width: 1200px;
			margin: 0 auto;
		}

		.calendar-header {
			background: linear-gradient(135deg, <?php echo esc_attr( $primary_color ); ?> 0%, <?php echo esc_attr( $secondary_color ); ?> 100%);
			color: white;
			padding: 30px;
			border-radius: 12px;
			margin-bottom: 30px;
		}

		.calendar-header h1 {
			margin: 0 0 10px 0;
			font-size: 28px;
			font-weight: 700;
		}

		.calendar-header p {
			margin: 0;
			font-size: 14px;
			opacity: 0.9;
		}

		.calendar-card {
			background: white;
			padding: 30px;
			border-radius: 12px;
			box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
			margin-bottom: 30px;
		}

		#calendar {
			font-size: 14px;
		}

		.fc {
			font-family: inherit;
		}

		.fc .fc-button-primary {
			background-color: <?php echo esc_attr( $primary_color ); ?>;
			border-color: <?php echo esc_attr( $primary_color ); ?>;
		}

		.fc .fc-button-primary:hover {
			background-color: <?php echo esc_attr( $secondary_color ); ?>;
			border-color: <?php echo esc_attr( $secondary_color ); ?>;
		}

		.fc .fc-button-primary.fc-button-active {
			background-color: <?php echo esc_attr( $secondary_color ); ?>;
			border-color: <?php echo esc_attr( $secondary_color ); ?>;
		}

		.fc .fc-daygrid-day.fc-day-other {
			background-color: #fafafa;
		}

		.fc .fc-daygrid-day.fc-day-today {
			background-color: #e3f2fd;
		}

		.fc .fc-col-header-cell {
			background-color: #f5f5f5;
			font-weight: 600;
			color: #333;
		}

		/* Leave event styling */
		.fc-event.leave-event {
			background-color: <?php echo esc_attr( $primary_color ); ?> !important;
			border-color: <?php echo esc_attr( $secondary_color ); ?> !important;
		}

		.fc-event.leave-event .fc-event-title {
			font-weight: 600;
		}

		/* Holiday event styling */
		.fc-event.holiday-event {
			background-color: #FFC107 !important;
			border-color: #FF9800 !important;
			color: #333 !important;
		}

		.fc-event.holiday-event .fc-event-title {
			color: #333 !important;
			font-weight: 600;
		}

		.legend {
			display: flex;
			gap: 30px;
			margin-top: 30px;
			padding-top: 20px;
			border-top: 1px solid #e0e0e0;
			flex-wrap: wrap;
		}

		.legend-item {
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.legend-color {
			width: 24px;
			height: 24px;
			border-radius: 4px;
			border: 2px solid;
		}

		.legend-color.leave {
			background-color: <?php echo esc_attr( $primary_color ); ?>;
			border-color: <?php echo esc_attr( $secondary_color ); ?>;
		}

		.legend-color.holiday {
			background-color: #FFC107;
			border-color: #FF9800;
		}

		.legend-text {
			font-size: 13px;
			font-weight: 500;
			color: #666;
		}

		.loading {
			text-align: center;
			padding: 40px;
			color: #999;
		}

		.error-message {
			background-color: #ffebee;
			color: #c62828;
			padding: 15px;
			border-radius: 6px;
			margin-bottom: 20px;
			border-left: 4px solid #c62828;
		}

		.info-message {
			background-color: #e3f2fd;
			color: #1565c0;
			padding: 15px;
			border-radius: 6px;
			margin-bottom: 20px;
			border-left: 4px solid #1565c0;
		}
	</style>
</head>
<body>
	<div class="calendar-container">
		<div class="calendar-header">
			<h1>📅 My Leave Calendar</h1>
			<p>View your leave requests and public holidays</p>
		</div>

		<div class="calendar-card">
			<div id="calendar"></div>

			<div class="legend">
				<div class="legend-item">
					<div class="legend-color leave"></div>
					<span class="legend-text">My Leave</span>
				</div>
				<div class="legend-item">
					<div class="legend-color holiday"></div>
					<span class="legend-text">Public Holiday</span>
				</div>
			</div>
		</div>
	</div>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const calendarEl = document.getElementById('calendar');
			const calendar = new FullCalendar.Calendar(calendarEl, {
				initialView: 'dayGridMonth',
				headerToolbar: {
					left: 'prev,next today',
					center: 'title',
					right: 'dayGridMonth,timeGridWeek,listMonth'
				},
				height: 'auto',
				contentHeight: 'auto',
				editable: false,
				selectable: false,
				eventDisplay: 'block',
				eventTimeFormat: {
					hour: '2-digit',
					minute: '2-digit',
					meridiem: 'short'
				},
				datesSet: function(info) {
					// Fetch events when date range changes
					fetchCalendarEvents(info.start, info.end);
				},
				eventDidMount: function(info) {
					// Add tooltip on hover
					info.el.title = info.event.title;
				}
			});

			calendar.render();

			// Store calendar instance globally for event updates
			window.leaveCalendar = calendar;

			// Initial load
			const today = new Date();
			const start = new Date(today.getFullYear(), today.getMonth(), 1);
			const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
			fetchCalendarEvents(start, end);

			function fetchCalendarEvents(start, end) {
				const startDate = start.toISOString().split('T')[0];
				const endDate = end.toISOString().split('T')[0];

				// Fetch leave requests
				fetch(ajaxurl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'leave_manager_get_staff_leave_events',
						nonce: '<?php echo wp_create_nonce( 'leave_manager_nonce' ); ?>',
						start: startDate,
						end: endDate
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success && data.data.leave_events) {
						data.data.leave_events.forEach(event => {
							window.leaveCalendar.addEvent(event);
						});
					}
				})
				.catch(error => console.error('Error fetching leave events:', error));

				// Fetch public holidays
				fetch(ajaxurl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'leave_manager_get_public_holidays_events',
						nonce: '<?php echo wp_create_nonce( 'leave_manager_nonce' ); ?>',
						start: startDate,
						end: endDate
					})
				})
				.then(response => response.json())
				.then(data => {
					if (data.success && data.data.holiday_events) {
						data.data.holiday_events.forEach(event => {
							window.leaveCalendar.addEvent(event);
						});
					}
				})
				.catch(error => console.error('Error fetching holiday events:', error));
			}
		});
	</script>
</body>
</html>
