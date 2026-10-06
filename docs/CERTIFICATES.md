# گواهینامه‌های فارسی LearnDash

این سند معماری، بسته‌بندی، تست و عیب‌یابی گواهینامهٔ سفارشی قالب را توضیح می‌دهد. پیاده‌سازی اصلی در `inc/certificates.php` است.

## جریان تولید

1. LearnDash کاربر را به نوشتهٔ `sfwd-certificates` با پارامترهای آزمون و زمان تلاش هدایت می‌کند.
2. قالب ورود کاربر، مالکیت درخواست و وجود تلاش قبول‌شده در `_sfwd-quizzes` را کنترل می‌کند. فقط مدیر دارای `manage_options` می‌تواند گواهی کاربر دیگر را ببیند.
3. نام از `first_last_name` و سپس `display_name`/نام و نام خانوادگی، کد ملی از `national_code`، دوره از تلاش LearnDash و تاریخ از timestamp تلاش خوانده می‌شود.
4. پس‌زمینه ابتدا از تصویر شاخص نوشتهٔ گواهی و سپس از فایل قدیمی `uploads/2022/05/lic2.jpg` انتخاب می‌شود.
5. `shamiim_cert_fields()` محل تاریخ، نام، کد ملی و دوره را با واحد میلی‌متر روی A4 افقی تعیین می‌کند.

## مسیر اصلی PDF

- mPDF و وابستگی‌های production در بستهٔ رسمی زیر `inc/lib/` هستند.
- autoloader ابتدا در `inc/lib` قالب فعال/والد و سپس برای سازگاری با نصب‌های قدیمی در `vendor` جستجو می‌شود.
- فونت PDF باید TTF باشد. فایل‌های مورد استفاده:
  - `assets/fonts/Vazirmatn-Regular.ttf`
  - `assets/fonts/Vazirmatn-Bold.ttf`
- خانوادهٔ داخلی `eventedcert` عمداً با نامی تازه ثبت شده تا cache متریک فونت‌های قدیمی Vazir/Shabnam دوباره استفاده نشود.
- `useOTL = 0xFF`، `useKashida = 75`، جهت RTL و `lang="fa"` برای شکل‌دهی صحیح حروف فارسی فعال‌اند.
- پوشهٔ موقت mPDF در `uploads/mpdf-tmp` ساخته و از دسترسی مستقیم محافظت می‌شود.

## fallback بدون کتابخانه

اگر هاست فایل‌های Composer را حذف یا بارگذاری آن‌ها را مسدود کند، درخواست متوقف نمی‌شود. قالب همان داده و مختصات را در یک سند HTML مستقل با مشخصات زیر نمایش می‌دهد:

- A4 افقی با واحد میلی‌متر؛
- پس‌زمینهٔ گواهی به‌صورت data URI؛
- فونت محلی Vazirmatn WOFF2؛
- دکمهٔ «چاپ / ذخیره PDF»؛
- CSS مخصوص چاپ.

عبارت خطای قدیمی «کتابخانه mPDF بارگذاری نشده است» نباید در کد یا خروجی نسخهٔ 2.4.4 به بعد وجود داشته باشد.

## چرا mPDF داخل Git نیست؟

`vendor/` خروجی Composer و خارج از Git است. در زمان ساخت release:

1. `composer update/install --no-dev` وابستگی سازگار با PHP 7.4 را می‌سازد.
2. `bin/build-theme-release.sh` فایل‌های track‌شده را در staging استخراج می‌کند.
3. dependency production به `evented-edu/inc/lib/` منتقل می‌شود؛ نام `vendor` عمداً در artifact استفاده نمی‌شود چون بعضی فیلترهای امنیتی هاست آن را حذف می‌کنند.
4. فونت‌های عمومی و بلااستفادهٔ حجیم mPDF حذف می‌شوند؛ دو TTF فارسی قالب باقی می‌مانند.
5. تست از خود staging نهایی PDF فارسی واقعی می‌سازد.
6. فقط سپس `evented-edu.zip` تولید می‌شود.

بنابراین فایل خودکار GitHub با نام **Source code.zip** بستهٔ نصب production نیست. فقط asset دقیق `evented-edu.zip` از صفحهٔ Release نصب شود.

## تست‌ها

```bash
composer install --no-dev --classmap-authoritative
php tests/certificate-font-test.php
python3 tests/static-audit.py
bash bin/build-theme-release.sh /tmp/evented-edu.zip

unzip -l /tmp/evented-edu.zip | grep -E \
  'inc/lib/autoload.php|inc/lib/mpdf/mpdf/src/Mpdf.php|Vazirmatn-(Regular|Bold).ttf'
```

پذیرش نهایی روی staging:

- نامی شامل حروف «پ ژ چ گ ی ک» بدون `؟` چاپ شود؛
- پس‌زمینه و چهار فیلد در مختصات مورد انتظار باشند؛
- PDF روی PHP 7.4، 8.1 و 8.3 ساخته شود؛
- با تغییر موقت نام `inc/lib`، fallback HTML قابل چاپ باز شود؛
- تغییر شناسهٔ کاربر در URL برای کاربر عادی پاسخ 403 بدهد.

## عیب‌یابی استقرار

1. نسخهٔ فعال در «نمایش ← پوسته‌ها» باید 2.5.4 یا بالاتر باشد.
2. وجود `wp-content/themes/evented-edu/inc/lib/autoload.php` بررسی شود.
3. از نصب `Source code.zip` خودداری شود.
4. پس از جایگزینی فایل‌های قالب، page cache، object cache، CDN و PHP OPcache پاک شوند.
5. اگر mPDF در دسترس نباشد، fallback چاپی باید باز شود؛ مشاهدهٔ پیام خطای قدیمی یعنی فایل PHP نسخهٔ قدیمی هنوز از cache یا پوشهٔ دیگری اجرا می‌شود.

حذف و نصب مجدد پوشهٔ قالب، داده‌های دوره، کاربران، تلاش‌های آزمون یا تنظیمات ذخیره‌شده در دیتابیس را حذف نمی‌کند؛ بااین‌حال پیش از استقرار production همیشه backup گرفته شود.
