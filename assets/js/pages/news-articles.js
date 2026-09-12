/**
 * News & Articles page script.
 *
 * Lifted out of the template's inline <script> during the R1/R3 refactor.
 * Shared behaviour — toast, drawer, modal, action dropdown, sidebar collapse,
 * date/currency formatting and the fetch wrapper — comes from
 * assets/js/dashboard-ui.js; gtiAjax comes from wp_localize_script().
 */

document.addEventListener('DOMContentLoaded', function() {
        // Sidebar collapse
        var collapseBtn = document.getElementById('gti-collapse-btn');
        var sidebar = document.getElementById('gti-sidebar');
        var mainEl = document.getElementById('gti-main');


        // Action Dropdown Toggle

        // Backdrop click → close drawer
        document.getElementById('drawerBackdrop').addEventListener('click', function() {
            closeDetailDrawer();
        });

        // Close drawer on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeDetailDrawer();
        });

        // Row click → open drawer
        document.querySelectorAll('.gti-ue-table tbody tr[data-article]').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target.closest('.gti-ue-action-menu')) return;
                var article;
                try { article = JSON.parse(this.getAttribute('data-article')); } catch(err) { return; }
                showArticleDetail(article);
            });
        });

        // Auto-populate drawer with first row on page load
        var firstRow = document.querySelector('.gti-ue-table tbody tr[data-article]');
        if (firstRow) {
            try {
                var firstArticle = JSON.parse(firstRow.getAttribute('data-article'));
                updateDrawerContent(firstArticle);
            } catch(e) {}
        }
    });

    var _currentArticle = null;

    function updateDrawerContent(article) {
        _currentArticle = article;

        document.getElementById('drawer-title').textContent = article.title || '-';

        // Status badge
        var badge = document.getElementById('drawer-status-badge');
        badge.textContent = (article.status || 'draft').charAt(0).toUpperCase() + (article.status || 'draft').slice(1);
        badge.className = 'gti-drawer-status status-' + (article.status || 'draft');

        // Category badge
        var catBadge = document.getElementById('drawer-cat-badge');
        var catLabels = { 'company-news': 'Company News', 'tips': 'Tips & Tricks', 'event': 'Event', 'industry': 'Industry', 'product': 'Product' };
        var catColors = {
            'company-news': ['#dbeafe', '#1d4ed8'],
            'tips': ['#d1fae5', '#047857'],
            'event': ['#fef3c7', '#b45309'],
            'industry': ['#e0e7ff', '#4338ca'],
            'product': ['#fce7f3', '#be185d']
        };
        var catLabel = catLabels[article.category] || article.category;
        var catColor = catColors[article.category] || ['#f3f4f6', '#374151'];
        catBadge.textContent = catLabel;
        catBadge.style.background = catColor[0];
        catBadge.style.color = catColor[1];

        // Featured image
        var imgContainer = document.getElementById('drawer-featured-img');
        if (article.featured_image) {
            imgContainer.innerHTML = '<img src="' + article.featured_image + '" alt="">';
        } else {
            imgContainer.innerHTML = '<i class="fas fa-image"></i>';
        }

        // Info rows
        document.getElementById('drawer-article-id').textContent = article.article_id || '-';
        document.getElementById('drawer-author').textContent = article.author || '-';
        document.getElementById('drawer-published').textContent = article.published_at ? formatDateID(article.published_at) : '-';
        document.getElementById('drawer-created').textContent = article.created_at ? formatDateID(article.created_at) : '-';
        document.getElementById('drawer-views').textContent = formatViews(article.views);
        document.getElementById('drawer-featured').textContent = article.is_featured ? 'Yes' : 'No';

        // Excerpt
        document.getElementById('drawer-excerpt').textContent = article.excerpt || article.content || 'No content available';

        // Tags
        var tagsContainer = document.getElementById('drawer-tags');
        if (article.tags) {
            var tags = article.tags.split(',').map(function(t) { return t.trim(); }).filter(function(t) { return t; });
            if (tags.length > 0) {
                tagsContainer.innerHTML = tags.map(function(tag) {
                    return '<span class="gti-drawer-tag"><i class="fas fa-tag" style="margin-right:4px;font-size:9px;"></i>' + tag + '</span>';
                }).join('');
            } else {
                tagsContainer.innerHTML = '<span style="color:#9ca3af;font-size:13px;">No tags</span>';
            }
        } else {
            tagsContainer.innerHTML = '<span style="color:#9ca3af;font-size:13px;">No tags</span>';
        }

        // Timeline
        var timelineItems = [];
        if (article.created_at) {
            timelineItems.push({ event: 'Article Created', date: article.created_at, actor: article.author || 'System' });
        }
        if (article.published_at) {
            timelineItems.push({ event: 'Published', date: article.published_at, actor: article.author || 'System' });
        }
        if (article.updated_at && article.updated_at !== article.created_at) {
            timelineItems.push({ event: 'Last Updated', date: article.updated_at, actor: 'System' });
        }

        var timelineHtml = '';
        timelineItems.forEach(function(item, idx) {
            var isLast = idx === timelineItems.length - 1;
            var dotClass = isLast ? 'gti-drawer-timeline-dot is-active' : 'gti-drawer-timeline-dot';
            timelineHtml += '<div class="gti-drawer-timeline-item">' +
                '<div class="' + dotClass + '"></div>' +
                '<div class="gti-drawer-timeline-event">' + item.event + '</div>' +
                '<div class="gti-drawer-timeline-meta">' + formatDateID(item.date) + ' &middot; by ' + item.actor + '</div>' +
                '</div>';
        });
        document.getElementById('drawer-timeline').innerHTML = timelineHtml || '<div style="color:#9ca3af;font-size:13px;">No activity yet</div>';

        // Toggle button text
        var toggleText = document.getElementById('drawer-btn-toggle-text');
        toggleText.textContent = article.status === 'published' ? 'Unpublish' : 'Publish';

        // Footer links point at the real editor and the live post
        var editBtn = document.getElementById('drawer-btn-edit');
        var viewBtn = document.getElementById('drawer-btn-preview');
        editBtn.href = article.edit_url || '#';
        viewBtn.href = article.permalink || '#';
        viewBtn.target = '_blank';
        viewBtn.rel = 'noopener';

        // Highlight active row
        document.querySelectorAll('.gti-ue-table tbody tr').forEach(function(r) { r.classList.remove('active-row'); });
        var activeRow = document.querySelector('.gti-ue-table tbody tr[data-article-id="' + article.id + '"]');
        if (activeRow) activeRow.classList.add('active-row');
    }

    function showArticleDetail(article) {
        updateDrawerContent(article);
        if (window.innerWidth < 1600) {
            document.getElementById('articleDetailDrawer').classList.add('open');
            document.getElementById('drawerBackdrop').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        document.querySelectorAll('.gti-ue-action-dropdown.show').forEach(function(d) { d.classList.remove('show'); });
    }

    function formatViews(views) {
        views = parseInt(views) || 0;
        if (views >= 1000) return (views / 1000).toFixed(1) + 'K views';
        return views.toLocaleString() + ' views';
    }

    function toggleStatusFromDrawer() {
        if (!_currentArticle) return;
        var newStatus = _currentArticle.status === 'published' ? 'draft' : 'published';
        toggleArticleStatus(_currentArticle.id, newStatus);
    }

    function deleteArticle(articleId) {
        if (!confirm('Move this article to the trash?')) return;

        var formData = new FormData();
        formData.append('action', 'gti_delete_article');
        formData.append('id', articleId);
        formData.append('nonce', typeof gtiAjax !== 'undefined' ? gtiAjax.nonce : '');

        fetch(typeof gtiAjax !== 'undefined' ? gtiAjax.ajaxurl : '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data && res.data.message ? res.data.message : 'Failed to delete article');
            }
        })
        .catch(function() {
            alert('An error occurred. Please try again.');
        });
    }

    function toggleArticleStatus(articleId, newStatus) {
        var label = newStatus === 'published' ? 'publish' : 'unpublish';
        if (!confirm('Are you sure you want to ' + label + ' this article?')) return;

        var formData = new FormData();
        formData.append('action', 'gti_update_article_status');
        formData.append('id', articleId);
        formData.append('status', newStatus);
        formData.append('nonce', typeof gtiAjax !== 'undefined' ? gtiAjax.nonce : '');

        fetch(typeof gtiAjax !== 'undefined' ? gtiAjax.ajaxurl : '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data && res.data.message ? res.data.message : 'Failed to update status');
            }
        })
        .catch(function() {
            alert('An error occurred. Please try again.');
        });
    }
