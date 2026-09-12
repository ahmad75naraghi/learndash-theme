<?php
// ==========================================
// پردازش فرم ثبت نظر دوره با ایجکس
// ==========================================
add_action('wp_ajax_submit_course_review', 'handle_submit_course_review');
add_action('wp_ajax_nopriv_submit_course_review', 'handle_submit_course_review');

function handle_submit_course_review() {
    // بررسی امنیت (Nonce)
    check_ajax_referer('course_review_nonce', 'security');

    $course_id = intval($_POST['course_id']);
    $rating    = intval($_POST['rating']);
    $content   = sanitize_textarea_field($_POST['content']);

    // بررسی خالی نبودن مقادیر
    if (!$course_id || !$rating || empty($content)) {
        wp_send_json_error('لطفا تمام فیلدها و امتیاز را وارد کنید.');
    }
    if ($rating < 1 || $rating > 5 || 'sfwd-courses' !== get_post_type($course_id)) {
        wp_send_json_error('امتیاز یا دوره نامعتبر است.');
    }

    $user = wp_get_current_user();
    if (!$user->exists()) {
        wp_send_json_error('برای ثبت نظر ابتدا باید وارد سایت شوید.');
    }

    // ساخت داده‌های کامنت
    $comment_data = array(
        'comment_post_ID'      => $course_id,
        'comment_author'       => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_content'      => $content,
        'user_id'              => $user->ID,
        'comment_approved'     => 0, // مقدار 0 یعنی نیاز به تایید مدیر دارد
    );

    /** امکان وتو (مثلاً جلوگیری از امتیاز تکراری در inc/reviews.php). */
    $comment_data = apply_filters('evented_review_before_insert', $comment_data, $course_id, $rating);
    if (is_wp_error($comment_data)) {
        wp_send_json_error($comment_data->get_error_message());
    }

    // ذخیره کامنت در دیتابیس وردپرس
    $comment_id = wp_insert_comment($comment_data);

    if ($comment_id) {
        // ذخیره امتیاز ستاره‌ها به عنوان متای کامنت
        add_comment_meta($comment_id, 'review_rating', $rating);
        wp_send_json_success('نظر شما با موفقیت ثبت شد و پس از تایید مدیریت نمایش داده می‌شود.');
    } else {
        wp_send_json_error('متاسفانه خطایی در ثبت نظر رخ داد.');
    }
}

// ==========================================
// پردازش تکمیل اتوماتیک درس با ایجکس و برگشت درصد پیشرفت
// ==========================================
add_action('wp_ajax_custom_mark_lesson_complete', 'handle_custom_mark_lesson_complete');
function handle_custom_mark_lesson_complete() {
    $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    
    if ( ! check_ajax_referer('mark_complete_nonce_' . $lesson_id, 'security', false) ) {
         wp_send_json_error('خطای امنیتی');
    }
    
    $user_id = get_current_user_id();
    
    if ($lesson_id && $user_id && $course_id) {
        
        // ۱. دور زدن قفل ویدیوی لرن‌دش (شبیه‌سازی تماشای کامل ویدیو)
        if ( function_exists('learndash_video_complete_for_step') ) {
            learndash_video_complete_for_step( $course_id, $lesson_id, $user_id );
        } else {
            update_user_meta( $user_id, 'learndash_video_complete_' . $lesson_id, time() );
        }
        
        // ۲. دور زدن قفل زمانی (اگر درس تایمر داشته باشد)
        update_user_meta( $user_id, 'learndash_timer_complete_' . $lesson_id, time() );

        // ۳. درخواست تیک خوردن رسمی از هسته لرن‌دش
        if (function_exists('learndash_process_mark_complete')) {
            learndash_process_mark_complete($user_id, $lesson_id, false, $course_id);
        }

        // ۴. اقدام اجباری: اگر لرن‌دش باز هم لج‌بازی کرد، متای پیشرفت کاربر را خودمان در دیتابیس می‌نویسیم!
        $course_progress = get_user_meta( $user_id, '_sfwd-course_progress', true );
        if ( empty( $course_progress ) ) $course_progress = array();
        if ( ! isset( $course_progress[$course_id] ) ) $course_progress[$course_id] = array( 'lessons' => array(), 'topics'  => array() );
        
        if ( ! isset( $course_progress[$course_id]['lessons'][$lesson_id] ) ) {
            $course_progress[$course_id]['lessons'][$lesson_id] = 1;
            update_user_meta( $user_id, '_sfwd-course_progress', $course_progress );
            
            if ( function_exists('learndash_update_user_activity') ) {
                learndash_update_user_activity( array(
                    'course_id' => $course_id, 'post_id' => $lesson_id, 'user_id' => $user_id,
                    'activity_type' => 'lesson', 'activity_action' => 'insert', 'activity_status' => true,
                    'activity_started' => time(), 'activity_completed' => time(),
                ) );
            }
        }

        // ۵. دریافت اطلاعات جدید پیشرفت برای ارسال به صفحه
        if (function_exists('learndash_course_progress')) {
            $progress_data = learndash_course_progress(array(
                'user_id'   => $user_id,
                'course_id' => $course_id,
                'array'     => true
            ));
            wp_send_json_success($progress_data);
        }
    }
    wp_send_json_error('خطا در سیستم پردازش');
}




