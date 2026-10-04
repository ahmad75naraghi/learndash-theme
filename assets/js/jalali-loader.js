/* Progressive Jalali picker bootstrap; loaded only on screens that contain Persian date inputs. */
(function (window, document) {
    'use strict';
    var selector = 'input[data-jdp],input[data-jalali]';
    function dateKey(parts) { return parts ? parts.year * 10000 + parts.month * 100 + parts.day : 0; }
    function validateRange(input) {
        var form = input.form || input.closest('form') || document;
        var start = form.querySelector('[data-jalali-range="start"]');
        var end = form.querySelector('[data-jalali-range="end"]');
        if (!start || !end || !window.EventedJalali) { return; }
        var a = window.EventedJalali.parse(start.value), b = window.EventedJalali.parse(end.value);
        if (a && b && dateKey(a) > dateKey(b)) { end.setCustomValidity('تاریخ پایان نباید قبل از تاریخ شروع باشد.'); }
        else if (b) { end.setCustomValidity(''); }
    }
    function prepare(root) {
        var inputs = (root || document).querySelectorAll ? (root || document).querySelectorAll(selector) : [];
        Array.prototype.forEach.call(inputs, function (input) {
            if (input.dataset.jalaliReady) { return; }
            input.dataset.jalaliReady = '1';
            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('dir', 'ltr');
            input.setAttribute('autocomplete', input.getAttribute('autocomplete') || 'off');
            input.setAttribute('aria-haspopup', 'dialog');
            if (!input.placeholder) { input.placeholder = input.dataset.jalaliTime != null ? '۱۴۰۵/۰۱/۰۱ ۱۲:۰۰' : '۱۴۰۵/۰۱/۰۱'; }
            input.addEventListener('blur', function () {
                if (!input.value) { input.setCustomValidity(''); return; }
                var parsed = window.EventedJalali && window.EventedJalali.parse(input.value);
                input.setCustomValidity(parsed ? '' : 'تاریخ شمسی معتبر نیست. نمونه: ۱۴۰۵/۰۱/۰۱');
                if (parsed) {
                    var min = window.EventedJalali.parse(input.dataset.jalaliMin || '');
                    var max = window.EventedJalali.parse(input.dataset.jalaliMax || '');
                    if (min && dateKey(parsed) < dateKey(min)) { input.setCustomValidity('تاریخ انتخابی پیش از حد مجاز است.'); }
                    else if (max && dateKey(parsed) > dateKey(max)) { input.setCustomValidity('تاریخ انتخابی پس از حد مجاز است.'); }
                    input.value = window.EventedJalali.format(parsed, input.dataset.jalaliTime != null, true);
                }
                validateRange(input);
            });
        });
    }
    function start() {
        prepare(document);
        if (window.jalaliDatepicker) {
            window.jalaliDatepicker.startWatch({
                selector: selector,
                persianDigits: true,
                showTodayBtn: true,
                showEmptyBtn: true,
                autoReadOnlyInput: false,
                hideAfterChange: true,
                hasSecond: false,
                zIndex: 100000
            });
        }
        if ('MutationObserver' in window) {
            new MutationObserver(function (records) { records.forEach(function (record) { record.addedNodes.forEach(function (node) { if (node.nodeType === 1) { if (node.matches && node.matches(selector)) { prepare(node.parentNode); } else { prepare(node); } } }); }); }).observe(document.body, {childList:true,subtree:true});
        }
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start, {once:true}); } else { start(); }
}(window, document));
