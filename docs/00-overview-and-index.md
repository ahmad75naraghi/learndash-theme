# مستندات قالب «edu falnic» (لرن‌دش)

> نسخه مستندات: ۱.۰ — تاریخ: ۱۴۰۵/۰۶ (سپتامبر ۲۰۲۶)
> مبنای مستندات: بررسی کامل سورس موجود در ریپازیتوری `learndash-theme` (شاخه `arena/01a08032-learndash-theme`)

## این مستندات چیست؟

این پوشه (`docs/`) شامل مجموعه‌ای از فایل‌های مارک‌داون است که «تمام» قالب وردپرس/لرن‌دش شما را
- بدون وابسته‌بودن به حافظهٔ یک نفر یا ویدیوهای آموزشی - مستند می‌کند تا هر توسعه‌دهنده‌ای بتواند کار را ادامه دهد.
هر فایل با دقت از روی کد واقعی نوشته شده و محل دقیق توابع/متاها/کلاس‌ها ذکر شده است.

## شاخص فایل‌ها

| فایل | موضوع |
|---|---|
| `00-overview-and-index.md` | همین‌جا: نمای کلی، مشخصات، نقشهٔ ذهنی سایت |
| `01-architecture-and-dependencies.md` | معماری، ترتیب بارگذاری، وابستگی‌ها (پلاگین/سرویس‌های بیرونی) |
| `02-file-map.md` | نقشهٔ کامل فایل‌ها و پوشه‌ها + شرح نقش هر فایل |
| `03-template-and-routing.md` | نگاشت تمپلیت‌ها با URL ها (Template Hierarchy) و جزئیات هر صفحه |
| `04-data-map.md` | نقشهٔ داده: post-meta / term-meta / user-meta / جدول سفارشی / ترنزینت / سشن |
| `05-auth-otp-and-ajax.md` | جریان ورود/ثبت‌نام با OTP و رمز + جدول کامل اکشن‌های AJAX |
| `06-assets-and-libraries.md` | سیستم enqueue، کتابخانه‌های JS/CSS، فونت‌ها و فایل‌های مفقود |
| `07-front-page-sections.md` | بخش‌های صفحهٔ اصلی؛ کدام بخش داینامیک و کدام استاتیک است |
| `08-single-course-page.md` | صفحهٔ تکی دوره: قفل/پیش‌نمایش درس، مودال ویدیو، پیشرفت، خرید، گواهینامه |
| `09-user-panel.md` | پنل کاربری: صفحات، سایدبار، API های استفاده‌شده |
| `10-known-issues-and-todo.md` | باگ‌ها، موارد ناتمام، هشدارهای امنیتی و پیشنهاد اصلاح |

---

## نمای کلی قالب (خلاصهٔ مدیر)

- **نام قالب در style.css:** `edu falnic` — Author: falnic team — `https://falnic.com/`
- **پوشهٔ لایو در سرور تولید:** `wp-content/themes/edu-falnic/` (خیلی از آدرس‌های asset به‌صورت هاردکد به همین مسیر/دامنه اشاره دارند؛ ریشهٔ ریپازیتوری `learndash-theme` است.)
- **زبان/جهت:** فارسی، `dir="rtl"`، فونت اختصاصی «دانا» (`falnic-font.woff2`)
- **سایت واقعی:** `https://edu.falnic.com/` — برند مادر: `falnic.com` (ایران اچ‌پی)
- **موضوع:** آموزشگاه آنلاین IT (شبکه، سرور، امنیت، سخت‌افزار، مجازی‌سازی، CRM)

### پشتهٔ فنی

| لایه | انتخاب |
|---|---|
| CMS | وردپرس (فارسی، راست‌چین) |
| LMS | **LearnDash** (پست‌تایپ `sfwd-courses`، تاکسونومی `ld_course_category` و `ld_course_tag`) |
| فروش دوره | دکمه‌های پرداخت نیتیو لرن‌دش (`learndash_payment_buttons`) + (به نظر) ووکامرس/درگاه خارج از این ریپو |
| تراکنش‌ها | جدول سفارشی `{wp}_falnic_transactions` که **سازنده‌اش در این قالب نیست** (افزونه/اسکریپت بیرونی باید بسازد و پر کند) |
| ارسال پیامک | وب‌سرویس SOAP پنل پیامک «پیامک پنل» (payamak-panel.com) با نام کاربری/رمز هاردکد در `inc/sms.php` |
| پیام‌رسان | تابع `falnic_send_otp_with_bale()` صدا زده می‌شود ولی **در قالب تعریف نشده** (باید جای دیگری مثل mu-plugin تعریف شده باشد) |
| کتابخانه‌های فرانت | jQuery، Owl Carousel 2، Plyr (ویدیو)، Jalali Date Picker، (PhotoSwipe بدون استفاده) |
| فونت | `دانا` (woff2 محلی) |

