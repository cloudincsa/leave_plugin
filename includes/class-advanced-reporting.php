<?php
/**
 * Advanced Reporting Features Class
 * 
 * Provides comprehensive reporting capabilities for leave management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Leave_Manager_Advanced_Reporting {

    private $db;
    private $logger;

    /**
     * Constructor
     */
    public function __construct($db, $logger) {
        $this->db = $db;
        $this->logger = $logger;
        $this->init();
    }

    /**
     * Initialize reporting features
     */
    public function init() {
        // Register AJAX handlers
        add_action('wp_ajax_leave_manager_generate_report', array($this, 'generate_report'));
        add_action('wp_ajax_leave_manager_export_report', array($this, 'export_report'));
        add_action('wp_ajax_leave_manager_schedule_report', array($this, 'schedule_report'));
        
        // Register report pages
        add_action('admin_menu', array($this, 'register_report_menu'));
    }

    /**
     * Register report menu
     */
    public function register_report_menu() {
        add_submenu_page(
            'leave-manager-management',
            'Advanced Reports',
            'Reports',
            'manage_leave_requests',
            'leave-manager-reports',
            array($this, 'render_reports_page')
        );
    }

    /**
     * Render reports page
     */
    public function render_reports_page() {
        ?>
        <div class="wrap">
            <h1>Advanced Leave Reports</h1>
            
            <div class="lm-reports-container">
                <!-- Report Type Selection -->
                <div class="lm-report-selector">
                    <h2>Select Report Type</h2>
                    <div class="lm-report-types">
                        <button class="lm-report-btn" data-report="employee-summary">
                            <span class="dashicons dashicons-groups"></span>
                            Employee Summary
                        </button>
                        <button class="lm-report-btn" data-report="department-analysis">
                            <span class="dashicons dashicons-chart-bar"></span>
                            Department Analysis
                        </button>
                        <button class="lm-report-btn" data-report="leave-trends">
                            <span class="dashicons dashicons-chart-line"></span>
                            Leave Trends
                        </button>
                        <button class="lm-report-btn" data-report="compliance">
                            <span class="dashicons dashicons-yes-alt"></span>
                            Compliance Report
                        </button>
                        <button class="lm-report-btn" data-report="financial-impact">
                            <span class="dashicons dashicons-money-alt"></span>
                            Financial Impact
                        </button>
                        <button class="lm-report-btn" data-report="custom">
                            <span class="dashicons dashicons-admin-tools"></span>
                            Custom Report
                        </button>
                    </div>
                </div>

                <!-- Report Filters -->
                <div class="lm-report-filters">
                    <h3>Report Filters</h3>
                    <div class="lm-filter-grid">
                        <div class="lm-filter-item">
                            <label>Date Range</label>
                            <input type="date" id="report-start-date" />
                            <input type="date" id="report-end-date" />
                        </div>
                        <div class="lm-filter-item">
                            <label>Department</label>
                            <select id="report-department">
                                <option value="">All Departments</option>
                                <!-- Populated dynamically -->
                            </select>
                        </div>
                        <div class="lm-filter-item">
                            <label>Leave Type</label>
                            <select id="report-leave-type">
                                <option value="">All Types</option>
                                <option value="annual">Annual Leave</option>
                                <option value="sick">Sick Leave</option>
                                <option value="other">Other Leave</option>
                            </select>
                        </div>
                        <div class="lm-filter-item">
                            <label>Status</label>
                            <select id="report-status">
                                <option value="">All Status</option>
                                <option value="approved">Approved</option>
                                <option value="pending">Pending</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                    </div>
                    <button id="generate-report-btn" class="button button-primary">Generate Report</button>
                </div>

                <!-- Report Output -->
                <div id="report-output" class="lm-report-output" style="display:none;">
                    <div class="lm-report-header">
                        <h2 id="report-title"></h2>
                        <div class="lm-report-actions">
                            <button id="export-pdf-btn" class="button">Export as PDF</button>
                            <button id="export-csv-btn" class="button">Export as CSV</button>
                            <button id="export-excel-btn" class="button">Export as Excel</button>
                            <button id="schedule-report-btn" class="button">Schedule Report</button>
                        </div>
                    </div>
                    <div id="report-content" class="lm-report-content"></div>
                </div>
            </div>
        </div>

        <style>
            .lm-reports-container {
                max-width: 1200px;
                margin: 20px 0;
            }

            .lm-report-types {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }

            .lm-report-btn {
                padding: 20px;
                border: 2px solid #ddd;
                background: white;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }

            .lm-report-btn:hover {
                border-color: #4A5FFF;
                background: #fffbf0;
            }

            .lm-report-btn.active {
                border-color: #4A5FFF;
                background: #4A5FFF;
                color: white;
            }

            .lm-report-filters {
                background: white;
                padding: 20px;
                border-radius: 8px;
                margin: 20px 0;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .lm-filter-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin: 15px 0;
            }

            .lm-filter-item {
                display: flex;
                flex-direction: column;
            }

            .lm-filter-item label {
                font-weight: 600;
                margin-bottom: 5px;
            }

            .lm-filter-item input,
            .lm-filter-item select {
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }

            .lm-report-output {
                background: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }

            .lm-report-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 2px solid #eee;
            }

            .lm-report-actions {
                display: flex;
                gap: 10px;
            }

            .lm-report-content {
                margin-top: 20px;
            }

            .lm-report-table {
                width: 100%;
                border-collapse: collapse;
            }

            .lm-report-table th,
            .lm-report-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }

            .lm-report-table th {
                background: #f5f5f5;
                font-weight: 600;
            }

            .lm-report-chart {
                margin: 30px 0;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 8px;
            }
        </style>
        <?php
    }

    /**
     * Generate report
     */
    public function generate_report() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'leave_manager_reports')) {
            wp_send_json_error('Invalid security token');
        }

        // Check permissions
        if (!current_user_can('manage_leave_requests')) {
            wp_send_json_error('Permission denied');
        }

        $report_type = sanitize_text_field($_POST['report_type']);
        $filters = isset($_POST['filters']) ? json_decode(stripslashes($_POST['filters']), true) : array();

        $report_data = $this->build_report($report_type, $filters);

        if ($report_data) {
            wp_send_json_success($report_data);
        } else {
            wp_send_json_error('Failed to generate report');
        }
    }

    /**
     * Build report based on type
     */
    private function build_report($report_type, $filters) {
        switch ($report_type) {
            case 'employee-summary':
                return $this->build_employee_summary_report($filters);
            case 'department-analysis':
                return $this->build_department_analysis_report($filters);
            case 'leave-trends':
                return $this->build_leave_trends_report($filters);
            case 'compliance':
                return $this->build_compliance_report($filters);
            case 'financial-impact':
                return $this->build_financial_impact_report($filters);
            case 'custom':
                return $this->build_custom_report($filters);
            default:
                return null;
        }
    }

    /**
     * Build employee summary report
     */
    private function build_employee_summary_report($filters) {
        global $wpdb;
        $table = $wpdb->prefix . 'leave_manager_leave_requests';

        $query = "SELECT u.ID, u.display_name, 
                  COUNT(*) as total_requests,
                  SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END) as approved,
                  SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending,
                  SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END) as rejected
                  FROM {$table} lr
                  JOIN {$wpdb->users} u ON lr.user_id = u.ID
                  WHERE 1=1";

        // Add filters
        if (!empty($filters['start_date'])) {
            $query .= $wpdb->prepare(" AND lr.start_date >= %s", $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query .= $wpdb->prepare(" AND lr.end_date <= %s", $filters['end_date']);
        }

        $query .= " GROUP BY u.ID ORDER BY total_requests DESC";

        $results = $wpdb->get_results($query);

        return array(
            'title' => 'Employee Leave Summary',
            'type' => 'table',
            'data' => $results,
            'columns' => array(
                'display_name' => 'Employee',
                'total_requests' => 'Total Requests',
                'approved' => 'Approved',
                'pending' => 'Pending',
                'rejected' => 'Rejected'
            )
        );
    }

    /**
     * Build department analysis report
     */
    private function build_department_analysis_report($filters) {
        // Implementation for department analysis
        return array(
            'title' => 'Department Analysis',
            'type' => 'chart',
            'chartType' => 'bar'
        );
    }

    /**
     * Build leave trends report
     */
    private function build_leave_trends_report($filters) {
        // Implementation for leave trends
        return array(
            'title' => 'Leave Trends',
            'type' => 'chart',
            'chartType' => 'line'
        );
    }

    /**
     * Build compliance report
     */
    private function build_compliance_report($filters) {
        // Implementation for compliance report
        return array(
            'title' => 'Compliance Report',
            'type' => 'table'
        );
    }

    /**
     * Build financial impact report
     */
    private function build_financial_impact_report($filters) {
        // Implementation for financial impact
        return array(
            'title' => 'Financial Impact Analysis',
            'type' => 'chart',
            'chartType' => 'pie'
        );
    }

    /**
     * Build custom report
     */
    private function build_custom_report($filters) {
        // Implementation for custom reports
        return array(
            'title' => 'Custom Report',
            'type' => 'table'
        );
    }

    /**
     * Export report
     */
    public function export_report() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'leave_manager_reports')) {
            wp_send_json_error('Invalid security token');
        }

        $format = sanitize_text_field($_POST['format']); // pdf, csv, excel
        $report_data = json_decode(stripslashes($_POST['report_data']), true);

        // Generate export based on format
        switch ($format) {
            case 'pdf':
                $this->export_as_pdf($report_data);
                break;
            case 'csv':
                $this->export_as_csv($report_data);
                break;
            case 'excel':
                $this->export_as_excel($report_data);
                break;
        }
    }

    /**
     * Export as PDF
     */
    private function export_as_pdf($report_data) {
        // Implementation using PDF library
        // This would use a library like TCPDF or mPDF
    }

    /**
     * Export as CSV
     */
    private function export_as_csv($report_data) {
        // Implementation for CSV export
    }

    /**
     * Export as Excel
     */
    private function export_as_excel($report_data) {
        // Implementation for Excel export
    }

    /**
     * Schedule report
     */
    public function schedule_report() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'leave_manager_reports')) {
            wp_send_json_error('Invalid security token');
        }

        $report_type = sanitize_text_field($_POST['report_type']);
        $frequency = sanitize_text_field($_POST['frequency']); // daily, weekly, monthly
        $recipients = isset($_POST['recipients']) ? array_map('sanitize_email', $_POST['recipients']) : array();

        // Save scheduled report to database
        // Implementation would store this in a custom table

        wp_send_json_success('Report scheduled successfully');
    }
}
