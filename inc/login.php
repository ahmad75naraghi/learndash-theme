<?php
if (!defined('ABSPATH')) exit;

class FalnicAuthHandler
{
    private static $crm_guids = [
        'server'  => '79a1795d-757e-e911-a991-000c29994c65',
        'printer' => 'e07e3a98-847e-e911-a991-000c29994c65',
        'network' => '7c7e3a98-847e-e911-a991-000c29994c65',
        'laptop'  => '09c48b8c-847e-e911-a991-000c29994c65'
    ];

    public function __construct()
    {
        add_action('wp_ajax_falnic_send_otp',                               [$this, 'handle_send_otp']);
        add_action('wp_ajax_nopriv_falnic_send_otp',                        [$this, 'handle_send_otp']);
        add_action('wp_ajax_falnic_check_mobile_and_send_otp',              [$this, 'handle_check_mobile_and_send_otp']);
        add_action('wp_ajax_nopriv_falnic_check_mobile_and_send_otp',       [$this, 'handle_check_mobile_and_send_otp']);
        add_action('wp_ajax_falnic_verify_otp',                             [$this, 'handle_verify_otp']);
        add_action('wp_ajax_nopriv_falnic_verify_otp',                      [$this, 'handle_verify_otp']);
        add_action('wp_ajax_falnic_reset_password',                         [$this, 'handle_reset_password']);
        add_action('wp_ajax_nopriv_falnic_reset_password',                  [$this, 'handle_reset_password']);
        add_action('wp_ajax_falnic_submit_cta',                             [$this, 'handle_cta_submit']);
        add_action('wp_ajax_nopriv_falnic_submit_cta',                      [$this, 'handle_cta_submit']);

        add_action('wp_ajax_falnic_register_user',                          [$this, 'handle_register_user']);
        add_action('wp_ajax_nopriv_falnic_register_user',                   [$this, 'handle_register_user']);

        add_action('wp_ajax_save_user_register_name',                      [$this, 'handle_save_user_register_name']);
        add_action('wp_ajax_nopriv_save_user_register_name',               [$this, 'handle_save_user_register_name']);

        add_action('wp_ajax_falnic_login_user',                            [$this, 'handle_login_user']);
        add_action('wp_ajax_nopriv_falnic_login_user',                     [$this, 'handle_login_user']);
        add_filter( 'login_url',                                           [$this, 'redirect_login_url'], 10, 3 );
        add_filter( 'logout_redirect',                                     [$this, 'custom_logout_redirect_to_home'], 10, 3 );
    }


