<?php
/**
 * Contact Messages List View
 *
 * @package Global_Tractors
 */

defined('ABSPATH') || exit;

global $wpdb;
$messages = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}gti_messages ORDER BY created_at DESC LIMIT 50"
);
$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_messages");
$unread_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gti_messages WHERE status = 'unread'");
?>

<div class="gti-admin-wrap">
    <div class="gti-page-header">
        <div>
            <h1 class="gti-page-title">Contact Messages</h1>
        </div>
    </div>
    
    <div class="gti-stats-row">
        <div class="gti-stat-card stat-primary">
            <div class="stat-value"><?php echo esc_html($total); ?></div>
            <div class="stat-label">Total Messages</div>
        </div>
        <div class="gti-stat-card stat-info">
            <div class="stat-value"><?php echo esc_html($unread_count); ?></div>
            <div class="stat-label">Unread</div>
        </div>
    </div>
    
    <div class="gti-table-wrap">
        <table class="gti-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Email</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="width: 100px; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <div class="gti-empty-state">
                                <div class="gti-empty-state-icon">✉️</div>
                                <div class="gti-empty-state-title">No messages</div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <tr>
                            <td><strong><?php echo esc_html($msg->sender_name); ?></strong></td>
                            <td><?php echo esc_html($msg->sender_email); ?></td>
                            <td><?php echo esc_html($msg->subject); ?></td>
                            <td><?php echo GTI_Helpers::get_status_badge($msg->status); ?></td>
                            <td><?php echo esc_html(GTI_Helpers::format_date($msg->created_at)); ?></td>
                            <td style="text-align: right;">
                                <a href="#" class="gti-btn gti-btn-sm gti-btn-secondary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>