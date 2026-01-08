<?php
/**
 * Frontend Shortcodes
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register frontend shortcodes
 */
function leave_manager_register_shortcodes() {
	add_shortcode( 'leave_manager_leave_calendar', 'leave_manager_leave_calendar_shortcode' );
	add_shortcode( 'leave_manager_leave_balance', 'leave_manager_leave_balance_shortcode' );
	add_shortcode( 'leave_manager_leave_form', 'leave_manager_leave_form_shortcode' );
	add_shortcode( 'leave_manager_leave_dashboard', 'leave_manager_leave_dashboard_shortcode' );
	add_shortcode( 'leave_manager_leave_history', 'leave_manager_leave_history_shortcode' );
}
add_action( 'init', 'leave_manager_register_shortcodes' );

/**
 * Calendar shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string Calendar HTML
 */
function leave_manager_leave_calendar_shortcode( $atts ) {
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		return '<p>You must be logged in to view the calendar.</p>';
	}

	// Get instances
	$db = new Leave_Manager_Database();
	$logger = new Leave_Manager_Logger();
	$calendar = new Leave_Manager_Calendar( $db, $logger );

	// Get current user
	$current_user_id = get_current_user_id();

	// Get month and year from attributes or request
	$month = isset( $_GET['month'] ) ? intval( $_GET['month'] ) : intval( date( 'm' ) );
	$year = isset( $_GET['year'] ) ? intval( $_GET['year'] ) : intval( date( 'Y' ) );

	// Validate
	$month = max( 1, min( 12, $month ) );
	$year = max( 2000, min( 2100, $year ) );

	// Enqueue calendar styles
	wp_enqueue_style( 'leave_manager-frontend-styles' );

	// Start output buffering
	ob_start();
	include LEAVE_MANAGER_PLUGIN_DIR . 'frontend/pages/calendar-improved.php';
	return ob_get_clean();
}

/**
 * Leave balance shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string Balance HTML
 */
function leave_manager_leave_balance_shortcode( $atts ) {
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		return '<p>You must be logged in to view your leave balance.</p>';
	}

	// Get instances
	$db = new Leave_Manager_Database();
	$logger = new Leave_Manager_Logger();
	$users = new Leave_Manager_Users( $db, $logger );

	// Get current user
	$current_user_id = get_current_user_id();

	// Get user data
	global $wpdb;
	$users_table = $db->users_table;
	$user = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $users_table WHERE wp_user_id = %d",
			intval( $current_user_id )
		)
	);

	if ( ! $user ) {
		return '<p>User information not found.</p>';
	}

	// Enqueue styles
	wp_enqueue_style( 'leave_manager-frontend-styles' );

	// Build HTML
	$html = '<div class="leave-manager-balance">';
	$html .= '<h3>Leave Balance</h3>';
	$html .= '<div class="balance-cards">';

	// Annual Leave
	$html .= '<div class="balance-card annual-leave">';
	$html .= '<div class="balance-type">Annual Leave</div>';
	$html .= '<div class="balance-amount">' . esc_html( $user->annual_leave_balance ) . ' days</div>';
	$html .= '</div>';

	// Sick Leave
	$html .= '<div class="balance-card sick-leave">';
	$html .= '<div class="balance-type">Sick Leave</div>';
	$html .= '<div class="balance-amount">' . esc_html( $user->sick_leave_balance ) . ' days</div>';
	$html .= '</div>';

	// Other Leave
	$html .= '<div class="balance-card other-leave">';
	$html .= '<div class="balance-type">Other Leave</div>';
	$html .= '<div class="balance-amount">' . esc_html( $user->other_leave_balance ) . ' days</div>';
	$html .= '</div>';

	$html .= '</div>';
	$html .= '</div>';

	return $html;
}

/**
 * Leave form shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string Form HTML
 */
function leave_manager_leave_form_shortcode( $atts ) {
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		return '<p>You must be logged in to submit a leave request.</p>';
	}

	// Enqueue styles and scripts
	wp_enqueue_style( 'leave_manager-frontend-styles' );
	wp_enqueue_script( 'leave_manager-frontend-scripts' );

	// Get instances
	$db = new Leave_Manager_Database();
	$logger = new Leave_Manager_Logger();
	$leave_requests = new Leave_Manager_Leave_Requests( $db, $logger );

	// Get current user
	$current_user_id = get_current_user_id();

	// Handle form submission
	$message = '';
	if ( isset( $_POST['action'] ) && 'submit_leave_request' === $_POST['action'] ) {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_leave_form' ) ) {
			$message = '<div class="notice notice-error"><p>Security check failed.</p></div>';
		} else {
			$start_date = sanitize_text_field( $_POST['start_date'] );
			$end_date = sanitize_text_field( $_POST['end_date'] );
			$leave_type = sanitize_text_field( $_POST['leave_type'] );
			$reason = sanitize_textarea_field( $_POST['reason'] );

			// Validate dates
			if ( strtotime( $start_date ) > strtotime( $end_date ) ) {
				$message = '<div class="notice notice-error"><p>End date must be after start date.</p></div>';
			} else {
				// Submit request
				$result = $leave_requests->create_request(
					$current_user_id,
					$start_date,
					$end_date,
					$leave_type,
					$reason
				);

				if ( $result ) {
					$message = '<div class="notice notice-success"><p>Leave request submitted successfully.</p></div>';
				} else {
					$message = '<div class="notice notice-error"><p>Failed to submit leave request.</p></div>';
				}
			}
		}
	}

	// Start output buffering
	ob_start();
	?>
	<div class="leave-manager-form-wrapper">
		<?php echo wp_kses_post( $message ); ?>
		<form method="post" class="leave-manager-form">
			<?php wp_nonce_field( 'leave_manager_leave_form', 'nonce' ); ?>
			<input type="hidden" name="action" value="submit_leave_request">

			<div class="form-group">
				<label for="start_date">Start Date *</label>
				<input type="date" id="start_date" name="start_date" required>
			</div>

			<div class="form-group">
				<label for="end_date">End Date *</label>
				<input type="date" id="end_date" name="end_date" required>
			</div>

			<div class="form-group">
				<label for="leave_type">Leave Type *</label>
				<select id="leave_type" name="leave_type" required>
					<option value="">Select Leave Type</option>
					<option value="annual">Annual Leave</option>
					<option value="sick">Sick Leave</option>
					<option value="other">Other Leave</option>
				</select>
			</div>

			<div class="form-group">
				<label for="reason">Reason for Leave</label>
				<textarea id="reason" name="reason" rows="4"></textarea>
			</div>

			<button type="submit" class="button button-primary">Submit Request</button>
		</form>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Leave dashboard shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string Dashboard HTML
 */
