<?php
if (!defined('ABSPATH')) exit;

/**
 * شروع نشست کوتاه‌عمر احراز هویت با کوکی امن و نام اختصاصی.
 *
 * @return bool
 */
function evented_auth_start_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }
    if (headers_sent()) {
        return false;
    }

    $secure = is_ssl();
    $domain = defined('COOKIE_DOMAIN') && COOKIE_DOMAIN ? COOKIE_DOMAIN : '';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('evented_auth_' . substr(md5(home_url('/')), 0, 12));
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => $domain,
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ));

    return session_start();
}

/**
 * یکسان‌سازی ارقام و اعتبارسنجی شماره موبایل ایران.
 */
function evented_auth_mobile($mobile)
{
    $mobile = sanitize_text_field(wp_unslash((string) $mobile));
    return str_replace(
        array('۰','۱','۲','۳','۴','۵','۶','۷','۸','۹', '٠','١','٢','٣','٤','٥','٦','٧','٨','٩'),
        array('0','1','2','3','4','5','6','7','8','9', '0','1','2','3','4','5','6','7','8','9'),
        trim($mobile)
    );
}

/**
 * قانون رمز سمت سرور؛ همسان با چهار شرط نمایش‌داده‌شده در فرم ورود.
 *
 * @return string پیام خطا یا رشتهٔ خالی
 */
function evented_auth_password_error($password)
{
    if (strlen($password) < 8) {
        return 'رمز عبور باید حداقل ۸ کاراکتر باشد.';
    }
    if (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
        return 'رمز عبور باید شامل حروف کوچک و بزرگ انگلیسی باشد.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        return 'رمز عبور باید حداقل یک عدد داشته باشد.';
    }
    if (!preg_match('/[^A-Za-z0-9\s]/', $password)) {
        return 'رمز عبور باید حداقل یک نویسهٔ ویژه داشته باشد.';
    }
    return '';
}

/**
 * اعتبار زمانی تأیید OTP را در مراحل بعدی جریان ثبت‌نام/بازیابی کنترل می‌کند.
 */
function evented_auth_otp_is_fresh()
{
    $issued = isset($_SESSION['evented_otp_time']) ? (int) $_SESSION['evented_otp_time'] : 0;
    $ttl    = max(2, (int) (function_exists('evented_opt') ? evented_opt('otp_ttl', 10) : 10));
    return $issued > 0 && (time() - $issued) <= $ttl * MINUTE_IN_SECONDS;
}

class EventedAuthHandler
{
    public function __construct()
    {
        add_action('wp_ajax_evented_send_otp',                               [$this, 'handle_send_otp']);
        add_action('wp_ajax_nopriv_evented_send_otp',                        [$this, 'handle_send_otp']);
        add_action('wp_ajax_evented_check_mobile_and_send_otp',              [$this, 'handle_check_mobile_and_send_otp']);
        add_action('wp_ajax_nopriv_evented_check_mobile_and_send_otp',       [$this, 'handle_check_mobile_and_send_otp']);
        add_action('wp_ajax_evented_verify_otp',                             [$this, 'handle_verify_otp']);
        add_action('wp_ajax_nopriv_evented_verify_otp',                      [$this, 'handle_verify_otp']);
        add_action('wp_ajax_evented_reset_password',                         [$this, 'handle_reset_password']);
        add_action('wp_ajax_nopriv_evented_reset_password',                  [$this, 'handle_reset_password']);
        add_action('wp_ajax_evented_register_user',                          [$this, 'handle_register_user']);
        add_action('wp_ajax_nopriv_evented_register_user',                   [$this, 'handle_register_user']);

        add_action('wp_ajax_save_user_register_name',                      [$this, 'handle_save_user_register_name']);
        add_action('wp_ajax_nopriv_save_user_register_name',               [$this, 'handle_save_user_register_name']);

        add_action('wp_ajax_evented_login_user',                            [$this, 'handle_login_user']);
        add_action('wp_ajax_nopriv_evented_login_user',                     [$this, 'handle_login_user']);
        add_filter( 'login_url',                                           [$this, 'redirect_login_url'], 10, 3 );
        add_filter( 'logout_redirect',                                     [$this, 'custom_logout_redirect_to_home'], 10, 3 );

        /*
         * محافظ فرم استاندارد: اگر افزونه/کد دیگری wp-login.php را به /login هدایت کند،
         * مدیر هرگز نمی‌تواند وارد پیشخوان شود. اینجا هر ریدایرکتِ از wp-login.php به
         * صفحهٔ ورود سفارشی خنثی می‌شود (ریدایرکت‌های خود وردپرس بعد از ورود موفق دست نمی‌خورد).
         */
        add_filter( 'wp_redirect', [ $this, 'protect_wp_login_form' ], 999, 2 );
    }

