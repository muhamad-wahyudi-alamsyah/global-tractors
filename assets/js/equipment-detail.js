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

    // ═══ INQUIRY FORM ═══════════════════════════════════════════════════
    var form = document.getElementById('gti-ed-inquiry-form');
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();

        var name    = form.querySelector('[name="ed_name"]');
        var phone   = form.querySelector('[name="ed_phone"]');
        var message = form.querySelector('[name="ed_message"]');
        var submitBtn = form.querySelector('.gti-ed-form-submit');

        if (!name || !name.value.trim()) { name.focus(); return; }
        if (!phone || !phone.value.trim()) { phone.focus(); return; }

        // Build WhatsApp message
        var brand = wrapper.dataset.brand || '';
        var model = wrapper.dataset.model || '';
        var unitName = brand + ' ' + model;
        var text = 'Halo GTI, saya tertarik dengan unit *' + unitName.trim() + '*.\n\n';
        if (message && message.value.trim()) {
          text += message.value.trim() + '\n\n';
        }
        text += 'Nama: ' + (name ? name.value.trim() : '-') + '\n';
        text += 'Telepon: ' + (phone ? phone.value.trim() : '-');

        var waNumber = wrapper.dataset.wa || '6281234567890';
        var waUrl = 'https://wa.me/' + waNumber + '?text=' + encodeURIComponent(text);
        window.open(waUrl, '_blank');

        // Reset
        form.reset();
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
