<?php
/**
 * Authentication Handler
 * Handles user authentication and capability verification
 */
class Leave_Manager_Authentication_Handler {
/**
 * Verify nonce
 *
 * @param string $nonce The nonce to verify
 * @param string $action The action associated with the nonce
 * @return bool True if nonce is valid
 */
public function verify_nonce( string $nonce, string $action ): bool {
 wp_verify_nonce( $nonce, $action );
}

/**
 * Check user capability
 *
 * @param string $cap The capability to check
 * @return bool True if user has capability
 */
public function check_capability( string $cap ): bool {
 current_user_can( $cap );
}

/**
 * Verify user is logged in
 *
 * @return bool True if user is logged in
 */
public function verify_user(): bool {
 is_user_logged_in();
}
}
