/* evented-edu صفحهٔ اصلی — رفتارهای کوچک (منو/چیپ/اسلایدر) */
(function () {
    'use strict';

    /* ---------- کشوی منوی موبایل (off-canvas) ---------- */
    var drawer   = document.getElementById('eeDrawer');
    var backdrop = document.getElementById('eeDrawerBackdrop');
    var hamb     = document.getElementById('eeHamb');
    var closeBtn = document.getElementById('eeDrawerClose');
    var lastFocus = null;

    function openDrawer() {
        if (!drawer) { return; }
        lastFocus = document.activeElement;
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
        if (backdrop) { backdrop.hidden = false; requestAnimationFrame(function () { backdrop.classList.add('is-on'); }); }
        document.body.classList.add('ee-drawer-lock');
        if (hamb) { hamb.setAttribute('aria-expanded', 'true'); }
        window.setTimeout(function () { (closeBtn || drawer).focus(); }, 30);
    }
    function closeDrawer() {
        if (!drawer || !drawer.classList.contains('is-open')) { return; }
        drawer.classList.remove('is-open');
        drawer.setAttribute('aria-hidden', 'true');
        if (backdrop) { backdrop.classList.remove('is-on'); window.setTimeout(function () { backdrop.hidden = true; }, 220); }
        document.body.classList.remove('ee-drawer-lock');
        if (hamb) { hamb.setAttribute('aria-expanded', 'false'); }
        if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
    }

    if (drawer) {
        if (hamb) { hamb.addEventListener('click', openDrawer); }
        document.querySelectorAll('[data-ee-drawer-open]').forEach(function (b) { b.addEventListener('click', openDrawer); });
        if (closeBtn) { closeBtn.addEventListener('click', closeDrawer); }
        if (backdrop) { backdrop.addEventListener('click', closeDrawer); }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeDrawer(); }
        });
        // بستن هنگام بازگشت به دسکتاپ
        if (window.matchMedia) {
            var mq = window.matchMedia('(min-width: 900px)');
            var onMq = function (ev) { if (ev.matches) { closeDrawer(); } };
            if (mq.addEventListener) { mq.addEventListener('change', onMq); } else if (mq.addListener) { mq.addListener(onMq); }
        }

        // آکاردئون زیرمنوها
        drawer.querySelectorAll('.ee-dn-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.ee-dn-item');
                var sub  = item ? item.querySelector('.ee-dn-sub') : null;
                if (!sub) { return; }
                var open = btn.getAttribute('aria-expanded') === 'true';
                // فقط یک زیرمنو باز بماند
                drawer.querySelectorAll('.ee-dn-item.is-open').forEach(function (o) {
                    if (o !== item) {
                        o.classList.remove('is-open');
                        var ob = o.querySelector('.ee-dn-toggle'); if (ob) { ob.setAttribute('aria-expanded', 'false'); }
                        var os = o.querySelector('.ee-dn-sub'); if (os) { os.hidden = true; }
                    }
                });
                btn.setAttribute('aria-expanded', open ? 'false' : 'true');
                sub.hidden = open;
                item.classList.toggle('is-open', !open);
            });
        });
        // آیتم فعال از ابتدا باز باشد
        var activeItem = drawer.querySelector('.ee-dn-item.ee-active.has-sub');
        if (activeItem) {
            var ab = activeItem.querySelector('.ee-dn-toggle');
            if (ab) { ab.click(); }
        }
    }

    /* ---------- زیرمنوی دسکتاپ: باز شدن با کلید/لمس (هاور در CSS) ---------- */
    document.querySelectorAll('.ee-nav .ee-nav-item.has-sub > a').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var item = a.parentNode;
            var coarse = !!(window.matchMedia && window.matchMedia('(hover: none)').matches);
            if (coarse && !item.classList.contains('is-open')) {
                // لمس اول: باز کردن زیرمنو؛ لمس دوم: رفتن به لینک
                e.preventDefault();
                document.querySelectorAll('.ee-nav .ee-nav-item.is-open').forEach(function (o) { o.classList.remove('is-open'); });
                item.classList.add('is-open');
            }
        });
        a.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                var first = a.parentNode.querySelector('.ee-sub a');
                a.parentNode.classList.add('is-open');
                if (first) { first.focus(); }
            }
        });
    });
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.ee-nav-item')) {
            document.querySelectorAll('.ee-nav .ee-nav-item.is-open').forEach(function (o) { o.classList.remove('is-open'); });
        }
    });

    /* ---------- جستجوی موبایل: باز/بسته با دکمهٔ ذره‌بین ---------- */
    var sToggle = document.getElementById('eeSearchToggle');
    var sBox    = document.getElementById('eeSearchM');
    if (sToggle && sBox) {
        // اگر در صفحهٔ نتایج جستجو هستیم، باز باشد
        if (document.body.classList.contains('search')) {
            sBox.hidden = false;
            sToggle.setAttribute('aria-expanded', 'true');
        }
        sToggle.addEventListener('click', function () {
            var open = !sBox.hidden;
            sBox.hidden = open;
            sToggle.setAttribute('aria-expanded', open ? 'false' : 'true');
            if (!open) {
                var inp = sBox.querySelector('input[type="search"]');
                if (inp) { inp.focus(); }
            }
        });
    }

    /* ---------- تب‌ها (مقالات و دوره‌های صفحهٔ اصلی) ---------- */
    document.querySelectorAll('[role="tablist"]').forEach(function (tabsWrap) {
        var tabBtns = tabsWrap.querySelectorAll('[role="tab"]');
        if (!tabBtns.length) { return; }
        var panels = [];
        tabBtns.forEach(function (b) { var p = document.getElementById(b.getAttribute('aria-controls') || ''); if (p) { panels.push(p); } });
        var activate = function (btn) {
            tabBtns.forEach(function (b) {
                var on = b === btn;
                b.classList.toggle('ee-on', on);
                b.setAttribute('aria-selected', on ? 'true' : 'false');
                b.setAttribute('tabindex', on ? '0' : '-1');
            });
            var target = btn.getAttribute('aria-controls');
            panels.forEach(function (p) { p.hidden = p.id !== target; });
            // تب فعال را در نوار اسکرول‌شونده به دید بیاور
            if (btn.scrollIntoView) { try { btn.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' }); } catch (e) {} }
        };
        tabBtns.forEach(function (b, i) {
            b.addEventListener('click', function () { activate(b); });
            b.addEventListener('keydown', function (e) {
                var n = null;
                if (e.key === 'ArrowLeft')  { n = tabBtns[(i + 1) % tabBtns.length]; }
                if (e.key === 'ArrowRight') { n = tabBtns[(i - 1 + tabBtns.length) % tabBtns.length]; }
                if (e.key === 'Home') { n = tabBtns[0]; }
                if (e.key === 'End') { n = tabBtns[tabBtns.length - 1]; }
                if (n) { e.preventDefault(); n.focus(); activate(n); }
            });
        });
    });

    /* ---------- چیپ‌های دسته‌بندی: فقط جابه‌جایی حالت فعال ---------- */
    var chips = document.querySelectorAll('.ee-chip-btn:not([role="tab"])');
    chips.forEach(function (c) {
        c.addEventListener('click', function () {
            chips.forEach(function (x) { x.classList.remove('ee-on'); });
            c.classList.add('ee-on');
        });
    });

    /* ---------- اسلایدر هیرو ---------- */
    var slider     = document.getElementById('eeSlider');
    var ctrlWrap   = document.getElementById('eeSliderControls');
    if (slider && ctrlWrap) {
        var slides   = slider.querySelectorAll('.ee-slide');
        var dotsWrap = document.getElementById('eeSliderDots');
        var dots     = dotsWrap ? dotsWrap.querySelectorAll('.ee-slide-dot') : [];
        var count    = slides.length;
        var current  = 0;
        var timer    = null;
        var DELAY    = 6000;

        function goTo(index) {
            if (!slides.length) { return; }
            current = (index + count) % count;
            slides.forEach(function (s, i) {
                s.classList.toggle('is-active', i === current);
            });
            dots.forEach(function (d, i) {
                d.classList.toggle('is-active', i === current);
            });
        }

        function next() { goTo(current + 1); }
        function prev() { goTo(current - 1); }

        function play() {
            stop();
            if (count > 1) {
                timer = window.setInterval(next, DELAY);
            }
        }
        function stop() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        // دکمه‌های جهت
        ctrlWrap.querySelectorAll('.ee-slide-arrow').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var dir = parseInt(btn.getAttribute('data-dir'), 10) || 1;
                stop();
                dir > 0 ? next() : prev();
                play();
            });
        });

        // نقطه‌ها
        if (dotsWrap) {
            dotsWrap.addEventListener('click', function (e) {
                var dot = e.target.closest('.ee-slide-dot');
                if (!dot) { return; }
                stop();
                goTo(parseInt(dot.getAttribute('data-go'), 10) || 0);
                play();
            });
        }

        // سوایپ / درگ (لمسی و ماوس) با Pointer Events
        var drag = { on: false, x0: 0, y0: 0, dx: 0, moved: false, id: null, blockUntil: 0 };
        var active = function () { return slides[current]; };
        slider.addEventListener('pointerdown', function (e) {
            if (e.button !== undefined && e.button !== 0) { return; }
            if (e.target.closest('.ee-slider-controls')) { return; }
            drag.on = true; drag.moved = false; drag.x0 = e.clientX; drag.y0 = e.clientY; drag.dx = 0; drag.id = e.pointerId;
            stop();
        });
        slider.addEventListener('pointermove', function (e) {
            if (!drag.on) { return; }
            var dx = e.clientX - drag.x0, dy = e.clientY - drag.y0;
            if (!drag.moved) {
                if (Math.abs(dx) < 8) { return; }
                if (Math.abs(dy) > Math.abs(dx)) { drag.on = false; return; } // اسکرول عمودی
                drag.moved = true;
                slider.classList.add('is-dragging');
                try { slider.setPointerCapture(drag.id); } catch (err) {}
            }
            drag.dx = dx;
            var el = active();
            if (el) { el.style.transform = 'translateX(' + dx + 'px)'; el.style.opacity = String(Math.max(.35, 1 - Math.abs(dx) / 600)); }
            e.preventDefault();
        });
        var endDrag = function () {
            if (!drag.on) { return; }
            drag.on = false;
            var el = active();
            slider.classList.remove('is-dragging');
            if (el) { el.style.transform = ''; el.style.opacity = ''; }
            if (drag.moved) {
                var th = Math.min(90, slider.clientWidth * 0.18);
                if (drag.dx > th) { prev(); }        // کشیدن به راست → قبلی (RTL)
                else if (drag.dx < -th) { next(); }  // کشیدن به چپ → بعدی
                // جلوگیری از کلیک لینک بعد از درگ
                drag.blockUntil = Date.now() + 400;
            }
            play();
        };
        slider.addEventListener('click', function (ev) {
            if (drag.blockUntil && Date.now() < drag.blockUntil) { ev.preventDefault(); ev.stopPropagation(); }
        }, true);
        slider.addEventListener('dragstart', function (ev) { ev.preventDefault(); });
        slider.addEventListener('pointerup', endDrag);
        slider.addEventListener('pointercancel', endDrag);
        slider.addEventListener('pointerleave', function () { if (drag.on && drag.moved) { endDrag(); } });

        // کیبورد
        slider.setAttribute('tabindex', '0');
        slider.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowLeft') { stop(); next(); play(); }
            if (e.key === 'ArrowRight') { stop(); prev(); play(); }
        });

        // توقف خودکار هنگام هاور
        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', play);
        ctrlWrap.addEventListener('mouseenter', stop);
        ctrlWrap.addEventListener('mouseleave', play);

        // احترام به reduced motion
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            // بدون حرکت خودکار
        } else {
            play();
        }
    }

    /* ---------- کپی لینک (دکمهٔ اشتراک‌گذاری) ---------- */
    var copyBtns = document.querySelectorAll('.ee-share-copy');
    if (copyBtns.length) {
        var toast = document.getElementById('eeShareToast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'eeShareToast';
            toast.className = 'ee-share-toast';
            document.body.appendChild(toast);
        }

        var toastTimer = null;
        var showToast = function (message) {
            toast.textContent = message;
            toast.classList.add('is-on');
            window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(function () {
                toast.classList.remove('is-on');
            }, 2200);
        };

        copyBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var value = btn.getAttribute('data-copy') || window.location.href;

                var done = function () { showToast('لینک کپی شد'); };
                var failed = function () { showToast('کپی لینک ممکن نشد'); };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(value).then(done).catch(failed);
                    return;
                }

                /* مسیر جایگزین برای مرورگرهای قدیمی/بدون HTTPS */
                var field = document.createElement('textarea');
                field.value = value;
                field.setAttribute('readonly', '');
                field.style.position = 'fixed';
                field.style.opacity = '0';
                document.body.appendChild(field);
                field.select();
                try {
                    document.execCommand('copy') ? done() : failed();
                } catch (err) {
                    failed();
                }
                document.body.removeChild(field);
            });
        });
    }

    /* ---------- لینک‌های placeholder داخلی (#) بی‌صدا باشند ---------- */
    document.querySelectorAll('.ee-home a[href="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            e.preventDefault();
        });
    });
})();