### صفحات اصلی سایت (دید کاربر)

1. **صفحهٔ اصلی** `/` → `front-page.php`
2. **صفحهٔ ورود/ثبت‌نام** `/login` → `page-login.php` (HTML مستقل، بدون هدر/فوتر قالب)
3. **صفحهٔ پنل کاربری** `/panel` و زیرصفحه‌ها → صفحاتی با اسلاگ زیرمجموعهٔ panel
4. **آرشیو دوره‌ها / دسته‌بندی** `/courses/` و `/courses/<category>` → `taxonomy-ld_course_category.php` / `archive-sfwd-courses.php`
5. **صفحهٔ تکی دوره** `/courses/<slug>` → `single-sfwd-courses.php`
6. **پروفایل استاد** `/author/<user>` → `author.php`
7. **لیست اساتید** (تمپلیت اختصاصی) → `template-instructors.php`
8. **صفحهٔ همهٔ دسته‌بندی‌ها** (تمپلیت اختصاصی) → `page-courses-cat.php`
9. **404** → `404.php`

### سه نکتهٔ بسیار مهم قبل از هر تغییر (جزئیات در فایل‌های مربوطه)

1. **بسیاری از asset ها و ریدایرکت‌ها هاردکدِ دامنهٔ تولید هستند** (`https://edu.falnic.com/...` یا `/wp-content/themes/edu-falnic/...`). برای اجرای لوکال باید آن‌ها را با `get_template_directory_uri()` / `home_url()` جایگزین کرد.
2. **تمپلیت‌های پنل داخل زیرپوشهٔ `panel/` هستند**؛ وردپرس به‌صورت پیش‌فرض فقط تمپلیت‌های «ریشهٔ قالب» را می‌خواند. اگر فیلتر `theme_page_templates` (یا معادل) در جای دیگر تعریف نشده باشد، این تمپلیت‌ها در پیشخوان قابل انتخاب نیستند (توضیح+راه‌حل در `10-known-issues-and-todo.md`).
3. **فایل‌های asset مفقود/خالی** وجود دارند که درخواست 404 تولید می‌کنند (`single-post.css/js`، `single-page.css`، `archive-product.css`، `archive-post.css/js` و…). فهرست کامل در `06-assets-and-libraries.md`.

---

## نقشهٔ ذهنی (Data Flow کوتاه)

```
[کاربر]  →  صفحه ورود page-login.php  →  AJAX: falnic_check_mobile_and_send_otp / falnic_send_otp
        →  پیامک (payamak) + بله → OTP در $_SESSION → falnic_verify_otp
        →  ثبت نام: save_user_register_name (ساخت کاربر با لاگین=موبایل) → falnic_register_user (ست پسورد + لاگین)
        →  ورود با رمز: falnic_login_user

[کاربر عضو]  →  دوره = sfwd-courses  →  single-sfwd-courses.php
        →  درس‌ها: learndash_get_course_lessons_list + قفل/نمونه learndash_get_setting(sample_lesson)
        →  مودال ویدیو → دکمه بعدی → AJAX: custom_mark_lesson_complete
        →  ثبت نظر → WP Comment + meta review_rating → AJAX: submit_course_review
        →  علاقه‌مندی → user_meta fav_courses (رشتهٔ کامایی) → AJAX: toggle_course_wishlist

[پنل]  →  دوره‌های من (learndash_user_get_enrolled_courses + learndash_course_progress)
       →  گواهینامه (learndash_get_course_certificate_link)
       →  تراکنش‌ها (wp_falnic_transactions)
       →  پروفایل (user-meta های first_name_fa و…)  →  تنظیمات حساب (ایمیل/رمز/billing_phone)
```

---

ادامه: فایل‌های `01` تا `10` را بهترتیب بخوانید. برای شروع کار سریع: `01` و `03` و `10`.
