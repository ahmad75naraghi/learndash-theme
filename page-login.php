<?php
// اعتبارسنجی redirect_to یک‌بار در ابتدای صفحه (جلوگیری از تزریق در inline JS)
$evented_redirect_to = isset($_GET['redirect_to'])
    ? (string) wp_validate_redirect(wp_unslash($_GET['redirect_to']), home_url('/'))
    : home_url('/');

// اگر مقصد، پیشخوان وردپرس است، این صفحه (ورود با موبایل) به درد نمی‌خورد؛ به فرم استاندارد برو.
$evented_redirect_path = (string) wp_parse_url($evented_redirect_to, PHP_URL_PATH);
if (false !== strpos($evented_redirect_path, '/wp-admin')) {
    wp_safe_redirect(add_query_arg('redirect_to', rawurlencode($evented_redirect_to), site_url('wp-login.php')));
    exit;
}

// کاربر لاگین‌شده نیازی به این صفحه ندارد
if (is_user_logged_in()) {
    wp_safe_redirect(home_url('/panel'));
    exit;
}

/* آدرس فرم استاندارد وردپرس برای مدیران/نویسندگان (بدون عبور از فیلتر login_url) */
$evented_admin_login_url = site_url('wp-login.php?admin=1');
?>
<!DOCTYPE html>

