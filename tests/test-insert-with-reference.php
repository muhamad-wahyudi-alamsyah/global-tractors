<?php
/**
 * gti_insert_with_reference(): sequence comes from the highest number, and a
 * duplicate key costs a retry instead of a failed submission.
 *
 * $wpdb is faked — the point here is the retry/sequence logic, not SQL.
 */
define( 'ABSPATH', __DIR__ );

/** Minimal stand-in for the two $wpdb methods the helper calls. */
class GTI_Fake_Wpdb {
    public $insert_id = 0;
    public $last_error = '';
    public $taken = array();   // reference => true
    public $inserted = array();
    public $max = 0;           // what MAX(...) returns

    public function esc_like( $t ) { return $t; }
    public function prepare( $sql ) { return $sql; }
    public function get_var( $sql ) { return $this->max; }

    public function insert( $table, $data ) {
        $ref = end( $data );
        if ( isset( $this->taken[ $ref ] ) ) {
            $this->last_error = "Duplicate entry '{$ref}' for key 'quotation_id'";
            return false;
        }
        $this->taken[ $ref ] = true;
        $this->inserted[]    = $ref;
        $this->last_error    = '';
        $this->insert_id     = count( $this->inserted );
        return 1;
    }
}

global $wpdb;
$wpdb = new GTI_Fake_Wpdb();

require __DIR__ . '/../inc/db/repository.php';

// 1. Empty table → sequence starts at 1.
$got = gti_insert_with_reference( 'q', array( 'name' => 'a' ), 'ref', 'Q-202609-' );
assert( $got['ref'] === 'Q-202609-0001', 'first ref: ' . var_export( $got, true ) );
assert( $got['id'] === 1, 'first id' );

// 2. Highest stored sequence wins, not the newest row: a hand-typed
//    QT/2026/IX/001 row does not drag the generator back to a used number.
$wpdb->max = 7;
$got = gti_insert_with_reference( 'q', array( 'name' => 'b' ), 'ref', 'Q-202609-' );
assert( $got['ref'] === 'Q-202609-0008', 'after max=7: ' . var_export( $got, true ) );

// 3. Two visitors racing: the sequence the loser computed is already taken,
//    so it retries the next one instead of returning an error.
$wpdb->max = 8;
$wpdb->taken['Q-202609-0009'] = true;   // won by a parallel request
$got = gti_insert_with_reference( 'q', array( 'name' => 'c' ), 'ref', 'Q-202609-' );
assert( $got['ref'] === 'Q-202609-0010', 'after race: ' . var_export( $got, true ) );

// 4. A non-duplicate database error is not retried.
$wpdb2 = new GTI_Fake_Wpdb();
$wpdb2->max = 0;
$wpdb = $wpdb2;
$wpdb->taken = array();
$failing = new class extends GTI_Fake_Wpdb {
    public function insert( $table, $data ) {
        $this->last_error = 'Table q does not exist';
        return false;
    }
};
$wpdb = $failing;
assert( gti_insert_with_reference( 'q', array( 'name' => 'd' ), 'ref', 'REQ-' ) === false, 'hard error returns false' );

echo "gti_insert_with_reference OK\n";
