# موتور تاریخ شمسی قالب

موتور داخلی `inc/jalali.php` رابط سایت را شمسی می‌کند اما تاریخ‌های هستهٔ WordPress، LearnDash، REST، Cron و دیتابیس را استاندارد میلادی/UTC نگه می‌دارد. خروجی‌های ISO، feed و SEO نیز عمداً میلادی باقی می‌مانند.

## API سمت PHP

- `evented_jalali_from_gregorian($year, $month, $day)`
- `evented_jalali_to_gregorian($year, $month, $day)`
- `evented_jalali_checkdate($month, $day, $year)`
- `evented_jalali_parse($value)` — پذیرش ارقام فارسی، عربی و لاتین
- `evented_jalali_to_timestamp($value, $end_of_day = false)`
- `evented_jalali_format($timestamp, $format, $persian_digits)`
- `evented_jalali_datetime_attr($timestamp)` — خروجی ISO استاندارد برای `datetime`
- `evented_enqueue_jalali_picker()` — بارگذاری شرطی انتخابگر

توکن‌های اصلی قالب‌بندی: `Y y m n d j F M l D t L z H G h g i s a A U`. کاراکترهای escapeشده با `\` عیناً چاپ می‌شوند.

## API سمت JavaScript

پس از فراخوانی enqueue، شیء `window.EventedJalali` در دسترس است:

- `toGregorian` و `fromGregorian`
- `parse`، `format` و `isValid`
- `isLeapYear` و `monthLength`
- `toLatinDigits` و `toPersianDigits`

## افزودن ورودی شمسی

```html
<input type="text" data-jdp inputmode="numeric" placeholder="۱۴۰۵/۰۱/۰۱">
```

قابلیت‌های اختیاری:

- `data-jalali-time` برای تاریخ‌وزمان
- `data-jalali-min="۱۴۰۰/۰۱/۰۱"` و `data-jalali-max="۱۴۱۰/۱۲/۲۹"`
- `data-jalali-range="start"` و `data-jalali-range="end"` برای دو فیلد بازه در یک فرم

Loader دسترس‌پذیری، ارقام فارسی، اعتبارسنجی تاریخ/کبیسه، min/max، بازه و فیلدهای اضافه‌شده با AJAX را مدیریت می‌کند.

## قواعد ذخیره‌سازی

1. تاریخ‌های کامل عملیاتی را با `evented_jalali_to_timestamp()` به timestamp تبدیل کنید و میلادی/UTC ذخیره کنید.
2. تاریخ هستهٔ WordPress یا LearnDash را شمسی در دیتابیس ننویسید.
3. خروجی ماشین‌خوان (`c`، `r`، REST، feed و schema) را میلادی نگه دارید.
4. برای متن انسانی از `evented_jalali_format()` و برای attribute استاندارد از `evented_jalali_datetime_attr()` استفاده کنید.
