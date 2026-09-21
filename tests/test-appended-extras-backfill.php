<?php
/**
 * Guards the parser inside GTI_Activator::backfill_appended_extras() — the rule
 * that only splits the trailing paragraph when *every* line matches a known
 * label, so free text like "Durasi: 3 bulan" survives the migration.
 *
 * Run: php tests/test-appended-extras-backfill.php
 */

$labels = array(
    'Merk (detail)' => null, // dropped field: matched, then discarded
    'Tahun (min)'   => 'year_min',
    'Tahun (max)'   => 'year_max',
    'Kondisi'       => 'equipment_condition',
    'Durasi'        => 'duration',
);

/** Mirror of the loop body in the activator. Returns null when the text is left alone. */
function parse_extras($text, array $labels) {
    $chunks = explode("\n\n", (string) $text);
    if (count($chunks) < 2) {
        return null;
    }

    $update = array();

    foreach (preg_split('/\R/', trim(end($chunks))) as $line) {
        $pair = explode(': ', $line, 2);
        if (count($pair) !== 2 || !array_key_exists($pair[0], $labels)) {
            return null;
        }
        if ($labels[$pair[0]] !== null) {
            $update[$labels[$pair[0]]] = $pair[1];
        }
    }

    array_pop($chunks);
    $update['text'] = trim(implode("\n\n", $chunks));

    return $update;
}

$cases = array(
    'live row 28' => array(
        "22222\n\nMerk (detail): Indomacha\nTahun (min): 2020\nTahun (max): 2021\nKondisi: Sangat Bagus\nDurasi: 1",
        array('year_min' => '2020', 'year_max' => '2021',
              'equipment_condition' => 'Sangat Bagus', 'duration' => '1', 'text' => '22222'),
    ),
    'sell row: one label after free text' => array(
        "f4efwfe\n\nKetersediaan: Tersedia",
        array('availability' => 'Tersedia', 'text' => 'f4efwfe'),
    ),
    'whole note is label-shaped: left alone' => array("Durasi: 3 bulan ya pak, tolong", null),
    'block with no free text in front: left alone' => array("Kondisi: Bagus\nDurasi: 6 bulan", null),
    'stray line inside the block' => array(
        "catatan\n\nKondisi: Bagus\nbutuh cepat", null,
    ),
    'plain free text' => array("Tolong kirim penawaran", null),
    'multi-paragraph notes, real block at the end' => array(
        "baris satu\n\nbaris dua\n\nDurasi: 2",
        array('duration' => '2', 'text' => "baris satu\n\nbaris dua"),
    ),
    'empty' => array('', null),
);

$failed = 0;
foreach ($cases as $name => $case) {
    list($input, $expected) = $case;
    $actual = parse_extras($input, $labels + array('Ketersediaan' => 'availability'));
    if ($actual !== $expected) {
        $failed++;
        echo "FAIL  {$name}\n  expected: " . var_export($expected, true) . "\n  actual:   " . var_export($actual, true) . "\n";
    } else {
        echo "ok    {$name}\n";
    }
}

echo $failed ? "\n{$failed} failed\n" : "\nall passed\n";
exit($failed ? 1 : 0);
