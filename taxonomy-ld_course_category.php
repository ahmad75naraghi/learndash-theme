<?php
get_header();

// دریافت اطلاعات دسته‌بندی فعلی
$queried_object = get_queried_object();
$term_name      = $queried_object->name ?? 'توضیحات دسته بندی';
$course_count   = $queried_object->count ?? 0;

// ---> کدهای جدید برای دریافت توضیحات سئو و سوالات متداول <---
$term_id           = $queried_object->term_id ?? 0;
$short_discription      = $queried_object->description ?? NULL;
$term_desc = get_term_meta($term_id, 'short_discription', true);
$faqs              = get_term_meta($term_id, 'ld_category_faqs', true);

?>

<main class="eduf-archive-main">
    <div class="eduf-container">

        <!-- مسیر راهنما (Breadcrumb) -->
        <nav class="eduf-breadcrumb">
            <a href="<?php echo home_url(); ?>">صفحه اصلی</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <a href="#">دوره ها</a>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <span class="current"><?php echo esc_html($term_name); ?></span>
        </nav>

        <!-- عنوان و توضیحات دسته -->
        <header class="eduf-category-header">
            <h1 class="eduf-title"><?php echo esc_html($term_name); ?></h1>
            <div class="eduf-desc">
                <?php echo wpautop(wp_kses_post($term_desc)); ?>
            </div>
        </header>

        <div class="eduf-layout-wrapper">

            <!-- سایدبار (فیلترها) -->
            <aside id="eduf-sidebar" class="eduf-sidebar">

                <div class="container-eduf-sidebar">
                    <div class="container-filter-close">
                        <span class="close-eduf-sidebar">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </span>
                        <div class="eduf-filter-header">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M5.40039 2.20312H18.6004C19.7004 2.20312 20.6004 3.10312 20.6004 4.20312V6.40312C20.6004 7.20312 20.1004 8.20312 19.6004 8.70312L15.3004 12.5031C14.7004 13.0031 14.3004 14.0031 14.3004 14.8031V19.1031C14.3004 19.7031 13.9004 20.5031 13.4004 20.8031L12.0004 21.7031C10.7004 22.5031 8.90039 21.6031 8.90039 20.0031V14.7031C8.90039 14.0031 8.50039 13.1031 8.10039 12.6031L4.30039 8.60312C3.80039 8.10312 3.40039 7.20312 3.40039 6.60312V4.30312C3.40039 3.10312 4.30039 2.20312 5.40039 2.20312Z" stroke="black" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M10.9305 2.20312L6.00049 10.1031" stroke="black" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            فیلترها
                        </div>
                    </div>

                    <div class="eduf-widget">
                        <button class="eduf-widget-toggle active">آموزش دوره <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg></button>
                        <div class="eduf-widget-content" style="display: block;">
                            <label class="eduf-checkbox"><input type="checkbox"> <span>مقدماتی</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span>پیشرفته</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span>متوسطه</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span>حرفه ای</span></label>
                        </div>
                    </div>
                    <div class="eduf-widget">
                        <button class="eduf-widget-toggle">نوع مخاطب / هدف شغلی<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg></button>
                        <div class="eduf-widget-content">
                            <label class="eduf-checkbox"><input type="checkbox"> <span>ادمین شبکه و دیتاسنتر</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span>تکنسین فنی و پشتیبانی / Helpdesk</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span>کاربری عمومی و اداری</span></label>
                        </div>
                    </div>
                    <div class="eduf-widget">
                        <button class="eduf-widget-toggle">اساتید دوره <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg></button>
                        <div class="eduf-widget-content">
                            <label class="eduf-checkbox"><input type="checkbox"> <span>فالنیک</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span>رضا کاظمی</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span>محمد نصیری</span></label>
                            <label class="eduf-checkbox"><input type="checkbox"> <span>احمد نراقی</span></label>

                        </div>
                    </div>
                    <div class="eduf-widget-btn">
                        <button class="filter-submit">اعمال فیلتر</button>
                        <button class="filter-reset">حذف فیلتر</button>
                    </div>
                </div>
                <div class="eduf-sidebar-overlay"></div>

            </aside>

            <!-- محتوای اصلی دوره‌ها -->
            <section class="eduf-courses-content">

                <!-- نوار ابزار (ترتیب و تعداد) -->
                <div class="eduf-toolbar">
                    <!-- دکمه‌های مخصوص موبایل -->
                    <div class="eduf-mobile-actions">
                        <button class="eduf-btn-mobile" data-active="eduf-sidebar"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M4.50028 1.83594H15.5003C16.417 1.83594 17.167 2.58594 17.167 3.5026V5.33594C17.167 6.0026 16.7503 6.83594 16.3336 7.2526L12.7503 10.4193C12.2503 10.8359 11.917 11.6693 11.917 12.3359V15.9193C11.917 16.4193 11.5836 17.0859 11.167 17.3359L10.0003 18.0859C8.91695 18.7526 7.41695 18.0026 7.41695 16.6693V12.2526C7.41695 11.6693 7.08362 10.9193 6.75028 10.5026L3.58362 7.16927C3.16695 6.7526 2.83362 6.0026 2.83362 5.5026V3.58594C2.83362 2.58594 3.58362 1.83594 4.50028 1.83594Z" stroke="black" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M9.10858 1.83594L5.00024 8.41927" stroke="black" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            فیلترها</button>
                        <button class="eduf-btn-mobile" data-active="eduf-sorting"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M2.5 5.83398H17.5" stroke="black" stroke-width="1.5" stroke-linecap="round" />
                                <path d="M5 10H15" stroke="black" stroke-width="1.5" stroke-linecap="round" />
                                <path d="M8.33337 14.166H11.6667" stroke="black" stroke-width="1.5" stroke-linecap="round" />
                            </svg>
                            مرتب سازی بر اساس</button>
                    </div>

                    <!-- مرتب‌سازی دسکتاپ -->
                    <div id="eduf-sorting" class="eduf-sorting">
                        <div class="container-eduf-sorting">
                            <div class="container-eduf-sorting-close">
                                <span class="close-eduf-sorting">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </span>
                                <span class="eduf-sort-label">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 7H21" stroke="black" stroke-width="1.5" stroke-linecap="round" />
                                        <path d="M6 12H18" stroke="black" stroke-width="1.5" stroke-linecap="round" />
                                        <path d="M10 17H14" stroke="black" stroke-width="1.5" stroke-linecap="round" />

                                    </svg>
                                    ترتیب

                                </span>
                            </div>

                            <div class="filter-options">
                                <label>
                                    <input type="radio" name="sort" value="default" checked>
                                    پیش‌فرض
                                </label>

                                <label>
                                    <input type="radio" name="sort" value="newest">
                                    جدیدترین ها
                                </label>

                                <label>
                                    <input type="radio" name="sort" value="popular">
                                    محبوب ترین ها
                                </label>

                                <label>
                                    <input type="radio" name="sort" value="top-rated">
                                    بالاترین امتیاز
                                </label>
                            </div>
                        </div>
                        <div class="eduf-sorting-overlay"></div>

                    </div>
                    <div class="eduf-course-count"><?php echo number_format_i18n($course_count); ?> دوره</div>
                </div>

                <!-- گرید دوره‌ها -->
                <div class="eduf-grid">
                    <?php
                    if (have_posts()) :
                        while (have_posts()) : the_post();
                            $course_id = get_the_ID();

                            // Extract native LearnDash meta
                            $course_meta = (array) get_post_meta($course_id, '_sfwd-courses', true);
                            $price_type  = $course_meta['sfwd-courses_course_price_type'] ?? '';
                            $price       = $course_meta['sfwd-courses_course_price'] ?? '';

                            // Fetch Taxonomies
                            $course_terms = get_the_terms($course_id, 'ld_course_category');
                            $course_tags  = get_the_terms($course_id, 'ld_course_tag');
                    ?>
                            <article class="eduf-card">
                                <a href="<?php the_permalink(); ?>" class="eduf-card-thumb">
                                    <?php
                                    if (has_post_thumbnail()) {
                                        the_post_thumbnail('medium', ['alt' => get_the_title()]);
                                    } else {
                                        $fallback_img = get_theme_file_uri('assets/img/front-page/Screenshot.webp');
                                        printf('<img src="%s" alt="%s">', esc_url($fallback_img), esc_attr(get_the_title()));
                                    }
                                    ?>
                                </a>
                                <div class="eduf-card-body">
                                    <h2 class="eduf-card-title">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h2>

                                    <div class="eduf-card-teacher">
                                        <?php echo esc_html(get_the_author()); ?>
                                    </div>

                                    <div class="eduf-card-badges">
                                        <?php
                                        // Render Categories
                                        if (! empty($course_terms) && ! is_wp_error($course_terms)) {
                                            foreach ($course_terms as $term) {
                                                printf('<span class="badge badge-purple">%s</span>', esc_html($term->name));
                                            }
                                        }
                                        // Render Tags
                                        if (! empty($course_tags) && ! is_wp_error($course_tags)) {
                                            foreach ($course_tags as $tag) {
                                                printf('<span class="badge badge-orange">%s</span>', esc_html($tag->name));
                                            }
                                        }
                                        ?>
                                    </div>

                                    <div class="eduf-card-price">
                                        <?php
                                        if (in_array($price_type, ['open', 'free'], true) || empty($price)) {
                                            echo '<ins>رایگان</ins>';
                                        } else {
                                            $formatted_price = is_numeric($price) ? number_format((float) $price) : esc_html($price);
                                            // Note: Add <del> here if you have a custom meta field for old prices.
                                            printf('<ins>%s <span>تومان</span></ins>', $formatted_price);
                                        }
                                        ?>
                                    </div>
                                </div>
                            </article>
                    <?php
                        endwhile;
                        wp_reset_postdata();
                    else :
                        echo '<p>دوره‌ای یافت نشد.</p>';
                    endif;
                    ?>
                </div>

                <!-- صفحه‌بندی -->
                <div class="eduf-pagination">
                    <?php
                    echo paginate_links(array(
                        'prev_text' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>',
                        'next_text' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>',
                    ));
                    ?>
                </div>

            </section>
        </div>

        <!-- بخش توضیحات سئو و سوالات متداول (پایین صفحه) -->
        <section class="eduf-bottom-content">
            <div class="eduf-seo-text">
                <?php
                // نمایش توضیحات در صورتی که خالی نباشد
                if (!empty($short_discription)) {
                    // چون از ویرایشگر وردپرس استفاده کردیم، خروجی به صورت خودکار تگ‌های HTML را دارد
                    echo wpautop(wp_kses_post($short_discription));
                }
                ?>
            </div>

            <?php if (!empty($faqs) && is_array($faqs)) : ?>
                <div class="eduf-faq-section">
                    <h3 class="eduf-faq-title">سوالات متداول</h3>

                    <?php foreach ($faqs as $faq) : ?>
                        <div class="eduf-faq-item">
                            <button class="eduf-faq-toggle">
                                <?php echo esc_html($faq['question']); ?>
                                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                </svg>
                            </button>
                            <div class="eduf-faq-content">
                                <p><?php echo wp_kses_post(nl2br($faq['answer'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<!-- اسکریپت ساده برای آکاردئون‌ها -->
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // آکاردئون سایدبار
        document.querySelectorAll('.eduf-widget-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const content = button.nextElementSibling;
                button.classList.toggle('active');
                content.style.display = content.style.display === 'block' ? 'none' : 'block';
            });
        });

        // آکاردئون سوالات متداول
        document.querySelectorAll('.eduf-faq-toggle').forEach(button => {
            button.addEventListener('click', () => {
                const content = button.nextElementSibling;
                const icon = button.querySelector('svg');
                const isOpen = content.style.display === 'block';
                content.style.display = isOpen ? 'none' : 'block';
                icon.innerHTML = isOpen ? '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>' : '<line x1="5" y1="12" x2="19" y2="12"></line>';
            });
        });
    });
</script>

<?php get_footer(); ?>