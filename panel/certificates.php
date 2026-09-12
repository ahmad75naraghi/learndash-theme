<?php
/* Template Name: Panel - Cerificates */

if (! is_user_logged_in()) {
    // ریدایرکت به صفحه لاگین
    wp_safe_redirect(add_query_arg('redirect_to', rawurlencode(home_url('/panel/certificates')), home_url('/login')));
    exit;
}

$current_user_id = get_current_user_id();

// دریافت لیست دوره‌هایی که کاربر در آن‌ها ثبت‌نام کرده
$enrolled_courses = function_exists('learndash_user_get_enrolled_courses') ? learndash_user_get_enrolled_courses($current_user_id) : array();
$user_certificates = array();

// بررسی دوره‌ها برای استخراج گواهینامه‌های دریافت شده
if ( !empty($enrolled_courses) ) {
    foreach ( $enrolled_courses as $course_id ) {
        
        // دریافت لینک گواهینامه (اگر کاربر دوره را تمام نکرده باشد یا دوره گواهینامه نداشته باشد، این مقدار خالی خواهد بود)
        $cert_link = function_exists('learndash_get_course_certificate_link') ? learndash_get_course_certificate_link($course_id, $current_user_id) : '';
        
        if ( !empty($cert_link) ) {
            $author_id = get_post_field( 'post_author', $course_id );
            
            $user_certificates[] = array(
                'course_id' => $course_id,
                'type'      => 'course',
                'title'     => get_the_title($course_id),
                'author'    => get_the_author_meta('display_name', $author_id),
                'link'      => $cert_link,
            );
        }
    }
}

/* گواهینامه‌های آزمون‌ها (Quiz certificates) — از سوابق آزمون کاربر در لرن‌دش */
if ( function_exists('learndash_get_certificate_link') ) {
    $quiz_attempts = get_user_meta( $current_user_id, '_sfwd-quizzes', true );
    $seen_quiz     = array();
    if ( is_array($quiz_attempts) ) {
        // آخرین تلاش هر آزمون اول بررسی شود
        usort( $quiz_attempts, function ($a, $b) { return (int) ($b['time'] ?? 0) <=> (int) ($a['time'] ?? 0); } );
        foreach ( $quiz_attempts as $attempt ) {
            $quiz_id = isset($attempt['quiz']) ? (int) $attempt['quiz'] : 0;
            if ( ! $quiz_id || isset($seen_quiz[$quiz_id]) ) { continue; }
            if ( empty($attempt['pass']) ) { continue; }
            $cert_post = function_exists('learndash_get_setting') ? (int) learndash_get_setting( $quiz_id, 'certificate' ) : 0;
            if ( ! $cert_post ) { continue; }
            $cert = learndash_get_certificate_link( $quiz_id, $current_user_id );
            if ( empty($cert) ) { continue; }
            $seen_quiz[$quiz_id] = true;
            $course_id = isset($attempt['course']) ? (int) $attempt['course'] : ( function_exists('learndash_get_course_id') ? (int) learndash_get_course_id($quiz_id) : 0 );
            $user_certificates[] = array(
                'course_id' => $course_id,
                'quiz_id'   => $quiz_id,
                'type'      => 'quiz',
                'title'     => get_the_title($quiz_id),
                'author'    => $course_id ? get_the_title($course_id) : get_the_author_meta( 'display_name', (int) get_post_field('post_author', $quiz_id) ),
                'link'      => $cert,
                'date'      => ! empty($attempt['time']) ? (int) $attempt['time'] : 0,
                'score'     => isset($attempt['percentage']) ? (float) $attempt['percentage'] : null,
            );
        }
    }
}

