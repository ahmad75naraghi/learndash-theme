<?php

/**
 * The template for displaying the header
 *
 * This is the template that displays all of the <head> section, opens the <body> tag and adds the site's header.
 *
 * @package HelloElementor
 */
if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

$is_login = is_user_logged_in();
$current_user_id = get_current_user_id();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="/favicon.ico">
    <link rel="apple-touch-icon" href="/apple-touch-icon.ico">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

    <!-- هدر سایت -->
    <header class="site-header">
        <div class="header-container">
            <div class="header-right">
                <span class="open-menu desktop-hidden">
                    <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2.75 6.41602H19.25" stroke="#0A3762" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M2.75 6.41602H19.25" stroke="#093157" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M2.75 6.41602H19.25" stroke="#082B4C" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M2.75 11H19.25" stroke="#0A3762" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M2.75 11H19.25" stroke="#093157" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M2.75 11H19.25" stroke="#082B4C" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M2.75 15.584H19.25" stroke="#0A3762" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M2.75 15.584H19.25" stroke="#093157" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M2.75 15.584H19.25" stroke="#082B4C" stroke-width="1.5" stroke-linecap="round" />
                    </svg>

                </span>
                <a href="/" class="logo">
                    <!-- جایگزین با لوگوی واقعی -->
                    <img src='https://edu.falnic.com/wp-content/themes/edu-falnic/assets/img/front-page/Falnic-Logo-1-2.webp' alt="لوگو">
                </a>
                <div class="categories-dropdown mob-hidden">
                    <a href="/courses/" >
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M22 8.52V3.98C22 2.57 21.36 2 19.77 2H15.73C14.14 2 13.5 2.57 13.5 3.98V8.51C13.5 9.93 14.14 10.49 15.73 10.49H19.77C21.36 10.5 22 9.93 22 8.52Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M22 19.77V15.73C22 14.14 21.36 13.5 19.77 13.5H15.73C14.14 13.5 13.5 14.14 13.5 15.73V19.77C13.5 21.36 14.14 22 15.73 22H19.77C21.36 22 22 21.36 22 19.77Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M10.5 8.52V3.98C10.5 2.57 9.86 2 8.27 2H4.23C2.64 2 2 2.57 2 3.98V8.51C2 9.93 2.64 10.49 4.23 10.49H8.27C9.86 10.5 10.5 9.93 10.5 8.52Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                            <path d="M10.5 19.77V15.73C10.5 14.14 9.86 13.5 8.27 13.5H4.23C2.64 13.5 2 14.14 2 15.73V19.77C2 21.36 2.64 22 4.23 22H8.27C9.86 22 10.5 21.36 10.5 19.77Z" stroke="#292D32" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        دسته بندی دوره ها
                    </a>
                </div>
                <nav class="main-nav mob-hidden">
                    <ul>
                        <li><a href="https://falnic.com/contact-us">تماس با ما</a></li>
                        <li><a href="https://falnic.com/blog/">بلاگ</a></li>
                        <li><a href="/#categories-section">دوره ها</a></li>
                        <li><a href="/#instructors-section">اساتید</a></li>
                    </ul>
                </nav>
            </div>

            <div class="header-left">
                <!-- <div class="search-box">
                    <input type="text" placeholder="آموزشی که میخوای جستجو کن">
                    <span class="search-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M11.3647 19.2841C6.99884 19.2841 3.44434 15.7296 3.44434 11.3637C3.44434 6.99787 6.99884 3.44336 11.3647 3.44336C15.7306 3.44336 19.2851 6.99787 19.2851 11.3637C19.2851 15.7296 15.7306 19.2841 11.3647 19.2841ZM11.3647 4.60244C7.63247 4.60244 4.60341 7.63922 4.60341 11.3637C4.60341 15.0882 7.63247 18.125 11.3647 18.125C15.0969 18.125 18.126 15.0882 18.126 11.3637C18.126 7.63922 15.0969 4.60244 11.3647 4.60244Z" fill="#033333"></path>
                            <path d="M19.4786 20.0567C19.3318 20.0567 19.1849 20.0026 19.069 19.8867L17.5236 18.3413C17.2995 18.1172 17.2995 17.7463 17.5236 17.5222C17.7477 17.2981 18.1186 17.2981 18.3427 17.5222L19.8881 19.0677C20.1122 19.2917 20.1122 19.6627 19.8881 19.8867C19.7722 20.0026 19.6254 20.0567 19.4786 20.0567Z" fill="#033333"></path>
                        </svg></span>
                </div> -->
                <div class="cart-icon mob-hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path opacity="0.4" d="M16.0331 8.86234C15.6431 8.86234 15.3331 8.55234 15.3331 8.16234V6.88234C15.3331 5.90234 14.9131 4.96234 14.1931 4.30234C13.4531 3.63234 12.5031 3.32234 11.5031 3.41234C9.82312 3.57234 8.35313 5.28234 8.35313 7.06234V7.96234C8.35313 8.35234 8.04312 8.66234 7.65313 8.66234C7.26313 8.66234 6.95312 8.35234 6.95312 7.96234V7.06234C6.95312 4.56234 8.97313 2.25234 11.3631 2.02234C12.7531 1.89234 14.0931 2.33234 15.1231 3.27234C16.1431 4.19234 16.7231 5.51234 16.7231 6.88234V8.16234C16.7231 8.55234 16.4131 8.86234 16.0331 8.86234Z" fill="#FFA303"></path>
                        <path d="M19.8035 8.96252C18.9635 8.03252 17.5835 7.58252 15.5635 7.58252H8.1235C6.1035 7.58252 4.7235 8.03252 3.8835 8.96252C2.9135 10.0425 2.9435 11.4825 3.0535 12.4825L3.7535 18.0525C3.9635 20.0025 4.7535 22.0025 9.0535 22.0025H14.6335C18.9335 22.0025 19.7235 20.0025 19.9335 18.0625L20.6335 12.4725C20.7435 11.4825 20.7635 10.0425 19.8035 8.96252ZM8.2635 13.1525H8.2535C7.7035 13.1525 7.2535 12.7025 7.2535 12.1525C7.2535 11.6025 7.7035 11.1525 8.2535 11.1525C8.8135 11.1525 9.2635 11.6025 9.2635 12.1525C9.2635 12.7025 8.8135 13.1525 8.2635 13.1525ZM15.2635 13.1525H15.2535C14.7035 13.1525 14.2535 12.7025 14.2535 12.1525C14.2535 11.6025 14.7035 11.1525 15.2535 11.1525C15.8135 11.1525 16.2635 11.6025 16.2635 12.1525C16.2635 12.7025 15.8135 13.1525 15.2635 13.1525Z" fill="#FFA303"></path>
                    </svg>
                </div>
                <?php if (is_user_logged_in()) : ?>
                    <a href="/panel" class="btn-primary btn-login mob-hidden">پنل کاربری</a>
                <?php else : ?>
                    <a href="/login" class="btn-primary btn-login mob-hidden">ورود / ثبت نام</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="mobile-header-panel">
            <a href="/panel">
                <svg width="16" height="12" viewBox="0 0 16 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9.85833 0.75L14.9167 5.80833L9.85833 10.8667M0.75 5.80833H14.775" stroke="#333333" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
            <span>
                <?= get_the_title(); ?>
            </span>
        </div>
    </header>