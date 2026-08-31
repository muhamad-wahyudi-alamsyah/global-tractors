/**
 * GTI Search Card — Homepage Search Interactivity
 *
 * Handles search form submission with keyword, brand, type, year, price filters.
 * @package global-tractors
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var card = document.getElementById('gti-search-card');
    if (!card) return;

    var searchBtn  = document.getElementById('gti-search-btn');
    var keywordEl  = document.getElementById('gti-search-keyword');
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
      var keyword = keywordEl  ? keywordEl.value.trim() : '';
      var brand   = brandEl    ? brandEl.value : '';
      var type    = typeEl     ? typeEl.value : '';
      var year    = yearEl     ? yearEl.value : '';
      var price   = priceEl    ? priceEl.value : '';

      var params = new URLSearchParams();

      if (keyword)                          params.set('q', keyword);
      if (brand && brand !== 'all brand')   params.set('brand', brand);
      if (type && type !== 'all type')       params.set('type', type);
      if (year && year !== 'all year')       params.set('year', year);
      if (price && price !== 'all price')   params.set('price', price);

      var baseUrl     = window.gtiSearchData ? gtiSearchData.equipmentUrl : '/equipment/';
      var queryString = params.toString();
      var redirectUrl = baseUrl + (queryString ? '?' + queryString : '');

      window.location.href = redirectUrl;
    }
  });
})();
