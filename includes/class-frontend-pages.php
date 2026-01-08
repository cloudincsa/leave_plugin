<?php
/**
 * Frontend Pages Handler class for Leave Manager Plugin
 *
 * Manages frontend pages and routing for the leave management system.
 * Creates pages within the main domain instead of using subdomains.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_Frontend_Pages class
 */
class Leave_Manager_Frontend_Pages {

	/**
	 * Database instance
	 *
	 * @var Leave_Manager_Database
	 */
	private $db;

	/**
	 * Logger instance
	 *
	 * @var Leave_Manager_Logger
	 */
	private $logger;

	/**
	 * Base slug for frontend pages
	 *
	 * @var string
	 */
	private $base_slug = 'leave-management';

	/**
	 * Pages to create
	 *
	 * @var array
	 */
		private $pages = array(
			'dashboard' => array(
				'title' => 'Leave Dashboard',
				'slug' => 'dashboard',
				'content' => '[leave_manager_leave_dashboard]',
			),
			'calendar' => array(
				'title' => 'Leave Calendar',
				'slug' => 'calendar',
				'content' => '[leave_manager_leave_calendar]',
			),
			'request' => array(
				'title' => 'Request Leave',
				'slug' => 'request',
				'content' => '[leave_manager_leave_form]',
			),
			'balance' => array(
				'title' => 'Leave Balance',
				'slug' => 'balance',
				'content' => '[leave_manager_leave_balance]',
			),
			'history' => array(
				'title' => 'Leave History',
				'slug' => 'history',
				'content' => '[leave_manager_leave_history]',
			),
			'signup' => array(
				'title' => 'Employee Signup',
				'slug' => 'employee-signup',
				'content' => '[leave_manager_employee_signup]',
			),
		);

	/**
	 * Constructor
	 *
	 * @param Leave_Manager_Database $db Database instance
	 * @param Leave_Manager_Logger   $logger Logger instance
	 */
	public function __construct( $db, $logger ) {
		$this->db     = $db;
		$this->logger = $logger;
	}

