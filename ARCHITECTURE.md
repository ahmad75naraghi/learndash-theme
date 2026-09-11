# ARCHITECTURE — معماری قالب «evented-edu»

این سند معماری نرم‌افزار، الگوهای استفاده‌شده، جریان داده، ساختار پایگاه‌داده و قراردادهای AJAX را شرح می‌دهد. همهٔ دیاگرام‌ها با **سینتکس Mermaid.js** نوشته شده‌اند تا مستقیماً توسط انسان و مدل‌های AI قابل درک باشند.

---

## ۱. نمای کلی لایه‌ها

| لایه | مسیر | مسئولیت |
|---|---|---|
| **Presentation (Templates)** | ریشهٔ قالب + `panel/` | رندر HTML/RTL صفحات |
| **Logic (inc)** | `inc/*.php` | احراز هویت، پیامک، متاباکس‌ها، AJAX، تنظیمات |
| **Assets** | `assets/` + `assets/assets_functions.php` | CSS/JS/فونت و بارگذاری شرطی |
| **Integration** | خارج از قالب | LearnDash، درگاه پرداخت، جدول تراکنش، سرویس پیامک/بله |

الگوی کلی: **WP Theme + MVC-lite** — قالب‌ها = View، `inc/*` = Controller/Service، وردپرس/لرن‌دش = Model/DB. هیچ فریم‌ورک یا autoloader/PHP مودرن (namespace) استفاده نشده؛ کد به‌صورت توابع `evented_*` و کلاس‌های ساده نوشته شده است (پس از برندینگ، وارث نام‌های `falnic_*`).

## ۲. بوت‌استرپ و ترتیب بارگذاری

```mermaid
flowchart TD
    A["functions.php<br/>(define PATH_DIR / PATH_DIR_URL)"] --> B["assets/assets_functions.php<br/>hook: wp_enqueue_scripts"]
    A --> C["inc/includes.php"]
    C --> D["inc/login.php — EventedAuthHandler<br/>7 AJAX actions + login_url/logout_redirect filters"]
    C --> E["inc/sms.php — send_pattern_sms (SOAP)"]
    C --> F["inc/meta_functions.php — metaboxes + avatar filter"]
    C --> G["inc/theme_options.php — roles, redirects, is_current_path"]
    C --> H["inc/ajax_functions.php — 5 AJAX (review/progress/wishlist/profile/settings)"]
    D --> I["(external Bale provider) evented_send_otp_with_bale()<br/>optional mu-plugin; legacy falnic_… also honored"]
    E --> J["(external) Payamak SOAP API"]
```

نکات:
- `EventedAuthHandler` با `add_action('init', fn => new EventedAuthHandler())` نمونه‌سازی می‌شود.
- `inc/captcha.php` و `captcha_verify()` (در login.php) فعلاً **به هیچ فرمی متصل نیستند** (کد مردهٔ آماده).
- هوک‌های ریدایرکت: `login_url` → `/login/` و `logout_redirect` → خانه (هر دو در `inc/login.php`)؛ `login_redirect` برای نقش subscriber → `/panel` (در `inc/theme_options.php`).

## ۳. ساختار پایگاه‌داده (Data Structures)

### ۳.۱ پست‌تایپ‌ها/تاکسونومی‌ها

```mermaid
erDiagram
    COURSE ||--o{ LESSON : "contains (ld)"
    COURSE ||--o{ SECTION : "organizes (ld 3.0)"
    COURSE }o--o{ TERM : "ld_course_category / ld_course_tag"
    COURSE ||--o{ COMMENT : "course reviews"
    COMMENT ||--|| COMMENT_META : "review_rating 1..5"
    USER ||--o{ USER_META : "profile/avatar/fav_courses"
    USER ||--o{ TRANSACTION : "wp_evented_transactions"
    TRANSACTION }o--|| COURSE : "course_id"

    COURSE { int ID PK "post_type=sfwd-courses" }
    LESSON { int ID PK "sfwd-lessons" }
    SECTION { int ID PK "sfwd-courses (section rows, ld 3.0)" }
    TERM { int term_id PK "ld_course_category" }
    TRANSACTION { int user_id "buyer" int course_id varchar status varchar tracking_code decimal amount datetime created_at }
    COMMENT_META { varchar meta_key "review_rating" }
    USER_META { varchar meta_key "fav_courses, first_name_fa, ..." }
```

