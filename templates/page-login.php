<?php
/**
 * Template: Login Page (/dashboard/login)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_redirect_if_logged_in();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/pages/login.css?v=<?php echo gti_asset_version('assets/css/pages/login.css'); ?>">
</head>
<body>
    <div class="login-card">
        <div style="text-align:center;margin-bottom:20px">
            <svg viewBox="0 0 40 40" width="48" height="48" xmlns="http://www.w3.org/2000/svg">
                <rect width="40" height="40" rx="8" fill="#F5A623"/>
                <path d="M10 28V12h6l4 8 4-8h6v16h-5V18l-3 6h-4l-3-6v10z" fill="#1a1f36"/>
            </svg>
        </div>
        <h1>Global Tractors Indonesia</h1>
        <p>Masuk ke dashboard admin</p>
        <div class="login-error" id="login-error"></div>
        <form id="gti-login-form">
            <label for="username">Username atau Email</label>
            <input type="text" id="username" name="log" required>
            <label for="password">Password</label>
            <input type="password" id="password" name="pwd" required>
            <button type="submit">Masuk</button>
        </form>
    </div>
    <script>
    document.getElementById('gti-login-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var err = document.getElementById('login-error');
        err.style.display = 'none';
        var data = new FormData(form);
        data.append('action', 'gti_ajax_login');
        data.append('nonce', '<?php echo wp_create_nonce('gti_nonce'); ?>');
        fetch('<?php echo admin_url("admin-ajax.php"); ?>', { method: 'POST', body: data })
        .then(function(r){return r.json()})
        .then(function(res){
            if(res.success){window.location.href=res.data.redirect}
            else{err.textContent=res.data||'Login gagal';err.style.display='block'}
        });
    });
    </script>
</body>
</html>
