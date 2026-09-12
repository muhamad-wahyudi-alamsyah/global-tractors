/**
 * Customer Detail page script.
 *
 * Lifted out of the template's inline <script> during the R1/R3 refactor.
 * Shared behaviour — toast, drawer, modal, action dropdown, sidebar collapse,
 * date/currency formatting and the fetch wrapper — comes from
 * assets/js/dashboard-ui.js; gtiAjax comes from wp_localize_script().
 */

document.addEventListener('DOMContentLoaded', function() {
        // Sidebar Collapse
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar     = document.getElementById('gti-sidebar');
        var mainEl      = document.querySelector('.gti-main');


        // More Actions Dropdown
        var moreToggle = document.getElementById('cd-more-toggle');
        var moreDropdown = document.getElementById('cd-more-dropdown');
        if (moreToggle && moreDropdown) {
            moreToggle.addEventListener('click', function(e) {
                e.preventDefault();
                moreDropdown.classList.toggle('show');
            });
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.gti-cd-more-wrap')) {
                    moreDropdown.classList.remove('show');
                }
            });
        }

        // Tab Switching
        document.querySelectorAll('.gti-cd-tab[data-tab]').forEach(function(tab) {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                var target = this.getAttribute('data-tab');

                // Update active tab
                document.querySelectorAll('.gti-cd-tab[data-tab]').forEach(function(t) {
                    t.classList.remove('active');
                });
                document.querySelectorAll('.gti-cd-tab[data-tab="' + target + '"]').forEach(function(t) {
                    t.classList.add('active');
                });

                // Show/hide content
                document.querySelectorAll('.gti-cd-tab-content').forEach(function(c) {
                    c.style.display = 'none';
                });
                var content = document.getElementById('tab-' + target);
                if (content) content.style.display = 'block';
            });
        });
    });