### ۳.۲ جدول سفارشی `{wp}_evented_transactions`

قالب **فقط از این جدول می‌خواند** (صفحات `payments` و `dashboard`)؛ سازنده/درگاه خارج از قالب است.
ستون‌های استفاده‌شده در کد (مستخرج از `panel/payments.php` و `panel/dashboard.php`): `user_id`, `course_id`, `status` (مقادیر غیر از `success` = ناموفق), `tracking_code`, `amount`, `created_at`.

DDL پیشنهادی (سازگار با کد — در صورت نبودِ جدول در نصب):

```sql
CREATE TABLE IF NOT EXISTS {wp}_evented_transactions (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
    course_id     BIGINT UNSIGNED NOT NULL DEFAULT 0,
    status        VARCHAR(50)     NOT NULL DEFAULT '',
    tracking_code VARCHAR(190)    NOT NULL DEFAULT '',
    amount        DECIMAL(15,0)   NOT NULL DEFAULT 0,
    created_at    DATETIME        NOT NULL,
    PRIMARY KEY (id),
    KEY idx_user (user_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

> ⚠️ در `panel/certificates.php` گارد مهمان به‌اشتباه به `/panel/payments.php` ریدایرکت می‌کند (رجوع: `TODO.md`).

### ۳.۳ کلیدهای متای دوره (`sfwd-courses`)

| کلید (post_meta) | نوع | معنا |
|---|---|---|
| `_course_subtitle` | textarea | زیرعنوان/خلاصهٔ Hero |
| `_total_duration` | string | مدت کل (نمایشی) |
| `_course_level` | select | مقدماتی/متوسط/پیشرفته |
| `_course_status` | select | در حال برگزاری/تمام‌شده |
| `_course_suffering` | `"0"/"1"` | فعال‌سازی باکس «آپلود تمرین» (فقط UI) |
| `_course_outcomes` | array | «آنچه می‌آموزید» (ریپیتر) |
| `_course_faq` | array | FAQ دوره `[{question, answer}]` |
| `_webinar_active` | `yes/no` | نمایش بخش وبینار |
| `_webinar_date` / `_webinar_time` / `_webinar_location` | string | جزئیات وبینار |
| `_webinar_map_iframe` | url | src نقشهٔ embed |
| `_webinar_gmap_link` / `_webinar_neshan_link` | url | لینک‌های مسیریابی |

### ۳.۴ کلیدهای لرن‌دش (خوانده‌شده در قالب)

| کلید/تابع | کاربرد |
|---|---|
| `_sfwd-courses` (متای آرایه‌ای) | `sfwd-courses_course_price`، `sfwd-courses_course_price_type`، `sfwd-courses_course_materials*` |
| `price_type` مقادیر | `open/free` → «رایگان»؛ بقیه → قیمت‌دار |
| `_learndash_course_grid_duration` (ثانیه) | مدت هر درس (کلید افزونهٔ Course Grid) |
| `learndash_get_setting(id,'sample_lesson')` | `on` = درس پیش‌نمایش (قفل ندارد) |
| `learndash_get_setting(id,'lesson_video_url')` | ویدیوی درس (با fallback به `_sfwd-lessons['sfwd-lessons_lesson_video_url']`) |
| `_lesson_audio` / `_lesson_audio_url` / `sfwd-lessons_lesson_audio_url` | پادکست درس (هرکدام پر باشد؛ چند مقدار = چند فایل) |
| `_lesson_attachments` (آرایهٔ آیدی پیوست) | فایل‌های قابل دانلود درس |
| `learndash_get_setting(id,'certificate')` | گواهینامهٔ دوره |
| `learndash_30_get_course_sections(id)` | سکشن‌ها (فصل‌ها) |
| `_sfwd-course_progress` (user-meta) | درس‌های تکمیل‌شدهٔ کاربر (خوانده‌شده یک‌جا در `evented_course_steps`) |

### ۳.۵ Term-Meta دسته (`ld_course_category`)

| کلید | محل نوشتن | معنا |
|---|---|---|
| `ld_cat_image_id` | پیشخوان دسته | آیدی تصویر دسته |
| `short_discription` | پیشخوان دسته (wp_editor) | متن هدر دسته |
| `ld_category_faqs` | پیشخوان دسته | `[{question, answer}]` (پایین آرشیو) |

### ۳.۶ User-Meta

| کلید | معنا |
|---|---|
| `fav_courses` | CSV از course_id (علاقه‌مندی) |
| `first_name_fa/last_name_fa/first_name_en/last_name_en` | نام برای پروفایل/گواهینامه |
| `gender`, `birth_date` | جنسیت، تولد (جلالی) |
| `billing_phone` | موبایل (استاندارد ووکامرس) |
| `custom_user_avatar` | آواتار سفارشی (جایگزین Gravatar با فیلتر) |
| `youtube/linkedin/instagram/about_teacher` | پروفایل مدرس |
| `teacher_jobs` / `teacher_education` | ریپیتر سوابق شغلی/تحصیلی |
| `instructor_user_role` | نقش نمایشی مدرس |
| `instructor_rating_average` | میانگین امتیاز (فقط خوانده می‌شود) |

### ۳.۷ Session و Transient (جریان OTP)

| کلید | معنا |
|---|---|
| سشن: `fl_otp` / `fl_mobile` / `fl_otp_time` / `fl_otp_purpose` / `fl_otp_verified` / `fl_otp_verified_for` | وضعیت OTP |
| سشن: `captcha_code` | (برای captcha — غیرفعال) |
| ترنزینت: `otp_limit_{mobile}` (۶۰ ثانیه) | محدودیت نرخ ارسال کد |

---

## ۴. جریان‌های اصلی (Data Flow)

### ۴.۱ احراز هویت با OTP

```mermaid
sequenceDiagram
    autonumber
    actor U as "User (/login)"
    participant P as "page-login.php (JS)"
    participant A as "admin-ajax.php"
    participant S as "inc/login.php"
    participant X as "External SMS / Bale"

    U->>P: enters mobile 09XXXXXXXXX
    P->>A: POST evented_check_mobile_and_send_otp (nonce)
    A->>S: handle_check_mobile_and_send_otp
    alt user exists (login = mobile)
        S-->>P: {status:login} -> show password form
    else new user
        S->>X: send_pattern_sms + evented_send_otp_with_bale (OTP 5-digit)
        X-->>S: ok
        S-->>P: {status:register} -> show OTP step
    end

    U->>P: enters OTP
    P->>A: POST evented_verify_otp
    A->>S: handle_verify_otp (compare session fl_otp)
    alt purpose = reset_password
        S-->>P: {result:reset_password} -> new-password step
    else new user (not found)
        S-->>P: {is_new_user:true} -> name step
    else existing user (login by OTP)
        S-->>P: auto login + {result:login, nonce}
    end
