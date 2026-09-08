/* evented-edu صفحهٔ اصلی — رفتارهای کوچک (منو/چیپ) */
(function () {
    'use strict';

    // هَم‌برگر موبایل: باز/بسته کردن منوی قرصی
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

    // فیلتر چیپ‌های «همه دوره‌ها»/دسته‌ها — چون کارت‌ها سمت سرور رندر شده‌اند،
    // اینجا فقط ظاهر فعال/غیرفعال چیپ عوض می‌شود و به #eeCourseGrid اسکرول می‌کند.
    var chips = document.querySelectorAll('.ee-chip-btn');
    var onChipClick = function (e) {
        var btn = e.currentTarget;
        chips.forEach(function (c) { c.classList.remove('ee-on'); });
        btn.classList.add('ee-on');
    };
    chips.forEach(function (c) {
        c.addEventListener('click', onChipClick);
    });

    // لینک‌های placeholder داخلی (#) بی‌صدا باشند تا کاربر به بالای صفحه نپرد
    document.querySelectorAll('.ee-home a[href="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            if (a.getAttribute('href') === '#') {
                e.preventDefault();
            }
        });
    });
})();
