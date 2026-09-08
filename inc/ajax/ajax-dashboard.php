<?php
/**
 * AJAX endpoints for the dashboard pages.
 *
 * These back buttons that already existed in the templates but pointed at
 * actions nobody had registered: media upload/delete, article publish toggle,
 * profile save, and password change.
 *
 * @package global-tractors
 */

if (!defined('ABSPATH')) exit;

/**
 * Shared guard: logged in, correct nonce, and holding the required capability.
 *
 * @param string $capability Capability to require.
 */
function gti_ajax_guard($capability = 'read') {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in.'), 401);
    }
    if (!check_ajax_referer('gti_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed. Please reload the page.'), 403);
    }
    if ($capability && !current_user_can($capability)) {
        wp_send_json_error(array('message' => 'You do not have permission to do that.'), 403);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Media Library
// ─────────────────────────────────────────────────────────────────────────────

add_action('wp_ajax_gti_upload_media', 'gti_ajax_upload_media');
function gti_ajax_upload_media() {
    gti_ajax_guard('upload_files');

    if (empty($_FILES['files']['name'][0]) && empty($_FILES['file']['name'])) {
        wp_send_json_error(array('message' => 'No files were received.'));
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    // Normalise both the multi-file ("files[]") and single-file shapes.
    $files = array();
    if (!empty($_FILES['files']['name'])) {
        $count = count((array) $_FILES['files']['name']);
        for ($i = 0; $i < $count; $i++) {
            $files[] = array(
                'name'     => $_FILES['files']['name'][$i],
                'type'     => $_FILES['files']['type'][$i],
                'tmp_name' => $_FILES['files']['tmp_name'][$i],
                'error'    => $_FILES['files']['error'][$i],
                'size'     => $_FILES['files']['size'][$i],
            );
        }
    } elseif (!empty($_FILES['file']['name'])) {
        $files[] = $_FILES['file'];
    }

    $uploaded = array();
    $errors   = array();

    foreach ($files as $index => $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = $file['name'] . ': upload failed.';
            continue;
        }

        // media_handle_upload() reads straight out of $_FILES, so hand it this entry.
        $_FILES['gti_media_upload'] = $file;
        $attachment_id = media_handle_upload('gti_media_upload', 0);
        unset($_FILES['gti_media_upload']);

        if (is_wp_error($attachment_id)) {
            $errors[] = $file['name'] . ': ' . $attachment_id->get_error_message();
            continue;
        }

        $uploaded[] = gti_media_item($attachment_id);
    }

    if (empty($uploaded)) {
        wp_send_json_error(array('message' => $errors ? implode(' ', $errors) : 'Nothing was uploaded.'));
    }

    wp_send_json_success(array(
        'message'  => count($uploaded) . ' file(s) uploaded.',
        'uploaded' => $uploaded,
        'errors'   => $errors,
    ));
}

add_action('wp_ajax_gti_delete_media', 'gti_ajax_delete_media');
function gti_ajax_delete_media() {
    gti_ajax_guard('upload_files');

    $id = (int) ($_POST['id'] ?? 0);
    $attachment = $id ? get_post($id) : null;

    if (!$attachment || $attachment->post_type !== 'attachment') {
        wp_send_json_error(array('message' => 'That file no longer exists.'));
    }
    if (!current_user_can('delete_post', $id)) {
        wp_send_json_error(array('message' => 'You do not have permission to delete that file.'), 403);
    }

    if (!wp_delete_attachment($id, true)) {
        wp_send_json_error(array('message' => 'Failed to delete the file.'));
    }

    wp_send_json_success(array('message' => 'File deleted.'));
}

add_action('wp_ajax_gti_update_media', 'gti_ajax_update_media');
function gti_ajax_update_media() {
    gti_ajax_guard('upload_files');

    $id = (int) ($_POST['id'] ?? 0);
    $attachment = $id ? get_post($id) : null;
    if (!$attachment || $attachment->post_type !== 'attachment') {
        wp_send_json_error(array('message' => 'That file no longer exists.'));
    }

    if (isset($_POST['title'])) {
        wp_update_post(array('ID' => $id, 'post_title' => sanitize_text_field($_POST['title'])));
    }
    if (isset($_POST['alt'])) {
        update_post_meta($id, '_wp_attachment_image_alt', sanitize_text_field($_POST['alt']));
    }

    wp_send_json_success(array('message' => 'File details saved.', 'item' => gti_media_item($id)));
}

// ─────────────────────────────────────────────────────────────────────────────
// News & Articles
// ─────────────────────────────────────────────────────────────────────────────

add_action('wp_ajax_gti_update_article_status', 'gti_ajax_update_article_status');
function gti_ajax_update_article_status() {
    gti_ajax_guard('edit_posts');

    $id     = (int) ($_POST['id'] ?? 0);
    $status = sanitize_text_field($_POST['status'] ?? '');
    $post   = $id ? get_post($id) : null;

    if (!$post || $post->post_type !== 'post') {
        wp_send_json_error(array('message' => 'That article no longer exists.'));
    }
    if (!in_array($status, array('published', 'draft', 'archived'), true)) {
        wp_send_json_error(array('message' => 'Unknown status.'));
    }
    if (!current_user_can('edit_post', $id)) {
        wp_send_json_error(array('message' => 'You do not have permission to edit that article.'), 403);
    }

    $result = wp_update_post(array(
        'ID'          => $id,
        'post_status' => gti_article_status_to_post($status),
    ), true);

    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    wp_send_json_success(array('message' => 'Article status updated.', 'status' => $status));
}

add_action('wp_ajax_gti_delete_article', 'gti_ajax_delete_article');
function gti_ajax_delete_article() {
    gti_ajax_guard('edit_posts');

    $id   = (int) ($_POST['id'] ?? 0);
    $post = $id ? get_post($id) : null;

    if (!$post || $post->post_type !== 'post') {
        wp_send_json_error(array('message' => 'That article no longer exists.'));
    }
    if (!current_user_can('delete_post', $id)) {
        wp_send_json_error(array('message' => 'You do not have permission to delete that article.'), 403);
    }

    if (!wp_trash_post($id)) {
        wp_send_json_error(array('message' => 'Failed to delete the article.'));
    }

    wp_send_json_success(array('message' => 'Article moved to trash.'));
}

// ─────────────────────────────────────────────────────────────────────────────
// Profile & password (/dashboard/users)
// ─────────────────────────────────────────────────────────────────────────────

add_action('wp_ajax_gti_update_profile', 'gti_ajax_save_profile');
function gti_ajax_save_profile() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in.'), 401);
    }
    if (!wp_verify_nonce($_POST['gti_profile_nonce'] ?? '', 'gti_profile_action')) {
        wp_send_json_error(array('message' => 'Security check failed. Please reload the page.'), 403);
    }

    $user_id = get_current_user_id();
    $name    = sanitize_text_field($_POST['name'] ?? '');

    if ($name === '') {
        wp_send_json_error(array('message' => 'Full name is required.'));
    }

    $result = wp_update_user(array('ID' => $user_id, 'display_name' => $name));
    if (is_wp_error($result)) {
        wp_send_json_error(array('message' => $result->get_error_message()));
    }

    update_user_meta($user_id, 'gti_phone', sanitize_text_field($_POST['phone'] ?? ''));
    update_user_meta($user_id, 'gti_address', sanitize_textarea_field($_POST['address'] ?? ''));
    update_user_meta($user_id, 'gti_city', sanitize_text_field($_POST['city'] ?? ''));
    update_user_meta($user_id, 'gti_province', sanitize_text_field($_POST['province'] ?? ''));

    wp_send_json_success(array('message' => 'Profile updated successfully.'));
}

