<?php
/**
 * News & Articles — backed by native WordPress posts.
 *
 * The dashboard used to read/write a bespoke wp_gti_news_articles table, so
 * articles created here never appeared on the public site and posts written in
 * wp-admin never appeared in the dashboard. Everything now goes through the
 * `post` post type: categories are real terms, tags are real tags, the featured
 * image is a real attachment, and the dashboard is just another editor.
 *
 * Rows from the legacy table are imported once, on first load.
 *
 * @package global-tractors
 */

if (!defined('ABSPATH')) exit;

/** Meta keys used to carry the dashboard-only fields. */
const GTI_ARTICLE_ID_META       = '_gti_article_id';
const GTI_ARTICLE_VIEWS_META    = 'gti_article_views';
const GTI_ARTICLE_FEATURED_META = '_gti_is_featured';
const GTI_ARTICLE_LEGACY_META   = '_gti_legacy_article_row';

/**
 * Category slugs offered by the dashboard, mapped to display labels.
 */
function gti_article_categories() {
    return array(
        'company-news' => 'Company News',
        'tips'         => 'Tips & Tricks',
        'event'        => 'Event',
        'industry'     => 'Industry',
        'product'      => 'Product',
    );
}

function gti_article_category_label($slug) {
    $labels = gti_article_categories();
    if (isset($labels[$slug])) return $labels[$slug];
    $term = get_term_by('slug', $slug, 'category');
    return $term ? $term->name : ucfirst(str_replace('-', ' ', (string) $slug));
}

function gti_article_category_color($slug) {
    $colors = array(
        'company-news' => array('#dbeafe', '#1d4ed8'),
        'tips'         => array('#d1fae5', '#047857'),
        'event'        => array('#fef3c7', '#b45309'),
        'industry'     => array('#e0e7ff', '#4338ca'),
        'product'      => array('#fce7f3', '#be185d'),
    );
    return $colors[$slug] ?? array('#f3f4f6', '#374151');
}

/**
 * Term id for a dashboard category slug, creating the term when missing.
 */
function gti_article_category_term_id($slug) {
    if (!$slug) return 0;
    $term = get_term_by('slug', $slug, 'category');
    if ($term) return (int) $term->term_id;

    $labels = gti_article_categories();
    $created = wp_insert_term($labels[$slug] ?? ucfirst(str_replace('-', ' ', $slug)), 'category', array('slug' => $slug));
    return is_wp_error($created) ? 0 : (int) $created['term_id'];
}

/** dashboard status → WP post_status */
function gti_article_status_to_post($status) {
    switch ($status) {
        case 'published': return 'publish';
        case 'archived':  return 'private';
        default:          return 'draft';
    }
}

/** WP post_status → dashboard status */
function gti_article_status_from_post($status) {
    switch ($status) {
        case 'publish': return 'published';
        case 'future':  return 'published';
        case 'private': return 'archived';
        case 'trash':   return 'archived';
        default:        return 'draft';
    }
}

/**
 * Stable per-post article id (ART-yymmddNN), generated on first use.
 */
function gti_article_id_for_post($post_id, $created_at = '') {
    $existing = get_post_meta($post_id, GTI_ARTICLE_ID_META, true);
    if ($existing) return $existing;

    $stamp  = $created_at ? strtotime($created_at) : (get_post_time('U', true, $post_id) ?: time());
    $prefix = 'ART-' . date('ymd', $stamp);

    global $wpdb;
    $used = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
        GTI_ARTICLE_ID_META,
        $wpdb->esc_like($prefix) . '%'
    ));

    $article_id = $prefix . str_pad($used + 1, 2, '0', STR_PAD_LEFT);
    update_post_meta($post_id, GTI_ARTICLE_ID_META, $article_id);
    return $article_id;
}

/**
 * Normalise a WP_Post into the flat shape the dashboard templates and their
 * JavaScript already expect.
 *
 * @return stdClass
 */
