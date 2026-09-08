# ۰۲ — نقشهٔ کامل فایل‌ها و پوشه‌ها

درخت واقعی ریپو (فایل‌های PHP و پوشه‌ها؛ asset های باینری خلاصه شده‌اند):

```
learndash-theme/
├── style.css                  ← هدر قالب + استایل پایه/هدر/فوتر/دکمه‌ها/منوی موبایل
├── functions.php              ← تعریف PATH_DIR و require های اصلی (فقط ۶ خط)
├── index.php                  ← ⚠️ فایل دیباگ! به‌جای قالب معمولی، اطلاعات وردپرس را چاپ می‌کند
├── screenshot.png             ← ⚠️ خالی (۰ بایت)؛ باید تصویر واقعی ۱۲۰۰×۹۰۰ گذاشته شود
├── Untitled-1.json            ← دادهٔ schema.org (LocalBusiness)؛ به‌نظر پروندهٔ جانبی/زائده است
│
├── header.php                 ← هدر سایت: لوگو، منو، دسته‌ها، دکمه ورود/پنل، منوی موبایل(overlay/sidebar در footer)
├── footer.php                 ← action-bar موبایل (خانه/دسته‌ها/سبد/پنل) + فوتر + wp_footer + منوی کشویی موبایل
│
├── front-page.php             ← صفحهٔ اصلی (هیرو، دسته‌ها، اساتید، مراحل یادگیری، اسلایدر دوره، نظرات، بنر، مقالات)
├── 404.php                    ← صفحهٔ «پیدا نشد»
├── page.php                   ← قالب عمومی برگه‌ها
├── page-courses.php           ← برگه با اسلاگ courses: فقط لیست ۱۲ عنوان (استاب/ناتمام)
├── page-courses-cat.php       ← تمپلیت «صفحهٔ دسته‌بندی دوره‌های لرن‌دش»: گرید همهٔ ld_course_category
├── page-login.php             ← برگهٔ /login — صفحهٔ HTML مستقلِ ورود/ثبت‌نام/بازیابی رمز (بدون wp_head/wp_footer)
├── page-panel.php             ← برگهٔ /panel — اگر لاگین نبود ریدایرکت به edu.falnic.com/login
├── archive-sfwd-courses.php   ← آرشیو دوره‌ها: فقط get_template_part('taxonomy','ld_course_category')
├── taxonomy-ld_course_category.php ← آرشیو/دسته‌بندی دوره: سایدبار فیلتر + گرید کارت + SEO/FAQ پایین
├── single-sfwd-courses.php    ← صفحهٔ تکی دوره (هدر دوره، سرفصل‌ها+آکاردئون، مودال ویدیو، توضیحات، وبینار، نظرات، استاد، گواهینامه، دوره‌های مرتبط، سایدبار خرید/پیشرفت)
├── author.php                 ← پروفایل استاد (آمار، بیو، سوابق شغلی/تحصیلی، دوره‌ها، مقالات)
└── template-instructors.php   ← تمپلیت «لیست اساتید» با نقش group_leader + سایدبار فیلتر
```

## پوشه‌ها

