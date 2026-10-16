<?php
/**
 * Admin-managed dropdown fields of the Used / Rental equipment forms.
 *
 * Each managed field has a label per equipment type and a list of options, each
 * option scoped to used, rental or both. Defaults live here; whatever the super
 * admin saves in Settings (or adds through "Lainnya") is kept in the option
 * `gti_field_config` and wins over the defaults.
 *
 * Two kinds of field:
 *   text  — the stored value IS the label (brand, location, ...). Renaming an
 *           option also renames it on existing units.
 *   coded — the stored value is a code (condition_status 'excellent'); only the
 *           label is editable, so literals elsewhere in the theme keep working.
 *
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

const GTI_FIELD_CONFIG_OPTION = 'gti_field_config';
const GTI_FIELD_OTHER_VALUE   = 'Lainnya';

/** Option rows from a list of values, sharing one scope. */
function gti_field_opts(array $items, $scope = 'both') {
    $out = array();
    foreach ($items as $value => $label) {
        if (is_int($value)) { $value = $label; }
        $out[] = array('value' => (string) $value, 'label' => (string) $label, 'scope' => $scope);
    }
    return $out;
}

/** Field definitions and default options. Column name == field key. */
function gti_field_registry() {
    static $r = null;
    if ($r !== null) return $r;

    $condition = array('excellent' => 'Excellent', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor');
    $countries = array('Indonesia', 'Japan', 'South Korea', 'China', 'USA', 'Germany');
    $cities    = array('Jakarta', 'Bandung', 'Surabaya', 'Balikpapan', 'Samarinda', 'Makassar', 'Medan');

    $categories = array();
    foreach (array('Excavator' => 'EXC', 'Bulldozer' => 'BLD', 'Wheel Loader' => 'WLD', 'Dump Truck' => 'DMP',
                   'Motor Grader' => 'MGR', 'Crane' => 'CRN', 'Compactor' => 'CMP') as $name => $abbr) {
        $categories[] = array('value' => $name, 'label' => $name, 'scope' => 'both', 'abbr' => $abbr);
    }

    $r = array(
        'category' => array('label' => 'Category', 'placeholder' => 'Select Category', 'kind' => 'text', 'len' => 100,
            'abbr' => true, 'options' => $categories),
        'brand' => array('label' => 'Brand', 'placeholder' => 'Select Brand', 'kind' => 'text', 'len' => 100,
            'options' => gti_field_opts(array('KOMATSU', 'CATERPILLAR', 'HITACHI', 'VOLVO', 'KOBELCO', 'DOOSAN', 'HYUNDAI'))),
        'condition_status' => array('label' => 'Condition', 'placeholder' => 'Select Condition', 'kind' => 'coded', 'len' => 50,
            'options' => gti_field_opts($condition)),
        'origin_country' => array('label' => 'Country of Origin', 'placeholder' => 'Select Origin', 'kind' => 'text', 'len' => 100,
            'options' => gti_field_opts(array('Japan', 'USA', 'South Korea', 'China', 'Germany', 'Sweden'))),
        'location' => array('label' => 'Location', 'placeholder' => 'Select Location', 'kind' => 'text', 'len' => 255,
            'options' => gti_field_opts(array('Jakarta', 'Balikpapan', 'Surabaya', 'Bandung', 'Medan', 'Makassar'))),
        'price_type' => array('label' => 'Price Type', 'placeholder' => '-- Select Price Type --', 'kind' => 'coded', 'len' => 50,
            'options' => gti_field_opts(array('sale' => 'For Sale', 'rental' => 'For Rental', 'sale_and_rental' => 'Sale & Rental', 'price_on_ask' => 'Price on Ask'))),
        'availability_status' => array('label' => 'Availability Status', 'placeholder' => '-- Select Status --', 'kind' => 'coded', 'len' => 50,
            'options' => array_merge(
                gti_field_opts(array('available' => 'Available', 'reserved' => 'Reserved')),
                gti_field_opts(array('sold' => 'Sold'), 'used'),
                gti_field_opts(array('rented' => 'Rented'), 'rental'),
                gti_field_opts(array('in_transit' => 'In Transit', 'under_maintenance' => 'Under Maintenance')))),
        'ready_to_use' => array('label' => 'Ready to Use', 'placeholder' => '-- Select --', 'kind' => 'coded', 'len' => 20,
            'options' => gti_field_opts(array('yes' => 'Yes', 'no' => 'No', 'after_service' => 'After Service'))),
        'service_history' => array('label' => 'Service History', 'placeholder' => '-- Select --', 'kind' => 'coded', 'len' => 50,
            'options' => gti_field_opts(array('full' => 'Full Service History', 'partial' => 'Partial Service History', 'none' => 'No Service History'))),
        'working_condition' => array('label' => 'Working Condition', 'placeholder' => 'Select Condition', 'kind' => 'coded', 'len' => 50,
            'options' => gti_field_opts($condition)),
        'maintenance_record' => array('label' => 'Maintenance Record', 'placeholder' => 'Select', 'kind' => 'coded', 'len' => 50,
            'options' => gti_field_opts(array('full' => 'Full Record', 'partial' => 'Partial Record', 'none' => 'No Record'))),
        'ownership' => array('label' => 'Ownership', 'placeholder' => 'Select', 'kind' => 'coded', 'len' => 50,
            'options' => gti_field_opts(array('first_owner' => 'First Owner', 'second_owner' => 'Second Owner', 'third_plus' => 'Third Owner or More', 'company' => 'Company Fleet'))),
        'location_country' => array('label' => 'Country', 'placeholder' => 'Select Country', 'kind' => 'text', 'len' => 100,
            'options' => gti_field_opts($countries)),
        'location_province' => array('label' => 'Province', 'placeholder' => 'Select Province', 'kind' => 'text', 'len' => 100,
            'options' => gti_field_opts(array('DKI Jakarta', 'Jawa Barat', 'Jawa Timur', 'Kalimantan Timur', 'Kalimantan Selatan', 'Sulawesi Selatan', 'Sumatera Utara'))),
        'location_city' => array('label' => 'City / Regency', 'placeholder' => 'Select City', 'kind' => 'text', 'len' => 100,
            'options' => gti_field_opts($cities)),
    );

    // Stable ids so Settings can address an option before anything is saved.
    foreach ($r as $f => &$def) {
        foreach ($def['options'] as $i => &$o) { $o['id'] = 'd' . $i; }
        unset($o);
    }
    unset($def);
    return $r;
}

function gti_field_types($scope) {
    return $scope === 'both' ? array('used', 'rental') : array($scope);
}

function gti_field_scope_overlaps($a, $b) {
    return $a === 'both' || $b === 'both' || $a === $b;
}

function gti_field_in_scope($scope, $type) {
    return $type === '' || $scope === 'both' || $scope === $type;
}

/** @param bool $reload Drop the per-request cache (tests, after a save). */
function gti_field_config($reload = false) {
    static $cache = null;
    if ($cache !== null && !$reload) return $cache;

    $saved = get_option(GTI_FIELD_CONFIG_OPTION, array());
    $saved = is_array($saved) ? $saved : array();

    $cache = array();
    foreach (gti_field_registry() as $f => $def) {
        $s = isset($saved[$f]) && is_array($saved[$f]) ? $saved[$f] : array();
        $label = isset($s['label']) && is_array($s['label']) ? $s['label'] : array();
        $cache[$f] = array(
            'label' => array(
                'used'   => !empty($label['used'])   ? (string) $label['used']   : $def['label'],
                'rental' => !empty($label['rental']) ? (string) $label['rental'] : $def['label'],
            ),
            'options' => isset($s['options']) && is_array($s['options']) ? array_values($s['options']) : $def['options'],
        );
    }
    return $cache;
}

/** Persist one field's (label|options) change; null leaves that part as is. */
function gti_field_store($field, $label = null, $options = null) {
    $saved = get_option(GTI_FIELD_CONFIG_OPTION, array());
    $saved = is_array($saved) ? $saved : array();
    $cfg   = gti_field_config(true);

    $saved[$field] = array(
        'label'   => $label   !== null ? $label   : $cfg[$field]['label'],
        'options' => $options !== null ? array_values($options) : $cfg[$field]['options'],
    );
    update_option(GTI_FIELD_CONFIG_OPTION, $saved, false);
    gti_field_config(true);
}

function gti_field_label($field, $type = 'used') {
    $cfg  = gti_field_config();
    $type = $type === 'rental' ? 'rental' : 'used';
    return isset($cfg[$field]) ? $cfg[$field]['label'][$type] : $field;
}

/**
 * Label for public pages: the page's own wording ($fallback) until the super admin
 * renames the field, then the new name.
 */
function gti_field_label_or($field, $type, $fallback, $upper = false) {
    $reg = gti_field_registry();
    $cur = gti_field_label($field, $type);
    if (!isset($reg[$field]) || $cur === $reg[$field]['label']) return $fallback;
    return $upper ? mb_strtoupper($cur) : $cur;
}

/**
 * Options visible for an equipment type. A stored value the list no longer
 * has ($current) is kept so an old unit never shows a blank select.
 *
 * @return array[] each array('value'=>, 'label'=>)
 */
function gti_field_options($field, $type = '', $current = '') {
    $cfg = gti_field_config();
    $out = array();
    if (!isset($cfg[$field])) return $out;

    foreach ($cfg[$field]['options'] as $o) {
        if (gti_field_in_scope($o['scope'], $type)) {
            $out[] = array('value' => $o['value'], 'label' => $o['label']);
        }
    }
    if ($current !== '' && gti_field_find($out, $current) === null) {
        $out[] = array('value' => $current, 'label' => $current);
    }
    return $out;
}

/** Index of the option whose value or label equals $text (case-insensitive). */
function gti_field_find(array $options, $text) {
    $needle = mb_strtolower(trim((string) $text));
    foreach ($options as $i => $o) {
        if (mb_strtolower($o['value']) === $needle) return $i;
    }
    foreach ($options as $i => $o) {
        if (mb_strtolower($o['label']) === $needle) return $i;
    }
    return null;
}

/** Display label for a stored value; the raw value when no option matches. */
function gti_field_option_label($field, $value, $type = '') {
    if ($value === null || $value === '') return '';
    $opts = gti_field_options($field, $type);
    $i = gti_field_find($opts, $value);
    return $i === null ? (string) $value : $opts[$i]['label'];
}

/** Value → label map for JS (drawers), for one equipment type. */
function gti_field_label_map($type) {
    $map = array();
    foreach (array_keys(gti_field_registry()) as $f) {
        $map[$f] = array('label' => gti_field_label($f, $type), 'options' => array());
        foreach (gti_field_options($f, $type) as $o) {
            $map[$f]['options'][$o['value']] = $o['label'];
        }
    }
    return $map;
}

/** Category value → code prefix (EXC, BLD...) for the auto-generated equipment code. */
function gti_field_abbr_map() {
    $map = array();
    $cfg = gti_field_config();
    foreach ($cfg['category']['options'] as $o) {
        if (!empty($o['abbr'])) $map[$o['value']] = $o['abbr'];
    }
    return $map;
}

/** <option> list + "Lainnya"; only the markup, so callers keep their own <select>. */
function gti_field_select($field, $type, $attrs = '', $current = '') {
    $def = gti_field_registry();
    if (!isset($def[$field])) return;

    echo '<select name="' . esc_attr($field) . '" ' . $attrs . '>';
    echo '<option value="">' . esc_html($def[$field]['placeholder']) . '</option>';
    foreach (gti_field_options($field, $type, $current) as $o) {
        printf('<option value="%s"%s>%s</option>', esc_attr($o['value']),
            $current !== '' && strcasecmp($o['value'], $current) === 0 ? ' selected' : '', esc_html($o['label']));
    }
    echo '<option value="' . GTI_FIELD_OTHER_VALUE . '" data-other>' . GTI_FIELD_OTHER_VALUE . '</option>';
    echo '</select>';
}

/** Code for a new option of a coded field: 'Under Repair' → 'under_repair'. */
function gti_field_slug($text) {
    $slug = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower($text)), '_');
    return $slug !== '' ? $slug : 'x' . substr(md5($text), 0, 8);
}

