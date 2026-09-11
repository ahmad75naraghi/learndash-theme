<?php

define('PATH_DIR_URL', get_template_directory_uri());
define('PATH_DIR', get_template_directory());

require_once get_stylesheet_directory() . '/assets/assets_functions.php';
require_once get_stylesheet_directory() . '/inc/includes.php';

/*
 * پوستهٔ «evented-edu» (طراحی pastel با کلاس‌های ee-*)
 *
 * صفحات تحت پوشش (evented_is_ee_view):
 *   - صفحهٔ اصلی (front-page.php)          → ee-shell.css + evented-home.css
 *   - تک‌نوشته (single.php)                 → ee-shell.css + single-post.css
 *   - آرشیو/برگهٔ نوشته‌ها/جستجو            → ee-shell.css + archive-post.css
 *   - تک‌دوره/تک‌درس (single-sfwd-*.php)    → ee-shell.css + newhome/ee-lms.css
 */
add_action('wp_enqueue_scripts', function () {
    if (!function_exists('evented_is_ee_view') || !evented_is_ee_view()) {
        return;
    }

    // فونت وزیرمتن (اولویت: گوگل‌فونت؛ در نبود آن فونت محلی دانا استفاده می‌شود)
    wp_enqueue_style('ee-vazirmatn', 'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap', array(), null);

    // آیکن‌های Material Symbols
    wp_enqueue_style('ee-material-icons', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap', array(), null);

    // پوستهٔ مشترک: توکن‌ها، هدر، فوتر، نوار موبایل و ویجت‌های سایدبار
    wp_enqueue_style('ee-shell', PATH_DIR_URL . '/assets/css/newhome/ee-shell.css', array(), '1.1.0');

    // رفتارها: منوی موبایل، اسلایدر هیرو، کپی لینک اشتراک‌گذاری
    wp_enqueue_script('ee-home-js', PATH_DIR_URL . '/assets/js/newhome/evented-home.js', array(), '1.1.0', true);

    if (is_front_page()) {
        wp_enqueue_style('ee-home', PATH_DIR_URL . '/assets/css/newhome/evented-home.css', array('ee-shell'), '1.1.0');
    } elseif (is_singular('post')) {
        wp_enqueue_style('single-post', PATH_DIR_URL . '/assets/css/single-post.css', array('ee-shell'), '1.0.0');
    } elseif (is_singular(array('sfwd-courses', 'sfwd-lessons'))) {
        // دوره و درس: استایل + رفتارها (آکاردئون، دیدگاه، تکمیل درس، علاقه‌مندی)
        wp_enqueue_style('ee-lms', PATH_DIR_URL . '/assets/css/newhome/ee-lms.css', array('ee-shell'), '1.0.0');
        wp_enqueue_script('ee-lms', PATH_DIR_URL . '/assets/js/newhome/ee-lms.js', array(), '1.0.0', true);
        wp_localize_script('ee-lms', 'eeLms', array('ajax_url' => admin_url('admin-ajax.php')));
    } elseif (is_home() || is_archive() || is_search()) {
        wp_enqueue_style('archive-post', PATH_DIR_URL . '/assets/css/archive-post.css', array('ee-shell'), '1.0.0');
    }
}, 20);
