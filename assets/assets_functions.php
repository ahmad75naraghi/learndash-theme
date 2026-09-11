<?php
function theme_enqueue()
{
    wp_enqueue_style('theme-style', PATH_DIR_URL . '/style.css', '', '1.0.0');

    wp_enqueue_style('owl.carousel', PATH_DIR_URL . '/assets/css/owl.carousel.min.css');
    wp_enqueue_script('owl.carousel', PATH_DIR_URL . '/assets/js/owl.carousel.min.js', ['jquery'], '', true);

    wp_enqueue_script('main', PATH_DIR_URL . '/assets/js/main.js', ['jquery'], '1.0.0', true);
    // wp_enqueue_script('main-ex', PATH_DIR_URL . '/assets/js/main-ex.js', '', '1.0.0', true);
    wp_localize_script('main', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('notification_nonce')));

    // صفحهٔ اصلی: پوستهٔ جدید (ee-shell.css + evented-home.css) در functions.php بارگذاری می‌شود؛
    // is_home() عمداً اینجا نیست چون برگهٔ نوشته‌ها از قالب آرشیو جدید استفاده می‌کند.
    if (is_page('home') || is_front_page()) {
        wp_enqueue_style('front-page', PATH_DIR_URL . '/assets/css/front-page.css', '', '1.0.0');
        wp_enqueue_script('front-page', PATH_DIR_URL . '/assets/js/front-page.js', '', '1.0.0', true);
        // wp_enqueue_script('front-page-ex', PATH_DIR_URL . '/assets/js/front-page-ex.js', '', '1.0.0', true);
    }
    // بایگانی/دستهٔ دوره، صفحهٔ دوره‌ها، آزمون، اساتید، پروفایل مدرس، برگه و ۴۰۴
    // همگی با پوستهٔ evented-edu رندر می‌شوند؛ استایل‌های آن‌ها (newhome/ee-courses.css
    // و newhome/ee-lms.css) در functions.php بارگذاری می‌شوند. فایل‌های قدیمی
    // archive-courses.css، author.css/js، single-page.css و archive-product.css
    // دیگر استفاده نمی‌شوند.
    
    if (is_page()) {
        global $post;

        $panel_page = get_page_by_path('panel');

        if (
            $panel_page &&
            (
                $post->ID == $panel_page->ID ||
                in_array($panel_page->ID, get_post_ancestors($post))
            )
        ) {
            wp_enqueue_style('panel-css', PATH_DIR_URL . '/assets/css/panel.css', [], '1.0.0');

            wp_enqueue_script('jalaliDatePicker', PATH_DIR_URL . '/assets/js/jalalidatepicker.min.js', ['jquery'], '1.0.0', true);
            wp_enqueue_script('panel-js', PATH_DIR_URL . '/assets/js/panel.js', ['jquery'], '1.0.0', true);
        }
    }
}

add_action('wp_enqueue_scripts', 'theme_enqueue');
