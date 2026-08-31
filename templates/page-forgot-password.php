<?php
/**
 * Template: Forgot Password (/dashboard/forgot-password)
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
    <title>Lupa Password - <?php bloginfo('name'); ?></title>
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
            <h1 class="gti-auth-title">Lupa Password?</h1>
            <p class="gti-auth-subtitle">Masukkan email Anda dan kami akan mengirimkan link untuk reset password.</p>
            <div id="gti-forgot-alert" class="gti-alert" role="alert"></div>
            <form id="gti-forgot-form" novalidate>
                <?php wp_nonce_field( 'gti_forgot_password_action', 'gti_forgot_password_nonce' ); ?>
                <div class="gti-field">
                    <label for="gti-forgot-email" class="gti-label">Email <span class="required">*</span></label>
                    <div class="gti-input-wrapper">
                        <i data-lucide="mail" class="gti-input-icon"></i>
                        <input type="email" id="gti-forgot-email" name="email" class="gti-input gti-input--icon-left" placeholder="email@example.com" autocomplete="email" required>
                    </div>
                </div>
                <button type="submit" class="gti-btn gti-btn--primary" id="gti-forgot-btn">
                    <i data-lucide="send"></i>
                    <span class="btn-text">Kirim Link Reset</span>
                    <span class="gti-spinner"></span>
                </button>
            </form>
            <p class="gti-auth-footer"><a href="<?php echo esc_url( gti_login_url() ); ?>" class="gti-link">&larr; Kembali ke login</a></p>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        var form = document.getElementById('gti-forgot-form');
        var btn = document.getElementById('gti-forgot-btn');
        var alert = document.getElementById('gti-forgot-alert');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            btn.classList.add('loading');
            btn.disabled = true;
            alert.style.display = 'none';
            var data = new FormData(form);
            data.append('action', 'gti_forgot_password');
            fetch(gtiAjax.ajaxurl, { method: 'POST', body: data })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    alert.className = 'gti-alert gti-alert--success';
                    alert.textContent = res.data.message;
                    alert.style.display = 'block';
                    form.reset();
                })
                .catch(function() {
                    btn.classList.remove('loading');
                    btn.disabled = false;
                    alert.className = 'gti-alert gti-alert--error';
                    alert.textContent = 'Terjadi kesalahan.';
                    alert.style.display = 'block';
                });
        });
    });
    </script>
</body>
</html>
