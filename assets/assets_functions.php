<?php
function theme_enqueue()
{
    /*
     * پوستهٔ ee-* (همهٔ نماهای عمومی) استایل/اسکریپت خودش را در functions.php بارگذاری می‌کند.
     * فایل‌های قدیمی (style.css سراسری، Owl Carousel، main.js، front-page.css/js) فقط برای
     * برگه‌های مستقل (پنل کاربری) لازم‌اند؛ بارگذاری آن‌ها روی صفحهٔ اصلی جدید هم بی‌فایده بود
     * و هم با ریست سراسری `* { font-family }` روی طراحی جدید اثر می‌گذاشت.
     */
    $is_ee_view = function_exists('evented_is_ee_view') && evented_is_ee_view();

    if ($is_ee_view) {
        return;
    }

    wp_enqueue_style('theme-style', PATH_DIR_URL . '/style.css', '', '1.0.1');

    wp_enqueue_script('main', PATH_DIR_URL . '/assets/js/main.js', ['jquery'], '1.0.1', true);
    wp_localize_script('main', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('notification_nonce')));

    if (is_page()) {
        global $post;

        $panel_page = get_page_by_path('panel');

        // برگهٔ «panel» و زیربرگه‌هایش، یا هر برگه‌ای که یکی از قالب‌های
        // panel/*.php (Template Name: Panel - …) رویش انتخاب شده باشد.
        $panel_template = (string) get_page_template_slug();
        $is_panel_view  = (0 === strpos($panel_template, 'panel/'));

        if (
            $is_panel_view ||
            (
                $panel_page &&
                (
                    $post->ID == $panel_page->ID ||
                    in_array($panel_page->ID, get_post_ancestors($post))
                )
            )
        ) {
            wp_enqueue_style('panel-css', PATH_DIR_URL . '/assets/css/panel.css', [], '1.0.0');

            wp_enqueue_script('jalaliDatePicker', PATH_DIR_URL . '/assets/js/jalalidatepicker.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('panel-js', PATH_DIR_URL . '/assets/js/panel.js', ['jquery'], '1.0.0', true);
        }
    }
}

add_action('wp_enqueue_scripts', 'theme_enqueue');
