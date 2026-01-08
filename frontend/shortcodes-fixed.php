<?php
/**
 * Fixed Frontend Shortcodes - Using WordPress User Meta
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
function leave_manager_register_shortcodes_fixed() {
	add_shortcode( 'leave_manager_leave_dashboard', 'leave_manager_leave_dashboard_shortcode_fixed' );
	add_shortcode( 'leave_manager_leave_form', 'leave_manager_leave_form_shortcode_fixed' );
	add_shortcode( 'leave_manager_leave_balance', 'leave_manager_leave_balance_shortcode_fixed' );
	add_shortcode( 'leave_manager_leave_history', 'leave_manager_leave_history_shortcode_fixed' );
	add_shortcode( 'leave_manager_signup', 'leave_manager_signup_shortcode_fixed' );
}
add_action( 'init', 'leave_manager_register_shortcodes_fixed', 11 );

/**
 * Get logo HTML
 */
function leave_manager_get_logo_header() {
	$logo_url = '/wp-content/uploads/2025/12/lfcc-logo-transparent.png';
	ob_start();
	?>
	<header class="lm-frontend-header">
		<div class="lm-header-container">
			<div class="lm-logo-section">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="LFCC Logo" class="lm-logo" style="height: 40px; width: auto;">
				<h1 class="lm-header-title" style="margin: 0; font-size: 18px; font-weight: 600; color: #1f2937;">Leave Manager</h1>
			</div>
			<?php if ( is_user_logged_in() ) : ?>
				<div class="lm-user-menu" style="display: flex; align-items: center; gap: 16px;">
					<span class="lm-user-name" style="font-size: 14px; color: #6b7280;"><?php echo esc_html( wp_get_current_user()->display_name ); ?></span>
					<a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" style="color: #667eea; text-decoration: none; font-size: 14px;">Logout</a>
				</div>
			<?php endif; ?>
		</div>
	</header>
	<style>
		.lm-frontend-header {
			background: white;
			border-bottom: 1px solid #e5e7eb;
			padding: 16px 20px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
			position: sticky;
			top: 0;
			z-index: 100;
		}
		.lm-header-container {
			max-width: 1200px;
			margin: 0 auto;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.lm-logo-section {
			display: flex;
			align-items: center;
			gap: 12px;
		}
	</style>
	<?php
	return ob_get_clean();
}

/**
 * Leave Dashboard Shortcode - Fixed
 */
function leave_manager_leave_dashboard_shortcode_fixed( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>You must be logged in to view your dashboard.</p>';
	}

	$current_user_id = get_current_user_id();
	$user = get_user_by( 'id', $current_user_id );

	// Get user meta
	$department = get_user_meta( $current_user_id, 'department', true ) ?: 'Not assigned';
	$annual_leave = intval( get_user_meta( $current_user_id, 'annual_leave_balance', true ) ) ?: 20;
	$sick_leave = intval( get_user_meta( $current_user_id, 'sick_leave_balance', true ) ) ?: 10;
	$other_leave = intval( get_user_meta( $current_user_id, 'other_leave_balance', true ) ) ?: 5;

	// Get leave requests (from WordPress posts)
	$requests = get_posts( array(
		'post_type' => 'leave_request',
		'author' => $current_user_id,
		'numberposts' => 5,
		'orderby' => 'date',
		'order' => 'DESC',
	) );

	ob_start();
	?>
	<?php echo leave_manager_get_logo_header(); ?>
	
	<div class="lm-dashboard-wrapper" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
		<div class="lm-dashboard-header" style="margin-bottom: 30px;">
			<h2 style="margin: 0 0 8px 0; font-size: 28px; color: #1f2937;">Welcome, <?php echo esc_html( $user->first_name ); ?>!</h2>
			<p style="margin: 0; color: #6b7280; font-size: 16px;"><?php echo esc_html( $department ); ?></p>
		</div>

		<div class="lm-stat-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 30px;">
			<div style="background: white; border-radius: 8px; padding: 20px; border-left: 5px solid #667eea; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
				<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">Annual Leave</div>
				<div style="font-size: 32px; font-weight: 700; color: #667eea;"><?php echo intval( $annual_leave ); ?></div>
				<div style="font-size: 13px; color: #6b7280;">days available</div>
			</div>
			<div style="background: white; border-radius: 8px; padding: 20px; border-left: 5px solid #ef4444; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
				<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">Sick Leave</div>
				<div style="font-size: 32px; font-weight: 700; color: #ef4444;"><?php echo intval( $sick_leave ); ?></div>
				<div style="font-size: 13px; color: #6b7280;">days available</div>
			</div>
			<div style="background: white; border-radius: 8px; padding: 20px; border-left: 5px solid #f97316; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
				<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">Other Leave</div>
				<div style="font-size: 32px; font-weight: 700; color: #f97316;"><?php echo intval( $other_leave ); ?></div>
				<div style="font-size: 13px; color: #6b7280;">days available</div>
			</div>
			<div style="background: white; border-radius: 8px; padding: 20px; border-left: 5px solid #22c55e; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
				<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 8px;">Total Available</div>
				<div style="font-size: 32px; font-weight: 700; color: #22c55e;"><?php echo intval( $annual_leave + $sick_leave + $other_leave ); ?></div>
				<div style="font-size: 13px; color: #6b7280;">days this year</div>
			</div>
		</div>

		<div style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); margin-bottom: 30px;">
			<div style="border-bottom: 1px solid #e5e7eb; padding: 16px 20px;">
				<h3 style="margin: 0; font-size: 16px; font-weight: 600; color: #1f2937;">Recent Leave Requests</h3>
			</div>
			<div style="padding: 20px;">
				<?php if ( ! empty( $requests ) ) : ?>
					<table style="width: 100%; border-collapse: collapse; font-size: 14px;">
						<thead>
							<tr style="background: #f3f4f6;">
								<th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Type</th>
								<th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Dates</th>
								<th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Status</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $requests as $request ) : ?>
								<?php
								$type = get_post_meta( $request->ID, 'leave_type', true );
								$start = get_post_meta( $request->ID, 'start_date', true );
								$end = get_post_meta( $request->ID, 'end_date', true );
								$status = get_post_meta( $request->ID, 'status', true ) ?: 'pending';
								$status_color = 'pending' === $status ? '#fbbf24' : ('approved' === $status ? '#10b981' : '#ef4444');
								?>
								<tr style="border-bottom: 1px solid #e5e7eb;">
									<td style="padding: 12px 16px;"><?php echo esc_html( ucfirst( $type ) ); ?></td>
									<td style="padding: 12px 16px;"><?php echo esc_html( date( 'M d, Y', strtotime( $start ) ) . ' - ' . date( 'M d, Y', strtotime( $end ) ) ); ?></td>
									<td style="padding: 12px 16px;"><span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; background: <?php echo esc_attr( $status_color ); ?>20; color: <?php echo esc_attr( $status_color ); ?>;"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p style="text-align: center; color: #6b7280; padding: 20px;">No leave requests yet. <a href="<?php echo esc_url( home_url( '/request/' ) ); ?>" style="color: #667eea; text-decoration: none;">Submit your first request</a></p>
				<?php endif; ?>
			</div>
		</div>

		<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
			<div style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 20px;">
				<h3 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #1f2937;">Your Profile</h3>
				<div style="margin-bottom: 16px;">
					<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Email</div>
					<div style="font-weight: 600; color: #1f2937;"><?php echo esc_html( $user->user_email ); ?></div>
				</div>
				<div style="margin-bottom: 16px;">
					<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Department</div>
					<div style="font-weight: 600; color: #1f2937;"><?php echo esc_html( $department ); ?></div>
				</div>
				<div>
					<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 4px;">Member Since</div>
					<div style="font-weight: 600; color: #1f2937;"><?php echo esc_html( date( 'M d, Y', strtotime( $user->user_registered ) ) ); ?></div>
				</div>
			</div>

			<div style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 20px;">
				<h3 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 600; color: #1f2937;">Quick Actions</h3>
				<a href="<?php echo esc_url( home_url( '/request/' ) ); ?>" style="display: block; padding: 10px 16px; background: #667eea; color: white; border-radius: 4px; text-decoration: none; text-align: center; margin-bottom: 10px; font-weight: 500;">Submit Leave Request</a>
				<a href="<?php echo esc_url( home_url( '/balance/' ) ); ?>" style="display: block; padding: 10px 16px; background: #e5e7eb; color: #374151; border-radius: 4px; text-decoration: none; text-align: center; font-weight: 500;">View Leave Balance</a>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Leave Form Shortcode - Fixed
 */
