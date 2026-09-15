<?php
/**
 * Dashboard closing markup: shared modals, toast host and scripts.
 *
 * gti_dashboard_footer() stands in for wp_footer(); see gti_dashboard_head().
 *
 * @var array $args modals[] — a name, or name => settings for that part
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
        </main>
    </div><!-- /.gti-wrapper -->

    <div class="gti-drawer-backdrop" id="gti-drawer-backdrop"></div>

    <?php
    foreach ( (array) $args['modals'] as $gti_key => $gti_value ) {
        $gti_modal      = is_string( $gti_key ) ? $gti_key : $gti_value;
        $gti_modal_args = is_array( $gti_value ) ? $gti_value : array();

        $gti_part = GTI_CHILD_DIR . '/template-parts/dashboard/modal-' . sanitize_file_name( $gti_modal ) . '.php';
        if ( file_exists( $gti_part ) ) {
            include $gti_part;
        }
    }
    ?>

    <div class="gti-toast-container" id="gti-toast-container"></div>

    <?php gti_dashboard_footer(); ?>
</body>
</html>
