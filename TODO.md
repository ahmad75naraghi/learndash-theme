# TODO — تسک‌های باز، باگ‌های شناخته‌شده و چک‌لیست‌ها

> این فایل لیست عملیاتی کارهاست (منبع: بازبینی کامل کد). هر آیتم شامل محل دقیق و راه‌حل پیشنهادی است.
> برای شرح عمیق‌ترِ «چرا» به `TECH_DEBT.md` و برای اولویت‌بندی فازها به `ROADMAP.md` مراجعه کنید.

## ۱. بحرانی (P0 — باید قبل از انتشار/استقرار جدید حل شود)

- [ ] **محافظت از فراخوانی تابع بله** — `inc/login.php` در دو نقطه `falnic_send_otp_with_bale($mobile, $otp)` را بدون `function_exists` صدا می‌زند؛ در نبودِ آن (mu-plugin/افزونه) Fatal Error رخ می‌دهد.
  راه‌حل: `if (function_exists('falnic_send_otp_with_bale')) { falnic_send_otp_with_bale(...); }`
- [ ] **escape کردن `redirect_to`** — `page-login.php` (۳ نقطهٔ فعال + ۱ نمونهٔ کامنت‌شده در inline JS):
  `window.location.href = '<?= $_GET['redirect_to'] ?? home_url(); ?>'` — باید `esc_url_raw()` + `esc_js()` شود (و باگ double-encode ناشی از `urlencode` در `FalnicAuthHandler::redirect_login_url` هم رفع شود).
- [ ] **الزام تأیید OTP در ساخت حساب** — `handle_save_user_register_name` (`inc/login.php`) بدون بررسی `fl_otp_verified` با دانستن nonce کاربر می‌سازد.
- [ ] **اعتبارنامهٔ SMS در سورس** — `inc/sms.php`: username/رمز/bodyId هاردکد → انتقال به wp-config/option.
- [ ] **انتقال هاردکدهای دامنه** — همهٔ `https://edu.falnic.com/...` و `/wp-content/themes/edu-falnic/...` در فایل‌ها (فهرست کامل در `TECH_DEBT.md`).

## ۲. امنیت (Security)

- [ ] **Rate limit برای ورود با رمز** — `falnic_login_user` محدودیت ندارد (فقط OTP ترنزینت ۶۰ثانیه دارد).
- [ ] **بهبود سشن** — `session_start()` در قالب بدون نام/تنظیمات امن؛ جایگزینی/سخت‌سازی بررسی شود.
- [ ] **بررسی مجدد whitelist فیلدهای** `save_user_profile`/`save_account_settings` (nonce + current_user هست؛ whitelist نسبی است).

## ۳. باگ‌های عملکردی (Functional)

- [ ] **ریدایرکت اشتباه `panel/certificates.php`** — گارد مهمان به `https://edu.falnic.com/panel/payments.php` می‌رود (باید به صفحهٔ certificates خودش).
- [ ] **redirect_to های دارای `.php`** — در `panel/my-courses.php`, `panel/payments.php`, `panel/wishlist.php`, `panel/settings.php` مقدار `.../panel/xxx.php` است (باید بدون `.php`).
- [ ] **`page-panel.php` خالی** — بین هدر/فوتر محتوایی ندارد؛ باید داشبورد را include کند یا ریدایرکت به اولین زیرصفحه.
- [ ] **`index.php` باکس دیباگ** — هر صفحهٔ بدون قالب (درس، quiz، آرشیو عمومی) اطلاعات وردپرس را چاپ می‌کند → نوشتن `index.php` واقعی.
- [ ] **لاگین با OTP برای کاربر موجود → `wp_set_current_user($user->ID)` روی null** در `handle_register_user` (چون `wp_set_password` id برنمی‌گرداند) — تست و اصلاح.
- [ ] **`falnic_submit_cta`** — hook ثبت شده اما متد `handle_cta_submit` وجود ندارد (در صورت فراخوانی Fatal).
- [ ] **`author.php` لینک فیسبوک** — متغیر `$facebook` تعریف نمی‌شود؛ بلوک `!empty($facebook)` همیشه false.
- [ ] **`author.js`** — `selectedRating` بدون `var/let/const` (متغیر سراسری)؛ لوپ index روی ستاره‌ها در RTL ترتیب را اشتباه active می‌کند.
- [ ] **settings.php نمایش ایمیل جعلی** — اگر ایمیل با `09` شروع شود، مقدار نمایشی `sdasd@dfsfd.dfd` نشان داده می‌شود (باید ایمیل واقعی یا حالت «تنظیم نشده»).
- [ ] **toast موفقیت بی‌قیدوشرط در صفحهٔ لاگین** — بعد از `falnic_reset_password`/`falnic_register_user` بدون بررسی پاسخ سرور پیام موفقیت می‌آید.
- [ ] **دوره‌های مرتبط بدون فیلتر دسته** — `tax_query` در `single-sfwd-courses.php` کامنت است؛ فعال شود یا حذف.

