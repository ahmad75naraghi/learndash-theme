/* کاتالوگ دوره‌ها (صفحهٔ اصلی): تب دسته‌ها + بارگذاری بیشتر — vanilla JS، بدون وابستگی */
(function () {
    'use strict';

    var root = document.querySelector('[data-ee-catalog]');
    if (!root || typeof window.eeCatalog === 'undefined') { return; }

    var cfg = window.eeCatalog;
    var tabsWrap = root.querySelector('[data-ee-ct-tabs]');
    var tabs = Array.prototype.slice.call(root.querySelectorAll('.ee-ct-tab'));
    var panel = root.querySelector('[data-ee-ct-panel]');
    var grid = root.querySelector('[data-ee-ct-grid]');
    var skel = root.querySelector('[data-ee-ct-skel]');
    var more = root.querySelector('[data-ee-ct-more]');
    var end = root.querySelector('[data-ee-ct-end]');
    var allLink = root.querySelector('[data-ee-ct-all]');
    var allTx = root.querySelector('[data-ee-ct-all-tx]');
    var shownEl = root.querySelector('[data-ee-ct-shown]');
    var totalEl = root.querySelector('[data-ee-ct-total2]');
    var progEl = root.querySelector('[data-ee-ct-progress]');
    if (!tabsWrap || !panel || !grid || !more) { return; }

    var FA = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    function fa(n) { return String(n).replace(/\d/g, function (d) { return FA[+d]; }); }

    /* کش سمت مرورگر: cat -> { pages: {n: data}, total } */
    var cache = {};
    var state = { cat: 0, page: 1, total: 0, loading: false, req: 0 };

    /* صفحهٔ اول تب «همه» از HTML سرور */
    cache[0] = { pages: { 1: { html: grid.innerHTML, total: +grid.getAttribute('data-total') || 0, has_more: !more.hidden, page: 1 } } };
    state.total = cache[0].pages[1].total;

    function setStatus(shown, total) {
        if (shownEl) { shownEl.textContent = fa(shown); }
        if (totalEl) { totalEl.textContent = fa(total); }
        if (progEl) { progEl.style.width = (total ? Math.round(shown * 100 / total) : 100) + '%'; }
    }

    function setBusy(b) {
        state.loading = b;
        panel.setAttribute('aria-busy', b ? 'true' : 'false');
        more.classList.toggle('is-loading', b);
    }

    function fetchPage(cat, page) {
        var c = cache[cat] || (cache[cat] = { pages: {} });
        if (c.pages[page]) { return Promise.resolve(c.pages[page]); }
        var url = cfg.ajax + '?action=evented_catalog&nonce=' + encodeURIComponent(cfg.nonce) + '&cat=' + cat + '&page=' + page;
        return fetch(url, { credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) { throw new Error(r.status); } return r.json(); })
            .then(function (j) {
                if (!j || !j.success) { throw new Error('bad'); }
                c.pages[page] = j.data;
                return j.data;
            });
    }

    function renderError(retry) {
        var d = document.createElement('div');
        d.className = 'ee-ct-error';
        d.innerHTML = 'خطا در دریافت دوره‌ها. <button type="button">تلاش دوباره</button>';
        d.querySelector('button').addEventListener('click', function () { d.remove(); retry(); });
        grid.appendChild(d);
    }

    function applyMeta(data, append) {
        state.total = data.total;
        var shown = Math.min(data.total, data.page * (cfg.per || 8));
        setStatus(shown, data.total);
        more.hidden = !data.has_more;
        if (end) { end.hidden = !!data.has_more || data.total === 0; }
        if (!append && data.total === 0) {
            grid.innerHTML = '<div class="ee-ct-empty"><svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-search_off"></use></svg>در این دسته هنوز دوره‌ای منتشر نشده است.</div>';
            if (end) { end.hidden = true; }
        }
    }

    /* ---- تعویض تب ---- */
    function activate(tab, focus) {
        var cat = +tab.getAttribute('data-cat') || 0;
        tabs.forEach(function (t) {
            var on = t === tab;
            t.classList.toggle('is-on', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.setAttribute('tabindex', on ? '0' : '-1');
        });
        panel.setAttribute('aria-labelledby', tab.id);
        if (focus) { tab.focus({ preventScroll: true }); }
        if (typeof tab.scrollIntoView === 'function' && tabsWrap.scrollWidth > tabsWrap.clientWidth) {
            tab.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
        }

        /* لینک «همه» را به آرشیو همان دسته ببر */
        if (allLink) {
            var u = tab.getAttribute('data-url') || allLink.getAttribute('data-base');
            allLink.href = u;
            if (allTx) { allTx.textContent = cat ? 'همهٔ دوره‌های ' + tab.getAttribute('data-name') : 'مشاهدهٔ صفحهٔ همهٔ دوره‌ها'; }
        }

        if (cat === state.cat && !state.loading) { return; }
        state.cat = cat; state.page = 1;
        grid.setAttribute('data-cat', cat); grid.setAttribute('data-page', 1);
        if (end) { end.hidden = true; }

        var my = ++state.req;
        var cached = cache[cat] && cache[cat].pages[1];
        if (!cached) { setBusy(true); if (skel) { skel.hidden = false; } }

        fetchPage(cat, 1).then(function (data) {
            if (my !== state.req) { return; }
            grid.innerHTML = data.html;
            applyMeta(data, false);
        }).catch(function () {
            if (my !== state.req) { return; }
            grid.innerHTML = '';
            renderError(function () { state.cat = -1; activate(tab, false); });
            more.hidden = true;
        }).then(function () {
            if (my !== state.req) { return; }
            setBusy(false); if (skel) { skel.hidden = true; }
        });
    }

    tabs.forEach(function (t) {
        t.addEventListener('click', function () { activate(t, false); });
        t.addEventListener('keydown', function (e) {
            var i = tabs.indexOf(t), n = null;
            if (e.key === 'ArrowLeft') { n = tabs[(i + 1) % tabs.length]; }        /* RTL: چپ = بعدی */
            else if (e.key === 'ArrowRight') { n = tabs[(i - 1 + tabs.length) % tabs.length]; }
            else if (e.key === 'Home') { n = tabs[0]; }
            else if (e.key === 'End') { n = tabs[tabs.length - 1]; }
            if (n) { e.preventDefault(); activate(n, true); }
        });
    });

    /* ---- بارگذاری بیشتر ---- */
    more.addEventListener('click', function () {
        if (state.loading) { return; }
        var cat = state.cat, next = state.page + 1, my = ++state.req;
        setBusy(true); if (skel) { skel.hidden = false; }
        var before = grid.children.length;

        fetchPage(cat, next).then(function (data) {
            if (my !== state.req) { return; }
            state.page = next; grid.setAttribute('data-page', next);
            var tmp = document.createElement('div');
            tmp.innerHTML = data.html;
            var frag = document.createDocumentFragment(), k = 0, first = null;
            while (tmp.firstElementChild) {
                var el = tmp.firstElementChild;
                el.style.setProperty('--i', k++);
                if (!first) { first = el; }
                frag.appendChild(el);
            }
            grid.appendChild(frag);
            applyMeta(data, true);
            /* فوکوس/اسکرول نرم روی اولین کارت تازه */
            if (first && grid.children.length > before) {
                var a = first.querySelector('.ee-cat-title a');
                if (a) { a.focus({ preventScroll: true }); }
                var r = first.getBoundingClientRect();
                if (r.top > window.innerHeight * .8 || r.top < 80) {
                    window.scrollBy({ top: r.top - 120, behavior: 'smooth' });
                }
            }
        }).catch(function () {
            if (my !== state.req) { return; }
            renderError(function () { more.click(); });
        }).then(function () {
            if (my !== state.req) { return; }
            setBusy(false); if (skel) { skel.hidden = true; }
        });
    });

    /* ---- پیش‌واکشی هوشمند: با هاور روی تب، صفحهٔ اول آن را در سکوت بگیر ---- */
    var hoverT;
    tabs.forEach(function (t) {
        t.addEventListener('mouseenter', function () {
            clearTimeout(hoverT);
            hoverT = setTimeout(function () {
                var cat = +t.getAttribute('data-cat') || 0;
                if (!(cache[cat] && cache[cat].pages[1])) { fetchPage(cat, 1).catch(function () {}); }
            }, 160);
        });
        t.addEventListener('mouseleave', function () { clearTimeout(hoverT); });
    });

    /* ---- بازکردن تب از هش آدرس: #ee-catalog=cat-12 ---- */
    var m = /ee-catalog=(cat-\d+|all)/.exec(location.hash);
    if (m) {
        var target = document.getElementById('eeCt-' + m[1]);
        if (target) { activate(target, false); }
    }
})();
