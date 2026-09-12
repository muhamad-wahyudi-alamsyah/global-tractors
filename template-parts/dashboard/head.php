<?php
/**
 * Dashboard <head> (PRD §13.6).
 *
 * Replaces a 116-line block that was copied into 18 templates. Assets are
 * enqueued rather than hand-written as <link> tags so they get one consistent
 * cache key, and the logo comes from a theme option instead of a hardcoded
 * .test domain (R-08, R-09).
 *
 * @var array $args page, title, subtitle, body_class, extra_css[], extra_js[]
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html( $args['title'] ); ?> &ndash; <?php bloginfo( 'name' ); ?></title>
    <?php wp_head(); ?>
</head>
<body class="gti-body <?php echo esc_attr( $args['body_class'] ); ?>">
    <div class="gti-wrapper">
