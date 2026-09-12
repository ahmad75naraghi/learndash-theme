/**
 * پنل کاربری evented-edu — رفتارهای مشترک (بدون jQuery)
 * مودال خروج، مودال‌های ساده (data-ee-modal-open / data-ee-modal-close)، ارسال ایجکسی فرم‌ها
 */
(function () {
    'use strict';

    var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
    var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

    /* --- مودال خروج --- */
    var logoutModal = $('#eeLogoutModal');
    var logoutConfirm = $('#eeLogoutConfirm');
    function openModal(m) { if (!m) { return; } m.hidden = false; document.body.style.overflow = 'hidden'; var f = m.querySelector('a,button'); if (f) { f.focus(); } }
    function closeModal(m) { if (!m) { return; } m.hidden = true; document.body.style.overflow = ''; }

    $$('[data-ee-logout]').forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.preventDefault();
            if (logoutConfirm) { logoutConfirm.href = b.getAttribute('data-ee-logout'); }
            openModal(logoutModal);
        });
    });
    $$('.ee-modal').forEach(function (m) {
        $$('[data-ee-modal-close]', m).forEach(function (c) { c.addEventListener('click', function () { closeModal(m); }); });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { $$('.ee-modal:not([hidden])').forEach(closeModal); $$('.overlay.is-open, .password-modal.is-open').forEach(function (o) { o.classList.remove('is-open'); }); }
    });

    /* --- مودال‌های قدیمی پنل (رسید تراکنش، تغییر رمز) --- */
    $$('.btn-receipt.active').forEach(function (btn) {
        btn.addEventListener('click', function () {
            ['course', 'price', 'date', 'time', 'track'].forEach(function (k) {
                var el = $('#modal-' + k); if (el) { el.textContent = btn.getAttribute('data-' + k) || ''; }
            });
            var ov = $('.overlay'); if (ov) { ov.classList.add('is-open'); }
        });
    });
    $$('.overlay .btn-close').forEach(function (b) { b.addEventListener('click', function () { b.closest('.overlay').classList.remove('is-open'); }); });
    $$('.overlay').forEach(function (o) { o.addEventListener('click', function (e) { if (e.target === o) { o.classList.remove('is-open'); } }); });
    $$('input[type="password"] + .icon-edit').forEach(function (i) { i.addEventListener('click', function () { var pm = $('.password-modal'); if (pm) { pm.classList.add('is-open'); } }); });
    $$('.password-modal .close').forEach(function (c) { c.addEventListener('click', function (e) { e.preventDefault(); c.closest('.password-modal').classList.remove('is-open'); }); });
    $$('.password-modal').forEach(function (pm) { pm.addEventListener('click', function (e) { if (e.target === pm) { pm.classList.remove('is-open'); } }); });

    /* --- کمکی: ارسال فرم با fetch --- */
    function post(data) {
        var body = new URLSearchParams();
        Object.keys(data).forEach(function (k) { body.append(k, data[k]); });
        return fetch((window.eePanel && eePanel.ajax_url) || '/wp-admin/admin-ajax.php', {
            method: 'POST', credentials: 'same-origin', body: body,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.json(); });
    }
    function formData(form) { var o = {}; new FormData(form).forEach(function (v, k) { o[k] = v; }); return o; }
    function flash(el, text, ok) {
        if (!el) { return; }
        el.textContent = text; el.style.display = 'block';
        el.style.color = ok ? '#047857' : '#b91c1c'; el.style.background = ok ? '#ecfdf5' : '#fef2f2';
        el.style.padding = '.6rem .8rem'; el.style.borderRadius = '.7rem';
        clearTimeout(el._t); el._t = setTimeout(function () { el.style.display = 'none'; }, 4000);
    }

    /* پروفایل */
    var profileForm = $('#profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = profileForm.querySelector('.btn-submit'); var old = btn ? btn.textContent : '';
            if (btn) { btn.textContent = 'در حال ذخیره...'; btn.disabled = true; }
            var d = formData(profileForm); d.action = 'save_user_profile'; d.security = ($('#profile_nonce') || {}).value || '';
            post(d).then(function (res) { flash($('#form-msg'), res && res.success ? res.data : (res && res.data) || 'خطایی رخ داد!', !!(res && res.success)); })
                .catch(function () { flash($('#form-msg'), 'خطای ارتباط با سرور', false); })
                .then(function () { if (btn) { btn.textContent = old; btn.disabled = false; } });
        });
    }

    /* تنظیمات حساب */
    var settingsForm = $('#settings-form');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = $('#settings-footer .btn-submit') || settingsForm.querySelector('.btn-submit'); var old = btn ? btn.textContent : '';
            if (btn) { btn.textContent = 'در حال ذخیره...'; btn.disabled = true; }
            var d = formData(settingsForm); d.action = 'save_account_settings'; d.security = ($('#settings_nonce') || {}).value || '';
            post(d).then(function (res) {
                var ok = !!(res && res.success);
                flash($('#settings-msg'), ok ? res.data : (res && res.data) || 'خطایی رخ داد!', ok);
                if (ok) {
                    $$('#settings-form input').forEach(function (i) { i.readOnly = true; });
                    var pw = settingsForm.querySelector('input[name="user_password"]'); if (pw && pw.value !== '') { pw.value = '..........'; }
                    var foot = $('#settings-footer'); if (foot) { setTimeout(function () { foot.style.display = 'none'; }, 1500); }
                }
            }).catch(function () { flash($('#settings-msg'), 'خطای ارتباط با سرور', false); })
              .then(function () { if (btn) { btn.textContent = old; btn.disabled = false; } });
        });
    }

    /* علاقه‌مندی‌ها: حذف + جستجو */
    $$('.remove-from-wishlist').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var card = btn.closest('.course-card'); if (card) { card.style.opacity = '.5'; }
            post({ action: 'toggle_course_wishlist', course_id: btn.getAttribute('data-course-id') || '0', security: (window.eePanel && eePanel.wishlist_nonce) || '' })
                .then(function (res) {
                    if (res && res.success && res.data && res.data.status === 'removed') {
                        if (card) { card.remove(); }
                        if (!$$('.wishlist-item').length) { location.reload(); }
                    } else { if (card) { card.style.opacity = '1'; } alert('خطایی در حذف دوره رخ داد.'); }
                }).catch(function () { if (card) { card.style.opacity = '1'; } alert('خطای ارتباط با سرور.'); });
        });
    });
    function liveFilter(inputSel, itemSel, attr) {
        var input = $(inputSel); if (!input) { return; }
        input.addEventListener('input', function () {
            var term = input.value.trim().toLowerCase();
            $$(itemSel).forEach(function (el) { var t = (el.getAttribute(attr) || '').toLowerCase(); el.style.display = t.indexOf(term) !== -1 ? '' : 'none'; });
        });
    }
    liveFilter('#wishlist-search', '.wishlist-item', 'data-title');
    liveFilter('#my-courses-search', '.horizontal-card', 'data-course-title');
}());
