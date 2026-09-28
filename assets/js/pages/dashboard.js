/**
 * Dashboard charts — Website Visitors (line) and Request Overview (donut).
 *
 * Both draw from gtiPageData; the donut's legend is rendered server-side, so
 * Chart.js only draws the ring. Nothing runs if Chart.js failed to load from
 * its CDN — the cards keep their markup and the page stays usable.
 */
(function () {
    'use strict';

    var data = window.gtiPageData || {};

    function shortDate(iso) {
        var parts = String(iso).split('-');
        var month = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return parts.length === 3 ? Number(parts[2]) + ' ' + month[Number(parts[1]) - 1] : iso;
    }

    var visitorsCanvas = document.getElementById('visitorsChart');
    var visitors = data.visitors || [];

    if (visitorsCanvas && visitors.length && typeof Chart !== 'undefined') {
        new Chart(visitorsCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: visitors.map(function (row) { return shortDate(row.date); }),
                datasets: [{
                    label: 'Visitors',
                    data: visitors.map(function (row) { return row.visits; }),
                    borderColor: '#F9B204',
                    backgroundColor: 'rgba(249,178,4,0.12)',
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    tension: 0.35,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f0f0f0' } },
                    x: { grid: { display: false }, ticks: { maxTicksLimit: 8 } }
                }
            }
        });
    }

    var requestCanvas = document.getElementById('requestChart');
    var overview = data.overview || [];

    if (requestCanvas && overview.length && typeof Chart !== 'undefined') {
        new Chart(requestCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Request Equipment', 'Request Quotation', 'Sell Equipment'],
                datasets: [{
                    data: overview,
                    backgroundColor: ['#F5A623', '#1a1f36', '#6b7280'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { legend: { display: false } }
            }
        });
    }
})();
