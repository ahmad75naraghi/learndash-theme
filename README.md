# README — قالب وردپرس «evented-edu» (لرن‌دش)

قالب اختصاصی سایت آموزش آنلاین **evented-edu** (وارث قالب «edu falnic»/«فالنیک») برای وردپرس + افزونهٔ **LearnDash**. قالب از هر دامنه‌ای قابل اجراست و هیچ URL هاردکد دامنه ندارد.
این قالب شامل سیستم ورود/ثبت‌نام با **پیامک OTP**، فروش دورهٔ لرن‌دش، پنل کاربری کامل، آرشیو/صفحهٔ تکی دوره، پروفایل مدرس و صفحهٔ اصلی فروشگاهی است. زبان رابط: **فارسی (RTL)**؛ فونت اختصاصی «دانا».

> 📌 این مستندات «منبع حقیقت» پروژه است. قبل از هر تغییری در کد، ترتیب خواندن پیشنهادی:
> `README.md` ← `ARCHITECTURE.md` ← `TECH_DEBT.md` ← `TODO.md`.
> اگر با یک مدل AI کار می‌کنید، ابتدا `AI.md` را مطالعه کنید.

---

## ۱. هدف سیستم

- **آموزش آنلاین IT** (شبکه، سرور، امنیت، سخت‌افزار، مجازی‌سازی، CRM) با مدل دوره‌های ویدیویی لرن‌دش.
- **فروش آنلاین دوره**: دکمه‌های پرداخت نیتیو لرن‌دش + جدول سفارشی تراکنش‌ها (درگاه/سازندهٔ جدول خارج از این قالب).
- **عضویت با شماره موبایل**: ثبت‌نام و ورود بدون ایمیل/رمز، با کد یک‌بارمصرف (OTP) از طریق **پیامک** و **ربات بله**.
- **پنل کاربری** برای: مشاهدهٔ دوره‌های من و پیشرفت، گواهینامه‌ها، علاقه‌مندی‌ها، تراکنش‌ها، پروفایل و تنظیمات حساب.
- **نمایش اساتید**: لیست اساتید (نقش `group_leader`) + پروفایل هر استاد (آمار، سوابق، دوره‌ها، سوشال).

## ۲. پشتهٔ فناوری (Tech Stack)

| لایه | انتخاب | توضیح |
|---|---|---|
| CMS | وردپرس (نسخهٔ ۶+، به‌روز) | فارسی، RTL |
| LMS | **LearnDash** (نسخهٔ 3.6+) | پست‌تایپ `sfwd-courses`، درس، سکشن، گواهینامه، پرداخت |
| زبان/سمت‌سرور | PHP 8.x (سازگار با 7.4) | استفاده از `str_starts_with()` و `??` |
| فرانت‌اند | jQuery + Owl Carousel + Plyr | اسکریپت‌های دستی در `assets/js` |
| فونت | «دانا» (`assets/fonts/evented-edu-font.woff2`) | `@font-face` در `style.css` |
| پیامک OTP | SOAP پنل پیامک (payamak-panel) | `inc/sms.php` — ⚠️ اعتبارنامه هاردکد |
| پیام‌رسان | ربات بله — تابع `evented_send_otp_with_bale()` | قالب یک wrapper محافظت‌شده دارد؛ ارسال واقعی به mu-plugin/افزونه (هم‌نام یا legacy با نام `falnic_send_otp_with_bale`) واگذار می‌شود |
| پایگاه‌دادهٔ تراکنش | جدول سفارشی `{wp}_evented_transactions` | ⚠️ سازندهٔ جدول در این قالب نیست؛ نصب‌های قدیمی باید جدول را تغییر نام دهند (migration) |
| تست | ندارد (در این مرحله) | بخش ۷ را ببینید |

## ۳. ساختار دایرکتوری‌ها

