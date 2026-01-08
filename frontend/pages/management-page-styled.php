<?php
/**
 * Modern Styled Leave Management Page
 * 
 * Displays the Leave Management landing page with modern design
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get branding settings
$org_name = get_option('leave_manager_organization_name', 'Leave Manager');
$primary_color = get_option('leave_manager_primary_color', '#4A5FFF');
$primary_dark = get_option('leave_manager_primary_dark', '#ff9800');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($org_name); ?> - Leave Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }

        .lm-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styling */
        .lm-header {
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 20px 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .lm-header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }

        .lm-logo {
            font-size: 24px;
            font-weight: 700;
            color: <?php echo esc_attr($primary_color); ?>;
            text-decoration: none;
        }

        .lm-nav {
            display: flex;
            gap: 30px;
            align-items: center;
        }

        .lm-nav a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .lm-nav a:hover {
            color: <?php echo esc_attr($primary_color); ?>;
        }

        /* Main Content */
        .lm-main {
            padding: 60px 20px;
        }

        .lm-hero {
            text-align: center;
            margin-bottom: 60px;
        }

        .lm-hero h1 {
            font-size: 48px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
        }

        .lm-hero p {
            font-size: 18px;
            color: #666;
            margin-bottom: 30px;
        }

        /* Cards Grid */
        .lm-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .lm-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
        }

        .lm-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .lm-card-icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: <?php echo esc_attr($primary_color); ?>;
        }

        .lm-card-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .lm-card-description {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            flex-grow: 1;
        }

        .lm-card-button {
            margin-top: 20px;
            padding: 10px 20px;
            background: <?php echo esc_attr($primary_color); ?>;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }

        .lm-card-button:hover {
            background: <?php echo esc_attr($primary_dark); ?>;
        }

        /* Stats Section */
        .lm-stats {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 60px;
        }

        .lm-stats-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 30px;
            color: #333;
        }

        .lm-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }

        .lm-stat-item {
            text-align: center;
            padding: 20px;
            background: #f5f7fa;
            border-radius: 8px;
        }

        .lm-stat-number {
            font-size: 32px;
            font-weight: 700;
            color: <?php echo esc_attr($primary_color); ?>;
            margin-bottom: 10px;
        }

        .lm-stat-label {
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        /* Footer */
        .lm-footer {
            background: #333;
            color: white;
            padding: 40px 20px;
            text-align: center;
            margin-top: 60px;
        }

        .lm-footer p {
            margin: 10px 0;
            font-size: 14px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .lm-header-content {
                flex-direction: column;
                gap: 20px;
            }

            .lm-nav {
                flex-direction: column;
                gap: 15px;
            }

            .lm-hero h1 {
                font-size: 32px;
            }

            .lm-hero p {
                font-size: 16px;
            }

            .lm-cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="lm-header">
        <div class="lm-container">
            <div class="lm-header-content">
                <a href="<?php echo esc_url(home_url('/index.php/leave-management/')); ?>" class="lm-logo">
                    <?php echo esc_html($org_name); ?>
                </a>
                <nav class="lm-nav">
                    <a href="<?php echo esc_url(home_url('/index.php/leave-management/dashboard/')); ?>">Dashboard</a>
                    <a href="<?php echo esc_url(home_url('/index.php/leave-management/calendar/')); ?>">Calendar</a>
                    <a href="<?php echo esc_url(home_url('/index.php/leave-management/request/')); ?>">Request Leave</a>
                    <a href="<?php echo esc_url(home_url('/index.php/leave-management/balance/')); ?>">Balance</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="lm-main">
        <div class="lm-container">
            <!-- Hero Section -->
            <div class="lm-hero">
                <h1>Leave Management System</h1>
                <p>Streamlined leave requests, approvals, and management for your organization</p>
            </div>

            <!-- Feature Cards -->
            <div class="lm-cards-grid">
                <a href="<?php echo esc_url(home_url('/index.php/leave-management/request/')); ?>" class="lm-card">
                    <div class="lm-card-icon">📝</div>
                    <h3 class="lm-card-title">Request Leave</h3>
                    <p class="lm-card-description">Submit new leave requests with just a few clicks. Track your requests in real-time.</p>
                    <button class="lm-card-button">Request Now</button>
                </a>

                <a href="<?php echo esc_url(home_url('/index.php/leave-management/calendar/')); ?>" class="lm-card">
                    <div class="lm-card-icon">📅</div>
                    <h3 class="lm-card-title">Leave Calendar</h3>
                    <p class="lm-card-description">View all leave requests on an interactive calendar. See team availability at a glance.</p>
                    <button class="lm-card-button">View Calendar</button>
                </a>

                <a href="<?php echo esc_url(home_url('/index.php/leave-management/balance/')); ?>" class="lm-card">
                    <div class="lm-card-icon">⏱️</div>
                    <h3 class="lm-card-title">Leave Balance</h3>
                    <p class="lm-card-description">Check your available leave balance. Track your leave usage throughout the year.</p>
                    <button class="lm-card-button">Check Balance</button>
                </a>

                <a href="<?php echo esc_url(home_url('/index.php/leave-management/history/')); ?>" class="lm-card">
                    <div class="lm-card-icon">📊</div>
                    <h3 class="lm-card-title">Leave History</h3>
                    <p class="lm-card-description">View your complete leave history. Track all past and current leave requests.</p>
                    <button class="lm-card-button">View History</button>
                </a>
            </div>

            <!-- Statistics Section -->
            <div class="lm-stats">
                <h2 class="lm-stats-title">Organization Statistics</h2>
                <div class="lm-stats-grid">
                    <div class="lm-stat-item">
                        <div class="lm-stat-number">6</div>
                        <div class="lm-stat-label">Total Employees</div>
                    </div>
                    <div class="lm-stat-item">
                        <div class="lm-stat-number">7</div>
                        <div class="lm-stat-label">Total Requests</div>
                    </div>
                    <div class="lm-stat-item">
                        <div class="lm-stat-number">3</div>
                        <div class="lm-stat-label">Pending Approval</div>
                    </div>
                    <div class="lm-stat-item">
                        <div class="lm-stat-number">3</div>
                        <div class="lm-stat-label">Approved</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="lm-footer">
        <div class="lm-container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html($org_name); ?>. All rights reserved.</p>
            <p>Powered by Leave Manager Plugin v1.0.1</p>
        </div>
    </footer>
</body>
</html>
