<?php
/**
 * Branding Settings Class
 *
 * Manages customizable branding and design system settings
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Branding class
 */
class Leave_Manager_Branding {

	/**
	 * Option key for branding settings
	 *
	 * @var string
	 */
	private $option_key = 'leave_manager_branding';

	/**
	 * Default branding settings
	 *
	 * @var array
	 */
	private $defaults = array(
		'primary_color'       => '#4A5FFF',
		'primary_dark_color'  => '#3A4FE8',
		'primary_light_color' => '#667EEA',
		'accent_color'        => '#764BA2',
		'success_color'       => '#4caf50',
		'error_color'         => '#f44336',
		'warning_color'       => '#ff9800',
		'info_color'          => '#2196f3',
		'text_color'          => '#333333',
		'text_muted_color'    => '#999999',
		'background_color'    => '#ffffff',
		'border_color'        => '#e0e0e0',
		'font_family'         => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif',
		'logo_id'             => 0,
		'favicon_id'          => 0,
		'logo_url'            => '',
		'favicon_url'         => '',
		'enable_dark_mode'    => true,
		'border_radius'       => 8,
		'shadow_intensity'    => 'medium',
		'organization_name'   => '',
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_head', array( $this, 'output_css_variables' ) );
		add_action( 'wp_head', array( $this, 'output_favicon' ) );
		add_action( 'admin_head', array( $this, 'output_favicon' ) );
		add_action( 'wp_ajax_leave_manager_set_logo', array( $this, 'ajax_set_logo' ) );
		add_action( 'wp_ajax_leave_manager_set_favicon', array( $this, 'ajax_set_favicon' ) );
	}

	/**
	 * Register branding settings
	 */
	public function register_settings() {
		register_setting(
			'leave_manager_branding_group',
			$this->option_key,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'leave_manager_branding_section',
			'Branding & Design System',
			array( $this, 'render_section_description' ),
			'leave_manager_branding_group'
		);

		// Enqueue media uploader
		wp_enqueue_media();
	}

	/**
	 * Render section description
	 */
	public function render_section_description() {
		echo '<p>Customize the appearance and branding of your Leave Manager interface.</p>';
	}

	/**
	 * Get branding settings
	 *
	 * @return array Branding settings
	 */
	public function get_settings() {
		$settings = get_option( $this->option_key, array() );
		return wp_parse_args( $settings, $this->defaults );
	}

	/**
	 * Get a single branding setting
	 *
	 * @param string $key Setting key
	 * @param mixed  $default Default value
	 * @return mixed Setting value
	 */
	public function get_setting( $key, $default = null ) {
		$settings = $this->get_settings();
		if ( null === $default ) {
			$default = isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : null;
		}
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}

	/**
	 * Update branding settings
	 *
	 * @param array $settings New settings
	 * @return bool True on success
	 */
	public function update_settings( $settings ) {
		$current = $this->get_settings();
		$merged = wp_parse_args( $settings, $current );
		return update_option( $this->option_key, $merged );
	}

