# ۱۰ — مشکلات شناخته‌شده، ناتمامی‌ها و پیشنهاد اصلاح (To-Do)

> همهٔ موارد زیر از بررسی مستقیم سورس در ریپو استخراج شده‌اند. مرتب‌سازی بر اساس شدت/اولویت.

## A. باگ‌های بحرانی (Critical — روی تولید اثر دارد)

### A1. تابع تعریف‌نشده `falnic_send_otp_with_bale()`
- محل صدا زدن: `inc/login.php` (هنگام ارسال OTP برای کاربر جدید و resend).
- اگر این تابع در سایت (mu-plugin/افزونهٔ بیرونی) تعریف نشده باشد، ارسال کد با **Fatal Error** می‌شکند.
- اقدام: یا تابع را در قالب تعریف کنید (و ارسال به بله را پیاده/غیرفعال کنید) یا در `inc/login.php` با `function_exists` محافظت کنید:
  `if (function_exists('falnic_send_otp_with_bale')) falnic_send_otp_with_bale($m, $c);`

### A2. captcha باگ است (کپچا به‌هیچ‌وجه وصل نیست)
- `inc/captcha.php` (`generate_captcha_image`) از `assets/fonts/DanaVF.ttf` استفاده می‌کند که **در ریپو نیست** (فقط `falnic-font.woff2` موجود است) → `imagettftext` با خطا مواجه می‌شود.
- هیچ endpoint/فرم‌ی captcha را صدا نمی‌زند (هیچ ارجاعی به `generate_captcha_image` در کد نیست)؛ وجود تابع `captcha_verify()` در `inc/login.php` هم فقط یک helper بلااستفاده است.
- اقدام: یا حذف، یا فعال‌سازی کامل (نقطهٔ تصویر + سشن `captcha_code` + فراخوانی در فرم ورود/ثبت‌نام) + افزودن فونت TTF.

### A3. اتصال به جدول/سرویس‌های بیرونی که در این قالب نیستند
- جدول `{wp}_falnic_transactions` ساخته/پر نمی‌شود (پرداخت احتمالاً توسط افزونه/درگاه دیگر).
- دریافت OTP از طریق بله (A1).
- اگر سایت لوکال بدون اینها اجرا شود، صفحات «تراکنش‌ها/داشبورد» خطا/خالی می‌شوند و OTP ارسال نمی‌شود.

## B. هاردکد دامنهٔ تولید (Portability)

### B1. آدرس‌های asset و ریدایرکت
- ده‌ها `https://edu.falnic.com/wp-content/themes/edu-falnic/...` و `https://edu.falnic.com/...` هاردکد در: `header.php` (لوگو)، `footer.php` (لوگو/نمادها)، `front-page.php`، `single-sfwd-courses.php` (وبینار/گواهینامه)، `inc/meta_functions.php` (آواتار پیش‌فرض)، `page-panel.php` و `panel/*.php` (ریدایرکت‌ها).
- نمونهٔ قابل‌توجه: `style.css` سطر ۳۰۳ برای تصویر پس‌زمینه، URL مطلق دارد.
- ریدایرکت‌های `inc/login.php` و `panel/*` و `page-panel.php` باید به `home_url()`/`wp_login_url()` تبدیل شوند (ضمن این‌که برخی redirect_to ها مثل `.../panel/my-courses.php` پسوند `.php` اضافه دارند).
- لینک‌های `author.php` به بلاگ (`https://falnic.com/blog/...`) عمدی است (بلاگ دامنهٔ دیگر است)؛ fallback تصویرش هم از `get_template_directory_uri()` استفاده می‌کند — این دو را با «هاردکد» اشتباه نگیرید.
- ساختار پیشنهادی: ثابت `THEME_URI = get_template_directory_uri()` در functions.php (تعریف‌شده `PATH_DIR_URL` هست؛ در همهٔ نقاط استفاده نشده).

### B2. مسیرهای absolute داخل CSS
- `style.css` و استایل‌های inline فایل‌ها بعضاً عکس را از مسیر absolute لود می‌کنند.

## C. فایل‌های asset مفقود/خالی (404)

| فایل | اقدام |
|---|---|
| `assets/js/archive-post.js`, `assets/js/single-post.js` | ساخت یا حذف شرط enqueue |
| `assets/css/single-post.css`, `assets/css/single-page.css`, `assets/css/archive-product.css` | ساخت یا حذف شرط enqueue |
| `assets/css/archive-post.css` (۰ بایت — تنها CSS خالی قالب) | پر کردن (یا حذف enqueue) |
| `assets/img/.../default-cat.jpg` (fallback page-courses-cat) | افزودن فایل |
| `screenshot.png` (۰ بایت) | تصویر واقعی ۱۲۰۰×۹۰۰ |
| `assets/css/photoswipe.min .css` (نام با فاصله) | اصلاح نام + حذف از enqueue یا استفاده واقعی |
| فونت `DanaVF.ttf` برای captcha | افزودن (A2) |

