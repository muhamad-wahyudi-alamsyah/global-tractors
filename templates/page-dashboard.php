<?php
/**
 * Template: Dashboard Home (/dashboard)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

gti_dashboard_open( array(
    'page'     => 'dashboard',
    'title'    => 'Dashboard',
    'subtitle' => 'Overview of activity across GTI 📊',
    'cap'      => 'gti_access',
    'js'       => array( 'dashboard' ),
) );
?>


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

<?php
gti_dashboard_close(  );
