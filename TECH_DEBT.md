# TECH DEBT — بدهی فنی، ریفکتور و تنگناهای عملکرد

فهرست «آگاهانه» از مشکلات ساختاری/فنی کدبیس که مدل‌های AI و توسعه‌دهنده‌ها باید پیش از تغییرات جدید از آن‌ها مطلع باشند (یا در فرصت مناسب برطرف کنند). هر مورد: **محل | مشکل | ریسک | راه‌حل پیشنهادی**.

> برای آیتم‌های عملیاتی/باگ با اولویت‌بندی، `TODO.md` و برای فازبندی، `ROADMAP.md` را ببینید.

---

## ۱. هاردکد و عدم قابلیت حمل (Portability)

| محل | مشکل | ریسک | راه‌حل |
|---|---|---|---|
| `header.php` (لوگو)، `footer.php` (لوگو/نمادها)، `front-page.php`، `single-sfwd-courses.php`، `inc/meta_functions.php` (آواتار پیش‌فرض)، `page-panel.php` + `panel/*.php` (ریدایرکت‌ها)، `style.css` (سطر ~۳۰۳) | آدرس‌های مطلق `https://edu.falnic.com/wp-content/themes/edu-falnic/...` و `/wp-content/themes/edu-falnic/...` | اجرا در لوکال/دامنهٔ جدید می‌شکند (تصویر/ریدایرکت خراب) | جایگزینی با `get_template_directory_uri()` / `home_url()`؛ تعریف ثابت `FALNIC_*` در wp-config |
| `inc/sms.php` | اعتبارنامهٔ پیامک (username/password/bodyId) در سورس | افشای راز در Git | خواندن از ثابت wp-config؛ fallback برای لوکال |
| `inc/login.php` | `session_start()` بدون نام و تنظیمات امن | Session fixation/محتوای پیش‌فرض | سشن با `session_set_cookie_params` امن یا حذف و جایگزینی |

## ۲. امنیت (Security Debt)

| محل | مشکل | ریسک | راه‌حل |
|---|---|---|---|
| `page-login.php` | `$_GET['redirect_to']` بدون escape در inline JS (۳ نقطهٔ فعال؛ سطرهای ~۱۰۱۳، ~۱۱۴۱، ~۱۳۳۶) + double-encode از `redirect_login_url()` | XSS بالقوه/ریدایرکت خراب | `esc_url_raw()` + `esc_js()`؛ حذف `urlencode` اضافی |
| `inc/login.php` | `handle_save_user_register_name` بدون بررسی OTP | ساخت حساب انبوه | الزام `fl_otp_verified` + تطابق `fl_mobile` |
| `inc/login.php` | `handle_register_user`: `$user = get_user_by('id', $user_id)` که null است چون `wp_set_password` id برنمی‌گرداند | لاگین خودکار اجرا نمی‌شود / اخطار | `wp_set_password($p, $user->ID)` سپس همان `$user` را استفاده کن |
| `inc/login.php` | عدم throttle برای `falnic_login_user` | Brute-force | محدودسازی بر اساس `wp_login_failed`/ترنزینت |
| `inc/sms.php` | اعتبارنامهٔ هاردکد (بند ۱) | افشا | ثابت/option |
| `inc/theme_options.php` | `restrict_subscriber_admin_access` شامل AJAX نمی‌شود (عمدی برای login؟) | پنل/اکشن‌های AJAX محافظت nonce دارند اما بررسی نقش نشده | بازبینی: محدودسازی‌ها در سطح اکشن‌ها |

## ۳. تکرار کد (Duplication) — ریفکتور پیشنهادی

| محل‌های تکراری | مشکل | راه‌حل |
|---|---|---|
| قیمت/تصویر/دسته/مدرس دوره در `front-page.php`، `single-sfwd-courses.php`، `taxonomy-ld_course_category.php`، `panel/my-courses.php`، `author.php` | منطق کارت دوره ۵ بار تقریباً یکسان (با کلاس‌های CSS متفاوت) | استخراج به `inc/helpers.php`: `falnic_get_course_price_label()`, `falnic_get_course_thumbnail()`, `falnic_get_course_terms()`, `falnic_render_course_card()` |
| تبدیل ارقام فارسی↔انگلیسی | در `inc/login.php` (۲ بار)، `inc/captcha.php`، `main.js`، و منطق‌های دیگر | یک تابع سراسری `falnic_normalize_digits($s)` |
| متغیرهای ابتدای `panel/*.php` (گارد لاگین + `get_current_user_id`) | کپی در ۷ فایل | قالب «گارد مشترک» در `panel/sidebar.php` یا فایل `panel/guard.php` که include شود |
| HTML مودال/کارت با رشتهٔ PHP | `single-sfwd-courses.php` (متغیر `$modals_html` با رشته‌های بلند) | partial ها با `get_template_part` یا HTML داخل حلقه (escape در هر دو) |

## ۴. فایل‌های غول‌پیکر و ساختار

