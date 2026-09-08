# ۰۶ — سیستم Asset ها (CSS/JS/فونت) و کتابخانه‌ها

## ۱. نقطهٔ کنترل: `assets/assets_functions.php`

تابع `theme_enqueue()` روی `wp_enqueue_scripts` بسته به نوع صفحه، استایل/اسکریپت لود می‌کند.
ترتیب شرط‌ها (اولین تطابق برنده است):

| ترتیب | شرط | فایل‌های لودشده |
|---|---|---|
| ۱ | `is_home() \|\| is_page('home') \|\| is_front_page()` | `front-page.css` + `front-page.js` |
| ۲ | `is_post_type_archive('sfwd-courses') \|\| is_tax('ld_course_category') \|\| is_page('courses')` | `archive-courses.css` |
| ۳ | `is_author()` | `author.css` + `author.js` |
| ۴ | `is_singular('sfwd-courses')` | `plyr.css` + `plyr.polyfilled.js` + `single-courses.css` + `single-courses.js` |
| ۵ | `is_archive() \|\| is_category() \|\| is_tag() \|\| is_page('blog')` | `archive-post.css` (خالی) + `archive-post.js` ❌ |
| ۶ | `get_post_type() === 'post'` | `single-post.css` ❌ + `single-post.js` ❌ |
| ۷ | `get_post_type() === 'page'` (هر برگهٔ دیگر) | `archive-product.css` ❌ + `single-page.css` ❌ |
| — | جداگانه: برگهٔ پنل (صفحهٔ `panel` یا فرزند آن) | `panel.css` + `jalalidatepicker.min.js` + `panel.js` |

فایل‌های سراسری (همهٔ صفحات):
- `style.css` (استایل پایه/هدر/فوتر)
- `owl.carousel.min.css` + `owl.carousel.min.js` (وابسته به jquery)
- `main.js` (وابسته به jquery) + `wp_localize_script('main','ajax_object', {ajax_url, nonce})`
  - ⚠️ `nonce` لوکال‌شده از «notification_nonce» ساخته می‌شود ولی هیچ کد کلاینتی آن را استفاده نمی‌کند؛ `single-courses.js` فقط `ajax_object.ajax_url` را می‌خواند و nonce های واقعی را از data-attribute های دکمه می‌گیرد.

> نکته: قالب‌هایی که خارج از این شرط‌ها هستند (مثلاً `page-courses-cat.php`، `template-instructors.php`) باید استایل خودشان را دستی enqueue کنند؛ فقط `template-instructors.php` این کار را کرده (archive-courses.css). `page-courses-cat.php` استایلش را `<style>` داخل خود فایل دارد و چون برگه است، شرط ۷ فایل‌های مفقود را هم صدا می‌زند (درخواست ۴۰۴).

## ۲. کتابخانه‌های شخص ثالث (همه به‌صورت محلی داخل قالب)

| کتابخانه | فایل‌ها | استفاده در |
|---|---|---|
| **jQuery** | از هستهٔ وردپرس | همه‌جا |
| **Owl Carousel 2** | `assets/js/owl.carousel.min.js`, `assets/css/owl.carousel.min.css` | اسلایدر دوره‌ها/نظرات/مقالات/دوره‌های مرتبط (صفحه اصلی، single، author) |
| **Plyr** | `assets/js/plyr.polyfilled.js`, `assets/css/plyr.css` (~32KB، استایل کامل vendor) | پخش ویدیوی مستقیم mp4 در مودال درس (`video.js-player`) |
| **Jalali Date Picker** | `assets/js/jalalidatepicker.min.js`, `assets/css/jalalidatepicker.min.css` | فیلد تاریخ تولد پنل (`data-jdp`، با `jalaliDatepicker.startWatch()`) |
| **PhotoSwipe** | `assets/js/photoswipe.min.js`, `assets/css/photoswipe.min .css` | ⚠️ هیچ استفاده‌ای در قالب ندارد (اضافه‌بار)؛ نام فایل CSS هم دارای فاصلهٔ اشتباه است |
| **Font Awesome** | — | فقط یک آیکن `<i class="fa-brands fa-facebook">` در author.php؛ **فایل/لودر آن نیست** → آیکن نمایش داده نمی‌شود |

## ۳. فونت

