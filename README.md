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
| فرانت‌اند | فقط Vanilla JS (بدون jQuery/Owl/Plyr) | همهٔ اسکریپت‌ها در `assets/js/newhome/`؛ فقط `jalalidatepicker` (محلی) به‌عنوان vendor |
| فونت/آیکن | **Vazirmatn متغیر** (`assets/fonts/Vazirmatn-Variable.woff2`, OFL) + «دانا» به‌عنوان fallback؛ آیکن‌ها اسپرایت SVG محلی (`assets/icons/ee-icons.svg`, Material Symbols) | `assets/css/newhome/ee-fonts.css` — **هیچ درخواست خارجی** (`inc/no_external.php` بقیه را هم مسدود می‌کند) |
| پیامک OTP/اعلان | SOAP پنل پیامک (payamak-panel) | `inc/sms.php` — اعتبارنامه از **تنظیمات قالب → پیامک** یا ثابت‌های `EVENTED_SMS_*` |
| پیام‌رسان | ربات بله — تابع `evented_send_otp_with_bale()` | قالب یک wrapper محافظت‌شده دارد؛ ارسال واقعی به mu-plugin/افزونه (هم‌نام یا legacy با نام `falnic_send_otp_with_bale`) واگذار می‌شود |
| پایگاه‌دادهٔ تراکنش | جدول سفارشی `{wp}_evented_transactions` | ⚠️ سازندهٔ جدول در این قالب نیست؛ نصب‌های قدیمی باید جدول را تغییر نام دهند (migration) |
| تست | ندارد (در این مرحله) | بخش ۷ را ببینید |

## ۳. ساختار دایرکتوری‌ها