```

مسیرهای تکمیلی (خلاصه): ثبت‌نام نهایی = `save_user_register_name` (ساخت کاربر: login=موبایل، ایمیل `{mobile}@evented-edu.user`) سپس `evented_register_user` (ست رمز + لاگین). ورود با رمز = `evented_login_user`. بازیابی رمز = `evented_send_otp(purpose=reset_password)` → `evented_verify_otp` → `evented_reset_password`.

> ✅ این دو «خط طلایی» رفع شده‌اند: `save_user_register_name` فقط با OTP تأییدشدهٔ همان شماره کاربر می‌سازد و `handle_register_user` با `$user->ID` (نه خروجی void تابع `wp_set_password`) لاگین خودکار را انجام می‌دهد.

### ۴.۲ تکمیل درس (دور زدن قفل ویدیو/تایمر)

```mermaid
sequenceDiagram
    autonumber
    participant U as "User (enrolled)"
    participant M as "lesson modal (single-courses.js)"
    participant A as "admin-ajax.php"
    participant S as "inc/ajax_functions.php"
    participant LD as "LearnDash"

    U->>M: click "next video" / "finish lesson"
    M->>A: POST custom_mark_lesson_complete (security=mark_complete_nonce_lessonId)
    A->>S: handle_custom_mark_lesson_complete
    S->>LD: learndash_video_complete_for_step(..) / fallback meta
    S->>LD: update_user_meta(learndash_timer_complete_lessonId)
    S->>LD: learndash_process_mark_complete(..)
    alt still not saved
        S->>S: force write _sfwd-course_progress[courseId][lessons][id]=1
        S->>LD: learndash_update_user_activity(..)
    end
    S->>LD: learndash_course_progress(array=true)
    S-->>M: progress data -> updateProgressUI(bar %)
