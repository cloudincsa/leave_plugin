=== LFCC Leave Management ===
Contributors: LFCC Development Team
Tags: leave management, hr, employee management, time off
Requires at least: 5.0
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive leave management system for WordPress with user management, leave requests, approvals, and reporting.

== Description ==

LFCC Leave Management is a complete leave management solution for WordPress that helps organizations manage employee leave requests efficiently. The plugin provides:

- User management with role-based access control
- Leave request submission and approval workflow
- Email notifications for all leave activities
- Admin dashboard with statistics and reporting
- System logging and diagnostics
- Customizable email templates
- Leave balance tracking
- Calendar integration

== Features ==

- **User Management**: Create, update, and manage users with different roles (Staff, HR, Admin)
- **Leave Requests**: Submit, approve, and reject leave requests with detailed tracking
- **Email Notifications**: Automated email notifications for all leave activities
- **Admin Dashboard**: Comprehensive dashboard with statistics and quick actions
- **Settings Management**: Configurable plugin settings for organization and system
- **Email Templates**: Customizable email templates for all notifications
- **System Logging**: Detailed logging of all system activities
- **REST API**: Complete REST API for frontend integration
- **Responsive Design**: Mobile-friendly user interface

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Leave Management menu to configure the plugin
4. Create users and start managing leave requests

== Usage ==

After installation:

1. **Configure Settings**: Go to Leave Management > Settings to configure your organization details
2. **Create Users**: Go to Leave Management > User Management to add employees
3. **Manage Requests**: Go to Leave Management > Leave Requests to view and approve requests
4. **View Reports**: Check the Dashboard for statistics and reports

== API Endpoints ==

The plugin provides REST API endpoints for frontend integration:

- `POST /wp-json/lfcc-leave/v1/auth/login` - User login
- `POST /wp-json/lfcc-leave/v1/auth/logout` - User logout
- `GET /wp-json/lfcc-leave/v1/user/profile` - Get user profile
- `PUT /wp-json/lfcc-leave/v1/user/profile` - Update user profile
- `GET /wp-json/lfcc-leave/v1/leave-requests` - Get leave requests
- `POST /wp-json/lfcc-leave/v1/leave-requests` - Submit leave request
- `PUT /wp-json/lfcc-leave/v1/leave-requests/{id}` - Update leave request
- `DELETE /wp-json/lfcc-leave/v1/leave-requests/{id}` - Delete leave request
- `GET /wp-json/lfcc-leave/v1/leave-balance` - Get leave balance
- `GET /wp-json/lfcc-leave/v1/calendar` - Get calendar data

== Shortcodes ==

- `[lfcc_leave_dashboard]` - Display user's leave dashboard
- `[lfcc_leave_form]` - Display leave request form

== Database Tables ==

The plugin creates the following database tables:

- `wp_lfcc_leave_users` - User information
- `wp_lfcc_leave_requests` - Leave request records
- `wp_lfcc_email_logs` - Email activity logs
- `wp_lfcc_settings` - Plugin settings

== Security ==

The plugin follows WordPress security best practices:

- Input sanitization and validation
- Output escaping
- Nonce verification
- Prepared database statements
- User capability checks
- Role-based access control

== Frequently Asked Questions ==

= How do I create a new user? =
Go to Leave Management > User Management and click "Add New User"

= How do I approve a leave request? =
Go to Leave Management > Leave Requests and click "Approve" on the request

= Can I customize email templates? =
Yes, go to Leave Management > Email Templates to customize templates

= How do I reset leave balances? =
Go to Leave Management > User Management and use the "Reset Balances" option

== Changelog ==

= 1.0.0 =
* Initial release
* Core leave management functionality
* User management system
* Email notifications
* Admin dashboard
* REST API endpoints
* System logging

== Support ==

For support, please contact the development team or visit the plugin documentation.

== License ==

This plugin is licensed under the GPL v2 or later. See LICENSE file for details.