```
learndash-theme/                  (در سرور: wp-content/themes/<نام-پوشه> — کد از get_template_directory_uri() می‌خواند)
├── style.css / functions.php     هدر قالب + بوت‌استرپ (PATH_DIR* + require ها)
├── README.md / ARCHITECTURE.md / CHANGELOG.md / CONTRIBUTING.md / AI.md
├── ROADMAP.md / TODO.md / TECH_DEBT.md
├── front-page.php                صفحهٔ اصلی (بخش‌های داینامیک/استاتیک — رجوع: ARCHITECTURE §5)
├── single.php                    تک‌نوشته (پوستهٔ ee-*؛ مقاله + سایدبار + دیدگاه)
├── archive.php / home.php / search.php   آرشیو نوشته‌ها، برگهٔ نوشته‌ها، نتایج جستجو
├── comments.php                  فهرست دیدگاه‌ها + فرم دیدگاه (فارسی)
├── template-parts/               ee-head · ee-header · ee-footer · ee-sidebar · ee-archive-main · lms/* (enroll-card، curriculum، course-sidebar، lesson-list، course-grid، category-grid، courses-sidebar)
├── single-sfwd-courses.php       صفحهٔ دوره (پوستهٔ ee-*: سرفصل‌ها، آکاردئون‌ها، نظرات، سایدبار ثبت‌نام)
├── single-sfwd-lessons.php       صفحهٔ درس (کلیپ/پادکست/متن، آزمون، تکمیل درس، فهرست درس‌ها)
├── single-sfwd-quizzes.php       صفحهٔ آزمون (فکت‌های آزمون + بدنهٔ لرن‌دش + فهرست درس‌ها)
├── taxonomy-ld_course_category.php  آرشیو دستهٔ دوره (سربرگ دسته + گرید دوره‌ها + سوالات متداول)
├── archive-sfwd-courses.php      بایگانی همهٔ دوره‌ها (پوستهٔ ee-*)
├── author.php                    پروفایل مدرس  |  template-instructors.php  لیست اساتید
├── page-login.php                صفحهٔ ورود/OTP (HTML مستقل، بدون wp_head)
├── page-panel.php                برگهٔ /panel → پیشخوان پنل (روی پوستهٔ ee-*)
├── page.php / page-courses.php / page-courses-cat.php / 404.php   برگهٔ عمومی · فهرست دوره‌ها · دسته‌بندی‌ها · ۴۰۴ (همه با پوستهٔ ee-*)
├── index.php                     قالب بازگشتی عمومی (fallback) برای انواع پست بدون قالب اختصاصی
├── assets/
│   ├── assets_functions.php      enqueue دارایی‌های پنل کاربری (بقیه در functions.php)
│   ├── css/  single-post · archive-post · panel (محتوای پنل) · jalalidatepicker
│   ├── css/newhome/  ee-fonts (فونت/آیکن محلی) · ee-shell (پوستهٔ مشترک) · evented-home · ee-lms · ee-courses (+ نوار فیلتر) · ee-panel · ee-live-search · ee-notify
│   ├── js/newhome/evented-home.js  کشوی منوی موبایل · زیرمنوها · جستجوی موبایل · تب‌های مقالات · اسلایدر هیرو · کپی لینک
│   ├── js/newhome/ee-lms.js        آکاردئون · گروه درس‌ها · دیدگاه/امتیاز · تکمیل درس · علاقه‌مندی
│   ├── js/newhome/ee-live-search.js  جستجوی زندهٔ هدر  |  ee-notify.js  زنگولهٔ اعلان‌ها  |  ee-panel.js  رفتارهای پنل
│   ├── icons/ee-icons.svg        اسپرایت SVG آیکن‌ها (با `ee_icon('name')` استفاده می‌شود)
│   ├── fonts/  Vazirmatn-Variable.woff2 · evented-edu-font.woff2 (دانا)
│   └── img/  front-page (۲۳) · single-page (۸) · panel (۲)
├── inc/
│   ├── includes.php              فقط require کردن ماژول‌های پایین
│   ├── icons.php                 ee_icon() + تزریق اسپرایت SVG در body
│   ├── seo.php                   title-tag، متا/OG/Twitter، JSON-LD (Organization/WebSite/Course/Article/Breadcrumb/Collection)؛ با Yoast/RankMath خاموش می‌شود
│   ├── no_external.php           حذف اموجی/oEmbed/dns-prefetch و هر استایل/اسکریپت خارجی
│   ├── theme_options_store.php   اسکیمای تنظیمات قالب + evented_opt() + لینک شبکه‌ها/کانال‌ها
│   ├── live_search.php           REST جستجوی زنده (/evented/v1/search)
│   ├── course_filters.php        فیلتر/مرتب‌سازی سمت‌سرور آرشیو دوره‌ها (level/price/status/instructor/orderby)
│   ├── reviews.php               امتیاز واقعی دوره‌ها (کش متا، ستاره‌ها، AggregateRating/Review، سازگار با Yoast)
│   ├── resume.php                ادامهٔ یادگیری: آخرین درس، چیپ هدر، کارت پنل، یادآور کاربران غیرفعال (کرون)
│   ├── pwa.php                   PWA: manifest، سرویس‌ورکر (/ee-sw.js)، صفحهٔ آفلاین، دکمهٔ نصب
│   ├── notifications.php         اعلان‌ها: جدول {wp}_evented_notifications، REST، رویدادها، پیامک اختیاری
│   ├── template_helpers.php      هلپرهای پوستهٔ ee-* (شمسی، بازدید، اشتراک، مرتبط‌ها) + هلپرهای LMS
│   ├── navigation.php            منوی استاتیک هدر/فوتر/کشوی موبایل (کش هفتگی) + فیلتر بخش جستجو + تب‌های مقالات خانه
│   ├── login.php                 کلاس FalnicAuthHandler (OTP/ورود) + captcha_verify
│   ├── sms.php                   send_pattern_sms (SOAP)
│   ├── meta_functions.php        متاباکس دوره/دسته/کاربر + فیلتر آواتار
│   ├── theme_options.php         MIME ها، گارد subscriber، is_current_path()
│   ├── theme_settings.php        صفحهٔ «تنظیمات قالب» تب‌دار: اسلایدر، عمومی/تماس، شبکه‌ها، متن‌های خانه، پیامک، سئو، اعلان‌ها
│   ├── ajax_functions.php        ۵ اکشن AJAX (نظر/تکمیل درس/علاقه‌مندی/پروفایل/تنظیمات)
│   └── ajax_functions.php
├── template-parts/panel/         shell-open.php / shell-close.php (سایدبار پنل + مودال خروج روی هدر/فوتر ee-*)
└── panel/                        تمپلیت‌های پنل (Template Name: Panel - …)
    ├── dashboard.php و my-courses.php و certificates.php
    └── wishlist.php و payments.php و profile.php و settings.php
```

## ۴. نقشهٔ سریع صفحات ↔ تمپلیت

| مسیر | قالب | نکته |
|---|---|---|
| `/` | `front-page.php` | لندینگ |
| آرشیو نوع دوره (is_post_type_archive) | `archive-sfwd-courses.php` | فهرست همهٔ دوره‌ها |
| `/ld_course_category/<slug>/` | `taxonomy-ld_course_category.php` | آرشیو دستهٔ دوره + سوالات متداول دسته |
| `/courses/<slug>/` | `single-sfwd-courses.php` | صفحهٔ دوره |
| `/lessons/<slug>/` | `single-sfwd-lessons.php` | صفحهٔ درس (کلیپ/پادکست/متن + فهرست درس‌ها) |
| آزمون (`sfwd-quizzes`) | `single-sfwd-quizzes.php` | صفحهٔ آزمون (فکت‌ها + بدنهٔ لرن‌دش) |
| برگهٔ «دوره‌ها» | `page-courses.php` | گرید همهٔ دوره‌ها با صفحه‌بندی |
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


