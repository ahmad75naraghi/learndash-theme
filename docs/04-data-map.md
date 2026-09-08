# ۰۴ — نقشهٔ داده‌ها (Data Map)

تمام کلیدهای سفارشی (meta) که قالب می‌نویسد/می‌خواند + کلیدهای نیتیو لرن‌دش که استفاده می‌کند + سشن/ترنزینت/جدول.

## ۱. Post Meta دوره (`sfwd-courses`)

### متاهای سفارشی قالب (در `inc/meta_functions.php` ذخیره / در قالب‌ها خوانده می‌شوند)

| کلید | نوع | توضیح | نمونهٔ مقدار |
|---|---|---|---|
| `_course_subtitle` | textarea | توضیح کوتاه زیر عنوان دوره (Hero) | متن |
| `_total_duration` | string | مدت کل دوره (نمایشی) | «۵ ساعت و ۳۰ دقیقه» |
| `_course_level` | select | سطح | `مقدماتی` / `متوسط` / `پیشرفته` |
| `_course_status` | select | وضعیت | `در حال برگزاری` / `تمام شده` |
| `_course_suffering` | string «0/1» | فعال‌سازی باکس «آپلود تمرین‌های دوره» | `1` = فعال |
| `_course_outcomes` | array | ریپیتر «آنچه در این دوره می‌آموزید» | `["مورد ۱", …]` |
| `_course_faq` | array | ریپیتر سوالات متداول | `[['question'=>…,'answer'=>…]]` |
| `_webinar_active` | string | نمایش بخش وبینار در صفحهٔ دوره | `yes` / `no` |
| `_webinar_date` | string | تاریخ شروع وبینار (نمایشی) | «۲۹ اردیبهشت ۱۴۰۵» |
| `_webinar_time` | string | ساعت وبینار (نمایشی) | «ساعت ۱۹ تا ۲۲» |
| `_webinar_location` | textarea | محل برگزاری | آدرس |
| `_webinar_map_iframe` | url | لینک src نقشه embed (گوگل مپ/نشان/بلد) | `https://…/embed…` |
| `_webinar_gmap_link` | url | مسیریابی گوگل مپ | `https://goo.gl/maps/…` |
| `_webinar_neshan_link` | url | مسیریابی نشان | `https://nshn.ir/…` |

متاباکس‌ها فقط روی پست‌تایپ `sfwd-courses` ثبت شده‌اند (`add_meta_box('lms_course_meta', …, 'sfwd-courses', 'normal', 'high')`).

### متاهای نیتیو لرن‌دش که قالب می‌خواند

| کلید / تابع | محل | کاربرد |
|---|---|---|
| `get_post_meta($id,'_sfwd-courses',true)` | همه‌جای قالب | آرایهٔ تنظیمات دوره |
| `…['sfwd-courses_course_price_type']` | کارت‌ها/سایدبار | `open|free|closed|buynow|subscribe` — قالب `open/free` را «رایگان» و بقیه را قیمت‌دار می‌بیند |
| `…['sfwd-courses_course_price']` | کارت‌ها/سایدبار | مبلغ (عدد) |
| `…['sfwd-courses_course_materials_enabled']` | سایدبار دوره | `on` یعنی مواد دوره فعال است |
| `…['sfwd-courses_course_materials']` | سایدبار دوره | محتوای HTML مواد دوره؛ قالب <li>ها را استخراج می‌کند |
| `learndash_get_setting($course_id,'certificate')` | دوره/گواهینامه | آیدی پست گواهینامهٔ لرن‌دش |
| `learndash_get_setting($lesson_id,'sample_lesson')` | سرفصل‌ها | `on` = درس «پیش‌نمایش» (بدون خرید پخش می‌شود) |
| `learndash_get_setting($lesson_id,'lesson_video_url')` | سرفصل‌ها | آدرس ویدیوی درس |
| `get_post_meta($lesson_id,'_learndash_course_grid_duration',true)` | سرفصل‌ها | مدت درس به ثانیه (کلید افزونه‌ی Course Grid!) → تبدیل به «ساعت/دقیقه» |
| `learndash_30_get_course_sections($course_id)` | سرفصل‌ها | سکشن‌های «فصل»؛ خروجی آرایه با کلید lesson_id |
| `get_user_meta($user_id,'_sfwd-course_progress',true)` | ajax تکمیل درس | ساختار پیشرفت `[course_id]['lessons'][lesson_id]=1` و … |

## ۲. Term Meta دسته‌بندی دوره (`ld_course_category`)

| کلید | نوشته‌شده در | خوانده‌شده در | توضیح |
|---|---|---|---|
| `ld_cat_image_id` | `edited_ld_course_category` | `page-courses-cat.php` | آیدی attachment تصویر دسته |
| `short_discription` | `edited_ld_course_category` | `taxonomy…` (هدر دسته) | توضیح/متن SEO با `wp_editor` (HTML مجاز، `wp_kses_post`) |
| `ld_category_faqs` | `edited_ld_course_category` | `taxonomy…` (پایین صفحه) | آرایه `[['question'=>…,'answer'=>…]]` |

> نکته: در `taxonomy-ld_course_category.php` متغیر `$short_discription` از `$queried_object->description` (فیلد پیش‌فرض تاکسونومی) پر می‌شود نه متا؛ بخش SEO پایین فقط همان description را نشان می‌دهد. نمایش هدر از متای `short_discription` است.

