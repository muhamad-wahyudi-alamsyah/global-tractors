/**
 * Testing helper: fills every field of a form with fresh random data.
 * Bound to any <button class="gti-autofill-btn"> inside (or next to) a form.
 * ponytail: values are randomized off each field's own placeholder — add a
 * name->generator map only if a field needs a stricter format.
 */
document.addEventListener('DOMContentLoaded', function () {
    var btns = document.querySelectorAll('.gti-autofill-btn');
    if (!btns.length) return;

    function rnd(max) { return Math.floor(Math.random() * max); }

    function sample(el) {
        var hint = (el.placeholder || '').replace(/^e\.g\.,?\s*/i, '');
        if (el.type === 'date') {
            var d = new Date(Date.now() + (rnd(730) - 365) * 86400000);
            return d.toISOString().slice(0, 10);
        }
        if (el.type === 'number') {
            var base = +((hint.match(/\d+/) || [])[0] || 100);
            var n = 1 + rnd(Math.max(base * 2, 10));
            if (el.max !== '' && n > +el.max) n = +el.max;
            if (el.min !== '' && n < +el.min) n = +el.min;
            return String(n);
        }
        // Keep the placeholder's shape, swap every number for a random one.
        if (/\d/.test(hint)) {
            return hint.replace(/\d+/g, function (m) {
                return String(rnd(Math.pow(10, m.length))).padStart(m.length, '0');
            });
        }
        return (hint || 'Test ' + (el.name || 'value')) + ' ' + (1000 + rnd(9000));
    }

    btns.forEach(function (btn) {
        var form = btn.closest('form') || document.querySelector(btn.dataset.autofillForm || '.gti-ae-form');
        if (!form) return;
        btn.addEventListener('click', function () {
            // DOM order, so a category change regenerates the code before later fields.
            form.querySelectorAll('input, select, textarea').forEach(function (el) {
                if (el.disabled || el.readOnly || el.type === 'file' || el.type === 'hidden') return;
                if (el.tagName === 'SELECT') {
                    var opts = Array.prototype.filter.call(el.options, function (o) { return o.value !== ''; });
                    if (!opts.length) return;
                    el.value = opts[rnd(opts.length)].value;
                } else if (el.type === 'checkbox' || el.type === 'radio') {
                    el.checked = Math.random() < 0.7;
                } else {
                    el.value = sample(el);
                }
                el.dispatchEvent(new Event('input', { bubbles: true }));
                el.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });
});
