<?php
/**
 * Format helper functions
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

function gti_format_date($date, $format = 'd M Y, H:i') {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format($format);
}

function gti_format_number($number) {
    return number_format($number, 0, ',', '.');
}

function gti_time_ago($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' tahun lalu';
    if ($diff->m > 0) return $diff->m . ' bulan lalu';
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja';
}

/**
 * Should demo/placeholder catalogue rows be shown?
 *
 * Off by default. The three catalogue shortcodes used to fall back to invented
 * equipment whenever their table came back empty, which on a live site meant
 * visitors browsing units that do not exist (PRD §7.5).
 */
function gti_show_demo_data() {
    return get_option( 'gti_show_demo_data', 'no' ) === 'yes'
        || ( defined( 'GTI_DEMO_DATA' ) && GTI_DEMO_DATA );
}
