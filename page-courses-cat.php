<?php

/**
 * Template Name: صفحه دسته‌بندی دوره‌های لرن‌دش
 * Description: نمایش شبکه‌ای و زیبای تمام دسته‌بندی‌های دوره‌ها
 */
get_header();
?>
<style>
    /* تنظیمات کلی صفحه و هدر */
    .ld-categories-page {
        background-color: #f8f9fc;
        padding-bottom: 80px;
    }

    .ld-categories-page .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    /* بنر هدر */
    .ld-hero-section {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        /* رنگ گرادیانت آبی جذاب */
        padding: 80px 0;
        text-align: center;
        color: #fff;
        margin-bottom: -60px;
        /* کشیدن کانتینر اصلی روی بنر برای عمق دادن به طراحی */
    }

    .ld-hero-title {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 15px;
        color: #fff;
    }

    .ld-hero-desc {
        font-size: 1.1rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
    }

    /* شبکه گرید کارت‌ها */
    .ld-categories-wrapper {
        position: relative;
        z-index: 10;
    }

    .ld-categories-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(270px, 2fr));
        gap: 30px;
        margin-top: 40px;
    }

    /* طراحی کارت هر دسته‌بندی */
    .ld-cat-card {
        background: #fff;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        text-decoration: none;
        display: flex;
        flex-direction: column;
    }

    .ld-cat-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    }

    /* بخش عکس کارت */
    .ld-cat-image-wrap {
        position: relative;
        width: 100%;
        height: 220px;
        overflow: hidden;
    }

    .ld-cat-image {
        width: 100%;
        height: 100%;
        object-fit: contain;
        transition: transform 0.5s ease;
    }

    .ld-cat-card:hover .ld-cat-image {
        transform: scale(1.08);
        /* زوم ملایم عکس در هنگام هاور */
    }

    /* بج تعداد دوره‌ها روی عکس */
    .ld-course-count {
        position: absolute;
        top: 15px;
        right: 15px;
        /* برای سایت‌های راست‌چین */
        background: rgba(0, 0, 0, 0.65);
        backdrop-filter: blur(4px);
        color: #fff;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 30px;
        z-index: 2;
    }

    /* محتوای متنی کارت */
    .ld-cat-content {
        padding: 25px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .ld-cat-title {
        font-size: 1.4rem;
        color: #222;
        margin-bottom: 12px;
        font-weight: 700;
    }

    .ld-cat-desc {
        font-size: 0.95rem;
        color: #666;
        line-height: 1.6;
        margin-bottom: 20px;
        flex-grow: 1;
        /* باعث می‌شود دکمه‌ها در یک خط هم‌تراز شوند */
    }

    /* دکمه "مشاهده دوره‌ها" در پایین کارت */
    .ld-cat-btn {
        display: inline-block;
        color: #2a5298;
        font-weight: bold;
        font-size: 1rem;
        transition: color 0.2s ease;
    }

    .ld-cat-card:hover .ld-cat-btn {
        color: #1e3c72;
    }
@media (max-width: 768px) {
.ld-categories-grid {
    grid-template-columns: repeat(1, minmax(163px, 68fr));}
    }

</style>
<main class="ld-categories-page">

    <section class="ld-hero-section">
        <div class="container">
            <h1 class="ld-hero-title">دسته‌بندی دوره‌های آموزشی</h1>
            <p class="ld-hero-desc">مسیر یادگیری خود را انتخاب کنید و از همین امروز شروع به یادگیری مهارت‌های جدید کنید.</p>
        </div>
    </section>

    <section class="ld-categories-wrapper">
        <div class="container">
            <div class="ld-categories-grid">

                <?php
                // تنظیمات فراخوانی دسته‌بندی‌های لرن‌دش
                $args = array(
                    'taxonomy'   => 'ld_course_category',
                    'hide_empty' => true,
                );
                $categories = get_terms($args);

                if (! empty($categories) && ! is_wp_error($categories)) :
                    foreach ($categories as $category) :

                        $term_link = get_term_link($category);

                        // --- بخش ویرایش شده ---
                        // خواندن عکس از فیلدی که خودمان در وردپرس ساختیم
                        $image_id = get_term_meta($category->term_id, 'ld_cat_image_id', true);
                        $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'large') : get_template_directory_uri() . '/images/default-cat.jpg';
                        // -----------------------
                ?>

                        <a href="<?php echo esc_url($term_link); ?>" class="ld-cat-card">

                            <div class="ld-cat-image-wrap">
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($category->name); ?>" class="ld-cat-image">
                                <span class="ld-course-count">
                                    <?php echo sprintf(_n('%s دوره', '%s دوره', $category->count), number_format_i18n($category->count)); ?>
                                </span>
                            </div>

                            <div class="ld-cat-content">
                                <h3 class="ld-cat-title"><?php echo esc_html($category->name); ?></h3>
                                <?php if (! empty($category->description)) : ?>
                                    <p class="ld-cat-desc"><?php echo wp_trim_words($category->description, 15, '...'); ?></p>
                                <?php endif; ?>
                                <span class="ld-cat-btn">مشاهده دوره‌ها <i class="icon-arrow-left"></i></span>
                            </div>

                        </a>

                <?php
                    endforeach;
                else :
                    echo '<p class="no-categories">در حال حاضر دسته‌بندی برای دوره‌ها وجود ندارد.</p>';
                endif;

                ?>

            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>