function gti_article_from_post($post) {
    $post = get_post($post);
    if (!$post) return null;

    $author = get_userdata($post->post_author);

    // The dashboard shows a single category; prefer one of its own slugs.
    $terms = wp_get_post_categories($post->ID, array('fields' => 'all'));
    $known = gti_article_categories();
    $category = '';
    foreach ($terms as $term) {
        if (isset($known[$term->slug])) { $category = $term->slug; break; }
    }
    if (!$category && $terms) {
        // A post categorised in wp-admin still needs to render somewhere sensible.
        $category = $terms[0]->slug;
    }

    $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));

    $a = new stdClass();
    $a->id             = (int) $post->ID;
    $a->article_id     = gti_article_id_for_post($post->ID, $post->post_date);
    $a->title          = $post->post_title;
    $a->slug           = $post->post_name;
    $a->excerpt        = $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 40, '...');
    $a->content        = $post->post_content;
    $a->category       = $category;
    $a->category_label = $category ? gti_article_category_label($category) : '';
    $a->tags           = implode(',', $tags);
    $a->author         = $author ? ($author->display_name ?: $author->user_login) : '';
    $a->author_email   = $author ? $author->user_email : '';
    $a->featured_image = get_the_post_thumbnail_url($post->ID, 'medium') ?: '';
    $a->status         = gti_article_status_from_post($post->post_status);
    $a->views          = (int) get_post_meta($post->ID, GTI_ARTICLE_VIEWS_META, true);
    $a->slug           = $post->post_name;
    $a->is_featured    = (int) get_post_meta($post->ID, GTI_ARTICLE_FEATURED_META, true);
    $a->published_at   = ($post->post_status === 'publish') ? $post->post_date : null;
    $a->created_at     = $post->post_date;
    $a->updated_at     = $post->post_modified;
    $a->permalink      = get_permalink($post->ID);
    $a->edit_url       = gti_dashboard_url('news-articles/add') . '?id=' . $post->ID;

    return $a;
}

/**
 * Query articles for the dashboard list.
 *
 * @param array $args search, status, category, per_page, page
 * @return array{items: array, total: int, pages: int}
 */
function gti_query_articles($args = array()) {
    gti_migrate_legacy_articles();

    $args = wp_parse_args($args, array(
        'search'   => '',
        'status'   => '',
        'category' => '',
        'per_page' => 10,
        'page'     => 1,
    ));

    $query_args = array(
        'post_type'      => 'post',
        'post_status'    => $args['status']
            ? gti_article_status_to_post($args['status'])
            : array('publish', 'draft', 'pending', 'private', 'future'),
        'posts_per_page' => (int) $args['per_page'],
        'paged'          => max(1, (int) $args['page']),
        'orderby'        => 'date',
        'order'          => 'DESC',
        'ignore_sticky_posts' => true,
    );

    if ($args['search'])   $query_args['s'] = $args['search'];
    if ($args['category']) $query_args['category_name'] = $args['category'];

    $query = new WP_Query($query_args);

    $items = array();
    foreach ($query->posts as $post) {
        $article = gti_article_from_post($post);
        if ($article) $items[] = $article;
    }

    return array(
        'items' => $items,
        'total' => (int) $query->found_posts,
        'pages' => (int) $query->max_num_pages,
    );
}

/**
 * Counters for the stat cards.
 */
function gti_article_counts() {
    gti_migrate_legacy_articles();

    global $wpdb;
    $counts = wp_count_posts('post');

    $published = (int) ($counts->publish ?? 0) + (int) ($counts->future ?? 0);
    $draft     = (int) ($counts->draft ?? 0) + (int) ($counts->pending ?? 0);
    $archived  = (int) ($counts->private ?? 0);

    $views = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(meta_value + 0), 0) FROM {$wpdb->postmeta} WHERE meta_key = %s",
        GTI_ARTICLE_VIEWS_META
    ));

    return array(
        'total'     => $published + $draft + $archived,
        'published' => $published,
        'draft'     => $draft,
        'archived'  => $archived,
        'views'     => $views,
    );
}

/**
 * Category slugs that actually have articles, for the filter dropdown.
 */
