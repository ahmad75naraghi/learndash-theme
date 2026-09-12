/** انتخاب‌گر ستاره در فرم دیدگاه نوشته‌ها (inc/reviews.php) */
(function () {
    'use strict';
    var labels = ['', 'خیلی ضعیف', 'ضعیف', 'متوسط', 'خوب', 'عالی'];
    document.querySelectorAll('[data-ee-rate-pick]').forEach(function (box) {
        var stars = box.querySelector('.ee-rate-stars');
        var txt = box.querySelector('.ee-rate-txt');
        var items = Array.prototype.slice.call(box.querySelectorAll('.ee-rate-star'));
        function paint(v) {
            stars.classList.toggle('has-value', v > 0);
            items.forEach(function (l) { l.classList.toggle('is-on', parseInt(l.querySelector('input').value, 10) <= v); });
            if (txt) { txt.textContent = v ? labels[v] : ''; }
        }
        box.addEventListener('change', function (e) {
            if (e.target.name === 'ee_rating') { paint(parseInt(e.target.value, 10) || 0); }
        });
        paint(0);
        // در حالت «پاسخ به دیدگاه» امتیاز معنا ندارد
        var form = box.closest('form');
        if (form) {
            var parent = form.querySelector('#comment_parent');
            var obs = new MutationObserver(function () { box.hidden = parseInt(parent.value, 10) > 0; });
            if (parent) { obs.observe(parent, { attributes: true, attributeFilter: ['value'] }); }
            var cancel = document.getElementById('cancel-comment-reply-link');
            if (cancel) { cancel.addEventListener('click', function () { box.hidden = false; }); }
            document.addEventListener('click', function (e) { if (e.target.closest && e.target.closest('.comment-reply-link')) { setTimeout(function () { box.hidden = true; }, 0); } });
        }
    });
})();
