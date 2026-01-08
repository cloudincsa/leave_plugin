/**
 * LFCC Leave Management Admin Scripts
 * Version: 3.0.1 - Added dashboard stats refresh
 */

jQuery(document).ready(function($) {
	'use strict';

	// Initialize admin functionality
	initializeAdmin();

	/**
	 * Initialize admin functionality
	 */
	function initializeAdmin() {
		attachEventListeners();
		
		// Check if we're on the dashboard page and refresh stats
		if (window.location.href.indexOf('leave-manager-management') !== -1 && 
		    window.location.href.indexOf('leave-manager-management&') === -1 ||
		    window.location.href.indexOf('page=leave-manager-management&') !== -1) {
			// Only refresh on the main dashboard, not sub-pages
			if (!window.location.href.match(/page=leave-manager-management&tab=/)) {
				setTimeout(refreshDashboardStats, 800);
			}
		}
	}

	/**
	 * Attach event listeners
	 */
	function attachEventListeners() {
		// Add event listeners here as needed
		console.log('LFCC Leave Management admin initialized');
	}

	/**
	 * Refresh dashboard statistics via AJAX
	 * This bypasses any HTML caching by fetching fresh data
	 */
	function refreshDashboardStats() {
		// Get nonce from the page or use the localized variable
		var nonce = '';
		if (typeof leave_manager_ajax !== 'undefined' && leave_manager_ajax.nonce) {
			nonce = leave_manager_ajax.nonce;
		} else if (typeof lm_nonce !== 'undefined') {
			nonce = lm_nonce;
		} else {
			// Try to find nonce in the page
			var nonceInput = document.querySelector('input[name="nonce"]');
			if (nonceInput) {
				nonce = nonceInput.value;
			}
		}

		if (!nonce) {
			console.log('Dashboard stats refresh: No nonce available');
			return;
		}

		console.log('Refreshing dashboard stats...');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'leave_manager_get_dashboard_stats',
				nonce: nonce
			},
			success: function(response) {
				if (response.success) {
					var data = response.data;
					console.log('Dashboard stats received:', data);
					
					// Update stat cards
					var statValues = $('.lm-stat-value');
					if (statValues.length >= 4) {
						statValues.eq(0).text(data.total_staff);
						statValues.eq(1).text(data.total_requests);
						statValues.eq(2).text(data.pending_requests);
						statValues.eq(3).text(data.approval_rate + '%');
					}
					
					// Update department summary
					var deptCard = $('.lm-card h3:contains("Department Summary")').closest('.lm-card');
					if (deptCard.length && data.departments && data.departments.length > 0) {
						var html = '<h3>Department Summary</h3><ul class="lm-dept-list">';
						data.departments.forEach(function(dept) {
							html += '<li><span class="lm-dept-name">' + (dept.department || 'Unassigned') + '</span>';
							html += '<span class="lm-dept-count">' + dept.staff_count + ' staff</span></li>';
						});
						html += '</ul>';
						deptCard.html(html);
					} else if (deptCard.length && (!data.departments || data.departments.length === 0)) {
						deptCard.html('<h3>Department Summary</h3><p>No departments configured.</p>');
					}
					
					// Update request statistics in sidebar
					var miniStats = $('.lm-mini-stat-value');
					if (miniStats.length >= 3) {
						miniStats.eq(0).text(data.approved_requests);
						miniStats.eq(1).text(data.pending_requests);
						miniStats.eq(2).text(data.rejected_requests);
					}
					
					console.log('Dashboard stats refreshed successfully');
				} else {
					console.log('Dashboard stats refresh failed:', response);
				}
			},
			error: function(xhr, status, error) {
				console.error('Dashboard stats refresh error:', error);
			}
		});
	}

	// Expose function globally for manual refresh
	window.refreshDashboardStats = refreshDashboardStats;
});
