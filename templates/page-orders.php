<?php
/**
 * Template: Orders (/dashboard/orders)
 * @package global-tractors
 */
if ( ! defined( 'ABSPATH' ) ) exit;
gti_require_login();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
</head>
<body class="gti-dashboard-body">
    <div class="gti-dashboard">
        <aside class="gti-sidebar">
            <div class="gti-sidebar-logo">
                <?php if ( has_custom_logo() ) : ?><?php the_custom_logo(); ?><?php else : ?>
                    <h3 style="color:white"><?php bloginfo('name'); ?></h3>
                <?php endif; ?>
            </div>
            <nav class="gti-sidebar-menu">
                <a href="<?php echo esc_url( gti_dashboard_url() ); ?>" class="gti-menu-item"><i data-lucide="layout-dashboard"></i> Dashboard</a>
                <a href="<?php echo esc_url( gti_dashboard_url('profile') ); ?>" class="gti-menu-item"><i data-lucide="user"></i> Profil Saya</a>
                <a href="<?php echo esc_url( gti_dashboard_url('orders') ); ?>" class="gti-menu-item active"><i data-lucide="package"></i> Pesanan Saya</a>
                <div class="gti-menu-divider"></div>
                <a href="#" class="gti-menu-item" id="gti-logout-btn"><i data-lucide="log-out"></i> Keluar</a>
            </nav>
        </aside>
        <main class="gti-main">
            <header class="gti-header">
                <h1 class="gti-page-title">Pesanan Saya</h1>
            </header>
            <div class="gti-content">
                <div class="gti-card">
                    <div class="gti-card-body">
                        <div id="gti-orders-list">
                            <p class="gti-text-muted">Memuat pesanan...</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        var container = document.getElementById('gti-orders-list');

        fetch(gtiAjax.ajaxurl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=gti_get_orders'
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (!res.success || !res.data.orders.length) {
                container.innerHTML = '<p class="gti-text-muted">Belum ada pesanan.</p>';
                return;
            }
            var orders = res.data.orders;
            var html = '<table class="gti-table"><thead><tr><th>ID</th><th>Tanggal</th><th>Items</th><th>Status</th><th>Total</th></tr></thead><tbody>';
            orders.forEach(function(order) {
                html += '<tr>';
                html += '<td><a href="' + gtiAjax.ajaxurl + '" class="gti-link">#' + order.id + '</a></td>';
                html += '<td>' + new Date(order.date).toLocaleDateString('id-ID') + '</td>';
                html += '<td>' + order.item_count + ' item</td>';
                html += '<td><span class="gti-badge gti-badge--' + order.status + '">' + order.status + '</span></td>';
                html += '<td>Rp ' + parseInt(order.total).toLocaleString('id-ID') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        });

        document.getElementById('gti-logout-btn').addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Yakin ingin keluar?')) {
                fetch(gtiAjax.ajaxurl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=gti_logout&gti_logout_nonce=' + gtiAjax.nonce
                }).then(function(r) { return r.json(); }).then(function(res) {
                    if (res.success) window.location.href = res.data.redirect;
                });
            }
        });
    });
    </script>
</body>
</html>
