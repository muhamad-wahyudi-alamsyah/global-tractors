<?php
/**
 * Guards gti_quotation_split_notes(): the rental operator answer used to be
 * appended to the customer message, so old rows must still yield a labelled
 * value while free text that merely looks similar is left alone.
 *
 * Run: php tests/test-quotation-split-notes.php
 */
define('ABSPATH', __DIR__);
require __DIR__ . '/../inc/helpers/format-helpers.php';

$cases = array(
    // notes in                                  => text out,            operator out
    array( "Butuh unit cepat\nButuh operator: ya", 'Butuh unit cepat',   'ya' ),
    array( 'Butuh operator: tidak',                '',                   'tidak' ),
    array( "Baris satu\n\nButuh operator: ya",     'Baris satu',         'ya' ),
    array( 'Pesan biasa saja',                     'Pesan biasa saja',   '' ),
    array( "Butuh operator: ya\nlalu catatan",     "Butuh operator: ya\nlalu catatan", '' ),
    array( 'Butuh operator:',                      'Butuh operator:',    '' ),
    array( '',                                     '',                   '' ),
    array( null,                                   '',                   '' ),
);

foreach ( $cases as $c ) {
    $got = gti_quotation_split_notes( $c[0] );
    assert( $got['text'] === $c[1], var_export( $c[0], true ) . " => text " . var_export( $got['text'], true ) . ", expected " . var_export( $c[1], true ) );
    assert( $got['operator_needed'] === $c[2], var_export( $c[0], true ) . " => operator '{$got['operator_needed']}', expected '{$c[2]}'" );
}
echo "gti_quotation_split_notes OK\n";
