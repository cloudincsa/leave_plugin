<?php
/**
 * Email Templates Page - Leave Manager v3.0
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Available email templates
$templates = array(
	'welcome' => array(
		'name' => 'Welcome Email',
		'description' => 'Sent when a new employee account is created',
		'variables' => array( 'full_name', 'email', 'department', 'position', 'organization_name', 'organization_website' ),
	),
	'leave-request' => array(
		'name' => 'Leave Request Notification',
		'description' => 'Sent to HR/Admin when a new leave request is submitted',
		'variables' => array( 'full_name', 'email', 'department', 'leave_type', 'start_date', 'end_date', 'reason', 'organization_name' ),
	),
	'leave-approval' => array(
		'name' => 'Leave Approval',
		'description' => 'Sent to employee when their leave request is approved',
		'variables' => array( 'full_name', 'leave_type', 'start_date', 'end_date', 'approval_date', 'organization_name' ),
	),
	'leave-rejection' => array(
		'name' => 'Leave Rejection',
		'description' => 'Sent to employee when their leave request is rejected',
		'variables' => array( 'full_name', 'leave_type', 'start_date', 'end_date', 'rejection_reason', 'organization_name' ),
	),
	'password-reset' => array(
		'name' => 'Password Reset',
		'description' => 'Sent when a user requests a password reset',
		'variables' => array( 'full_name', 'reset_link', 'organization_name' ),
	),
);

// Handle template preview
$preview_template = isset( $_GET['preview'] ) ? sanitize_text_field( $_GET['preview'] ) : '';
$edit_template = isset( $_GET['edit'] ) ? sanitize_text_field( $_GET['edit'] ) : '';

// Sample data for preview
$sample_data = array(
	'full_name' => 'John Doe',
	'email' => 'john.doe@example.com',
	'department' => 'Engineering',
	'position' => 'Senior Developer',
	'organization_name' => 'Little Falls Christian Centre',
	'organization_website' => 'https://www.littlefalls.co.za',
	'organization_email' => 'hr@littlefalls.co.za',
	'leave_type' => 'Annual Leave',
	'start_date' => date( 'Y-m-d', strtotime( '+7 days' ) ),
	'end_date' => date( 'Y-m-d', strtotime( '+14 days' ) ),
	'reason' => 'Family vacation',
	'approval_date' => date( 'Y-m-d' ),
	'rejection_reason' => 'Insufficient leave balance',
	'reset_link' => 'https://example.com/reset-password?token=abc123',
);
?>

<div class="wrap leave_manager-wrap">
	<h1>Email Templates</h1>
	
	<?php if ( $preview_template && isset( $templates[ $preview_template ] ) ) : ?>
		<!-- Template Preview -->
		<div class="leave-manager-template-preview">
			<div class="leave-manager-template-preview-header">
				<h2 style="margin: 0;">Preview: <?php echo esc_html( $templates[ $preview_template ]['name'] ); ?></h2>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-templates' ) ); ?>" class="leave-manager-btn leave_manager-btn-secondary">
					← Back to Templates
				</a>
			</div>
			
			<div class="leave-manager-template-preview-content">
				<?php
				$template_file = LEAVE_MANAGER_PLUGIN_DIR . 'templates/emails/' . $preview_template . '.html';
				if ( file_exists( $template_file ) ) {
					$content = file_get_contents( $template_file );
					// Remove subject line
					$content = preg_replace( '/<subject>.*?<\/subject>/s', '', $content );
					// Replace variables with sample data
					foreach ( $sample_data as $key => $value ) {
						$content = str_replace( '{{' . $key . '}}', $value, $content );
					}
					echo wp_kses_post( $content );
				} else {
					echo '<p style="color: #666;">Template file not found: ' . esc_html( $template_file ) . '</p>';
				}
				?>
			</div>
		</div>
		
	<?php elseif ( $edit_template && isset( $templates[ $edit_template ] ) ) : ?>
		<!-- Template Edit -->
		<div class="leave-manager-card">
			<div class="leave-manager-card-header-flex">
				<h3>Edit: <?php echo esc_html( $templates[ $edit_template ]['name'] ); ?></h3>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-templates' ) ); ?>" class="leave-manager-btn leave_manager-btn-secondary leave_manager-btn-sm">
					← Back
				</a>
			</div>
			
			<div class="leave-manager-alert leave_manager-alert-info">
				<strong>Available Variables:</strong> 
				<?php foreach ( $templates[ $edit_template ]['variables'] as $var ) : ?>
					<code style="margin-left: 5px;">{{<?php echo esc_html( $var ); ?>}}</code>
				<?php endforeach; ?>
			</div>
			
			<form method="post" class="leave-manager-form" style="margin-top: 20px;">
				<?php wp_nonce_field( 'leave_manager_edit_template', 'leave_manager_nonce' ); ?>
				<input type="hidden" name="template_key" value="<?php echo esc_attr( $edit_template ); ?>">
				
				<div class="leave-manager-form-group">
					<label class="leave-manager-form-label">Email Subject</label>
					<input type="text" name="template_subject" class="leave-manager-form-input" 
						value="<?php 
						$template_file = LEAVE_MANAGER_PLUGIN_DIR . 'templates/emails/' . $edit_template . '.html';
						if ( file_exists( $template_file ) ) {
							$content = file_get_contents( $template_file );
							preg_match( '/<subject>(.*?)<\/subject>/s', $content, $matches );
							echo esc_attr( isset( $matches[1] ) ? trim( $matches[1] ) : '' );
						}
						?>"
						placeholder="Email subject line">
				</div>
				
				<div class="leave-manager-form-group">
					<label class="leave-manager-form-label">Email Body (HTML)</label>
					<textarea name="template_body" class="leave-manager-form-textarea" rows="15" style="font-family: monospace; font-size: 13px;"><?php
					if ( file_exists( $template_file ) ) {
						$content = file_get_contents( $template_file );
						// Remove subject tags
						$content = preg_replace( '/<subject>.*?<\/subject>/s', '', $content );
						echo esc_textarea( trim( $content ) );
					}
					?></textarea>
					<span class="leave-manager-form-help">Use HTML for formatting. Variables like {{full_name}} will be replaced with actual values.</span>
				</div>
				
				<div class="leave-manager-btn-group">
					<button type="submit" class="leave-manager-btn leave_manager-btn-primary">Save Template</button>
					<a href="<?php echo esc_url( add_query_arg( 'preview', $edit_template, admin_url( 'admin.php?page=leave-manager-templates' ) ) ); ?>" class="leave-manager-btn leave_manager-btn-secondary">
						Preview
					</a>
				</div>
			</form>
		</div>
		
	<?php else : ?>
		<!-- Template List -->
		<p style="color: var(--leave_manager-text-medium); margin-bottom: 20px;">
			Customize the email templates sent by the Leave Management system. Click Preview to see how the email looks, or Edit to modify the content.
		</p>
		
		<div class="leave-manager-templates-grid">
			<?php foreach ( $templates as $template_key => $template_data ) : ?>
				<div class="leave-manager-template-card">
					<div class="leave-manager-template-header">
						<h3><?php echo esc_html( $template_data['name'] ); ?></h3>
					</div>
					<div class="leave-manager-template-body">
						<p class="leave-manager-template-description"><?php echo esc_html( $template_data['description'] ); ?></p>
						
						<div class="leave-manager-template-variables">
							<p class="leave-manager-template-variables-title">Available Variables:</p>
							<div class="leave-manager-template-variables-list">
								<?php foreach ( $template_data['variables'] as $var ) : ?>
									<span class="leave-manager-template-var">{{<?php echo esc_html( $var ); ?>}}</span>
								<?php endforeach; ?>
							</div>
						</div>
						
						<div class="leave-manager-template-actions">
							<a href="<?php echo esc_url( add_query_arg( 'preview', $template_key, admin_url( 'admin.php?page=leave-manager-templates' ) ) ); ?>" class="leave-manager-btn leave_manager-btn-secondary leave_manager-btn-sm">
								Preview
							</a>
							<a href="<?php echo esc_url( add_query_arg( 'edit', $template_key, admin_url( 'admin.php?page=leave-manager-templates' ) ) ); ?>" class="leave-manager-btn leave_manager-btn-primary leave_manager-btn-sm">
								Edit
							</a>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