get_template_part('template-parts/panel/shell', 'open', array('ee_panel_current' => 'certificates', 'ee_panel_title' => 'گواهینامه‌ها')); ?>
        <div>

            <h2 class="section-title">گواهینامه ها</h2>

            <div class="certificates-container">
                
                <?php if ( !empty($user_certificates) ) : ?>
                    <?php foreach ( $user_certificates as $cert ) : 
                        
                        // ساخت لینک هوشمند برای افزودن گواهینامه به لینکدین کاربر
                        $linkedin_url = 'https://www.linkedin.com/profile/add?startTask=CERTIFICATION_NAME&name=' . urlencode( 'گواهینامه ' . $cert['title'] ) . '&certUrl=' . urlencode( $cert['link'] );
                    ?>
                        <div class="card">
                            <div class="certificate-thumb">
                                <img src="<?= get_template_directory_uri() . '/assets/img/panel/certificate-thumb.png' ?>" />
                            </div>
                            <div class="card-content">
                                <div>
                                    <span class="ee-cert-type <?php echo 'quiz' === $cert['type'] ? 'is-quiz' : 'is-course'; ?>"><?php echo 'quiz' === $cert['type'] ? 'گواهی آزمون' : 'گواهی دوره'; ?></span>
                                    <div class="card-title"><?php echo esc_html( $cert['title'] ); ?></div>
                                    <div class="card-subtitle"><?php echo esc_html( $cert['author'] ); ?>
                                        <?php if ( 'quiz' === $cert['type'] && ! empty($cert['date']) ) : ?>
                                            · <?php echo esc_html( function_exists('evented_format_jalali') ? evented_format_jalali( $cert['date'], 'j F Y' ) : date_i18n( 'Y/m/d', $cert['date'] ) ); ?>
                                        <?php endif; ?>
                                        <?php if ( 'quiz' === $cert['type'] && null !== $cert['score'] ) : ?>
                                            · نمره <?php echo esc_html( round( $cert['score'] ) ); ?>٪
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-actions">
                                    <!-- تغییر button به a برای لینک شدن به فایل گواهینامه (معمولا PDF) -->
                                    <a href="<?php echo esc_url( $cert['link'] ); ?>" target="_blank" class="btn-download" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
                                        دریافت گواهینامه
                                    </a>
                                    
                                    <a href="<?php echo esc_url( $linkedin_url ); ?>" target="_blank" class="btn-linkedin" title="افزودن به لینکدین">
                                        <svg width="17" height="17" viewBox="0 0 17 17" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M0.575019 3.20192C0.190985 2.84535 0 2.40399 0 1.87885C0 1.35371 0.192006 0.892939 0.575019 0.535355C0.959055 0.178791 1.4534 0 2.05907 0C2.66475 0 3.13969 0.178791 3.5227 0.535355C3.90673 0.891917 4.09772 1.34043 4.09772 1.87885C4.09772 2.41727 3.90572 2.84535 3.5227 3.20192C3.13867 3.55848 2.65146 3.73727 2.05907 3.73727C1.46668 3.73727 0.959055 3.55848 0.575019 3.20192ZM3.77498 5.24731V16.1792H0.321722V5.24731H3.77498Z" fill="#6A40FF"/>
                                            <path d="M15.2708 6.32708C16.0236 7.14441 16.3994 8.26622 16.3994 9.69452V15.986H13.1198V10.1379C13.1198 9.41764 12.9329 8.85775 12.5601 8.4593C12.1873 8.06085 11.6848 7.86061 11.0556 7.86061C10.4264 7.86061 9.92389 8.05983 9.55109 8.4593C9.17829 8.85775 8.99138 9.41764 8.99138 10.1379V15.986H5.69238V5.21653H8.99138V6.64482C9.32537 6.16872 9.77581 5.79276 10.3417 5.51588C10.9075 5.23901 11.5438 5.10107 12.2516 5.10107C13.512 5.10107 14.519 5.50974 15.2708 6.32606V6.32708Z" fill="#6A40FF"/>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="no-exist-notice">
                        <div>
                            <img src="<?= get_template_directory_uri() . '/assets/img/panel/certificate-sample.png' ?>" />
                        </div>
                        <p>
                            هنوز دوره ای شرکت نکردی!!
                        </p>
                        <p>
                            با تکمیل هر دوره و قبولی در آزمون، گواهینامهٔ آن همین‌جا قابل دریافت است.
                        </p>

                        <a class="btn-primary" href="<?php echo esc_url(get_post_type_archive_link('sfwd-courses') ?: home_url('/')); ?>">
                            مشاهده دوره ها
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    

<?php get_template_part('template-parts/panel/shell', 'close'); ?>
