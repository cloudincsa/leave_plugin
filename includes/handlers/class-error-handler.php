<?php
/**
 * Error Handler Class
 *
 * Handles error logging for the Leave Manager plugin.
 * Only logs errors when debug mode is enabled.
 *
 * @package Leave_Manager
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Leave_Manager_Error_Handler
 */
class Leave_Manager_Error_Handler {

    /**
     * Log an error message
     *
     * @param string $message The error message to log.
     * @param string $level   The error level (error, warning, info, debug).
     * @return bool True if logged, false if debug mode is disabled.
     */
    public function log_error( string $message, string $level = 'error' ): bool {
        // Only log if debug mode is enabled
        if ( ! defined( 'LEAVE_MANAGER_DEBUG_MODE' ) || ! LEAVE_MANAGER_DEBUG_MODE ) {
            // Store critical errors even when debug is off
            if ( $level === 'error' ) {
                set_transient( 'leave_manager_last_error', $message, HOUR_IN_SECONDS );
            }
            return false;
        }

        $log_message = sprintf(
            '[Leave Manager %s] [%s] %s',
            strtoupper( $level ),
            current_time( 'mysql' ),
            $message
        );

        error_log( $log_message );
        return true;
    }

    /**
     * Get the last error message
     *
     * @return string The last error message or empty string.
     */
    public function get_last_error(): string {
        return get_transient( 'leave_manager_last_error' ) ?: '';
    }

    /**
     * Clear the last error
     *
     * @return bool True on success.
     */
    public function clear_last_error(): bool {
        return delete_transient( 'leave_manager_last_error' );
    }
}
