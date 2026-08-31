<?php
/**
 * Custom rewrite rules for /dashboard/*
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

add_action('init', 'gti_add_rewrite_rules');
function gti_add_rewrite_rules() {
    add_rewrite_rule('^dashboard/login/?$', 'index.php?gti_page=login', 'top');
    add_rewrite_rule('^dashboard/profile/?$', 'index.php?gti_page=profile', 'top');
    add_rewrite_rule('^dashboard/orders/?$', 'index.php?gti_page=orders', 'top');
    add_rewrite_rule('^dashboard/used-equipment/?$', 'index.php?gti_page=used-equipment', 'top');
    add_rewrite_rule('^dashboard/used-equipment/add/?$', 'index.php?gti_page=add-equipment', 'top');
    add_rewrite_rule('^dashboard/rental-equipment/?$', 'index.php?gti_page=rental-equipment', 'top');
    add_rewrite_rule('^dashboard/rental-equipment/add/?$', 'index.php?gti_page=add-rental-equipment', 'top');
    add_rewrite_rule('^dashboard/spare-parts/?$', 'index.php?gti_page=spare-parts', 'top');
    add_rewrite_rule('^dashboard/spare-parts/add/?$', 'index.php?gti_page=add-spare-part', 'top');
    add_rewrite_rule('^dashboard/request-equipment/?$', 'index.php?gti_page=request-equipment', 'top');
    add_rewrite_rule('^dashboard/request-quotation/?$', 'index.php?gti_page=request-quotation', 'top');
    add_rewrite_rule('^dashboard/sell-equipment/?$', 'index.php?gti_page=sell-equipment', 'top');
    add_rewrite_rule('^dashboard/contact-messages/?$', 'index.php?gti_page=contact-messages', 'top');
    add_rewrite_rule('^dashboard/customers/?$', 'index.php?gti_page=customers', 'top');
    add_rewrite_rule('^dashboard/customer-detail/?$', 'index.php?gti_page=customer-detail', 'top');
    add_rewrite_rule('^dashboard/news-articles/?$', 'index.php?gti_page=news-articles', 'top');        add_rewrite_rule('^dashboard/news-articles/add/?$', 'index.php?gti_page=add-news-article', 'top');    add_rewrite_rule('^dashboard/media-library/?$', 'index.php?gti_page=media-library', 'top');
    add_rewrite_rule('^dashboard/users/?$', 'index.php?gti_page=users', 'top');
    add_rewrite_rule('^dashboard/website-settings/?$', 'index.php?gti_page=website-settings', 'top');
    add_rewrite_rule('^dashboard/activity-log/?$', 'index.php?gti_page=activity-log', 'top');
    add_rewrite_rule('^dashboard/?$', 'index.php?gti_page=dashboard', 'top');
}

add_filter('query_vars', 'gti_add_query_vars');
function gti_add_query_vars($vars) {
    $vars[] = 'gti_page';
    return $vars;
}

add_action('template_redirect', 'gti_handle_dashboard_routing');
function gti_handle_dashboard_routing() {
    $page = get_query_var('gti_page');
    if (!$page) return;

    $map = [
        'login' => 'page-login',
        'profile' => 'page-profile',
        'orders' => 'page-orders',
        'dashboard' => 'page-dashboard',
        'used-equipment' => 'page-used-equipment',
        'add-equipment' => 'page-add-used-equipment',
        'rental-equipment' => 'page-rental-equipment',
        'add-rental-equipment' => 'page-add-rental-equipment',
        'spare-parts' => 'page-spare-parts',
        'add-spare-part' => 'page-add-spare-part',
        'request-equipment' => 'page-request-equipment',
        'request-quotation' => 'page-request-quotation',
        'sell-equipment' => 'page-sell-equipment',
        'contact-messages' => 'page-coming-soon',
        'customers' => 'page-customers',
        'customer-detail' => 'page-customer-detail',
        'news-articles' => 'page-news-articles',
        'add-news-article' => 'page-add-news-article',
        'media-library' => 'page-media-library',
        'users' => 'page-users',
        'website-settings' => 'page-coming-soon',
        'activity-log' => 'page-activity-log',
    ];

    if (!isset($map[$page])) return;

    $public_pages = ['login'];

    if (!in_array($page, $public_pages, true)) {
        gti_require_login();
    }

    if (in_array($page, $public_pages, true)) {
        gti_redirect_if_logged_in();
    }

    gti_get_template($map[$page]);
    exit;
}

function gti_flush_rewrite_rules() {
    gti_add_rewrite_rules();
    flush_rewrite_rules();
}
