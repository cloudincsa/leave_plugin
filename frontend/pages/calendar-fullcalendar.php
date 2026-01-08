<?php
/**
 * Leave Calendar Page - FullCalendar Integration
 *
 * Displays an interactive calendar with leave requests using FullCalendar
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current user
$current_user = wp_get_current_user();
if ( ! $current_user->ID ) {
	echo '<div class="leave-manager-alert leave-manager-alert-warning">';
	echo '<p>You must be logged in to view your leave calendar.</p>';
	echo '</div>';
	return;
}

// Get database and logger instances
global $wpdb;
$db     = new Leave_Manager_Database();
$logger = new Leave_Manager_Logger( $db );

// Initialize FullCalendar class
$fullcalendar = new Leave_Manager_FullCalendar( $db, $logger );

// Get events for current user
$events = $fullcalendar->get_user_events( $current_user->ID );
$events_json = wp_json_encode( $events );

// Get calendar configuration
$calendar_config = Leave_Manager_FullCalendar::get_calendar_config();
$calendar_config_json = wp_json_encode( $calendar_config );

?>

<div class="leave-manager-page leave-manager-calendar-page">
	<div class="leave-manager-page-header">
		<h1>Leave Calendar</h1>
		<p class="leave-manager-page-subtitle">View your leave requests and manage your schedule</p>
	</div>

	<div class="leave-manager-calendar-container">
		<!-- Calendar -->
		<div class="leave-manager-calendar-wrapper">
			<div id="leave-manager-calendar" data-nonce="<?php echo wp_create_nonce( 'leave-manager-calendar' ); ?>"></div>
		</div>

		<!-- Sidebar -->
		<div class="leave-manager-calendar-sidebar">
			<!-- Legend -->
			<div class="leave-manager-calendar-legend">
				<h3>Leave Types</h3>
				<ul>
					<li>
						<span class="leave-manager-calendar-legend-color" style="background-color: #4A5FFF;"></span>
						<span>Annual Leave</span>
					</li>
					<li>
						<span class="leave-manager-calendar-legend-color" style="background-color: #f44336;"></span>
						<span>Sick Leave</span>
					</li>
					<li>
						<span class="leave-manager-calendar-legend-color" style="background-color: #667eea;"></span>
						<span>Other Leave</span>
					</li>
				</ul>
			</div>

			<!-- Status Legend -->
			<div class="leave-manager-calendar-status-legend">
				<h3>Status</h3>
				<ul>
					<li>
						<span class="leave-manager-status-badge leave-manager-status-approved">Approved</span>
					</li>
					<li>
						<span class="leave-manager-status-badge leave-manager-status-pending">Pending</span>
					</li>
					<li>
						<span class="leave-manager-status-badge leave-manager-status-rejected">Rejected</span>
					</li>
				</ul>
			</div>

			<!-- Quick Actions -->
			<div class="leave-manager-calendar-actions">
				<h3>Quick Actions</h3>
				<a href="<?php echo esc_url( get_permalink( get_page_by_path( 'leave-management/request' ) ) ); ?>" class="leave-manager-btn leave-manager-btn-primary leave-manager-btn-block">
					<span class="leave-manager-btn-icon">+</span>
					Request Leave
				</a>
				<a href="<?php echo esc_url( get_permalink( get_page_by_path( 'leave-management/balance' ) ) ); ?>" class="leave-manager-btn leave-manager-btn-secondary leave-manager-btn-block">
					View Balance
				</a>
			</div>

			<!-- Upcoming Leaves -->
			<div class="leave-manager-calendar-upcoming">
				<h3>Upcoming Leaves</h3>
				<div id="leave-manager-upcoming-leaves">
					<p class="leave-manager-text-muted">No upcoming leaves scheduled.</p>
				</div>
			</div>

			<!-- Statistics -->
			<div class="leave-manager-calendar-stats">
				<h3>This Month's Statistics</h3>
				<div class="leave-manager-stats-grid">
					<div class="leave-manager-stat">
						<div class="leave-manager-stat-label">Total Leaves</div>
						<div class="leave-manager-stat-value" id="stat-total-leaves">0</div>
					</div>
					<div class="leave-manager-stat">
						<div class="leave-manager-stat-label">Annual</div>
						<div class="leave-manager-stat-value" id="stat-annual-leaves">0</div>
					</div>
					<div class="leave-manager-stat">
						<div class="leave-manager-stat-label">Sick</div>
						<div class="leave-manager-stat-value" id="stat-sick-leaves">0</div>
					</div>
					<div class="leave-manager-stat">
						<div class="leave-manager-stat-label">Other</div>
						<div class="leave-manager-stat-value" id="stat-other-leaves">0</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
	.leave-manager-calendar-page {
		max-width: 1400px;
		margin: 0 auto;
		padding: 24px;
	}

	.leave-manager-page-header {
		margin-bottom: 32px;
	}

	.leave-manager-page-header h1 {
		font-size: 32px;
		font-weight: 700;
		color: #333333;
		margin: 0 0 8px 0;
	}

	.leave-manager-page-subtitle {
		font-size: 16px;
		color: #999999;
		margin: 0;
	}

	.leave-manager-calendar-container {
		display: grid;
		grid-template-columns: 1fr 320px;
		gap: 24px;
	}

	.leave-manager-calendar-wrapper {
		background: #ffffff;
		border: 1px solid #e0e0e0;
		border-radius: 8px;
		padding: 20px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
	}

	.leave-manager-calendar-sidebar {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	.leave-manager-calendar-legend,
	.leave-manager-calendar-status-legend,
	.leave-manager-calendar-actions,
	.leave-manager-calendar-upcoming,
	.leave-manager-calendar-stats {
		background: #ffffff;
		border: 1px solid #e0e0e0;
		border-radius: 8px;
		padding: 16px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
	}

	.leave-manager-calendar-legend h3,
	.leave-manager-calendar-status-legend h3,
	.leave-manager-calendar-actions h3,
	.leave-manager-calendar-upcoming h3,
	.leave-manager-calendar-stats h3 {
		font-size: 14px;
		font-weight: 600;
		color: #333333;
		margin: 0 0 12px 0;
		padding-bottom: 8px;
		border-bottom: 1px solid #e0e0e0;
	}

	.leave-manager-calendar-legend ul,
	.leave-manager-calendar-status-legend ul {
		list-style: none;
		margin: 0;
		padding: 0;
	}

	.leave-manager-calendar-legend li,
	.leave-manager-calendar-status-legend li {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 8px 0;
		font-size: 13px;
		color: #666666;
	}

	.leave-manager-calendar-legend-color {
		display: inline-block;
		width: 12px;
		height: 12px;
		border-radius: 2px;
	}

	.leave-manager-btn-block {
		width: 100%;
		text-align: center;
		display: block;
		margin-bottom: 8px;
	}

	.leave-manager-btn-icon {
		margin-right: 4px;
	}

	#leave-manager-upcoming-leaves {
		font-size: 13px;
		color: #666666;
	}

	.leave-manager-text-muted {
		color: #999999;
		margin: 0;
	}

	.leave-manager-stats-grid {
		display: grid;
		grid-template-columns: 1fr 1fr;
		gap: 12px;
	}

	.leave-manager-stat {
		text-align: center;
		padding: 12px;
		background: #f5f5f5;
		border-radius: 6px;
	}

	.leave-manager-stat-label {
		font-size: 11px;
		color: #999999;
		text-transform: uppercase;
		letter-spacing: 0.5px;
		margin-bottom: 4px;
	}

	.leave-manager-stat-value {
		font-size: 20px;
		font-weight: 700;
		color: #4A5FFF;
	}

	@media (max-width: 1024px) {
		.leave-manager-calendar-container {
			grid-template-columns: 1fr;
		}

		.leave-manager-calendar-sidebar {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 16px;
		}
	}

	@media (max-width: 768px) {
		.leave-manager-calendar-page {
			padding: 16px;
		}

		.leave-manager-page-header h1 {
			font-size: 24px;
		}

		.leave-manager-calendar-wrapper {
			padding: 12px;
		}

		.leave-manager-calendar-sidebar {
			grid-template-columns: 1fr;
		}

		.leave-manager-stats-grid {
			grid-template-columns: repeat(2, 1fr);
		}
	}

	@media (prefers-color-scheme: dark) {
		.leave-manager-calendar-page {
			background: #1a1a1a;
		}

		.leave-manager-page-header h1 {
			color: #e0e0e0;
		}

		.leave-manager-page-subtitle {
			color: #999999;
		}

		.leave-manager-calendar-wrapper,
		.leave-manager-calendar-legend,
		.leave-manager-calendar-status-legend,
		.leave-manager-calendar-actions,
		.leave-manager-calendar-upcoming,
		.leave-manager-calendar-stats {
			background: #2a2a2a;
			border-color: #444444;
		}

		.leave-manager-calendar-legend h3,
		.leave-manager-calendar-status-legend h3,
		.leave-manager-calendar-actions h3,
		.leave-manager-calendar-upcoming h3,
		.leave-manager-calendar-stats h3 {
			color: #e0e0e0;
			border-bottom-color: #444444;
		}

		.leave-manager-calendar-legend li,
		.leave-manager-calendar-status-legend li {
			color: #b0b0b0;
		}

		#leave-manager-upcoming-leaves {
			color: #b0b0b0;
		}

		.leave-manager-stat {
			background: #333333;
		}

		.leave-manager-stat-label {
			color: #999999;
		}

		.leave-manager-stat-value {
			color: #4A5FFF;
		}
	}
</style>

<script>
	// Initialize calendar with events
	document.addEventListener('DOMContentLoaded', function() {
		const events = <?php echo $events_json; ?>;
		const calendarEl = document.getElementById('leave-manager-calendar');
		
		if (calendarEl && window.FullCalendar) {
			const calendar = new FullCalendar.Calendar(calendarEl, {
				initialView: 'dayGridMonth',
				headerToolbar: {
					left: 'prev,next today',
					center: 'title',
					right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
				},
				editable: false,
				eventClick: function(info) {
					const event = info.event;
					const props = event.extendedProps;
					alert(
						'Leave Type: ' + props.type + '\n' +
						'Status: ' + props.status + '\n' +
						'Start: ' + event.start.toDateString() + '\n' +
						'End: ' + new Date(event.end.getTime() - 86400000).toDateString()
					);
				},
				events: events,
				eventDisplay: 'block',
				eventTextColor: '#ffffff',
				eventBorderColor: 'transparent',
				dayMaxEventRows: 3,
				moreLinkText: '+{0} more',
				nowIndicator: true,
				weekends: true,
				weekNumbers: true,
				contentHeight: 'auto',
				height: 'auto'
			});
			
			calendar.render();

			// Update statistics
			updateCalendarStats(events);
		}
	});

	// Update calendar statistics
	function updateCalendarStats(events) {
		const now = new Date();
		const currentMonth = now.getMonth();
		const currentYear = now.getFullYear();

		let totalLeaves = 0;
		let annualLeaves = 0;
		let sickLeaves = 0;
		let otherLeaves = 0;

		events.forEach(function(event) {
			const eventDate = new Date(event.start);
			if (eventDate.getMonth() === currentMonth && eventDate.getFullYear() === currentYear) {
				totalLeaves++;
				switch (event.extendedProps.type) {
					case 'annual':
						annualLeaves++;
						break;
					case 'sick':
						sickLeaves++;
						break;
					case 'other':
						otherLeaves++;
						break;
				}
			}
		});

		document.getElementById('stat-total-leaves').textContent = totalLeaves;
		document.getElementById('stat-annual-leaves').textContent = annualLeaves;
		document.getElementById('stat-sick-leaves').textContent = sickLeaves;
		document.getElementById('stat-other-leaves').textContent = otherLeaves;
	}
</script>
