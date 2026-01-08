<?php
/**
 * Polished Leave Manager Login Page
 * Minimalistic Design with Professional Styling
 */

// Check if already logged in
if ( leave_manager_is_logged_in() ) {
    wp_redirect( leave_manager_dashboard_url() );
    exit;
}

// Handle login form submission
$error = '';
$email = '';

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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Manager - Sign In</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            background-attachment: fixed;
        }

        .login-container {
            width: 100%;
            max-width: 550px;
            min-height: 50vh;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            padding: 60px 50px;
            animation: slideUp 0.4s ease-out;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
        }

        .login-header p {
            font-size: 14px;
            color: #718096;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group:last-of-type {
            margin-bottom: 28px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            font-size: 15px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            background: #f7fafc;
            color: #2d3748;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        input[type="email"]::placeholder,
        input[type="password"]::placeholder {
            color: #cbd5e0;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        input[type="email"]:hover,
        input[type="password"]:hover {
            border-color: #cbd5e0;
        }

        .error-message {
            background: #fff5f5;
            color: #c53030;
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            border-left: 4px solid #c53030;
            animation: shake 0.3s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .btn-signin {
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-signin:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }

        .btn-signin:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .login-footer p {
            font-size: 12px;
            color: #a0aec0;
            line-height: 1.6;
        }

        .login-footer strong {
            color: #667eea;
            font-weight: 600;
        }

        /* Mobile Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 40px 24px;
                border-radius: 16px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .login-header p {
                font-size: 13px;
            }

            input[type="email"],
            input[type="password"] {
                padding: 14px 12px;
                font-size: 16px;
            }

            .btn-signin {
                padding: 14px 16px;
                font-size: 14px;
            }

            label {
                font-size: 12px;
            }
        }

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            * {
                animation: none !important;
                transition: none !important;
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background: linear-gradient(135deg, #4c5282 0%, #5a3a7a 100%);
            }

            .login-container {
                background: #2d3748;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            }

            .login-header h1 {
                color: #f7fafc;
            }

            .login-header p {
                color: #cbd5e0;
            }

            label {
                color: #e2e8f0;
            }

            input[type="email"],
            input[type="password"] {
                background: #1a202c;
                border-color: #4a5568;
                color: #e2e8f0;
            }

            input[type="email"]::placeholder,
            input[type="password"]::placeholder {
                color: #718096;
            }

            input[type="email"]:focus,
            input[type="password"]:focus {
                background: #2d3748;
                border-color: #667eea;
            }

            .error-message {
                background: rgba(197, 48, 48, 0.1);
                color: #fc8181;
                border-left-color: #fc8181;
            }

            .login-footer {
                border-top-color: #4a5568;
            }

            .login-footer p {
                color: #a0aec0;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Leave Manager</h1>
            <p>Sign in to your account</p>
        </div>

        <?php if ( $error ) : ?>
            <div class="error-message">
                <?php echo esc_html( $error ); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email"
                    name="email" 
                    value="<?php echo esc_attr( $email ); ?>"
                    placeholder="your@email.com" 
                    required
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    placeholder="••••••••" 
                    required
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="btn-signin">Sign In</button>
        </form>

        <div class="login-footer">
            <p>
                <strong>Demo Credentials:</strong><br>
                john@example.com / password123
            </p>
        </div>
    </div>
</body>
</html>
