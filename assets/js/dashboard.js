document.addEventListener('DOMContentLoaded', function() {
    // Sidebar collapse is handled inline in page-dashboard.php (with localStorage)
    var menuToggle = document.getElementById('gti-menu-toggle');
    var sidebar = document.getElementById('gti-sidebar');

    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
    }

    // Logout
    var logoutBtn = document.getElementById('gti-logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Yakin ingin keluar?')) {
                fetch(gtiAjax.ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=gti_logout&nonce=' + gtiAjax.nonce
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) window.location.href = res.data.redirect;
                });
            }
        });
    }

    // Visitors Chart
    var visitorsCanvas = document.getElementById('visitorsChart');
    if (visitorsCanvas && typeof Chart !== 'undefined') {
        new Chart(visitorsCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: ['May 1', '', 'May 8', '', 'May 15', '', 'May 22', '', 'May 31'],
                datasets: [{
                    label: 'Visitors',
                    data: [3200, 2800, 4500, 3800, 5200, 4800, 6100, 5500, 5800],
                    borderColor: '#F5A623',
                    backgroundColor: 'rgba(245,166,35,0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 8000,
                        ticks: { stepSize: 2000, callback: function(v) { return (v / 1000) + 'K'; } },
                        grid: { color: '#f3f4f6' },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
    }

    // Request Overview Donut
    var requestCanvas = document.getElementById('requestChart');
    if (requestCanvas && typeof Chart !== 'undefined') {
        new Chart(requestCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Request Equipment', 'Request Quotation', 'Sell Equipment', 'Contact Messages'],
                datasets: [{
                    data: [18, 31, 14, 19],
                    backgroundColor: ['#F5A623', '#1a1f36', '#6b7280', '#d1d5db'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var total = ctx.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var pct = Math.round((ctx.raw / total) * 100);
                                return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'centerText',
                afterDraw: function(chart) {
                    var ctx = chart.ctx;
                    var centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                    var centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;
                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.font = 'bold 28px Inter';
                    ctx.fillStyle = '#1a1f36';
                    ctx.fillText('82', centerX, centerY - 8);
                    ctx.font = '12px Inter';
                    ctx.fillStyle = '#6b7280';
                    ctx.fillText('Total', centerX, centerY + 14);
                    ctx.restore();
                }
            }]
        });
    }
});