add_action('wp_ajax_gti_change_password', 'gti_ajax_change_password');
function gti_ajax_change_password() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'You must be logged in.'), 401);
    }
    if (!wp_verify_nonce($_POST['gti_password_nonce'] ?? '', 'gti_password_action')) {
        wp_send_json_error(array('message' => 'Security check failed. Please reload the page.'), 403);
    }

    $user    = wp_get_current_user();
    $current = (string) ($_POST['current_password'] ?? '');
    $new     = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if (!wp_check_password($current, $user->user_pass, $user->ID)) {
        wp_send_json_error(array('message' => 'Your current password is incorrect.'));
    }
    if (strlen($new) < 8) {
        wp_send_json_error(array('message' => 'The new password must be at least 8 characters.'));
    }
    if ($new !== $confirm) {
        wp_send_json_error(array('message' => 'The new passwords do not match.'));
    }
    if ($new === $current) {
        wp_send_json_error(array('message' => 'The new password must differ from the current one.'));
    }

    wp_set_password($new, $user->ID);
    gti_log_activity($user->ID, 'password_change', 'Password changed from the dashboard', 'user', $user->ID);

    // wp_set_password() destroys the session, so sign the user back in.
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true);

    wp_send_json_success(array('message' => 'Password updated successfully.'));
}
