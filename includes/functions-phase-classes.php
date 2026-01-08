<?php
/**
 * Global Functions for Phase 1-4 Classes
 * Provides access to all plugin managers and utilities
 *
 * @package LeaveManager
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Phase 1: Foundation Classes

/**
 * Get Database Migration Manager instance
 *
 * @return Leave_Manager_Database_Migration
 */
function leave_manager_database_migration() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Database_Migration();
	}
	return $instance;
}

/**
 * Get Transaction Manager instance
 *
 * @return Leave_Manager_Transaction_Manager
 */
function leave_manager_transaction() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Transaction_Manager();
	}
	return $instance;
}

/**
 * Get Concurrency Control instance
 *
 * @return Leave_Manager_Concurrency_Control
 */
function leave_manager_concurrency() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Concurrency_Control();
	}
	return $instance;
}

/**
 * Get Security Framework instance
 *
 * @return Leave_Manager_Security_Framework
 */
function leave_manager_security() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Security_Framework();
	}
	return $instance;
}

/**
 * Get Approval Request Manager instance
 *
 * @return Leave_Manager_Approval_Request_Manager
 */
function leave_manager_approval_request() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Approval_Request_Manager();
	}
	return $instance;
}

/**
 * Get Approval Task Manager instance
 *
 * @return Leave_Manager_Approval_Task_Manager
 */
function leave_manager_approval_task() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Approval_Task_Manager();
	}
	return $instance;
}

/**
 * Get Approval Delegation Manager instance
 *
 * @return Leave_Manager_Approval_Delegation_Manager
 */
function leave_manager_approval_delegation() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Approval_Delegation_Manager();
	}
	return $instance;
}

// Phase 2A: Pro-Rata & Public Holidays

/**
 * Get Pro-Rata Calculator instance
 *
 * @return Leave_Manager_ProRata_Calculator
 */
function leave_manager_prorata() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_ProRata_Calculator();
	}
	return $instance;
}

/**
 * Get Public Holiday Manager instance
 *
 * @return Leave_Manager_Public_Holiday_Manager
 */
function leave_manager_public_holiday() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Public_Holiday_Manager();
	}
	return $instance;
}

// Phase 2B: Carry-Over & Reports

/**
 * Get Carry-Over Manager instance
 *
 * @return Leave_Manager_Carryover_Manager
 */
function leave_manager_carryover() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Carryover_Manager();
	}
	return $instance;
}

/**
 * Get Custom Report Builder instance
 *
 * @return Leave_Manager_Custom_Report_Builder
 */
function leave_manager_custom_report() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Custom_Report_Builder();
	}
	return $instance;
}

// Phase 2C: Scheduled Reports & Visualization

/**
 * Get Scheduled Reports Manager instance
 *
 * @return Leave_Manager_Scheduled_Reports_Manager
 */
function leave_manager_scheduled_reports() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Scheduled_Reports_Manager();
	}
	return $instance;
}

/**
 * Get Data Visualization Manager instance
 *
 * @return Leave_Manager_Data_Visualization_Manager
 */
function leave_manager_data_visualization() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Data_Visualization_Manager();
	}
	return $instance;
}

// Phase 3: Optimization & Security

/**
 * Get Performance Optimizer instance
 *
 * @return Leave_Manager_Performance_Optimizer
 */
function leave_manager_performance() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Performance_Optimizer();
	}
	return $instance;
}

/**
 * Get Advanced Security Manager instance
 *
 * @return Leave_Manager_Advanced_Security_Manager
 */
function leave_manager_advanced_security() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Advanced_Security_Manager();
	}
	return $instance;
}

/**
 * Get API Integration Manager instance
 *
 * @return Leave_Manager_API_Integration_Manager
 */
function leave_manager_api() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_API_Integration_Manager();
	}
	return $instance;
}

// Phase 4: Testing & Documentation

/**
 * Get Testing Framework instance
 *
 * @return Leave_Manager_Testing_Framework
 */
function leave_manager_testing() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Leave_Manager_Testing_Framework();
	}
	return $instance;
}

/**
 * Get Documentation Generator instance
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
