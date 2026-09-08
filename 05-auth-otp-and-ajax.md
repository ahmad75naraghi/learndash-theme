# ۰۵ — جریان احراز هویت (OTP / رمز عبور) و اکشن‌های AJAX

همهٔ منطق در `inc/login.php` داخل کلاس `FalnicAuthHandler` است که با `add_action('init', …)` نمونه‌سازی می‌شود.
فرانت‌اند آن صفحهٔ `/login` است (`page-login.php`) با اسکریپت inline که با `fetch()` به `admin-ajax.php` وصل می‌شود.

> Nonce مشترک همهٔ درخواست‌ها: `falnic_nonce` → فیلد `nonce`.
> اکشن‌های بخش احراز هویت (و `submit_course_review`) هم برای لاگین (`wp_ajax_…`) و هم مهمان (`wp_ajax_nopriv_…`) ثبت شده‌اند. اکشن‌های `custom_mark_lesson_complete`، `toggle_course_wishlist`، `save_user_profile` و `save_account_settings` فقط مخصوص کاربران لاگین‌شده‌اند (فقط `wp_ajax_…`).

## ۱. نمودار جریان (Flow)

```
گام موبایل: /login
   │  شماره ۰۹XXXXXXXXX (فقط عدد، تبدیل ارقام فارسی→انگلیسی در سرور)
   ▼
falnic_check_mobile_and_send_otp
   ├─ کاربر با username=موبایل وجود دارد؟ → status:"login" → نمایش «ورود با رمز عبور»
   └─ وجود ندارد → ارسال OTP (SMS + بله) → status:"register" → نمایش گام OTP
        └─ ترنزینت otp_limit_… = 60s (Rate limit)

گام OTP (۵ خانه):  falnic_verify_otp
   ├─ مقایسه با $_SESSION['fl_otp']
   ├─ purpose == reset_password →  علامت fl_otp_verified_for=reset_password → گام «رمز جدید»
   ├─ کاربر با موبایل پیدا نشد → fl_otp_verified_for=register → گام «نام/نام خانوادگی»
   └─ کاربر پیدا شد (ورود با OTP) → کوکی auth ست → لاگین → ریدایرکت

ثبت‌نام کامل:
   گام نام → save_user_register_name
        └─ (اگر کاربر ساخته نشده) wp_create_user(login=mobile, pass=random, email=mobile@falnic.user)
             + first_name/last_name/display_name
   گام رمز → falnic_register_user
        └─ wp_set_password روی کاربرِ موجود + لاگین خودکار

ورود با رمز (از گام موبایل وقتی کاربر موجود است):
   falnic_login_user  → بررسی wp_check_password → لاگین

بازیابی رمز:
   از گام رمز → falnic_send_otp(purpose=reset_password) → OTP → falnic_verify_otp
        → گام رمز جدید → falnic_reset_password → لاگین خودکار
```

## ۲. جدول اکشن‌های AJAX (همه‌جا nonce: `falnic_nonce`)

| اکشن | متد سرور | ورودی‌ها | خروجی success | نکته |
|---|---|---|---|---|
| `falnic_check_mobile_and_send_otp` | `handle_check_mobile_and_send_otp` | `mobile` | `{status:"login"}` یا `{status:"register", message}` | اگر کاربر جدید باشد SMS+بله می‌فرستد؛ اگر موجود باشد فقط status می‌دهد |
| `falnic_send_otp` | `handle_send_otp` | `mobile`, `purpose` | `{message, purpose}` | purpose مجاز: register/login_otp/reset_password/resend_otp؛ اعتبارسنجی وجود/نبود کاربر بر اساس purpose؛ محدودیت ۶۰ثانیه |
| `falnic_verify_otp` | `handle_verify_otp` | `otp`, `phone` | بسته به سناریو: `{result:"reset_password"}` / `{is_new_user:true}` / `{result:"login", nonce}` | مقایسه با سشن؛ لاگین خودکار برای کاربر موجود؛ nonce تازه برمی‌گرداند |
| `save_user_register_name` | `handle_save_user_register_name` | `mobile`,`firstname`,`lastname` | `{message}` | ساخت کاربر با ایمیل `{mobile}@falnic.user`؛ اعتبارسنجی فقط حروف فارسی نام‌ها؛ ⚠️ بررسی نمی‌کند OTP تایید شده باشد |
| `falnic_register_user` | `handle_register_user` | `phone`,`password`,`confirmPassword` | `{result:"login", nonce, message}` | فقط برای کاربر «از قبل ساخته‌شده» (بعد از گام نام)؛ شرط سشن `fl_otp_verified` |
| `falnic_login_user` | `handle_login_user` | `mobile`,`password` | `{result:"login", redirect:home_url()}` | چک رمز با `wp_check_password` |
| `falnic_reset_password` | `handle_reset_password` | `phone`,`password`,`confirmPassword` | `{result:"login", nonce, message}` | نیازمند `fl_otp_verified_for==='reset_password'` و تطابق `phone` با `fl_mobile` |
| `falnic_submit_cta` | ⚠️ `handle_cta_submit` **وجود ندارد** | — | — | فقط hook ثبت شده؛ اگر صدا زده شود Fatal Error (فایل ۱۰) |

