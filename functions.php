<?php

define('PATH_DIR_URL', get_template_directory_uri());
define('PATH_DIR', get_template_directory());

require_once get_stylesheet_directory() . '/assets/assets_functions.php';
require_once get_stylesheet_directory() . '/inc/includes.php';
/*
 * صفحهٔ اصلی جدید (evented-edu pastel design)
 * فقط در front-page: استایل + فونت وزیرمتن + اسکریپت منو/چیپ
 */
add_action('wp_enqueue_scripts', function () {
    if (!is_front_page()) {
        return;
    }

    // فونت وزیرمتن (اولویت: گوگل‌فونت؛ در نبود آن فونت محلی دانا استفاده می‌شود)
    wp_enqueue_style('ee-vazirmatn', 'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap', array(), null);

    // آیکن‌های Material Symbols
    wp_enqueue_style('ee-material-icons', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap', array(), null);

    // استایل صفحهٔ اصلی
    wp_enqueue_style('ee-home', PATH_DIR_URL . '/assets/css/newhome/evented-home.css', array(), '1.0.0');

    // رفتار کوچک (منوی موبایل، فیلتر چیپ‌ها)
    wp_enqueue_script('ee-home-js', PATH_DIR_URL . '/assets/js/newhome/evented-home.js', array(), '1.0.0', true);
});
