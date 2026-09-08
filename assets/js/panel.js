jalaliDatepicker.startWatch();
jQuery(document).ready(function($) {
    var logoutUrl = '';

    // باز کردن مودال با کلیک روی دکمه خروج
    $('.logout-trigger').on('click', function(e) {
        e.preventDefault();
        logoutUrl = $(this).data('url'); // خواندن لینک اصلی خروج وردپرس
        $('#logout-modal-overlay').fadeIn(200).css('display', 'flex');
    });

    // بستن مودال با کلیک روی دکمه انصراف یا ضربدر
    $('.logout-modal-close, #cancel-logout-btn').on('click', function(e) {
        e.preventDefault();
        $('#logout-modal-overlay').fadeOut(200);
    });

    // بستن مودال در صورت کلیک روی فضای خالی تیره رنگ (overlay)
    $('#logout-modal-overlay').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut(200);
        }
    });

    // اجرای خروج در صورت کلیک روی تایید
    $('#confirm-logout-btn').on('click', function() {
        if (logoutUrl) {
            // تغییر متن دکمه برای حس بهتر (UX)
            $(this).text('در حال خروج...').css('opacity', '0.7'); 
            window.location.href = logoutUrl; // انتقال کاربر به مسیر خروج وردپرس
        }
    });
});