| فایل | اندازه | مشکل | راه‌حل |
|---|---|---|---|
| `page-login.php` | ~۱۴۰۰ خط (HTML+CSS+JS داخل فایل) | عدم اشتراک استایل/فونت با قالب؛ نگهداری سخت | جداکردن استایل به `login.css`/اسکریپت به فایل JS (در صورت امکان) |
| `single-sfwd-courses.php` | ~۱۰۸۰ خط | همهٔ بخش‌ها در یک فایل | partial: سرفصل/مودال/وبینار/نظرات/سایدبار |
| `front-page.php` | ~۹۳۰ خط (SVG های inline حجیم) | نگهداری سخت | SVG به sprite/فایل جدا |
| `style.css` + CSS های صفحه‌ای | تکرار کلاس‌ها بین `style.css` و فایل‌های صفحه‌ای | تداخل | ممیزی کلاس‌های مشترک |
| سطرهای بیش از ۱۰۰۰ کاراکتر | (SVG های تک‌خطی) | دیفیف/مرور سخت | فرمت‌بندی |

## ۵. Anti-pattern های کوچک

- **short open tag** در `front-page.php` (~سطر ۲۸۱) و `single-sfwd-courses.php` (~سطرهای ۴۷۶/۴۸۶) → وابسته به `short_open_tag=On`.
- **`$is_reviews_enabled = true;` بی‌قیدوشرط** در `single-sfwd-courses.php` → بخش نظرات همیشه نمایش داده می‌شود.
- **`author.php`**: `$facebook` استفاده‌شده ولی تعریف‌نشده؛ ستاره امتیاز بدون ارسال؛ آیکن FontAwesome بدون لودر.
- **`author.js`**: `selectedRating` سراسریِ ناخواسته؛ لوپ ستاره‌ها در RTL.
- **قیمت «تومان»** بدون تبدیل به ریال/درگاه واقعی — پرداخت‌های LD/WC خارج از قالب.
- **کلاس/متغیرهای بلااستفاده**: `FalnicAuthHandler::$crm_guids`، `$facebook` و… (فهرست کامل: `TODO.md` §5).

## ۶. تنگناهای عملکرد (Performance)

| محل | مشکل | ریسک | راه‌حل |
|---|---|---|---|
| `author.php` | حلقهٔ روی **همهٔ دوره‌های مدرس** + برای هر دوره `learndash_get_users_for_course` (N+1) | صفحهٔ اساتید پربازدید کند می‌شود | کوئری تجمیعی دانشجویان یک‌بار (`get_users` با role ثبت‌نام دوره‌ها) یا کش ترنزینت |
| `single-sfwd-courses.php` | برای **هر درس باز** یک `div` مودال کامل (با ویدیو/iframe) در DOM ساخته می‌شود | حجم DOM و درخواست‌های embed برای دوره‌های بلند | ساخت مودال به‌صورت lazy (فقط هنگام باز شدن) |
| `taxonomy-ld_course_category.php` | `get_post_meta('_sfwd-courses')` و `get_the_terms` برای هر کارت در لوپ | تعداد کوئری بالا در آرشیوهای شلوغ | کش متا در ترنزینت یا `update_postmeta_cache` گروهی |
| `front-page.php` | تصاویر/فایل‌های وب بزرگ (heroimg و…) و SVG های تکراری در هر رندر | FCP سنگین | lazy-load، sprite، فشرده‌سازی تصاویر |
| سراسری | **هر دو** `owl.carousel` (css/js) در **همهٔ صفحات** لود می‌شود؛ `main.js` عمومی | بار اضافه در صفحات ساده | enqueue شرطی بر اساس نیاز (البته پنل/صفحات مختلف به owl نیاز دارند — نقشهٔ enqueue را ببینید) |
| `inc/meta_functions.php` | فیلتر `get_avatar` روی هر آواتار، چند `get_user_meta` | کم اما تکرارشونده | کش object |

## ۷. وابستگی‌های «نامرئی» (باید برای هر توسعه‌دهنده روشن باشد)

1. تابع `falnic_send_otp_with_bale()` — خارج از قالب (mu-plugin). در ریپو نیست.
2. جدول `{wp}_falnic_transactions` — DDL در `ARCHITECTURE.md` §3؛ سازنده در ریپو نیست.
3. افزونهٔ LearnDash Course Grid — کلید `_learndash_course_grid_duration` از آن خوانده می‌شود (اگر افزونه نباشد، مدت درس‌ها خالی است).
4. درگاه پرداخت/ووکامرس — دکمه‌های `learndash_payment_buttons()` به تنظیمات خود لرن‌دش/افزونهٔ پرداخت وابسته‌اند.
5. اعتبارنامهٔ پنل پیامک در `inc/sms.php`.

## ۸. مواردی که «فعلاً عمدی/قابل قبول» است (نباید بی‌جهت عوض شود)

- `page-login.php` بدون `wp_head`/`wp_footer` — طراحی تمام‌صفحه عمدی.
- تمپلیت‌های پنل در زیرپوشهٔ `panel/` — از وردپرس 4.7+ خودکار شناسایی می‌شوند.
- متن‌های فارسی مستقیم در قالب (بدون i18n) — سایت تک‌زبانه است.
- نام کاربری = شماره موبایل و ایمیل ساختگی `{mobile}@falnic.user` — قرارداد دامنهٔ احراز هویت فعلی (بازبینی در فاز ۲: نمایش ایمیل در settings.php).
