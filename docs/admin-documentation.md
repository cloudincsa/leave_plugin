# Leave Manager Plugin: Administrator Documentation

**Author:** Manus AI
**Version:** 2.0

## 1. Introduction

This document provides a comprehensive guide for administrators to install, configure, and manage the Leave Manager plugin for WordPress. This plugin provides a complete system for managing employee leave requests, balances, and policies.

## 2. Installation

### Prerequisites

- WordPress 6.0 or higher
- PHP 8.0 or higher
- MySQL 5.7 or higher

### Installation Steps

1.  **Download the Plugin:** Download the `leave-manager.zip` file from the release page.
2.  **Upload to WordPress:**
    - Go to your WordPress Admin Dashboard
    - Navigate to **Plugins → Add New**
    - Click **Upload Plugin**
    - Choose the `leave-manager.zip` file and click **Install Now**
3.  **Activate the Plugin:** Once installed, click **Activate Plugin**.

Upon activation, the plugin will create the necessary database tables and default settings.

## 3. Configuration

After activation, you will find a new **Leave Manager** menu in your WordPress admin sidebar. This is where you will configure and manage all aspects of the plugin.

### 3.1. General Settings

Navigate to **Leave Manager → Settings** to configure the general settings of the plugin.

| Setting | Description |
| :--- | :--- |
| **Default Leave Approver** | Select a user who will be the default approver for leave requests if no department manager is assigned. |
| **Notification Emails** | Enable or disable email notifications for leave requests, approvals, and rejections. |
| **Weekend Policy** | Define whether weekends are counted as leave days. |
| **Public Holiday Policy** | Configure how public holidays are handled in leave calculations. |
| **Custom CSS** | Add your own custom CSS to style the frontend pages. |

### 3.2. Leave Types

Navigate to **Leave Manager → Leave Types** to manage the different types of leave available to employees.

- **Add New Leave Type:** Click "Add New" to create a new leave type (e.g., Annual, Sick, Maternity).
- **Edit Leave Type:** Click "Edit" to modify an existing leave type.
- **Delete Leave Type:** Click "Delete" to remove a leave type.

For each leave type, you can configure:
- **Name:** The name of the leave type.
- **Entitlement:** The number of days employees are entitled to per year.
- **Accrual Policy:** Whether the leave accrues monthly or annually.
- **Carry-over Policy:** The maximum number of days that can be carried over to the next year.

### 3.3. Departments

Navigate to **Leave Manager → Departments** to manage the departments in your organization.

- **Add New Department:** Click "Add New" to create a new department.
- **Assign Manager:** For each department, you can assign a manager who will be responsible for approving leave requests from that department.

### 3.4. Staff Management

Navigate to **Leave Manager → Staff** to manage your employees.

- **Add New Staff:** Click "Add New" to add a new employee to the system.
- **Assign Department:** Assign each employee to a department.
- **Set Role:** Assign a role to each user (e.g., Employee, Manager, Administrator).

### 3.5. Public Holidays

Navigate to **Leave Manager → Public Holidays** to manage the list of public holidays for your region.

- **Add New Holiday:** Click "Add New" to add a new public holiday.
- **Import Holidays:** You can import a list of public holidays from a CSV file.

## 4. Managing Leave Requests

### 4.1. Approving/Rejecting Requests

As an administrator or manager, you can approve or reject leave requests from the **Leave Manager → Pending Requests** page.

- **Approve:** Click the "Approve" button to approve a request. The employee will be notified, and their leave balance will be updated.
- **Reject:** Click the "Reject" button to reject a request. You will be prompted to provide a reason for the rejection.

### 4.2. Viewing Leave History

Navigate to **Leave Manager → Leave History** to view a complete history of all leave requests, including pending, approved, and rejected requests.

## 5. Reports

The plugin provides several reports to help you track leave data:

- **Leave Balance Report:** View the current leave balance for all employees.
- **Leave History Report:** Generate a report of all leave requests within a specific date range.
- **Departmental Leave Report:** View a summary of leave taken by department.

## 6. Shortcodes

The plugin provides several shortcodes to display leave management functionality on the frontend of your website.

| Shortcode | Description |
| :--- | :--- |
| `[leave_manager_dashboard]` | Displays the employee dashboard. |
| `[leave_manager_apply]` | Displays the leave application form. |
| `[leave_manager_history]` | Displays the employee's leave history. |
| `[leave_manager_balance]` | Displays the employee's leave balance. |
| `[leave_manager_calendar]` | Displays a calendar of employee leave. |
| `[leave_manager_login]` | Displays the login form. |
| `[leave_manager_signup]` | Displays the employee signup form. |

To use a shortcode, simply add it to the content of any WordPress page or post.

## 7. Troubleshooting

### Emails not being sent

- Ensure that your WordPress installation is configured to send emails correctly. You can use a plugin like **WP Mail SMTP** to configure your email settings.
- Check the email notification settings in **Leave Manager → Settings**.

### Pages not appearing

- Ensure that you have created WordPress pages and added the appropriate shortcodes.
- If you are using a caching plugin, clear the cache after making changes.

### Database errors

- Ensure that your database user has the necessary permissions to create and modify tables.
- If you encounter errors after an update, try deactivating and reactivating the plugin to re-run the database migrations.

For further assistance, please contact support.
