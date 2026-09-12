<?php
/**
 * Attachment service (PRD §5.2).
 *
 * Every document the panel sends or receives — proposal, quotation, invoice —
 * is stored once here: uploaded into the WP media library (so it also appears
 * in /dashboard/media-library) and indexed in wp_gti_attachments against the
 * inbox row it belongs to.
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'GTI_ATTACHMENT_MAX_BYTES', 10 * 1024 * 1024 ); // 10 MB, PRD §5.2

/**
 * MIME types accepted for a document upload.
 *
 * @return array extension pattern => mime
 */
function gti_allowed_document_mimes() {
    return array(
        'pdf'          => 'application/pdf',
        'doc'          => 'application/msword',
        'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls'          => 'application/vnd.ms-excel',
        'xlsx'         => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg|jpeg'     => 'image/jpeg',
        'png'          => 'image/png',
    );
}

/**
 * Route document uploads into their own dated folder.
 *
 * Hooked only for the duration of a single gti_attach_document() call so it
 * never affects ordinary media uploads.
 */
function gti_document_upload_dir( $dirs ) {
    $sub = '/gti-documents/' . date( 'Y/m' );

    $dirs['subdir'] = $sub;
    $dirs['path']   = $dirs['basedir'] . $sub;
    $dirs['url']    = $dirs['baseurl'] . $sub;

    return $dirs;
}

/**
 * Store one uploaded document against an inbox row.
 *
 * @param array $args entity_type, entity_id, kind, file_key (a $_FILES key), note
 * @return int|WP_Error wp_gti_attachments.id
 */
function gti_attach_document( array $args ) {
    global $wpdb;

    $args = array_merge(
        array( 'entity_type' => '', 'entity_id' => 0, 'kind' => 'other', 'file_key' => 'file', 'note' => '' ),
        $args
    );

    $key = $args['file_key'];
    if ( empty( $_FILES[ $key ] ) || ! isset( $_FILES[ $key ]['name'] ) || $_FILES[ $key ]['name'] === '' ) {
        return new WP_Error( 'no_file', 'Tidak ada berkas yang diunggah.' );
    }

    $file = $_FILES[ $key ];

    if ( ! empty( $file['error'] ) && $file['error'] !== UPLOAD_ERR_OK ) {
        return new WP_Error( 'upload_error', 'Berkas gagal diunggah. Silakan coba lagi.' );
    }

    if ( (int) $file['size'] > GTI_ATTACHMENT_MAX_BYTES ) {
        return new WP_Error( 'too_large', 'Ukuran berkas melebihi 10 MB.' );
    }

    // Validate by sniffing the file, not by trusting the submitted name or type:
    // a .php renamed to .pdf must not get through.
    $check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], gti_allowed_document_mimes() );
    if ( empty( $check['ext'] ) || empty( $check['type'] ) || ! in_array( $check['type'], gti_allowed_document_mimes(), true ) ) {
        return new WP_Error( 'bad_type', 'Tipe berkas tidak diizinkan. Gunakan PDF, DOC, DOCX, XLS, XLSX, JPG, atau PNG.' );
    }

    $original_name = sanitize_file_name( $file['name'] );

    // Rename before upload so the stored URL cannot be guessed from the entity ref.
    $entity_ref  = $args['entity_type'] . '-' . (int) $args['entity_id'];
    $random      = substr( str_replace( array( '.', '-', '_' ), '', wp_generate_password( 16, false, false ) ), 0, 8 );
    $safe_kind   = sanitize_key( $args['kind'] );
    $_FILES[ $key ]['name'] = $safe_kind . '-' . $entity_ref . '-' . $random . '.' . $check['ext'];

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    add_filter( 'upload_dir', 'gti_document_upload_dir' );
    $attachment_id = media_handle_upload( $key, 0, array(
        'post_title'   => $original_name,
        'post_excerpt' => $args['note'],
    ) );
    remove_filter( 'upload_dir', 'gti_document_upload_dir' );

    // Restore the submitted name for any later reader of $_FILES.
    $_FILES[ $key ]['name'] = $file['name'];

    if ( is_wp_error( $attachment_id ) ) {
        return $attachment_id;
    }

    gti_protect_documents_dir();

    $wpdb->insert(
        $wpdb->prefix . 'gti_attachments',
        array(
            'entity_type'   => $args['entity_type'],
            'entity_id'     => (int) $args['entity_id'],
            'kind'          => $safe_kind,
            'attachment_id' => (int) $attachment_id,
            'original_name' => $original_name,
            'file_size'     => (int) $file['size'],
            'mime_type'     => $check['type'],
            'note'          => $args['note'] ?: null,
            'uploaded_by'   => get_current_user_id() ?: null,
            'created_at'    => current_time( 'mysql' ),
        ),
        array( '%s', '%d', '%s', '%d', '%s', '%d', '%s', '%s', '%d', '%s' )
    );

    $row_id = (int) $wpdb->insert_id;

    gti_log_current_activity(
        'upload',
        sprintf( 'Uploaded %s document: %s', $safe_kind, $original_name ),
        $args['entity_type'],
        (int) $args['entity_id'],
        array( 'kind' => $safe_kind, 'file' => $original_name, 'size' => (int) $file['size'] )
    );

    return $row_id;
}

