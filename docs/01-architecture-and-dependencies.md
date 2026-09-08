# ۰۱ — معماری، ترتیب بارگذاری و وابستگی‌ها

## ۱. نقطهٔ شروع (Bootstrap)

### `functions.php` (ریشهٔ قالب — فقط ۶ خط)

```php
define('PATH_DIR_URL', get_template_directory_uri());
define('PATH_DIR', get_template_directory());

require_once get_stylesheet_directory() . '/assets/assets_functions.php';
require_once get_stylesheet_directory() . '/inc/includes.php';
```

نکات:
- دو ثابت سراسری `PATH_DIR_URL` و `PATH_DIR` تعریف می‌شوند (معادل‌های تمپلیت‌دایرکتوری). بعضی فایل‌ها مستقیم `get_template_directory_uri()` را صدا می‌زنند؛ بعضی هم اصلاً مسیر را هاردکد کرده‌اند.
- از `get_stylesheet_directory()` استفاده شده؛ یعنی اگر روزی به child theme تبدیل شود، لود از child انجام می‌شود. برای تم اصلی تفاوتی ندارد.

### زنجیرهٔ require

```
functions.php
 └─ assets/assets_functions.php      → فقط هوک wp_enqueue_scripts (تمپلیت فایل)
 └─ inc/includes.php
     ├─ inc/login.php                → کلاس FalnicAuthHandler (AJAX های ورود/OTP) + captcha_verify
     ├─ inc/sms.php                  → send_pattern_sms (SOAP پیامک)
     ├─ inc/meta_functions.php       → متاباکس دوره، متاهای دسته‌بندی، فیلدهای پروفایل مدرس/کاربر، آواتار
     ├─ inc/theme_options.php        → (اسمش گمراه‌کننده است!) MIME ها، post-thumbnails، ریدایرکت لاگین subscriber، پنهان‌کردن ادمین‌بار، محدودیت wp-admin، is_current_path()
     └─ inc/ajax_functions.php       → review / mark_lesson_complete / wishlist / profile / settings
```

نکته: `falnic_send_otp_with_bale()` در `inc/login.php` صدا زده می‌شود اما **در هیچ‌جای قالب تعریف نشده** — در لایو باید از بیرون (افزونه/mu-plugin) وجود داشته باشد، وگرنه ارسال کد با Fatal Error مواجه می‌شود.

## ۲. وابستگی‌های بیرونی (باید نصب/فعال باشند)

| وابستگی | چرا لازم است |
|---|---|
| **LearnDash** (نسخهٔ ۳+ با Course Builder) | پست‌تایپ `sfwd-courses`، درس‌ها، سکشن‌ها، قفل درس، گواهینامه، پیشرفت، دکمه پرداخت |
| **PHP 8** (حداقل 7.4) | استفاده از `str_starts_with()`، آرگومان‌های تایپ‌شده، null coalescing (`??`) |
| **وب‌سرویس SOAP در PHP** | `inc/sms.php` از `SoapClient` استفاده می‌کند |
| **GD کتابخانهٔ تصویر** | `inc/captcha.php` (فعلاً استفاده نمی‌شود) |
| **فونت `DanaVF.ttf`** | فقط برای captcha (در ریپو موجود نیست؛ فقط `falnic-font.woff2` هست) |
| **سرویس پیامک payamak-panel** | اعتبارنامه هاردکد در `inc/sms.php` |
| **ربات/API پیام‌رسان بله** | تابع تعریف‌نشده `falnic_send_otp_with_bale()` |
| **جدول `{wp}_falnic_transactions`** | صفحات «تراکنش‌ها» و آمار داشبورد پنل از آن می‌خوانند؛ سازندهٔ جدول و درگاه پرداخت در این قالب نیست |
| **فایل favicon در ریشهٔ دامنه** | هدر: `/favicon.ico` و `/apple-touch-icon.ico` |

> ⚠️ اعتبارنامهٔ پیامک (`iranhp` / رمز) و GUID های یک کلاس بلااستفاده (`FalnicAuthHandler::$crm_guids`) در سورس قابل مشاهده‌اند؛ در صورت امکان به option/ثابت منتقل شوند (نکات امنیتی در فایل ۱۰).

## ۳. الگوی کدنویسی مشاهده‌شده

- HTML تمیز با SVG های inline زیاد (بسیاری از آیکون‌ها مستقیم داخل قالب/card ها هستند).
- کلاس‌های CSS فارسی/مفهومی: `.btn-primary`, `.btn-yellow`, `.course-card-pro`, `.eduf-*`, `.ld-*`, `.lesson-row` و…
- بخش‌های داینامیک با `WP_Query` روی `sfwd-courses` و توابع نیتیو لرن‌دش.
- دادهٔ «نظرات/اساتید/مقالات/دسته‌ها» در صفحهٔ اصلی و چند جای دیگر **استاتیک (هاردکد)** است.
- استفاده از short-open-tag در ۲ فایل (با `short_open_tag=On` کار می‌کند، در هاست‌های سخت‌گیر خطا می‌دهد — رجوع به فایل ۱۰):
  - `single-sfwd-courses.php` سطر ~۴۷۶: `<? if ($suffering == '1') { ?>`
  - `front-page.php` سطر ~۲۸۱: `<? } ?>`
- خروجی‌ها اغلب با `esc_html/esc_url` امن‌سازی شده‌اند؛ چند استثنا در فایل ۱۰.

## ۴. منطق ریدایرکت و نقش‌ها (inc/theme_options.php)

- `custom_subscriber_login_redirect`: کاربران با نقش `subscriber` بعد از لاگین به `/panel` می‌روند.
- `hide_admin_bar_for_subscribers`: ادمین‌بار برای subscriber مخفی است.
- `restrict_subscriber_admin_access`: subscriber نمی‌تواند وارد `wp-admin` شود (به `/panel` ریدایرکت می‌شود)؛ درخواست‌های AJAX مسدود نمی‌شوند.
- `is_current_path($path)`: مقایسهٔ مسیر جاری با مسیر داده‌شده (برای active کردن آیتم‌های منوی موبایل در `footer.php`).
- `redirect_login_url` (در `inc/login.php`): لینک استاندارد ورود وردپرس به `/login/` تغییر می‌کند.
- `custom_logout_redirect_to_home`: خروج → صفحهٔ اصلی.

## ۵. نقش‌های کاربری در قالب

| نقش/مفهوم | کجا استفاده شده |
|---|---|
| `subscriber` | کاربر عادیِ خریدار دوره؛ ورود به پنل |
| `group_leader` | «استاد» در تمپلیت لیست اساتید (`template-instructors.php`) |
| `instructor_user_role` (user-meta) | عنوان/نقش نمایشی دلخواه مدرس (نه نقش واقعی WP) |
| ادمین | مدیریت محتوا؛ «مدرس» واقعی دوره‌ها = نویسندهٔ پست دوره (`post_author`) |

> نویسندهٔ دوره (`post_author`) در صفحات دوره/استاد «مدرس» در نظر گرفته می‌شود، نه الزاماً نقش `group_leader`.
