/**
 * GTI Admin Main JavaScript
 */

(function($) {
    'use strict';

    // Initialize
    $(document).ready(function() {
        GTI_Admin.init();
    });

    var GTI_Admin = {
        init: function() {
            this.bindEvents();
            this.initDropdowns();
            this.initDeleteConfirm();
            this.initDrawers();
        },

        bindEvents: function() {
            // Delete button
            $(document).on('click', '.gti-btn-delete', this.handleDelete);
            
            // Status change
            $(document).on('change', '.gti-status-select', this.handleStatusChange);
            
            // Filter form submit
            $(document).on('submit', '.gti-filter-form', this.handleFilter);
            
            // Reset filter
            $(document).on('click', '.gti-btn-reset-filter', this.resetFilter);
        },

        initDropdowns: function() {
            $(document).on('click', '.gti-dropdown-toggle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                var $dropdown = $(this).closest('.gti-dropdown');
                var $menu = $dropdown.find('.gti-dropdown-menu');
                
                // Close other dropdowns
                $('.gti-dropdown-menu').not($menu).removeClass('active');
                
                $menu.toggleClass('active');
            });

            // Close dropdown on click outside
            $(document).on('click', function() {
                $('.gti-dropdown-menu').removeClass('active');
            });
        },

        initDeleteConfirm: function() {
            $(document).on('click', '.gti-confirm-delete', function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var message = $btn.data('message') || 'Are you sure you want to delete this item?';
                
                if (confirm(message)) {
                    // Proceed with deletion
                    var action = $btn.data('action');
                    var id = $btn.data('id');
                    
                    if (action && id) {
                        GTI_Admin.performDelete(action, id, $btn);
                    }
                }
            });
        },

        performDelete: function(action, id, $btn) {
            var $row = $btn.closest('tr');
            
            $.ajax({
                url: gtiAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    id: id,
                    nonce: gtiAjax.nonce
                },
                beforeSend: function() {
                    $btn.prop('disabled', true);
                    $row.css('opacity', '0.5');
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(300, function() {
                            $(this).remove();
                        });
                        GTI_Admin.showToast('Item deleted successfully', 'success');
                    } else {
                        $row.css('opacity', '1');
                        $btn.prop('disabled', false);
                        GTI_Admin.showToast(response.data.message || 'Delete failed', 'error');
                    }
                },
                error: function() {
                    $row.css('opacity', '1');
                    $btn.prop('disabled', false);
                    GTI_Admin.showToast('An error occurred', 'error');
                }
            });
        },

        handleDelete: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var message = $btn.data('message') || 'Are you sure?';
            
            if (confirm(message)) {
                var url = $btn.attr('href');
                if (url) {
                    window.location.href = url;
                }
            }
        },

        handleStatusChange: function(e) {
            var $select = $(this);
            var id = $select.data('id');
            var action = $select.data('action');
            var status = $select.val();
            
            $.ajax({
                url: gtiAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: action,
                    id: id,
                    status: status,
                    nonce: gtiAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        GTI_Admin.showToast('Status updated', 'success');
                    } else {
                        GTI_Admin.showToast('Failed to update status', 'error');
                    }
                },
                error: function() {
                    GTI_Admin.showToast('An error occurred', 'error');
                }
            });
        },

        handleFilter: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var data = $form.serialize();
            var baseUrl = window.location.pathname;
            
            window.location.href = baseUrl + '?' + data;
        },

        resetFilter: function(e) {
            e.preventDefault();
            var baseUrl = window.location.pathname;
            window.location.href = baseUrl;
        },

        showToast: function(message, type) {
            type = type || 'success';
            
            var $toast = $('<div class="gti-toast gti-toast-' + type + '">' + message + '</div>');
            $('body').append($toast);
            
            setTimeout(function() {
                $toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },

        // Format currency
        formatCurrency: function(amount) {
            return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        },

        // Format date
        formatDate: function(dateString) {
            if (!dateString) return '-';
            var date = new Date(dateString);
            var options = { day: '2-digit', month: 'short', year: 'numeric' };
            return date.toLocaleDateString('id-ID', options);
        },

        // ===========================================
        // RIGHT DRAWER
        // ===========================================

        initDrawers: function() {
            var self = this;

            // Open drawer on row click
            $(document).on('click', '.gti-sell-row', function(e) {
                if ($(e.target).closest('.gti-dropdown, .gti-dropdown-toggle, .gti-status-select').length) return;
                var id = $(this).data('id');
                self.openSellDrawer(id);
            });

            // Open drawer from dropdown button
            $(document).on('click', '.gti-open-drawer', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var id = $(this).data('id');
                self.openSellDrawer(id);
            });

            // Close drawer
            $(document).on('click', '#sellDrawerClose, #sellDrawerOverlay', function() {
                self.closeSellDrawer();
            });

            // Close on Escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.closeSellDrawer();
                }
            });
        },

        openSellDrawer: function(id) {
            var self = this;
            var $overlay = $('#sellDrawerOverlay');
            var $drawer = $('#sellDrawer');
            var $body = $('#sellDrawerBody');

            // Show loading
            $body.html(
                '<div class="gti-drawer-loading">' +
                    '<div class="gti-spinner"></div>' +
                    '<p>Loading details...</p>' +
                '</div>'
            );

            // Open drawer
            $overlay.addClass('active');
            $drawer.addClass('active');
            $('body').css('overflow', 'hidden');

            // Fetch data
            $.ajax({
                url: gtiAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'gti_get_sell_request_detail',
                    id: id,
                    nonce: gtiAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.renderSellDrawerContent(response.data);
                    } else {
                        $body.html(
                            '<div class="gti-drawer-loading">' +
                                '<p style="color: #ef4444;">Failed to load details</p>' +
                            '</div>'
                        );
                    }
                },
                error: function() {
                    $body.html(
                        '<div class="gti-drawer-loading">' +
                            '<p style="color: #ef4444;">An error occurred</p>' +
                        '</div>'
                    );
                }
            });
        },

        closeSellDrawer: function() {
            $('#sellDrawerOverlay').removeClass('active');
            $('#sellDrawer').removeClass('active');
            $('body').css('overflow', '');
        },

        renderSellDrawerContent: function(data) {
            var self = this;
            var statusOptions = [
                { value: 'new', label: 'New' },
                { value: 'processing', label: 'Processing' },
                { value: 'approved', label: 'Approved' },
                { value: 'rejected', label: 'Rejected' },
                { value: 'completed', label: 'Completed' }
            ];

            // Build status select
            var statusSelect = '<select class="gti-status-select" data-id="' + data.id + '" data-action="gti_update_sell_request_status">';
            for (var i = 0; i < statusOptions.length; i++) {
                var selected = statusOptions[i].value === data.status ? ' selected' : '';
                statusSelect += '<option value="' + statusOptions[i].value + '"' + selected + '>' + statusOptions[i].label + '</option>';
            }
            statusSelect += '</select>';

            // Build timeline
            var timelineSteps = ['new', 'processing', 'approved', 'completed'];
            var timelineLabels = ['New', 'Processing', 'Approved', 'Completed'];
            var currentIndex = timelineSteps.indexOf(data.status);
            if (data.status === 'rejected') currentIndex = -1;

            var timeline = '<div class="gti-drawer-timeline">';
            for (var j = 0; j < timelineSteps.length; j++) {
                var stepClass = '';
                if (j < currentIndex) stepClass = 'completed';
                else if (j === currentIndex) stepClass = 'active';
                timeline += '<div class="gti-timeline-step ' + stepClass + '">' +
                    '<div class="gti-timeline-dot"></div>' +
                    '<div class="gti-timeline-label">' + timelineLabels[j] + '</div>' +
                '</div>';
            }
            timeline += '</div>';

            // If rejected, show rejected indicator
            if (data.status === 'rejected') {
                timeline += '<div style="text-align: center; padding: 8px 0; color: #ef4444; font-size: 13px; font-weight: 500;">✗ Rejected</div>';
            }

            // Build images section
            var imagesHtml = '';
            if (data.images_array && data.images_array.length > 0) {
                imagesHtml = '<div class="gti-drawer-images">';
                for (var k = 0; k < data.images_array.length; k++) {
                    var imgUrl = data.images_array[k];
                    if (/^\d+$/.test(imgUrl)) {
                        // WordPress attachment ID - would need AJAX to resolve, use placeholder
                        imgUrl = '';
                    }
                    if (imgUrl) {
                        imagesHtml += '<img src="' + imgUrl + '" alt="Equipment image" />';
                    }
                }
                imagesHtml += '</div>';
            } else {
                imagesHtml = '<div class="gti-drawer-no-images">No images uploaded</div>';
            }

            // Build HTML
            var html = 
                // Status header
                '<div class="gti-drawer-status-header">' +
                    '<div>' + self.getStatusBadgeHtml(data.status) + '</div>' +
                    statusSelect +
                '</div>' +

                '<div style="padding: 24px;">' +
                    // Price highlight
                    '<div class="gti-drawer-section">' +
                        '<div class="gti-drawer-price">' + self.formatCurrency(data.offered_price) + '</div>' +
                    '</div>' +

                    // Timeline
                    '<div class="gti-drawer-section">' +
                        '<div class="gti-drawer-section-title">Status Timeline</div>' +
                        timeline +
                    '</div>' +

                    // Customer Information
                    '<div class="gti-drawer-section">' +
                        '<div class="gti-drawer-section-title">Customer Information</div>' +
                        '<div class="gti-drawer-info-grid">' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Name</span>' +
                                '<span class="gti-drawer-info-value">' + (data.customer_name || '-') + '</span>' +
                            '</div>' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Company</span>' +
                                '<span class="gti-drawer-info-value">' + (data.customer_company || '-') + '</span>' +
                            '</div>' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Email</span>' +
                                '<span class="gti-drawer-info-value">' + (data.customer_email || '-') + '</span>' +
                            '</div>' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Phone</span>' +
                                '<span class="gti-drawer-info-value">' + (data.customer_phone || '-') + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +

                    // Equipment Information
                    '<div class="gti-drawer-section">' +
                        '<div class="gti-drawer-section-title">Equipment Information</div>' +
                        '<div class="gti-drawer-info-grid">' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Equipment Name</span>' +
                                '<span class="gti-drawer-info-value">' + (data.equipment_name || '-') + '</span>' +
                            '</div>' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Brand</span>' +
                                '<span class="gti-drawer-info-value">' + (data.equipment_brand || '-') + '</span>' +
                            '</div>' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Model</span>' +
                                '<span class="gti-drawer-info-value">' + (data.equipment_model || '-') + '</span>' +
                            '</div>' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Year</span>' +
                                '<span class="gti-drawer-info-value">' + (data.equipment_year || '-') + '</span>' +
                            '</div>' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Condition</span>' +
                                '<span class="gti-drawer-info-value">' + (data.equipment_condition || '-') + '</span>' +
                            '</div>' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Hours</span>' +
                                '<span class="gti-drawer-info-value">' + (data.equipment_hours ? data.equipment_hours.toLocaleString() + ' hrs' : '-') + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +

                    // Images
                    '<div class="gti-drawer-section">' +
                        '<div class="gti-drawer-section-title">Equipment Images</div>' +
                        imagesHtml +
                    '</div>' +

                    // Submission Details
                    '<div class="gti-drawer-section">' +
                        '<div class="gti-drawer-section-title">Submission Details</div>' +
                        '<div class="gti-drawer-info-grid">' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Submission Date</span>' +
                                '<span class="gti-drawer-info-value">' + self.formatDate(data.created_at) + '</span>' +
                            '</div>' +
                            '<div class="gti-drawer-info-item">' +
                                '<span class="gti-drawer-info-label">Last Updated</span>' +
                                '<span class="gti-drawer-info-value">' + self.formatDate(data.updated_at) + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            $('#sellDrawerBody').html(html);

            // Bind status change inside drawer
            $('#sellDrawerBody').on('change', '.gti-status-select', function() {
                var $select = $(this);
                var newStatus = $select.val();
                GTI_Admin.handleStatusChange.call($select[0]);
                // Update timeline
                setTimeout(function() { GTI_Admin.openSellDrawer(data.id); }, 500);
            });
        },

        getStatusBadgeHtml: function(status) {
            var badges = {
                'new':        '<span class="gti-badge gti-badge-info">New</span>',
                'processing': '<span class="gti-badge gti-badge-warning">Processing</span>',
                'approved':   '<span class="gti-badge gti-badge-success">Approved</span>',
                'rejected':   '<span class="gti-badge gti-badge-danger">Rejected</span>',
                'completed':  '<span class="gti-badge gti-badge-neutral">Completed</span>'
            };
            return badges[status] || '<span class="gti-badge">' + status + '</span>';
        }
    };

    // Make available globally
    window.GTI_Admin = GTI_Admin;

})(jQuery);