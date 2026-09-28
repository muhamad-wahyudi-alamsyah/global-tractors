<?php
define('ABSPATH', __DIR__);

// Guard the regression: login pakai username ("admin") menormalisasi jadi telepon kosong,
// dan lookup-nya dulu cocok dengan user mana pun yang punya meta gti_phone kosong.
class GTI_Fake_WPDB {
    public $usermeta = 'wp_usermeta';
    public $queried  = false;
    public function prepare( $sql, ...$args ) { return $sql; }
    public function get_var( $sql ) { $this->queried = true; return 25; } // pura-pura ketemu user
}
$wpdb = new GTI_Fake_WPDB();
function get_userdata( $id ) { return (object) array( 'ID' => $id, 'user_login' => 'prabowo' ); }

require __DIR__ . '/../inc/user/user-helpers.php';

foreach ( array( '', 'admin', 'super admin', '-', 'abc@gmail.com' ) as $input ) {
    assert( gti_get_user_by_phone( $input ) === null, "non-digit input '$input' must not resolve to a user" );
    assert( gti_phone_exists( $input ) === false, "non-digit input '$input' must not count as existing phone" );
}
assert( $wpdb->queried === false, 'DB must not be queried for digitless input' );
echo "digitless phone lookup OK\n";

// Nomor asli tetap jalan.
assert( gti_get_user_by_phone( '0812-3456-7890' )->user_login === 'prabowo', 'real phone must still resolve' );
assert( $wpdb->queried === true, 'real phone must hit the DB' );
echo "real phone lookup OK\n";

foreach ( array( '08123456789' => '628123456789', '+62 812 3456 789' => '628123456789',
                 '8123456789' => '628123456789', 'admin' => '' ) as $in => $want ) {
    $got = gti_normalize_phone( $in );
    assert( $got === $want, "normalize('$in') => '$got', expected '$want'" );
}
echo "gti_normalize_phone OK\n";
