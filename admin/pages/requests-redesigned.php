<?php
/**
 * Redesigned Leave Requests Management Page
 * Modern UI with advanced filtering and status management
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'all';
$type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all';
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Build query
$where = array('1=1');
if ($status_filter !== 'all') {
    $where[] = $wpdb->prepare('lr.status = %s', $status_filter);
}
if ($type_filter !== 'all') {
    $where[] = $wpdb->prepare('lr.leave_type = %s', $type_filter);
}
if ($search) {
    $where[] = $wpdb->prepare('(lmu.first_name LIKE %s OR lmu.last_name LIKE %s OR lmu.email LIKE %s)', 
        '%' . $search . '%', '%' . $search . '%', '%' . $search . '%');
}

$where_clause = implode(' AND ', $where);

// Get requests
$requests = $wpdb->get_results("
    SELECT lr.*, lmu.first_name, lmu.last_name, lmu.email, lmu.department
    FROM {$wpdb->prefix}leave_manager_leave_requests lr
    JOIN {$wpdb->prefix}leave_manager_leave_users lmu ON lr.user_id = lmu.id
    WHERE $where_clause
    ORDER BY lr.created_at DESC
");

// Get counts
$all_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests");
$pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests WHERE status = 'pending'");
$approved_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests WHERE status = 'approved'");
$rejected_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}leave_manager_leave_requests WHERE status = 'rejected'");
?>

<div class="leave-manager-requests-wrapper">
    <!-- Header -->
    <div class="page-header">
        <div class="header-content">
            <h1><?php _e('Leave Requests', 'leave-manager'); ?></h1>
            <p class="subtitle"><?php _e('Manage and approve employee leave requests', 'leave-manager'); ?></p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="<?php echo admin_url('admin.php?page=leave-manager-requests'); ?>" class="filter-tab <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
            <span class="tab-label"><?php _e('All Requests', 'leave-manager'); ?></span>
            <span class="tab-count"><?php echo intval($all_count); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=leave-manager-requests&status=pending'); ?>" class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
            <span class="tab-label"><?php _e('Pending', 'leave-manager'); ?></span>
            <span class="tab-count"><?php echo intval($pending_count); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=leave-manager-requests&status=approved'); ?>" class="filter-tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
            <span class="tab-label"><?php _e('Approved', 'leave-manager'); ?></span>
            <span class="tab-count"><?php echo intval($approved_count); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=leave-manager-requests&status=rejected'); ?>" class="filter-tab <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
            <span class="tab-label"><?php _e('Rejected', 'leave-manager'); ?></span>
            <span class="tab-count"><?php echo intval($rejected_count); ?></span>
        </a>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="get" action="">
            <input type="hidden" name="page" value="leave-manager-requests">
            <input type="hidden" name="status" value="<?php echo esc_attr($status_filter); ?>">
            
            <div class="filter-group">
                <input type="text" name="search" placeholder="<?php _e('Search by name or email...', 'leave-manager'); ?>" value="<?php echo esc_attr($search); ?>" class="filter-input">
                <select name="type" class="filter-select">
                    <option value="all"><?php _e('All Leave Types', 'leave-manager'); ?></option>
                    <option value="annual" <?php selected($type_filter, 'annual'); ?>><?php _e('Annual Leave', 'leave-manager'); ?></option>
                    <option value="sick" <?php selected($type_filter, 'sick'); ?>><?php _e('Sick Leave', 'leave-manager'); ?></option>
                    <option value="other" <?php selected($type_filter, 'other'); ?>><?php _e('Other Leave', 'leave-manager'); ?></option>
                </select>
                <button type="submit" class="btn btn-primary"><?php _e('Filter', 'leave-manager'); ?></button>
            </div>
        </form>
    </div>

    <!-- Requests Table -->
    <div class="requests-table-section">
        <?php if ($requests): ?>
            <div class="table-responsive">
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th><?php _e('Employee', 'leave-manager'); ?></th>
                            <th><?php _e('Leave Type', 'leave-manager'); ?></th>
                            <th><?php _e('Dates', 'leave-manager'); ?></th>
                            <th><?php _e('Days', 'leave-manager'); ?></th>
                            <th><?php _e('Status', 'leave-manager'); ?></th>
                            <th><?php _e('Submitted', 'leave-manager'); ?></th>
                            <th><?php _e('Actions', 'leave-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): 
                            $days = (strtotime($request->end_date) - strtotime($request->start_date)) / (60 * 60 * 24) + 1;
                        ?>
                            <tr class="request-row request-status-<?php echo esc_attr($request->status); ?>">
                                <td class="employee-cell">
                                    <div class="employee-info">
                                        <div class="employee-avatar">
                                            <?php echo strtoupper(substr($request->first_name, 0, 1) . substr($request->last_name, 0, 1)); ?>
                                        </div>
                                        <div>
                                            <strong><?php echo esc_html($request->first_name . ' ' . $request->last_name); ?></strong>
                                            <br>
                                            <small><?php echo esc_html($request->email); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="leave-type-badge leave-type-<?php echo esc_attr($request->leave_type); ?>">
                                        <?php echo ucfirst($request->leave_type); ?>
                                    </span>
                                </td>
                                <td class="date-cell">
                                    <?php echo date_i18n('M d', strtotime($request->start_date)) . ' - ' . date_i18n('M d, Y', strtotime($request->end_date)); ?>
                                </td>
                                <td class="days-cell">
                                    <strong><?php echo intval($days); ?></strong>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($request->status); ?>">
                                        <?php echo ucfirst($request->status); ?>
                                    </span>
                                </td>
                                <td class="date-cell">
                                    <small><?php echo date_i18n('M d, Y', strtotime($request->created_at)); ?></small>
                                </td>
                                <td class="actions-cell">
                                    <?php if ($request->status === 'pending'): ?>
                                        <button class="btn-action btn-approve" data-id="<?php echo intval($request->id); ?>" title="<?php _e('Approve', 'leave-manager'); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="20 6 9 17 4 12"></polyline>
                                            </svg>
                                        </button>
                                        <button class="btn-action btn-reject" data-id="<?php echo intval($request->id); ?>" title="<?php _e('Reject', 'leave-manager'); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                            </svg>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?php echo admin_url('admin.php?page=leave-manager-requests&action=view&id=' . $request->id); ?>" class="btn-action btn-view" title="<?php _e('View Details', 'leave-manager'); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                </svg>
                <h3><?php _e('No Requests Found', 'leave-manager'); ?></h3>
                <p><?php _e('There are no leave requests matching your filters.', 'leave-manager'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.leave-manager-requests-wrapper {
    padding: 30px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
}

.page-header {
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0;
    font-size: 28px;
    color: #1a1a1a;
}

.page-header .subtitle {
    margin: 8px 0 0 0;
    color: #666;
    font-size: 14px;
}

.filter-tabs {
    display: flex;
    gap: 12px;
    margin-bottom: 24px;
    background: white;
    padding: 12px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow-x: auto;
}

.filter-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 8px;
    background: #f0f0f0;
    color: #666;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    white-space: nowrap;
}

.filter-tab:hover {
    background: #e0e0e0;
}

.filter-tab.active {
    background: linear-gradient(135deg, #4A5FFF 0%, #ff9800 100%);
    color: white;
}

.tab-count {
    background: rgba(0, 0, 0, 0.1);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.filter-tab.active .tab-count {
    background: rgba(255, 255, 255, 0.3);
}

.filters-section {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 24px;
}

.filter-group {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.filter-input,
.filter-select {
    padding: 10px 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.filter-input {
    flex: 1;
    min-width: 200px;
}

.filter-input:focus,
.filter-select:focus {
    outline: none;
    border-color: #4A5FFF;
    box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.1);
}

.requests-table-section {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.table-responsive {
    overflow-x: auto;
}

.requests-table {
    width: 100%;
    border-collapse: collapse;
}

.requests-table thead {
    background: #f9f9f9;
    border-bottom: 2px solid #eee;
}

.requests-table th {
    padding: 16px;
    text-align: left;
    font-weight: 600;
    color: #1a1a1a;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.requests-table tbody tr {
    border-bottom: 1px solid #eee;
    transition: background 0.3s ease;
}

.requests-table tbody tr:hover {
    background: #f9f9f9;
}

.requests-table td {
    padding: 16px;
    font-size: 14px;
    color: #1a1a1a;
}

.employee-cell {
    min-width: 250px;
}

.employee-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.employee-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 14px;
    flex-shrink: 0;
}

.employee-info strong {
    display: block;
    margin-bottom: 4px;
}

.employee-info small {
    color: #999;
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

.date-cell {
    min-width: 150px;
}

.days-cell {
    text-align: center;
}

.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-approved {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.actions-cell {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
}

.btn-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    background: #f0f0f0;
    color: #666;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.btn-action:hover {
    background: #e0e0e0;
}

.btn-approve:hover {
    background: #4caf50;
    color: white;
}

.btn-reject:hover {
    background: #f44336;
    color: white;
}

.btn-view:hover {
    background: #2196f3;
    color: white;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
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

.btn {
    display: inline-block;
    padding: 10px 20px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    font-size: 14px;
}

.btn-primary {
    background: linear-gradient(135deg, #4A5FFF 0%, #ff9800 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

@media (max-width: 768px) {
    .leave-manager-requests-wrapper {
        padding: 16px;
    }

    .filter-tabs {
        flex-wrap: wrap;
    }

    .filter-group {
        flex-direction: column;
    }

    .filter-input,
    .filter-select {
        width: 100%;
    }

    .requests-table th,
    .requests-table td {
        padding: 12px 8px;
        font-size: 12px;
    }

    .employee-cell {
        min-width: auto;
    }

    .date-cell {
        min-width: auto;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Approve button
    $(document).on('click', '.btn-approve', function() {
        const id = $(this).data('id');
        if (confirm('Are you sure you want to approve this request?')) {
            // Handle approval via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'approve_leave_request',
                    id: id,
                    nonce: '<?php echo wp_create_nonce('approve_leave_request'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });

    // Reject button
    $(document).on('click', '.btn-reject', function() {
        const id = $(this).data('id');
        const reason = prompt('Please enter rejection reason:');
        if (reason !== null) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'reject_leave_request',
                    id: id,
                    reason: reason,
                    nonce: '<?php echo wp_create_nonce('reject_leave_request'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });
});
</script>
