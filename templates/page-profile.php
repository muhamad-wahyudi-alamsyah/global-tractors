<?php
/**
 * Template: Profile (/dashboard/profile)
 * @package global-tractors
 */
if ( ! defined( 'ABSPATH' ) ) exit;
gti_require_login();

$current_user = wp_get_current_user();
$profile = gti_get_user_profile( $current_user->ID );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - <?php bloginfo('name'); ?></title>
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
                <a href="<?php echo esc_url( gti_dashboard_url('profile') ); ?>" class="gti-menu-item active"><i data-lucide="user"></i> Profil Saya</a>
                <a href="<?php echo esc_url( gti_dashboard_url('orders') ); ?>" class="gti-menu-item"><i data-lucide="package"></i> Pesanan Saya</a>
                <div class="gti-menu-divider"></div>
                <a href="#" class="gti-menu-item" id="gti-logout-btn"><i data-lucide="log-out"></i> Keluar</a>
            </nav>
        </aside>
        <main class="gti-main">
            <header class="gti-header">
                <h1 class="gti-page-title">Profil Saya</h1>
            </header>
            <div class="gti-content">
                <div class="gti-card">
                    <div class="gti-card-body">
                        <div id="gti-profile-alert" class="gti-alert" role="alert"></div>
                        <form id="gti-profile-form" novalidate>
                            <?php wp_nonce_field( 'gti_profile_action', 'gti_profile_nonce' ); ?>
                            <div class="gti-form-grid">
                                <div class="gti-field">
                                    <label for="gti-profile-name" class="gti-label">Nama Lengkap</label>
                                    <input type="text" id="gti-profile-name" name="name" class="gti-input" value="<?php echo esc_attr( $profile['name'] ); ?>" required>
                                </div>
                                <div class="gti-field">
                                    <label class="gti-label">Email</label>
                                    <input type="email" class="gti-input" value="<?php echo esc_attr( $profile['email'] ); ?>" disabled>
                                </div>
                                <div class="gti-field">
                                    <label for="gti-profile-phone" class="gti-label">Nomor HP</label>
                                    <input type="tel" id="gti-profile-phone" name="phone" class="gti-input" value="<?php echo esc_attr( $profile['phone'] ); ?>" required>
                                </div>
                                <div class="gti-field">
                                    <label for="gti-profile-city" class="gti-label">Kota</label>
                                    <input type="text" id="gti-profile-city" name="city" class="gti-input" value="<?php echo esc_attr( $profile['city'] ); ?>">
                                </div>
                                <div class="gti-field gti-field--full">
                                    <label for="gti-profile-address" class="gti-label">Alamat</label>
                                    <textarea id="gti-profile-address" name="address" class="gti-input gti-textarea" rows="3"><?php echo esc_textarea( $profile['address'] ); ?></textarea>
                                </div>
                            </div>
                            <div class="gti-form-actions">
                                <button type="submit" class="gti-btn gti-btn--primary" id="gti-profile-btn">
                                    <i data-lucide="save"></i>
                                    <span class="btn-text">Simpan Perubahan</span>
                                    <span class="gti-spinner"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        var form = document.getElementById('gti-profile-form');
        var btn = document.getElementById('gti-profile-btn');
        var alert = document.getElementById('gti-profile-alert');

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            btn.classList.add('loading');
            btn.disabled = true;
            alert.style.display = 'none';

            var data = new FormData(form);
            data.append('action', 'gti_update_profile');

            fetch(gtiAjax.ajaxurl, { method: 'POST', body: data })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    alert.className = res.success ? 'gti-alert gti-alert--success' : 'gti-alert gti-alert--error';
                    alert.textContent = res.data.message;
                    alert.style.display = 'block';
                })
                .catch(function() {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    alert.className = 'gti-alert gti-alert--error';
                    alert.textContent = 'Terjadi kesalahan.';
                    alert.style.display = 'block';
                });
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
