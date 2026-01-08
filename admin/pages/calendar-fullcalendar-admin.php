<?php
/**
 * Admin/Manager Calendar - FullCalendar.js Integration
 * 
 * Displays all staff leave requests and public holidays
 * 
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get branding
$branding = new Leave_Manager_Branding();
$primary_color = $branding->get_setting( 'primary_color' );
$secondary_color = $branding->get_setting( 'primary_dark_color' );
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
			max-width: 1400px;
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

		.filters {
			display: flex;
			gap: 15px;
			margin-bottom: 20px;
			flex-wrap: wrap;
			padding-bottom: 20px;
			border-bottom: 1px solid #e0e0e0;
		}

		.filter-group {
			display: flex;
			gap: 10px;
			align-items: center;
		}

		.filter-group label {
			font-size: 13px;
			font-weight: 600;
			color: #666;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}

		.filter-group select {
			padding: 8px 12px;
			border: 1px solid #e0e0e0;
			border-radius: 6px;
			font-size: 13px;
			font-family: inherit;
		}

		.filter-group select:focus {
			outline: none;
			border-color: <?php echo esc_attr( $primary_color ); ?>;
			box-shadow: 0 0 0 3px rgba(74, 95, 255, 0.1);
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

		.fc-event.leave-event-pending {
			background-color: #FF9800 !important;
			border-color: #F57C00 !important;
		}

		.fc-event.leave-event-rejected {
			background-color: #f44336 !important;
			border-color: #d32f2f !important;
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

		.legend-color.leave-approved {
			background-color: <?php echo esc_attr( $primary_color ); ?>;
			border-color: <?php echo esc_attr( $secondary_color ); ?>;
		}

		.legend-color.leave-pending {
			background-color: #FF9800;
			border-color: #F57C00;
		}

		.legend-color.leave-rejected {
			background-color: #f44336;
			border-color: #d32f2f;
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
	</style>
</head>
<body>
	<div class="calendar-container">
		<div class="calendar-header">
			<h1>📅 Team Leave Calendar</h1>
			<p>View all staff leave requests and public holidays</p>
		</div>

		<div class="calendar-card">
			<div class="filters">
				<div class="filter-group">
					<label for="department-filter">Department:</label>
					<select id="department-filter">
						<option value="">All Departments</option>
					</select>
				</div>
				<div class="filter-group">
					<label for="staff-filter">Staff:</label>
					<select id="staff-filter">
						<option value="">All Staff</option>
					</select>
				</div>
				<div class="filter-group">
					<label for="status-filter">Status:</label>
					<select id="status-filter">
						<option value="">All Statuses</option>
						<option value="approved">Approved</option>
						<option value="pending">Pending</option>
						<option value="rejected">Rejected</option>
					</select>
				</div>
			</div>

			<div id="calendar"></div>

			<div class="legend">
				<div class="legend-item">
					<div class="legend-color leave-approved"></div>
					<span class="legend-text">Approved Leave</span>
				</div>
				<div class="legend-item">
					<div class="legend-color leave-pending"></div>
					<span class="legend-text">Pending Leave</span>
				</div>
				<div class="legend-item">
					<div class="legend-color leave-rejected"></div>
					<span class="legend-text">Rejected Leave</span>
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

			// Filter event listeners
			document.getElementById('department-filter').addEventListener('change', function() {
				window.leaveCalendar.removeAllEvents();
				const today = new Date();
				const start = new Date(today.getFullYear(), today.getMonth(), 1);
				const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
				fetchCalendarEvents(start, end);
			});

			document.getElementById('staff-filter').addEventListener('change', function() {
				window.leaveCalendar.removeAllEvents();
				const today = new Date();
				const start = new Date(today.getFullYear(), today.getMonth(), 1);
				const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
				fetchCalendarEvents(start, end);
			});

			document.getElementById('status-filter').addEventListener('change', function() {
				window.leaveCalendar.removeAllEvents();
				const today = new Date();
				const start = new Date(today.getFullYear(), today.getMonth(), 1);
				const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
				fetchCalendarEvents(start, end);
			});

			function fetchCalendarEvents(start, end) {
				const startDate = start.toISOString().split('T')[0];
				const endDate = end.toISOString().split('T')[0];
				const departmentFilter = document.getElementById('department-filter').value;
				const staffFilter = document.getElementById('staff-filter').value;
				const statusFilter = document.getElementById('status-filter').value;

				// Fetch leave requests
				fetch(ajaxurl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams({
						action: 'leave_manager_get_team_leave_events',
						nonce: '<?php echo wp_create_nonce( 'leave_manager_nonce' ); ?>',
						start: startDate,
						end: endDate,
						department: departmentFilter,
						staff: staffFilter,
						status: statusFilter
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
