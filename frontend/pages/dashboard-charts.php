<?php
/**
 * Leave Dashboard Page - Chart.js Integration
 *
 * Displays dashboard with leave statistics and charts
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
	echo '<p>User information not found.</p>';
	echo '</div>';
	return;
}

// Get database and logger instances
global $wpdb;
$db     = new Leave_Manager_Database();
$logger = new Leave_Manager_Logger( $db );

// Initialize dashboard charts class
$charts = new Leave_Manager_Dashboard_Charts( $db, $logger );

// Get chart data
$leave_by_type = $charts->get_leave_by_type( 'month' );
$leave_over_time = $charts->get_leave_over_time( 'month' );
$leave_by_status = $charts->get_leave_by_status();
$monthly_stats = $charts->get_monthly_statistics();

// Get user's leave balance
$user_table = $db->users_table;
$user_data = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT annual_balance, sick_balance, other_balance FROM {$user_table} WHERE id = %d",
		$current_user->ID
	)
);

?>

<div class="leave-manager-page leave-manager-dashboard-page">
	<div class="leave-manager-page-header">
		<h1>Leave Dashboard</h1>
		<p class="leave-manager-page-subtitle">Overview of your leave requests and balance</p>
	</div>

	<!-- Statistics Cards -->
	<div class="leave-manager-stats-cards">
		<div class="leave-manager-stat-card">
			<div class="leave-manager-stat-card-icon" style="background-color: #4A5FFF;">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
					<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
					<polyline points="9 22 9 12 15 12 15 22"></polyline>
				</svg>
			</div>
			<div class="leave-manager-stat-card-content">
				<div class="leave-manager-stat-card-label">Annual Leave Balance</div>
				<div class="leave-manager-stat-card-value"><?php echo isset( $user_data->annual_balance ) ? number_format( $user_data->annual_balance, 1 ) : '0'; ?> days</div>
			</div>
		</div>

		<div class="leave-manager-stat-card">
			<div class="leave-manager-stat-card-icon" style="background-color: #f44336;">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
					<circle cx="12" cy="12" r="1"></circle>
					<path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m0 5.08l-4.24 4.24M20 4l-5.5 5.5m0 5l5.5 5.5M3.5 10.5h17M10.5 3.5v17"></path>
				</svg>
			</div>
			<div class="leave-manager-stat-card-content">
				<div class="leave-manager-stat-card-label">Sick Leave Balance</div>
				<div class="leave-manager-stat-card-value"><?php echo isset( $user_data->sick_balance ) ? number_format( $user_data->sick_balance, 1 ) : '0'; ?> days</div>
			</div>
		</div>

		<div class="leave-manager-stat-card">
			<div class="leave-manager-stat-card-icon" style="background-color: #667eea;">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
					<path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"></path>
					<path d="M12 6v6l4 2"></path>
				</svg>
			</div>
			<div class="leave-manager-stat-card-content">
				<div class="leave-manager-stat-card-label">Other Leave Balance</div>
				<div class="leave-manager-stat-card-value"><?php echo isset( $user_data->other_balance ) ? number_format( $user_data->other_balance, 1 ) : '0'; ?> days</div>
			</div>
		</div>

		<div class="leave-manager-stat-card">
			<div class="leave-manager-stat-card-icon" style="background-color: #4caf50;">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
					<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
					<polyline points="22 4 12 14.01 9 11.01"></polyline>
				</svg>
			</div>
			<div class="leave-manager-stat-card-content">
				<div class="leave-manager-stat-card-label">Total Balance</div>
				<div class="leave-manager-stat-card-value">
					<?php 
						$total = 0;
						if ( isset( $user_data->annual_balance ) ) $total += $user_data->annual_balance;
						if ( isset( $user_data->sick_balance ) ) $total += $user_data->sick_balance;
						if ( isset( $user_data->other_balance ) ) $total += $user_data->other_balance;
						echo number_format( $total, 1 );
					?> days
				</div>
			</div>
		</div>
	</div>

	<!-- Charts -->
	<div class="leave-manager-charts-grid">
		<!-- Leave by Type Chart -->
		<div class="leave-manager-chart-container leave-manager-chart-pie">
			<div class="leave-manager-chart-wrapper">
				<h3 class="leave-manager-chart-title">Leave Requests by Type</h3>
				<canvas id="chart-leave-by-type" 
					data-chart-type="doughnut" 
					data-chart-id="chart-leave-by-type"
					data-chart-data='<?php echo wp_json_encode( $leave_by_type ); ?>'></canvas>
			</div>
		</div>

		<!-- Leave by Status Chart -->
		<div class="leave-manager-chart-container leave-manager-chart-bar">
			<div class="leave-manager-chart-wrapper">
				<h3 class="leave-manager-chart-title">Leave Requests by Status</h3>
				<canvas id="chart-leave-by-status" 
					data-chart-type="bar" 
					data-chart-id="chart-leave-by-status"
					data-chart-data='<?php echo wp_json_encode( $leave_by_status ); ?>'></canvas>
			</div>
		</div>

		<!-- Leave Over Time Chart (Full Width) -->
		<div class="leave-manager-chart-container leave-manager-chart-line leave-manager-chart-full-width">
			<div class="leave-manager-chart-wrapper">
				<h3 class="leave-manager-chart-title">Leave Requests Over Time</h3>
				<p class="leave-manager-chart-description">Trend of leave requests this month</p>
				<canvas id="chart-leave-over-time" 
					data-chart-type="line" 
					data-chart-id="chart-leave-over-time"
					data-chart-data='<?php echo wp_json_encode( $leave_over_time ); ?>'></canvas>
			</div>
		</div>

		<!-- Monthly Statistics Chart (Full Width) -->
		<div class="leave-manager-chart-container leave-manager-chart-bar leave-manager-chart-full-width">
			<div class="leave-manager-chart-wrapper">
				<h3 class="leave-manager-chart-title">Monthly Leave Statistics</h3>
				<p class="leave-manager-chart-description">Leave requests by status for the current year</p>
				<canvas id="chart-monthly-stats" 
					data-chart-type="bar" 
					data-chart-id="chart-monthly-stats"
					data-chart-data='<?php echo wp_json_encode( $monthly_stats ); ?>'></canvas>
			</div>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="leave-manager-quick-actions">
		<h3>Quick Actions</h3>
		<div class="leave-manager-quick-actions-grid">
			<a href="<?php echo esc_url( get_permalink( get_page_by_path( 'leave-management/request' ) ) ); ?>" class="leave-manager-btn leave-manager-btn-primary">
				<span class="leave-manager-btn-icon">+</span>
				Request Leave
			</a>
			<a href="<?php echo esc_url( get_permalink( get_page_by_path( 'leave-management/calendar' ) ) ); ?>" class="leave-manager-btn leave-manager-btn-secondary">
				<span class="leave-manager-btn-icon">📅</span>
				View Calendar
			</a>
			<a href="<?php echo esc_url( get_permalink( get_page_by_path( 'leave-management/history' ) ) ); ?>" class="leave-manager-btn leave-manager-btn-secondary">
				<span class="leave-manager-btn-icon">📋</span>
				View History
			</a>
		</div>
	</div>
</div>

<style>
	.leave-manager-dashboard-page {
		max-width: 1200px;
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

	/* Statistics Cards */
	.leave-manager-stats-cards {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		gap: 16px;
		margin-bottom: 32px;
	}

	.leave-manager-stat-card {
		background: #ffffff;
		border: 1px solid #e0e0e0;
		border-radius: 8px;
		padding: 20px;
		display: flex;
		gap: 16px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
		transition: all 200ms ease-in-out;
	}

	.leave-manager-stat-card:hover {
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
		transform: translateY(-2px);
	}

	.leave-manager-stat-card-icon {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 56px;
		height: 56px;
		border-radius: 8px;
		flex-shrink: 0;
	}

	.leave-manager-stat-card-content {
		flex: 1;
	}

	.leave-manager-stat-card-label {
		font-size: 12px;
		color: #999999;
		text-transform: uppercase;
		letter-spacing: 0.5px;
		margin-bottom: 4px;
	}

	.leave-manager-stat-card-value {
		font-size: 24px;
		font-weight: 700;
		color: #333333;
	}

	/* Charts Grid */
	.leave-manager-charts-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
		gap: 20px;
		margin-bottom: 32px;
	}

	.leave-manager-chart-full-width {
		grid-column: 1 / -1;
	}

	.leave-manager-chart-wrapper {
		background: #ffffff;
		border: 1px solid #e0e0e0;
		border-radius: 8px;
		padding: 20px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
	}

	.leave-manager-chart-title {
		font-size: 16px;
		font-weight: 600;
		color: #333333;
		margin: 0 0 16px 0;
		padding-left: 12px;
		border-left: 4px solid #4A5FFF;
	}

	.leave-manager-chart-description {
		font-size: 13px;
		color: #999999;
		margin: 0 0 12px 0;
	}

	.leave-manager-chart-wrapper canvas {
		max-height: 300px;
	}

	/* Quick Actions */
	.leave-manager-quick-actions {
		background: #ffffff;
		border: 1px solid #e0e0e0;
		border-radius: 8px;
		padding: 20px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
	}

	.leave-manager-quick-actions h3 {
		font-size: 16px;
		font-weight: 600;
		color: #333333;
		margin: 0 0 16px 0;
	}

	.leave-manager-quick-actions-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
		gap: 12px;
	}

	.leave-manager-btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		gap: 8px;
		padding: 12px 16px;
		border-radius: 6px;
		font-size: 14px;
		font-weight: 500;
		text-decoration: none;
		transition: all 200ms ease-in-out;
		border: none;
		cursor: pointer;
	}

	.leave-manager-btn-primary {
		background-color: #4A5FFF;
		color: #ffffff;
	}

	.leave-manager-btn-primary:hover {
		background-color: #ff9800;
		box-shadow: 0 4px 8px rgba(255, 152, 0, 0.2);
	}

	.leave-manager-btn-secondary {
		background-color: #f5f5f5;
		color: #333333;
		border: 1px solid #e0e0e0;
	}

	.leave-manager-btn-secondary:hover {
		background-color: #e0e0e0;
		border-color: #bdbdbd;
	}

	.leave-manager-btn-icon {
		font-size: 16px;
	}

	/* Responsive Design */
	@media (max-width: 1024px) {
		.leave-manager-charts-grid {
			grid-template-columns: 1fr;
		}

		.leave-manager-chart-full-width {
			grid-column: 1;
		}
	}

	@media (max-width: 768px) {
		.leave-manager-dashboard-page {
			padding: 16px;
		}

		.leave-manager-page-header h1 {
			font-size: 24px;
		}

		.leave-manager-stats-cards {
			grid-template-columns: 1fr;
			gap: 12px;
		}

		.leave-manager-stat-card {
			padding: 16px;
		}

		.leave-manager-quick-actions-grid {
			grid-template-columns: 1fr;
		}
	}

	/* Dark Mode Support */
	@media (prefers-color-scheme: dark) {
		.leave-manager-dashboard-page {
			background: #1a1a1a;
		}

		.leave-manager-page-header h1 {
			color: #e0e0e0;
		}

		.leave-manager-page-subtitle {
			color: #999999;
		}

		.leave-manager-stat-card,
		.leave-manager-chart-wrapper,
		.leave-manager-quick-actions {
			background: #2a2a2a;
			border-color: #444444;
		}

		.leave-manager-stat-card-label {
			color: #999999;
		}

		.leave-manager-stat-card-value {
			color: #e0e0e0;
		}

		.leave-manager-chart-title {
			color: #e0e0e0;
		}

		.leave-manager-chart-description {
			color: #999999;
		}

		.leave-manager-quick-actions h3 {
			color: #e0e0e0;
		}

		.leave-manager-btn-secondary {
			background-color: #333333;
			color: #e0e0e0;
			border-color: #444444;
		}

		.leave-manager-btn-secondary:hover {
			background-color: #444444;
			border-color: #555555;
		}
	}
</style>
