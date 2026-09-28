<?php
/**
 * Template Name: Panel - Certificates
 * 
 * @package Theme
 */

declare(strict_types=1);

if (! is_user_logged_in()) {
    wp_safe_redirect(add_query_arg('redirect_to', rawurlencode(home_url('/panel/certificates')), wp_login_url()));
    exit;
}

$current_user_id   = get_current_user_id();
$user_certificates = [];

// 1. استخراج استاندارد گواهینامه‌های دوره‌ها
if (function_exists('learndash_user_get_enrolled_courses') && function_exists('learndash_get_course_certificate_link')) {
    $enrolled_courses = learndash_user_get_enrolled_courses($current_user_id);

    if (! empty($enrolled_courses) && is_array($enrolled_courses)) {
        foreach ($enrolled_courses as $course_id) {
            $course_id = (int) $course_id;

            // بازگردانی لینک استاندارد دانلود/نمایش گواهی دوره
            $cert_link = learndash_get_course_certificate_link($course_id, $current_user_id);

            if (empty($cert_link)) {
                continue;
            }

            // پاکسازی تگ‌های احتمالی HTML در صورتی که نسخه لرندش رشته <a> برگرداند
            if (str_starts_with($cert_link, '<a') && preg_match('/href=[\'"]([^\'"]+)[\'"]/', $cert_link, $matches)) {
                $cert_link = $matches[1];
            }

            $author_id = (int) get_post_field('post_author', $course_id);

            $user_certificates[] = [
                'id'        => $course_id,
                'course_id' => $course_id,
                'type'      => 'course',
                'title'     => get_the_title($course_id),
                'author'    => get_the_author_meta('display_name', $author_id),
                'link'      => esc_url_raw(html_entity_decode($cert_link)),
                'date'      => 0,
                'score'     => null,
            ];
        }
    }
}

// 2. استخراج استاندارد گواهینامه‌های آزمون‌ها
if (function_exists('learndash_get_certificate_link')) {
    $quiz_attempts = get_user_meta($current_user_id, '_sfwd-quizzes', true);
    $seen_quizzes  = [];

    if (is_array($quiz_attempts) && ! empty($quiz_attempts)) {
        // مرتب‌سازی آزمون‌ها از جدیدترین به قدیمی‌ترین
        usort($quiz_attempts, static function (array $a, array $b): int {
            return (int) ($b['time'] ?? 0) <=> (int) ($a['time'] ?? 0);
        });

        foreach ($quiz_attempts as $attempt) {
            $quiz_id = isset($attempt['quiz']) ? (int) $attempt['quiz'] : 0;

            if (! $quiz_id || isset($seen_quizzes[$quiz_id]) || empty($attempt['pass'])) {
                continue;
            }

            $has_cert = function_exists('learndash_get_setting') 
                ? (int) learndash_get_setting($quiz_id, 'certificate') 
                : (int) get_post_meta($quiz_id, 'certificate', true);

            if (! $has_cert) {
                continue;
            }

            // پارامتر سوم true جهت دریافت URL خام مستقیم به هسته LearnDash
            $cert_url = learndash_get_certificate_link($quiz_id, $current_user_id, true);

            if (empty($cert_url)) {
                continue;
            }

            // پاکسازی ساختار احتمالی تگ HTML
            if (str_starts_with($cert_url, '<a') && preg_match('/href=[\'"]([^\'"]+)[\'"]/', $cert_url, $matches)) {
                $cert_url = $matches[1];
            }

            // افزودن پارامتر زمان آزمون جهت اعتبارسنجی تمپلیت خروجی PDF
            $attempt_time = ! empty($attempt['time']) ? (int) $attempt['time'] : 0;
            if ($attempt_time > 0 && ! str_contains($cert_url, 'time=')) {
                $cert_url = add_query_arg('time', $attempt_time, $cert_url);
            }

            $seen_quizzes[$quiz_id] = true;
            $course_id = isset($attempt['course']) ? (int) $attempt['course'] : 0;

            if (! $course_id && function_exists('learndash_get_course_id')) {
                $course_id = (int) learndash_get_course_id($quiz_id);
            }

            $user_certificates[] = [
                'id'        => $quiz_id,
                'course_id' => $course_id,
                'quiz_id'   => $quiz_id,
                'type'      => 'quiz',
                'title'     => get_the_title($quiz_id),
                'author'    => $course_id ? get_the_title($course_id) : get_the_author_meta('display_name', (int) get_post_field('post_author', $quiz_id)),
                'link'      => esc_url_raw(html_entity_decode($cert_url)),
                'date'      => $attempt_time,
                'score'     => isset($attempt['percentage']) ? (float) $attempt['percentage'] : null,
            ];
        }
    }
}

