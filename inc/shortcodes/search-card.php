<?php
/**
 * [gti_search_card] Shortcode — Homepage Search Card
 *
 * Usage:
 *   [gti_search_card]
 *   [gti_search_card brands="All Brand,Kubota,John Deere,Case,Caterpillar"]
 *   [gti_search_card types="All Type,Traktor,Excavator,Bulldozer,Loader"]
 *   [gti_search_card years="All Year,2024,2023,2022,2021"]
 *   [gti_search_card prices="All Price,Under 500 Juta,500 Juta - 1 Miliar,Above 1 Miliar"]
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode( 'gti_search_card', 'gti_render_search_card' );

function gti_render_search_card( $atts = [] ) {
    $defaults = [
        'placeholder' => 'Search Equipment',
        'brands'      => 'All Brand,Kubota,John Deere,Case,Caterpillar,Volvo,Hitachi',
        'types'       => 'All Type,Traktor,Excavator,Bulldozer,Loader,Backhoe,Grader',
        'years'       => 'All Year,2024,2023,2022,2021,2020',
        'prices'      => 'All Price,Under 500 Juta,500 Juta – 1 Miliar,1 Miliar – 2 Miliar,Above 2 Miliar',
        'btn_text'    => 'SEARCH',
    ];

    $atts = shortcode_atts( $defaults, $atts, 'gti_search_card' );

    $placeholder = esc_attr( $atts['placeholder'] );
    $brands      = array_map( 'trim', explode( ',', $atts['brands'] ) );
    $types       = array_map( 'trim', explode( ',', $atts['types'] ) );
    $years       = array_map( 'trim', explode( ',', $atts['years'] ) );
    $prices      = array_map( 'trim', explode( ',', $atts['prices'] ) );
    $btn_text    = esc_html( $atts['btn_text'] );

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
                    placeholder="<?php echo $placeholder; ?>"
                    aria-label="Search Equipment"
                />
            </div>

            <!-- Brand -->
            <div style="position:relative;min-width:130px;max-width:160px;">
                <select id="gti-search-brand" aria-label="Brand"
                    style="width:100%;height:38px;padding:0 28px 0 10px;border:1.5px solid #e5e7eb;border-radius:8px;background:#ffffff;font-family:'Inter',sans-serif;font-size:12px;font-weight:500;color:#374151;appearance:none;-webkit-appearance:none;cursor:pointer;box-sizing:border-box;margin:0;">
                    <?php foreach ( $brands as $b ) : ?>
                        <option value="<?php echo esc_attr( strtolower( $b ) ); ?>"><?php echo esc_html( $b ); ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:9px;pointer-events:none;"></i>
            </div>

            <!-- Type -->
            <div style="position:relative;min-width:130px;max-width:160px;">
                <select id="gti-search-type" aria-label="Type"
                    style="width:100%;height:38px;padding:0 28px 0 10px;border:1.5px solid #e5e7eb;border-radius:8px;background:#ffffff;font-family:'Inter',sans-serif;font-size:12px;font-weight:500;color:#374151;appearance:none;-webkit-appearance:none;cursor:pointer;box-sizing:border-box;margin:0;">
                    <?php foreach ( $types as $t ) : ?>
                        <option value="<?php echo esc_attr( strtolower( $t ) ); ?>"><?php echo esc_html( $t ); ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:9px;pointer-events:none;"></i>
            </div>

            <!-- Year -->
            <div style="position:relative;min-width:110px;max-width:140px;">
                <select id="gti-search-year" aria-label="Year"
                    style="width:100%;height:38px;padding:0 28px 0 10px;border:1.5px solid #e5e7eb;border-radius:8px;background:#ffffff;font-family:'Inter',sans-serif;font-size:12px;font-weight:500;color:#374151;appearance:none;-webkit-appearance:none;cursor:pointer;box-sizing:border-box;margin:0;">
                    <?php foreach ( $years as $y ) : ?>
                        <option value="<?php echo esc_attr( strtolower( $y ) ); ?>"><?php echo esc_html( $y ); ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:9px;pointer-events:none;"></i>
            </div>

            <!-- Price -->
            <div style="position:relative;min-width:130px;max-width:160px;">
                <select id="gti-search-price" aria-label="Price"
                    style="width:100%;height:38px;padding:0 28px 0 10px;border:1.5px solid #e5e7eb;border-radius:8px;background:#ffffff;font-family:'Inter',sans-serif;font-size:12px;font-weight:500;color:#374151;appearance:none;-webkit-appearance:none;cursor:pointer;box-sizing:border-box;margin:0;">
                    <?php foreach ( $prices as $p ) : ?>
                        <option value="<?php echo esc_attr( strtolower( $p ) ); ?>"><?php echo esc_html( $p ); ?></option>
                    <?php endforeach; ?>
                </select>
                <i class="fas fa-chevron-down" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:9px;pointer-events:none;"></i>
            </div>

            <!-- Search Button -->
            <button type="button" id="gti-search-btn"
                style="flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:0 24px;height:38px;background:#FFB800;color:#1a1a2e;border:none;border-radius:8px;font-family:'Inter',sans-serif;font-size:13px;font-weight:800;letter-spacing:0.04em;cursor:pointer;white-space:nowrap;margin:0;"
            >
                <span><?php echo $btn_text; ?></span>
                <i class="fas fa-arrow-right" style="font-size:11px;"></i>
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
