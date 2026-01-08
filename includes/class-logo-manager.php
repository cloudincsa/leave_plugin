<?php
/**
 * Logo Manager Class
 * Handles logo upload and management using WordPress media library
 */

if (!defined('ABSPATH')) {
    exit;
}

class Leave_Manager_Logo_Manager {

    /**
     * Initialize logo manager
     */
    public static function init() {
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_media_uploader'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_media_uploader'));
        add_action('wp_ajax_upload_leave_logo', array(__CLASS__, 'handle_logo_upload'));
        add_action('wp_ajax_nopriv_upload_leave_logo', array(__CLASS__, 'handle_logo_upload'));
    }

    /**
     * Enqueue media uploader scripts
     */
    public static function enqueue_media_uploader() {
        wp_enqueue_media();
        wp_enqueue_script('leave-manager-logo-uploader', 
            LEAVE_MANAGER_PLUGIN_URL . 'assets/js/logo-uploader.js',
            array('jquery', 'media-upload', 'media-views'),
            '1.0.1',
            true
        );
        
        wp_localize_script('leave-manager-logo-uploader', 'leaveManagerLogo', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('leave_manager_logo_nonce'),
            'uploadText' => __('Upload Logo', 'leave-manager'),
            'selectText' => __('Select Logo', 'leave-manager')
        ));
    }

    /**
     * Handle logo upload via AJAX
     */
    public static function handle_logo_upload() {
        check_ajax_referer('leave_manager_logo_nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'leave-manager')));
        }

        if (empty($_FILES['logo'])) {
            wp_send_json_error(array('message' => __('No file uploaded', 'leave-manager')));
        }

        $file = $_FILES['logo'];
        $upload = wp_handle_upload($file, array('test_form' => false));

        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
        }

        // Save logo URL to option
        update_option('leave_manager_logo_url', $upload['url']);
        update_option('leave_manager_logo_id', $upload['file']);

        wp_send_json_success(array(
            'url' => $upload['url'],
            'message' => __('Logo uploaded successfully', 'leave-manager')
        ));
    }

    /**
     * Get logo URL
     */
    public static function get_logo_url() {
        $logo_url = get_option('leave_manager_logo_url');
        
        if (!$logo_url) {
            // Return default logo or placeholder
            return LEAVE_MANAGER_PLUGIN_URL . 'assets/images/default-logo.png';
        }
        
        return $logo_url;
    }

    /**
     * Get logo ID
     */
    public static function get_logo_id() {
        return get_option('leave_manager_logo_id');
    }

    /**
     * Delete logo
     */
    public static function delete_logo() {
        delete_option('leave_manager_logo_url');
        delete_option('leave_manager_logo_id');
    }

    /**
     * Render logo upload field
     */
    public static function render_logo_field() {
        $logo_url = self::get_logo_url();
        $has_logo = $logo_url && strpos($logo_url, 'default-logo') === false;
        ?>
        <div class="leave-manager-logo-field">
            <div class="logo-preview">
                <?php if ($has_logo): ?>
                    <img id="logo-preview-img" src="<?php echo esc_url($logo_url); ?>" alt="Logo" style="max-width: 200px; max-height: 100px;">
                <?php else: ?>
                    <div id="logo-preview-img" style="width: 200px; height: 100px; background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999; border-radius: 8px;">
                        <?php _e('No logo uploaded', 'leave-manager'); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="logo-actions">
                <button type="button" class="button button-primary" id="upload-logo-btn">
                    <?php _e('Upload Logo', 'leave-manager'); ?>
                </button>
                <?php if ($has_logo): ?>
                    <button type="button" class="button button-secondary" id="delete-logo-btn">
                        <?php _e('Delete Logo', 'leave-manager'); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <input type="hidden" id="logo-url" name="leave_manager_logo_url" value="<?php echo esc_attr($logo_url); ?>">
        </div>
        <?php
    }
}

// Initialize
Leave_Manager_Logo_Manager::init();
