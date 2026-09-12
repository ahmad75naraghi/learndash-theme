/**
 * ee-lms.js — رفتارهای صفحهٔ دوره و درس (بدون jQuery)
 *
 *  ۱. آکاردئون‌های معرفی/کلیپ/پادکست/متن
 *  ۲. جمع‌کردن/بازکردن فصل‌های فهرست درس‌ها (دکمهٔ «باز کردن همه»)
 *  ۳. ثبت دیدگاه + امتیاز دوره (AJAX: submit_course_review)
 *  ۴. علامت‌گذاری تکمیل درس (AJAX: custom_mark_lesson_complete) + به‌روزرسانی پیشرفت
 *  ۵. علاقه‌مندی‌ها (AJAX: toggle_course_wishlist)
 *  ۶. اسکرول فهرست درس‌ها به درس جاری
 *
 * @package evented-edu
 */
(function () {
    'use strict';


    /* تعویض آیکن SVG اسپرایت (جایگزین متن فونت آیکن) */
    function setIcon(el, name) {
        if (!el) { return; }
        var use = el.querySelector ? el.querySelector('use') : null;
        if (use) { use.setAttribute('href', '#i-' + name); } else { el.textContent = name; }
    }
    /* ---------- ابزارها ---------- */
    function cfg() {
        if (window.eeLms && window.eeLms.ajax_url) { return window.eeLms.ajax_url; }
        if (window.ajax_object && window.ajax_object.ajax_url) { return window.ajax_object.ajax_url; }
        return '/wp-admin/admin-ajax.php';
    }

    function post(body) {
        return fetch(cfg(), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: Object.keys(body).map(function (k) {
                return encodeURIComponent(k) + '=' + encodeURIComponent(body[k]);
            }).join('&')
        }).then(function (r) { return r.json(); });
    }

    function msg(el, text, isError) {
        if (!el) { return; }
        el.textContent = text || '';
        el.classList.toggle('is-error', !!isError);
    }

    /* ---------- ۱. آکاردئون ---------- */
    function initAccordions() {
        document.querySelectorAll('.ee-acc-head').forEach(function (head) {
            head.addEventListener('click', function () {
                var box = head.closest('.ee-acc');
                if (!box) { return; }
                var open = box.classList.toggle('is-open');
                head.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });
    }

    /* ---------- ۲. گروه‌های جمع‌شونده (فهرست درس‌ها) ---------- */
    function setGroup(group, open) {
        group.classList.toggle('is-open', open);
        var head = group.querySelector('.ee-ln-group-head');
        if (head) { head.setAttribute('aria-expanded', open ? 'true' : 'false'); }
    }

    function initGroups() {
        document.querySelectorAll('.ee-ln-group-head').forEach(function (head) {
            head.addEventListener('click', function () {
                var group = head.closest('.ee-ln-group');
                if (group) { setGroup(group, !group.classList.contains('is-open')); }
            });
        });

        var expandAll = document.querySelector('[data-expand-all]');
        if (expandAll) {
            var labels = {
                expand: expandAll.dataset.expandLabel || 'باز کردن همه',
                collapse: expandAll.dataset.collapseLabel || 'بستن همه'
            };
            expandAll.addEventListener('click', function () {
                var groups = document.querySelectorAll('.ee-ln-group');
                var open = expandAll.getAttribute('aria-expanded') !== 'true';
                groups.forEach(function (g) { setGroup(g, open); });
                expandAll.setAttribute('aria-expanded', open ? 'true' : 'false');
                expandAll.textContent = open ? labels.collapse : labels.expand;
            });
        }
    }

    /* ---------- ۶. اسکرول به درس جاری ---------- */
    function scrollToCurrent() {
        var list = document.querySelector('.ee-ln-list');
        var current = document.querySelector('.ee-ln-item.is-current');
        if (list && current && list.scrollHeight > list.clientHeight) {
            list.scrollTop = Math.max(0, current.offsetTop - list.clientHeight / 2);
        }
    }

    /* ---------- ۳. دیدگاه + امتیاز ---------- */
    function initStars(form) {
        var input = form.querySelector('input[name="rating"]');
        var buttons = form.querySelectorAll('.ee-star-btn');
        if (!input || !buttons.length) { return; }

        function paint(value) {
            buttons.forEach(function (btn) {
                var on = parseInt(btn.dataset.value, 10) <= value;
                btn.classList.toggle('is-on', on);
            });
        }

        buttons.forEach(function (btn) {
            var value = parseInt(btn.dataset.value, 10);
            btn.addEventListener('mouseenter', function () { paint(value); });
            btn.addEventListener('focus', function () { paint(value); });
            btn.addEventListener('click', function () {
                input.value = String(value);
                paint(value);
            });
        });

        var wrap = form.querySelector('.ee-review-stars');
        if (wrap) { wrap.addEventListener('mouseleave', function () { paint(parseInt(input.value, 10) || 0); }); }
    }

    function initReview() {
        document.querySelectorAll('.ee-review-form').forEach(function (form) {
            initStars(form);

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                var button = form.querySelector('[type="submit"]');
                var box = form.querySelector('.ee-review-msg');
                var textarea = form.querySelector('textarea[name="content"]');
                var rating = parseInt(form.querySelector('input[name="rating"]').value, 10) || 0;
                var content = textarea ? textarea.value.trim() : '';

                if (!rating) { msg(box, 'لطفاً امتیاز خود را انتخاب کنید.', true); return; }
                if (!content) { msg(box, 'متن دیدگاه را بنویسید.', true); return; }

                if (button) { button.disabled = true; }
                msg(box, 'در حال ارسال...', false);

                post({
                    action: 'submit_course_review',
                    security: form.dataset.nonce || '',
                    course_id: form.dataset.course || '0',
                    rating: rating,
                    content: content
                }).then(function (res) {
                    var ok = res && res.success;
                    msg(box, (res && res.data) ? res.data : (ok ? 'ثبت شد.' : 'خطا در ثبت دیدگاه.'), !ok);
                    if (ok && textarea) {
                        textarea.value = '';
                        form.querySelector('input[name="rating"]').value = '0';
                        form.querySelectorAll('.ee-star-btn').forEach(function (b) { b.classList.remove('is-on'); });
                    }
                }).catch(function () {
                    msg(box, 'ارتباط با سرور برقرار نشد.', true);
                }).then(function () {
                    if (button) { button.disabled = false; }
                });
            });
        });
    }

    /* ---------- ۴. تکمیل درس ---------- */
    function toFa(value) {
        var map = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return String(value).replace(/\d/g, function (d) { return map[d]; });
    }

    function applyProgress(data) {
        if (!data) { return; }
        var percent = parseInt(data.percentage, 10) || 0;

        document.querySelectorAll('.ee-progress-fill').forEach(function (el) { el.style.width = percent + '%'; });
        document.querySelectorAll('.ee-progress-num').forEach(function (el) { el.textContent = toFa(percent) + '٪'; });
        document.querySelectorAll('#eeProgress').forEach(function (el) { el.dataset.percent = String(percent); });
        document.querySelectorAll('.ee-ln-bar span').forEach(function (el) { el.style.width = percent + '%'; });

        if (typeof data.completed !== 'undefined' && typeof data.total !== 'undefined') {
            var text = toFa(data.completed) + ' از ' + toFa(data.total) + ' درس';
            document.querySelectorAll('.ee-ln-count').forEach(function (el) { el.textContent = text; });
        }
    }

    function initMarkComplete() {
        document.querySelectorAll('.ee-mark-btn').forEach(function (button) {
            if (button.dataset.isDone === '1') { return; }

            button.addEventListener('click', function () {
                var box = document.querySelector('.ee-mark-msg');
                button.disabled = true;
                msg(box, 'در حال ثبت...', false);

                post({
                    action: 'custom_mark_lesson_complete',
                    security: button.dataset.nonce || '',
                    lesson_id: button.dataset.lesson || '0',
                    course_id: button.dataset.course || '0'
                }).then(function (res) {
                    var ok = res && res.success;
                    if (ok) {
                        button.classList.add('is-done');
                        button.setAttribute('aria-pressed', 'true');
                        button.dataset.isDone = '1';
                        var label = button.querySelector('[data-mark-label]');
                        if (label) { label.textContent = 'تکمیل شد'; }
                        var icon = button.querySelector('.ee-ic');
                        if (icon) { setIcon(icon, 'task_alt'); }
                        applyProgress(res.data);
                        msg(box, 'این درس تکمیل شد.', false);
                    } else {
                        msg(box, (res && res.data) ? res.data : 'خطا در ثبت تکمیل درس.', true);
                        button.disabled = false;
                    }
                }).catch(function () {
                    msg(box, 'ارتباط با سرور برقرار نشد.', true);
                    button.disabled = false;
                });
            });
        });
    }

    /* ---------- ۵. علاقه‌مندی‌ها ---------- */
    function initWishlist() {
        document.querySelectorAll('.ee-wishlist').forEach(function (button) {
            button.addEventListener('click', function () {
                if (button.dataset.loggedIn !== '1') {
                    window.location.href = button.dataset.loginUrl || '/';
                    return;
                }

                var label = button.querySelector('.ee-wishlist-text');
                var icon = button.querySelector('.ee-ic');
                var busy = button.disabled;
                if (busy) { return; }

                button.disabled = true;

                post({
                    action: 'toggle_course_wishlist',
                    security: button.dataset.nonce || '',
                    course_id: button.dataset.course || '0'
                }).then(function (res) {
                    var status = (res && res.success && res.data) ? res.data.status : '';
                    var on = (status === 'added') || (!status && !button.classList.contains('is-on'));
                    button.classList.toggle('is-on', on);
                    if (label) { label.textContent = on ? 'در علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها'; }
                    if (icon) { setIcon(icon, on ? 'favorite' : 'favorite_border'); }
                }).catch(function () {
                    /* خطای شبکه: وضعیت تغییری نمی‌کند */
                }).then(function () {
                    button.disabled = false;
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initAccordions();
        initGroups();
        initReview();
        initMarkComplete();
        initWishlist();
        scrollToCurrent();
    });
}());
