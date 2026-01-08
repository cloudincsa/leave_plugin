<?php
/**
 * Branding Settings Admin Page
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user capabilities
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( 'Unauthorized' );
}

// Get branding instance
$branding = new Leave_Manager_Branding();
$settings = $branding->get_settings();
$color_palette = $branding->get_color_palette();

// Handle form submission
if ( isset( $_POST['leave_manager_branding_nonce'] ) && wp_verify_nonce( $_POST['leave_manager_branding_nonce'], 'leave_manager_branding_action' ) ) {
	if ( isset( $_POST['reset_branding'] ) ) {
		$branding->reset_to_defaults();
		echo '<div class="notice notice-success"><p>Branding settings reset to defaults.</p></div>';
		$settings = $branding->get_settings();
	} elseif ( isset( $_POST['save_branding'] ) ) {
		$new_settings = array();

		// Sanitize color inputs
		foreach ( $color_palette as $color ) {
			if ( isset( $_POST[ $color['key'] ] ) ) {
				$new_settings[ $color['key'] ] = sanitize_hex_color( $_POST[ $color['key'] ] );
			}
		}

		// Sanitize other inputs
		if ( isset( $_POST['font_family'] ) ) {
			$new_settings['font_family'] = sanitize_text_field( $_POST['font_family'] );
		}

		if ( isset( $_POST['border_radius'] ) ) {
			$new_settings['border_radius'] = absint( $_POST['border_radius'] );
		}

		if ( isset( $_POST['shadow_intensity'] ) ) {
			$new_settings['shadow_intensity'] = sanitize_text_field( $_POST['shadow_intensity'] );
		}

		if ( isset( $_POST['enable_dark_mode'] ) ) {
			$new_settings['enable_dark_mode'] = true;
		} else {
			$new_settings['enable_dark_mode'] = false;
		}

		// Handle logo upload
		if ( ! empty( $_FILES['logo_file']['name'] ) ) {
			$upload = wp_handle_upload( $_FILES['logo_file'], array( 'test_form' => false ) );
			if ( ! isset( $upload['error'] ) ) {
				$new_settings['logo_url'] = $upload['url'];
			}
		}

		// Handle favicon upload
		if ( ! empty( $_FILES['favicon_file']['name'] ) ) {
			$upload = wp_handle_upload( $_FILES['favicon_file'], array( 'test_form' => false ) );
			if ( ! isset( $upload['error'] ) ) {
				$new_settings['favicon_url'] = $upload['url'];
			}
		}

		$branding->update_settings( $new_settings );
		echo '<div class="notice notice-success"><p>Branding settings saved successfully.</p></div>';
		$settings = $branding->get_settings();
	}
}
?>

<div class="wrap leave-manager-branding-page">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="branding-container">
		<!-- Tabs -->
		<div class="branding-tabs">
			<button class="tab-button active" data-tab="colors">Colors</button>
			<button class="tab-button" data-tab="typography">Typography</button>
			<button class="tab-button" data-tab="design">Design System</button>
			<button class="tab-button" data-tab="branding">Branding Assets</button>
		</div>

		<form method="POST" enctype="multipart/form-data" class="branding-form">
			<?php wp_nonce_field( 'leave_manager_branding_action', 'leave_manager_branding_nonce' ); ?>

			<!-- Colors Tab -->
			<div class="tab-content active" id="colors-tab">
				<h2>Color Palette</h2>
				<p>Customize the colors used throughout the Leave Manager interface.</p>

				<div class="color-grid">
					<?php foreach ( $color_palette as $color ) : ?>
						<div class="color-field">
							<label for="<?php echo esc_attr( $color['key'] ); ?>">
								<?php echo esc_html( $color['label'] ); ?>
							</label>
							<p class="description"><?php echo esc_html( $color['description'] ); ?></p>
							<div class="color-input-wrapper">
								<input 
									type="color" 
									id="<?php echo esc_attr( $color['key'] ); ?>" 
									name="<?php echo esc_attr( $color['key'] ); ?>" 
									value="<?php echo esc_attr( $color['value'] ); ?>"
									class="color-picker"
								>
								<input 
									type="text" 
									name="<?php echo esc_attr( $color['key'] ); ?>_text" 
									value="<?php echo esc_attr( $color['value'] ); ?>"
									class="color-text-input"
									readonly
								>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Typography Tab -->
			<div class="tab-content" id="typography-tab">
				<h2>Typography Settings</h2>
				<p>Customize the fonts and text styling used throughout the interface.</p>

				<div class="form-group">
					<label for="font_family">
						<strong>Font Family</strong>
					</label>
					<p class="description">Enter a comma-separated list of fonts. Example: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif</p>
					<textarea 
						id="font_family" 
						name="font_family" 
						rows="3"
						class="large-text"
					><?php echo esc_textarea( $settings['font_family'] ); ?></textarea>
				</div>

				<div class="form-group">
					<label for="text_color">
						<strong>Primary Text Color</strong>
					</label>
					<div class="color-input-wrapper">
						<input 
							type="color" 
							id="text_color" 
							name="text_color" 
							value="<?php echo esc_attr( $settings['text_color'] ); ?>"
							class="color-picker"
						>
						<input 
							type="text" 
							name="text_color_text" 
							value="<?php echo esc_attr( $settings['text_color'] ); ?>"
							class="color-text-input"
							readonly
						>
					</div>
				</div>

				<div class="form-group">
					<label for="text_muted_color">
						<strong>Secondary Text Color</strong>
					</label>
					<div class="color-input-wrapper">
						<input 
							type="color" 
							id="text_muted_color" 
							name="text_muted_color" 
							value="<?php echo esc_attr( $settings['text_muted_color'] ); ?>"
							class="color-picker"
						>
						<input 
							type="text" 
							name="text_muted_color_text" 
							value="<?php echo esc_attr( $settings['text_muted_color'] ); ?>"
							class="color-text-input"
							readonly
						>
					</div>
				</div>
			</div>

			<!-- Design System Tab -->
			<div class="tab-content" id="design-tab">
				<h2>Design System Settings</h2>
				<p>Customize the design system parameters like border radius and shadow intensity.</p>

				<div class="form-group">
					<label for="border_radius">
						<strong>Border Radius (px)</strong>
					</label>
					<p class="description">Controls the roundness of corners on buttons, cards, and inputs. Range: 0-20px</p>
					<input 
						type="number" 
						id="border_radius" 
						name="border_radius" 
						value="<?php echo esc_attr( $settings['border_radius'] ); ?>"
						min="0"
						max="20"
						class="small-text"
					>
					<span class="preview-radius" style="border-radius: <?php echo esc_attr( $settings['border_radius'] ); ?>px;">Preview</span>
				</div>

				<div class="form-group">
					<label for="shadow_intensity">
						<strong>Shadow Intensity</strong>
					</label>
					<p class="description">Controls the depth of shadows on elements.</p>
					<select id="shadow_intensity" name="shadow_intensity">
						<option value="light" <?php selected( $settings['shadow_intensity'], 'light' ); ?>>Light</option>
						<option value="medium" <?php selected( $settings['shadow_intensity'], 'medium' ); ?>>Medium</option>
						<option value="heavy" <?php selected( $settings['shadow_intensity'], 'heavy' ); ?>>Heavy</option>
					</select>
				</div>

				<div class="form-group">
					<label for="enable_dark_mode">
						<input 
							type="checkbox" 
							id="enable_dark_mode" 
							name="enable_dark_mode" 
							value="1"
							<?php checked( $settings['enable_dark_mode'], true ); ?>
						>
						<strong>Enable Dark Mode Support</strong>
					</label>
					<p class="description">Allow users to switch to dark mode if their system preference is set.</p>
				</div>
			</div>

			<!-- Branding Assets Tab -->
			<div class="tab-content" id="branding-tab">
				<h2>Branding Assets</h2>
				<p>Upload your logo and favicon for the Leave Manager interface.</p>

				<div class="form-group">
					<label for="logo_file">
						<strong>Logo</strong>
					</label>
					<p class="description">Upload your company logo. Recommended size: 200x50px</p>
					<?php if ( ! empty( $settings['logo_url'] ) ) : ?>
						<div class="logo-preview">
							<img src="<?php echo esc_url( $settings['logo_url'] ); ?>" alt="Logo">
							<p><a href="#" class="remove-logo">Remove Logo</a></p>
						</div>
					<?php endif; ?>
					<input 
						type="file" 
						id="logo_file" 
						name="logo_file" 
						accept="image/*"
					>
				</div>

				<div class="form-group">
					<label for="favicon_file">
						<strong>Favicon</strong>
					</label>
					<p class="description">Upload your favicon. Recommended size: 32x32px (PNG or ICO)</p>
					<?php if ( ! empty( $settings['favicon_url'] ) ) : ?>
						<div class="favicon-preview">
							<img src="<?php echo esc_url( $settings['favicon_url'] ); ?>" alt="Favicon">
							<p><a href="#" class="remove-favicon">Remove Favicon</a></p>
						</div>
					<?php endif; ?>
					<input 
						type="file" 
						id="favicon_file" 
						name="favicon_file" 
						accept="image/*"
					>
				</div>
			</div>

			<!-- Form Actions -->
			<div class="form-actions">
				<button type="submit" name="save_branding" class="button button-primary">
					Save Branding Settings
				</button>
				<button type="submit" name="reset_branding" class="button" onclick="return confirm('Are you sure you want to reset all branding settings to defaults?');">
					Reset to Defaults
				</button>
			</div>
		</form>
	</div>
</div>

<style>
	.leave-manager-branding-page {
		max-width: 1200px;
		margin: 20px auto;
	}

	.branding-container {
		background: white;
		border-radius: 8px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
		padding: 20px;
	}

	.branding-tabs {
		display: flex;
		gap: 10px;
		margin-bottom: 30px;
		border-bottom: 2px solid #e0e0e0;
	}

	.tab-button {
		padding: 12px 20px;
		background: none;
		border: none;
		border-bottom: 3px solid transparent;
		cursor: pointer;
		font-size: 14px;
		font-weight: 600;
		color: #666;
		transition: all 0.3s;
	}

	.tab-button:hover {
		color: #333;
	}

	.tab-button.active {
		color: #4A5FFF;
		border-bottom-color: #4A5FFF;
	}

	.tab-content {
		display: none;
		animation: fadeIn 0.3s;
	}

	.tab-content.active {
		display: block;
	}

	@keyframes fadeIn {
		from { opacity: 0; }
		to { opacity: 1; }
	}

	.color-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		gap: 30px;
		margin-bottom: 30px;
	}

	.color-field {
		padding: 20px;
		background: #f9f9f9;
		border-radius: 8px;
		border: 1px solid #e0e0e0;
	}

	.color-field label {
		display: block;
		font-weight: 600;
		margin-bottom: 5px;
		color: #333;
	}

	.color-field .description {
		font-size: 12px;
		color: #666;
		margin-bottom: 15px;
	}

	.color-input-wrapper {
		display: flex;
		gap: 10px;
		align-items: center;
	}

	.color-picker {
		width: 60px;
		height: 40px;
		border: 1px solid #ddd;
		border-radius: 4px;
		cursor: pointer;
	}

	.color-text-input {
		flex: 1;
		padding: 8px 12px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-family: monospace;
		font-size: 12px;
	}

	.form-group {
		margin-bottom: 25px;
	}

	.form-group label {
		display: block;
		margin-bottom: 8px;
		color: #333;
	}

	.form-group .description {
		font-size: 12px;
		color: #666;
		margin-bottom: 10px;
	}

	.form-group input[type="text"],
	.form-group input[type="number"],
	.form-group textarea,
	.form-group select {
		width: 100%;
		max-width: 400px;
		padding: 8px 12px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-family: inherit;
	}

	.form-group textarea {
		max-width: 100%;
		font-family: monospace;
		font-size: 12px;
	}

	.preview-radius {
		display: inline-block;
		width: 60px;
		height: 40px;
		background: #4A5FFF;
		margin-left: 10px;
		vertical-align: middle;
	}

	.logo-preview,
	.favicon-preview {
		margin-bottom: 15px;
		padding: 15px;
		background: #f9f9f9;
		border-radius: 4px;
		border: 1px solid #e0e0e0;
	}

	.logo-preview img {
		max-width: 200px;
		max-height: 100px;
		display: block;
		margin-bottom: 10px;
	}

	.favicon-preview img {
		max-width: 50px;
		max-height: 50px;
		display: block;
		margin-bottom: 10px;
	}

	.form-actions {
		display: flex;
		gap: 10px;
		margin-top: 30px;
		padding-top: 20px;
		border-top: 1px solid #e0e0e0;
	}

	.form-actions button {
		padding: 10px 20px;
		font-size: 14px;
		border-radius: 4px;
		cursor: pointer;
		transition: all 0.3s;
	}

	.form-actions .button-primary {
		background: #4A5FFF;
		color: #333;
		border: none;
	}

	.form-actions .button-primary:hover {
		background: #ff9800;
	}

	.form-actions .button {
		background: white;
		color: #333;
		border: 1px solid #ddd;
	}

	.form-actions .button:hover {
		background: #f9f9f9;
	}
</style>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Tab switching
		const tabButtons = document.querySelectorAll('.tab-button');
		const tabContents = document.querySelectorAll('.tab-content');

		tabButtons.forEach(button => {
			button.addEventListener('click', function() {
				const tabName = this.dataset.tab;
				
				// Remove active class from all
				tabButtons.forEach(b => b.classList.remove('active'));
				tabContents.forEach(c => c.classList.remove('active'));
				
				// Add active class to clicked
				this.classList.add('active');
				document.getElementById(tabName + '-tab').classList.add('active');
			});
		});

		// Color picker sync
		const colorPickers = document.querySelectorAll('.color-picker');
		colorPickers.forEach(picker => {
			const textInput = picker.parentElement.querySelector('.color-text-input');
			if (textInput) {
				picker.addEventListener('change', function() {
					textInput.value = this.value;
				});
			}
		});
	});
</script>