## ۴. Asset های مفقود / خالی (404 / بی‌اثر)

| فایل | وضعیت | پیشنهاد |
|---|---|---|
| `assets/js/archive-post.js` | ❌ مفقود ولی enqueue می‌شود | ساخت یا حذف شرط |
| `assets/js/single-post.js` | ❌ مفقود ولی enqueue می‌شود | ساخت یا حذف شرط |
| `assets/css/single-post.css` | ❌ مفقود | ساخت یا حذف شرط |
| `assets/css/single-page.css` | ❌ مفقود | ساخت یا حذف شرط |
| `assets/css/archive-product.css` | ❌ مفقود | ساخت یا حذف شرط |
| `assets/css/archive-post.css` | موجود، ۰ بایت | پر کردن یا حذف enqueue |
| `screenshot.png` | ۰ بایت | تصویر واقعی ۱۲۰۰×۹۰۰ |
| `assets/css/photoswipe.min .css` | نام دارای فاصله | اصلاح نام / حذف |
| `images/default-cat.jpg` (در ریشهٔ قالب؛ fallback در `page-courses-cat.php`) | مفقود | افزودن فایل |
| `assets/fonts/DanaVF.ttf` | مفقود (برای captcha) | افزودن یا حذف captcha |
| فونت FontAwesome | لودر نیست (آیکن fa-facebook در author.php) | حذف آیکن یا لود واقعی |

> توجه: `plyr.css` سالم است (~۳۲KB) و مشکل ندارد.

## ۵. کد مرده / غیرفعال (Dead Code)

- [ ] `inc/captcha.php` + `captcha_verify()` — به هیچ فرمی وصل نیست؛ فعال یا حذف شود.
- [ ] بخش «وبینار پیش رو» در `front-page.php` داخل `if (false) {}`.
- [ ] PhotoSwipe (`assets/js/photoswipe.min.js` و CSS) — استفاده نمی‌شود.
- [ ] کامنت‌های اسکریپت/استایل `*-ex` (main-ex/front-page-ex) در `assets_functions.php`.
- [ ] `Untitled-1.json` در ریشه (schema.org خارج از قالب).
- [ ] اسکریپت particle canvas کامنت‌شده در `front-page.php`.
- [ ] متغیر/کلاس بلااستفاده (مثل `FalnicAuthHandler::$crm_guids`) در login.php.

## ۶. استاتیک‌هایی که باید داینامیک شوند

- [ ] دسته‌بندی‌ها (۸ کارت) در `front-page.php` — هاردکد با لینک دستی.
- [ ] اساتید (۲ کارت: علی کاظمی/محمد نصیری) در `front-page.php`.
- [ ] نظرات/تجربیات (۴ کارت) در `front-page.php`.
- [ ] مقالات (۶ کارت به `falnic.com/blog`) در `front-page.php` و `author.php`.
- [ ] فیلترهای سایدبار و مرتب‌سازی در `taxonomy-ld_course_category.php` و `template-instructors.php` — UI بدون منطق.

## ۷. مستندات/کیفیت

- [ ] تکمیل پوشش‌دهی هر تغییر جدید در `CHANGELOG.md`.
- [ ] به‌روزرسانی جدول «کلیدهای متا» در `ARCHITECTURE.md` هنگام افزودن فیلد جدید.
- [ ] افزودن تست خودکار (حداقل `php -l` در CI).