## ۵.۳ منوی هدر (استاتیک + کش هفتگی)

منو در پیشخوان ساخته نمی‌شود؛ آیتم‌ها ثابت‌اند و فقط **آدرس/زیرمنو**ی هرکدام از محتوای سایت خوانده و **یک هفته** کش می‌شود (`inc/navigation.php`):

| آیتم | آدرس از کجا می‌آید | زیرمنو |
|---|---|---|
| صفحه اصلی | `home_url()` | — |
| مقالات | برگهٔ «نوشته‌ها» (تنظیمات ← خواندن) یا برگهٔ `blog` | دسته‌بندی‌های وبلاگ (پرمحتواترین‌ها) |
| کتابخانه / گالری / ویدیو / دانلودها / پادکست | بایگانی پست‌تایپ افزونه (اسلاگ‌های رایج مثل `library`, `gallery`, `video`, `download(s)`, `podcast` امتحان می‌شوند) → در نبود آن، برگهٔ هم‌نام → در نهایت مسیر پیش‌فرض | ترم‌های اولین تاکسونومی آن پست‌تایپ |
| دوره‌ها | بایگانی `sfwd-courses` یا برگهٔ `courses` | دسته‌های `ld_course_category` |
| ویژه رمضان / مسابقات / معرفی سایت / ارتباط با ما | برگه با اسلاگ `ramadan` / `contests` / `about` / `contact` (اسلاگ‌های جایگزین هم پشتیبانی می‌شوند) | — |

- اگر افزونه اسلاگ متفاوتی برای پست‌تایپ دارد: `add_filter('evented_nav_post_type_candidates', fn($m) => array_merge($m, ['library' => ['my_books']]))`.
- بعد از فعال‌کردن افزونه یا ساختن برگه‌ها، کش خودکار باطل می‌شود؛ در صورت نیاز از «بازسازی منو و کش قالب» در نوار مدیریت استفاده کنید.
- فیلتر جستجوی هدر (`?post_type=`) از همین آیتم‌ها ساخته می‌شود؛ حالت «همه‌جا» فقط همین پست‌تایپ‌ها + برگه‌ها را می‌گردد.
- ورود مدیران: `/wp-admin` و `wp-login.php?admin=1` به فرم استاندارد وردپرس می‌روند؛ `/login` مخصوص کاربران (موبایل + OTP) است.

## ۶. پیکربندی

همهٔ تنظیمات از **نمایش → تنظیمات قالب** خوانده می‌شوند (آپشن `evented_theme_options`، دسترسی با `evented_opt('key')`):

| تب | کلیدهای مهم |
|---|---|
| عمومی/تماس | `site_tagline_fa`, `license_text`, `phone`, `phone_hours`, `email`, `address`, `copyright`, `terms_url` |
| شبکه‌ها | `channel_id` (ایتا/بله/روبیکا/سروش) یا URL کامل هرکدام، `telegram_url`, `instagram_url`, `youtube_url`, `linkedin_url` |
| صفحهٔ اصلی | عنوان/زیرعنوان بخش‌ها، `tabs_count`, `tabs_per` |
| پیامک | `sms_username`, `sms_password`, `sms_body_id`, `sms_notify_body_id`, `otp_ttl`, `otp_rate` |
| سئو | `seo_enabled`, `seo_home_title`, `seo_home_desc`, `seo_default_img`, `org_name`, `org_logo` |
| اعلان‌ها | `notify_enabled`, `notify_new_lesson`, `notify_comment`, `notify_sms`, `notify_keep_days` + فرم «ارسال اعلان دستی» |
| یادآور ادامهٔ دوره (تب اعلان‌ها) | `resume_reminder_enabled`, `resume_reminder_days`, `resume_reminder_sms` — کرون روزانهٔ `evented_resume_reminder` |
| برنامهٔ وب (PWA) | `pwa_enabled`, `pwa_name`, `pwa_short_name`, `pwa_theme_color`, `pwa_bg_color`, `pwa_icon_192/512`, `pwa_install_btn`, `pwa_cache_ver` |

برای محیط‌های حساس می‌توان اعتبارنامهٔ پیامک را در `wp-config.php` گذاشت؛ ثابت‌ها بر تنظیمات اولویت دارند:

```php
define('EVENTED_SMS_USERNAME', '…');
define('EVENTED_SMS_PASSWORD', '…');
define('EVENTED_SMS_BODY_ID', 12345);
```

**سیاست «بدون درخواست خارجی»:** فونت‌ها، آیکن‌ها و همهٔ اسکریپت‌ها محلی‌اند و `inc/no_external.php` هر استایل/اسکریپت ثبت‌شده از دامنهٔ دیگر را روی فرانت‌اند حذف می‌کند (استثنا با فیلتر `evented_allowed_external_hosts`).

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
