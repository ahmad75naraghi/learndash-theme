<?php
// اعتبارسنجی redirect_to یک‌بار در ابتدای صفحه (جلوگیری از تزریق در inline JS)
$evented_redirect_to = isset($_GET['redirect_to'])
    ? (string) wp_validate_redirect(wp_unslash($_GET['redirect_to']), home_url('/'))
    : home_url('/');

// اگر مقصد، پیشخوان وردپرس است، این صفحه (ورود با موبایل) به درد نمی‌خورد؛ به فرم استاندارد برو.
$evented_redirect_path = (string) wp_parse_url($evented_redirect_to, PHP_URL_PATH);
if (false !== strpos($evented_redirect_path, '/wp-admin')) {
    wp_safe_redirect(add_query_arg('redirect_to', $evented_redirect_to, site_url('wp-login.php')));
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
    <link rel="stylesheet" href="<?php echo esc_url(get_template_directory_uri() . '/assets/css/login.css?ver=1.0.0'); ?>">
</head>

<body>

    <div class="auth-container">

        <div class="auth-content">
            <div class="brand-logo">
                <div class="logo-placeholder">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/front-page/evented-edu-logo-login.png'); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
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
                    <input type="password" id="login-password" placeholder="رمز عبور" autocomplete="current-password">
                    <button type="button" class="toggle-password" data-password-target="login-password" aria-label="نمایش یا پنهان کردن رمز">
                        <svg width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.8886 7.64935C11.8886 9.29932 10.5553 10.6327 8.90521 10.6327C7.25521 10.6327 5.92188 9.29932 5.92188 7.64935C5.92188 5.99935 7.25521 4.66602 8.90521 4.66602C10.5553 4.66602 11.8886 5.99935 11.8886 7.64935Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M8.90417 14.5417C11.8458 14.5417 14.5876 12.8083 16.4958 9.80831C17.2458 8.63331 17.2458 6.65833 16.4958 5.48333C14.5876 2.48333 11.8458 0.75 8.90417 0.75C5.9625 0.75 3.22083 2.48333 1.3125 5.48333C0.5625 6.65833 0.5625 8.63331 1.3125 9.80831C3.22083 12.8083 5.9625 14.5417 8.90417 14.5417Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
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
                    <input type="text" id="first-name" placeholder="نام" autocomplete="given-name">
                </div>
                <div class="input-group">
                    <input type="text" id="last-name" placeholder="نام خانوادگی" autocomplete="family-name">
                </div>

                <button class="btn-submit" id="btn-save-name" disabled>تایید و ادامه</button>
            </div>

            <div class="auth-step" id="step-new-password">
                <h2>تغییر رمز عبور</h2>
                <p class="step-desc">رمز عبور جدید خود را وارد کنید</p>

                <div class="input-group password-wrapper">
                    <input type="password" id="new-password" placeholder="رمز عبور جدید" autocomplete="new-password">
                    <button type="button" class="toggle-password" data-password-target="new-password" aria-label="نمایش یا پنهان کردن رمز">
                        <svg width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.8886 7.64935C11.8886 9.29932 10.5553 10.6327 8.90521 10.6327C7.25521 10.6327 5.92188 9.29932 5.92188 7.64935C5.92188 5.99935 7.25521 4.66602 8.90521 4.66602C10.5553 4.66602 11.8886 5.99935 11.8886 7.64935Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M8.90417 14.5417C11.8458 14.5417 14.5876 12.8083 16.4958 9.80831C17.2458 8.63331 17.2458 6.65833 16.4958 5.48333C14.5876 2.48333 11.8458 0.75 8.90417 0.75C5.9625 0.75 3.22083 2.48333 1.3125 5.48333C0.5625 6.65833 0.5625 8.63331 1.3125 9.80831C3.22083 12.8083 5.9625 14.5417 8.90417 14.5417Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
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
                    <input type="password" id="confirm-password" placeholder="تکرار رمز عبور جدید" autocomplete="new-password">
                    <button type="button" class="toggle-password" data-password-target="confirm-password" aria-label="نمایش یا پنهان کردن رمز">
                        <svg width="18" height="16" viewBox="0 0 18 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.8886 7.64935C11.8886 9.29932 10.5553 10.6327 8.90521 10.6327C7.25521 10.6327 5.92188 9.29932 5.92188 7.64935C5.92188 5.99935 7.25521 4.66602 8.90521 4.66602C10.5553 4.66602 11.8886 5.99935 11.8886 7.64935Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M8.90417 14.5417C11.8458 14.5417 14.5876 12.8083 16.4958 9.80831C17.2458 8.63331 17.2458 6.65833 16.4958 5.48333C14.5876 2.48333 11.8458 0.75 8.90417 0.75C5.9625 0.75 3.22083 2.48333 1.3125 5.48333C0.5625 6.65833 0.5625 8.63331 1.3125 9.80831C3.22083 12.8083 5.9625 14.5417 8.90417 14.5417Z" stroke="black" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </button>
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
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/img/front-page/login-banner.webp'); ?>" alt="" aria-hidden="true">
            </div>
        </div>

    </div>

    <script>window.eventedLogin = <?php echo wp_json_encode(array(
        'ajaxUrl'    => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce('evented_nonce'),
        'redirectTo' => $evented_redirect_to,
    )); ?>;</script>
    <script src="<?php echo esc_url(get_template_directory_uri() . '/assets/js/login.js?ver=1.0.0'); ?>"></script>
</body>

</html>