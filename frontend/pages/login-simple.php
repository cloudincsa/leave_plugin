<?php
/**
 * Simple Leave Manager Login Page
 * Minimalistic Design
 */

// Check if already logged in
if ( leave_manager_is_logged_in() ) {
    wp_redirect( leave_manager_dashboard_url() );
    exit;
}

// Handle login form submission
$error = '';
if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
    $email = sanitize_email( $_POST['email'] ?? '' );
    $password = $_POST['password'] ?? '';
    
    if ( $email && $password ) {
        $result = Leave_Manager_Custom_Auth::authenticate( $email, $password );
        
        if ( is_wp_error( $result ) ) {
            $error = $result->get_error_message();
        } else {
            // Create session
            $session_id = Leave_Manager_Session_Manager::create_session( $result['user_id'] );
            setcookie( 'leave_manager_session', $session_id, time() + (30 * 24 * 60 * 60), '/' );
            
            // Redirect to dashboard
            wp_redirect( leave_manager_dashboard_url() );
            exit;
        }
    }
}

?>
<div style="max-width: 400px; margin: 60px auto; padding: 40px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <h1 style="font-size: 28px; font-weight: 600; text-align: center; margin-bottom: 8px; color: #333;">Leave Manager</h1>
    <p style="text-align: center; color: #999; margin-bottom: 30px; font-size: 14px;">Sign in to your account</p>
    
    <?php if ( $error ) : ?>
        <div style="background: #fee; color: #c33; padding: 12px; border-radius: 4px; margin-bottom: 20px; font-size: 14px; border-left: 4px solid #c33;">
            <?php echo esc_html( $error ); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: #333;">Email</label>
            <input type="email" name="email" required placeholder="your@email.com" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
        </div>
        
        <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-size: 14px; font-weight: 500; color: #333;">Password</label>
            <input type="password" name="password" required placeholder="••••••••" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px;">
        </div>
        
        <button type="submit" style="width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 4px; font-size: 14px; font-weight: 600; cursor: pointer;">Sign In</button>
    </form>
    
    <p style="text-align: center; margin-top: 20px; font-size: 12px; color: #999;">
        Test credentials: john@example.com / password123
    </p>
</div>
