<?php
/**
 * Guards the equipment add/edit forms against the two ways a filled-in field
 * silently fails to reach the database:
 *
 *   1. The same name= appears twice in one <form>. FormData sends both values and
 *      PHP keeps only the last, so an earlier step's input is overwritten — usually
 *      by the blank duplicate the user never saw.
 *   2. The form submits a field that save_equipment() never reads, so it is dropped.
 *
 * Run: php tests/test-equipment-form-fields.php
 *
 * @package Global_Tractors
 */

$theme = dirname(__DIR__);

// Only the equipment forms. Both pages also carry unrelated markup — the list
// page's GET filter toolbar reuses names like 'category' and 'status' on purpose.
$templates = array(
    'add form'   => array($theme . '/templates/page-add-used-equipment.php', 'gti-ae-form'),
    'edit modal' => array($theme . '/templates/page-used-equipment.php', 'gti-edit-form'),
);
$handler = $theme . '/includes/class-gti-ajax.php';

// Submitted, but never as a plain $_POST field: uploads land in $_FILES, checklist[]
// is a UI-only confirmation, and action/nonce/id are the AJAX envelope.
$not_posted = array('main_image', 'gallery_images', 'video_file', 'checklist', 'action', 'nonce', 'id');

/** Field names inside one <form>, ignoring <script> bodies and array inputs. */
function gti_form_field_names($file, $form_id) {
    $html = file_get_contents($file);
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html);

    $start = strpos($html, $form_id);
    if ($start === false) {
        fwrite(STDERR, "Could not find form '{$form_id}' in {$file}\n");
        exit(1);
    }
    $start = strrpos(substr($html, 0, $start), '<form');
    $html  = substr($html, $start, strpos($html, '</form>', $start) - $start);

    preg_match_all('/\bname="([a-z_]+)"/', $html, $m);
    return $m[1];
}

/** Every field save_equipment() reads, including the JSON-packed spec fields. */
function gti_handled_field_names($file) {
    $php = file_get_contents($file);
    $save = substr($php, strpos($php, 'function save_equipment'));
    $save = substr($save, 0, strpos($save, 'private static function upload_file'));

    preg_match_all('/\$_POST\[\s*[\'"]([a-z_]+)[\'"]\s*\]/', $save, $m);
    $handled = $m[1];

    // $spec_fields entries are read as $_POST[ $f ] inside a loop.
    if (preg_match('/\$spec_fields\s*=\s*\[(.*?)\];/s', $save, $spec)) {
        preg_match_all('/[\'"]([a-z_]+)[\'"]/', $spec[1], $sm);
        $handled = array_merge($handled, $sm[1]);
    }
    // Checkbox groups arrive as arrays.
    preg_match_all('/\$_POST\[\s*[\'"](features|documents)[\'"]\s*\]/', $save, $cb);
    return array_unique(array_merge($handled, $cb[1]));
}

$handled  = gti_handled_field_names($handler);
$failures = array();

foreach ($templates as $label => $info) {
    $names = gti_form_field_names($info[0], $info[1]);

    $seen = array_count_values($names);
    foreach ($seen as $name => $count) {
        if ($count > 1) {
            $failures[] = "{$label}: '{$name}' is defined {$count}x — the last one wins and blanks the others.";
        }
    }

    foreach (array_unique($names) as $name) {
        if (!in_array($name, $not_posted, true) && !in_array($name, $handled, true)) {
            $failures[] = "{$label}: '{$name}' is submitted but save_equipment() never reads it.";
        }
    }
}

if ($failures) {
    echo "FAIL\n" . implode("\n", array_map(fn($f) => "  - {$f}", $failures)) . "\n";
    exit(1);
}

echo "PASS — no duplicate field names, every submitted field is persisted.\n";
