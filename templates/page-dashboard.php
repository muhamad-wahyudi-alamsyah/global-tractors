<?php
/**
 * Template: Dashboard Home (/dashboard)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;
gti_require_login();

$current_user = wp_get_current_user();
$user_name = $current_user->display_name ?: $current_user->user_login;
$user_avatar = get_avatar_url($current_user->ID, ['size' => 80]);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo GTI_CHILD_URL; ?>/assets/css/dashboard.css">
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
                    <a href="<?php echo esc_url(gti_dashboard_url()); ?>" class="gti-nav-item active">
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
                    <a href="<?php echo esc_url(gti_dashboard_url('news-articles')); ?>" class="gti-nav-item"><i class="fas fa-newspaper"></i><span>News &amp; Articles</span></a>
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
                        <h1 class="gti-page-title">Dashboard</h1>
                        <p class="gti-welcome">Welcome back, Admin! <span>&#128075;</span></p>
                    </div>
                </div>
                <div class="gti-header-right">
                    <div class="gti-date-filter">
                        <i class="fas fa-calendar"></i>
                        <span>May 1, 2024 - May 31, 2024</span>
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
                <!-- Stats Row 1 -->
                <div class="gti-stats-grid">
                    <div class="gti-stat-card">
                        <div class="gti-stat-content">
                            <div class="gti-stat-icon"><i class="fas fa-truck"></i></div>
                            <div class="gti-stat-info">
                                <p class="gti-stat-title">Used Equipment</p>
                                <p class="gti-stat-value">148</p>
                                <p class="gti-stat-label">Total Unit</p>
                            </div>
                        </div>
                        <div class="gti-stat-footer">
                            <a href="#">View More</a>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="gti-stat-card">
                        <div class="gti-stat-content">
                            <div class="gti-stat-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="gti-stat-info">
                                <p class="gti-stat-title">Rental Equipment</p>
                                <p class="gti-stat-value">86</p>
                                <p class="gti-stat-label">Total Unit</p>
                            </div>
                        </div>
                        <div class="gti-stat-footer">
                            <a href="#">View More</a>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="gti-stat-card">
                        <div class="gti-stat-content">
                            <div class="gti-stat-icon"><i class="fas fa-cogs"></i></div>
                            <div class="gti-stat-info">
                                <p class="gti-stat-title">Spare Parts</p>
                                <p class="gti-stat-value">256</p>
                                <p class="gti-stat-label">Total Unit</p>
                            </div>
                        </div>
                        <div class="gti-stat-footer">
                            <a href="#">View More</a>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="gti-stat-card">
                        <div class="gti-stat-content">
                            <div class="gti-stat-icon"><i class="fas fa-users"></i></div>
                            <div class="gti-stat-info">
                                <p class="gti-stat-title">Customers</p>
                                <p class="gti-stat-value">120</p>
                                <p class="gti-stat-label">Total Customer</p>
                            </div>
                        </div>
                        <div class="gti-stat-footer">
                            <a href="#">View More</a>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                    <div class="gti-stat-card">
                        <div class="gti-stat-content">
                            <div class="gti-stat-icon"><i class="fas fa-file-invoice"></i></div>
                            <div class="gti-stat-info">
                                <p class="gti-stat-title">Request</p>
                                <p class="gti-stat-value">42</p>
                                <p class="gti-stat-label">Total Request</p>
                            </div>
                        </div>
                        <div class="gti-stat-footer">
                            <a href="#">View More</a>
                            <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </div>

                <!-- Stats Row 2 -->
                <div class="gti-stats-row">
                    <div class="gti-stat-card">
                        <div class="gti-stat-icon"><i class="fas fa-file-alt"></i></div>
                        <div class="gti-stat-info">
                            <p>Request Quotation</p>
                            <strong>31</strong>
                            <span>New Request</span>
                        </div>
                        <div class="gti-stat-growth">
                            <b>12%</b>
                            <span>vs last month</span>
                        </div>
                    </div>
                    <div class="gti-stat-card">
                        <div class="gti-stat-icon"><i class="fas fa-envelope"></i></div>
                        <div class="gti-stat-info">
                            <p>Contact Messages</p>
                            <strong>23</strong>
                            <span>New Messages</span>
                        </div>
                        <div class="gti-stat-growth">
                            <b>8%</b>
                            <span>vs last month</span>
                        </div>
                    </div>
                    <div class="gti-stat-card">
                        <div class="gti-stat-icon"><i class="fas fa-newspaper"></i></div>
                        <div class="gti-stat-info">
                            <p>News &amp; Articles</p>
                            <strong>12</strong>
                            <span>Published</span>
                        </div>
                        <div class="gti-stat-growth">
                            <b>4%</b>
                            <span>vs last month</span>
                        </div>
                    </div>
                    <div class="gti-stat-card">
                        <div class="gti-stat-icon"><i class="fas fa-globe"></i></div>
                        <div class="gti-stat-info">
                            <p>Website Visitors</p>
                            <strong>12,540</strong>
                            <span>Total Visitors</span>
                        </div>
                        <div class="gti-stat-growth">
                            <b>15%</b>
                            <span>vs last month</span>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="gti-charts-row">
                    <div class="gti-card gti-chart-card">
                        <div class="gti-card-header">
                            <h3>Website Visitors</h3>
                            <div class="gti-dropdown"><span>Last 30 Days</span> <i class="fas fa-chevron-down"></i></div>
                        </div>
                        <div class="gti-card-body"><canvas id="visitorsChart"></canvas></div>
                    </div>
                    <div class="gti-card gti-chart-card">
                        <div class="gti-card-header">
                            <h3>Request Overview</h3>
                            <div class="gti-dropdown"><span>Last 30 Days</span> <i class="fas fa-chevron-down"></i></div>
                        </div>
                        <div class="gti-card-body gti-donut-wrapper">
                            <div class="gti-donut-chart"><canvas id="requestChart"></canvas></div>
                            <div class="gti-donut-legend">
                                <div class="gti-legend-item"><span class="gti-legend-dot" style="background:#F5A623"></span><span class="gti-legend-label">Request Equipment</span><span class="gti-legend-value">18 (22%)</span></div>
                                <div class="gti-legend-item"><span class="gti-legend-dot" style="background:#1a1f36"></span><span class="gti-legend-label">Request Quotation</span><span class="gti-legend-value">31 (38%)</span></div>
                                <div class="gti-legend-item"><span class="gti-legend-dot" style="background:#6b7280"></span><span class="gti-legend-label">Sell Equipment</span><span class="gti-legend-value">14 (17%)</span></div>
                                <div class="gti-legend-item"><span class="gti-legend-dot" style="background:#d1d5db"></span><span class="gti-legend-label">Contact Messages</span><span class="gti-legend-value">19 (23%)</span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="gti-bottom-row">
                    <!-- Latest Activities -->
                    <div class="gti-card">
                        <div class="gti-card-header"><h3>Latest Activities</h3></div>
                        <div class="gti-card-body">
                            <div class="gti-activity-list">
                                <div class="gti-activity-item">
                                    <div class="gti-activity-icon orange"><i class="fas fa-file-alt"></i></div>
                                    <div class="gti-activity-info"><p><strong>New request equipment from PT. Bumi Karya</strong></p><small>REQ-240531-001</small></div>
                                    <div class="gti-activity-time">31 May 2024, 10:32 AM</div>
                                </div>
                                <div class="gti-activity-item">
                                    <div class="gti-activity-icon blue"><i class="fas fa-clipboard-list"></i></div>
                                    <div class="gti-activity-info"><p><strong>New quotation request for KOMATSU PC200-8</strong></p><small>RFQ-240531-002</small></div>
                                    <div class="gti-activity-time">31 May 2024, 10:15 AM</div>
                                </div>
                                <div class="gti-activity-item">
                                    <div class="gti-activity-icon green"><i class="fas fa-handshake"></i></div>
                                    <div class="gti-activity-info"><p><strong>New sell equipment submission from CV. Mandiri</strong></p><small>SELL-240531-001</small></div>
                                    <div class="gti-activity-time">31 May 2024, 09:48 AM</div>
                                </div>
                                <div class="gti-activity-item">
                                    <div class="gti-activity-icon purple"><i class="fas fa-envelope"></i></div>
                                    <div class="gti-activity-info"><p><strong>New message from Andi Wijaya</strong></p><small>Contact Message</small></div>
                                    <div class="gti-activity-time">31 May 2024, 09:20 AM</div>
                                </div>
                                <div class="gti-activity-item">
                                    <div class="gti-activity-icon gray"><i class="fas fa-newspaper"></i></div>
                                    <div class="gti-activity-info"><p><strong>Artikel "Tips Merawat Excavator" telah dipublish</strong></p></div>
                                    <div class="gti-activity-time">31 May 2024, 08:55 AM</div>
                                </div>
                            </div>
                            <div class="gti-card-footer"><a href="#">View All Activities <i class="fas fa-arrow-right"></i></a></div>
                        </div>
                    </div>

                    <!-- Pending Approval -->
                    <div class="gti-card">
                        <div class="gti-card-header"><h3>Pending Approval</h3></div>
                        <div class="gti-card-body">
                            <div class="gti-pending-list">
                                <div class="gti-pending-item">
                                    <div class="gti-pending-thumb orange"><i class="fas fa-truck"></i></div>
                                    <div class="gti-pending-info"><p><strong>Sell Equipment Submission</strong></p><p class="detail">KOMATSU PC200-8 - CV. Mandiri</p><small>Submitted on 31 May 2024</small></div>
                                    <button class="gti-btn-review">Review</button>
                                </div>
                                <div class="gti-pending-item">
                                    <div class="gti-pending-thumb blue"><i class="fas fa-clipboard-list"></i></div>
                                    <div class="gti-pending-info"><p><strong>Request Quotation</strong></p><p class="detail">CAT 320D - PT. Karya Indah</p><small>Submitted on 31 May 2024</small></div>
                                    <button class="gti-btn-review">Review</button>
                                </div>
                                <div class="gti-pending-item">
                                    <div class="gti-pending-thumb gray"><i class="fas fa-user-plus"></i></div>
                                    <div class="gti-pending-info"><p><strong>New User Registration</strong></p><p class="detail">Sales - Budi Santoso</p><small>Requested on 31 May 2024</small></div>
                                    <button class="gti-btn-review">Review</button>
                                </div>
                            </div>
                            <div class="gti-card-footer"><a href="#">View All Pending <i class="fas fa-arrow-right"></i></a></div>
                        </div>
                    </div>

                    <!-- Quick Summary -->
                    <div class="gti-card">
                        <div class="gti-card-header"><h3>Quick Summary</h3></div>
                        <div class="gti-card-body">
                            <div class="gti-summary-list">
                                <div class="gti-summary-item"><span>Total Products</span><strong>520</strong></div>
                                <div class="gti-summary-item"><span>Active Rental</span><strong>25</strong></div>
                                <div class="gti-summary-item warning"><span>Low Stock Parts</span><strong class="text-warning">17</strong></div>
                                <div class="gti-summary-item"><span>Total Customers</span><strong>236</strong></div>
                                <div class="gti-summary-item"><span>Total Users</span><strong>18</strong></div>
                                <div class="gti-summary-item"><span>Total Articles</span><strong>12</strong></div>
                            </div>
                            <button class="gti-btn-generate"><i class="fas fa-file-pdf"></i> Generate Report</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="<?php echo GTI_CHILD_URL; ?>/assets/js/dashboard.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Collapse Menu
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

        // Dropdown Toggle
        document.querySelectorAll('[data-toggle="dropdown"]').forEach(function(toggle) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                var group = this.closest('.gti-has-children');
                if (group) {
                    group.classList.toggle('open');
                }
            });
        });
    });
    </script>
</body>
</html>