> توجه: `assets/css/plyr.css` با وجود enqueue در صفحهٔ دوره، سالم و ~۳۲KB است (استایل vendor پلیر) و مشکل ندارد.

نکته: این ۴۰۴ها روی «بلاگ/تک‌پست/برگه‌های عمومی» رخ می‌دهند و صفحاتی مثل `page.php`/`404.php` دارند استایل می‌گیرند که نیست؛ ساده‌ترین راه: شرط‌های ۵–۷ enqueue را محدود به صفحاتی کنید که واقعاً فایل دارند یا فایل‌های کمینه بسازید.

## D. مسائل امنیتی

- **D1. خطر XSS + باگ ریدایرکت در `page-login.php`**: `$_GET['redirect_to']` بدون escape مستقیم داخل inline JS چاپ می‌شود (`window.location.href='<?= $_GET['redirect_to'] ?? home_url();?>'`). با وجود اینکه داخل رشتهٔ تک‌کوتِ JS است و مستقیم خارج‌شدن از آن سخت است، این الگو «خطرناک» است (هر escape ای می‌تواند XSS شود) و افزون بر آن چون مقدار در `redirect_login_url()` یک‌بار `urlencode` شده (فایل ۰۵)، چاپِ خامِ آن در صفحهٔ لاگین **double-encode** می‌شود و `redirect_to=/panel` به `/panel%252F` (مسیر اشتباه) می‌انجامد.
  ← اصلاح: در `page-login.php` مقدار را با `esc_url_raw()` در PHP بگیرید و با `esc_js()` چاپ کنید؛ و در `redirect_login_url()` به‌جای `urlencode` کل کوئری از `add_query_arg` استفاده شود (یا طرف چاپ فقط یک‌بار decode شود).
- **D2. اعتبارنامهٔ SMS در سورس** (`inc/sms.php`: username/رمز/bodyId) ← انتقال به option/ثابت وابسته به env یا افزونه.
- **D3. ساخت حساب بدون اعتبارسنجی OTP**: `save_user_register_name` سشن `fl_otp_verified` را چک نمی‌کند؛ با دانستن nonce می‌شود کاربر ساخت ← قبل از ساخت، تأیید OTP را الزامی کنید.
- **D4. عدم محدودیت نرخ در اکشن‌های ورود**: فقط OTP دارای transient محدودیت است؛ `falnic_login_user` را با `wp_login_failed`/throttling محافظت کنید.
- **D5. session_start در قالب** بدون نام/طول عمر؛ توصیه: session cookie محکم‌تر یا جایگزینی با ترنزینت رمزنگاری‌شده.
- **D6. دسترسی AJAX پنل** (save_user_profile/save_account_settings) تنها nonce/current_user دارد؛ فیلدهای ارسالی را whitelist کنید (تا حدودی شده؛ بررسی مجدد).

## E. مشکلات منطقی/رفتاری

### E1. «دوره‌های مرتبط» بدون فیلتر دسته
- کوئری tax_query در `single-sfwd-courses.php` داخل کامنت است → فقط ۴ دورهٔ رندم/جدید به‌جز جاری. اگر هدف «هم‌دسته» است، کد کامنت‌شده را فعال کنید.

### E2. `$is_reviews_enabled = true;` در انتهای شرط‌ها
- بخش نظرات **همیشه** نمایش داده می‌شود حتی اگر افزونهٔ نقد لرن‌دش غیرفعال باشد. اگر فرم نقد باید فقط در شرایطی باشد، شرط واقعی بگذارید.

### E3. گام رمز صفحهٔ لاگین: toast موفقیت بی‌قیدوشرط
- بعد از `falnic_reset_password`/`falnic_register_user`، در JS صرف‌نظر از خطای سرور، پیام موفقیت نمایش داده می‌شود ← پاسخ را بررسی کنید.

### E4. `author.php`: لینک فیسبوک و ستاره امتیاز
- `$facebook` تعریف/خوانده نمی‌شود (بلاک شرطی هیچ‌وقت رندر نمی‌شود).
- ستاره‌های «ثبت امتیاز» دکمهٔ فعال ندارند (بدون handler ارسال)؛ فعلاً UI است.

