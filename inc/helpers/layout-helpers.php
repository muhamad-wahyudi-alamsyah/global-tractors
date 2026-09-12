<?php
/**
 * Dashboard layout helpers (PRD §13.6 / R1).
 *
 * A dashboard template used to open with ~200 lines of boilerplate — doctype,
 * <head>, sidebar, header — copied verbatim into 18 files. That is why fixing
 * one bug (B-06, B-07, R-09) meant editing 18 files and why some copies were
 * always missed. Templates now open with gti_dashboard_open() and close with
 * gti_dashboard_close().
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Assets the current dashboard page asked for. Read back by the enqueue hook.
 */
function gti_page_assets( $set = null ) {
    static $assets = array( 'css' => array(), 'js' => array(), 'page' => '', 'data' => array() );

    if ( is_array( $set ) ) {
        $assets = array_merge( $assets, $set );
    }

    return $assets;
}

/**
 * Data for the current page's script, exposed as `gtiPageData`.
 *
 * Replaces the `<?php echo $json; ?>` interpolations that used to sit inside
 * inline <script> blocks — which is what kept that JS stuck in the template
 * (PRD §13.8).
 *
 * @param array|null $set Merge these keys, or omit to read the current set.
 */
function gti_page_data( $set = null ) {
    static $data = array();

    if ( is_array( $set ) ) {
        $data = array_merge( $data, $set );
    }

    return $data;
}

/**
 * Open a dashboard page: doctype, head, sidebar, header, opening <main>.
 *
 * @param array $args {
 *     @type string $page       Slug, used for the active menu item and page CSS/JS.
 *     @type string $title      Page heading and <title>.
 *     @type string $subtitle   Line under the heading.
 *     @type string $body_class Extra body classes.
 *     @type array  $css        Extra shared stylesheets, e.g. ['dashboard-drawer'].
 *     @type array  $js         Extra page scripts from assets/js/pages/.
 *     @type string $cap        Capability required; redirects to /dashboard without it.
 * }
 */
function gti_dashboard_open( array $args = array() ) {
    $args = array_merge( array(
        'page'       => '',
        'title'      => '',
        'subtitle'   => '',
        'body_class' => '',
        'css'        => array(),
        'js'         => array(),
        'cap'        => '',
    ), $args );

    // PRD §9.4 layer 2 — page-level capability check.
    if ( $args['cap'] ) {
        gti_require_cap( $args['cap'] );
    } else {
        gti_require_login();
    }

    gti_page_assets( array(
        'page' => $args['page'],
        'css'  => (array) $args['css'],
        'js'   => (array) $args['js'],
    ) );

    include GTI_CHILD_DIR . '/template-parts/dashboard/head.php';
    include GTI_CHILD_DIR . '/template-parts/dashboard/sidebar.php';

    // id is kept because page scripts resolve the main region by getElementById.
    echo '<main class="gti-main" id="gti-main">';

    include GTI_CHILD_DIR . '/template-parts/dashboard/header.php';
}

/**
 * Close a dashboard page: shared modals, toast host, footer scripts.
 *
 * @param array $args {
 *     @type array $modals Shared modals to render: delete, email, upload, invoice, assign.
 * }
 */
function gti_dashboard_close( array $args = array() ) {
    $args = array_merge( array( 'modals' => array() ), $args );

    include GTI_CHILD_DIR . '/template-parts/dashboard/footer.php';
}

/**
 * Enqueue dashboard assets, including whatever the current page declared.
 *
 * Versioned with filemtime() so a changed file busts its own cache instead of
 * waiting for someone to remember to bump GTI_VERSION (R-08).
 */
function gti_asset_version( $relative_path ) {
    $file = GTI_CHILD_DIR . '/' . ltrim( $relative_path, '/' );

    return file_exists( $file ) ? (string) filemtime( $file ) : GTI_VERSION;
}

/**
 * Register and enqueue one theme stylesheet.
 */
function gti_enqueue_style( $handle, $relative_path, $deps = array() ) {
    if ( ! file_exists( GTI_CHILD_DIR . '/' . ltrim( $relative_path, '/' ) ) ) {
        return false;
    }

    wp_enqueue_style( $handle, GTI_CHILD_URL . '/' . ltrim( $relative_path, '/' ), $deps, gti_asset_version( $relative_path ) );

    return true;
}

/**
 * Register and enqueue one theme script.
 */
function gti_enqueue_script( $handle, $relative_path, $deps = array(), $in_footer = true ) {
    if ( ! file_exists( GTI_CHILD_DIR . '/' . ltrim( $relative_path, '/' ) ) ) {
        return false;
    }

    wp_enqueue_script( $handle, GTI_CHILD_URL . '/' . ltrim( $relative_path, '/' ), $deps, gti_asset_version( $relative_path ), $in_footer );

    return true;
}
