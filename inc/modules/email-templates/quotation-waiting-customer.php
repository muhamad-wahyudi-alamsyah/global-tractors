<?php
/**
 * Quotation — ready for approval
 *
 * Available: $row, $entity_type, $status, $ref, $note, $attachment_ids, $title
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<p style="color: #374151; line-height: 1.6; margin: 0 0 15px 0;">Dokumen penawaran untuk <strong><?php echo esc_html( $ref ); ?></strong> sudah siap dan terlampir pada email ini.</p>
<p style="color: #374151; line-height: 1.6; margin: 0 0 15px 0;"><?php if ( ! empty( $row["valid_until"] ) && $row["valid_until"] !== "0000-00-00" ) : ?>Penawaran ini berlaku sampai <strong><?php echo esc_html( date_i18n( "d F Y", strtotime( $row["valid_until"] ) ) ); ?></strong>.<?php endif; ?></p>
<p style="color: #374151; line-height: 1.6; margin: 0 0 15px 0;">Mohon konfirmasi persetujuan Anda dengan membalas email ini.</p>
