(function($) {
    'use strict';

    var BERP_Reports = {
        init: function() {
            this.bindEvents();
            this.initCharts();
        },

        bindEvents: function() {
            // Print button
            $('#berp-print-report').on('click', function() {
                window.print();
            });

            // Export buttons
            $('#berp-export-excel').on('click', function() {
                var currentUrl = window.location.href;
                if (currentUrl.indexOf('?') === -1) {
                    currentUrl += '?';
                } else {
                    currentUrl += '&';
                }
                var exportUrl = currentUrl + 'export=excel';
                window.location.href = exportUrl;
            });

            $('#berp-export-pdf').on('click', function() {
                var currentUrl = window.location.href;
                if (currentUrl.indexOf('?') === -1) {
                    currentUrl += '?';
                } else {
                    currentUrl += '&';
                }
                var exportUrl = currentUrl + 'export=pdf';
                window.location.href = exportUrl;
            });
        },

        initCharts: function() {
            if (typeof berpReportData === 'undefined') {
                return;
            }

            var ctx = null;
            var chartType = 'bar'; // Default
            var options = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            };

            // Determine canvas ID and chart type based on report type
            switch (berpReportData.type) {
                case 'attendance':
                    ctx = document.getElementById('berp-attendance-chart');
                    chartType = 'bar';
                    options.scales = {
                        y: {
                            beginAtZero: true
                        }
                    };
                    break;
                case 'payroll':
                    ctx = document.getElementById('berp-payroll-chart');
                    chartType = 'bar';
                    options.scales = {
                        x: {
                            stacked: true,
                        },
                        y: {
                            stacked: true,
                            beginAtZero: true
                        }
                    };
                    break;
                case 'profitability':
                    ctx = document.getElementById('berp-profitability-chart');
                    chartType = 'bar';
                    options.indexAxis = 'y'; // Horizontal bar
                    break;
                case 'expense':
                    ctx = document.getElementById('berp-expense-chart');
                    chartType = 'doughnut';
                    break;
                case 'aging':
                    ctx = document.getElementById('berp-aging-chart');
                    chartType = 'bar';
                    break;
                case 'payments':
                    ctx = document.getElementById('berp-payments-chart');
                    chartType = 'line';
                    options.elements = {
                        line: {
                            tension: 0.3 // Smooth curves
                        }
                    };
                    break;
                case 'balances':
                    ctx = document.getElementById('berp-balances-chart');
                    chartType = 'bar';
                    break;
                case 'budget':
                    ctx = document.getElementById('berp-budget-chart');
                    chartType = 'bar';
                    break;
                case 'overtime':
                    ctx = document.getElementById('berp-overtime-chart');
                    chartType = 'bar';
                    break;
                case 'revenue':
                    ctx = document.getElementById('berp-revenue-chart');
                    chartType = 'line';
                    options.elements = {
                        line: {
                            tension: 0.3
                        }
                    };
                    break;
            }

            if (ctx) {
                new Chart(ctx, {
                    type: chartType,
                    data: {
                        labels: berpReportData.labels,
                        datasets: berpReportData.datasets
                    },
                    options: options
                });
            }
        }
    };

    $(document).ready(function() {
        BERP_Reports.init();
    });

})(jQuery);
