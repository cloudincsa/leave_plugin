<?php
/**
 * Leave Manager Dashboard - Professional Version
 * Displays system overview and management metrics
 */

// Get database instance
global $wpdb;

// Get system statistics - Direct queries without AJAX
$total_staff = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_users WHERE status = 'active'" );
$total_requests = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests" );
$pending_requests = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests WHERE status = 'pending'" );
$approved_requests = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests WHERE status = 'approved'" );
$rejected_requests = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests WHERE status = 'rejected'" );

// Calculate approval rate
$approval_rate = $total_requests > 0 ? round( ( $approved_requests / $total_requests ) * 100, 1 ) : 0;

// Get departments
$departments = $wpdb->get_results( "SELECT name, COUNT(id) as staff_count FROM {$wpdb->prefix}leave_manager_leave_users WHERE status = 'active' GROUP BY name ORDER BY name" );

// Get leave type distribution
$leave_types = $wpdb->get_results( "SELECT leave_type, COUNT(id) as count FROM {$wpdb->prefix}leave_manager_leave_requests GROUP BY leave_type ORDER BY count DESC" );

// Calculate leave type percentages
$leave_type_data = array();
if ( ! empty( $leave_types ) ) {
	foreach ( $leave_types as $type ) {
		$percentage = $total_requests > 0 ? round( ( $type->count / $total_requests ) * 100, 1 ) : 0;
		$leave_type_data[] = array(
			'type'       => $type->leave_type,
			'count'      => $type->count,
			'percentage' => $percentage,
		);
	}
}
?>