	/**
	 * Sanitize branding settings
	 *
	 * @param array $input Raw input
	 * @return array Sanitized settings
	 */
	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			return $this->defaults;
		}

		$sanitized = array();

		// Color fields
		$color_fields = array(
			'primary_color',
			'primary_dark_color',
			'primary_light_color',
			'accent_color',
			'success_color',
			'error_color',
			'warning_color',
			'info_color',
			'text_color',
			'text_muted_color',
			'background_color',
			'border_color',
		);

		foreach ( $color_fields as $field ) {
			if ( isset( $input[ $field ] ) ) {
				$sanitized[ $field ] = sanitize_hex_color( $input[ $field ] );
				if ( ! $sanitized[ $field ] ) {
					$sanitized[ $field ] = $this->defaults[ $field ];
				}
			}
		}

		// Font family
		if ( isset( $input['font_family'] ) ) {
			$sanitized['font_family'] = sanitize_text_field( $input['font_family'] );
		}

		// Media IDs
		if ( isset( $input['logo_id'] ) ) {
			$sanitized['logo_id'] = absint( $input['logo_id'] );
		}

		if ( isset( $input['favicon_id'] ) ) {
			$sanitized['favicon_id'] = absint( $input['favicon_id'] );
		}

		// Logo and favicon URLs
		if ( isset( $input['logo_url'] ) ) {
			$sanitized['logo_url'] = esc_url_raw( $input['logo_url'] );
		}

		if ( isset( $input['favicon_url'] ) ) {
			$sanitized['favicon_url'] = esc_url_raw( $input['favicon_url'] );
		}

		// Organization name
		if ( isset( $input['organization_name'] ) ) {
			$sanitized['organization_name'] = sanitize_text_field( $input['organization_name'] );
		}

		// Dark mode toggle
		if ( isset( $input['enable_dark_mode'] ) ) {
			$sanitized['enable_dark_mode'] = (bool) $input['enable_dark_mode'];
		}

		// Border radius
		if ( isset( $input['border_radius'] ) ) {
			$sanitized['border_radius'] = absint( $input['border_radius'] );
			$sanitized['border_radius'] = max( 0, min( 20, $sanitized['border_radius'] ) );
		}

		// Shadow intensity
		if ( isset( $input['shadow_intensity'] ) ) {
			$allowed = array( 'light', 'medium', 'heavy' );
			$sanitized['shadow_intensity'] = in_array( $input['shadow_intensity'], $allowed, true ) ? $input['shadow_intensity'] : 'medium';
		}

		return $sanitized;
	}

	/**
	 * Output CSS variables based on branding settings
	 */
	public function output_css_variables() {
		$settings = $this->get_settings();

		// Build CSS variables
		$css = ':root {' . "\n";
		$css .= '  /* Brand Colors */' . "\n";
		$css .= '  --color-primary: ' . $settings['primary_color'] . ';' . "\n";
		$css .= '  --color-primary-dark: ' . $settings['primary_dark_color'] . ';' . "\n";
		$css .= '  --color-primary-light: ' . $settings['primary_light_color'] . ';' . "\n";
		$css .= '  --color-accent-blue: ' . $settings['accent_color'] . ';' . "\n";
		$css .= '  --color-success: ' . $settings['success_color'] . ';' . "\n";
		$css .= '  --color-error: ' . $settings['error_color'] . ';' . "\n";
		$css .= '  --color-warning: ' . $settings['warning_color'] . ';' . "\n";
		$css .= '  --color-info: ' . $settings['info_color'] . ';' . "\n";
		$css .= '  --color-gray-dark: ' . $settings['text_color'] . ';' . "\n";
		$css .= '  --color-gray-medium: ' . $settings['text_muted_color'] . ';' . "\n";
		$css .= '  --color-white: ' . $settings['background_color'] . ';' . "\n";
		$css .= '  /* Typography */' . "\n";
		$css .= '  --font-family: ' . $settings['font_family'] . ';' . "\n";
		$css .= '  /* Border Radius */' . "\n";
		$css .= '  --radius-sm: ' . ( $settings['border_radius'] / 2 ) . 'px;' . "\n";
		$css .= '  --radius-md: ' . ( $settings['border_radius'] / 1.3 ) . 'px;' . "\n";
		$css .= '  --radius-lg: ' . $settings['border_radius'] . 'px;' . "\n";
		$css .= '  --radius-xl: ' . ( $settings['border_radius'] * 1.5 ) . 'px;' . "\n";

		// Shadow intensity
		switch ( $settings['shadow_intensity'] ) {
			case 'light':
				$css .= '  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);' . "\n";
				$css .= '  --shadow-md: 0 2px 4px rgba(0, 0, 0, 0.08);' . "\n";
				$css .= '  --shadow-lg: 0 4px 8px rgba(0, 0, 0, 0.1);' . "\n";
				$css .= '  --shadow-xl: 0 8px 16px rgba(0, 0, 0, 0.12);' . "\n";
				break;
			case 'heavy':
				$css .= '  --shadow-sm: 0 4px 8px rgba(0, 0, 0, 0.15);' . "\n";
				$css .= '  --shadow-md: 0 8px 16px rgba(0, 0, 0, 0.2);' . "\n";
				$css .= '  --shadow-lg: 0 12px 24px rgba(0, 0, 0, 0.25);' . "\n";
				$css .= '  --shadow-xl: 0 16px 32px rgba(0, 0, 0, 0.3);' . "\n";
				break;
			case 'medium':
			default:
				$css .= '  --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.1);' . "\n";
				$css .= '  --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.15);' . "\n";
				$css .= '  --shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.2);' . "\n";
				$css .= '  --shadow-xl: 0 12px 24px rgba(0, 0, 0, 0.25);' . "\n";
				break;
		}

		$css .= '}' . "\n";

		// Output as inline style
		echo '<style id="leave-manager-branding-variables">' . wp_kses_post( $css ) . '</style>' . "\n";
	}

	/**
	 * Get color palette for display
	 *
	 * @return array Color palette
	 */
	public function get_color_palette() {
		$settings = $this->get_settings();

		return array(
			'primary'       => array(
				'label'       => 'Primary Color',
				'description' => 'Main brand color used for buttons and highlights',
				'value'       => $settings['primary_color'],
				'key'         => 'primary_color',
			),
			'primary_dark'  => array(
				'label'       => 'Primary Dark Color',
				'description' => 'Darker shade of primary color for hover states',
				'value'       => $settings['primary_dark_color'],
				'key'         => 'primary_dark_color',
			),
			'primary_light' => array(
				'label'       => 'Primary Light Color',
				'description' => 'Lighter shade of primary color for backgrounds',
				'value'       => $settings['primary_light_color'],
				'key'         => 'primary_light_color',
			),
			'accent'        => array(
				'label'       => 'Accent Color',
				'description' => 'Secondary color for links and accents',
				'value'       => $settings['accent_color'],
				'key'         => 'accent_color',
			),
			'success'       => array(
				'label'       => 'Success Color',
				'description' => 'Color for success messages and positive states',
				'value'       => $settings['success_color'],
				'key'         => 'success_color',
			),
			'error'         => array(
				'label'       => 'Error Color',
				'description' => 'Color for error messages and alerts',
				'value'       => $settings['error_color'],
				'key'         => 'error_color',
			),
			'warning'       => array(
				'label'       => 'Warning Color',
				'description' => 'Color for warning messages',
				'value'       => $settings['warning_color'],
				'key'         => 'warning_color',
			),
			'info'          => array(
				'label'       => 'Info Color',
				'description' => 'Color for informational messages',
				'value'       => $settings['info_color'],
				'key'         => 'info_color',
			),
		);
	}

	/**
	 * Get logo URL from media library
	 *
	 * @return string Logo URL
	 */
	public function get_logo_url() {
		$logo_id = $this->get_setting( 'logo_id' );
		if ( ! $logo_id ) {
			return '';
		}
		$logo = wp_get_attachment_image_src( $logo_id, 'full' );
		return $logo ? $logo[0] : '';
	}

	/**
	 * Get favicon URL from media library
	 *
	 * @return string Favicon URL
	 */
	public function get_favicon_url() {
		$favicon_id = $this->get_setting( 'favicon_id' );
		if ( ! $favicon_id ) {
			return '';
		}
		$favicon = wp_get_attachment_image_src( $favicon_id, 'full' );
		return $favicon ? $favicon[0] : '';
	}

	/**
	 * Get organization name
	 *
	 * @return string Organization name
	 */
	public function get_organization_name() {
		$name = $this->get_setting( 'organization_name' );
		return $name ? $name : get_bloginfo( 'name' );
	}

	/**
	 * Output favicon in head
	 */
	public function output_favicon() {
		$favicon_url = $this->get_favicon_url();
		if ( $favicon_url ) {
			echo '<link rel="icon" type="image/png" href="' . esc_url( $favicon_url ) . '">' . "\n";
		}
	}

	/**
	 * AJAX handler for setting logo
	 */
	public function ajax_set_logo() {
		check_ajax_referer( 'leave_manager_branding' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$logo_id = isset( $_POST['logo_id'] ) ? absint( $_POST['logo_id'] ) : 0;

		if ( ! $logo_id || ! wp_attachment_is_image( $logo_id ) ) {
			wp_send_json_error( 'Invalid image' );
		}

		$settings = $this->get_settings();
		$settings['logo_id'] = $logo_id;
		$settings['logo_url'] = wp_get_attachment_image_src( $logo_id, 'full' )[0];

		if ( update_option( $this->option_key, $settings ) ) {
			wp_send_json_success( array( 'logo_url' => $settings['logo_url'] ) );
		} else {
			wp_send_json_error( 'Failed to update settings' );
		}
	}

	/**
	 * AJAX handler for setting favicon
	 */
	public function ajax_set_favicon() {
		check_ajax_referer( 'leave_manager_branding' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$favicon_id = isset( $_POST['favicon_id'] ) ? absint( $_POST['favicon_id'] ) : 0;

		if ( ! $favicon_id || ! wp_attachment_is_image( $favicon_id ) ) {
			wp_send_json_error( 'Invalid image' );
		}

		$settings = $this->get_settings();
		$settings['favicon_id'] = $favicon_id;
		$settings['favicon_url'] = wp_get_attachment_image_src( $favicon_id, 'full' )[0];

		if ( update_option( $this->option_key, $settings ) ) {
			wp_send_json_success( array( 'favicon_url' => $settings['favicon_url'] ) );
		} else {
			wp_send_json_error( 'Failed to update settings' );
		}
	}

	/**
	 * Get logo HTML
	 *
	 * @param string $class CSS class
	 * @return string Logo HTML
	 */
	public function get_logo_html( $class = 'leave-manager-logo' ) {
		$logo_url = $this->get_logo_url();
		if ( $logo_url ) {
			return '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr( $this->get_organization_name() ) . '" class="' . esc_attr( $class ) . '">';
		}
		return '<span class="' . esc_attr( $class ) . '-text">' . esc_html( $this->get_organization_name() ) . '</span>';
	}

	/**
	 * Reset to default settings
	 *
	 * @return bool True on success
	 */
	public function reset_to_defaults() {
		return update_option( $this->option_key, $this->defaults );
	}

	/**
	 * Export settings as JSON
	 *
	 * @return string JSON string
	 */
	public function export_settings() {
		return wp_json_encode( $this->get_settings(), JSON_PRETTY_PRINT );
	}

	/**
	 * Import settings from JSON
	 *
	 * @param string $json JSON string
	 * @return bool True on success
	 */
	public function import_settings( $json ) {
		$settings = json_decode( $json, true );
		if ( ! is_array( $settings ) ) {
			return false;
		}
		return $this->update_settings( $settings );
	}
}
