<?php
/**
 * Professional Reports Page - Real Database Data
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$requests_table = $wpdb->prefix . 'leave_manager_leave_requests';
$users_table = $wpdb->prefix . 'leave_manager_leave_users';

// Leave Report Statistics
$leave_stats = $wpdb->get_row("SELECT COUNT(*) as total_requests, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected, SUM(CASE WHEN status = 'approved' THEN days ELSE 0 END) as days_taken FROM $requests_table");

// Leave by type
$leave_by_type = $wpdb->get_results("SELECT leave_type, COUNT(*) as total_requests, SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected, SUM(CASE WHEN status = 'approved' THEN days ELSE 0 END) as days_taken FROM $requests_table GROUP BY leave_type", ARRAY_A);

// User Report Data
$user_report = $wpdb->get_results("SELECT u.first_name, u.last_name, u.department, COUNT(r.request_id) as total_requests, SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN r.status = 'approved' THEN r.days ELSE 0 END) as days_taken FROM $users_table u LEFT JOIN $requests_table r ON u.user_id = r.user_id GROUP BY u.user_id, u.first_name, u.last_name, u.department", ARRAY_A);

// Department Report Data
$dept_report = $wpdb->get_results("SELECT u.department, COUNT(DISTINCT u.user_id) as total_employees, COUNT(r.request_id) as total_requests, SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END) as approved, SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending, SUM(CASE WHEN r.status = 'approved' THEN r.days ELSE 0 END) as days_taken FROM $users_table u LEFT JOIN $requests_table r ON u.user_id = r.user_id GROUP BY u.department", ARRAY_A);

// Get unique departments and users for filters
$departments = $wpdb->get_col("SELECT DISTINCT department FROM $users_table WHERE department IS NOT NULL AND department != ''");
$users = $wpdb->get_results("SELECT user_id, first_name, last_name FROM $users_table", ARRAY_A);

include 'admin-page-template.php';
?>

<div class="leave-manager-admin-container">
<div class="lm-page-content">
<div class="page-header">
    <div>
        <h1><?php esc_html_e( 'Reports', 'leave-manager' ); ?></h1>
        <p class="subtitle"><?php esc_html_e( 'View detailed leave analytics and reports', 'leave-manager' ); ?></p>
    </div>
</div>

<div class="admin-tabs">
    <button class="admin-tab active" data-tab="leave-reports"><?php esc_html_e( 'Leave Reports', 'leave-manager' ); ?></button>
    <button class="admin-tab" data-tab="user-reports"><?php esc_html_e( 'User Reports', 'leave-manager' ); ?></button>
    <button class="admin-tab" data-tab="department-reports"><?php esc_html_e( 'Department Reports', 'leave-manager' ); ?></button>
</div>

<div class="content-wrapper">
    <div class="content-main">
        <!-- Leave Reports Tab -->
        <div class="lm-tab-content active" id="leave-reports">
            <div class="lm-card">
                <div class="lm-form-group">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                        <div><label>Start Date</label><input type="date" id="leave-start-date" class="lm-input"></div>
                        <div><label>End Date</label><input type="date" id="leave-end-date" class="lm-input"></div>
                        <div><label>Leave Type</label>
                            <select id="leave-type" class="lm-select">
                                <option value="">All Types</option>
                                <option value="annual">Annual Leave</option>
                                <option value="sick">Sick Leave</option>
                                <option value="other">Other Leave</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="lm-form-actions">
                    <button class="lm-btn lm-btn-primary" id="btn-generate-leave">Generate Report</button>
                    <button class="lm-btn lm-btn-secondary" id="btn-export-leave">Export CSV</button>
                </div>

                <div class="lm-stat-grid">
                    <div class="lm-stat-card"><div class="lm-stat-label">TOTAL REQUESTS</div><div class="lm-stat-value" id="stat-total"><?php echo intval($leave_stats->total_requests ?? 0); ?></div></div>
                    <div class="lm-stat-card"><div class="lm-stat-label">APPROVED</div><div class="lm-stat-value" style="color: #10B981;" id="stat-approved"><?php echo intval($leave_stats->approved ?? 0); ?></div></div>
                    <div class="lm-stat-card"><div class="lm-stat-label">PENDING</div><div class="lm-stat-value" style="color: #F59E0B;" id="stat-pending"><?php echo intval($leave_stats->pending ?? 0); ?></div></div>
                    <div class="lm-stat-card"><div class="lm-stat-label">REJECTED</div><div class="lm-stat-value" style="color: #EF4444;" id="stat-rejected"><?php echo intval($leave_stats->rejected ?? 0); ?></div></div>
                    <div class="lm-stat-card"><div class="lm-stat-label">DAYS TAKEN</div><div class="lm-stat-value" id="stat-days"><?php echo intval($leave_stats->days_taken ?? 0); ?></div></div>
                </div>

                <div class="lm-table-wrapper">
                    <table class="lm-table">
                        <thead><tr><th>Leave Type</th><th>Total Requests</th><th>Approved</th><th>Pending</th><th>Rejected</th><th>Days Taken</th></tr></thead>
                        <tbody id="leave-report-body">
                            <?php if (!empty($leave_by_type)): foreach ($leave_by_type as $row): ?>
                            <tr>
                                <td><strong><?php echo esc_html(ucfirst($row['leave_type'] ?? 'Unknown')); ?></strong></td>
                                <td><?php echo intval($row['total_requests']); ?></td>
                                <td style="color: #10B981;"><?php echo intval($row['approved']); ?></td>
                                <td style="color: #F59E0B;"><?php echo intval($row['pending']); ?></td>
                                <td style="color: #EF4444;"><?php echo intval($row['rejected']); ?></td>
                                <td><?php echo intval($row['days_taken']); ?> days</td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="6" style="text-align:center;">No leave requests found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- User Reports Tab -->
        <div class="lm-tab-content" id="user-reports">
            <div class="lm-card">
                <div class="lm-form-group">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                        <div><label>Department</label>
                            <select id="user-dept-filter" class="lm-select">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo esc_attr($dept); ?>"><?php echo esc_html($dept); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="lm-form-actions">
                    <button class="lm-btn lm-btn-primary" id="btn-generate-user">Generate Report</button>
                    <button class="lm-btn lm-btn-secondary" id="btn-export-user">Export CSV</button>
                </div>

                <div class="lm-table-wrapper">
                    <table class="lm-table">
                        <thead><tr><th>Employee Name</th><th>Department</th><th>Total Requests</th><th>Approved</th><th>Pending</th><th>Days Taken</th></tr></thead>
                        <tbody id="user-report-body">
                            <?php if (!empty($user_report)): foreach ($user_report as $row): ?>
                            <tr>
                                <td><strong><?php echo esc_html(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')); ?></strong></td>
                                <td><?php echo esc_html($row['department'] ?? 'N/A'); ?></td>
                                <td><?php echo intval($row['total_requests']); ?></td>
                                <td style="color: #10B981;"><?php echo intval($row['approved']); ?></td>
                                <td style="color: #F59E0B;"><?php echo intval($row['pending']); ?></td>
                                <td><?php echo intval($row['days_taken']); ?> days</td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="6" style="text-align:center;">No user data found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Department Reports Tab -->
        <div class="lm-tab-content" id="department-reports">
            <div class="lm-card">
                <div class="lm-form-group">
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                        <div><label>Start Date</label><input type="date" id="dept-start-date" class="lm-input"></div>
                        <div><label>End Date</label><input type="date" id="dept-end-date" class="lm-input"></div>
                    </div>
                </div>
                <div class="lm-form-actions">
                    <button class="lm-btn lm-btn-primary" id="btn-generate-dept">Generate Report</button>
                    <button class="lm-btn lm-btn-secondary" id="btn-export-dept">Export CSV</button>
                </div>

                <div class="lm-table-wrapper">
                    <table class="lm-table">
                        <thead><tr><th>Department</th><th>Total Employees</th><th>Total Requests</th><th>Approved</th><th>Pending</th><th>Days Taken</th></tr></thead>
                        <tbody id="dept-report-body">
                            <?php if (!empty($dept_report)): foreach ($dept_report as $row): ?>
                            <tr>
                                <td><strong><?php echo esc_html($row['department'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo intval($row['total_employees']); ?></td>
                                <td><?php echo intval($row['total_requests']); ?></td>
                                <td style="color: #10B981;"><?php echo intval($row['approved']); ?></td>
                                <td style="color: #F59E0
B;"><?php echo intval($row['pending']); ?></td>
                                <td><?php echo intval($row['days_taken']); ?> days</td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="6" style="text-align:center;">No department data found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="content-sidebar">
        <div class="lm-card">
            <h3>Report Filters</h3>
            <p>Use the filters above to customize your reports.</p>
        </div>
        <div class="lm-card">
            <h3>Export Options</h3>
            <p>Export reports as CSV for use in spreadsheets and other tools.</p>
        </div>
        <div class="lm-card">
            <h3>Quick Links</h3>
            <ul style="list-style:none;padding:0;margin:0;">
                <li style="margin-bottom:8px;"><a href="<?php echo esc_url(admin_url('admin.php?page=leave-manager-management')); ?>">Dashboard</a></li>
                <li style="margin-bottom:8px;"><a href="<?php echo esc_url(admin_url('admin.php?page=leave-manager-requests')); ?>">Requests</a></li>
                <li style="margin-bottom:8px;"><a href="<?php echo esc_url(admin_url('admin.php?page=leave-manager-staff')); ?>">Staff</a></li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=leave-manager-settings')); ?>">Settings</a></li>
            </ul>
        </div>
    </div>
</div>
</div>
</div>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';
    var lm_nonce = '<?php echo wp_create_nonce("leave_manager_admin_nonce"); ?>';

    // Tab switching
    $('.admin-tab').on('click', function() {
        var tabId = $(this).data('tab');
        $('.lm-tab-content').removeClass('active');
        $('.admin-tab').removeClass('active');
        $('#' + tabId).addClass('active');
        $(this).addClass('active');
    });

    // Generate Leave Report
    window.generateLeaveReport = function() {
        var formData = new FormData();
        formData.append('action', 'leave_manager_generate_leave_report');
        formData.append('nonce', lm_nonce);
        formData.append('start_date', $('#leave-start-date').val());
        formData.append('end_date', $('#leave-end-date').val());
        formData.append('leave_type', $('#leave-type').val());

        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.data) {
                    var html = '';
                    data.data.data.forEach(function(row) {
                        html += '<tr><td><strong>' + (row.leave_type || 'Unknown') + '</strong></td>';
                        html += '<td>' + (row.total_requests || 0) + '</td>';
                        html += '<td style="color:#10B981;">' + (row.approved || 0) + '</td>';
                        html += '<td style="color:#F59E0B;">' + (row.pending || 0) + '</td>';
                        html += '<td style="color:#EF4444;">' + (row.rejected || 0) + '</td>';
                        html += '<td>' + (row.days_taken || 0) + ' days</td></tr>';
                    });
                    $('#leave-report-body').html(html || '<tr><td colspan="6" style="text-align:center;">No data found</td></tr>');
                    alert('Report generated successfully!');
                } else {
                    alert('Error: ' + (data.data ? data.data.message : 'Unknown error'));
                }
            })
            .catch(err => { console.error(err); alert('Failed to generate report'); });
    };

    // Export Leave Report
    window.exportLeaveReport = function() {
        var formData = new FormData();
        formData.append('action', 'leave_manager_export_leave_report');
        formData.append('nonce', lm_nonce);
        formData.append('start_date', $('#leave-start-date').val());
        formData.append('end_date', $('#leave-end-date').val());
        formData.append('leave_type', $('#leave-type').val());

        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.csv) {
                    downloadCSV(data.data.csv, data.data.filename || 'leave_report.csv');
                } else {
                    alert('Error: ' + (data.data ? data.data.message : 'Unknown error'));
                }
            })
            .catch(err => { console.error(err); alert('Failed to export report'); });
    };

    // Generate User Report
    window.generateUserReport = function() {
        var formData = new FormData();
        formData.append('action', 'leave_manager_generate_user_report');
        formData.append('nonce', lm_nonce);
        formData.append('department', $('#user-dept-filter').val());

        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.data) {
                    var html = '';
                    data.data.data.forEach(function(row) {
                        html += '<tr><td><strong>' + (row.first_name || '') + ' ' + (row.last_name || '') + '</strong></td>';
                        html += '<td>' + (row.department || 'N/A') + '</td>';
                        html += '<td>' + (row.total_requests || 0) + '</td>';
                        html += '<td style="color:#10B981;">' + (row.approved || 0) + '</td>';
                        html += '<td style="color:#F59E0B;">' + (row.pending || 0) + '</td>';
                        html += '<td>' + (row.days_taken || 0) + ' days</td></tr>';
                    });
                    $('#user-report-body').html(html || '<tr><td colspan="6" style="text-align:center;">No data found</td></tr>');
                    alert('Report generated successfully!');
                } else {
                    alert('Error: ' + (data.data ? data.data.message : 'Unknown error'));
                }
            })
            .catch(err => { console.error(err); alert('Failed to generate report'); });
    };

    // Export User Report
    window.exportUserReport = function() {
        var formData = new FormData();
        formData.append('action', 'leave_manager_export_user_report');
        formData.append('nonce', lm_nonce);

        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.csv) {
                    downloadCSV(data.data.csv, data.data.filename || 'user_report.csv');
                } else {
                    alert('Error: ' + (data.data ? data.data.message : 'Unknown error'));
                }
            })
            .catch(err => { console.error(err); alert('Failed to export report'); });
    };

    // Generate Department Report
    window.generateDepartmentReport = function() {
        var formData = new FormData();
        formData.append('action', 'leave_manager_generate_department_report');
        formData.append('nonce', lm_nonce);
        formData.append('start_date', $('#dept-start-date').val());
        formData.append('end_date', $('#dept-end-date').val());

        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.data) {
                    var html = '';
                    data.data.data.forEach(function(row) {
                        html += '<tr><td><strong>' + (row.department || 'N/A') + '</strong></td>';
                        html += '<td>' + (row.total_employees || 0) + '</td>';
                        html += '<td>' + (row.total_requests || 0) + '</td>';
                        html += '<td style="color:#10B981;">' + (row.approved || 0) + '</td>';
                        html += '<td style="color:#F59E0B;">' + (row.pending || 0) + '</td>';
                        html += '<td>' + (row.days_taken || 0) + ' days</td></tr>';
                    });
                    $('#dept-report-body').html(html || '<tr><td colspan="6" style="text-align:center;">No data found</td></tr>');
                    alert('Report generated successfully!');
                } else {
                    alert('Error: ' + (data.data ? data.data.message : 'Unknown error'));
                }
            })
            .catch(err => { console.error(err); alert('Failed to generate report'); });
    };

    // Export Department Report
    window.exportDepartmentReport = function() {
        var formData = new FormData();
        formData.append('action', 'leave_manager_export_department_report');
        formData.append('nonce', lm_nonce);

        fetch(ajaxurl, { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data.csv) {
                    downloadCSV(data.data.csv, data.data.filename || 'department_report.csv');
                } else {
                    alert('Error: ' + (data.data ? data.data.message : 'Unknown error'));
                }
            })
            .catch(err => { console.error(err); alert('Failed to export report'); });
    };

    // Download CSV helper
    function downloadCSV(csv, filename) {
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Button click handlers
    $('#btn-generate-leave').on('click', generateLeaveReport);
    $('#btn-export-leave').on('click', exportLeaveReport);
    $('#btn-generate-user').on('click', generateUserReport);
    $('#btn-export-user').on('click', exportUserReport);
    $('#btn-generate-dept').on('click', generateDepartmentReport);
    $('#btn-export-dept').on('click', exportDepartmentReport);
});
</script>
