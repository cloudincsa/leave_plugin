<?php
/**
 * Email Templates Page
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized' );
}

// Get instances
$db = new Leave_Manager_Database();
$logger = new Leave_Manager_Logger( $db );
$settings = new Leave_Manager_Settings( $logger );
$email_handler = new Leave_Manager_Email_Handler( $db, $logger, $settings );

// Available email templates
$templates = array(
	'welcome' => array(
		'name' => 'Welcome Email',
		'description' => 'Sent when a new user account is created',
		'variables' => array( 'full_name', 'email', 'department', 'position', 'organization_website', 'organization_email', 'organization_name' ),
	),
	'leave-request' => array(
		'name' => 'Leave Request Notification',
		'description' => 'Sent to HR when a new leave request is submitted',
		'variables' => array( 'full_name', 'email', 'department', 'leave_type', 'start_date', 'end_date', 'reason', 'organization_website', 'organization_name' ),
	),
	'leave-approval' => array(
		'name' => 'Leave Approval Notification',
		'description' => 'Sent to employee when their leave request is approved',
		'variables' => array( 'full_name', 'leave_type', 'start_date', 'end_date', 'approval_date', 'organization_name' ),
	),
	'leave-rejection' => array(
		'name' => 'Leave Rejection Notification',
		'description' => 'Sent to employee when their leave request is rejected',
		'variables' => array( 'full_name', 'leave_type', 'start_date', 'end_date', 'rejection_reason', 'organization_name' ),
	),
	'password-reset' => array(
		'name' => 'Password Reset Email',
		'description' => 'Sent when a user requests a password reset',
		'variables' => array( 'full_name', 'reset_link', 'organization_name' ),
	),
);

// Handle template preview
$preview_template = isset( $_GET['preview'] ) ? sanitize_text_field( $_GET['preview'] ) : '';
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="email-templates-container">
		<h2>Available Email Templates</h2>
		<p>These templates are used to send notifications to users. You can preview each template below.</p>

		<div class="templates-grid">
			<?php foreach ( $templates as $template_key => $template_data ) : ?>
				<div class="template-card">
					<h3><?php echo esc_html( $template_data['name'] ); ?></h3>
					<p><?php echo esc_html( $template_data['description'] ); ?></p>
					
					<div class="template-variables">
						<strong>Available Variables:</strong>
						<ul>
							<?php foreach ( $template_data['variables'] as $var ) : ?>
								<li><code>{{<?php echo esc_html( $var ); ?>}}</code></li>
							<?php endforeach; ?>
						</ul>
					</div>

					<div class="template-actions">
						<a href="<?php echo esc_url( add_query_arg( 'preview', $template_key ) ); ?>" class="button">Preview</a>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-email-templates&edit=' . $template_key ) ); ?>" class="button">Edit</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<?php if ( ! empty( $preview_template ) && isset( $templates[ $preview_template ] ) ) : ?>
			<div class="template-preview-section">
				<h2>Preview: <?php echo esc_html( $templates[ $preview_template ]['name'] ); ?></h2>
				
				<div class="template-preview">
					<?php
					$template_file = LEAVE_MANAGER_PLUGIN_DIR . 'templates/emails/' . $preview_template . '.html';
					if ( file_exists( $template_file ) ) {
						$content = file_get_contents( $template_file );
						// Remove subject line
						$content = preg_replace( '/<subject>.*?<\/subject>/s', '', $content );
						// Replace variables with sample data
						$sample_data = array(
							'full_name' => 'John Doe',
							'email' => 'john@example.com',
							'department' => 'Engineering',
							'position' => 'Senior Developer',
							'organization_website' => 'https://example.com',
							'organization_email' => 'hr@example.com',
							'organization_name' => 'Example Organization',
							'leave_type' => 'Annual Leave',
							'start_date' => date( 'Y-m-d', strtotime( '+7 days' ) ),
							'end_date' => date( 'Y-m-d', strtotime( '+14 days' ) ),
							'reason' => 'Vacation',
							'approval_date' => date( 'Y-m-d' ),
							'rejection_reason' => 'Insufficient leave balance',
							'reset_link' => 'https://example.com/reset-password?token=abc123',
						);

						foreach ( $sample_data as $key => $value ) {
							$content = str_replace( '{{' . $key . '}}', $value, $content );
						}

						echo wp_kses_post( $content );
					}
					?>
				</div>

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-email-templates' ) ); ?>" class="button">Back to Templates</a>
			</div>
		<?php endif; ?>
	</div>
</div>

<style>
	.email-templates-container {
		margin-top: 20px;
	}

	.templates-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
		gap: 20px;
		margin: 20px 0;
	}

	.template-card {
		background: white;
		border: 1px solid #ccc;
		border-radius: 5px;
		padding: 20px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.template-card h3 {
		margin-top: 0;
		color: #0073aa;
		border-bottom: 2px solid #0073aa;
		padding-bottom: 10px;
	}

	.template-card p {
		color: #666;
		font-size: 14px;
	}

	.template-variables {
		background-color: #f9f9f9;
		padding: 10px;
		border-radius: 4px;
		margin: 15px 0;
		max-height: 150px;
		overflow-y: auto;
	}

	.template-variables strong {
		display: block;
		margin-bottom: 10px;
	}

	.template-variables ul {
		list-style: none;
		padding: 0;
		margin: 0;
	}

	.template-variables li {
		margin: 5px 0;
		font-size: 12px;
	}

	.template-variables code {
		background-color: #f0f0f0;
		padding: 2px 6px;
		border-radius: 3px;
		font-family: monospace;
	}

	.template-actions {
		display: flex;
		gap: 10px;
		margin-top: 15px;
	}

	.template-actions .button {
		flex: 1;
		text-align: center;
		padding: 8px;
		font-size: 13px;
	}

	.template-preview-section {
		background: white;
		border: 1px solid #ccc;
		border-radius: 5px;
		padding: 20px;
		margin-top: 30px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.template-preview-section h2 {
		margin-top: 0;
		border-bottom: 2px solid #0073aa;
		padding-bottom: 10px;
		color: #0073aa;
	}

	.template-preview {
		background: white;
		border: 1px solid #ddd;
		border-radius: 4px;
		padding: 20px;
		margin: 20px 0;
		max-width: 600px;
	}

	.template-preview img {
		max-width: 100%;
		height: auto;
	}

	.template-preview a {
		color: #0073aa;
		text-decoration: none;
	}

	.template-preview a:hover {
		text-decoration: underline;
	}
</style>
