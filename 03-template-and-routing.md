# ۰۳ — نگاشت تمپلیت‌ها و مسیرها (Template Hierarchy & Routing)

> نحوهٔ فعال‌شدن هر قالب در وردپرس استاندارد + مسیرهایی که سایت استفاده می‌کند.

## جدول نگاشت

| URL نمونه | شرط وردپرس | فایل قالب | محتوا |
|---|---|---|---|
| `/` | `is_front_page()` | `front-page.php` | لندینگ (جزئیات فایل ۰۷) |
| `/courses/<slug>/` | `is_singular('sfwd-courses')` | `single-sfwd-courses.php` | صفحهٔ دوره (فایل ۰۸) |
| `/courses/` | `is_post_type_archive('sfwd-courses')` | `archive-sfwd-courses.php` → `taxonomy-ld_course_category.php` | گرید دوره‌ها |
| `/courses/cat/<cat>/` یا هر آرشیو `ld_course_category` | `is_tax('ld_course_category')` | `taxonomy-ld_course_category.php` | گرید + فیلتر + SEO/FAQ |
| `/author/<user>/` | `is_author()` | `author.php` | پروفایل مدرس |
| `/login` | برگه با اسلاگ `login` | `page-login.php` | فرم ورود/OTP (HTML مستقل) |
| `/panel` | برگه با اسلاگ `panel` | `page-panel.php` | (فقط گارد لاگین + هدر/فوتر خالی) |
| `/panel/my-courses` و… | برگه‌هایی با تمپلیت‌های پنل | `panel/*.php` | صفحات پنل |
| 404 | `is_404()` | `404.php` | صفحهٔ خطا |
| برگهٔ دلخواه | `is_page()` | `page.php` | محتوای برگه |
| برگهٔ «دسته‌بندی‌ها» | برگه + انتخاب تمپلیت «صفحه دسته‌بندی دوره‌های لرن‌دش» | `page-courses-cat.php` | گرید همهٔ دسته‌ها |
| برگهٔ «اساتید» | برگه + انتخاب تمپلیت «صفحه لیست اساتید (Group Leaders)» | `template-instructors.php` | کارت اساتید با نقش `group_leader` |
| برگه با اسلاگ `courses` (در صورت وجود) | `is_page('courses')` | `page-courses.php` | لیست ۱۲ عنوان (استاب) |
| هر چیز دیگر | fallback | `index.php` | ⚠️ باکس دیباگ! |

## جزئیات فایل‌های کلیدی

### `page-login.php` (ورود/ثبت‌نام)
- **بدون** `get_header()/get_footer()/wp_head()/wp_footer()` — یک سند کامل HTML/RT است.
- شامل ۵ «گام» (`auth-step`): موبایل → (رمز یا OTP) → ثبت‌نام نام → رمز جدید.
- متغیرهای JS سراسری: `falnic_ajax_url` و `falnic_auth_nonce`.
- ریدایرکت بعد از ورود: `redirect_to` از کوئری‌استرینگ خوانده می‌شود؛ اگر نبود → `home_url()`.
- ⚠️ خروجی `$_GET['redirect_to']` بدون escape داخل JS است (XSS؛ فایل ۱۰).

### `archive-sfwd-courses.php`
- فقط `get_template_part('taxonomy', 'ld_course_category')` را صدا می‌زند؛ یعنی آرشیو دوره‌ها همان طراحی دسته را دارد.
- در قالب دسته، لوپ اصلی (`have_posts()`) کار می‌کند؛ `$queried_object` برای آرشیو ممکن است null باشد — کد با `??` محافظت شده است.

### `taxonomy-ld_course_category.php` (مهم‌ترین آرشیو)
- متغیرهای ابتدا: `$term_name` (نام دسته؛ fallback عجیب «توضیحات دسته بندی»)، `$course_count`، `$short_discription` (= `$queried_object->description` پیش‌فرض وردپرس — برای بخش SEO پایین)، `$term_desc` (متای `short_discription` — برای هدر دسته)، `$faqs` (متای `ld_category_faqs`).
- **سایدبار فیلتر** در این نسخه صرفاً UI است (چک‌باکس‌ها بدون منطق/اکشن؛ دکمه‌های اعمال/حذف بدون handler واقعی).
- مرتب‌سازی (پیش‌فرض/جدیدترین/محبوب/بالاترین امتیاز): UI است؛ هنوز به کوئری وصل نشده (المان‌های رادیویی بدون JS منطقی).
- گرید کارت‌ها: از لوپ اصلی استفاده می‌کند؛ قیمت از `_sfwd-courses` (کلید `sfwd-courses_course_price_type` / `_course_price`)؛ badge های `badge-purple`(دسته) و `badge-orange`(تگ).
- صفحه‌بندی: `paginate_links`.
- پایین صفحه: متن SEO (متای `short_discription`) + آکاردئون FAQ (متای `ld_category_faqs`) با یک اسکریپت inline ساده در انتهای فایل.
- `$short_discription` از `get_queried_object()->description` گرفته شده و در بخش SEO پایین، شرط `!empty($short_discription)` روی همین مقدار است (نه متا) — دقت کنید کدام فیلد واقعاً نمایش داده می‌شود: هدر دسته از `$term_desc` (متا) و بخش پایین از description پیش‌فرض تاکسونومی.

