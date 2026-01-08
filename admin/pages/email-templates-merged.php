<?php
/**
 * Email Templates Management Page (Merged)
 *
 * Combines Email Templates and Email Template Editor functionality
 * into a single page with inline editing capability.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get all email templates
global $wpdb;
$templates_table = $wpdb->prefix . 'leave_manager_email_templates';
$templates = $wpdb->get_results( "SELECT * FROM {$templates_table} ORDER BY template_name ASC" );

// Handle template update
if ( isset( $_POST['leave_manager_update_template'] ) && wp_verify_nonce( $_POST['leave_manager_template_nonce'], 'leave_manager_template_nonce' ) ) {
	$template_id = intval( $_POST['template_id'] );
	$subject = sanitize_text_field( $_POST['template_subject'] );
	$body = wp_kses_post( $_POST['template_body'] );

	$updated = $wpdb->update(
		$templates_table,
		array(
			'subject' => $subject,
			'body'    => $body,
		),
		array( 'id' => $template_id ),
		array( '%s', '%s' ),
		array( '%d' )
	);

	if ( $updated !== false ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Template updated successfully!', 'leave-manager-management' ) . '</p></div>';
	} else {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Error updating template.', 'leave-manager-management' ) . '</p></div>';
	}
}

?>
<div class="wrap">
	<h1><?php esc_html_e( 'Email Templates', 'leave-manager-management' ); ?></h1>
	<p><?php esc_html_e( 'Manage and customize email notification templates. Click on any template to edit it inline.', 'leave-manager-management' ); ?></p>

	<?php if ( ! empty( $templates ) ) : ?>
		<div class="leave-manager-email-templates-container">
			<?php foreach ( $templates as $template ) : ?>
				<div class="leave-manager-template-card">
					<div class="leave-manager-template-header">
						<h3><?php echo esc_html( $template->template_name ); ?></h3>
						<button class="button button-small leave_manager-toggle-edit" data-template-id="<?php echo esc_attr( $template->id ); ?>">
							<?php esc_html_e( 'Edit', 'leave-manager-management' ); ?>
						</button>
					</div>

					<div class="leave-manager-template-preview" id="preview-<?php echo esc_attr( $template->id ); ?>">
						<div class="leave-manager-template-subject">
							<strong><?php esc_html_e( 'Subject:', 'leave-manager-management' ); ?></strong>
							<p><?php echo esc_html( $template->subject ); ?></p>
						</div>
						<div class="leave-manager-template-body">
							<strong><?php esc_html_e( 'Body:', 'leave-manager-management' ); ?></strong>
							<div class="leave-manager-template-body-preview">
								<?php echo wp_kses_post( $template->body ); ?>
							</div>
						</div>
					</div>

					<div class="leave-manager-template-editor" id="editor-<?php echo esc_attr( $template->id ); ?>" style="display: none;">
						<form method="post" action="">
							<?php wp_nonce_field( 'leave_manager_template_nonce', 'leave_manager_template_nonce' ); ?>
							<input type="hidden" name="template_id" value="<?php echo esc_attr( $template->id ); ?>">

							<div class="leave-manager-form-group">
								<label for="subject-<?php echo esc_attr( $template->id ); ?>">
									<?php esc_html_e( 'Email Subject:', 'leave-manager-management' ); ?>
								</label>
								<input type="text" id="subject-<?php echo esc_attr( $template->id ); ?>" name="template_subject" value="<?php echo esc_attr( $template->subject ); ?>" class="regular-text">
							</div>

							<div class="leave-manager-form-group">
								<label for="body-<?php echo esc_attr( $template->id ); ?>">
									<?php esc_html_e( 'Email Body:', 'leave-manager-management' ); ?>
								</label>
								<?php
								wp_editor(
									$template->body,
									'template_body_' . $template->id,
									array(
										'textarea_name' => 'template_body',
										'media_buttons' => false,
										'teeny'         => true,
										'quicktags'     => array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close' ),
									)
								);
								?>
							</div>

							<div class="leave-manager-form-group">
								<p class="description">
									<?php esc_html_e( 'Available variables:', 'leave-manager-management' ); ?>
									<code>{user_name}, {user_email}, {leave_type}, {start_date}, {end_date}, {reason}, {approver_name}</code>
								</p>
							</div>

							<div class="leave-manager-form-actions">
								<button type="submit" name="leave_manager_update_template" class="button button-primary">
									<?php esc_html_e( 'Save Changes', 'leave-manager-management' ); ?>
								</button>
								<button type="button" class="button leave_manager-toggle-edit" data-template-id="<?php echo esc_attr( $template->id ); ?>">
									<?php esc_html_e( 'Cancel', 'leave-manager-management' ); ?>
								</button>
							</div>
						</form>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'No email templates found.', 'leave-manager-management' ); ?></p>
	<?php endif; ?>
</div>

<style>
	.leave-manager-email-templates-container {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
		gap: 20px;
		margin-top: 20px;
	}

	.leave-manager-template-card {
		border: 1px solid #ddd;
		border-radius: 5px;
		padding: 15px;
		background: #fff;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.leave-manager-template-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 15px;
		border-bottom: 1px solid #eee;
		padding-bottom: 10px;
	}

	.leave-manager-template-header h3 {
		margin: 0;
		font-size: 16px;
	}

	.leave-manager-template-preview {
		margin-bottom: 15px;
	}

	.leave-manager-template-subject {
		margin-bottom: 15px;
	}

	.leave-manager-template-subject p {
		margin: 5px 0;
		padding: 10px;
		background: #f5f5f5;
		border-left: 3px solid #0073aa;
	}

	.leave-manager-template-body-preview {
		padding: 10px;
		background: #f9f9f9;
		border: 1px solid #e0e0e0;
		border-radius: 3px;
		max-height: 200px;
		overflow-y: auto;
		font-size: 13px;
		line-height: 1.5;
	}

	.leave-manager-template-editor {
		background: #f9f9f9;
		padding: 15px;
		border-radius: 3px;
		border: 1px solid #ddd;
	}

	.leave-manager-form-group {
		margin-bottom: 15px;
	}

	.leave-manager-form-group label {
		display: block;
		margin-bottom: 5px;
		font-weight: 600;
	}

	.leave-manager-form-group input[type="text"],
	.leave-manager-form-group textarea {
		width: 100%;
		max-width: 100%;
	}

	.leave-manager-form-actions {
		margin-top: 15px;
		padding-top: 15px;
		border-top: 1px solid #ddd;
		display: flex;
		gap: 10px;
	}

	.leave-manager-toggle-edit {
		cursor: pointer;
	}

	@media (max-width: 768px) {
		.leave-manager-email-templates-container {
			grid-template-columns: 1fr;
		}
	}
</style>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		const toggleButtons = document.querySelectorAll('.leave-manager-toggle-edit');

		toggleButtons.forEach(button => {
			button.addEventListener('click', function(e) {
				e.preventDefault();
				const templateId = this.getAttribute('data-template-id');
				const preview = document.getElementById('preview-' + templateId);
				const editor = document.getElementById('editor-' + templateId);

				if (editor.style.display === 'none') {
					preview.style.display = 'none';
					editor.style.display = 'block';
					this.textContent = '<?php esc_html_e( 'Cancel', 'leave-manager-management' ); ?>';
				} else {
					preview.style.display = 'block';
					editor.style.display = 'none';
					this.textContent = '<?php esc_html_e( 'Edit', 'leave-manager-management' ); ?>';
				}
			});
		});
	});
</script>
