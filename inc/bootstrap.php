<?php
/**
 * Bootstrap — load semua module secara terurut
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ── Helpers (harus pertama) ──────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/helpers/url-helpers.php';
require_once GTI_CHILD_DIR . '/inc/helpers/format-helpers.php';
require_once GTI_CHILD_DIR . '/inc/helpers/template-loader.php';

// ── Security ─────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/security/sanitize.php';
require_once GTI_CHILD_DIR . '/inc/security/nonce.php';
require_once GTI_CHILD_DIR . '/inc/security/rate-limit.php';
require_once GTI_CHILD_DIR . '/inc/security/capabilities.php';

// ── Database ─────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/db/schema.php';
require_once GTI_CHILD_DIR . '/inc/db/queries.php';
require_once GTI_CHILD_DIR . '/inc/db/migrations.php';

// ── User ─────────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/user/user-meta.php';
require_once GTI_CHILD_DIR . '/inc/user/user-helpers.php';
require_once GTI_CHILD_DIR . '/inc/user/user-validation.php';

// ── Auth ─────────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/auth/filters.php';
require_once GTI_CHILD_DIR . '/inc/auth/block-admin.php';
require_once GTI_CHILD_DIR . '/inc/auth/login.php';
require_once GTI_CHILD_DIR . '/inc/auth/register.php';
require_once GTI_CHILD_DIR . '/inc/auth/logout.php';
require_once GTI_CHILD_DIR . '/inc/auth/reset-password.php';

// ── Setup ────────────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/setup/roles.php';
require_once GTI_CHILD_DIR . '/inc/setup/rewrite.php';
require_once GTI_CHILD_DIR . '/inc/setup/enqueue.php';
require_once GTI_CHILD_DIR . '/inc/setup/install.php';

// ── AJAX handlers ────────────────────────────────────────────────────────────
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-helpers.php';
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-login.php';
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-register.php';
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-profile.php';
require_once GTI_CHILD_DIR . '/inc/ajax/ajax-orders.php';