<div class="leave-manager-admin-page">
	<div class="page-header">
		<div>
			<h1><?php esc_html_e( 'Admin Dashboard', 'leave-manager' ); ?></h1>
			<p class="subtitle"><?php esc_html_e( 'System overview and management metrics', 'leave-manager' ); ?></p>
		</div>
	</div>

	<!-- System Statistics -->
	<div class="lm-stat-grid">
		<div class="lm-stat-card">
			<div class="lm-stat-label"><?php esc_html_e( 'Total Staff', 'leave-manager' ); ?></div>
			<div class="lm-stat-value" style="color: #3b82f6;"><?php echo intval( $total_staff ); ?></div>
			<div class="lm-stat-description"><?php esc_html_e( 'active employees', 'leave-manager' ); ?></div>
		</div>
		<div class="lm-stat-card">
			<div class="lm-stat-label"><?php esc_html_e( 'Total Requests', 'leave-manager' ); ?></div>
			<div class="lm-stat-value" style="color: #8b5cf6;"><?php echo intval( $total_requests ); ?></div>
			<div class="lm-stat-description"><?php esc_html_e( 'all time', 'leave-manager' ); ?></div>
		</div>
		<div class="lm-stat-card">
			<div class="lm-stat-label"><?php esc_html_e( 'Pending Approval', 'leave-manager' ); ?></div>
			<div class="lm-stat-value" style="color: #f97316;"><?php echo intval( $pending_requests ); ?></div>
			<div class="lm-stat-description"><?php esc_html_e( 'awaiting action', 'leave-manager' ); ?></div>
		</div>
		<div class="lm-stat-card">
			<div class="lm-stat-label"><?php esc_html_e( 'Approval Rate', 'leave-manager' ); ?></div>
			<div class="lm-stat-value" style="color: #22c55e;"><?php echo esc_html( $approval_rate . '%' ); ?></div>
			<div class="lm-stat-description"><?php esc_html_e( 'of all requests', 'leave-manager' ); ?></div>
		</div>
	</div>

	<!-- Pending Approvals Section -->
	<div class="lm-card">
		<div class="lm-card-header">
			<h2><?php esc_html_e( 'Pending Approvals', 'leave-manager' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-requests' ) ); ?>" class="lm-link"><?php esc_html_e( 'View All →', 'leave-manager' ); ?></a>
		</div>
		<div class="lm-card-body">
			<?php
			if ( $pending_requests > 0 ) {
				$pending_list = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests WHERE status = 'pending' LIMIT 5" );
				echo '<table class="lm-table">';
				echo '<thead><tr><th>' . esc_html__( 'Employee', 'leave-manager' ) . '</th><th>' . esc_html__( 'Leave Type', 'leave-manager' ) . '</th><th>' . esc_html__( 'From', 'leave-manager' ) . '</th><th>' . esc_html__( 'To', 'leave-manager' ) . '</th></tr></thead>';
				echo '<tbody>';
				foreach ( $pending_list as $request ) {
					echo '<tr>';
					echo '<td>' . esc_html( $request->employee_name ?? 'N/A' ) . '</td>';
					echo '<td>' . esc_html( $request->leave_type ?? 'N/A' ) . '</td>';
					echo '<td>' . esc_html( $request->start_date ?? 'N/A' ) . '</td>';
					echo '<td>' . esc_html( $request->end_date ?? 'N/A' ) . '</td>';
					echo '</tr>';
				}
				echo '</tbody>';
				echo '</table>';
			} else {
				echo '<p>' . esc_html__( 'No pending requests. All caught up!', 'leave-manager' ) . '</p>';
			}
			?>
		</div>
	</div>

	<!-- Leave Type Distribution -->
	<div class="lm-card">
		<div class="lm-card-header">
			<h2><?php esc_html_e( 'Leave Type Distribution', 'leave-manager' ); ?></h2>
		</div>
		<div class="lm-card-body">
			<?php
			if ( ! empty( $leave_type_data ) ) {
				echo '<table class="lm-table">';
				echo '<thead><tr><th>' . esc_html__( 'Leave Type', 'leave-manager' ) . '</th><th>' . esc_html__( 'Requests', 'leave-manager' ) . '</th><th>' . esc_html__( 'Percentage', 'leave-manager' ) . '</th></tr></thead>';
				echo '<tbody>';
				foreach ( $leave_type_data as $data ) {
					$bar_width = $data['percentage'];
					echo '<tr>';
					echo '<td>' . esc_html( $data['type'] ) . '</td>';
					echo '<td>' . intval( $data['count'] ) . '</td>';
					echo '<td><div style="background: #e5e7eb; height: 20px; border-radius: 4px; overflow: hidden;"><div style="background: #3b82f6; height: 100%; width: ' . intval( $bar_width ) . '%;"></div></div> ' . esc_html( $data['percentage'] ) . '%</td>';
					echo '</tr>';
				}
				echo '</tbody>';
				echo '</table>';
			} else {
				echo '<p>' . esc_html__( 'No leave requests yet.', 'leave-manager' ) . '</p>';
			}
			?>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="lm-card">
		<div class="lm-card-header">
			<h2><?php esc_html_e( 'Quick Actions', 'leave-manager' ); ?></h2>
		</div>
		<div class="lm-card-body">
			<div class="lm-quick-actions">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-staff' ) ); ?>" class="btn btn-primary"><?php esc_html_e( 'Manage Staff', 'leave-manager' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-requests' ) ); ?>" class="btn btn-secondary"><?php esc_html_e( 'View Requests', 'leave-manager' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-reports' ) ); ?>" class="btn btn-secondary"><?php esc_html_e( 'Generate Reports', 'leave-manager' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-settings' ) ); ?>" class="btn btn-secondary"><?php esc_html_e( 'Settings', 'leave-manager' ); ?></a>
			</div>
		</div>
	</div>

	<!-- Department Summary -->
	<?php if ( class_exists( 'Leave_Manager_Department_Toggle' ) && Leave_Manager_Department_Toggle::is_enabled() ) { ?>
	<div class="lm-card">
		<div class="lm-card-header">
			<h2><?php esc_html_e( 'Department Summary', 'leave-manager' ); ?></h2>
		</div>
		<div class="lm-card-body">
			<?php
			if ( ! empty( $departments ) ) {
				echo '<table class="lm-table">';
				echo '<thead><tr><th>' . esc_html__( 'Department', 'leave-manager' ) . '</th><th>' . esc_html__( 'Staff Count', 'leave-manager' ) . '</th></tr></thead>';
				echo '<tbody>';
				foreach ( $departments as $dept ) {
					echo '<tr>';
					echo '<td>' . esc_html( $dept->name ) . '</td>';
					echo '<td>' . intval( $dept->staff_count ) . '</td>';
					echo '</tr>';
				}
				echo '</tbody>';
				echo '</table>';
			} else {
				echo '<p>' . esc_html__( 'No departments configured.', 'leave-manager' ) . '</p>';
			}
			?>
		</div>
	</div>
	<?php } ?>

	<!-- System Status -->
	<div class="lm-card">
		<div class="lm-card-header">
			<h2><?php esc_html_e( 'System Status', 'leave-manager' ); ?></h2>
		</div>
		<div class="lm-card-body">
			<div class="lm-status-grid">
				<div class="lm-status-item">
					<span class="lm-status-indicator lm-status-green"></span>
					<span><?php esc_html_e( 'Database Connected', 'leave-manager' ); ?></span>
				</div>
				<div class="lm-status-item">
					<span class="lm-status-indicator lm-status-green"></span>
					<span><?php esc_html_e( 'Plugin Active', 'leave-manager' ); ?></span>
				</div>
				<div class="lm-status-item">
					<span class="lm-status-indicator lm-status-yellow"></span>
					<span><?php echo intval( $pending_requests ); ?> <?php esc_html_e( 'Pending Actions', 'leave-manager' ); ?></span>
				</div>
			</div>
		</div>
	</div>

	<!-- Request Statistics -->
	<div class="lm-card">
		<div class="lm-card-header">
			<h2><?php esc_html_e( 'Request Statistics', 'leave-manager' ); ?></h2>
		</div>
		<div class="lm-card-body">
			<div class="lm-stat-grid">
				<div class="lm-mini-stat">
					<span class="lm-mini-stat-value" style="color: #10b981;"><?php echo intval( $approved_requests ); ?></span>
					<span class="lm-mini-stat-label"><?php esc_html_e( 'Approved', 'leave-manager' ); ?></span>
				</div>
				<div class="lm-mini-stat">
					<span class="lm-mini-stat-value" style="color: #f59e0b;"><?php echo intval( $pending_requests ); ?></span>
					<span class="lm-mini-stat-label"><?php esc_html_e( 'Pending', 'leave-manager' ); ?></span>
				</div>
				<div class="lm-mini-stat">
					<span class="lm-mini-stat-value" style="color: #ef4444;"><?php echo intval( $rejected_requests ); ?></span>
					<span class="lm-mini-stat-label"><?php esc_html_e( 'Rejected', 'leave-manager' ); ?></span>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.lm-stat-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 20px;
	margin-bottom: 30px;
}