/**
 * Documents attached to a row.
 *
 * @param string|null $kind Filter by kind, or null for all.
 */
function gti_get_attachments( $entity_type, $entity_id, $kind = null ) {
    global $wpdb;

    $sql    = "SELECT * FROM {$wpdb->prefix}gti_attachments WHERE entity_type = %s AND entity_id = %d";
    $params = array( $entity_type, (int) $entity_id );

    if ( $kind ) {
        $sql     .= ' AND kind = %s';
        $params[] = $kind;
    }
    $sql .= ' ORDER BY created_at DESC, id DESC';

    $rows = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
    if ( ! $rows ) {
        return array();
    }

    foreach ( $rows as &$row ) {
        $row['url']       = wp_get_attachment_url( (int) $row['attachment_id'] );
        $row['size_text'] = gti_format_bytes( (int) $row['file_size'] );
    }

    return $rows;
}

/**
 * One attachment row by wp_gti_attachments.id.
 */
function gti_get_attachment( $id ) {
    global $wpdb;

    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}gti_attachments WHERE id = %d", (int) $id
    ), ARRAY_A );

    if ( $row ) {
        $row['url']       = wp_get_attachment_url( (int) $row['attachment_id'] );
        $row['size_text'] = gti_format_bytes( (int) $row['file_size'] );
    }

    return $row ?: null;
}

/**
 * Absolute file paths for a set of attachment row IDs, for wp_mail()'s 5th argument.
 */
function gti_attachment_paths( array $ids ) {
    $paths = array();

    foreach ( $ids as $id ) {
        $row = gti_get_attachment( $id );
        if ( ! $row ) {
            continue;
        }
        $path = get_attached_file( (int) $row['attachment_id'] );
        if ( $path && file_exists( $path ) ) {
            $paths[] = $path;
        }
    }

    return $paths;
}

/**
 * Mark attachments as having been emailed.
 */
function gti_mark_attachments_emailed( array $ids ) {
    global $wpdb;

    foreach ( $ids as $id ) {
        $wpdb->update(
            $wpdb->prefix . 'gti_attachments',
            array( 'emailed_at' => current_time( 'mysql' ) ),
            array( 'id' => (int) $id ),
            array( '%s' ), array( '%d' )
        );
    }
}

/**
 * Delete an attachment row and the media item behind it.
 */
function gti_delete_attachment( $id ) {
    global $wpdb;

    $row = gti_get_attachment( $id );
    if ( ! $row ) {
        return false;
    }

    wp_delete_attachment( (int) $row['attachment_id'], true );

    return (bool) $wpdb->delete( $wpdb->prefix . 'gti_attachments', array( 'id' => (int) $id ), array( '%d' ) );
}

/**
 * Drop a robots.txt-style guard into the documents folder.
 *
 * Note this only discourages indexing. The files still live under uploads and
 * remain reachable by anyone holding the URL; the random filename is what makes
 * the URL hard to guess. Truly private documents would need storage outside the
 * webroot behind a capability-checked download endpoint.
 */
function gti_protect_documents_dir() {
    $uploads = wp_get_upload_dir();
    $dir     = trailingslashit( $uploads['basedir'] ) . 'gti-documents';

    if ( ! is_dir( $dir ) ) {
        return;
    }

    $htaccess = $dir . '/.htaccess';
    if ( ! file_exists( $htaccess ) ) {
        @file_put_contents( $htaccess, "Options -Indexes\n<IfModule mod_headers.c>\n  Header set X-Robots-Tag \"noindex, nofollow\"\n</IfModule>\n" );
    }

    $index = $dir . '/index.php';
    if ( ! file_exists( $index ) ) {
        @file_put_contents( $index, "<?php\n// Silence is golden.\n" );
    }
}

/**
 * Byte count as a short human string.
 */
function gti_format_bytes( $bytes ) {
    if ( $bytes <= 0 ) {
        return '—';
    }
    if ( $bytes < 1024 ) {
        return $bytes . ' B';
    }
    if ( $bytes < 1024 * 1024 ) {
        return round( $bytes / 1024, 1 ) . ' KB';
    }
    return round( $bytes / ( 1024 * 1024 ), 1 ) . ' MB';
}
