<?php
/**
 * Pending Approvals Page - ChatPanel Leave Manager
 * Manage and approve new employee signups
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check user permissions
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $action = sanitize_text_field($_POST['action']);
        $user_id = intval($_POST['user_id']);
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        if ($action === 'approve') {
            // Update user status
            global $wpdb;
            $users_table = $wpdb->prefix . 'leave_manager_users';

            $wpdb->update(
                $users_table,
                array(
                    'status' => 'active',
                    'approved_at' => current_time('mysql'),
                    'approved_by' => get_current_user_id(),
                    'notes' => $notes,
                ),
                array('user_id' => $user_id),
                array('%s', '%s', '%d', '%s'),
                array('%d')
            );

            // Get user details
            $user = get_user_by('ID', $user_id);
            $user_email = $user->user_email;
            $user_name = $user->first_name . ' ' . $user->last_name;

            // Send approval email
            $subject = 'Your ChatPanel Leave Manager Account Has Been Approved';
            $message = sprintf(
                '<html><body style="font-family: Arial, sans-serif; background-color: #f9fafb;">
                <div style="max-width: 600px; margin: 0 auto; background-color: white; padding: 40px; border-radius: 8px;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h1 style="color: #4A5FFF; margin: 0;">ChatPanel</h1>
                        <p style="color: #6B7280; margin: 5px 0 0 0;">Leave Manager</p>
                    </div>
                    <h2 style="color: #111827; margin-bottom: 20px;">Welcome, %s!</h2>
                    <p style="color: #374151; line-height: 1.6; margin-bottom: 20px;">
                        Great news! Your account has been approved and is now active. You can now log in and start using ChatPanel Leave Manager.
                    </p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="%s" style="background-color: #4A5FFF; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; display: inline-block;">
                            Log In Now
                        </a>
                    </div>
                    <h3 style="color: #111827; margin-top: 30px; margin-bottom: 15px;">Getting Started</h3>
                    <ul style="color: #374151; line-height: 1.8; padding-left: 20px;">
                        <li>View your leave balance</li>
                        <li>Submit leave requests</li>
                        <li>Track approval status</li>
                        <li>View team calendar</li>
                    </ul>
                    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
                    <p style="color: #6B7280; font-size: 12px; margin: 0;">
                        If you have any questions, please contact your administrator.
                    </p>
                </div>
                </body></html>',
                esc_html($user_name),
                esc_url(wp_login_url())
            );

            $headers = array('Content-Type: text/html; charset=UTF-8');
            wp_mail($user_email, $subject, $message, $headers);

            echo '<div class="notice notice-success"><p>User approved successfully!</p></div>';

        } elseif ($action === 'reject') {
            // Delete user and Leave Manager record
            global $wpdb;
            $users_table = $wpdb->prefix . 'leave_manager_users';

            $wpdb->delete(
                $users_table,
                array('user_id' => $user_id),
                array('%d')
            );

            wp_delete_user($user_id);

            echo '<div class="notice notice-success"><p>User rejected and deleted.</p></div>';
        }
    }
}

// Get pending approvals
global $wpdb;
$users_table = $wpdb->prefix . 'leave_manager_users';

$pending_users = $wpdb->get_results(
    "SELECT u.*, wu.user_email, wu.user_login, wu.user_registered
    FROM {$users_table} u
    JOIN {$wpdb->users} wu ON u.user_id = wu.ID
    WHERE u.status = 'pending_approval'
    ORDER BY u.created_at DESC"
);

?>
<div class="wrap lm-page-wrapper">
    <div class="page-header">
        <div>
            <h1>Pending Approvals</h1>
            <p>Review and approve new employee signups</p>
        </div>
    </div>

    <div class="content-wrapper">
        <div class="content-main">
            <?php if (empty($pending_users)): ?>
                <div class="lm-card">
                    <div class="lm-empty-state">
                        <span class="lm-empty-state-icon">✓</span>
                        <h3>No Pending Approvals</h3>
                        <p>All new employee signups have been reviewed and approved.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="lm-table-wrapper">
                    <table class="lm-table">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Signup Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_users as $user): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></strong></td>
                                    <td><?php echo esc_html($user->email); ?></td>
                                    <td><?php echo esc_html($user->department ?: 'Not specified'); ?></td>
                                    <td><?php echo esc_html(date('M d, Y', strtotime($user->created_at))); ?></td>
                                    <td>
                                        <button class="lm-btn-approve" onclick="approveUser(<?php echo intval($user->user_id); ?>)">Approve</button>
                                        <button class="lm-btn-reject" onclick="rejectUser(<?php echo intval($user->user_id); ?>)">Reject</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Approval Modal -->
                <div id="approval-modal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Approve Employee</h2>
                            <button class="modal-close" onclick="closeModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <form id="approval-form" method="POST">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="user_id" id="approval-user-id">

                                <div class="form-group">
                                    <label class="form-label">Notes (Optional)</label>
                                    <textarea name="notes" class="form-textarea" placeholder="Add any notes about this approval..."></textarea>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="lm-btn-secondary" onclick="closeModal()">Cancel</button>
                                    <button type="submit" class="lm-btn-approve">Approve Employee</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Rejection Modal -->
                <div id="rejection-modal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Reject Employee</h2>
                            <button class="modal-close" onclick="closeModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <p style="color: #6B7280; margin-bottom: 20px;">
                                Are you sure you want to reject this employee signup? This action cannot be undone.
                            </p>
                            <form id="rejection-form" method="POST">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="user_id" id="rejection-user-id">

                                <div class="modal-footer">
                                    <button type="button" class="lm-btn-secondary" onclick="closeModal()">Cancel</button>
                                    <button type="submit" class="lm-btn-reject">Reject Employee</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="content-sidebar">
            <div class="lm-card">
                <h3>Approval Stats</h3>
                <div class="lm-stat-grid">
                    <div class="lm-stat-card">
                        <div class="lm-stat-label">Pending</div>
                        <div class="lm-stat-value"><?php echo count($pending_users); ?></div>
                    </div>
                </div>
            </div>

            <div class="lm-card">
                <h3>Quick Actions</h3>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 12px;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=leave-manager-staff')); ?>" style="color: #4A5FFF; text-decoration: none;">
                            Manage All Employees
                        </a>
                    </li>
                    <li style="margin-bottom: 12px;">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=leave-manager-settings')); ?>" style="color: #4A5FFF; text-decoration: none;">
                            Settings
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=leave-manager-system')); ?>" style="color: #4A5FFF; text-decoration: none;">
                            System Status
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    align-items: center;
    justify-content: center;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 24px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #111827;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #6B7280;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    padding: 24px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

.form-textarea {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    min-height: 100px;
}

.form-textarea:focus {
    outline: none;
    border-color: #4A5FFF;
    box-shadow: 0 0 0 3px rgba(74, 95, 255, 0.1);
}
</style>

<script>
function approveUser(userId) {
    document.getElementById('approval-user-id').value = userId;
    document.getElementById('approval-modal').classList.add('active');
}

function rejectUser(userId) {
    document.getElementById('rejection-user-id').value = userId;
    document.getElementById('rejection-modal').classList.add('active');
}

function closeModal() {
    document.getElementById('approval-modal').classList.remove('active');
    document.getElementById('rejection-modal').classList.remove('active');
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const approvalModal = document.getElementById('approval-modal');
    const rejectionModal = document.getElementById('rejection-modal');

    if (event.target === approvalModal) {
        approvalModal.classList.remove('active');
    }
    if (event.target === rejectionModal) {
        rejectionModal.classList.remove('active');
    }
});
</script>
