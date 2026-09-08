<?php
get_header();

// واکشی داده‌های پایه
$course_id = get_the_ID();
$author_id = get_post_field('post_author', $course_id);

// متادیتای کاستوم
$subtitle = get_post_meta($course_id, '_course_subtitle', true);
$duration = get_post_meta($course_id, '_total_duration', true);
$level    = get_post_meta($course_id, '_course_level', true);
$status   = get_post_meta($course_id, '_course_status', true);
$suffering   = get_post_meta($course_id, '_course_suffering', true);
$outcomes = get_post_meta($course_id, '_course_outcomes', true) ?: array();
$faqs     = get_post_meta($course_id, '_course_faq', true) ?: array();

// تنظیمات قیمت هسته لرن‌دش
$course_meta_settings = get_post_meta($course_id, '_sfwd-courses', true);
$course_price         = isset($course_meta_settings['sfwd-courses_course_price']) ? $course_meta_settings['sfwd-courses_course_price'] : 'رایگان';

// دریافت سرفصل‌ها
$lessons = learndash_get_course_lessons_list($course_id);
$course_categories = get_the_terms($course_id, 'ld_course_category');
$course_tags = get_the_terms($course_id, 'ld_course_tag');

if ($course_categories && !is_wp_error($course_categories)) {
    // انتخاب اولین دسته‌بندی
    $first_cat = $course_categories[0];
    $cat_link = get_term_link($first_cat);

    // بررسی معتبر بودن لینک و نمایش آن در بردکرامب
    if (!is_wp_error($cat_link)) {
        $breadcrumb_category = '<a href="' . esc_url($cat_link) . '" class="breadcrumb-item">' . esc_html($first_cat->name) . '</a>';
    }
} else {
    // حالت جایگزین (اختیاری): اگر دوره هیچ دسته‌بندی نداشت، می‌توانید لینک صفحه آرشیو دوره‌ها را بذارید
    $course_archive_link = get_post_type_archive_link('sfwd-courses');
    if ($course_archive_link) {
        $breadcrumb_category = '<a href="' . esc_url($course_archive_link) . '" class="breadcrumb-item">دوره های آموزشی</a>';
    }
}
?>

