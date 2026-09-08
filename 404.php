<?php

/**
 * قالب نمایش صفحه 404 (پیدا نشد)
 */
get_header();
?>
<style>
    body {
        background-color: #fff;
    }
    .page-404-container {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 60vh;
        padding: 60px 20px;
        text-align: center;
    }
    .img-404 {
        max-width: 100%;
        width: 400px;
        height: auto;
        margin-bottom: 30px;
    }
    .title-404 {
        font-size: 2rem;
        font-weight: bold;
        margin-bottom: 15px;
        color: #333;
    }
    .desc-404 {
        font-size: 1rem;
        color: #666;
        margin-bottom: 40px;
        line-height: 1.6;
    }
</style>

<main class="page-404-container">
    <div class="content-404">

        <img src="<?php echo get_template_directory_uri(); ?>/assets/img/404.webp" alt="صفحه پیدا نشد!" class="img-404">

        <h1 class="title-404">اوپس! این صفحه وجود ندارد.</h1>
        <p class="desc-404">به نظر می‌رسد صفحه‌ای که به دنبال آن بودید حذف شده، تغییر نام داده یا آدرس آن را اشتباه وارد کرده‌اید.</p>

        <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-primary">بازگشت به صفحه اصلی</a>

    </div>
</main>

<?php
get_footer();
?>