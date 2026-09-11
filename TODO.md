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
- [x] **دکمهٔ «انتخاب تصویر» در تنظیمات قالب** — بازتولید با jsdom روی HTML رندرشدهٔ واقعی: دو مسیر شکست بی‌صدا (early-return اسکریپت و `ReferenceError` در نبود `wp.media`) رفع شد، به‌همراه افزودن اسلایدر ناقص (کپی `innerHTML` به‌جای کل ردیف)، جایگزین ورود دستی نشانی و watchdog صفحه.
- [x] **لوگوی سفارشی در فوتر بدون استایل** — قواعد `.ee-logo-img` اضافه شد.
- [x] **لینک‌های سخت‌کد سایدبار پنل** — شش لینک `/panel/…` به `home_url()` تبدیل شد.
- [ ] **انتقال هاردکدهای دامنه** — همهٔ `https://edu.falnic.com/...` و `/wp-content/themes/edu-falnic/...` در فایل‌ها (فهرست کامل در `TECH_DEBT.md`).

## ۲. امنیت (Security)

- [ ] **Rate limit برای ورود با رمز** — `falnic_login_user` محدودیت ندارد (فقط OTP ترنزینت ۶۰ثانیه دارد).
- [ ] **بهبود سشن** — `session_start()` در قالب بدون نام/تنظیمات امن؛ جایگزینی/سخت‌سازی بررسی شود.
- [ ] **بررسی مجدد whitelist فیلدهای** `save_user_profile`/`save_account_settings` (nonce + current_user هست؛ whitelist نسبی است).

## ۳. باگ‌های عملکردی (Functional)

- [ ] **ریدایرکت اشتباه `panel/certificates.php`** — گارد مهمان به `https://edu.falnic.com/panel/payments.php` می‌رود (باید به صفحهٔ certificates خودش).
- [ ] **redirect_to های دارای `.php`** — در `panel/my-courses.php`, `panel/payments.php`, `panel/wishlist.php`, `panel/settings.php` مقدار `.../panel/xxx.php` است (باید بدون `.php`).
- [x] **`page-panel.php` خالی** — کاربر لاگین‌شده را به `/panel/my-courses` ریدایرکت می‌کند؛ همچنین باگ‌های باز نشدن صفحات پنل (نبود `global $wpdb` در داشبورد، include نسبی سایدبار و بارگذاری‌نشدن `panel.css` برای قالب‌های `panel/*`) رفع شد.
- [x] **`index.php` باکس دیباگ** — با یک قالب بازگشتی واقعی جایگزین شد؛ آرشیو/برگهٔ نوشته‌ها/جستجو هم به `archive.php`/`home.php`/`search.php` منتقل شدند.
- [ ] **لاگین با OTP برای کاربر موجود → `wp_set_current_user($user->ID)` روی null** در `handle_register_user` (چون `wp_set_password` id برنمی‌گرداند) — تست و اصلاح.
- [ ] **`falnic_submit_cta`** — hook ثبت شده اما متد `handle_cta_submit` وجود ندارد (در صورت فراخوانی Fatal).
- [x] **`author.php` لینک فیسبوک** — قالب بازنویسی شد؛ شبکه‌های اجتماعی از `evented_instructor_data()` (کلیدهای `youtube`/`linkedin`/`instagram`/`facebook` در user meta) خوانده می‌شوند.
- [x] **`author.js`** — دیگر enqueue نمی‌شود (قالب مدرس با پوستهٔ ee-* بازنویسی شد)؛ فایل باقی‌مانده مرده است و می‌توان حذفش کرد.
- [ ] **settings.php نمایش ایمیل جعلی** — اگر ایمیل با `09` شروع شود، مقدار نمایشی `sdasd@dfsfd.dfd` نشان داده می‌شود (باید ایمیل واقعی یا حالت «تنظیم نشده»).
- [ ] **toast موفقیت بی‌قیدوشرط در صفحهٔ لاگین** — بعد از `falnic_reset_password`/`falnic_register_user` بدون بررسی پاسخ سرور پیام موفقیت می‌آید.
- [x] **دوره‌های مرتبط بدون فیلتر دسته** — در قالب جدید دوره از `evented_related_courses()` استفاده می‌شود: `tax_query` روی `ld_course_category` فعال است و اگر دوره دسته نداشت، به آخرین دوره‌ها برمی‌گردد.

## ۴. Asset های مفقود / خالی (404 / بی‌اثر)

