<?php
/**
 * Shared vocabulary for spare parts — categories and brands.
 *
 * The dashboard add form, the dashboard edit modal, the part-number generator
 * and the public catalog filter all read from here. Without a single source a
 * category saved from one screen is not selectable on another, and the public
 * filter checkbox never matches the value stored in the database.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Canonical spare part categories.
 *
 * Keyed by the exact string stored in wp_gti_spare_parts.category — that value
 * is what the public filter compares against, so it must not be re-slugged.
 * 'abbr' feeds the SP-<abbr>-<year>-<seq> part number generator.
 */
function gti_spare_part_categories() {
    return array(
        'Filter'        => array( 'abbr' => 'FLT', 'icon' => 'fas fa-filter' ),
        'Belt'          => array( 'abbr' => 'BLT', 'icon' => 'fas fa-link' ),
        'Brake'         => array( 'abbr' => 'BRK', 'icon' => 'fas fa-record-vinyl' ),
        'Engine'        => array( 'abbr' => 'ENG', 'icon' => 'fas fa-gears' ),
        'Hydraulic'     => array( 'abbr' => 'HYD', 'icon' => 'fas fa-water' ),
        'Seal'          => array( 'abbr' => 'SEL', 'icon' => 'fas fa-circle-notch' ),
        'Undercarriage' => array( 'abbr' => 'UND', 'icon' => 'fas fa-cogs' ),
        'Cooling'       => array( 'abbr' => 'CLG', 'icon' => 'fas fa-snowflake' ),
        'Electrical'    => array( 'abbr' => 'ELT', 'icon' => 'fas fa-bolt' ),
        'Other'         => array( 'abbr' => 'OTH', 'icon' => 'fas fa-ellipsis-h' ),
    );
}

/**
 * Category names in display order.
 */
function gti_spare_part_category_names() {
    return array_keys( gti_spare_part_categories() );
}

/**
 * Part-number abbreviation for a category, matched case-insensitively.
 */
function gti_spare_part_category_abbr( $category ) {
    $category = trim( (string) $category );
    foreach ( gti_spare_part_categories() as $name => $meta ) {
        if ( strcasecmp( $name, $category ) === 0 ) {
            return $meta['abbr'];
        }
    }
    return 'GEN';
}

/**
 * Font Awesome icon for a category, matched case-insensitively.
 */
function gti_spare_part_category_icon( $category ) {
    $category = trim( (string) $category );
    foreach ( gti_spare_part_categories() as $name => $meta ) {
        if ( strcasecmp( $name, $category ) === 0 ) {
            return $meta['icon'];
        }
    }
    return 'fas fa-cog';
}

/**
 * Stock level derived from the quantities.
 *
 * Saving, the dashboard badge, the catalog card and the catalog filters all go
 * through this, so a part can never be labelled "In Stock" while holding none.
 */
function gti_spare_stock_status( $stock, $min_stock ) {
    $stock     = (int) $stock;
    $min_stock = (int) $min_stock;
    if ( $stock <= 0 ) {
        return 'out_of_stock';
    }
    if ( $stock <= $min_stock ) {
        return 'low_stock';
    }
    return 'in_stock';
}

/**
 * Canonical spare part brands.
 */
function gti_spare_part_brands() {
    return array(
        'KOMATSU',
        'CATERPILLAR',
        'HITACHI',
        'VOLVO',
        'KOBELCO',
        'DOOSAN',
        'HYUNDAI',
        'OTHER',
    );
}
