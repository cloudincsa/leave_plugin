/**
 * Admin AJAX Handler
 * Handles all AJAX requests for settings, user management, etc.
 */

jQuery(document).ready(function($) {
    'use strict';

    // Settings Form Submit
    $(document).on('submit', '.leave-manager-settings-form', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();

        // Collect form data
        const settings = {};
        $form.find('input, select, textarea').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            if (name) {
                settings[name.replace('leave_manager_', '')] = $field.val();
            }
        });

        // Show loading state
        $submitBtn.prop('disabled', true).html('<span class="spinner"></span> Saving...');

        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'leave_manager_save_settings',
                nonce: leaveManagerNonce,
                settings: settings
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    $submitBtn.prop('disabled', false).text(originalText);
                } else {
                    showNotification('error', response.data.message || 'Error saving settings');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showNotification('error', 'Error saving settings');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Branding Form Submit
    $(document).on('submit', '.leave-manager-branding-form', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();

        // Collect branding data
        const branding = {};
        $form.find('input[type="color"], input[type="text"].color-input').each(function() {
            const $field = $(this);
            const name = $field.attr('name');
            if (name) {
                branding[name.replace('leave_manager_', '')] = $field.val();
            }
        });

        // Show loading state
        $submitBtn.prop('disabled', true).html('<span class="spinner"></span> Saving...');

        // Send AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'leave_manager_save_branding',
                nonce: leaveManagerNonce,
                branding: branding
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    $submitBtn.prop('disabled', false).text(originalText);
                    // Reload CSS
                    reloadBrandingCSS();
                } else {
                    showNotification('error', response.data.message || 'Error saving branding');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showNotification('error', 'Error saving branding');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Delete User
    $(document).on('click', '.btn-delete-user', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const userId = $btn.data('user-id');
        const $row = $btn.closest('tr');

        if (!confirm('Are you sure you want to delete this user?')) {
            return;
        }

        $btn.prop('disabled', true).html('<span class="spinner"></span>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'leave_manager_delete_user',
                nonce: leaveManagerNonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        // Update user count
                        updateUserCount();
                    });
                } else {
                    showNotification('error', response.data.message || 'Error deleting user');
                    $btn.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                showNotification('error', 'Error deleting user');
                $btn.prop('disabled', false).text('Delete');
            }
        });
    });

    // Edit User
    $(document).on('click', '.btn-edit-user', function(e) {
        e.preventDefault();

        const $btn = $(this);
        const userId = $btn.data('user-id');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'leave_manager_edit_user',
                nonce: leaveManagerNonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    openEditUserModal(response.data);
                } else {
                    showNotification('error', response.data.message || 'Error loading user');
                }
            },
            error: function() {
                showNotification('error', 'Error loading user');
            }
        });
    });

    // Update User
    $(document).on('submit', '#edit-user-form', function(e) {
        e.preventDefault();

        const $form = $(this);
        const userId = $form.data('user-id');
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();

        const userData = {
            user_id: userId,
            first_name: $form.find('[name="first_name"]').val(),
            last_name: $form.find('[name="last_name"]').val(),
            email: $form.find('[name="email"]').val(),
            department: $form.find('[name="department"]').val(),
            role: $form.find('[name="role"]').val()
        };

        $submitBtn.prop('disabled', true).html('<span class="spinner"></span> Saving...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'leave_manager_update_user',
                nonce: leaveManagerNonce,
                ...userData
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', response.data.message);
                    closeEditUserModal();
                    // Reload users table
                    location.reload();
                } else {
                    showNotification('error', response.data.message || 'Error updating user');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showNotification('error', 'Error updating user');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Helper Functions
    function showNotification(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        const $alert = $(`<div class="alert ${alertClass}">${message}</div>`);

        $('body').prepend($alert);

        setTimeout(function() {
            $alert.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }

    function reloadBrandingCSS() {
        const link = document.querySelector('link[href*="branding.css"]');
        if (link) {
            link.href = link.href.split('?')[0] + '?t=' + new Date().getTime();
        }
    }

    function updateUserCount() {
        const count = $('.existing-users tbody tr').length;
        $('.existing-users-count').text(count);
    }

    function openEditUserModal(userData) {
        // Create modal HTML
        const modalHTML = `
            <div id="edit-user-modal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Edit User</h2>
                    <form id="edit-user-form" data-user-id="${userData.id}">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" value="${userData.first_name}" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" value="${userData.last_name}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" value="${userData.email}" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Department</label>
                                <input type="text" name="department" value="${userData.department}">
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role">
                                    <option value="employee" ${userData.role === 'employee' ? 'selected' : ''}>Employee</option>
                                    <option value="manager" ${userData.role === 'manager' ? 'selected' : ''}>Manager</option>
                                    <option value="admin" ${userData.role === 'admin' ? 'selected' : ''}>Admin</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                    </form>
                </div>
            </div>
        `;

        // Remove existing modal if present
        $('#edit-user-modal').remove();

        // Add new modal
        $('body').append(modalHTML);

        // Show modal
        $('#edit-user-modal').fadeIn();

        // Close button
        $('#edit-user-modal .close').on('click', closeEditUserModal);
    }

    window.closeEditUserModal = function() {
        $('#edit-user-modal').fadeOut(300, function() {
            $(this).remove();
        });
    };
});
