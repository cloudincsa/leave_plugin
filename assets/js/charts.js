/**
 * Chart.js Integration Script
 * 
 * Initializes and manages Chart.js for dashboard visualizations
 */

(function() {
	'use strict';

	// Store chart instances
	window.leaveManagerCharts = {};

	/**
	 * Initialize all charts on the page
	 */
	function initCharts() {
		const chartElements = document.querySelectorAll('[data-chart-type]');
		
		chartElements.forEach(element => {
			const chartType = element.getAttribute('data-chart-type');
			const chartId = element.getAttribute('data-chart-id');
			const chartData = element.getAttribute('data-chart-data');
			
			if (chartId && chartData) {
				try {
					const data = JSON.parse(chartData);
					initChart(chartId, chartType, data);
				} catch (e) {
					console.error('Error parsing chart data:', e);
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

		const ctx = canvas.getContext('2d');
		const options = getChartOptions(chartType);

		// Create chart instance
		const chart = new Chart(ctx, {
			type: chartType,
			data: data,
			options: options
		});

		// Store chart instance
		window.leaveManagerCharts[chartId] = chart;

		// Add export functionality
		addChartExport(chartId, chart);
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
							family: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif',
							size: 12
						},
						color: '#333333',
						padding: 15,
						usePointStyle: true
					}
				},
				tooltip: {
					backgroundColor: 'rgba(0, 0, 0, 0.8)',
					titleFont: { size: 14 },
					bodyFont: { size: 13 },
					padding: 12,
					cornerRadius: 6,
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
								font: { size: 11 },
								color: '#999999'
							}
						},
						x: {
							grid: { display: false },
							ticks: {
								font: { size: 11 },
								color: '#999999'
							}
						}
					}
				};

			case 'bar':
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
								font: { size: 11 },
								color: '#999999'
							}
						},
						x: {
							grid: { display: false },
							ticks: {
								font: { size: 11 },
								color: '#999999'
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
	 * Add export functionality to chart
	 */
	function addChartExport(chartId, chart) {
		const container = document.getElementById(chartId)?.parentElement;
		
		if (!container) return;

		const exportBtn = document.createElement('button');
		exportBtn.className = 'leave-manager-chart-export';
		exportBtn.textContent = 'Download Chart';
		exportBtn.addEventListener('click', () => {
			downloadChart(chartId, chart);
		});

		container.appendChild(exportBtn);
	}

	/**
	 * Download chart as image
	 */
	function downloadChart(chartId, chart) {
		const link = document.createElement('a');
		link.href = chart.toBase64Image();
		link.download = chartId + '-' + new Date().getTime() + '.png';
		link.click();
	}

	/**
	 * Update chart data
	 */
	window.updateChart = function(chartId, newData) {
		const chart = window.leaveManagerCharts[chartId];
		
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
	window.destroyChart = function(chartId) {
		const chart = window.leaveManagerCharts[chartId];
		
		if (!chart) return;

		chart.destroy();
		delete window.leaveManagerCharts[chartId];
	};

	/**
	 * Initialize on DOM ready
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initCharts);
	} else {
		initCharts();
	}

	/**
	 * Re-initialize charts when content is dynamically loaded
	 */
	document.addEventListener('leave-manager-content-loaded', initCharts);
})();
