<?php
/**
 * Documentation Generator Class
 * Generates API documentation, user guides, and technical documentation
 *
 * @package LeaveManager
 * @subpackage Documentation
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Leave_Manager_Documentation_Generator {

	/**
	 * Documentation directory
	 *
	 * @var string
	 */
	private $doc_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->doc_dir = LEAVE_MANAGER_PLUGIN_DIR . 'docs/';

		if ( ! is_dir( $this->doc_dir ) ) {
			wp_mkdir_p( $this->doc_dir );
		}
	}

	/**
	 * Generate all documentation
	 *
	 * @return array Generated files
	 */
	public function generate_all_documentation() {
		$files = array();

		$files[] = $this->generate_api_documentation();
		$files[] = $this->generate_user_guide();
		$files[] = $this->generate_developer_guide();
		$files[] = $this->generate_installation_guide();
		$files[] = $this->generate_configuration_guide();
		$files[] = $this->generate_troubleshooting_guide();

		return $files;
	}

	/**
	 * Generate API documentation
	 *
	 * @return string File path
	 */
	private function generate_api_documentation() {
		$content = "# Leave Manager API Documentation\n\n";
		$content .= "## Overview\n";
		$content .= "The Leave Manager plugin provides a comprehensive REST API for integrating with external systems.\n\n";

		$content .= "## Authentication\n";
		$content .= "All API endpoints require authentication. Use your WordPress user credentials.\n\n";

		$content .= "## Endpoints\n\n";

		$content .= "### Leave Requests\n";
		$content .= "- `GET /leave-manager/v1/leave-requests` - Get all leave requests\n";
		$content .= "- `POST /leave-manager/v1/leave-requests` - Create a new leave request\n\n";

		$content .= "### Approvals\n";
		$content .= "- `GET /leave-manager/v1/approvals` - Get all approvals\n";
		$content .= "- `POST /leave-manager/v1/approvals/{id}` - Update approval status\n\n";

		$content .= "### Reports\n";
		$content .= "- `GET /leave-manager/v1/reports` - Get all reports\n";
		$content .= "- `POST /leave-manager/v1/reports/{id}/generate` - Generate report\n\n";

		$content .= "### Analytics\n";
		$content .= "- `GET /leave-manager/v1/analytics/dashboard` - Get dashboard analytics\n";
		$content .= "- `GET /leave-manager/v1/analytics/charts` - Get chart data\n\n";

		$content .= "### Public Holidays\n";
		$content .= "- `GET /leave-manager/v1/holidays` - Get holidays\n";
		$content .= "- `POST /leave-manager/v1/holidays` - Create holiday\n\n";

		$content .= "## Response Format\n";
		$content .= "All responses are in JSON format with the following structure:\n";
		$content .= "```json\n";
		$content .= "{\n";
		$content .= "  \"success\": true,\n";
		$content .= "  \"data\": {},\n";
		$content .= "  \"message\": \"Success message\"\n";
		$content .= "}\n";
		$content .= "```\n\n";

		$content .= "## Error Handling\n";
		$content .= "Errors are returned with appropriate HTTP status codes:\n";
		$content .= "- 400: Bad Request\n";
		$content .= "- 401: Unauthorized\n";
		$content .= "- 403: Forbidden\n";
		$content .= "- 404: Not Found\n";
		$content .= "- 500: Internal Server Error\n\n";

		return $this->save_documentation( 'API_DOCUMENTATION.md', $content );
	}

	/**
	 * Generate user guide
	 *
	 * @return string File path
	 */
	private function generate_user_guide() {
		$content = "# Leave Manager User Guide\n\n";
		$content .= "## Getting Started\n";
		$content .= "Welcome to the Leave Manager plugin. This guide will help you get started.\n\n";

		$content .= "## Submitting a Leave Request\n";
		$content .= "1. Navigate to the Leave Requests section\n";
		$content .= "2. Click 'New Request'\n";
		$content .= "3. Fill in the required information:\n";
		$content .= "   - Leave Type\n";
		$content .= "   - Start Date\n";
		$content .= "   - End Date\n";
		$content .= "   - Reason (optional)\n";
		$content .= "4. Click 'Submit'\n\n";

		$content .= "## Viewing Your Leave Balance\n";
		$content .= "Your current leave balance is displayed on the dashboard.\n";
		$content .= "It includes:\n";
		$content .= "- Annual leave\n";
		$content .= "- Sick leave\n";
		$content .= "- Carry-over leave\n";
		$content .= "- Other leave types\n\n";

		$content .= "## Approval Process\n";
		$content .= "Once you submit a leave request, it goes through the approval process:\n";
		$content .= "1. Your manager reviews the request\n";
		$content .= "2. If approved, it's added to your calendar\n";
		$content .= "3. If rejected, you'll receive a notification with the reason\n\n";

		$content .= "## Managing Your Profile\n";
		$content .= "You can update your profile information:\n";
		$content .= "1. Click on your profile icon\n";
		$content .= "2. Select 'Settings'\n";
		$content .= "3. Update your information\n";
		$content .= "4. Click 'Save'\n\n";

		$content .= "## Frequently Asked Questions\n";
		$content .= "Q: Can I cancel a leave request?\n";
		$content .= "A: Yes, you can cancel pending requests. Approved requests can only be cancelled with manager approval.\n\n";

		$content .= "Q: How is my leave balance calculated?\n";
		$content .= "A: Leave balance is calculated based on your joining date and leave policy.\n\n";

		return $this->save_documentation( 'USER_GUIDE.md', $content );
	}

	/**
	 * Generate developer guide
	 *
	 * @return string File path
	 */
	private function generate_developer_guide() {
		$content = "# Leave Manager Developer Guide\n\n";
		$content .= "## Architecture\n";
		$content .= "The Leave Manager plugin follows a modular architecture with the following components:\n\n";

		$content .= "### Core Components\n";
		$content .= "- **Database Migration**: Manages database schema creation and updates\n";
		$content .= "- **Transaction Manager**: Handles database transactions with retry logic\n";
		$content .= "- **Concurrency Control**: Manages row-level locking for concurrent access\n";
		$content .= "- **Security Framework**: Provides authentication, authorization, and audit logging\n\n";

		$content .= "### Feature Components\n";
		$content .= "- **Approval Engine**: Manages approval workflows and tasks\n";
		$content .= "- **Pro-Rata Calculator**: Calculates pro-rata leave entitlements\n";
		$content .= "- **Public Holiday Manager**: Manages public holidays for 50+ countries\n";
		$content .= "- **Carry-Over Manager**: Handles leave carry-over and year-end processing\n";
		$content .= "- **Custom Report Builder**: Generates custom reports\n";
		$content .= "- **Scheduled Reports Manager**: Manages scheduled report generation and distribution\n";
		$content .= "- **Data Visualization Manager**: Provides charts and analytics\n\n";

		$content .= "### Optimization Components\n";
		$content .= "- **Performance Optimizer**: Handles caching and query optimization\n";
		$content .= "- **Advanced Security Manager**: Provides 2FA, encryption, and threat detection\n";
		$content .= "- **API Integration Manager**: Manages REST API endpoints\n\n";

		$content .= "## Extending the Plugin\n";
		$content .= "To extend the plugin, create a new class that extends the base classes:\n";
		$content .= "```php\n";
		$content .= "class My_Custom_Feature extends Leave_Manager_Base {\n";
		$content .= "    public function __construct() {\n";
		$content .= "        parent::__construct();\n";
		$content .= "    }\n";
		$content .= "}\n";
		$content .= "```\n\n";

		$content .= "## Hooks and Filters\n";
		$content .= "The plugin provides numerous hooks and filters for customization.\n\n";

		$content .= "### Actions\n";
		$content .= "- `leave_manager_leave_request_created` - Fired when a leave request is created\n";
		$content .= "- `leave_manager_approval_approved` - Fired when an approval is approved\n";
		$content .= "- `leave_manager_approval_rejected` - Fired when an approval is rejected\n\n";

		$content .= "### Filters\n";
		$content .= "- `leave_manager_leave_balance` - Filter leave balance calculation\n";
		$content .= "- `leave_manager_approval_workflow` - Filter approval workflow\n\n";

		$content .= "## Database Schema\n";
		$content .= "The plugin uses 12 custom database tables for data storage.\n";
		$content .= "See DATABASE_SCHEMA.md for detailed information.\n\n";

		return $this->save_documentation( 'DEVELOPER_GUIDE.md', $content );
	}

	/**
	 * Generate installation guide
	 *
	 * @return string File path
	 */
	private function generate_installation_guide() {
		$content = "# Leave Manager Installation Guide\n\n";
		$content .= "## Requirements\n";
		$content .= "- WordPress 5.0 or higher\n";
		$content .= "- PHP 7.4 or higher\n";
		$content .= "- MySQL 5.7 or higher\n";
		$content .= "- At least 50MB of free disk space\n\n";

		$content .= "## Installation Steps\n";
		$content .= "1. Download the plugin from the WordPress plugin repository\n";
		$content .= "2. Extract the plugin files to `/wp-content/plugins/leave-manager/`\n";
		$content .= "3. Log in to WordPress admin\n";
		$content .= "4. Navigate to Plugins\n";
		$content .= "5. Find 'Leave Manager' and click 'Activate'\n";
		$content .= "6. Follow the setup wizard\n\n";

		$content .= "## Initial Configuration\n";
		$content .= "After activation, configure the following:\n";
		$content .= "1. Set default country for public holidays\n";
		$content .= "2. Configure leave policies\n";
		$content .= "3. Set up approval workflows\n";
		$content .= "4. Configure email notifications\n\n";

		$content .= "## Troubleshooting\n";
		$content .= "If you encounter issues during installation:\n";
		$content .= "1. Check that PHP version is 7.4 or higher\n";
		$content .= "2. Ensure database user has CREATE TABLE permissions\n";
		$content .= "3. Check WordPress error logs for detailed error messages\n\n";

		return $this->save_documentation( 'INSTALLATION_GUIDE.md', $content );
	}

	/**
	 * Generate configuration guide
	 *
	 * @return string File path
	 */
	private function generate_configuration_guide() {
		$content = "# Leave Manager Configuration Guide\n\n";
		$content .= "## General Settings\n";
		$content .= "Configure general plugin settings:\n";
		$content .= "- Plugin name and description\n";
		$content .= "- Default country for public holidays\n";
		$content .= "- Currency for encashment calculations\n";
		$content .= "- Email notification settings\n\n";

		$content .= "## Leave Policies\n";
		$content .= "Create and manage leave policies:\n";
		$content .= "1. Navigate to Settings > Leave Policies\n";
		$content .= "2. Click 'New Policy'\n";
		$content .= "3. Configure:\n";
		$content .= "   - Policy name\n";
		$content .= "   - Annual leave days\n";
		$content .= "   - Sick leave days\n";
		$content .= "   - Other leave types\n";
		$content .= "4. Click 'Save'\n\n";

		$content .= "## Approval Workflows\n";
		$content .= "Configure approval workflows:\n";
		$content .= "1. Navigate to Settings > Approval Workflows\n";
		$content .= "2. Select workflow type (Simple, Multi-Level, Custom)\n";
		$content .= "3. Configure approvers and approval rules\n";
		$content .= "4. Click 'Save'\n\n";

		$content .= "## Email Notifications\n";
		$content .= "Configure email notifications:\n";
		$content .= "1. Navigate to Settings > Email Notifications\n";
		$content .= "2. Enable/disable notifications\n";
		$content .= "3. Configure email templates\n";
		$content .= "4. Set email recipients\n";
		$content .= "5. Click 'Save'\n\n";

		return $this->save_documentation( 'CONFIGURATION_GUIDE.md', $content );
	}

	/**
	 * Generate troubleshooting guide
	 *
	 * @return string File path
	 */
	private function generate_troubleshooting_guide() {
		$content = "# Leave Manager Troubleshooting Guide\n\n";
		$content .= "## Common Issues\n\n";

		$content .= "### Issue: Plugin not activating\n";
		$content .= "**Solution:**\n";
		$content .= "1. Check PHP version (must be 7.4 or higher)\n";
		$content .= "2. Check database permissions\n";
		$content .= "3. Check WordPress error logs\n";
		$content .= "4. Disable other plugins and try again\n\n";

		$content .= "### Issue: Leave requests not appearing\n";
		$content .= "**Solution:**\n";
		$content .= "1. Check user permissions\n";
		$content .= "2. Verify database tables are created\n";
		$content .= "3. Check for database errors in logs\n";
		$content .= "4. Clear WordPress cache\n\n";

		$content .= "### Issue: Approvals not working\n";
		$content .= "**Solution:**\n";
		$content .= "1. Verify approval workflow is configured\n";
		$content .= "2. Check approver permissions\n";
		$content .= "3. Verify email notifications are working\n";
		$content .= "4. Check approval task assignments\n\n";

		$content .= "### Issue: Reports not generating\n";
		$content .= "**Solution:**\n";
		$content .= "1. Verify report configuration\n";
		$content .= "2. Check database queries\n";
		$content .= "3. Verify user permissions\n";
		$content .= "4. Check for database errors\n\n";

		$content .= "### Issue: Performance issues\n";
		$content .= "**Solution:**\n";
		$content .= "1. Enable caching\n";
		$content .= "2. Optimize database queries\n";
		$content .= "3. Archive old records\n";
		$content .= "4. Increase server resources\n\n";

		$content .= "## Getting Help\n";
		$content .= "If you need additional help:\n";
		$content .= "1. Check the documentation\n";
		$content .= "2. Contact support\n";
		$content .= "3. Check WordPress forums\n";
		$content .= "4. Review error logs\n\n";

		return $this->save_documentation( 'TROUBLESHOOTING_GUIDE.md', $content );
	}

	/**
	 * Save documentation file
	 *
	 * @param string $filename Filename
	 * @param string $content Content
	 * @return string File path
	 */
	private function save_documentation( $filename, $content ) {
		$file_path = $this->doc_dir . $filename;

		file_put_contents( $file_path, $content );

		return $file_path;
	}

	/**
	 * Generate README
	 *
	 * @return string File path
	 */
	public function generate_readme() {
		$content = "# Leave Manager Plugin v3.0.0\n\n";
		$content .= "A comprehensive leave management system for WordPress with advanced features.\n\n";

		$content .= "## Features\n";
		$content .= "- Unified Approval Engine with 3 modes (simple, multi-level, workflow)\n";
		$content .= "- Multi-level approvals with hierarchical routing\n";
		$content .= "- Pro-rata calculations (3 methods: daily, monthly, yearly)\n";
		$content .= "- Carry-over management with year-end processing\n";
		$content .= "- Custom report builder (6 report types)\n";
		$content .= "- Scheduled reports with batch processing\n";
		$content .= "- Data visualization with Chart.js\n";
		$content .= "- Public holiday management (50+ countries)\n";
		$content .= "- Advanced security (2FA, encryption, threat detection)\n";
		$content .= "- REST API integration\n";
		$content .= "- Performance optimization (caching, query optimization)\n\n";

		$content .= "## Installation\n";
		$content .= "See INSTALLATION_GUIDE.md for detailed instructions.\n\n";

		$content .= "## Configuration\n";
		$content .= "See CONFIGURATION_GUIDE.md for detailed instructions.\n\n";

		$content .= "## Documentation\n";
		$content .= "- API_DOCUMENTATION.md - REST API documentation\n";
		$content .= "- USER_GUIDE.md - User guide\n";
		$content .= "- DEVELOPER_GUIDE.md - Developer guide\n";
		$content .= "- TROUBLESHOOTING_GUIDE.md - Troubleshooting guide\n\n";

		$content .= "## Requirements\n";
		$content .= "- WordPress 5.0 or higher\n";
		$content .= "- PHP 7.4 or higher\n";
		$content .= "- MySQL 5.7 or higher\n\n";

		$content .= "## License\n";
		$content .= "GPL v2 or later\n\n";

		$content .= "## Support\n";
		$content .= "For support, please contact us at support@cloudinc.co.za\n\n";

		return $this->save_documentation( 'README.md', $content );
	}

	/**
	 * Generate changelog
	 *
	 * @return string File path
	 */
	public function generate_changelog() {
		$content = "# Changelog\n\n";
		$content .= "## [3.0.0] - 2026-01-02\n";
		$content .= "### Added\n";
		$content .= "- Unified Approval Engine with 3 modes\n";
		$content .= "- Multi-level approvals\n";
		$content .= "- Pro-rata calculations\n";
		$content .= "- Carry-over management\n";
		$content .= "- Custom report builder\n";
		$content .= "- Scheduled reports\n";
		$content .= "- Data visualization\n";
		$content .= "- Public holiday management (50+ countries)\n";
		$content .= "- Advanced security features\n";
		$content .= "- REST API\n";
		$content .= "- Performance optimization\n";
		$content .= "- Testing framework\n";
		$content .= "- Comprehensive documentation\n\n";

		$content .= "### Changed\n";
		$content .= "- Complete rewrite of plugin architecture\n";
		$content .= "- Database schema redesign\n";
		$content .= "- Improved security\n";
		$content .= "- Enhanced performance\n\n";

		$content .= "### Fixed\n";
		$content .= "- Various bugs from v1.0\n\n";

		return $this->save_documentation( 'CHANGELOG.md', $content );
	}

	/**
	 * Get documentation directory
	 *
	 * @return string Directory path
	 */
	public function get_documentation_directory() {
		return $this->doc_dir;
	}

	/**
	 * List all documentation files
	 *
	 * @return array File list
	 */
	public function list_documentation_files() {
		$files = array();

		if ( is_dir( $this->doc_dir ) ) {
			$files = scandir( $this->doc_dir );
			$files = array_diff( $files, array( '.', '..' ) );
		}

		return $files;
	}
}

// Global instance
if ( ! function_exists( 'leave_manager_documentation' ) ) {
	/**
	 * Get documentation generator instance
	 *
	 * @return Leave_Manager_Documentation_Generator
	 */
	function leave_manager_documentation() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Leave_Manager_Documentation_Generator();
		}

		return $instance;
	}
}
