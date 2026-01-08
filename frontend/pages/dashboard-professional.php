<?php
/**
 * Professional Frontend Dashboard - ChatPanel Design
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$branding = new Leave_Manager_Branding();
$primary_color = $branding->get_setting( 'primary_color' );
$secondary_color = $branding->get_setting( 'primary_dark_color' );

// Get current user
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
?>

<style>
body {
	font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
	background: #f5f5f5;
	color: #333;
}

.leave-manager-container {
	max-width: 1200px;
	margin: 0 auto;
	padding: 20px;
}

.professional-header {
	background: linear-gradient(135deg, <?php echo esc_attr( $primary_color ); ?> 0%, <?php echo esc_attr( $secondary_color ); ?> 100%);
	color: white;
	padding: 40px;
	border-radius: 12px;
	margin-bottom: 30px;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.header-content h1 {
	margin: 0 0 10px 0;
	font-size: 32px;
	font-weight: 700;
}

.header-content p {
	margin: 0;
	font-size: 16px;
	opacity: 0.95;
}

.user-greeting {
	text-align: right;
	font-size: 14px;
}

.user-greeting .user-name {
	font-size: 18px;
	font-weight: 700;
	margin-bottom: 5px;
}

.stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
	margin-bottom: 30px;
}

.stat-card {
	background: white;
	padding: 25px;
	border-radius: 12px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
	border-left: 4px solid <?php echo esc_attr( $primary_color ); ?>;
}

.stat-card h3 {
	margin: 0 0 10px 0;
	font-size: 14px;
	color: #666;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.stat-card .stat-value {
	font-size: 32px;
	font-weight: 700;
	color: <?php echo esc_attr( $primary_color ); ?>;
	margin: 0;
}

.stat-card .stat-subtext {
	font-size: 12px;
	color: #999;
	margin-top: 10px;
}

.content-grid {
	display: grid;
	grid-template-columns: 2fr 1fr;
	gap: 30px;
	margin-bottom: 30px;
}

.content-card {
	background: white;
	padding: 30px;
	border-radius: 12px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.content-card h2 {
	margin: 0 0 20px 0;
	font-size: 20px;
	font-weight: 700;
	color: #333;
	padding-bottom: 15px;
	border-bottom: 2px solid #f0f0f0;
}

.request-item {
	padding: 15px 0;
	border-bottom: 1px solid #f0f0f0;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.request-item:last-child {
	border-bottom: none;
}

.request-info h4 {
	margin: 0 0 5px 0;
	font-size: 14px;
	font-weight: 600;
	color: #333;
}

.request-info p {
	margin: 0;
	font-size: 12px;
	color: #999;
}

.request-status {
	padding: 6px 12px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.status-pending {
	background: #fff3cd;
	color: #856404;
}

.status-approved {
	background: #d4edda;
	color: #155724;
}

.status-rejected {
	background: #f8d7da;
	color: #721c24;
}

.quick-actions {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.btn-action {
	padding: 12px 20px;
	background: linear-gradient(135deg, <?php echo esc_attr( $primary_color ); ?> 0%, <?php echo esc_attr( $secondary_color ); ?> 100%);
	color: white;
	border: none;
	border-radius: 8px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.3s ease;
	text-decoration: none;
	text-align: center;
	display: block;
	font-size: 14px;
}

.btn-action:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 12px rgba(74, 95, 255, 0.3);
}

.btn-secondary {
	background: #f0f0f0;
	color: #333;
}

.btn-secondary:hover {
	background: #e0e0e0;
}

.calendar-preview {
	background: #f8f9fa;
	padding: 20px;
	border-radius: 8px;
	margin-top: 15px;
}

.calendar-preview h4 {
	margin: 0 0 15px 0;
	color: #333;
	font-weight: 600;
	font-size: 13px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.calendar-grid {
	display: grid;
	grid-template-columns: repeat(7, 1fr);
	gap: 5px;
}

.calendar-day {
	aspect-ratio: 1;
	display: flex;
	align-items: center;
	justify-content: center;
	border-radius: 6px;
	font-size: 12px;
	font-weight: 600;
	background: white;
	border: 1px solid #e0e0e0;
	color: #666;
}

.calendar-day.today {
	background: <?php echo esc_attr( $primary_color ); ?>;
	color: white;
}

.calendar-day.leave {
	background: #f0f4ff;
	color: <?php echo esc_attr( $primary_color ); ?>;
}

.empty-state {
	text-align: center;
	padding: 40px 20px;
	color: #999;
}

.empty-state-icon {
	font-size: 48px;
	margin-bottom: 15px;
}

.empty-state h3 {
	margin: 0 0 10px 0;
	color: #666;
	font-size: 16px;
}

.empty-state p {
	margin: 0;
	font-size: 14px;
}

.info-banner {
	background: #f0f4ff;
	border-left: 4px solid <?php echo esc_attr( $primary_color ); ?>;
	padding: 20px;
	border-radius: 8px;
	margin-bottom: 20px;
	font-size: 14px;
	color: #333;
	line-height: 1.6;
}

.info-banner strong {
	color: <?php echo esc_attr( $primary_color ); ?>;
}

@media (max-width: 768px) {
	.content-grid {
		grid-template-columns: 1fr;
	}
	
	.professional-header {
		flex-direction: column;
		text-align: center;
	}
	
	.user-greeting {
		text-align: center;
		margin-top: 20px;
	}
	
	.stats-grid {
		grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
	}
}
</style>

<div class="leave-manager-container">
	<div class="professional-header">
		<div class="header-content">
			<h1>Welcome to Leave Manager</h1>
			<p>Manage your time off efficiently</p>
		</div>
		<div class="user-greeting">
			<div class="user-name"><?php echo esc_html( $current_user->first_name ?: $current_user->display_name ); ?></div>
			<div><?php echo esc_html( $current_user->user_email ); ?></div>
		</div>
	</div>

	<div class="stats-grid">
		<div class="stat-card">
			<h3>Leave Balance</h3>
			<p class="stat-value">15</p>
			<p class="stat-subtext">Days available</p>
		</div>
		<div class="stat-card">
			<h3>Used This Year</h3>
			<p class="stat-value">8</p>
			<p class="stat-subtext">Days taken</p>
		</div>
		<div class="stat-card">
			<h3>Pending Requests</h3>
			<p class="stat-value">2</p>
			<p class="stat-subtext">Awaiting approval</p>
		</div>
		<div class="stat-card">
			<h3>Approved Requests</h3>
			<p class="stat-value">5</p>
			<p class="stat-subtext">This year</p>
		</div>
	</div>

	<div class="content-grid">
		<div class="content-card">
			<h2>Recent Leave Requests</h2>
			
			<div class="request-item">
				<div class="request-info">
					<h4>Annual Leave</h4>
					<p>Dec 26, 2025 - Dec 28, 2025 (3 days)</p>
				</div>
				<span class="request-status status-approved">Approved</span>
			</div>

			<div class="request-item">
				<div class="request-info">
					<h4>Sick Leave</h4>
					<p>Jan 10, 2026 - Jan 10, 2026 (1 day)</p>
				</div>
				<span class="request-status status-pending">Pending</span>
			</div>

			<div class="request-item">
				<div class="request-info">
					<h4>Annual Leave</h4>
					<p>Jan 15, 2026 - Jan 18, 2026 (4 days)</p>
				</div>
				<span class="request-status status-approved">Approved</span>
			</div>

			<div style="text-align: center; margin-top: 20px;">
				<a href="#" class="btn-action btn-secondary" style="display: inline-block; width: auto;">View All Requests</a>
			</div>
		</div>

		<div class="content-card">
			<h2>Quick Actions</h2>
			<div class="quick-actions">
				<a href="#" class="btn-action">Request Leave</a>
				<a href="#" class="btn-action btn-secondary">View Calendar</a>
				<a href="#" class="btn-action btn-secondary">My Profile</a>
				<a href="#" class="btn-action btn-secondary">Help & Support</a>
			</div>

			<div class="calendar-preview">
				<h4>December 2025</h4>
				<div class="calendar-grid">
					<div class="calendar-day">S</div>
					<div class="calendar-day">M</div>
					<div class="calendar-day">T</div>
					<div class="calendar-day">W</div>
					<div class="calendar-day">T</div>
					<div class="calendar-day">F</div>
					<div class="calendar-day">S</div>
					
					<div class="calendar-day">1</div>
					<div class="calendar-day">2</div>
					<div class="calendar-day">3</div>
					<div class="calendar-day">4</div>
					<div class="calendar-day">5</div>
					<div class="calendar-day">6</div>
					<div class="calendar-day">7</div>
					
					<div class="calendar-day">8</div>
					<div class="calendar-day">9</div>
					<div class="calendar-day">10</div>
					<div class="calendar-day">11</div>
					<div class="calendar-day">12</div>
					<div class="calendar-day">13</div>
					<div class="calendar-day">14</div>
					
					<div class="calendar-day">15</div>
					<div class="calendar-day">16</div>
					<div class="calendar-day">17</div>
					<div class="calendar-day">18</div>
					<div class="calendar-day">19</div>
					<div class="calendar-day">20</div>
					<div class="calendar-day">21</div>
					
					<div class="calendar-day today">22</div>
					<div class="calendar-day leave">23</div>
					<div class="calendar-day leave">24</div>
					<div class="calendar-day leave">25</div>
					<div class="calendar-day leave">26</div>
					<div class="calendar-day leave">27</div>
					<div class="calendar-day leave">28</div>
					
					<div class="calendar-day">29</div>
					<div class="calendar-day">30</div>
					<div class="calendar-day">31</div>
				</div>
			</div>
		</div>
	</div>

	<div class="info-banner">
		<strong>ℹ️ Did you know?</strong> You can submit leave requests up to 30 days in advance. Make sure to check the leave calendar to avoid conflicts with team members.
	</div>
</div>
