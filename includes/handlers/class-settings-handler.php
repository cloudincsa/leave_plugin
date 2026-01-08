<?php
/**
 * Settings Handler
 * Manages plugin settings and configuration
 */
class Leave_Manager_Settings_Handler {
/**
 * Get setting
 *
 * @param string $key Setting key
 * @param mixed $default Default value
 * @return mixed The setting value
 */
public function get_setting( string $key, mixed $default = null ): mixed {
 get_option( 'leave_manager_' . $key, $default );
}

/**
 * Update setting
 *
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @return bool True if setting was updated
 */
public function update_setting( string $key, mixed $value ): bool {
 update_option( 'leave_manager_' . $key, $value );
}
}