### `page-courses-cat.php`
- استایل کامل داخل `<style>` در خود فایل.
- کوئری `get_terms(['taxonomy' => 'ld_course_category', 'hide_empty' => true])`.
- تصویر دسته از متای `ld_cat_image_id` (آیدی attachment)؛ اگر نبود fallback: `.../images/default-cat.jpg` (⚠️ این فایل هم در ریپو نیست).
- لینک هر کارت → صفحهٔ همان دسته.

### `template-instructors.php`
- خودش `archive-courses.css` را با `wp_enqueue_style` لود می‌کند (چون enqueue خودکار فقط برای context های مشخص است).
- کوئری: `WP_User_Query` با `role => group_leader`، هر صفحه ۱۲ نفر، ترتیب `display_name ASC`؛ صفحه‌بندی با `paginate_links` و `format => 'page/%#%/'`.
- آواتار: `get_avatar_url` (که با فیلتر قالب، آواتار سفارشی/پیش‌فرض فالنیک را برمی‌گرداند — فایل ۰۴).
- سایدبار فیلتر: UI بدون منطق (مثل آرشیو).
- محتوای SEO پایین: متن ثابت در خود فایل.

### `author.php`
- متغیرهای ابتدای فایل: نام، بیو (`description`)، `about_teacher`، آواتار، سوشال (youtube/linkedin/instagram).
- `$facebook` چک می‌شود ولی **خوانده نمی‌شود** (همیشه undefined → لینک فیسبوک هیچ‌وقت نمایش داده نمی‌شود).
- تعداد دوره‌ها: `count_user_posts($author_id, 'sfwd-courses')`.
- تعداد دانشجویان یکتا: حلقه روی دوره‌های نویسنده و `learndash_get_users_for_course($course_id, [], false)` + ددوپلیکیت.
- امتیاز: `instructor_rating_average` اگر نبود «۵.۰» هاردکد.
- جدول‌های سوابق: `teacher_jobs` و `teacher_education`.
- اسلایدر دوره‌ها (Owl) با لوپ `WP_Query` روی دوره‌های نویسنده.
- بخش «به استاد امتیاز دهید» + دکمهٔ «ثبت امتیاز»: **بدون handler** (فقط UI).
- مقالات: کارت‌های هاردکد به بلاگ بیرونی `falnic.com/blog`.

### `page-panel.php`
```php
if (!is_user_logged_in()) {
    wp_redirect('https://edu.falnic.com/login?redirect_to=https://edu.falnic.com/panel');
    exit;
}
get_header(); ?>
<?php get_footer();
```
- بین هدر و فوتر **هیچ محتوایی** ندارد؛ اگر صفحهٔ `/panel` با همین قالب باز شود خالی دیده می‌شود (به فایل ۰۹ مراجعه کنید — احتمالاً باید به داشبورد پنل ریدایرکت/نمایش دهد).

### `index.php`
- یک «باکس دیباگ» است: نام تمپلیت جاری، شرط‌های وردپرس، `get_queried_object()` و query_vars را چاپ می‌کند.
- چون fallback قالب است، هر صفحه‌ای که قالب اختصاصی ندارد (مثلاً single درس‌ها، quiz، گروه‌ها، بایگانی پست‌ها و…) این باکس را نشان می‌دهد! ⚠️ برای تولید باید یک `index.php` واقعی نوشته شود (فایل ۱۰).

## نکته: تمپلیت‌های داخل `panel/`
فایل‌های `panel/*.php` دارای هدر `Template Name:` هستند. از **وردپرس ۴.۷** به بعد، قالب‌های صفحه در زیرپوشهٔ سطح اول به‌صورت خودکار توسط هسته پیدا می‌شوند؛ پس این تمپلیت‌ها باید در متاباکس «قالب صفحه» پیشخوان ظاهر شوند (اگر ظاهر نشدند، فیلتر `theme_page_templates` را اضافه کنید — فایل ۱۰).
