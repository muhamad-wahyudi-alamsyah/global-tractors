<?php
/**
 * Template: Add New Article (/dashboard/news-articles/add)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

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
        // "Save as Draft" / "Publish" are explicit overrides; otherwise the
        // Status dropdown decides. Both used to be name="status", so whichever
        // button was clicked silently overwrote the dropdown selection.
        $status = sanitize_text_field($_POST['status'] ?? 'draft');
        $action = sanitize_text_field($_POST['submit_action'] ?? '');
        if ($action !== '') {
            $status = $action;
        }

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
                    'slug'              => sanitize_title($_POST['slug'] ?? ''),
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
// gti_na_value() lives in inc/modules/news-articles.php (R-06).

$page_title  = $editing_id ? 'Edit Article' : 'Add New Article';
$page_intro  = $editing_id ? 'Update an existing news or article post' : 'Create a new news or article post';
$existing_thumb = $article ? $article->featured_image : '';

gti_dashboard_open( array(
    'page'     => 'add-news-article',
    'title'    => 'Add Article',
    'cap'      => 'edit_posts',
) );
?>


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
                                <div class="gti-ae-field gti-ae-field-full">
                                    <label>Slug <span class="gti-field-hint">leave blank to derive it from the title</span></label>
                                    <input type="text" name="slug" placeholder="article-url-slug" value="<?php echo esc_attr(gti_na_value('slug', $article)); ?>">
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
                                    <?php
                                    // TinyMCE rather than a plain textarea, so formatting written
                                    // here survives and matches wp-admin (PRD §6.10 gap 2).
                                    wp_editor(
                                        gti_na_value( 'content', $article ),
                                        'gti-article-content',
                                        array(
                                            'textarea_name' => 'content',
                                            'textarea_rows' => 18,
                                            'media_buttons' => current_user_can( 'upload_files' ),
                                            'teeny'         => false,
                                            'quicktags'     => true,
                                        )
                                    );
                                    ?>
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
                            <button type="submit" name="submit_action" value="draft" class="gti-ae-btn gti-ae-btn-draft">
                                <i class="fas fa-save"></i> Save as Draft
                            </button>
                            <button type="submit" name="submit_action" value="published" class="gti-ae-btn gti-ae-btn-submit">
                                <i class="fas fa-check"></i> <?php echo $editing_id ? 'Update &amp; Publish' : 'Publish Article'; ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

<?php
gti_dashboard_close(  );
