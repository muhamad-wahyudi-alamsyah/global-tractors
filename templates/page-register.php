<?php
/**
 * Template: Register (/dashboard/register)
 * @package global-tractors
 */
if ( ! defined( 'ABSPATH' ) ) exit;
gti_redirect_if_logged_in();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/auth.css">
</head>
<body class="gti-auth-body">
    <div class="gti-auth-container">
        <div class="gti-auth-card">
            <div class="gti-auth-logo">
                <?php if ( has_custom_logo() ) : ?><?php the_custom_logo(); ?><?php else : ?>
                    <h2 style="color:#1a1f36"><?php bloginfo('name'); ?></h2>
                <?php endif; ?>
            </div>
            <h1 class="gti-auth-title">Buat Akun Baru</h1>
            <p class="gti-auth-subtitle">Daftar untuk mulai menggunakan layanan kami.</p>
            <div id="gti-register-alert" class="gti-alert" role="alert"></div>
            <form id="gti-register-form" novalidate>
                <?php wp_nonce_field( 'gti_register_action', 'gti_register_nonce' ); ?>
                <div class="gti-field">
                    <label for="gti-name" class="gti-label">Nama Lengkap <span class="required">*</span></label>
                    <div class="gti-input-wrapper">
                        <i data-lucide="user" class="gti-input-icon"></i>
                        <input type="text" id="gti-name" name="name" class="gti-input gti-input--icon-left" placeholder="John Doe" required>
                    </div>
                </div>
                <div class="gti-field">
                    <label for="gti-email" class="gti-label">Email <span class="required">*</span></label>
                    <div class="gti-input-wrapper">
                        <i data-lucide="mail" class="gti-input-icon"></i>
                        <input type="email" id="gti-email" name="email" class="gti-input gti-input--icon-left" placeholder="email@example.com" autocomplete="email" required>
                    </div>
                </div>
                <div class="gti-field">
                    <label for="gti-phone" class="gti-label">Nomor HP <span class="required">*</span></label>
                    <div class="gti-input-wrapper">
                        <i data-lucide="phone" class="gti-input-icon"></i>
                        <input type="tel" id="gti-phone" name="phone" class="gti-input gti-input--icon-left" placeholder="08123456789" autocomplete="tel" required>
                    </div>
                </div>
                <div class="gti-field">
                    <label for="gti-reg-password" class="gti-label">Password <span class="required">*</span></label>
                    <div class="gti-input-wrapper">
                        <i data-lucide="lock" class="gti-input-icon"></i>
                        <input type="password" id="gti-reg-password" name="password" class="gti-input gti-input--icon-left gti-input--icon-right" placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                        <button type="button" class="gti-input-toggle" data-toggle="gti-reg-password"><i data-lucide="eye"></i></button>
                    </div>
                </div>
                <div class="gti-field">
                    <label for="gti-reg-confirm" class="gti-label">Konfirmasi Password <span class="required">*</span></label>
                    <div class="gti-input-wrapper">
                        <i data-lucide="lock" class="gti-input-icon"></i>
                        <input type="password" id="gti-reg-confirm" name="password_confirm" class="gti-input gti-input--icon-left" placeholder="Ulangi password" autocomplete="new-password" required>
                    </div>
                </div>
                <button type="submit" class="gti-btn gti-btn--primary" id="gti-register-btn">
                    <i data-lucide="user-plus"></i>
                    <span class="btn-text">Daftar</span>
                    <span class="gti-spinner"></span>
                </button>
            </form>
            <p class="gti-auth-footer">Sudah punya akun? <a href="<?php echo esc_url( gti_login_url() ); ?>" class="gti-link">Masuk</a></p>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        var form = document.getElementById('gti-register-form');
        var btn = document.getElementById('gti-register-btn');
        var alert = document.getElementById('gti-register-alert');

        document.querySelectorAll('[data-toggle]').forEach(function(el) {
            el.addEventListener('click', function() {
                var input = document.getElementById(el.dataset.toggle);
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            btn.classList.add('loading');
            btn.disabled = true;
            alert.style.display = 'none';

            var data = new FormData(form);
            data.append('action', 'gti_register');

            fetch(gtiAjax.ajaxurl, { method: 'POST', body: data })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    if (res.success) {
                        alert.className = 'gti-alert gti-alert--success';
                        alert.textContent = res.data.message;
                        alert.style.display = 'block';
                        setTimeout(function() { window.location.href = res.data.redirect; }, 500);
                    } else {
                        alert.className = 'gti-alert gti-alert--error';
                        alert.textContent = res.data.message;
                        alert.style.display = 'block';
                    }
                })
                .catch(function() {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    alert.className = 'gti-alert gti-alert--error';
                    alert.textContent = 'Terjadi kesalahan. Silakan coba lagi.';
                    alert.style.display = 'block';
                });
        });
    });
    </script>
</body>
</html>
