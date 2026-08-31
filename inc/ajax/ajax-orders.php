<?php
/**
 * AJAX orders handler
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get user orders.
 */
function gti_ajax_get_orders() {
    $user_id = gti_ajax_get_user_id();
    if ( ! $user_id ) {
        gti_ajax_error( 'Unauthorized', 401 );
    }
    
    $orders = gti_get_user_orders( $user_id, 20 );
    
    $formatted = [];
    foreach ( $orders as $order ) {
        $formatted[] = [
            'id'         => $order->get_id(),
            'status'     => $order->get_status(),
            'total'      => $order->get_total(),
            'date'       => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
            'item_count' => $order->get_item_count(),
        ];
    }
    
    gti_ajax_success( [ 'orders' => $formatted ] );
}
add_action( 'wp_ajax_gti_get_orders', 'gti_ajax_get_orders' );

/**
 * Get order detail.
 */
function gti_ajax_get_order_detail() {
    $user_id = gti_ajax_get_user_id();
    if ( ! $user_id ) {
        gti_ajax_error( 'Unauthorized', 401 );
    }
    
    $order_id = gti_sanitize_int( $_POST['order_id'] ?? 0 );
    if ( ! $order_id ) {
        gti_ajax_error( 'Order ID tidak valid' );
    }
    
    $order = wc_get_order( $order_id );
    if ( ! $order || $order->get_customer_id() != $user_id ) {
        gti_ajax_error( 'Order tidak ditemukan', 404 );
    }
    
    $items = [];
    foreach ( $order->get_items() as $item ) {
        $items[] = [
            'name'     => $item->get_name(),
            'quantity' => $item->get_quantity(),
            'total'    => $item->get_total(),
        ];
    }
    
    gti_ajax_success( [
        'order' => [
            'id'          => $order->get_id(),
            'status'      => $order->get_status(),
            'total'       => $order->get_total(),
            'date'        => $order->get_date_created()->format( 'Y-m-d H:i:s' ),
            'items'       => $items,
            'address'     => $order->get_formatted_billing_address(),
            'payment_method' => $order->get_payment_method_title(),
        ],
    ] );
}
add_action( 'wp_ajax_gti_get_order_detail', 'gti_ajax_get_order_detail' );
