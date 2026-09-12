<?php
/* Template Name: Panel - Dashboard */

if (! is_user_logged_in()) {
    wp_safe_redirect(add_query_arg('redirect_to', rawurlencode(home_url('/panel')), home_url('/login')));
    exit;
}

$current_user_id = get_current_user_id();

$enrolled_courses = function_exists('learndash_user_get_enrolled_courses') ? learndash_user_get_enrolled_courses($current_user_id) : array();
$courses_count = count($enrolled_courses);

// جدول تراکنش‌ها — $wpdb در scope قالب در دسترس نیست، پس global می‌شود.
global $wpdb;
$payments_table_name = $wpdb->prefix . 'evented_transactions';
$transactions_count = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM $payments_table_name WHERE user_id = %d",
    $current_user_id
) );


get_template_part('template-parts/panel/shell', 'open', array('ee_panel_current' => 'dashboard', 'ee_panel_title' => 'پیشخوان')); ?>

        <!-- Top Stats -->
        <div class="top-stats">
            <div class="stat-item">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18.3333 12.5001V7.50008C18.3333 3.33341 16.6666 1.66675 12.5 1.66675H7.49996C3.33329 1.66675 1.66663 3.33341 1.66663 7.50008V12.5001C1.66663 16.6667 3.33329 18.3334 7.49996 18.3334H12.5C16.6666 18.3334 18.3333 16.6667 18.3333 12.5001Z" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2.09998 5.92505H17.9" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7.09998 1.7583V5.8083" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M12.9 1.7583V5.4333" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M8.125 12.0416V11.0416C8.125 9.75827 9.03333 9.23327 10.1417 9.87493L11.0083 10.3749L11.875 10.8749C12.9833 11.5166 12.9833 12.5666 11.875 13.2083L11.0083 13.7083L10.1417 14.2083C9.03333 14.8499 8.125 14.3249 8.125 13.0416V12.0416V12.0416Z" stroke="#400BFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                دوره ها ( <?php echo esc_html( $courses_count ); ?> )
            </div>
            <div class="divider"></div>
            <div class="stat-item">
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3.2749 13.2315L13.2332 3.27319" stroke="#400BFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M9.25098 15.2307L10.251 14.2307" stroke="#400BFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M11.4941 12.99L13.4858 10.9983" stroke="#400BFF" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3.0009 8.53258L8.53423 2.99925C10.3009 1.23258 11.1842 1.22425 12.9342 2.97425L17.0259 7.06591C18.7759 8.81591 18.7676 9.69925 17.0009 11.4659L11.4676 16.9992C9.7009 18.7659 8.81756 18.7742 7.06756 17.0242L2.9759 12.9326C1.2259 11.1826 1.2259 10.3076 3.0009 8.53258Z" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M1.6665 18.3313H18.3332" stroke="#400BFF" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                تراکنش ها ( <?php echo esc_html( $transactions_count ); ?>  )
            </div>
        </div>

        <!-- Current Courses Section -->
        <div>
            <h2 class="section-title">دوره های جاری</h2>

            <div class="course-grid">
                <!-- Course Card (Repeated 3 times) -->
                <?php 
                if ( !empty($enrolled_courses) ) : 
                    foreach ( $enrolled_courses as $course_id ) : 
                        
                        // متغیرهای دوره
                        $course_title = get_the_title( $course_id );
                        $course_link = get_permalink( $course_id );
                        $author_id = get_post_field( 'post_author', $course_id );
                        $course_author = get_the_author_meta( 'display_name', $author_id );
                        
                        // دریافت تصویر شاخص
                        $course_thumbnail = get_the_post_thumbnail_url( $course_id, 'medium' );
                        if ( ! $course_thumbnail ) {
                            $course_thumbnail = 'https://via.placeholder.com/300x200?text=No+Image'; // مسیر تصویر جایگزین در صورت نداشتن تصویر شاخص
                        }

                        // دریافت وضعیت و قیمت دوره لرن‌دش
                        $meta = get_post_meta( $course_id, '_sfwd-courses', true );
                        $price_type = isset($meta['sfwd-courses_course_price_type']) ? $meta['sfwd-courses_course_price_type'] : 'free';
                        $price = isset($meta['sfwd-courses_course_price']) ? $meta['sfwd-courses_course_price'] : '';
                        $price_text = ( $price_type === 'free' || empty($price) ) ? 'رایگان' : $price . ' تومان';

                        // دریافت تکسونومی‌های دسته‌بندی لرن‌دش
                        $categories = get_the_terms( $course_id, 'ld_course_category' );
                ?>
                    <div class="course-card">
                        <!-- تگ a برای کلیک‌پذیر کردن کل کارت (در صورت نیاز استایل را در CSS تنظیم کنید) -->
                        <a href="<?php echo esc_url($course_link); ?>" style="text-decoration: none; color: inherit; display: block;">
                            <div class="card-header">
                                <div class="image-placeholder">
                                    <img src="<?php echo esc_url($course_thumbnail); ?>" />
                                </div>
                            </div>
                            <div class="card-body">
                                <h3 class="card-title"><?php echo esc_html($course_title); ?></h3>
                                <p class="card-author"><?php echo esc_html($course_author); ?></p>
                                
                                <div class="card-tags">
                                    <?php if ( !empty($categories) && !is_wp_error($categories) ) : ?>
                                        <?php foreach ( $categories as $cat ) : ?>
                                            <span class="cat-badge"><?php echo esc_html($cat->name); ?></span>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <span class="cat-badge">عمومی</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-price"><?php echo esc_html($price_text); ?></div>
                            </div>
                        </a>
                    </div>
                <?php 
                    endforeach; 
                else : 
                ?>
                    <div class="no-exist-notice" style="grid-column: 1 / -1; padding: 20px; text-align: center; color: #666;">
                        <p>
                            هنوز دوره ای شرکت نکردی!!
                        </p>
                        <p>
                            از فهرست دوره‌ها یکی را انتخاب کن تا مسیر یادگیری‌ات از همین‌جا شروع شود.
                        </p>
                        <a class="btn-primary" href="<?php echo esc_url(get_post_type_archive_link('sfwd-courses') ?: home_url('/')); ?>">
                            مشاهده دوره ها
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    

<?php get_template_part('template-parts/panel/shell', 'close'); ?>
