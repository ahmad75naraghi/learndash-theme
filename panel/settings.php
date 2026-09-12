<?php
/* Template Name: Panel - Account Settings */

if (! is_user_logged_in()) {
    wp_safe_redirect(add_query_arg('redirect_to', rawurlencode(home_url('/panel/settings')), home_url('/login')));
    exit;
}

$current_user = wp_get_current_user();

// دریافت شماره موبایل (اگر از افزونه دیجیتس استفاده می‌کنید، ممکن است کلید آن digits_phone_no باشد)
$user_phone = get_user_meta($current_user->ID, 'billing_phone', true);

// ایمیل‌های placeholder تولیدشده هنگام ثبت‌نام با موبایل (با «09…» شروع می‌شوند) را
// به‌جای مقدار جعلی نمایش نده؛ کاربر باید ایمیل واقعی خود را در همین بخش ثبت کند.
$user_email     = (string) $current_user->user_email;
$has_real_email = is_email($user_email) && !str_starts_with($user_email, '09');
if (!$has_real_email) {
    $user_email = '';
}

$ee_pencil = function_exists('ee_icon') ? ee_icon('edit') : '✎';
get_template_part('template-parts/panel/shell', 'open', array('ee_panel_current' => 'settings', 'ee_panel_title' => 'حساب کاربری')); ?>
        <div class="settings-panel">
            <h2 class="section-title">تنظیمات حساب کاربری</h2>
            <?php wp_nonce_field('settings_nonce_action', 'settings_nonce'); ?>

            <div class="settings-grid">
                <!-- شماره همراه: فقط‌خواندنی -->
                <div class="input-block">
                    <div class="input-header">
                        <span class="input-label">شماره همراه</span>
                        <span class="badge-verified">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            تایید شده
                        </span>
                    </div>
                    <div class="input-wrapper is-locked">
                        <input type="text" class="form-control" value="<?php echo esc_attr($current_user->user_login); ?>" dir="ltr" readonly aria-readonly="true">
                        <svg class="ee-ic icon-lock" aria-hidden="true" focusable="false"><use href="#i-lock"></use></svg>
                    </div>
                    <div class="input-hint">شماره همراه شناسهٔ ورود شماست و قابل تغییر نیست.</div>
                </div>

                <!-- ایمیل -->
                <div class="input-block">
                    <div class="input-header">
                        <span class="input-label">آدرس ایمیل</span>
                        <?php if (!empty($user_email)): ?>
                            <span class="badge-verified" id="eeEmailBadge">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                ثبت شده
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="input-wrapper">
                        <input type="email" id="eeEmailView" class="form-control email-input" value="<?php echo esc_attr($user_email); ?>" dir="ltr" placeholder="هنوز ایمیلی ثبت نشده" readonly>
                        <button type="button" class="icon-edit ee-edit-btn" data-ee-modal-open="eeEmailModal" aria-label="ویرایش ایمیل" title="ویرایش ایمیل"><?php echo $ee_pencil; // phpcs:ignore ?></button>
                    </div>
                    <div class="input-hint">برای <?php echo $user_email ? 'تغییر' : 'افزودن'; ?> ایمیل روی مداد بزنید.</div>
                </div>

                <!-- رمز عبور -->
                <div class="input-block">
                    <div class="input-header">
                        <span class="input-label">رمز عبور</span>
                    </div>
                    <div class="input-wrapper">
                        <input type="password" class="form-control" value=".........." readonly aria-label="رمز عبور">
                        <button type="button" class="icon-edit ee-edit-btn" data-ee-modal-open="eePassModal" aria-label="تغییر رمز عبور" title="تغییر رمز عبور"><?php echo $ee_pencil; // phpcs:ignore ?></button>
                    </div>
                    <div class="input-hint">برای تغییر رمز روی مداد بزنید.</div>
                </div>
            </div>

            <div id="settings-msg" style="margin-top: 15px; text-align: center; font-weight: bold; border-radius: 8px; padding: 10px; display: none;"></div>
        </div>

