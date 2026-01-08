<?php
/**
 * Frontend Calendar Page
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get instances
$db = new Leave_Manager_Database();
$logger = new Leave_Manager_Logger();
$calendar = new Leave_Manager_Calendar( $db, $logger );

// Get current user
$current_user_id = get_current_user_id();
if ( empty( $current_user_id ) ) {
	wp_die( 'You must be logged in to view this page.' );
}

// Get month and year from request
$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : intval( date( 'm' ) );
$year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : intval( date( 'Y' ) );

// Validate month and year
$month = max( 1, min( 12, $month ) );
$year = max( 2000, min( 2100, $year ) );

// Get calendar data
$events = $calendar->get_user_events( $current_user_id, $year );
$upcoming = $calendar->get_upcoming_leaves( 30, 5 );
$stats = $calendar->get_statistics( date( 'Y-m-01' ), date( 'Y-m-t' ) );

// Navigation URLs
$prev_month = $month - 1;
$prev_year = $year;
if ( $prev_month < 1 ) {
	$prev_month = 12;
	$prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ( $next_month > 12 ) {
	$next_month = 1;
	$next_year++;
}

$prev_url = add_query_arg( array( 'month' => $prev_month, 'year' => $prev_year ) );
$next_url = add_query_arg( array( 'month' => $next_month, 'year' => $next_year ) );
?>

<div class="leave-manager-calendar-container">
	<div class="calendar-wrapper">
		<div class="calendar-header">
			<a href="<?php echo esc_url( $prev_url ); ?>" class="nav-button prev">&laquo; Previous</a>
			<h2><?php echo esc_html( date( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) ); ?></h2>
			<a href="<?php echo esc_url( $next_url ); ?>" class="nav-button next">Next &raquo;</a>
		</div>

		<div class="calendar-content">
			<?php echo wp_kses_post( $calendar->render_calendar( $month, $year, $current_user_id ) ); ?>
		</div>

		<div class="calendar-legend">
			<h4>Leave Types</h4>
			<div class="legend-items">
				<div class="legend-item">
					<span class="legend-color leave-annual"></span>
					<span class="legend-label">Annual Leave</span>
				</div>
				<div class="legend-item">
					<span class="legend-color leave-sick"></span>
					<span class="legend-label">Sick Leave</span>
				</div>
				<div class="legend-item">
					<span class="legend-color leave-other"></span>
					<span class="legend-label">Other Leave</span>
				</div>
			</div>
		</div>
	</div>

	<div class="calendar-sidebar">
		<div class="sidebar-section upcoming-leaves">
			<h3>Upcoming Leaves</h3>
			<?php if ( ! empty( $upcoming ) ) : ?>
				<ul class="leaves-list">
					<?php foreach ( $upcoming as $leave ) : ?>
						<li class="leave-item">
							<div class="leave-date">
								<?php echo esc_html( date_i18n( 'M d', strtotime( $leave->start_date ) ) ); ?>
								-
								<?php echo esc_html( date_i18n( 'M d', strtotime( $leave->end_date ) ) ); ?>
							</div>
							<div class="leave-type-badge leave-<?php echo esc_attr( $leave->leave_type ); ?>">
								<?php echo esc_html( ucfirst( str_replace( '_', ' ', $leave->leave_type ) ) ); ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="no-data">No upcoming leaves scheduled.</p>
			<?php endif; ?>
		</div>

		<div class="sidebar-section leave-statistics">
			<h3>This Month's Statistics</h3>
			<div class="stats-grid">
				<div class="stat-item">
					<div class="stat-number"><?php echo esc_html( $stats['total_leaves'] ); ?></div>
					<div class="stat-label">Total Leaves</div>
				</div>
				<div class="stat-item">
					<div class="stat-number"><?php echo esc_html( $stats['annual_count'] ); ?></div>
					<div class="stat-label">Annual</div>
				</div>
				<div class="stat-item">
					<div class="stat-number"><?php echo esc_html( $stats['sick_count'] ); ?></div>
					<div class="stat-label">Sick</div>
				</div>
				<div class="stat-item">
					<div class="stat-number"><?php echo esc_html( $stats['other_count'] ); ?></div>
					<div class="stat-label">Other</div>
				</div>
			</div>
		</div>

		<div class="sidebar-section quick-actions">
			<h3>Quick Actions</h3>
			<a href="<?php echo esc_url( add_query_arg( 'action', 'new_request' ) ); ?>" class="button button-primary button-block">
				Request Leave
			</a>
			<a href="<?php echo esc_url( add_query_arg( 'action', 'view_balance' ) ); ?>" class="button button-secondary button-block">
				View Balance
			</a>
		</div>
	</div>
</div>

<style>
	.leave-manager-calendar-container {
		display: grid;
		grid-template-columns: 1fr 300px;
		gap: 20px;
		margin: 20px 0;
	}

	.calendar-wrapper {
		background: white;
		border: 1px solid #ddd;
		border-radius: 5px;
		padding: 20px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.calendar-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 20px;
		padding-bottom: 15px;
		border-bottom: 2px solid #0073aa;
	}

	.calendar-header h2 {
		margin: 0;
		color: #0073aa;
		font-size: 20px;
	}

	.nav-button {
		padding: 8px 15px;
		background-color: #0073aa;
		color: white;
		border: none;
		border-radius: 4px;
		cursor: pointer;
		text-decoration: none;
		font-size: 14px;
		transition: background-color 0.3s;
	}

	.nav-button:hover {
		background-color: #005a87;
	}

	.leave-manager-calendar {
		width: 100%;
	}

	.calendar-table {
		width: 100%;
		border-collapse: collapse;
		margin-bottom: 20px;
	}

	.calendar-table thead {
		background-color: #f5f5f5;
	}

	.calendar-table th {
		padding: 10px;
		text-align: center;
		font-weight: 600;
		color: #333;
		border: 1px solid #ddd;
	}

	.calendar-table td {
		padding: 10px;
		height: 100px;
		border: 1px solid #ddd;
		vertical-align: top;
		position: relative;
		background-color: #fafafa;
	}

	.calendar-table td.empty {
		background-color: #f0f0f0;
	}

	.calendar-table td.leave-day {
		background-color: #e8f4f8;
	}

	.calendar-table td.leave-day.leave-annual {
		background-color: #fff3cd;
	}

	.calendar-table td.leave-day.leave-sick {
		background-color: #f8d7da;
	}

	.calendar-table td.leave-day.leave-other {
		background-color: #d1ecf1;
	}

	.day-number {
		display: block;
		font-weight: 600;
		color: #333;
		margin-bottom: 5px;
	}

	.leave-type {
		display: block;
		font-size: 11px;
		color: #666;
		font-weight: 500;
	}

	.calendar-legend {
		background-color: #f9f9f9;
		padding: 15px;
		border-radius: 4px;
		border: 1px solid #ddd;
	}

	.calendar-legend h4 {
		margin-top: 0;
		color: #333;
		font-size: 14px;
	}

	.legend-items {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
		gap: 10px;
	}

	.legend-item {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.legend-color {
		display: inline-block;
		width: 20px;
		height: 20px;
		border-radius: 3px;
		border: 1px solid #ddd;
	}

	.legend-color.leave-annual {
		background-color: #fff3cd;
	}

	.legend-color.leave-sick {
		background-color: #f8d7da;
	}

	.legend-color.leave-other {
		background-color: #d1ecf1;
	}

	.legend-label {
		font-size: 12px;
		color: #666;
	}

	.calendar-sidebar {
		display: flex;
		flex-direction: column;
		gap: 20px;
	}

	.sidebar-section {
		background: white;
		border: 1px solid #ddd;
		border-radius: 5px;
		padding: 15px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.sidebar-section h3 {
		margin-top: 0;
		color: #0073aa;
		font-size: 14px;
		border-bottom: 2px solid #0073aa;
		padding-bottom: 10px;
	}

	.leaves-list {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.leave-item {
		padding: 10px;
		border-bottom: 1px solid #eee;
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.leave-item:last-child {
		border-bottom: none;
	}

	.leave-date {
		font-size: 12px;
		color: #666;
		font-weight: 500;
	}

	.leave-type-badge {
		display: inline-block;
		padding: 3px 8px;
		border-radius: 3px;
		font-size: 11px;
		font-weight: 600;
		color: white;
	}

	.leave-type-badge.leave-annual {
		background-color: #4A5FFF;
		color: #333;
	}

	.leave-type-badge.leave-sick {
		background-color: #dc3545;
	}

	.leave-type-badge.leave-other {
		background-color: #17a2b8;
	}

	.no-data {
		color: #666;
		font-size: 13px;
		margin: 10px 0;
	}

	.stats-grid {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: 10px;
	}

	.stat-item {
		text-align: center;
		padding: 10px;
		background-color: #f9f9f9;
		border-radius: 4px;
	}

	.stat-number {
		font-size: 24px;
		font-weight: 700;
		color: #0073aa;
	}

	.stat-label {
		font-size: 12px;
		color: #666;
		margin-top: 5px;
	}

	.quick-actions {
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	.button-block {
		width: 100%;
		text-align: center;
	}

	@media (max-width: 768px) {
		.leave-manager-calendar-container {
			grid-template-columns: 1fr;
		}

		.calendar-header {
			flex-direction: column;
			gap: 10px;
		}

		.calendar-table td {
			height: auto;
			padding: 8px 5px;
			font-size: 12px;
		}

		.day-number {
			font-size: 14px;
		}

		.leave-type {
			font-size: 10px;
		}
	}
</style>
