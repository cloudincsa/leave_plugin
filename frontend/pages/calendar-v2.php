<?php
/**
 * Frontend Calendar Page - Version 1.0.1
 * Modern Minimalist Design
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

<div class="leave-manager-calendar-v2">
	<div class="container">
		<!-- Calendar Section -->
		<div class="calendar-grid">
			<div class="calendar-main">
				<!-- Calendar Header -->
				<div class="calendar-header-v2">
					<a href="<?php echo esc_url( $prev_url ); ?>" class="btn btn-text calendar-nav-btn">
						<span class="icon">←</span>
						<span>Previous</span>
					</a>
					<h2 class="calendar-title"><?php echo esc_html( date( 'F Y', mktime( 0, 0, 0, $month, 1, $year ) ) ); ?></h2>
					<a href="<?php echo esc_url( $next_url ); ?>" class="btn btn-text calendar-nav-btn">
						<span>Next</span>
						<span class="icon">→</span>
					</a>
				</div>

				<!-- Calendar Widget -->
				<div class="calendar-widget">
					<?php echo wp_kses_post( $calendar->render_calendar( $month, $year, $current_user_id ) ); ?>
				</div>

				<!-- Legend -->
				<div class="calendar-legend-v2">
					<h4>Leave Types</h4>
					<div class="legend-grid">
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

			<!-- Sidebar -->
			<div class="calendar-sidebar-v2">
				<!-- Upcoming Leaves Card -->
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">Upcoming Leaves</h3>
					</div>
					<div class="card-body">
						<?php if ( ! empty( $upcoming ) ) : ?>
							<ul class="leaves-list-v2">
								<?php foreach ( $upcoming as $leave ) : ?>
									<li class="leave-item-v2">
										<div class="leave-info">
											<div class="leave-date-v2">
												<?php echo esc_html( date_i18n( 'M d', strtotime( $leave->start_date ) ) ); ?>
												–
												<?php echo esc_html( date_i18n( 'M d', strtotime( $leave->end_date ) ) ); ?>
											</div>
											<div class="leave-type-badge leave-<?php echo esc_attr( $leave->leave_type ); ?>">
												<?php echo esc_html( ucfirst( str_replace( '_', ' ', $leave->leave_type ) ) ); ?>
											</div>
										</div>
									</li>
								<?php endforeach; ?>
							</ul>
						<?php else : ?>
							<p class="text-muted">No upcoming leaves scheduled.</p>
						<?php endif; ?>
					</div>
				</div>

				<!-- Statistics Card -->
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">This Month's Statistics</h3>
					</div>
					<div class="card-body">
						<div class="stats-grid-v2">
							<div class="stat-card">
								<div class="stat-icon">📊</div>
								<div class="stat-number"><?php echo esc_html( $stats['total_leaves'] ); ?></div>
								<div class="stat-label">Total Leaves</div>
							</div>
							<div class="stat-card">
								<div class="stat-icon">📅</div>
								<div class="stat-number"><?php echo esc_html( $stats['annual_count'] ); ?></div>
								<div class="stat-label">Annual</div>
							</div>
							<div class="stat-card">
								<div class="stat-icon">🏥</div>
								<div class="stat-number"><?php echo esc_html( $stats['sick_count'] ); ?></div>
								<div class="stat-label">Sick</div>
							</div>
							<div class="stat-card">
								<div class="stat-icon">📌</div>
								<div class="stat-number"><?php echo esc_html( $stats['other_count'] ); ?></div>
								<div class="stat-label">Other</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Quick Actions Card -->
				<div class="card">
					<div class="card-header">
						<h3 class="card-title">Quick Actions</h3>
					</div>
					<div class="card-body">
						<div class="quick-actions-v2">
							<a href="<?php echo esc_url( add_query_arg( 'action', 'new_request' ) ); ?>" class="btn btn-primary btn-block">
								Request Leave
							</a>
							<a href="<?php echo esc_url( add_query_arg( 'action', 'view_balance' ) ); ?>" class="btn btn-secondary btn-block">
								View Balance
							</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
	/* Calendar Container */
	.leave-manager-calendar-v2 {
		padding: var(--spacing-3xl) 0;
		background-color: var(--color-gray-light);
	}

	.calendar-grid {
		display: grid;
		grid-template-columns: 1fr 350px;
		gap: var(--spacing-xl);
		margin-bottom: var(--spacing-3xl);
	}

	@media (max-width: 1024px) {
		.calendar-grid {
			grid-template-columns: 1fr;
		}
	}

	/* Calendar Header */
	.calendar-header-v2 {
		display: flex;
		align-items: center;
		justify-content: space-between;
		margin-bottom: var(--spacing-xl);
		padding-bottom: var(--spacing-lg);
		border-bottom: 2px solid var(--color-primary);
	}

	.calendar-title {
		font-size: var(--font-size-h2);
		font-weight: var(--font-weight-semibold);
		color: var(--color-gray-dark);
		margin: 0;
	}

	.calendar-nav-btn {
		display: flex;
		align-items: center;
		gap: var(--spacing-sm);
		padding: var(--spacing-sm) var(--spacing-md);
		color: var(--color-primary);
		font-weight: var(--font-weight-semibold);
	}

	.calendar-nav-btn:hover {
		color: var(--color-primary-dark);
	}

	.calendar-nav-btn .icon {
		font-size: 18px;
	}

	/* Calendar Widget */
	.calendar-widget {
		background-color: var(--color-white);
		border-radius: var(--radius-lg);
		padding: var(--spacing-xl);
		box-shadow: var(--shadow-sm);
		margin-bottom: var(--spacing-xl);
		overflow-x: auto;
	}

	/* Calendar Table Styles */
	.leave-manager-calendar {
		width: 100%;
		border-collapse: collapse;
	}

	.leave-manager-calendar thead {
		background-color: var(--color-gray-light);
	}

	.leave-manager-calendar th {
		padding: var(--spacing-md);
		text-align: center;
		font-weight: var(--font-weight-semibold);
		color: var(--color-gray-dark);
		border: 1px solid #e0e0e0;
		font-size: var(--font-size-small);
	}

	.leave-manager-calendar td {
		padding: var(--spacing-md);
		height: 100px;
		border: 1px solid #e0e0e0;
		vertical-align: top;
		position: relative;
		background-color: var(--color-white);
		transition: background-color var(--transition-fast);
	}

	.leave-manager-calendar td:hover {
		background-color: var(--color-gray-light);
	}

	.leave-manager-calendar td.empty {
		background-color: #fafafa;
	}

	.leave-manager-calendar td.leave-day {
		background-color: rgba(255, 193, 7, 0.1);
	}

	.leave-manager-calendar td.leave-day.leave-annual {
		background-color: rgba(255, 193, 7, 0.15);
	}

	.leave-manager-calendar td.leave-day.leave-sick {
		background-color: rgba(244, 67, 54, 0.1);
	}

	.leave-manager-calendar td.leave-day.leave-other {
		background-color: rgba(102, 126, 234, 0.1);
	}

	.day-number {
		display: block;
		font-weight: var(--font-weight-semibold);
		color: var(--color-gray-dark);
		margin-bottom: var(--spacing-xs);
		font-size: var(--font-size-body);
	}

	.leave-type {
		display: block;
		font-size: var(--font-size-xs);
		color: var(--color-gray-medium);
		font-weight: var(--font-weight-semibold);
	}

	/* Calendar Legend */
	.calendar-legend-v2 {
		background-color: var(--color-white);
		padding: var(--spacing-lg);
		border-radius: var(--radius-lg);
		box-shadow: var(--shadow-sm);
	}

	.calendar-legend-v2 h4 {
		margin-top: 0;
		margin-bottom: var(--spacing-md);
		color: var(--color-gray-dark);
		font-size: var(--font-size-body);
		font-weight: var(--font-weight-semibold);
	}

	.legend-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
		gap: var(--spacing-md);
	}

	.legend-item {
		display: flex;
		align-items: center;
		gap: var(--spacing-sm);
	}

	.legend-color {
		display: inline-block;
		width: 20px;
		height: 20px;
		border-radius: var(--radius-sm);
		border: 2px solid #e0e0e0;
	}

	.legend-color.leave-annual {
		background-color: #4A5FFF;
	}

	.legend-color.leave-sick {
		background-color: #f44336;
	}

	.legend-color.leave-other {
		background-color: #667eea;
	}

	.legend-label {
		font-size: var(--font-size-small);
		color: var(--color-gray-dark);
		font-weight: var(--font-weight-normal);
	}

	/* Sidebar */
	.calendar-sidebar-v2 {
		display: flex;
		flex-direction: column;
		gap: var(--spacing-xl);
	}

	/* Leaves List */
	.leaves-list-v2 {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.leave-item-v2 {
		padding: var(--spacing-md) 0;
		border-bottom: 1px solid var(--color-gray-light);
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.leave-item-v2:last-child {
		border-bottom: none;
	}

	.leave-info {
		flex: 1;
	}

	.leave-date-v2 {
		font-size: var(--font-size-small);
		color: var(--color-gray-dark);
		font-weight: var(--font-weight-semibold);
		margin-bottom: var(--spacing-xs);
	}

	.leave-type-badge {
		display: inline-block;
		padding: var(--spacing-xs) var(--spacing-sm);
		border-radius: var(--radius-sm);
		font-size: var(--font-size-xs);
		font-weight: var(--font-weight-semibold);
		color: white;
	}

	.leave-type-badge.leave-annual {
		background-color: #4A5FFF;
		color: var(--color-gray-dark);
	}

	.leave-type-badge.leave-sick {
		background-color: #f44336;
	}

	.leave-type-badge.leave-other {
		background-color: #667eea;
	}

	/* Statistics Grid */
	.stats-grid-v2 {
		display: grid;
		grid-template-columns: repeat(2, 1fr);
		gap: var(--spacing-md);
	}

	.stat-card {
		text-align: center;
		padding: var(--spacing-md);
		background-color: var(--color-gray-light);
		border-radius: var(--radius-md);
		transition: all var(--transition-fast);
	}

	.stat-card:hover {
		background-color: rgba(255, 193, 7, 0.1);
		transform: translateY(-2px);
	}

	.stat-icon {
		font-size: 24px;
		margin-bottom: var(--spacing-xs);
	}

	.stat-number {
		font-size: 28px;
		font-weight: var(--font-weight-bold);
		color: var(--color-primary);
		margin-bottom: var(--spacing-xs);
	}

	.stat-label {
		font-size: var(--font-size-small);
		color: var(--color-gray-medium);
		font-weight: var(--font-weight-normal);
	}

	/* Quick Actions */
	.quick-actions-v2 {
		display: flex;
		flex-direction: column;
		gap: var(--spacing-md);
	}

	.btn-block {
		width: 100%;
		text-align: center;
	}

	/* Responsive */
	@media (max-width: 768px) {
		.calendar-header-v2 {
			flex-direction: column;
			gap: var(--spacing-md);
			text-align: center;
		}

		.calendar-title {
			font-size: var(--font-size-h3);
		}

		.calendar-widget {
			padding: var(--spacing-lg);
		}

		.leave-manager-calendar td {
			height: auto;
			padding: var(--spacing-sm);
			font-size: var(--font-size-small);
		}

		.day-number {
			font-size: var(--font-size-body);
		}

		.leave-type {
			font-size: 10px;
		}

		.stats-grid-v2 {
			grid-template-columns: repeat(2, 1fr);
		}

		.legend-grid {
			grid-template-columns: 1fr;
		}
	}
</style>
