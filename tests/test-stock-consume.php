<?php
/**
 * Which status changes consume stock, and what status the part lands on
 * afterwards. The UPDATE itself needs a database and is verified by hand.
 */
define('ABSPATH', __DIR__);
function add_action() {}
require __DIR__ . '/../inc/helpers/spare-parts-taxonomy.php';
require __DIR__ . '/../inc/modules/stock.php';

// entity_type, from, to, should consume
$transitions = array(
    array( 'quotation', 'approved',  'completed', true  ),
    array( 'quotation', 'completed', 'completed', false ), // assignment re-fires the hook
    array( 'quotation', 'approved',  'rejected',  false ),
    array( 'quotation', 'new',       'processing',false ),
    array( 'sell',      'approved',  'completed', false ),
    array( 'request',   'processing','closed',    false ),
);
foreach ( $transitions as $t ) {
    $got = gti_stock_should_consume( $t[0], $t[1], $t[2] );
    assert( $got === $t[3], "{$t[0]} {$t[1]}->{$t[2]} => " . var_export( $got, true ) );
}

// stock after the sale, minimum_stock, expected derived status
$levels = array(
    array( 20, 10, 'in_stock' ),
    array( 10, 10, 'low_stock' ),
    array(  1, 10, 'low_stock' ),
    array(  0, 10, 'out_of_stock' ),
    array(  0,  0, 'out_of_stock' ),
);
foreach ( $levels as $l ) {
    $got = gti_spare_stock_status( $l[0], $l[1] );
    assert( $got === $l[2], "stock {$l[0]} / min {$l[1]} => $got" );
}

echo "gti_stock_should_consume + status derivation OK\n";

// Which quantity leaves the warehouse: the confirmed figure when there is one,
// otherwise what the customer asked about.
$quantities = array(
    array( array( 'quantity' => 50, 'fulfilled_quantity' => 5 ),    5  ),
    array( array( 'quantity' => 50, 'fulfilled_quantity' => 0 ),    0  ),  // nothing shipped
    array( array( 'quantity' => 50, 'fulfilled_quantity' => null ), 50 ),  // pre-migration row
    array( array( 'quantity' => 50, 'fulfilled_quantity' => '' ),   50 ),
    array( array( 'quantity' => 3 ),                                3  ),
    array( array(),                                                 0  ),
);
foreach ( $quantities as $q ) {
    $got = gti_stock_sold_quantity( $q[0] );
    assert( $got === $q[1], 'sold qty ' . json_encode( $q[0] ) . " => $got" );
}

echo "gti_stock_sold_quantity OK\n";