> اکشن‌های غیرمرتبط با auth در `inc/ajax_functions.php` (جدول زیر):

| اکشن | nonce (فیلد `security`) | ورودی | خروجی |
|---|---|---|---|
| `submit_course_review` | `course_review_nonce` | `course_id`,`rating`,`content` | ثبت کامنت + `review_rating`؛ نیاز به لاگین؛ `comment_approved=0` |
| `custom_mark_lesson_complete` | `mark_complete_nonce_{lesson_id}` | `lesson_id`,`course_id` | تکمیل درس + دادهٔ پیشرفت (شرح در فایل ۰۸) |
| `toggle_course_wishlist` | `wishlist_nonce` | `course_id` | `{status:'added'|'removed'}` |
| `save_user_profile` | `profile_nonce_action` | `first_name_fa`, `last_name_fa`, `first_name_en`, `last_name_en`, `gender`, `birth_date` | پیام موفقیت |
| `save_account_settings` | `settings_nonce_action` | `user_email`,`user_password`,`user_phone` | تغییر ایمیل/رمز (`wp_update_user`) و `billing_phone` |

## ۳. تابع ارسال پیامک (`inc/sms.php`)

```php
send_pattern_sms($mobile, $code): bool
```
- SOAP به `https://api.payamak-panel.com/post/Send.asmx?wsdl` با `SendByBaseNumber`
- اعتبارنامهٔ هاردکد: username=`iranhp` — template/bodyId=`315445`
- اگر `SendByBaseNumberResult > 0` → true

## ۴. پیام‌رسان بله (Bale)

```php
falnic_send_otp_with_bale($mobile, $otp);   // در ۲ نقطه از login.php صدا زده می‌شود
```
- ⚠️ این تابع در قالب تعریف نشده؛ باید به‌صورت بیرونی (mu-plugin/افزونه) وجود داشته باشد وگرنه ارسال کد با Fatal Error مواجه می‌شود.

## ۵. فیلترهای auth

| هوک | رفتار |
|---|---|
| `login_url` | هر `wp_login_url()` به `home_url('/login/')` (با حفظ `redirect_to`) |
| `logout_redirect` | بعد از خروج → `home_url()` |
| `login_redirect` | subscriber → `home_url('/panel')` |

## ۶. نکات امنیتی/آسیب‌پذیری این بخش (خلاصه؛ جزئیات در فایل ۱۰)

- `$_GET['redirect_to']` مستقیم داخل JS صفحهٔ لاگین چاپ می‌شود → XSS (باید `esc_url` + `esc_js`).
- سشن شروع‌شده داخل قالب (بدون نام‌گذاری/پاکسازی سفت‌وسخت).
- اعتبارنامهٔ SMS در سورس.
- `handle_save_user_register_name` بدون بررسی OTP/سشن می‌تواند کاربر بسازد (حملات ساخت حساب انبوه).
- `falnic_register_user`: `wp_set_password()` مقدار user_id برنمی‌گرداند؛ کدِ بعدی `get_user_by('id', null)` را صدا می‌زند و لاگین‌اتوماتیک در عمل اجرا نمی‌شود (ریسک؛ نیاز به تست دقیق).