## ۳. User Meta

### احراز هویت و ساخت کاربر
- کاربران با **username = شماره موبایل** (۱۱ رقمی `09…`) ساخته می‌شوند.
- ایمیل پیش‌فرض ساخت: `{mobile}@falnic.user` (`wp_create_user($mobile, wp_generate_password(), $mobile.'@falnic.user')`).
- در ثبت‌نام: `first_name`، `last_name`، `display_name` ست می‌شود (کلید `full_name` در `wp_update_user` معتبر نیست و ذخیره نمی‌شود).

### پروفایل/پنل (خوانده/نوشته در panel/*.php و ajax_functions)

| کلید | محل | توضیح |
|---|---|---|
| `first_name_fa`، `last_name_fa`، `first_name_en`، `last_name_en` | `profile.php` / `save_user_profile` | نام برای گواهینامه |
| `gender` | همان | `male`/`female` |
| `birth_date` | همان | تاریخ جلالی |
| `billing_phone` | `settings.php` / `save_account_settings` | شماره موبایل (کلید استاندارد ووکامرس) |
| `fav_courses` | دوره/پنل | رشتهٔ جدا شده با کاما از course_id ها (لیست علاقه‌مندی) |
| `first_name` | سایدبار پنل | برای «سلام …» (باید با ثبت‌نام پر شود) |

### فیلدهای مدرس (admin → پروفایل کاربر؛ `inc/meta_functions.php`)

| کلید | نوع | توضیح |
|---|---|---|
| `instructor_user_role` | string | عنوان/نقش نمایشی استاد (مثلاً «مدرس ارشد») |
| `custom_user_avatar` | url | آواتار سفارشی (با مدیا آپلودر) |
| `youtube` / `linkedin` / `instagram` | url | شبکه‌های اجتماعی |
| `about_teacher` | textarea | «درباره مدرس» طولانی |
| `teacher_jobs` | array | `[['company','position','duration']]` |
| `teacher_education` | array | `[['degree','field','university']]` |
| `instructor_rating_average` | string | (فقط خوانده می‌شود؛ نوشته نمی‌شود) |

### فیلترهای آواتار (سراسری)
- `get_avatar` و `get_avatar_url` با فیلتر جایگزین شده‌اند: اگر `custom_user_avatar` بود از آن استفاده کن؛ وگرنه تصویر پیش‌فرض:
  `https://edu.falnic.com/wp-content/themes/edu-falnic/assets/img/single-page/placeholder_avatar_icon.png` (⚠️ هاردکد؛ فایل محلی هم در `assets/img/single-page/placeholder_avatar_icon.png` موجود است).

## ۴. Comment Meta

| کلید | محل |
|---|---|
| `review_rating` | روی کامنت‌های نظر دوره (۱ تا ۵) — در `handle_submit_course_review` ذخیره، در `single-sfwd-courses.php` خوانده می‌شود. پیش‌فرض نمایش ۵ وقتی خالی باشد. |

نظرات دوره = کامنت عادی وردپرس روی پست دوره؛ `comment_approved=0` (نیازمند تایید) هنگام ثبت.

## ۵. Session (PHP session)

کلیدهای استفاده‌شده (در `inc/login.php` و `inc/captcha.php`):

| کلید | معنی |
|---|---|
| `fl_otp` | کد ۵ رقمی فعلی |
| `fl_mobile` | موبایلی که کد برایش صادر شده |
| `fl_otp_time` | timestamp صدور |
| `fl_otp_purpose` | `register` / `login_otp` / `reset_password` / `resend_otp` |
| `fl_otp_verified` | true بعد از تایید موفق OTP |
| `fl_otp_verified_for` | `reset_password` / `register` |
| `captcha_code` | کد captcha (کپچا فعلاً استفاده نمی‌شود) |

## ۶. Transient

| کلید | مدت | محل |
|---|---|---|
| `otp_limit_{mobile}` | ۶۰ ثانیه | جلوگیری از ارسال مکرر OTP (Rate limit) |

## ۷. جدول سفارشی `{wp}_falnic_transactions`

سازندهٔ این جدول **در قالب نیست**؛ قالب فقط از آن می‌خواند:

```sql
SELECT * FROM {wp}_falnic_transactions WHERE user_id = %d ORDER BY created_at DESC
SELECT COUNT(*) FROM {wp}_falnic_transactions WHERE user_id = %d ORDER BY created_at DESC
```

ستون‌های استفاده‌شده در کد:

| ستون | نوع (استنتاجی) | توضیح |
|---|---|---|
| `id` | int (احتمالاً PK) | — |
| `user_id` | int | کاربر خریدار |
| `course_id` | int | دوره |
| `status` | string | `success` = موفق؛ هر مقدار دیگر = ناموفق |
| `tracking_code` | string | شماره پیگیری تراکنش |
| `amount` | int/decimal | مبلغ |
| `created_at` | datetime | زمان؛ با `strtotime` → `wp_date` نمایش داده می‌شود |

> اگر روزی بخواهید درگاه/دریافت‌کنندهٔ پرداخت را در همین قالب بسازید، باید این جدول و مکانیزم پرکردنش را پیاده‌سازی کنید (فعلاً خارج از ریپو).

## ۸. گزینه‌های (options) سفارشی
- گزینهٔ اختصاصی (add_option/update_option) در قالب استفاده نشده است؛ همه‌چیز از meta ها/جدول است.
