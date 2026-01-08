<?php
/**
 * Custom Authentication System for Leave Manager
 * 
 * Provides independent authentication without WordPress users
 */

class Leave_Manager_Custom_Auth {
    
    /**
     * Authenticate user with credentials
     * 
     * @param string $email User email
     * @param string $password User password
     * @return array|WP_Error User data or error
     */
    public static function authenticate( $email, $password ) {
        global $wpdb;
        
        if ( empty( $email ) || empty( $password ) ) {
            return new WP_Error( 'empty_credentials', 'Email and password are required' );
        }
        
        // Get user by email
        $user = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}leave_manager_leave_users WHERE email = %s",
            $email
        ));
        
        if ( ! $user ) {
            return new WP_Error( 'invalid_user', 'User not found' );
        }
        
        // Check if account is locked
        if ( $user->account_locked ) {
            return new WP_Error( 'account_locked', 'Account is locked. Please contact administrator.' );
        }
        
        // Verify password
        if ( ! password_verify( $password, $user->password_hash ) ) {
            // Increment login attempts
            $wpdb->update(
                "{$wpdb->prefix}leave_manager_leave_users",
                array( 'login_attempts' => $user->login_attempts + 1 ),
                array( 'user_id' => $user->user_id )
            );
            
            // Lock account after 5 failed attempts
            if ( $user->login_attempts >= 4 ) {
                $wpdb->update(
                    "{$wpdb->prefix}leave_manager_leave_users",
                    array( 'account_locked' => true ),
                    array( 'user_id' => $user->user_id )
                );
                return new WP_Error( 'account_locked', 'Too many failed login attempts. Account locked.' );
            }
            
            return new WP_Error( 'invalid_password', 'Invalid password' );
        }
        
        // Reset login attempts on successful login
        $wpdb->update(
            "{$wpdb->prefix}leave_manager_leave_users",
            array( 
                'login_attempts' => 0,
                'last_login' => current_time( 'mysql' )
            ),
            array( 'user_id' => $user->user_id )
        );
        
        return array(
            'user_id' => $user->user_id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'role' => $user->role
        );
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public static function is_logged_in() {
        return isset( $_COOKIE['leave_manager_session'] ) &&
               Leave_Manager_Session_Manager::validate_session( $_COOKIE['leave_manager_session'] );
    }
    
    /**
     * Get current logged-in user
     * 
     * @return object|null
     */
    public static function get_current_user() {
        if ( ! self::is_logged_in() ) {
            return null;
        }
        
        $session_id = $_COOKIE['leave_manager_session'];
        $user_id = Leave_Manager_Session_Manager::get_user_id( $session_id );
        
        if ( ! $user_id ) {
            return null;
        }
        
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}leave_manager_leave_users WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if ( isset( $_COOKIE['leave_manager_session'] ) ) {
            Leave_Manager_Session_Manager::destroy_session( $_COOKIE['leave_manager_session'] );
            setcookie( 'leave_manager_session', '', time() - 3600, '/' );
        }
    }
    
    /**
     * Hash password
     * 
     * @param string $password
     * @return string
     */
    public static function hash_password( $password ) {
        return password_hash( $password, PASSWORD_BCRYPT, array( 'cost' => 12 ) );
    }
    
    /**
     * Verify password
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verify_password( $password, $hash ) {
        return password_verify( $password, $hash );
    }
    
    /**
     * Check user capability
     * 
     * @param string $capability
     * @return bool
     */
    public static function user_can( $capability ) {
        $user = self::get_current_user();
        
        if ( ! $user ) {
            return false;
        }
        
        // Define capabilities by role
        $capabilities = array(
            'staff' => array(
                'view_own_requests',
                'submit_request',
                'view_own_balance',
                'view_calendar',
            ),
            'hr' => array(
                'view_own_requests',
                'submit_request',
                'view_own_balance',
                'view_calendar',
                'view_all_requests',
                'approve_request',
                'reject_request',
                'view_all_balances',
            ),
            'admin' => array(
                'view_own_requests',
                'submit_request',
                'view_own_balance',
                'view_calendar',
                'view_all_requests',
                'approve_request',
                'reject_request',
                'view_all_balances',
                'manage_users',
                'manage_policies',
                'manage_settings',
                'view_reports',
            ),
        );
        
        if ( ! isset( $capabilities[ $user->role ] ) ) {
            return false;
        }
        
        return in_array( $capability, $capabilities[ $user->role ] );
    }
}
