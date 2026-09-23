/**
 * GTI Search Card — Homepage Search Interactivity
 *
 * Redirects to the selected catalog (Used / Rental) with keyword, brand, type,
 * year and price in the query string. equipment-filter.js reads them there.
 * @package global-tractors
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var card = document.getElementById('gti-search-card');
    if (!card) return;

    var searchBtn  = document.getElementById('gti-search-btn');
    var keywordEl  = document.getElementById('gti-search-keyword');
    var listingEl  = document.getElementById('gti-search-listing');
    var brandEl    = document.getElementById('gti-search-brand');
    var typeEl     = document.getElementById('gti-search-type');
    var yearEl     = document.getElementById('gti-search-year');
    var priceEl    = document.getElementById('gti-search-price');

    if (searchBtn) {
      searchBtn.addEventListener('click', performSearch);
    }

    if (keywordEl) {
      keywordEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
          e.preventDefault();
          performSearch();
        }
      });
    }

    function performSearch() {
      // new URL() keeps any query string the catalog URL already has (e.g. ?page_id=).
      var url    = new URL(listingEl.value, window.location.href);
      var params = url.searchParams;

      var keyword = keywordEl ? keywordEl.value.trim() : '';
      if (keyword)                   params.set('q', keyword);
      if (brandEl && brandEl.value)  params.set('brand', brandEl.value);
      if (typeEl && typeEl.value)    params.set('category', typeEl.value);

      // WordPress reserves ?year=, so a single year goes out as a min/max range.
      if (yearEl && yearEl.value) {
        params.set('year_min', yearEl.value);
        params.set('year_max', yearEl.value);
      }

      // Price option values are "min-max"; either side may be empty.
      if (priceEl && priceEl.value) {
        var range = priceEl.value.split('-');
        if (range[0]) params.set('price_min', range[0]);
        if (range[1]) params.set('price_max', range[1]);
      }

      window.location.href = url.toString();
    }
  });
})();