function gti_article_used_categories() {
    $terms = get_terms(array('taxonomy' => 'category', 'hide_empty' => false, 'fields' => 'id=>slug'));
    if (is_wp_error($terms)) return array_keys(gti_article_categories());

    // Always offer the dashboard's own set, plus anything created in wp-admin.
    return array_values(array_unique(array_merge(array_keys(gti_article_categories()), array_values($terms))));
}

/**
 * Create or update an article.
 *
 * @param array $data title, content, excerpt, category, tags, status, is_featured,
 *                    author_id, id (0 = create), featured_image_id
 * @return int|WP_Error Post id.
 */
function gti_save_article($data) {
    $id     = (int) ($data['id'] ?? 0);
    $status = gti_article_status_to_post($data['status'] ?? 'draft');

    $postarr = array(
        'post_type'    => 'post',
        'post_title'   => wp_strip_all_tags($data['title'] ?? ''),
        'post_content' => $data['content'] ?? '',
        'post_excerpt' => $data['excerpt'] ?? '',
        'post_status'  => $status,
    );

    if (!empty($data['author_id'])) {
        $postarr['post_author'] = (int) $data['author_id'];
    }

    // An explicit slug wins; leaving it blank lets WordPress derive one from the
    // title, which is the behaviour editors expect (PRD §6.10 gap 5).
    if (!empty($data['slug'])) {
        $postarr['post_name'] = sanitize_title($data['slug']);
    }

    if ($id > 0) {
        $postarr['ID'] = $id;
        $post_id = wp_update_post($postarr, true);
    } else {
        $post_id = wp_insert_post($postarr, true);
    }

    if (is_wp_error($post_id)) return $post_id;

    // Category — one term, matching the single-select in the form.
    if (!empty($data['category'])) {
        $term_id = gti_article_category_term_id($data['category']);
        if ($term_id) wp_set_post_categories($post_id, array($term_id), false);
    }

    // Tags — comma separated in the form, real terms here.
    if (isset($data['tags'])) {
        $tags = array_filter(array_map('trim', explode(',', (string) $data['tags'])));
        wp_set_post_tags($post_id, $tags, false);
    }

    update_post_meta($post_id, GTI_ARTICLE_FEATURED_META, !empty($data['is_featured']) ? 1 : 0);

    if (!empty($data['featured_image_id'])) {
        set_post_thumbnail($post_id, (int) $data['featured_image_id']);
    } elseif (!empty($data['remove_featured_image'])) {
        delete_post_thumbnail($post_id);
    }

    if (get_post_meta($post_id, GTI_ARTICLE_VIEWS_META, true) === '') {
        update_post_meta($post_id, GTI_ARTICLE_VIEWS_META, 0);
    }
    gti_article_id_for_post($post_id);

    return $post_id;
}

/**
 * Count a public read of an article.
 *
 * Throttled to once per visitor per article per 12 hours (PRD §6.10). Without
 * that, a reload — or any bot — inflated the number, which made the figure
 * shown in the dashboard meaningless.
 *
 * @param bool $force Skip the throttle (used by the importer).
 */
function gti_article_register_view($post_id, $force = false) {
    $post_id = (int) $post_id;
    if (!$post_id) {
        return false;
    }

    if (!$force) {
        // Keyed by IP hash rather than a cookie so it still works for visitors
        // who block cookies; the hash is never reversible to an address.
        $key = 'gti_view_' . $post_id . '_' . substr(md5(gti_get_client_ip() . wp_salt()), 0, 12);
        if (get_transient($key)) {
            return false;
        }
        set_transient($key, 1, 12 * HOUR_IN_SECONDS);
    }

    $views = (int) get_post_meta($post_id, GTI_ARTICLE_VIEWS_META, true);
    update_post_meta($post_id, GTI_ARTICLE_VIEWS_META, $views + 1);

    return true;
}

add_action('wp_head', 'gti_article_count_single_view');
function gti_article_count_single_view() {
    // Staff reading their own drafts should not move the counter.
    if (!is_singular('post') || is_user_logged_in()) {
        return;
    }
    gti_article_register_view(get_queried_object_id());
}

