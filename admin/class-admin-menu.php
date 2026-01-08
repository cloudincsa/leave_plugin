<?php
/**
 * Branding Menu Handler
 *
 * Manages branding and additional admin menu items
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Branding_Menu class
 */
class Leave_Manager_Branding_Menu {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu_items' ) );
	}

	/**
	 * Register admin menu items
	 */
	public function register_menu_items() {
		// Add Branding submenu
		add_submenu_page(
			'leave-manager',
			'Branding & Design',
			'Branding',
			'manage_options',
			'leave-manager-branding',
			array( $this, 'render_branding_page' )
		);

		// Add Help submenu
		add_submenu_page(
			'leave-manager',
			'Help & Documentation',
			'Help',
			'manage_options',
			'leave-manager-help',
			array( $this, 'render_help_page' )
		);
	}

	/**
	 * Render branding page
	 */
	public function render_branding_page() {
		include LEAVE_MANAGER_PLUGIN_DIR . 'admin/pages/branding.php';
	}

	/**
	 * Render help page
	 */
	public function render_help_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div class="help-content">
				<h2>Getting Started with Leave Manager</h2>
				<p>Welcome to Leave Manager! This plugin provides a comprehensive leave management system for your WordPress site.</p>

				<h3>Quick Start Guide</h3>
				<ol>
					<li><strong>Configure Settings:</strong> Go to Leave > Settings to set up your organization details and leave policies.</li>
					<li><strong>Add Staff:</strong> Go to Leave > Staff to add employees and assign leave policies.</li>
					<li><strong>Customize Branding:</strong> Go to Leave > Branding to customize colors and design.</li>
					<li><strong>Share Pages:</strong> Share the Leave Management pages with your employees.</li>
				</ol>

				<h3>Available Pages</h3>
				<ul>
					<li><strong>Leave Management:</strong> Main hub for leave management</li>
					<li><strong>Leave Dashboard:</strong> Employee dashboard with overview</li>
					<li><strong>Leave Calendar:</strong> Visual calendar of all leaves</li>
					<li><strong>Request Leave:</strong> Form for employees to request leave</li>
					<li><strong>Leave Balance:</strong> View remaining leave balance</li>
					<li><strong>Leave History:</strong> View past leave requests</li>
					<li><strong>Employee Signup:</strong> New employee registration</li>
				</ul>

				<h3>Features</h3>
				<ul>
					<li>Modern, minimalist design system</li>
					<li>Customizable branding and colors</li>
					<li>Dark mode support</li>
					<li>Responsive design for mobile and desktop</li>
					<li>Email notifications</li>
					<li>Leave policy management</li>
					<li>Staff management</li>
				</ul>

				<h3>Support</h3>
				<p>For more information and support, visit our documentation or contact support.</p>
			</div>
		</div>
		<?php
	}
}
