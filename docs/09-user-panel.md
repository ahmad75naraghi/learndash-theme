# ۰۹ — پنل کاربری (`panel/`)

## ۱. ساختار صفحات و URL ها

| URL | فایل | «Template Name» در هدر فایل |
|---|---|---|
| `/panel` | `page-panel.php` (ریشه) یا `panel/dashboard.php` | «Panel - Dashboard» |
| `/panel/my-courses` | `panel/my-courses.php` | «Panel - My Courses» |
| `/panel/certificates` | `panel/certificates.php` | «Panel - Cerificates» |
| `/panel/wishlist` | `panel/wishlist.php` | «Panel - Wishlist» |
| `/panel/payments` | `panel/payments.php` | «Panel - Payments» |
| `/panel/profile` | `panel/profile.php` | «Panel - Profile» |
| `/panel/settings` | `panel/settings.php` | «Panel - Account Settings» |
| سایدبار مشترک | `panel/sidebar.php` (با include_once از بقیه) | — |

نحوهٔ کار در وردپرس: برای هر مسیر باید یک **برگه** ساخته شود (مثلاً برگهٔ «دوره‌های من» با اسلاگ `my-courses` که والدش برگهٔ `panel` باشد) و در پیشخوان «قالب صفحه» = همان Template Name انتخاب شود.

### ⚠️ هشدار مهم
از وردپرس ۴.۷ به بعد، تمپلیت‌های داخل زیرپوشهٔ سطح اول (مثل همین `panel/`) به‌صورت خودکار در متاباکس «قالب صفحه» پیشخوان پیدا می‌شوند. اگر در نصب شما این تمپلیت‌ها در لیست نیستند، فیلتر `theme_page_templates` را اضافه کنید (فایل ۱۰) — یا نسخهٔ وردپرس را بررسی کنید.

## ۲. گارد دسترسی مشترک

گارد مشترک همهٔ فایل‌های پنل (به‌جز `profile.php` که هیچ گاردی ندارد) این الگوست؛ فقط مقدار `redirect_to` در هر فایل فرق دارد:

| فایل | redirect_to بعد از لاگین |
|---|---|
| `page-panel.php` | `https://edu.falnic.com/panel` |
| `panel/dashboard.php` | `https://edu.falnic.com/panel` |
| `panel/my-courses.php` | `https://edu.falnic.com/panel/my-courses.php` |
| `panel/payments.php` | `https://edu.falnic.com/panel/payments.php` |
| `panel/wishlist.php` | `https://edu.falnic.com/panel/wishlist.php` |
| `panel/settings.php` | `https://edu.falnic.com/panel/settings.php` |
| `panel/certificates.php` | ⚠️ `https://edu.falnic.com/panel/payments.php` (به‌اشتباه به صفحهٔ تراکنش‌ها می‌رود، نه certificates) |

```php
if (!is_user_logged_in()) {
    wp_redirect('https://edu.falnic.com/login?redirect_to=' . /* مسیر همان صفحه */);
    exit;
}
```

- ⚠️ ریدایرکت و redirect_to هاردکدِ دامنهٔ تولید است؛ در لوکال باید `home_url('/login')` شود.
- ⚠️ در redirect_to ها پسوند `.php` اضافه دیده می‌شود (`/panel/my-courses.php`) که مسیر درستی نیست (صفحهٔ واقعی `my-courses` است).
- `page-panel.php` (ریشه) فقط گارد دارد و بین هدر/فوتر محتوایی ندارد → احتمالاً باید داشبورد را include کند یا ریدایرکت به `/panel/my-courses` بدهد.

## ۳. سایدبار (`panel/sidebar.php`)
- شناسایی صفحهٔ فعال: `get_post_field('post_name', get_queried_object_id())` مقایسه با `my-courses|certificates|wishlist|payments|profile|settings` (صفحهٔ `panel` برای greeting).
- سلام: `get_user_meta($uid,'first_name',true)` (⚠️ در جریان ثبت‌نام فعلی، first_name فقط وقتی کاربر جدید از گام نام رد شده باشد پر می‌شود).
- آیتم‌ها: دوره‌های من، گواهینامه‌ها، علاقه‌مندی‌ها، تراکنش‌ها، تنظیمات پروفایل، تنظیمات حساب کاربری، خروج.
- «خروج» یک `<a data-url="wp_logout_url('login')">` است؛ مودال تایید (`.logout-modal-*`) توسط `panel.js` مدیریت می‌شود.

## ۴. داشبورد (`dashboard.php`)
- `learndash_user_get_enrolled_courses($uid)` → تعداد/لیست دوره‌ها.
- شمارش تراکنش از جدول `{wp}_falnic_transactions` (`COUNT(*) WHERE user_id`).
- کارت دوره‌های جاری: تصویر (یا placeholder)، عنوان، نویسنده، دسته‌ها، قیمت (`price_type=free` یا خالی → رایگان).
- اگر دوره‌ای نبود: باکس «هنوز دوره ای شرکت نکردی!!» + دکمه به `/`.