    public function protect_wp_login_form( $location, $status ) {
        $pagenow = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';
        $uri     = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        $on_wp_login = ( 'wp-login.php' === $pagenow ) || ( false !== strpos( $uri, 'wp-login.php' ) );
        if ( ! $on_wp_login ) {
            return $location;
        }
        $target_path = (string) wp_parse_url( (string) $location, PHP_URL_PATH );
        $custom_path = (string) wp_parse_url( home_url( '/login/' ), PHP_URL_PATH );
        if ( '' !== $target_path && rtrim( $target_path, '/' ) === rtrim( $custom_path, '/' ) ) {
            // به‌جای رفتن به /login، همین فرم استاندارد نمایش داده شود
            return false;
        }
        return $location;
    }


    public function handle_verify_otp()
    {
        check_ajax_referer('evented_nonce', 'nonce');

        $mobile    = evented_auth_mobile($_POST['phone'] ?? '');
        $otp_input = sanitize_text_field($_POST['otp'] ?? '');

        if (!evented_auth_start_session()) {
            wp_send_json_error(array('message' => 'امکان آغاز نشست امن وجود ندارد. لطفاً دوباره تلاش کنید.'));
        }

        // کد باید ابتدا درخواست شده باشد
        if (empty($_SESSION['evented_otp']) || empty($_SESSION['evented_otp_time'])) {
            wp_send_json_error([
                'message' => 'ابتدا درخواست کد تایید دهید.'
            ]);
        }

        // انقضای کد (۱۰ دقیقه)
        if ((time() - (int) $_SESSION['evented_otp_time']) > max(2, (int) (function_exists('evented_opt') ? evented_opt('otp_ttl', 10) : 10)) * MINUTE_IN_SECONDS) {
            unset($_SESSION['evented_otp'], $_SESSION['evented_otp_time'], $_SESSION['evented_mobile'], $_SESSION['evented_otp_purpose'], $_SESSION['evented_otp_verified'], $_SESSION['evented_otp_verified_for']);
            wp_send_json_error([
                'message' => 'کد تایید منقضی شده است. لطفاً دوباره درخواست دهید.'
            ]);
        }

        // شماره‌ای که کد برایش صادر شده باید با شمارهٔ ارسالی یکی باشد
        if (!empty($mobile) && $mobile !== $_SESSION['evented_mobile']) {
            wp_send_json_error([
                'message' => 'شماره موبایل با کد تایید همخوانی ندارد.'
            ]);
        }

        if ((string) $_SESSION['evented_otp'] !== (string) $otp_input) {
            // محدود کردن تلاش برای حدس زدن کد
            $attempts = (int) ($_SESSION['evented_otp_attempts'] ?? 0) + 1;
            $_SESSION['evented_otp_attempts'] = $attempts;

            if ($attempts >= 5) {
                unset($_SESSION['evented_otp'], $_SESSION['evented_otp_time'], $_SESSION['evented_otp_attempts'], $_SESSION['evented_otp_verified'], $_SESSION['evented_otp_verified_for']);
                wp_send_json_error([
                    'message' => 'تعداد تلاش‌های ناموفق زیاد شد. لطفاً کد جدید درخواست دهید.'
                ]);
            }

            wp_send_json_error([
                'message' => 'کد تایید اشتباه است.'
            ]);
        }

        // کد درست بود؛ شمارندهٔ تلاش پاک شود
        unset($_SESSION['evented_otp_attempts']);
        session_regenerate_id(true);

        $mobile  = $_SESSION['evented_mobile'];
        $purpose = $_SESSION['evented_otp_purpose'] ?? '';

        // سناریوی بازیابی رمزعبور
        if ($purpose === 'reset_password') {

            $_SESSION['evented_otp_verified']     = true;
            $_SESSION['evented_otp_verified_for'] = 'reset_password';

            wp_send_json_success([
                'message' => 'شماره موبایل تایید شد.',
                'result'  => 'reset_password'
            ]);

            return;
        }

        $user = get_user_by('login', $mobile);

        // ثبت نام
        if (!$user) {

            $_SESSION['evented_otp_verified']     = true;
            $_SESSION['evented_otp_verified_for'] = 'register';

            wp_send_json_success([
                'is_new_user' => true,
                'message' => 'لطفا اطلاعات ثبت نام را تکمیل کنید.'
            ]);

            return;
        }

        // لاگین با OTP؛ دادهٔ یک‌بارمصرف پس از مصرف کامل پاک می‌شود.
        unset(
            $_SESSION['evented_otp'],
            $_SESSION['evented_otp_time'],
            $_SESSION['evented_otp_purpose'],
            $_SESSION['evented_otp_verified'],
            $_SESSION['evented_otp_verified_for'],
            $_SESSION['evented_mobile']
        );
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        wp_send_json_success([
            'message' => 'خوش آمدید.',
            'result'  => 'login',
            'nonce'   => wp_create_nonce('evented_nonce')
        ]);
    }

