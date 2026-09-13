/**
 * PWA client — ثبت سرویس‌ورکر، دکمهٔ نصب، وضعیت آنلاین/آفلاین، اعلان به‌روزرسانی
 */
(function () {
    'use strict';
    var cfg = window.eePwa || {};
    if (!('serviceWorker' in navigator)) { return; }

    function toast(msg, action, onAction) {
        var el = document.getElementById('eePwaToast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'eePwaToast';
            el.className = 'ee-pwa-toast';
            el.setAttribute('role', 'status');
            el.setAttribute('aria-live', 'polite');
            document.body.appendChild(el);
        }
        el.innerHTML = '<span></span>';
        el.firstChild.textContent = msg;
        if (action) {
            var b = document.createElement('button');
            b.type = 'button'; b.textContent = action;
            b.addEventListener('click', function () { el.classList.remove('is-on'); if (onAction) { onAction(); } });
            el.appendChild(b);
        }
        el.classList.add('is-on');
        clearTimeout(el._t);
        if (!action) { el._t = setTimeout(function () { el.classList.remove('is-on'); }, 3500); }
    }

    /* ثبت */
    function register(url) {
        return navigator.serviceWorker.register(url, { scope: '/' }).then(function (reg) {
            reg.addEventListener('updatefound', function () {
                var nw = reg.installing;
                if (!nw) { return; }
                nw.addEventListener('statechange', function () {
                    if (nw.state === 'installed' && navigator.serviceWorker.controller) {
                        toast(cfg.i18n.update, cfg.i18n.reload, function () { userAskedReload = true; nw.postMessage({ type: 'SKIP_WAITING' }); });
                    }
                });
            });
            return reg;
        });
    }
    window.addEventListener('load', function () {
        register(cfg.sw).catch(function () { if (cfg.swFallback) { register(cfg.swFallback).catch(function () {}); } });
    });
    /* فقط وقتی کاربر خودش «به‌روزرسانی» را زده باشد صفحه رفرش شود؛
       در اولین بازدید (نصب اولیهٔ SW) نباید صفحه ناگهان reload شود. */
    var refreshing = false;
    var userAskedReload = false;
    navigator.serviceWorker.addEventListener('controllerchange', function () {
        if (refreshing || !userAskedReload) { return; }
        refreshing = true;
        window.location.reload();
    });

    /* نصب */
    var deferred = null;
    var btn = document.getElementById('eePwaInstall');
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferred = e;
        if (btn && cfg.installBtn) { btn.hidden = false; }
    });
    if (btn) {
        btn.addEventListener('click', function () {
            if (!deferred) { return; }
            deferred.prompt();
            deferred.userChoice.then(function () { deferred = null; btn.hidden = true; });
        });
    }
    window.addEventListener('appinstalled', function () { if (btn) { btn.hidden = true; } toast(cfg.i18n.installed); });

    /* آنلاین/آفلاین */
    window.addEventListener('offline', function () { document.documentElement.classList.add('ee-offline'); toast(cfg.i18n.offline); });
    window.addEventListener('online', function () { document.documentElement.classList.remove('ee-offline'); toast(cfg.i18n.online); });
    if (!navigator.onLine) { document.documentElement.classList.add('ee-offline'); }
})();
