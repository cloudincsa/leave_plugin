<?php
/**
 * Leave Types Management Page
 * 
 * Admin interface for managing leave types (Annual, Sick, Study, etc.)
 *
 * @package Leave_Manager
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Initialize Leave Types class
$leave_types = new Leave_Manager_Leave_Types();

// Handle form submissions
$message = '';
$message_type = '';

if ( isset( $_POST['action'] ) && check_admin_referer( 'leave_manager_leave_types', 'leave_types_nonce' ) ) {
    
    $action = sanitize_text_field( $_POST['action'] );
    
    if ( $action === 'create_type' ) {
        $data = array(
            'type_name'         => isset( $_POST['type_name'] ) ? sanitize_text_field( $_POST['type_name'] ) : '',
            'type_code'         => isset( $_POST['type_code'] ) ? sanitize_key( $_POST['type_code'] ) : '',
            'description'       => isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '',
            'default_days'      => isset( $_POST['default_days'] ) ? floatval( $_POST['default_days'] ) : 0,
            'color'             => isset( $_POST['color'] ) ? sanitize_hex_color( $_POST['color'] ) : '#3498db',
            'requires_approval' => isset( $_POST['requires_approval'] ) ? 1 : 0,
            'is_paid'           => isset( $_POST['is_paid'] ) ? 1 : 0,
            'status'            => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active'
        );
        
        $validation = $leave_types->validate( $data );
        if ( $validation === true ) {
            $result = $leave_types->create( $data );
            if ( $result ) {
                $message = 'Leave type created successfully.';
                $message_type = 'success';
            } else {
                $message = 'Failed to create leave type. The type code may already exist.';
                $message_type = 'error';
            }
        } else {
            $message = implode( '<br>', $validation );
            $message_type = 'error';
        }
    }
    
    if ( $action === 'update_type' ) {
        $type_id = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;
        $data = array(
            'type_name'         => isset( $_POST['type_name'] ) ? sanitize_text_field( $_POST['type_name'] ) : '',
            'type_code'         => isset( $_POST['type_code'] ) ? sanitize_key( $_POST['type_code'] ) : '',
            'description'       => isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '',
            'default_days'      => isset( $_POST['default_days'] ) ? floatval( $_POST['default_days'] ) : 0,
            'color'             => isset( $_POST['color'] ) ? sanitize_hex_color( $_POST['color'] ) : '#3498db',
            'requires_approval' => isset( $_POST['requires_approval'] ) ? 1 : 0,
            'is_paid'           => isset( $_POST['is_paid'] ) ? 1 : 0,
            'status'            => isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active'
        );
        
        $validation = $leave_types->validate( $data );
        if ( $validation === true ) {
            $result = $leave_types->update( $type_id, $data );
            if ( $result ) {
                $message = 'Leave type updated successfully.';
                $message_type = 'success';
            } else {
                $message = 'Failed to update leave type.';
                $message_type = 'error';
            }
        } else {
            $message = implode( '<br>', $validation );
            $message_type = 'error';
        }
    }
    
    if ( $action === 'delete_type' ) {
        $type_id = isset( $_POST['type_id'] ) ? absint( $_POST['type_id'] ) : 0;
        $result = $leave_types->delete( $type_id );
        if ( $result ) {
            $message = 'Leave type deleted successfully.';
            $message_type = 'success';
        } else {
            $message = 'Failed to delete leave type.';
            $message_type = 'error';
        }
    }
    
    if ( $action === 'install_defaults' ) {
        $result = $leave_types->install_defaults();
        if ( $result ) {
            $message = 'Default leave types installed successfully.';
            $message_type = 'success';
        } else {
            $message = 'Default leave types already exist or installation failed.';
            $message_type = 'warning';
        }
    }
}

// Get all leave types
$all_types = $leave_types->get_all();
$active_count = $leave_types->get_count( 'active' );
$inactive_count = $leave_types->get_count( 'inactive' );
$total_count = count( $all_types );

// Get edit type if specified
$edit_type = null;
if ( isset( $_GET['edit'] ) ) {
    $edit_type = $leave_types->get( absint( $_GET['edit'] ) );
}
?>

<div class="wrap lm-admin-wrap">
    <div class="lm-page-header">
        <h1 class="lm-page-title">
            <span class="dashicons dashicons-tag"></span>
            Leave Types Management
        </h1>
        <p class="lm-page-description">Configure the types of leave available in your organization</p>
    </div>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="lm-notice lm-notice-<?php echo esc_attr( $message_type ); ?>">
            <p><?php echo wp_kses_post( $message ); ?></p>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="lm-stats-grid">
        <div class="lm-stat-card">
            <div class="lm-stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                <span class="dashicons dashicons-tag"></span>
            </div>
            <div class="lm-stat-content">
                <span class="lm-stat-value"><?php echo esc_html( $total_count ); ?></span>
                <span class="lm-stat-label">Total Types</span>
            </div>
        </div>
        <div class="lm-stat-card">
            <div class="lm-stat-icon" style="background: linear-gradient(135deg, #27ae60, #219a52);">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="lm-stat-content">
                <span class="lm-stat-value"><?php echo esc_html( $active_count ); ?></span>
                <span class="lm-stat-label">Active Types</span>
            </div>
        </div>
        <div class="lm-stat-card">
            <div class="lm-stat-icon" style="background: linear-gradient(135deg, #95a5a6, #7f8c8d);">
                <span class="dashicons dashicons-hidden"></span>
            </div>
            <div class="lm-stat-content">
                <span class="lm-stat-value"><?php echo esc_html( $inactive_count ); ?></span>
                <span class="lm-stat-label">Inactive Types</span>
            </div>
        </div>
    </div>

    <div class="lm-content-grid">
        <!-- Left Column: Form -->
        <div class="lm-card">
            <div class="lm-card-header">
                <h2 class="lm-card-title">
                    <?php echo $edit_type ? 'Edit Leave Type' : 'Add New Leave Type'; ?>
                </h2>
                <?php if ( $edit_type ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-leave-types' ) ); ?>" class="lm-btn lm-btn-secondary lm-btn-sm">
                        <span class="dashicons dashicons-plus-alt2"></span> Add New
                    </a>
                <?php endif; ?>
            </div>
            <div class="lm-card-body">
                <form method="post" action="" class="lm-form">
                    <?php wp_nonce_field( 'leave_manager_leave_types', 'leave_types_nonce' ); ?>
                    <input type="hidden" name="action" value="<?php echo $edit_type ? 'update_type' : 'create_type'; ?>">
                    <?php if ( $edit_type ) : ?>
                        <input type="hidden" name="type_id" value="<?php echo esc_attr( $edit_type['type_id'] ); ?>">
                    <?php endif; ?>

                    <div class="lm-form-group">
                        <label for="type_name" class="lm-label">Type Name <span class="required">*</span></label>
                        <input type="text" id="type_name" name="type_name" class="lm-input" required
                               value="<?php echo $edit_type ? esc_attr( $edit_type['type_name'] ) : ''; ?>"
                               placeholder="e.g., Annual Leave">
                    </div>

                    <div class="lm-form-group">
                        <label for="type_code" class="lm-label">Type Code <span class="required">*</span></label>
                        <input type="text" id="type_code" name="type_code" class="lm-input" required
                               value="<?php echo $edit_type ? esc_attr( $edit_type['type_code'] ) : ''; ?>"
                               placeholder="e.g., annual" pattern="[a-z0-9_]+"
                               title="Lowercase letters, numbers, and underscores only">
                        <p class="lm-help-text">Unique identifier (lowercase, no spaces)</p>
                    </div>

                    <div class="lm-form-group">
                        <label for="description" class="lm-label">Description</label>
                        <textarea id="description" name="description" class="lm-textarea" rows="3"
                                  placeholder="Brief description of this leave type"><?php echo $edit_type ? esc_textarea( $edit_type['description'] ) : ''; ?></textarea>
                    </div>

                    <div class="lm-form-row">
                        <div class="lm-form-group">
                            <label for="default_days" class="lm-label">Default Days</label>
                            <input type="number" id="default_days" name="default_days" class="lm-input"
                                   value="<?php echo $edit_type ? esc_attr( $edit_type['default_days'] ) : '0'; ?>"
                                   min="0" step="0.5">
                            <p class="lm-help-text">Default allocation per year</p>
                        </div>

                        <div class="lm-form-group">
                            <label for="color" class="lm-label">Color</label>
                            <input type="color" id="color" name="color" class="lm-input-color"
                                   value="<?php echo $edit_type ? esc_attr( $edit_type['color'] ) : '#3498db'; ?>">
                            <p class="lm-help-text">For calendar display</p>
                        </div>
                    </div>

                    <div class="lm-form-row">
                        <div class="lm-form-group">
                            <label class="lm-checkbox-label">
                                <input type="checkbox" name="requires_approval" value="1"
                                       <?php checked( $edit_type ? $edit_type['requires_approval'] : 1, 1 ); ?>>
                                <span class="lm-checkbox-text">Requires Approval</span>
                            </label>
                        </div>

                        <div class="lm-form-group">
                            <label class="lm-checkbox-label">
                                <input type="checkbox" name="is_paid" value="1"
                                       <?php checked( $edit_type ? $edit_type['is_paid'] : 1, 1 ); ?>>
                                <span class="lm-checkbox-text">Paid Leave</span>
                            </label>
                        </div>
                    </div>

                    <div class="lm-form-row">
                        <div class="lm-form-group">
                            <label class="lm-checkbox-label">
                                <input type="checkbox" name="allow_half_day" value="1"
                                       <?php checked( isset( $edit_type['allow_half_day'] ) ? $edit_type['allow_half_day'] : 1, 1 ); ?>>
                                <span class="lm-checkbox-text">Allow Half Day Selection</span>
                            </label>
                            <p class="lm-help-text">Staff can take half day for this leave type</p>
                        </div>

                        <div class="lm-form-group">
                            <label for="half_day_value" class="lm-label">Half Day Value</label>
                            <input type="number" id="half_day_value" name="half_day_value" class="lm-input"
                                   value="<?php echo isset( $edit_type['half_day_value'] ) ? esc_attr( $edit_type['half_day_value'] ) : '0.5'; ?>"
                                   min="0" max="1" step="0.25">
                            <p class="lm-help-text">How many days counts as half day (0.25, 0.5, etc.)</p>
                        </div>
                    </div>

                    <div class="lm-form-row">
                        <div class="lm-form-group">
                            <label class="lm-checkbox-label">
                                <input type="checkbox" name="allow_quarter_day" value="1"
                                       <?php checked( isset( $edit_type['allow_quarter_day'] ) ? $edit_type['allow_quarter_day'] : 0, 1 ); ?>>
                                <span class="lm-checkbox-text">Allow Quarter Day Selection</span>
                            </label>
                            <p class="lm-help-text">Staff can take quarter day (morning/afternoon) for this leave type</p>
                        </div>

                        <div class="lm-form-group">
                            <label for="quarter_day_value" class="lm-label">Quarter Day Value</label>
                            <input type="number" id="quarter_day_value" name="quarter_day_value" class="lm-input"
                                   value="<?php echo isset( $edit_type['quarter_day_value'] ) ? esc_attr( $edit_type['quarter_day_value'] ) : '0.25'; ?>"
                                   min="0" max="0.5" step="0.25">
                            <p class="lm-help-text">How many days counts as quarter day</p>
                        </div>
                    </div>

                    <div class="lm-form-group">
                        <label for="status" class="lm-label">Status</label>
                        <select id="status" name="status" class="lm-select">
                            <option value="active" <?php selected( $edit_type ? $edit_type['status'] : 'active', 'active' ); ?>>Active</option>
                            <option value="inactive" <?php selected( $edit_type ? $edit_type['status'] : '', 'inactive' ); ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="lm-form-actions">
                        <button type="submit" class="lm-btn lm-btn-primary">
                            <span class="dashicons dashicons-<?php echo $edit_type ? 'update' : 'plus-alt2'; ?>"></span>
                            <?php echo $edit_type ? 'Update Leave Type' : 'Create Leave Type'; ?>
                        </button>
                        <?php if ( $edit_type ) : ?>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-leave-types' ) ); ?>" class="lm-btn lm-btn-secondary">
                                Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if ( $total_count === 0 ) : ?>
                    <hr style="margin: 20px 0;">
                    <form method="post" action="">
                        <?php wp_nonce_field( 'leave_manager_leave_types', 'leave_types_nonce' ); ?>
                        <input type="hidden" name="action" value="install_defaults">
                        <button type="submit" class="lm-btn lm-btn-secondary">
                            <span class="dashicons dashicons-download"></span>
                            Install Default Leave Types
                        </button>
                        <p class="lm-help-text" style="margin-top: 10px;">
                            This will create 4 standard leave types: Annual, Sick, Study, and Other.
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: List -->
        <div class="lm-card">
            <div class="lm-card-header">
                <h2 class="lm-card-title">All Leave Types</h2>
            </div>
            <div class="lm-card-body">
                <?php if ( empty( $all_types ) ) : ?>
                    <div class="lm-empty-state">
                        <span class="dashicons dashicons-tag"></span>
                        <h3>No Leave Types Found</h3>
                        <p>Create your first leave type or install the defaults to get started.</p>
                    </div>
                <?php else : ?>
                    <table class="lm-table">
                        <thead>
                            <tr>
                                <th>Color</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Days</th>
                                <th>Approval</th>
                                <th>Paid</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $all_types as $type ) : ?>
                                <tr>
                                    <td>
                                        <span class="lm-color-badge" style="background-color: <?php echo esc_attr( $type['color'] ); ?>;"></span>
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html( $type['type_name'] ); ?></strong>
                                        <?php if ( ! empty( $type['description'] ) ) : ?>
                                            <br><small class="lm-text-muted"><?php echo esc_html( wp_trim_words( $type['description'], 10 ) ); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?php echo esc_html( $type['type_code'] ); ?></code></td>
                                    <td><?php echo esc_html( $type['default_days'] ); ?></td>
                                    <td>
                                        <?php if ( $type['requires_approval'] ) : ?>
                                            <span class="lm-badge lm-badge-info">Yes</span>
                                        <?php else : ?>
                                            <span class="lm-badge lm-badge-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ( $type['is_paid'] ) : ?>
                                            <span class="lm-badge lm-badge-success">Paid</span>
                                        <?php else : ?>
                                            <span class="lm-badge lm-badge-warning">Unpaid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ( $type['status'] === 'active' ) : ?>
                                            <span class="lm-badge lm-badge-success">Active</span>
                                        <?php else : ?>
                                            <span class="lm-badge lm-badge-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="lm-action-buttons">
                                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=leave-manager-leave-types&edit=' . $type['type_id'] ) ); ?>" 
                                               class="lm-btn lm-btn-sm lm-btn-secondary" title="Edit">
                                                <span class="dashicons dashicons-edit"></span>
                                            </a>
                                            <form method="post" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Are you sure you want to delete this leave type?');">
                                                <?php wp_nonce_field( 'leave_manager_leave_types', 'leave_types_nonce' ); ?>
                                                <input type="hidden" name="action" value="delete_type">
                                                <input type="hidden" name="type_id" value="<?php echo esc_attr( $type['type_id'] ); ?>">
                                                <button type="submit" class="lm-btn lm-btn-sm lm-btn-danger" title="Delete">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Leave Types Page Specific Styles */
.lm-content-grid {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 20px;
    margin-top: 20px;
}

@media (max-width: 1200px) {
    .lm-content-grid {
        grid-template-columns: 1fr;
    }
}

.lm-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.lm-input-color {
    width: 60px;
    height: 40px;
    padding: 2px;
    border: 1px solid #ddd;
    border-radius: 4px;
    cursor: pointer;
}

.lm-color-badge {
    display: inline-block;
    width: 24px;
    height: 24px;
    border-radius: 4px;
    border: 1px solid rgba(0,0,0,0.1);
}

.lm-checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.lm-checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

.lm-action-buttons {
    display: flex;
    gap: 5px;
}

.lm-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.lm-empty-state .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ccc;
    margin-bottom: 15px;
}

.lm-empty-state h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.lm-empty-state p {
    margin: 0;
    color: #666;
}

.required {
    color: #e74c3c;
}

.lm-text-muted {
    color: #666;
}

code {
    background: #f5f5f5;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 12px;
}
</style>
