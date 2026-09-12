<?php
/**
 * Template: News & Articles (/dashboard/news-articles)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

// Articles are native WordPress posts — see inc/modules/news-articles.php
// Filters
$search          = isset($_GET['search'])   ? sanitize_text_field($_GET['search'])   : '';
$status_filter   = isset($_GET['status'])   ? sanitize_text_field($_GET['status'])   : '';
$category_filter = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';

// Pagination — 'page_num' is used instead of 'paged' because WordPress reserves 'paged'
// Filters carried across pagination links.
$query_params = array_filter( array(
    'gti_page' => 'news-articles',
    'search'   => $search,
    'status'   => $status_filter,
    'category' => $category_filter,
) );

$paged    = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$per_page = 10;
$offset   = ($paged - 1) * $per_page;

$results = gti_query_articles(array(
    'search'   => $search,
    'status'   => $status_filter,
    'category' => $category_filter,
    'per_page' => $per_page,
    'page'     => $paged,
));

$articles    = $results['items'];
$total       = $results['total'];
$total_pages = $results['pages'];

// Status counts
$counts          = gti_article_counts();
$total_count     = $counts['total'];
$published_count = $counts['published'];
$draft_count     = $counts['draft'];
$total_views     = $counts['views'];

// Categories for filter
$categories = gti_article_used_categories();

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

gti_dashboard_open( array(
    'page'     => 'news-articles',
    'title'    => 'News & Articles',
    'subtitle' => 'Publish and manage articles 📰',
    'cap'      => 'edit_posts',
    'js'       => array( 'news-articles' ),
) );
?>


            <!-- Content -->
            <div class="gti-content">
                <div class="gti-content-left">
                <!-- Statistics Cards -->
                <div class="gti-ue-stats-row">
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon"><i class="fas fa-newspaper"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Total Articles</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($total_count); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon available"><i class="fas fa-check-circle"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Published</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($published_count); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon reserved"><i class="fas fa-file-alt"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Draft</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html($draft_count); ?></p>
                        </div>
                    </div>
                    <div class="gti-ue-stat-card">
                        <div class="gti-ue-stat-icon sold"><i class="fas fa-eye"></i></div>
                        <div class="gti-ue-stat-info">
                            <p class="gti-ue-stat-label">Total Views</p>
                            <p class="gti-ue-stat-value"><?php echo esc_html(number_format($total_views)); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Toolbar -->
                <form class="gti-ue-toolbar" method="get" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                    <input type="hidden" name="gti_page" value="news-articles">
                    <div class="gti-ue-toolbar-left" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;flex:1;">
                        <div class="gti-ue-search">
                            <i class="fas fa-search"></i>
                            <input type="text" name="search" placeholder="Search articles..." value="<?php echo esc_attr($search); ?>">
                        </div>
                        <div class="gti-ue-filter">
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat); ?>" <?php selected($category_filter, $cat); ?>><?php echo esc_html(gti_na_category_label($cat)); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="gti-ue-filter">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="published" <?php selected($status_filter, 'published'); ?>>Published</option>
                                <option value="draft" <?php selected($status_filter, 'draft'); ?>>Draft</option>
                                <option value="archived" <?php selected($status_filter, 'archived'); ?>>Archived</option>
                            </select>
                        </div>
                        <a href="<?php echo esc_url(gti_dashboard_url('news-articles')); ?>" class="gti-ue-btn-reset">
                            <i class="fas fa-rotate-right"></i> Reset
                        </a>
                    </div>
                    <a href="<?php echo esc_url(gti_dashboard_url('news-articles/add')); ?>" class="gti-btn-add">
                        <i class="fas fa-plus"></i> Add New Article
                    </a>
                </form>

                <!-- Table -->
                <div class="gti-ue-table-card">
                    <table class="gti-ue-table">
                        <thead>
                            <tr>
                                <th class="col-thumb"></th>
                                <th class="col-title">Title</th>
                                <th class="col-category">Category</th>
                                <th class="col-author">Author</th>
                                <th class="col-status">Status</th>
                                <th class="col-views">Views</th>
                                <th class="col-date">Date</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($articles)): ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 60px 20px;">
                                        <div style="color: #9ca3af;">
                                            <i class="fas fa-newspaper" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                                            <p style="font-size: 16px; font-weight: 500; margin-bottom: 8px;">No articles found</p>
                                            <p style="font-size: 14px;">
                                                <?php echo ($search || $status_filter || $category_filter) ? 'Try adjusting your filters' : 'No articles yet'; ?>
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($articles as $i => $a): ?>
                                    <?php
                                    $cat_colors = gti_na_category_color($a->category);
                                    ?>
                                    <tr data-article-id="<?php echo esc_attr($a->id); ?>" data-article='<?php echo esc_attr(json_encode($a)); ?>'>
                                        <td class="col-thumb">
                                            <div class="gti-article-thumb">
                                                <?php if ($a->featured_image): ?>
                                                    <img src="<?php echo esc_url($a->featured_image); ?>" alt="">
                                                <?php else: ?>
                                                    <i class="fas fa-file-alt"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="col-title">
                                            <div class="gti-title-cell">
                                                <strong><?php echo esc_html($a->title); ?></strong>
                                                <small><?php echo esc_html($a->article_id); ?></small>
                                            </div>
                                        </td>
                                        <td class="col-category">
                                            <span class="gti-cat-pill" style="background:<?php echo esc_attr($cat_colors[0]); ?>;color:<?php echo esc_attr($cat_colors[1]); ?>">
                                                <?php echo esc_html(gti_na_category_label($a->category)); ?>
                                            </span>
                                        </td>
                                        <td class="col-author">
                                            <strong style="color:#1a1f36"><?php echo esc_html($a->author); ?></strong>
                                        </td>
                                        <td class="col-status">
                                            <span class="gti-badge-status <?php echo esc_attr(gti_na_status_class($a->status)); ?>"><?php echo esc_html(ucfirst($a->status)); ?></span>
                                        </td>
                                        <td class="col-views">
                                            <span class="gti-views-cell">
                                                <i class="fas fa-eye"></i>
                                                <?php echo esc_html(gti_na_format_views((int)$a->views)); ?>
                                            </span>
                                        </td>
                                        <td class="col-date">
                                            <?php echo esc_html(date('M j, Y', strtotime($a->published_at ?: $a->created_at))); ?>
                                        </td>
                                        <td class="col-actions">
                                            <div class="gti-ue-action-menu">
                                                <button class="gti-ue-action-toggle" title="Actions"><i class="fas fa-ellipsis-v"></i></button>
                                                <div class="gti-ue-action-dropdown">
                                                    <button type="button" class="gti-ue-action-item" onclick='showArticleDetail(<?php echo esc_attr(json_encode($a)); ?>)'>
                                                        <i class="fas fa-eye"></i> View Details
                                                    </button>
                                                    <a href="<?php echo esc_url($a->edit_url); ?>" class="gti-ue-action-item">
                                                        <i class="fas fa-edit"></i> Edit Article
                                                    </a>
                                                    <a href="<?php echo esc_url($a->permalink); ?>" target="_blank" rel="noopener" class="gti-ue-action-item">
                                                        <i class="fas fa-external-link-alt"></i> View on Site
                                                    </a>
                                                    <button type="button" class="gti-ue-action-item" onclick="toggleArticleStatus(<?php echo esc_attr($a->id); ?>, '<?php echo esc_attr($a->status === 'published' ? 'draft' : 'published'); ?>')">
                                                        <i class="fas fa-<?php echo $a->status === 'published' ? 'eye-slash' : 'check'; ?>"></i>
                                                        <?php echo $a->status === 'published' ? 'Unpublish' : 'Publish'; ?>
                                                    </button>
                                                    <button type="button" class="gti-ue-action-item" style="color:#dc2626" onclick="deleteArticle(<?php echo esc_attr($a->id); ?>)">
                                                        <i class="fas fa-trash" style="color:#dc2626"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <?php
                        gti_render_pagination( array(
                            'total'    => $total,
                            'per_page' => $per_page,
                            'current'  => $paged,
                            'base_url' => gti_dashboard_url( 'news-articles' ),
                            'params'   => $query_params,
                        ) );
                        ?>
                        </div>
                    <?php endif; ?>
                </div>
                </div><!-- /.gti-content-left -->

                <!-- Detail Drawer (In-Flow ≥1600px / Fixed <1600px) -->
                <div class="gti-drawer-backdrop" id="drawerBackdrop"></div>
                <div class="gti-drawer" id="articleDetailDrawer">
                    <div class="gti-drawer-header">
                        <div class="gti-drawer-header-left">
                            <h2 id="drawer-title">-</h2>
                            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:4px;">
                                <span class="gti-drawer-status" id="drawer-status-badge">Published</span>
                                <span class="gti-drawer-cat-badge" id="drawer-cat-badge">-</span>
                            </div>
                        </div>
                        <button type="button" class="gti-drawer-close" onclick="closeDetailDrawer()"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="gti-drawer-body">
                        <!-- Featured Image -->
                        <div class="gti-drawer-featured-img" id="drawer-featured-img">
                            <i class="fas fa-image"></i>
                        </div>

                        <!-- Article Info -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-info-circle"></i> Article Information</div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Article ID</span><span class="gti-drawer-value" id="drawer-article-id">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Author</span><span class="gti-drawer-value" id="drawer-author">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Published</span><span class="gti-drawer-value" id="drawer-published">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Created</span><span class="gti-drawer-value" id="drawer-created">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Views</span><span class="gti-drawer-value" id="drawer-views">-</span></div>
                            <div class="gti-drawer-row"><span class="gti-drawer-label">Featured</span><span class="gti-drawer-value" id="drawer-featured">-</span></div>
                        </div>

                        <!-- Excerpt / Content Preview -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-align-left"></i> Content Preview</div>
                            <div class="gti-drawer-content-preview" id="drawer-excerpt">-</div>
                        </div>

                        <!-- Tags -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-tags"></i> Tags</div>
                            <div class="gti-drawer-tags" id="drawer-tags">
                                <span style="color:#9ca3af;font-size:13px;">No tags</span>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title"><i class="fas fa-clock"></i> Activity</div>
                            <div class="gti-drawer-timeline" id="drawer-timeline">
                                <!-- Populated by JS -->
                            </div>
                        </div>
                    </div>
                    <div class="gti-drawer-footer">
                        <a href="#" class="gti-drawer-btn gti-drawer-btn-primary" id="drawer-btn-edit"><i class="fas fa-edit"></i> Edit</a>
                        <a href="#" class="gti-drawer-btn" id="drawer-btn-preview"><i class="fas fa-external-link-alt"></i> View on Site</a>
                        <span class="gti-drawer-footer-spacer"></span>
                        <button type="button" class="gti-drawer-btn" id="drawer-btn-toggle-status" onclick="toggleStatusFromDrawer()">
                            <i class="fas fa-sync-alt"></i> <span id="drawer-btn-toggle-text">Unpublish</span>
                        </button>
                    </div>
                </div>
            </div>

<?php
gti_dashboard_close( array(
    'modals' => array( 'delete' ),
) );