```
learndash-theme/                  (در سرور: wp-content/themes/<نام-پوشه> — کد از get_template_directory_uri() می‌خواند)
├── style.css / functions.php     هدر قالب + بوت‌استرپ (PATH_DIR* + require ها)
├── README.md / ARCHITECTURE.md / CHANGELOG.md / CONTRIBUTING.md / AI.md
├── ROADMAP.md / TODO.md / TECH_DEBT.md
├── header.php / footer.php       هدر/فوتر + action-bar موبایل + منوی کشویی
├── front-page.php                صفحهٔ اصلی (بخش‌های داینامیک/استاتیک — رجوع: ARCHITECTURE §5)
├── single.php                    تک‌نوشته (پوستهٔ ee-*؛ مقاله + سایدبار + دیدگاه)
├── archive.php / home.php / search.php   آرشیو نوشته‌ها، برگهٔ نوشته‌ها، نتایج جستجو
├── comments.php                  فهرست دیدگاه‌ها + فرم دیدگاه (فارسی)
├── template-parts/               ee-head · ee-header · ee-footer · ee-sidebar · ee-archive-main
├── single-sfwd-courses.php       صفحهٔ تکی دوره (سرفصل/ویدیو/خرید/نظر/گواهینامه)
├── taxonomy-ld_course_category.php  آرشیو دسته/دوره
├── archive-sfwd-courses.php      شیم: فقط get_template_part از قالب دسته
├── author.php                    پروفایل مدرس  |  template-instructors.php  لیست اساتید
├── page-login.php                صفحهٔ ورود/OTP (HTML مستقل، بدون wp_head)
├── page-panel.php / page.php / page-courses.php / page-courses-cat.php / 404.php
├── index.php                     قالب بازگشتی عمومی (fallback) برای انواع پست بدون قالب اختصاصی
├── assets/
│   ├── assets_functions.php      منطق enqueue همهٔ CSS/JS
│   ├── css/  front-page · single-courses · archive-courses · single-post · archive-post · author · panel · vendorها
│   ├── css/newhome/  ee-shell (پوستهٔ مشترک) · evented-home (صفحهٔ اصلی)
│   ├── js/   main · front-page · single-courses · author · panel + vendorها
│   ├── js/newhome/evented-home.js  منوی موبایل · اسلایدر هیرو · کپی لینک اشتراک
│   ├── fonts/evented-edu-font.woff2   فونت «دانا»
│   └── img/  front-page (۲۳) · single-page (۸) · panel (۲)
├── inc/
│   ├── includes.php              فقط require کردن ماژول‌های پایین
│   ├── template_helpers.php      هلپرهای پوستهٔ ee-* (شمسی، بازدید، اشتراک، مرتبط‌ها)
│   ├── login.php                 کلاس FalnicAuthHandler (OTP/ورود) + captcha_verify
│   ├── sms.php                   send_pattern_sms (SOAP)
│   ├── meta_functions.php        متاباکس دوره/دسته/کاربر + فیلتر آواتار
│   ├── theme_options.php         MIME ها، گارد subscriber، is_current_path()
│   ├── theme_settings.php        مدیر اسلایدر هیرو در پیشخوان (آپشن evented_home_slides)
│   ├── ajax_functions.php        ۵ اکشن AJAX (نظر/تکمیل درس/علاقه‌مندی/پروفایل/تنظیمات)
│   └── captcha.php               کپچای GD (⚠️ متصل نیست + فونتش مفقود)
└── panel/                        تمپلیت‌های پنل (Template Name: Panel - …)
    ├── sidebar.php و dashboard.php و my-courses.php و certificates.php
    └── wishlist.php و payments.php و profile.php و settings.php
```

## ۴. نقشهٔ سریع صفحات ↔ تمپلیت