<!-- مودال ایمیل -->
<div class="ee-modal" id="eeEmailModal" hidden>
    <div class="ee-modal-bg" data-ee-modal-close></div>
    <form class="ee-modal-box ee-modal-form" role="dialog" aria-modal="true" aria-labelledby="eeEmailTitle" id="eeEmailForm" novalidate>
        <button type="button" class="ee-modal-x" data-ee-modal-close aria-label="بستن"><?php echo function_exists('ee_icon') ? ee_icon('close') : '×'; // phpcs:ignore ?></button>
        <div class="ee-modal-ic"><?php echo function_exists('ee_icon') ? ee_icon('mail') : ''; // phpcs:ignore ?></div>
        <h3 id="eeEmailTitle"><?php echo $user_email ? 'تغییر آدرس ایمیل' : 'افزودن آدرس ایمیل'; ?></h3>
        <p>ایمیل برای بازیابی حساب و دریافت اطلاعیه‌ها استفاده می‌شود.</p>
        <label class="ee-field">
            <span>آدرس ایمیل</span>
            <input type="email" name="user_email" dir="ltr" autocomplete="email" placeholder="example@domain.com" value="<?php echo esc_attr($user_email); ?>" required>
        </label>
        <div class="ee-modal-msg" hidden></div>
        <div class="ee-modal-actions">
            <button type="button" class="ee-btn ee-btn-ghost" data-ee-modal-close>انصراف</button>
            <button type="submit" class="ee-btn ee-btn-primary">ذخیره ایمیل</button>
        </div>
    </form>
</div>

<!-- مودال رمز عبور -->
<div class="ee-modal" id="eePassModal" hidden>
    <div class="ee-modal-bg" data-ee-modal-close></div>
    <form class="ee-modal-box ee-modal-form" role="dialog" aria-modal="true" aria-labelledby="eePassTitle" id="eePassForm" novalidate>
        <button type="button" class="ee-modal-x" data-ee-modal-close aria-label="بستن"><?php echo function_exists('ee_icon') ? ee_icon('close') : '×'; // phpcs:ignore ?></button>
        <div class="ee-modal-ic"><?php echo function_exists('ee_icon') ? ee_icon('lock') : ''; // phpcs:ignore ?></div>
        <h3 id="eePassTitle">تغییر رمز عبور</h3>
        <p>رمز جدید باید حداقل ۸ کاراکتر و ترکیبی از حروف و عدد باشد.</p>
        <label class="ee-field ee-field-pass">
            <span>رمز عبور جدید</span>
            <input type="password" name="user_password" id="eeNewPass" dir="ltr" autocomplete="new-password" minlength="8" required>
            <button type="button" class="ee-eye" data-ee-eye="eeNewPass" aria-label="نمایش رمز"><?php echo function_exists('ee_icon') ? ee_icon('visibility') : '👁'; // phpcs:ignore ?></button>
        </label>
        <div class="ee-strength" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
        <ul class="ee-pass-rules">
            <li data-rule="length">حداقل ۸ کاراکتر</li>
            <li data-rule="number">شامل عدد</li>
            <li data-rule="letter">شامل حرف</li>
            <li data-rule="mix">حرف بزرگ و کوچک یا علامت (پیشنهادی)</li>
        </ul>
        <label class="ee-field ee-field-pass">
            <span>تکرار رمز عبور</span>
            <input type="password" name="user_password2" id="eeNewPass2" dir="ltr" autocomplete="new-password" required>
            <button type="button" class="ee-eye" data-ee-eye="eeNewPass2" aria-label="نمایش رمز"><?php echo function_exists('ee_icon') ? ee_icon('visibility') : '👁'; // phpcs:ignore ?></button>
        </label>
        <div class="ee-modal-msg" hidden></div>
        <div class="ee-modal-actions">
            <button type="button" class="ee-btn ee-btn-ghost" data-ee-modal-close>انصراف</button>
            <button type="submit" class="ee-btn ee-btn-primary" disabled>تغییر رمز</button>
        </div>
    </form>
</div>
<?php get_template_part('template-parts/panel/shell', 'close'); ?>
