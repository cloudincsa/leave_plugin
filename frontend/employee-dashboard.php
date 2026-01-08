<?php
/**
 * Frontend Employee Dashboard - Shows user-specific leave data
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure user is logged in
if ( ! is_user_logged_in() ) {
	wp_redirect( wp_login_url( get_permalink() ) );
	exit;
}

$current_user_id = get_current_user_id();
$user = get_user_by( 'id', $current_user_id );

// Get user's leave requests
global $wpdb;
$leave_requests = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests WHERE user_id = %d ORDER BY created_at DESC LIMIT 10",
		$current_user_id
	)
);

// Calculate leave balance
$total_leave_days = 20;
$used_leave_days = 0;
$pending_leave_days = 0;

if ( $leave_requests ) {
	foreach ( $leave_requests as $request ) {
		$start = strtotime( $request->start_date );
		$end = strtotime( $request->end_date );
		$days = ceil( ( $end - $start ) / 86400 ) + 1;

		if ( 'approved' === $request->status ) {
			$used_leave_days += $days;
		} elseif ( 'pending' === $request->status ) {
			$pending_leave_days += $days;
		}
	}
}

$remaining_leave_days = $total_leave_days - $used_leave_days;

// Enqueue CSS
wp_enqueue_style( 'leave-manager-professional', plugin_dir_url( __DIR__ ) . 'assets/css/professional.css' );
wp_enqueue_style( 'leave-manager-frontend', plugin_dir_url( __DIR__ ) . 'assets/css/frontend.css' );

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?php echo esc_html( $user->first_name . ' ' . $user->last_name ); ?> - Leave Manager Dashboard</title>
	<?php wp_head(); ?>
	<style>
		body {
			background-color: #f9fafb;
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
		}
		.lm-employee-dashboard {
			max-width: 1200px;
			margin: 0 auto;
			padding: 20px;
		}
		.lm-header-bar {
			background: white;
			padding: 16px 20px;
			border-bottom: 1px solid #e5e7eb;
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20px;
			border-radius: 8px;
		}
		.lm-header-bar h1 {
			margin: 0;
			font-size: 24px;
			color: #1f2937;
		}
		.lm-logout-btn {
			background: #667eea;
			color: white;
			padding: 8px 16px;
			border: none;
			border-radius: 4px;
			cursor: pointer;
			text-decoration: none;
			font-size: 14px;
		}
		.lm-logout-btn:hover {
			background: #5568d3;
		}
		.lm-grid-2 {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 20px;
			margin-bottom: 20px;
		}
		.lm-grid-4 {
			display: grid;
			grid-template-columns: repeat(4, 1fr);
			gap: 16px;
			margin-bottom: 20px;
		}
		@media (max-width: 768px) {
			.lm-grid-2 {
				grid-template-columns: 1fr;
			}
			.lm-grid-4 {
				grid-template-columns: repeat(2, 1fr);
			}
		}
		.lm-card {
			background: white;
			border-radius: 8px;
			box-shadow: 0 2px 8px rgba(0,0,0,0.08);
			overflow: hidden;
		}
		.lm-card-header {
			background: white;
			padding: 16px 20px;
			border-bottom: 1px solid #e5e7eb;
		}
		.lm-card-header h2 {
			margin: 0;
			font-size: 16px;
			font-weight: 600;
			color: #1f2937;
		}
		.lm-card-body {
			padding: 20px;
		}
		.lm-stat-card {
			background: white;
			border-radius: 8px;
			padding: 20px;
			border-left: 5px solid #667eea;
			box-shadow: 0 2px 8px rgba(0,0,0,0.08);
		}
		.lm-stat-label {
			font-size: 12px;
			color: #6b7280;
			text-transform: uppercase;
			font-weight: 600;
			margin-bottom: 8px;
		}
		.lm-stat-value {
			font-size: 32px;
			font-weight: 700;
			color: #667eea;
			margin-bottom: 4px;
		}
		.lm-stat-description {
			font-size: 13px;
			color: #6b7280;
		}
		.lm-table {
			width: 100%;
			border-collapse: collapse;
			font-size: 14px;
		}
		.lm-table thead {
			background: #f3f4f6;
		}
		.lm-table th {
			padding: 12px 16px;
			text-align: left;
			font-weight: 600;
			color: #374151;
			border-bottom: 1px solid #e5e7eb;
		}
		.lm-table td {
			padding: 12px 16px;
			border-bottom: 1px solid #e5e7eb;
		}
		.lm-table tbody tr:hover {
			background: #f9fafb;
		}
		.lm-badge {
			display: inline-block;
			padding: 4px 12px;
			border-radius: 12px;
			font-size: 12px;
			font-weight: 600;
		}
		.lm-badge-approved {
			background: #d1fae5;
			color: #065f46;
		}
		.lm-badge-pending {
			background: #fef3c7;
			color: #92400e;
		}
		.lm-badge-rejected {
			background: #fee2e2;
			color: #991b1b;
		}
		.lm-btn {
			display: inline-block;
			padding: 10px 16px;
			border-radius: 4px;
			font-size: 14px;
			text-decoration: none;
			border: none;
			cursor: pointer;
			font-weight: 500;
		}
		.lm-btn-primary {
			background: #667eea;
			color: white;
		}
		.lm-btn-primary:hover {
			background: #5568d3;
		}
		.lm-btn-secondary {
			background: #e5e7eb;
			color: #374151;
		}
		.lm-btn-secondary:hover {
			background: #d1d5db;
		}
		.lm-empty-state {
			text-align: center;
			padding: 40px 20px;
			color: #6b7280;
		}
		.lm-empty-state p {
			margin: 0 0 16px 0;
		}
	</style>
</head>
<body>
	<div class="lm-employee-dashboard">
		<div class="lm-header-bar">
			<div>
				<h1>Welcome, <?php echo esc_html( $user->first_name ); ?>!</h1>
				<p style="margin: 4px 0 0 0; color: #6b7280; font-size: 14px;">Your leave management dashboard</p>
			</div>
			<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="lm-logout-btn">Logout</a>
		</div>

		<div class="lm-grid-4">
			<div class="lm-stat-card">
				<div class="lm-stat-label">Total Leave Balance</div>
				<div class="lm-stat-value"><?php echo intval( $total_leave_days ); ?></div>
				<div class="lm-stat-description">days per year</div>
			</div>
			<div class="lm-stat-card" style="border-left-color: #ef4444;">
				<div class="lm-stat-label">Days Used</div>
				<div class="lm-stat-value" style="color: #ef4444;"><?php echo intval( $used_leave_days ); ?></div>
				<div class="lm-stat-description">approved requests</div>
			</div>
			<div class="lm-stat-card" style="border-left-color: #f97316;">
				<div class="lm-stat-label">Days Pending</div>
				<div class="lm-stat-value" style="color: #f97316;"><?php echo intval( $pending_leave_days ); ?></div>
				<div class="lm-stat-description">awaiting approval</div>
			</div>
			<div class="lm-stat-card" style="border-left-color: #22c55e;">
				<div class="lm-stat-label">Days Remaining</div>
				<div class="lm-stat-value" style="color: #22c55e;"><?php echo intval( $remaining_leave_days ); ?></div>
				<div class="lm-stat-description">available to use</div>
			</div>
		</div>

		<div class="lm-grid-2">
			<div class="lm-card">
				<div class="lm-card-header">
					<h2>Your Leave Requests</h2>
				</div>
				<div class="lm-card-body">
					<?php if ( $leave_requests ) : ?>
						<table class="lm-table">
							<thead>
								<tr>
									<th>Type</th>
									<th>Dates</th>
									<th>Days</th>
									<th>Status</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $leave_requests as $request ) : ?>
									<?php
									$start = strtotime( $request->start_date );
									$end = strtotime( $request->end_date );
									$days = ceil( ( $end - $start ) / 86400 ) + 1;
									$status_class = 'lm-badge-' . strtolower( $request->status );
									?>
									<tr>
										<td><?php echo esc_html( $request->leave_type ); ?></td>
										<td><?php echo esc_html( date( 'M d, Y', $start ) . ' - ' . date( 'M d, Y', $end ) ); ?></td>
										<td><?php echo intval( $days ); ?> days</td>
										<td><span class="lm-badge <?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( ucfirst( $request->status ) ); ?></span></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php else : ?>
						<div class="lm-empty-state">
							<p>No leave requests yet.</p>
							<a href="<?php echo esc_url( home_url( '/submit-leave-request/' ) ); ?>" class="lm-btn lm-btn-primary">Submit Your First Request</a>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="lm-card">
				<div class="lm-card-header">
					<h2>Your Profile</h2>
				</div>
				<div class="lm-card-body">
					<div style="margin-bottom: 16px;">
						<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Full Name</div>
						<div style="font-weight: 600; color: #1f2937;"><?php echo esc_html( $user->first_name . ' ' . $user->last_name ); ?></div>
					</div>
					<div style="margin-bottom: 16px;">
						<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Email</div>
						<div style="font-weight: 600; color: #1f2937;"><?php echo esc_html( $user->user_email ); ?></div>
					</div>
					<div style="margin-bottom: 16px;">
						<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Department</div>
						<div style="font-weight: 600; color: #1f2937;"><?php echo esc_html( get_user_meta( $current_user_id, 'department', true ) ?: 'Not assigned' ); ?></div>
					</div>
					<div>
						<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Member Since</div>
						<div style="font-weight: 600; color: #1f2937;"><?php echo esc_html( date( 'M d, Y', strtotime( $user->user_registered ) ) ); ?></div>
					</div>
				</div>
			</div>
		</div>

		<div class="lm-card">
			<div class="lm-card-header">
				<h2>Quick Actions</h2>
			</div>
			<div class="lm-card-body">
				<a href="<?php echo esc_url( home_url( '/submit-leave-request/' ) ); ?>" class="lm-btn lm-btn-primary" style="margin-right: 10px;">Submit Leave Request</a>
				<a href="<?php echo esc_url( home_url( '/leave-policies/' ) ); ?>" class="lm-btn lm-btn-secondary">View Leave Policies</a>
			</div>
		</div>
	</div>

	<?php wp_footer(); ?>
</body>
</html>
