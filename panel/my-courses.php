<?php
/* Template Name: Panel - My Courses */

if (! is_user_logged_in()) {
    wp_redirect(add_query_arg('redirect_to', home_url('/panel/my-courses'), wp_login_url()));
    exit;
}

$user_id = get_current_user_id();

/**
 * -------------------------------------------------------
 * دوره‌های ثبت‌نام‌شده کاربر (LearnDash)
 * -------------------------------------------------------
 */
$enrolled_course_ids = function_exists('learndash_user_get_enrolled_courses')
    ? learndash_user_get_enrolled_courses($user_id, array(), true)
    : array();

// اگر کاربر هیچ دوره‌ای نداشت، آرایه خالی برمی‌گردد نه false
if (! is_array($enrolled_course_ids)) {
    $enrolled_course_ids = array();
}

/**
 * تابع کمکی: گرفتن دسته‌بندی‌های یک دوره
 */
function evented_get_course_categories($course_id)
{
    $terms = get_the_terms($course_id, 'ld_course_category');
    if (empty($terms) || is_wp_error($terms)) {
        return array();
    }
    return wp_list_pluck($terms, 'name');
}

/**
 * تابع کمکی: نام استاد دوره
 */
function evented_get_course_instructor($course_id)
{
    $author_id = get_post_field('post_author', $course_id);
    $name      = get_the_author_meta('display_name', $author_id);
    return $name ? $name : 'نامشخص';
}

/**
 * تابع کمکی: قیمت دوره (رایگان / مبلغ)
 */
function evented_get_course_price_label($course_id)
{
    $price_type = learndash_get_course_meta_setting($course_id, 'course_price_type');

    if ($price_type === 'free' || empty($price_type)) {
        return 'رایگان';
    }

    $price = learndash_get_course_meta_setting($course_id, 'course_price');
    if (empty($price)) {
        return 'رایگان';
    }

    return number_format((float) $price) . ' تومان';
}

/**
 * تابع کمکی: تصویر شاخص دوره (در صورت نبود، بازگشت خالی تا از CSS پیش‌فرض استفاده شود)
 */
function evented_get_course_thumbnail($course_id)
{
    $thumb = get_the_post_thumbnail_url($course_id, 'medium');
    return $thumb ? $thumb : '';
}

/**
 * تابع کمکی: تبدیل اعداد انگلیسی به فارسی (برای درصد پیشرفت)
 */
function evented_to_persian_digits($number)
{
    $en = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
    $fa = array('۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹');
    return str_replace($en, $fa, (string) $number);
}