	/**
	 * Initialize frontend pages
	 *
	 * @return void
	 */
	public function init() {
		// Create frontend pages on plugin activation
		add_action( 'init', array( $this, 'create_pages' ) );

		// Handle frontend page routing
		add_action( 'template_redirect', array( $this, 'handle_page_routing' ) );

		// Add frontend pages to navigation menu
		add_filter( 'wp_nav_menu_items', array( $this, 'add_to_menu' ), 10, 2 );

		// Enqueue frontend styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_styles' ) );

		// Add body class for leave manager pages
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * Create frontend pages
	 *
	 * @return void
	 */
	public function create_pages() {
		// Only create pages once
		if ( get_option( 'leave_manager_frontend_pages_created' ) ) {
			return;
		}

		// Create parent page
		$parent_page = $this->create_page(
			'Leave Management',
			'leave-management',
			'<h1>Leave Management System</h1><p>Welcome to the Leave Management System. Use the menu below to navigate.</p>'
		);

		if ( ! $parent_page ) {
			$this->logger->error( 'Failed to create parent leave management page' );
			return;
		}

		// Create child pages
		foreach ( $this->pages as $key => $page ) {
			$page_id = $this->create_page(
				$page['title'],
				$page['slug'],
				$page['content'],
				$parent_page
			);

			if ( ! $page_id ) {
				$this->logger->error( 'Failed to create page: ' . $key );
			}
		}

		// Mark pages as created
		update_option( 'leave_manager_frontend_pages_created', true );
		$this->logger->info( 'Frontend pages created successfully' );
	}

	/**
	 * Create a single page
	 *
	 * @param string $title Page title
	 * @param string $slug Page slug
	 * @param string $content Page content
	 * @param int    $parent_id Parent page ID (optional)
	 * @return int|false Page ID or false
	 */
	private function create_page( $title, $slug, $content, $parent_id = 0 ) {
		// Check if page already exists
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			return $existing->ID;
		}

		// Create page
		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_parent'  => $parent_id,
			)
		);

		if ( is_wp_error( $page_id ) ) {
			return false;
		}

		return $page_id;
	}

	/**
	 * Handle page routing for leave management pages
	 *
	 * @return void
	 */
		public function handle_page_routing() {
		global $wp;
		
		// Debug logging
		
		// Check if this is a leave management page
		if ( strpos( $wp->request, 'leave-management' ) === 0 ) {
			
			// Require user to be logged in via custom auth
			$is_logged_in = Leave_Manager_Custom_Auth::is_logged_in();
			
			if ( ! $is_logged_in ) {
				// Redirect to custom login page
				wp_redirect( home_url( '/wp-content/plugins/leave-manager/login.php' ) );
				exit;
			}
			// Load custom template if needed
			$this->load_custom_template();
		}
	}

	/**
	 * Load custom template for leave management pages
	 *
	 * @return void
	 */
	private function load_custom_template() {
		// This can be extended to load custom templates
		// For now, the shortcodes handle the display
	}

	/**
	 * Add leave management pages to navigation menu
	 *
	 * @param string $items Menu items HTML
	 * @param object $args Menu arguments
	 * @return string Modified menu items HTML
	 */
	public function add_to_menu( $items, $args ) {
		// Only add to primary menu
		if ( 'primary' !== $args->theme_location ) {
			return $items;
		}

		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			return $items;
		}

		// Get leave management page
		$parent_page = get_page_by_path( 'leave-management' );
		if ( ! $parent_page ) {
			return $items;
		}

		// Build menu HTML
		$menu_html = '<li class="menu-item menu-item-type-post_type menu-item-object-page">';
		$menu_html .= '<a href="' . esc_url( get_permalink( $parent_page->ID ) ) . '">Leave Management</a>';
		$menu_html .= '<ul class="sub-menu">';

		foreach ( $this->pages as $page ) {
			$page_obj = get_page_by_path( $page['slug'] );
			if ( $page_obj ) {
				$menu_html .= '<li class="menu-item menu-item-type-post_type menu-item-object-page">';
				$menu_html .= '<a href="' . esc_url( get_permalink( $page_obj->ID ) ) . '">' . esc_html( $page['title'] ) . '</a>';
				$menu_html .= '</li>';
			}
		}

		$menu_html .= '</ul>';
		$menu_html .= '</li>';

		return $items . $menu_html;
	}

	/**
	 * Get leave management page URL
	 *
	 * @param string $page Page key
	 * @return string|false Page URL or false
	 */
	public function get_page_url( $page ) {
		if ( ! isset( $this->pages[ $page ] ) ) {
			return false;
		}

		$page_obj = get_page_by_path( $this->pages[ $page ]['slug'] );
		if ( ! $page_obj ) {
			return false;
		}

		return get_permalink( $page_obj->ID );
	}

	/**
	 * Get all leave management page URLs
	 *
	 * @return array Page URLs
	 */
	public function get_all_page_urls() {
		$urls = array();

		foreach ( array_keys( $this->pages ) as $page ) {
			$url = $this->get_page_url( $page );
			if ( $url ) {
				$urls[ $page ] = $url;
			}
		}

		return $urls;
	}

	/**
	 * Delete frontend pages on plugin deactivation
	 *
	 * @return void
	 */
	public function delete_pages() {
		// Get parent page
		$parent_page = get_page_by_path( 'leave-management' );
		if ( ! $parent_page ) {
			return;
		}

		// Delete child pages
		foreach ( $this->pages as $page ) {
			$page_obj = get_page_by_path( $page['slug'] );
			if ( $page_obj ) {
				wp_delete_post( $page_obj->ID, true );
			}
		}

		// Delete parent page
		wp_delete_post( $parent_page->ID, true );

		// Remove option
		delete_option( 'leave_manager_frontend_pages_created' );

		$this->logger->info( 'Frontend pages deleted' );
	}

	/**
	 * Get page navigation breadcrumbs
	 *
	 * @return array Breadcrumbs
	 */
	public function get_breadcrumbs() {
			global $wp;

			$breadcrumbs = array();

			// Add home
			$breadcrumbs[] = array(
				'title' => 'Home',
				'url'   => home_url(),
			);

			// Add leave management
			$parent_page = get_page_by_path( 'leave-management' );
			if ( $parent_page ) {
				$breadcrumbs[] = array(
					'title' => 'Leave Management',
					'url'   => get_permalink( $parent_page->ID ),
				);
			}

			// Add current page
			if ( strpos( $wp->request, 'leave-management' ) === 0 ) {
				$current_slug = str_replace( 'leave-management/', '', $wp->request );
				foreach ( $this->pages as $key => $page ) {
					if ( $page['slug'] === $current_slug ) {
						$breadcrumbs[] = array(
							'title' => $page['title'],
							'url'   => '',
						);
						break;
					}
				}
			}

				return $breadcrumbs;
		}

		/**
		 * Enqueue frontend styles
		 *
		 * @return void
		 */
		public function enqueue_frontend_styles() {
			if ( is_page() && is_leave_manager_page() ) {
				wp_enqueue_style(
					'leave-manager-modern',
					LEAVE_MANAGER_PLUGIN_URL . 'assets/css/frontend-modern.css',
					array(),
					'1.0.1'
				);
			}
		}

		/**
		 * Add body class for leave manager pages
		 *
		 * @param array $classes Body classes
		 * @return array
		 */
		public function add_body_class( $classes ) {
			if ( is_page() && is_leave_manager_page() ) {
				$classes[] = 'leave-manager-page';
			}
			return $classes;
		}
	}