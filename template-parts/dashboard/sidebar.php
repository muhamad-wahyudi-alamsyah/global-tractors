<?php
/**
 * Dashboard sidebar, rendered from gti_visible_dashboard_menu().
 *
 * Items the current role has no capability for are already filtered out
 * (PRD §9.4 layer 1), and badge numbers are real counts rather than the
 * hardcoded "3" that used to ship in every template (B-06).
 *
 * The markup is the shape the templates used to write by hand, because that is
 * what dashboard.css styles: a section heading and all of its links share one
 * .gti-nav-group, and a group holding a parent menu is itself the
 * .gti-has-children element that opens (.gti-nav-group.open shows the children).
 *
 * @var array $args page (current slug)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$gti_current = gti_menu_active_slug( $args['page'] );
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
        <i<?php echo $child ? '' : ' class="fas ' . esc_attr( $item['icon'] ) . '"'; ?>></i>
        <span><?php echo esc_html( $item['label'] ); ?></span>
        <?php if ( $count ) : ?>
            <span class="gti-nav-badge"><?php echo esc_html( $count > 99 ? '99+' : $count ); ?></span>
        <?php endif; ?>
    </a>
    <?php
};

// Every section heading starts a new group; items before the first heading
// (Dashboard) get a group of their own.
$gti_groups = array();
foreach ( gti_visible_dashboard_menu() as $gti_item ) {
    $gti_type = isset( $gti_item['type'] ) ? $gti_item['type'] : 'item';

    if ( $gti_type === 'section' || ! $gti_groups ) {
        $gti_groups[] = array( 'label' => $gti_type === 'section' ? $gti_item['label'] : '', 'items' => array() );
    }
    if ( $gti_type !== 'section' ) {
        $gti_groups[ count( $gti_groups ) - 1 ]['items'][] = $gti_item;
    }
}
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
        <?php foreach ( $gti_groups as $gti_group ) :
            $gti_parent_group = false;
            $gti_group_active = false;

            foreach ( $gti_group['items'] as $gti_item ) {
                $gti_links = isset( $gti_item['children'] ) ? $gti_item['children'] : array( $gti_item );
                if ( isset( $gti_item['type'] ) && $gti_item['type'] === 'parent' ) {
                    $gti_parent_group = true;
                }
                if ( in_array( $gti_current, wp_list_pluck( $gti_links, 'slug' ), true ) ) {
                    $gti_group_active = true;
                }
            }

            // The group stays open while any page inside it is showing — Spare
            // Parts included, as before.
            $gti_group_class = 'gti-nav-group';
            if ( $gti_parent_group ) {
                $gti_group_class .= ' gti-has-children' . ( $gti_group_active ? ' open' : '' );
            }
            ?>
            <div class="<?php echo esc_attr( $gti_group_class ); ?>">
                <?php if ( $gti_group['label'] !== '' ) : ?>
                    <div class="gti-nav-section"><?php echo esc_html( $gti_group['label'] ); ?></div>
                <?php endif; ?>

                <?php foreach ( $gti_group['items'] as $gti_item ) :
                    if ( ! isset( $gti_item['type'] ) || $gti_item['type'] !== 'parent' ) {
                        $gti_render_item( $gti_item );
                        continue;
                    }

                    $gti_child_active = in_array( $gti_current, wp_list_pluck( $gti_item['children'], 'slug' ), true );
                    ?>
                    <a href="#" class="gti-nav-parent<?php echo $gti_child_active ? ' active' : ''; ?>" data-toggle="dropdown">
                        <i class="fas <?php echo esc_attr( $gti_item['icon'] ); ?>"></i>
                        <span><?php echo esc_html( $gti_item['label'] ); ?></span>
                        <i class="fas fa-chevron-down gti-nav-arrow"></i>
                    </a>
                    <div class="gti-nav-children">
                        <?php foreach ( $gti_item['children'] as $gti_child ) { $gti_render_item( $gti_child, true ); } ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="gti-sidebar-footer">
        <a href="#" class="gti-nav-item" id="gti-collapse-btn">
            <i class="fas fa-chevron-left"></i>
            <span>Collapse Menu</span>
        </a>
    </div>
</aside>
