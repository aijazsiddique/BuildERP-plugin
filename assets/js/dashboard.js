(function($) {
    'use strict';

    var BERP_Dashboard = {
        init: function() {
            this.initCharts();
        },

        initCharts: function() {
            if (typeof berpDashboard === 'undefined' || !berpDashboard.data) {
                return;
            }

            // Revenue Chart
            var revenueCtx = document.getElementById('berp-revenue-chart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'line',
                    data: berpDashboard.data.revenue,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value;
                                    }
                                }
                            }
                        },
                        elements: {
                            line: {
                                tension: 0.3
                            }
                        }
                    }
                });
            }

            // Profitability Chart
            var profitCtx = document.getElementById('berp-profitability-chart');
            if (profitCtx) {
                new Chart(profitCtx, {
                    type: 'bar',
                    data: berpDashboard.data.profitability,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }
    };

    $(document).ready(function() {
        BERP_Dashboard.init();
    });

})(jQuery);
