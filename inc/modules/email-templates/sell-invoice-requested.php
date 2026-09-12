<?php
/**
 * Sell Equipment — invoice requested (PRD §5.3).
 *
 * Must carry: unit name, agreed price, the address the invoice goes to, the
 * supporting documents being asked for, and a deadline.
 *
 * Available: $row, $entity_type, $status, $ref, $note, $attachment_ids, $title
 * Extra:     $invoice_documents (array of labels), $invoice_deadline_days
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$unit = trim( implode( ' ', array_filter( array(
    $row['equipment_name']  ?? '',
    $row['equipment_brand'] ?? '',
    $row['equipment_model'] ?? '',
) ) ) );

$year  = ! empty( $row['equipment_year'] ) ? ' (' . $row['equipment_year'] . ')' : '';
$price = ! empty( $row['offered_price'] )
    ? 'Rp ' . number_format( (float) $row['offered_price'], 0, ',', '.' )
    : '—';

$deadline_days = isset( $invoice_deadline_days ) ? (int) $invoice_deadline_days : 7;
$deadline      = date_i18n( 'd F Y', strtotime( '+' . $deadline_days . ' days' ) );

$documents = ! empty( $invoice_documents ) && is_array( $invoice_documents )
    ? $invoice_documents
    : array( 'Invoice', 'STNK / BPKB', 'Faktur', 'Form A' );

$invoice_to = get_option( 'gti_invoice_email', get_option( 'admin_email' ) );
?>
<p style="color: #374151; line-height: 1.6; margin: 0 0 15px 0;">
    Terkait penawaran unit Anda dengan nomor <strong><?php echo esc_html( $ref ); ?></strong>,
    kami mohon Anda mengirimkan invoice beserta dokumen pendukungnya.
</p>

<table cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 20px 0; border-collapse: collapse;">
    <tr>
        <td style="padding: 8px 0; color: #6b7280; font-size: 14px; width: 40%;">Unit</td>
        <td style="padding: 8px 0; color: #1a1f36; font-size: 14px;"><strong><?php echo esc_html( $unit . $year ); ?></strong></td>
    </tr>
    <tr>
        <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Harga yang disepakati</td>
        <td style="padding: 8px 0; color: #1a1f36; font-size: 14px;"><strong><?php echo esc_html( $price ); ?></strong></td>
    </tr>
    <tr>
        <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Kirim invoice ke</td>
        <td style="padding: 8px 0; color: #1a1f36; font-size: 14px;"><a href="mailto:<?php echo esc_attr( $invoice_to ); ?>" style="color:#F5A623;"><?php echo esc_html( $invoice_to ); ?></a></td>
    </tr>
    <tr>
        <td style="padding: 8px 0; color: #6b7280; font-size: 14px;">Batas waktu</td>
        <td style="padding: 8px 0; color: #1a1f36; font-size: 14px;"><strong><?php echo esc_html( $deadline ); ?></strong> (<?php echo (int) $deadline_days; ?> hari)</td>
    </tr>
</table>

<p style="color: #374151; line-height: 1.6; margin: 0 0 8px 0;"><strong>Dokumen yang kami perlukan:</strong></p>
<ul style="color: #374151; line-height: 1.8; margin: 0 0 15px 0; padding-left: 20px;">
    <?php foreach ( $documents as $document ) : ?>
        <li><?php echo esc_html( $document ); ?></li>
    <?php endforeach; ?>
</ul>

<p style="color: #374151; line-height: 1.6; margin: 0 0 15px 0;">
    Balas email ini dengan melampirkan dokumen tersebut. Bila ada kendala, silakan hubungi kami.
</p>
