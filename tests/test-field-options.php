<?php
/**
 * Managed equipment dropdowns (inc/helpers/field-options.php): scope filtering,
 * the "Lainnya" -> permanent option path, and stored values the list no longer has.
 *
 * Run: php tests/test-field-options.php
 */
define('ABSPATH', __DIR__);

$GLOBALS['gti_test_options'] = array();
function get_option($k, $d = false) { return $GLOBALS['gti_test_options'][$k] ?? $d; }
function update_option($k, $v) { $GLOBALS['gti_test_options'][$k] = $v; return true; }
function wp_strip_all_tags($s) { return strip_tags($s); }
function esc_attr($s) { return htmlspecialchars($s, ENT_QUOTES); }
function esc_html($s) { return htmlspecialchars($s, ENT_QUOTES); }

require __DIR__ . '/../inc/helpers/field-options.php';

function vals($opts) { return array_column($opts, 'value'); }

// Defaults: shared options in both types, status options split by type.
assert(in_array('KOMATSU', vals(gti_field_options('brand', 'used'))), 'default brand');
assert(in_array('sold', vals(gti_field_options('availability_status', 'used'))), 'sold is used-only');
assert(!in_array('sold', vals(gti_field_options('availability_status', 'rental'))), 'sold hidden for rental');
assert(in_array('rented', vals(gti_field_options('availability_status', 'rental'))), 'rented is rental-only');

// "Lainnya": a new brand becomes a permanent option for the saving type only.
assert(gti_field_remember('brand', 'used', 'Sany') === 'Sany', 'new value is kept as typed');
assert(in_array('Sany', vals(gti_field_options('brand', 'used'))), 'new brand offered for used');
assert(!in_array('Sany', vals(gti_field_options('brand', 'rental'))), 'new brand is not offered for rental yet');

// Same wording, other case → the canonical spelling; no duplicate row.
$before = count(gti_field_config()['brand']['options']);
assert(gti_field_remember('brand', 'used', 'komatsu') === 'KOMATSU', 'case-insensitive match returns canonical');
assert(gti_field_remember('brand', 'used', 'SANY') === 'Sany', 'custom brand is canonical too');
assert(count(gti_field_config()['brand']['options']) === $before, 'no duplicate option');

// Used-only option saved from a rental unit becomes shared instead of duplicated.
assert(gti_field_remember('brand', 'rental', 'sany') === 'Sany', 'reuses used-only option');
assert(in_array('Sany', vals(gti_field_options('brand', 'rental'))), 'now offered for rental');

// Coded field: typed text gets a code, and its label is the typed text.
assert(gti_field_remember('condition_status', 'used', 'Like New') === 'like_new', 'coded value is a slug');
assert(gti_field_option_label('condition_status', 'like_new', 'used') === 'Like New', 'label of new code');
assert(gti_field_remember('condition_status', 'used', 'GOOD') === 'good', 'matches existing code');
assert(gti_field_remember('condition_status', 'used', 'excellent') === 'excellent', 'existing code kept');

// Nothing typed → nothing stored.
assert(gti_field_remember('brand', 'used', 'Lainnya') === '', 'bare Lainnya is empty');
assert(gti_field_remember('brand', 'used', '   ') === '', 'blank is empty');

// Over-long text is clipped to the column.
assert(mb_strlen(gti_field_remember('origin_country', 'used', str_repeat('x', 300))) === 100, 'clipped to VARCHAR(100)');

// A stored value that is no longer an option stays selectable.
$kept = gti_field_options('brand', 'used', 'Legacy Brand');
assert(end($kept)['value'] === 'Legacy Brand', 'unknown current value is appended');
assert(count(gti_field_options('brand', 'used', 'komatsu')) === count(gti_field_options('brand', 'used')), 'known value (any case) is not appended');

// Labels: renamed for one type only.
gti_field_store('brand', array('used' => 'Merek', 'rental' => 'Brand'));
assert(gti_field_label('brand', 'used') === 'Merek' && gti_field_label('brand', 'rental') === 'Brand', 'per-type label');
assert(gti_field_label_or('brand', 'used', 'BRAND', true) === 'MEREK', 'public label follows the rename');
assert(gti_field_label_or('brand', 'rental', 'BRAND', true) === 'BRAND', 'public label keeps its own wording while unrenamed');
assert(gti_field_option_label('price_type', 'sale_and_rental', 'used') === 'Sale & Rental', 'option label lookup');

// Category codes feed the equipment code generator.
assert(gti_field_abbr_map()['Excavator'] === 'EXC', 'category abbreviation');

// The select markup always ends with Lainnya.
ob_start(); gti_field_select('location', 'used', 'required'); $html = ob_get_clean();
assert(strpos($html, '<option value="Lainnya" data-other>Lainnya</option></select>') !== false, 'select ends with Lainnya');
assert(strpos($html, 'name="location" required') !== false, 'attrs are passed through');

echo "field-options OK\n";
