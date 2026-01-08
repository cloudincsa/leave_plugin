<?php
/**
 * Email Template Editor Page
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

// Get template to edit
$template = isset( $_GET['template'] ) ? sanitize_text_field( $_GET['template'] ) : '';
$message = '';
$error = '';

// Handle template save
if ( isset( $_POST['action'] ) && 'save_template' === $_POST['action'] ) {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_edit_template' ) ) {
		$error = 'Security check failed.';
	} else {
		$template = sanitize_text_field( $_POST['template'] );
		$content = wp_kses_post( $_POST['template_content'] );

		if ( $email_handler->update_template( $template, $content ) ) {
			$message = 'Template saved successfully.';
		} else {
			$error = 'Failed to save template.';
		}
	}
}

// Get template content
if ( ! empty( $template ) ) {
	$template_content = $email_handler->get_template( $template );
	$template_variables = $email_handler->get_template_variables( $template );
}

// Available templates
$templates = array(
	'welcome' => 'Welcome Email',
	'leave-request' => 'Leave Request Notification',
	'leave-approval' => 'Leave Approval Notification',
	'leave-rejection' => 'Leave Rejection Notification',
	'password-reset' => 'Password Reset Email',
);
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $error ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( $error ); ?></p>
		</div>
	<?php endif; ?>

	<div class="email-editor-container">
		<div class="template-selector">
			<h2>Select Template</h2>
			<div class="template-list">
				<?php foreach ( $templates as $key => $name ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'template', $key ) ); ?>" class="template-item <?php echo ( $template === $key ) ? 'active' : ''; ?>">
						<?php echo esc_html( $name ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

		<?php if ( ! empty( $template ) && isset( $template_content ) ) : ?>
			<div class="template-editor">
				<h2>Edit Template: <?php echo esc_html( $templates[ $template ] ?? 'Unknown' ); ?></h2>

				<form method="post" class="editor-form">
					<?php wp_nonce_field( 'leave_manager_edit_template', 'nonce' ); ?>
					<input type="hidden" name="action" value="save_template">
					<input type="hidden" name="template" value="<?php echo esc_attr( $template ); ?>">

					<div class="editor-content">
						<label for="template_content">Template HTML:</label>
						<textarea id="template_content" name="template_content" rows="20" class="widefat"><?php echo esc_textarea( $template_content ); ?></textarea>
					</div>

					<div class="editor-variables">
						<h3>Available Variables</h3>
						<p>Use the following variables in your template. They will be replaced with actual values when the email is sent.</p>
						<div class="variables-list">
							<?php if ( ! empty( $template_variables ) ) : ?>
								<?php foreach ( $template_variables as $var => $description ) : ?>
									<div class="variable-item">
										<code>{{<?php echo esc_html( $var ); ?>}}</code>
										<span class="variable-description"><?php echo esc_html( $description ); ?></span>
									</div>
								<?php endforeach; ?>
							<?php else : ?>
								<p>No variables available for this template.</p>
							<?php endif; ?>
						</div>
					</div>

					<div class="editor-actions">
						<button type="submit" class="button button-primary button-large">Save Template</button>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-email-templates' ) ); ?>" class="button button-secondary">Back to Templates</a>
					</div>
				</form>
			</div>
		<?php else : ?>
			<div class="template-editor">
				<p>Select a template from the list to edit it.</p>
			</div>
		<?php endif; ?>
	</div>
</div>

<style>
	.email-editor-container {
		display: grid;
		grid-template-columns: 250px 1fr;
		gap: 20px;
		margin-top: 20px;
	}

	.template-selector {
		background: white;
		border: 1px solid #ddd;
		border-radius: 5px;
		padding: 20px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
		height: fit-content;
	}

	.template-selector h2 {
		margin-top: 0;
		font-size: 16px;
		border-bottom: 2px solid #0073aa;
		padding-bottom: 10px;
		color: #0073aa;
	}

	.template-list {
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	.template-item {
		display: block;
		padding: 10px 15px;
		border: 1px solid #ddd;
		border-radius: 4px;
		text-decoration: none;
		color: #0073aa;
		transition: all 0.3s;
		cursor: pointer;
	}

	.template-item:hover {
		background-color: #f5f5f5;
		border-color: #0073aa;
	}

	.template-item.active {
		background-color: #0073aa;
		color: white;
		border-color: #0073aa;
		font-weight: 600;
	}

	.template-editor {
		background: white;
		border: 1px solid #ddd;
		border-radius: 5px;
		padding: 20px;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.template-editor h2 {
		margin-top: 0;
		border-bottom: 2px solid #0073aa;
		padding-bottom: 10px;
		color: #0073aa;
	}

	.editor-form {
		display: grid;
		grid-template-columns: 1fr 300px;
		gap: 20px;
	}

	.editor-content {
		display: flex;
		flex-direction: column;
	}

	.editor-content label {
		font-weight: 600;
		margin-bottom: 10px;
	}

	.editor-content textarea {
		font-family: 'Courier New', monospace;
		font-size: 13px;
		padding: 10px;
		border: 1px solid #ddd;
		border-radius: 4px;
	}

	.editor-variables {
		background: #f9f9f9;
		padding: 15px;
		border-radius: 4px;
		border: 1px solid #ddd;
		max-height: 600px;
		overflow-y: auto;
	}

	.editor-variables h3 {
		margin-top: 0;
		font-size: 14px;
		color: #333;
	}

	.editor-variables p {
		font-size: 12px;
		color: #666;
		margin-bottom: 15px;
	}

	.variables-list {
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	.variable-item {
		display: flex;
		flex-direction: column;
		gap: 5px;
		padding: 10px;
		background: white;
		border: 1px solid #ddd;
		border-radius: 4px;
	}

	.variable-item code {
		background-color: #f0f0f0;
		padding: 5px 8px;
		border-radius: 3px;
		font-size: 11px;
		font-weight: 600;
		color: #0073aa;
	}

	.variable-description {
		font-size: 11px;
		color: #666;
	}

	.editor-actions {
		grid-column: 1 / -1;
		display: flex;
		gap: 10px;
		margin-top: 20px;
		padding-top: 20px;
		border-top: 1px solid #ddd;
	}

	.editor-actions .button {
		padding: 10px 20px;
		font-size: 14px;
	}

	@media (max-width: 768px) {
		.email-editor-container {
			grid-template-columns: 1fr;
		}

		.editor-form {
			grid-template-columns: 1fr;
		}

		.editor-variables {
			max-height: none;
		}
	}
</style>