/**
 * One-time import of wp_gti_news_articles rows into real posts.
 *
 * Without this, switching the dashboard to WP posts would hide every article
 * that was written before the switch.
 */
function gti_migrate_legacy_articles() {
    static $done = false;
    if ($done) return 0;
    $done = true;

    if (get_option('gti_articles_migrated') === 'yes') return 0;

    global $wpdb;
    $table = $wpdb->prefix . 'gti_news_articles';
    if (!gti_table_exists($table)) {
        update_option('gti_articles_migrated', 'yes', false);
        return 0;
    }

    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id ASC");
    $imported = 0;

    foreach ($rows as $row) {
        // Skip rows already imported (re-runs must not duplicate posts).
        $already = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
            GTI_ARTICLE_LEGACY_META,
            (string) $row->id
        ));
        if ($already) continue;

        $author = get_user_by('email', $row->author_email);
        if (!$author && $row->author) {
            $author = get_user_by('login', sanitize_user($row->author));
        }

        $post_id = wp_insert_post(array(
            'post_type'     => 'post',
            'post_title'    => $row->title,
            'post_name'     => $row->slug,
            'post_content'  => $row->content,
            'post_excerpt'  => $row->excerpt,
            'post_status'   => gti_article_status_to_post($row->status),
            'post_author'   => $author ? $author->ID : 1,
            'post_date'     => $row->published_at ?: $row->created_at,
        ), true);

        if (is_wp_error($post_id)) continue;

        if ($row->category) {
            $term_id = gti_article_category_term_id($row->category);
            if ($term_id) wp_set_post_categories($post_id, array($term_id), false);
        }
        if ($row->tags) {
            wp_set_post_tags($post_id, array_filter(array_map('trim', explode(',', $row->tags))), false);
        }

        update_post_meta($post_id, GTI_ARTICLE_LEGACY_META, (string) $row->id);
        update_post_meta($post_id, GTI_ARTICLE_ID_META, $row->article_id);
        update_post_meta($post_id, GTI_ARTICLE_VIEWS_META, (int) $row->views);
        update_post_meta($post_id, GTI_ARTICLE_FEATURED_META, (int) $row->is_featured);

        if ($row->featured_image) {
            $attachment_id = gti_attachment_id_from_url($row->featured_image);
            if ($attachment_id) set_post_thumbnail($post_id, $attachment_id);
        }

        $imported++;
    }

    update_option('gti_articles_migrated', 'yes', false);
    return $imported;
}

/**
 * Resolve an uploads URL back to its attachment id, if WordPress knows it.
 */
function gti_attachment_id_from_url($url) {
    if (!$url) return 0;
    $id = attachment_url_to_postid($url);
    if ($id) return (int) $id;

    // attachment_url_to_postid() misses resized URLs; retry on the original file.
    $stripped = preg_replace('/-\d+x\d+(\.[a-zA-Z0-9]+)$/', '$1', $url);
    return $stripped !== $url ? (int) attachment_url_to_postid($stripped) : 0;
}

// ── Display helpers (moved out of templates/page-news-articles.php, R-06) ───
if ( ! function_exists( 'gti_na_category_label' ) ) {
    function gti_na_category_label($cat) {
        return gti_article_category_label($cat);
    }
    function gti_na_category_color($cat) {
        return gti_article_category_color($cat);
    }
    function gti_na_status_class($status) {
        return $status === 'published' ? 'available' : ($status === 'draft' ? 'reserved' : 'sold');
    }
    function gti_na_excerpt($text, $len = 80) {
        if (!$text) return '-';
        return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '...' : $text;
    }
    function gti_na_format_views($views) {
        if ($views >= 1000) {
            return number_format($views / 1000, 1) . 'K';
        }
        return number_format($views);
    }
}

if ( ! function_exists( 'gti_na_value' ) ) {
    function gti_na_value($field, $article, $default = '') {
        if (isset($_POST[$field])) return $_POST[$field];
        if ($article && isset($article->$field)) return $article->$field;
        return $default;
    }
}
