<?php
/**
 * Dashboard sidebar, rendered from gti_visible_dashboard_menu().
 *
 * Items the current role has no capability for are already filtered out
 * (PRD §9.4 layer 1), and badge numbers are real counts rather than the
 * hardcoded "3" that used to ship in every template (B-06).
 *
 * @var array $args page (current slug)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$gti_current = $args['page'];
$gti_badges  = gti_menu_badge_counts();

/** Render one leaf link. */
$gti_render_item = function ( array $item, $child = false ) use ( $gti_current, $gti_badges ) {
    $slug   = isset( $item['slug'] ) ? $item['slug'] : '';
    $active = ( $slug === $gti_current ) ? ' active' : '';
    $class  = $child ? 'gti-nav-child' : 'gti-nav-item';
    $count  = ( ! empty( $item['badge'] ) && ! empty( $gti_badges[ $item['badge'] ] ) )
        ? (int) $gti_badges[ $item['badge'] ] : 0;
    ?>
    <a href="<?php echo esc_url( gti_dashboard_url( $slug ) ); ?>" class="<?php echo esc_attr( $class . $active ); ?>">
        <i class="<?php echo $child ? '' : 'fas ' . esc_attr( $item['icon'] ); ?>"></i>
        <span><?php echo esc_html( $item['label'] ); ?></span>
        <?php if ( $count ) : ?>
            <span class="gti-nav-badge"><?php echo esc_html( $count > 99 ? '99+' : $count ); ?></span>
        <?php endif; ?>
    </a>
    <?php
};
?>
<aside class="gti-sidebar" id="gti-sidebar">
    <div class="gti-sidebar-header">
        <div class="gti-logo">
            <img src="<?php echo esc_url( gti_logo_url( 'full' ) ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="gti-logo-img">
            <img src="<?php echo esc_url( gti_logo_url( 'favicon' ) ); ?>" alt="" class="gti-logo-favicon">
        </div>
    </div>

    <div class="gti-sidebar-divider"></div>

    <nav class="gti-nav">
        <?php
        $gti_menu = gti_visible_dashboard_menu();
        $gti_open = false;

        foreach ( $gti_menu as $gti_item ) :
            $gti_type = isset( $gti_item['type'] ) ? $gti_item['type'] : 'item';

            if ( $gti_type === 'section' ) :
                if ( $gti_open ) { echo '</div>'; }
                echo '<div class="gti-nav-group">';
                $gti_open = true;
                echo '<div class="gti-nav-section">' . esc_html( $gti_item['label'] ) . '</div>';
                continue;
            endif;

            if ( ! $gti_open ) { echo '<div class="gti-nav-group">'; $gti_open = true; }

            if ( $gti_type === 'parent' ) :
                $gti_child_active = false;
                foreach ( $gti_item['children'] as $gti_child ) {
                    if ( $gti_child['slug'] === $gti_current ) { $gti_child_active = true; }
                }
                ?>
                <div class="gti-has-children<?php echo $gti_child_active ? ' open' : ''; ?>">
                    <a href="#" class="gti-nav-parent" data-toggle="dropdown">
                        <i class="fas <?php echo esc_attr( $gti_item['icon'] ); ?>"></i>
                        <span><?php echo esc_html( $gti_item['label'] ); ?></span>
                        <i class="fas fa-chevron-down gti-nav-arrow"></i>
                    </a>
                    <div class="gti-nav-children">
                        <?php foreach ( $gti_item['children'] as $gti_child ) { $gti_render_item( $gti_child, true ); } ?>
                    </div>
                </div>
                <?php
                continue;
            endif;

            $gti_render_item( $gti_item );
        endforeach;

        if ( $gti_open ) { echo '</div>'; }
        ?>
    </nav>

    <div class="gti-sidebar-footer">
        <a href="#" class="gti-nav-item" id="gti-collapse-btn">
            <i class="fas fa-chevron-left"></i>
            <span>Collapse Menu</span>
        </a>
    </div>
</aside>