<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود / ثبت نام</title>
    <style>
        @font-face {
            font-family: 'دانا';
            font-display: swap;
            src: url('<?= get_template_directory_uri(); ?>/assets/fonts/evented-edu-font.woff2') format('woff2');
        }

        :root {
            --primary-color: #fca326;
            --primary-disabled: #e2e2e2;
            --text-color: #333;
            --gray-light: #f9f9f9;
            --gray-border: #e0e0e0;
            --error-color: #ED2E2E;
            --success-color: #00BA88;
            --font-main: 'دانا', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'دانا';
            font-feature-settings: "ss02";
        }

        body {
            background-color: #f5f6f8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .auth-container {
            background: #ffffff;
            width: 960px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            display: flex;
            overflow: hidden;
        }

        /* بخش سمت چپ - گرافیکی هماهنگ با عکس */
        .auth-sidebar {
            flex: 1;
            background-color: #55b3d0;
            position: relative;
            color: white;
            overflow: hidden;
        }

        .sidebar-overlay {}

        .ui-text {
            font-size: 80px;
            font-weight: bold;
            opacity: 0.3;
            position: absolute;
            top: 20px;
            right: 40px;
        }

        .elem-menu {
            top: 120px;
            right: 20px;
            font-size: 24px;
            color: #333;
            padding: 5px 15px !important;
        }

        .elem-palette {
            top: 10px;
            left: 80px;
            background: transparent !important;
            box-shadow: none !important;
            font-size: 70px;
            transform: rotate(-15deg);
        }

        .elem-search {
            top: 130px;
            left: 40px;
            width: 140px;
            height: 24px;
            border-radius: 20px !important;
            display: flex;
            align-items: center;
            color: #888;
            font-size: 12px;
        }

        .elem-pencil {
            top: 180px;
            left: 100px;
            background: transparent !important;
            box-shadow: none !important;
            font-size: 50px;
            transform: rotate(-25deg);
        }

        .elem-close {
            top: 190px;
            left: 30px;
            background: #fff;
            border-radius: 4px !important;
            padding: 2px 6px !important;
            font-size: 12px;
            color: #555;
        }

        .elem-image {
            bottom: 60px;
            left: 80px;
            font-size: 40px;
            padding: 5px 15px !important;
            color: #333;
        }

        .desk-surface {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: #e1edf0;
            border-top: 3px solid #cbdde2;
        }

        .coffee-cup {
            position: absolute;
            bottom: 0;
            left: 80px;
            width: 45px;
            height: 70px;
            background: #fff;
            border-radius: 4px 4px 20px 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .coffee-cup::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 14px;
            background: #333;
            border-radius: 4px 4px 0 0;
        }

        /* بخش سمت راست - فرم‌ها */
        .auth-content {
            flex: 1.2;
            padding: 50px 60px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            position: relative;
        }

        .brand-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
        }

        .logo-placeholder {
            text-align: center;
        }

        .logo-text {
            font-size: 13px;
            color: #004b93;
            font-weight: bold;
            margin-top: -5px;
            letter-spacing: 0.5px;
        }

        /* مدیریت وضعیت نمایش مراحل */
        .auth-step {
            display: none;
            animation: fadeIn 0.4s ease-in-out forwards;
        }

        .auth-step.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            font-size: 20px;
            color: #000;
            text-align: center;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .step-desc {
            font-size: 16px;
            color: #000;
            text-align: center;
            margin-bottom: 25px;
        }

        /* فیلدهای ورودی */
        .input-group {
            position: relative;
            margin-bottom: 24px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            outline: none;
            transition: all 0.2s;
            text-align: right;
        }

        input[id="mobile-input"] {
            text-align: right;
        }

        input:focus {
            border-color: #b0b0b0;
        }

        /* فیلدهای پسورد چشمی دار */
        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-left: 45px;
            text-align: right;
            direction: ltr;
        }

        .toggle-password {
            position: absolute;
            left: 15px;
            top: 35%;
            cursor: pointer;
            color: #888;
            user-select: none;
        }

        /* متن خطا */
        .error-message {
            display: none;
            background-color: #fff5f5;
            border: 1px solid #ffccd0;
            color: var(--error-color);
            padding: 12px;
            border-radius: 8px;
            font-size: 12px;
            margin-top: 15px;
            align-items: center;
            gap: 8px;
            border-right: 5px solid var(--error-color);
        }

        .error-icon {
            background: var(--error-color);
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
        }

        /* دکمه اصلی */
        .btn-submit {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 10px;
            background-color: var(--primary-color);
            color: #000;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-submit:disabled {
            background-color: var(--primary-disabled);
            color: #fff;
            cursor: not-allowed;
        }

        .terms-text {
            font-size: 12px;
            color: #000;
            text-align: right;
            margin-top: 15px;
        }

        .terms-text a {
            color: var(--primary-color);
            text-decoration: none;
        }

        /* استایل بخش کدهای تایید OTP */
        .otp-inputs-container {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .otp-field {
            width: 46px !important;
            height: 46px !important;
            border: 1px solid var(--gray-border);
            border-radius: 10px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            outline: none;
        }

        .otp-field:focus {
            border-color: var(--primary-color);
        }

        .timer-container {
            text-align: center;
            font-size: 16px;
            color: #000;
            font-weight: 400;
            margin-bottom: 17px;
        }

        .btn-resend-otp {
            color: #FFA200;
            background: none;
            border: none;
            cursor: pointer;
            margin: 0 auto 16px;
            font-size: 16px;
        }

        #timer-clock {
            color: var(--primary-color);
            font-weight: bold;
        }

        /* لینک های کمکی انتهای فرم ها */
        .alt-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            margin-top: 25px;
        }

        .alt-actions a {
            font-size: 14px;
            font-weight: 600;
            color: #000;
            text-decoration: none;
            transition: color 0.2s;
            display: flex;
            flex-flow: row;
            align-items: center;
            gap: 8px;
        }

        .alt-actions a:hover {
            color: var(--primary-color);
        }

        /* بخش امنیت پسورد و خط ها (مرحله ۴) */
        .password-strength-container {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            margin-top: 19px;
            margin-bottom: 19px;
        }

        .strength-label {
            font-size: 12px;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .strength-label.weak {
            color: var(--error-color);
        }

        .strength-label.medium {
            color: #F4B740;
        }

        .strength-label.strong {
            color: var(--success-color);
        }

        .strength-bars {
            display: flex;
            width: 100%;
            gap: 6px;
        }

        .bar {
            flex: 1;
            height: 5px;
            background-color: var(--primary-disabled);
            border-radius: 20px;
            transition: background-color 0.3s;
        }

        /* لیست قوانین پسورد */
        .password-rules {
            list-style: none;
            margin-bottom: 25px;
            padding-right: 5px;
        }

        .password-rules li {
            font-size: 12px;
            color: #000;
            margin-bottom: 6px;
            position: relative;
            padding-right: 15px;
        }

        .password-rules li::before {
            content: '•';
            position: absolute;
            right: 0;
            color: #000;
            font-size: 20px;
            top: -9px;
        }

        .password-rules li.valid {
            color: var(--success-color);
        }

        .password-rules li.valid::before {
            color: var(--success-color);
        }

        .toast-wrapper {
            display: none;
            flex-direction: column;
            gap: 20px;
            width: 100%;
            max-width: 550px;
            padding: 20px;
            margin-top: auto;
        }

        .toast-wrapper:has(.active) {
            display: flex;
        }

        /* استایل کلی و مشترک کارت‌ها */
        .toast-card {
            display: none;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            min-height: 46px;
            border: 1px solid rgba(0, 0, 0, 0.04);
            position: relative;
        }

        .toast-card.success.active, .toast-card.error.active {
            display: flex;
        }

        /* بخش محتوای متنی و آیکون */
        .toast-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            /* برای چسبیدن متن و آیکون به سمت راست مطابق عکس */
            padding: 15px 12px;
            gap: 8px;
        }

        .toast-text {
            font-size: 12px;
            font-weight: 400;
        }

        .toast-icon {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* نوار رنگی عمودی لبه سمت راست */
        .toast-side-bar {
            width: 12px;
            height: 100%;
        }


        /* ---------------------------------
        حالت موفقیت (Success - رنگ سبز)
        --------------------------------- */
        .toast-card.success .toast-text {
            color: #00BA88;
        }

        .toast-card.success .toast-icon {
            color: #10A853;
        }

        .toast-card.success .toast-side-bar {
            background-color: #00BA88;
        }


        /* ---------------------------------
        حالت خطا (Error - رنگ قرمز)
        --------------------------------- */
        .toast-card.error .toast-text {
            color: #ED2E2E;
        }

        .toast-card.error .toast-icon {
            color: #DC2626;
        }

        .toast-card.error .toast-side-bar {
            background-color: #ED2E2E;
        }

        .show-password + span::after {
            content: "|";
            width: 18px;
            height: 18px;
            position: absolute;
            left: 5px;
            color: #000;
            text-align: center;
            font-size: 25px;
            top: -7px;
            transform: rotate(42deg);
        }
        @media (max-width: 768px) {
            .auth-sidebar{
                display: none;
            }
        }
        .admin-login-link { margin-top: 10px; }
        .admin-login-link a { color: #94a3b8; font-size: 12px; }
        .admin-login-link a:hover { color: var(--primary-color); }
    </style>
</head>

<body>

    <div class="auth-container">

        <div class="auth-content">
            <div class="brand-logo">
                <div class="logo-placeholder">
                    <img src="<?= get_template_directory_uri(); ?>/assets/img/front-page/evented-edu-logo-login.png" />
                </div>
            </div>

            <div class="auth-step active" id="step-mobile">
                <h2>ورود / ثبت نام</h2>
                <p class="step-desc">لطفاً شماره موبایل خود را وارد کنید</p>

                <div class="input-group">
                    <input type="tel" inputmode="numeric" autocomplete="tel" class="just_number" id="mobile-input" placeholder="09121234567" maxlength="11" dir="ltr">
                </div>

                <button class="btn-submit" id="btn-to-otp" disabled>تایید و ادامه</button>
                <p class="terms-text">ورود شما به معنی پذیرش <a href="<?php echo esc_url((function_exists('evented_opt') && evented_opt('terms_url', '')) ? evented_opt('terms_url') : home_url('/terms/')); ?>">قوانین و مقررات</a> <?php echo esc_html(get_bloginfo('name')); ?> است</p>
                <p class="terms-text admin-login-link"><a href="<?php echo esc_url($evented_admin_login_url); ?>">ورود مدیران و نویسندگان (نام کاربری و رمز)</a></p>
            </div>

            <div class="auth-step" id="step-password-option">
                <h2>ورود با رمز عبور</h2>
                <p class="step-desc">رمز عبور خود را وارد کنید</p>

                <div class="input-group password-wrapper">
                    <input type="password" id="login-password" placeholder="رمز عبور">
                    <span class="toggle-password" onclick="togglePasswordVisibility('login-password')">
                        <svg width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.8886 7.64935C11.8886 9.29932 10.5553 10.6327 8.90521 10.6327C7.25521 10.6327 5.92188 9.29932 5.92188 7.64935C5.92188 5.99935 7.25521 4.66602 8.90521 4.66602C10.5553 4.66602 11.8886 5.99935 11.8886 7.64935Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M8.90417 14.5417C11.8458 14.5417 14.5876 12.8083 16.4958 9.80831C17.2458 8.63331 17.2458 6.65833 16.4958 5.48333C14.5876 2.48333 11.8458 0.75 8.90417 0.75C5.9625 0.75 3.22083 2.48333 1.3125 5.48333C0.5625 6.65833 0.5625 8.63331 1.3125 9.80831C3.22083 12.8083 5.9625 14.5417 8.90417 14.5417Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>

                <button class="btn-submit" id="btn-login-submit">ورود</button>

                <div class="alt-actions">
                    <a href="#" id="link-to-otp-direct">
                        ورود با رمز یکبار مصرف
                        <svg width="8" height="16" viewBox="0 0 8 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.82013 15.9418C6.65495 15.9418 6.48977 15.8775 6.35937 15.7395L0.691139 9.74437C-0.23038 8.76973 -0.23038 7.16976 0.691139 6.19513L6.35937 0.199994C6.61148 -0.0666645 7.02877 -0.0666645 7.28089 0.199994C7.533 0.466643 7.533 0.908007 7.28089 1.17466L1.61266 7.16976C1.19537 7.61114 1.19537 8.32836 1.61266 8.76973L7.28089 14.7649C7.533 15.0315 7.533 15.4729 7.28089 15.7395C7.15048 15.8683 6.98531 15.9418 6.82013 15.9418Z" fill="#292D32" />
                        </svg>
                    </a>
                    <a href="#" id="link-to-reset">
                        فراموشی رمز عبور
                        <svg width="8" height="16" viewBox="0 0 8 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.82013 15.9418C6.65495 15.9418 6.48977 15.8775 6.35937 15.7395L0.691139 9.74437C-0.23038 8.76973 -0.23038 7.16976 0.691139 6.19513L6.35937 0.199994C6.61148 -0.0666645 7.02877 -0.0666645 7.28089 0.199994C7.533 0.466643 7.533 0.908007 7.28089 1.17466L1.61266 7.16976C1.19537 7.61114 1.19537 8.32836 1.61266 8.76973L7.28089 14.7649C7.533 15.0315 7.533 15.4729 7.28089 15.7395C7.15048 15.8683 6.98531 15.9418 6.82013 15.9418Z" fill="#292D32" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="auth-step" id="step-otp">
                <h2>کد تایید را وارد کنید</h2>
                <p class="step-desc">کد تایید برای شماره <span id="user-phone-display">09123456789</span> پیامک شد</p>

                <div class="otp-inputs-container" dir="ltr">
                    <input type="text" class="otp-field" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code">
                    <input type="text" class="otp-field" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code">
                    <input type="text" class="otp-field" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code">
                    <input type="text" class="otp-field" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code">
                    <input type="text" class="otp-field" maxlength="1" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code">
                </div>

                <div class="timer-container">
                    <span id="timer-clock">02:00</span>
                    <span id="timer-text">مانده تا دریافت مجدد کد</span>
                </div>

                <button
                    type="button"
                    id="btn-resend-otp"
                    class="btn-resend-otp"
                    style="display:none; margin-bottom:15px;">
                    دریافت مجدد کد
                </button>

                <button class="btn-submit" id="btn-verify-otp">تایید</button>

                <div class="alt-actions">
                    <a href="#" id="link-to-password-direct">
                        ورود با رمزعبور
                        <svg width="8" height="16" viewBox="0 0 8 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.82013 15.9418C6.65495 15.9418 6.48977 15.8775 6.35937 15.7395L0.691139 9.74437C-0.23038 8.76973 -0.23038 7.16976 0.691139 6.19513L6.35937 0.199994C6.61148 -0.0666645 7.02877 -0.0666645 7.28089 0.199994C7.533 0.466643 7.533 0.908007 7.28089 1.17466L1.61266 7.16976C1.19537 7.61114 1.19537 8.32836 1.61266 8.76973L7.28089 14.7649C7.533 15.0315 7.533 15.4729 7.28089 15.7395C7.15048 15.8683 6.98531 15.9418 6.82013 15.9418Z" fill="#292D32" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="auth-step" id="step-name">
                <h2>اطلاعات فردی</h2>
                <p class="step-desc">لطفاً نام و نام خانوادگی خود را وارد کنید</p>

                <div class="input-group">
                    <input type="text" id="first-name" placeholder="نام">
                </div>
                <div class="input-group">
                    <input type="text" id="last-name" placeholder="نام خانوادگی">
                </div>

                <button class="btn-submit" id="btn-save-name" disabled>تایید و ادامه</button>
            </div>

            <div class="auth-step" id="step-new-password">
                <h2>تغییر رمز عبور</h2>
                <p class="step-desc">رمز عبور جدید خود را وارد کنید</p>

                <div class="input-group password-wrapper">
                    <input type="password" id="new-password" placeholder="رمز عبور جدید">
                    <span class="toggle-password" onclick="togglePasswordVisibility('new-password')">
                        <svg width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.8886 7.64935C11.8886 9.29932 10.5553 10.6327 8.90521 10.6327C7.25521 10.6327 5.92188 9.29932 5.92188 7.64935C5.92188 5.99935 7.25521 4.66602 8.90521 4.66602C10.5553 4.66602 11.8886 5.99935 11.8886 7.64935Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M8.90417 14.5417C11.8458 14.5417 14.5876 12.8083 16.4958 9.80831C17.2458 8.63331 17.2458 6.65833 16.4958 5.48333C14.5876 2.48333 11.8458 0.75 8.90417 0.75C5.9625 0.75 3.22083 2.48333 1.3125 5.48333C0.5625 6.65833 0.5625 8.63331 1.3125 9.80831C3.22083 12.8083 5.9625 14.5417 8.90417 14.5417Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>

                <div class="password-strength-container">
                    <span class="strength-label felt" id="strength-text">ضعیف</span>
                    <div class="strength-bars">
                        <div class="bar" id="bar-1"></div>
                        <div class="bar" id="bar-2"></div>
                        <div class="bar" id="bar-3"></div>
                    </div>
                </div>

                <ul class="password-rules">
                    <li id="rule-number" class="invalid">شامل عدد</li>
                    <li id="rule-length" class="invalid">حداقل ۸ حرف</li>
                    <li id="rule-case" class="invalid">شامل یک حرف بزرگ و یک حرف کوچک</li>
                    <li id="rule-special" class="invalid">شامل علامت (@#$%^&*)</li>
                </ul>

                <div class="input-group password-wrapper">
                    <input type="password" id="confirm-password" placeholder="تکرار رمز عبور جدید">
                    <span class="toggle-password" onclick="togglePasswordVisibility('confirm-password')">
                        <svg width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.8886 7.64935C11.8886 9.29932 10.5553 10.6327 8.90521 10.6327C7.25521 10.6327 5.92188 9.29932 5.92188 7.64935C5.92188 5.99935 7.25521 4.66602 8.90521 4.66602C10.5553 4.66602 11.8886 5.99935 11.8886 7.64935Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M8.90417 14.5417C11.8458 14.5417 14.5876 12.8083 16.4958 9.80831C17.2458 8.63331 17.2458 6.65833 16.4958 5.48333C14.5876 2.48333 11.8458 0.75 8.90417 0.75C5.9625 0.75 3.22083 2.48333 1.3125 5.48333C0.5625 6.65833 0.5625 8.63331 1.3125 9.80831C3.22083 12.8083 5.9625 14.5417 8.90417 14.5417Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>

                <button class="btn-submit" id="btn-save-password" disabled>تغییر رمز</button>
            </div>

            <div class="toast-wrapper">

                <div class="toast-card success">
                    <div class="toast-side-bar"></div>
                    <div class="toast-content">
                        <span class="toast-icon">
                            <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8.33333 0C3.74167 0 0 3.74166 0 8.33335C0 12.9249 3.74167 16.6667 8.33333 16.6667C12.925 16.6667 16.6666 12.9249 16.6666 8.33335C16.6666 3.74166 12.925 0 8.33333 0ZM12.3166 6.41666L7.59166 11.1417C7.47448 11.2587 7.31562 11.3245 7.15 11.3245C6.98437 11.3245 6.82552 11.2587 6.70833 11.1417L4.35 8.78335C4.23376 8.66575 4.16858 8.50705 4.16858 8.34165C4.16858 8.17631 4.23376 8.01761 4.35 7.9C4.59167 7.65833 4.99167 7.65833 5.23333 7.9L7.15 9.81665L11.4333 5.53333C11.675 5.29166 12.075 5.29166 12.3166 5.53333C12.5583 5.775 12.5583 6.16666 12.3166 6.41666Z" fill="#10A853" />
                            </svg>
                        </span>
                        <span class="toast-text">رمز عبور با موفقیت تغییر کرد</span>
                    </div>
                </div>

                <div class="toast-card error">
                    <div class="toast-side-bar"></div>
                    <div class="toast-content">
                        <span class="toast-icon">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8.6709 0.75V0.75ZM8.6709 0.75C13.0459 0.75 16.5918 4.29592 16.5918 8.6709M8.6709 0.75C4.29592 0.75 0.75 4.29592 0.75 8.6709M16.5918 8.6709V8.6709ZM16.5918 8.6709C16.5918 13.0459 13.0459 16.5918 8.6709 16.5918M8.6709 16.5918V16.5918ZM8.6709 16.5918C4.29592 16.5918 0.75 13.0459 0.75 8.6709M0.75 8.6709V8.6709Z" stroke="#DC2626" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M8.6709 9.11095V4.71045" stroke="#DC2626" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M8.67032 12.1913C8.54887 12.1913 8.4503 12.2899 8.45118 12.4113C8.45118 12.5328 8.54975 12.6313 8.6712 12.6313C8.79266 12.6313 8.89123 12.5328 8.89123 12.4113C8.89123 12.2899 8.79266 12.1913 8.67032 12.1913Z" stroke="#DC2626" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span class="toast-text">رمز خود را درست وارد کنید</span>
                    </div>
                </div>

            </div>

        </div>

        <div class="auth-sidebar">
            <div class="sidebar-overlay">
                <img src="<?= get_template_directory_uri(); ?>/assets/img/front-page/login-banner.webp" />
            </div>
        </div>

    </div>

    <script>
        const evented_ajax_url = "<?php echo admin_url('admin-ajax.php'); ?>";
        const evented_auth_nonce = "<?php echo wp_create_nonce('evented_nonce'); ?>";
        let otpTimerInterval = null;

        // تشخیص تایپ فارسی
        const persianRegex = /[\u0600-\u06FF\uFB8A\u067E\u0686\u06AF\u200C\u200F]/;

        // تابع کمکی برای نمایش توست موفقیت
        function showSuccessToast(message) {
            const toast = document.querySelector('.toast-card.success');
            const toastText = document.querySelector('.toast-card.success .toast-text');

            toastText.innerText = message;
            toast.classList.add('active');

            // پنهان شدن خودکار بعد از 3.5 ثانیه
            setTimeout(() => {
                toast.classList.remove('active');
            }, 5000);
        }

        // تابع کمکی برای نمایش توست خطا
        function showErrorToast(message) {
            const toast = document.querySelector('.toast-card.error');
            const toastText = document.querySelector('.toast-card.error .toast-text');

            toastText.innerText = message;
            toast.classList.add('active');

            setTimeout(() => {
                toast.classList.remove('active');
            }, 5000);
        }

        // آبجکت وضعیت کلی برای جابجایی تستی بین کامپوننت ها
        const state = {
            mobile: '',
            registeredUsers: ['09123456789'],
            otpPurpose: null,
            firstName: '',
            lastName: ''
        };

        // المان‌های عمومی هدر و فیلدها
        const btnToOtp = document.getElementById('btn-to-otp');
        const mobileInput = document.getElementById('mobile-input');
        const mobileError = document.getElementById('mobile-error');

        mobileInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !btnToOtp.disabled) btnToOtp.click();
        });
        document.getElementById('login-password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !document.getElementById('btn-login-submit').disabled) document.getElementById('btn-login-submit').click();
        });
        document.getElementById('first-name').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') document.getElementById('last-name').focus();
        });
        document.getElementById('last-name').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !document.getElementById('btn-save-name').disabled) document.getElementById('btn-save-name').click();
        });
        document.getElementById('new-password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') document.getElementById('confirm-password').focus();
        });
        document.getElementById('confirm-password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !document.getElementById('btn-save-password').disabled) document.getElementById('btn-save-password').click();
        });

        // کنترل فعالسازی دکمه مرحله اول
        mobileInput.addEventListener('input', function() {
            const val = this.value.trim();
            // فقط عدد مجاز باشد
            this.value = val.replace(/[^0-9]/g, '');

            if (this.value.length === 11 && this.value.startsWith('09')) {
                btnToOtp.removeAttribute('disabled');
            } else {
                btnToOtp.setAttribute('disabled', 'true');
            }
        });

        // کلیک دکمه مرحله اول و هدایت هوشمند به مرحله بعد
        btnToOtp.addEventListener('click', async function() {
            const phone = mobileInput.value.trim();
            state.mobile = phone;

            if (phone) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'evented_check_mobile_and_send_otp');
                    formData.append('mobile', phone);
                    formData.append('nonce', evented_auth_nonce);
                    const response = await fetch(evented_ajax_url, {
                        method: 'POST',
                        body: formData
                    });
                    const res = await response.json();
                    if (res.success === true) {
                        document.getElementById('user-phone-display').innerText = phone;
                        if (res.data.status === "login") {
                            state.otpPurpose = 'password_login';
                            goToStep('step-password-option');
                        } else if(res.data.status === "register") {
                            state.otpPurpose = 'register';
                            goToStep('step-otp');
                        }
                        startOtpTimer();
                    } else {
                        showErrorToast(res.data.message);
                    }
                    console.log(response,res);
                    

                } catch (error) {
                    console.error('Ajax Error:', error);
                    showErrorToast('خطا در برقراری ارتباط با سرور');
                }
                return;
            }

            

            // بررسی اینکه کاربر قبلاً ثبت نام کرده یا جدید است
            if (state.registeredUsers.includes(phone)) {
                goToStep('step-password-option'); // رفتن به صفحه ورود با پسورد معمولی
            } else {
                state.otpPurpose = 'login_otp';
                goToStep('step-otp'); // رفتن به تایید پیامکی جهت ثبت نام
                // startOtpTimer();
            }
        });

        // لینک های جابجایی مستقیم در مرحله پسورد
        document.getElementById('link-to-otp-direct').addEventListener('click', async (e) => {
            e.preventDefault();

            try {

                const formData = new FormData();
                formData.append('action', 'evented_send_otp');
                formData.append('mobile', state.mobile);
                formData.append('purpose', 'login_otp');
                formData.append('nonce', evented_auth_nonce);

                const response = await fetch(evented_ajax_url, {
                    method: 'POST',
                    body: formData
                });

                const res = await response.json();

                if (res.success) {

                    document.getElementById('user-phone-display').innerText = state.mobile;
                    state.otpPurpose = 'login_otp';
                    goToStep('step-otp');
                    startOtpTimer();

                    showSuccessToast(res.data.message);

                } else {
                    showErrorToast(res.data.message);
                }

            } catch (error) {
                console.error(error);
                showErrorToast('خطا در برقراری ارتباط با سرور');
            }
        });

        document.getElementById('link-to-reset').addEventListener('click', async (e) => {
            e.preventDefault();

            try {

                const formData = new FormData();
                formData.append('action', 'evented_send_otp');
                formData.append('mobile', state.mobile);
                formData.append('purpose', 'reset_password');
                formData.append('nonce', evented_auth_nonce);

                const response = await fetch(evented_ajax_url, {
                    method: 'POST',
                    body: formData
                });

                const res = await response.json();

                if (res.success) {

                    document.getElementById('user-phone-display').innerText = state.mobile;

                    // مشخص می‌کنیم OTP برای بازیابی رمز است
                    state.otpPurpose = 'reset_password';

                    goToStep('step-otp');
                    startOtpTimer();

                    showSuccessToast(res.data.message);

                } else {
                    showErrorToast(res.data.message);
                }

            } catch (error) {
                console.error(error);
                showErrorToast('خطا در برقراری ارتباط با سرور');
            }
        });

        document.getElementById('link-to-password-direct').addEventListener('click', (e) => {
            e.preventDefault();
            goToStep('step-password-option');
        });

        // دکمه تایید ورود با پسورد معمولی
        document.getElementById('btn-login-submit').addEventListener('click', async() => {
            const loginPass = document.getElementById('login-password').value;
            const btn = document.getElementById('btn-login-submit');

            // btn.innerText = 'در حال تایید کد...';
            btn.setAttribute('disabled', 'true');


            const formData = new FormData();
            formData.append('action', 'evented_login_user');
            formData.append('password', loginPass);
            formData.append('mobile', state.mobile);
            formData.append('nonce', evented_auth_nonce);
            const response = await fetch(evented_ajax_url, {
                method: 'POST',
                body: formData
            });

            btn.removeAttribute('disabled');

            const res = await response.json();

            if (res.success) {

                showSuccessToast(res.data.message);

                setTimeout(() => {
                    window.location.href = <?php echo wp_json_encode($evented_redirect_to); ?>
                }, 1000);

            } else {
                showErrorToast(res.data.message);
            }
            
            // یک نمونه شرط فرضی برای نمایش توست خطا یا موفقیت
            // if (loginPass.length > 0) {
            //     showSuccessToast('ورود با موفقیت انجام شد!');
            // } else {
            //     showErrorToast('رمز خود را درست وارد کنید');
            // }
        });

        // مدیریت اینپوت های ۵ گانه OTP پی در پی
        const otpFields = document.querySelectorAll('.otp-field');
        otpFields.forEach((field, index) => {
            field.addEventListener('input', (e) => {
                field.value = e.target.value.replace(/[^0-9]/g, '');
                if (field.value.length === 1 && index < otpFields.length - 1) {
                    otpFields[index + 1].focus();
                }
                // بررسی برای ارسال خودکار کد
                let currentOtp = '';
                otpFields.forEach(input => currentOtp += input.value.trim());
                if (currentOtp.length === 5) {
                    document.getElementById('btn-verify-otp').click();
                }
            });

            field.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && field.value.length === 0 && index > 0) {
                    otpFields[index - 1].focus();
                }
            });
        });

        document.getElementById('btn-resend-otp').addEventListener('click', async () => {

            const resendBtn = document.getElementById('btn-resend-otp');

            resendBtn.disabled = true;
            resendBtn.innerText = 'در حال ارسال...';

            try {

                const formData = new FormData();
                formData.append('action', 'evented_send_otp');
                formData.append('mobile', state.mobile);
                formData.append('purpose', state.otpPurpose);
                formData.append('nonce', evented_auth_nonce);

                const response = await fetch(evented_ajax_url, {
                    method: 'POST',
                    body: formData
                });

                const res = await response.json();

                if (res.success) {

                    showSuccessToast(res.data.message);

                    resendBtn.style.display = 'none';

                    document.getElementById('timer-clock').style.display = 'inline';
                    document.getElementById('timer-text').style.display = 'inline';

                    startOtpTimer();

                } else {
                    showErrorToast(res.data.message);
                }

            } catch (error) {
                console.error(error);
                showErrorToast('خطا در برقراری ارتباط با سرور');
            }

            resendBtn.disabled = false;
            resendBtn.innerText = 'ارسال مجدد کد';

        });

        document.getElementById('btn-verify-otp').addEventListener('click', async() => {
            const btn = document.getElementById('btn-verify-otp');
            let otpCode = '';
            otpFields.forEach(input => otpCode += input.value.trim());

            if (otpCode.length < 5) {
                showErrorToast('لطفاً کد تایید ۵ رقمی را کامل وارد کنید');
                return;
            }

            btn.innerText = 'در حال تایید کد...';
            btn.setAttribute('disabled', 'true');


            const formData = new FormData();
            formData.append('action', 'evented_verify_otp');
            formData.append('otp', otpCode);
            formData.append('phone', state.mobile);
            formData.append('purpose', state.otpPurpose);
            formData.append('nonce', evented_auth_nonce);
            const response = await fetch(evented_ajax_url, {
                method: 'POST',
                body: formData
            });

            btn.innerText = 'تایید کد';
            btn.removeAttribute('disabled');

            const res = await response.json();

            if (res.success) {
                
                switch (state.otpPurpose) {

                    case 'reset_password':
                        goToStep('step-new-password');
                        break;

                    case 'register':
                        goToStep('step-name');
                        break;

                    case 'login_otp':
                        window.location.href = <?php echo wp_json_encode($evented_redirect_to); ?>;
                        break;
                }
                // if (res.data.result === 'login') {
                //     // کاربر از قبل ثبت نام کرده بود و لاگین شد -> انتقال به صفحه اصلی
                //     setTimeout(() => window.location.href = <?php echo wp_json_encode($evented_redirect_to); ?>, 1500);
                // } else if (res.data.is_new_user === true) {
                //     // کاربر جدید است -> هدایت به بخش تعیین پسورد
                //     goToStep('step-new-password');
                // }

            } else {
                // ارور اشتباه بودن کد تایید
                showErrorToast(res.data.message);
            }
            console.log(response,res);
            
        });

        const firstNameInput = document.getElementById('first-name');
        const lastNameInput = document.getElementById('last-name');
        const btnSaveName = document.getElementById('btn-save-name');

        function validateName() {
            if (firstNameInput.value.trim() !== '' && lastNameInput.value.trim() !== '') {
                btnSaveName.removeAttribute('disabled');
            } else {
                btnSaveName.setAttribute('disabled', 'true');
            }
        }
        firstNameInput.addEventListener('input', validateName);
        lastNameInput.addEventListener('input', validateName);

        btnSaveName.addEventListener('click', async () => {
            state.firstName = firstNameInput.value.trim();
            state.lastName = lastNameInput.value.trim();
            
            btnSaveName.setAttribute('disabled', 'true');

            const formData = new FormData();
            formData.append('action', 'save_user_register_name');
            formData.append('mobile', state.mobile);
            formData.append('firstname', state.firstName);
            formData.append('lastname', state.lastName);
            formData.append('nonce', evented_auth_nonce);
            const response = await fetch(evented_ajax_url, {
                method: 'POST',
                body: formData
            });

            btnSaveName.removeAttribute('disabled');

            const res = await response.json();

            if (res.success) {
                goToStep('step-new-password');
            } else {
                // ارور اشتباه بودن کد تایید
                showErrorToast(res.data.message);
            }

            document.getElementById('new-password').focus();
        });

        // اعتبارسنجی زنده پسورد و نمایش قدرت امنیت آن
        const newPassword = document.getElementById('new-password');
        const confirmPassword = document.getElementById('confirm-password');
        const btnSavePassword = document.getElementById('btn-save-password');

        const rules = {
            number: /[0-9]/,
            length: /^.{8,}$/,
            case: /^(?=.*[a-z])(?=.*[A-Z])/,
            special: /[@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/
        };

        function preventPersianChars(e) {
            if (persianRegex.test(e.target.value)) {
                showErrorToast('لطفاً کیبورد خود را انگلیسی کنید و از حروف فارسی استفاده نکنید');
                e.target.value = e.target.value.replace(/[\u0600-\u06FF\uFB8A\u067E\u0686\u06AF\u200C\u200F]/g, '');
            }
        }

        newPassword.addEventListener('input', function(e) {
            preventPersianChars(e);

            const val = this.value;
            let score = 0;

            if (rules.number.test(val)) {
                document.getElementById('rule-number').className = 'valid';
                score++;
            } else {
                document.getElementById('rule-number').className = 'invalid';
            }

            if (rules.length.test(val)) {
                document.getElementById('rule-length').className = 'valid';
                score++;
            } else {
                document.getElementById('rule-length').className = 'invalid';
            }

            if (rules.case.test(val)) {
                document.getElementById('rule-case').className = 'valid';
                score++;
            } else {
                document.getElementById('rule-case').className = 'invalid';
            }

            if (rules.special.test(val)) {
                document.getElementById('rule-special').className = 'valid';
                score++;
            } else {
                document.getElementById('rule-special').className = 'invalid';
            }

            const txt = document.getElementById('strength-text');
            const b1 = document.getElementById('bar-1');
            const b2 = document.getElementById('bar-2');
            const b3 = document.getElementById('bar-3');

            b1.style.backgroundColor = b2.style.backgroundColor = b3.style.backgroundColor = 'var(--primary-disabled)';

            if (score <= 1 && val.length > 0) {
                txt.innerText = 'ضعیف';
                txt.className = 'strength-label weak';
                b1.style.backgroundColor = 'var(--error-color)';
            } else if (score >= 2 && score <= 3) {
                txt.innerText = 'متوسط';
                txt.className = 'strength-label medium';
                b1.style.backgroundColor = '#F4B740';
                b2.style.backgroundColor = '#F4B740';
            } else if (score === 4) {
                txt.innerText = 'قوی';
                txt.className = 'strength-label strong';
                b1.style.backgroundColor = 'var(--success-color)';
                b2.style.backgroundColor = 'var(--success-color)';
                b3.style.backgroundColor = 'var(--success-color)';
            } else {
                txt.innerText = '';
            }

            validatePasswordMatch();
        });

        confirmPassword.addEventListener('input', validatePasswordMatch);

        function validatePasswordMatch() {
            const allValid = document.querySelectorAll('.password-rules .valid').length === 4;
            if (allValid && newPassword.value === confirmPassword.value && confirmPassword.value.length > 0) {
                btnSavePassword.removeAttribute('disabled');
            } else {
                btnSavePassword.setAttribute('disabled', 'true');
            }
        }

        // دکمه ذخیره و تغییر نهایی رمز عبور
        document.getElementById('btn-save-password').addEventListener('click', async() => {
            const btn = document.getElementById('btn-save-password');
            let otpCode = '';

            btn.innerText = 'در حال اربسال...';
            btn.setAttribute('disabled', 'true');

            const password = newPassword.value.trim();
            const confirmPass = confirmPassword.value.trim();

            let actionName = 'evented_register_user';

            if (state.otpPurpose === 'reset_password') {
                actionName = 'evented_reset_password';
            }

            const formData = new FormData();
            formData.append('action', actionName);
            formData.append('phone', state.mobile);
            formData.append('password', password);
            formData.append('confirmPassword', confirmPass);
            formData.append('nonce', evented_auth_nonce);
            const response = await fetch(evented_ajax_url, {
                method: 'POST',
                body: formData
            });

            btn.innerText = 'تغییر مزعبور';
            btn.removeAttribute('disabled');

            const res = await response.json();

            if (res.success) {
                showSuccessToast(res.data.message);
                
                if (res.data.result === 'login') {
                    // کاربر از قبل ثبت نام کرده بود و لاگین شد -> انتقال به صفحه اصلی
                    setTimeout(() => window.location.href = <?php echo wp_json_encode($evented_redirect_to); ?>, 1500);
                } else if (res.data.is_new_user === true) {
                    // کاربر جدید است -> هدایت به بخش تعیین پسورد
                    goToStep('step-name');
                }
            } else {
                // ارور اشتباه بودن کد تایید
                showErrorToast(res.data.message);
            }
            console.log(response,res);
            showSuccessToast('رمز عبور شما با موفقیت تغییر کرد.');
        });

        // توابع کمکی کاربردی عمومی
        function goToStep(stepId) {
            document.querySelectorAll('.auth-step').forEach(step => step.classList.remove('active'));
            document.getElementById(stepId).classList.add('active');
            document.querySelectorAll('.toast-card').forEach((e)=>{
                e.classList.remove('active')
            });
            if (stepId === 'step-otp') {
                setTimeout(() => {
                    otpFields.forEach(field => field.value = ''); // اختیاری: پاک کردن مقادیر قبلی
                    otpFields[0].focus();
                }, 100);
            }
        }

        function togglePasswordVisibility(id) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
            input.type === 'text' ? input.classList.add('show-password') : input.classList.remove('show-password');
        }

        function startOtpTimer() {

            if (otpTimerInterval) {
                clearInterval(otpTimerInterval);
            }

            const resendBtn = document.getElementById('btn-resend-otp');
            const timerClock = document.getElementById('timer-clock');
            const timerText = document.getElementById('timer-text');

            resendBtn.style.display = 'none';
            timerClock.style.display = 'inline';
            timerText.style.display = 'inline';

            let duration = 120;

            updateDisplay();

            otpTimerInterval = setInterval(() => {

                duration--;

                if (duration <= 0) {

                    clearInterval(otpTimerInterval);

                    timerClock.style.display = 'none';
                    timerText.style.display = 'none';

                    resendBtn.style.display = 'block';

                    return;
                }

                updateDisplay();

            }, 1000);

            function updateDisplay() {

                const minutes = String(Math.floor(duration / 60)).padStart(2, '0');
                const seconds = String(duration % 60).padStart(2, '0');

                timerClock.innerText = `${minutes}:${seconds}`;
            }
        }
    </script>
</body>

</html>