    public function handle_register_user()
    {
        check_ajax_referer('evented_nonce', 'nonce');

        $mobile          = evented_auth_mobile($_POST['phone'] ?? '');
        $password        = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
        $confirmPassword = isset($_POST['confirmPassword']) ? (string) wp_unslash($_POST['confirmPassword']) : '';

        if (!evented_auth_start_session()) {
            wp_send_json_error(array('message' => 'امکان آغاز نشست امن وجود ندارد. لطفاً دوباره تلاش کنید.'));
        }

        if (!preg_match('/^09[0-9]{9}$/', $mobile) || empty($password)) {
            wp_send_json_error(array('message' => 'شماره موبایل یا رمز عبور نامعتبر است.'));
        }
        $password_error = evented_auth_password_error($password);
        if ('' !== $password_error) {
            wp_send_json_error(array('message' => $password_error));
        }

        // بررسی اینکه آیا موبایل وارد شده و کد تایید قبلا وریفای شده است یا خیر
        if (
            empty($_SESSION['evented_mobile']) ||
            empty($_SESSION['evented_otp_verified']) ||
            !evented_auth_otp_is_fresh() ||
            ($_SESSION['evented_otp_verified_for'] ?? '') !== 'register' ||
            $_SESSION['evented_mobile'] !== $mobile
        ) {
            wp_send_json_error(['message' => 'دسترسی غیرمجاز یا پایان اعتبار نشست.']);
            return; // خروج از تابع
        }

        if ($confirmPassword !== $password) {
            wp_send_json_error(['message' => 'رمزعبور با تایید رمزعبور یکسان نیست.']);
        }


        $user = get_user_by('login', $mobile);

        if (!$user) {
            wp_send_json_error([
                'message' => 'کاربری با این شماره یافت نشد. ابتدا اطلاعات ثبت‌نام را کامل کنید.'
            ]);
        }

        // ثبت رمز عبور (خروجی این تابع user_id نیست؛ فقط رمز را آپدیت می‌کند)
        wp_set_password($password, $user->ID);

        // پاک‌سازی نشست تایید
        unset(
            $_SESSION['evented_otp_verified'],
            $_SESSION['evented_otp_verified_for'],
            $_SESSION['evented_otp'],
            $_SESSION['evented_otp_time'],
            $_SESSION['evented_otp_purpose'],
            $_SESSION['evented_mobile']
        );

        // لاگین خودکار
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        $new_nonce = wp_create_nonce('evented_nonce');
        wp_send_json_success([
            'message' => 'ثبت نام با موفقیت انجام شد.',
            'nonce' => $new_nonce,
            'result'  => 'login'
        ]);
    }