function leave_manager_leave_form_shortcode_fixed( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>You must be logged in to submit a leave request.</p>';
	}

	$current_user_id = get_current_user_id();
	$message = '';

	// Handle form submission
	if ( isset( $_POST['action'] ) && 'submit_leave_request' === $_POST['action'] ) {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_form' ) ) {
			$message = '<div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">Security check failed.</div>';
		} else {
			$start_date = sanitize_text_field( $_POST['start_date'] );
			$end_date = sanitize_text_field( $_POST['end_date'] );
			$leave_type = sanitize_text_field( $_POST['leave_type'] );
			$reason = sanitize_textarea_field( $_POST['reason'] );

			// Create post for leave request
			$post_id = wp_insert_post( array(
				'post_type' => 'leave_request',
				'post_title' => $leave_type . ' - ' . $start_date,
				'post_author' => $current_user_id,
				'post_status' => 'publish',
			) );

			if ( $post_id ) {
				update_post_meta( $post_id, 'start_date', $start_date );
				update_post_meta( $post_id, 'end_date', $end_date );
				update_post_meta( $post_id, 'leave_type', $leave_type );
				update_post_meta( $post_id, 'reason', $reason );
				update_post_meta( $post_id, 'status', 'pending' );
				$message = '<div style="background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">Leave request submitted successfully!</div>';
			} else {
				$message = '<div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">Failed to submit leave request.</div>';
			}
		}
	}

	ob_start();
	?>
	<?php echo leave_manager_get_logo_header(); ?>
	
	<div class="lm-form-wrapper" style="max-width: 600px; margin: 0 auto; padding: 20px;">
		<div style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); padding: 30px;">
			<h2 style="margin: 0 0 20px 0; font-size: 24px; font-weight: 600; color: #1f2937;">Submit Leave Request</h2>
			
			<?php echo wp_kses_post( $message ); ?>

			<form method="post">
				<?php wp_nonce_field( 'leave_manager_form', 'nonce' ); ?>
				<input type="hidden" name="action" value="submit_leave_request">

				<div style="margin-bottom: 20px;">
					<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1f2937;">Start Date *</label>
					<input type="date" name="start_date" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px;">
				</div>

				<div style="margin-bottom: 20px;">
					<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1f2937;">End Date *</label>
					<input type="date" name="end_date" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px;">
				</div>

				<div style="margin-bottom: 20px;">
					<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1f2937;">Leave Type *</label>
					<select name="leave_type" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px;">
						<option value="">Select Leave Type</option>
						<option value="annual">Annual Leave</option>
						<option value="sick">Sick Leave</option>
						<option value="other">Other Leave</option>
					</select>
				</div>

				<div style="margin-bottom: 20px;">
					<label style="display: block; font-weight: 600; margin-bottom: 8px; color: #1f2937;">Reason</label>
					<textarea name="reason" rows="4" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px; font-family: inherit;"></textarea>
				</div>

				<button type="submit" style="width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 14px;">Submit Request</button>
			</form>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Leave Balance Shortcode - Fixed
 */
