# ۰۸ — صفحهٔ تکی دوره (`single-sfwd-courses.php`)

این فایل (~۱۰۸۰ خط) پیچیده‌ترین قالب سایت است. ساختار کلی:

```
main.course-page > .container > .course-main-content + aside.course-sidebar-content
 + .cta-btn-buy (موبایل، چسبان پایین)
```

## ۱. داده‌های ابتدای فایل

| متغیر | منبع |
|---|---|
| `$subtitle, $duration, $level, $status, $suffering` | متاهای سفارشی `_course_*` |
| `$outcomes, $faqs` | `_course_outcomes`, `_course_faq` |
| `$course_meta_settings['sfwd-courses_course_price']` | قیمت نیتیو لرن‌دش |
| `$lessons` | `learndash_get_course_lessons_list($course_id)` |
| `$course_categories / $course_tags` | `get_the_terms` روی `ld_course_category` / `ld_course_tag` |
| `$breadcrumb_category` | لینک اولین دسته (یا آرشیو در صورت نبود) |

## ۲. هدر دوره (course-header-section)

- تصویر شاخص یا fallback `single-course.png` + آیکن پخش؛ کلیک روی تصویر (`preview-hover-overlay`) در JS اولین درسِ «باز» را باز می‌کند، وگرنه → `/login/`.
- عنوان + دکمهٔ علاقه‌مندی (wishlist) با data-attribute های:
  `data-course`, `data-nonce=wishlist_nonce`, `data-logged-in`, `data-login-url`.
  وضعیت اولیه از `user_meta fav_courses` (رشتهٔ کامایی) خوانده می‌شود.
- خلاصه (`$subtitle` یا excerpt) + لینک «بیشتر» به `#course-description`.
- چیپ‌های دسته/تگ + باکس «اطلاعات دوره» (سطح/وضعیت/مدت).
- تب‌های چسبان (سرفصل‌ها/توضیحات/نظرات/مدرسین/FAQ): اسکرول نرم توسط `main.js` با IntersectionObserver.

## ۳. آنچه می‌آموزید (outcomes)
- باکس `.features-grid` فقط اگر `$outcomes` خالی نباشد.

## ۴. سرفصل‌ها (content-list) — مهم‌ترین منطق

- شمارنده‌ها: `count($lessons)` جلسه، `count(learndash_30_get_course_sections($course_id))` فصل، مدت دوره.
- `$has_access = sfwd_lms_has_access($course_id, $user_id)`.
- لوپ روی درس‌ها:
  - مدت هر درس از `_learndash_course_grid_duration` (ثانیه) → متن «X:YY ساعت»/«X دقیقه».
  - باز بودن درس: `$has_access || sample_lesson === 'on'` → کلاس `lesson-free` (پخش) یا `lesson-locked` (قفل).
  - سکشن‌ها (فصل‌ها): با `learndash_30_get_course_sections`؛ هرجا کلید سکشن با lesson_id جاری برابر شد، یک `<details class="course-section-accordion">` باز می‌شود (اولین فصل باز). شمارش درس‌ها/مدت هر فصل هم محاسبه می‌شود.
  - درس‌های بدون فصل در `.standalone-lessons-wrapper`.

## ۵. مودال ویدیوی درس (به‌صورت رشتهٔ HTML جمع می‌شود)

برای هر درس باز یک `<div id="lesson-modal-{id}">` ساخته می‌شود:
- ویدیو: `lesson_video_url` ← اول `wp_oembed_get`؛ اگر خالی بود و آدرس `.mp4` → تگ `<video class="js-player">` (Plyr)؛ در غیر این صورت `<iframe>`.
- فوتر مودال:
  - دکمهٔ قبلی/بعدی: «ویدیوی قبلی/بعدی» بین `$unlocked_lessons` (درس‌های باز) با شمارندهٔ «درس/پیش‌نمایش X از Y».
  - اگر کاربر دسترسی دارد، با هر «بعدی/تکمیل»، AJAX `custom_mark_lesson_complete` صدا زده می‌شود (nonce: `mark_complete_nonce_{lesson_id}`، ارسال course_id).
  - آخرین درس + دسترسی → دکمهٔ سبز «تکمیل درس».
  - آخرین درس + **بدون دسترسی** → دکمهٔ زرد «شرکت در دوره» که مودال را می‌بندد و `#start_course` (دکمهٔ خرید سایدبار) را کلیک می‌کند.

## ۶. پایان‌دهی تکمیل درس (`inc/ajax_functions.php` → `custom_mark_lesson_complete`)
ترتیب عملیات برای دور زدن قفل‌ها و ثبت رسمی:
1. `learndash_video_complete_for_step($course_id, $lesson_id, $user_id)` (یا fallback: user_meta `learndash_video_complete_{id}`)
2. `update_user_meta($user_id,'learndash_timer_complete_'.$lesson_id, time())`
3. `learndash_process_mark_complete($user_id, $lesson_id, false, $course_id)`
4. اگر باز هم ثبت نشد: نوشتن مستقیم `_sfwd-course_progress[$course_id]['lessons'][$lesson_id]=1` + `learndash_update_user_activity`
5. پاسخ: `learndash_course_progress([... 'array'=>true])` → فرانت نوار پیشرفت را آپدیت می‌کند (`updateProgressUI` در `single-courses.js`).

