<?php
get_header();

// دریافت اطلاعات نویسنده/مدرس این صفحه
$author = get_queried_object();
$author_id = $author->ID;

$author_name = get_the_author_meta('display_name', $author_id);
$author_bio = get_the_author_meta('description', $author_id);
$about_teacher = get_the_author_meta('about_teacher', $author_id);

// دریافت آواتار سفارشی (با استفاده از هک‌های قبلی مچ شده است)
$author_avatar = get_avatar_url($author_id, array('size' => 150));

// دریافت شبکه‌های اجتماعی (فیلدهای پیش‌فرض یا سفارشی پروفایل)
$youtube   = get_user_meta($author_id, 'youtube', true);
$linkedin  = get_user_meta($author_id, 'linkedin', true);
$instagram = get_user_meta($author_id, 'instagram', true);

// آمار لرن‌دش
$course_count = count_user_posts($author_id, 'sfwd-courses'); // تعداد دوره‌ها

// محاسبه تعداد دانشجوها (نمونه محاسباتی استاندارد لرن‌دش)
$total_students = 0;
// ابتدا آیدی تمام دوره‌های این مدرس را می‌گیریم
$courses_args = array(
    'post_type'      => 'sfwd-courses',
    'author'         => $author_id,
    'posts_per_page' => -1,
    'fields'         => 'ids' // برای بهینه‌سازی فقط ID ها را می‌گیریم
);
$course_ids = get_posts($courses_args);

