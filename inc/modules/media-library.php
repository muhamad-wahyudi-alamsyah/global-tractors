<?php
/**
 * Media Library — backed by the native WordPress media library.
 *
 * The dashboard used to glob() the uploads directory directly, so it listed
 * orphaned files, missed everything WordPress knew about them (title, alt text,
 * uploader, real dimensions), and its Upload/Delete buttons pointed at AJAX
 * actions that were never registered. This module queries real attachments.
 *
 * @package global-tractors
 */

if (!defined('ABSPATH')) exit;

/** Mime prefixes treated as "documents" in the dashboard's two-way split. */
function gti_media_document_mimes() {
    return array('application', 'text', 'audio', 'video');
}

/**
 * Turn an attachment into the flat shape the media template and its JS expect.
 */
function gti_media_item($post) {
    $post = get_post($post);
    if (!$post) return null;

    $file      = get_attached_file($post->ID);
    $url       = wp_get_attachment_url($post->ID);
    $mime      = $post->post_mime_type;
    $is_image  = strpos($mime, 'image/') === 0;
    $metadata  = wp_get_attachment_metadata($post->ID);
    $filename  = $file ? basename($file) : basename((string) $url);
    $timestamp = strtotime($post->post_date);

    // WP 6.0+ records filesize in the metadata; fall back to the file itself.
    $size = 0;
    if (is_array($metadata) && !empty($metadata['filesize'])) {
        $size = (int) $metadata['filesize'];
    } elseif ($file && file_exists($file)) {
        $size = (int) filesize($file);
    }

    $dims = null;
    if ($is_image && is_array($metadata) && !empty($metadata['width'])) {
        $dims = array((int) $metadata['width'], (int) $metadata['height']);
    }

    $uploader = get_userdata($post->post_author);

    $item = array(
        'id'          => (int) $post->ID,
        'filename'    => $filename,
        'title'       => $post->post_title,
        'alt'         => (string) get_post_meta($post->ID, '_wp_attachment_image_alt', true),
        'caption'     => $post->post_excerpt,
        'url'         => $url,
        'thumb'       => $is_image ? (wp_get_attachment_image_url($post->ID, 'medium') ?: $url) : '',
        'type'        => $is_image ? 'image' : 'document',
        'mime'        => $mime,
        'ext'         => strtoupper(pathinfo($filename, PATHINFO_EXTENSION)),
        'size'        => $size,
        'size_human'  => $size ? size_format($size) : '—',
        'date'        => $timestamp,
        'date_human'  => date_i18n('M j, Y', $timestamp),
        'date_full'   => $post->post_date,
        'month'       => date('Y-m', $timestamp),
        'month_label' => date_i18n('F Y', $timestamp),
        'dims'        => $dims,
        'uploader'    => $uploader ? ($uploader->display_name ?: $uploader->user_login) : '',
        'attached_to' => $post->post_parent ? get_the_title($post->post_parent) : '',
    );

    return $item;
}

/**
 * Let WP_Query's `s` match attachment filenames, not just titles.
 */
function gti_media_enable_filename_search() {
    add_filter('wp_allow_query_attachment_by_filename', '__return_true');
}

/**
 * Query the media library for the dashboard grid.
 *
 * @param array $args search, type ('image'|'document'), month ('Y-m'), per_page, page
 * @return array{items: array, total: int, pages: int}
 */
function gti_query_media($args = array()) {
    $args = wp_parse_args($args, array(
        'search'   => '',
        'type'     => '',
        'month'    => '',
        'per_page' => 24,
        'page'     => 1,
    ));

    $query_args = array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => (int) $args['per_page'],
        'paged'          => max(1, (int) $args['page']),
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    if ($args['type'] === 'image') {
        $query_args['post_mime_type'] = 'image';
    } elseif ($args['type'] === 'document') {
        $query_args['post_mime_type'] = gti_media_document_mimes();
    }

    if ($args['search']) {
        $query_args['s'] = $args['search'];
        gti_media_enable_filename_search();
    }

    if ($args['month'] && preg_match('/^(\d{4})-(\d{2})$/', $args['month'], $m)) {
        $query_args['date_query'] = array(array('year' => (int) $m[1], 'month' => (int) $m[2]));
    }

    $query = new WP_Query($query_args);

    $items = array();
    foreach ($query->posts as $post) {
        $item = gti_media_item($post);
        if ($item) $items[] = $item;
    }

    return array(
        'items' => $items,
        'total' => (int) $query->found_posts,
        'pages' => (int) $query->max_num_pages,
    );
}

/**
 * Stat-card counters for the media page.
 */
function gti_media_counts() {
    $counts = (array) wp_count_attachments();

    $images = 0;
    $documents = 0;
    foreach ($counts as $mime => $count) {
        if (strpos($mime, 'image/') === 0) {
            $images += (int) $count;
        } else {
            $documents += (int) $count;
        }
    }

    return array(
        'total'     => $images + $documents,
        'images'    => $images,
        'documents' => $documents,
        'size'      => gti_media_total_size(),
    );
}

/**
 * Total bytes held by the media library.
 *
 * Summing requires touching every attachment's metadata, so the result is
 * cached for an hour and invalidated whenever an attachment is added or removed.
 */
function gti_media_total_size() {
    $cached = get_transient('gti_media_total_size');
    if ($cached !== false) return (int) $cached;

    global $wpdb;
    $ids = $wpdb->get_col(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_status = 'inherit'"
    );

    $total = 0;
    foreach ($ids as $id) {
        $metadata = wp_get_attachment_metadata($id);
        if (is_array($metadata) && !empty($metadata['filesize'])) {
            $total += (int) $metadata['filesize'];
            continue;
        }
        $file = get_attached_file($id);
        if ($file && file_exists($file)) $total += (int) filesize($file);
    }

    set_transient('gti_media_total_size', $total, HOUR_IN_SECONDS);
    return $total;
}

add_action('add_attachment', 'gti_media_flush_size_cache');
add_action('delete_attachment', 'gti_media_flush_size_cache');
function gti_media_flush_size_cache() {
    delete_transient('gti_media_total_size');
}

/**
 * Number of attachments per 'Y-m', for the month group headers.
 */
function gti_media_month_counts() {
    global $wpdb;

    $rows = $wpdb->get_results(
        "SELECT DATE_FORMAT(post_date, '%Y-%m') AS ym, COUNT(*) AS total
         FROM {$wpdb->posts}
         WHERE post_type = 'attachment' AND post_status = 'inherit'
         GROUP BY ym"
    );

    $counts = array();
    foreach ($rows as $row) {
        $counts[$row->ym] = (int) $row->total;
    }
    return $counts;
}

/**
 * Months that actually contain uploads, for the filter dropdown.
 *
 * @return array 'Y-m' => 'F Y', newest first
 */
function gti_media_months() {
    global $wpdb;

    $rows = $wpdb->get_results(
        "SELECT DISTINCT YEAR(post_date) AS y, MONTH(post_date) AS m
         FROM {$wpdb->posts}
         WHERE post_type = 'attachment' AND post_status = 'inherit'
         ORDER BY y DESC, m DESC"
    );

    $months = array();
    foreach ($rows as $row) {
        $key = sprintf('%04d-%02d', $row->y, $row->m);
        $months[$key] = date_i18n('F Y', mktime(0, 0, 0, (int) $row->m, 1, (int) $row->y));
    }
    return $months;
}
