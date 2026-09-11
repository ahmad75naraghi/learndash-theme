<?php

defined('ABSPATH') || exit;

if ( ! is_user_logged_in() ) {
    wp_redirect(add_query_arg('redirect_to', home_url('/panel'), wp_login_url()));
    exit;
}

/*
 * برگهٔ «panel» به‌تنهایی قالب محتوایی ندارد؛ اگر مدیر برای همین برگه
 * «Template Name: Panel - Dashboard» را انتخاب کرده باشد، وردپرس اصلاً
 * به این فایل نمی‌رسد. در غیر این صورت کاربر لاگین‌شده را به داشبورد
 * (my-courses) هدایت می‌کنیم تا صفحه خالی نبیند.
 */
wp_redirect(home_url('/panel/my-courses'));
exit;
