<?php
// اجازه آپلود فایل‌های دیگر
function add_custom_mime_types($mimes)
{
    // SVG can contain scripts and external references. Without a dedicated SVG
    // sanitizer, allow it only to users WordPress trusts with unfiltered markup.
    // This also limits multisite uploads to super administrators.
    if (current_user_can('unfiltered_html')) {
        $mimes['svg'] = 'image/svg+xml';
    } else {
        unset($mimes['svg'], $mimes['svgz']);
    }
    $mimes['epub'] = 'application/epub+zip';
    $mimes['csv']  = 'text/csv';
    $mimes['ico'] = 'image/x-icon';

    return $mimes;
}
add_filter('upload_mimes', 'add_custom_mime_types');

add_theme_support('post-thumbnails');

/*
 * لوگوی سایت از «سفارشی‌سازی › هویت سایت» وردپرس خوانده می‌شود
 * (هدر و فوتر پوستهٔ ee-* با evented_logo_html() چاپ می‌کنند).
 */
add_theme_support('custom-logo', array(
	'height'      => 96,
	'width'       => 320,
	'flex-height' => true,
	'flex-width'  => true,
));

/**
 * هدایت کاربران مشترک به صفحه پنل پس از ورود
 */
function custom_subscriber_login_redirect($redirect_to, $request, $user)
{
    // بررسی اینکه آیا کاربر به درستی لاگین کرده است یا خیر
    if (isset($user->roles) && is_array($user->roles)) {
        // اگر نقش کاربر مشترک (subscriber) بود
        if (in_array('subscriber', $user->roles)) {
            return home_url('/panel'); // آدرس مقصد بعد از لاگین
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'custom_subscriber_login_redirect', 10, 3);

/**
 * مخفی کردن ادمین بار برای کاربران مشترک
 */
function hide_admin_bar_for_subscribers($show)
{
    if (current_user_can('subscriber')) {
        return false;
    }
    return $show;
}
add_filter('show_admin_bar', 'hide_admin_bar_for_subscribers');

/**
 * جلوگیری از دسترسی کاربران مشترک به پیشخوان (wp-admin) و هدایت به panel
 */
function restrict_subscriber_admin_access()
{
    // اگر درخواست از نوع AJAX بود، آن را مسدود نکنیم تا سایت دچار اختلال نشود
    if ((defined('DOING_AJAX') && DOING_AJAX) || (defined('DOING_CRON') && DOING_CRON) || wp_doing_ajax()) {
        return;
    }
    // فقط کاربران بدون هیچ دسترسی پیشخوان (مشترک خالص) هدایت می‌شوند
    if (current_user_can('edit_posts') || current_user_can('manage_options')) {
        return;
    }

    // بررسی اینکه آیا کاربر لاگین کرده است
    if (is_user_logged_in()) {
        $user = wp_get_current_user();

        // اگر کاربر نقش مشترک (subscriber) داشت
        if (in_array('subscriber', (array) $user->roles)) {
            // ریدایرکت به صفحه پنل و متوقف کردن اجرای بقیه کدها
            wp_redirect(home_url('/panel'));
            exit;
        }
    }
}
add_action('admin_init', 'restrict_subscriber_admin_access');


function is_current_path($path)
{
    $current_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
    $target_path = trim($path, '/');
    return $current_path === $target_path || 0 === strpos($current_path, $target_path . '/');
}




final class WP_User_Switcher {

    const ACTION_SWITCH = 'wp_switch_to_user';
    const ACTION_REVERT = 'wp_switch_back_admin';
    const COOKIE_NAME   = 'wp_admin_switcher_token';

    public static function init() {
        add_filter('user_row_actions', [__CLASS__, 'add_switch_action_link'], 10, 2);
        add_action('admin_init', [__CLASS__, 'handle_switch_to_user']);
        add_action('init', [__CLASS__, 'handle_switch_back']);
        add_action('wp_footer', [__CLASS__, 'render_switch_back_bar']);
        add_action('admin_footer', [__CLASS__, 'render_switch_back_bar']);
    }

    /**
     * افزودن لینک سوییچ به سطر هر کاربر در جدول Users
     */
    public static function add_switch_action_link(array $actions, WP_User $user): array {
        if (!current_user_can('manage_options') || $user->ID === get_current_user_id()) {
            return $actions;
        }

        $switch_url = wp_nonce_url(
            add_query_arg([
                'action'  => self::ACTION_SWITCH,
                'user_id' => $user->ID,
            ], admin_url('users.php')),
            'switch_to_' . $user->ID
        );

        $actions['switch_user'] = sprintf(
            '<a href="%s" style="color:#d63638; font-weight:600;">%s</a>',
            esc_url($switch_url),
            esc_html__('سوییچ', 'textdomain')
        );

        return $actions;
    }

    /**
     * پردازش سوییچ به کاربر مقصد و ذخیره توکن بازگشت ادمین
     */
    public static function handle_switch_to_user() {
        if (!isset($_GET['action']) || $_GET['action'] !== self::ACTION_SWITCH) {
            return;
        }

        $target_user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
        check_admin_referer('switch_to_' . $target_user_id);

        if (!current_user_can('manage_options') || !$target_user_id || !get_userdata($target_user_id)) {
            wp_die(
                esc_html__('دسترسی غیرمجاز یا کاربر مقصد نامعتبر است.', 'evented-edu'),
                '',
                array('response' => 403)
            );
        }

        $admin_id = get_current_user_id();

        // ساخت توکن امن یکبار مصرف برای احراز هویت بازگشت
        $token = wp_generate_password(32, false);
        set_transient('switch_auth_' . $token, $admin_id, HOUR_IN_SECONDS * 8);

        // ست کردن کوکی HttpOnly برای حفظ نشست ادمین
        setcookie(
            self::COOKIE_NAME,
            $token,
            [
                'expires'  => time() + (HOUR_IN_SECONDS * 8),
                'path'     => COOKIEPATH,
                'domain'   => COOKIE_DOMAIN,
                'secure'   => is_ssl(),
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        // لاگین به کاربر جدید
        wp_clear_auth_cookie();
        wp_set_current_user($target_user_id);
        wp_set_auth_cookie($target_user_id, false);

        wp_safe_redirect(admin_url());
        exit;
    }

    /**
     * پردازش بازگشت به اکانت اولیه ادمین
     */
    public static function handle_switch_back() {
        if (!isset($_GET['action']) || $_GET['action'] !== self::ACTION_REVERT) {
            return;
        }

        check_admin_referer(self::ACTION_REVERT);

        $token = isset($_COOKIE[self::COOKIE_NAME]) ? sanitize_text_field($_COOKIE[self::COOKIE_NAME]) : '';
        if (!$token) {
            wp_die(esc_html__('توکن بازگشت یافت نشد.', 'evented-edu'), '', array('response' => 403));
        }

        $original_admin_id = get_transient('switch_auth_' . $token);
        if (!$original_admin_id || !user_can($original_admin_id, 'manage_options')) {
            wp_die(esc_html__('نشست نامعتبر یا منقضی شده است.', 'evented-edu'), '', array('response' => 403));
        }

        // پاکسازی توکن و کوکی
        delete_transient('switch_auth_' . $token);
        setcookie(self::COOKIE_NAME, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);

        // ورود مجدد ادمین اصلی
        wp_clear_auth_cookie();
        wp_set_current_user($original_admin_id);
        wp_set_auth_cookie($original_admin_id, true);

        wp_safe_redirect(admin_url('users.php'));
        exit;
    }

    /**
     * نوار شناور بالای صفحه جهت بازگشت سریع به اکانت مدیر اصلی
     */
    public static function render_switch_back_bar() {
        if (empty($_COOKIE[self::COOKIE_NAME])) {
            return;
        }

        $token = sanitize_text_field($_COOKIE[self::COOKIE_NAME]);
        $original_admin_id = get_transient('switch_auth_' . $token);

        if (!$original_admin_id) {
            return;
        }

        $admin_user = get_userdata($original_admin_id);
        $revert_url = wp_nonce_url(
            add_query_arg(['action' => self::ACTION_REVERT], home_url()),
            self::ACTION_REVERT
        );
        ?>
        <div id="wp-switch-back-bar" style="position:fixed;top:0;left:0;right:0;width:100%;background:#1d2327;color:#fff;padding:8px 16px;z-index:999999;display:flex;align-items:center;justify-content:space-between;font-family:sans-serif;font-size:13px;border-bottom:2px solid #2271b1;box-shadow:0 2px 5px rgba(0,0,0,0.2);">
            <span>
                حالت سوییچ فعال است (وارد شده با عنوان: <strong><?php echo esc_html(wp_get_current_user()->display_name); ?></strong>)
            </span>
            <a href="<?php echo esc_url($revert_url); ?>" style="background:#2271b1;color:#fff;padding:5px 12px;border-radius:3px;text-decoration:none;font-weight:bold;">
                بازگشت به حساب مدیریت (<?php echo esc_html($admin_user->display_name); ?>)
            </a>
        </div>
        <style>
            html { margin-top: 38px !important; }
            * html body { margin-top: 38px !important; }
        </style>
        <?php
    }
}

WP_User_Switcher::init();