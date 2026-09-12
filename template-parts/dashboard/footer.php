<?php
/**
 * Dashboard closing markup: shared modals, toast host, and wp_footer().
 *
 * @var array $args modals[], js[]
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
        </main>
    </div><!-- /.gti-wrapper -->

    <div class="gti-drawer-backdrop" id="gti-drawer-backdrop"></div>

    <?php
    foreach ( (array) $args['modals'] as $gti_modal ) {
        $gti_part = GTI_CHILD_DIR . '/template-parts/dashboard/modal-' . sanitize_file_name( $gti_modal ) . '.php';
        if ( file_exists( $gti_part ) ) {
            include $gti_part;
        }
    }
    ?>

    <div class="gti-toast-container" id="gti-toast-container"></div>

    <?php wp_footer(); ?>
</body>
</html>
