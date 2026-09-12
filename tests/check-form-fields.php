<?php
/**
 * Duplicate name= detector (PRD §6.4).
 *
 * A field name repeated inside one <form> makes FormData send both values and
 * PHP keep the last — which is how Step 1 of the equipment form silently lost
 * its values (A-02), and how the article Status dropdown was overridden by its
 * own submit buttons.
 *
 * Submit buttons are exempt: only the clicked one is ever sent, so sharing a
 * name between them is deliberate.
 *
 * Usage: php tests/check-form-fields.php [file …]
 */

$files = array_slice($argv, 1);
if (!$files) {
    $files = glob(dirname(__DIR__) . '/templates/*.php');
}

$failures = 0;

foreach ($files as $file) {
    $html = file_get_contents($file);

    // Strip PHP blocks and HTML comments so prose cannot trip the check.
    $html = preg_replace('/<\?php.*?\?>/s', '', $html);
    $html = preg_replace('/<!--.*?-->/s', '', $html);

    // One form at a time; names only collide within a form.
    preg_match_all('/<form\b.*?<\/form>/is', $html, $forms);

    foreach ($forms[0] as $index => $form) {
        preg_match_all('/<(input|select|textarea|button)\b([^>]*)>/i', $form, $tags, PREG_SET_ORDER);

        $seen = array();
        foreach ($tags as $tag) {
            $attrs = $tag[2];

            if (!preg_match('/\bname\s*=\s*["\']([^"\']+)["\']/i', $attrs, $m)) {
                continue;
            }
            $name = $m[1];

            // Arrays are meant to repeat.
            if (substr($name, -2) === '[]') {
                continue;
            }
            // Submit/reset buttons and radios legitimately share a name.
            if (preg_match('/\btype\s*=\s*["\'](submit|reset|radio)["\']/i', $attrs)) {
                continue;
            }
            if (strtolower($tag[1]) === 'button' && !preg_match('/\btype\s*=/i', $attrs)) {
                continue; // defaults to submit
            }

            if (isset($seen[$name])) {
                printf("FAIL %s (form #%d): duplicate name=\"%s\"\n", basename($file), $index + 1, $name);
                $failures++;
            }
            $seen[$name] = true;
        }
    }
}

if ($failures === 0) {
    echo "ok   no duplicate field names\n";
}

exit($failures > 0 ? 1 : 0);
