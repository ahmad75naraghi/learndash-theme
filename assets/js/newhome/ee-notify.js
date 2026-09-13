/**
 * اعلان‌های evented-edu — زنگوله، پاپ‌آور، خواندن، پولینگ سبک، توست اعلان تازه
 */
(function () {
    'use strict';
    if (typeof window.eeNotify === 'undefined') { return; }
    var cfg = window.eeNotify;
    var root = document.querySelector('[data-ee-notify]');
    if (!root) { return; }
    var btn = root.querySelector('#eeNotifyBtn');
    var pop = root.querySelector('#eeNotifyPop');
    var badge = root.querySelector('[data-ee-notify-count]');
    var lastUnread = parseInt((badge && badge.textContent.replace(/[^\d۰-۹]/g, '').replace(/[۰-۹]/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'.indexOf(d); })) || '0', 10) || 0;
    var loaded = false, items = [], hidden = document.hidden;

    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); }
    function icon(n) { return '<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-' + esc(n) + '"></use></svg>'; }
    function fa(n) { return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; }); }
    function api(path, method, data) {
        var url = cfg.endpoint + (path || '');
        var opt = { method: method || 'GET', credentials: 'same-origin', headers: { 'X-WP-Nonce': cfg.nonce || '' } };
        if (data) { opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(data); }
        return fetch(url, opt).then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); });
    }
    function setUnread(n) {
        n = parseInt(n, 10) || 0;
        if (badge) { badge.textContent = n > 99 ? '۹۹+' : fa(n); badge.hidden = n === 0; }
        var use = btn.querySelector('use'); if (use) { use.setAttribute('href', n ? '#i-notifications_active' : '#i-notifications'); }
        if (n > lastUnread) { btn.classList.remove('has-new'); void btn.offsetWidth; btn.classList.add('has-new'); }
        lastUnread = n;
    }

    function render(data) {
        items = data.items || [];
        var unread = data.unread || 0;
        var h = '<div class="ee-nb-head"><h3>' + icon('notifications') + esc(cfg.i18n.title) + (unread ? ' <span class="cnt">' + fa(unread) + '</span>' : '') + '</h3>';
        h += '<div class="ee-nb-actions">' + (unread ? '<button type="button" data-act="read-all">' + icon('done_all') + esc(cfg.i18n.readAll) + '</button>' : '') + (items.length ? '<button type="button" data-act="clear">' + icon('delete') + esc(cfg.i18n.clear) + '</button>' : '') + '</div></div>';
        if (!items.length) {
            h += '<div class="ee-nb-empty"><span class="big">' + icon('notifications') + '</span><strong>' + esc(cfg.i18n.empty) + '</strong><span>' + esc(cfg.i18n.emptySub) + '</span></div>';
        } else {
            h += '<ul class="ee-nb-list">';
            items.forEach(function (it) {
                var tag = it.url ? 'a' : 'div';
                h += '<li><' + tag + ' class="ee-nb-item' + (it.is_read ? '' : ' is-unread') + '" data-id="' + it.id + '" data-type="' + esc(it.type) + '"' + (it.url ? ' href="' + esc(it.url) + '"' : '') + '>';
                h += '<span class="ee-nb-ic">' + icon(it.icon) + '</span><span class="ee-nb-txt"><span class="ee-nb-title">' + esc(it.title) + '</span>' + (it.body ? '<span class="ee-nb-body">' + esc(it.body) + '</span>' : '') + '<span class="ee-nb-time">' + icon('schedule') + esc(it.ago) + (it.is_read ? '' : ' · <b style="color:var(--ee-tealP)">' + esc(cfg.i18n.new) + '</b>') + '</span></span>';
                if (!it.is_read) { h += '<button type="button" class="ee-nb-x" data-read="' + it.id + '" title="علامت خوانده‌شده">' + icon('check') + '</button>'; }
                h += '</' + tag + '></li>';
            });
            h += '</ul>';
        }
        pop.innerHTML = h;
        setUnread(unread);
    }

    function load(force) {
        if (loaded && !force) { return Promise.resolve(); }
        pop.innerHTML = '<div class="ee-nb-state"><span class="ee-nb-spin"></span><span>' + esc(cfg.i18n.loading) + '</span></div>';
        return api('', 'GET').then(function (d) { loaded = true; render(d); }).catch(function () { pop.innerHTML = '<div class="ee-nb-state">' + icon('error') + '<span>' + esc(cfg.i18n.error) + '</span></div>'; });
    }
    function open() { pop.hidden = false; requestAnimationFrame(function () { pop.classList.add('is-open'); }); btn.setAttribute('aria-expanded', 'true'); load(true); }
    function close() { pop.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); setTimeout(function () { if (!pop.classList.contains('is-open')) { pop.hidden = true; } }, 170); }
    function toggle() { if (pop.hidden) { open(); } else { close(); } }

    btn.addEventListener('click', toggle);
    document.addEventListener('click', function (e) { if (!root.contains(e.target)) { close(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });

    pop.addEventListener('click', function (e) {
        var act = e.target.closest('[data-act]');
        if (act) {
            e.preventDefault();
            var a = act.getAttribute('data-act');
            api('/' + a, 'POST').then(function () { return load(true); });
            return;
        }
        var rd = e.target.closest('[data-read]');
        if (rd) {
            e.preventDefault(); e.stopPropagation();
            var id = rd.getAttribute('data-read');
            api('/read', 'POST', { id: id }).then(function (d) { setUnread(d.unread); var li = rd.closest('.ee-nb-item'); if (li) { li.classList.remove('is-unread'); rd.remove(); } });
            return;
        }
        var item = e.target.closest('.ee-nb-item.is-unread');
        if (item && item.tagName === 'A') {
            // علامت خوانده‌شده قبل از ناوبری (sendBeacon)
            var id2 = item.getAttribute('data-id');
            try {
                var blob = new Blob([JSON.stringify({ id: id2 })], { type: 'application/json' });
                if (!navigator.sendBeacon || !navigator.sendBeacon(cfg.endpoint + '/read?_wpnonce=' + encodeURIComponent(cfg.nonce), blob)) {
                    api('/read', 'POST', { id: id2 });
                }
            } catch (err) { api('/read', 'POST', { id: id2 }); }
        }
    });

    /* توست اعلان تازه */
    function toast(it) {
        var t = document.createElement('div');
        t.className = 'ee-nb-toast';
        t.innerHTML = icon(it.icon || 'notifications') + '<div><strong>' + esc(it.title) + '</strong>' + (it.body ? '<span>' + esc(it.body) + '</span>' : '') + '</div>';
        t.addEventListener('click', function () { if (it.url) { window.location.href = it.url; } else { open(); } });
        document.body.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('is-in'); });
        setTimeout(function () { t.classList.remove('is-in'); setTimeout(function () { t.remove(); }, 300); }, 6000);
    }

    /* پولینگ سبک: فقط وقتی تب فعال است */
    var pollMs = Math.max(30, parseInt(cfg.poll, 10) || 90) * 1000;
    function poll() {
        if (document.hidden) { return; }
        api('?limit=3', 'GET').then(function (d) {
            var prev = lastUnread;
            setUnread(d.unread);
            if (d.unread > prev && d.items && d.items.length) {
                var fresh = d.items.filter(function (i) { return !i.is_read; })[0];
                if (fresh && pop.hidden) { toast(fresh); }
                loaded = false;
            }
        }).catch(function () { /* بی‌صدا */ });
    }
    setInterval(poll, pollMs);
    document.addEventListener('visibilitychange', function () { if (!document.hidden && hidden) { poll(); } hidden = document.hidden; });
}());