/** "Lainnya" → stored option. Pure: returns the new options list and canonical value. */
function gti_field_merge_custom(array $options, $def, $type, $text) {
    $text = trim(wp_strip_all_tags((string) $text));
    $text = mb_substr($text, 0, $def['len']);
    if ($text === '' || $text === GTI_FIELD_OTHER_VALUE) return array($options, '');

    // Same wording already offered for this type → reuse its exact value.
    $scope = $type === 'rental' ? 'rental' : 'used';
    foreach ($options as $i => $o) {
        $match = mb_strtolower($o['value']) === mb_strtolower($text) || mb_strtolower($o['label']) === mb_strtolower($text);
        if (!$match) continue;
        if (gti_field_in_scope($o['scope'], $scope)) return array($options, $o['value']);
        // Exists for the other type only: offer it to both.
        $options[$i]['scope'] = 'both';
        return array($options, $o['value']);
    }

    $value = $def['kind'] === 'coded' ? gti_field_slug($text) : $text;
    $options[] = array('id' => 'n' . uniqid(), 'value' => $value, 'label' => $text, 'scope' => $scope);
    return array($options, $value);
}

/**
 * Make a submitted value a permanent option (the "Lainnya" path) and return the
 * canonical value to store. Unknown fields come back untouched.
 */
function gti_field_remember($field, $type, $raw) {
    $reg = gti_field_registry();
    if (!isset($reg[$field])) return $raw;

    $cfg = gti_field_config();
    list($options, $value) = gti_field_merge_custom($cfg[$field]['options'], $reg[$field], $type, $raw);
    if ($options !== $cfg[$field]['options']) {
        gti_field_store($field, null, $options);
    }
    return $value;
}

/** Units using each value of a text field, keyed value => array(used=>n, rental=>n). */
function gti_field_usage($field) {
    global $wpdb;
    $reg = gti_field_registry();
    if (!isset($reg[$field])) return array();

    $table = $wpdb->prefix . 'gti_equipment';
    $rows  = $wpdb->get_results("SELECT `{$field}` AS v, type, COUNT(*) AS n FROM {$table} WHERE deleted_at IS NULL AND `{$field}` <> '' GROUP BY `{$field}`, type");
    $out = array();
    foreach ((array) $rows as $r) {
        $out[mb_strtolower($r->v)][$r->type] = (int) $r->n;
    }
    return $out;
}