.lm-stat-card {
	background: white;
	padding: 20px;
	border-radius: 12px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
	border-left: 4px solid #4A5FFF;
}

.lm-stat-label {
	font-size: 12px;
	color: #9ca3af;
	text-transform: uppercase;
	font-weight: 600;
	margin-bottom: 10px;
}

.lm-stat-value {
	font-size: 32px;
	font-weight: 700;
	margin-bottom: 5px;
}

.lm-stat-description {
	font-size: 12px;
	color: #6b7280;
}

.lm-card {
	background: white;
	padding: 20px;
	border-radius: 12px;
	box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
	margin-bottom: 20px;
}

.lm-card-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 20px;
	padding-bottom: 15px;
	border-bottom: 1px solid #e5e7eb;
}

.lm-card-header h2 {
	margin: 0;
	font-size: 18px;
	color: #1f2937;
	font-weight: 700;
}

.lm-link {
	color: #3b82f6;
	text-decoration: none;
	font-weight: 600;
}

.lm-link:hover {
	text-decoration: underline;
}

.lm-table {
	width: 100%;
	border-collapse: collapse;
}

.lm-table thead {
	background: #f9fafb;
	border-bottom: 1px solid #e5e7eb;
}

.lm-table th {
	padding: 12px;
	text-align: left;
	font-weight: 600;
	color: #374151;
	font-size: 12px;
	text-transform: uppercase;
}

.lm-table td {
	padding: 12px;
	border-bottom: 1px solid #e5e7eb;
	color: #6b7280;
}

.lm-table tbody tr:hover {
	background: #f9fafb;
}

.lm-quick-actions {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.btn {
	padding: 10px 20px;
	border: none;
	border-radius: 6px;
	cursor: pointer;
	font-weight: 600;
	transition: all 0.3s ease;
	font-size: 14px;
	display: inline-block;
	text-decoration: none;
	text-align: center;
}

.btn-primary {
	background: #3b82f6;
	color: white;
}

.btn-primary:hover {
	background: #2563eb;
}

.btn-secondary {
	background: #e5e7eb;
	color: #1f2937;
}

.btn-secondary:hover {
	background: #d1d5db;
}

.lm-status-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
}

.lm-status-item {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 10px;
	background: #f9fafb;
	border-radius: 6px;
}

.lm-status-indicator {
	display: inline-block;
	width: 12px;
	height: 12px;
	border-radius: 50%;
}

.lm-status-green {
	background: #10b981;
}

.lm-status-yellow {
	background: #f59e0b;
}

.lm-status-red {
	background: #ef4444;
}

.lm-mini-stats {
	display: flex;
	justify-content: space-around;
	text-align: center;
}

.lm-mini-stat {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 5px;
}

.lm-mini-stat-value {
	display: block;
	font-size: 24px;
	font-weight: bold;
}

.lm-mini-stat-label {
	font-size: 12px;
	color: #6b7280;
}

.lm-quick-actions {
	display: flex;
	flex-direction: column;
}
</style>
