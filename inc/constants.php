<?php
/**
 * Konstanta global child theme Global Tractors Indonesia
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GTI_VERSION',       '2.0.0' );
define( 'GTI_CHILD_DIR',     get_stylesheet_directory() );
define( 'GTI_CHILD_URL',     get_stylesheet_directory_uri() );
define( 'GTI_TEXT_DOMAIN',   'global-tractors' );

// Database
define( 'GTI_DB_VERSION_KEY', 'gti_db_version' );
define( 'GTI_DB_VERSION',     '1.0.0' );

// Custom Role
define( 'GTI_ROLE_CUSTOMER', 'gti_customer' );

// URL base dashboard
define( 'GTI_DASHBOARD_BASE', 'dashboard' );

// FluentForms → Request Equipment sync
// Ganti 0 dengan ID form FluentForms yang digunakan untuk request equipment
define( 'GTI_FLUENTFORM_REQUEST_FORM_ID', 3 );

// FluentForms → Sell Equipment sync
// Ganti 0 dengan ID form FluentForms yang digunakan untuk sell equipment
define( 'GTI_FLUENTFORM_SELL_FORM_ID', 4 );

// Rate limit
define( 'GTI_RL_LOGIN_MAX',       5 );
define( 'GTI_RL_LOGIN_WINDOW',    900 );   // 15 menit
define( 'GTI_RL_REGISTER_MAX',    3 );
define( 'GTI_RL_REGISTER_WINDOW', 3600 );  // 1 jam
define( 'GTI_RL_RESEND_VERIFY_MAX',    3 );
define( 'GTI_RL_RESEND_VERIFY_WINDOW', 3600 );  // 1 jam
