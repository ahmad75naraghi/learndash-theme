# CONTRIBUTING — استانداردهای توسعه و مشارکت

این سند قوانین مشارکت در کدبیس قالب «edu falnic» است. همهٔ تغییرات (توسط انسان یا AI) باید با این استانداردها هم‌خوانی داشته باشند.

---

## ۱. اصول پایه

1. **مستندات را به‌روز نگه دارید** — هر تغییر قابل‌توجه باید در `CHANGELOG.md` (بخش `[Unreleased]`) ثبت شود؛ اگر ساختار/معماری تغییر کرد، `ARCHITECTURE.md` و `TECH_DEBT.md` را هم اصلاح کنید.
2. **بدون تغییرات گستردهٔ بی‌ربط** — هر PR فقط یک موضوع داشته باشد (atomic).
3. **RTL/فارسی را نشکنید** — قالب راست‌چین است؛ از `dir` های دستی بی‌دلیل پرهیز کنید؛ متن‌های جدید فارسی با فونت «دانا» نمایش داده می‌شوند.
4. **با LearnDash از طریق API عمومی آن کار کنید** — از توابع `learndash_*` و `sfwd_lms_has_access()` استفاده کنید، نه کوئری مستقیم روی جدول‌های داخلی لرن‌دش (مگر با دلیل مستند).

## ۲. قراردادهای نام‌گذاری (Naming Conventions)

| حوزه | قانون | نمونه |
|---|---|---|
| توابع PHP | توابع جدید را با پیشوند `falnic_` بنویسید؛ نام‌های تاریخیِ بدون پیشوند (`send_pattern_sms`, `theme_enqueue`, `is_current_path`, `captcha_verify`, `handle_*`) را برای سازگاری تغییر ندهید | `falnic_get_course_price_label()` (جدید) |
| کلاس‌های PHP | `Falnic*` (PascalCase) | `FalnicAuthHandler` |
| اکشن/هوک | `falnic_*` (lowercase snake) | `falnic_verify_otp` |
| post/user/term meta | snake_case با پیشوند `_` برای post-meta | `_course_outcomes`, `first_name_fa` |
| توابع کمکی در `inc/` | `falnic_*` (هنگام استخراج helpers تکراری) | `falnic_get_course_thumbnail()` |
| هندل‌های CSS | کلاس‌های صفحه‌ای معنادار + پیشوندهای موجود | `.eduf-*`, `.ld-*`, `.lesson-*` |
| JS | camelCase؛ تعریف متغیر با `const/let` | `coursescarousel` (موجود)، `updateProgressUI` |
| handle های enqueue | kebab/lowercase | `courses-page`, `panel-js` |

## ۳. استانداردهای کدنویسی

### PHP (هم‌سو با WordPress Coding Standards)
- **هرگز short open tagِ خالص** ننویسید — `<? if`, `<? }` (بدون `php`) ممنوع است؛ فقط `<?php`.
- برای خروجیِ ساده در قالب‌ها `<?=` (echo کوتاه) مطابق کد موجود مجاز است؛ در کد جدید ترجیحاً `<?php echo … ?>` بنویسید و **خروجی همیشه escape** شود.
- **خروجی‌ها را escape کنید**: `esc_html()`, `esc_url()`, `esc_attr()`, `wp_kses_post()`.
- **ورودی‌ها را sanitize کنید**: `sanitize_text_field()`, `intval()`.
- **همهٔ اکشن‌های AJAX** باید `check_ajax_referer()` داشته باشند و پیام خطای مناسب `wp_send_json_error`.
- **کوئری‌های دیتابیس**: فقط با `$wpdb->prepare()`؛ جدول‌ها با `$wpdb->prefix`.
- **هیچ آدرس/اعتبارنامه‌ای هاردکد نشود** — به‌جای `https://edu.falnic.com/...` از `home_url()`, `get_template_directory_uri()`, `PATH_DIR_URL` استفاده کنید.
- PHP 7.4+ (نوشتن کد 8.x سازگار)؛ اجتناب از توابع حذف‌شده.

### JS
- بدون فریم‌ورک/باندلر (jQuery ساده). ترتیب: داخل `jQuery(document).ready`.
- متغیر سراسری ناخواسته نسازید (`let/const` یا IIFE) — ⚠️ نمونهٔ موجود: `selectedRating` در `author.js`.
- داده‌های پویا را از `data-*`/`ajax_object.ajax_url` بخوانید؛ URL ها را هاردکد نکنید.