function leave_manager_leave_dashboard_shortcode( $atts ) {
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		return '<p>You must be logged in to view your dashboard.</p>';
	}

	// Get instances
	$db = new Leave_Manager_Database();
	$logger = new Leave_Manager_Logger();
	$calendar = new Leave_Manager_Calendar( $db, $logger );
	$users = new Leave_Manager_Users( $db, $logger );

	// Get current user
	$current_user_id = get_current_user_id();

	// Get user data
	global $wpdb;
	$users_table = $db->users_table;
	$requests_table = $db->leave_requests_table;

	$user = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM $users_table WHERE wp_user_id = %d",
			intval( $current_user_id )
		)
	);

	if ( ! $user ) {
		return '<p>User information not found.</p>';
	}

	// Get recent requests
	$recent_requests = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $requests_table WHERE user_id = %d ORDER BY created_at DESC LIMIT 5",
			intval( $user->user_id )
		)
	);

	// Enqueue styles
	wp_enqueue_style( 'leave_manager-frontend-styles' );

	// Start output buffering
	ob_start();
	?>
	<div class="leave-manager-user-dashboard">
		<div class="dashboard-header">
			<h2>Welcome, <?php echo esc_html( $user->first_name . ' ' . $user->last_name ); ?></h2>
			<p class="dashboard-subtitle"><?php echo esc_html( $user->department ); ?> - <?php echo esc_html( $user->position ); ?></p>
		</div>

		<div class="dashboard-content">
			<div class="dashboard-section">
				<h3>Leave Balance</h3>
				<div class="balance-cards">
					<div class="balance-card">
						<div class="balance-type">Annual Leave</div>
						<div class="balance-amount"><?php echo esc_html( $user->annual_leave_balance ); ?> days</div>
					</div>
					<div class="balance-card">
						<div class="balance-type">Sick Leave</div>
						<div class="balance-amount"><?php echo esc_html( $user->sick_leave_balance ); ?> days</div>
					</div>
					<div class="balance-card">
						<div class="balance-type">Other Leave</div>
						<div class="balance-amount"><?php echo esc_html( $user->other_leave_balance ); ?> days</div>
					</div>
				</div>
			</div>

			<div class="dashboard-section">
				<h3>Recent Requests</h3>
				<?php if ( ! empty( $recent_requests ) ) : ?>
					<table class="requests-table">
						<thead>
							<tr>
								<th>Type</th>
								<th>Dates</th>
								<th>Status</th>
								<th>Submitted</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $recent_requests as $request ) : ?>
								<tr>
									<td><?php echo esc_html( ucfirst( str_replace( '_', ' ', $request->leave_type ) ) ); ?></td>
									<td>
										<?php
										$start = date_i18n( 'M d', strtotime( $request->start_date ) );
										$end = date_i18n( 'M d, Y', strtotime( $request->end_date ) );
										echo esc_html( $start . ' - ' . $end );
										?>
									</td>
									<td>
										<span class="status-badge status-<?php echo esc_attr( $request->status ); ?>">
											<?php echo esc_html( ucfirst( $request->status ) ); ?>
										</span>
									</td>
									<td><?php echo esc_html( date_i18n( 'M d, Y', strtotime( $request->created_at ) ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p>No leave requests yet.</p>
				<?php endif; ?>
			</div>

			<div class="dashboard-actions">
				<a href="#" class="button button-primary">Request Leave</a>
				<a href			<a href="#" class="button button-secondary">View Calendar</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Leave history shortcode
 *
 * @param array $atts Shortcode attributes
 * @return string History HTML
 */
function leave_manager_leave_history_shortcode( $atts ) {
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		return '<p>You must be logged in to view your leave history.</p>';
	}

	// Enqueue styles
	wp_enqueue_style( 'leave_manager-frontend-styles' );

	// Start output buffering
	ob_start();
	include LEAVE_MANAGER_PLUGIN_DIR . 'frontend/pages/history.php';
	return ob_get_clean();
}
