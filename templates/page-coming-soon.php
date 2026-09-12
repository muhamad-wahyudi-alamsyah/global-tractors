<?php
/**
 * Template: Coming Soon (placeholder for unimplemented pages)
 * @package global-tractors
 */
if (!defined('ABSPATH')) exit;

$page_slug = get_query_var('gti_page');
$page_titles = [
    'rental-equipment' => 'Rental Equipment',
    'spare-parts' => 'Spare Parts',
    'request-equipment' => 'Request Equipment',
    'request-quotation' => 'Request Quotation',
    'sell-equipment' => 'Sell Equipment',
    'contact-messages' => 'Contact Messages',
    'customers' => 'Customers',
    'news-articles' => 'News & Articles',
    'media-library' => 'Media Library',
    'users' => 'Users',
    'website-settings' => 'Website Settings',
    'activity-log' => 'Activity Log',
];
$page_title = $page_titles[$page_slug] ?? 'Page';

// Determine which nav item should be active
$is_equipment_child = in_array($page_slug, ['rental-equipment']);

gti_dashboard_open( array(
    'page'     => 'coming-soon',
    'title'    => 'Coming Soon',
    'cap'      => 'gti_access',
) );
?>


            <!-- Content -->
            <div class="gti-content">
                <div class="gti-empty-state">
                    <i class="fas fa-construction"></i>
                    <h3>Coming Soon</h3>
                    <p>This page is under development.</p>
                </div>
            </div>

<?php
gti_dashboard_close(  );
