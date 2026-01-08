<?php
/**
 * Custom Leave Manager Login Page
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

// Remove WordPress admin bar
add_filter( 'show_admin_bar', '__return_false' );

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Manager - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-title {
            font-size: 28px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        
        .login-subtitle {
            font-size: 14px;
            color: #999;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c33;
        }
        
        .login-button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .login-button:hover {
            background: #5568d3;
        }
        
        .login-button:active {
            transform: translateY(1px);
        }
        
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            .login-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1 class="login-title">Leave Manager</h1>
            <p class="login-subtitle">Sign in to your account</p>
        </div>
        
        <?php if ( $error ) : ?>
            <div class="error-message"><?php echo esc_html( $error ); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required placeholder="your@email.com">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>
            
            <button type="submit" class="login-button">Sign In</button>
        </form>
    </div>
</body>
</html>
