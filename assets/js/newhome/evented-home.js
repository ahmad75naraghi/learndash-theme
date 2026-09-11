/* evented-edu صفحهٔ اصلی — رفتارهای کوچک (منو/چیپ/اسلایدر) */
(function () {
    'use strict';

    /* ---------- هَمبرگر موبایل: باز/بسته کردن منوی قرصی ---------- */
    var hamb = document.getElementById('eeHamb');
    var nav = document.getElementById('eeNav');
    if (hamb && nav) {
        hamb.addEventListener('click', function () {
            var isHidden = nav.style.display === 'none' || getComputedStyle(nav).display === 'none';
            nav.style.display = isHidden ? 'flex' : 'none';
            nav.style.flexWrap = 'wrap';
            nav.style.paddingBottom = '0.5rem';
        });
    }

    /* ---------- چیپ‌های دسته‌بندی: فقط جابه‌جایی حالت فعال ---------- */
    var chips = document.querySelectorAll('.ee-chip-btn');
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
