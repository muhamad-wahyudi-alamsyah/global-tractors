<?php
/**
 * AJAX Handler — Equipment Filter (Server-Side Filtering)
 *
 * Provides AJAX endpoint for filtering, sorting, and paginating equipment.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp_ajax_gti_filter_equipment', 'gti_ajax_filter_equipment' );
add_action( 'wp_ajax_nopriv_gti_filter_equipment', 'gti_ajax_filter_equipment' );

function gti_ajax_filter_equipment() {
    check_ajax_referer( 'gti_equipment_filter', 'nonce' );

    global $wpdb;
    $table = $wpdb->prefix . 'gti_equipment';

    $per_page   = absint( $_POST['per_page'] ?? 12 );
    $page       = max( 1, absint( $_POST['page'] ?? 1 ) );
    $sort_by    = sanitize_text_field( wp_unslash( $_POST['sort_by'] ?? 'newest' ) );

    // Filter params
    $categories = array_map( 'sanitize_text_field', wp_unslash( $_POST['categories'] ?? [] ) );
    $brands     = array_map( 'sanitize_text_field', wp_unslash( $_POST['brands'] ?? [] ) );
    $types      = array_map( 'sanitize_text_field', wp_unslash( $_POST['types'] ?? [] ) );
    $locations  = array_map( 'sanitize_text_field', wp_unslash( $_POST['locations'] ?? [] ) );
    $conditions = array_map( 'sanitize_text_field', wp_unslash( $_POST['condition'] ?? [] ) );
    $min_year   = absint( $_POST['min_year'] ?? 0 );
    $max_year   = absint( $_POST['max_year'] ?? 0 );
    $min_price  = floatval( $_POST['min_price'] ?? 0 );
    $max_price  = floatval( $_POST['max_price'] ?? 0 );
    $min_hours  = floatval( $_POST['min_hours'] ?? 0 );
    $max_hours  = floatval( $_POST['max_hours'] ?? 0 );

    // Check table exists
    $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    if ( ! $table_exists ) {
        wp_send_json_success( [ 'items' => [], 'total' => 0, 'totalPages' => 0, 'page' => $page, 'perPage' => $per_page ] );
    }

    // Build WHERE
    $where   = [ 'deleted_at IS NULL' ];
    $params  = [];

    // Categories (multi)
    if ( ! empty( $categories ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $categories ), '%s' ) );
        $where[]      = "LOWER(category) IN ({$placeholders})";
        $params       = array_merge( $params, array_map( 'strtolower', $categories ) );
    }

    // Brand (multi)
    if ( ! empty( $brands ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $brands ), '%s' ) );
        $where[]      = "brand IN ({$placeholders})";
        $params       = array_merge( $params, $brands );
    }

    // Type/category (multi)
    if ( ! empty( $types ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $types ), '%s' ) );
        $where[]      = "category IN ({$placeholders})";
        $params       = array_merge( $params, $types );
    }

    // Location (multi)
    if ( ! empty( $locations ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $locations ), '%s' ) );
        $where[]      = "location IN ({$placeholders})";
        $params       = array_merge( $params, $locations );
    }

    // Condition (multi)
    if ( ! empty( $conditions ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $conditions ), '%s' ) );
        $where[]      = "condition_status IN ({$placeholders})";
        $params       = array_merge( $params, $conditions );
    }

    // Year range
    if ( $min_year > 0 ) {
        $where[]  = 'year >= %d';
        $params[] = $min_year;
    }
    if ( $max_year > 0 ) {
        $where[]  = 'year <= %d';
        $params[] = $max_year;
    }

    // Price range
    if ( $min_price > 0 ) {
        $where[]  = 'price >= %f';
        $params[] = $min_price;
    }
    if ( $max_price > 0 ) {
        $where[]  = 'price <= %f';
        $params[] = $max_price;
    }

    // Hours range
    if ( $min_hours > 0 ) {
        $where[]  = 'hours >= %d';
        $params[] = $min_hours;
    }
    if ( $max_hours > 0 ) {
        $where[]  = 'hours <= %d';
        $params[] = $max_hours;
    }

    $where_sql = implode( ' AND ', $where );

    // Sorting
    switch ( $sort_by ) {
        case 'price-low':  $order_sql = 'price ASC'; break;
        case 'price-high': $order_sql = 'price DESC'; break;
        case 'hours-low':  $order_sql = 'hours ASC'; break;
        default:           $order_sql = 'created_at DESC';
    }

    // Count total
    $count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
    $total     = ! empty( $params ) ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : (int) $wpdb->get_var( $count_sql );

    // Fetch page
    $offset    = ( $page - 1 ) * $per_page;
    $data_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$order_sql} LIMIT %d OFFSET %d";
    $data_params = $params;
    $data_params[] = $per_page;
    $data_params[] = $offset;
    $rows = $wpdb->get_results( $wpdb->prepare( $data_sql, $data_params ) );

    $items = [];
    foreach ( $rows as $row ) {
        $image = '';
        if ( ! empty( $row->main_image ) ) {
            $image = $row->main_image;
        } elseif ( ! empty( $row->images ) ) {
            $imgs = json_decode( $row->images, true );
            if ( is_array( $imgs ) && ! empty( $imgs[0] ) ) {
                $image = $imgs[0];
            }
        }

        $status = $row->status ?: 'available';
        switch ( strtolower( $status ) ) {
            case 'sold':        $display_status = 'sold'; break;
            case 'coming_soon': $display_status = 'coming_soon'; break;
            default:            $display_status = 'ready_stock';
        }

        $items[] = [
            'id'            => (int) $row->id,
            'title'         => $row->name ?: 'Equipment',
            'url'           => '#',
            'category'      => strtolower( $row->category ?: 'others' ),
            'brand'         => $row->brand ?: '',
            'type'          => $row->category ?: '',
            'year'          => $row->year ?: '',
            'price'         => $row->price ?: '',
            'hours'         => $row->hours ?: '',
            'location'      => $row->location ?: '',
            'condition'     => $row->condition_status ?: '',
            'status'        => $display_status,
            'image'         => $image,
            'is_wishlisted' => false,
        ];
    }

    $total_pages = max( 1, ceil( $total / $per_page ) );

    wp_send_json_success( [
        'items'      => $items,
        'total'      => $total,
        'totalPages' => $total_pages,
        'page'       => $page,
        'perPage'    => $per_page,
    ] );
}
