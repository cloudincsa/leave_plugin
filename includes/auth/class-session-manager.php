<?php
/**
 * Session Manager for Leave Manager
 * 
 * Handles session creation, validation, and cleanup
 */

class Leave_Manager_Session_Manager {
    
    const SESSION_TIMEOUT = 1800; // 30 minutes
    
    /**
     * Create a new session
     * 
     * @param int $user_id
     * @return string Session ID
     */
    public static function create_session( $user_id ) {
        global $wpdb;
        
        // Generate secure session ID
        $session_id = bin2hex( random_bytes( 32 ) );
        
        // Get client IP
        $ip_address = self::get_client_ip();
        
        // Get user agent
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? substr( $_SERVER['HTTP_USER_AGENT'], 0, 255 ) : '';
        
        // Calculate expiration time
        $expires_at = date( 'Y-m-d H:i:s', time() + self::SESSION_TIMEOUT );
        
        // Insert session into database
        $wpdb->insert(
            "{$wpdb->prefix}leave_manager_sessions",
            array(
                'session_id' => $session_id,
                'user_id' => $user_id,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'created_at' => current_time( 'mysql' ),
                'expires_at' => $expires_at,
            ),
            array( '%s', '%d', '%s', '%s', '%s', '%s' )
        );
        
        return $session_id;
    }
    
    /**
     * Validate session
     * 
     * @param string $session_id
     * @return bool
     */
    public static function validate_session( $session_id ) {
        global $wpdb;
        
        $session = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}leave_manager_sessions WHERE session_id = %s",
            $session_id
        ));
        
        if ( ! $session ) {
            return false;
        }
        
        // Check if session has expired
        if ( strtotime( $session->expires_at ) < time() ) {
            self::destroy_session( $session_id );
            return false;
        }
        
        // Skip strict IP validation for proxy/development environments
        // The session is still secured by the unique session ID
        // Uncomment below for stricter security in production
        // $current_ip = self::get_client_ip();
        // if ( $session->ip_address !== $current_ip ) {
        //     return false;
        // }
        
        // Extend session on activity
        self::extend_session( $session_id );
        
        return true;
    }
    
    /**
     * Get user ID from session
     * 
     * @param string $session_id
     * @return int|null
     */
    public static function get_user_id( $session_id ) {
        global $wpdb;
        
        $session = $wpdb->get_row( $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->prefix}leave_manager_sessions WHERE session_id = %s",
            $session_id
        ));
        
        return $session ? $session->user_id : null;
    }
    
    /**
     * Destroy session
     * 
     * @param string $session_id
     */
    public static function destroy_session( $session_id ) {
        global $wpdb;
        
        $wpdb->delete(
            "{$wpdb->prefix}leave_manager_sessions",
            array( 'session_id' => $session_id )
        );
    }
    
    /**
     * Cleanup expired sessions
     */
    public static function cleanup_expired_sessions() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->prefix}leave_manager_sessions WHERE expires_at < NOW()"
        );
    }
    
    /**
     * Extend session expiration
     * 
     * @param string $session_id
     */
    public static function extend_session( $session_id ) {
        global $wpdb;
        
        $new_expires = date( 'Y-m-d H:i:s', time() + self::SESSION_TIMEOUT );
        
        $wpdb->update(
            "{$wpdb->prefix}leave_manager_sessions",
            array( 'expires_at' => $new_expires ),
            array( 'session_id' => $session_id )
        );
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    private static function get_client_ip() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ip = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] )[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        
        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
    }
}