if (!empty($course_ids)) {
    // آرایه‌ای برای ذخیره کاربران یکتا
    $unique_students = array();

    foreach ($course_ids as $course_id) {
        // دریافت آبجکت کوئری کاربران دوره از لرن‌دش
        $user_query = learndash_get_users_for_course($course_id, array(), false);

        // بررسی اینکه آیا خروجی یک آبجکت معتبر WP_User_Query است
        if ($user_query instanceof WP_User_Query) {
            // استخراج آرایه آیدی کاربران واقعی با متد get_results()
            $course_users = $user_query->get_results();

            if (!empty($course_users) && is_array($course_users)) {
                foreach ($course_users as $user) {
                    // گرفتن شناسه کاربر (با توجه به اینکه فیلد روی ID ست شده، مستقیماً آیدی یا آبجکت است)
                    $student_id = is_object($user) ? $user->ID : $user;
                    $unique_students[$student_id] = true;
                }
            }
        }
    }
    // تعداد کل دانشجویان یکتا
    $total_students = count($unique_students);
}
?>
<div class="container">
    <nav class="breadcrumb">
        <a href="/">خانه</a>
        <span class="divider">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10.0002 13.2807L5.65355 8.93404C5.14022 8.4207 5.14022 7.5807 5.65355 7.06737L10.0002 2.7207" stroke="#333333" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <a href="/author/">اساتید</a>
        <span class="divider">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M10.0002 13.2807L5.65355 8.93404C5.14022 8.4207 5.14022 7.5807 5.65355 7.06737L10.0002 2.7207" stroke="#333333" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </span>
        <span class="current"><?php echo esc_html($author_name); ?></span>
    </nav>
    <div class="hero-banner">
        <div class="play-btn">
            <svg width="84" height="87" viewBox="0 0 84 87" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M62.879 40.5222C65.5776 42.0555 65.5776 45.9445 62.879 47.4778L30.976 65.6045C28.3095 67.1196 25 65.1936 25 62.1267V25.8733C25 22.8064 28.3095 20.8804 30.976 22.3955L62.879 40.5222Z" stroke="black" />
                <path d="M42 0.5C64.9034 0.5 83.5 19.7351 83.5 43.5C83.5 67.2649 64.9034 86.5 42 86.5C19.0966 86.5 0.5 67.2649 0.5 43.5C0.5 19.7351 19.0966 0.5 42 0.5Z" stroke="black" />
            </svg>
        </div>
    </div>

    <div class="profile-section">
        <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author_name); ?>" class="profile-avatar">
        <h1 class="profile-name">
            <?php echo esc_html($author_name); ?>
        </h1>
        <p class="profile-bio"><?php echo esc_html($author_bio ?: 'توضیحات بیوگرافی برای این مدرس ثبت نشده است.'); ?></p>

        <div class="social-links">
            <?php if (!empty($youtube)): ?><a href="<?php echo esc_url($youtube); ?>" class="social-link" target="_blank"><svg width="20" height="16" viewBox="0 0 20 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.4" d="M15 16H5C2 16 0 14 0 11V5C0 2 2 0 5 0H15C18 0 20 2 20 5V11C20 14 18 16 15 16Z" fill="#167BDC" />
                        <path d="M9.41962 5.49009L11.8896 6.9701C12.8296 7.5401 12.8296 8.46009 11.8896 9.03009L9.41962 10.5101C8.41962 11.1101 7.59961 10.6501 7.59961 9.4801V6.5101C7.59961 5.3501 8.41962 4.89009 9.41962 5.49009Z" fill="#167BDC" />
                    </svg>
                    یوتیوب</a><?php endif; ?>
            <?php if (!empty($linkedin)): ?><a href="<?php echo esc_url($linkedin); ?>" class="social-link" target="_blank"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.9845 0H9.93914C4.44991 0 0 4.45122 0 9.94206V9.98745C0 15.4783 4.44991 19.9295 9.93914 19.9295H9.9845C15.4737 19.9295 19.9237 15.4783 19.9237 9.98745V9.94206C19.9237 4.45122 15.4737 0 9.9845 0Z" fill="#9FC8F0" />
                        <path d="M4.75137 6.62403C4.48892 6.38035 4.3584 6.07872 4.3584 5.71983C4.3584 5.36094 4.48961 5.04604 4.75137 4.80166C5.01382 4.55798 5.35168 4.43579 5.7656 4.43579C6.17953 4.43579 6.50411 4.55798 6.76587 4.80166C7.02832 5.04534 7.15885 5.35186 7.15885 5.71983C7.15885 6.0878 7.02763 6.38035 6.76587 6.62403C6.50342 6.86772 6.17045 6.98991 5.7656 6.98991C5.36074 6.98991 5.01382 6.86772 4.75137 6.62403ZM6.93829 8.02188V15.4929H4.57826V8.02188H6.93829Z" fill="#167BDC" />
                        <path d="M14.7945 8.75999C15.309 9.31857 15.5659 10.0852 15.5659 11.0614V15.3611H13.3246V11.3644C13.3246 10.8722 13.1968 10.4895 12.9421 10.2172C12.6873 9.94488 12.3438 9.80803 11.9139 9.80803C11.4838 9.80803 11.1405 9.94418 10.8857 10.2172C10.6308 10.4895 10.5031 10.8722 10.5031 11.3644V15.3611H8.24854V8.00101H10.5031V8.97714C10.7314 8.65176 11.0392 8.39482 11.4259 8.2056C11.8127 8.01638 12.2475 7.92212 12.7312 7.92212C13.5926 7.92212 14.2808 8.2014 14.7945 8.75928V8.75999Z" fill="#167BDC" />
                    </svg>
                    لینکدین</a><?php endif; ?>
            <?php if (!empty($facebook)): ?><a href="<?php echo esc_url($facebook); ?>" class="social-link" target="_blank"><i class="fa-brands fa-facebook"></i> فیسبوک</a><?php endif; ?>
            <?php if (!empty($instagram)): ?><a href="<?php echo esc_url($instagram); ?>" class="social-link" target="_blank"><svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path opacity="0.4" d="M14.19 0H5.81C2.17 0 0 2.17 0 5.81V14.18C0 17.83 2.17 20 5.81 20H14.18C17.82 20 19.99 17.83 19.99 14.19V5.81C20 2.17 17.83 0 14.19 0Z" fill="#167BDC" />
                        <path d="M9.99914 13.8801C12.142 13.8801 13.8791 12.143 13.8791 10.0001C13.8791 7.85725 12.142 6.12012 9.99914 6.12012C7.85628 6.12012 6.11914 7.85725 6.11914 10.0001C6.11914 12.143 7.85628 13.8801 9.99914 13.8801Z" fill="#167BDC" />
                        <path d="M15 5.4999C14.73 5.4999 14.48 5.3999 14.29 5.2099C14.2 5.1099 14.13 4.9999 14.08 4.8799C14.03 4.7599 14 4.6299 14 4.4999C14 4.3699 14.03 4.2399 14.08 4.1199C14.13 3.9899 14.2 3.8899 14.29 3.7899C14.52 3.5599 14.87 3.4499 15.19 3.5199C15.26 3.5299 15.32 3.5499 15.38 3.5799C15.44 3.5999 15.5 3.6299 15.56 3.6699C15.61 3.6999 15.66 3.7499 15.71 3.7899C15.8 3.8899 15.87 3.9899 15.92 4.1199C15.97 4.2399 16 4.3699 16 4.4999C16 4.6299 15.97 4.7599 15.92 4.8799C15.87 4.9999 15.8 5.1099 15.71 5.2099C15.61 5.2999 15.5 5.3699 15.38 5.4199C15.26 5.4699 15.13 5.4999 15 5.4999Z" fill="#167BDC" />
                    </svg>
                    اینستاگرام</a><?php endif; ?>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo esc_html($course_count); ?> عدد</div>
            <div class="stat-label">تعداد دوره فعال</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo esc_html($total_students); ?> نفر</div>
            <div class="stat-label">تعداد دانشجوی مدرس</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo esc_html(get_user_meta($author_id, 'instructor_rating_average', true) ?: '۵.۰'); ?> امتیاز</div>
            <div class="stat-label">میانگین امتیاز از ۵</div>
        </div>
    </div>

    <div class="section-wrapper">
        <h2 class="section-title">درباره مدرس</h2>
        <p class="about-text"><?php echo isset($about_teacher) ? esc_html($about_teacher) : esc_html($author_bio); ?></p>
    </div>

    <div class="section-wrapper">
        <h2 class="section-title">سوابق مدرس</h2>
        <div class="data-table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>محل کار مدرس</th>
                        <th>سمت</th>
                        <th>مدت فعالیت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $jobs = get_user_meta($author_id, 'teacher_jobs', true);
                    if (!empty($jobs) && is_array($jobs)):
                        foreach ($jobs as $job): ?>
                            <tr>
                                <td><?php echo esc_html($job['company']); ?></td>
                                <td><?php echo esc_html($job['position']); ?></td>
                                <td><?php echo esc_html($job['duration']); ?></td>
                            </tr>
                        <?php endforeach;
                    else: ?>
                        <tr>
                            <td colspan="3" style="text-align:center;">سابقه‌ای ثبت نشده است.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>مقطع تحصیلی</th>
                        <th>رشته و گرایش</th>
                        <th>محل اخذ مدرک</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $education = get_user_meta($author_id, 'teacher_education', true);
                    if (!empty($education) && is_array($education)):
                        foreach ($education as $edu): ?>
                            <tr>
                                <td><?php echo esc_html($edu['degree']); ?></td>
                                <td><?php echo esc_html($edu['field']); ?></td>
                                <td><?php echo esc_html($edu['university']); ?></td>
                            </tr>
                        <?php endforeach;
                    else: ?>
                        <tr>
                            <td colspan="3" style="text-align:center;">سابقه‌ای ثبت نشده است.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="section-wrapper">
        <h2 class="section-title">دوره های مرتبط</h2>
        <!-- <div class="cards-grid"> -->
        <?php
        $courses_query = new WP_Query(array(
            'post_type' => 'sfwd-courses',
            'author'    => $author_id,
            'posts_per_page' => 6
        ));

        if ($courses_query->have_posts()) : ?>
            <div class="courses-grid author-courses-carousel owl-carousel owl-theme">

                <?php while ($courses_query->have_posts()) : $courses_query->the_post();
                    $course_id = get_the_ID();

                    // دریافت تصویر شاخص
                    $image = get_the_post_thumbnail_url($course_id, 'medium');
                    if (!$image) {
                        $image = get_template_directory_uri() . '/assets/img/front-page/Screenshot.webp'; // تصویر پیش‌فرض
                    }

                    // دریافت نام مدرس
                    $author_id = get_post_field('post_author', $course_id);
                    $author_name = get_the_author_meta('display_name', $author_id);

                    // دریافت قیمت از تنظیمات لرن‌دش
                    $course_meta = get_post_meta($course_id, '_sfwd-courses', true);
                    $price = isset($course_meta['sfwd-courses_course_price']) ? $course_meta['sfwd-courses_course_price'] : '';

                    // دسته‌بندی‌ها
                    $categories = get_the_terms($course_id, 'ld_course_category');
                ?>

                    <a href="<?php the_permalink(); ?>">
                        <div class="course-card-pro">
                            <div class="card-image-box">
                                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                            </div>
                            <div class="card-body">
                                <h3 class="card-title"><?php the_title(); ?></h3>
                                <p class="card-teacher"><?php echo esc_html($author_name); ?></p>

                                <div class="card-tags">
                                    <?php
                                    if ($categories && !is_wp_error($categories)) {
                                        $tag_colors = ['tag-orange', 'tag-purple']; // رنگ‌های متناوب
                                        $color_index = 0;
                                        // نمایش نهایتا 3 دسته بندی
                                        $display_cats = array_slice($categories, 0, 3);
                                        foreach ($display_cats as $cat) {
                                            $current_color = $tag_colors[$color_index % count($tag_colors)];
                                            echo '<span class="tag ' . esc_attr($current_color) . '">' . esc_html($cat->name) . '</span>';
                                            $color_index++;
                                        }
                                    } else {
                                        // اگر دسته‌بندی نداشت یک تگ پیش‌فرض خالی بگذاریم که ساختار به هم نریزد
                                        echo '<span class="tag tag-orange">عمومی</span>';
                                    }
                                    ?>
                                </div>

                                <div class="card-price-box">
                                    <?php if (empty($price) || $price === 'free' || $price === 'رایگان') : ?>
                                        <div class="price-current">رایگان</div>
                                    <?php else : ?>
                                        <div class="price-current"><?php echo number_format((float)$price); ?> <span>تومان</span></div>
                                    <?php endif; ?>

                                    <!-- اگر برای قیمت تخفیف‌خورده از متای خاصی استفاده می‌کنید، آن را اینجا قرار دهید -->
                                    <!-- <div class="price-old">۸۹۰,۰۰۰</div> -->
                                </div>
                            </div>
                        </div>
                    </a>

                <?php
                endwhile;
                wp_reset_postdata(); // ریست کردن کوئری
                ?>

            </div>
        <?php else: ?>
            <p>دوره‌ای توسط این مدرس یافت نشد.</p>
        <?php endif; ?>
        <!-- </div> -->
    </div>

    <div class="rating-section">
        <h2 class="rating-title">به استاد و نحوه تدریس آن امتیاز دهید</h2>
        <div class="rating-stars">
            <div class="stars">
                <?php for ($i = 1; $i <= 5; $i++) : ?>
                    <svg data-val="<?= $i ?>" width="24" height="24" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M11.544 2.06813L13.1635 5.30713C13.3844 5.75801 13.9733 6.19049 14.4702 6.27332L17.4055 6.761C19.2827 7.07385 19.7243 8.4357 18.3717 9.77914L16.0897 12.0612C15.7032 12.4476 15.4916 13.193 15.6112 13.7267L16.2645 16.5516C16.7798 18.7876 15.5928 19.6525 13.6144 18.4839L10.8631 16.8552C10.3662 16.5608 9.54728 16.5608 9.04117 16.8552L6.28986 18.4839C4.32072 19.6525 3.12449 18.7784 3.63981 16.5516L4.29313 13.7267C4.41276 13.193 4.20109 12.4476 3.81461 12.0612L1.53263 9.77914C0.189186 8.4357 0.62165 7.07385 2.49879 6.761L5.43412 6.27332C5.92182 6.19049 6.51072 5.75801 6.73157 5.30713L8.35105 2.06813C9.23441 0.310623 10.6699 0.310623 11.544 2.06813Z" stroke="#FFA200" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                <?php endfor; ?>
            </div>
        </div>
        <button class="btn-primary">ثبت امتیاز</button>
    </div>

    <div class="section-wrapper">
        <div class="section-header-flex">
            <h2 class="section-title" style="margin: 0;">مقالات</h2>
            <div class="flex">
                <div class="reviews-slider-controls">
                    <!-- دکمه بعدی (راست) -->
                    <button class="review-btn articles-carousel-next">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </button>
                    <!-- دکمه قبلی (چپ) -->
                    <button class="review-btn articles-carousel-prev">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2">
                            <path d="M15 18l-6-6 6-6"></path>
                        </svg>
                    </button>
                </div>
                <a href="#" class="view-all-link">
                    مشاهده همه
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none">
                        <path d="M15.846 21.6949C15.656 21.6949 15.466 21.6209 15.316 21.4622L8.79598 14.5662C7.73598 13.445 7.73598 11.6047 8.79598 10.4835L15.316 3.58747C15.606 3.28074 16.086 3.28074 16.376 3.58747C16.666 3.89419 16.666 4.40188 16.376 4.70861L9.85598 11.6047C9.37598 12.1124 9.37598 12.9373 9.85598 13.445L16.376 20.3411C16.666 20.6478 16.666 21.1555 16.376 21.4622C16.226 21.6103 16.036 21.6949 15.846 21.6949Z" fill="black"></path>
                    </svg>
                </a>
            </div>
        </div>
        <div id="articles-carousel" class="owl-carousel owl-theme">

            <!-- کارت مقاله -->
            <article class="article-card">
                <a href="https://falnic.com/blog/difference-between-lan-wan-man.html" target="_blank">
                    <div class="article-image">
                        <img src="https://falnic.com/blog/wp-content/uploads/2026/06/difference-between-lan-wan-man-390x187.webp" alt="کاربر">
                    </div>
                    <div class="article-content">
                        <h3>تفاوت شبکه LAN، WAN، MAN چیست؟ </h3>
                        <p>
                            اگر دانشجو، صاحب یک کسب‌وکار یا حتی یک کاربر عادی هستید که با اینترنت یا ...
                        </p>
                    </div>
                </a>
            </article>
            <article class="article-card">
                <a href="https://falnic.com/blog/best-hp-servers.html" target="_blank">
                    <div class="article-image">
                        <img src="https://falnic.com/blog/wp-content/uploads/2025/10/best-hp-servers-390x187.webp" alt="کاربر">
                    </div>
                    <div class="article-content">
                        <h3>
                            معرفی بهترین سرورهای hp
                        </h3>
                        <p>
                            آیا می‌دانستید بیش از ۶۰ درصد زیرساخت‌های دیتاسنترهای جهان از محصولات برند HP استفاده ...
                        </p>
                    </div>
                </a>
            </article>
            <article class="article-card">
                <a href="https://falnic.com/blog/dl380-g11-vs-dl380-g12-servers.html" target="_blank">
                    <div class="article-image">
                        <img src="https://falnic.com/blog/wp-content/uploads/2026/06/dl380-g11-vs-dl380-g12-390x187.webp" alt="کاربر">
                    </div>
                    <div class="article-content">
                        <h3>
                            مقایسه سرور DL380 G11 با DL380 G12 به زبان ساده
                        </h3>
                        <p>
                            آیا قصد دارید سرور hp جدید بخرید و بین دو نسل جدید و محبوب HPE یعنی Gen11 و ...
                        </p>
                    </div>
                </a>
            </article>
            <article class="article-card">
                <a href="https://falnic.com/blog/dl380-g11-vs-dl380-g12-servers.html" target="_blank">
                    <div class="article-image">
                        <img src="https://falnic.com/blog/wp-content/uploads/2026/06/server-suitable-for-small-businesses-390x187.webp" alt="کاربر">
                    </div>
                    <div class="article-content">
                        <h3>
                            معرفی بهترین سرور مناسب کسب و کارهای کوچک
                        </h3>
                        <p>
                            وقتی یک کسب‌وکار کوچک شروع به رشد می‌کند، خیلی زود مدیریت فایل‌ها، برنامه‌ها و کاربران، امنیت اطلاعات و ...
                        </p>
                    </div>
                </a>
            </article>
            <article class="article-card">
                <a href="https://falnic.com/blog/server-suitable-for-small-businesses.html" target="_blank">
                    <div class="article-image">
                        <img src="https://falnic.com/blog/wp-content/uploads/2026/06/server-suitable-for-small-businesses-390x187.webp" alt="کاربر">
                    </div>
                    <div class="article-content">
                        <h3>
                            چگونه IPv6 سیستم خود را پیدا کنیم؟
                        </h3>
                        <p>
                            گاهی هنگام پیکربندی شبکه، عیب‌یابی اتصال اینترنت یا راه‌اندازی برخی سرویس‌های آنلاین، لازم است بدانید آدرس IPv6 دستگاه شما چیست. بسیاری از ...
                        </p>
                    </div>
                </a>
            </article>
            <article class="article-card">
                <a href="https://falnic.com/blog/how-to-connect-modem-to-computer.html" target="_blank">
                    <div class="article-image">
                        <img src="https://falnic.com/blog/wp-content/uploads/2025/11/how-to-connect-modem-to-computer-390x187.webp" alt="کاربر">
                    </div>
                    <div class="article-content">
                        <h3>
                            آموزش مرحله به مرحله وصل کردن مودم به کامپیوتر
                        </h3>
                        <p>
                            اتصال مودم به کامپیوتر از اولین گام‌هایی است که پس از راه‌اندازی اینترنت خانگی یا اداری باید انجام شود؛ اما برای بسیاری از کاربران ...
                        </p>
                    </div>
                </a>
            </article>
        </div>
    </div>

</div>

<?php get_footer(); ?>