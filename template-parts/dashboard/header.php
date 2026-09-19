<?php
/**
 * Dashboard topbar.
 *
 * Fixes two things that were hardcoded in every copy of this block:
 *   B-06 — the notification badge said "3" regardless of the data
 *   B-07 — the role under the username always said "Super Admin"
 * The bell opens a popup of the latest "new" inbox rows (gti_notification_items()).
 *
 * @var array $args title, subtitle
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$gti_user       = wp_get_current_user();
$gti_role_label = gti_role_label_for_user();
$gti_unread     = gti_notification_count();
$gti_notifs     = gti_notification_items();
?>
<header class="gti-header">
    <div class="gti-header-left">
        <button class="gti-menu-toggle" id="gti-menu-toggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <div>
            <h1 class="gti-page-title"><?php echo esc_html( $args['title'] ); ?></h1>
            <?php if ( ! empty( $args['subtitle'] ) ) : ?>
                <p class="gti-welcome"><?php echo wp_kses_post( $args['subtitle'] ); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="gti-header-right">
        <div class="gti-notifications-wrap">
            <button type="button" class="gti-notifications" id="gti-notif-toggle" aria-haspopup="true" aria-expanded="false"
                    title="<?php echo esc_attr( sprintf( '%d pesanan baru', $gti_unread ) ); ?>">
                <i class="fas fa-bell"></i>
                <?php if ( $gti_unread > 0 ) : ?>
                    <span class="gti-badge"><?php echo esc_html( $gti_unread > 99 ? '99+' : $gti_unread ); ?></span>
                <?php endif; ?>
            </button>
            <div class="gti-notif-dropdown" id="gti-notif-dropdown">
                <div class="gti-notif-head">Notifikasi</div>
                <?php if ( ! $gti_notifs ) : ?>
                    <div class="gti-notif-empty">Tidak ada notifikasi baru</div>
                <?php endif; ?>
                <?php foreach ( $gti_notifs as $n ) : ?>
                    <a href="<?php echo esc_url( $n['url'] ); ?>" class="gti-notif-item">
                        <i class="fas <?php echo esc_attr( $n['icon'] ); ?>"></i>
                        <div>
                            <strong><?php echo esc_html( $n['label'] ); ?></strong>
                            <span><?php echo esc_html( implode( ' · ', array_filter( array( $n['customer'], $n['ref'] ) ) ) ); ?></span>
                            <small><?php echo esc_html( gti_time_ago( $n['created'] ) ); ?></small>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="gti-user-menu-wrap">
            <button type="button" class="gti-user-menu" id="gti-user-menu-toggle" aria-haspopup="true" aria-expanded="false">
                <img src="<?php echo esc_url( get_avatar_url( $gti_user->ID, array( 'size' => 80 ) ) ); ?>" alt="" class="gti-avatar">
                <div class="gti-user-info">
                    <strong><?php echo esc_html( $gti_user->display_name ); ?></strong>
                    <?php if ( $gti_role_label ) : ?><small><?php echo esc_html( $gti_role_label ); ?></small><?php endif; ?>
                </div>
                <i class="fas fa-chevron-down"></i>
            </button>
            <?php
            // The old sidebar carried the only Log Out control on the dashboard;
            // it lives here now that the sidebar is generated from the menu array.
            ?>
            <div class="gti-user-dropdown" id="gti-user-dropdown">
                <a href="<?php echo esc_url( gti_dashboard_url( 'users' ) ); ?>" class="gti-user-dropdown-item">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <?php if ( current_user_can( 'gti_manage_settings' ) ) : ?>
                    <a href="<?php echo esc_url( gti_dashboard_url( 'activity-log' ) ); ?>" class="gti-user-dropdown-item">
                        <i class="fas fa-history"></i> Activity Log
                    </a>
                <?php endif; ?>
                <div class="gti-user-dropdown-sep"></div>
                <button type="button" class="gti-user-dropdown-item danger" id="gti-logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Log Out
                </button>
            </div>
        </div>
    </div>
</header>