```
├── assets/
│   ├── assets_functions.php   ← هوک wp_enqueue_scripts (منطق انتخاب CSS/JS بر اساس context)
│   ├── css/
│   │   ├── style→ جدا در ریشه
│   │   ├── front-page.css       (1694 خط) استایل بخش‌های صفحهٔ اصلی
│   │   ├── single-courses.css   (2252 خط) استایل صفحهٔ تکی دوره (درس، مودال، وبینار، فرم نظر)
│   │   ├── archive-courses.css  (986 خط)  استایل مشترک آرشیو/دسته/لیست اساتید (کلاس‌های eduf-*)
│   │   ├── author.css           (704 خط)  پروفایل استاد
│   │   ├── panel.css            (1903 خط) پنل کاربری
│   │   ├── archive-post.css     ⚠️ خالی (۰ بایت)
│   │   ├── plyr.css             ← استایل کامل Plyr (~۳۲KB)؛ در صفحهٔ دوره enqueue می‌شود
│   │   ├── single-post.css      ❌ مفقود (enqueue می‌شود)
│   │   ├── single-page.css      ❌ مفقود (enqueue می‌شود)
│   │   ├── archive-product.css  ❌ مفقود (enqueue می‌شود)
│   │   ├── owl.carousel.min.css / jalalidatepicker.min.css / photoswipe.min .css (نام با فاصله!)
│   │   └── backhhero.webp      ⚠️ عکس داخل پوشهٔ css (احتمالاً اشتباه جابه‌جا شده)
│   ├── js/
│   │   ├── main.js             ← عمومی: تبدیل ارقام، منوی موبایل، تب/اسکرول، فیلترهای eduf، انکر لینک
│   │   ├── front-page.js       ← راه‌اندازی Owl ها و دکمه‌های صفحهٔ اصلی
│   │   ├── single-courses.js   ← اسلایدرها + Plyr + نظر(star/rating) + wishlist + openLessonModal و توابع تکمیل درس
│   │   ├── author.js           ← اسلایدر دوره‌ها/مقالات + ستاره امتیاز (بدون ارسال)
│   │   ├── panel.js            ← مودال خروج
│   │   ├── archive-post.js     ❌ مفقود (enqueue می‌شود)
│   │   ├── single-post.js      ❌ مفقود (enqueue می‌شود)
│   │   ├── owl.carousel.min.js / jalalidatepicker.min.js / plyr.polyfilled.js / photoswipe.min.js
│   ├── fonts/falnic-font.woff2 ← فونت «دانا» (فقط همین یک فایل؛ DanaVF.ttf برای captcha نیست)
│   └── img/…                   ← عکس‌های استاتیک: front-page (۲۳ فایل)، single-page (۸)، panel (۲)
│
├── inc/                        ← منطق سمت سرور
│   ├── includes.php            ← فقط require کردن ۵ فایل بعدی
│   ├── login.php               ← کلاس FalnicAuthHandler + ۸ اکشن AJAX ورود/OTP + captcha_verify + ریدایرکت‌ها
│   ├── sms.php                 ← send_pattern_sms (SOAP payamak-panel) — اعتبارنامه هاردکد
│   ├── meta_functions.php      ← متاباکس «اطلاعات تکمیلی دوره»، متای دسته (تصویر/SEO/FAQ)،
│   │                             فیلدهای پروفایل (مدرس: سوشال/درباره/سوابق + آواتار کاربر)
│   ├── theme_options.php       ← MIME ها، post-thumbnails، ریدایرکت/محدودیت subscriber، is_current_path
│   ├── ajax_functions.php      ← submit_course_review / custom_mark_lesson_complete /
│   │                             toggle_course_wishlist / save_user_profile / save_account_settings
│   └── captcha.php             ← تولید تصویر PNG کپچا (GD) — فعلاً به هیچ‌جا وصل نیست؛ فونت DanaVF.ttf مفقود
│
└── panel/                      ← فایل‌های صفحه‌های پنل (هرکدام «Template Name» دارند)
    ├── sidebar.php             ← سایدبار مشترک (آیتم‌ها + مودال خروج)
    ├── dashboard.php           ← داشبورد: شمارنده دوره/تراکنش + کارت دوره‌های جاری
    ├── my-courses.php          ← دوره‌های من (progress/گواهینامه/جستجو) + دوره‌های پیشنهادی
    ├── wishlist.php            ← علاقه‌مندی‌ها + حذف AJAX + جستجو
    ├── certificates.php        ← گواهینامه‌های دریافت‌شده + دکمه LinkedIn
    ├── payments.php            ← تراکنش‌ها + مودال رسید/چاپ
    ├── profile.php             ← پروفایل (نام فارسی/انگلیسی، جنسیت، تولد با جلالی) — ذخیره AJAX
    └── settings.php            ← تنظیمات حساب (ایمیل/رمز/موبایل) + مودال تغییر رمز
```

## نکته دربارهٔ دو فایل «شبیه به هم»

- `page-courses.php` ← برای **برگه‌ای با اسلاگ `courses`** است (استاب ساده).
- `archive-sfwd-courses.php` ← برای **آرشیو پست‌تایپ** (`/courses/`) است و همان قالب دسته را صدا می‌زند.
- اگر هم برگهٔ `courses` ساخته شود و هم آرشیو CPT موجود باشد، وردپرس بسته به اینکه `/courses/` به برگه یا آرشیو خورده باشد یکی را نشان می‌دهد؛ در هدر/منوها معمولاً `/courses/` همان آرشیو است.

## فایل‌هایی که در کد صدا زده می‌شوند ولی در ریپو نیستند (خلاصه)

- `falnic_send_otp_with_bale()` — تابع PHP
- جدول `{wp}_falnic_transactions` — دیتابیس
- فایل‌های CSS/JS مفقود (فایل ۰۶)
- فونت `DanaVF.ttf` (مخصوص captcha)
