/**
 * Leave Manager Admin JavaScript
 * All AJAX functions for admin pages
 */

// Global AJAX settings
const leaveManagerAjax = {
    url: ajaxurl,
    nonce: leaveManagerNonce || ''
};

// ============================================
// STAFF MANAGEMENT FUNCTIONS
// ============================================

function addNewUser() {
    const name = document.getElementById('user_name')?.value;
    const email = document.getElementById('user_email')?.value;
    const department = document.getElementById('user_department')?.value;

    if (!name || !email) {
        alert('Please fill in all required fields');
        return;
    }

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_add_staff',
            nonce: leaveManagerAjax.nonce,
            name: name,
            email: email,
            department: department
        },
        success: function(response) {
            if (response.success) {
                alert('Staff member added successfully');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        },
        error: function() {
            alert('AJAX error occurred');
        }
    });
}

function editUser(id) {
    const name = document.getElementById('edit_user_name_' + id)?.value;
    const email = document.getElementById('edit_user_email_' + id)?.value;
    const department = document.getElementById('edit_user_department_' + id)?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_edit_staff',
            nonce: leaveManagerAjax.nonce,
            id: id,
            name: name,
            email: email,
            department: department
        },
        success: function(response) {
            if (response.success) {
                alert('Staff member updated successfully');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function deleteUser(id) {
    if (!confirm('Are you sure you want to delete this staff member?')) {
        return;
    }

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_delete_staff',
            nonce: leaveManagerAjax.nonce,
            id: id
        },
        success: function(response) {
            if (response.success) {
                alert('Staff member deleted successfully');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

// ============================================
// LEAVE REQUESTS FUNCTIONS
// ============================================

function createLeaveRequest() {
    const type = document.getElementById('leave_type')?.value;
    const startDate = document.getElementById('start_date')?.value;
    const endDate = document.getElementById('end_date')?.value;
    const reason = document.getElementById('reason')?.value;

    if (!type || !startDate || !endDate) {
        alert('Please fill in all required fields');
        return;
    }

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_create_request',
            nonce: leaveManagerAjax.nonce,
            type: type,
            start_date: startDate,
            end_date: endDate,
            reason: reason
        },
        success: function(response) {
            if (response.success) {
                alert('Leave request created successfully');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function approveRequest(id) {
    if (!confirm('Approve this leave request?')) {
        return;
    }

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_approve_request',
            nonce: leaveManagerAjax.nonce,
            id: id
        },
        success: function(response) {
            if (response.success) {
                alert('Request approved');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function rejectRequest(id) {
    const reason = prompt('Enter rejection reason:');
    if (!reason) return;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_reject_request',
            nonce: leaveManagerAjax.nonce,
            id: id,
            reason: reason
        },
        success: function(response) {
            if (response.success) {
                alert('Request rejected');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function deleteRequest(id) {
    if (!confirm('Delete this request?')) {
        return;
    }

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_delete_request',
            nonce: leaveManagerAjax.nonce,
            id: id
        },
        success: function(response) {
            if (response.success) {
                alert('Request deleted');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

// ============================================
// REPORTS FUNCTIONS
// ============================================

function generateLeaveReport() {
    const startDate = document.getElementById('report_start_date')?.value;
    const endDate = document.getElementById('report_end_date')?.value;
    const type = document.getElementById('report_type')?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_generate_leave_report',
            nonce: leaveManagerAjax.nonce,
            start_date: startDate,
            end_date: endDate,
            type: type
        },
        success: function(response) {
            if (response.success) {
                displayReportData(response.data);
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function exportLeaveReport() {
    const startDate = document.getElementById('report_start_date')?.value;
    const endDate = document.getElementById('report_end_date')?.value;
    const type = document.getElementById('report_type')?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_export_leave_report',
            nonce: leaveManagerAjax.nonce,
            start_date: startDate,
            end_date: endDate,
            type: type
        },
        success: function(response) {
            if (response.success) {
                downloadCSV(response.data, 'leave_report.csv');
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function generateUserReport() {
    const userId = document.getElementById('report_user_id')?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_generate_user_report',
            nonce: leaveManagerAjax.nonce,
            user_id: userId
        },
        success: function(response) {
            if (response.success) {
                displayReportData(response.data);
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function exportUserReport() {
    const userId = document.getElementById('report_user_id')?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_export_user_report',
            nonce: leaveManagerAjax.nonce,
            user_id: userId
        },
        success: function(response) {
            if (response.success) {
                downloadCSV(response.data, 'user_report.csv');
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function generateDepartmentReport() {
    const departmentId = document.getElementById('report_department_id')?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_generate_department_report',
            nonce: leaveManagerAjax.nonce,
            department_id: departmentId
        },
        success: function(response) {
            if (response.success) {
                displayReportData(response.data);
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function exportDepartmentReport() {
    const departmentId = document.getElementById('report_department_id')?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_export_department_report',
            nonce: leaveManagerAjax.nonce,
            department_id: departmentId
        },
        success: function(response) {
            if (response.success) {
                downloadCSV(response.data, 'department_report.csv');
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

// ============================================
// TEMPLATES FUNCTIONS
// ============================================

function previewTemplate(id) {
    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_preview_template',
            nonce: leaveManagerAjax.nonce,
            id: id
        },
        success: function(response) {
            if (response.success) {
                showModal('Template Preview', response.data);
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function editTemplate(id) {
    const content = document.getElementById('template_content_' + id)?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_edit_template',
            nonce: leaveManagerAjax.nonce,
            id: id,
            content: content
        },
        success: function(response) {
            if (response.success) {
                alert('Template updated successfully');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function saveTemplate(id) {
    editTemplate(id);
}

function deleteTemplate(id) {
    if (!confirm('Delete this template?')) {
        return;
    }

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_delete_template',
            nonce: leaveManagerAjax.nonce,
            id: id
        },
        success: function(response) {
            if (response.success) {
                alert('Template deleted');
                location.reload();
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

// ============================================
// SETTINGS FUNCTIONS
// ============================================

function saveGeneralSettings() {
    const siteName = document.getElementById('site_name')?.value;
    const siteEmail = document.getElementById('site_email')?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_save_general_settings',
            nonce: leaveManagerAjax.nonce,
            site_name: siteName,
            site_email: siteEmail
        },
        success: function(response) {
            if (response.success) {
                alert('Settings saved successfully');
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function saveEmailSettings() {
    const smtpHost = document.getElementById('smtp_host')?.value;
    const smtpPort = document.getElementById('smtp_port')?.value;
    const smtpUser = document.getElementById('smtp_user')?.value;
    const smtpPass = document.getElementById('smtp_pass')?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_save_email_settings',
            nonce: leaveManagerAjax.nonce,
            smtp_host: smtpHost,
            smtp_port: smtpPort,
            smtp_user: smtpUser,
            smtp_pass: smtpPass
        },
        success: function(response) {
            if (response.success) {
                alert('Email settings saved');
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function saveLeavePolicies() {
    const annualDays = document.getElementById('annual_leave_days')?.value;
    const sickDays = document.getElementById('sick_leave_days')?.value;
    const casualDays = document.getElementById('casual_leave_days')?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_save_leave_policies',
            nonce: leaveManagerAjax.nonce,
            annual_days: annualDays,
            sick_days: sickDays,
            casual_days: casualDays
        },
        success: function(response) {
            if (response.success) {
                alert('Leave policies saved');
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function saveNotificationSettings() {
    const notifyApprover = document.getElementById('notify_approver')?.checked;
    const notifyEmployee = document.getElementById('notify_employee')?.checked;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_save_notification_settings',
            nonce: leaveManagerAjax.nonce,
            notify_approver: notifyApprover,
            notify_employee: notifyEmployee
        },
        success: function(response) {
            if (response.success) {
                alert('Notification settings saved');
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function saveAppearanceSettings() {
    const primaryColor = document.getElementById('primary_color')?.value;
    const logoUrl = document.getElementById('logo_url')?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_save_appearance_settings',
            nonce: leaveManagerAjax.nonce,
            primary_color: primaryColor,
            logo_url: logoUrl
        },
        success: function(response) {
            if (response.success) {
                alert('Appearance settings saved');
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function saveIntegrationSettings() {
    const apiKey = document.getElementById('api_key')?.value;
    const apiUrl = document.getElementById('api_url')?.value;

    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_save_integration_settings',
            nonce: leaveManagerAjax.nonce,
            api_key: apiKey,
            api_url: apiUrl
        },
        success: function(response) {
            if (response.success) {
                alert('Integration settings saved');
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

function testEmailSettings() {
    jQuery.ajax({
        url: leaveManagerAjax.url,
        type: 'POST',
        data: {
            action: 'leave_manager_test_email_settings',
            nonce: leaveManagerAjax.nonce
        },
        success: function(response) {
            if (response.success) {
                alert('Test email sent successfully');
            } else {
                alert('Error: ' + response.data);
            }
        }
    });
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function displayReportData(data) {
    const container = document.getElementById('report_results');
    if (!container) return;

    let html = '<table class="wp-list-table widefat"><thead><tr>';
    
    // Add headers
    if (data.length > 0) {
        Object.keys(data[0]).forEach(key => {
            html += '<th>' + key + '</th>';
        });
        html += '</tr></thead><tbody>';
        
        // Add rows
        data.forEach(row => {
            html += '<tr>';
            Object.values(row).forEach(value => {
                html += '<td>' + value + '</td>';
            });
            html += '</tr>';
        });
    }
    
    html += '</tbody></table>';
    container.innerHTML = html;
}

function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}

function showModal(title, content) {
    const modal = document.createElement('div');
    modal.className = 'leave-manager-modal';
    modal.innerHTML = '<div class="modal-content"><h2>' + title + '</h2><div>' + content + '</div><button onclick="this.closest(\'.leave-manager-modal\').remove()">Close</button></div>';
    document.body.appendChild(modal);
}