- `@font-face` در `style.css` و صفحهٔ لاگین: `font-family:'دانا'` ← `assets/fonts/falnic-font.woff2`
- تقریباً همهٔ المان‌ها `font-family: 'دانا'` دارند.
- captcha دنبال `assets/fonts/DanaVF.ttf` می‌گردد (مفقود).

## ۴. فایل‌های مفقود / خالی (درخواست‌های 404 یا بی‌اثر)

| فایل | وضعیت | اثر |
|---|---|---|
| `assets/js/archive-post.js` | ❌ مفقود | 404 در بلاگ/آرشیو |
| `assets/js/single-post.js` | ❌ مفقود | 404 در تک‌پست |
| `assets/css/single-post.css` | ❌ مفقود | 404 در تک‌پست |
| `assets/css/single-page.css` | ❌ مفقود | 404 در همهٔ برگه‌های معمولی (page.php و…) |
| `assets/css/archive-product.css` | ❌ مفقود | 404 در برگه‌ها |
| `assets/css/archive-post.css` | موجود ولی ۰ بایت | بی‌اثر (تنها CSS خالی قالب) |
| `assets/img/.../default-cat.jpg` (مسیر اشاره‌شده در page-courses-cat) | ❌ مفقود | fallback دسته‌ها |
| `screenshot.png` | ۰ بایت | تصویر قالب در پیشخوان خالی است |

## ۵. فایل‌های موجود ولی بدون ارجاع
- `assets/js/photoswipe.min.js` ، `assets/css/photoswipe.min .css` — استفاده نمی‌شوند
- `assets/css/backhhero.webp` — در پوشهٔ css جا مانده
- `Untitled-1.json` در ریشه — دادهٔ schema.org؛ در قالب لود نمی‌شود

## ۶. استایل‌های صفحه‌ای (خلاصه برای توسعه)

| CSS | کلاس‌های کلیدی |
|---|---|
| `style.css` | `.site-header/.header-container`, `.btn-primary/.btn-yellow/.btn-outline*`, `.site-footer/.footer-wrapper/.footer-grid`, `.action-bar`, `.mobile-menu-*`, breadcrumb |
| `front-page.css` | `.hero-section/.hero-container`, `.categories-carousel/.category-card`, `.instructor-card-horizontal`, `.learning-*`, `.courses-slider-section`, `.course-card-pro/.card-*`, `.reviews-*`, `.banner-show-courses`, `.articles-*` |
| `single-courses.css` | `.course-header-section`, `.course-tabs-nav`, `.content-list`, `.curriculum-accordion`, `.lesson-row(.lesson-free/.lesson-locked)`, `.lesson-video-modal`, `.homework-upload`, `.webinar-*`, `.reviews-section/.review-card`, `.instructors-*`, `.certificate-*`, `.course-sidebar-content/.sticky-sidebar-card`, `.cta-btn-buy`, استایل دکمه‌های پرداخت لرن‌دش (`2240: همسان‌سازی لرن‌دش/ووکامرس`) |
| `archive-courses.css` | `.eduf-archive-main`, `.eduf-breadcrumb`, `.eduf-sidebar/.eduf-widget`, `.eduf-toolbar/.eduf-sorting`, `.eduf-grid/.eduf-card`, `.eduf-bottom-content/.eduf-faq-*`, `.eduf-instructor-*` |
| `author.css` | `.hero-banner`, `.profile-section`, `.stats-grid`, `.data-table`, `.rating-*` |
| `panel.css` | `.sidebar/.nav-item/.user-greeting`, `.main-content`, `.top-stats`, `.course-grid/.course-card/.horizontal-card`, `.search-bar`, `.progress-*`, `.certificates-container`, `.transaction-content/.table-*`, `.overlay/.modal/.invoice-table`, `.settings-panel`, `.logout-modal-*`, `.password-modal` |

## ۷. نکتهٔ مسیر (مهم!)
تعداد زیادی `url(...)` و `src=` به‌صورت مطلق به دامنهٔ تولید اشاره دارند:
- `https://edu.falnic.com/wp-content/themes/edu-falnic/assets/...`
- `/wp-content/themes/edu-falnic/assets/...`

برای لوکال/دامنهٔ جدید باید همه به `get_template_directory_uri()` تبدیل شوند. فایل‌های درگیر: `style.css` (پس‌زمینهٔ فوتر)، `header.php` (لوگو)، `front-page.php`، `single-sfwd-courses.php`، `inc/meta_functions.php` (آواتار پیش‌فرض) و…
