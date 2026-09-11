<?php
/* Template Name: Panel - Account Settings */

if (! is_user_logged_in()) {
    wp_redirect(add_query_arg('redirect_to', home_url('/panel/settings'), wp_login_url()));
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

get_header(); ?>
<div class="container">

    <!-- Sidebar -->
    <?php locate_template('panel/sidebar.php', true, false); ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="settings-panel">
            <h2 class="section-title">تنظیمات حساب کاربری</h2>

            <form id="settings-form">
                <?php wp_nonce_field('settings_nonce_action', 'settings_nonce'); ?>

                <div class="settings-grid">
                    <!-- Phone Number -->
                    <div class="input-block">
                        <div class="input-header">
                            <span class="input-label">شماره همراه</span>
                            <?php if (!empty($user_phone)): ?>
                                <span class="badge-verified">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    تایید شده
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="input-wrapper">
                            <input type="text" name="user_phone" class="form-control" value="<?php echo esc_attr($current_user->user_login); ?>" disabled="disabled">
                            <svg class="icon-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="cursor:pointer;">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </div>
                        <div class="input-hint">(در صورت تغییر باید شماره همراه جدید را تایید کنید.)</div>
                    </div>

                    <!-- Email Address -->
                    <div class="input-block">
                        <div class="input-header">
                            <span class="input-label">آدرس ایمیل</span>
                            <?php if (!empty($user_email)): ?>
                                <span class="badge-verified">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                    </svg>
                                    تایید شده
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="input-wrapper">
                            <input type="email" name="user_email" class="form-control email-input" value="<?php echo esc_attr($user_email); ?>" dir="ltr" placeholder="example@domain.com" disabled="disabled">
                            <svg class="icon-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="cursor:pointer;">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </div>
                        <div class="input-hint">(در صورت تغییر باید آدرس ایمیل جدید را تایید کنید.)</div>
                    </div>

                    <!-- Password -->
                    <div class="input-block">
                        <div class="input-header">
                            <span class="input-label">رمز عبور</span>
                        </div>
                        <div class="input-wrapper">
                            <!-- بجای مقدار واقعی پسورد، از نقطه استفاده می‌کنیم. در صورت ویرایش، خالی می‌شود -->
                            <input type="password" name="user_password" class="form-control" value=".........." disabled="disabled">
                            <svg class="icon-edit" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="cursor:pointer;">
                                <path d="M12 20h9"></path>
                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path>
                            </svg>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-submit">ذخیره اطلاعات</button>
            </form>

            <!-- باکس پیام وضعیت -->
            <div id="settings-msg" style="margin-top: 15px; text-align: center; font-weight: bold; border-radius: 8px; padding: 10px; display: none;"></div>

        </div>
    </main>
</div>
<style>

</style>
<div class="password-modal" style="display: none;" id="step-new-password">
    <div class="close">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 6L18.7742 18.7742" stroke="black" stroke-width="1.53267" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M6 18.7744L18.7742 6.00022" stroke="black" stroke-width="1.53267" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
    </div>
    <div class="modal-content">
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
</div>
<!-- کدهای جاوااسکریپت و ایجکس -->
<script>
    jQuery(document).ready(function($) {

        // ۲. ارسال فرم تنظیمات با ایجکس
        $('#settings-form').on('submit', function(e) {
            e.preventDefault();

            var formData = $(this).serialize();
            var btn = $('#settings-footer .btn-submit');
            var msgDiv = $('#settings-msg');

            btn.text('در حال ذخیره...').prop('disabled', true);

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData + '&action=save_account_settings&security=' + $('#settings_nonce').val(),
                success: function(response) {
                    if (response.success) {
                        msgDiv.text(response.data).css({
                            'color': 'green',
                            'background': '#e8f5e9'
                        }).fadeIn();

                        // برگرداندن فیلدها به حالت Readonly
                        $('#settings-form input').prop('readonly', true);
                        if ($('input[name="user_password"]').val() !== '') {
                            $('input[name="user_password"]').val('..........');
                        }
                        setTimeout(function() {
                            $('#settings-footer').slideUp();
                        }, 1500);

                    } else {
                        msgDiv.text(response.data || 'خطایی رخ داد!').css({
                            'color': 'red',
                            'background': '#ffebee'
                        }).fadeIn();
                    }
                },
                error: function() {
                    msgDiv.text('خطای ارتباط با سرور').css({
                        'color': 'red',
                        'background': '#ffebee'
                    }).fadeIn();
                },
                complete: function() {
                    btn.text('ذخیره تغییرات').prop('disabled', false);
                    setTimeout(function() {
                        msgDiv.fadeOut();
                    }, 4000);
                }
            });
        });
        $('input[type="password"] + .icon-edit').on('click', function(e) {
            $('.password-modal').fadeIn(200).css('display', 'flex');
        });
        // بستن مودال با کلیک روی دکمه انصراف یا ضربدر
        $('.password-modal .close').on('click', function(e) {
            e.preventDefault();
            $('.password-modal').fadeOut(200);
        });

        // بستن مودال در صورت کلیک روی فضای خالی تیره رنگ (overlay)
        $('.password-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).fadeOut(200);
            }
        });
    });
</script>

<?php get_footer(); ?>