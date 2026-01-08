<?php
/**
 * Redesigned Employee Dashboard
 * Modern, personalized dashboard for employees
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
if (!$current_user->ID) {
    wp_redirect(wp_login_url());
    exit;
}

// Get user leave data
global $wpdb;
$user_data = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}leave_manager_leave_users WHERE email = %s",
    $current_user->user_email
));

if (!$user_data) {
    echo '<div class="leave-manager-page-wrapper"><div style="padding: 40px; text-align: center;"><h2>User information not found.</h2><p>Please contact your administrator.</p></div></div>';
    return;
}

// Get leave balance
$balance = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}leave_manager_leave_balance WHERE user_id = %d",
    $user_data->id
));

// Get upcoming leaves
$upcoming_leaves = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}leave_manager_leave_requests 
     WHERE user_id = %d AND start_date >= DATE(NOW()) AND status = 'approved'
     ORDER BY start_date ASC LIMIT 5",
    $user_data->id
));

// Get pending requests
$pending_requests = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests 
     WHERE user_id = %d AND status = 'pending'",
    $user_data->id
));

// Calculate leave percentages
$annual_used = $balance ? ($balance->annual_used / $balance->annual_total) * 100 : 0;
$sick_used = $balance ? ($balance->sick_used / $balance->sick_total) * 100 : 0;
$other_used = $balance ? ($balance->other_used / $balance->other_total) * 100 : 0;
?>

<div class="leave-manager-page-wrapper">
    <?php Leave_Manager_Frontend_Wrapper::render_page_header(); ?>

    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="welcome-content">
                <h1><?php printf(__('Welcome, %s! 👋', 'leave-manager'), esc_html($user_data->first_name)); ?></h1>
                <p class="welcome-subtitle"><?php echo esc_html($user_data->position . ' | ' . $user_data->department); ?></p>
            </div>
        </div>

        <!-- Leave Balance Cards -->
        <div class="balance-section">
            <h2><?php _e('Your Leave Balance', 'leave-manager'); ?></h2>
            <div class="balance-cards">
                <div class="balance-card balance-card-annual">
                    <div class="balance-header">
                        <h3><?php _e('Annual Leave', 'leave-manager'); ?></h3>
                        <span class="balance-percentage"><?php echo intval($annual_used); ?>%</span>
                    </div>
                    <div class="balance-bar">
                        <div class="balance-progress" style="width: <?php echo intval($annual_used); ?>%"></div>
                    </div>
                    <div class="balance-info">
                        <span class="balance-used"><?php echo intval($balance ? $balance->annual_used : 0); ?> / <?php echo intval($balance ? $balance->annual_total : 0); ?> days</span>
                        <span class="balance-remaining"><?php echo intval($balance ? $balance->annual_total - $balance->annual_used : 0); ?> remaining</span>
                    </div>
                </div>

                <div class="balance-card balance-card-sick">
                    <div class="balance-header">
                        <h3><?php _e('Sick Leave', 'leave-manager'); ?></h3>
                        <span class="balance-percentage"><?php echo intval($sick_used); ?>%</span>
                    </div>
                    <div class="balance-bar">
                        <div class="balance-progress" style="width: <?php echo intval($sick_used); ?>%"></div>
                    </div>
                    <div class="balance-info">
                        <span class="balance-used"><?php echo intval($balance ? $balance->sick_used : 0); ?> / <?php echo intval($balance ? $balance->sick_total : 0); ?> days</span>
                        <span class="balance-remaining"><?php echo intval($balance ? $balance->sick_total - $balance->sick_used : 0); ?> remaining</span>
                    </div>
                </div>

                <div class="balance-card balance-card-other">
                    <div class="balance-header">
                        <h3><?php _e('Other Leave', 'leave-manager'); ?></h3>
                        <span class="balance-percentage"><?php echo intval($other_used); ?>%</span>
                    </div>
                    <div class="balance-bar">
                        <div class="balance-progress" style="width: <?php echo intval($other_used); ?>%"></div>
                    </div>
                    <div class="balance-info">
                        <span class="balance-used"><?php echo intval($balance ? $balance->other_used : 0); ?> / <?php echo intval($balance ? $balance->other_total : 0); ?> days</span>
                        <span class="balance-remaining"><?php echo intval($balance ? $balance->other_total - $balance->other_used : 0); ?> remaining</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-item">
                <div class="stat-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    </svg>
                </div>
                <div class="stat-content">
                    <p class="stat-label"><?php _e('Pending Requests', 'leave-manager'); ?></p>
                    <p class="stat-value"><?php echo intval($pending_requests); ?></p>
                </div>
            </div>
        </div>

        <!-- Upcoming Leaves -->
        <div class="upcoming-section">
            <h2><?php _e('Upcoming Leaves', 'leave-manager'); ?></h2>
            <?php if ($upcoming_leaves): ?>
                <div class="leaves-list">
                    <?php foreach ($upcoming_leaves as $leave): 
                        $days = (strtotime($leave->end_date) - strtotime($leave->start_date)) / (60 * 60 * 24) + 1;
                    ?>
                        <div class="leave-item">
                            <div class="leave-dates">
                                <strong><?php echo date_i18n('M d', strtotime($leave->start_date)) . ' - ' . date_i18n('M d, Y', strtotime($leave->end_date)); ?></strong>
                                <span class="leave-duration"><?php echo intval($days); ?> days</span>
                            </div>
                            <div class="leave-type">
                                <span class="leave-type-badge leave-type-<?php echo esc_attr($leave->leave_type); ?>">
                                    <?php echo ucfirst($leave->leave_type); ?>
                                </span>
                            </div>
                            <div class="leave-status">
                                <span class="status-badge status-<?php echo esc_attr($leave->status); ?>">
                                    ✓ <?php echo ucfirst($leave->status); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                    <h3><?php _e('No Upcoming Leaves', 'leave-manager'); ?></h3>
                    <p><?php _e('You have no approved leave requests scheduled.', 'leave-manager'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><?php _e('Quick Actions', 'leave-manager'); ?></h2>
            <div class="actions-grid">
                <a href="<?php echo home_url('/index.php/leave-management/request/'); ?>" class="action-btn action-btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"></path>
                    </svg>
                    <?php _e('Request Leave', 'leave-manager'); ?>
                </a>
                <a href="<?php echo home_url('/index.php/leave-management/calendar/'); ?>" class="action-btn action-btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <?php _e('View Calendar', 'leave-manager'); ?>
                </a>
                <a href="<?php echo home_url('/index.php/leave-management/history/'); ?>" class="action-btn action-btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"></path>
                        <path d="M21 3v5h-5"></path>
                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"></path>
                        <path d="M3 21v-5h5"></path>
                    </svg>
                    <?php _e('Leave History', 'leave-manager'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.leave-manager-page-wrapper {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    padding-bottom: 40px;
}

.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 30px;
}

.welcome-section {
    background: white;
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 40px;
}

.welcome-section h1 {
    margin: 0;
    font-size: 32px;
    color: #1a1a1a;
}

.welcome-subtitle {
    margin: 8px 0 0 0;
    color: #666;
    font-size: 16px;
}

.balance-section {
    margin-bottom: 40px;
}

.balance-section h2 {
    margin: 0 0 20px 0;
    font-size: 20px;
    color: #1a1a1a;
}

.balance-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
}

.balance-card {
    background: white;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.balance-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.balance-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.balance-header h3 {
    margin: 0;
    font-size: 16px;
    color: #1a1a1a;
    font-weight: 600;
}

.balance-percentage {
    font-size: 24px;
    font-weight: bold;
    color: #4A5FFF;
}

.balance-bar {
    height: 8px;
    background: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 12px;
}

.balance-progress {
    height: 100%;
    background: linear-gradient(90deg, #4A5FFF 0%, #ff9800 100%);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.balance-card-annual .balance-progress {
    background: linear-gradient(90deg, #4A5FFF 0%, #ff9800 100%);
}

.balance-card-sick .balance-progress {
    background: linear-gradient(90deg, #f44336 0%, #e91e63 100%);
}

.balance-card-other .balance-progress {
    background: linear-gradient(90deg, #2196f3 0%, #00bcd4 100%);
}

.balance-info {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
}

.balance-used {
    color: #1a1a1a;
    font-weight: 600;
}

.balance-remaining {
    color: #999;
}

.quick-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 40px;
}

.stat-item {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    background: linear-gradient(135deg, #4A5FFF 0%, #ff9800 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-label {
    margin: 0;
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    margin: 4px 0 0 0;
    font-size: 28px;
    font-weight: bold;
    color: #1a1a1a;
}

.upcoming-section {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 40px;
}

.upcoming-section h2 {
    margin: 0 0 20px 0;
    font-size: 20px;
    color: #1a1a1a;
}

.leaves-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.leave-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 16px;
    background: #f9f9f9;
    border-radius: 8px;
    border-left: 4px solid #4A5FFF;
    transition: background 0.3s ease;
}

.leave-item:hover {
    background: #f0f0f0;
}

.leave-dates {
    flex: 1;
}

.leave-dates strong {
    display: block;
    margin-bottom: 4px;
    color: #1a1a1a;
}

.leave-duration {
    font-size: 12px;
    color: #999;
}

.leave-type {
    flex: 0.3;
}

.leave-type-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.leave-type-annual {
    background: #fff3cd;
    color: #856404;
}

.leave-type-sick {
    background: #f8d7da;
    color: #721c24;
}

.leave-type-other {
    background: #d1ecf1;
    color: #0c5460;
}

.leave-status {
    flex: 0.2;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    background: #d4edda;
    color: #155724;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.empty-state svg {
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state h3 {
    margin: 16px 0 8px 0;
    color: #666;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

.quick-actions {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.quick-actions h2 {
    margin: 0 0 20px 0;
    font-size: 20px;
    color: #1a1a1a;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 16px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    text-align: center;
}

.action-btn-primary {
    background: linear-gradient(135deg, #4A5FFF 0%, #ff9800 100%);
    color: white;
}

.action-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.action-btn-secondary {
    background: #f0f0f0;
    color: #666;
    border: 2px solid #e0e0e0;
}

.action-btn-secondary:hover {
    background: #e0e0e0;
    color: #1a1a1a;
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 20px 16px;
    }

    .welcome-section {
        padding: 24px;
    }

    .welcome-section h1 {
        font-size: 24px;
    }

    .balance-cards {
        grid-template-columns: 1fr;
    }

    .leave-item {
        flex-direction: column;
        align-items: flex-start;
    }

    .leave-type,
    .leave-status {
        width: 100%;
    }

    .actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>