// ==========================================
// پردازش دکمه علاقه‌مندی‌ها (Wishlist) با ایجکس
// ==========================================
add_action('wp_ajax_toggle_course_wishlist', 'handle_toggle_course_wishlist');
function handle_toggle_course_wishlist() {
    // بررسی امنیت ریکوئست
    check_ajax_referer('wishlist_nonce', 'security');

    $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    $user_id = get_current_user_id();

    if (!$course_id || !$user_id) {
        wp_send_json_error('درخواست نامعتبر');
    }

    // دریافت لیست فعلی از متای کاربر
    $fav_courses_str = get_user_meta($user_id, 'fav_courses', true);
    $fav_courses = $fav_courses_str ? explode(',', $fav_courses_str) : array();

    $status = '';
    
    // اگر دوره در لیست بود، آن را حذف کن، در غیر این صورت اضافه کن
    if (($key = array_search($course_id, $fav_courses)) !== false) {
        unset($fav_courses[$key]);
        $status = 'removed';
    } else {
        $fav_courses[] = $course_id;
        $status = 'added';
    }

    // تمیز کردن آرایه و تبدیل مجدد به رشته با کاما
    $fav_courses = array_unique(array_filter($fav_courses));
    update_user_meta($user_id, 'fav_courses', implode(',', $fav_courses));

    // ارسال موفقیت‌آمیز وضعیت جدید به فرانت‌اند
    wp_send_json_success(array('status' => $status));
}

// ذخیره اطلاعات پروفایل
add_action('wp_ajax_save_user_profile', 'handle_save_user_profile');
function handle_save_user_profile() {
    check_ajax_referer('profile_nonce_action', 'security');

    $user_id = get_current_user_id();
    if (!$user_id) wp_send_json_error('کاربر لاگین نیست');

    // لیست فیلدها و کلیدهای متا
    $fields = [
        'first_name_fa' => sanitize_text_field($_POST['first_name_fa']),
        'last_name_fa'  => sanitize_text_field($_POST['last_name_fa']),
        'first_name_en' => sanitize_text_field($_POST['first_name_en']),
        'last_name_en'  => sanitize_text_field($_POST['last_name_en']),
        'gender'        => sanitize_text_field($_POST['gender']),
        'birth_date'    => sanitize_text_field($_POST['birth_date']),
    ];

    foreach ($fields as $key => $value) {
        update_user_meta($user_id, $key, $value);
    }

    wp_send_json_success('اطلاعات با موفقیت ذخیره شد.');
}

// پردازش تغییرات تنظیمات حساب کاربری (ایمیل، موبایل، رمز عبور)
add_action('wp_ajax_save_account_settings', 'handle_save_account_settings');
function handle_save_account_settings() {
    check_ajax_referer('settings_nonce_action', 'security');

    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error('شما دسترسی ندارید.');
    }

    $userdata = array(
        'ID' => $user_id
    );

    // بررسی و تغییر ایمیل
    if (!empty($_POST['user_email'])) {
        $new_email = sanitize_email($_POST['user_email']);
        if (is_email($new_email) && !email_exists($new_email)) {
            $userdata['user_email'] = $new_email;
        } elseif (email_exists($new_email) && $new_email !== wp_get_current_user()->user_email) {
            wp_send_json_error('این ایمیل قبلاً توسط شخص دیگری ثبت شده است.');
        }
    }

    // بررسی و تغییر رمز عبور
    if (!empty($_POST['user_password']) && $_POST['user_password'] !== '..........') {
        $userdata['user_pass'] = sanitize_text_field($_POST['user_password']);
    }

    // آپدیت ایمیل و رمز عبور (در صورت تغییر)
    if (isset($userdata['user_email']) || isset($userdata['user_pass'])) {
        $user_id = wp_update_user($userdata);
        if (is_wp_error($user_id)) {
            wp_send_json_error($user_id->get_error_message());
        }
    }

    // بروزرسانی شماره موبایل (ذخیره در user_meta - کلید استاندارد ووکامرس)
    if (!empty($_POST['user_phone'])) {
        $new_phone = sanitize_text_field($_POST['user_phone']);
        update_user_meta($user_id, 'billing_phone', $new_phone);
    }

    wp_send_json_success('تغییرات با موفقیت ذخیره شد.');
}