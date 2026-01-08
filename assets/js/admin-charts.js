/**
 * Admin Dashboard Charts Script
 * 
 * Initializes charts for the WordPress admin dashboard
 */

(function() {
	'use strict';

	// Store chart instances
	window.leaveManagerAdminCharts = {};

	/**
	 * Initialize admin dashboard charts
	 */
	function initAdminCharts() {
		const dashboardContainer = document.querySelector('.leave-manager-admin-charts-container');
		
		if (!dashboardContainer) {
			return;
		}

		// Initialize all chart elements
		const chartElements = dashboardContainer.querySelectorAll('[data-chart-type]');
		
		chartElements.forEach(element => {
			const chartType = element.getAttribute('data-chart-type');
			const chartId = element.getAttribute('data-chart-id');
			const chartDataAttr = element.getAttribute('data-chart-data');
			
			if (chartId && chartDataAttr) {
				try {
					const data = JSON.parse(chartDataAttr);
					initChart(chartId, chartType, data);
				} catch (e) {
					console.error('Error parsing chart data for ' + chartId + ':', e);
					showChartError(chartId, 'Failed to load chart data');
				}
			}
		});
	}

	/**
	 * Initialize a single chart
	 */
	function initChart(chartId, chartType, data) {
		const canvas = document.getElementById(chartId);
		
		if (!canvas) {
			console.warn('Chart canvas not found:', chartId);
			return;
		}

		try {
			const ctx = canvas.getContext('2d');
			const options = getChartOptions(chartType);

			// Create chart instance
			const chart = new Chart(ctx, {
				type: chartType,
				data: data,
				options: options
			});

			// Store chart instance
			window.leaveManagerAdminCharts[chartId] = chart;

			// Add export functionality
			addChartControls(chartId, chart);
		} catch (e) {
			console.error('Error initializing chart ' + chartId + ':', e);
			showChartError(chartId, 'Failed to initialize chart');
		}
	}

	/**
	 * Get chart options based on type
	 */
	function getChartOptions(type) {
		const commonOptions = {
			responsive: true,
			maintainAspectRatio: true,
			plugins: {
				legend: {
					position: 'top',
					labels: {
						font: {
							family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
							size: 12
						},
						color: '#23282d',
						padding: 12,
						usePointStyle: true
					}
				},
				tooltip: {
					backgroundColor: 'rgba(0, 0, 0, 0.8)',
					titleFont: { size: 12 },
					bodyFont: { size: 11 },
					padding: 8,
					cornerRadius: 4,
					displayColors: true
				}
			}
		};

		switch (type) {
			case 'line':
				return {
					...commonOptions,
					scales: {
						y: {
							beginAtZero: true,
							grid: {
								color: 'rgba(0, 0, 0, 0.05)',
								drawBorder: false
							},
							ticks: {
								font: { size: 10 },
								color: '#666'
							}
						},
						x: {
							grid: { display: false },
							ticks: {
								font: { size: 10 },
								color: '#666'
							}
						}
					}
				};

			case 'bar':
				return {
					...commonOptions,
					indexAxis: 'y',
					scales: {
						x: {
							beginAtZero: true,
							grid: {
								color: 'rgba(0, 0, 0, 0.05)',
								drawBorder: false
							},
							ticks: {
								font: { size: 10 },
								color: '#666'
							}
						},
						y: {
							grid: { display: false },
							ticks: {
								font: { size: 10 },
								color: '#666'
							}
						}
					}
				};

			case 'pie':
			case 'doughnut':
				return {
					...commonOptions,
					plugins: {
						...commonOptions.plugins,
						tooltip: {
							...commonOptions.plugins.tooltip,
							callbacks: {
								label: function(context) {
									const label = context.label || '';
									const value = context.parsed || 0;
									const total = context.dataset.data.reduce((a, b) => a + b, 0);
									const percentage = ((value / total) * 100).toFixed(1);
									return label + ': ' + value + ' (' + percentage + '%)';
								}
							}
						}
					}
				};

			default:
				return commonOptions;
		}
	}

	/**
	 * Add controls (refresh, export) to chart
	 */
	function addChartControls(chartId, chart) {
		const container = document.getElementById(chartId)?.parentElement;
		
		if (!container) return;

		const controlsDiv = document.createElement('div');
		controlsDiv.style.marginTop = '8px';

		// Refresh button
		const refreshBtn = document.createElement('button');
		refreshBtn.className = 'leave-manager-admin-chart-refresh';
		refreshBtn.textContent = 'Refresh';
		refreshBtn.addEventListener('click', () => {
			refreshChart(chartId);
		});

		// Export button
		const exportBtn = document.createElement('button');
		exportBtn.className = 'leave-manager-admin-chart-export';
		exportBtn.textContent = 'Download';
		exportBtn.addEventListener('click', () => {
			downloadChart(chartId, chart);
		});

		controlsDiv.appendChild(refreshBtn);
		controlsDiv.appendChild(exportBtn);
		container.appendChild(controlsDiv);
	}

	/**
	 * Refresh chart data
	 */
	function refreshChart(chartId) {
		const canvas = document.getElementById(chartId);
		const chartType = canvas?.getAttribute('data-chart-type');

		if (!canvas || !chartType) return;

		// Show loading state
		const container = canvas.parentElement;
		const originalContent = container.innerHTML;
		container.innerHTML = '<div class="leave-manager-admin-chart-loading">Loading...</div>';

		// Fetch new data
		fetch(ajaxurl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				action: 'leave_manager_get_chart_data',
				chart_id: chartId,
				nonce: document.querySelector('[data-nonce]')?.getAttribute('data-nonce') || ''
			})
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				// Restore original HTML
				container.innerHTML = originalContent;
				
				// Update chart
				const chart = window.leaveManagerAdminCharts[chartId];
				if (chart) {
					chart.data = data.data;
					chart.update();
				}
			} else {
				showChartError(chartId, data.data.message || 'Failed to refresh chart');
			}
		})
		.catch(error => {
			console.error('Error refreshing chart:', error);
			showChartError(chartId, 'Failed to refresh chart');
		});
	}

	/**
	 * Download chart as image
	 */
	function downloadChart(chartId, chart) {
		const link = document.createElement('a');
		link.href = chart.toBase64Image();
		link.download = 'leave-manager-' + chartId + '-' + new Date().getTime() + '.png';
		link.click();
	}

	/**
	 * Show chart error message
	 */
	function showChartError(chartId, message) {
		const canvas = document.getElementById(chartId);
		if (canvas) {
			const container = canvas.parentElement;
			container.innerHTML = '<div class="leave-manager-admin-chart-error">' + message + '</div>';
		}
	}

	/**
	 * Update chart data
	 */
	window.updateAdminChart = function(chartId, newData) {
		const chart = window.leaveManagerAdminCharts[chartId];
		
		if (!chart) {
			console.warn('Chart not found:', chartId);
			return;
		}

		chart.data = newData;
		chart.update();
	};

	/**
	 * Destroy chart
	 */
	window.destroyAdminChart = function(chartId) {
		const chart = window.leaveManagerAdminCharts[chartId];
		
		if (!chart) return;

		chart.destroy();
		delete window.leaveManagerAdminCharts[chartId];
	};

	/**
	 * Initialize on DOM ready
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAdminCharts);
	} else {
		initAdminCharts();
	}
})();
