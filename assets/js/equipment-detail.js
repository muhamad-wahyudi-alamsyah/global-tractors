/**
 * GTI Equipment Detail Page — Interactivity
 *
 * Gallery slider, thumbnail nav, inquiry form, related carousel, share.
 * @package global-tractors
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    var wrapper = document.getElementById('gti-equipment-detail');
    if (!wrapper) return;

    // ═══ GALLERY SLIDER ═══════════════════════════════════════════════════
    var mainImage = document.getElementById('gti-ed-main-image');
    var thumbs    = document.querySelectorAll('.gti-ed-thumb[data-index]');
    var prevBtn   = document.querySelector('.gti-ed-gallery-arrow.prev');
    var nextBtn   = document.querySelector('.gti-ed-gallery-arrow.next');
    var images    = [];
    var current   = 0;

    // Collect gallery images from data attributes
    thumbs.forEach(function (t) {
      var src = t.dataset.src || '';
      if (src) images.push(src);
    });

    // If no real images, keep at least one for the placeholder state
    if (images.length === 0 && mainImage) {
      var src = mainImage.src || '';
      if (src) images.push(src);
    }

    function showImage(idx) {
      if (images.length === 0) return;
      current = idx;
      if (current < 0) current = images.length - 1;
      if (current >= images.length) current = 0;

      if (mainImage && images[current]) {
        mainImage.src = images[current];
        mainImage.style.opacity = '0';
        requestAnimationFrame(function () {
          mainImage.style.opacity = '1';
        });
      }

      // Update active thumb
      thumbs.forEach(function (t, i) {
        t.classList.toggle('active', i === current);
      });
    }

    if (prevBtn) prevBtn.addEventListener('click', function () { showImage(current - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function () { showImage(current + 1); });

    thumbs.forEach(function (t, i) {
      t.addEventListener('click', function () { showImage(i); });
    });

    // ═══ SHARE ═══════════════════════════════════════════════════════════
    var shareBtn = document.getElementById('gti-ed-share-btn');
    if (shareBtn) {
      shareBtn.addEventListener('click', function () {
        if (navigator.share) {
          navigator.share({
            title: document.title,
            url: window.location.href,
          }).catch(function () {});
        } else if (navigator.clipboard) {
          navigator.clipboard.writeText(window.location.href).then(function () {
            var original = shareBtn.innerHTML;
            shareBtn.innerHTML = '<i class="fas fa-check"></i> <span>LINK COPIED!</span>';
            setTimeout(function () { shareBtn.innerHTML = original; }, 2000);
          });
        }
      });
    }

    // ═══ INQUIRY FORM → REQUEST QUOTATION ══════════════════════════════
    // Sends the full field set from PRD §7.2. The handler reads the ed_* names
    // straight off the form, so adding a field to the markup is enough — there
    // is no per-field append() list to keep in sync any more.
    var form = document.getElementById('gti-ed-inquiry-form');
    if (form) {
      var submitBtn = form.querySelector('.gti-ed-form-submit');

      // Progressive disclosure (§7.4): the advanced half starts collapsed.
      var moreToggle = document.getElementById('gti-ed-more-toggle');
      var moreBlock  = document.getElementById('gti-ed-more');
      if (moreToggle && moreBlock) {
        moreToggle.addEventListener('click', function () {
          var open = moreBlock.hidden;
          moreBlock.hidden = !open;
          moreToggle.setAttribute('aria-expanded', String(open));
          moreToggle.classList.toggle('is-open', open);
        });
      }

      // Thousands separators while typing a budget.
      var budget = form.querySelector('[name="ed_budget"]');
      if (budget) {
        budget.addEventListener('input', function () {
          var digits = this.value.replace(/\D/g, '');
          this.value = digits ? Number(digits).toLocaleString('id-ID') : '';
        });
      }

      // Preset rental duration fills in the end date.
      var rStart = form.querySelector('[name="ed_rental_start"]');
      var rEnd   = form.querySelector('[name="ed_rental_end"]');
      var rDur   = form.querySelector('[name="ed_rental_duration"]');
      function syncRentalEnd() {
        if (!rStart || !rEnd || !rDur || !rStart.value) return;
        var months = parseInt(rDur.value, 10);
        if (!months || rDur.value === 'custom') return;
        var d = new Date(rStart.value);
        d.setMonth(d.getMonth() + months);
        rEnd.value = d.toISOString().slice(0, 10);
      }
      if (rDur) rDur.addEventListener('change', syncRentalEnd);
      if (rStart) rStart.addEventListener('change', syncRentalEnd);

      var EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      function setError(name, message) {
        var slot = form.querySelector('.gti-ed-form-error[data-for="' + name + '"]');
        var field = form.querySelector('[name="' + name + '"]');
        if (slot) slot.textContent = message || '';
        if (field) {
          var group = field.closest('.gti-ed-form-group, .gti-ed-form-consent');
          if (group) group.classList.toggle('has-error', !!message);
        }
        return !message;
      }

      function validateField(name) {
        var field = form.querySelector('[name="' + name + '"]');
        if (!field) return true;
        var value = (field.value || '').trim();

        if (name === 'ed_name')  return setError(name, value ? '' : 'Nama wajib diisi.');
        if (name === 'ed_phone') return setError(name, value ? '' : 'Nomor telepon wajib diisi.');
        if (name === 'ed_email') {
          if (!value) return setError(name, 'Email wajib diisi.');
          return setError(name, EMAIL_RE.test(value) ? '' : 'Format email tidak valid.');
        }
        if (name === 'ed_consent') {
          return setError(name, field.checked ? '' : 'Mohon setujui untuk dihubungi.');
        }
        if (name === 'ed_rental_end') {
          if (!rStart || !rStart.value || !value) return setError(name, '');
          return setError(name, value > rStart.value ? '' : 'Harus setelah tanggal mulai.');
        }
        return true;
      }

      // Validate on blur rather than only on submit (§7.4).
      ['ed_name', 'ed_phone', 'ed_email', 'ed_rental_end'].forEach(function (name) {
        var field = form.querySelector('[name="' + name + '"]');
        if (field) field.addEventListener('blur', function () { validateField(name); });
      });
      var consent = form.querySelector('[name="ed_consent"]');
      if (consent) consent.addEventListener('change', function () { validateField('ed_consent'); });

      form.addEventListener('submit', function (e) {
        e.preventDefault();

        var checks = ['ed_name', 'ed_phone', 'ed_email', 'ed_consent', 'ed_rental_end'];
        var ok = checks.map(validateField).every(Boolean);
        if (!ok) {
          var firstBad = form.querySelector('.has-error input, .has-error select, .has-error textarea');
          if (firstBad) firstBad.focus();
          return;
        }

        var fd = new FormData(form);
        fd.append('action', 'gti_customer_submit_quotation');

        // Fall back to the wrapper's data attributes when the hidden inputs are absent.
        if (!fd.get('equipment_id') && wrapper.dataset.id) fd.set('equipment_id', wrapper.dataset.id);
        if (!fd.get('equipment_name')) {
          fd.set('equipment_name', wrapper.dataset.name ||
                 ((wrapper.dataset.brand || '') + ' ' + (wrapper.dataset.model || '')).trim());
        }

        var original = submitBtn ? submitBtn.innerHTML : '';
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> MENGIRIM...';
        }

        var email = form.querySelector('[name="ed_email"]');

        fetch(gtiAjaxUrl(), { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (response) {
            return response.text().then(function (text) {
              try { return JSON.parse(text); }
              catch (err) { throw new Error('Server mengirim respons yang tidak dikenali.'); }
            });
          })
          .then(function (res) {
            if (!res || !res.success) {
              throw new Error((res && res.data && res.data.message) || 'Terjadi kesalahan. Silakan coba lagi.');
            }
            form.innerHTML =
              '<div class="gti-ed-form-done">' +
                '<i class="fas fa-check-circle"></i>' +
                '<h3>Permintaan Terkirim</h3>' +
                '<p>Nomor quotation: <strong>' + (res.data.quotation_id || '-') + '</strong></p>' +
                '<p>Konfirmasi sudah dikirim ke <strong>' + (email ? email.value.trim() : '') + '</strong>. ' +
                'Tim kami akan menghubungi Anda dalam 1&times;24 jam kerja.</p>' +
              '</div>';
          })
          .catch(function (err) {
            var slot = form.querySelector('.gti-ed-form-error[data-for="ed_name"]');
            if (slot) slot.textContent = err.message;
            if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = original; }
          });
      });
    }

    /** admin-ajax endpoint; localized when available, otherwise the default path. */
    function gtiAjaxUrl() {
      return (window.gtiAjax && window.gtiAjax.ajaxurl) || '/wp-admin/admin-ajax.php';
    }

    // ═══ RELATED PRODUCTS CAROUSEL ═══════════════════════════════════════
    var carousel   = document.getElementById('gti-ed-related-carousel');
    var carPrevBtn = document.querySelector('.gti-ed-carousel-arrow.prev');
    var carNextBtn = document.querySelector('.gti-ed-carousel-arrow.next');

    if (carousel && carPrevBtn && carNextBtn) {
      var scrollAmount = 300;
      carPrevBtn.addEventListener('click', function () {
        carousel.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
      });
      carNextBtn.addEventListener('click', function () {
        carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
      });
    }
  });
})();
