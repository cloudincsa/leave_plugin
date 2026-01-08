/**
 * LFCC Leave Management Frontend Scripts
 */

jQuery(document).ready(function($) {
	'use strict';

	// Initialize frontend functionality
	initializeFrontend();

	/**
	 * Initialize frontend functionality
	 */
	function initializeFrontend() {
		// Add event listeners
		attachEventListeners();
	}

	/**
	 * Attach event listeners
	 */
	function attachEventListeners() {
		// Leave request form submission
		$(document).on('submit', '#leave-request-form', function(e) {
			e.preventDefault();
			submitLeaveRequest($(this));
		});

		// Edit request buttons
		$(document).on('click', '.edit-request', function(e) {
			e.preventDefault();
			var requestId = $(this).data('id');
			editLeaveRequest(requestId);
		});
	}

	/**
	 * Submit leave request via AJAX
	 */
	function submitLeaveRequest(form) {
		var formData = {
			action: 'leave_manager_submit_leave_request',
			leave_type: form.find('#leave_type').val(),
			start_date: form.find('#start_date').val(),
			end_date: form.find('#end_date').val(),
			reason: form.find('#reason').val(),
			_wpnonce: lfccLeaveData.nonce
		};

		$.ajax({
			url: lfccLeaveData.ajaxUrl,
			type: 'POST',
			dataType: 'json',
			data: formData,
			success: function(data) {
				if (data.success) {
					showMessage('Leave request submitted successfully!', 'success');
					form[0].reset();
					// Reload requests list if it exists
					if ($('#requests-tbody').length) {
						loadLeaveRequests();
					}
				} else {
					showMessage(data.message || 'Error submitting leave request', 'error');
				}
			},
			error: function(error) {
				showMessage('Error submitting leave request. Please try again.', 'error');
			}
		});
	}

	/**
	 * Load leave requests
	 */
	function loadLeaveRequests() {
		$.ajax({
			url: lfccLeaveData.ajaxUrl,
			type: 'GET',
			dataType: 'json',
			data: {
				action: 'leave_manager_get_leave_requests',
				_wpnonce: lfccLeaveData.nonce
			},
			success: function(data) {
				if (data && data.length > 0) {
					renderLeaveRequests(data);
				} else {
					$('#requests-tbody').html('<tr><td colspan="5">No leave requests found</td></tr>');
				}
			}
		});
	}

	/**
	 * Render leave requests in table
	 */
	function renderLeaveRequests(requests) {
		var tbody = $('#requests-tbody');
		tbody.empty();

		requests.forEach(function(request) {
			var statusClass = 'status-' + request.status;
			var row = '<tr>';
			row += '<td>' + capitalizeFirst(request.leave_type) + '</td>';
			row += '<td>' + formatDate(request.start_date) + '</td>';
			row += '<td>' + formatDate(request.end_date) + '</td>';
			row += '<td><span class="' + statusClass + '">' + capitalizeFirst(request.status) + '</span></td>';
			row += '<td><a href="#" class="edit-request" data-id="' + request.request_id + '">Edit</a></td>';
			row += '</tr>';
			tbody.append(row);
		});
	}

	/**
	 * Edit leave request
	 */
	function editLeaveRequest(requestId) {
		// This would typically open a modal or navigate to an edit page
		console.log('Edit request:', requestId);
	}

	/**
	 * Load leave balance
	 */
	function loadLeaveBalance() {
		$.ajax({
			url: lfccLeaveData.ajaxUrl,
			type: 'GET',
			dataType: 'json',
			data: {
				action: 'leave_manager_get_leave_balance',
				_wpnonce: lfccLeaveData.nonce
			},
			success: function(data) {
				if (data) {
					$('#annual-balance').text(data.annual_leave || 0);
					$('#sick-balance').text(data.sick_leave || 0);
					$('#other-balance').text(data.other_leave || 0);
				}
			}
		});
	}

	/**
	 * Show message
	 */
	function showMessage(message, type) {
		var messageDiv = $('#form-message');
		if (!messageDiv.length) {
			messageDiv = $('<div id="form-message" class="form-message"></div>');
			$('form').after(messageDiv);
		}

		messageDiv.removeClass('success error').addClass(type);
		messageDiv.html('<p>' + message + '</p>').show();

		setTimeout(function() {
			messageDiv.fadeOut();
		}, 5000);
	}

	/**
	 * Capitalize first letter
	 */
	function capitalizeFirst(str) {
		return str.charAt(0).toUpperCase() + str.slice(1);
	}

	/**
	 * Format date
	 */
	function formatDate(dateStr) {
		var date = new Date(dateStr);
		return date.toLocaleDateString('en-US', {
			year: 'numeric',
			month: 'short',
			day: 'numeric'
		});
	}

	// Load data on page load
	if ($('#requests-tbody').length) {
		loadLeaveRequests();
	}
	if ($('#annual-balance').length) {
		loadLeaveBalance();
	}
});