| مسیر | قالب | نکته |
|---|---|---|
| `/` | `front-page.php` | لندینگ |
| `/courses/` و `/courses/<cat>/` | `taxonomy-ld_course_category.php` (+ شیم archive) | آرشیو/دسته دوره |
| `/courses/<slug>/` | `single-sfwd-courses.php` | صفحهٔ دوره |
| `/<post-slug>/` | `single.php` | تک‌نوشته (مقاله + سایدبار + دیدگاه) |
| برگهٔ «نوشته‌ها» (is_home) | `home.php` | آرشیو همهٔ نوشته‌ها |
| `/category/<cat>/` و `/tag/<tag>/` | `archive.php` | آرشیو دسته/برچسب با چیپ فیلتر |
| `/?s=<عبارت>` | `search.php` | نتایج جستجو |
| `/login` | `page-login.php` (برگهٔ `login`) | ورود/OTP |
| `/panel` و زیرصفحه‌ها | `panel/*.php` (برگه‌ها با Template Name) | پنل کاربر |
| `/author/<user>/` | `author.php` | پروفایل مدرس |
| برگهٔ «لیست اساتید» | `template-instructors.php` | نقش `group_leader` |
| برگهٔ «دسته‌بندی دوره‌ها» | `page-courses-cat.php` | گرید همهٔ دسته‌ها |
| سایر برگه‌ها | `page.php` | عمومی |
| هر چیز دیگر | `index.php` | قالب بازگشتی عمومی (عنوان + چکیده + صفحه‌بندی) |

## ۵. پیش‌نیازها و نصب

### ۵.۱ پیش‌نیازها (Environment)

| مورد | نسخه/شرط |
|---|---|
| PHP | 8.x با افزونهٔ `soap`، `gd`، `dom`/`libxml` |
| وردپرس | 6.x |
| LearnDash | نسخهٔ فعال (با Course Builder) |
| فونت TTF برای captcha | `DanaVF.ttf` در `assets/fonts/` — در ریپو **نیست** (اختیاری تا زمان فعال‌سازی captcha) |
| جدول `{wp}_evented_transactions` | باید ساخته شود (DDL در `ARCHITECTURE.md` §3) |
| تابع بله | اختیاری: `evented_send_otp_with_bale()` یا legacy `falnic_send_otp_with_bale()` در mu-plugin (بدون آن، فقط پیامک ارسال می‌شود) |

### ۵.۲ نصب (لوکال)

```bash
# ۱) قالب را در مسیر تم‌ها قرار دهید:
wp-content/themes/<theme-folder>/

# ۲) در پیشخوان: نمایش ← پوسته‌ها ← فعال‌سازی «evented-edu»
# ۳) افزونهٔ LearnDash را نصب/فعال کنید (برای دیدن پست‌تایپ دوره‌ها ضروری است)
# ۴) برگهٔ «ورود»: یک برگه با اسلاگ login بسازید (نیازی به انتخاب قالب نیست؛
#      وردپرس به‌صورت خودکار از page-login.php استفاده می‌کند؛ محتوای برگه خالی باشد).
# ۵) برگهٔ «پنل کاربری» با اسلاگ panel بسازید:
#      - یا قالب پیش‌فرض (page-panel.php → گارد لاگین؛ کاربرِ لاگین‌شده به /panel/my-courses هدایت می‌شود) را نگه دارید،
#      - یا «Template Name: Panel - Dashboard» را برای همین برگه انتخاب کنید.
# ۶) زیربرگه‌ها را با «والد = panel» بسازید (فرزند بودن الزامی است؛ چون استایل/اسکریپت پنل
#      فقط وقتی enqueue می‌شود که برگه، خودِ panel یا زیرمجموعهٔ آن باشد) و Template ست کنید:
#      /panel/my-courses → Panel - My Courses
#      /panel/certificates → Panel - Cerificates   (املای «Cerificates» در فایل همین است!)
#      /panel/wishlist  → Panel - Wishlist
#      /panel/payments  → Panel - Payments
#      /panel/profile   → Panel - Profile
#      /panel/settings  → Panel - Account Settings
# ۷) تنظیمات ← پیوندهای یکتا ← «نام پست» (Post name)
# ۸) جدول تراکنش‌ها را بسازید (DDL در ARCHITECTURE.md) و درگاه/ثبت‌کنندهٔ تراکنش را وصل کنید
# ۹) (اختیاری) تابع بله `evented_send_otp_with_bale` (یا legacy `falnic_send_otp_with_bale`) را در mu-plugin تعریف کنید
```


