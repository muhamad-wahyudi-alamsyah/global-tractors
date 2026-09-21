<?php
define('ABSPATH', __DIR__);
require __DIR__ . '/../inc/helpers/format-helpers.php';
$cases = array(
    array( '850.000.000',   850000000.0 ),
    array( 'Rp 1.250.000',  1250000.0 ),
    array( '850000000',     850000000.0 ),
    array( '1.500',         1500.0 ),
    array( '1,234,567',     1234567.0 ),
    array( '1.234.567,89',  1234567.89 ),
    array( '1234.5',        1234.5 ),
    array( '',              0.0 ),
    array( '-',             0.0 ),
    array( 850000000,       850000000.0 ),
);
foreach ( $cases as $c ) {
    $got = gti_parse_amount( $c[0] );
    assert( $got === $c[1], var_export( $c[0], true ) . " => $got, expected {$c[1]}" );
}
echo "gti_parse_amount OK\n";