```

### ۴.۳ نظر / علاقه‌مندی (کوتاه)

```mermaid
flowchart LR
    U["User"] -->|submit_course_review nonce=course_review_nonce| R["comment + review_rating<br/>status=0 (moderation)"]
    U -->|toggle_course_wishlist nonce=wishlist_nonce| W["user_meta fav_courses CSV<br/>added/removed"]
```

---

## ۵. نگاشت قالب‌ها (Template Map)

```mermaid
flowchart TD
    FRONT["is_front_page"] --> F["front-page.php"]
    SING["is_singular sfwd-courses"] --> SC["single-sfwd-courses.php"]
    SLESS["is_singular sfwd-lessons"] --> SL["single-sfwd-lessons.php"]
    ARCH["is_post_type_archive sfwd-courses"] --> AX["archive-sfwd-courses.php"]
    AX -->|get_template_part| TAX["taxonomy-ld_course_category.php"]
    TAXT["is_tax ld_course_category"] --> TAX
    POST["is_singular post"] --> SP["single.php"]
    BLOG["is_home (برگهٔ نوشته‌ها)"] --> HM["home.php"]
    PARCH["is_category / is_tag / is_date"] --> AR["archive.php"]
    SEARCH["is_search"] --> SE["search.php"]
    HM --> AM["template-parts/ee-archive-main.php"]
    AR --> AM
    SE --> AM
    AM --> SB["template-parts/ee-sidebar.php"]
    SP --> SB
    SC --> LMS["template-parts/lms/*"]
    SL --> LMS
    AUTH["is_author"] --> AU["author.php"]
    LOGIN["page slug = login"] --> PL["page-login.php"]
    PANEL["page slug = panel or child"] --> PP["page-panel.php + panel templates"]
    OTHER["other pages"] --> PG["page.php"]
    FALLBACK["else"] --> IDX["index.php (fallback)"]
```

### ۵.۱ پوستهٔ مشترک «ee-*» (template-parts)

قالب‌های صفحهٔ اصلی، تک‌نوشته، آرشیو/برگهٔ نوشته‌ها، جستجو، تک‌دوره و تک‌درس سند HTML کامل
را خودشان چاپ می‌کنند (`get_header()`/`get_footer()` قدیمی را صدا نمی‌زنند) و از قطعه‌های
مشترک زیر استفاده می‌کنند:

| قطعه | نقش |
| --- | --- |
| `template-parts/ee-head.php` | از `<!DOCTYPE html>` تا `wp_head()` و باز شدن `<body>`؛ آرگومان `ee_body_class`. |
| `template-parts/ee-header.php` | نوار ابزار بالا + هدر چسبان + منو + جستجوی موبایل؛ آرگومان `ee_active`. در صفحهٔ اصلی لینک‌ها لنگر (`#ee-…`) و در سایر صفحات آدرس کامل‌اند. |
| `template-parts/ee-footer.php` | فوتر + نوار پایین موبایل + `wp_footer()` و بستن سند. |
| `template-parts/ee-sidebar.php` | ویجت‌های سایدبار نوشته‌ها: جستجو، اشتراک‌گذاری، آخرین مطالب، ویژه‌ها، پربازدید، دوره‌ها، دنبال‌کردن کانال‌ها. |
| `template-parts/ee-archive-main.php` | سربرگ بایگانی + چیپ دسته‌بندی + شبکهٔ کارت‌ها + صفحه‌بندی (مشترک بین archive/home/search). |
| `template-parts/lms/curriculum.php` | سرفصل‌های دوره: سربرگ فصل‌ها + ردیف هر درس (شماره، عنوان، مدت، آزمون، پیش‌نمایش، قفل، وضعیت تکمیل). آرگومان‌ها: `ee_steps`, `ee_course_id`. |
| `template-parts/lms/enroll-card.php` | کارت ثبت‌نام/پیشرفت: قیمت، نوار پیشرفت، فکت‌ها، منابع دوره، دکمهٔ شروع/ادامه، علاقه‌مندی، `learndash_payment_buttons()`. آرگومان‌ها: `ee_course_id`, `ee_steps`, `ee_pricing`, `ee_progress`. |
| `template-parts/lms/course-sidebar.php` | سایدبار صفحهٔ دوره: کارت ثبت‌نام + جستجو + اشتراک‌گذاری + سایر دوره‌ها + آخرین دوره‌ها + دنبال‌کردن. |
| `template-parts/lms/lesson-list.php` | سایدبار صفحهٔ درس: نوار پیشرفت، فهرست درس‌ها گروه‌بندی‌شده بر اساس فصل (جمع‌شونده + «باز کردن همه») و لینک بازگشت به دوره. آرگومان‌ها: `ee_steps`, `ee_course_id`, `ee_current_id`, `ee_progress`. |