<main class="course-page">
    <div class="container">
        <div class="course-main-content">

            <nav class="breadcrumb">
                <a href="#">خانه</a>
                <span class="divider">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.0002 13.2807L5.65355 8.93404C5.14022 8.4207 5.14022 7.5807 5.65355 7.06737L10.0002 2.7207" stroke="#333333" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <?= $breadcrumb_category; ?>
                <span class="divider">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M10.0002 13.2807L5.65355 8.93404C5.14022 8.4207 5.14022 7.5807 5.65355 7.06737L10.0002 2.7207" stroke="#333333" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="current"><?php the_title(); ?></span>
            </nav>


            <section class="course-header-section">
                <div class="preview-hover-overlay">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium'); ?>
                    <?php else: ?>
                        <img src="<?= get_template_directory_uri(); ?>/assets/img/single-page/single-course.png" />
                    <?php endif; ?>
                    <svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M25.9511 51.1524C39.8695 51.1524 51.1524 39.8694 51.1524 25.9512C51.1524 12.033 39.8695 0.75 25.9511 0.75C12.033 0.75 0.75 12.033 0.75 25.9512C0.75 39.8694 12.033 51.1524 25.9511 51.1524Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M17.8125 26.5298V22.3212C17.8125 17.0795 21.517 14.9374 26.0532 17.5583L29.7074 19.6752L33.3616 21.792C37.8978 24.4129 37.8978 28.6971 33.3616 31.3181L29.7074 33.435L26.0532 35.5519C21.517 38.1728 17.8125 36.0307 17.8125 30.7888V26.5298Z" stroke="white" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <?php
                // ==========================================
                // بررسی وضعیت علاقه‌مندی دوره برای کاربر فعلی
                // ==========================================
                $is_favorited = false;
                $user_id = get_current_user_id();
                
                if ($user_id) {
                    $fav_courses_str = get_user_meta($user_id, 'fav_courses', true);
                    $fav_courses = $fav_courses_str ? explode(',', $fav_courses_str) : array();
                    if (in_array($course_id, $fav_courses)) {
                        $is_favorited = true;
                    }
                }
                
                // تنظیم رنگ‌ها و متن‌ها بر اساس وضعیت اولیه
                $heart_fill   = $is_favorited ? '#e74c3c' : 'none'; // قرمز یا توخالی
                $heart_stroke = $is_favorited ? '#e74c3c' : '#292D32'; 
                $wishlist_txt = $is_favorited ? 'حذف از علاقه مندی ها' : 'افزودن به علاقه مندی ها';
                
                // لینک صفحه ورود برای کاربران مهمان (با قابلیت بازگشت به همین صفحه)
                $login_url = !is_user_logged_in() ? wp_login_url(get_permalink()) : '';
                ?>
                <div class="title-section">
                    <h1 class="course-title"><?php the_title(); ?></h1>
                    
                    <div class="wishlist-add-btn" 
                         data-course="<?php echo $course_id; ?>" 
                         data-nonce="<?php echo wp_create_nonce('wishlist_nonce'); ?>" 
                         data-logged-in="<?php echo is_user_logged_in() ? 'true' : 'false'; ?>"
                         data-login-url="<?php echo esc_url($login_url); ?>"
                         style="cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px;">
                         
                        <svg class="wishlist-icon" width="23" height="20" viewBox="0 0 23 20" fill="<?php echo $heart_fill; ?>" xmlns="http://www.w3.org/2000/svg" style="transition: fill 0.3s, stroke 0.3s;">
                            <path d="M11.6814 18.9792C11.3314 19.1027 10.755 19.1027 10.405 18.9792C7.41998 17.9602 0.75 13.7091 0.75 6.50389C0.75 3.3233 3.313 0.75 6.47301 0.75C8.34637 0.75 10.0036 1.6558 11.0432 3.05567C12.0828 1.6558 13.7503 0.75 15.6134 0.75C18.7734 0.75 21.3364 3.3233 21.3364 6.50389C21.3364 13.7091 14.6664 17.9602 11.6814 18.9792Z" stroke="<?php echo $heart_stroke; ?>" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        
                        <span class="wishlist-text">
                            <?php echo $wishlist_txt; ?>
                        </span>
                    </div>
                </div>
                <p class="course-brief">
                    <input type="checkbox" id="toggle-brief" class="toggle-checkbox">
                    <?php echo !empty($subtitle) ? esc_html($subtitle) : wp_trim_words(get_the_excerpt(), 40); ?>
                    <label for="toggle-brief" class="toggle-brief-btn">
                        <a class="btn-more" href="#course-description">... بیشتر</a>
                    </label>
                </p>
                <?php
                if ($course_categories && !is_wp_error($course_categories)) :
                ?>
                    <ul class="related-course-categories">
                        <?php foreach ($course_categories as $category) : ?>
                            <li class="category-item">
                                <a href="<?php echo esc_url(get_term_link($category)); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </a>
                            </li>
                            <?php if ($course_tags && !is_wp_error($course_tags)) : ?>
                                <?php foreach ($course_tags as $tag) : ?>
                                    <li class="category-item">
                                        <a href="<?php echo esc_url(get_term_link($tag)); ?>">
                                            <?php echo esc_html($tag->name); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <div class="sidebar-features-list">
                    <span class="sidebar-features-list-title">اطلاعات دوره</span>
                    <div class="sidebar-feature-row">
                        <span class="feat-label">سطح: <?php echo esc_html($level ?: 'نامشخص'); ?></span>
                    </div>
                    <div class="sidebar-feature-row">
                        <span class="feat-label">وضعیت: <?php echo esc_html($status ?: 'نامشخص'); ?></span>
                    </div>
                    <div class="sidebar-feature-row">
                        <span class="feat-label">مدت زمان: <?php echo esc_html($duration ?: 'نامشخص'); ?></span>
                    </div>
                </div>
            </section>

            <nav class="course-tabs-nav">
                <button type="button" class="tab-btn" data-target="content-list">سرفصل‌ها</button>
                <button type="button" class="tab-btn active" data-target="course-description">توضیحات دوره</button>
                <button type="button" class="tab-btn" data-target="reviews">نظرات کاربران</button>
                <button type="button" class="tab-btn" data-target="instructors">مدرسین</button>
                <button type="button" class="tab-btn" data-target="faq">سوالات متداول</button>
            </nav>

            <?php if (!empty($outcomes)) : ?>
                <section class="course-card-box">
                    <h2 class="box-title">آنچه در این دوره می‌آموزید</h2>
                    <div class="features-grid">
                        <?php foreach ($outcomes as $outcome) : ?>
                            <div class="feature-card-item">
                                <svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M10.75 20.75C16.25 20.75 20.75 16.25 20.75 10.75C20.75 5.25 16.25 0.75 10.75 0.75C5.25 0.75 0.75 5.25 0.75 10.75C0.75 16.25 5.25 20.75 10.75 20.75Z" stroke="#0A3762" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    <path d="M6.5 10.7499L9.33 13.5799L15 7.91992" stroke="#0A3762" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <span><?php echo esc_html($outcome); ?></span>
                            </div>

                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section id="content-list" class="course-card-box content-list">
                <div class="box-title">
                    <h2>محتوای دوره</h2>
                    <ul class="columns">
                        <li>
                            <span><?php echo count($lessons); ?></span>
                            جلسه
                        </li>
                        <?php
                        $sections_count = function_exists('learndash_30_get_course_sections') ? count(learndash_30_get_course_sections($course_id)) : 0;
                        if ($sections_count > 0): ?>
                            <li>
                                <span><?php echo $sections_count; ?></span>
                                فصل
                            </li>
                        <?php endif; ?>
                        <?php if (!empty($duration)) : ?>
                            <li>
                                <span><?php echo esc_html($duration); ?></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="curriculum-accordion">
                    <?php
                    $user_id    = get_current_user_id();
                    $has_access = sfwd_lms_has_access($course_id, $user_id);
                    $sections = function_exists('learndash_30_get_course_sections') ? learndash_30_get_course_sections($course_id) : array();

                    $section_lesson_counts = array();
                    $section_lesson_times = array();
                    $current_sec_id = 0;
                    $unlocked_lessons = array();

                    if (!empty($lessons)) {
                        foreach ($lessons as $l) {
                            $l_id = $l['post']->ID;
                            if (isset($sections[$l_id])) {
                                $current_sec_id = $l_id;
                                $section_lesson_counts[$current_sec_id] = 0;
                                $section_lesson_times[$current_sec_id] = 0;
                            }

                            $duration_sec = (int) get_post_meta($l_id, '_learndash_course_grid_duration', true);
                            if ($current_sec_id > 0) {
                                $section_lesson_counts[$current_sec_id]++;
                                $section_lesson_times[$current_sec_id] += $duration_sec;
                            }

                            $is_sample = learndash_get_setting($l_id, 'sample_lesson') === 'on';
                            if ($has_access || $is_sample) {
                                $unlocked_lessons[] = $l_id;
                            }
                        }
                    }

                    $is_in_section = false;
                    $modals_html = '';

                    if (!empty($lessons)) :
                        foreach ($lessons as $index => $lesson) :
                            $lesson_id    = $lesson['post']->ID;
                            $lesson_title = get_the_title($lesson_id);

                            $duration_sec = (int) get_post_meta($lesson_id, '_learndash_course_grid_duration', true);
                            $total_minutes = floor($duration_sec / 60);
                            $cg_hour = floor($total_minutes / 60);
                            $cg_minute = $total_minutes % 60;

                            $lesson_duration_text = '';
                            if ($cg_hour > 0 && $cg_minute > 0) {
                                $lesson_duration_text = $cg_hour . ':' . str_pad($cg_minute, 2, '0', STR_PAD_LEFT) . ' ساعت';
                            } elseif ($cg_hour > 0) {
                                $lesson_duration_text = $cg_hour . ':00 ساعت';
                            } elseif ($cg_minute > 0) {
                                $lesson_duration_text = $cg_minute . ' دقیقه';
                            }

                            $is_sample   = learndash_get_setting($lesson_id, 'sample_lesson') === 'on';
                            $is_unlocked = $has_access || $is_sample;
                            $row_class   = $is_unlocked ? 'lesson-row lesson-free' : 'lesson-row lesson-locked';

                            // ساختار پاپ آپ
                            if ($is_unlocked) {
                                $video_url = learndash_get_setting($lesson_id, 'lesson_video_url');
                                $video_embed = '';

                                if (!empty($video_url)) {
                                    $video_embed = wp_oembed_get($video_url);
                                    if (empty($video_embed)) {
                                        if (preg_match('/\.mp4$/i', $video_url)) {
                                            $video_embed = '<video class="js-player" controls crossorigin playsinline src="' . esc_url($video_url) . '" style="width:100%; border-radius: 8px;"></video>';
                                        } else {
                                            $video_embed = '<iframe src="' . esc_url($video_url) . '" style="width:100%; aspect-ratio: 16/9; border:none; border-radius: 8px;" allowfullscreen></iframe>';
                                        }
                                    }
                                }

                                $current_idx = array_search($lesson_id, $unlocked_lessons);
                                $prev_id = ($current_idx > 0) ? $unlocked_lessons[$current_idx - 1] : null;
                                $next_id = ($current_idx < count($unlocked_lessons) - 1) ? $unlocked_lessons[$current_idx + 1] : null;
                                $total_preview = count($unlocked_lessons);
                                $current_step = $current_idx + 1;

                                $step_text_prefix = $has_access ? 'درس' : 'پیش‌نمایش';



                                // تنظیمات ارسال ایجکس
                                $mark_complete_args = '';
                                if ($has_access) {
                                    $nonce = wp_create_nonce('mark_complete_nonce_' . $lesson_id);
                                    // ارسال آی‌دی دوره و آی‌دی درس به جاوا اسکریپت
                                    $mark_complete_args = ', \'' . $nonce . '\', ' . $course_id;
                                }

                                $course_title = get_the_title($course_id);

                                $modals_html .= '
                                <div id="lesson-modal-' . $lesson_id . '" class="lesson-video-modal" style="display: none;">
                                    <div class="modal-overlay" onclick="closeLessonModal(' . $lesson_id . ')"></div>
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <div class="modal-titles">
                                                <h3>' . esc_html($lesson_title) . '</h3>
                                                <p>' . esc_html($course_title) . '</p>
                                            </div>
                                            <span class="close-modal" onclick="closeLessonModal(' . $lesson_id . ')">
                                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                                            </span>
                                        </div>
                                        <div class="modal-body">
                                            <div class="video-container">
                                                ' . ($video_embed ? $video_embed : '<p style="text-align:center; padding: 40px; background:#f9f9f9; border-radius:12px;">ویدیویی برای این درس ثبت نشده است.</p>') . '
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <div class="footer-btn-container">';

                                // دکمه قبلی
                                if ($prev_id) {
                                    $modals_html .= '<button class="btn-nav btn-prev" onclick="switchLessonModal(' . $lesson_id . ', ' . $prev_id . ')">
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                                                        ویدیوی قبلی
                                                    </button>';
                                } else {
                                    $modals_html .= '<div style="width:130px;"></div>';
                                }

                                // شمارنده هوشمند
                                $modals_html .= '<span class="step-counter">' . $step_text_prefix . ' ' . $current_step . ' از ' . $total_preview . '</span>';

                                // دکمه بعدی یا (دکمه سبزِ تکمیل درس آخر)
                                if ($next_id) {
                                    $modals_html .= '<button class="btn-nav btn-next" onclick="handleNextLesson(' . $lesson_id . ', ' . $next_id . $mark_complete_args . ')">
                                                        ویدیوی بعدی
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                                                    </button>';
                                } else {
                                if ($has_access) {
                                    $modals_html .= '<button class="btn-nav btn-finish" onclick="handleFinishLastLesson(' . $lesson_id . $mark_complete_args . ')" style="background: #28a745; border-color: #28a745; color: #fff;">
                                                        تکمیل درس
                                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                                                    </button>';
                                }else{
                                    $modals_html .= '<button onclick="closeLessonModal(' . $lesson_id . '); document.getElementById(\'start_course\').click();" style="background: #FFA200; color: #fff; padding: 12px 30px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 15px; box-shadow: 0 4px 15px rgba(255, 162, 0, 0.3);">
                                            شرکت در دوره
                                        </button>';
                                }

                                }

                                $modals_html .= '
                                            </div>
                                        </div>

                                    </div>
                                </div>';
                            }

                            // ===== کد باز کردن آکاردئون‌ها و نمایش ردیف‌ها بدون تغییر باقی می‌ماند ... =====
                            if (!empty($sections) && isset($sections[$lesson_id])) {
                                if ($is_in_section) {
                                    echo '</div></details>';
                                }

                                $section_title = $sections[$lesson_id]->post_title;
                                $lesson_count_in_sec = isset($section_lesson_counts[$lesson_id]) ? $section_lesson_counts[$lesson_id] : 0;

                                $time_in_sec = isset($section_lesson_times[$lesson_id]) ? $section_lesson_times[$lesson_id] : 0;
                                $total_sec_minutes = floor($time_in_sec / 60);
                                $sec_h = floor($total_sec_minutes / 60);
                                $sec_m = $total_sec_minutes % 60;

                                $sec_duration_text = '';
                                if ($sec_h > 0 && $sec_m > 0) {
                                    $sec_duration_text = $sec_h . ' ساعت و ' . $sec_m . ' دقیقه';
                                } elseif ($sec_h > 0) {
                                    $sec_duration_text = $sec_h . ' ساعت';
                                } elseif ($sec_m > 0) {
                                    $sec_duration_text = $sec_m . ' دقیقه';
                                }

                                echo '<details class="course-section-accordion" ' . ($index === 0 ? 'open' : '') . '>';
                                echo '<summary class="accordion-header" style="display: flex; justify-content: space-between; align-items: center;">';
                                echo '<span class="accordion-title">' . esc_html($section_title) . '</span>';

                                echo '<ul class="accordion-meta" style="display: flex; gap: 15px; align-items: center; list-style: none; margin: 0; padding: 0;">';
                                if ($lesson_count_in_sec > 0) {
                                    echo '<li>' . $lesson_count_in_sec . ' قسمت</li>';
                                }
                                if (!empty($sec_duration_text)) {
                                    echo '<li>' . $sec_duration_text . '</li>';
                                }
                                echo '<li>
                            <svg width="18" height="9" viewBox="0 0 18 9" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16.59 0.75L10.07 7.26998C9.30002 8.03998 8.04002 8.03998 7.27002 7.26998L0.75 0.75" stroke="black" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                          </li>';
                                echo '</ul>';
                                echo '</summary>';
                                echo '<div class="section-content">';

                                $is_in_section = true;
                            }

                            if (!$is_in_section && $index === 0) {
                                echo '<div class="standalone-lessons-wrapper">';
                            }

                    ?>
                            <div class="<?php echo esc_attr($row_class); ?>"
                                <?php if ($is_unlocked): ?> onclick="openLessonModal(<?php echo $lesson_id; ?>)" <?php endif; ?>>

                                <div class="lesson-right">
                                    <span><?php echo ($index) . '- ' . esc_html($lesson_title); ?></span>
                                </div>

                                <div class="lesson-left" style="display: flex; align-items: center; gap: 20px;">

                                    <div class="lesson-status-icon" style="display: flex; align-items: center; gap: 8px; color: #888;">
                                        <span><?php echo $is_unlocked ? 'پخش' : 'قفل'; ?></span>
                                        <?php if ($is_unlocked) : ?>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M10 1.25C5.175 1.25 1.25 5.175 1.25 10C1.25 14.825 5.175 18.75 10 18.75C14.825 18.75 18.75 14.825 18.75 10C18.75 5.175 14.825 1.25 10 1.25ZM10 18.1844C5.4875 18.1844 1.81562 14.5125 1.81562 10C1.81562 5.4875 5.4875 1.81562 10 1.81562C14.5125 1.81562 18.1844 5.4875 18.1844 10C18.1844 14.5125 14.5125 18.1844 10 18.1844Z" fill="black" />
                                                <path d="M14.9624 9.21289L8.19987 5.30977C7.9155 5.14414 7.57487 5.14414 7.2905 5.30977C7.00612 5.47539 6.83425 5.76914 6.83425 6.09727V13.9066C6.82175 14.3973 7.25925 14.8254 7.74362 14.816C7.88737 14.816 8.03112 14.7816 8.1655 14.7098C8.19675 14.6973 14.7905 10.8816 14.9592 10.7879C15.478 10.5504 15.6374 9.58477 14.9624 9.21289ZM14.6811 10.3004L7.91862 14.2035C7.76237 14.2941 7.62487 14.2348 7.57487 14.2035C7.52175 14.1723 7.403 14.0848 7.403 13.9035V6.09414C7.42487 5.84102 7.56237 5.76602 7.74987 5.74727C7.79987 5.74727 7.85925 5.75977 7.92175 5.79414L14.6842 9.69727C14.9124 9.84727 14.9186 10.1504 14.6811 10.3004Z" fill="black" />
                                            </svg>
                                        <?php else : ?>
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M10 1.25C5.175 1.25 1.25 5.175 1.25 10C1.25 14.825 5.175 18.75 10 18.75C14.825 18.75 18.75 14.825 18.75 10C18.75 5.175 14.825 1.25 10 1.25ZM10 18.1844C5.4875 18.1844 1.81562 14.5125 1.81562 10C1.81562 5.4875 5.4875 1.81562 10 1.81562C14.5125 1.81562 18.1844 5.4875 18.1844 10C18.1844 14.5125 14.5125 18.1844 10 18.1844Z" fill="black" />
                                                <g clip-path="url(#clip0_640_4154)">
                                                    <path d="M10 5C8.45621 5 7.2002 6.25602 7.2002 7.79986V9.17082C7.2002 9.26418 7.27596 9.33988 7.36932 9.33988H8.30793C8.35277 9.33988 8.39577 9.32206 8.42747 9.29036C8.45917 9.25866 8.47699 9.21566 8.47699 9.17082V7.79986C8.47699 6.96006 9.16022 6.27682 10 6.27682C10.8398 6.27682 11.523 6.96006 11.523 7.79986V9.17082C11.523 9.26418 11.5988 9.33988 11.6922 9.33988H12.6308C12.6757 9.33988 12.7187 9.32206 12.7504 9.29036C12.7821 9.25866 12.7999 9.21566 12.7999 9.17082V7.79986C12.7999 6.25602 11.5439 5 10 5ZM12.4618 9.00176H11.8612V7.79986C11.8612 6.77361 11.0263 5.93869 10.0001 5.93869C8.97385 5.93869 8.13893 6.77361 8.13893 7.79986V9.00176H7.53838V7.79986C7.53838 6.44246 8.64268 5.33814 10.0001 5.33814C11.3575 5.33814 12.4618 6.44246 12.4618 7.79986V9.00176Z" fill="black" />
                                                    <path d="M13.02 9.00195H6.97975C6.57926 9.00195 6.25342 9.32779 6.25342 9.72828V14.2739C6.25342 14.6744 6.57926 15.0002 6.97975 15.0002H13.02C13.4204 15.0002 13.7463 14.6744 13.7463 14.2738V9.72828C13.7463 9.32779 13.4204 9.00195 13.02 9.00195ZM13.4081 14.2738C13.4081 14.4879 13.234 14.6621 13.02 14.6621H6.97975C6.76568 14.6621 6.59154 14.4879 6.59154 14.2738V9.72828C6.59154 9.51422 6.76568 9.34008 6.97975 9.34008H13.02C13.234 9.34008 13.4081 9.51422 13.4081 9.72828V14.2738Z" fill="black" />
                                                    <path d="M10.5683 12.2221C10.7658 12.0557 10.8807 11.8124 10.8807 11.551C10.8807 11.0652 10.4855 10.6699 9.99971 10.6699C9.51389 10.6699 9.11865 11.0652 9.11865 11.551C9.11865 11.8124 9.23359 12.0557 9.43115 12.2222L9.24109 13.1277C9.23594 13.1523 9.23633 13.1778 9.24225 13.2022C9.24817 13.2266 9.25947 13.2495 9.27532 13.269C9.29117 13.2885 9.31118 13.3043 9.33388 13.3151C9.35658 13.3259 9.38141 13.3315 9.40656 13.3315H10.5929C10.618 13.3315 10.6428 13.3259 10.6655 13.3151C10.6882 13.3043 10.7083 13.2886 10.7241 13.269C10.7399 13.2495 10.7512 13.2267 10.7572 13.2022C10.7631 13.1778 10.7635 13.1523 10.7583 13.1277L10.5683 12.2221ZM10.2898 12.0081C10.2608 12.0265 10.2381 12.0534 10.2248 12.0851C10.2114 12.1168 10.208 12.1518 10.2151 12.1854L10.3846 12.9934H9.6148L9.78437 12.1855C9.79144 12.1518 9.78808 12.1168 9.77473 12.0851C9.76139 12.0535 9.7387 12.0266 9.70969 12.0081C9.55133 11.9073 9.4568 11.7364 9.4568 11.551C9.4568 11.2516 9.70033 11.0081 9.99971 11.0081C10.2991 11.0081 10.5426 11.2516 10.5426 11.551C10.5426 11.7365 10.4481 11.9073 10.2898 12.0081Z" fill="black" />
                                                </g>
                                                <defs>
                                                    <clipPath id="clip0_640_4154">
                                                        <rect width="10" height="10" fill="white" transform="translate(5 5)" />
                                                    </clipPath>
                                                </defs>
                                            </svg>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($lesson_duration_text)) : ?>
                                        <div class="lesson-duration" style="color: #333; min-width: 60px; text-align: left;">
                                            <?php echo $lesson_duration_text; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php
                        endforeach;

                        if ($is_in_section) {
                            echo '</div></details>';
                        } elseif (!empty($lessons) && empty($sections)) {
                            echo '</div>';
                        }
                        echo $modals_html;

                    else: ?>
                        <div style="padding: 20px; text-align: center; color: #666;">
                            <p>هنوز سرفصلی برای این دوره ثبت نشده است.</p>
                        </div>
                    <?php endif; ?>
            </section>
            <? if ($suffering == '1') { ?>
                <section class="course-card-box homework-upload">
                    <h2 class="box-title">آپلود تمرینهای دوره</h2>
                    <div class="homework-uploader">
                        <div class="upload-icon"></div>
                        <span>
                            برای آپلود فایل خود را اینجا بکشید یا کلیک کنید
                        </span>
                    </div>
                </section>
            <? } ?>

            <section id="course-description" class="course-content">
                <div class="content">
                    <input type="checkbox" id="toggle-text" class="toggle-checkbox">

                    <?php the_content(); ?>
                </div>
                <label for="toggle-text" class="read-more-btn">
                    <span></span>
                    <svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M15.846 20.9645C15.656 20.9645 15.466 20.8904 15.316 20.7318L8.79598 13.8357C7.73598 12.7146 7.73598 10.8742 8.79598 9.75306L15.316 2.857C15.606 2.55027 16.086 2.55027 16.376 2.857C16.666 3.16372 16.666 3.67141 16.376 3.97814L9.85598 10.8742C9.37598 11.3819 9.37598 12.2069 9.85598 12.7146L16.376 19.6106C16.666 19.9174 16.666 20.425 16.376 20.7318C16.226 20.8798 16.036 20.9645 15.846 20.9645Z" fill="#0A3762" />
                    </svg>
                </label>
            </section>
            <?php
            // دریافت وضعیت و داده‌های وبینار
            $webinar_active      = get_post_meta($course_id, '_webinar_active', true);

            // فقط در صورتی که وبینار فعال باشد این سکشن رندر می‌شود
            if ($webinar_active === 'yes') :
                $webinar_date        = get_post_meta($course_id, '_webinar_date', true);
                $webinar_time        = get_post_meta($course_id, '_webinar_time', true);
                $webinar_location    = get_post_meta($course_id, '_webinar_location', true);
                $webinar_map_iframe  = get_post_meta($course_id, '_webinar_map_iframe', true);
                $webinar_gmap_link   = get_post_meta($course_id, '_webinar_gmap_link', true);
                $webinar_neshan_link = get_post_meta($course_id, '_webinar_neshan_link', true);
            ?>
                <section class="webinar-section">
                    <h2 class="section-title">مکان و زمان برگزاری وبینار</h2>
                    <div class="webinar-card">
                        <div class="webinar-info-grid">

                            <div class="info-item">
                                <span class="icon">
                                    <svg width="20" height="23" viewBox="0 0 20 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5.75 0.75V3.75" stroke="#0A3762" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M13.75 0.75V3.75" stroke="#0A3762" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                        <path opacity="0.4" d="M1.25 8.19434H18.25" stroke="#0A3762" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M18.75 7.52027V16.4797C18.75 19.6419 17.25 21.75 13.75 21.75H5.75C2.25 21.75 0.75 19.6419 0.75 16.4797V7.52027C0.75 4.35811 2.25 2.25 5.75 2.25H13.75C17.25 2.25 18.75 4.35811 18.75 7.52027Z" stroke="#0A3762" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                                        <path opacity="0.4" d="M13.4447 13.0352H13.4537" stroke="#0A3762" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path opacity="0.4" d="M13.4447 16.1853H13.4537" stroke="#0A3762" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path opacity="0.4" d="M9.74548 13.0352H9.75448" stroke="#0A3762" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path opacity="0.4" d="M9.74548 16.1853H9.75448" stroke="#0A3762" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path opacity="0.4" d="M6.04431 13.0352H6.05329" stroke="#0A3762" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        <path opacity="0.4" d="M6.04395 16.1853H6.05293" stroke="#0A3762" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <p><strong>شروع وبینار:</strong> <?php echo esc_html($webinar_date ?: 'نامشخص'); ?></p>
                            </div>

                            <div class="info-item">
                                <span class="icon">
                                    <svg width="22" height="23" viewBox="0 0 22 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20.75 11.25C20.75 17.046 16.27 21.75 10.75 21.75C5.23 21.75 0.75 17.046 0.75 11.25C0.75 5.454 5.23 0.75 10.75 0.75C16.27 0.75 20.75 5.454 20.75 11.25Z" stroke="#0A3762" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path opacity="0.4" d="M14.4599 14.3766L11.3599 12.5266C10.8199 12.2066 10.3799 11.4366 10.3799 10.8066V6.70654" stroke="#0A3762" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <p><strong>تایم وبینار:</strong> <?php echo esc_html($webinar_time ?: 'نامشخص'); ?></p>
                            </div>

                            <div class="info-item full-width">
                                <span class="icon">
                                    <svg width="22" height="23" viewBox="0 0 22 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20.75 8.75002V14.75C20.75 17.25 20.25 19 19.13 20.13L12.75 13.75L20.48 6.02002C20.66 6.81002 20.75 7.71002 20.75 8.75002Z" stroke="#0A3762" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M20.48 5.23642L5.01999 21.48C2.00999 20.755 0.75 18.5696 0.75 14.4089V8.10479C0.75 2.85137 2.75 0.75 7.75 0.75H13.75C17.71 0.75 19.79 2.07386 20.48 5.23642Z" stroke="#0A3762" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path d="M19.1295 20.13C17.9995 21.25 16.2495 21.75 13.7495 21.75H7.74954C6.70954 21.75 5.80953 21.66 5.01953 21.48L12.7495 13.75L19.1295 20.13Z" stroke="#0A3762" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        <path opacity="0.4" d="M4.98929 7.0161C5.66929 4.0861 10.0693 4.0861 10.7493 7.0161C11.1393 8.7361 10.0593 10.1961 9.1093 11.0961C8.41928 11.7561 7.3293 11.7561 6.6293 11.0961C5.6793 10.1961 4.58929 8.7361 4.98929 7.0161Z" stroke="#0A3762" stroke-width="1.5" />
                                        <path opacity="0.4" d="M7.84412 7.78516H7.8531" stroke="#0A3762" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </span>
                                <p><strong>محل برگزاری:</strong> <?php echo esc_html($webinar_location ?: 'نامشخص'); ?></p>
                            </div>
                        </div>

                        <?php if (!empty($webinar_map_iframe)) : ?>
                            <div class="map-placeholder">
                                <iframe src="<?php echo esc_url($webinar_map_iframe); ?>" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

                                <div class="map-directions">
                                    <?php if (!empty($webinar_gmap_link)): ?>
                                        <a href="<?php echo esc_url($webinar_gmap_link); ?>" target="_blank" title="مسیریابی با گوگل مپ">
                                            <img src="https://edu.falnic.com/wp-content/themes/edu-falnic/assets/img/single-page/google-map-icon.png">
                                        </a>
                                    <?php endif; ?>

                                    <?php if (!empty($webinar_neshan_link)): ?>
                                        <a href="<?php echo esc_url($webinar_neshan_link); ?>" target="_blank" title="مسیریابی با نشان">
                                            <img src="https://edu.falnic.com/wp-content/themes/edu-falnic/assets/img/single-page/neshan-map-icon.png">
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </section>
            <?php endif; ?>

            <?php
            // 1. بررسی وضعیت روشن بودن نظرات
            $is_reviews_enabled = false;

            // بررسی باز بودن کامنت‌های بومی وردپرس (کاری که لرن‌دش در پس‌زمینه انجام می‌دهد)
            if (comments_open($course_id)) {
                $is_reviews_enabled = true;
            }

            // بررسی متای لرن‌دش در صورت وجود
            $ld_reviews = get_post_meta($course_id, 'learndash-course-reviews', true);
            if (is_array($ld_reviews) && isset($ld_reviews['show_reviews']) && $ld_reviews['show_reviews'] === 'yes') {
                $is_reviews_enabled = true;
            }

            // ⭐ خط طلایی: اگر می‌خواهید فرم نظرات همیشه و بدون باگ در صفحات دوره شما نمایش داده شود، خط زیر را همینطور رها کنید:
            $is_reviews_enabled = true;

            if ($is_reviews_enabled) :
                // دریافت نظرات تایید شده برای این دوره
                $comments = get_comments(array(
                    'post_id' => $course_id,
                    'status'  => 'approve',
                ));
            ?>
                <section id="reviews" class="reviews-section">
                    <h2 class="section-title">نظرات و ثبت نظر</h2>

                    <!-- نمایش نظرات موجود -->
                    <?php if (!empty($comments)) : ?>
                        <div class="reviews-slider owl-carousel owl-theme">
                            <?php foreach ($comments as $comment) :
                                $rating = get_comment_meta($comment->comment_ID, 'review_rating', true) ?: 5; // دیفالت 5 ستاره
                            ?>
                                <div class="review-card">
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++) : ?>
                                            <svg class="<?php echo ($i <= $rating) ? 'active' : ''; ?>" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M11.544 2.06813L13.1635 5.30713C13.3844 5.75801 13.9733 6.19049 14.4702 6.27332L17.4055 6.761C19.2827 7.07385 19.7243 8.4357 18.3717 9.77914L16.0897 12.0612C15.7032 12.4476 15.4916 13.193 15.6112 13.7267L16.2645 16.5516C16.7798 18.7876 15.5928 19.6525 13.6144 18.4839L10.8631 16.8552C10.3662 16.5608 9.54728 16.5608 9.04117 16.8552L6.28986 18.4839C4.32072 19.6525 3.12449 18.7784 3.63981 16.5516L4.29313 13.7267C4.41276 13.193 4.20109 12.4476 3.81461 12.0612L1.53263 9.77914C0.189186 8.4357 0.62165 7.07385 2.49879 6.761L5.43412 6.27332C5.92182 6.19049 6.51072 5.75801 6.73157 5.30713L8.35105 2.06813C9.23441 0.310623 10.6699 0.310623 11.544 2.06813Z" stroke="#FFA200" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                    <p class="review-text">
                                        <?php echo nl2br(esc_html($comment->comment_content)); ?>
                                    </p>
                                    <div class="user-info">
                                        <div class="avatar">
                                            <?php echo get_avatar($comment->user_id, 40); ?>
                                        </div>
                                        <div class="user-details">
                                            <h4><?php echo esc_html($comment->comment_author); ?></h4>
                                            <span>کاربر سایت</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: right; margin-bottom: 30px; color: #666; background: #f9f9f9; padding: 15px; border-radius: 8px;">
                            <p style="margin: 0;">هنوز نظری برای این دوره ثبت نشده است. اولین نفری باشید که نظر می‌دهد!</p>
                        </div>
                    <?php endif; ?>
                        <div class="comment-form">
                            <div class="rating-input" id="review-rating-stars">
                                <div class="stars" style="cursor: pointer;">
                                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                                        <svg data-val="<?php echo $i; ?>" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M11.544 2.06813L13.1635 5.30713C13.3844 5.75801 13.9733 6.19049 14.4702 6.27332L17.4055 6.761C19.2827 7.07385 19.7243 8.4357 18.3717 9.77914L16.0897 12.0612C15.7032 12.4476 15.4916 13.193 15.6112 13.7267L16.2645 16.5516C16.7798 18.7876 15.5928 19.6525 13.6144 18.4839L10.8631 16.8552C10.3662 16.5608 9.54728 16.5608 9.04117 16.8552L6.28986 18.4839C4.32072 19.6525 3.12449 18.7784 3.63981 16.5516L4.29313 13.7267C4.41276 13.193 4.20109 12.4476 3.81461 12.0612L1.53263 9.77914C0.189186 8.4357 0.62165 7.07385 2.49879 6.761L5.43412 6.27332C5.92182 6.19049 6.51072 5.75801 6.73157 5.30713L8.35105 2.06813C9.23441 0.310623 10.6699 0.310623 11.544 2.06813Z" stroke="#FFA200" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <textarea id="review-content" placeholder="دیدگاه خود را در این قسمت تایپ کنید..."></textarea>

                            <!-- نمایش پیغام‌ها -->
                            <div id="review-message" style="display:none; margin-top: 15px; font-size: 14px; text-align: right;"></div>

                            <div class="form-actions">
                                <button id="submit-course-review" class="btn-submit" data-course="<?php echo $course_id; ?>" data-nonce="<?php echo wp_create_nonce('course_review_nonce'); ?>">
                                    ثبت نظر
                                </button>
                            </div>
                        </div>


                </section>
            <?php endif; // پایان بررسی فعال بودن نظرات 
            ?>


            <section id="instructors" class="instructors-section">
                <h2 class="section-title">اساتید دوره</h2>
                <div class="instructors-grid">
                    <?php
                    $author_name   = get_the_author_meta('display_name', $author_id);
                    $author_bio    = get_the_author_meta('description', $author_id);
                    $author_role    = get_the_author_meta('instructor_user_role', $author_id);
                    $author_avatar = get_avatar_url($author_id, array('size' => 150));
                    ?>
                    <div class="instructor-card">
                        <div class="instructor-image-box">
                            <img src="<?php echo esc_url($author_avatar); ?>" alt="<?php echo esc_attr($author_name); ?>">
                        </div>
                        <div class="instructor-content">
                            <h3 class="instructor-name"><?php echo esc_html($author_name); ?></h3>
                            <p class="instructor-subtitle">مدیر راهکارهای شبکه و امنیت داده</p>
                            <p class="instructor-desc"><?php echo esc_html($author_bio ?: 'توضیحاتی ثبت نشده است.'); ?></p>
                            <!-- <a href="<?php echo get_author_posts_url($author_id); ?>" class="instructor-link">
                                مشاهده پروفایل
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M13.2035 17.7812C13.0452 17.7812 12.8868 17.7196 12.7618 17.5873L7.32852 11.8406C6.44518 10.9063 6.44518 9.37271 7.32852 8.43842L12.7618 2.6917C13.0035 2.4361 13.4035 2.4361 13.6452 2.6917C13.8868 2.94731 13.8868 3.37038 13.6452 3.62599L8.21185 9.37271C7.81185 9.79578 7.81185 10.4833 8.21185 10.9063L13.6452 16.6531C13.8868 16.9087 13.8868 17.3317 13.6452 17.5873C13.5202 17.7107 13.3618 17.7812 13.2035 17.7812Z" fill="black" />
                                </svg>
                            </a> -->
                        </div>
                    </div>


                </div>
            </section>

            <?php
            // 1. بررسی اینکه آیا گواهینامه‌ای برای این دوره تنظیم شده است یا خیر
            $certificate_id = learndash_get_setting($course_id, 'certificate');

            if (!empty($certificate_id)) :
                $user_id = get_current_user_id();
                $cert_link = '';
                $is_completed = false;

                // 2. بررسی وضعیت پیشرفت کاربر و دریافت لینک گواهینامه
                if ($user_id) {
                    $cert_link = learndash_get_course_certificate_link($course_id, $user_id);
                    $is_completed = learndash_course_completed($user_id, $course_id);
                }

                // 3. دریافت تصویر شاخص گواهینامه (در صورت عدم وجود، تصویر پیش‌فرض لود می‌شود)
                $cert_thumbnail = get_the_post_thumbnail_url($certificate_id, 'medium');
                if (!$cert_thumbnail) {
                    $cert_thumbnail = get_template_directory_uri() . '/assets/img/single-page/sample-certificate.png';
                }
            ?>
                <section class="certificate-section">
                    <h2 class="section-title">گواهینامه</h2>
                    <div class="card certificate-card">
                        <div class="certificate-image">
                            <img src="<?php echo esc_url($cert_thumbnail); ?>" alt="گواهینامه دوره">
                        </div>
                        <div class="certificate-description">

                            <?php if (!empty($cert_link)) : ?>
                                <!-- حالت اول: کاربر دوره را تمام کرده و گواهینامه صادر شده است -->
                                <p class="certificate-text">
                                    تبریک! شما این دوره را با موفقیت به پایان رسانده‌اید و گواهینامه شما صادر شده است. هم‌اکنون می‌توانید آن را دریافت کنید.
                                </p>
                                <div class="certificate-actions">
                                    <a href="<?php echo esc_url($cert_link); ?>" target="_blank" class="btn-submit" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">
                                        مشاهده و دریافت گواهینامه
                                    </a>
                                    <a href="<?php echo esc_url($cert_link); ?>" target="_blank" class="btn btn-outline icon-btn" title="دانلود گواهینامه" style="display:inline-flex; align-items:center; justify-content:center;">
                                        <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M0.575019 3.20192C0.190985 2.84535 0 2.40399 0 1.87885C0 1.35371 0.192006 0.892939 0.575019 0.535355C0.959055 0.178791 1.4534 0 2.05907 0C2.66475 0 3.13969 0.178791 3.5227 0.535355C3.90673 0.891917 4.09772 1.34043 4.09772 1.87885C4.09772 2.41727 3.90572 2.84535 3.5227 3.20192C3.13867 3.55848 2.65146 3.73727 2.05907 3.73727C1.46668 3.73727 0.959055 3.55848 0.575019 3.20192ZM3.77498 5.24731V16.1792H0.321722V5.24731H3.77498Z" fill="#C9C9C9" />
                                            <path d="M15.2706 6.32708C16.0233 7.14441 16.3991 8.26622 16.3991 9.69452V15.986H13.1195V10.1379C13.1195 9.41764 12.9326 8.85775 12.5598 8.4593C12.187 8.06085 11.6845 7.86061 11.0553 7.86061C10.4262 7.86061 9.92364 8.05983 9.55084 8.4593C9.17804 8.85775 8.99113 9.41764 8.99113 10.1379V15.986H5.69214V5.21653H8.99113V6.64482C9.32513 6.16872 9.77557 5.79276 10.3414 5.51588C10.9073 5.23901 11.5436 5.10107 12.2514 5.10107C13.5117 5.10107 14.5188 5.50974 15.2706 6.32606V6.32708Z" fill="#C9C9C9" />
                                        </svg>
                                    </a>
                                </div>

                            <?php else : ?>
                                <!-- حالت دوم: کاربر هنوز دوره را تمام نکرده یا مهمان است -->
                                <p class="certificate-text">
                                    برای دریافت گواهینامه پس از تکمیل مشاهده دوره و رسیدن درصد پیشرفت به ۱۰۰٪، (و انجام آزمون در صورت وجود) گواهینامه برای شما صادر خواهد شد.
                                </p>
                                <div class="certificate-actions">
                                    <button class="btn-submit" disabled style="opacity: 0.6; cursor: not-allowed;">نیازمند تکمیل دوره</button>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php
            // 1. دریافت دسته‌بندی‌های دوره فعلی برای پیدا کردن دوره‌های مشابه
            $current_course_terms = wp_get_post_terms($course_id, 'ld_course_category', array('fields' => 'ids'));

            // 2. تنظیمات کوئری برای دریافت 4 دوره دیگر
            $args = array(
                'post_type'      => 'sfwd-courses',
                'post_status'    => 'publish',
                'posts_per_page' => 4,
                'post__not_in'   => array($course_id), // دوره فعلی را از لیست حذف کن
            );

            // اگر دوره فعلی دسته‌بندی داشت، دوره‌های هم‌دسته را بیاور
            // if (!empty($current_course_terms) && !is_wp_error($current_course_terms)) {
            //     $args['tax_query'] = array(
            //         array(
            //             'taxonomy' => 'ld_course_category',
            //             'field'    => 'id',
            //             'terms'    => $current_course_terms,
            //         ),
            //     );
            // }

            $related_courses = new WP_Query($args);

            if ($related_courses->have_posts()) :
            ?>
                <section class="related-course-section">
                    <h2 class="section-title">دوره های مرتبط</h2>
                    <!-- اضافه شدن کلاس‌های owl-carousel برای اسلایدر -->
                    <div class="courses-grid related-courses-carousel owl-carousel owl-theme">

                        <?php
                        while ($related_courses->have_posts()) : $related_courses->the_post();
                            $rel_course_id = get_the_ID();

                            // دریافت تصویر شاخص
                            $rel_image = get_the_post_thumbnail_url($rel_course_id, 'medium');
                            if (!$rel_image) {
                                $rel_image = get_template_directory_uri() . '/assets/img/front-page/Screenshot.webp'; // تصویر پیش‌فرض
                            }

                            // دریافت نام مدرس
                            $rel_author_id = get_post_field('post_author', $rel_course_id);
                            $rel_author_name = get_the_author_meta('display_name', $rel_author_id);

                            // دریافت قیمت از تنظیمات لرن‌دش
                            $rel_course_meta = get_post_meta($rel_course_id, '_sfwd-courses', true);
                            $rel_price = isset($rel_course_meta['sfwd-courses_course_price']) ? $rel_course_meta['sfwd-courses_course_price'] : '';

                            // دسته‌بندی‌ها
                            $rel_categories = get_the_terms($rel_course_id, 'ld_course_category');
                        ?>

                            <a href="<?php the_permalink(); ?>">
                                <div class="course-card-pro">
                                    <div class="card-image-box">
                                        <img src="<?php echo esc_url($rel_image); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                                    </div>
                                    <div class="card-body">
                                        <h3 class="card-title"><?php the_title(); ?></h3>
                                        <p class="card-teacher"><?php echo esc_html($rel_author_name); ?></p>

                                        <div class="card-tags">
                                            <?php
                                            if ($rel_categories && !is_wp_error($rel_categories)) {
                                                $tag_colors = ['tag-orange', 'tag-purple']; // رنگ‌های متناوب
                                                $color_index = 0;
                                                // نمایش نهایتا 3 دسته بندی
                                                $display_cats = array_slice($rel_categories, 0, 3);
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
                                            <?php if (empty($rel_price) || $rel_price === 'free' || $rel_price === 'رایگان') : ?>
                                                <div class="price-current">رایگان</div>
                                            <?php else : ?>
                                                <div class="price-current"><?php echo number_format((float)$rel_price); ?> <span>تومان</span></div>
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
                </section>
            <?php endif; ?>

            <?php if (!empty($faqs)) : ?>
                <section id="faq" class="faq-section">
                    <h2 class="section-title">سوالات متداول</h2>
                    <div class="faq-list">
                        <?php foreach ($faqs as $faq) : ?>
                            <details class="faq-item">
                                <summary class="faq-question">
                                    <?php echo esc_html($faq['question']); ?>
                                    <span class="faq-icon">
                                    </span>
                                </summary>
                                <p><?php echo nl2br(esc_html($faq['answer'])); ?></p>
                            </details>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

        </div>

        <aside class="course-sidebar-content">
            <div class="sticky-sidebar-card">

                <div class="sidebar-media-preview">
                    <div class="preview-hover-overlay">
                        <?php if (has_post_thumbnail()) : ?>
                            <?php the_post_thumbnail('medium'); ?>
                        <?php else: ?>
                            <img src="<?= get_template_directory_uri(); ?>/assets/img/single-page/single-course.png" />
                        <?php endif; ?>
                        <svg width="52" height="52" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M25.9511 51.1524C39.8695 51.1524 51.1524 39.8694 51.1524 25.9512C51.1524 12.033 39.8695 0.75 25.9511 0.75C12.033 0.75 0.75 12.033 0.75 25.9512C0.75 39.8694 12.033 51.1524 25.9511 51.1524Z" stroke="white" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M17.8125 26.5298V22.3212C17.8125 17.0795 21.517 14.9374 26.0532 17.5583L29.7074 19.6752L33.3616 21.792C37.8978 24.4129 37.8978 28.6971 33.3616 31.3181L29.7074 33.435L26.0532 35.5519C21.517 38.1728 17.8125 36.0307 17.8125 30.7888V26.5298Z" stroke="white" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                </div>

                <?php
                // ==========================================
                // بررسی وضعیت کاربر و اطلاعات قیمت دوره
                // ==========================================
                $user_id = get_current_user_id();
                $course_id = get_the_ID();

                // بررسی دسترسی کاربر به این دوره (خریداری کرده، رایگان باز کرده یا ادمین است)
                $has_access = sfwd_lms_has_access($course_id, $user_id);

                // استخراج متاهای قیمت دوره از لرن‌دش
                $course_meta = get_post_meta($course_id, '_sfwd-courses', true);
                $course_price_type = isset($course_meta['sfwd-courses_course_price_type']) ? $course_meta['sfwd-courses_course_price_type'] : 'open';
                $course_price = isset($course_meta['sfwd-courses_course_price']) ? $course_meta['sfwd-courses_course_price'] : '';

                // ----------------------------------------------------
                // حالت اول: کاربر در دوره شرکت کرده است (دارای دسترسی)
                // ----------------------------------------------------
                if ($has_access) :

                    // دریافت میزان پیشرفت کاربر به صورت آرایه
                    $course_progress = learndash_course_progress(array(
                        'user_id'   => $user_id,
                        'course_id' => $course_id,
                        'array'     => true
                    ));
                    $percentage = isset($course_progress['percentage']) ? $course_progress['percentage'] : 0;
                    $completed  = isset($course_progress['completed']) ? $course_progress['completed'] : 0;
                    $total      = isset($course_progress['total']) ? $course_progress['total'] : 0;
                ?>
                    <span class="show-progress-box" data-click="course-progress-box">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M10.0002 13.2807L5.65355 8.93404C5.14022 8.4207 5.14022 7.5807 5.65355 7.06737L10.0002 2.7207" stroke="#333333" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </span>
                    <div class="course-progress-box">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px;">
                            <span>میزان پیشرفت شما:</span>
                            <span class="progress-percentage-text" style="font-weight: bold; color: #0A3762;"><?php echo $percentage; ?>%</span>
                        </div>
                        <div style="background: #e9ecef; border-radius: 10px; height: 10px; width: 100%; overflow: hidden;">
                            <div class="progress-bar-fill" style="background: #0A3762; height: 100%; width: <?php echo $percentage; ?>%; border-radius: 10px; transition: width 0.6s ease-in-out;"></div>
                        </div>
                        <div class="progress-steps-text" style="margin-top: 8px; font-size: 12px; color: #666; text-align: left;">
                            <?php echo $completed; ?> از <?php echo $total; ?> مرحله تکمیل شده
                        </div>
                    </div>

                    <?php
                    // روش کاملاً امن برای دریافت لینک (جلوگیری از خطای Fatal)
                    $resume_link = '#content-list'; // پیش‌فرض: اسکرول به لیست دروس

                    // استفاده از تابع ایمن در صورتی که در نسخه لرن‌دش شما وجود داشته باشد
                    if (function_exists('learndash_course_get_resume_step_url')) {
                        $ld_resume = learndash_course_get_resume_step_url($course_id, $user_id);
                        if (!empty($ld_resume)) {
                            $resume_link = $ld_resume;
                        }
                    }
                    ?>
                    <a href="#content-list" id="btn-content-list" class="btn-submit">
                        <?php echo ($percentage == 0) ? 'شروع یادگیری' : (($percentage == 100) ? 'مرور دوره' : 'ادامه یادگیری'); ?>
                    </a>

                <?php
                // ----------------------------------------------------
                // حالت دوم: کاربر در دوره شرکت نکرده است
                // ----------------------------------------------------
                else :
                ?>
                    <h2 class="sidebar-cta-title"><?php the_title(); ?></h2>

                    <div class="sidebar-pricing-block">
                        <div class="current-sale-price">
                            <?php if ($course_price_type === 'free' || empty($course_price)) : ?>
                                <span class="price-amount">رایگان</span>
                            <?php else : ?>
                                <span class="price-amount"><?php echo number_format((float)$course_price); ?></span>
                                <span class="price-currency">تومان</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php
                    // بررسی اینکه آیا کاربر لاگین نیست و دوره توسط ووکامرس مدیریت نمیشود (closed نیست)
                    if (! is_user_logged_in() && $course_price_type !== 'closed') :
                        // تولید لینک لاگین همراه با ریدایرکت به همین صفحه
                        $login_url = wp_login_url(get_permalink());
                    ?>
                        <a id="start_course" href="<?php echo esc_url($login_url); ?>" class="btn-submit">
                            برای ثبت‌نام ابتدا وارد شوید
                        </a>
                    <?php else: ?>
                        <div class="ld-payment-buttons">
                            <?php echo learndash_payment_buttons($post); ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>

                <?php
                // ==========================================
                // بخش اطلاعات دوره (Course Materials)
                // ==========================================
                $materials_enabled = learndash_get_setting($course_id, 'course_materials_enabled');
                $materials_content = learndash_get_setting($course_id, 'course_materials');

                if (empty($materials_content)) {
                    $ld_settings = get_post_meta($course_id, '_sfwd-courses', true);
                    if (is_array($ld_settings)) {
                        $materials_enabled = isset($ld_settings['sfwd-courses_course_materials_enabled']) ? $ld_settings['sfwd-courses_course_materials_enabled'] : '';
                        $materials_content = isset($ld_settings['sfwd-courses_course_materials']) ? $ld_settings['sfwd-courses_course_materials'] : '';
                    }
                }

                if ($materials_enabled === 'on' && !empty($materials_content)) :
                    $features_list = array();

                    if (preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $materials_content, $matches)) {
                        foreach ($matches[1] as $match) {
                            $clean_text = trim(wp_strip_all_tags($match));
                            if (!empty($clean_text)) {
                                $features_list[] = $clean_text;
                            }
                        }
                    } else {
                        $clean_content = wp_strip_all_tags(str_replace(array('<br>', '<br/>', '<br />', '</p>'), "\n", $materials_content));
                        $lines = explode("\n", $clean_content);
                        foreach ($lines as $line) {
                            $clean_text = trim($line);
                            if (!empty($clean_text)) {
                                $features_list[] = $clean_text;
                            }
                        }
                    }

                    if (!empty($features_list)) :
                ?>
                        <div class="sidebar-features-list">
                            <span class="sidebar-features-list-title">
                                اطلاعات دوره
                            </span>

                            <?php foreach ($features_list as $index => $feature) : ?>
                                <div class="sidebar-feature-row">
                                    <span class="feat-label">
                                        <?php echo ($index + 1) . '- ' . esc_html($feature); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>

                        </div>
                <?php
                    endif;
                endif;
                ?>
            </div>
        </aside>
    </div>
</main>

<?php get_footer(); ?>
<div class="cta-btn-buy">
    <!-- دکمه خرید یا ثبت‌نام داینامیک لرن‌دش -->
    <div class="mobile-buy-btn-wrapper">
        <?php echo learndash_payment_buttons($post); ?>
    </div>

    <!-- قیمت داینامیک -->
    <div class="current-sale-price">
        <?php if (empty($course_price) || $course_price === 'free') : ?>
            <span class="price-amount">رایگان</span>
        <?php else : ?>
            <span class="price-amount"><?php echo number_format((float)$course_price); ?></span>
            <span class="price-currency">تومان</span>
        <?php endif; ?>
    </div>
</div>