<?php
/**
 * Histats Summary page parser.
 *
 * Histats has no API, so the visitor chart reads the JSON their page embeds.
 * This pins the shape we depend on: several `_init_from_JSON("…")` blocks, the
 * daily one flagged "range":"d", dates counted off UNIXTIME because the
 * per-bucket `unixT` values are always zero, and days that have not happened
 * yet dropped instead of plotted as zero visitors.
 *
 * Usage: php tests/test-histats-parse.php
 */

define('ABSPATH', __DIR__);
define('DAY_IN_SECONDS', 86400);

// 15 Jan 2026, 00:00 UTC — "today" for this run.
define('GTI_TEST_NOW', 1768435200);
function current_time($type) { return GTI_TEST_NOW; }

require __DIR__ . '/../inc/modules/histats.php';

/**
 * One Histats page: an hourly block, then a daily block of $days days starting
 * at $start, escaped the way their HTML escapes it.
 */
function gti_test_histats_page($start, array $visits, array $hits) {
    $zeros = array_fill(0, 24, 0);
    $hourly = array(
        'id_s' => '123', 'UNIXTIME' => $start, 'range' => 'h',
        'AR_segments' => array('h' => $zeros, 'v' => $zeros, 'unixT' => $zeros),
    );
    $daily = array(
        'id_s' => '123', 'UNIXTIME' => $start, 'range' => 'd',
        'AR_segments' => array(
            'h'     => $hits,
            'v'     => $visits,
            'unixT' => array_fill(0, count($hits), 0),
        ),
    );

    $out = '<html><script>';
    foreach (array($hourly, $daily) as $block) {
        $out .= 'x._init_from_JSON("' . addslashes(json_encode($block)) . '");';
    }

    return $out . '</script></html>';
}

// 1 Jan 2026 + 31 days: the second half of the month has not happened yet.
$start  = 1767225600;
$visits = array();
$hits   = array();
for ($i = 0; $i < 31; $i++) {
    $visits[] = $i < 15 ? $i + 1 : 0;
    $hits[]   = $i < 15 ? ($i + 1) * 3 : 0;
}

$rows = gti_histats_parse_daily(gti_test_histats_page($start, $visits, $hits), 30);

assert(count($rows) === 15, 'future days dropped, got ' . count($rows));
assert($rows[0]['date'] === '2026-01-01', 'first date ' . $rows[0]['date']);
assert($rows[14]['date'] === '2026-01-15', 'last date ' . $rows[14]['date']);
assert($rows[14]['visits'] === 15, 'last visits ' . $rows[14]['visits']);
assert($rows[14]['hits'] === 45, 'last hits ' . $rows[14]['hits']);
echo "daily series OK\n";

// A 61-day window ending today, asked for 30: the newest 30 come back.
$start_long = 1768435200 - 60 * DAY_IN_SECONDS;
$long       = range(1, 61);
$rows       = gti_histats_parse_daily(gti_test_histats_page($start_long, $long, $long), 30);

assert(count($rows) === 30, 'window trimmed to 30, got ' . count($rows));
assert($rows[29]['date'] === '2026-01-15', 'newest kept, got ' . $rows[29]['date']);
assert($rows[0]['visits'] === 32, 'oldest of the 30 is day 32, got ' . $rows[0]['visits']);
echo "30-day window OK\n";

// Nothing usable must produce nothing — never a chart of invented numbers.
foreach (array('', '<html>no stats here</html>', 'x._init_from_JSON("{not json}");') as $junk) {
    assert(gti_histats_parse_daily($junk, 30) === array(), 'junk input: ' . substr($junk, 0, 20));
}
// An hourly-only page (a site with no daily data yet) is not a daily series.
$hourly_only = 'x._init_from_JSON("' . addslashes(json_encode(array(
    'UNIXTIME' => $start, 'range' => 'h',
    'AR_segments' => array('h' => array_fill(0, 24, 5)),
))) . '");';
assert(gti_histats_parse_daily($hourly_only, 30) === array(), 'hourly-only page');
echo "empty inputs OK\n";
