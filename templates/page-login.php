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
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#f3f4f6;display:flex;align-items:center;justify-content:center;min-height:100vh}
        .login-card{background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.1);padding:40px;width:100%;max-width:400px;margin:20px}
        .login-card h1{font-size:22px;text-align:center;margin-bottom:8px;color:#1a1f36}
        .login-card p{text-align:center;color:#6b7280;font-size:13px;margin-bottom:24px}
        .login-card label{display:block;font-size:13px;font-weight:500;color:#374151;margin-bottom:6px}
        .login-card input[type="text"],.login-card input[type="password"]{width:100%;padding:10px 14px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;font-family:inherit;margin-bottom:16px;transition:border-color .2s}
        .login-card input:focus{outline:none;border-color:#F5A623}
        .login-card button{width:100%;padding:12px;background:#F5A623;color:#1a1f36;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;font-family:inherit}
        .login-card button:hover{background:#e6951a}
        .login-error{background:#fef2f2;color:#dc2626;padding:10px;border-radius:8px;font-size:13px;margin-bottom:16px;display:none}
    </style>
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
