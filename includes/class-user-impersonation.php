<?php
/**
 * User Impersonation Class - For Testing Different User Roles
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Leave_Manager_User_Impersonation class
 */
class Leave_Manager_User_Impersonation {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Ensure sessions are started
		if ( ! session_id() ) {
			if (session_status() === PHP_SESSION_NONE && !headers_sent()) { session_start(); }
		}

		// Add admin bar menu for user switching
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 999 );
		
		// Add frontend user switcher
		add_action( 'wp_footer', array( $this, 'add_frontend_switcher' ) );
		
		// Handle impersonation requests via AJAX
		add_action( 'wp_ajax_switch_user', array( $this, 'handle_ajax_switch' ) );
		
		// Add CSS for switcher
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_switcher_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_switcher_styles' ) );
		
		// Add JS for switcher
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_switcher_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_switcher_scripts' ) );
		
		// Filter shortcode outputs to show impersonated user data
		add_filter( 'leave_manager_dashboard_user_id', array( $this, 'filter_user_id' ) );
		add_filter( 'leave_manager_leave_requests_user_id', array( $this, 'filter_user_id' ) );
		add_filter( 'leave_manager_balance_user_id', array( $this, 'filter_user_id' ) );
	}

	/**
	 * Add user switcher to admin bar
	 *
	 * @param WP_Admin_Bar $wp_admin_bar Admin bar object
	 * @return void
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get current impersonated user
		$impersonated_id = $this->get_impersonated_user_id();
		$current_user = wp_get_current_user();

		$label = 'Switch User';
		if ( $impersonated_id ) {
			$impersonated_user = get_user_by( 'id', $impersonated_id );
			if ( $impersonated_user ) {
				$label = 'Viewing as: ' . $impersonated_user->display_name;
			}
		}

		// Add main menu
		$wp_admin_bar->add_menu(
			array(
				'id'    => 'leave-manager-impersonate',
				'title' => $label,
				'href'  => '#',
			)
		);

		// Get all users
		$users = get_users(
			array(
				'orderby' => 'display_name',
			)
		);

		// Add user options
		foreach ( $users as $user ) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'leave-manager-impersonate',
					'id'     => 'impersonate-' . $user->ID,
					'title'  => $user->display_name . ' (' . implode( ', ', $user->roles ) . ')',
					'href'   => '#',
					'meta'   => array(
						'onclick' => 'return lm_switch_user(' . $user->ID . ');',
					),
				)
			);
		}

		// Add reset option
		if ( $impersonated_id ) {
			$wp_admin_bar->add_menu(
				array(
					'parent' => 'leave-manager-impersonate',
					'id'     => 'impersonate-reset',
					'title'  => '--- Reset to ' . $current_user->display_name . ' ---',
					'href'   => '#',
					'meta'   => array(
						'onclick' => 'return lm_switch_user(0);',
					),
				)
			);
		}
	}

	/**
	 * Add frontend user switcher widget
	 *
	 * @return void
	 */
	public function add_frontend_switcher() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$impersonated_id = $this->get_impersonated_user_id();
		$current_user = wp_get_current_user();

		// Get all users
		$users = get_users(
			array(
				'orderby' => 'display_name',
			)
		);

		?>
		<div id="leave-manager-user-switcher" style="position: fixed; bottom: 20px; right: 20px; background: #4A5FFF; color: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; min-width: 280px;">
			<div style="margin-bottom: 10px; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.9;">
				Viewing as:
			</div>
			<div style="margin-bottom: 12px; font-weight: 600; font-size: 15px;">
				<?php
				if ( $impersonated_id ) {
					$impersonated_user = get_user_by( 'id', $impersonated_id );
					if ( $impersonated_user ) {
						echo esc_html( $impersonated_user->display_name );
						echo ' <span style="font-size: 12px; opacity: 0.8;">(' . esc_html( implode( ', ', $impersonated_user->roles ) ) . ')</span>';
					}
				} else {
					echo esc_html( $current_user->display_name );
					echo ' <span style="font-size: 12px; opacity: 0.8;">(admin)</span>';
				}
				?>
			</div>
			<select id="leave-manager-user-select" style="width: 100%; padding: 8px 10px; border: none; border-radius: 4px; font-size: 13px; background: white; color: #333; font-family: inherit; cursor: pointer;">
				<option value="">-- Switch User --</option>
				<?php foreach ( $users as $user ) : ?>
					<option value="<?php echo esc_attr( $user->ID ); ?>">
						<?php echo esc_html( $user->display_name . ' (' . implode( ', ', $user->roles ) . ')' ); ?>
					</option>
				<?php endforeach; ?>
				<?php if ( $impersonated_id ) : ?>
					<option value="0">-- Reset to <?php echo esc_html( $current_user->display_name ); ?> --</option>
				<?php endif; ?>
			</select>
		</div>

		<script>
		function lm_switch_user(user_id) {
			jQuery.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
				action: 'switch_user',
				user_id: user_id,
				nonce: '<?php echo esc_attr( wp_create_nonce( 'leave_manager_switch_user' ) ); ?>'
			}, function(response) {
				if (response.success) {
					location.reload();
				}
			});
			return false;
		}

		document.getElementById('leave-manager-user-select').addEventListener('change', function(e) {
			if (this.value !== '') {
				lm_switch_user(this.value);
			}
		});
		</script>
		<?php
	}

	/**
	 * Handle AJAX user switch request
	 *
	 * @return void
	 */
	public function handle_ajax_switch() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'leave_manager_switch_user' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		}

		// Check if user is admin
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Not authorized' ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

		if ( 0 === $user_id ) {
			// Reset impersonation
			unset( $_SESSION['impersonated_user_id'] );
		} else {
			// Verify user exists
			$user = get_user_by( 'id', $user_id );
			if ( $user ) {
				$_SESSION['impersonated_user_id'] = $user_id;
			} else {
				wp_send_json_error( array( 'message' => 'User not found' ) );
			}
		}

		wp_send_json_success( array( 'message' => 'User switched successfully' ) );
	}

	/**
	 * Filter user ID for shortcodes - returns impersonated user if set
	 *
	 * @param int $user_id Current user ID
	 * @return int Impersonated user ID if set, otherwise current user ID
	 */
	public function filter_user_id( $user_id ) {
		$impersonated_id = $this->get_impersonated_user_id();
		if ( $impersonated_id ) {
			return $impersonated_id;
		}
		return $user_id;
	}

	/**
	 * Get impersonated user ID
	 *
	 * @return int|null
	 */
	public function get_impersonated_user_id() {
		if ( ! session_id() ) {
			if (session_status() === PHP_SESSION_NONE && !headers_sent()) { session_start(); }
		}

		return isset( $_SESSION['impersonated_user_id'] ) ? intval( $_SESSION['impersonated_user_id'] ) : null;
	}

	/**
	 * Enqueue switcher styles
	 *
	 * @return void
	 */
	public function enqueue_switcher_styles() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_add_inline_style(
			'wp-admin',
			'
			#leave-manager-user-switcher {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
			}
			#leave-manager-user-select {
				font-family: inherit !important;
			}
			'
		);
	}

	/**
	 * Enqueue switcher scripts
	 *
	 * @return void
	 */
	public function enqueue_switcher_scripts() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
	}

	/**
	 * Static method to get current impersonated user
	 *
	 * @return int|null
	 */
	public static function get_impersonated_user() {
		if ( ! session_id() ) {
			if (session_status() === PHP_SESSION_NONE && !headers_sent()) { session_start(); }
		}

		return isset( $_SESSION['impersonated_user_id'] ) ? intval( $_SESSION['impersonated_user_id'] ) : null;
	}

	/**
	 * Static method to check if currently impersonating
	 *
	 * @return bool
	 */
	public static function is_impersonating() {
		return null !== self::get_impersonated_user();
	}
}
