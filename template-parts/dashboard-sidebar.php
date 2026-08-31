<?php
/**
 * Template Part: Dashboard Sidebar
 * @package global-tractors
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<aside class="gti-sidebar">
    <div class="gti-sidebar-logo">
        <?php if ( has_custom_logo() ) : ?><?php the_custom_logo(); ?><?php else : ?>
            <h3 style="color:white"><?php bloginfo('name'); ?></h3>
        <?php endif; ?>
    </div>
    <nav class="gti-sidebar-menu">
        <a href="<?php echo esc_url( gti_dashboard_url() ); ?>" class="gti-menu-item <?php echo ( untrailingslashit($_SERVER['REQUEST_URI'] ?? '') === '/dashboard' ) ? 'active' : ''; ?>">
            <i data-lucide="layout-dashboard"></i> Dashboard
        </a>
        <a href="<?php echo esc_url( gti_dashboard_url('profile') ); ?>" class="gti-menu-item <?php echo gti_is_active('/dashboard/profile') ? 'active' : ''; ?>">
            <i data-lucide="user"></i> Profil Saya
        </a>
        <a href="<?php echo esc_url( gti_dashboard_url('orders') ); ?>" class="gti-menu-item <?php echo gti_is_active('/dashboard/orders') ? 'active' : ''; ?>">
            <i data-lucide="package"></i> Pesanan Saya
        </a>
        <div class="gti-menu-divider"></div>
        <a href="#" class="gti-menu-item" id="gti-logout-btn">
            <i data-lucide="log-out"></i> Keluar
        </a>
    </nav>
</aside>
