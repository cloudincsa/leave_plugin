/**
 * FullCalendar Integration Script
 * 
 * Initializes and manages FullCalendar for leave management
 */

(function() {
	'use strict';

	/**
	 * Initialize calendar
	 */
	function initCalendar() {
		const calendarEl = document.getElementById('leave-manager-calendar');
		
		if (!calendarEl) {
			return;
		}

		// Get calendar configuration
		const config = {
			initialView: 'dayGridMonth',
			headerToolbar: {
				left: 'prev,next today',
				center: 'title',
				right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
			},
			editable: false,
			eventClick: handleEventClick,
			selectConstraint: 'businessHours',
			eventDisplay: 'block',
			eventTextColor: '#ffffff',
			eventBorderColor: 'transparent',
			dayMaxEventRows: 3,
			moreLinkText: '+{0} more',
			eventTimeFormat: {
				hour: '2-digit',
				minute: '2-digit',
				meridiem: 'short'
			},
			slotLabelFormat: {
				hour: '2-digit',
				minute: '2-digit',
				meridiem: 'short'
			},
			slotDuration: '00:30:00',
			slotLabelInterval: '00:30',
			scrollTime: '09:00:00',
			nowIndicator: true,
			weekends: true,
			weekNumbers: true,
			weekNumberCalculation: 'ISO',
			businessHours: [
				{
					daysOfWeek: [1, 2, 3, 4, 5],
					startTime: '09:00',
					endTime: '17:00'
				}
			],
			contentHeight: 'auto',
			height: 'auto',
			events: getCalendarEvents,
			datesSet: handleDatesSet
		};

		// Initialize FullCalendar
		const calendar = new FullCalendar.Calendar(calendarEl, config);
		calendar.render();

		// Store calendar instance for later use
		window.leaveManagerCalendar = calendar;
	}

	/**
	 * Get calendar events
	 */
	function getCalendarEvents(info, successCallback, failureCallback) {
		const startDate = info.start.toISOString().split('T')[0];
		const endDate = info.end.toISOString().split('T')[0];

		// Fetch events from server
		fetch(ajaxurl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'leave_manager_get_calendar_events',
				start_date: startDate,
				end_date: endDate,
				nonce: document.querySelector('[data-nonce]')?.getAttribute('data-nonce') || ''
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				successCallback(data.data);
			} else {
				failureCallback(new Error(data.data.message || 'Failed to fetch events'));
			}
		})
		.catch(error => {
			console.error('Error fetching calendar events:', error);
			failureCallback(error);
		});
	}

	/**
	 * Handle event click
	 */
	function handleEventClick(info) {
		const event = info.event;
		const props = event.extendedProps;

		// Create event details modal
		showEventModal({
			title: event.title,
			start: event.start,
			end: event.end,
			type: props.type,
			status: props.status,
			reason: props.reason
		});
	}

	/**
	 * Handle dates set (month/week/day change)
	 */
	function handleDatesSet(info) {
		// Update any date-dependent UI elements
		console.log('Calendar view changed:', info.view.type);
	}

	/**
	 * Show event details modal
	 */
	function showEventModal(event) {
		const modal = document.createElement('div');
		modal.className = 'leave-manager-event-modal';
		modal.innerHTML = `
			<div class="leave-manager-event-modal-content">
				<div class="leave-manager-event-modal-header">
					<h3>${event.title}</h3>
					<button class="leave-manager-event-modal-close">&times;</button>
				</div>
				<div class="leave-manager-event-modal-body">
					<div class="leave-manager-event-detail">
						<label>Leave Type:</label>
						<span class="leave-type-badge leave-type-${event.type}">${capitalizeString(event.type)}</span>
					</div>
					<div class="leave-manager-event-detail">
						<label>Status:</label>
						<span class="leave-status-badge leave-status-${event.status}">${capitalizeString(event.status)}</span>
					</div>
					<div class="leave-manager-event-detail">
						<label>Start Date:</label>
						<span>${formatDate(event.start)}</span>
					</div>
					<div class="leave-manager-event-detail">
						<label>End Date:</label>
						<span>${formatDate(event.end)}</span>
					</div>
					${event.reason ? `
						<div class="leave-manager-event-detail">
							<label>Reason:</label>
							<p>${event.reason}</p>
						</div>
					` : ''}
				</div>
				<div class="leave-manager-event-modal-footer">
					<button class="leave-manager-btn leave-manager-btn-secondary leave-manager-event-modal-close-btn">Close</button>
				</div>
			</div>
		`;

		document.body.appendChild(modal);

		// Handle close button clicks
		modal.querySelectorAll('.leave-manager-event-modal-close, .leave-manager-event-modal-close-btn').forEach(btn => {
			btn.addEventListener('click', () => {
				modal.remove();
			});
		});

		// Close on background click
		modal.addEventListener('click', (e) => {
			if (e.target === modal) {
				modal.remove();
			}
		});
	}

	/**
	 * Utility: Capitalize string
	 */
	function capitalizeString(str) {
		return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
	}

	/**
	 * Utility: Format date
	 */
	function formatDate(date) {
		if (!date) return '';
		return new Date(date).toLocaleDateString('en-US', {
			year: 'numeric',
			month: 'long',
			day: 'numeric'
		});
	}

	/**
	 * Initialize on DOM ready
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initCalendar);
	} else {
		initCalendar();
	}
})();
