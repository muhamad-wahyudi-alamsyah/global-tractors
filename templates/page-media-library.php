<?php
/**
 * Template: Media Library (/dashboard/media-library)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

// Media comes from the WordPress media library — see inc/modules/media-library.php
// Filters
$search       = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$type_filter  = isset($_GET['type'])   ? sanitize_text_field($_GET['type'])   : '';
$month_filter = isset($_GET['month'])  ? sanitize_text_field($_GET['month'])  : '';

// Pagination — 'page_num' is used instead of 'paged' because WordPress reserves 'paged'
$paged    = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$per_page = 24;

$results     = gti_query_media(array(
    'search'   => $search,
    'type'     => $type_filter,
    'month'    => $month_filter,
    'per_page' => $per_page,
    'page'     => $paged,
));
$page_items  = $results['items'];
$total_items = $results['total'];
$total_pages = $results['pages'];

// Statistics
$counts          = gti_media_counts();
$total_media     = $counts['total'];
$total_images    = $counts['images'];
$total_documents = $counts['documents'];
$total_size      = $counts['size'];

// Month grouping / filter options
$unique_months = gti_media_months();
$month_counts  = gti_media_month_counts();
$is_filtered   = ($search || $type_filter || $month_filter);

$can_upload = current_user_can('upload_files');

gti_dashboard_open( array(
    'page'     => 'media-library',
    'title'    => 'Media Library',
    'subtitle' => 'Manage uploads and documents 🖼️',
    'cap'      => 'upload_files',
    'js'       => array( 'media-library' ),
) );
?>


            <!-- Content -->
            <div class="gti-content">
                <div class="gti-content-left">
                    <!-- Statistics Cards -->
                    <div class="gti-ml-stats">
                        <div class="gti-ml-stat">
                            <div class="gti-ml-stat-icon blue"><i class="fas fa-photo-video"></i></div>
                            <div class="gti-ml-stat-info">
                                <p class="gti-ml-stat-label">Total Media</p>
                                <p class="gti-ml-stat-value"><?php echo esc_html($total_media); ?></p>
                            </div>
                        </div>
                        <div class="gti-ml-stat">
                            <div class="gti-ml-stat-icon green"><i class="fas fa-image"></i></div>
                            <div class="gti-ml-stat-info">
                                <p class="gti-ml-stat-label">Images</p>
                                <p class="gti-ml-stat-value"><?php echo esc_html($total_images); ?></p>
                            </div>
                        </div>
                        <div class="gti-ml-stat">
                            <div class="gti-ml-stat-icon orange"><i class="fas fa-file-alt"></i></div>
                            <div class="gti-ml-stat-info">
                                <p class="gti-ml-stat-label">Documents</p>
                                <p class="gti-ml-stat-value"><?php echo esc_html($total_documents); ?></p>
                            </div>
                        </div>
                        <div class="gti-ml-stat">
                            <div class="gti-ml-stat-icon purple"><i class="fas fa-database"></i></div>
                            <div class="gti-ml-stat-info">
                                <p class="gti-ml-stat-label">Total Size</p>
                                <p class="gti-ml-stat-value"><?php echo esc_html(size_format($total_size)); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar -->
                    <div class="gti-ml-bulkbar" id="gti-ml-bulkbar" hidden>
                        <label class="gti-checkbox">
                            <input type="checkbox" id="gti-ml-select-all">
                            <span>Select all on this page</span>
                        </label>
                        <span class="gti-ml-bulkinfo"><strong id="gti-ml-bulkcount">0</strong> selected</span>
                        <button type="button" class="gti-btn-secondary" id="gti-ml-bulkdelete">
                            <i class="fas fa-trash"></i> Delete selected
                        </button>
                    </div>

                    <form class="gti-ml-toolbar" method="get">
                        <input type="hidden" name="gti_page" value="media-library">
                        <div class="gti-ml-toolbar-left">
                            <div class="gti-ml-search">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search files..." value="<?php echo esc_attr($search); ?>">
                            </div>
                            <div class="gti-ml-filter">
                                <select name="type">
                                    <option value="">All Types</option>
                                    <option value="image" <?php selected($type_filter, 'image'); ?>>Images</option>
                                    <option value="document" <?php selected($type_filter, 'document'); ?>>Documents</option>
                                </select>
                            </div>
                            <div class="gti-ml-filter">
                                <select name="month">
                                    <option value="">All Months</option>
                                    <?php foreach ($unique_months as $mk => $ml): ?>
                                        <option value="<?php echo esc_attr($mk); ?>" <?php selected($month_filter, $mk); ?>><?php echo esc_html($ml); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <a href="<?php echo esc_url(gti_dashboard_url('media-library')); ?>" class="gti-ml-btn-reset">
                                <i class="fas fa-rotate-right"></i> Reset
                            </a>
                        </div>
                        <div class="gti-ml-toolbar-right">
                            <div class="gti-ml-view-toggle">
                                <button type="button" class="gti-ml-view-btn active" data-view="grid" title="Grid View"><i class="fas fa-th"></i></button>
                                <button type="button" class="gti-ml-view-btn" data-view="list" title="List View"><i class="fas fa-list"></i></button>
                            </div>
                            <?php if ($can_upload): ?>
                            <button type="button" class="gti-ml-upload-btn" id="uploadMediaBtn">
                                <i class="fas fa-cloud-upload-alt"></i> Upload File
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Grid View -->
                    <div class="gti-ml-grid" id="mediaGrid">
                        <?php if (empty($page_items)): ?>
                            <div class="gti-ml-empty" style="grid-column: 1/-1;">
                                <i class="fas fa-photo-video"></i>
                                <h3>No media found</h3>
                                <p><?php echo $is_filtered ? 'Try adjusting your filters' : 'No files uploaded yet'; ?></p>
                            </div>
                        <?php else: ?>
                            <?php
                            $current_month = '';
                            foreach ($page_items as $item):
                                if ($item['month'] !== $current_month && !$is_filtered):
                                    $current_month = $item['month'];
                                    ?>
                                    <div class="gti-ml-month-header" style="grid-column: 1/-1;">
                                        <h3><?php echo esc_html($item['month_label']); ?></h3>
                                        <span class="gti-ml-month-count"><?php echo (int) ($month_counts[$current_month] ?? 0); ?> files</span>
                                        <div class="gti-ml-month-line"></div>
                                    </div>
                                <?php endif; ?>
                                <div class="gti-ml-card" data-media-id="<?php echo esc_attr($item['id']); ?>"
                                     data-media='<?php echo esc_attr(json_encode($item)); ?>'
                                     onclick="openMediaDetail(this)">
                                    <div class="gti-ml-card-thumb">
                                        <?php if ($item['type'] === 'image'): ?>
                                            <img src="<?php echo esc_url($item['thumb'] ?: $item['url']); ?>" alt="<?php echo esc_attr($item['alt'] ?: $item['filename']); ?>" loading="lazy">
                                        <?php else: ?>
                                            <i class="fas fa-file-alt gti-ml-doc-icon"></i>
                                        <?php endif; ?>
                                        <label class="gti-ml-card-check-wrap" onclick="event.stopPropagation();">
                                            <input type="checkbox" class="gti-ml-card-check"
                                                   value="<?php echo (int) $item['id']; ?>"
                                                   aria-label="Select <?php echo esc_attr($item['filename']); ?>">
                                        </label>
                                    </div>
                                    <div class="gti-ml-card-info">
                                        <div class="gti-ml-card-name" title="<?php echo esc_attr($item['filename']); ?>"><?php echo esc_html($item['filename']); ?></div>
                                        <div class="gti-ml-card-meta">
                                            <span class="gti-ml-card-date"><?php echo esc_html($item['date_human']); ?></span>
                                            <span class="gti-ml-card-ext <?php echo $item['type'] === 'image' ? 'img' : ''; ?>"><?php echo esc_html($item['ext']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="gti-ml-pagination">
                            <div class="gti-ml-page-info">
                                Showing <?php echo esc_html(($paged - 1) * $per_page + 1); ?>-<?php echo esc_html(min($paged * $per_page, $total_items)); ?> of <?php echo esc_html($total_items); ?> files
                            </div>
                            <div class="gti-ml-page-controls">
                                <?php
                                $qp = array();
                                $qp['gti_page'] = 'media-library';
                                if ($search) $qp['search'] = $search;
                                if ($type_filter) $qp['type'] = $type_filter;
                                if ($month_filter) $qp['month'] = $month_filter;
                                $base = gti_dashboard_url('media-library');
                                ?>
                                <?php if ($paged > 1): ?>
                                    <a href="<?php echo esc_url($base . '?' . http_build_query(array_merge($qp, ['page_num' => $paged - 1]))); ?>" class="gti-ml-page-btn"><i class="fas fa-chevron-left"></i></a>
                                <?php else: ?>
                                    <button class="gti-ml-page-btn" disabled><i class="fas fa-chevron-left"></i></button>
                                <?php endif; ?>

                                <?php
                                $start_p = max(1, $paged - 2);
                                $end_p = min($total_pages, $paged + 2);
                                if ($start_p > 1): ?>
                                    <a href="<?php echo esc_url($base . '?' . http_build_query(array_merge($qp, ['page_num' => 1]))); ?>" class="gti-ml-page-btn">1</a>
                                    <?php if ($start_p > 2): ?><span style="padding:0 4px;color:#9ca3af;">...</span><?php endif; ?>
                                <?php endif; ?>
                                <?php for ($i = $start_p; $i <= $end_p; $i++): ?>
                                    <?php if ($i === $paged): ?>
                                        <button class="gti-ml-page-btn active"><?php echo $i; ?></button>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($base . '?' . http_build_query(array_merge($qp, ['page_num' => $i]))); ?>" class="gti-ml-page-btn"><?php echo $i; ?></a>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                <?php if ($end_p < $total_pages): ?>
                                    <?php if ($end_p < $total_pages - 1): ?><span style="padding:0 4px;color:#9ca3af;">...</span><?php endif; ?>
                                    <a href="<?php echo esc_url($base . '?' . http_build_query(array_merge($qp, ['page_num' => $total_pages]))); ?>" class="gti-ml-page-btn"><?php echo $total_pages; ?></a>
                                <?php endif; ?>

                                <?php if ($paged < $total_pages): ?>
                                    <a href="<?php echo esc_url($base . '?' . http_build_query(array_merge($qp, ['page_num' => $paged + 1]))); ?>" class="gti-ml-page-btn"><i class="fas fa-chevron-right"></i></a>
                                <?php else: ?>
                                    <button class="gti-ml-page-btn" disabled><i class="fas fa-chevron-right"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div><!-- /.gti-content-left -->

                <!-- Detail Drawer -->
                <div class="gti-drawer-backdrop" id="drawerBackdrop"></div>
                <div class="gti-drawer" id="mediaDetailDrawer">
                    <!-- Header -->
                    <div class="gti-drawer-header">
                        <div class="gti-drawer-header-left">
                            <h2 id="drawer-filename">File Details</h2>
                            <span class="gti-drawer-status status-new" id="drawer-type-badge">IMAGE</span>
                        </div>
                        <button type="button" class="gti-drawer-close" onclick="closeMediaDrawer()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="gti-drawer-body">
                        <!-- Preview -->
                        <div class="gti-drawer-preview" id="drawer-preview"></div>

                        <!-- File Information -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title">
                                <i class="fas fa-info-circle"></i> File Information
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">File Name</span>
                                <span class="gti-drawer-value" id="drawer-filename-val">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">File Type</span>
                                <span class="gti-drawer-value" id="drawer-filetype">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">File Size</span>
                                <span class="gti-drawer-value" id="drawer-filesize">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Upload Date</span>
                                <span class="gti-drawer-value" id="drawer-uploaded">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Dimensions</span>
                                <span class="gti-drawer-value" id="drawer-dims">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Uploaded By</span>
                                <span class="gti-drawer-value" id="drawer-uploader">-</span>
                            </div>
                            <div class="gti-drawer-row">
                                <span class="gti-drawer-label">Attached To</span>
                                <span class="gti-drawer-value" id="drawer-attached">-</span>
                            </div>
                        </div>

                        <?php
                        // B-11: gti_update_media has been registered all along with no
                        // interface behind it. Title / Alt / Caption / Description are
                        // editable here and save straight back to the attachment.
                        ?>
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title">
                                <i class="fas fa-pen"></i> Edit Details
                            </div>
                            <form id="gti-media-meta-form">
                                <input type="hidden" name="id" id="gti-media-id" value="">
                                <div class="gti-field">
                                    <label for="gti-media-title">Title</label>
                                    <input type="text" id="gti-media-title" name="title">
                                </div>
                                <div class="gti-field">
                                    <label for="gti-media-alt">Alt Text
                                        <span class="gti-field-hint">describes the image for screen readers</span>
                                    </label>
                                    <input type="text" id="gti-media-alt" name="alt">
                                </div>
                                <div class="gti-field">
                                    <label for="gti-media-caption">Caption</label>
                                    <textarea id="gti-media-caption" name="caption" rows="2"></textarea>
                                </div>
                                <div class="gti-field">
                                    <label for="gti-media-description">Description</label>
                                    <textarea id="gti-media-description" name="description" rows="3"></textarea>
                                </div>
                                <button type="submit" class="gti-btn-primary"><i class="fas fa-save"></i> Save Details</button>
                            </form>
                        </div>

                        <!-- URL Section -->
                        <div class="gti-drawer-section">
                            <div class="gti-drawer-section-title">
                                <i class="fas fa-link"></i> File URL
                            </div>
                            <div class="gti-drawer-row" style="flex-direction: column;">
                                <span class="gti-drawer-value is-link" id="drawer-url" onclick="copyUrl(this)" style="background: #f9fafb; padding: 8px 12px; border-radius: 8px; font-size: 12px; text-align: left; word-break: break-all; cursor: pointer;">-</span>
                                <small style="color: #9ca3af; font-size: 11px; margin-top: 4px;">Click to copy URL</small>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="gti-drawer-footer">
                        <a href="#" class="gti-drawer-btn gti-drawer-btn-primary" id="drawer-download" target="_blank">
                            <i class="fas fa-download"></i> Download
                        </a>
                        <button type="button" class="gti-drawer-btn" id="drawer-copy-btn" onclick="copyUrl(document.getElementById('drawer-url'))">
                            <i class="fas fa-copy"></i> Copy URL
                        </button>
                        <span class="gti-drawer-footer-spacer"></span>
                        <button type="button" class="gti-drawer-btn gti-drawer-btn-danger" id="drawer-delete">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>

<?php
gti_dashboard_close( array(
    'modals' => array( 'delete', 'upload-media' ),
) );
