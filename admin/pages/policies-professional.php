<?php
/**
 * Professional Leave Policies Page
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$policies_table = $wpdb->prefix . 'leave_manager_leave_policies';
$users_table = $wpdb->prefix . 'leave_manager_leave_users';

// Check if policies table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$policies_table'") === $policies_table;

// Get all policies
$policies = array();
if ($table_exists) {
    $policies = $wpdb->get_results("SELECT * FROM $policies_table ORDER BY created_at DESC", ARRAY_A);
}

// Get all users for assignment
$users = $wpdb->get_results("SELECT user_id, first_name, last_name, email, department, policy_id FROM $users_table ORDER BY first_name ASC", ARRAY_A);

// Get policy statistics
$total_policies = count($policies);
$active_policies = 0;
$total_assigned = 0;
foreach ($policies as $policy) {
    if (($policy['status'] ?? 'active') === 'active') {
        $active_policies++;
    }
}
foreach ($users as $user) {
    if (!empty($user['policy_id'])) {
        $total_assigned++;
    }
}

include 'admin-page-template.php';
?>

<div class="leave-manager-admin-container">
<div class="lm-page-content">
<div class="page-header">
    <div>
        <h1><?php esc_html_e( 'Leave Policies', 'leave-manager' ); ?></h1>
        <p class="subtitle"><?php esc_html_e( 'Create and manage leave policies for your organization', 'leave-manager' ); ?></p>
    </div>
    <button class="lm-btn lm-btn-primary" onclick="showAddPolicyModal()">
        <span>+</span> <?php esc_html_e( 'Add Policy', 'leave-manager' ); ?>
    </button>
</div>

<!-- Statistics Cards -->
<div class="lm-stat-grid">
    <div class="lm-stat-card">
        <div class="lm-stat-label"><?php esc_html_e( 'TOTAL POLICIES', 'leave-manager' ); ?></div>
        <div class="lm-stat-value"><?php echo intval($total_policies); ?></div>
        <div class="lm-stat-description"><?php esc_html_e( 'Configured policies', 'leave-manager' ); ?></div>
    </div>
    <div class="lm-stat-card">
        <div class="lm-stat-label"><?php esc_html_e( 'ACTIVE POLICIES', 'leave-manager' ); ?></div>
        <div class="lm-stat-value" style="color: #10B981;"><?php echo intval($active_policies); ?></div>
        <div class="lm-stat-description"><?php esc_html_e( 'Currently in use', 'leave-manager' ); ?></div>
    </div>
    <div class="lm-stat-card">
        <div class="lm-stat-label"><?php esc_html_e( 'USERS ASSIGNED', 'leave-manager' ); ?></div>
        <div class="lm-stat-value" style="color: #4F5BD5;"><?php echo intval($total_assigned); ?></div>
        <div class="lm-stat-description"><?php esc_html_e( 'With policies', 'leave-manager' ); ?></div>
    </div>
    <div class="lm-stat-card">
        <div class="lm-stat-label"><?php esc_html_e( 'UNASSIGNED USERS', 'leave-manager' ); ?></div>
        <div class="lm-stat-value" style="color: #F59E0B;"><?php echo intval(count($users) - $total_assigned); ?></div>
        <div class="lm-stat-description"><?php esc_html_e( 'Need assignment', 'leave-manager' ); ?></div>
    </div>
</div>

<div class="admin-tabs">
    <button class="admin-tab active" data-tab="policies-list"><?php esc_html_e( 'All Policies', 'leave-manager' ); ?></button>
    <button class="admin-tab" data-tab="assign-policy"><?php esc_html_e( 'Assign Policies', 'leave-manager' ); ?></button>
    <button class="admin-tab" data-tab="leave-types"><?php esc_html_e( 'Leave Types', 'leave-manager' ); ?></button>
</div>

<div class="content-wrapper">
    <div class="content-main">
        <!-- Policies List Tab -->
        <div class="lm-tab-content active" id="policies-list">
            <div class="lm-card">
                <h2><?php esc_html_e( 'Leave Policies', 'leave-manager' ); ?></h2>
                
                <?php if (!$table_exists): ?>
                <div class="lm-notice lm-notice-warning">
                    <p><?php esc_html_e( 'The policies table does not exist. Please deactivate and reactivate the plugin to create the required tables.', 'leave-manager' ); ?></p>
                </div>
                <?php elseif (empty($policies)): ?>
                <div class="lm-empty-state">
                    <div class="lm-empty-icon">📋</div>
                    <h3><?php esc_html_e( 'No Policies Yet', 'leave-manager' ); ?></h3>
                    <p><?php esc_html_e( 'Create your first leave policy to get started.', 'leave-manager' ); ?></p>
                    <button class="lm-btn lm-btn-primary" onclick="showAddPolicyModal()"><?php esc_html_e( 'Create Policy', 'leave-manager' ); ?></button>
                </div>
                <?php else: ?>
                <div class="lm-table-wrapper">
                    <table class="lm-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Policy Name', 'leave-manager' ); ?></th>
                                <th><?php esc_html_e( 'Leave Type', 'leave-manager' ); ?></th>
                                <th><?php esc_html_e( 'Annual Days', 'leave-manager' ); ?></th>
                                <th><?php esc_html_e( 'Carryover', 'leave-manager' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'leave-manager' ); ?></th>
                                <th><?php esc_html_e( 'Actions', 'leave-manager' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="policies-table-body">
                            <?php foreach ($policies as $policy): ?>
                            <tr data-policy-id="<?php echo intval($policy['policy_id']); ?>">
                                <td>
                                    <strong><?php echo esc_html($policy['policy_name']); ?></strong>
                                    <?php if (!empty($policy['is_default']) && $policy['is_default'] == 1): ?>
                                    <span class="lm-badge lm-badge-primary" style="margin-left: 8px; font-size: 10px;">DEFAULT</span>
                                    <?php endif; ?>
                                    <?php if (!empty($policy['description'])): ?>
                                    <br><small style="color: #666;"><?php echo esc_html(substr($policy['description'], 0, 50)); ?><?php echo strlen($policy['description']) > 50 ? '...' : ''; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(ucfirst($policy['leave_type'] ?? 'annual')); ?></td>
                                <td><strong><?php echo intval($policy['annual_days']); ?></strong> days</td>
                                <td><?php echo intval($policy['carryover_days']); ?> days</td>
                                <td>
                                    <?php if (($policy['status'] ?? 'active') === 'active'): ?>
                                    <span class="lm-badge lm-badge-success"><?php esc_html_e( 'Active', 'leave-manager' ); ?></span>
                                    <?php else: ?>
                                    <span class="lm-badge lm-badge-warning"><?php esc_html_e( 'Inactive', 'leave-manager' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="lm-btn-view" onclick="editPolicy(<?php echo intval($policy['policy_id']); ?>)"><?php esc_html_e( 'Edit', 'leave-manager' ); ?></button>
                                    <button class="lm-btn-reject" onclick="deletePolicy(<?php echo intval($policy['policy_id']); ?>)"><?php esc_html_e( 'Delete', 'leave-manager' ); ?></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assign Policy Tab -->
        <div class="lm-tab-content" id="assign-policy">
            <div class="lm-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="margin: 0;"><?php esc_html_e( 'Assign Policies to Users', 'leave-manager' ); ?></h2>
                    <?php 
                    $default_policy = null;
                    foreach ($policies as $p) {
                        if (!empty($p['is_default']) && $p['is_default'] == 1) {
                            $default_policy = $p;
                            break;
                        }
                    }
                    $unassigned_count = count($users) - $total_assigned;
                    if ($default_policy && $unassigned_count > 0): ?>
                    <button class="lm-btn lm-btn-primary" onclick="applyDefaultToAll()">
                        <?php printf(esc_html__('Apply Default to All (%d users)', 'leave-manager'), $unassigned_count); ?>
                    </button>
                    <?php elseif (!$default_policy && !empty($policies)): ?>
                    <span style="color: #666; font-size: 13px;"><?php esc_html_e('Set a default policy to enable bulk assignment', 'leave-manager'); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($users)): ?>
                <div class="lm-empty-state">
                    <div class="lm-empty-icon">👥</div>
                    <h3><?php esc_html_e( 'No Users Found', 'leave-manager' ); ?></h3>
                    <p><?php esc_html_e( 'Add staff members first before assigning policies.', 'leave-manager' ); ?></p>
                </div>
                <?php else: ?>
                <div class="lm-table-wrapper">
                    <table class="lm-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Employee', 'leave-manager' ); ?></th>
                                <th><?php esc_html_e( 'Department', 'leave-manager' ); ?></th>
                                <th><?php esc_html_e( 'Current Policy', 'leave-manager' ); ?></th>
                                <th><?php esc_html_e( 'Assign Policy', 'leave-manager' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): 
                                $current_policy = null;
                                foreach ($policies as $p) {
                                    if ($p['policy_id'] == $user['policy_id']) {
                                        $current_policy = $p;
                                        break;
                                    }
                                }
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                    <br><small style="color: #666;"><?php echo esc_html($user['email']); ?></small>
                                </td>
                                <td><?php echo esc_html($user['department'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($current_policy): ?>
                                    <span class="lm-badge lm-badge-success"><?php echo esc_html($current_policy['policy_name']); ?></span>
                                    <?php else: ?>
                                    <span class="lm-badge lm-badge-warning"><?php esc_html_e( 'Not Assigned', 'leave-manager' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <select class="lm-select policy-select" data-user-id="<?php echo intval($user['user_id']); ?>" onchange="assignPolicy(this)">
                                        <option value=""><?php esc_html_e( '-- Select Policy --', 'leave-manager' ); ?></option>
                                        <?php foreach ($policies as $policy): ?>
                                        <option value="<?php echo intval($policy['policy_id']); ?>" <?php selected($user['policy_id'], $policy['policy_id']); ?>>
                                            <?php echo esc_html($policy['policy_name'] . ' (' . $policy['annual_days'] . ' days)'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Leave Types Tab -->
        <div class="lm-tab-content" id="leave-types">
            <div class="lm-card">
                <h2><?php esc_html_e( 'Leave Types Configuration', 'leave-manager' ); ?></h2>
                <p><?php esc_html_e( 'Configure the types of leave available in your organization.', 'leave-manager' ); ?></p>
                
                <div class="lm-leave-types-grid">
                    <div class="lm-leave-type-card">
                        <div class="lm-leave-type-icon" style="background: #10B981;">🏖️</div>
                        <h3><?php esc_html_e( 'Annual Leave', 'leave-manager' ); ?></h3>
                        <p><?php esc_html_e( 'Paid time off for vacation and personal time.', 'leave-manager' ); ?></p>
                        <span class="lm-badge lm-badge-success"><?php esc_html_e( 'Active', 'leave-manager' ); ?></span>
                    </div>
                    <div class="lm-leave-type-card">
                        <div class="lm-leave-type-icon" style="background: #EF4444;">🏥</div>
                        <h3><?php esc_html_e( 'Sick Leave', 'leave-manager' ); ?></h3>
                        <p><?php esc_html_e( 'Time off for illness or medical appointments.', 'leave-manager' ); ?></p>
                        <span class="lm-badge lm-badge-success"><?php esc_html_e( 'Active', 'leave-manager' ); ?></span>
                    </div>
                    <div class="lm-leave-type-card">
                        <div class="lm-leave-type-icon" style="background: #F59E0B;">📚</div>
                        <h3><?php esc_html_e( 'Study Leave', 'leave-manager' ); ?></h3>
                        <p><?php esc_html_e( 'Time off for educational purposes.', 'leave-manager' ); ?></p>
                        <span class="lm-badge lm-badge-success"><?php esc_html_e( 'Active', 'leave-manager' ); ?></span>
                    </div>
                    <div class="lm-leave-type-card">
                        <div class="lm-leave-type-icon" style="background: #6366F1;">🎉</div>
                        <h3><?php esc_html_e( 'Other Leave', 'leave-manager' ); ?></h3>
                        <p><?php esc_html_e( 'Miscellaneous leave types.', 'leave-manager' ); ?></p>
                        <span class="lm-badge lm-badge-success"><?php esc_html_e( 'Active', 'leave-manager' ); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="content-sidebar">
        <div class="lm-card">
            <h3><?php esc_html_e( 'Policy Help', 'leave-manager' ); ?></h3>
            <p><?php esc_html_e( 'Leave policies define how much leave each employee is entitled to per year.', 'leave-manager' ); ?></p>
        </div>
        <div class="lm-card">
            <h3><?php esc_html_e( 'Quick Links', 'leave-manager' ); ?></h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 8px;"><a href="<?php echo esc_url(admin_url('admin.php?page=leave-manager-staff')); ?>"><?php esc_html_e( 'Staff Management', 'leave-manager' ); ?></a></li>
                <li style="margin-bottom: 8px;"><a href="<?php echo esc_url(admin_url('admin.php?page=leave-manager-requests')); ?>"><?php esc_html_e( 'Leave Requests', 'leave-manager' ); ?></a></li>
                <li><a href="<?php echo esc_url(admin_url('admin.php?page=leave-manager-settings')); ?>"><?php esc_html_e( 'Settings', 'leave-manager' ); ?></a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Add/Edit Policy Modal -->
<div id="policy-modal" class="lm-modal" style="display: none;">
    <div class="lm-modal-content">
        <div class="lm-modal-header">
            <h2 id="modal-title"><?php esc_html_e( 'Add New Policy', 'leave-manager' ); ?></h2>
            <button class="lm-modal-close" onclick="closePolicyModal()">×</button>
        </div>
        <div class="lm-modal-body">
            <form id="policy-form">
                <input type="hidden" id="policy-id" name="policy_id" value="">
                
                <div class="lm-form-group">
                    <label for="policy-name"><?php esc_html_e( 'Policy Name', 'leave-manager' ); ?> *</label>
                    <input type="text" id="policy-name" name="policy_name" class="lm-input" required placeholder="e.g., Standard Employee Policy">
                </div>
                
                <div class="lm-form-group">
                    <label for="policy-description"><?php esc_html_e( 'Description', 'leave-manager' ); ?></label>
                    <textarea id="policy-description" name="description" class="lm-textarea" rows="3" placeholder="Brief description of this policy"></textarea>
                </div>
                
                <div class="lm-form-row">
                    <div class="lm-form-group">
                        <label for="leave-type"><?php esc_html_e( 'Leave Type', 'leave-manager' ); ?></label>
                        <select id="leave-type" name="leave_type" class="lm-select">
                            <option value="annual"><?php esc_html_e( 'Annual Leave', 'leave-manager' ); ?></option>
                            <option value="sick"><?php esc_html_e( 'Sick Leave', 'leave-manager' ); ?></option>
                            <option value="study"><?php esc_html_e( 'Study Leave', 'leave-manager' ); ?></option>
                            <option value="other"><?php esc_html_e( 'Other', 'leave-manager' ); ?></option>
                        </select>
                    </div>
                    <div class="lm-form-group">
                        <label for="policy-status"><?php esc_html_e( 'Status', 'leave-manager' ); ?></label>
                        <select id="policy-status" name="status" class="lm-select">
                            <option value="active"><?php esc_html_e( 'Active', 'leave-manager' ); ?></option>
                            <option value="inactive"><?php esc_html_e( 'Inactive', 'leave-manager' ); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="lm-form-row">
                    <div class="lm-form-group">
                        <label for="annual-days"><?php esc_html_e( 'Annual Days', 'leave-manager' ); ?></label>
                        <input type="number" id="annual-days" name="annual_days" class="lm-input" value="20" min="0" step="0.5">
                    </div>
                    <div class="lm-form-group">
                        <label for="carryover-days"><?php esc_html_e( 'Carryover Days', 'leave-manager' ); ?></label>
                        <input type="number" id="carryover-days" name="carryover_days" class="lm-input" value="5" min="0" step="0.5">
                    </div>
                    <div class="lm-form-group">
                        <label for="expiry-days"><?php esc_html_e( 'Expiry (Days)', 'leave-manager' ); ?></label>
                        <input type="number" id="expiry-days" name="expiry_days" class="lm-input" value="365" min="0">
                    </div>
                </div>
                
                <div class="lm-form-group" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" id="is-default" name="is_default" value="1" style="width: 18px; height: 18px;">
                        <span><?php esc_html_e( 'Set as Default Policy', 'leave-manager' ); ?></span>
                    </label>
                    <p style="margin: 8px 0 0 28px; font-size: 12px; color: #666;"><?php esc_html_e( 'Default policy will be automatically assigned to all new staff members.', 'leave-manager' ); ?></p>
                </div>
            </form>
        </div>
        <div class="lm-modal-footer">
            <button class="lm-btn lm-btn-secondary" onclick="closePolicyModal()"><?php esc_html_e( 'Cancel', 'leave-manager' ); ?></button>
            <button class="lm-btn lm-btn-primary" onclick="savePolicy()"><?php esc_html_e( 'Save Policy', 'leave-manager' ); ?></button>
        </div>
    </div>
</div>

</div>
</div>

<style>
.lm-leave-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.lm-leave-type-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    border: 1px solid #e0e0e0;
    transition: all 0.2s ease;
}

.lm-leave-type-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.lm-leave-type-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 24px;
}

.lm-leave-type-card h3 {
    margin: 0 0 10px 0;
    font-size: 16px;
    color: #333;
}

.lm-leave-type-card p {
    margin: 0 0 15px 0;
    font-size: 13px;
    color: #666;
}

.lm-form-row {
    display: flex;
    gap: 15px;
}

.lm-form-row .lm-form-group {
    flex: 1;
}

.lm-empty-state {
    text-align: center;
    padding: 40px 20px;
}

.lm-empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.lm-empty-state h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.lm-empty-state p {
    margin: 0 0 20px 0;
    color: #666;
}

.lm-notice {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.lm-notice-warning {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
}

.policy-select {
    min-width: 200px;
}
</style>

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

    // Show Add Policy Modal
    window.showAddPolicyModal = function() {
        $('#modal-title').text('Add New Policy');
        $('#policy-id').val('');
        $('#policy-form')[0].reset();
        $('#policy-modal').show();
    };

    // Close Modal
    window.closePolicyModal = function() {
        $('#policy-modal').hide();
    };

    // Edit Policy
    window.editPolicy = function(policyId) {
        // Find policy data from table row
        var row = $('tr[data-policy-id="' + policyId + '"]');
        var policyName = row.find('td:first strong').text();
        
        $('#modal-title').text('Edit Policy');
        $('#policy-id').val(policyId);
        $('#policy-name').val(policyName);
        
        // Load policy data via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'leave_manager_get_policy',
                nonce: lm_nonce,
                policy_id: policyId
            },
            success: function(response) {
                if (response.success && response.data) {
                    var policy = response.data;
                    $('#policy-name').val(policy.policy_name || '');
                    $('#policy-description').val(policy.description || '');
                    $('#leave-type').val(policy.leave_type || 'annual');
                    $('#policy-status').val(policy.status || 'active');
                    $('#annual-days').val(policy.annual_days || 20);
                    $('#carryover-days').val(policy.carryover_days || 5);
                    $('#expiry-days').val(policy.expiry_days || 365);
                    $('#is-default').prop('checked', policy.is_default == 1);
                }
                $('#policy-modal').show();
            },
            error: function() {
                $('#policy-modal').show();
            }
        });
    };

    // Save Policy
    window.savePolicy = function() {
        var policyId = $('#policy-id').val();
        var action = policyId ? 'leave_manager_update_policy' : 'leave_manager_create_policy';
        
        var formData = {
            action: action,
            nonce: lm_nonce,
            policy_id: policyId,
            policy_name: $('#policy-name').val(),
            description: $('#policy-description').val(),
            leave_type: $('#leave-type').val(),
            status: $('#policy-status').val(),
            annual_days: $('#annual-days').val(),
            carryover_days: $('#carryover-days').val(),
            expiry_days: $('#expiry-days').val(),
            is_default: $('#is-default').is(':checked') ? 1 : 0
        };

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || 'Policy saved successfully!');
                    closePolicyModal();
                    location.reload();
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to save policy. Please try again.');
            }
        });
    };

    // Delete Policy
    window.deletePolicy = function(policyId) {
        if (!confirm('Are you sure you want to delete this policy? This action cannot be undone.')) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'leave_manager_delete_policy',
                nonce: lm_nonce,
                policy_id: policyId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || 'Policy deleted successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to delete policy. Please try again.');
            }
        });
    };

    // Apply Default Policy to All Unassigned Users
    window.applyDefaultToAll = function() {
        if (!confirm('This will assign the default policy to all unassigned users. Continue?')) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'leave_manager_apply_default_policy',
                nonce: lm_nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message || 'Default policy applied to all unassigned users!');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to apply default policy. Please try again.');
            }
        });
    };

    // Assign Policy to User
    window.assignPolicy = function(selectElement) {
        var userId = $(selectElement).data('user-id');
        var policyId = $(selectElement).val();

        if (!policyId) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'leave_manager_assign_policy',
                nonce: lm_nonce,
                user_id: userId,
                policy_id: policyId
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message || 'Policy assigned successfully!');
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to assign policy. Please try again.');
            }
        });
    };

    function showNotification(type, message) {
        var notification = $('<div class="lm-notification ' + type + '">' + message + '</div>');
        notification.css({
            position: 'fixed',
            top: '50px',
            right: '20px',
            padding: '15px 25px',
            borderRadius: '8px',
            color: 'white',
            fontWeight: '500',
            zIndex: 9999,
            background: type === 'success' ? '#28a745' : '#dc3545'
        });
        $('body').append(notification);
        setTimeout(function() {
            notification.fadeOut(function() { $(this).remove(); });
        }, 3000);
    }

    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if ($(e.target).hasClass('lm-modal')) {
            closePolicyModal();
        }
    });
});
</script>
