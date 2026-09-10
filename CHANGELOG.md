# CHANGELOG

تمامی تغییرات مهم این پروژه در این فایل ثبت می‌شود.

قالب این فایل از استاندارد **[Keep a Changelog](https://keepachangelog.com/fa-IR/1.1.0/)** پیروی می‌کند و نسخه‌گذاری پروژه از **[Semantic Versioning](https://semver.org/)** (به شکل `<major>.<minor>.<patch>`) تبعیت می‌کند.

## انواع تغییرات (طبق استاندارد)

- **`Added`** — قابلیت‌های جدید
- **`Changed`** — تغییر در قابلیت‌های موجود
- **`Deprecated`** — قابلیت‌هایی که به‌زودی حذف می‌شوند
- **`Removed`** — قابلیت‌های حذف‌شده در این نسخه
- **`Fixed`** — رفع باگ
- **`Security`** — رفع آسیب‌پذیری

---

## [Unreleased]

### Added

- **قالب تک‌نوشته (`single.php`) بر پایهٔ الگوی صفحهٔ تک‌مقالهٔ «شمیم»**: کارت سفید مقاله در ستون راست (۸ ستون) و سایدبار ویجت‌ها در ستون چپ (۴ ستون) — مسیریابی، عنوان مرکزی، تصویر شاخص، نوار اطلاعات (دسته، تاریخ شمسی، ساعت، زمان مطالعه، تعداد دیدگاه، تعداد بازدید)، نوار نویسنده، تایپوگرافی کامل متن (justify با `line-height: 2.15`، blockquote، جدول، کد)، برچسب‌ها، اشتراک‌گذاری، ناوبری نوشتهٔ پیشین/پسین، جعبهٔ دربارهٔ نویسنده، نوشته‌های مرتبط و دیدگاه‌ها.
  - `single.php`، `comments.php` (لیست دیدگاه + `comment_form()` با لیبل فارسی).
  - `template-parts/ee-sidebar.php` — ویجت‌های جستجو، اشتراک‌گذاری، آخرین مطالب، ویژه‌ها، مطالب پربازدید، دوره‌ها و «ما را دنبال کنید».
  - `inc/template_helpers.php` — هلپرها: تبدیل شمسی (`evented_gregorian_to_jalali`/`evented_format_jalali`، فقط وقتی افزونهٔ شمسی‌ساز فعال نباشد)، زمان مطالعه، شمارندهٔ بازدید (`evented_post_views`)، لینک‌های اشتراک/کانال (فیلترپذیر با `evented_share_links` و `evented_channel_links`) و نوشته‌های مرتبط.
- **آرشیو نوشته‌ها**: `archive.php` (دسته/برچسب/بایگانی زمانی)، `home.php` (برگهٔ نوشته‌ها) و `search.php` (نتایج جستجو) که هر سه از یک بدنهٔ مشترک استفاده می‌کنند:
  - `template-parts/ee-archive-main.php` — سربرگ بایگانی (عنوان، توضیح، تعداد نوشته)، چیپ‌های فیلتر دسته‌بندی با شمارنده، شبکهٔ کارت نوشته‌ها (تصویر، دسته، چکیده، تاریخ، زمان مطالعه، بازدید، دیدگاه) و صفحه‌بندی `paginate_links` + حالت «موردی یافت نشد» با فرم جستجو.
- **پوستهٔ مشترک `ee-*`**: `template-parts/ee-head.php`، `ee-header.php` و `ee-footer.php` از `front-page.php` استخراج شدند تا صفحهٔ اصلی، تک‌نوشته و آرشیو هدر/فوتر واحد داشته باشند (خروجی صفحهٔ اصلی بدون تغییر؛ فقط لینک‌های `#` به مقصدهای واقعی و آیتم فعال منو داینامیک شد).
  - `assets/css/newhome/ee-shell.css` — توکن‌های طراحی، ریست، هدر/ناوبری، فوتر، نوار موبایل و ویجت‌های سایدبار (از `evented-home.css` جدا شد؛ بدون هیچ سلکتور مشترک).
  - `assets/css/single-post.css` و `assets/css/archive-post.css` (که پیش‌تر خالی/ناموجود بودند و ۴۰۴ می‌دادند).

### Changed

- **Enqueue**: بارگذاری پوستهٔ جدید از `functions.php` با شرط `evented_is_ee_view()` و اولویت ۲۰ انجام می‌شود؛ شاخه‌های `archive-post.*` و `single-post.*` در `assets/assets_functions.php` حذف شدند (فایل‌های JS آن‌ها وجود نداشت) و `is_home()` از شاخهٔ `front-page.css` بیرون آمد.
- **JS صفحهٔ اصلی** (`assets/js/newhome/evented-home.js`): افزودن کپی لینک اشتراک‌گذاری با `navigator.clipboard` (و مسیر جایگزین `execCommand`) + Toast.

### Added (پیشین)

- **مدیر اسلایدر هیروی صفحهٔ اصلی** (زیرمنوی «تنظیمات قالب» در پیشخوان): افزودن/حذف چند اسلاید با انتخاب تصویر از کتابخانهٔ رسانه (wp.media)، به‌همراه برچسب، عنوان، توضیح و لینک برای هر اسلاید. داده‌ها در آپشن `evented_home_slides` ذخیره می‌شوند؛ بدون اسلاید تنظیم‌شده، هیرو مثل قبل آخرین دورهٔ ویژه را نمایش می‌دهد.
  - `inc/theme_settings.php` — منطق ذخیره/خواندن + صفحهٔ پیشخوان (`evented-theme-settings`).
  - `assets/css/admin/theme-settings.css` و `assets/js/admin/theme-settings.js` — استایل و رفتار ردیف‌های اسلاید در پیشخوان.
  - اسلایدر سمت سایت در `front-page.php` با استایل/رفتار در `assets/css/newhome/evented-home.css` و `assets/js/newhome/evented-home.js` (پیکان‌ها، نقطه‌ها، چرخش خودکار ۶ ثانیه‌ای، توقف با هاور، احترام به `prefers-reduced-motion`).
- افزودن مستندات استاندارد و کامل پروژه به ریشهٔ قالب:
  - `README.md` — نقطهٔ ورود: هدف، پشتهٔ فناوری، ساختار، نصب، پیکربندی، تست‌ها.
  - `ARCHITECTURE.md` — معماری، جریان داده و دیاگرام‌ها (Mermaid)، نقشهٔ داده و قراردادهای AJAX.
  - `CONTRIBUTING.md` — استانداردهای توسعه، قرارداد Conventional Commits، فرایند PR.
  - `AI.md` — قوانین و محدودیت‌های مدل‌های AI هنگام کار روی کدبیس.
  - `ROADMAP.md` — نقشهٔ راه نسخه‌ها.
  - `TODO.md` — تسک‌های معلق و باگ‌های شناخته‌شده.
  - `TECH_DEBT.md` — فهرست بدهی فنی و نقاط نیازمند ریفکتور.
- نگاشت کامل «صفحات ↔ تمپلیت‌ها»، «کلیدهای متا/سشن/جدول سفارشی» و «اکشن‌های AJAX» در مستندات ثبت شد.

### Changed

- جایگزینی مستندات شماره‌دار قبلی (`docs/00-overview-and-index.md` تا `docs/10-known-issues-and-todo.md`، که سپس به ریشهٔ قالب منتقل شده بودند) با مجموعهٔ استاندارد نام‌گذاری‌شده (`README.md`، `ARCHITECTURE.md`، …) مطابق قراردادهای رایج مخازن؛ محتوای فنی حفظ و بازنویسی/بازچینش شد.

### Changed

- **برندینگ «edu falnic»/«فالنیک» → «evented-edu»**: پیشوند شناسه‌ها `falnic_*` → `evented_*`، کلاس `FalnicAuthHandler` → `EventedAuthHandler`، اکشن‌های AJAX و nonce (`falnic_nonce` → `evented_nonce`)، کلیدهای سشن (`fl_otp*`/`fl_mobile` → `evented_otp*`/`evented_mobile`)، نام فایل‌های فونت/لوگو (`evented-edu-font.woff2`، `evented-edu-logo*`)، هدر `style.css` و متن‌های فارسی UI.
- **حذف URL های هاردکد دامنه** (assets و ریدایرکت‌ها) و جایگزینی با `PATH_DIR_URL` / `get_template_directory_uri()` / `home_url()` / `wp_login_url()` — قالب روی هر دامنه/مسیری قابل اجراست.
- ارجاع جدول سفارشی تراکنش `{wp}_falnic_transactions` → `{wp}_evented_transactions` (⚠️ migration — پایین را ببینید).
- `page-panel.php`: کاربرِ لاگین‌شده به `/panel/my-courses` هدایت می‌شود (جای صفحهٔ خالی).
- صفحات پنل: گارد لاگین برای `/panel/profile` اضافه شد؛ ریدایرکت‌های گارد بدون `.php` و از طریق `wp_login_url()` با `redirect_to` شدند؛ صفحهٔ گواهینامه‌ها دیگر به اشتباه به payments نمی‌فرستد.
- `index.php`: از اسکریپت دیباگ به قالب fallback استاندارد (فهرست/بایگانی) تبدیل شد.
- تنظیمات حساب: ایمیلِ placeholder تولیدشدهٔ ثبت‌نام موبایلی دیگر با مقدار جعلی نمایش داده نمی‌شود (فیلد خالی + placeholder واقعی).
- placeholder ایمیل کاربران جدید: `{mobile}@evented-edu.user`.

### Fixed

- **ثبت‌نام نهایی**: لاگین خودکار پس از `wp_set_password()` اصلاح شد (این تابع void برمی‌گرداند؛ حالا از `$user->ID` استفاده می‌شود).
- **OTP**: کد باید ابتدا درخواست شده باشد؛ انقضای ۱۰ دقیقه؛ تطبیق شمارهٔ موبایل با کد؛ سقف ۵ تلاش اشتباه (پس از آن کد باطل می‌شود)؛ `rand()` → `wp_rand()`؛ شمارندهٔ تلاش هنگام ارسال کد جدید ریست می‌شود.
- **ثبت‌نام/نام**: `handle_save_user_register_name` فقط با OTP تأییدشدهٔ همان شماره حساب می‌سازد و آرگومان نامعتبر `full_name` حذف شد.
- حذف urlencode دستیِ اضافه در `redirect_login_url` (double-encode بودن `redirect_to`).
- فراخوانی `learndash_user_get_enrolled_courses()` در نبود LearnDash دیگر Fatal نمی‌سازد (گارد + fallback خالی).
- `author.php`: متغیر `$facebook` تعریف شد؛ منطق ستاره‌های امتیاز در RTL اصلاح شد (`assets/js/author.js`).
- حذف `</div>` اضافه در هیروی `front-page.php`.

### Security

- ورود با رمز: محدودیت ۵ تلاش ناموفق در ۱۵ دقیقه برای هر شماره (transient) — پس از آن تا ۱۵ دقیقه مسدود است.
- جلوگیری از تزریق در inline JS: `redirect_to` با `wp_validate_redirect` + `wp_json_encode` مقداردهی شد (قبلاً `$_GET['redirect_to']` خام چاپ می‌شد).
- ارسال OTP از طریق wrapper محافظت‌شدهٔ `evented_send_otp_with_bale()` — اگر سرویس بله در دسترس نباشد، به‌جای Fatal فقط لاگ می‌شود؛ نام قدیمی `falnic_send_otp_with_bale()` هم به‌عنوان fallback پشتیبانی می‌شود.

### Removed

- اکشن‌های AJAX بدون هندلر `falnic_submit_cta`/`evented_submit_cta` (متدی وجود نداشت).
- کد مردهٔ `$crm_guids` در `EventedAuthHandler`.
- کامنت اسکریپت particle (کانفیگ مرده) در `front-page.php`.

> ⚠️ **Migration (لازمالاجرا):** اگر در نصب لایو جدول تراکنش‌ها `{wp}_falnic_transactions` است، پیش از استقرار آن را تغییر نام دهید
> (`ALTER TABLE wp_falnic_transactions RENAME TO wp_evented_transactions;`). نشست‌های OTP و nonce های قبلی با نام‌های قدیمی نامعتبر می‌شوند (طراحی عمدیِ برندینگ).

> ℹ️ **یادداشت تاریخچه:** ریپازیتوری عمومی پیش از این مرحله فقط شامل یک کامیت اولیه («a»، کامیت `875fd77`) بوده و نسخه‌ای تگ/منتشر نشده است؛ به همین دلیل بخش `[Unreleased]` نقطهٔ شروع ثبت تغییرات است. نسخهٔ فعلی قالب مطابق هدر `style.css` برابر `1.0.0` است. از این پس **هر تغییر کد** باید یک ورودی در این فایل ایجاد کند (بخش ۵ از `CONTRIBUTING.md`).

---

## قالب استاندارد برای نسخه‌های آینده

```markdown
## [1.1.0] - 2026-09-08

### Added
- توضیح قابلیت جدید.

### Changed
- توضیح تغییر رفتار موجود.

### Deprecated
- توضیح قابلیت در شرف حذف.

### Removed
- توضیح قابلیت حذف‌شده.

### Fixed
- توضیح باگ رفع‌شده.

### Security
- توضیح آسیب‌پذیری رفع‌شده.
```
