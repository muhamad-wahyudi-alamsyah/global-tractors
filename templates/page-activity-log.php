<?php
/**
 * Template: Activity Log (/dashboard/activity-log)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

// Restored during the layout refactor: the shared header renders the user
// chrome, but this page still reads the record itself.
$current_user = wp_get_current_user();

// The activity log table ships in two historic shapes; this creates or patches it.
global $wpdb;
gti_ensure_activity_log_table();
$activity_table = gti_activity_log_table();

// Filters
$search        = isset($_GET['search'])      ? sanitize_text_field($_GET['search'])      : '';
$action_filter = isset($_GET['action_type']) ? sanitize_text_field($_GET['action_type']) : '';
$date_from     = isset($_GET['date_from'])   ? sanitize_text_field($_GET['date_from'])   : '';
$date_to       = isset($_GET['date_to'])     ? sanitize_text_field($_GET['date_to'])     : '';
$user_filter   = isset($_GET['user_id'])     ? (int) $_GET['user_id']                    : 0;

// Pagination — 'page_num' is used instead of 'paged' because WordPress reserves 'paged'
// Filters carried across pagination links.
$query_params = array_filter( array(
    'gti_page'    => 'activity-log',
    'search'      => $search,
    'action_type' => $action_filter,
    'date_from'   => $date_from,
    'date_to'     => $date_to,
    'user_id'     => $user_filter ?: '',
) );

$paged    = isset($_GET['page_num']) ? max(1, intval($_GET['page_num'])) : 1;
$per_page = 15;
$offset   = ($paged - 1) * $per_page;

// Build query
$where  = "WHERE 1=1";
$params = array();

if ($search) {
    $where .= " AND (al.description LIKE %s OR al.action LIKE %s OR al.entity_type LIKE %s OR u.display_name LIKE %s OR u.user_login LIKE %s)";
    $search_like = '%' . $wpdb->esc_like($search) . '%';
    $params = array_merge($params, array($search_like, $search_like, $search_like, $search_like, $search_like));
}

if ($action_filter) {
    $where .= " AND al.action = %s";
    $params[] = $action_filter;
}

if ($user_filter) {
    $where   .= " AND al.user_id = %d";
    $params[] = $user_filter;
}
if ($date_from) {
    $where .= " AND al.created_at >= %s";
    $params[] = $date_from . ' 00:00:00';
}

if ($date_to) {
    $where .= " AND al.created_at <= %s";
    $params[] = $date_to . ' 23:59:59';
}

$join = "FROM {$activity_table} al LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID";

// Get total count
$count_query = "SELECT COUNT(*) {$join} {$where}";
$total       = !empty($params) ? (int) $wpdb->get_var($wpdb->prepare($count_query, $params)) : (int) $wpdb->get_var($count_query);
$total_pages = (int) ceil($total / $per_page);

// Get activities
$list_query = "SELECT al.*, u.display_name, u.user_login, u.user_email
               {$join}
               {$where}
               ORDER BY al.created_at DESC
               LIMIT %d OFFSET %d";
$activities = $wpdb->get_results($wpdb->prepare(
    $list_query,
    array_merge($params, array($per_page, $offset))
));

// Get unique actions for filter
$action_types = $wpdb->get_col("SELECT DISTINCT action FROM {$activity_table} WHERE action <> '' ORDER BY action");

// Status counts
$total_activities = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$activity_table}");
$today_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$activity_table} WHERE created_at >= %s AND created_at <= %s",
    current_time('Y-m-d') . ' 00:00:00',
    current_time('Y-m-d') . ' 23:59:59'
));
$week_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$activity_table} WHERE created_at >= %s",
    date('Y-m-d H:i:s', strtotime(current_time('mysql') . ' -7 days'))
));
$my_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$activity_table} WHERE user_id = %d",
    $current_user->ID
));

// gti_get_action_icon() lives in inc/db/activity-log.php (R-06).

gti_dashboard_open( array(
    'page'     => 'activity-log',
    'title'    => 'Activity Log',
    'subtitle' => 'Every change, who made it, and when 📜',
    'cap'      => 'gti_manage_settings',
    'js'       => array( 'activity-log' ),
) );
?>


            <!-- Content -->
            <div class="gti-content">
                <div class="gti-content-left">

                    <!-- Statistics Cards -->
                    <div class="gti-ue-stats-row">
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon primary"><i class="fas fa-list"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">All Activities</p>
                                <p class="gti-ue-stat-value"><?php echo esc_html($total_activities); ?></p>
                            </div>
                        </div>
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon success"><i class="fas fa-calendar-day"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">Today</p>
                                <p class="gti-ue-stat-value"><?php echo esc_html($today_count); ?></p>
                            </div>
                        </div>
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon info"><i class="fas fa-calendar-week"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">Last 7 Days</p>
                                <p class="gti-ue-stat-value"><?php echo esc_html($week_count); ?></p>
                            </div>
                        </div>
                        <div class="gti-ue-stat-card">
                            <div class="gti-ue-stat-icon warning"><i class="fas fa-user"></i></div>
                            <div class="gti-ue-stat-info">
                                <p class="gti-ue-stat-label">My Activities</p>
                                <p class="gti-ue-stat-value"><?php echo esc_html($my_count); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar -->
                    <form class="gti-ue-toolbar" method="get">
                        <input type="hidden" name="gti_page" value="activity-log">
                        <div class="gti-ue-toolbar-left">
                            <div class="gti-ue-search">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" placeholder="Search activities..." value="<?php echo esc_attr($search); ?>">
                            </div>
                            <div class="gti-ue-filter">
                                <select name="action_type">
                                    <option value="">All Actions</option>
                                    <?php foreach ($action_types as $at): ?>
                                        <option value="<?php echo esc_attr($at); ?>" <?php selected($action_filter, $at); ?>><?php echo esc_html(ucwords(str_replace('_', ' ', $at))); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="gti-ue-filter">
                                <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" style="padding:9px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:13px;font-family:inherit;color:#374151;background:#fff;" title="From date">
                            </div>
                            <div class="gti-ue-filter">
                                <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" style="padding:9px 12px;border-radius:8px;border:1px solid #d1d5db;font-size:13px;font-family:inherit;color:#374151;background:#fff;" title="To date">
                            </div>
                            <div class="gti-ue-filter">
                                <select name="user_id">
                                    <option value="">All Users</option>
                                    <?php foreach (gti_activity_log_users() as $gti_log_user) : ?>
                                        <option value="<?php echo (int) $gti_log_user->ID; ?>" <?php selected($user_filter, $gti_log_user->ID); ?>>
                                            <?php echo esc_html($gti_log_user->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="gti-ue-btn-reset"><i class="fas fa-filter"></i> Apply</button>
                            <a href="<?php echo esc_url(gti_dashboard_url('activity-log')); ?>" class="gti-ue-btn-reset">
                                <i class="fas fa-rotate-right"></i> Reset
                            </a>
                            <?php if (current_user_can('gti_manage_settings')) : ?>
                                <button type="button" class="gti-ue-btn-reset" id="gti-export-log">
                                    <i class="fas fa-download"></i> Export CSV
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>

                    <!-- Table -->
                    <div class="gti-ue-table-card">
                        <?php // The table is created/patched on load, so it always renders. ?>
                            <table class="gti-ue-table">
                                <thead>
                                    <tr>
                                        <th style="width:180px;">User</th>
                                        <th style="width:170px;">Action</th>
                                        <th>Description</th>
                                        <th style="width:150px;">Entity</th>
                                        <th style="width:120px;">IP Address</th>
                                        <th style="width:160px;">Date &amp; Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($activities)): ?>
                                        <tr>
                                            <td colspan="6" style="text-align:center; padding:60px 20px;">
                                                <div class="gti-al-empty">
                                                    <i class="fas fa-inbox"></i>
                                                    <p class="title">No activities found</p>
                                                    <p class="desc">
                                                        <?php echo ($search || $action_filter || $date_from || $date_to) ? 'Try adjusting your filters' : 'No activity recorded yet'; ?>
                                                    </p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($activities as $log): ?>
                                            <?php
                                            $action_style  = gti_get_action_icon($log->action);
                                            $is_guest      = empty($log->user_id) || !$log->user_login;
                                            $display_name  = $log->display_name ?: $log->user_login ?: ($is_guest ? 'Guest / System' : 'Deleted user');
                                            $activity_date = date('M j, Y', strtotime($log->created_at));
                                            $activity_time = date('H:i:s', strtotime($log->created_at));
                                            // Rows written before this page had a description column still render text.
                                            $description   = gti_activity_row_description($log);
                                            $entity_label  = !empty($log->entity_type) ? gti_activity_entity_label($log->entity_type) : '';
                                            ?>
                                            <tr data-log-id="<?php echo (int) $log->id; ?>" style="cursor:pointer;">
                                                <td>
                                                    <div class="gti-al-user">
                                                        <?php if ($is_guest): ?>
                                                            <div class="gti-al-action-icon" style="width:32px;height:32px;border-radius:50%;background:#f3f4f6;color:#9ca3af;"><i class="fas fa-user-secret"></i></div>
                                                        <?php else: ?>
                                                            <?php echo get_avatar($log->user_id, 32); ?>
                                                        <?php endif; ?>
                                                        <div class="gti-al-user-info">
                                                            <strong><?php echo esc_html($display_name); ?></strong>
                                                            <small><?php echo $log->user_login ? '@' . esc_html($log->user_login) : 'not signed in'; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="gti-al-action">
                                                        <div class="gti-al-action-icon" style="background:<?php echo esc_attr($action_style['bg']); ?>; color:<?php echo esc_attr($action_style['color']); ?>;">
                                                            <i class="fas <?php echo esc_attr($action_style['icon']); ?>"></i>
                                                        </div>
                                                        <span class="gti-al-action-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $log->action))); ?></span>
                                                    </div>
                                                </td>
                                                <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo esc_attr($description); ?>">
                                                    <?php echo esc_html($description ?: '-'); ?>
                                                </td>
                                                <td>
                                                    <?php if ($entity_label): ?>
                                                        <span class="gti-al-ip" style="background:#eef2ff;color:#4338ca;">
                                                            <?php echo esc_html($entity_label); ?><?php echo !empty($log->entity_id) ? ' #' . (int) $log->entity_id : ''; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="color:#9ca3af;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($log->ip_address)): ?>
                                                        <span class="gti-al-ip"><i class="fas fa-globe" style="font-size:10px;"></i> <?php echo esc_html($log->ip_address); ?></span>
                                                    <?php else: ?>
                                                        <span style="color:#9ca3af;">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="font-size:14px; color:#1a1f36; font-weight:500;"><?php echo esc_html($activity_date); ?></div>
                                                    <div style="font-size:12px; color:#9ca3af;"><?php echo esc_html($activity_time); ?></div>
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
                                    'base_url' => gti_dashboard_url( 'activity-log' ),
                                    'params'   => $query_params,
                                ) );
                                ?>
                                </div>
                            <?php endif; ?>
                    </div>

                </div>
            </div>

            <?php
            // Detail drawer — clicking a row shows the stored `details` JSON in a
            // readable form, including a before/after pair for status changes
            // (PRD §6.13 gap 2).
            ?>
            <div class="gti-drawer" id="detailDrawer">
                <div class="gti-drawer-header">
                    <div class="gti-drawer-header-left">
                        <h2 data-field="action">Activity</h2>
                    </div>
                    <button type="button" class="gti-drawer-close" onclick="GTI.ui.drawer.close()" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="gti-drawer-body">
                    <div class="gti-drawer-section">
                        <div class="gti-drawer-section-title"><i class="fas fa-info-circle"></i> Entry</div>
                        <div class="gti-drawer-row">
                            <span class="gti-drawer-label">User</span>
                            <span class="gti-drawer-value" data-field="actor">&mdash;</span>
                        </div>
                        <div class="gti-drawer-row">
                            <span class="gti-drawer-label">Entity</span>
                            <span class="gti-drawer-value" data-field="entity">&mdash;</span>
                        </div>
                        <div class="gti-drawer-row">
                            <span class="gti-drawer-label">When</span>
                            <span class="gti-drawer-value" data-field="when">&mdash;</span>
                        </div>
                        <div class="gti-drawer-row">
                            <span class="gti-drawer-label">IP</span>
                            <span class="gti-drawer-value" data-field="ip">&mdash;</span>
                        </div>
                        <div class="gti-drawer-row gti-drawer-row-stacked">
                            <span class="gti-drawer-label">Description</span>
                            <span class="gti-drawer-value is-message" data-field="description">&mdash;</span>
                        </div>
                    </div>
                    <div class="gti-drawer-section">
                        <div class="gti-drawer-section-title"><i class="fas fa-code"></i> Details</div>
                        <div data-field="details"><p class="gti-drawer-empty">Pilih satu baris.</p></div>
                    </div>
                </div>
            </div>

<?php
gti_dashboard_close(  );
