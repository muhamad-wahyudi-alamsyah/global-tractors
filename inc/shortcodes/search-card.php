<?php
/**
 * [gti_search_card] Shortcode — Homepage Search Card
 *
 * Redirects to the Used Equipment or Rental Equipment catalog with the chosen
 * filters in the query string; equipment-filter.js pre-applies them there.
 * Brand, type and year options come from the live gti_equipment rows.
 *
 * Usage:
 *   [gti_search_card]
 *   [gti_search_card used_url="/used-equipment/" rental_url="/rental-equipment/"]
 *   [gti_search_card prices="All Price,Under 500 Juta:0-500000000,Above 500 Juta:500000000-"]
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'gti_search_card', 'gti_render_search_card' );

function gti_render_search_card( $atts = [] ) {
    $defaults = [
        'placeholder' => 'Search Equipment',
        'used_url'    => '/used-equipment/',
        'rental_url'  => '/rental-equipment/',
        // "Label:min-max" in Rupiah; either bound may be empty.
        'prices'      => 'All Price,Under 500 Juta:0-500000000,500 Juta – 1 Miliar:500000000-1000000000,1 Miliar – 2 Miliar:1000000000-2000000000,Above 2 Miliar:2000000000-',
        'btn_text'    => 'SEARCH',
    ];

    $atts = shortcode_atts( $defaults, $atts, 'gti_search_card' );

    $options = gti_search_card_options();

    $prices = [];
    foreach ( array_map( 'trim', explode( ',', $atts['prices'] ) ) as $p ) {
        $parts            = explode( ':', $p, 2 );
        $prices[ isset( $parts[1] ) ? trim( $parts[1] ) : '' ] = trim( $parts[0] );
    }

    $selects = [
        'listing' => [ 'Listing', '150px', [
            home_url( $atts['used_url'] )   => 'Used Equipment',
            home_url( $atts['rental_url'] ) => 'Rental Equipment',
        ] ],
        'brand'   => [ 'Brand', '160px', [ '' => 'All Brand' ] + $options['brands'] ],
        'type'    => [ 'Type', '160px', [ '' => 'All Type' ] + $options['types'] ],
        'year'    => [ 'Year', '140px', [ '' => 'All Year' ] + $options['years'] ],
        'price'   => [ 'Price', '160px', $prices ],
    ];

    ob_start();
    ?>
    <div style="background:#ffffff;border-radius:10px;box-shadow:0 4px 24px rgba(0,0,0,0.10),0 1px 4px rgba(0,0,0,0.04);width:100%;font-family:'Inter',sans-serif;" id="gti-search-card">
        <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;flex-wrap:nowrap;">

            <!-- Search Input -->
            <div style="flex:1;position:relative;min-width:160px;">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:14px;pointer-events:none;"></i>
                <input
                    type="text"
                    id="gti-search-keyword"
                    style="width:100%;height:38px;padding:0 12px 0 36px;border:1.5px solid #e5e7eb;border-radius:8px;background:#ffffff;font-family:'Inter',sans-serif;font-size:13px;color:#374151;box-sizing:border-box;margin:0;"
                    placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>"
                    aria-label="Search Equipment"
                />
            </div>

            <?php foreach ( $selects as $key => $select ) : list( $label, $max_width, $choices ) = $select; ?>
            <div style="position:relative;min-width:110px;max-width:<?php echo esc_attr( $max_width ); ?>;">
                <select id="gti-search-<?php echo esc_attr( $key ); ?>" aria-label="<?php echo esc_attr( $label ); ?>"
                    style="width:100%;height:38px;padding:0 28px 0 10px;border:1.5px solid #e5e7eb;border-radius:8px;background:#ffffff;font-family:'Inter',sans-serif;font-size:12px;font-weight:500;color:#374151;appearance:none;-webkit-appearance:none;cursor:pointer;box-sizing:border-box;margin:0;">
                    <?php foreach ( $choices as $value => $text ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $text ); ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:9px;pointer-events:none;"></i>
            </div>
            <?php endforeach; ?>

            <!-- Search Button -->
            <button type="button" id="gti-search-btn"
                style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:0 24px;height:38px;background:#FFB800;color:#1a1a2e;border:none;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;font-weight:800;letter-spacing:0.04em;cursor:pointer;white-space:nowrap;margin:0;"
            >
                <span><?php echo esc_html( $atts['btn_text'] ); ?></span>
                <i class="fas fa-arrow-right" style="font-size:11px;"></i>
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Brand / type / year choices from the rows the Used and Rental catalogs show,
 * so every option can actually return a result there.
 *
 * @return array [ 'brands' => [value => label], 'types' => [...], 'years' => [...] ]
 */
function gti_search_card_options() {
    global $wpdb;
    $table   = $wpdb->prefix . 'gti_equipment';
    $options = [ 'brands' => [], 'types' => [], 'years' => [] ];

    if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
        return $options;
    }

    // Same visibility rules as gti_get_used_equipment_data() / gti_get_rental_equipment_data().
    $where = "type IN ('used','rental') AND deleted_at IS NULL AND status != 'draft' AND NOT (" . gti_price_expired_sql() . ")";

    $columns = [ 'brands' => 'brand ASC', 'types' => 'category ASC', 'years' => 'year DESC' ];
    foreach ( $columns as $key => $order ) {
        $col    = strtok( $order, ' ' );
        $values = $wpdb->get_col( "SELECT DISTINCT {$col} FROM {$table} WHERE {$where} AND {$col} IS NOT NULL AND {$col} <> '' ORDER BY {$order}" );
        $options[ $key ] = array_combine( $values, $values ) ?: [];
    }

    return $options;
}