function leave_manager_leave_balance_shortcode_fixed( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>You must be logged in to view your leave balance.</p>';
	}

	$current_user_id = get_current_user_id();
	$user = get_user_by( 'id', $current_user_id );

	$annual = intval( get_user_meta( $current_user_id, 'annual_leave_balance', true ) ) ?: 20;
	$sick = intval( get_user_meta( $current_user_id, 'sick_leave_balance', true ) ) ?: 10;
	$other = intval( get_user_meta( $current_user_id, 'other_leave_balance', true ) ) ?: 5;

	ob_start();
	?>
	<?php echo leave_manager_get_logo_header(); ?>
	
	<div class="lm-balance-wrapper" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
		<h2 style="margin: 0 0 30px 0; font-size: 28px; color: #1f2937;">Your Leave Balance</h2>

		<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
			<div style="background: white; border-radius: 8px; padding: 30px; border-left: 5px solid #667eea; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
				<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 12px;">Annual Leave</div>
				<div style="font-size: 48px; font-weight: 700; color: #667eea; margin-bottom: 8px;"><?php echo intval( $annual ); ?></div>
				<div style="font-size: 14px; color: #6b7280;">days available</div>
			</div>
			<div style="background: white; border-radius: 8px; padding: 30px; border-left: 5px solid #ef4444; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
				<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 12px;">Sick Leave</div>
				<div style="font-size: 48px; font-weight: 700; color: #ef4444; margin-bottom: 8px;"><?php echo intval( $sick ); ?></div>
				<div style="font-size: 14px; color: #6b7280;">days available</div>
			</div>
			<div style="background: white; border-radius: 8px; padding: 30px; border-left: 5px solid #f97316; box-shadow: 0 2px 8px rgba(0,0,0,0.08); text-align: center;">
				<div style="font-size: 12px; color: #6b7280; text-transform: uppercase; font-weight: 600; margin-bottom: 12px;">Other Leave</div>
				<div style="font-size: 48px; font-weight: 700; color: #f97316; margin-bottom: 8px;"><?php echo intval( $other ); ?></div>
				<div style="font-size: 14px; color: #6b7280;">days available</div>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Leave History Shortcode - Fixed
 */