## ۷. آپلود تمرین (فقط UI)
- اگر `$suffering == '1'` باکس «آپلود تمرین‌های دوره» با `.homework-uploader` نمایش داده می‌شود؛ **فعلاً فقط UI است و هیچ منطق آپلودی ندارد**.

## ۸. توضیحات دوره + دکمهٔ «ادامه مطلب» (checkbox مخفی)

## ۹. وبینار
- فقط وقتی `_webinar_active === 'yes'` رندر می‌شود: تاریخ/تایم/محل + نقشه اگر `_webinar_map_iframe` بود + دکمه‌های مسیریابی گوگل‌مپ/نشان (عکس‌های آیکن از مسیر تولید هاردکد).

## ۱۰. نظرات (reviews)
- منطق فعال‌سازی: `comments_open()` **یا** متای `learndash-course-reviews[show_reviews]==='yes'`؛ در انتها یک خط «طلایی» همه‌چیز را `true` می‌کند:
  `$is_reviews_enabled = true;` ← یعنی همیشه نمایش داده می‌شود.
- لیست: کامنت‌های تاییدشده (`status=approve`) + امتیاز از `review_rating` (پیش‌فرض ۵).
- فرم ثبت: ستاره‌های `#review-rating-stars` + متن + دکمه با data-course/data-nonce.
  ارسال → `submit_course_review`؛ بعد از موفقیت نظر در لیست نمی‌آید تا تایید مدیر (`comment_approved=0`).

## ۱۱. استاد دوره (instructors-section)
- فقط نویسندهٔ دوره (`post_author`): آواتار (فیلتر قالب)، نام، بیو (description) و نقش نمایشی `instructor_user_role`.
- زیرنویس «مدیر راهکارهای شبکه و امنیت داده» هاردکد است. لینک پروفایل در کامنت.

## ۱۲. گواهینامه (certificate-section)
- اگر `learndash_get_setting($course_id,'certificate')` خالی نبود:
  - `learndash_get_course_certificate_link($course_id,$user_id)` → اگر لینک داشت: «مشاهده و دریافت گواهینامه» + دکمهٔ دانلود (چاپ).
  - وگرنه: دکمهٔ غیرفعال «نیازمند تکمیل دوره».
- تصویر شاخص گواهینامه یا fallback `sample-certificate.png`.

## ۱۳. دوره‌های مرتبط
- `WP_Query` روی `sfwd-courses`، `posts_per_page=4`، حذف دورهٔ فعلی.
- ⚠️ کوئری tax_query برای هم‌دسته‌بودن در **کامنت** است → عملاً ۴ دورهٔ دلخواه (بدون اولویت دسته) می‌آید.
- اسلایدر owl با `.related-courses-carousel`.

## ۱۴. FAQ دوره
- `<details class="faq-item">` روی `_course_faq`.

## ۱۵. سایدبار (course-sidebar-content + sticky-sidebar-card)

**حالت ۱ — کاربر دسترسی دارد (`sfwd_lms_has_access`):**
- نوار پیشرفت: `learndash_course_progress(['array'=>true])` → درصد/تکمیل‌شده/کل.
- دکمهٔ CTA: «شروع یادگیری» (۰٪) / «ادامه یادگیری» / «مرور دوره» (۱۰۰٪) → به `#content-list` (یا `learndash_course_get_resume_step_url` در صورت وجود).

**حالت ۲ — کاربر دسترسی ندارد:**
- عنوان دوره + قیمت: `price_type=free` یا price خالی → «رایگان»؛ وگرنه `number_format` تومان.
- اگر **لاگین نیست و price_type !== 'closed'** → لینک «برای ثبت‌نام ابتدا وارد شوید» (`wp_login_url(get_permalink())`) با id=`start_course`.
- در غیر این صورت → `learndash_payment_buttons($post)` (دکمهٔ واقعی خرید/پرداخت لرن‌دش).

**بخش «اطلاعات دوره» (مواد دوره):**
- `course_materials_enabled === 'on'` و محتوای غیرخالی → استخراج `<li>`ها (یا خطوط) و نمایش به‌صورت شماره‌دار.
- دو منبع چک می‌شود: `learndash_get_setting(...)` و fallback روی آرایهٔ `_sfwd-courses`.

## ۱۶. نوار پایین موبایل (`cta-btn-buy`)
- خارج از `<main>`؛ دکمهٔ پرداخت لرن‌دش + قیمت — احتمالاً با CSS در موبایل نمایش داده می‌شود.

## نکات توسعه
- هر جا خواستید به «درس بعدی کاربر» برسید، آرایهٔ `$unlocked_lessons` همان ترتیب پخش را دارد.
- برای ویدیوهای آپارات (iframe) مودال، هنگام بستن `src` خالی می‌شود تا پخش قطع شود (`data-src` نگه‌داری می‌شود).
- قالب به کامنت‌های تاییدشده وابسته است؛ اگر مدیر نظری را approve نکند، در لیست نیست.