    public function handle_verify_otp()
    {
        check_ajax_referer('falnic_nonce', 'nonce');

        $mobile    = sanitize_text_field($_POST['phone'] ?? '');
        $otp_input = sanitize_text_field($_POST['otp'] ?? '');

        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['fl_otp']) || $_SESSION['fl_otp'] != $otp_input) {
            wp_send_json_error([
                'message' => 'کد تایید اشتباه است.'
            ]);
        }

        $mobile  = $_SESSION['fl_mobile'];
        $purpose = $_SESSION['fl_otp_purpose'] ?? '';

        // سناریوی بازیابی رمزعبور
        if ($purpose === 'reset_password') {

            $_SESSION['fl_otp_verified']     = true;
            $_SESSION['fl_otp_verified_for'] = 'reset_password';

            wp_send_json_success([
                'message' => 'شماره موبایل تایید شد.',
                'result'  => 'reset_password'
            ]);

            return;
        }

        $user = get_user_by('login', $mobile);

        // ثبت نام
        if (!$user) {

            $_SESSION['fl_otp_verified']     = true;
            $_SESSION['fl_otp_verified_for'] = 'register';

            wp_send_json_success([
                'is_new_user' => true,
                'message' => 'لطفا اطلاعات ثبت نام را تکمیل کنید.'
            ]);

            return;
        }

        // لاگین با OTP
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        wp_send_json_success([
            'message' => 'خوش آمدید.',
            'result'  => 'login',
            'nonce'   => wp_create_nonce('falnic_nonce')
        ]);
    }

    public function handle_register_user()
    {
        check_ajax_referer('falnic_nonce', 'nonce');

        $mobile   = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $password = isset($_POST['password']) ? sanitize_text_field($_POST['password']) : '';
        $confirmPassword = isset($_POST['confirmPassword']) ? sanitize_text_field($_POST['confirmPassword']) : '';

        if (!session_id()) {
            session_start();
        }

        if (empty($mobile) || empty($password) || strlen($password) < 8) {
            wp_send_json_error(['message' => 'گذرواژه باید حداقل ۸ کاراکتر و واجد شرایط امنیتی باشد']);
        }

        // بررسی اینکه آیا موبایل وارد شده و کد تایید قبلا وریفای شده است یا خیر
        if (empty($_SESSION['fl_mobile']) || empty($_SESSION['fl_otp_verified'])) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا پایان اعتبار نشست.']);
            return; // خروج از تابع
        }

        if ($confirmPassword !== $password) {
            wp_send_json_error(['message' => 'رمزعبور با تایید رمزعبور یکسان نیست.']);
        }


        $user = get_user_by('login', $mobile);

        $user_id = wp_set_password($password, $user->ID);

        if (is_wp_error($user_id)) {
            // اگر خطا به خاطر وجود کاربر تکراری است، پیام مناسب‌تری نمایش دهید
            if (isset($user_id->errors['existing_user_login']) || isset($user_id->errors['existing_user_email'])) {
                wp_send_json_error(['message' => 'این شماره موبایل یا ایمیل قبلا ثبت‌نام کرده است.']);
            } else {
                wp_send_json_error(['message' => 'خطا در ساخت حساب کاربری: ' . $user_id->get_error_message()]);
            }
            return; // خروج از تابع
        }
        

        // پس از ثبت نام موفق، نشست تایید را پاک کنید
        unset($_SESSION['fl_otp_verified']);

        $user = get_user_by('id', $user_id);
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        $new_nonce = wp_create_nonce('falnic_nonce');
        wp_send_json_success([
            'message' => 'ثبت نام با موفقیت انجام شد.',
            'nonce' => $new_nonce,
            'result'  => 'login'
        ]);
    }

    public function handle_send_otp()
    {
        check_ajax_referer('falnic_nonce', 'nonce');

        $mobile  = sanitize_text_field($_POST['mobile'] ?? '');
        $purpose = sanitize_text_field($_POST['purpose'] ?? '');

        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $english = ['0','1','2','3','4','5','6','7','8','9'];

        $mobile = str_replace($persian, $english, trim($mobile));

        if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
            wp_send_json_error([
                'message' => 'شماره موبایل معتبر نیست.'
            ]);
        }

        if (!in_array($purpose, [
            'register',
            'login_otp',
            'reset_password',
            'resend_otp'
        ])) {
            wp_send_json_error([
                'message' => 'نوع درخواست نامعتبر است.'
            ]);
        }

        $user = get_user_by('login', $mobile);

        // اعتبارسنجی بر اساس هدف ارسال کد

        if ($purpose === 'register' && $user) {
            wp_send_json_error([
                'message' => 'این شماره قبلاً ثبت شده است.'
            ]);
        }

        if (
            in_array($purpose, ['login_otp', 'reset_password']) &&
            !$user
        ) {
            wp_send_json_error([
                'message' => 'کاربری با این شماره یافت نشد.'
            ]);
        }

        // محدودیت ارسال
        if (get_transient('otp_limit_' . $mobile)) {
            wp_send_json_error([
                'message' => 'لطفاً کمی بعد دوباره تلاش کنید.'
            ]);
        }

        if (!session_id()) {
            session_start();
        }

        $otp = rand(10000, 99999);

        $_SESSION['fl_otp']         = $otp;
        $_SESSION['fl_mobile']      = $mobile;
        $_SESSION['fl_otp_time']    = time();
        $_SESSION['fl_otp_purpose'] = $purpose;

        $sms = send_pattern_sms($mobile, (string) $otp);

        if (!$sms) {
            wp_send_json_error([
                'message' => 'خطا در ارسال پیامک.'
            ]);
        }

        falnic_send_otp_with_bale($mobile, $otp);

        set_transient('otp_limit_' . $mobile, true, 60);

        wp_send_json_success([
            'message' => 'کد تایید ارسال شد.',
            'purpose' => $purpose
        ]);
    }

    public function handle_check_mobile_and_send_otp()
    {
        check_ajax_referer('falnic_nonce', 'nonce');

        $mobile = sanitize_text_field($_POST['mobile'] ?? '');

        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $mobile  = str_replace($persian, $english, trim($mobile));

        if ($this->is_rate_limited($mobile)) {
            wp_send_json_error(['message' => 'شما به تازگی یک کد برای این شماره دریافت کرده اید.']);
        }

        if (!session_id()) {
            session_start();
        }

        if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
            wp_send_json_error(['message' => 'شماره موبایل معتبر نیست.']);
        }

        $user_status = get_user_by('login', $mobile) ? 'login' : 'register';

        if ($user_status === "register") {
            $otp = rand(10000, 99999);
            $_SESSION['fl_otp']      = $otp;
            $_SESSION['fl_mobile']   = $mobile;
            $_SESSION['fl_otp_time'] = time();

            $sms = send_pattern_sms($mobile, (string)$otp);
            if ($sms) {
                falnic_send_otp_with_bale($mobile, $otp);
                set_transient('otp_limit_' . $mobile, true, 60);
                wp_send_json_success(['message' => 'کد تایید ارسال شد.', 'status' => 'register']);
            }
        }
        else {
            wp_send_json_success(['status' => 'login']);
        }

        

        wp_send_json_error(['message' => 'خطا در ارسال پیامک توسط پنل.']);
    }

    public function handle_reset_password()
    {
        check_ajax_referer('falnic_nonce', 'nonce');

        if (!session_id()) {
            session_start();
        }

        $mobile          = sanitize_text_field($_POST['phone'] ?? '');
        $password        = sanitize_text_field($_POST['password'] ?? '');
        $confirmPassword = sanitize_text_field($_POST['confirmPassword'] ?? '');

        if (
            empty($mobile) ||
            empty($password) ||
            empty($confirmPassword)
        ) {
            wp_send_json_error([
                'message' => 'اطلاعات ارسالی ناقص است.'
            ]);
        }

        if (strlen($password) < 8) {
            wp_send_json_error([
                'message' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.'
            ]);
        }

        if ($password !== $confirmPassword) {
            wp_send_json_error([
                'message' => 'رمز عبور و تکرار آن یکسان نیستند.'
            ]);
        }

        // بررسی اینکه OTP مخصوص بازیابی رمز تایید شده باشد
        if (
            empty($_SESSION['fl_otp_verified']) ||
            empty($_SESSION['fl_otp_verified_for']) ||
            $_SESSION['fl_otp_verified_for'] !== 'reset_password'
        ) {
            wp_send_json_error([
                'message' => 'ابتدا شماره موبایل خود را تایید کنید.'
            ]);
        }

        // جلوگیری از دستکاری شماره موبایل
        if (
            empty($_SESSION['fl_mobile']) ||
            $_SESSION['fl_mobile'] !== $mobile
        ) {
            wp_send_json_error([
                'message' => 'اطلاعات نشست نامعتبر است.'
            ]);
        }

        $user = get_user_by('login', $mobile);

        if (!$user) {
            wp_send_json_error([
                'message' => 'کاربری یافت نشد.'
            ]);
        }

        // تغییر رمز عبور
        wp_set_password($password, $user->ID);

        // پاکسازی نشست
        unset($_SESSION['fl_otp']);
        unset($_SESSION['fl_otp_time']);
        unset($_SESSION['fl_otp_verified']);
        unset($_SESSION['fl_otp_verified_for']);

        // لاگین خودکار
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        $new_nonce = wp_create_nonce('falnic_nonce');

        wp_send_json_success([
            'message' => 'رمز عبور با موفقیت تغییر کرد.',
            'result'  => 'login',
            'nonce'   => $new_nonce
        ]);
    }


    private function is_rate_limited($mobile)
    {
        return get_transient('otp_limit_' . $mobile) !== false;
    }

    public function custom_logout_redirect_to_home( $redirect_to, $requested_redirect_to, $user ) {
        // هدایت به صفحه اصلی سایت
        return home_url(); 
    }

    public function handle_login_user() {
        check_ajax_referer('falnic_nonce', 'nonce');

        $mobile   = sanitize_text_field($_POST['mobile'] ?? '');
        $password = sanitize_text_field($_POST['password']) ?? '';

        if (empty($mobile) || empty($password)) {
            wp_send_json_error([
                'message' => 'شماره موبایل و رمزعبور الزامی است.'
            ]);
        }

        $user = get_user_by('login', $mobile);

        if (!$user) {
            wp_send_json_error([
                'message' => 'کاربری با این شماره موبایل یافت نشد.'
            ]);
        }

        // بررسی رمز عبور
        if (!wp_check_password($password, $user->data->user_pass, $user->ID)) {
            wp_send_json_error([
                'message' => 'رمز عبور اشتباه است.'
                // 'message' => wp_check_password($password, $user->data->user_pass, $user->ID)
            ]);
        }

        // لاگین کاربر
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        wp_send_json_success([
            'message' => 'ورود با موفقیت انجام شد.',
            'result'  => 'login',
            'redirect' => home_url()
        ]);
    }

    public function handle_save_user_register_name() {
        check_ajax_referer('falnic_nonce', 'nonce');

        $mobile   = sanitize_text_field($_POST['mobile'] ?? '');
        $first_name   = sanitize_text_field($_POST['firstname'] ?? '');
        $last_name   = sanitize_text_field($_POST['lastname'] ?? '');
        $full_name  = $first_name . ' ' . $last_name;
        $mobile   = sanitize_text_field($_POST['mobile'] ?? '');

        $user = get_user_by('login', $mobile);

        if (!empty($first_name) && !preg_match('/^[ابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهیآأإؤئ\s\x{200C}]+$/u', $first_name)) {
            wp_send_json_error(['message' => 'لطفاً نام خود را فقط با حروف فارسی وارد کنید.']);
            return;
        }

        if (!empty($last_name) && !preg_match('/^[ابپتثجچحخدذرزژسشصضطظعغفقکگلمنوهیآأإؤئ\s\x{200C}]+$/u', $last_name)) {
            wp_send_json_error(['message' => 'لطفاً نام خانوادگی خود را فقط با حروف فارسی وارد کنید.']);
            return;
        }

        

        if (!$user) {
            $user_id = wp_create_user($mobile, wp_generate_password(), $mobile . '@falnic.user');
            $update = wp_update_user( array( 'ID' => $user_id, 'first_name' => $first_name, 'last_name' => $last_name, 'full_name' => $full_name, 'display_name' => $full_name ) );

            wp_send_json_success([
                'message'=> 'کاربر با موفقیت ثبت نام شد.'
            ]);
        }
        else {
            wp_send_json_error([
                'message'=> 'کاربر از قبل ثبت نام کرده است.'
            ]);
        }
    }

    public function redirect_login_url( $login_url, $redirect, $force_reauth ) {
        // آدرس صفحه لاگین خودت
        $custom_login_url = home_url( '/login/' );
        
        // حفظ ریدایرکت پس از لاگین (بازگشت به صفحه‌ای که کاربر در آن بود)
        if ( ! empty( $redirect ) ) {
            $custom_login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $custom_login_url );
        }
        
        return $custom_login_url;
    }
}

// برای هوک‌های AJAX بهتر است از 'init' استفاده کنید، اما 'after_setup_theme' هم در اینجا کار می‌کند
add_action('init', function () {
    new FalnicAuthHandler();
});


function captcha_verify($captcha)
{
    if (!session_id()) {
        session_start();
    }

    if (empty($captcha) || !isset($_SESSION['captcha_code'])) {
        return false;
    }

    $persian    = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english    = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $user_input = str_replace($persian, $english, trim($captcha));

    return ($user_input === $_SESSION['captcha_code']);
}