get_template_part('template-parts/panel/shell', 'open', [
    'ee_panel_current' => 'certificates',
    'ee_panel_title'   => 'گواهینامه‌ها',
]);
?>

<div>
    <h2 class="section-title">گواهینامه‌ها</h2>
    <div class="certificates-container">

        <?php if (! empty($user_certificates)) : ?>
            <?php foreach ($user_certificates as $cert) : 
                // اصلاح URL لینکدین و رفع دابل‌انکودینگ
                $linkedin_url = add_query_arg([
                    'startTask' => 'CERTIFICATION_NAME',
                    'name'      => 'گواهینامه ' . $cert['title'],
                    'certUrl'   => $cert['link'],
                ], 'https://www.linkedin.com/profile/add');
            ?>
                <div class="card">
                    <div class="certificate-thumb">
                        <img src="<?= esc_url(get_template_directory_uri() . '/assets/img/panel/certificate-thumb.png'); ?>" alt="تصویر گواهینامه" />
                    </div>
                    <div class="card-content">
                        <div>
                            <span class="ee-cert-type <?= 'quiz' === $cert['type'] ? 'is-quiz' : 'is-course'; ?>">
                                <?= 'quiz' === $cert['type'] ? 'گواهی آزمون' : 'گواهی دوره'; ?>
                            </span>
                            <div class="card-title"><?= esc_html($cert['title']); ?></div>
                            <div class="card-subtitle">
                                <?= esc_html($cert['author']); ?>
                                <?php if ('quiz' === $cert['type'] && ! empty($cert['date'])) : ?>
                                    · <?= esc_html(function_exists('evented_format_jalali') ? evented_format_jalali($cert['date'], 'j F Y') : date_i18n('Y/m/d', $cert['date'])); ?>
                                <?php endif; ?>
                                <?php if ('quiz' === $cert['type'] && null !== $cert['score']) : ?>
                                    · نمره <?= esc_html((string) round($cert['score'])); ?>٪
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-actions">
                            <a href="<?= esc_url($cert['link']); ?>" target="_blank" rel="noopener noreferrer" class="btn-download" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                                دریافت گواهینامه
                            </a>

                            <a href="<?= esc_url($linkedin_url); ?>" target="_blank" rel="noopener noreferrer" class="btn-linkedin" title="افزودن به لینکدین">
                                <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M0.575019 3.20192C0.190985 2.84535 0 2.40399 0 1.87885C0 1.35371 0.192006 0.892939 0.575019 0.535355C0.959055 0.178791 1.4534 0 2.05907 0C2.66475 0 3.13969 0.178791 3.5227 0.535355C3.90673 0.891917 4.09772 1.34043 4.09772 1.87885C4.09772 2.41727 3.90572 2.84535 3.5227 3.20192C3.13867 3.55848 2.65146 3.73727 2.05907 3.73727C1.46668 3.73727 0.959055 3.55848 0.575019 3.20192ZM3.77498 5.24731V16.1792H0.321722V5.24731H3.77498Z" fill="#6A40FF" />
                                    <path d="M15.2708 6.32708C16.0236 7.14441 16.3994 8.26622 16.3994 9.69452V15.986H13.1198V10.1379C13.1198 9.41764 12.9329 8.85775 12.5601 8.4593C12.1873 8.06085 11.6848 7.86061 11.0556 7.86061C10.4264 7.86061 9.92389 8.05983 9.55109 8.4593C9.17829 8.85775 8.99138 9.41764 8.99138 10.1379V15.986H5.69238V5.21653H8.99138V6.64482C9.32537 6.16872 9.77581 5.79276 10.3417 5.51588C10.9075 5.23901 11.5438 5.10107 12.2516 5.10107C13.512 5.10107 14.519 5.50974 15.2708 6.32606V6.32708Z" fill="#6A40FF" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <div class="no-exist-notice">
                <div>
                    <img src="<?= esc_url(get_template_directory_uri() . '/assets/img/panel/certificate-sample.png'); ?>" alt="بدون گواهینامه" />
                </div>
                <p>هنوز دوره‌ای شرکت نکردی!!</p>
                <p>با تکمیل هر دوره و قبولی در آزمون، گواهینامهٔ آن همین‌جا قابل دریافت است.</p>
                <a class="btn-primary" href="<?= esc_url(get_post_type_archive_link('sfwd-courses') ?: home_url('/')); ?>">
                    مشاهده دوره‌ها
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php get_template_part('template-parts/panel/shell', 'close'); ?>