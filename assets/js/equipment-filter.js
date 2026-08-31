/**
 * GTI Equipment Filter — Public Catalog Interactivity
 *
 * Handles filter panel, AJAX filtering, sorting, pagination, view switching.
 * @package global-tractors
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var wrapper = document.getElementById('gti-equipment-filter');
    if (!wrapper) return;

    // ── Config ─────────────────────────────────────────────────────────────
    var config = {
      ajaxUrl:  wrapper.dataset.ajaxUrl  || '/wp-admin/admin-ajax.php',
      nonce:    wrapper.dataset.nonce    || '',
      postType: wrapper.dataset.postType || 'post',
      perPage:  parseInt(wrapper.dataset.perPage, 10) || 12,
    };

    // ── State ──────────────────────────────────────────────────────────────
    var state = {
      categories:  [],
      brands:      [],
      types:       [],
      minYear:     '',
      maxYear:     '',
      minPrice:    '',
      maxPrice:    '',
      minHours:    '',
      maxHours:    '',
      locations:   [],
      condition:   [],
      sortBy:      'newest',
      viewMode:    'grid',
      currentPage: 1,
    };

    // ── DOM References ─────────────────────────────────────────────────────
    var gridEl         = document.getElementById('gti-ef-grid');
    var loadingEl      = document.getElementById('gti-ef-loading');
    var noResultsEl    = document.getElementById('gti-ef-no-results');
    var paginationEl   = document.getElementById('gti-ef-pagination');
    var resultCountEl  = document.getElementById('gti-ef-result-count');
    var sortEl         = document.getElementById('gti-ef-sort');
    var resetBtn       = document.getElementById('gti-ef-reset');
    var applyBtn       = document.getElementById('gti-ef-apply');
    var mobileToggle   = document.getElementById('gti-ef-mobile-toggle');
    var mobileOverlay  = document.getElementById('gti-ef-mobile-overlay');
    var sidebar        = document.getElementById('gti-ef-sidebar');

    // All cards in DOM (client-side filter)
    var allCards = Array.from(gridEl.querySelectorAll('.gti-ef-card'));

    // ═══ ACCORDION FILTERS (includes Categories) ══════════════════════════
    var filterHeaders = document.querySelectorAll('.gti-ef-filter-header');
    filterHeaders.forEach(function (header) {
      header.addEventListener('click', function () {
        var filterName = header.dataset.filter;
        var body = document.querySelector('[data-filter-body="' + filterName + '"]');
        if (!body) return;

        var isOpen = header.classList.contains('open');
        header.classList.toggle('open', !isOpen);
        body.classList.toggle('open', !isOpen);

        var icon = header.querySelector('.toggle-icon i');
        if (icon) {
          icon.className = isOpen ? 'fas fa-plus' : 'fas fa-minus';
        }
      });
    });

    // ═══ MOBILE FILTER PANEL ═══════════════════════════════════════════════
    if (mobileToggle && sidebar && mobileOverlay) {
      mobileToggle.addEventListener('click', function () {
        sidebar.classList.add('mobile-open');
        mobileOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
      });

      mobileOverlay.addEventListener('click', function () {
        sidebar.classList.remove('mobile-open');
        mobileOverlay.classList.remove('active');
        document.body.style.overflow = '';
      });
    }

    // ═══ SORT ══════════════════════════════════════════════════════════════
    if (sortEl) {
      sortEl.addEventListener('change', function () {
        state.sortBy = sortEl.value;
        state.currentPage = 1;
        applyFilters();
      });
    }

    // ═══ VIEW SWITCHER ═════════════════════════════════════════════════════
    var viewBtns = document.querySelectorAll('.gti-ef-view-btn');
    viewBtns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        viewBtns.forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        state.viewMode = btn.dataset.view;

        if (state.viewMode === 'list') {
          gridEl.classList.add('list-view');
        } else {
          gridEl.classList.remove('list-view');
        }
      });
    });

    // ═══ APPLY BUTTON ══════════════════════════════════════════════════════
    if (applyBtn) {
      applyBtn.addEventListener('click', function () {
        collectFilterValues();
        state.currentPage = 1;
        applyFilters();

        // Close mobile panel if open
        if (sidebar) sidebar.classList.remove('mobile-open');
        if (mobileOverlay) mobileOverlay.classList.remove('active');
        document.body.style.overflow = '';
      });
    }

    // ═══ RESET BUTTON ══════════════════════════════════════════════════════
    if (resetBtn) {
      resetBtn.addEventListener('click', function () {
        // Reset checkboxes
        document.querySelectorAll('.gti-ef-checkbox-item input[type="checkbox"]').forEach(function (cb) {
          cb.checked = false;
        });

        // Reset range inputs
        document.querySelectorAll('.gti-ef-range-inputs input').forEach(function (inp) {
          inp.value = '';
        });

        // Reset state
        state.categories = [];
        state.brands    = [];
        state.types     = [];
        state.locations = [];
        state.condition = [];
        state.minYear   = '';
        state.maxYear   = '';
        state.minPrice  = '';
        state.maxPrice  = '';
        state.minHours  = '';
        state.maxHours  = '';
        state.currentPage = 1;

        applyFilters();
      });
    }

    // ═══ PAGINATION ════════════════════════════════════════════════════════
    if (paginationEl) {
      paginationEl.addEventListener('click', function (e) {
        var btn = e.target.closest('.gti-ef-page-btn');
        if (!btn || btn.disabled) return;

        var page = btn.dataset.page;
        var totalPages = parseInt(paginationEl.dataset.totalPages, 10) || 1;

        switch (page) {
          case 'prev':
            state.currentPage = Math.max(1, state.currentPage - 1);
            break;
          case 'next':
            state.currentPage = Math.min(totalPages, state.currentPage + 1);
            break;
          case 'last':
            state.currentPage = totalPages;
            break;
          default:
            state.currentPage = parseInt(page, 10) || 1;
        }

        applyFilters();
        // Scroll to top of grid
        wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    }

    // ═══ COLLECT FILTER VALUES ═════════════════════════════════════════════
    function collectFilterValues() {
      // Categories
      state.categories = [];
      document.querySelectorAll('input[name="ef-category"]:checked').forEach(function (cb) {
        state.categories.push(cb.value);
      });

      // Brands
      state.brands = [];
      document.querySelectorAll('input[name="ef-brand"]:checked').forEach(function (cb) {
        state.brands.push(cb.value);
      });

      // Types
      state.types = [];
      document.querySelectorAll('input[name="ef-type"]:checked').forEach(function (cb) {
        state.types.push(cb.value);
      });

      // Locations
      state.locations = [];
      document.querySelectorAll('input[name="ef-location"]:checked').forEach(function (cb) {
        state.locations.push(cb.value);
      });

      // Condition
      state.condition = [];
      document.querySelectorAll('input[name="ef-condition"]:checked').forEach(function (cb) {
        state.condition.push(cb.value);
      });

      // Year range
      var yearMin = document.querySelector('input[name="ef-year-min"]');
      var yearMax = document.querySelector('input[name="ef-year-max"]');
      state.minYear = yearMin ? yearMin.value : '';
      state.maxYear = yearMax ? yearMax.value : '';

      // Price range
      var priceMin = document.querySelector('input[name="ef-price-min"]');
      var priceMax = document.querySelector('input[name="ef-price-max"]');
      state.minPrice = priceMin ? priceMin.value.replace(/\D/g, '') : '';
      state.maxPrice = priceMax ? priceMax.value.replace(/\D/g, '') : '';

      // Hours range
      var hoursMin = document.querySelector('input[name="ef-hours-min"]');
      var hoursMax = document.querySelector('input[name="ef-hours-max"]');
      state.minHours = hoursMin ? hoursMin.value : '';
      state.maxHours = hoursMax ? hoursMax.value : '';
    }

    // ═══ APPLY FILTERS (Client-Side) ══════════════════════════════════════
    function applyFilters() {
      var filtered = allCards.filter(function (card) {
        // Category (multi-select)
        if (state.categories.length > 0 && state.categories.indexOf(card.dataset.category) === -1) return false;

        // Brand
        if (state.brands.length > 0 && state.brands.indexOf(card.dataset.brand) === -1) return false;

        // Type
        if (state.types.length > 0 && state.types.indexOf(card.dataset.type) === -1) return false;

        // Year
        var cardYear = parseInt(card.dataset.year, 10);
        if (state.minYear && cardYear < parseInt(state.minYear, 10)) return false;
        if (state.maxYear && cardYear > parseInt(state.maxYear, 10)) return false;

        // Price
        var cardPrice = parseFloat(card.dataset.price) || 0;
        if (state.minPrice && cardPrice < parseFloat(state.minPrice)) return false;
        if (state.maxPrice && cardPrice > parseFloat(state.maxPrice)) return false;

        // Hours
        var cardHours = parseFloat(card.dataset.hours) || 0;
        if (state.minHours && cardHours < parseFloat(state.minHours)) return false;
        if (state.maxHours && cardHours > parseFloat(state.maxHours)) return false;

        // Location
        if (state.locations.length > 0 && state.locations.indexOf(card.dataset.location) === -1) return false;

        // Condition
        if (state.condition.length > 0 && state.condition.indexOf(card.dataset.condition) === -1) return false;

        return true;
      });

      // Sort
      filtered = sortCards(filtered);

      // Pagination
      var total      = filtered.length;
      var totalPages = Math.max(1, Math.ceil(total / config.perPage));

      // Clamp page
      if (state.currentPage > totalPages) state.currentPage = totalPages;
      if (state.currentPage < 1) state.currentPage = 1;

      var startIdx = (state.currentPage - 1) * config.perPage;
      var paged    = filtered.slice(startIdx, startIdx + config.perPage);

      // Render
      renderCards(paged);
      renderPagination(total, totalPages);
      updateResultCount(startIdx + 1, Math.min(startIdx + config.perPage, total), total);
    }

    // ═══ SORT ══════════════════════════════════════════════════════════════
    function sortCards(cards) {
      var sorted = cards.slice();
      switch (state.sortBy) {
        case 'price-low':
          sorted.sort(function (a, b) { return (parseFloat(a.dataset.price) || 0) - (parseFloat(b.dataset.price) || 0); });
          break;
        case 'price-high':
          sorted.sort(function (a, b) { return (parseFloat(b.dataset.price) || 0) - (parseFloat(a.dataset.price) || 0); });
          break;
        case 'hours-low':
          sorted.sort(function (a, b) { return (parseFloat(a.dataset.hours) || 0) - (parseFloat(b.dataset.hours) || 0); });
          break;
        case 'newest':
        default:
          sorted.sort(function (a, b) { return parseInt(b.dataset.id, 10) - parseInt(a.dataset.id, 10); });
      }
      return sorted;
    }

    // ═══ RENDER ════════════════════════════════════════════════════════════
    function renderCards(cards) {
      // Clear grid
      gridEl.innerHTML = '';

      if (cards.length === 0) {
        noResultsEl.style.display = '';
        gridEl.style.display = 'none';
        paginationEl.style.display = 'none';
        return;
      }

      noResultsEl.style.display = 'none';
      gridEl.style.display = '';
      paginationEl.style.display = '';

      cards.forEach(function (card) {
        gridEl.appendChild(card.cloneNode(true));
      });
    }

    function updateResultCount(from, to, total) {
      if (!resultCountEl) return;
      if (total === 0) {
        resultCountEl.innerHTML = 'No results found';
      } else {
        resultCountEl.innerHTML = 'Showing <strong>' + from + ' - ' + to + '</strong> of <strong>' + total + '</strong> units';
      }
    }

    function renderPagination(total, totalPages) {
      if (!paginationEl) return;

      var html = '';

      // Prev
      html += '<button class="gti-ef-page-btn" data-page="prev"' + (state.currentPage <= 1 ? ' disabled' : '') + '><i class="fas fa-chevron-left"></i></button>';

      // Pages
      var maxVisible = 5;
      var start = Math.max(1, state.currentPage - Math.floor(maxVisible / 2));
      var end   = Math.min(totalPages, start + maxVisible - 1);
      if (end - start < maxVisible - 1) {
        start = Math.max(1, end - maxVisible + 1);
      }

      if (start > 1) {
        html += '<button class="gti-ef-page-btn" data-page="1">1</button>';
        if (start > 2) {
          html += '<span class="gti-ef-page-btn" style="border:none;cursor:default;">…</span>';
        }
      }

      for (var i = start; i <= end; i++) {
        html += '<button class="gti-ef-page-btn' + (i === state.currentPage ? ' active' : '') + '" data-page="' + i + '">' + i + '</button>';
      }

      if (end < totalPages) {
        if (end < totalPages - 1) {
          html += '<span class="gti-ef-page-btn" style="border:none;cursor:default;">…</span>';
        }
        html += '<button class="gti-ef-page-btn" data-page="' + totalPages + '">' + totalPages + '</button>';
      }

      // Next
      html += '<button class="gti-ef-page-btn" data-page="next"' + (state.currentPage >= totalPages ? ' disabled' : '') + '><i class="fas fa-chevron-right"></i></button>';

      // Last
      html += '<button class="gti-ef-page-btn" data-page="last"' + (state.currentPage >= totalPages ? ' disabled' : '') + '><i class="fas fa-angle-double-right"></i></button>';

      paginationEl.innerHTML = html;
      paginationEl.dataset.totalPages = totalPages;
    }
  });
})();
