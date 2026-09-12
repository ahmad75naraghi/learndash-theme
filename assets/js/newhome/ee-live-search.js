/**
 * جستجوی زندهٔ هدر evented-edu
 * - debounce 300ms، لغو درخواست‌های قبلی (AbortController)، کش سمت کلاینت
 * - ناوبری با کیبورد (↑ ↓ Enter Esc)، بستن با کلیک بیرون
 * - روی هر فرم .ee-search (دسکتاپ و موبایل) به‌طور مستقل سوار می‌شود
 */
(function () {
    'use strict';

    if (typeof window.eeLiveSearch === 'undefined') { return; }
    var cfg = window.eeLiveSearch;
    var cache = Object.create(null);

    function esc(s) { return String(s).replace(/[&<>"']/g, function (c) { return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]; }); }
    function icon(name) { return '<svg class="ee-ic" aria-hidden="true" focusable="false"><use href="#i-' + esc(name) + '"></use></svg>'; }
    function highlight(title, q) {
        var t = esc(title), words = q.trim().split(/\s+/).filter(Boolean).map(esc);
        if (!words.length) { return t; }
        var re = new RegExp('(' + words.map(function (w) { return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }).join('|') + ')', 'gi');
        return t.replace(re, '<mark>$1</mark>');
    }
    function debounce(fn, ms) { var t; return function () { var a = arguments, c = this; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); }; }

    function mount(form) {
        var input = form.querySelector('input[type="search"]');
        var scopeSel = form.querySelector('select[name="post_type"]');
        if (!input) { return; }

        var box = document.createElement('div');
        box.className = 'ee-ls';
        box.setAttribute('role', 'listbox');
        box.id = (input.id || 'ee-s') + '-live';
        form.appendChild(box);
        input.setAttribute('aria-controls', box.id);
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');

        var ctrl = null, active = -1, items = [];

        function open() { box.classList.add('is-open'); input.setAttribute('aria-expanded', 'true'); }
        function close() { box.classList.remove('is-open'); input.setAttribute('aria-expanded', 'false'); active = -1; }
        function state(html) { box.innerHTML = '<div class="ee-ls-state">' + html + '</div>'; open(); }

        function render(data) {
            items = data.items || [];
            var q = data.q || input.value;
            if (!items.length) {
                state(icon('search_off') + '<span>' + esc(cfg.i18n.empty) + '</span>');
                return;
            }
            var scopeLabel = scopeSel ? scopeSel.options[scopeSel.selectedIndex].text : '';
            var html = '<div class="ee-ls-head"><span>نتایج برای <strong>«' + esc(q) + '»</strong>' + (scopeLabel ? ' در ' + esc(scopeLabel) : '') + '</span><span class="ee-ls-kbd"><kbd>↑</kbd><kbd>↓</kbd><kbd>Enter</kbd></span></div>';
            html += '<ul class="ee-ls-list">';
            items.forEach(function (it, i) {
                html += '<li><a class="ee-ls-item" role="option" data-i="' + i + '" href="' + esc(it.url) + '">';
                html += '<span class="ee-ls-th">' + (it.thumb ? '<img src="' + esc(it.thumb) + '" alt="" loading="lazy" decoding="async">' : icon(it.icon || 'search')) + '</span>';
                html += '<span class="ee-ls-txt"><span class="ee-ls-title">' + highlight(it.title, q) + '</span>';
                html += '<span class="ee-ls-sub"><span class="ee-ls-type">' + icon(it.icon || 'search') + esc(it.type) + '</span>' + (it.cat ? '<span class="ee-ls-cat">' + esc(it.cat) + '</span>' : '') + (it.meta ? '<span class="ee-ls-meta">' + esc(it.meta) + '</span>' : '') + '</span></span>';
                html += '<span class="ee-ls-go">' + icon('arrow_back') + '</span></a></li>';
            });
            html += '</ul>';
            var total = parseInt(data.total, 10) || items.length;
            html += '<div class="ee-ls-foot"><a class="ee-ls-all" href="' + esc(data.more) + '">' + icon('manage_search') + esc(cfg.i18n.all) + (total > items.length ? ' (' + total.toLocaleString('fa-IR') + ')' : '') + '</a></div>';
            box.innerHTML = html;
            open();
        }

        function search() {
            var q = input.value.trim();
            var scope = scopeSel ? scopeSel.value : '';
            if (q.length < (cfg.min || 2)) { close(); return; }
            var key = scope + '|' + q;
            if (cache[key]) { render(cache[key]); return; }
            if (ctrl) { ctrl.abort(); }
            ctrl = ('AbortController' in window) ? new AbortController() : null;
            state('<span class="ee-ls-spin" aria-hidden="true"></span><span>' + esc(cfg.i18n.loading) + '</span>');
            var url = cfg.endpoint + (cfg.endpoint.indexOf('?') > -1 ? '&' : '?') + 'q=' + encodeURIComponent(q) + '&scope=' + encodeURIComponent(scope);
            fetch(url, { credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined, headers: { 'X-WP-Nonce': cfg.nonce || '' } })
                .then(function (r) { return r.json(); })
                .then(function (data) { cache[key] = data; if (input.value.trim() === q) { render(data); } })
                .catch(function (e) { if (e && e.name === 'AbortError') { return; } state(icon('error') + '<span>' + esc(cfg.i18n.error) + '</span>'); });
        }
        var run = debounce(search, 300);

        function setActive(i) {
            var els = box.querySelectorAll('.ee-ls-item');
            if (!els.length) { return; }
            active = (i + els.length) % els.length;
            els.forEach(function (el, k) { el.classList.toggle('is-active', k === active); });
            els[active].scrollIntoView({ block: 'nearest' });
        }

        input.addEventListener('input', run);
        input.addEventListener('focus', function () { if (input.value.trim().length >= (cfg.min || 2)) { search(); } });
        if (scopeSel) { scopeSel.addEventListener('change', function () { cache = Object.create(null); search(); }); }
        input.addEventListener('keydown', function (e) {
            if (!box.classList.contains('is-open')) { return; }
            if (e.key === 'ArrowDown') { e.preventDefault(); setActive(active + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(active - 1); }
            else if (e.key === 'Enter' && active > -1) { var a = box.querySelectorAll('.ee-ls-item')[active]; if (a) { e.preventDefault(); window.location.href = a.href; } }
            else if (e.key === 'Escape') { close(); }
        });
        box.addEventListener('mousemove', function (e) { var a = e.target.closest('.ee-ls-item'); if (a) { setActive(parseInt(a.getAttribute('data-i'), 10)); } });
        document.addEventListener('click', function (e) { if (!form.contains(e.target)) { close(); } });
    }

    document.querySelectorAll('form.ee-search').forEach(mount);
}());
