<?php
/**
 * Settings AJAX Handler
 * Handles all AJAX requests for settings updates without page refresh
 */

class Leave_Manager_Settings_AJAX {

    public function __construct() {
        add_action( 'wp_ajax_leave_manager_save_settings', array( $this, 'save_settings' ) );
        add_action( 'wp_ajax_leave_manager_save_branding', array( $this, 'save_branding' ) );
        add_action( 'wp_ajax_leave_manager_delete_user', array( $this, 'delete_user' ) );
        add_action( 'wp_ajax_leave_manager_edit_user', array( $this, 'edit_user' ) );
        add_action( 'wp_ajax_leave_manager_update_user', array( $this, 'update_user' ) );
    }

    /**
     * Save general settings via AJAX
     */
    public function save_settings() {
        check_ajax_referer( 'leave_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $settings = isset( $_POST['settings'] ) ? $_POST['settings'] : array();

        // Sanitize and save settings
        foreach ( $settings as $key => $value ) {
            $sanitized_value = sanitize_text_field( $value );
            update_option( 'leave_manager_' . sanitize_key( $key ), $sanitized_value );
        }

        wp_send_json_success( array(
            'message' => 'Settings saved successfully!',
            'timestamp' => current_time( 'mysql' )
        ) );
    }

    /**
     * Save branding settings via AJAX
     */
    public function save_branding() {
        check_ajax_referer( 'leave_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $branding = isset( $_POST['branding'] ) ? $_POST['branding'] : array();

        // Save branding colors
        $colors = array(
            'primary_color',
            'primary_dark_color',
            'primary_light_color',
            'accent_color',
            'success_color',
            'error_color',
            'warning_color',
            'info_color'
        );

        foreach ( $colors as $color ) {
            if ( isset( $branding[ $color ] ) ) {
                $hex_color = sanitize_hex_color( $branding[ $color ] );
                if ( $hex_color ) {
                    update_option( 'leave_manager_' . $color, $hex_color );
                }
            }
        }

        // Generate CSS variables
        $this->generate_branding_css();

        wp_send_json_success( array(
            'message' => 'Branding saved successfully!',
            'timestamp' => current_time( 'mysql' )
        ) );
    }

    /**
     * Delete user via AJAX
     */
    public function delete_user() {
        check_ajax_referer( 'leave_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'Invalid user ID' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_users';

        $deleted = $wpdb->delete( $table, array( 'id' => $user_id ) );

        if ( $deleted ) {
            wp_send_json_success( array(
                'message' => 'User deleted successfully!',
                'user_id' => $user_id
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to delete user' ) );
        }
    }

    /**
     * Get user data for editing via AJAX
     */
    public function edit_user() {
        check_ajax_referer( 'leave_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'Invalid user ID' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_users';

        $user = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $user_id
        ) );

        if ( $user ) {
            wp_send_json_success( $user );
        } else {
            wp_send_json_error( array( 'message' => 'User not found' ) );
        }
    }

    /**
     * Update user via AJAX
     */
    public function update_user() {
        check_ajax_referer( 'leave_manager_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
        $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';
        $department = isset( $_POST['department'] ) ? sanitize_text_field( $_POST['department'] ) : '';
        $role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : 'employee';

        if ( ! $user_id || ! $first_name || ! $email ) {
            wp_send_json_error( array( 'message' => 'Missing required fields' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_users';

        $updated = $wpdb->update(
            $table,
            array(
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'department' => $department,
                'role' => $role
            ),
            array( 'id' => $user_id )
        );

        if ( $updated !== false ) {
            wp_send_json_success( array(
                'message' => 'User updated successfully!',
                'user_id' => $user_id
            ) );
        } else {
            wp_send_json_error( array( 'message' => 'Failed to update user' ) );
        }
    }

    /**
     * Generate branding CSS file
     */
    private function generate_branding_css() {
        $primary = get_option( 'leave_manager_primary_color', '#4A5FFF' );
        $primary_dark = get_option( 'leave_manager_primary_dark_color', '#ff9800' );
        $primary_light = get_option( 'leave_manager_primary_light_color', '#ffeb3b' );
        $accent = get_option( 'leave_manager_accent_color', '#667eea' );
        $success = get_option( 'leave_manager_success_color', '#4caf50' );
        $error = get_option( 'leave_manager_error_color', '#f44336' );
        $warning = get_option( 'leave_manager_warning_color', '#ff9800' );
        $info = get_option( 'leave_manager_info_color', '#2196f3' );

        $css = ":root {
    --lm-primary: {$primary};
    --lm-primary-dark: {$primary_dark};
    --lm-primary-light: {$primary_light};
    --lm-accent: {$accent};
    --lm-success: {$success};
    --lm-error: {$error};
    --lm-warning: {$warning};
    --lm-info: {$info};
}";

        $upload_dir = wp_upload_dir();
        $branding_dir = $upload_dir['basedir'] . '/leave-manager';

        if ( ! is_dir( $branding_dir ) ) {
            wp_mkdir_p( $branding_dir );
        }

        file_put_contents( $branding_dir . '/branding.css', $css );
    }
}

new Leave_Manager_Settings_AJAX();
