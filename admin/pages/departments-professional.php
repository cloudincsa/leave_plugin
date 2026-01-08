<?php
/**
 * Departments Page - Professional Design
 *
 * @package Leave_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;

// Initialize departments class
$departments_class = new Leave_Manager_Departments();

// Get departments
$departments = $departments_class->get_all( array( 'status' => '' ) );
$stats = $departments_class->get_stats();

// Get users for manager dropdown
$users_table = $wpdb->prefix . 'leave_manager_leave_users';
$users = $wpdb->get_results( "SELECT user_id, first_name, last_name, role FROM {$users_table} WHERE role IN ('manager', 'admin') ORDER BY last_name, first_name" );

// Generate nonce
$nonce = wp_create_nonce( 'leave_manager_admin_nonce' );

// Include admin page template styles
include 'admin-page-template.php';
?>

<div class="leave-manager-admin-container">
<div class="lm-page-content">
<!-- Page Header -->
<div class="page-header">
    <div>
        <h1><?php esc_html_e( 'Departments', 'leave-manager' ); ?></h1>
        <p class="subtitle"><?php esc_html_e( 'Manage organizational departments', 'leave-manager' ); ?></p>
    </div>
    <div class="page-actions">
        <button class="lm-btn-primary" onclick="openAddModal()">
            <?php esc_html_e( '+ Add Department', 'leave-manager' ); ?>
        </button>
        <button class="lm-btn-secondary" onclick="syncDepartments()">
            <?php esc_html_e( 'Sync from Users', 'leave-manager' ); ?>
        </button>
    </div>
</div>

<div class="content-wrapper">
    <div class="content-main">
        <!-- Statistics -->
        <div class="lm-stat-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 20px;">
            <div class="lm-stat-card">
                <div class="lm-stat-label"><?php esc_html_e( 'TOTAL DEPARTMENTS', 'leave-manager' ); ?></div>
                <div class="lm-stat-value" id="stat-total"><?php echo esc_html( $stats['total'] ); ?></div>
                <div class="lm-stat-sublabel"><?php esc_html_e( 'Configured', 'leave-manager' ); ?></div>
            </div>
            <div class="lm-stat-card">
                <div class="lm-stat-label"><?php esc_html_e( 'ACTIVE', 'leave-manager' ); ?></div>
                <div class="lm-stat-value" style="color: #00a32a;" id="stat-active"><?php echo esc_html( $stats['active'] ); ?></div>
                <div class="lm-stat-sublabel"><?php esc_html_e( 'Currently in use', 'leave-manager' ); ?></div>
            </div>
            <div class="lm-stat-card">
                <div class="lm-stat-label"><?php esc_html_e( 'INACTIVE', 'leave-manager' ); ?></div>
                <div class="lm-stat-value" style="color: #d63638;" id="stat-inactive"><?php echo esc_html( $stats['inactive'] ); ?></div>
                <div class="lm-stat-sublabel"><?php esc_html_e( 'Disabled', 'leave-manager' ); ?></div>
            </div>
        </div>

        <!-- Departments Table -->
        <div class="lm-card">
            <h3><?php esc_html_e( 'All Departments', 'leave-manager' ); ?></h3>
            <div class="lm-table-wrapper">
                <table class="lm-table" id="departments-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Department', 'leave-manager' ); ?></th>
                            <th><?php esc_html_e( 'Code', 'leave-manager' ); ?></th>
                            <th><?php esc_html_e( 'Manager', 'leave-manager' ); ?></th>
                            <th><?php esc_html_e( 'Users', 'leave-manager' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'leave-manager' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'leave-manager' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="departments-table-body">
                        <?php if ( ! empty( $departments ) ) : ?>
                            <?php foreach ( $departments as $dept ) : ?>
                                <tr id="dept-row-<?php echo esc_attr( $dept->department_id ); ?>">
                                    <td>
                                        <strong><?php echo esc_html( $dept->department_name ); ?></strong>
                                        <?php if ( ! empty( $dept->description ) ) : ?>
                                            <br><small style="color: #666;"><?php echo esc_html( substr( $dept->description, 0, 50 ) ); ?><?php echo strlen( $dept->description ) > 50 ? '...' : ''; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo esc_html( $dept->department_code ); ?></code></td>
                                    <td>
                                        <?php if ( $dept->manager_first_name ) : ?>
                                            <?php echo esc_html( $dept->manager_first_name . ' ' . $dept->manager_last_name ); ?>
                                        <?php else : ?>
                                            <span style="color: #999;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="lm-badge lm-badge-info"><?php echo esc_html( $dept->user_count ?? 0 ); ?></span>
                                    </td>
                                    <td>
                                        <span class="lm-badge lm-badge-<?php echo $dept->status === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo esc_html( ucfirst( $dept->status ) ); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="lm-action-buttons">
                                            <button class="lm-btn-view" onclick="editDepartment(<?php echo esc_attr( $dept->department_id ); ?>)"><?php esc_html_e( 'Edit', 'leave-manager' ); ?></button>
                                            <button class="lm-btn-reject" onclick="deleteDepartment(<?php echo esc_attr( $dept->department_id ); ?>, '<?php echo esc_js( $dept->department_name ); ?>')"><?php esc_html_e( 'Delete', 'leave-manager' ); ?></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr id="no-departments-row">
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    <div class="lm-empty-state">
                                        <div class="lm-empty-state-icon">🏢</div>
                                        <h3><?php esc_html_e( 'No Departments', 'leave-manager' ); ?></h3>
                                        <p><?php esc_html_e( 'No departments configured. Click "Sync from Users" to auto-create departments from existing user data, or add departments manually.', 'leave-manager' ); ?></p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="content-sidebar">
        <div class="lm-card">
            <h3><?php esc_html_e( 'Department Help', 'leave-manager' ); ?></h3>
            <p><?php esc_html_e( 'Departments help organize your staff and manage leave approvals by team.', 'leave-manager' ); ?></p>
            <ul style="margin: 15px 0; padding-left: 20px;">
                <li><?php esc_html_e( 'Assign managers to departments', 'leave-manager' ); ?></li>
                <li><?php esc_html_e( 'Track leave by department', 'leave-manager' ); ?></li>
                <li><?php esc_html_e( 'Generate department reports', 'leave-manager' ); ?></li>
            </ul>
        </div>

        <div class="lm-card">
            <h3><?php esc_html_e( 'Quick Links', 'leave-manager' ); ?></h3>
            <ul class="lm-quick-links">
                <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-staff' ) ); ?>"><?php esc_html_e( 'Staff Management', 'leave-manager' ); ?></a></li>
                <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-reports' ) ); ?>"><?php esc_html_e( 'Reports', 'leave-manager' ); ?></a></li>
                <li><a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'leave-manager' ); ?></a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Add/Edit Department Modal -->
<div id="department-modal" class="lm-modal" style="display: none;">
    <div class="lm-modal-content">
        <div class="lm-modal-header">
            <h2 id="modal-title"><?php esc_html_e( 'Add Department', 'leave-manager' ); ?></h2>
            <button class="lm-modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="lm-modal-body">
            <form id="department-form">
                <input type="hidden" id="department_id" name="department_id" value="">
                
                <div class="lm-form-group">
                    <label for="department_name"><?php esc_html_e( 'Department Name', 'leave-manager' ); ?> <span style="color: red;">*</span></label>
                    <input type="text" id="department_name" name="department_name" required class="lm-form-control">
                </div>

                <div class="lm-form-group">
                    <label for="department_code"><?php esc_html_e( 'Department Code', 'leave-manager' ); ?></label>
                    <input type="text" id="department_code" name="department_code" class="lm-form-control" placeholder="Auto-generated if empty">
                </div>

                <div class="lm-form-group">
                    <label for="description"><?php esc_html_e( 'Description', 'leave-manager' ); ?></label>
                    <textarea id="description" name="description" class="lm-form-control" rows="3"></textarea>
                </div>

                <div class="lm-form-group">
                    <label for="manager_id"><?php esc_html_e( 'Department Manager', 'leave-manager' ); ?></label>
                    <select id="manager_id" name="manager_id" class="lm-form-control">
                        <option value=""><?php esc_html_e( '— No Manager —', 'leave-manager' ); ?></option>
                        <?php foreach ( $users as $user ) : ?>
                            <option value="<?php echo esc_attr( $user->user_id ); ?>">
                                <?php echo esc_html( $user->first_name . ' ' . $user->last_name . ' (' . ucfirst( $user->role ) . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="lm-form-group">
                    <label for="status"><?php esc_html_e( 'Status', 'leave-manager' ); ?></label>
                    <select id="status" name="status" class="lm-form-control">
                        <option value="active"><?php esc_html_e( 'Active', 'leave-manager' ); ?></option>
                        <option value="inactive"><?php esc_html_e( 'Inactive', 'leave-manager' ); ?></option>
                    </select>
                </div>
            </form>
        </div>
        <div class="lm-modal-footer">
            <button type="button" class="lm-btn-secondary" onclick="closeModal()"><?php esc_html_e( 'Cancel', 'leave-manager' ); ?></button>
            <button type="button" class="lm-btn-primary" onclick="saveDepartment()"><?php esc_html_e( 'Save Department', 'leave-manager' ); ?></button>
        </div>
    </div>
</div>

</div>
</div>

<style>
.page-actions {
    display: flex;
    gap: 10px;
}
.lm-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.lm-modal-content {
    background: #fff;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}
.lm-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
}
.lm-modal-header h2 {
    margin: 0;
    font-size: 18px;
}
.lm-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}
.lm-modal-body {
    padding: 20px;
}
.lm-modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 15px 20px;
    border-top: 1px solid #ddd;
}
.lm-form-group {
    margin-bottom: 15px;
}
.lm-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}
.lm-form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}
.lm-form-control:focus {
    border-color: #2271b1;
    outline: none;
    box-shadow: 0 0 0 1px #2271b1;
}
</style>

<script>
const nonce = '<?php echo esc_js( $nonce ); ?>';
const ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';

function openAddModal() {
    document.getElementById('modal-title').textContent = '<?php esc_html_e( 'Add Department', 'leave-manager' ); ?>';
    document.getElementById('department-form').reset();
    document.getElementById('department_id').value = '';
    document.getElementById('department-modal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('department-modal').style.display = 'none';
}

function editDepartment(id) {
    // Fetch department data
    fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'leave_manager_get_department',
            nonce: nonce,
            department_id: id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const dept = data.data.department;
            document.getElementById('modal-title').textContent = '<?php esc_html_e( 'Edit Department', 'leave-manager' ); ?>';
            document.getElementById('department_id').value = dept.department_id;
            document.getElementById('department_name').value = dept.department_name;
            document.getElementById('department_code').value = dept.department_code || '';
            document.getElementById('description').value = dept.description || '';
            document.getElementById('manager_id').value = dept.manager_id || '';
            document.getElementById('status').value = dept.status;
            document.getElementById('department-modal').style.display = 'flex';
        } else {
            alert('Error: ' + data.data.message);
        }
    });
}

function saveDepartment() {
    const form = document.getElementById('department-form');
    const formData = new FormData(form);
    const departmentId = document.getElementById('department_id').value;
    
    const action = departmentId ? 'leave_manager_update_department' : 'leave_manager_create_department';
    
    fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: action,
            nonce: nonce,
            department_id: departmentId,
            department_name: formData.get('department_name'),
            department_code: formData.get('department_code'),
            description: formData.get('description'),
            manager_id: formData.get('manager_id'),
            status: formData.get('status')
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            closeModal();
            location.reload();
        } else {
            alert('Error: ' + data.data.message);
        }
    });
}

function deleteDepartment(id, name) {
    if (!confirm('Are you sure you want to delete the department "' + name + '"?')) {
        return;
    }
    
    fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'leave_manager_delete_department',
            nonce: nonce,
            department_id: id
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('dept-row-' + id).remove();
            // Update stats
            const total = document.getElementById('stat-total');
            total.textContent = parseInt(total.textContent) - 1;
        } else {
            alert('Error: ' + data.data.message);
        }
    });
}

function syncDepartments() {
    if (!confirm('This will create departments based on existing user department names. Continue?')) {
        return;
    }
    
    fetch(ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'leave_manager_sync_departments',
            nonce: nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            location.reload();
        } else {
            alert('Error: ' + data.data.message);
        }
    });
}

// Close modal on outside click
document.getElementById('department-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>