### E5. `index.php` فایل دیباگ است
- چون fallback همهٔ صفحه‌های بدون قالب است، هر صفحهٔ لرن‌دشی/آرشیوی بدون قالب اختصاصی، «باکس دیباگ» چاپ می‌کند. برای production یک `index.php` استاندارد بنویسید.

### E6. `page-panel.php` محتوای خالی
- بین هدر/فوتر هیچ چیزی ندارد؛ باید داشبورد را include کند یا به اولین زیرصفحه ریدایرکت دهد.

### E7. `page-login.php` بدون get_header/get_footer
- طراحی عمدی است (صفحهٔ تمام‌صفحه)؛ ولی فونت/CSS آن مستقل تعریف شده — هنگام تغییر فونت/استایل سایت، این صفحه را جداگانه هماهنگ کنید.

### E8. دکمهٔ «وبینار پیش رو» و `countdown-timer`
- بخش وبینار صفحهٔ اصلی در `if(false)` غیرفعال است؛ اگر نیازی نیست حذف شود.

### E9. نام/آیکن «خانه» موبایل و... در `footer.php`
- وضعیت active از `is_current_path()`؛ این تابع مسیر را با پرش به اسلش مقایسه می‌کند — اگر برگه/مسیرهای شرطی درست ست نشوند، active اشتباه می‌شود (چک در توسعهٔ مسیرهای جدید).

### E10. تمپلیت‌های پنل در زیرپوشه (اگر در پیشخوان ظاهر نشدند)
- از وردپرس ۴.۷ به بعد، تمپلیت‌های صفحه در زیرپوشهٔ سطح اول به‌صورت خودکار شناسایی می‌شوند و `panel/*.php` باید در پیشخوان قابل انتخاب باشند.
- اگر با این وجود در لیست «قالب صفحه» نیستند، این فیلتر را به قالب اضافه کنید:
  ```php
  add_filter('theme_page_templates', function ($templates) {
      foreach (glob(get_template_directory() . '/panel/*.php') as $f) {
          $h = get_file_data($f, ['Template Name' => 'Template Name']);
          if (!empty($h['Template Name'])) $templates[str_replace(get_template_directory().'/', '', $f)] = $h['Template Name'];
      }
      return $templates;
  });
  ```

## F. بهبودهای UI/UX و CSS

- **F1.** استایل‌های ووکامرس (`woocommerce.css` قالب) در ریپو نیست؛ اگر افزونهٔ فروش جداگانه استایل دارد، هماهنگی دکمه‌های خرید (`.btn-buy` در سایدبار) را بررسی کنید. (در single-courses.css بخشی برای یکدست‌سازی دکمه‌های LD/WC هست.)
- **F2.** آیکن FontAwesome در `author.php` بدون بارگذاری FontAwesome.
- **F3.** `style.css` به نظر بعضی کلاس‌ها (mobile-menu) را دارد ولی footer.php مارک‌آپ منو را تولید می‌کند — هنگام تغییر ساختار منو، هر دو را با هم عوض کنید.
- **F4.** فونت‌های سیستم/اعداد: اعداد فارسی با `font-family` اصلی؛ در بعضی جای‌ها (قیمت) اعداد با فونت لاتین داخل قیمت هستند — یکدست‌سازی.

## G. فهرست پیشنهادی «کارهای بعدی» (اگر قرار است توسعه ادامه یابد)

1. **ساخت فایل فهرست پیاده‌سازی (Implementation checklist)** برای هر قالب؛ شروع از این اسناد.
2. انتقال مقادیر هاردکد دامنه/اعتبارنامه به ثابت‌ها/option (B/D).
3. پر کردن/ساخت asset های مفقود یا اصلاح enqueue (C).
4. اصلاح آسیب‌پذیری‌های D1–D3.
5. تکمیل «ثبت امتیاز» استاد (E4) با اکشن AJAX واقعی + ذخیره `instructor_rating_average`.
6. فعال‌سازی آپلود تمرین‌ها در صفحهٔ دوره (فعلاً UI است) — یک endpoint آپلود با wp_handle_upload + جدول/attachment.
7. اگر پرداخت قرار است در همین قالب باشد: ساخت جدول `falnic_transactions` + درگاه → اتصال به دکمه‌های `learndash_payment_buttons`.
8. داینامیک‌کردن بخش‌های استاتیک صفحهٔ اصلی (دسته‌ها/اساتید/نظرات/مقالات — فایل ۰۷).
9. نوشتن `index.php` واقعی + حذف `index.php` دیباگ (E5).
10. تست کامل جریان OTP لوکال (با موک کردن sms/bale) چون وابسته به سرویس بیرونی است.