## ۶. پیکربندی (متغیرهای محیطی)

پروژه از `wp-config.php` هیچ ثابتی نمی‌خواند؛ این مقادیر «شکسته» در کد هاردکد شده‌اند و **باید** پیش از استقرار به ثابت/تنظیم منتقل شوند (در `TECH_DEBT.md` ثبت شده‌اند):

| متغیر پیشنهادی (wp-config) | محل فعلی | توضیح |
|---|---|---|
| `FALNIC_SMS_USERNAME` / `FALNIC_SMS_PASSWORD` / `FALNIC_SMS_BODY_ID` | `inc/sms.php` | اعتبارنامهٔ پنل پیامک (فعلاً `iranhp`/… در سورس!) |
| `FALNIC_BALE_HANDLER` | `inc/login.php` | نام تابع ارسال به بله |
| `FALNIC_SITE_URL` | تمام قالب‌ها | دامنهٔ اصلی برای ریدایرکت‌ها |
| `FALNIC_CAPTCHA_FONT` | `inc/captcha.php` | مسیر فونت TTF کپچا |

همچنین آدرس تصاویر استاتیک قالب بهتر است از `PATH_DIR_URL` (تعریف‌شده در `functions.php`) پیروی کنند.

## ۷. تست‌ها

- **وضعیت فعلی:** هیچ فریم‌ورک تست خودکار در ریپو وجود ندارد. تست‌ها دستی/اسموک هستند.
- دستورهای پیشنهادی برای بررسی سلامت کد:

```bash
# لینت PHP (همهٔ فایل‌ها)
find . -name "*.php" -not -path "./node_modules/*" -print0 | xargs -0 -n1 php -l

# بررسی سینتکس JS (در صورت نصب node)
node --check assets/js/main.js

# بررسی tag های ناقص HTML (در صورت نصب tidy) برای هر صفحهٔ کلیدی
```

- **سناریوهای تست دستی بحرانی** (هر بار پس از تغییر در auth):
  1. ورود/ثبت‌نام با OTP برای موبایل جدید و موبایل تکراری (با موک‌کردن `send_pattern_sms`/بله).
  2. ورود با رمز عبور و بازیابی رمز با OTP.
  3. ثبت نظر دوره (بررسی وضعیت «در انتظار تایید»).
  4. تکمیل درس → آپدیت درصد پیشرفت در سایدبار و داشبورد.
  5. تراکنش‌ها/دانلود رسید پس از ساخت یک رکورد دستی در `evented_transactions`.
  6. رندر همهٔ صفحات پنل با و بدون لاگین.

## ۸. سایر مستندات

| فایل | محتوا |
|---|---|
| `ARCHITECTURE.md` | معماری، جریان داده، ساختار DB (با دیاگرام Mermaid)، قراردادهای AJAX |
| `CHANGELOG.md` | تاریخچهٔ تغییرات (استاندارد Keep a Changelog) |
| `CONTRIBUTING.md` | استانداردهای تیم، قرارداد commit و فرایند PR |
| `AI.md` | قوانین سخت‌گیرانه برای مدل‌های AI هنگام ویرایش کدبیس |
| `ROADMAP.md` | نقشهٔ راه نسخه‌ها و ویژگی‌های برنامه‌ریزی‌شده |
| `TODO.md` | تسک‌های معلق، باگ‌های شناخته‌شده (چک‌لیست) |
| `TECH_DEBT.md` | بدهی فنی، کدهای نیازمند ریفکتور، تنگناهای عملکرد |

---

> ⚠️ هشدار استقرار: سه وابستگی حیاتی (جدول تراکنش، تابع بله، درگاه پرداخت) **خارج از این قالب** هستند؛ بدون آن‌ها جریان OTP و پرداخت در عمل کامل نیست.
