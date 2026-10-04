<?php
/**
 * بارگذاری دارایی‌های پنل کاربری
 *
 * پوستهٔ ee-* (همهٔ نماهای عمومی) استایل/اسکریپت خودش را در functions.php بارگذاری می‌کند.
 * پنل کاربری هم روی همان پوسته سوار است و فقط استایل/اسکریپت اختصاصی خود را اضافه می‌کند.
 * هیچ وابستگی به jQuery، style.css قدیمی یا کتابخانه‌های خارجی وجود ندارد.
 *
 * @package evented-edu
 */

defined('ABSPATH') || exit;

/**
 * آیا نمای جاری یکی از صفحه‌های پنل کاربری است؟
 */
function evented_is_panel_view()
{
    if (!is_page()) {
        return false;
    }

    $template = (string) get_page_template_slug();
    if (0 === strpos($template, 'panel/')) {
        return true;
    }

    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return false;
    }

    $panel_page = get_page_by_path('panel');

    return $panel_page instanceof WP_Post
        && ((int) $post->ID === (int) $panel_page->ID || in_array((int) $panel_page->ID, array_map('intval', get_post_ancestors($post)), true));
}

function theme_enqueue()
{
    if (!evented_is_panel_view()) {
        return;
    }

    wp_enqueue_style('ee-courses', PATH_DIR_URL . '/assets/css/newhome/ee-courses.css', array('ee-shell'), '1.3.0');
    wp_enqueue_style('ee-panel', PATH_DIR_URL . '/assets/css/newhome/ee-panel.css', array('ee-shell'), '1.2.0');
    wp_enqueue_style('panel-css', PATH_DIR_URL . '/assets/css/panel.css', array('ee-panel'), '2.4.0');

    /* موتور سبک تاریخ فقط در پروفایل و سایر نماهای دارای ورودی شمسی بارگذاری می‌شود. */
    $panel_section = function_exists('evented_panel_current_section') ? evented_panel_current_section() : '';
    if (('profile' === $panel_section || is_page_template('panel/profile.php')) && function_exists('evented_enqueue_jalali_picker')) {
        evented_enqueue_jalali_picker();
    }

    wp_enqueue_script('ee-panel', PATH_DIR_URL . '/assets/js/newhome/ee-panel.js', array(), '1.4.0', true);
    wp_localize_script('ee-panel', 'eePanel', array(
        'ajax_url'       => admin_url('admin-ajax.php'),
        'wishlist_nonce' => wp_create_nonce('wishlist_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'theme_enqueue', 25);