هلپرهای این پوسته در `inc/template_helpers.php` هستند: `evented_is_ee_view()`،
`evented_current_url()`، `evented_gregorian_to_jalali()`/`evented_format_jalali()` (تبدیل شمسی
فقط وقتی افزونهٔ شمسی‌ساز فعال نباشد)، `evented_post_date()`/`evented_post_time()`،
`evented_reading_time()`، `evented_get_post_views()`/`evented_track_post_view()`
(متای `evented_post_views`)، `evented_share_links()`/`evented_channel_links()` (فیلترپذیر) و
`evented_related_posts()`. دیدگاه‌ها از `comments.php` قالب با لیبل‌های فارسی و
`comment_form()` استایل‌خورده نمایش داده می‌شوند.

### ۵.۲ صفحهٔ دوره و درس (LMS)

`single-sfwd-courses.php` و `single-sfwd-lessons.php` هر دو از پوستهٔ مشترک `ee-*` استفاده
می‌کنند و دادهٔ مشترک را یک‌بار محاسبه و با `get_template_part($slug, $name, $args)` به قطعه‌ها
می‌دهند (بدون کوئری تکراری).

**قوانین دسترسی و وضعیت**

- درس باز است اگر `sfwd_lms_has_access($course_id)` **یا** `learndash_get_setting($lesson,'sample_lesson') === 'on'`؛ در `evented_course_steps()` به‌صورت `is_unlocked` خروجی داده می‌شود.
- وضعیت هر گام با `evented_step_state()` تعیین می‌شود: `is-complete` (تکمیل‌شده) ← `is-active` (گام جاری) ← `is-locked` (بدون دسترسی) ← `is-sample` (پیش‌نمایش رایگان) ← `is-open`.
- درس‌های تکمیل‌شده از `user_meta['_sfwd-course_progress'][$course_id]['lessons']` یک‌جا خوانده می‌شوند (یک کوئری به‌جای N کوئری).
- صفحهٔ درس، دورهٔ مادر را با `learndash_course_get_course_by_step_id()` و در غیاب آن `learndash_get_course_id()` پیدا می‌کند؛ درس قفل‌شده به‌جای رسانه، جعبهٔ «ثبت‌نام در دوره» نشان می‌دهد.
- نظرات دوره: فقط کامنت‌های تاییدشده (`status=approve`) + فرم امتیاز ستاره‌ای برای کاربر وارد‌شده؛ دیدگاه جدید با `comment_approved=0` و متای `review_rating` ثبت می‌شود.
- سایدبار دوره: کاربر دارای دسترسی → `learndash_course_progress` + دکمهٔ شروع/ادامه (`learndash_course_get_resume_step_url`)؛ بدون دسترسی → قیمت + `learndash_payment_buttons()` (یا لینک ورود وقتی `price_type === 'closed'` نباشد).

**تصمیم‌های پرفورمنس**

