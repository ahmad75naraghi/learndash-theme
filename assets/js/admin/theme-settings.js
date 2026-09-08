/* تنظیمات قالب evented-edu — مدیریت اسلایدرها (نیازمند wp_enqueue_media) */
(function ($) {
    'use strict';

    var list      = $('#evented-slides-list');
    var proto     = $('#evented-slide-proto');
    var frame     = null;
    var targetRow = null;
    var uidSeq    = 1000;

    function nextUid() {
        return '__row' + (uidSeq++) + '__';
    }

    if (!list.length || !proto.length) {
        return;
    }

    // افزودن ردیف تازه
    $('#evented-add-slide').on('click', function () {
        var uid  = nextUid();
        var html = proto.html().split('__UID__').join(uid);
        var node = $(html);

        $('#evented-slides-empty').remove();
        list.append(node);
        node.find('.evented-slide-imgbox').addClass('empty');
    });

    // حذف ردیف (واگذاری رویداد — برای ردیف‌های تازه هم کار می‌کند)
    list.on('click', '.evented-remove-slide', function (e) {
        e.preventDefault();
        var row = $(this).closest('.evented-slide-row');
        if (row.length && window.confirm('این اسلاید حذف شود؟')) {
            row.remove();
            if (!list.find('.evented-slide-row').length) {
                list.append('<p class="description" id="evented-slides-empty">هنوز اسلایدی اضافه نکرده‌اید.</p>');
            }
        }
    });

    // انتخاب تصویر از کتابخانهٔ رسانه
    list.on('click', '.evented-pick-image', function (e) {
        e.preventDefault();

        targetRow = $(this).closest('.evented-slide-row');
        if (!targetRow.length) {
            return;
        }

        if (frame) {
            frame.open();
            return;
        }

        frame = wp.media({
            title: 'انتخاب تصویر اسلایدر',
            button: { text: 'انتخاب تصویر' },
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            if (!attachment || !attachment.id || !targetRow || !targetRow.length) {
                return;
            }

            targetRow.find('.evented-slide-image-id').val(attachment.id);
            targetRow.find('.evented-slide-image').attr('src', attachment.url).addClass('has-image');
            targetRow.find('.evented-slide-imgbox').removeClass('empty');

            var titleInput = targetRow.find('.evented-slide-title');
            if (!titleInput.val() && attachment.title) {
                titleInput.val(attachment.title);
            }
            var badgeInput = targetRow.find('.evented-slide-badge');
            if (!badgeInput.val()) {
                badgeInput.val('اسلایدر ویژه');
            }
        });

        frame.open();
    });
})(jQuery);