| فایل | وضعیت | پیشنهاد |
|---|---|---|
| ~~`assets/js/archive-post.js`~~ | ✅ enqueue حذف شد (رفتار آرشیو در `evented-home.js`) | — |
| ~~`assets/js/single-post.js`~~ | ✅ enqueue حذف شد | — |
| ~~`assets/css/single-post.css`~~ | ✅ ساخته شد (استایل تک‌نوشته) | — |
| ~~`assets/css/single-page.css`~~ | ✅ شرط enqueue حذف شد (برگه‌ها از `ee-courses.css` استفاده می‌کنند) | — |
| ~~`assets/css/archive-product.css`~~ | ✅ شرط enqueue حذف شد (۴۰۴ در همهٔ برگه‌ها رفع شد) | — |
| ~~`assets/css/archive-post.css`~~ | ✅ پر شد (استایل آرشیو نوشته‌ها) | — |
| `screenshot.png` | ۰ بایت | تصویر واقعی ۱۲۰۰×۹۰۰ |
| `assets/css/photoswipe.min .css` | نام دارای فاصله | اصلاح نام / حذف |
| ~~`images/default-cat.jpg`~~ | ✅ در `page-courses-cat.php` حذف شد؛ کارت دستهٔ بدون تصویر آیکن Material می‌گیرد | — |
| `assets/fonts/DanaVF.ttf` | مفقود (برای captcha) | افزودن یا حذف captcha |
| ~~فونت FontAwesome~~ | ✅ `author.php` بازنویسی شد و از Material Symbols استفاده می‌کند | — |

> توجه: `plyr.css`/`plyr.polyfilled.js` سالم‌اند اما از زمان بازطراحی صفحهٔ دوره/درس دیگر enqueue نمی‌شوند (ویدیو با `<video controls>` پخش می‌شود)؛ پس از تأیید بصری می‌توان حذفشان کرد. همین‌طور `assets/css/single-courses.css` (~۳۸KB) و `assets/js/single-courses.js` (۳۰۹ خط) که فقط به قالب قدیمی دوره تعلق داشتند.

## ۵. کد مرده / غیرفعال (Dead Code)

- [ ] `inc/captcha.php` + `captcha_verify()` — به هیچ فرمی وصل نیست؛ فعال یا حذف شود.
- [ ] بخش «وبینار پیش رو» در `front-page.php` داخل `if (false) {}`.
- [ ] PhotoSwipe (`assets/js/photoswipe.min.js` و CSS) — استفاده نمی‌شود.
- [ ] کامنت‌های اسکریپت/استایل `*-ex` (main-ex/front-page-ex) در `assets_functions.php`.
- [ ] `Untitled-1.json` در ریشه (schema.org خارج از قالب).
- [ ] `assets/css/single-courses.css` + `assets/js/single-courses.js` — با قالب جدید دوره/درس بلااستفاده شدند؛ پس از QA حذف شوند.
- [ ] کلیدهای پادکست درس (`_lesson_audio`/`_lesson_audio_url`/`sfwd-lessons_lesson_audio_url`) و پیوست‌ها (`_lesson_attachments`) بر اساس کلیدهای سفارشی این قالب حدس زده شده‌اند؛ باید با دادهٔ واقعی سایت بررسی و در صورت نیاز متاباکس رسمی اضافه شود.
- [ ] اسکریپت particle canvas کامنت‌شده در `front-page.php`.
- [ ] متغیر/کلاس بلااستفاده (مثل `FalnicAuthHandler::$crm_guids`) در login.php.

## ۶. استاتیک‌هایی که باید داینامیک شوند

- [ ] دسته‌بندی‌ها (۸ کارت) در `front-page.php` — هاردکد با لینک دستی.
- [ ] اساتید (۲ کارت: علی کاظمی/محمد نصیری) در `front-page.php`.
- [ ] نظرات/تجربیات (۴ کارت) در `front-page.php`.
- [x] مقالات در `author.php` — از کوئری اصلی بایگانی نویسنده چاپ می‌شوند.
- [ ] مقالات (۶ کارت به `falnic.com/blog`) در `front-page.php`.
- [x] سایدبار دسته/فهرست‌ها واقعی شد (`template-parts/lms/courses-sidebar.php`: جستجو، دسته‌ها با شمار دوره‌ها، آخرین دوره‌ها، اشتراک‌گذاری).
- [ ] فیلتر/مرتب‌سازی پیشرفته (سطح، مدت، قیمت) در بایگانی دوره و فهرست اساتید — هنوز پیاده نشده.

## ۷. مستندات/کیفیت

- [ ] تکمیل پوشش‌دهی هر تغییر جدید در `CHANGELOG.md`.
- [ ] به‌روزرسانی جدول «کلیدهای متا» در `ARCHITECTURE.md` هنگام افزودن فیلد جدید.
- [ ] افزودن تست خودکار (حداقل `php -l` در CI).
