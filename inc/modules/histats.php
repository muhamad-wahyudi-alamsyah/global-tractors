<?php
/**
 * Website visitor figures from Histats.
 *
 * The public site carries the Histats tracker in its footer, and Histats has no
 * API: the only machine-readable copy of the numbers is the JSON their Summary
 * page embeds in its own HTML, one `_init_from_JSON("…")` call per chart. The
 * block with "range":"d" holds the daily series (h = hits, v = visits,
 * nv = new visitors) starting at its UNIXTIME; the per-bucket `unixT` array is
 * always zero, so the dates are counted off UNIXTIME instead.
 *
 * Nothing here invents a number. Without a SID, or when the fetch or the parse
 * fails, the caller gets an empty array and the dashboard says so.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Daily visitors for the last $days, newest last.
 *
 * ponytail: scraping their page, because there is no API. If Histats changes
 * the page shape this returns nothing and the card falls back to its empty
 * state — the upgrade path is tracking hits in our own table.
 *
 * @return array List of array( 'date' => 'Y-m-d', 'visits' => int, 'hits' => int ).
 */
function gti_histats_daily( $days = 30 ) {
    $sid = trim( (string) get_option( 'gti_histats_sid', '' ) );
    if ( ! $sid ) {
        return array();
    }

    $key    = 'gti_histats_daily_' . md5( $sid . '|' . (int) $days );
    $cached = get_transient( $key );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    $response = wp_remote_get(
        'https://www.histats.com/viewstats/?sid=' . rawurlencode( $sid ) . '&act=2',
        array( 'timeout' => 8 )
    );

    if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
        // Short cache so a Histats outage is not re-tried on every page view.
        set_transient( $key, array(), 15 * MINUTE_IN_SECONDS );
        return array();
    }

    $rows = gti_histats_parse_daily( wp_remote_retrieve_body( $response ), $days );

    set_transient( $key, $rows, $rows ? 6 * HOUR_IN_SECONDS : 15 * MINUTE_IN_SECONDS );

    return $rows;
}

/**
 * Pull the daily series out of a Histats Summary page.
 *
 * Separate from the fetch so tests/test-histats-parse.php can run it on a
 * fixture without touching the network.
 */
function gti_histats_parse_daily( $html, $days = 30 ) {
    if ( ! preg_match_all( '/_init_from_JSON\("((?:\\\\.|[^"\\\\])*)"\)/', (string) $html, $matches ) ) {
        return array();
    }

    $best = null;

    foreach ( $matches[1] as $escaped ) {
        $block = json_decode( stripslashes( $escaped ), true );

        if ( ! is_array( $block )
            || ( $block['range'] ?? '' ) !== 'd'
            || empty( $block['UNIXTIME'] )
            || ! isset( $block['AR_segments']['h'] ) || ! is_array( $block['AR_segments']['h'] ) ) {
            continue;
        }

        // Several daily blocks are printed — this month, last month, and one
        // spanning both. The longest is the widest window.
        if ( null === $best || count( $block['AR_segments']['h'] ) > count( $best['AR_segments']['h'] ) ) {
            $best = $block;
        }
    }

    if ( null === $best ) {
        return array();
    }

    $hits   = array_values( $best['AR_segments']['h'] );
    $visits = isset( $best['AR_segments']['v'] ) && is_array( $best['AR_segments']['v'] )
        ? array_values( $best['AR_segments']['v'] )
        : $hits;

    $start = (int) $best['UNIXTIME'];
    $today = (int) ( current_time( 'timestamp' ) / DAY_IN_SECONDS );
    $rows  = array();

    foreach ( $hits as $i => $hit ) {
        $stamp = $start + $i * DAY_IN_SECONDS;

        // The current month's block runs to its last day; days that have not
        // happened yet are zeroes, not quiet days.
        if ( (int) ( $stamp / DAY_IN_SECONDS ) > $today ) {
            break;
        }

        $rows[] = array(
            'date'   => gmdate( 'Y-m-d', $stamp ),
            'visits' => (int) ( $visits[ $i ] ?? 0 ),
            'hits'   => (int) $hit,
        );
    }

    $days = max( 1, (int) $days );

    return count( $rows ) > $days ? array_slice( $rows, -$days ) : $rows;
}