| مورد | نسخهٔ قدیمی | نسخهٔ فعلی |
| --- | --- | --- |
| ویدیوی درس‌ها در صفحهٔ دوره | برای هر درس `wp_oembed_get()` + یک مودال در DOM (N درخواست شبکه + N مودال) | حذف؛ ردیف سرفصل به صفحهٔ درس لینک می‌شود |
| پخش‌کننده | Plyr (polyfill ~۲۰۰KB) روی همهٔ درس‌ها | `evented_media_player()`: فایل مستقیم → `<video>/<audio preload="none">`، در غیر این صورت `<iframe loading="lazy">`؛ بدون oEmbed |
| دادهٔ درس‌ها | چند بار حلقه روی `learndash_get_course_lessons_list()` | `evented_course_steps()` با کش `static` در همان درخواست |
| دوره‌های مرتبط/آخرین | کوئری داخل قالب | `evented_related_courses()` / `evented_latest_courses()` با `no_found_rows` و `ignore_sticky_posts` |

**هلپرهای LMS در `inc/template_helpers.php`**

`evented_format_duration()`، `evented_course_steps()` (id/title/permalink/duration/is_sample/
is_unlocked/is_completed/section_id/section_title/quizzes/index)، `evented_course_progress()`،
`evented_course_pricing()` (price_type/price/is_free/has_access)، `evented_lesson_media()`
(ویدیو/پوستر/صوت/پیوست‌ها)، `evented_lesson_quizzes()`، `evented_adjacent_steps()` (قبلی/بعدی)،
`evented_step_state()`، `evented_related_courses()` و `evented_latest_courses()`.

## ۶. بارگذاری Assets (Enqueue)

```mermaid
flowchart TD
    START["theme_enqueue: style.css + owl + main.js"] --> C1{"is_page('home') یا is_front_page?"}
    C1 -- yes --> FP["front-page.css + js (خانهٔ قدیمی)"]
    C1 -- no --> C2{"archive/tax courses?"}
    C2 -- yes --> AC["archive-courses.css"]
    C2 -- no --> C3{"is_author?"}
    C3 -- yes --> AU["author.css + author.js"]
    C3 -- no --> C4{"is_singular course یا lesson?"}
    C4 -- yes --> CS["— (بدون asset؛ قالب جدید از ee-lms استفاده می‌کند)"]
    C4 -- no --> C7{"page?"}
    C7 -- yes --> SGP["single-page.css + archive-product.css MISSING"]
    C7 -- no --> END["+ panel assets if is page under 'panel'"]

    START2["functions.php @priority 20"] --> EE{"evented_is_ee_view()?"}
    EE -- no --> SKIP["—"]
    EE -- yes --> SHELL["ee-shell.css + ee-home-js + وزیرمتن + Material Symbols"]
    SHELL --> B1{"is_front_page?"}
    B1 -- yes --> EEH["evented-home.css"]
    B1 -- no --> B2{"is_singular post?"}
    B2 -- yes --> SPC["single-post.css"]
    B2 -- no --> B4{"is_singular sfwd-courses یا sfwd-lessons?"}
    B4 -- yes --> LMSC["ee-lms.css + ee-lms.js + localize eeLms.ajax_url"]
    B4 -- no --> B3{"is_home / is_archive / is_search?"}
    B3 -- yes --> APC["archive-post.css"]
```

- **پوستهٔ ee-***: `functions.php` با اولویت ۲۰ و شرط `evented_is_ee_view()` بارگذاری می‌کند:
  فونت وزیرمتن + Material Symbols + `assets/css/newhome/ee-shell.css` (توکن‌ها، هدر/فوتر،
  نوار موبایل، ویجت‌های سایدبار) + `assets/js/newhome/evented-home.js` (منوی موبایل،
  اسلایدر هیرو، کپی لینک اشتراک). سپس بسته به صفحه یکی از `evented-home.css`،
  `single-post.css` یا `archive-post.css` (هر سه با وابستگی به `ee-shell`).
- `evented-home.css` فقط بخش‌های اختصاصی صفحهٔ اصلی را دارد (hero/quick/courses/steps/articles/instructors)؛
  بخش مشترک به `ee-shell.css` منتقل شده و هیچ سلکتور مشترکی بین دو فایل نیست.