### CSS
- تغییر استایل فقط در فایل CSS همان صفحه (طبق جدول enqueue در `ARCHITECTURE.md` §6).
- رنگ‌ها از متغیرهای `:root` در `style.css` (`--primary-blue`, `--accent-yellow`)؛ از رنگ هاردکد پرهیز کنید.

## ۴. قالب پیشنهادی ابزارها (Prettier / ESLint)

ریپو در حال حاضر هیچ ابزار build/lint ندارد. اگر ابزار اضافه کردید، این تنظیمات را به‌عنوان پیش‌فرض نگه دارید:

### `.prettierrc` (برای JS/CSS/JSON — اگر معرفی شد)

```json
{
  "printWidth": 100,
  "singleQuote": true,
  "semi": true,
  "tabWidth": 4,
  "endOfLine": "lf"
}
```

و فایل `.prettierignore`:

```
# PHP را Prettier فرمت نمی‌کند (پارس‌گر ندارد)
*.php
*.md
screenshot.png
```

> PHP را با Prettier فرمت نکنید — PHP را با `php -l` و (اختیاری) `PHP_CodeSniffer` + استاندارد WP چک کنید. فایل‌های `.md` هم معمولاً خارج از Prettier نگه داشته می‌شوند.

### `.eslintrc` پیشنهادی (برای `assets/js/*.js`)

```json
{
  "env": { "browser": true, "jquery": true, "es2020": true },
  "globals": { "ajax_object": "readonly", "Plyr": "readonly", "jalaliDatepicker": "readonly" },
  "rules": {
    "no-unused-vars": "warn",
    "no-undef": "error",
    "no-implicit-globals": "error"
  }
}
```

## ۵. قرارداد Commit (Conventional Commits)

فرمت مجاز:

```
<type>(<scope-optional>): <description>

<body — اختیاری>

<footer — اختیاری (BREAKING CHANGE، Closes #id)>
```

انواع مجاز: `feat` · `fix` · `docs` · `refactor` · `perf` · `style` · `test` · `chore` · `security`.

نمونه‌ها:

```
feat(login): add rate limit for OTP resend
fix(panel): redirect certificates guard to certificates page
docs: update ARCHITECTURE meta table after adding new course field
security(auth): escape redirect_to before printing into inline JS
refactor(assets): drop enqueue of missing single-post assets
```

## ۶. فرایند Pull Request

1. از آخرین `main` یک شاخه بگیرید: `git checkout -b fix/panel-certificates-redirect`.
2. فقط تغییرات مرتبط با همان موضوع را commit کنید (atomic).
3. قوانین زیر را چک کنید:
   - [ ] `php -l` روی همهٔ فایل‌های PHP تغییرکرده پاس شد.
   - [ ] بدون URL/اعتبارنامهٔ هاردکد جدید.
   - [ ] خروجی‌ها escape و ورودی‌ها sanitize شده‌اند.
   - [ ] اکشن AJAX جدید دارای nonce و hook های درست (`wp_ajax_`/`wp_ajax_nopriv_`) است.
   - [ ] `CHANGELOG.md` به‌روز شده است.
   - [ ] در صورت تغییر رفتار کاربردی، تست دستی (سناریوهای README §7) انجام شده.
4. PR را با توضیح باز کنید (قالب زیر) و به `main` بسازید (base).
5. منتظر تایید/review باشید؛ هر نظر را در همان شاخه اصلاح و `--amend` یا commit جدید بزنید (تاریخچهٔ تمیز ترجیح دارد).
6. **بدون تایید، merge مستقیم به `main` نکنید.**

### قالب توضیح PR

```markdown
## خلاصه
<!-- چرا و چه تغییری -->

## تغییرات
- [ ] feat/fix/docs/... — توضیح

## تست
<!-- چه تست دستی/خودکاری انجام شد -->

## بستن
Closes #<issue>
```

## ۷. قوانین ویژهٔ این کدبیس (نبایدها)

- **نخورید به** `learndash_*` داخلی/متاهایش به‌جز API های عمومی.
- ساختار جدول `falnic_transactions` را بدون هماهنگی با مصرف‌کننده‌ها (payments/dashboard) تغییر ندهید.
- تابع `falnic_send_otp_with_bale()` را **داخل قالب تعریف نکنید** مگر تصمیم صریح معماری — فعلاً وابستگی بیرونی است.
- فایل‌های `panel/*.php` را از زیرپوشه خارج نکنید (Template Name وابسته به مسیر نیست اما نظم فعلی را حفظ کنید).
- کلاس‌های CSS صفحه‌ای را به فایل CSS نادرست منتقل نکنید (رجوع به جدول enqueue).
