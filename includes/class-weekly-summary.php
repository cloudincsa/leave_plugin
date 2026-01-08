<?php
/**
 * Weekly Summary Email Handler for Leave Manager Plugin
 *
 * Generates and sends comprehensive weekly leave reports for CEO/management reporting.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Weekly_Summary class
 */
class Leave_Manager_Weekly_Summary {

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
	 * Settings instance
	 *
	 * @var Leave_Manager_Settings
	 */
	private $settings;

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 * @param Leave_Manager_Settings $settings Settings instance
	 */
	public function __construct( $db, $logger, $settings ) {
		$this->db       = $db;
		$this->logger   = $logger;
		$this->settings = $settings;
	}

	/**
	 * Check if weekly summary is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->settings->get( 'weekly_summary_enabled', '0' ) === '1';
	}

	/**
	 * Get recipient email addresses
	 *
	 * @return array
	 */
	public function get_recipients() {
		$recipients_string = $this->settings->get( 'weekly_summary_recipients', get_option( 'admin_email' ) );
		$recipients = array_map( 'trim', explode( ',', $recipients_string ) );
		return array_filter( $recipients, 'is_email' );
	}

	/**
	 * Gather all statistics for the weekly summary
	 *
	 * @return array
	 */
	public function gather_statistics() {
		global $wpdb;

		$users_table = $wpdb->prefix . 'leave_manager_leave_users';
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		$today = current_time( 'Y-m-d' );
		$week_start = date( 'Y-m-d', strtotime( 'monday this week', strtotime( $today ) ) );
		$week_end = date( 'Y-m-d', strtotime( 'sunday this week', strtotime( $today ) ) );
		$next_week_start = date( 'Y-m-d', strtotime( '+1 week', strtotime( $week_start ) ) );
		$next_week_end = date( 'Y-m-d', strtotime( '+1 week', strtotime( $week_end ) ) );

		$stats = array();

		// Executive Summary
		$stats['total_staff'] = $wpdb->get_var( "SELECT COUNT(*) FROM {$users_table} WHERE status = 'active'" );
		
		$stats['staff_on_leave_this_week'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$requests_table} 
			WHERE status = 'approved' 
			AND start_date <= %s AND end_date >= %s",
			$week_end, $week_start
		) );

		$stats['staff_returning_this_week'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$requests_table} 
			WHERE status = 'approved' 
			AND end_date BETWEEN %s AND %s",
			$week_start, $week_end
		) );

		$stats['staff_leaving_next_week'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT user_id) FROM {$requests_table} 
			WHERE status = 'approved' 
			AND start_date BETWEEN %s AND %s",
			$next_week_start, $next_week_end
		) );

		// Leave Request Activity (This Week)
		$stats['new_requests'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$requests_table} 
			WHERE DATE(created_at) BETWEEN %s AND %s",
			$week_start, $week_end
		) );

		$stats['approved_this_week'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$requests_table} 
			WHERE status = 'approved' 
			AND DATE(updated_at) BETWEEN %s AND %s",
			$week_start, $week_end
		) );

		$stats['rejected_this_week'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$requests_table} 
			WHERE status = 'rejected' 
			AND DATE(updated_at) BETWEEN %s AND %s",
			$week_start, $week_end
		) );

		$stats['pending_requests'] = $wpdb->get_var( 
			"SELECT COUNT(*) FROM {$requests_table} WHERE status = 'pending'"
		);

		// Leave Balance Overview
		$stats['low_balance_staff'] = $wpdb->get_var( 
			"SELECT COUNT(*) FROM {$users_table} 
			WHERE status = 'active' AND annual_leave_balance > 0 AND annual_leave_balance < 5"
		);

		$stats['no_balance_staff'] = $wpdb->get_var( 
			"SELECT COUNT(*) FROM {$users_table} 
			WHERE status = 'active' AND annual_leave_balance <= 0"
		);

		$stats['avg_annual_balance'] = round( (float) $wpdb->get_var( 
			"SELECT AVG(annual_leave_balance) FROM {$users_table} WHERE status = 'active'"
		), 1 );

		// Leave by Type (This Week - approved requests with dates in this week)
		$stats['annual_days_taken'] = $this->calculate_days_taken( $week_start, $week_end, 'annual' );
		$stats['sick_days_taken'] = $this->calculate_days_taken( $week_start, $week_end, 'sick' );
		$stats['other_days_taken'] = $this->calculate_days_taken( $week_start, $week_end, 'other' );
		$stats['total_days_taken'] = $stats['annual_days_taken'] + $stats['sick_days_taken'] + $stats['other_days_taken'];

		// Department Breakdown
		$stats['department_breakdown'] = $wpdb->get_results(
			"SELECT u.department, 
				COUNT(DISTINCT r.user_id) as staff_on_leave,
				COUNT(r.request_id) as total_requests
			FROM {$users_table} u
			LEFT JOIN {$requests_table} r ON u.user_id = r.user_id 
				AND r.status = 'approved'
				AND r.start_date <= '{$week_end}' AND r.end_date >= '{$week_start}'
			WHERE u.status = 'active' AND u.department IS NOT NULL AND u.department != ''
			GROUP BY u.department
			ORDER BY staff_on_leave DESC"
		);

		// Upcoming Leave (Next 7 Days)
		$stats['upcoming_leave'] = $wpdb->get_results( $wpdb->prepare(
			"SELECT u.first_name, u.last_name, u.department, r.leave_type, r.start_date, r.end_date
			FROM {$requests_table} r
			JOIN {$users_table} u ON r.user_id = u.user_id
			WHERE r.status = 'approved' 
			AND r.start_date BETWEEN %s AND %s
			ORDER BY r.start_date ASC
			LIMIT 20",
			$today, $next_week_end
		) );

		// Staff Currently on Leave
		$stats['currently_on_leave'] = $wpdb->get_results( $wpdb->prepare(
			"SELECT u.first_name, u.last_name, u.department, r.leave_type, r.start_date, r.end_date
			FROM {$requests_table} r
			JOIN {$users_table} u ON r.user_id = u.user_id
			WHERE r.status = 'approved' 
			AND r.start_date <= %s AND r.end_date >= %s
			ORDER BY r.end_date ASC
			LIMIT 20",
			$today, $today
		) );

		// Pending Requests Requiring Action
		$stats['pending_action_items'] = $wpdb->get_results(
			"SELECT u.first_name, u.last_name, r.leave_type, r.start_date, r.end_date, r.created_at,
				DATEDIFF(NOW(), r.created_at) as days_pending
			FROM {$requests_table} r
			JOIN {$users_table} u ON r.user_id = u.user_id
			WHERE r.status = 'pending'
			ORDER BY r.created_at ASC
			LIMIT 10"
		);

		// Date range for report
		$stats['report_period'] = array(
			'start' => $week_start,
			'end' => $week_end,
			'generated' => current_time( 'Y-m-d H:i:s' ),
		);

		return $stats;
	}

	/**
	 * Calculate days taken for a specific leave type within a date range
	 *
	 * @param string $start_date Start date
	 * @param string $end_date End date
	 * @param string $leave_type Leave type
	 * @return int
	 */
	private function calculate_days_taken( $start_date, $end_date, $leave_type ) {
		global $wpdb;
		$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';

		$total_days = 0;

		$requests = $wpdb->get_results( $wpdb->prepare(
			"SELECT start_date, end_date FROM {$requests_table} 
			WHERE status = 'approved' 
			AND leave_type = %s
			AND start_date <= %s AND end_date >= %s",
			$leave_type, $end_date, $start_date
		) );

		foreach ( $requests as $request ) {
			$req_start = max( strtotime( $request->start_date ), strtotime( $start_date ) );
			$req_end = min( strtotime( $request->end_date ), strtotime( $end_date ) );
			$days = floor( ( $req_end - $req_start ) / 86400 ) + 1;
			$total_days += max( 0, $days );
		}

		return $total_days;
	}

	/**
	 * Generate the HTML email content
	 *
	 * @param array $stats Statistics array
	 * @return string
	 */
	public function generate_email_html( $stats ) {
		$org_name = $this->settings->get( 'organization_name', get_bloginfo( 'name' ) );
		$include_staff_list = $this->settings->get( 'weekly_summary_include_staff_list', '1' ) === '1';
		$include_department = $this->settings->get( 'weekly_summary_include_department_breakdown', '1' ) === '1';
		$include_pending = $this->settings->get( 'weekly_summary_include_pending_actions', '1' ) === '1';

		$period_start = date( 'j M Y', strtotime( $stats['report_period']['start'] ) );
		$period_end = date( 'j M Y', strtotime( $stats['report_period']['end'] ) );

		ob_start();
		?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Weekly Leave Summary</title>
	<style>
		body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
		.container { max-width: 700px; margin: 0 auto; background: #fff; }
		.header { background: linear-gradient(135deg, #2271b1 0%, #135e96 100%); color: #fff; padding: 30px; text-align: center; }
		.header h1 { margin: 0 0 5px 0; font-size: 24px; font-weight: 600; }
		.header p { margin: 0; opacity: 0.9; font-size: 14px; }
		.content { padding: 30px; }
		.section { margin-bottom: 30px; }
		.section-title { font-size: 18px; font-weight: 600; color: #1d2327; margin: 0 0 15px 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1; }
		.stats-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
		.stat-card { background: #f8f9fa; border-radius: 8px; padding: 20px; text-align: center; border-left: 4px solid #2271b1; }
		.stat-card.highlight { border-left-color: #d63638; background: #fef7f7; }
		.stat-card.success { border-left-color: #00a32a; background: #f0f9f0; }
		.stat-card.warning { border-left-color: #dba617; background: #fefcf5; }
		.stat-value { font-size: 32px; font-weight: 700; color: #1d2327; line-height: 1; }
		.stat-label { font-size: 13px; color: #646970; margin-top: 5px; }
		.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
		.data-table th { background: #f0f0f1; padding: 12px; text-align: left; font-size: 13px; font-weight: 600; color: #1d2327; border-bottom: 2px solid #c3c4c7; }
		.data-table td { padding: 12px; border-bottom: 1px solid #e0e0e0; font-size: 14px; }
		.data-table tr:hover { background: #f8f9fa; }
		.badge { display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; font-weight: 500; }
		.badge-annual { background: #e7f3ff; color: #0073aa; }
		.badge-sick { background: #fef0f0; color: #d63638; }
		.badge-other { background: #f0f0f1; color: #646970; }
		.badge-urgent { background: #d63638; color: #fff; }
		.action-item { background: #fff8e5; border-left: 4px solid #dba617; padding: 15px; margin: 10px 0; border-radius: 0 4px 4px 0; }
		.action-item strong { color: #1d2327; }
		.footer { background: #f0f0f1; padding: 20px 30px; text-align: center; font-size: 12px; color: #646970; }
		.footer a { color: #2271b1; text-decoration: none; }
		.summary-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e0e0e0; }
		.summary-row:last-child { border-bottom: none; }
		.summary-label { color: #646970; }
		.summary-value { font-weight: 600; color: #1d2327; }
		@media (max-width: 600px) {
			.stats-grid { grid-template-columns: 1fr; }
			.content { padding: 20px; }
		}
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h1><?php echo esc_html( $org_name ); ?></h1>
			<p>Weekly Leave Management Summary</p>
			<p style="margin-top: 10px; font-size: 13px;"><?php echo esc_html( $period_start ); ?> - <?php echo esc_html( $period_end ); ?></p>
		</div>
		
		<div class="content">
			<!-- Executive Summary -->
			<div class="section">
				<h2 class="section-title">Executive Summary</h2>
				<div class="stats-grid">
					<div class="stat-card">
						<div class="stat-value"><?php echo esc_html( $stats['total_staff'] ); ?></div>
						<div class="stat-label">Total Staff</div>
					</div>
					<div class="stat-card <?php echo $stats['staff_on_leave_this_week'] > 0 ? 'warning' : ''; ?>">
						<div class="stat-value"><?php echo esc_html( $stats['staff_on_leave_this_week'] ); ?></div>
						<div class="stat-label">Currently on Leave</div>
					</div>
					<div class="stat-card success">
						<div class="stat-value"><?php echo esc_html( $stats['staff_returning_this_week'] ); ?></div>
						<div class="stat-label">Returning This Week</div>
					</div>
					<div class="stat-card">
						<div class="stat-value"><?php echo esc_html( $stats['staff_leaving_next_week'] ); ?></div>
						<div class="stat-label">Starting Leave Next Week</div>
					</div>
				</div>
			</div>

			<!-- Leave Request Activity -->
			<div class="section">
				<h2 class="section-title">Leave Request Activity</h2>
				<div class="stats-grid">
					<div class="stat-card">
						<div class="stat-value"><?php echo esc_html( $stats['new_requests'] ); ?></div>
						<div class="stat-label">New Requests</div>
					</div>
					<div class="stat-card success">
						<div class="stat-value"><?php echo esc_html( $stats['approved_this_week'] ); ?></div>
						<div class="stat-label">Approved</div>
					</div>
					<div class="stat-card highlight">
						<div class="stat-value"><?php echo esc_html( $stats['rejected_this_week'] ); ?></div>
						<div class="stat-label">Rejected</div>
					</div>
					<div class="stat-card <?php echo $stats['pending_requests'] > 0 ? 'warning' : ''; ?>">
						<div class="stat-value"><?php echo esc_html( $stats['pending_requests'] ); ?></div>
						<div class="stat-label">Pending Review</div>
					</div>
				</div>
			</div>

			<!-- Leave Days Summary -->
			<div class="section">
				<h2 class="section-title">Leave Days This Week</h2>
				<div class="summary-row">
					<span class="summary-label">Annual Leave</span>
					<span class="summary-value"><?php echo esc_html( $stats['annual_days_taken'] ); ?> days</span>
				</div>
				<div class="summary-row">
					<span class="summary-label">Sick Leave</span>
					<span class="summary-value"><?php echo esc_html( $stats['sick_days_taken'] ); ?> days</span>
				</div>
				<div class="summary-row">
					<span class="summary-label">Other Leave</span>
					<span class="summary-value"><?php echo esc_html( $stats['other_days_taken'] ); ?> days</span>
				</div>
				<div class="summary-row" style="background: #f0f0f1; margin: 10px -15px -15px; padding: 15px; border-radius: 0 0 8px 8px;">
					<span class="summary-label"><strong>Total Days</strong></span>
					<span class="summary-value" style="font-size: 18px;"><?php echo esc_html( $stats['total_days_taken'] ); ?> days</span>
				</div>
			</div>

			<!-- Leave Balance Alerts -->
			<?php if ( $stats['low_balance_staff'] > 0 || $stats['no_balance_staff'] > 0 ) : ?>
			<div class="section">
				<h2 class="section-title">Leave Balance Alerts</h2>
				<?php if ( $stats['no_balance_staff'] > 0 ) : ?>
				<div class="action-item" style="border-left-color: #d63638; background: #fef7f7;">
					<strong><?php echo esc_html( $stats['no_balance_staff'] ); ?> staff member(s)</strong> have exhausted their annual leave balance.
				</div>
				<?php endif; ?>
				<?php if ( $stats['low_balance_staff'] > 0 ) : ?>
				<div class="action-item">
					<strong><?php echo esc_html( $stats['low_balance_staff'] ); ?> staff member(s)</strong> have less than 5 days annual leave remaining.
				</div>
				<?php endif; ?>
				<p style="font-size: 13px; color: #646970; margin-top: 15px;">
					Average annual leave balance across organization: <strong><?php echo esc_html( $stats['avg_annual_balance'] ); ?> days</strong>
				</p>
			</div>
			<?php endif; ?>

			<?php if ( $include_department && ! empty( $stats['department_breakdown'] ) ) : ?>
			<!-- Department Breakdown -->
			<div class="section">
				<h2 class="section-title">Department Overview</h2>
				<table class="data-table">
					<thead>
						<tr>
							<th>Department</th>
							<th>Staff on Leave</th>
							<th>Total Requests</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats['department_breakdown'] as $dept ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $dept->department ?: 'Unassigned' ); ?></strong></td>
							<td><?php echo esc_html( $dept->staff_on_leave ); ?></td>
							<td><?php echo esc_html( $dept->total_requests ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>

			<?php if ( $include_staff_list && ! empty( $stats['currently_on_leave'] ) ) : ?>
			<!-- Currently on Leave -->
			<div class="section">
				<h2 class="section-title">Staff Currently on Leave</h2>
				<table class="data-table">
					<thead>
						<tr>
							<th>Name</th>
							<th>Department</th>
							<th>Type</th>
							<th>Return Date</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats['currently_on_leave'] as $staff ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $staff->first_name . ' ' . $staff->last_name ); ?></strong></td>
							<td><?php echo esc_html( $staff->department ?: '-' ); ?></td>
							<td><span class="badge badge-<?php echo esc_attr( $staff->leave_type ); ?>"><?php echo esc_html( ucfirst( $staff->leave_type ) ); ?></span></td>
							<td><?php echo esc_html( date( 'j M', strtotime( $staff->end_date ) ) ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>

			<?php if ( $include_staff_list && ! empty( $stats['upcoming_leave'] ) ) : ?>
			<!-- Upcoming Leave -->
			<div class="section">
				<h2 class="section-title">Upcoming Leave (Next 7 Days)</h2>
				<table class="data-table">
					<thead>
						<tr>
							<th>Name</th>
							<th>Department</th>
							<th>Type</th>
							<th>Dates</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats['upcoming_leave'] as $staff ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $staff->first_name . ' ' . $staff->last_name ); ?></strong></td>
							<td><?php echo esc_html( $staff->department ?: '-' ); ?></td>
							<td><span class="badge badge-<?php echo esc_attr( $staff->leave_type ); ?>"><?php echo esc_html( ucfirst( $staff->leave_type ) ); ?></span></td>
							<td><?php echo esc_html( date( 'j M', strtotime( $staff->start_date ) ) . ' - ' . date( 'j M', strtotime( $staff->end_date ) ) ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>

			<?php if ( $include_pending && ! empty( $stats['pending_action_items'] ) ) : ?>
			<!-- Action Items -->
			<div class="section">
				<h2 class="section-title">⚠️ Pending Action Items</h2>
				<p style="font-size: 14px; color: #646970; margin-bottom: 15px;">The following leave requests require your attention:</p>
				<table class="data-table">
					<thead>
						<tr>
							<th>Name</th>
							<th>Type</th>
							<th>Dates</th>
							<th>Waiting</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $stats['pending_action_items'] as $item ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $item->first_name . ' ' . $item->last_name ); ?></strong></td>
							<td><span class="badge badge-<?php echo esc_attr( $item->leave_type ); ?>"><?php echo esc_html( ucfirst( $item->leave_type ) ); ?></span></td>
							<td><?php echo esc_html( date( 'j M', strtotime( $item->start_date ) ) . ' - ' . date( 'j M', strtotime( $item->end_date ) ) ); ?></td>
							<td>
								<?php if ( $item->days_pending > 3 ) : ?>
								<span class="badge badge-urgent"><?php echo esc_html( $item->days_pending ); ?> days</span>
								<?php else : ?>
								<?php echo esc_html( $item->days_pending ); ?> days
								<?php endif; ?>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php endif; ?>
		</div>
		
		<div class="footer">
			<p>This is an automated weekly summary from the Leave Management System.</p>
			<p>Generated on <?php echo esc_html( date( 'l, j F Y \a\t H:i', strtotime( $stats['report_period']['generated'] ) ) ); ?></p>
			<p style="margin-top: 10px;"><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-management' ) ); ?>">View Full Dashboard</a></p>
		</div>
	</div>
</body>
</html>
		<?php
		return ob_get_clean();
	}

	/**
	 * Send the weekly summary email
	 *
	 * @return bool|array True on success, array of errors on failure
	 */
	public function send_summary() {
		if ( ! $this->is_enabled() ) {
			$this->logger->info( 'Weekly summary is disabled, skipping' );
			return false;
		}

		$recipients = $this->get_recipients();
		if ( empty( $recipients ) ) {
			$this->logger->error( 'No valid recipients for weekly summary' );
			return array( 'error' => 'No valid recipients configured' );
		}

		// Gather statistics
		$stats = $this->gather_statistics();

		// Generate email content
		$html_content = $this->generate_email_html( $stats );

		// Email subject
		$org_name = $this->settings->get( 'organization_name', get_bloginfo( 'name' ) );
		$week_start = date( 'j M', strtotime( $stats['report_period']['start'] ) );
		$week_end = date( 'j M Y', strtotime( $stats['report_period']['end'] ) );
		$subject = sprintf( '[%s] Weekly Leave Summary: %s - %s', $org_name, $week_start, $week_end );

		// Email headers
		$from_name = $this->settings->get( 'email_from_name', get_bloginfo( 'name' ) );
		$from_email = $this->settings->get( 'email_from_address', get_option( 'admin_email' ) );
		
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $from_name, $from_email ),
		);

		// Send to each recipient
		$results = array();
		foreach ( $recipients as $recipient ) {
			$sent = wp_mail( $recipient, $subject, $html_content, $headers );
			$results[ $recipient ] = $sent;
			
			if ( $sent ) {
				$this->logger->info( 'Weekly summary sent', array( 'recipient' => $recipient ) );
			} else {
				$this->logger->error( 'Failed to send weekly summary', array( 'recipient' => $recipient ) );
			}
		}

		return $results;
	}

	/**
	 * Send a test summary email
	 *
	 * @param string $test_email Email address to send test to
	 * @return bool
	 */
	public function send_test_summary( $test_email ) {
		if ( ! is_email( $test_email ) ) {
			return false;
		}

		// Gather statistics
		$stats = $this->gather_statistics();

		// Generate email content
		$html_content = $this->generate_email_html( $stats );

		// Email subject
		$org_name = $this->settings->get( 'organization_name', get_bloginfo( 'name' ) );
		$subject = sprintf( '[TEST] [%s] Weekly Leave Summary', $org_name );

		// Email headers
		$from_name = $this->settings->get( 'email_from_name', get_bloginfo( 'name' ) );
		$from_email = $this->settings->get( 'email_from_address', get_option( 'admin_email' ) );
		
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', $from_name, $from_email ),
		);

		$sent = wp_mail( $test_email, $subject, $html_content, $headers );
		
		$this->logger->info( 'Test weekly summary sent', array( 
			'recipient' => $test_email, 
			'success' => $sent 
		) );

		return $sent;
	}
}
