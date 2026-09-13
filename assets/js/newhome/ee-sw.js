/* evented-edu service worker — پیکربندی در self.EE_SW (از inc/pwa.php تزریق می‌شود) */
(function () {
    'use strict';
    var C = self.EE_SW || {};
    var VER = C.ver || 'ee-dev';
    var ASSETS = VER + '-assets';
    var PAGES = VER + '-pages';
    var OFFLINE = C.offline || '/?ee_offline=1';
    var NEVER = C.never || [];
    var MAX_PAGES = C.maxPages || 40;

    function never(url) {
        for (var i = 0; i < NEVER.length; i++) { if (url.indexOf(NEVER[i]) !== -1) { return true; } }
        return false;
    }
    function isAsset(req, url) {
        if (req.destination && ['style', 'script', 'font', 'image'].indexOf(req.destination) !== -1) { return true; }
        return /\.(css|js|woff2?|ttf|svg|png|jpe?g|webp|gif|ico)(\?|$)/i.test(url);
    }
    function trim(cacheName, max) {
        return caches.open(cacheName).then(function (c) {
            return c.keys().then(function (keys) {
                if (keys.length <= max) { return; }
                return c.delete(keys[0]).then(function () { return trim(cacheName, max); });
            });
        });
    }

    self.addEventListener('install', function (e) {
        e.waitUntil(
            caches.open(ASSETS).then(function (c) {
                return Promise.all((C.precache || []).map(function (u) {
                    return fetch(u, { credentials: 'same-origin' }).then(function (r) { if (r.ok) { return c.put(u, r); } }).catch(function () {});
                }));
            }).then(function () { return self.skipWaiting(); })
        );
    });

    self.addEventListener('activate', function (e) {
        e.waitUntil(
            caches.keys().then(function (keys) {
                return Promise.all(keys.filter(function (k) { return k.indexOf('ee-') === 0 && k.indexOf(VER) !== 0; }).map(function (k) { return caches.delete(k); }));
            }).then(function () { return self.clients.claim(); })
        );
    });

    self.addEventListener('message', function (e) {
        if (e.data && e.data.type === 'SKIP_WAITING') { self.skipWaiting(); }
    });

    self.addEventListener('fetch', function (e) {
        var req = e.request;
        if (req.method !== 'GET') { return; }
        var url = req.url;
        if (url.indexOf(self.location.origin) !== 0) { return; } // فقط same-origin
        if (never(url)) { return; }

        // asset: cache-first
        if (isAsset(req, url)) {
            e.respondWith(
                caches.match(req).then(function (hit) {
                    if (hit) { return hit; }
                    return fetch(req).then(function (res) {
                        if (res && res.ok && (res.type === 'basic' || res.type === 'default')) {
                            var copy = res.clone();
                            caches.open(ASSETS).then(function (c) { c.put(req, copy); });
                        }
                        return res;
                    });
                })
            );
            return;
        }

        // HTML: network-first
        var accept = req.headers.get('accept') || '';
        if (req.mode === 'navigate' || accept.indexOf('text/html') !== -1) {
            e.respondWith(
                fetch(req).then(function (res) {
                    if (res && res.ok && res.type === 'basic' && !res.headers.get('set-cookie')) {
                        var copy = res.clone();
                        caches.open(PAGES).then(function (c) { c.put(req, copy); trim(PAGES, MAX_PAGES); });
                    }
                    return res;
                }).catch(function () {
                    return caches.match(req).then(function (hit) { return hit || caches.match(OFFLINE); });
                })
            );
        }
    });
})();
