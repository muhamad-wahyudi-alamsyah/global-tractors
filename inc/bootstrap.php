<?php
/**
 * Bootstrap — the single place modules are loaded.
 *
 * Before this file was wired up, functions.php required a hand-picked subset of
 * inc/ directly and inc/bootstrap.php was never included at all. The effect was
 * that includes/class-gti-activator.php (schema + migrations),
 * includes/class-gti-roles.php (every gti_* capability) and
 * inc/ajax/ajax-email-notifications.php never ran, even though the rest of the
 * code assumed they did. Keep additions here, in order, and nowhere else.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Helpers (must come first — everything below calls into these) ────────────
require_once GTI_CHILD_DIR . '/inc/helpers/url-helpers.php';
require_once GTI_CHILD_DIR . '/inc/helpers/format-helpers.php';
require_once GTI_CHILD_DIR . '/inc/helpers/log-helpers.php';
require_once GTI_CHILD_DIR . '/inc/helpers/template-loader.php';
require_once GTI_CHILD_DIR . '/inc/helpers/equipment-taxonomy.php';
require_once GTI_CHILD_DIR . '/inc/helpers/spare-parts-taxonomy.php';
require_once GTI_CHILD_DIR . '/inc/helpers/dashboard-menu.php';
require_once GTI_CHILD_DIR . '/inc/helpers/layout-helpers.php';
require_once GTI_CHILD_DIR . '/inc/helpers/render-helpers.php';

// ── Security ─────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/security/sanitize.php';
require_once GTI_CHILD_DIR . '/inc/security/nonce.php';
require_once GTI_CHILD_DIR . '/inc/security/rate-limit.php';
require_once GTI_CHILD_DIR . '/inc/security/capabilities.php';
require_once GTI_CHILD_DIR . '/inc/security/ajax-guard.php';

// ── Schema, roles & migrations ───────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/includes/class-gti-roles.php';
require_once GTI_CHILD_DIR . '/includes/class-gti-activator.php';
require_once GTI_CHILD_DIR . '/inc/db/migrations.php';

// ── User ─────────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/user/user-helpers.php';
require_once GTI_CHILD_DIR . '/inc/user/user-meta.php';

// ── Data ─────────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/db/activity-log.php';
require_once GTI_CHILD_DIR . '/inc/db/customers-sync.php';
require_once GTI_CHILD_DIR . '/inc/db/repository.php';

// ── Domain modules ───────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/modules/status-machine.php';
require_once GTI_CHILD_DIR . '/inc/modules/attachments.php';
require_once GTI_CHILD_DIR . '/inc/modules/mailer.php';
require_once GTI_CHILD_DIR . '/inc/modules/assignment.php';
require_once GTI_CHILD_DIR . '/inc/modules/news-articles.php';
require_once GTI_CHILD_DIR . '/inc/modules/media-library.php';
require_once GTI_CHILD_DIR . '/inc/modules/fluentform-intake.php';

// ── Setup ────────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/setup/rewrite.php';
require_once GTI_CHILD_DIR . '/inc/setup/enqueue.php';
require_once GTI_CHILD_DIR . '/inc/setup/install.php';

// ── Shortcodes ───────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/shortcodes/search-card.php';
require_once GTI_CHILD_DIR . '/inc/shortcodes/inquiry-form.php';
require_once GTI_CHILD_DIR . '/inc/shortcodes/equipment-filter-used-equipment.php';
require_once GTI_CHILD_DIR . '/inc/shortcodes/equipment-filter-rental-equipment.php';
require_once GTI_CHILD_DIR . '/inc/shortcodes/equipment-filter-spare-parts.php';

// ── AJAX ─────────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-helpers.php';
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-equipment-filter.php';
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-customer-quotation.php';
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-dashboard.php';
require_once GTI_CHILD_DIR . '/inc/ajax/admin/inbox.php';
require_once GTI_CHILD_DIR . '/inc/ajax/admin/management.php';
require_once GTI_CHILD_DIR . '/includes/class-gti-ajax.php';

add_action( 'init', function () {
    if ( class_exists( 'GTI_Ajax' ) ) {
        GTI_Ajax::init();
    }
} );