    public function handle_send_otp()
    {
        check_ajax_referer('evented_nonce', 'nonce');

        $mobile  = evented_auth_mobile($_POST['mobile'] ?? '');
        $purpose = sanitize_key($_POST['purpose'] ?? '');

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

        if (!evented_auth_start_session()) {
            wp_send_json_error(array('message' => 'امکان آغاز نشست امن وجود ندارد. لطفاً دوباره تلاش کنید.'));
        }

        $otp = wp_rand(10000, 99999);

        unset($_SESSION['evented_otp_verified'], $_SESSION['evented_otp_verified_for']);
        $_SESSION['evented_otp']         = $otp;
        $_SESSION['evented_mobile']      = $mobile;
        $_SESSION['evented_otp_time']    = time();
        $_SESSION['evented_otp_purpose'] = $purpose;
        unset($_SESSION['evented_otp_attempts']); // شروع دوبارهٔ شمارندهٔ تلاش

        $sms = send_pattern_sms($mobile, (string) $otp);

        if (!$sms) {
            wp_send_json_error([
                'message' => 'خطا در ارسال پیامک.'
            ]);
        }

        evented_send_otp_with_bale($mobile, $otp);

        set_transient('otp_limit_' . $mobile, true, max(30, (int) (function_exists('evented_opt') ? evented_opt('otp_rate', 60) : 60)));

        wp_send_json_success([
            'message' => 'کد تایید ارسال شد.',
            'purpose' => $purpose
        ]);
    }

    public function handle_check_mobile_and_send_otp()
    {
        check_ajax_referer('evented_nonce', 'nonce');

        $mobile = evented_auth_mobile($_POST['mobile'] ?? '');

        if ($this->is_rate_limited($mobile)) {
            wp_send_json_error(['message' => 'شما به تازگی یک کد برای این شماره دریافت کرده اید.']);
        }

        if (!evented_auth_start_session()) {
            wp_send_json_error(array('message' => 'امکان آغاز نشست امن وجود ندارد. لطفاً دوباره تلاش کنید.'));
        }

        if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
            wp_send_json_error(['message' => 'شماره موبایل معتبر نیست.']);
        }

        $user_status = get_user_by('login', $mobile) ? 'login' : 'register';

