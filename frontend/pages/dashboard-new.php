<?php
/**
 * Leave Manager Dashboard - Minimalistic Design
 */

// Require login
leave_manager_require_login();

// Get current user
$user = leave_manager_get_current_user();

// Get user's leave balance
global $wpdb;
$balance = $wpdb->get_row( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}leave_manager_leave_balances WHERE user_id = %d",
    $user->user_id
));

// Get recent leave requests
$requests = $wpdb->get_results( $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests WHERE user_id = %d ORDER BY created_at DESC LIMIT 5",
    $user->user_id
));

// Remove WordPress admin bar
add_filter( 'show_admin_bar', '__return_false' );

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Leave Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f9f9f9;
            color: #333;
        }
        
        .lm-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .lm-page-title {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #333;
        }
        
        .lm-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .lm-card {
            background: white;
            border-radius: 8px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            transition: box-shadow 0.2s;
        }
        
        .lm-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .lm-card-label {
            font-size: 14px;
            color: #999;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .lm-card-value {
            font-size: 32px;
            font-weight: 600;
            color: #667eea;
        }
        
        .lm-card-unit {
            font-size: 14px;
            color: #999;
            margin-left: 4px;
        }
        
        .lm-section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }
        
        .lm-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .lm-table th {
            background: #f9f9f9;
            padding: 16px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            color: #666;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .lm-table td {
            padding: 16px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 14px;
        }
        
        .lm-table tr:last-child td {
            border-bottom: none;
        }
        
        .lm-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .lm-status.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .lm-status.approved {
            background: #d4edda;
            color: #155724;
        }
        
        .lm-status.rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        .lm-empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .lm-empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        .lm-empty-state-text {
            font-size: 14px;
        }
        
        @media (max-width: 768px) {
            .lm-container {
                padding: 20px 15px;
            }
            
            .lm-page-title {
                font-size: 24px;
                margin-bottom: 20px;
            }
            
            .lm-grid {
                grid-template-columns: 1fr;
                gap: 15px;
                margin-bottom: 30px;
            }
            
            .lm-table {
                font-size: 12px;
            }
            
            .lm-table th,
            .lm-table td {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <?php include LEAVE_MANAGER_PLUGIN_DIR . 'frontend/components/header-minimal.php'; ?>
    
    <div class="lm-container">
        <h1 class="lm-page-title">Dashboard</h1>
        
        <!-- Leave Balance Cards -->
        <div class="lm-grid">
            <div class="lm-card">
                <div class="lm-card-label">Annual Leave</div>
                <div>
                    <span class="lm-card-value"><?php echo $balance ? number_format( $balance->annual_leave_balance, 1 ) : '0'; ?></span>
                    <span class="lm-card-unit">days</span>
                </div>
            </div>
            
            <div class="lm-card">
                <div class="lm-card-label">Sick Leave</div>
                <div>
                    <span class="lm-card-value"><?php echo $balance ? number_format( $balance->sick_leave_balance, 1 ) : '0'; ?></span>
                    <span class="lm-card-unit">days</span>
                </div>
            </div>
            
            <div class="lm-card">
                <div class="lm-card-label">Other Leave</div>
                <div>
                    <span class="lm-card-value"><?php echo $balance ? number_format( $balance->other_leave_balance, 1 ) : '0'; ?></span>
                    <span class="lm-card-unit">days</span>
                </div>
            </div>
        </div>
        
        <!-- Recent Requests -->
        <h2 class="lm-section-title">Recent Requests</h2>
        
        <?php if ( $requests ) : ?>
            <table class="lm-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $requests as $request ) : ?>
                        <tr>
                            <td><?php echo ucfirst( $request->leave_type ); ?></td>
                            <td><?php echo date( 'M d, Y', strtotime( $request->start_date ) ); ?></td>
                            <td><?php echo date( 'M d, Y', strtotime( $request->end_date ) ); ?></td>
                            <td>
                                <span class="lm-status <?php echo $request->status; ?>">
                                    <?php echo ucfirst( $request->status ); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="lm-empty-state">
                <div class="lm-empty-state-icon">📋</div>
                <div class="lm-empty-state-text">No leave requests yet</div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
