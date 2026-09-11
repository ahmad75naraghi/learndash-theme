<?php
// اجازه آپلود فایل‌های دیگر
function add_custom_mime_types($mimes)
{

    $mimes['svg']  = 'image/svg+xml';
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
    if (defined('DOING_AJAX') && DOING_AJAX) {
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
    return $current_path === $target_path || str_starts_with($current_path, $target_path . '/');
}
