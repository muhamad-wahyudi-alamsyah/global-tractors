<?php
/**
 * Dashboard topbar.
 *
 * Fixes three things that were hardcoded in every copy of this block:
 *   B-06 — the notification badge said "3" regardless of the data
 *   B-07 — the role under the username always said "Super Admin"
 *   B-08 — the date range was the literal string "May 1, 2024 - May 31, 2024"
 *
 * @var array $args title, subtitle
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$gti_user       = wp_get_current_user();
$gti_role_label = gti_role_label_for_user();
$gti_unread     = gti_notification_count();
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
        <div class="gti-date-filter">
            <i class="fas fa-calendar"></i>
            <span><?php echo esc_html( date_i18n( 'M j, Y' ) ); ?></span>
        </div>
        <div class="gti-notifications" title="<?php echo esc_attr( sprintf( '%d pesanan baru', $gti_unread ) ); ?>">
            <i class="fas fa-bell"></i>
            <?php if ( $gti_unread > 0 ) : ?>
                <span class="gti-badge"><?php echo esc_html( $gti_unread > 99 ? '99+' : $gti_unread ); ?></span>
            <?php endif; ?>
        </div>
        <div class="gti-user-menu">
            <img src="<?php echo esc_url( get_avatar_url( $gti_user->ID, array( 'size' => 80 ) ) ); ?>" alt="" class="gti-avatar">
            <div class="gti-user-info">
                <strong><?php echo esc_html( $gti_user->display_name ); ?></strong>
                <?php if ( $gti_role_label ) : ?><small><?php echo esc_html( $gti_role_label ); ?></small><?php endif; ?>
            </div>
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
</header>
