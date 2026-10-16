<?php
/**
 * Settings AJAX — field names and dropdown options of the equipment forms.
 * Super admin only (gti_manage_settings). Storage and rules: inc/helpers/field-options.php.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/** Validated field key + registry entry, or a JSON error. */
function gti_settings_field() {
    $field = sanitize_key( wp_unslash( $_POST['field'] ?? '' ) );
    $reg   = gti_field_registry();
    if ( ! isset( $reg[ $field ] ) ) {
        gti_send_json_error( 'Field tidak dikenal.', 'bad_field', 400 );
    }
    return array( $field, $reg[ $field ] );
}

function gti_settings_scope() {
    $scope = sanitize_key( wp_unslash( $_POST['scope'] ?? '' ) );
    if ( ! in_array( $scope, array( 'both', 'used', 'rental' ), true ) ) {
        gti_send_json_error( 'Pilih scope: Used, Rental, atau Keduanya.', 'bad_scope', 400 );
    }
    return $scope;
}

function gti_settings_text( $key, $max ) {
    $text = trim( sanitize_text_field( wp_unslash( $_POST[ $key ] ?? '' ) ) );
    if ( $text === '' ) {
        gti_send_json_error( 'Nama tidak boleh kosong.', 'empty', 400 );
    }
    if ( mb_strlen( $text ) > $max ) {
        gti_send_json_error( "Maksimal {$max} karakter.", 'too_long', 400 );
    }
    return $text;
}

add_action( 'wp_ajax_gti_settings_save_label', 'gti_ajax_settings_save_label' );
function gti_ajax_settings_save_label() {
    gti_ajax_guard( 'gti_manage_settings' );

    list( $field ) = gti_settings_field();
    $scope = gti_settings_scope();
    $text  = gti_settings_text( 'label', 60 );

    $labels = gti_field_config()[ $field ]['label'];
    foreach ( gti_field_types( $scope ) as $type ) {
        $labels[ $type ] = $text;
    }
    gti_field_store( $field, $labels );

    gti_log_current_activity( 'update', "Renamed field {$field} to \"{$text}\" ({$scope})", 'settings', 0 );
    wp_send_json_success( array( 'message' => 'Nama field disimpan.' ) );
}

add_action( 'wp_ajax_gti_settings_save_option', 'gti_ajax_settings_save_option' );
function gti_ajax_settings_save_option() {
    gti_ajax_guard( 'gti_manage_settings' );
    global $wpdb;

    list( $field, $def ) = gti_settings_field();
    $scope   = gti_settings_scope();
    $text    = gti_settings_text( 'label', $def['len'] );
    $id      = sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) );
    $abbr    = ! empty( $def['abbr'] )
        ? substr( strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', wp_unslash( $_POST['abbr'] ?? '' ) ) ), 0, 4 )
        : '';
    $options = gti_field_config()[ $field ]['options'];

    $index = null;
    foreach ( $options as $i => $o ) {
        if ( $id !== '' && $o['id'] === $id ) { $index = $i; break; }
    }
    if ( $id !== '' && $index === null ) {
        gti_send_json_error( 'Opsi tidak ditemukan.', 'not_found', 404 );
    }

    $old   = $index === null ? null : $options[ $index ];
    $coded = $def['kind'] === 'coded';
    $value = $old && $coded ? $old['value'] : ( $coded ? gti_field_slug( $text ) : $text );

    foreach ( $options as $i => $o ) {
        if ( $i !== $index && gti_field_scope_overlaps( $o['scope'], $scope )
            && mb_strtolower( $o['value'] ) === mb_strtolower( $value ) ) {
            gti_send_json_error( 'Opsi "' . $text . '" sudah ada untuk scope tersebut.', 'duplicate', 409 );
        }
    }

    $row = array( 'id' => $old ? $old['id'] : 'n' . uniqid(), 'value' => $value, 'label' => $text, 'scope' => $scope );
    if ( ! empty( $def['abbr'] ) && $abbr !== '' ) { $row['abbr'] = $abbr; }

    // A text field stores its label on the units, so a rename has to follow them.
    $renamed = 0;
    if ( $old && ! $coded && $old['value'] !== $value ) {
        $types = gti_field_types( $old['scope'] );
        $renamed = (int) $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}gti_equipment SET `{$field}` = %s WHERE `{$field}` = %s AND type IN (" . implode( ',', array_fill( 0, count( $types ), '%s' ) ) . ')',
            array_merge( array( $value, $old['value'] ), $types )
        ) );
    }

    if ( $index === null ) { $options[] = $row; } else { $options[ $index ] = $row; }
    gti_field_store( $field, null, $options );

    gti_log_current_activity( 'update', ( $old ? 'Updated' : 'Added' ) . " option \"{$text}\" for {$field} ({$scope})", 'settings', 0 );
    wp_send_json_success( array( 'message' => $renamed ? "Opsi disimpan. {$renamed} unit ikut diperbarui." : 'Opsi disimpan.' ) );
}

add_action( 'wp_ajax_gti_settings_delete_option', 'gti_ajax_settings_delete_option' );
function gti_ajax_settings_delete_option() {
    gti_ajax_guard( 'gti_manage_settings' );

    list( $field ) = gti_settings_field();
    $id      = sanitize_text_field( wp_unslash( $_POST['id'] ?? '' ) );
    $options = gti_field_config()[ $field ]['options'];

    foreach ( $options as $i => $o ) {
        if ( $o['id'] !== $id ) continue;
        unset( $options[ $i ] );
        gti_field_store( $field, null, $options );
        gti_log_current_activity( 'delete', "Removed option \"{$o['label']}\" from {$field}", 'settings', 0 );
        // Units keep the stored value; the edit form still shows it.
        wp_send_json_success( array( 'message' => 'Opsi dihapus. Unit yang memakainya tidak berubah.' ) );
    }
    gti_send_json_error( 'Opsi tidak ditemukan.', 'not_found', 404 );
}
