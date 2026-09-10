<?php
/**
 * Template: Add New Article (/dashboard/news-articles/add)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);

// Articles are native WordPress posts — see inc/modules/news-articles.php
$editing_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$article    = $editing_id ? gti_article_from_post($editing_id) : null;
if ($editing_id && !$article) {
    $editing_id = 0;
}

$success = isset($_GET['saved']);
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['gti_na_nonce']) && wp_verify_nonce($_POST['gti_na_nonce'], 'gti_save_article')) {

    if (!current_user_can('edit_posts')) {
        $error = 'You do not have permission to publish articles.';
    } else {
        $title    = sanitize_text_field($_POST['title'] ?? '');
        $category = sanitize_text_field($_POST['category'] ?? '');
        $status   = sanitize_text_field($_POST['status'] ?? 'draft');

        if (empty($title) || empty($category)) {
            $error = 'Please fill in all required fields.';
        } else {
            $featured_image_id = 0;

            // The featured image goes into the WordPress media library, so it is
            // reusable and shows up on /dashboard/media-library.
            if (!empty($_FILES['featured_image']['name']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $uploaded = media_handle_upload('featured_image', 0);
                if (is_wp_error($uploaded)) {
                    $error = 'Featured image upload failed: ' . $uploaded->get_error_message();
                } else {
                    $featured_image_id = $uploaded;
                }
            }

            if (!$error) {
                $post_id = gti_save_article(array(
                    'id'                => $editing_id,
                    'title'             => $title,
                    'category'          => $category,
                    'excerpt'           => sanitize_textarea_field($_POST['excerpt'] ?? ''),
                    'content'           => wp_kses_post($_POST['content'] ?? ''),
                    'tags'              => sanitize_text_field($_POST['tags'] ?? ''),
                    'status'            => $status,
                    'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
                    // Reassigning an article to someone else needs edit_others_posts.
                    'author_id'         => current_user_can('edit_others_posts')
                        ? (intval($_POST['author_id'] ?? 0) ?: ($editing_id ? 0 : $current_user->ID))
                        : ($editing_id ? 0 : $current_user->ID),
                    'featured_image_id' => $featured_image_id,
                    'remove_featured_image' => !empty($_POST['remove_featured_image']),
                ));

                if (is_wp_error($post_id)) {
                    $error = $post_id->get_error_message();
                } else {
                    // Redirect so a page refresh cannot re-submit the article.
                    wp_safe_redirect(gti_dashboard_url('news-articles/add') . '?id=' . $post_id . '&saved=1');
                    exit;
                }
            }
        }
    }
}

// Field values: what was just submitted wins, then the stored article, then a default.
function gti_na_value($field, $article, $default = '') {
    if (isset($_POST[$field])) return $_POST[$field];
    if ($article && isset($article->$field)) return $article->$field;
    return $default;
}

$page_title  = $editing_id ? 'Edit Article' : 'Add New Article';
$page_intro  = $editing_id ? 'Update an existing news or article post' : 'Create a new news or article post';
$existing_thumb = $article ? $article->featured_image : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title); ?> - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/add-equipment.css">
    <style>
        /* ====== Article-specific overrides ====== */
        .gti-na-form .gti-ae-form-grid { grid-template-columns: 1fr 1fr; }
        .gti-na-form .gti-ae-field-full { grid-column: 1 / -1; }

        /* Tags input */
        .gti-na-tags-wrap {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }
        .gti-na-tag {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 12px;
            color: #374151;
        }
        .gti-na-tag-remove {
            cursor: pointer;
            color: #9ca3af;
            font-size: 10px;
            background: none;
            border: none;
            padding: 0;
            line-height: 1;
        }
        .gti-na-tag-remove:hover { color: #ef4444; }

        /* Content editor */
        .gti-na-content-editor {
            width: 100%;
            min-height: 320px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 16px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            color: #1a1f36;
            line-height: 1.6;
            resize: vertical;
            background: #fff;
            transition: border-color 0.15s;
        }
        .gti-na-content-editor:focus {
            outline: none;
            border-color: #F5A623;
            box-shadow: 0 0 0 3px rgba(245, 166, 35, 0.1);
        }

        /* Featured image upload */
        .gti-na-featured-upload {
            border: 2px dashed #d1d5db;
            border-radius: 10px;
            padding: 32px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fafafa;
        }
        .gti-na-featured-upload:hover {
            border-color: #F5A623;
            background: #fffbeb;
        }
        .gti-na-featured-upload.has-image {
            padding: 0;
            border-style: solid;
            border-color: #e5e7eb;
            background: #fff;
        }
        .gti-na-featured-preview {
            display: none;
            position: relative;
        }
        .gti-na-featured-preview img {
            width: 100%;
            max-height: 240px;
            object-fit: cover;
            border-radius: 8px;
        }
        .gti-na-featured-remove {
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: rgba(0,0,0,0.6);
            color: #fff;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .gti-na-featured-remove:hover { background: rgba(239,68,68,0.9); }

        /* Character counter */
        .gti-na-char-count {
            font-size: 12px;
            color: #9ca3af;
            text-align: right;
            margin-top: 4px;
        }

        @media (max-width: 768px) {
            .gti-na-form .gti-ae-form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="gti-body">
    <div class="gti-wrapper">
        <!-- Sidebar -->
        <aside class="gti-sidebar" id="gti-sidebar">
            <div class="gti-sidebar-header">
                <div class="gti-logo">
                    <img src="http://global-tractors.test/wp-content/uploads/2026/07/logo-header-footer-pt-global-tractors-indonesia.png" alt="PT Global Tractors Indonesia" class="gti-logo-img">
                    <img src="http://global-tractors.test/wp-content/uploads/2026/07/cropped-favicon-pt-global-tractors-indonesia.png" alt="GTI" class="gti-logo-favicon">
                </div>
            </div>

            <div class="gti-sidebar-divider"></div>

            <nav class="gti-nav">
                <div class="gti-nav-group">
                    <a href="<?php echo esc_url(gti_dashboard_url()); ?>" class="gti-nav-item">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="gti-nav-group gti-has-children">
                    <div class="gti-nav-section">EQUIPMENT</div>
                    <a href="#" class="gti-nav-parent" data-toggle="dropdown"><i class="fas fa-truck"></i><span>Equipment</span><i class="fas fa-chevron-down gti-nav-arrow"></i></a>
                    <div class="gti-nav-children">
                        <a href="<?php echo esc_url(gti_dashboard_url('used-equipment')); ?>" class="gti-nav-child"><i></i><span>Used Equipment</span></a>
                        <a href="<?php echo esc_url(gti_dashboard_url('rental-equipment')); ?>" class="gti-nav-child"><i></i><span>Rental Equipment</span></a>
                    </div>
                    <a href="<?php echo esc_url(gti_dashboard_url('spare-parts')); ?>" class="gti-nav-item"><i class="fas fa-cog"></i><span>Spare Parts</span></a>
                </div>

                <div class="gti-nav-group">
                    <div class="gti-nav-section">REQUEST &amp; INQUIRY</div>
                    <a href="<?php echo esc_url(gti_dashboard_url('request-equipment')); ?>" class="gti-nav-item"><i class="fas fa-file-alt"></i><span>Request Equipment</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('request-quotation')); ?>" class="gti-nav-item"><i class="fas fa-clipboard-list"></i><span>Request Quotation</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('sell-equipment')); ?>" class="gti-nav-item"><i class="fas fa-handshake"></i><span>Sell Equipment</span></a>
                </div>

                <div class="gti-nav-group">
                    <div class="gti-nav-section">MANAGEMENT</div>
                    <a href="<?php echo esc_url(gti_dashboard_url('customers')); ?>" class="gti-nav-item"><i class="fas fa-users"></i><span>Customers</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('news-articles')); ?>" class="gti-nav-item active"><i class="fas fa-newspaper"></i><span>News &amp; Articles</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('media-library')); ?>" class="gti-nav-item"><i class="fas fa-photo-video"></i><span>Media Library</span></a>
                </div>

                <div class="gti-nav-group">
                    <div class="gti-nav-section">SYSTEM</div>
                    <a href="<?php echo esc_url(gti_dashboard_url('users')); ?>" class="gti-nav-item"><i class="fas fa-user-shield"></i><span>Users</span></a>
                    <a href="<?php echo esc_url(gti_dashboard_url('activity-log')); ?>" class="gti-nav-item"><i class="fas fa-history"></i><span>Activity Log</span></a>
                </div>
            </nav>

            <div class="gti-sidebar-footer">
                <a href="#" class="gti-nav-item" id="gti-collapse-btn">
                    <i class="fas fa-chevron-left"></i>
                    <span>Collapse Menu</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="gti-main" id="gti-main">
            <!-- Header -->
            <header class="gti-header">
                <div class="gti-header-left">
                    <button class="gti-menu-toggle" id="gti-menu-toggle"><i class="fas fa-bars"></i></button>
                    <div>
                        <h1 class="gti-page-title"><?php echo esc_html($page_title); ?></h1>
                        <p class="gti-welcome"><?php echo esc_html($page_intro); ?></p>
                    </div>
                </div>
                <div class="gti-header-right">
                    <div class="gti-date-filter">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo date('M j, Y'); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="gti-notifications">
                        <i class="fas fa-bell"></i>
                        <span class="gti-badge">3</span>
                    </div>
                    <div class="gti-user-menu">
                        <img src="<?php echo esc_url($user_avatar); ?>" alt="Avatar" class="gti-avatar">
                        <div class="gti-user-info">
                            <strong><?php echo esc_html($user_name); ?></strong>
                            <small>Super Admin</small>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="gti-content">
                <!-- Back Button -->
                <div class="gti-ae-back-row">
                    <a href="<?php echo esc_url(gti_dashboard_url('news-articles')); ?>" class="gti-ae-back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Articles List
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="gti-ae-form-card" style="border-left: 4px solid #10b981; margin-bottom: 20px;">
                        <div class="gti-ae-card-body" style="display: flex; align-items: center; gap: 12px; padding: 16px 20px;">
                            <i class="fas fa-check-circle" style="color: #10b981; font-size: 20px;"></i>
                            <span style="color: #10b981; font-weight: 500;">Article saved successfully!</span>
                            <?php if ($article && $article->status === 'published'): ?>
                                <a href="<?php echo esc_url($article->permalink); ?>" target="_blank" rel="noopener" style="margin-left: auto; color: var(--gti-primary); font-weight: 500; text-decoration: none;">View on Site &rarr;</a>
                                <a href="<?php echo esc_url(gti_dashboard_url('news-articles')); ?>" style="color: var(--gti-primary); font-weight: 500; text-decoration: none;">View List &rarr;</a>
                            <?php else: ?>
                                <a href="<?php echo esc_url(gti_dashboard_url('news-articles')); ?>" style="margin-left: auto; color: var(--gti-primary); font-weight: 500; text-decoration: none;">View List &rarr;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="gti-ae-form-card" style="border-left: 4px solid #ef4444; margin-bottom: 20px;">
                        <div class="gti-ae-card-body" style="display: flex; align-items: center; gap: 12px; padding: 16px 20px;">
                            <i class="fas fa-exclamation-circle" style="color: #ef4444; font-size: 20px;"></i>
                            <span style="color: #ef4444; font-weight: 500;"><?php echo esc_html($error); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form -->
                <form class="gti-ae-form gti-na-form" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('gti_save_article', 'gti_na_nonce'); ?>

                    <!-- Article Information -->
                    <div class="gti-ae-form-card">
                        <div class="gti-ae-card-header">
                            <h3><i class="fas fa-newspaper"></i> Article Information</h3>
                        </div>
                        <div class="gti-ae-card-body">
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Title <span class="required">*</span></label>
                                    <input type="text" name="title" placeholder="Enter article title..." required value="<?php echo esc_attr(gti_na_value('title', $article)); ?>">
                                </div>
                            </div>
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field">
                                    <label>Category <span class="required">*</span></label>
                                    <select name="category" required>
                                        <option value="">Select Category</option>
                                        <?php
                                        $cats    = gti_article_categories();
                                        $sel_cat = gti_na_value('category', $article);
                                        foreach ($cats as $val => $label):
                                        ?>
                                            <option value="<?php echo esc_attr($val); ?>" <?php selected($sel_cat, $val); ?>><?php echo esc_html($label); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="gti-ae-field">
                                    <label>Status</label>
                                    <?php $sel_status = gti_na_value('status', $article, 'draft'); ?>
                                    <select name="status">
                                        <option value="draft" <?php selected($sel_status, 'draft'); ?>>Draft</option>
                                        <option value="published" <?php selected($sel_status, 'published'); ?>>Published</option>
                                        <option value="archived" <?php selected($sel_status, 'archived'); ?>>Archived</option>
                                    </select>
                                </div>
                            </div>
                            <div class="gti-ae-form-grid">
                                <?php
                                // The author is a real WordPress user so the byline matches the public post.
                                $authors     = get_users(array('capability' => 'edit_posts', 'orderby' => 'display_name'));
                                if (empty($authors)) $authors = array($current_user);
                                $sel_author  = intval($_POST['author_id'] ?? 0);
                                if (!$sel_author && $article) {
                                    $sel_author = (int) get_post_field('post_author', $article->id);
                                }
                                if (!$sel_author) $sel_author = $current_user->ID;
                                $author_user = get_userdata($sel_author);
                                ?>
                                <div class="gti-ae-field">
                                    <label>Author <span class="required">*</span></label>
                                    <?php if (current_user_can('edit_others_posts')): ?>
                                        <select name="author_id" required>
                                            <?php foreach ($authors as $author_option): ?>
                                                <option value="<?php echo esc_attr($author_option->ID); ?>" <?php selected($sel_author, $author_option->ID); ?>>
                                                    <?php echo esc_html($author_option->display_name ?: $author_option->user_login); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="text" value="<?php echo esc_attr($author_user ? ($author_user->display_name ?: $author_user->user_login) : ''); ?>" disabled>
                                    <?php endif; ?>
                                </div>
                                <div class="gti-ae-field">
                                    <label>Author Email</label>
                                    <input type="email" value="<?php echo esc_attr($author_user ? $author_user->user_email : ''); ?>" disabled>
                                    <p style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Taken from the selected author's WordPress account</p>
                                </div>
                            </div>
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>
                                        <input type="checkbox" name="is_featured" value="1" <?php checked((string) gti_na_value('is_featured', $article), '1'); ?>>
                                        Set as Featured Article
                                    </label>
                                    <p style="font-size: 12px; color: #9ca3af; margin-top: 4px;">Featured articles will be highlighted on the homepage</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Excerpt -->
                    <div class="gti-ae-form-card">
                        <div class="gti-ae-card-header">
                            <h3><i class="fas fa-align-left"></i> Excerpt</h3>
                        </div>
                        <div class="gti-ae-card-body">
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Short Description</label>
                                    <textarea name="excerpt" rows="3" placeholder="Brief summary of the article (displayed in article list and social media previews)..."><?php echo esc_textarea(gti_na_value('excerpt', $article)); ?></textarea>
                                    <div class="gti-na-char-count"><span id="excerpt-count">0</span> / 300 characters</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="gti-ae-form-card">
                        <div class="gti-ae-card-header">
                            <h3><i class="fas fa-pen-fancy"></i> Content</h3>
                        </div>
                        <div class="gti-ae-card-body">
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Article Content <span class="required">*</span></label>
                                    <textarea name="content" class="gti-na-content-editor" placeholder="Write your article content here... Use double line breaks for paragraph separation."><?php echo esc_textarea(gti_na_value('content', $article)); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div class="gti-ae-form-card">
                        <div class="gti-ae-card-header">
                            <h3><i class="fas fa-tags"></i> Tags</h3>
                        </div>
                        <div class="gti-ae-card-body">
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Article Tags</label>
                                    <div style="display: flex; gap: 8px;">
                                        <input type="text" id="tag-input" placeholder="Type a tag and press Enter or comma..." style="flex: 1;">
                                        <button type="button" id="tag-add-btn" class="gti-ae-btn gti-ae-btn-cancel" style="white-space: nowrap;">
                                            <i class="fas fa-plus"></i> Add
                                        </button>
                                    </div>
                                    <input type="hidden" name="tags" id="tags-hidden" value="<?php echo esc_attr(gti_na_value('tags', $article)); ?>">
                                    <div class="gti-na-tags-wrap" id="tags-container">
                                        <!-- Tags populated by JS -->
                                    </div>
                                    <p style="font-size: 12px; color: #9ca3af; margin-top: 6px;">Press Enter or comma to add a tag. Click × to remove.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="gti-ae-form-card">
                        <div class="gti-ae-card-header">
                            <h3><i class="fas fa-image"></i> Featured Image</h3>
                        </div>
                        <div class="gti-ae-card-body">
                            <div class="gti-ae-form-grid">
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Upload Featured Image</label>
                                    <input type="hidden" name="remove_featured_image" id="na-remove-flag" value="">
                                    <div class="gti-na-featured-upload" id="na-upload-area"<?php echo $existing_thumb ? ' style="display:none;"' : ''; ?>>
                                        <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #9ca3af; margin-bottom: 8px;"></i>
                                        <p style="color: #6b7280; font-size: 13px; margin: 0;">Drag & drop image here or click to browse</p>
                                        <p style="color: #d1d5db; font-size: 11px; margin: 6px 0 0;">JPG, PNG, WebP — Max 5MB</p>
                                        <input type="file" name="featured_image" id="na-file-input" accept="image/*" style="display: none;">
                                    </div>
                                    <div class="gti-na-featured-preview" id="na-preview"<?php echo $existing_thumb ? ' style="display:block;"' : ''; ?>>
                                        <img id="na-preview-img" src="<?php echo esc_url($existing_thumb); ?>" alt="Preview">
                                        <button type="button" class="gti-na-featured-remove" id="na-remove-img">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Navigation -->
                    <div class="gti-ae-form-nav">
                        <div></div>
                        <div class="gti-ae-form-nav-right">
                            <a href="<?php echo esc_url(gti_dashboard_url('news-articles')); ?>" class="gti-ae-btn gti-ae-btn-cancel">Cancel</a>
                            <button type="submit" name="status" value="draft" class="gti-ae-btn gti-ae-btn-draft">
                                <i class="fas fa-save"></i> Save as Draft
                            </button>
                            <button type="submit" name="status" value="published" class="gti-ae-btn gti-ae-btn-submit">
                                <i class="fas fa-check"></i> <?php echo $editing_id ? 'Update &amp; Publish' : 'Publish Article'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // ── Sidebar collapse ──
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar = document.getElementById('gti-sidebar');
        var mainEl = document.querySelector('.gti-main');

        if (collapseBtn && sidebar) {
            if (localStorage.getItem('gti-sidebar-collapsed') === 'true') {
                sidebar.classList.add('collapsed');
                if (mainEl) mainEl.classList.add('collapsed');
            }
            collapseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                sidebar.classList.toggle('collapsed');
                if (mainEl) mainEl.classList.toggle('collapsed');
                localStorage.setItem('gti-sidebar-collapsed', sidebar.classList.contains('collapsed'));
            });
        }

        // ── Sidebar dropdown toggle ──
        document.querySelectorAll('[data-toggle="dropdown"]').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                var group = this.closest('.gti-has-children');
                if (group) group.classList.toggle('open');
            });
        });

        // ── Excerpt character counter ──
        var excerptField = document.querySelector('textarea[name="excerpt"]');
        var excerptCount = document.getElementById('excerpt-count');
        if (excerptField && excerptCount) {
            function updateExcerptCount() {
                excerptCount.textContent = excerptField.value.length;
                excerptCount.style.color = excerptField.value.length > 300 ? '#ef4444' : '';
            }
            excerptField.addEventListener('input', updateExcerptCount);
            updateExcerptCount();
        }

        // ── Tags Manager ──
        var tagInput = document.getElementById('tag-input');
        var tagAddBtn = document.getElementById('tag-add-btn');
        var tagsHidden = document.getElementById('tags-hidden');
        var tagsContainer = document.getElementById('tags-container');
        var tags = [];

        // Load existing tags from hidden field
        var existingTags = (tagsHidden.value || '').split(',').map(function(t) { return t.trim(); }).filter(Boolean);
        existingTags.forEach(function(tag) { addTag(tag); });

        function addTag(text) {
            text = text.trim();
            if (!text || tags.indexOf(text) !== -1) return;
            tags.push(text);
            renderTags();
            tagsHidden.value = tags.join(',');
        }

        function removeTag(index) {
            tags.splice(index, 1);
            renderTags();
            tagsHidden.value = tags.join(',');
        }

        function renderTags() {
            tagsContainer.innerHTML = tags.map(function(tag, i) {
                return '<span class="gti-na-tag">' + tag + '<button type="button" class="gti-na-tag-remove" data-index="' + i + '">&times;</button></span>';
            }).join('');
        }

        tagInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                var val = this.value.replace(/,/g, '').trim();
                if (val) { addTag(val); this.value = ''; }
            }
        });

        tagAddBtn.addEventListener('click', function() {
            var val = tagInput.value.replace(/,/g, '').trim();
            if (val) { addTag(val); tagInput.value = ''; }
        });

        tagsContainer.addEventListener('click', function(e) {
            var btn = e.target.closest('.gti-na-tag-remove');
            if (btn) removeTag(parseInt(btn.getAttribute('data-index'), 10));
        });

        // ── Featured Image Upload ──
        var uploadArea = document.getElementById('na-upload-area');
        var fileInput = document.getElementById('na-file-input');
        var preview = document.getElementById('na-preview');
        var previewImg = document.getElementById('na-preview-img');
        var removeBtn = document.getElementById('na-remove-img');

        if (uploadArea && fileInput) {
            uploadArea.addEventListener('click', function() { fileInput.click(); });

            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#F5A623';
                uploadArea.style.background = '#fffbeb';
            });

            uploadArea.addEventListener('dragleave', function() {
                uploadArea.style.borderColor = '#d1d5db';
                uploadArea.style.background = '#fafafa';
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.style.borderColor = '#d1d5db';
                uploadArea.style.background = '#fafafa';
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    showPreview(e.dataTransfer.files[0]);
                }
            });

            fileInput.addEventListener('change', function() {
                if (this.files.length) showPreview(this.files[0]);
            });
        }

        function showPreview(file) {
            if (file && file.type.startsWith('image/')) {
                if (file.size > 5 * 1024 * 1024) {
                    alert('File too large. Maximum size is 5MB.');
                    return;
                }
                var reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    preview.style.display = 'block';
                    uploadArea.style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                fileInput.value = '';
                previewImg.src = '';
                preview.style.display = 'none';
                uploadArea.style.display = '';
                // Tells the server to detach the stored featured image.
                var removeFlag = document.getElementById('na-remove-flag');
                if (removeFlag) removeFlag.value = '1';
            });
        }

        // Choosing a new file cancels a pending removal.
        if (fileInput) {
            fileInput.addEventListener('change', function() {
                var removeFlag = document.getElementById('na-remove-flag');
                if (removeFlag) removeFlag.value = '';
            });
        }
    });
    </script>
</body>
</html>