- `is_home()` دیگر در شاخهٔ `front-page.css` نیست؛ برگهٔ نوشته‌ها از قالب آرشیو جدید استفاده می‌کند.
- **دوره/درس**: `evented_is_ee_view()` شامل `is_singular(array('sfwd-courses','sfwd-lessons'))` شد؛
  `functions.php` فایل‌های `assets/css/newhome/ee-lms.css` + `assets/js/newhome/ee-lms.js` را
  بارگذاری می‌کند و `eeLms.ajax_url` را لوکال می‌کند. شاخهٔ قدیمی `is_singular('sfwd-courses')`
  در `assets/assets_functions.php` (Plyr + `single-courses.css/js`) خالی شد تا طراحی جدید
  دوبار استایل نگیرد؛ `single-courses.*` و `plyr.*` دیگر در هیچ صفحه‌ای بارگذاری نمی‌شوند.
- `ee-lms.js` بدون jQuery است و سه قرارداد AJAX موجود را مصرف می‌کند: `submit_course_review`،
  `custom_mark_lesson_complete` (پاسخ = خروجی `learndash_course_progress`؛ نوارهای پیشرفت و
  شمارندهٔ درس‌ها بدون رفرش به‌روز می‌شوند) و `toggle_course_wishlist`.
- `main.js` لوکال‌سازی: `ajax_object = {ajax_url, nonce}` — nonce مربوط به `notification_nonce` است و استفاده نمی‌شود؛ اسکریپت‌های واقعی nonce را از `data-nonce` می‌خوانند.
- پنل: `panel.css` + `jalalidatepicker.min.js` + `panel.js` وقتی برگه، خودِ `panel` یا زیرمجموعهٔ آن باشد.
- کتابخانه‌ها: Owl Carousel (سراسری)، Plyr (دیگر enqueue نمی‌شود)، Jalali Date Picker (پنل)، PhotoSwipe (enqueue نشده — بدون استفاده)، Font Awesome (یک آیکن در `author.php` بدون لودر!).

## ۷. قراردادهای AJAX (Inventory)

همهٔ درخواست‌ها به `admin-ajax.php` می‌روند. اسامی اکشن، nonce و سمت دسترسی:

| اکشن | nonce | دسترسی | سمت |
|---|---|---|---|
| `evented_check_mobile_and_send_otp` | `evented_nonce` | مهمان+کاربر | `login.php` |
| `evented_send_otp` | `evented_nonce` | مهمان+کاربر | `login.php` |
| `evented_verify_otp` | `evented_nonce` | مهمان+کاربر | `login.php` |
| `save_user_register_name` (بدون پیشوند) | `evented_nonce` | مهمان+کاربر | `login.php` |
| `evented_register_user` | `evented_nonce` | مهمان+کاربر | `login.php` |
| `evented_login_user` | `evented_nonce` | مهمان+کاربر | `login.php` |
| `evented_reset_password` | `evented_nonce` | مهمان+کاربر | `login.php` |
| `submit_course_review` | `course_review_nonce` | مهمان+کاربر (لاگین الزامی داخل هندلر) | `ajax_functions.php` |
| `custom_mark_lesson_complete` | `mark_complete_nonce_{lesson_id}` | فقط کاربر | `ajax_functions.php` |
| `toggle_course_wishlist` | `wishlist_nonce` | فقط کاربر | `ajax_functions.php` |
| `save_user_profile` | `profile_nonce_action` | فقط کاربر | `ajax_functions.php` |
| `save_account_settings` | `settings_nonce_action` | فقط کاربر | `ajax_functions.php` |

## ۸. الگوهای طراحی و قراردادهای موجود

- **Hook-driven**: همهٔ منطق از طریق `add_action/add_filter` به وردپرس وصل می‌شود؛ هیچ routing دستی‌ای نیست.
- **Template Name**: صفحات پنل با هدر `Template Name: Panel - …` (وردپرس 4.7+ تمپلیت‌های زیرپوشهٔ سطح اول را خودکار پیدا می‌کند).
- **توابع کمکی متمرکز** در برخی صفحات (مثل helpers قیمت/تصویر در `panel/my-courses.php`) — ⚠️ تکرار شده در چند فایل (رجوع: `TECH_DEBT.md`).
- **خروجی RTL فارسی**: همهٔ قالب‌ها `dir="rtl"`؛ اسکریپت‌های Owl با `rtl: true`.
- **امنیت پایه**: `check_ajax_referer`، `sanitize_text_field`، `esc_html/esc_url` در اکثر نقاط؛ استثناها در `TECH_DEBT.md`/`TODO.md`.
