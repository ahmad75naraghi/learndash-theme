jQuery(document).ready(function ($) {

    var articlescarousel = $('.reviews-slider');
    articlescarousel.owlCarousel({
        rtl: true,
        loop: false,
        margin: 14, // فاصله بین کارت‌ها (همان gap در grid)
        nav: false,
        dots: true, // اگر می‌خواهید دات‌های پایین باشند، این را true کنید
        autoplay: false,
        responsive: {
            0: {
                items: 1.5
            },
            768: {
                items: 2.5
            },
            1024: {
                items: 2.5
            }
        }
    });

    $('.related-courses-carousel').owlCarousel({
        rtl: true, // برای راست‌چین بودن
        loop: true,
        margin: 20,
        nav: false,
        dots: false,
        responsive: {
            0: {
                items: 1.4
            },
            576: {
                items: 2.2
            },
            768: {
                items: 3
            },
            1024: {
                items: 4
            }
        }
    });

    // اتصال دکمه‌های سفارشی نظرات
    $('.articles-carousel-next').click(function () {
        articlescarousel.trigger('next.owl.carousel');
    });

    $('.articles-carousel-prev').click(function () {
        articlescarousel.trigger('prev.owl.carousel');
    });

    /* Rating */
    let selectedRating = 0;

    // هندل کردن کلیک روی ستاره‌ها
    $('#review-rating-stars .stars svg').on('click', function () {
        selectedRating = $(this).data('val');

        $('#review-rating-stars .stars svg').removeClass('active');

        $('#review-rating-stars .stars svg').each(function (index) {
            if (index < selectedRating) {
                $(this).addClass('active');
            }
        });
    });

    // ارسال ایجکس
    $('#submit-course-review').on('click', function (e) {
        e.preventDefault();

        const btn = $(this);
        const msgBox = $('#review-message');
        const content = $('#review-content').val();
        const courseId = btn.data('course');
        const nonce = btn.data('nonce');

        if (selectedRating === 0) {
            msgBox.css('color', 'red').text('لطفا امتیاز خود را (تعداد ستاره) مشخص کنید.').show();
            return;
        }
        if (!content.trim()) {
            msgBox.css('color', 'red').text('لطفا متن نظر خود را بنویسید.').show();
            return;
        }

        btn.text('در حال ثبت...').prop('disabled', true);
        msgBox.hide();

        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'submit_course_review',
                security: nonce,
                course_id: courseId,
                rating: selectedRating,
                content: content
            },
            success: function (response) {
                if (response.success) {
                    msgBox.css('color', 'green').text(response.data).show();
                    $('#review-content').val('');
                    selectedRating = 0;
                    $('#review-rating-stars .stars svg').removeClass('active');
                } else {
                    msgBox.css('color', 'red').text(response.data).show();
                }
            },
            error: function () {
                msgBox.css('color', 'red').text('برای ثبت نظر، ابتدا باید وارد حساب کاربری خود شوید').show();
            },
            complete: function () {
                btn.text('ثبت نظر').prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.preview-hover-overlay', function (e) {
        e.preventDefault();
        var firstLesson = $('.lesson-row.lesson-free').first();
        if (firstLesson.length > 0) {
            firstLesson.trigger('click');
        } else {
            window.location.href = '/login/';
        }
    });

    const players = Array.from(document.querySelectorAll('.js-player')).map(p => new Plyr(p, {
        controls: ['play-large', 'play', 'progress', 'current-time', 'duration', 'mute', 'volume', 'settings', 'fullscreen'],
        settings: ['speed']
    }));


    $('.wishlist-add-btn').on('click', function () {
        var btn = $(this);
        var isLoggedIn = btn.data('logged-in');

        // 1. اگر لاگین نبود، بفرستش صفحه ورود
        if (isLoggedIn === false || isLoggedIn === 'false') {
            window.location.href = btn.data('login-url');
            return;
        }

        var courseId = btn.data('course');
        var nonce = btn.data('nonce');
        var svgIcon = btn.find('.wishlist-icon');
        var svgPath = btn.find('.wishlist-icon path');
        var textSpan = btn.find('.wishlist-text');

        // افکت لودینگ کوتاه
        btn.css('opacity', '0.6');

        // 2. ارسال درخواست به سرور
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'toggle_course_wishlist',
                security: nonce,
                course_id: courseId
            },
            success: function (response) {
                if (response.success) {
                    // تغییر رنگ و متن بصورت آنی
                    if (response.data.status === 'added') {
                        svgIcon.attr('fill', '#e74c3c');
                        svgPath.attr('stroke', '#e74c3c');
                        textSpan.text('حذف از علاقه مندی ها');
                    } else {
                        svgIcon.attr('fill', 'none');
                        svgPath.attr('stroke', '#292D32');
                        textSpan.text('افزودن به علاقه مندی ها');
                    }
                }
            },
            complete: function () {
                btn.css('opacity', '1');
            }
        });
    });
});
function openLessonModal(id) {
    var modal = document.getElementById('lesson-modal-' + id);

    // اگر سورس iframe قبلاً خالی شده بود، دوباره برش میگردونیم تا لود بشه
    var iframe = modal.querySelector('iframe');
    if (iframe && iframe.getAttribute('data-src')) {
        iframe.src = iframe.getAttribute('data-src');
    }

    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLessonModal(id) {
    var modal = document.getElementById('lesson-modal-' + id);
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';

    // توقف کامل آپارات (Iframe) با حذف موقت آدرس
    var iframe = modal.querySelector('iframe');
    if (iframe) {
        if (!iframe.getAttribute('data-src')) {
            iframe.setAttribute('data-src', iframe.src); // ذخیره آدرس برای استفاده مجدد
        }
        iframe.src = ''; // خالی کردن سورس برای توقف قطعی
    }

    // توقف ویدیو مستقیم (Plyr/mp4)
    var video = modal.querySelector('video');
    if (video) {
        video.pause();
    }
}

function switchLessonModal(currentId, nextId) {
    closeLessonModal(currentId);
    openLessonModal(nextId);
}



// 1. تابع به‌روزرسانی ظاهری نوار پیشرفت (تغییر عرض و عدد درصد)
function updateProgressUI(data) {
    if (data && data.percentage !== undefined) {
        jQuery('.progress-percentage-text').text(data.percentage + '%');
        jQuery('.progress-bar-fill').css('width', data.percentage + '%');
        jQuery('.progress-steps-text').text(data.completed + ' از ' + data.total + ' مرحله تکمیل شده');

        var ctaBtn = jQuery('.course-sidebar-content .btn-submit');
        if (data.percentage == 100) {
            ctaBtn.text('مرور دوره');
        } else if (data.percentage > 0) {
            ctaBtn.text('ادامه یادگیری');
        }
    }
}

// 2. تابع دکمه ویدیوی بعدی
function handleNextLesson(currentId, nextId, markCompleteNonce = null, courseId = null) {
    if (markCompleteNonce && courseId) {
        var btn = jQuery('#lesson-modal-' + currentId + ' .btn-next');
        var originalText = btn.html();
        btn.text('در حال ثبت...');

        jQuery.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'custom_mark_lesson_complete',
                security: markCompleteNonce,
                lesson_id: currentId,
                course_id: courseId
            },
            success: function (response) {
                if (response.success && response.data) {
                    updateProgressUI(response.data);
                }
            },
            complete: function () {
                btn.html(originalText);
                if (typeof closeLessonModal === "function") closeLessonModal(currentId);
                if (typeof openLessonModal === "function") openLessonModal(nextId);
            }
        });
    } else {
        if (typeof closeLessonModal === "function") closeLessonModal(currentId);
        if (typeof openLessonModal === "function") openLessonModal(nextId);
    }
}

// 3. تابع دکمه پایان (برای آخرین درس که ویدیوی بعدی ندارد)
function handleFinishLastLesson(currentId, markCompleteNonce = null, courseId = null) {
    if (markCompleteNonce && courseId) {
        var btn = jQuery('#lesson-modal-' + currentId + ' .btn-finish');
        var originalText = btn.html();
        btn.text('در حال ثبت...');

        jQuery.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'custom_mark_lesson_complete',
                security: markCompleteNonce,
                lesson_id: currentId,
                course_id: courseId
            },
            success: function (response) {
                if (response.success && response.data) {
                    updateProgressUI(response.data);
                }
            },
            complete: function () {
                btn.html(originalText);
                // چون ویدیوی بعدی وجود ندارد، فقط پاپ‌آپ بسته می‌شود.
                if (typeof closeLessonModal === "function") closeLessonModal(currentId);
            }
        });
    } else {
        if (typeof closeLessonModal === "function") closeLessonModal(currentId);
    }
}



