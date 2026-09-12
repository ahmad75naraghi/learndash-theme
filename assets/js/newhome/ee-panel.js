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
    $$('[data-ee-modal-open]').forEach(function (b) {
        b.addEventListener('click', function (e) {
            e.preventDefault();
            var m = document.getElementById(b.getAttribute('data-ee-modal-open'));
            if (!m) { return; }
            openModal(m);
            var first = m.querySelector('input:not([type=hidden])'); if (first) { first.focus(); }
        });
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

    /* پروفایل: نام فارسی فقط حروف فارسی/عربی، نام انگلیسی فقط لاتین */
    var SCRIPT_RE = {
        fa: /[^\u0600-\u06FF\u0750-\u077F\uFB50-\uFDFF\uFE70-\uFEFF\s\u200c]/g,
        en: /[^A-Za-z\s.\-']/g
    };
    $$('[data-ee-script]').forEach(function (inp) {
        var re = SCRIPT_RE[inp.getAttribute('data-ee-script')]; if (!re) { return; }
        var hint = document.createElement('div'); hint.className = 'ee-script-hint'; hint.hidden = true;
        hint.textContent = inp.getAttribute('data-ee-script') === 'fa' ? 'فقط حروف فارسی مجاز است.' : 'فقط حروف انگلیسی مجاز است.';
        inp.insertAdjacentElement('afterend', hint);
        var t = null;
        function clean() {
            var v = inp.value, c = v.replace(re, '');
            if (c !== v) {
                var pos = inp.selectionStart - (v.length - c.length);
                inp.value = c; try { inp.setSelectionRange(pos, pos); } catch (e) {}
                hint.hidden = false; inp.classList.add('is-shake');
                clearTimeout(t); t = setTimeout(function () { hint.hidden = true; inp.classList.remove('is-shake'); }, 1400);
            }
        }
        inp.addEventListener('input', clean);
        inp.addEventListener('paste', function () { setTimeout(clean, 0); });
        inp.addEventListener('beforeinput', function (e) {
            if (e.data && e.inputType === 'insertText' && re.test(e.data)) { re.lastIndex = 0; e.preventDefault(); hint.hidden = false; inp.classList.add('is-shake'); clearTimeout(t); t = setTimeout(function () { hint.hidden = true; inp.classList.remove('is-shake'); }, 1400); }
            re.lastIndex = 0;
        });
        clean();
    });

    /* تنظیمات حساب: مودال ایمیل و رمز */
    function modalMsg(form, text, ok) {
        var el = form.querySelector('.ee-modal-msg'); if (!el) { return; }
        el.hidden = !text; el.textContent = text || ''; el.className = 'ee-modal-msg ' + (ok ? 'is-ok' : 'is-err');
    }
    var emailForm = $('#eeEmailForm');
    if (emailForm) {
        emailForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var inp = emailForm.querySelector('[name=user_email]'); var v = (inp.value || '').trim();
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) { modalMsg(emailForm, 'آدرس ایمیل معتبر نیست.', false); inp.focus(); return; }
            var btn = emailForm.querySelector('[type=submit]'); var old = btn.textContent; btn.disabled = true; btn.textContent = 'در حال ذخیره...';
            post({ action: 'save_account_settings', user_email: v, security: ($('#settings_nonce') || {}).value || '' }).then(function (res) {
                var ok = !!(res && res.success);
                modalMsg(emailForm, ok ? 'ایمیل ذخیره شد.' : ((res && res.data) || 'خطایی رخ داد!'), ok);
                if (ok) {
                    var view = $('#eeEmailView'); if (view) { view.value = v; }
                    var badge = $('#eeEmailBadge'); if (badge) { badge.hidden = false; }
                    setTimeout(function () { closeModal($('#eeEmailModal')); modalMsg(emailForm, '', true); flash($('#settings-msg'), 'آدرس ایمیل با موفقیت ذخیره شد.', true); }, 900);
                }
            }).catch(function () { modalMsg(emailForm, 'خطای ارتباط با سرور', false); })
              .then(function () { btn.disabled = false; btn.textContent = old; });
        });
    }
    var passForm = $('#eePassForm');
    if (passForm) {
        var p1 = $('#eeNewPass'), p2 = $('#eeNewPass2'), submit = passForm.querySelector('[type=submit]');
        var bars = $$('.ee-strength i', passForm), rules = {};
        $$('.ee-pass-rules li', passForm).forEach(function (li) { rules[li.getAttribute('data-rule')] = li; });
        function evalPass() {
            var v = p1.value || '';
            var ok = { length: v.length >= 8, number: /\d/.test(v), letter: /[A-Za-z\u0600-\u06FF]/.test(v), mix: (/[a-z]/.test(v) && /[A-Z]/.test(v)) || /[^A-Za-z0-9]/.test(v) };
            var score = 0; Object.keys(ok).forEach(function (k) { if (rules[k]) { rules[k].classList.toggle('is-ok', ok[k]); } if (ok[k]) { score++; } });
            if (v.length >= 12) { score = Math.min(4, score + 1); }
            bars.forEach(function (b, i) { b.className = i < score ? (score <= 2 ? 'is-weak' : score === 3 ? 'is-mid' : 'is-strong') : ''; });
            var valid = ok.length && ok.number && ok.letter && p2.value === v && v !== '';
            submit.disabled = !valid;
            if (p2.value && p2.value !== v) { p2.setCustomValidity('x'); } else { p2.setCustomValidity(''); }
            return valid;
        }
        p1.addEventListener('input', evalPass); p2.addEventListener('input', evalPass);
        $$('.ee-eye', passForm).forEach(function (b) {
            b.addEventListener('click', function () { var t = document.getElementById(b.getAttribute('data-ee-eye')); if (t) { t.type = t.type === 'password' ? 'text' : 'password'; } });
        });
        passForm.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!evalPass()) { modalMsg(passForm, p2.value !== p1.value ? 'تکرار رمز با رمز جدید یکسان نیست.' : 'رمز عبور شرایط لازم را ندارد.', false); return; }
            var old = submit.textContent; submit.disabled = true; submit.textContent = 'در حال ذخیره...';
            post({ action: 'save_account_settings', user_password: p1.value, user_password2: p2.value, security: ($('#settings_nonce') || {}).value || '' }).then(function (res) {
                var ok = !!(res && res.success);
                modalMsg(passForm, ok ? 'رمز عبور تغییر کرد.' : ((res && res.data) || 'خطایی رخ داد!'), ok);
                if (ok) { setTimeout(function () { closeModal($('#eePassModal')); p1.value = ''; p2.value = ''; evalPass(); modalMsg(passForm, '', true); flash($('#settings-msg'), 'رمز عبور با موفقیت تغییر کرد.', true); }, 900); }
            }).catch(function () { modalMsg(passForm, 'خطای ارتباط با سرور', false); })
              .then(function () { submit.textContent = old; evalPass(); });
        });
    }

    /* تنظیمات حساب (فرم قدیمی — اگر جایی مانده باشد) */
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