get_header(); ?>
<div class="container">

    <!-- Sidebar -->
    <?php include_once 'sidebar.php'; ?>
    <!-- Main Content -->
    <main class="main-content">

        <!-- Search Bar -->
        <div class="search-bar">
            <input type="text" id="my-courses-search" placeholder="جستجو دوره ها">
            <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M7.92037 15.8407C3.55451 15.8407 0 12.2862 0 7.92037C0 3.55451 3.55451 0 7.92037 0C12.2862 0 15.8407 3.55451 15.8407 7.92037C15.8407 12.2862 12.2862 15.8407 7.92037 15.8407ZM7.92037 1.15908C4.18814 1.15908 1.15908 4.19586 1.15908 7.92037C1.15908 11.6449 4.18814 14.6817 7.92037 14.6817C11.6526 14.6817 14.6817 11.6449 14.6817 7.92037C14.6817 4.19586 11.6526 1.15908 7.92037 1.15908Z" fill="#444444"/>
                <path d="M16.0342 16.6134C15.8874 16.6134 15.7405 16.5593 15.6246 16.4434L14.0792 14.8979C13.8551 14.6738 13.8551 14.3029 14.0792 14.0788C14.3033 13.8547 14.6742 13.8547 14.8983 14.0788L16.4437 15.6243C16.6678 15.8484 16.6678 16.2193 16.4437 16.4434C16.3278 16.5593 16.181 16.6134 16.0342 16.6134Z" fill="#444444"/>
            </svg>
        </div>

        <!-- My Courses Section -->
        <div>
            <h2 class="section-title">دوره های من</h2>

            <div class="horizontal-grid">

                <?php if (empty($enrolled_course_ids)) : ?>

                    <p>شما هنوز در هیچ دوره‌ای ثبت‌نام نکرده‌اید.</p>

                <?php else :
                    foreach ($enrolled_course_ids as $course_id) :

                        $course_title      = get_the_title($course_id);
                        $course_link       = get_permalink($course_id);
                        $course_image      = evented_get_course_thumbnail($course_id);
                        $course_instructor = evented_get_course_instructor($course_id);
                        $course_categories = evented_get_course_categories($course_id);

                        // درصد پیشرفت
                        $progress = learndash_course_progress(
                            array(
                                'user_id'   => $user_id,
                                'course_id' => $course_id,
                                'array'     => true,
                            )
                        );
                        $percentage = isset($progress['percentage']) ? (int) $progress['percentage'] : 0;

                        // آیا دوره تکمیل شده؟
                        $is_complete = learndash_course_completed($user_id, $course_id);

                        // آیا این دوره گواهینامه دارد؟
                        $certificate_link    = learndash_get_course_certificate_link($course_id, $user_id);
                        $has_certificate     = ! empty($certificate_link);

                        ?>

                        <!-- Horizontal Card -->
                        <div class="horizontal-card" data-course-title="<?php echo esc_attr($course_title); ?>">
                            <div class="horizontal-card-top">
                                <div class="horizontal-card-image"<?php echo $course_image ? ' style="background-image:url(\'' . esc_url($course_image) . '\');"' : ''; ?>>
                                </div>
                                <div class="horizontal-card-info">
                                    <h3 class="card-title"><?php echo esc_html($course_title); ?></h3>
                                    <p class="card-author"><?php echo esc_html($course_instructor); ?></p>
                                    <div class="card-tags">
                                        <?php if (! empty($course_categories)) :
                                            foreach ($course_categories as $cat) : ?>
                                                <span class="cat-badge"><?php echo esc_html($cat); ?></span>
                                            <?php endforeach;
                                        else : ?>
                                            <span class="cat-badge">بدون دسته‌بندی</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="progress-wrapper">
                                    <div class="progress-track">
                                        <div class="progress-fill" style="width: <?php echo esc_attr($percentage); ?>%"></div>
                                    </div>
                                    <span class="progress-percent"><?php echo evented_to_persian_digits($percentage) . '٪'; ?></span>
                                </div>
                                <p class="progress-desc">
                                    <?php
                                    if (! $has_certificate) {
                                        echo 'برای این دوره گواهینامه صادر نمیشود';
                                    } elseif ($is_complete) {
                                        echo 'شما میتوانید گواهینامه دریافت کنید';
                                    } else {
                                        echo 'برای دریافت گواهینامه پس از تکمیل مشاهده دوره و درصد پیشرفت ۱۰۰ دوره آزمون مربوطه را انجام دهید تا گواهینامه برای شما صادر شود';
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="card-actions">
                                <?php if ($has_certificate && $is_complete) : ?>
                                    <div class="final-course-actions">
                                        <a href="<?php echo esc_url($certificate_link); ?>" target="_blank" class="get-certificate">آزمون و دریافت گواهینامه</a>
                                    </div>
                                <?php endif; ?>
                                <div class="course-actions">
                                    <a href="<?php echo esc_url($course_link . '#reviews'); ?>" class="submit-comment-btn">ثبت نظر</a>
                                    <a href="<?php echo esc_url($course_link); ?>" class="start-course-btn">ادامه دوره</a>
                                </div>
                            </div>
                        </div>

                    <?php endforeach;
                endif; ?>

            </div>
        </div>

        <!-- Suggested Courses Section -->
        <div class="suggested-courses-section mt-4">
            <h2 class="section-title">دوره های پیشنهادی</h2>

            <div class="course-grid">
                <?php
                $suggested_query = new WP_Query(
                    array(
                        'post_type'      => 'sfwd-courses',
                        'posts_per_page' => 3,
                        'post__not_in'   => $enrolled_course_ids,
                        'orderby'        => 'rand',
                    )
                );

                if ($suggested_query->have_posts()) :
                    while ($suggested_query->have_posts()) : $suggested_query->the_post();

                        $s_course_id     = get_the_ID();
                        $s_image         = evented_get_course_thumbnail($s_course_id);
                        $s_instructor    = evented_get_course_instructor($s_course_id);
                        $s_categories    = evented_get_course_categories($s_course_id);
                        $s_price_label   = evented_get_course_price_label($s_course_id);
                        ?>

                        <a href="<?php the_permalink(); ?>" class="course-card">
                            <div class="card-header"<?php echo $s_image ? ' style="background-image:url(\'' . esc_url($s_image) . '\');"' : ''; ?>>
                                <?php if (! $s_image) : ?>
                                    <div class="image-placeholder">تصویر دوره</div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h3 class="card-title"><?php the_title(); ?></h3>
                                <p class="card-author"><?php echo esc_html($s_instructor); ?></p>
                                <div class="card-tags">
                                    <?php if (! empty($s_categories)) :
                                        foreach ($s_categories as $cat) : ?>
                                            <span class="cat-badge"><?php echo esc_html($cat); ?></span>
                                        <?php endforeach;
                                    else : ?>
                                        <span class="cat-badge">بدون دسته‌بندی</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-price"><?php echo esc_html($s_price_label); ?></div>
                            </div>
                        </a>

                    <?php endwhile;
                    wp_reset_postdata();
                else : ?>
                    <p>در حال حاضر دوره پیشنهادی موجود نیست.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script>
// جستجوی ساده سمت کلاینت روی «دوره های من»
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('my-courses-search');
    if (!input) return;
    input.addEventListener('input', function () {
        var term = input.value.trim();
        document.querySelectorAll('.horizontal-card').forEach(function (card) {
            var title = card.getAttribute('data-course-title') || '';
            card.style.display = title.indexOf(term) !== -1 ? '' : 'none';
        });
    });
});
</script>

<?php get_footer(); ?>