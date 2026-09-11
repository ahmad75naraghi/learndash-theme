/*
 * تنظیمات قالب evented-edu — مدیریت اسلایدرهای صفحهٔ اصلی
 *
 * سه نکتهٔ طراحی که این فایل را در برابر خرابی‌های رایج پیشخوان مقاوم می‌کند:
 *   ۱) همهٔ هندلرها از `document` واگذار (delegate) می‌شوند؛ پس مهم نیست اسکریپت
 *      در فوتر چاپ شود یا هدر، یا ردیف‌ها بعداً با JS ساخته شوند.
 *   ۲) در نبود `wp.media` (کتابخانهٔ رسانه بارگذاری نشده باشد) کلیک بی‌صدا نمی‌ماند:
 *      پیام خطا کنار دکمه چاپ می‌شود و فیلد «نشانی تصویر» به‌عنوان جایگزین فعال می‌شود.
 *   ۳) برای هر ردیف یک frame جدا ساخته می‌شود تا انتخاب تصویر همیشه روی همان ردیف بنشیند.
 */
(function ($) {
    'use strict';

    var i18n    = window.eeSettings || {};
    var keySeq  = 0;
    var emptyNote = 'هنوز اسلایدی اضافه نکرده‌اید. از دکمهٔ «افزودن اسلایدر» استفاده کنید.';

    function txt(key, fallback) {
        return (i18n && i18n[key]) ? i18n[key] : fallback;
    }

    function nextUid() {
        return 'row' + Date.now().toString(36) + (keySeq++);
    }

    /* پیام کوتاه و dismissable کنار دکمه */
    function say($row, text, isError) {
        if (!$row || !$row.length) {
            return;
        }
        var $old = $row.children('.evented-inline-notice');
        if ($old.length) {
            $old.remove();
        }
        var $note = $('<div class="evented-inline-notice"><span class="evented-inline-notice-x" role="button" tabindex="0">&times;</span><span></span></div>')
            .addClass(isError ? 'is-error' : 'is-info');
        $note.children('span:last').text(text);
        $row.prepend($note);
    }

    function mediaReady() {
        return typeof window.wp !== 'undefined' && window.wp && typeof window.wp.media === 'function';
    }

    /* نوشتن تصویر انتخاب‌شده در ردیف */
    function applyImage($row, id, url, title) {
        if (!$row || !$row.length || !url) {
            return;
        }
        $row.find('.evented-slide-image-id').val(id ? String(id) : '');
        $row.find('.evented-slide-image').attr('src', url).addClass('has-image');
        $row.find('.evented-slide-imgbox').removeClass('empty');

        var $urlInput = $row.find('.evented-slide-image-url');
        if ($urlInput.length && !$urlInput.val()) {
            $urlInput.val(url);
        }

        if (title) {
            var $titleInput = $row.find('.evented-slide-title');
            if ($titleInput.length && !$titleInput.val()) {
                $titleInput.val(title);
            }
        }
    }

    function clearImage($row) {
        $row.find('.evented-slide-image-id').val('');
        $row.find('.evented-slide-image-url').val('');
        $row.find('.evented-slide-image').removeAttr('src').removeClass('has-image');
        $row.find('.evented-slide-imgbox').addClass('empty');
    }

    function openMedia($button) {
        var $row = $button.closest('.evented-slide-row');
        if (!$row.length) {
            return;
        }

        if (!mediaReady()) {
            // کتابخانهٔ رسانه در دسترس نیست → راهنمای جایگزین (ورود دستی نشانی)
            say($row, txt('mediaMissing', 'کتابخانهٔ رسانهٔ وردپرس بارگذاری نشد. صفحه را یک‌بار رفرش کنید یا نشانی تصویر را در فیلد «نشانی تصویر» وارد کنید.'), true);
            var $url = $row.find('.evented-slide-image-url');
            if ($url.length) {
                $url.trigger('focus');
            }
            return;
        }

        var frame = $row.data('eeMediaFrame');
        if (!frame) {
            frame = window.wp.media({
                title: txt('mediaTitle', 'انتخاب تصویر اسلایدر'),
                button: { text: txt('mediaButton', 'انتخاب تصویر') },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function () {
                var selection = frame.state().get('selection');
                var attachment = selection && selection.first ? selection.first() : null;
                var data = attachment && attachment.toJSON ? attachment.toJSON() : attachment;
                if (!data || !data.id) {
                    return;
                }
                applyImage($row, data.id, data.url || data.sizes && data.sizes.full && data.sizes.full.url, data.title);
                say($row, txt('picked', 'تصویر انتخاب شد. برای ماندگاری، «ذخیرهٔ اسلایدرها» را بزنید.'), false);
            });

            $row.data('eeMediaFrame', frame);
        }

        frame.open();
    }

    /* ---------- افزودن ردیف تازه ---------- */
    $(document).on('click', '#evented-add-slide', function (e) {
        e.preventDefault();

        var $list = $('#evented-slides-list');
        var $proto = $('#evented-slide-proto');
        if (!$list.length) {
            return;
        }

        if (!$proto.length) {
            $list.before('<div class="notice notice-error"><p>' +
                txt('protoMissing', 'قالب ردیف اسلاید در صفحه پیدا نشد؛ صفحه را یک‌بار رفرش کنید.') + '</p></div>');
            return;
        }

        // کلِ ردیف کپی می‌شود (نه فقط محتوای داخلی) تا پوستهٔ .evented-slide-row
        // و استایل کارت حفظ شود؛ سپس id قالب و __UID__ جایگزین می‌شوند.
        var uid   = nextUid();
        var $node = $proto.clone().removeAttr('id').css('display', '');
        $node.attr('data-uid', uid);
        $node.find('[name]').each(function () {
            this.name = String(this.name).split('__UID__').join(uid);
        });

        $('#evented-slides-empty').remove();
        $list.append($node);
        $node.find('.evented-slide-imgbox').addClass('empty');
    });

    /* ---------- حذف ردیف ---------- */
    $(document).on('click', '.evented-remove-slide', function (e) {
        e.preventDefault();

        var $row = $(this).closest('.evented-slide-row');
        if (!$row.length) {
            return;
        }
        if (!window.confirm(txt('confirmRemove', 'این اسلاید از فهرست حذف شود؟ (با «ذخیرهٔ اسلایدرها» قطعی می‌شود)'))) {
            return;
        }

        $row.remove();

        var $list = $('#evented-slides-list');
        if ($list.length && !$list.find('.evented-slide-row').length) {
            $list.append($('<p class="description" id="evented-slides-empty"></p>').text(emptyNote));
        }
    });

    /* ---------- انتخاب تصویر ---------- */
    $(document).on('click', '.evented-pick-image', function (e) {
        e.preventDefault();
        openMedia($(this));
    });

    /* ---------- پاک کردن تصویر ---------- */
    $(document).on('click', '.evented-remove-image', function (e) {
        e.preventDefault();
        var $row = $(this).closest('.evented-slide-row');
        clearImage($row);
        say($row, txt('cleared', 'تصویر پاک شد. برای ماندگاری، «ذخیرهٔ اسلایدرها» را بزنید.'), false);
    });

    /* ---------- ورود دستی نشانی تصویر (جایگزین کتابخانهٔ رسانه) ---------- */
    $(document).on('change', '.evented-slide-image-url', function () {
        var $row = $(this).closest('.evented-slide-row');
        var url = $.trim($(this).val());

        if (!url) {
            clearImage($row);
            return;
        }
        if (!/^(https?:)?\/\//i.test(url)) {
            say($row, txt('badUrl', 'نشانی تصویر باید با http یا // آغاز شود.'), true);
            return;
        }

        // تصویر دستی، پیوست وردپرس نیست → شناسه پاک می‌شود
        $row.find('.evented-slide-image-id').val('');
        applyImage($row, '', url, '');
        say($row, txt('urlSet', 'تصویر از نشانی وارد شد. برای ماندگاری، «ذخیرهٔ اسلایدرها» را بزنید.'), false);
    });

    /* اگر اسکریپت رسانه بعداً (مثلاً با تأخیر) بارگذاری شد، پیام خطای قبلی را پاک کن */
    $(function () {
        if (mediaReady()) {
            $('.evented-inline-notice.is-error').remove();
        }
    });

    // نشانهٔ «اسکریپت مدیریت اسلایدرها بارگذاری شد» — watchdog صفحه به آن نگاه می‌کند
    window.eventedSlidesReady = true;
})(jQuery);
