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

// ─────────────────────────────────────────────────────────────────────────────
// Customer display helpers (PRD §6.8 / R-06).
//
// These were defined at global scope inside page-customers.php and
// page-customer-detail.php. Including either template twice — which any
// shortcode or partial render can do — produced a fatal "cannot redeclare".
// function_exists guards plus a single home fix that.
// ─────────────────────────────────────────────────────────────────────────────

if ( ! function_exists( 'gti_fmt_currency' ) ) {
    /** "IDR 1.250.000" */
    function gti_fmt_currency( $amount ) {
        return 'IDR ' . number_format( (float) $amount, 0, ',', '.' );
    }
}

if ( ! function_exists( 'gti_fmt_date' ) ) {
    function gti_fmt_date( $date ) {
        if ( ! $date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00' ) {
            return '-';
        }
        return date_i18n( 'd M Y', strtotime( $date ) );
    }
}

if ( ! function_exists( 'gti_fmt_datetime' ) ) {
    function gti_fmt_datetime( $datetime ) {
        if ( ! $datetime || $datetime === '0000-00-00 00:00:00' ) {
            return '-';
        }
        return date_i18n( 'd M Y, H:i', strtotime( $datetime ) );
    }
}

if ( ! function_exists( 'gti_customer_initials' ) ) {
    /** Up to two initials for the avatar tile. */
    function gti_customer_initials( $name ) {
        $initials = '';
        foreach ( array_slice( explode( ' ', trim( (string) $name ) ), 0, 2 ) as $part ) {
            if ( $part !== '' ) {
                $initials .= mb_strtoupper( mb_substr( $part, 0, 1 ) );
            }
        }
        return $initials ?: '?';
    }
}

if ( ! function_exists( 'gti_value_or' ) ) {
    function gti_value_or( $value, $fallback = '—' ) {
        $value = trim( (string) $value );
        return $value !== '' ? $value : $fallback;
    }
}

// Aliases kept so the customer-detail template's existing markup still resolves.
if ( ! function_exists( 'gti_cd_initials' ) ) {
    function gti_cd_initials( $name )        { return gti_customer_initials( $name ); }
    function gti_cd_fmt_date( $date )        { return gti_fmt_date( $date ); }
    function gti_cd_fmt_datetime( $value )   { return gti_fmt_datetime( $value ); }
    function gti_cd_fmt_currency( $amount )  { return gti_fmt_currency( $amount ); }
    function gti_cd_val( $value, $fb = '—' ) { return gti_value_or( $value, $fb ); }

    function gti_cd_status_label( $status ) {
        // Route through the status machine so a label never disagrees with the
        // one the inbox pages show for the same key (PRD §5.1).
        foreach ( array( 'request', 'quotation', 'sell' ) as $entity ) {
            $map = gti_status_map( $entity );
            if ( isset( $map[ $status ] ) ) {
                return $map[ $status ]['label'];
            }
        }
        return ucwords( str_replace( '_', ' ', (string) $status ) );
    }

    function gti_cd_status_class( $status ) {
        foreach ( array( 'request', 'quotation', 'sell' ) as $entity ) {
            $map = gti_status_map( $entity );
            if ( isset( $map[ $status ] ) ) {
                return $map[ $status ]['class'];
            }
        }
        return sanitize_html_class( str_replace( '_', '-', (string) $status ) );
    }
}

if ( ! function_exists( 'gti_is_price_valid' ) ) {
    /**
     * Is a listed price still within its validity window?
     *
     * Defined in four places before this (two templates and two shortcodes),
     * one of them unguarded — a fatal waiting for the load order to change.
     */
    function gti_is_price_valid( $price_valid_until ) {
        if ( empty( $price_valid_until ) || $price_valid_until === '0000-00-00' ) {
            return true;
        }
        return strtotime( $price_valid_until ) >= strtotime( current_time( 'Y-m-d' ) );
    }
}
