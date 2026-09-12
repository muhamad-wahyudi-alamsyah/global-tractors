<?php
/**
 * Email layout. Table-based on purpose: it is what renders reliably across
 * mail clients, and it is carried over unchanged from the previous inline
 * implementation (PRD §5.3 item 2).
 *
 * Available: $title, $body_html, $ref, $sent_date, $site_url, $customer_name,
 *            $signature_html, $cta
 *
 * @package global-tractors
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #F5A623; padding: 30px; text-align: center;">
                            <h1 style="color: #1a1f36; margin: 0; font-size: 24px;">PT Global Tractors Indonesia</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1a1f36; margin: 0 0 20px 0; font-size: 20px;"><?php echo esc_html( $title ); ?></h2>

                            <?php if ( ! empty( $customer_name ) ) : ?>
                            <p style="color: #374151; line-height: 1.6; margin: 0 0 15px 0;">
                                Halo <?php echo esc_html( $customer_name ); ?>,
                            </p>
                            <?php endif; ?>

                            <?php echo $body_html; // phpcs:ignore — built from wp_kses_post()'d parts ?>

                            <?php if ( ! empty( $cta['url'] ) && ! empty( $cta['label'] ) ) : ?>
                            <p style="margin: 25px 0;">
                                <a href="<?php echo esc_url( $cta['url'] ); ?>"
                                   style="background-color: #F5A623; color: #1a1f36; text-decoration: none; padding: 12px 24px; border-radius: 6px; display: inline-block; font-weight: bold;">
                                    <?php echo esc_html( $cta['label'] ); ?>
                                </a>
                            </p>
                            <?php endif; ?>

                            <?php if ( ! empty( $signature_html ) ) : ?>
                            <div style="margin-top: 25px; color: #374151; line-height: 1.6;">
                                <?php echo $signature_html; // phpcs:ignore — escaped at build time ?>
                            </div>
                            <?php endif; ?>

                            <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">

                            <p style="color: #6b7280; font-size: 12px; margin: 0;">
                                Tanggal: <?php echo esc_html( $sent_date ); ?><br>
                                Ref: <?php echo esc_html( $ref ); ?>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="color: #6b7280; font-size: 12px; margin: 0 0 10px 0;">
                                PT Global Tractors Indonesia<br>
                                <a href="<?php echo esc_url( $site_url ); ?>" style="color: #F5A623; text-decoration: none;"><?php echo esc_html( $site_url ); ?></a>
                            </p>
                            <p style="color: #9ca3af; font-size: 11px; margin: 0;">
                                Balas email ini untuk menghubungi tim kami.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
