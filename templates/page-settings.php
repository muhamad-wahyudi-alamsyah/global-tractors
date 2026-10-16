<?php
/**
 * Template: Settings (/dashboard/settings) — equipment form fields.
 * Super admin only. Rules and storage: inc/helpers/field-options.php.
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

$gti_groups = array(
    'Equipment Info'  => array('category', 'brand', 'condition_status', 'origin_country'),
    'Location'        => array('location', 'location_country', 'location_province', 'location_city'),
    'Pricing & Status' => array('price_type', 'availability_status', 'ready_to_use'),
    'History'         => array('service_history', 'working_condition', 'maintenance_record', 'ownership'),
);
$gti_registry = gti_field_registry();
$gti_config   = gti_field_config();
$gti_scope_label = array('both' => 'Used & Rental', 'used' => 'Used', 'rental' => 'Rental');

gti_dashboard_open( array(
    'page'     => 'settings',
    'title'    => 'Settings',
    'subtitle' => 'Kelola nama field dan pilihan dropdown di form Used & Rental Equipment',
    'cap'      => 'gti_manage_settings',
    'css'      => array( 'dashboard-modal' ),
    'js'       => array( 'settings' ),
) );
?>

            <div class="gti-content">
                <p class="gti-st-note">
                    <i class="fas fa-info-circle"></i>
                    Pilihan juga bertambah otomatis saat admin memilih <strong>Lainnya</strong> di form equipment.
                    Scope <strong>Used &amp; Rental</strong> membuat perubahan berlaku di kedua form sekaligus.
                </p>

                <?php foreach ($gti_groups as $gti_group => $gti_fields) : ?>
                    <h2 class="gti-st-group"><?php echo esc_html($gti_group); ?></h2>
                    <div class="gti-st-grid">
                    <?php foreach ($gti_fields as $f) :
                        $def    = $gti_registry[$f];
                        $labels = $gti_config[$f]['label'];
                        $usage  = gti_field_usage($f);
                    ?>
                        <section class="gti-st-card" data-field="<?php echo esc_attr($f); ?>">
                            <header class="gti-st-card-head">
                                <div>
                                    <h3>
                                        <?php if ($labels['used'] === $labels['rental']) : ?>
                                            <?php echo esc_html($labels['used']); ?>
                                        <?php else : ?>
                                            <?php echo esc_html($labels['used']); ?> <small>(Used)</small> /
                                            <?php echo esc_html($labels['rental']); ?> <small>(Rental)</small>
                                        <?php endif; ?>
                                    </h3>
                                    <code><?php echo esc_html($f); ?></code>
                                </div>
                                <button type="button" class="gti-st-btn" data-st-rename
                                        data-field="<?php echo esc_attr($f); ?>"
                                        data-used="<?php echo esc_attr($labels['used']); ?>"
                                        data-rental="<?php echo esc_attr($labels['rental']); ?>">
                                    <i class="fas fa-pen"></i> Rename
                                </button>
                            </header>

                            <ul class="gti-st-options">
                                <?php foreach ($gti_config[$f]['options'] as $o) :
                                    $count = 0;
                                    foreach (gti_field_types($o['scope']) as $t) {
                                        $count += $usage[mb_strtolower($o['value'])][$t] ?? 0;
                                    }
                                ?>
                                    <li>
                                        <span class="gti-st-opt-label">
                                            <?php echo esc_html($o['label']); ?>
                                            <?php if (!empty($o['abbr'])) : ?><code><?php echo esc_html($o['abbr']); ?></code><?php endif; ?>
                                        </span>
                                        <span class="gti-st-scope gti-st-scope-<?php echo esc_attr($o['scope']); ?>"><?php echo esc_html($gti_scope_label[$o['scope']]); ?></span>
                                        <span class="gti-st-count"><?php echo (int) $count; ?> unit</span>
                                        <button type="button" class="gti-st-icon" data-st-edit title="Edit"
                                                data-field="<?php echo esc_attr($f); ?>"
                                                data-id="<?php echo esc_attr($o['id']); ?>"
                                                data-label="<?php echo esc_attr($o['label']); ?>"
                                                data-scope="<?php echo esc_attr($o['scope']); ?>"
                                                data-abbr="<?php echo esc_attr($o['abbr'] ?? ''); ?>"
                                                data-abbr-on="<?php echo !empty($def['abbr']) ? '1' : ''; ?>"><i class="fas fa-pen"></i></button>
                                        <button type="button" class="gti-st-icon danger" data-st-delete title="Delete"
                                                data-field="<?php echo esc_attr($f); ?>"
                                                data-id="<?php echo esc_attr($o['id']); ?>"
                                                data-label="<?php echo esc_attr($o['label']); ?>"
                                                data-count="<?php echo (int) $count; ?>"><i class="fas fa-trash"></i></button>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (!$gti_config[$f]['options']) : ?>
                                    <li class="gti-st-empty">Belum ada pilihan.</li>
                                <?php endif; ?>
                            </ul>

                            <button type="button" class="gti-st-add" data-st-add
                                    data-field="<?php echo esc_attr($f); ?>"
                                    data-abbr-on="<?php echo !empty($def['abbr']) ? '1' : ''; ?>">
                                <i class="fas fa-plus"></i> Add option
                            </button>
                        </section>
                    <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

<?php
gti_dashboard_close( array(
    'modals' => array(
        'delete' => array(
            'title'   => 'Delete Option',
            'icon'    => 'fa-list',
            'warning' => 'Opsi dihapus dari dropdown.',
        ),
        'settings',
    ),
) );
