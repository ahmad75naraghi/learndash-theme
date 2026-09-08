jQuery(document).ready(function ($) {

    // آرایه‌ای از حروف فارسی و عربی برای تبدیل
    const persianNumbers = [/۰/g, /۱/g, /۲/g, /۳/g, /۴/g, /۵/g, /۶/g, /۷/g, /۸/g, /۹/g];
    const arabicNumbers = [/٠/g, /١/g, /٢/g, /٣/g, /٤/g, /٥/g, /٦/g, /٧/g, /٨/g, /٩/g];
    // تابع تبدیل اعداد فارسی/عربی به انگلیسی
    function fixNumbers(str) {
        if (typeof str === 'string') {
            for (let i = 0; i < 10; i++) {
                str = str.replace(persianNumbers[i], i).replace(arabicNumbers[i], i);
            }
        }
        return str;
    }
    $(document).on('input', '.just_number', function () {
        let $input = $(this);
        let originalValue = $input.val();

        // مرحله ۱: تبدیل اعداد فارسی و عربی به انگلیسی
        let convertedValue = fixNumbers(originalValue);

        // مرحله ۲: حذف هر چیزی که عدد انگلیسی (0-9) نیست
        let sanitizedValue = convertedValue.replace(/[^0-9]/g, '');

        // اگر کاراکتر غیر عددی تایپ شده بود
        if (convertedValue !== sanitizedValue) {

            // جلوگیری از نمایش همزمان چند آلارم
            $input.next('.just-number-alert').remove();

            // ساخت و استایل‌دهی پیغام خطا (Absolute)
            let $alertMsg = $('<span class="just-number-alert">فقط عدد وارد کنید!</span>').css({
                position: 'absolute',
                color: '#fff',
                backgroundColor: '#dc3545', // رنگ قرمز هشدار
                fontSize: '11px',
                padding: '2px 8px',
                borderRadius: '4px',
                marginTop: '5px', // فاصله از پایین اینپوت
                zIndex: 9999,
                whiteSpace: 'nowrap',
                boxShadow: '0 2px 4px rgba(0,0,0,0.2)'
            });

            // قرار دادن آلارم دقیقاً بعد از اینپوت
            $input.after($alertMsg);

            // پنهان کردن آلارم بعد از ۳ ثانیه (3000 میلی‌ثانیه)
            $alertMsg.fadeIn(200).delay(3000).fadeOut(300, function () {
                $(this).remove(); // حذف کامل از DOM بعد از محو شدن
            });
        }

        // در نهایت، مقدار اصلاح شده را داخل اینپوت قرار می‌دهیم
        // (حتی اگر فقط تبدیل فارسی به انگلیسی بوده و خطایی نداشته باشد)
        if (originalValue !== sanitizedValue) {
            $input.val(sanitizedValue);
        }
    });






    // ۱. باز کردن منو با کلیک روی دکمه همبرگری
    $('.open-menu').on('click', function (e) {
        e.preventDefault();
        $('.mobile-menu-overlay').fadeIn(300);
        $('.mobile-menu-sidebar').addClass('active');
        $('body').css('overflow', 'hidden'); // جلوگیری از اسکرول شدن صفحه اصلی
    });

    // ۲. بستن منو با کلیک روی دکمه ضربدر یا فضای تاریک بیرون منو
    $('.close-mobile-menu, .mobile-menu-overlay').on('click', function () {
        $('.mobile-menu-overlay').fadeOut(300);
        $('.mobile-menu-sidebar').removeClass('active');
        $('body').css('overflow', 'auto'); // بازگرداندن قابلیت اسکرول
    });

    // ۳. باز و بسته شدن نرم آکاردئون‌ها (زیرمنوها)
    $('.menu-item-header').on('click', function () {
        // تغییر کلاس برای چرخش آیکون فلش
        $(this).toggleClass('open');
        // باز و بسته شدن کشویی نرم با جی‌کوئری
        $(this).next('.submenu-list').slideToggle(350);
    });





    // ۱. انتخاب تمام دکمه‌های تب
    const tabButtons = document.querySelectorAll('.tab-btn');

    // ۲. قابلیت کلیک: اسکرول نرم به بخش مربوطه
    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const targetSection = document.getElementById(targetId);

            if (targetSection) {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // ۳. قابلیت اسکرول: فعال شدن دکمه‌ها هنگام اسکرول دستی کاربر
    const observerOptions = {
        root: null, // مبنا کل پنجره مرورگر است
        rootMargin: '-20% 0px -60% 0px', // تنظیم محدوده حساسیت (زمانی که بخش به بالای صفحه نزدیک شد)
        threshold: 0 // به محض ورود بخش به محدوده، تابع اجرا می‌شود
    };

    const observerCallback = (entries) => {
        entries.forEach(entry => {
            // اگر بخش مورد نظر وارد محدوده دید شد
            if (entry.isIntersecting) {
                const currentSectionId = entry.target.getAttribute('id');

                // بررسی تمام دکمه‌ها و فعال کردن دکمه هم‌نام با این بخش
                tabButtons.forEach(btn => {
                    if (btn.getAttribute('data-target') === currentSectionId) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            }
        });
    };

    // ساختن ردیاب (Observer)
    const observer = new IntersectionObserver(observerCallback, observerOptions);

    // متصل کردن ردیاب به تمام بخش‌های محتوایی موجود در صفحه
    tabButtons.forEach(button => {
        const targetId = button.getAttribute('data-target');
        const section = document.getElementById(targetId);
        if (section) {
            observer.observe(section); // شروع ردیابی بخش
        }
    });
    $(document).on('click', '[data-click]', function (e) {
        if (this.tagName === 'A') {
            e.preventDefault();
        }
        var $this = $(this);
        var targetClassName = $this.attr('data-click');
        $this.toggleClass('active');
        if (targetClassName) {
            $('.' + targetClassName).toggleClass('active');
        }
    });

    function setupActiveOnClick() {
        $('[data-active]').on('click', function () {

            // گرفتن آیدی از المنت کلیک شده
            var targetId = $(this).attr('data-active');

            // (اختیاری) اگر می‌خواهید کلاس active از بقیه المنت‌ها برداشته شود، این دو خط را از کامنت درآورید:
            // $('[data_active]').removeClass('active');
            // $('.target-elements-class').removeClass('active'); // باید یک کلاس مشترک به هدف‌ها بدهید

            // اضافه کردن کلاس به خود المنت
            $(this).addClass('active');

            // اضافه کردن کلاس به المنت هدف
            if (targetId) {
                $('#' + targetId).addClass('active');
            }
        });
    }

    $(document).ready(function () {
        setupActiveOnClick();
    });

    $('.eduf-sidebar-overlay, .close-eduf-sidebar').on('click', function () {
        $('.eduf-sidebar').removeClass('active');
    });

    $('.eduf-sorting-overlay, .close-eduf-sorting').on('click', function () {
        $('.eduf-sorting').removeClass('active');
    });

    $(document).on('click', 'a[href*="#"]:not([href="#"])', function (e) {
        if (location.pathname.replace(/^\//, '') === this.pathname.replace(/^\//, '') && location.hostname === this.hostname) {
            var target = $(this.hash);
            target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
            if (target.length) {
                e.preventDefault();
                var windowHeight = $(window).height();
                var targetHeight = target.outerHeight();
                var targetTop = target.offset().top;
                var scrollToPosition = targetTop - (windowHeight / 2) + (targetHeight / 2);
                $('html, body').animate({
                    scrollTop: scrollToPosition
                }, 800);
            }
        }
    });

});