## ۵. دوره‌های من (`my-courses.php`)
- توابع کمکی در ابتدای فایل: `falnic_get_course_categories`، `falnic_get_course_instructor`، `falnic_get_course_price_label`، `falnic_get_course_thumbnail`، `falnic_to_persian_digits`.
- برای هر دوره: `learndash_course_progress` → درصد؛ `learndash_course_completed` → تکمیل؛ `learndash_get_course_certificate_link` → گواهینامه.
- متن راهنما بر اساس گواهینامه:
  - بدون لینک گواهینامه → «برای این دوره گواهینامه صادر نمیشود»
  - تکمیل‌شده → «شما میتوانید گواهینامه دریافت کنید» + دکمهٔ «آزمون و دریافت گواهینامه»
  - در حال یادگیری → متن آموزشی
- اکشن‌ها: «ثبت نظر» (لینک به `#reviews` دوره) و «ادامه دوره».
- جستجوی سمت کلاینت روی `data-course-title`.
- «دوره‌های پیشنهادی»: `WP_Query` رندم ۳ دوره به‌جز دوره‌های ثبت‌نام‌شده.

## ۶. علاقه‌مندی‌ها (`wishlist.php`)
- `fav_courses` را به آرایه تبدیل (explode) + filter/intval + `array_reverse` (جدیدترین اول).
- پرش از دوره‌های غیر publish / غیر sfwd-courses.
- دکمهٔ حذف (`.remove-from-wishlist`) با data-course-id → AJAX `toggle_course_wishlist` با nonce `wishlist_nonce`؛ در موفقیت کارت fadeOut می‌شود.
- جستجوی زنده روی `data-title`.

## ۷. گواهینامه‌ها (`certificates.php`)
- برای هر دورهٔ ثبت‌نام‌شده: اگر `learndash_get_course_certificate_link` خالی نبود → کارت با تصویر نمونهٔ ثابت `certificate-thumb.png`، دریافت PDF و دکمهٔ «افزودن به لینکدین» (لینک استاندارد LinkedIn Add Certification با نام دوره).
- حالت خالی: تصویر نمونه + متن + دکمهٔ `/`.

## ۸. تراکنش‌ها (`payments.php`)
- `global $wpdb` + `SELECT * FROM {wp}_falnic_transactions WHERE user_id … ORDER BY created_at DESC`.
- هر ردیف: عنوان دوره (`get_the_title(course_id)` یا «دوره نامشخص (حذف شده)»)، وضعیت success→موفق/دیگر→ناموفق، تاریخ `wp_date('Y/m/d')` + ساعت، tracking_code، مبلغ.
- دکمهٔ «دانلود رسید» (فقط موفق‌ها فعال) → مودال فاکتور (overlay) با دیتای data-* → `window.print()` برای چاپ.
- حالت خالی: پیام + دکمه.

## ۹. پروفایل (`profile.php`)
- فیلدها: `first_name_fa/last_name_fa/first_name_en/last_name_en/gender(male|female)/birth_date(data-jdp جلالی)`.
- ذخیره با AJAX `save_user_profile` (nonce `profile_nonce_action` داخل فرم به‌صورت hidden field).
- نکتهٔ UI: «اطلاعات گواهینامه پس از ثبت قابل تغییر نخواهد بود».
- ⚠️ هیچ‌کدام از این مقادیر در صفحهٔ گواهینامه/PDF فعلاً استفاده نمی‌شود (گواهی از خود لرن‌دش صادر می‌شود).

## ۱۰. تنظیمات حساب (`settings.php`)
- موبایل: فقط‌خواندنی، مقدار `user_login`؛ تاییدشده badge اگر `billing_phone` پر باشد.
- ایمیل: فقط‌خواندنی؛ مقداردهی عجیب:
  ```php
  $user_email = !str_starts_with($current_user->user_email, "09")
      ? $current_user->user_email
      : "sdasd@dfsfd.dfd";
  ```
  یعنی اگر ایمیل کاربر با «09» شروع شود (حالت ایمیل‌های ساختگی `{mobile}@falnic.user` که با رقم شروع می‌شوند) یک placeholder جعلی نمایش داده می‌شود! (باید اصلاح شود — فایل ۱۰)
- رمز: مقدار نمایشی `..........`؛ کلیک روی آیکن edit مودال تغییر رمز را باز می‌کند.
- ذخیره با AJAX `save_account_settings` (nonce `settings_nonce_action`)؛ تغییر ایمیل اگر `is_email` و تکراری نباشد؛ تغییر رمز اگر با `..........` فرق داشته باشد؛ ذخیرهٔ `billing_phone`.

## ۱۱. چیدمان مشترک
هر صفحه: `.container` (فلکس) ← `include_once 'sidebar.php'` + `main.main-content`. استایل‌ها در `panel.css`؛ `panel.js` + جلالی فقط وقتی enqueue می‌شوند که برگه پنل یا فرزندش باشد (assets_functions).
