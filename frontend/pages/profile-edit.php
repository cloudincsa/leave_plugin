<?php
/**
 * Staff Profile Edit Page
 * Allows staff to edit their own profile information
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get current user
$current_user = wp_get_current_user();
if ( ! $current_user->ID ) {
	wp_redirect( wp_login_url() );
	exit;
}

// Get user leave data
global $wpdb;
$user_data = $wpdb->get_row( $wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}leave_manager_leave_users WHERE email = %s",
	$current_user->user_email
) );

if ( ! $user_data ) {
	echo '<div class="leave-manager-page-wrapper"><div style="padding: 40px; text-align: center;"><h2>User information not found.</h2><p>Please contact your administrator.</p></div></div>';
	return;
}

// Get nonce for security
$nonce = wp_create_nonce( 'leave_manager_update_profile' );
?>

<div class="leave-manager-page-wrapper">
	<div class="profile-edit-container">
		<div class="profile-edit-header">
			<h1><?php _e( 'Edit Your Profile', 'leave-manager' ); ?></h1>
			<p class="subtitle"><?php _e( 'Update your personal information', 'leave-manager' ); ?></p>
		</div>

		<div class="profile-edit-content">
			<!-- Editable Section -->
			<div class="profile-section editable-section">
				<h2><?php _e( 'Personal Information', 'leave-manager' ); ?></h2>
				<p class="section-description"><?php _e( 'You can edit the following information', 'leave-manager' ); ?></p>

				<form id="profile-edit-form" class="profile-form">
					<input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>">
					<input type="hidden" name="user_id" value="<?php echo esc_attr( $user_data->id ); ?>">

					<!-- First Name -->
					<div class="form-group">
						<label for="first_name"><?php _e( 'First Name', 'leave-manager' ); ?> <span class="required">*</span></label>
						<input 
							type="text" 
							id="first_name" 
							name="first_name" 
							value="<?php echo esc_attr( $user_data->first_name ); ?>"
							placeholder="<?php _e( 'Enter your first name', 'leave-manager' ); ?>"
							required
						>
						<small class="form-help"><?php _e( 'Your first name as it appears in the system', 'leave-manager' ); ?></small>
					</div>

					<!-- Last Name -->
					<div class="form-group">
						<label for="last_name"><?php _e( 'Last Name', 'leave-manager' ); ?> <span class="required">*</span></label>
						<input 
							type="text" 
							id="last_name" 
							name="last_name" 
							value="<?php echo esc_attr( $user_data->last_name ); ?>"
							placeholder="<?php _e( 'Enter your last name', 'leave-manager' ); ?>"
							required
						>
						<small class="form-help"><?php _e( 'Your last name as it appears in the system', 'leave-manager' ); ?></small>
					</div>

					<!-- Email -->
					<div class="form-group">
						<label for="email"><?php _e( 'Email Address', 'leave-manager' ); ?> <span class="required">*</span></label>
						<input 
							type="email" 
							id="email" 
							name="email" 
							value="<?php echo esc_attr( $user_data->email ); ?>"
							placeholder="<?php _e( 'Enter your email address', 'leave-manager' ); ?>"
							required
						>
						<small class="form-help"><?php _e( 'Your email address for notifications and communications', 'leave-manager' ); ?></small>
					</div>

					<!-- Phone -->
					<div class="form-group">
						<label for="phone"><?php _e( 'Phone Number', 'leave-manager' ); ?></label>
						<input 
							type="tel" 
							id="phone" 
							name="phone" 
							value="<?php echo esc_attr( $user_data->phone ); ?>"
							placeholder="<?php _e( 'Enter your phone number', 'leave-manager' ); ?>"
						>
						<small class="form-help"><?php _e( 'Your contact phone number', 'leave-manager' ); ?></small>
					</div>

					<!-- Form Actions -->
					<div class="form-actions">
						<button type="submit" class="btn btn-primary" id="save-profile-btn">
							<?php _e( 'Save Changes', 'leave-manager' ); ?>
						</button>
						<button type="reset" class="btn btn-secondary">
							<?php _e( 'Cancel', 'leave-manager' ); ?>
						</button>
					</div>

					<!-- Status Messages -->
					<div id="profile-message" class="profile-message" style="display: none;"></div>
				</form>
			</div>

			<!-- Read-Only Section -->
			<div class="profile-section readonly-section">
				<h2><?php _e( 'Account Information', 'leave-manager' ); ?></h2>
				<p class="section-description"><?php _e( 'The following information is managed by your administrator', 'leave-manager' ); ?></p>

				<div class="readonly-fields">
					<!-- Department -->
					<div class="readonly-field">
						<label><?php _e( 'Department', 'leave-manager' ); ?></label>
						<p class="field-value"><?php echo esc_html( $user_data->department ?: __( 'Not assigned', 'leave-manager' ) ); ?></p>
					</div>

					<!-- Position -->
					<div class="readonly-field">
						<label><?php _e( 'Position', 'leave-manager' ); ?></label>
						<p class="field-value"><?php echo esc_html( $user_data->position ?: __( 'Not assigned', 'leave-manager' ) ); ?></p>
					</div>

					<!-- Manager -->
					<div class="readonly-field">
						<label><?php _e( 'Manager', 'leave-manager' ); ?></label>
						<p class="field-value">
							<?php
							if ( $user_data->manager_id ) {
								$manager = $wpdb->get_row( $wpdb->prepare(
									"SELECT * FROM {$wpdb->prefix}leave_manager_leave_users WHERE id = %d",
									$user_data->manager_id
								) );
								echo esc_html( $manager ? $manager->first_name . ' ' . $manager->last_name : __( 'Not assigned', 'leave-manager' ) );
							} else {
								echo esc_html( __( 'Not assigned', 'leave-manager' ) );
							}
							?>
						</p>
					</div>

					<!-- Role -->
					<div class="readonly-field">
						<label><?php _e( 'Role', 'leave-manager' ); ?></label>
						<p class="field-value"><?php echo esc_html( ucfirst( $user_data->role ) ); ?></p>
					</div>

					<!-- Status -->
					<div class="readonly-field">
						<label><?php _e( 'Account Status', 'leave-manager' ); ?></label>
						<p class="field-value">
							<span class="status-badge status-<?php echo esc_attr( $user_data->status ); ?>">
								<?php echo esc_html( ucfirst( $user_data->status ) ); ?>
							</span>
						</p>
					</div>
				</div>

				<!-- Contact Admin -->
				<div class="contact-admin-section">
					<p><?php _e( 'Need to update your department, position, or other account settings?', 'leave-manager' ); ?></p>
					<p><?php _e( 'Please contact your administrator or HR department.', 'leave-manager' ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
.profile-edit-container {
	max-width: 900px;
	margin: 0 auto;
	padding: 20px;
}

.profile-edit-header {
	margin-bottom: 40px;
	border-bottom: 2px solid #e0e0e0;
	padding-bottom: 20px;
}

.profile-edit-header h1 {
	margin: 0 0 10px 0;
	font-size: 32px;
	color: #333;
}

.profile-edit-header .subtitle {
	margin: 0;
	color: #666;
	font-size: 16px;
}

.profile-edit-content {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 30px;
	margin-top: 30px;
}

@media (max-width: 768px) {
	.profile-edit-content {
		grid-template-columns: 1fr;
	}
}

.profile-section {
	background: #f9f9f9;
	border: 1px solid #e0e0e0;
	border-radius: 8px;
	padding: 25px;
}

.profile-section h2 {
	margin: 0 0 10px 0;
	font-size: 20px;
	color: #333;
}

.section-description {
	margin: 0 0 20px 0;
	color: #666;
	font-size: 14px;
}

.form-group {
	margin-bottom: 20px;
}

.form-group label {
	display: block;
	margin-bottom: 8px;
	font-weight: 600;
	color: #333;
	font-size: 14px;
}

.form-group label .required {
	color: #e74c3c;
}

.form-group input {
	width: 100%;
	padding: 10px 12px;
	border: 1px solid #ddd;
	border-radius: 4px;
	font-size: 14px;
	font-family: inherit;
	transition: border-color 0.3s;
}

.form-group input:focus {
	outline: none;
	border-color: #3498db;
	box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
}

.form-help {
	display: block;
	margin-top: 5px;
	color: #999;
	font-size: 12px;
}

.form-actions {
	display: flex;
	gap: 10px;
	margin-top: 25px;
}

.btn {
	padding: 10px 20px;
	border: none;
	border-radius: 4px;
	font-size: 14px;
	font-weight: 600;
	cursor: pointer;
	transition: all 0.3s;
}

.btn-primary {
	background: #3498db;
	color: white;
}

.btn-primary:hover {
	background: #2980b9;
}

.btn-secondary {
	background: #ecf0f1;
	color: #333;
}

.btn-secondary:hover {
	background: #bdc3c7;
}

.profile-message {
	margin-top: 15px;
	padding: 12px 15px;
	border-radius: 4px;
	font-size: 14px;
}

.profile-message.success {
	background: #d4edda;
	color: #155724;
	border: 1px solid #c3e6cb;
}

.profile-message.error {
	background: #f8d7da;
	color: #721c24;
	border: 1px solid #f5c6cb;
}

.readonly-fields {
	display: flex;
	flex-direction: column;
	gap: 15px;
}

.readonly-field {
	padding: 12px 0;
	border-bottom: 1px solid #e0e0e0;
}

.readonly-field:last-child {
	border-bottom: none;
}

.readonly-field label {
	display: block;
	font-weight: 600;
	color: #333;
	font-size: 13px;
	margin-bottom: 5px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.field-value {
	margin: 0;
	color: #555;
	font-size: 15px;
}

.status-badge {
	display: inline-block;
	padding: 4px 12px;
	border-radius: 20px;
	font-size: 12px;
	font-weight: 600;
	text-transform: uppercase;
}

.status-active {
	background: #d4edda;
	color: #155724;
}

.status-inactive {
	background: #f8d7da;
	color: #721c24;
}

.contact-admin-section {
	margin-top: 20px;
	padding-top: 20px;
	border-top: 1px solid #e0e0e0;
}

.contact-admin-section p {
	margin: 8px 0;
	color: #666;
	font-size: 14px;
}
</style>

<script>
jQuery(document).ready(function($) {
	$('#profile-edit-form').on('submit', function(e) {
		e.preventDefault();

		const $form = $(this);
		const $submitBtn = $('#save-profile-btn');
		const $message = $('#profile-message');

		// Disable button and show loading state
		$submitBtn.prop('disabled', true).text('<?php _e( 'Saving...', 'leave-manager' ); ?>');

		// Prepare data
		const data = {
			action: 'leave_manager_update_profile',
			nonce: $form.find('input[name="nonce"]').val(),
			user_id: $form.find('input[name="user_id"]').val(),
			first_name: $form.find('#first_name').val(),
			last_name: $form.find('#last_name').val(),
			email: $form.find('#email').val(),
			phone: $form.find('#phone').val(),
		};

		// Send AJAX request
		$.post(ajaxurl, data, function(response) {
			$submitBtn.prop('disabled', false).text('<?php _e( 'Save Changes', 'leave-manager' ); ?>');

			if (response.success) {
				$message.removeClass('error').addClass('success').text(response.data.message).show();
				setTimeout(function() {
					$message.fadeOut();
				}, 3000);
			} else {
				$message.removeClass('success').addClass('error').text(response.data.message).show();
			}
		}).fail(function() {
			$submitBtn.prop('disabled', false).text('<?php _e( 'Save Changes', 'leave-manager' ); ?>');
			$message.removeClass('success').addClass('error').text('<?php _e( 'An error occurred. Please try again.', 'leave-manager' ); ?>').show();
		});
	});
});
</script>
