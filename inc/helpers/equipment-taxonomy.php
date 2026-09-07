<?php
/**
 * Shared vocabulary for equipment categories, plus the builder the public
 * catalog sidebars use to turn stored values into filter checkboxes.
 *
 * The filter panels compare a checkbox value against the card's data attribute,
 * which carries the value stored in the database. Hard-coding slugs there means
 * a category the dashboard offers ("Compactor") is unreachable and slugs the
 * dashboard never writes ("forklift", "others") match nothing at all, so the
 * options are always derived from real rows instead.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Canonical equipment categories, keyed by the exact string the dashboard
 * add/edit forms store in wp_gti_equipment.category.
 */
function gti_equipment_categories() {
    return array(
        'Excavator'    => 'fas fa-dumpster',
        'Bulldozer'    => 'fas fa-tractor',
        'Wheel Loader' => 'fas fa-truck-monster',
        'Dump Truck'   => 'fas fa-truck',
        'Motor Grader' => 'fas fa-road',
        'Crane'        => 'fas fa-people-carry',
        'Compactor'    => 'fas fa-circle',
    );
}

/**
 * Build category filter options from the rows a catalog is about to render.
 *
 * Canonical categories come first in dashboard order, then anything else the
 * rows actually contain. Categories with no rows are dropped — a checkbox that
 * can only ever return zero results is worse than no checkbox.
 *
 * @param array $data      Rows, each with a 'category' key.
 * @param array $canonical Map of canonical category name => icon class.
 * @return array List of [ 'value', 'name', 'icon', 'count' ].
 */
function gti_catalog_category_options( $data, $canonical ) {
    $counts = array();
    foreach ( $data as $item ) {
        $cat = trim( (string) ( isset( $item['category'] ) ? $item['category'] : '' ) );
        if ( '' === $cat ) {
            continue;
        }
        $key = strtolower( $cat );
        if ( ! isset( $counts[ $key ] ) ) {
            $counts[ $key ] = array( 'value' => $cat, 'count' => 0 );
        }
        $counts[ $key ]['count']++;
    }

    $result = array();
    foreach ( $canonical as $name => $icon ) {
        $key = strtolower( $name );
        if ( isset( $counts[ $key ] ) ) {
            $result[] = array(
                'value' => $counts[ $key ]['value'],
                'name'  => $name,
                'icon'  => $icon,
                'count' => $counts[ $key ]['count'],
            );
            unset( $counts[ $key ] );
        }
    }

    // Values stored outside the vocabulary still get a row, otherwise those
    // rows would be unreachable from the catalog.
    foreach ( $counts as $leftover ) {
        $result[] = array(
            'value' => $leftover['value'],
            'name'  => ucwords( $leftover['value'] ),
            'icon'  => 'fas fa-cube',
            'count' => $leftover['count'],
        );
    }

    return $result;
}
