/**
 * GTI Charts JavaScript
 * 
 * Chart.js integration for dashboard
 */

(function($) {
    'use strict';

    var GTI_Charts = {
        charts: {},

        init: function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded');
                return;
            }
            
            this.initCharts();
        },

        initCharts: function() {
            // Equipment Overview Chart
            if ($('#equipmentChart').length) {
                this.createEquipmentChart();
            }
            
            // Request Overview Chart
            if ($('#requestChart').length) {
                this.createRequestChart();
            }
            
            // Stock Status Chart
            if ($('#stockChart').length) {
                this.createStockChart();
            }
        },

        createEquipmentChart: function() {
            var ctx = document.getElementById('equipmentChart').getContext('2d');
            
            this.charts.equipment = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Available', 'Sold', 'Rented', 'Reserved', 'Maintenance'],
                    datasets: [{
                        label: 'Equipment',
                        data: [89, 20, 10, 3, 2],
                        backgroundColor: [
                            '#10b981',
                            '#ef4444',
                            '#3b82f6',
                            '#f59e0b',
                            '#8b5cf6'
                        ],
                        borderRadius: 6,
                        barThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        createRequestChart: function() {
            var ctx = document.getElementById('requestChart').getContext('2d');
            
            this.charts.request = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Requests',
                        data: [12, 19, 15, 25, 22, 30],
                        borderColor: '#F5A623',
                        backgroundColor: 'rgba(245, 166, 35, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#F5A623',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }, {
                        label: 'Quotations',
                        data: [8, 12, 10, 18, 15, 22],
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f3f4f6'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        },

        createStockChart: function() {
            var ctx = document.getElementById('stockChart').getContext('2d');
            
            this.charts.stock = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                    datasets: [{
                        data: [278, 23, 23],
                        backgroundColor: [
                            '#10b981',
                            '#f59e0b',
                            '#ef4444'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '70%'
                }
            });
        },

        // Update chart data
        updateChart: function(chartName, data) {
            if (this.charts[chartName]) {
                this.charts[chartName].data = data;
                this.charts[chartName].update();
            }
        },

        // Destroy chart
        destroyChart: function(chartName) {
            if (this.charts[chartName]) {
                this.charts[chartName].destroy();
                delete this.charts[chartName];
            }
        },

        // Destroy all charts
        destroyAll: function() {
            for (var chartName in this.charts) {
                this.destroyChart(chartName);
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        GTI_Charts.init();
    });

    // Make available globally
    window.GTI_Charts = GTI_Charts;

})(jQuery);