        if ($user_status === "register") {
            $otp = wp_rand(10000, 99999);
            unset($_SESSION['evented_otp_verified'], $_SESSION['evented_otp_verified_for']);
            $_SESSION['evented_otp']      = $otp;
            $_SESSION['evented_mobile']   = $mobile;
            $_SESSION['evented_otp_time'] = time();
            $_SESSION['evented_otp_purpose'] = 'register';
            unset($_SESSION['evented_otp_attempts']);

            $sms = send_pattern_sms($mobile, (string)$otp);
            if ($sms) {
                evented_send_otp_with_bale($mobile, $otp);
                set_transient('otp_limit_' . $mobile, true, max(30, (int) (function_exists('evented_opt') ? evented_opt('otp_rate', 60) : 60)));
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
        check_ajax_referer('evented_nonce', 'nonce');

        if (!evented_auth_start_session()) {
            wp_send_json_error(array('message' => 'امکان آغاز نشست امن وجود ندارد. لطفاً دوباره تلاش کنید.'));
        }

        $mobile          = evented_auth_mobile($_POST['phone'] ?? '');
        $password        = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';
        $confirmPassword = isset($_POST['confirmPassword']) ? (string) wp_unslash($_POST['confirmPassword']) : '';

        if (
            empty($mobile) ||
            empty($password) ||
            empty($confirmPassword)
        ) {
            wp_send_json_error([
                'message' => 'اطلاعات ارسالی ناقص است.'
            ]);
        }

        $password_error = evented_auth_password_error($password);
        if ('' !== $password_error) {
            wp_send_json_error(array('message' => $password_error));
        }

        if ($password !== $confirmPassword) {
            wp_send_json_error([
                'message' => 'رمز عبور و تکرار آن یکسان نیستند.'
            ]);
        }

        // بررسی اینکه OTP مخصوص بازیابی رمز تایید شده باشد
        if (
            empty($_SESSION['evented_otp_verified']) ||
            empty($_SESSION['evented_otp_verified_for']) ||
            !evented_auth_otp_is_fresh() ||
            $_SESSION['evented_otp_verified_for'] !== 'reset_password'
        ) {
            wp_send_json_error([
                'message' => 'ابتدا شماره موبایل خود را تایید کنید.'
            ]);
        }

        // جلوگیری از دستکاری شماره موبایل
        if (
            empty($_SESSION['evented_mobile']) ||
            $_SESSION['evented_mobile'] !== $mobile
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
        unset(
            $_SESSION['evented_otp'],
            $_SESSION['evented_otp_time'],
            $_SESSION['evented_otp_purpose'],
            $_SESSION['evented_otp_verified'],
            $_SESSION['evented_otp_verified_for'],
            $_SESSION['evented_mobile']
        );

        // لاگین خودکار
        wp_clear_auth_cookie();
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID, true);

        $new_nonce = wp_create_nonce('evented_nonce');

        wp_send_json_success([
            'message' => 'رمز عبور با موفقیت تغییر کرد.',
            'result'  => 'login',
            'nonce'   => $new_nonce
        ]);
    }


    /**
     * آیا برای این درخواست باید صفحهٔ ورود سفارشی (/login) استفاده شود؟
     */
    private function should_use_custom_login( $redirect, $force_reauth ) {
        if ( $force_reauth || is_admin() ) {
            return false;
        }

        $pagenow = isset( $GLOBALS['pagenow'] ) ? (string) $GLOBALS['pagenow'] : '';
        $uri     = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
        if ( 'wp-login.php' === $pagenow || false !== strpos( $uri, 'wp-login.php' ) || false !== strpos( $uri, '/wp-admin' ) ) {
            return false;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['admin'] ) || isset( $_GET['interim-login'] ) ) {
            return false;
        }

        if ( ! empty( $redirect ) ) {
            $path = (string) wp_parse_url( $redirect, PHP_URL_PATH );
            if ( false !== strpos( $path, '/wp-admin' ) || false !== strpos( $path, 'wp-login.php' ) ) {
                return false;
            }
        }

        return true;
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
        check_ajax_referer('evented_nonce', 'nonce');

        $mobile   = evented_auth_mobile($_POST['mobile'] ?? '');
        $password = isset($_POST['password']) ? (string) wp_unslash($_POST['password']) : '';

        if (empty($mobile) || empty($password)) {
            wp_send_json_error([
                'message' => 'شماره موبایل و رمزعبور الزامی است.'
            ]);
        }

        if (!preg_match('/^09[0-9]{9}$/', $mobile)) {
            wp_send_json_error(array('message' => 'شماره موبایل یا رمز عبور صحیح نیست.'));
        }

        $user = get_user_by('login', $mobile);

        // محدودیت مستقل برای ترکیب شماره و IP؛ کاربرِ موجود/ناموجود پاسخ یکسان می‌گیرد.
        $remote_ip  = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $fail_key   = 'evented_login_fail_' . md5($mobile . '|' . $remote_ip);
        $fail_count = (int) get_transient($fail_key);

        if ($fail_count >= 5) {
            wp_send_json_error(array(
                'message' => 'تلاش‌های ناموفق زیاد است. لطفاً ۱۵ دقیقه دیگر دوباره تلاش کنید.'
            ));
        }

        if (!$user || !wp_check_password($password, $user->data->user_pass, $user->ID)) {
            set_transient($fail_key, $fail_count + 1, 15 * MINUTE_IN_SECONDS);
            wp_send_json_error(array(
                'message' => 'شماره موبایل یا رمز عبور صحیح نیست.'
            ));
        }

        // ورود موفق؛ شمارندهٔ شکست پاک شود
        delete_transient($fail_key);

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
        check_ajax_referer('evented_nonce', 'nonce');

        $mobile   = evented_auth_mobile($_POST['mobile'] ?? '');
        $first_name   = sanitize_text_field($_POST['firstname'] ?? '');
        $last_name   = sanitize_text_field($_POST['lastname'] ?? '');

        if (!evented_auth_start_session()) {
            wp_send_json_error(array('message' => 'امکان آغاز نشست امن وجود ندارد. لطفاً دوباره تلاش کنید.'));
        }

        // امنیت: فقط پس از تأیید OTP برای همین شماره اجازهٔ ساخت حساب بده
        $verified_mobile = $_SESSION['evented_mobile'] ?? '';
        if (
            empty($_SESSION['evented_otp_verified'])
            || !evented_auth_otp_is_fresh()
            || ($_SESSION['evented_otp_verified_for'] ?? '') !== 'register'
            || $verified_mobile !== $mobile
        ) {
            wp_send_json_error(['message' => 'ابتدا کد تایید شماره موبایل را تایید کنید.']);
            return;
        }

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
            // ساخت حساب: نام کاربری = موبایل، ایمیل placeholder
            $user_id = wp_create_user($mobile, wp_generate_password(), $mobile . '@evented-edu.user');

            if (is_wp_error($user_id)) {
                wp_send_json_error(['message' => 'خطا در ساخت حساب: ' . $user_id->get_error_message()]);
                return;
            }

            $name_args = array('ID' => $user_id);
            if (!empty($first_name)) { $name_args['first_name'] = $first_name; }
            if (!empty($last_name))  { $name_args['last_name']  = $last_name; }
            if (!empty($first_name) || !empty($last_name)) {
                $name_args['display_name'] = trim($first_name . ' ' . $last_name);
            }
            wp_update_user($name_args);

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
        /*
         * فقط لینک‌های «ورود» در فرانت‌اند به /login (ورود با موبایل) تغییر می‌کنند.
         * هر جا پای پیشخوان/فرم استاندارد وردپرس در میان باشد، آدرس اصلی wp-login.php
         * دست‌نخورده برمی‌گردد تا مدیر بتواند با نام کاربری و رمز وارد wp-admin شود.
         */
        if ( ! $this->should_use_custom_login( $redirect, $force_reauth ) ) {
            return $login_url;
        }

        // آدرس صفحه لاگین خودت
        $custom_login_url = home_url( '/login/' );
        
        // حفظ ریدایرکت پس از لاگین (بازگشت به صفحه‌ای که کاربر در آن بود)
        // نکته: add_query_arg خودش مقدار را urlencode می‌کند؛ urlencode دستیِ اضافه باعث
        // double-encode می‌شد و redirect_to در صفحهٔ لاگین خراب به دست می‌رسید.
        if ( ! empty( $redirect ) ) {
            $custom_login_url = add_query_arg( 'redirect_to', $redirect, $custom_login_url );
        }
        
        return $custom_login_url;
    }
}

// برای هوک‌های AJAX بهتر است از 'init' استفاده کنید، اما 'after_setup_theme' هم در اینجا کار می‌کند
add_action('init', function () {
    new EventedAuthHandler();
});



/**
 * ارسال کد تایید از طریق ربات «بله».
 *
 * ارسال واقعی به یک تابع بیرونی (mu-plugin/افزونه) واگذار می‌شود. برای سازگاری با
 * نصب‌های قدیمی‌تر، نام قدیمی falnic_send_otp_with_bale() هم پشتیبانی می‌شود؛
 * اگر هیچ‌کدام موجود نباشد، به‌جای Fatal Error فقط لاگ می‌شود تا جریان پیامک ادامه یابد.
 */
if (!function_exists('evented_send_otp_with_bale')) {
    function evented_send_otp_with_bale($mobile, $otp)
    {
        if (function_exists('falnic_send_otp_with_bale')) {
            return falnic_send_otp_with_bale($mobile, $otp);
        }
        // سرویس بله در دسترس نیست؛ فقط لاگ می‌کنیم تا جریان OTP از طریق پیامک ادامه یابد.
        error_log('evented-edu: Bale OTP sender is not available for ' . $mobile);
        return false;
    }
}

