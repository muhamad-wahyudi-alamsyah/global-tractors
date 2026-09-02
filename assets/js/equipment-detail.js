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
    var form = document.getElementById('gti-ed-inquiry-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();

        var name    = form.querySelector('[name="ed_name"]');
        var phone   = form.querySelector('[name="ed_phone"]');
        var email   = form.querySelector('[name="ed_email"]');
        var message = form.querySelector('[name="ed_message"]');
        var submitBtn = form.querySelector('.gti-ed-form-submit');

        // Validate required fields
        if (!name || !name.value.trim()) { name.focus(); return; }
        if (!phone || !phone.value.trim()) { phone.focus(); return; }
        if (!email || !email.value.trim()) { email.focus(); return; }

        // Email format check
        var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRe.test(email.value.trim())) { email.focus(); return; }

        // Gather data
        var equipmentName = wrapper.dataset.name || (wrapper.dataset.brand + ' ' + wrapper.dataset.model) || '';
        var nonceField = form.querySelector('[name="gti_quot_nonce"]');
        var nonce = (nonceField ? nonceField.value : '') || wrapper.dataset.nonce || '';

        // Build FormData
        var fd = new FormData();
        fd.append('action', 'gti_customer_submit_quotation');
        fd.append('gti_quot_nonce', nonce);
        fd.append('name', name.value.trim());
        fd.append('company', (form.querySelector('[name="ed_company"]') || {}).value || '');
        fd.append('phone', phone.value.trim());
        fd.append('email', email.value.trim());
        fd.append('message', message ? message.value.trim() : '');
        fd.append('equipment_id', wrapper.dataset.id || '');
        fd.append('equipment_name', equipmentName);

        // Disable button + show loading
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> SENDING...';
        }

        // AJAX POST
        fetch('/wp-admin/admin-ajax.php', {
          method: 'POST',
          body: fd,
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.success) {
            // Success — show confirmation
            var custEmail = email ? email.value.trim() : '';
            form.innerHTML =
              '<div style="text-align:center;padding:24px 0;">' +
                '<i class="fas fa-check-circle" style="font-size:48px;color:#10B981;margin-bottom:12px;display:block;"></i>' +
                '<h3 style="margin:0 0 8px;color:#1a1f36;">Quotation Submitted!</h3>' +
                '<p style="margin:0 0 4px;color:#6b7280;">ID: <strong>' + (res.data.quotation_id || '') + '</strong></p>' +
                '<p style="margin:0;color:#6b7280;">Tim kami akan segera menghubungi Anda melalui email <strong>' + custEmail + '</strong>.</p>' +
              '</div>';
          } else {
            alert(res.data ? res.data.message : 'Terjadi kesalahan. Silakan coba lagi.');
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> SEND MESSAGE';
            }
          }
        })
        .catch(function () {
          alert('Gagal mengirim. Periksa koneksi internet Anda.');
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> SEND MESSAGE';
          }
        });
      });
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