function leave_manager_leave_history_shortcode_fixed( $atts ) {
	if ( ! is_user_logged_in() ) {
		return '<p>You must be logged in to view your leave history.</p>';
	}

	$current_user_id = get_current_user_id();
	$user = get_user_by( 'id', $current_user_id );

	$requests = get_posts( array(
		'post_type' => 'leave_request',
		'author' => $current_user_id,
		'numberposts' => -1,
		'orderby' => 'date',
		'order' => 'DESC',
	) );

	ob_start();
	?>
	<?php echo leave_manager_get_logo_header(); ?>
	
	<div class="lm-history-wrapper" style="max-width: 1200px; margin: 0 auto; padding: 20px;">
		<h2 style="margin: 0 0 30px 0; font-size: 28px; color: #1f2937;">Your Leave History</h2>

		<div style="background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden;">
			<?php if ( ! empty( $requests ) ) : ?>
				<table style="width: 100%; border-collapse: collapse;">
					<thead>
						<tr style="background: #f3f4f6;">
							<th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Type</th>
							<th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Start Date</th>
							<th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">End Date</th>
							<th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Status</th>
							<th style="padding: 12px 16px; text-align: left; font-weight: 600; color: #374151; border-bottom: 1px solid #e5e7eb;">Submitted</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $requests as $request ) : ?>
							<?php
							$type = get_post_meta( $request->ID, 'leave_type', true );
							$start = get_post_meta( $request->ID, 'start_date', true );
							$end = get_post_meta( $request->ID, 'end_date', true );
							$status = get_post_meta( $request->ID, 'status', true ) ?: 'pending';
							$status_color = 'pending' === $status ? '#fbbf24' : ('approved' === $status ? '#10b981' : '#ef4444');
							?>
							<tr style="border-bottom: 1px solid #e5e7eb;">
								<td style="padding: 12px 16px;"><?php echo esc_html( ucfirst( $type ) ); ?></td>
								<td style="padding: 12px 16px;"><?php echo esc_html( date( 'M d, Y', strtotime( $start ) ) ); ?></td>
								<td style="padding: 12px 16px;"><?php echo esc_html( date( 'M d, Y', strtotime( $end ) ) ); ?></td>
								<td style="padding: 12px 16px;"><span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; background: <?php echo esc_attr( $status_color ); ?>20; color: <?php echo esc_attr( $status_color ); ?>;"><?php echo esc_html( ucfirst( $status ) ); ?></span></td>
								<td style="padding: 12px 16px;"><?php echo esc_html( date( 'M d, Y', strtotime( $request->post_date ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div style="padding: 40px 20px; text-align: center; color: #6b7280;">
					<p>No leave requests found.</p>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Signup Shortcode - Fixed
 */
function leave_manager_signup_shortcode_fixed( $atts ) {
	if ( is_user_logged_in() ) {
		return '<p>You are already logged in. <a href="' . esc_url( home_url( '/dashboard/' ) ) . '">Go to Dashboard</a></p>';
	}

	$message = '';

	// Handle signup
	if ( isset( $_POST['action'] ) && 'signup' === $_POST['action'] ) {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_signup' ) ) {
			$message = '<div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">Security check failed.</div>';
		} else {
			$first_name = sanitize_text_field( $_POST['first_name'] );
			$last_name = sanitize_text_field( $_POST['last_name'] );
			$email = sanitize_email( $_POST['email'] );
			$password = sanitize_text_field( $_POST['password'] );
			$password_confirm = sanitize_text_field( $_POST['password_confirm'] );
			$department = sanitize_text_field( $_POST['department'] );

			// Validate
			if ( empty( $first_name ) || empty( $last_name ) || empty( $email ) || empty( $password ) ) {
				$message = '<div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">All fields are required.</div>';
			} elseif ( $password !== $password_confirm ) {
				$message = '<div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">Passwords do not match.</div>';
			} elseif ( email_exists( $email ) ) {
				$message = '<div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">Email already registered.</div>';
			} else {
				// Create user
				$user_id = wp_create_user( $email, $password, $email );
				if ( ! is_wp_error( $user_id ) ) {
					wp_update_user( array(
						'ID' => $user_id,
						'first_name' => $first_name,
						'last_name' => $last_name,
					) );
					update_user_meta( $user_id, 'department', $department );
					update_user_meta( $user_id, 'annual_leave_balance', 20 );
					update_user_meta( $user_id, 'sick_leave_balance', 10 );
					update_user_meta( $user_id, 'other_leave_balance', 5 );

					// Log user in
					wp_set_current_user( $user_id );
					wp_set_auth_cookie( $user_id );

					$message = '<div style="background: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">Account created successfully! Redirecting...</div>';
					echo '<script>setTimeout(function() { window.location.href = "' . esc_url( home_url( '/dashboard/' ) ) . '"; }, 2000);</script>';
				} else {
					$message = '<div style="background: #fee2e2; color: #991b1b; padding: 12px 16px; border-radius: 4px; margin-bottom: 20px;">Failed to create account.</div>';
				}
			}
		}
	}

	ob_start();
	?>
	<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
		<div style="background: white; border-radius: 8px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; max-width: 900px; width: 100%; display: grid; grid-template-columns: 1fr 1fr;">
			<!-- Left side -->
			<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; color: white; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
				<img src="/wp-content/uploads/2025/12/lfcc-logo-transparent.png" alt="LFCC Logo" style="height: 80px; width: auto; margin-bottom: 20px;">
				<h1 style="margin: 0 0 16px 0; font-size: 28px; font-weight: 700;">Leave Manager</h1>
				<p style="margin: 0; font-size: 16px; opacity: 0.9;">Manage your leave requests and track your time off with ease.</p>
			</div>

			<!-- Right side -->
			<div style="padding: 40px;">
				<h2 style="margin: 0 0 30px 0; font-size: 24px; font-weight: 600; color: #1f2937;">Create Your Account</h2>

				<?php echo wp_kses_post( $message ); ?>

				<form method="post">
					<?php wp_nonce_field( 'leave_manager_signup', 'nonce' ); ?>
					<input type="hidden" name="action" value="signup">

					<div style="margin-bottom: 16px;">
						<label style="display: block; font-weight: 600; margin-bottom: 6px; color: #1f2937; font-size: 14px;">First Name *</label>
						<input type="text" name="first_name" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
					</div>

					<div style="margin-bottom: 16px;">
						<label style="display: block; font-weight: 600; margin-bottom: 6px; color: #1f2937; font-size: 14px;">Last Name *</label>
						<input type="text" name="last_name" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
					</div>

					<div style="margin-bottom: 16px;">
						<label style="display: block; font-weight: 600; margin-bottom: 6px; color: #1f2937; font-size: 14px;">Email *</label>
						<input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
					</div>

					<div style="margin-bottom: 16px;">
						<label style="display: block; font-weight: 600; margin-bottom: 6px; color: #1f2937; font-size: 14px;">Department</label>
						<select name="department" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
							<option value="">Select Department</option>
							<option value="Engineering">Engineering</option>
							<option value="Sales">Sales</option>
							<option value="Marketing">Marketing</option>
							<option value="HR">HR</option>
							<option value="Finance">Finance</option>
						</select>
					</div>

					<div style="margin-bottom: 16px;">
						<label style="display: block; font-weight: 600; margin-bottom: 6px; color: #1f2937; font-size: 14px;">Password *</label>
						<input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
					</div>

					<div style="margin-bottom: 20px;">
						<label style="display: block; font-weight: 600; margin-bottom: 6px; color: #1f2937; font-size: 14px;">Confirm Password *</label>
						<input type="password" name="password_confirm" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
					</div>

					<button type="submit" style="width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; font-size: 14px; margin-bottom: 16px;">Create Account</button>

					<p style="text-align: center; color: #6b7280; font-size: 14px; margin: 0;">Already have an account? <a href="<?php echo esc_url( wp_login_url() ); ?>" style="color: #667eea; text-decoration: none;">Log in</a></p>
				</form>
			</div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}
